<?php
	require_once('../../config.php');
	date_default_timezone_set($timezone_str);
	$events = file_get_contents(CONTENT_URL . 'examples/json-providers/events.php?images=1');

	// Attempt to reload the page if unable to fetch live JSON content
	if ($events === false) {
		header('Refresh:1; url=' . $_SERVER['REQUEST_URI']);
		exit();
	}

	$events = json_decode($events, true);
	$events = $events['events'];

	// Don't display/advertise events that require registration and are already full
	foreach ($events as $index => $event) {
		if ($event['seats'] == null) continue;
		if ($event['seats_taken'] == $event['seats']) {
			unset($events[$index]);
		}
	}
	// Reset the array keys
	$events = array_values($events);

	// Display a random event
	$rand = rand(0, (count($events)-1));

	$event = $events[$rand];

	// Check to verify that the event/content contains data, and reload if not
	if (!isset($event['title']) || empty($event['title'])) {
		header('Refresh:1; url=' . $_PHP['REQUEST_URI']);
		exit();
	}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
	<title><?= $event['title'] ?></title>
	<link rel="stylesheet" href="./css/bootstrap.min.css">
	<link rel="stylesheet" href="./css/style.css">
	<style>
		header, footer { background-color: <?= $event['color'] ?>; }
	</style>
	<script src="./js/vanillaqr.min.js"></script>
	<script src="./js/fitty.min.js"></script>
</head>
<body>
<header>
	<div class="container-fluid">
		<div class="row">
			<div class="col">
				<h1 class="fit"><?= $event['title'] ?></h1>
			</div>
		</div>
	</div>
</header>
<div id="main" class="container-fluid">
	<div class="row">
		<div class="col content">
			<div class="content-info">
				<img id="event-image" class="img-fluid" src="<?= $event['image'] ?>" alt="" />
<?php if (!$event['allday'] || $event['location']): ?>
				<div class="infobox">
					<?= $event['allday'] ? '' : '<div class="datetime">' . date('D, F  d, g:ia', $event['start']) . '</div>'; ?>
					<?= $event['location'] ? '<div class="location">' . $event['location'] . '</div>' : ''; ?>
				</div>
<?php endif; ?>
			</div>
			<div class="description"><?= str_replace('<p>&nbsp;</p>', '', strip_tags($event['description'], '<p><img><br><strong><em><ul><ol><li><s>')) ?></div>
		</div>
	</div>
</div>
<footer id="footer">
	<div id="svg-logo"><img src="<?= IMAGE_LOC ?>" /></div>
	<div class="targetmarket">
		Intended Audiences
		<p class="descriptors">
<?php
	$cats = [];
	foreach (explode(DB_STRING_DELIMITER, $event['audience']) as $category) {
		$pos = strpos($category, '>');
		if ($pos !== false) { $pos += 2; }
		$cats[] = trim(substr($category, $pos));
	}
	echo implode(', ', $cats);
?>
		</p>
		Program Categories
		<p class="descriptors">
<?php
	$cats = [];
	foreach (explode(DB_STRING_DELIMITER, $event['category']) as $category) {
		$pos = strpos($category, '>');
		if ($pos !== false) { $pos += 2; }
		$cats[] = trim(substr($category, $pos));
	}
	echo implode(', ', $cats);
?>
		</p>
	</div>
	<div id="qrcode"><?= $event['seats'] ? '<p>Sign up is required.<br />Visit our online calendar or scan this QRCode:</p>' : '' ?></div>
</footer>
<?php if ($event['seats']): ?>
<script>
	var qr3 = new VanillaQR({
		url: "<?= $cal_prefix ?>event/<?= $event['id'] ?>",
		width: 100,
		height: 100,
		colorLight: "#EEE",
		colorDark: "#666",
		noBorder: true
	});

	var imageElement = qr3.toImage("png");
	if(imageElement) {
		document.getElementById('qrcode').appendChild(imageElement).classList.add('img-fluid');
	}
</script>
<?php endif; ?>
<script>
	// Auto-fit text with a class of "fit"
	fitty('.fit', {
		maxSize: 75
	});
</script>
</body>
</html>