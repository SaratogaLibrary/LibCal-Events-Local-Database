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
$html = '';
foreach ($setup as $day => $room_events) {
    $html .= '<h2>'.date(LONG_DATE_FORMAT, strtotime($day)).'</h2>';
	ksort($room_events);
    foreach ($room_events as $room => $bookings) {
        $html .= '<table>';
        $html .= '<caption>'.$room.'</caption>';
        $html .= '<tr>';
        $html .= '<th>Time</th>';
        $html .= '<th>Event</th>';
        $html .= '<th>Contact</th>';
        $html .= '<th>Setup / Info</th>';
        $html .= '</tr>';
        foreach ($bookings as $booking) {
            // Setup / Info Notes
            $notes = '';
            $notes .= $booking['equipment']  ? "<div class='notes_equipment'><span class='notes_title equipment'>EQUIPMENT:</span> {$booking['equipment']}</div>" : '';
            $descriptor = $booking['owner']  ? 'setup' : 'notes';
            $notes .= $booking['event_note'] ? "<div class='notes_{$descriptor}'><span class='notes_title {$descriptor}'>{$descriptor}:</span> {$booking['event_note']}</div>" : '';
            
            // Time detail
            $time = '';
            if (!$booking['owner']) {
                $time = date(SHORT_TIME_FORMAT, $booking['booking_start']) . '-' . date(SHORT_TIME_FORMAT, $booking['booking_end']);
            } else {
                $time = '<span class="notes_title">RESERVED:</span><br />' .
                        date(SHORT_TIME_FORMAT, $booking['booking_start']) . '-' . date(SHORT_TIME_FORMAT, $booking['booking_end']) . '<br />' .
                        '<span class="notes_title">EVENT:</span><br />' .
                        date(SHORT_TIME_FORMAT, $booking['event_start']) . '-' . date(SHORT_TIME_FORMAT, $booking['event_end']);
            }

            // Display the row
            $html .= '<tr>';
            $html .= '<td>'.$time.'</td>';
            $html .= '<td>'.htmlentities($booking['meeting']).'</td>';
            $html .= $booking['owner'] ? '<td>'.$booking['owner'].'</td>' : '<td>'.$booking['booking_name'].'</td>';
            $html .= '<td>'.$notes.'</td>';
            $html .= '</tr>';
        }
        $html .= '</table>';
    }
}
?>
        <?= $html ?>
    </div>
</body>
</html>