<?php
	// Get the various date/time components, then apply them to associated variables
	$date = date('l,F,j,g,i,a');
	list($weekday, $month, $daynum, $hour, $minute, $ampm) = explode(',', $date);

	$location_id = isset($_GET['id']) && (int) $_GET['id'] ? (int) $_GET['id'] : 0;

	// If things aren't yet appropriately setup, end processing
	if ($location_id == 0) {
		die('<p>No location(s) found. Please verify configuration.</p>');
	}

	$events = $event_data = json_decode(@file_get_contents(CONTENT_URL . 'examples/json-providers/events.php?space='.$location_id), true);

	$room_data = array_shift($events);
	$current = array_values(array_filter($events, 'get_current'));
	$events  = array_values(array_filter($events, 'filter_old'));
	if ($current) $current = $current[0];
	
	function get_current($a) {
		$start = isset($a['setup']) ? $a['start'] - ($a['setup']*60) : $a['start'];
		$end = isset($a['breakdown']) ? $a['end'] + ($a['breakdown']*60) : $a['end'];
		return ($end > time() && $start < time());
	}
	function filter_old($a) {
		// Remove any events/bookings that have already occurred
		$end = isset($a['breakdown']) ? $a['end'] + ($a['breakdown']*60) : $a['end'];
		return ($end > time());
	}

	$statusClass = 'status';
	if (!$current) {
		$statusClass .= ' available';
		$current['title'] = NOT_IN_USE_MESSAGE;
		if (isset($events[0])) {
			$next_available = isset($events[0]['setup']) ? $events[0]['start'] - ($events[0]['setup']*60) : $events[0]['start'];
			$next_available = date(SHORT_TIME_FORMAT, $next_available);
			$current['curtime'] = UNTIL_NEXT_PREFIX . " {$next_available}";
		} else {
			$current['curtime'] = UNTIL_DAY_END_MESSAGE;
		}
	} else {
		if ($current['type'] == 'event') {
			// The event has already completed, but the space is still reserved for cleanup
			if ($current['breakdown'] && time() >= $current['end']  && ($current['end'] + ($current['breakdown']*60)) > time()) {
				$statusClass .= ' breakdown';
			// The event has not yet begun, but the space is reserved for setup
			} else if ($current['setup'] && time() <= $current['start'] && $current['start'] - ($current['setup']*60) < time()) {
				$statusClass .= ' setup';
			}
			$current['title'] .= ' <img src="images/circle-info.svg" class="icon pulsate" id="info-icon" />';
		} else {
			// I realize this is duplicated, but provides for simpler customization in the future
			$current['title'] .= ' <img src="images/circle-info.svg" class="icon pulsate" id="info-icon" />';
		}
	}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Room Status</title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Merriweather%7CPoppins">
	<link rel="stylesheet" href="styles/theme-01.css">
	<style>
<?php
	// Too lazy to not do this dynamically
	$img = '';
	if (isset($current['image']) && $current['image']) {
		$img = $current['image'];
	} else if (isset($room_data['image']) && $room_data['image']) {
		$img = $room_data['image'];
	}
	$img = $img ? "url('{$img}')" : 'none';
?>
		.wrapper:before {
			content:'';
			height:100vh;
			width:100vw;
			overflow:hidden;
			position:absolute;
			top:0;
			left:0;
			z-index:-1;
			background-image: <?= $img ?>;
			background-size: cover;
			background-position: center center;
		}
	</style>
	<script src="js/htmx.min.js"></script>
</head>
<body>
	<div class="wrapper">
		<header class="room-name"><?= $room_data['name'] ?></header>
		<div class="<?= $statusClass ?>">
			<div class="room-info">
				<div class="event-name"><a href="#" class="action event-link" data-modal="eventInfo" data-eid="<?= $current['id'] ?? '' ?>" data-start="<?= $current['start'] ?? '' ?>"><?= $current['title'] ?></a></div>
				<div class="curtime">
<?php if (!isset($current['id'])): ?>
					<?= $current['curtime'] ?>
<?php else: ?>
					<?= date(SHORT_TIME_FORMAT, $current['start']) ?> <span>-</span> <?= date(SHORT_TIME_FORMAT, $current['end']) ?>
<?php endif; ?>
				</div>
			</div>
		</div>
		<div class="datetime">
			<div>
				<div class="time"><span class="clock-hour"><?php echo $hour; ?></span>:<span class="clock-minute"><?php echo $minute; ?></span><span class="clock-meridian"><?php echo $ampm; ?></span></div>
				<div class="date"><span class="clock-day"><?= $weekday ?></span>, <span class="clock-month"><?= $month ?></span> <span class="clock-date"><?= $daynum ?></span></div>
			</div>
		</div>
		<div class="section-title"><?= SCHEDULE_TITLE ?></div>
		<div class="eventlist">
			<ul>
<?php if (count($events)): ?>
	<?php foreach ($events as $event): ?>
				<li><a href="#" class="action event-link" data-modal="eventInfo" data-start="<?= $event['start'] ?>"><span class="title"><?= ($event['type'] == 'event' && $event['private']) ? '<span class="private-event">Library Use</span>' : $event['title'] ?></span><span class="time"><?= date(SHORT_TIME_FORMAT, $event['start']) ?> - <?= date(SHORT_TIME_FORMAT, $event['end']) ?></span></a></li>
	<?php endforeach; ?>
	<?php else: ?>
				<li class="not-in-use"><?= NOTHING_SCHEDULED_MESSAGE ?></li>
<?php endif; ?>
			</ul>
		</div>
		<footer class="control">
			<img id="logo" src="<?= IMAGE_LOC ?>" />
			<a href="?room_status" class="action d-print-none" id="admin-menu-link" hx-get="?room_status" hx-target=".modal-content .description"><img src="images/caret-solid.svg" class="icon pulsate" id="caret-icon" /><?= MENU_LINK_TEXT ?></a>
		</footer>
	</div>
	<dialog class="modal" aria-modal="true" aria-labelledby="dialog-title">
		<header>
			<div class="modal-title" id="dialog-title"></div>
			<button class="btn-close">X</button>
		</header>
		<!-- <div id="qrcode"></div> -->
		<div class="modal-container">
			<div class="modal-content">
				<div class="description"></div>
			</div>
		</div>
	</dialog>
</body>
<script>
	sessionStorage.setItem("room_data", JSON.stringify(<?= json_encode($event_data) ?>));
</script>
<script src="js/counter.js"></script>
<script src="js/lookup.js" type="module"></script>
</html>