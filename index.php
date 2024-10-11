<?php

/*

STEPS:

1. Check for "token" file [/oauth/token]
	a. if > 56 minutes old, re-authenticate and save contents to file and variable
	b. else assign content of file to variable
2. Acquire all available calendar IDs and visibility [/calenars]
3. Acquire all available branch IDs, and whether admin only, and if public [/space/locations]
4. Loop [/space/utilization] once per branch/location to get spaces': names and ids
5. Loop [/events] at least once per calendar
6. [/space/bookings]
7. (NEEDED?) Loop [/space/categories] per location for: cID, public, admin_only

*/

/**
 * AVAILABLE GET QUERY STRING PARAMETERS AND THEIR IMPACT
 * ======================================================
 * reset (boolean: 1|0) - Attempt to remove the current SQLite database file and create a new one
 * days  (integer)      - Number of days (inclusive of the current day) to retrieve data; max 365
 */

require_once('config.php');

/**
 * NOTES ON EFFICIENT SQLITE USAGE IN PHP
 * // Source: https://gist.github.com/aalfiann/acc4fdbb4a141a356ef991f397a7701f
 * <?php
 * $db = new SQLite3('/my/sqlite/file.sqlite3');
 * $db->busyTimeout(5000);
 * // WAL mode has better control over concurrency.
 * // Source: https://www.sqlite.org/wal.html
 * $db->exec('PRAGMA main.cache_size=10000;PRAGMA main.locking_mode=EXCLUSIVE;PRAGMA main.synchronous=NORMAL;PRAGMA main.journal_mode=WAL;');
 *
 *
 * // Second Source: https://phiresky.github.io/blog/2020/sqlite-performance-tuning/
 * pragma synchronous = normal;
 * pragma temp_store = memory;
 * pragma mmap_size = 30000000000;
 */

// Set timezone
date_default_timezone_set($timezone_str);

// Fix prefix URL if needed
$cal_prefix = check_prefix($cal_prefix);

// Default checks
$new_database = false;

// SQLite database age
$db_old = false;
if (file_exists(DATABASE_FILE)) {
	$modified = filemtime(DATABASE_FILE);
	if (date('Ymd',$modified) != date('Ymd')) {
		$db_old = true;
	}
}

// Get bearer token
$token = get_authentication($cal_prefix, $client_id, $client_secret);
if (!$token) {
	die('Token invalid. Unable to continue.');
}

if ($db_old || (isset($_GET['reset']) && file_exists(DATABASE_FILE))) {
	if(!@unlink(realpath(DATABASE_FILE))) {
		die('Unable to reset DB. Check file permissions.');
	}
}

/*

1. Check if SQLite file exists, if not, create it
2. If the file didn't exist, grab ALL available data and store it
3. If the file did exist, check the requested data, retrieve it and if
	retrieved, clear that span of time from the DB and insert the updated data

*/

if (!file_exists(DATABASE_FILE)) {
	if (create_database()) {
		$new_database = true;
	}
} else {
	// Adjust total days if requested
	$total_days = (isset($_GET['days']) && intval($_GET['days']) >= 0) ? (int) $_GET['days'] : $total_days;
}
$db = new PDO('sqlite:' . DATABASE_FILE, '', '', PDO_OPTIONS);
$db->exec('PRAGMA journal_mode=wal');

// Get calendars
$calendars = get_calendars($cal_prefix, $token)->calendars;
if (!$calendars) {
	die('Calendars not found. Unable to continue.');
} else if ($new_database) {
	set_calendars($db, $calendars);
}

// calendars = array([n]->calid=n, [n]->name=s, [n]->url->public=s, [n]->visibility=(Public|Internal))
// SAVE: calid, name, public url, visibility
if ($new_database) {
	// Get locations (branches/libraries)
	$locations = get_space_locations($cal_prefix, $token);
	if (!$locations) {
		die('Locations not found. Unable to continue.');
	} else {
		set_space_locations($db, $locations);
	}

	// Get spaces per branch/location
	$spaces = [];
	foreach ($locations as $location) {
		$usage = get_space_utilization($cal_prefix, $token, $location->lid);
		if ($usage === false) {
			die('No space usage found; possible API failure?');
		}
		$spaces[$location->lid] = $usage;
	}
	if (empty($spaces)) {
		die('No spaces found. Unable to continue.');
	} else {
		set_space_utilization($db, $spaces);
	}

	// Get all categories and attributes
	$space_categories = get_categories($cal_prefix, $token, $locations);
	if ($space_categories === false) {
		die('No location categories found; possible API failure?');
	} else {
		set_categories($db, $space_categories);
	}
}
// Get all events per each calendar
$events = get_events($cal_prefix, $token, $calendars, $total_days);
if ($events) {
	if (!$new_database) {
		clear_event_dates($db, $total_days);
	}
	set_events($db, $events, $calendars);
}

// Get all space bookings (to determine non-event room/space usage)
$bookings = get_bookings($cal_prefix, $token, $total_days);
if ($bookings) {
	if (!$new_database) {
		clear_booking_dates($db, $total_days);
	}
	set_bookings($db, $bookings);
}

# EQUIPMENT BOOKINGS CURRENTLY DON'T MAP TO A SPACE OR EVENT IF THEY SHOULD.
# ... SKIPPING IMPLEMENTATION FOR NOW ...
// Get all equipment bookings
$equipment = get_equipment_bookings($cal_prefix, $token, $total_days);
if ($equipment) {
	if (!$new_database) {
		clear_equipment_dates($db, $total_days);
	}
	set_equipment_bookings($db, $equipment);
}
// die('<pre>'.print_r($equipment,true).'</pre>');


echo 'All gathered!';


// Retrieve all space categories
function get_categories(string $prefix, string $token, array $locations) {
	if (!$locations) return false;
	$category_ids = [];
	foreach ($locations as $location) {
		$category_ids[] = $location->lid;
	}
	$category_ids = implode(',', $category_ids);
	$result = call_api($prefix, $token, "/1.1/space/categories/{$category_ids}?admin_only=1&details=1");
	return $result;
}
function set_categories($db, $categories) {
	if (isset($categories) && count($categories) > 0) {
		$db->beginTransaction();
		foreach ($categories as $location) {
			$location_id = $location->lid;
			foreach ($location->categories as $category) {
				$vals = [];
				$vals['id']                   = (int) $category->cid;
				$vals['lid']                  = (int) $location_id;
				$vals['location_name']        = $location->name;
				$vals['name']                 = $category->name;
				$vals['formid']               = (int) $category->formid;
				$vals['public']               = (int) $category->public;
				$vals['admin_only']           = (int) $category->admin_only;
				$vals['terms_and_conditions'] = $category->termsAndConditions;
				$vals['description']          = $category->description;
				$vals['google']               = (int) $category->google;

				$keys        = array_keys($vals);
				$fields      = '`'.implode('`, `',$keys).'`';
				$placeholder = ':' . implode(', :', $keys);

				$sth = $db->prepare("INSERT INTO `space_categories` ({$fields}) VALUES ({$placeholder})");
				$sth->execute(array_values($vals));
				$arr = $sth->errorInfo();
			}
		}
		try {
			$db->commit();
		} catch (Exception $ex) {
			if ($db->inTransaction()) {
				$db->rollback();
			}
		}
	}
}

// Clear the data in the tables, but keep the schema
function truncate_table(string $table) {
	switch($table) {
		case 'calendars':
			truncate('calendars');
			break;
		case 'locations':
			truncate('locations');
			break;
		case 'events':
			truncate('events');
			break;
		case 'spaces';
			truncate('spaces');
			break;
		case 'bookings';
			truncate('bookings');
			break;
		case 'space_categories';
			truncate('space_categories');
			break;
		default:
			die('Invalid table name.');
	};
}
/*private*/ function truncate(string $table) {
	// Create the file and the database, and insert data into it ... and query it ... and print the results
	try {
		//open the database
		$db = new PDO('sqlite:'.DATABASE_FILE, '', '', PDO_OPTIONS);
		$db->exec('PRAGMA journal_mode=wal');
		$db->exec("DELETE FROM '{$table}'");
		$db = null;
	} catch  (PDOException $error) {
		die($error->getMessage());
	}
}
function clear_event_dates($db, $future_days) {
	clear_dates($db, 'events', $future_days);
}
function clear_booking_dates($db, $future_days) {
	clear_dates($db, 'bookings', $future_days);
}
function clear_equipment_dates($db, $future_days) {
	clear_dates($db, 'equipment', $future_days);
}
function clear_dates($db, $table, $future_days) {
	try {
		$end_date   = date('Y-m-d\T23:59:59P', strtotime("today +{$future_days} days"));
		$query_string = "DELETE FROM '{$table}' WHERE `end` < '{$end_date}'";
		$db->exec($query_string);
	} catch  (PDOException $error) {
		die($error->getMessage());
	}
}

// Retrieve the space bookings - this includes public bookings, and bookings associated to events
function get_bookings(string $prefix, string $token, int $days = 1) {
	$bookings = [];
	$page = 1;
	do {
		$result = get_all_bookings($prefix, $token, $days, $page);
		if ($result) {
			$bookings = array_merge($bookings, $result);
		}
		$page++;
	} while ($result && count($result) == 500);
	return $bookings ?: false;
}
/* private */ function get_all_bookings(string $prefix, string $token, int $days = 1, int $page = 1) {
	$include_remote = INCLUDE_REMOTE_BOOKINGS ? 1 : 0;
	$include_cancelled = INCLUDE_CANCELLED_BOOKINGS ? 1 : 0;
	$include_tentative = INCLUDE_TENTATIVE_BOOKINGS ? 1 : 0;
	$include_answers   = GET_FORM_ANSWERS           ? 1 : 0;
	$include_check_in  = GET_CHECKIN_STATUS         ? 1 : 0;

	$result = call_api($prefix, $token, "/1.1/space/bookings?formAnswers={$include_answers}&checkInStatus={$include_check_in}&include_tentative={$include_tentative}&include_cancel={$include_cancelled}&include_remote={$include_remote}&days={$days}&limit=500&page={$page}");
	return $result;
}
function set_bookings($db, $bookings) {
	if (isset($bookings) && count($bookings) > 0) {
		$db->beginTransaction();
		foreach ($bookings as $booking) {
			// Skip entering a cancelled booking into the database
			if (!INCLUDE_CANCELLED_BOOKINGS && isset($booking->cancelled)) continue;

			$vals = [];
			$vals['booking_id']      = $booking->bookId;
			$vals['id']              = (int) $booking->id;         //
			$vals['title']           = $booking->nickname ?? null; // $booking->event->title ?? $booking->nickname (?)
			$vals['eid']             = (int) $booking->eid;        // space ID
			$vals['cid']             = (int) $booking->cid;        // space category ID
			$vals['lid']             = (int) $booking->lid;        // location (library/branch) ID
			$vals['event_id']        = $booking->event            ? (int) $booking->event->id : null;
			$vals['seat_id']         = isset($booking->seat_id)   ? (int) $booking->seat_id   : null;
			$vals['branch']          = $booking->location_name;
			$vals['category']        = $booking->category_name;
			$vals['location']        = $booking->item_name;
			$vals['seat_name']       = isset($booking->seat_name) ? $booking->seat_name : null;
			$vals['start']           = strtotime($booking->fromDate);
			$vals['end']             = strtotime($booking->toDate);
			$vals['created']         = $booking->created;
			$vals['firstname']       = $booking->firstName;
			$vals['lastname']        = $booking->lastName;
			$vals['email']           = $booking->email;
			$vals['account']         = $booking->account;
			$vals['status']          = $booking->status;
			$vals['check_in_code']   = GET_CHECKIN_STATUS && isset($booking->check_in_code)   ? $booking->check_in_code   : null;
			$vals['check_in_status'] = GET_CHECKIN_STATUS && isset($booking->check_in_status) ? $booking->check_in_status : null;
			$vals['cancelled']       = $booking->cancelled ?? null;
			if (GET_FORM_ANSWERS && !$booking->event) {
				// Return and store only object values where the key matches a format of "q" followed by numbers
				$bookingArray = (array) $booking;
				$bookingArray = array_filter($bookingArray, function($v){
					return preg_match('/^q\d{1,}$/', $v);
				}, ARRAY_FILTER_USE_KEY);
				// Serialize the array into a string value
				$vals['form_answers'] = serialize($bookingArray);
			}

			$keys        = array_keys($vals);
			$fields      = implode(', ',$keys);
			$placeholder = ':' . implode(', :', $keys);

			$sth = $db->prepare("INSERT OR REPLACE INTO bookings ({$fields}) VALUES ({$placeholder})");
			$sth->execute(array_values($vals));
			$arr = $sth->errorInfo();
		}
		try {
			$db->commit();
		} catch (Exception $ex) {
			if ($db->inTransaction()) {
				$db->rollback();
			}
			die(''.$arr);
		}
	}
}

// Retrieve all equipment bookings
function get_equipment_bookings(string $prefix, string $token, int $days = 1) {
	$bookings = [];
	$page = 1;
	do {
		$result = get_all_equipment_bookings($prefix, $token, $days, $page);
		if ($result) {
			$bookings = array_merge($bookings, $result);
		}
		$page++;
	} while ($result && count($result) == 500);
	return $bookings ?: false;
}
/* private */ function get_all_equipment_bookings(string $prefix, string $token, int $days = 1, int $page = 1) {
	$include_cancelled = INCLUDE_CANCELLED_BOOKINGS ? 1 : 0;
	$include_tentative = INCLUDE_TENTATIVE_BOOKINGS ? 1 : 0;
	$include_answers   = GET_FORM_ANSWERS           ? 1 : 0;

	$result = call_api($prefix, $token, "/1.1/equipment/bookings?formAnswers={$include_answers}&include_tentative={$include_tentative}&include_cancel={$include_cancelled}days={$days}&limit=500&page={$page}");
	return $result;
}
function set_equipment_bookings($db, $equipment) {
	if (isset($equipment) && count($equipment) > 0) {
		$db->beginTransaction();
		foreach ($equipment as $booking) {
			// Skip entering a cancelled booking into the database
			if (!INCLUDE_CANCELLED_BOOKINGS && isset($booking->cancelled)) continue;

			$vals = [];
			$vals['booking_id']      = $booking->bookId;
			$vals['id']              = (int) $booking->id;         // unique ID for the specific record
			$vals['eid']             = (int) $booking->eid;        // space ID
			$vals['cid']             = (int) $booking->cid;        // space category ID
			$vals['lid']             = (int) $booking->lid;        // location (library/branch) ID
			$vals['start']           = strtotime($booking->fromDate);
			$vals['end']             = strtotime($booking->toDate);
			$vals['created']         = $booking->created;
			$vals['firstname']       = $booking->firstName;
			$vals['lastname']        = $booking->lastName;
			$vals['email']           = $booking->email;
			$vals['account']         = $booking->account;
			$vals['status']          = $booking->status;
			$vals['location_name']   = $booking->location_name;
			$vals['category_name']   = $booking->category_name;
			$vals['item_name']       = $booking->item_name;
			$vals['event_id']        = $booking->event ? (int) $booking->event->id : null;
			$vals['event_title']     = $booking->event ? $booking->event->title : null;
			$vals['barcode']         = isset($booking->check_in_status) ? $booking->check_in_status : null;
			$vals['cancelled']       = $booking->cancelled ?? null;
			if (GET_FORM_ANSWERS && !$booking->event) {
				// Return and store only object values where the key matches a format of "q" followed by numbers
				$bookingArray = (array) $booking;
				$bookingArray = array_filter($bookingArray, function($v){
					return preg_match('/^q\d{1,}$/', $v);
				}, ARRAY_FILTER_USE_KEY);
				// Serialize the array into a string value
				$vals['form_answers'] = serialize($bookingArray);
			}

			$keys        = array_keys($vals);
			$fields      = implode(', ',$keys);
			$placeholder = ':' . implode(', :', $keys);

			$sth = $db->prepare("INSERT OR REPLACE INTO equipment ({$fields}) VALUES ({$placeholder})");
			$sth->execute(array_values($vals));
			$arr = $sth->errorInfo();
		}
		try {
			$db->commit();
		} catch (Exception $ex) {
			if ($db->inTransaction()) {
				$db->rollback();
			}
			die(''.$arr);
		}
	}
}

// Retrieve all events based on criteria passed in by parameters
function get_events(string $prefix, string $token, array $calendar_list, int $days = 1) {
	$events = [];
	foreach ($calendar_list as $calendar) {
		$page = 1;
		do {
			// Get the first 500 events, continue checking until we don't return 500 events (last page)
			$result = get_events_from_calendar($prefix, $token, $calendar->calid, $days, $page)->events;
			if ($result) {
				$events = array_merge($events, $result);
			}
			$page++;
		} while ($result && count($result) == 500);
	}
	return $events ?: false;
}
/* private */ function get_events_from_calendar(string $prefix, string $token, int $cal_id, int $days = 1, $page = 1) {
	$result = call_api($prefix, $token, "/1.1/events?cal_id={$cal_id}&days={$days}&limit=500&event_note=1&internal_notes=1&page={$page}");
	return $result;
}
function set_events($db, $events, $calendars = null) {
	$lookup = [];
	if ($calendars) {
		foreach ($calendars as $calendar) {
			// Testing Internal/Private: Private = 1, Public = 0
			$lookup[$calendar->calid] = $calendar->visibility == 'Internal' ? 1 : 0;
		}
	}
	if (isset($events) && count($events) > 0) {
		$db->beginTransaction();
		foreach ($events as $event) {
			$vals = [];
			$vals['id'] = (int) $event->id;
			$vals['title'] = $event->title;
			$vals['allday'] = (int) $event->allday;
			$vals['multiday'] = date('Ymd', strtotime($event->end)) > date('Ymd', strtotime($event->start)) ? 1 : 0;
			$vals['private'] = array_key_exists($event->calendar->id, $lookup) ? $lookup[$event->calendar->id] : 0;
			$vals['start'] = strtotime($event->start);
			$vals['end'] = strtotime($event->end);
			$vals['setup'] = $event->setup_time;
			$vals['breakdown'] = $event->teardown_time;
			$vals['description'] = $event->description;
			$vals['more_info'] = $event->more_info;
			if (GET_EVENT_NOTES) {
				$vals['event_note'] = $event->event_note ?? null;
			}
			if (GET_INTERNAL_NOTES) {
				$vals['internal_notes'] = (isset($event->internal_noteS) && count($event->internal_notes)) ? serialize($event->internal_notes) : null;
			}
			$vals['url'] = $event->url->public;
			$vals['admin_url'] = $event->url->admin;
			if (isset($event->location) && is_array($event->location)) {
				// This might eventually become default
				$vals['location_id'] = implode(DB_STRING_DELIMITER, array_column($event->location, 'id'));
				$vals['location']    = implode(DB_STRING_DELIMITER, array_column($event->location, 'name'));
			} else {
				// As of development time this is the only valid if/else path
				$vals['location_id'] = isset($event->location->id)   ? $event->location->id   : null;
				$vals['location']    = isset($event->location->name) ? $event->location->name : null;
			}
			$vals['audience_id'] = (isset($event->audience) && count($event->audience)) ? implode(DB_STRING_DELIMITER, array_map(function ($a) { return $a->id; }, $event->audience)) : null;
			$vals['audience'] = (isset($event->audience) && count($event->audience)) ? implode(DB_STRING_DELIMITER, array_map(function ($a) { return $a->name; }, $event->audience)) : null;
			$vals['campus_id'] = (!empty($event->campus) && count($event->campus)) ? implode(DB_STRING_DELIMITER, array_map(function ($a) { return $a->id; }, $event->campus)) : null;
			$vals['campus'] = (!empty($event->campus) && count($event->campus)) ? implode(DB_STRING_DELIMITER, array_map(function ($a) { return $a->name; }, $event->campus)) : null;
			$vals['cat_id'] = (isset($event->category) && count($event->category)) ? implode(DB_STRING_DELIMITER, array_map(function ($a) { return $a->id; }, $event->category)) : null;
			$vals['category'] = (isset($event->category) && count($event->category)) ? implode(DB_STRING_DELIMITER, array_map(function ($a) { return $a->name; }, $event->category)) : null;
			$vals['owner_id'] = $event->owner->id;
			$vals['owner'] = $event->owner->name;
			$vals['presenter'] = isset($event->presenter) && !empty($event->presenter) ? $event->presenter : null;
			$vals['cal_id'] = $event->calendar->id;
			$vals['calendar'] = $event->calendar->name;
			$vals['color'] = $event->color;
			$vals['image'] = isset($event->featured_image) && !empty($event->featured_image) ? $event->featured_image : null;
			$vals['geo_id'] = isset($event->geolocation->{'place-id'}) && !empty($event->geolocation->{'place-id'}) ? $event->geolocation->{'place-id'} : null;
			$vals['geo_lat'] = isset($event->geolocation->latitude) && !empty($event->geolocation->latitude) ? $event->geolocation->latitude : null;
			$vals['geo_long'] = isset($event->geolocation->longitude) && !empty($event->geolocation->longitude) ? $event->geolocation->longitude : null;
			$vals['cost'] = $event->registration_cost;
			$vals['registration'] = (isset($event->registration) && $event->registration) ? 1 : 0;
			$vals['registration_form_id'] = $event->registration_form_id ?? null;
			if (isset($event->registration_series_linked)) {
				$vals['registration_linked'] = $event->registration_series_linked ? 1 : 0;
			} else {
				// Explicit in code, leaving this out would do the same thing in the database though
				$vals['registration_linked'] = null;
			}
			$vals['registration_type'] = $vals['registration'] ? 1 : null;
			if ($vals['registration_type']) {
				if ($event->seats && $event->seats == $event->online_seats) {
					$vals['registration_type'] = 'online';
				} else if ($event->seats && $event->seats == $event->physical_seats) {
					$vals['registration_type'] = 'in-person';
				} else {
					$vals['registration_type'] = 'hybrid';
				}
			}
			$vals['registration_open'] = isset($event->has_registration_opened) && isset($event->has_registration_closed) ? (int) ($event->has_registration_opened && !$event->has_registration_closed) : 0;
			$vals['registration_closed'] = isset($event->has_registration_opened) && isset($event->has_registration_closed) ? (int) $event->has_registration_closed : 0;
			$vals['registration_cost'] = $event->registration_cost;
			$vals['attendance_physical'] = isset($event->attendance->in_person) && $event->attendance->in_person ? $event->attendance->in_person : null;
			$vals['attendance_online'] = isset($event->attendance->online) && $event->attendance->online ? $event->attendance->online : null;
			$vals['seats'] = $event->seats;
			$vals['seats_taken'] = $event->seats && $event->seats_taken ? $event->seats_taken : null;
			$vals['physical_seats'] = $event->seats && $event->physical_seats ? $event->physical_seats : null;
			$vals['physical_seats_taken'] = $event->seats && $event->physical_seats_taken ? $event->physical_seats_taken : null;
			$vals['online_seats'] = $event->seats && $event->online_seats ? $event->online_seats : null;
			$vals['online_seats_taken'] = $event->seats && $event->online_seats_taken ? $event->online_seats_taken : null;
			$vals['zoom_email'] = isset($event->zoom_email) ? $event->zoom_email: null;
			$vals['online_user_id'] = isset($event->online_user_id) ? $event->online_user_id : null;
			$vals['online_meeting_id'] = isset($event->online_meeting_id) ? $event->online_meeting_id : null;
			$vals['online_host_url'] = isset($event->online_host_url) ? $event->online_host_url : null;
			$vals['online_join_url'] = isset($event->online_join_url) ? $event->online_join_url : null;
			$vals['online_join_password'] = isset($event->online_join_password) ? $event->online_join_password : null;
			$vals['online_provider'] = isset($event->online_provider) ? $event->online_provider : null;
			$vals['wait_list'] = isset($event->wait_list) && $event->wait_list ? 1 : 0;
			$vals['future_dates'] = count($event->future_dates) ? serialize($event->future_dates) : null;

			$keys        = array_keys($vals);
			$fields      = implode(', ', $keys);
			$placeholder = ':' . implode(', :', $keys);

			$sth = $db->prepare("INSERT OR REPLACE INTO `events` ({$fields}) VALUES ({$placeholder})");
			$sth->execute(array_values($vals));
			$arr = $sth->errorInfo();
		}
		try {
			$db->commit();
		} catch (Exception $ex) {
			if ($db->inTransaction()) {
				$db->rollback();
			}
		}
	}
}

// Retrieve detailed data from a particular space
function get_space_utilization($prefix, $token, $id) {
	$result = call_api($prefix, $token, "/api/1.1/space/utilization/{$id}");
	return $result;
}
function set_space_utilization($db, $spaces_data) {
	if (isset($spaces_data) && count($spaces_data) > 0) {
		$db->beginTransaction();
		// Not storing location summary data...should we?

		foreach ($spaces_data as $location_id => $obj) {
			foreach ($obj->zones as $zone) {
				// Not storing zone info, but if we were...
				$zone_id = $zone->id;
				$zone_name = $zone->name;

				// Loop through the spaces to get the info
				foreach ($zone->spaces as $space) {
					$vals = [];
					$vals['id']               = (int) $space->id;
					$vals['name']             = $space->name;
					$vals['bookableAsWhole']  = (int) $space->bookableAsWhole;
					$vals['currentOccupancy'] = (int) $space->currentOccupancy;
					$vals['currentCapacity']  = (int) $space->currentCapacity;
					$vals['maxCapacity']      = (int) $space->maxCapacity;
					$vals['lid']              = (int) $location_id;

					$keys        = array_keys($vals);
					$fields      = '`'.implode('`, `',$keys).'`';
					$placeholder = ':' . implode(', :', $keys);

					$sth = $db->prepare("INSERT INTO `spaces` ({$fields}) VALUES ({$placeholder})");
					$sth->execute(array_values($vals));
					$arr = $sth->errorInfo();
				}
			}
		}
		try {
			$db->commit();
		} catch (Exception $ex) {
			if ($db->inTransaction()) {
				$db->rollback();
			}
		}
	}
}

// Retrieve primary locations where spaces can exist (ex: Main Library, Branch Library, etc.)
function get_space_locations($prefix, $token) {
	$result = call_api($prefix, $token, "/1.1/space/locations?admin_only=1");
	return $result;
}
function set_space_locations($db, $locs) {
	if (isset($locs) && count($locs) > 0) {
		$db->beginTransaction();
		foreach ($locs as $loc) {
			$vals = [];
			$vals['id']         = (int) $loc->lid;
			$vals['name']       = $loc->name;
			$vals['public']     = (int) $loc->public;
			$vals['admin_only'] = (int) $loc->admin_only;

			$keys        = array_keys($vals);
			$fields      = '`'.implode('`, `',$keys).'`';
			$placeholder = ':' . implode(', :', $keys);

			$sth = $db->prepare("INSERT INTO `locations` ({$fields}) VALUES ({$placeholder})");
			$sth->execute(array_values($vals));
			$arr = $sth->errorInfo();
		}
		try {
			$db->commit();
		} catch (Exception $ex) {
			if ($db->inTransaction()) {
				$db->rollback();
			}
		}
	}
}

// Retrieve all of the available calendars and their details
function get_calendars($prefix, $token) {
	$result = call_api($prefix, $token, "/1.1/calendars");
	return $result;
}
function set_calendars($db, $cals) {
	if (isset($cals) && count($cals) > 0) {
		$db->beginTransaction();
		foreach ($cals as $cal) {
			$vals = [];
			$vals['id']         = (int) $cal->calid;
			$vals['name']       = $cal->name;
			$vals['url']        = $cal->url->public;
			$vals['owner']      = $cal->owner->name;
			$vals['visibility'] = $cal->visibility;

			$keys        = array_keys($vals);
			$fields      = '`'.implode('`, `',$keys).'`';
			$placeholder = ':' . implode(', :', $keys);

			$sth = $db->prepare("INSERT INTO `calendars` ({$fields}) VALUES ({$placeholder})");
			$sth->execute(array_values($vals));
			$arr = $sth->errorInfo();
		}
		try {
			$db->commit();
		} catch (Exception $ex) {
			if ($db->inTransaction()) {
				$db->rollback();
			}
		}
	}
}

// Retrieve a valid "bearer" auth token
function get_authentication($prefix, int $id = null, string $secret = null) {
	if (file_exists(TOKEN_FILE) && (time() < (filemtime(TOKEN_FILE) + (TOKEN_LIFETIME-1)*60))) {
		// file exists and is less than TOKEN_LIFETIME minutes old
		return file_get_contents(TOKEN_FILE);
	} else {
		// either file does not exist, or exists but is invalid
		$url = $prefix . '/1.1/oauth/token';
		$grant_type = 'client_credentials';

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => "",
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => "POST",
			CURLOPT_POSTFIELDS => "-----011000010111000001101001\r\nContent-Disposition: form-data; name=\"grant_type\"\r\n\r\n{$grant_type}\r\n-----011000010111000001101001\r\nContent-Disposition: form-data; name=\"client_id\"\r\n\r\n{$id}\r\n-----011000010111000001101001\r\nContent-Disposition: form-data; name=\"client_secret\"\r\n\r\n{$secret}\r\n-----011000010111000001101001--",
			CURLOPT_HTTPHEADER => array(
				"cache-control: no-cache",
				"content-type: multipart/form-data; boundary=---011000010111000001101001"
			),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);

		if ($err) {
			#die("Error: {$err}");
			return false;
		}
		$response = json_decode($response);

		if (!$response || (json_last_error() !== JSON_ERROR_NONE)) return false;
		file_put_contents(TOKEN_FILE, $response->access_token);
		return $response->access_token;
	}
}

// Initiate a cURL request to retrieve and return a JSON payload, or return false
function call_api(string $prefix, string $token, string $endpoint = null) {
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => "{$prefix}{$endpoint}",
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_ENCODING => "",
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_TIMEOUT => 30,
		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_HTTPHEADER => array(
			"authorization: Bearer {$token}",
			"cache-control: no-cache"
		),
	));

	$result = curl_exec($curl);
	$err = curl_error($curl);
	curl_close($curl);

	if ($err) {
		# echo "cURL Error #:" . $err;
		return false;
	} else {
		# echo '<pre>' . print_r(json_decode($events), true) . '</pre>';
		$result = json_decode($result);
	}
	return $result ?: false;
}

// Validate the supplied value is a proper URL
function check_prefix($url) {
	if (filter_var($url, FILTER_VALIDATE_URL) === false) {
		die('The supplied prefix is not a valid URL. Please check your configuration values.');
	}
	if ($url[-1] == '/') {
		return trim($url, '/');
	}
	return $url;
}

// Create our SQLite database file if it doesn't already exist
function create_database() {
	$create = file_get_contents('schema.sql');
	$db = new PDO('sqlite:'.DATABASE_FILE, '', '', PDO_OPTIONS);
	$db->exec('PRAGMA journal_mode=wal');
	try {
		// Create the tables
		$db->exec($create);
		echo '<p>Database created.</p>';
	} catch (PDOException $error) {
		die('DB Creation Error: ' . $error->getMessage());
	}
	$db = null;
	return true;
}