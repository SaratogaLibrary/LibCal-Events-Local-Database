<?php

	require_once('../../config.php');

	date_default_timezone_set($timezone_str);

	function prepareAudience(&$audience, $key) {
		// Remove undesirable whitespace from the delimited audience string
		$audience = trim(urldecode($audience));

		// Set the SQL match fragment
		if (is_numeric($audience)) {
			$audience = 'events.audience_id LIKE "%'.$audience.'%"';
		} else {
			$audience = 'events.audience LIKE "%'.$audience.'%"';
		}
	}
	function prepareCategories(&$category, $key) {
		// Remove undesirable whitespace from the delimited category string
		$category = trim(urldecode($category));

		// Set the SQL match fragment
		if (is_numeric($category)) {
			$category = 'events.cat_id LIKE "%'.$category.'%"';
		} else {
			$category = 'events.category LIKE "%'.$category.'%"';
		}
	}
	// Even if an invalid date value is passed to this (based on our source data timeframe),
	// a timestamp will always be returned
	function prepareDate($date, $start = true) {
		// Remove undesirable whitespace from the provided string
		$date = trim(urldecode($date));

		// Test if the string is a number (timestamp?), or if it has textual components
		// Number might be shortened date string, also test for that (ex: 20230813)
		if (is_numeric($date) && $date > strtotime(intval(date('Y') . date('m') . date('d')))) {
			// The time date is *likely* formatted properly, do nothing
		} else {
			// We suspect it's a string-based format; convert it to a UNIX timestamp
			$clock = $start ? '00:00.00' : '23:59.59';
			$date = strtotime("{$date} {$clock}");
		}
		return $date;
	}

	function getEvents($days = 1, $all_events = false, $categories = array(), $audience = array(), $start = null, $end = null, $online = null) {
		array_walk($audience,   'prepareAudience');
		array_walk($categories, 'prepareCategories');
		$internal = !$all_events ? ' AND events.private != 1 ' : '';
		$auds     = count($audience)   ? ' AND '.implode(' OR ', $audience)   : '';
		$cats     = count($categories) ? ' AND '.implode(' OR ', $categories) : '';
		if ($online == true) {
			// only return events that are hybrid or online-only
			$online = ' AND events.online_seats IS NOT NULL ';
		} else if ($online === false) {
			// prevent displaying any online events
			$online = ' AND (events.online_seats IS NULL OR (events.online_seats IS NOT NULL AND events.physical_seats IS NOT NULL)) ';
		}

		// Default query if no $start or $end
		$query = 'SELECT * FROM events WHERE events.start < "'.(strtotime('+'.$days.' days 00:00:00')-1).'" '. $internal . $cats . $auds . $online . ' ORDER BY events.start ASC, events.title ASC';
		if (isset($start) && isset($end)) {
			// Base the timeframe on $start and either $days or $end, depending
			$days_calc = $days - 1;
			$days_end = strtotime(date('Ymd 23:59.59', $start) . "+ {$days_calc} days");
			// If the "days" value provided is shorter than the "end" date,
			// then force the end date to be based on "days"
			$end = $days_end < $end ? $days_end : $end;
			$query = 'SELECT * FROM events WHERE events.start BETWEEN "'.$start.'" AND "'.$end.'" '. $internal . $cats . $auds . ' ORDER BY events.start ASC, events.title ASC';
		} else if (isset($start)) {
			// Base the timeframe on $start and $days
			$end = (strtotime(date('Ymd', $start) . " +{$days} days")-1);
			$query = 'SELECT * FROM events WHERE events.start > "'.$start.'" AND events.start < "'.$end.'" '. $internal . $cats . $auds . ' ORDER BY events.start ASC, events.title ASC';
		} else if (isset($end)) {
			// Base the timeframe on $end and $days
			$start = strtotime(date('Ymd', $end+1) . " -{$days} days");
			$query = 'SELECT * FROM events WHERE events.start > "'.$start.'" AND events.start < "'.$end.'" '. $internal . $cats . $auds . ' ORDER BY events.start ASC, events.title ASC';
		}
		try {
			//open the database
			$db = new PDO('sqlite:' . DATABASE_FILE);
			$result = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

			// close the database connection
			$db = NULL;

			return $result;
		}
		catch (PDOException $e) {
			if ($e->getCode() === 14) {
				return false;
			}
		}
	}

	// Space reservations that were not created by library staff
	function getMeetings($days = 1) {
		try {
			//open the database
			$db = new PDO('sqlite:' . DATABASE_FILE);
			$result = $db->query('SELECT * FROM bookings WHERE bookings.account != "admin" AND bookings.start BETWEEN "'.strtotime('00:00:00').'" AND "'.(strtotime('+'.$days.' days 00:00:00')-1).'" AND bookings.status LIKE "%Approved%" ORDER BY bookings.start ASC, bookings.title ASC')->fetchAll(PDO::FETCH_ASSOC);

			// close the database connection
			$db = NULL;

			return $result;
		}
		catch (PDOException $e) {
			if ($e->getCode() === 14) {
				return false;
			}
		}
	}

	function getSpaceSchedule($space_id, $days = 1) {
		try {
			//open the database
			$db = new PDO('sqlite:' . DATABASE_FILE);

			$space = $db->query("SELECT * from spaces where id = {$space_id}")->fetchAll(PDO::FETCH_ASSOC);

			$day_end  = strtotime("today + {$days} days") - 1;
			$events   = $db->query("SELECT * FROM events e WHERE e.location_id = {$space_id} /*AND e.private != 1*/ AND e.start < {$day_end} ORDER BY e.start ASC")->fetchAll(PDO::FETCH_ASSOC);
			$bookings = $db->query("SELECT * FROM bookings b WHERE b.eid = {$space_id} AND b.account != 'admin' AND b.status LIKE '%Approved%' AND b.start < {$day_end} ORDER BY b.start ASC")->fetchAll(PDO::FETCH_ASSOC);

			// close the database connection
			$db = NULL;
		}
		catch (PDOException $e) {
			if ($e->getCode() === 14) {
				return false;
			}
		}

		// Preface the data with its type, then add it to the result array with an index of its UNIX timestamp start time
		$result = $space;
		foreach ($events as $event) {
			$result[$event['start']] = array_merge(['type' => 'event'], $event);
		}
		foreach ($bookings as $booking) {
			$result[$booking['start']] = array_merge(['type' => 'booking'], $booking);
		}

		// Force the room info (index 0) to top, and sort the room usage by start time, then return the array
		ksort($result);
		// die('<pre>'.print_r($result,1).'</pre>');
		return $result;
	}

	// Set default values
	$days = 1;
	$public_and_private = false;
	$audience = array();
	$categories = array();
	$events = true; // this checked by default
	$space = false;
	$meetings = false;
	$end = null;
	$start = null;
	$online = null;

	// Get overrides, if any
	if (isset($_GET['days']) && $_GET['days'] > 0) {
		$days = (int) $_GET['days'];
	}
	if (isset($_GET['space']) && intval($_GET['space']) > 0) {
		$space = filter_var($_GET['space'], FILTER_VALIDATE_INT);
		$events = false;
	} else {
		if (isset($_GET['all'])) {
			$public_and_private = filter_var($_GET['all'], FILTER_VALIDATE_BOOLEAN);
		}
		if (isset($_GET['audience'])) {
			$audience = explode(',', $_GET['audience']);
		}
		if (isset($_GET['categories'])) {
			$categories = explode(',', $_GET['categories']);
		}
		if (isset($_GET['meetings'])) {
			$meetings = filter_var($_GET['meetings'], FILTER_VALIDATE_BOOLEAN);
		}
		if (isset($_GET['events'])) {
			$events = filter_var($_GET['events'], FILTER_VALIDATE_BOOLEAN);
		}
		if (isset($_GET['online'])) {
			$online = filter_var($_GET['online'], FILTER_VALIDATE_BOOLEAN);
		}
	}
	if (isset($_GET['start'])) {
		$start = prepareDate($_GET['start']);
	}
	if (isset($_GET['end'])) {
		$end = prepareDate($_GET['end'], false);
	}

	// Set headers
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-cache, no-store, must-revalidate');
	header('Pragma: no-cache');
	header('Expires: 0');

	// Get and display the data
	$val = array();

	if ($events) {
		$val['events'] = getEvents($days, $public_and_private, $categories, $audience, $start, $end, $online);
	}
	if ($meetings) {
		$val['meetings'] = getMeetings($days);
	}
	if ($space) {
		$val = getSpaceSchedule($space, $days);
	}
	echo json_encode($val);

?>
