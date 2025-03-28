<?php

function getLocationsAndStatus() {
	// Returns available locations and associated _current_ status information
	try {
		// open the database
		$db = new PDO('sqlite:' . DATABASE_FILE);
		$sql = "select
				s.id, s.name, s.image,
				sc.public, sc.admin_only,
				c.visibility,
				b.id as booking_id, b.title as meeting, b.event_id, b.cid, b.category as space_category, b.firstname, b.lastname,
				e.id as event_id, e.title as event_name, e.description, e.more_info, e.event_note, e.url, e.campus, e.audience, e.category, e.owner, e.image as event_image, e.cost, e.private,
				(CASE WHEN b.title IS NULL THEN 0 ELSE 1 END) as is_active
			FROM spaces s
				LEFT JOIN
					bookings b on s.id = b.eid and b.start <= cast(strftime('%s', 'now') as integer) and b.end >= cast(strftime('%s', 'now') as integer)
				LEFT JOIN
					events e on s.id = e.location_id and (e.start - e.setup*60) <= cast(strftime('%s', 'now') as integer) and (e.end + e.breakdown*60) >= cast(strftime('%s', 'now') as integer)
				LEFT JOIN
					space_categories sc on sc.id = b.cid
				LEFT JOIN
					calendars c on c.id = e.cal_id group by s.id
			ORDER BY is_active DESC, s.name ASC";

		$spaces = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

		// close the database connection
		$db = NULL;
		
		return $spaces;
	}
	catch (PDOException $e) {
		if ($e->getCode() === 14) {
			return false;
		}
	}
}

if (isset($_GET['json'])) {
	// Set headers
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-cache, no-store, must-revalidate');
	header('Pragma: no-cache');
	header('Expires: 0');
	
	echo json_encode(getLocationsAndStatus());
	exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Room Information</title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Merriweather%7CPoppins">
	<link rel="stylesheet" href="styles/theme-01.css">
</head>
<body>
	<div class="room-status-container">
		<h1>Room Status</h1>
<?php
	$status = getLocationsAndStatus();
	if (count($status)):
?>
		<a href="?room_report" id="room_report_link" class="action" hx-prompt="Enter Access Code" class="d-print-none" hx-get="?room_report" hx-target=".modal-content .description" target="_self">Room Setup Report</a>
		<ul class="room-list">
<?php
	foreach ($status as $room):
		$url = $room['event_image'] ? $room['event_image'] : $room['image'];
		if (!$url) $url = 'https://dummyimage.com/512x288';
		// $url = $room['image'] ? $room['image'] : 'https://dummyimage.com/512x288';
?>
			<li class="room-data">
				<a href="?id=<?= $room['id'] ?>">
					<figure>
						<img src="<?= $url ?>" alt="" />
						<figcaption class="<?= $room['meeting'] ? '' : 'inactive' ?>">
							<h2><?= $room['name'] ?></h2>
							<?= $room['meeting'] ?>
						</figcaption>
					</figure>
				</a>
			</li>
<?php endforeach; ?>
		</ul>
<?php else: ?>
		<p>ERROR: No rooms found. Something is wrong. Please contact a staff member.</p>
<?php endif; ?>
	</div>
</body>
</html>