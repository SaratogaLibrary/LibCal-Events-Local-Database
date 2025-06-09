<?php
	if (!isset($_SERVER['HTTP_REFERER'])) {
		if (!isset($_GET['passcode']) || $_GET['passcode'] === PASSCODE) {
			echo '<p>Invalid access detected.</p>';
			exit();
		}
	}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Current Room Status</title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Merriweather%7CPoppins">
	<link rel="stylesheet" href="styles/theme-01.css">
</head>
<body>
	<div class="room-setup-container tablet">
		<h1>Room Setup Review</h1>
		<?= $linktext ?>
<?php
	foreach ($setup as $day => $room_events):
?>
		<h2><?= date('l, F j, Y', strtotime($day)) ?></h2>
<?php
		foreach ($room_events as $room => $bookings):
?>
		<table>
			<caption><?= $room ?></caption>
			<tr>
				<th>Time</th>
				<th>Event</th>
				<th>Contact</th>
				<th>Setup / Info</th>
			</tr>
<?php
			foreach ($bookings as $booking):
				// Setup / Info Notes
				$notes = '';
				$notes .= $booking['equipment'] ? "<div class='notes_equipment'><span class='notes_title equipment'>EQUIPMENT:</span> {$booking['equipment']}</div>" : '';
				$descriptor = $booking['owner'] ? 'setup' : 'notes';
				$notes .= $booking['event_note'] ? "<div class='notes_{$descriptor}'><span class='notes_title {$descriptor}'>{$descriptor}:</span> {$booking['event_note']}</div>" : '';

				// Time detail
				$time = '';
				if (!$booking['owner']) {
					$time = date('g:ia', $booking['booking_start']) . '-' . date('g:ia', $booking['booking_end']);
				} else {
					$time = '<span class="notes_title">RESERVED:</span><br>' .
					        date('g:ia', $booking['booking_start']) . '-' . date('g:ia', $booking['booking_end']) . '<br>' .
					        '<span class="notes_title">EVENT:</span><br>' .
							date('g:ia', $booking['event_start']) . '-' . date('g:ia', $booking['event_end']);
				}
?>
			<tr>
				<td><?= $time ?></td>
				<td><?= $booking['meeting'] ?></td>
				<td><?= $booking['owner'] ? $booking['owner'] : $booking['booking_name'] ?></td>
				<td>
					<?= $notes ?>
				</td>
			</tr>
<?php
			endforeach;
		endforeach;
?>
		</table>
<?php
	endforeach;
?>
	</div>
</body>
</html>