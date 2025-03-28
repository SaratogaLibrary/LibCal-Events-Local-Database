<?php

$query_string = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);

// Retrieve the room setup data
function filter_setup_entries($setup) {
    $filtered = [];
    foreach ($setup as $day => $room_events) {
        $filtered[$day] = [];
        foreach ($room_events as $room => $bookings) {
            $filtered[$day][$room] = array_filter($bookings, function($booking) {
                return !empty($booking['event_note']) || !empty($booking['equipment']);
            });
            // Remove empty room arrays
            if (empty($filtered[$day][$room])) {
                unset($filtered[$day][$room]);
            }
        }
        // Remove empty day arrays
        if (empty($filtered[$day])) {
            unset($filtered[$day]);
        }
    }
    return $filtered;
}

// Returns available locations and associated _current_ status information
function getLocationsAndStatus() {
	// Set the START time
	if (isset($_GET['start']) && $_GET['start']) {
		$start = strtotime($_GET['start']);
	} else {
		$start = time();
	}
	// Set the END time
	if (isset($_GET['end']) && $_GET['end']) {
		// +1 day ends on the following day's "midnight", including all of the expected day's events
		$end = strtotime($_GET['end'] . ' +1 day');
	} else {
		// One week from today:
		$end = strtotime(date('Y-m-d 00:00:00', strtotime('+7 days')));
	}

	try {
		// open the database
		$db = new PDO('sqlite:' . DATABASE_FILE);
		$sql = "SELECT
					s.name,
					b.start as event_date, b.title as meeting, (b.firstname  || ' ' || b.lastname) as booking_name, b.start as booking_start, b.end as booking_end,
					e.start as event_start, e.end as event_end, e.setup, e.breakdown, e.owner, e.event_note,
					(
						SELECT
							group_concat(eq.item_name, ', ')
						from equipment eq
						where
							eq.status='Confirmed' AND
							eq.cancelled=0 AND
							eq.end <= {$end} AND
							eq.end > {$start} AND
							(e.id = eq.event_id OR b.booking_id = eq.booking_id)
					) as equipment
				FROM bookings b
					left join
						events e on b.event_id = e.id AND e.id
					left join
						spaces s on b.eid = s.id
				WHERE
					b.end > {$start} AND
					b.end <= {$end}
				order by
					event_date ASC,
					s.name ASC,
					b.start ASC";

		$spaces = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

		// close the database connection
		$db = NULL;
		
		// PHP's SQLite support is lacking on our production server, so I can't use 'localtime'
		// ... manually convert the timestamp to a SQL date string
		foreach ($spaces as $index => $array) {
			foreach ($array as $k => $v) {
				if ($k == 'event_date') {
					$spaces[$index][$k] = date('Y-m-d', $v);
				}
				continue;
			}
		}

		return $spaces;
	}
	catch (PDOException $e) {
		echo '<pre>' . print_r($e, 1) . '</pre>'; die();
		if ($e->getCode() === 14) {
			return false;
		}
	}
}

$headers = getallheaders();

if (isset($headers['Hx-Prompt']) && $headers['Hx-Prompt'] !== PASSCODE) die('<p>Invalid Code</p>');

$values = getLocationsAndStatus();

if (isset($_GET['json'])) {
	// Set headers
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-cache, no-store, must-revalidate');
	header('Pragma: no-cache');
	header('Expires: 0');

	echo json_encode($values);
	exit();
}

$setup = [];
foreach ($values as $entry) {
	$date_index = str_replace('-', '', $entry['event_date']);
	$time_index = $entry['booking_start'];
	$room_index = $entry['name'];

	// Set up the array structure we'll use to loop and display the data
	$setup[$date_index][$room_index][$time_index] = $entry;
}

$linktext = '';
if (isset($_GET['showall'])) {
	$q = str_replace('&showall', '', $query_string);
	$linktext = "<p class='d-print-none'>Showing all entries. &emsp;&mdash;&emsp; <a hx-target='.modal-content .description' target='_self' hx-get='?{$q}' href='?{$q}'>Hide empty setups.</a></p>";
} else {
	$q = $query_string . '&showall';
	$linktext = "<p class='d-print-none'>Showing only entries with setup requested. &emsp;&mdash;&emsp; <a hx-target='.modal-content .description' target='_self' hx-get='?{$q}' href='?{$q}'>Show all usage</a></p>";
	$setup = filter_setup_entries($setup);
}

include('report-themes/tablet-table.php');
?>