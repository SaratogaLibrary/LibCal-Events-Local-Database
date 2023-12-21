<?php
require_once('../../config.php');

// with no parameters passed, events.php returns events for the current day
$url = 'examples/json-providers/events.php';
$query_str  = '';
$default_settings = [
	'events'   => 'true',
	'meetings' => 'false',
	'private'  => 'false',
	'days'     => 1
];
$truncate_options = [
	'maxLength' => 170,
	'keepTags' => '<em><i><strong><b>'
];

function eventTitle($listing, $type = 'event') {
	$hybrid = false;
	$online = false;
	if ($type == 'meeting') {
		// This is a public meeting / space booking
		$time = date('g:i a', $listing->start) . '-' . date('g:i a', $listing->end);
		$summary = "Contact {$listing->firstname} {$listing->lastname} at {$listing->email} for more information on this room usage.";
	} else {
		// This is a library event
		$online = (isset($listing->online_seats) && $listing->online_seats > 0);
		$hybrid = ($online && $listing->location);
		$time = $listing->allday ? 'All Day' : date('g:i a', $listing->start) . '-' . date('g:i a', $listing->end);
		$listing->location = ($listing->cat_id == 42786) ? '' : $listing->location;
		$summary = strip_tags($listing->description, '<p><br><ul><ol><li>');
	}
	// TODO: Handle multi-date event listings (check for appropriate end time; ex: not all-day?)
	$date = (date('Ymd') > date('Ymd', $listing->start)) ? date('l, F j') : date('l, F j', $listing->start);
	// If the event is a holiday/closure notice, remove the location information
	$summary = preg_replace(
		array(
			'/ style=[\'"].*?[\'"]/i',  // removes style tags
			'/<[uo]l.*?<\/[uo]l>/si'    // removes ol/ul and content
		), '', $summary);
	$title = $listing->title;
	if ($hybrid) {
		$location = "{$listing->location} or Online";
	} else if ($online) {
		$location = 'Online Event';
	} else if ($listing->location) {
		$location = $listing->location;
	} else {
		$location = '(No Location Provided)';
	}
	// Cover scenario if we want to remove "No Location Provided" ...
	$location = $location ? "- $location" : '';

	// Return the formatted "title" tag string data in HTML format
	return "<strong>{$date}</strong><br>{$time} {$location}<br><br>{$summary}";
}

$types      = null;
$days       = 21;
$limit      = 12;
$private    = false;
$images     = true;
$audiences  = null;
$categories = null;
$not_found  = 'Sorry - no upcoming events were found. Please check back soon!';

$types[] = 'events';
foreach ($types as $type) {
	$default_settings[$type] = 'true';
}
$default_settings[$type] = 'true';
$default_settings['days'] = $days;
if (!in_array('events', $types)) $default_settings['events'] = 'false';
if ($private) $default_settings['private'] = 'true';
if ($audiences) $default_settings['audience'] = implode(',', $audiences);
if ($categories) $default_settings['categories'] = implode(',', $categories);

$query_str = http_build_query($default_settings);
$event_data = json_decode(@file_get_contents(CONTENT_URL . $url . '?' . $query_str));
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Hover Style Card Event Widget</title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
	<link rel="stylesheet" href="hover-cards.css">
</head>
<body>

<?php
if ((!isset($event_data->events) || !$event_data->events) && (!isset($event_data->meetings) || !$event_data->meetings)):
	echo '<p class="my-4">'.$not_found.'</p>';
else:
	if (isset($event_data->events) && $event_data->events):
		$count = 1;
?>
	<div class="row d-flex justify-content-around my-5 event-grid">
<?php foreach ($event_data->events as $item): ?>
	<div class="col-12 col-md-6 col-xl-4 event-grid-event">
		<a href="<?= $item->url ?>" class="rounded">
			<figure class="event-grid-image rounded">
				<div class="event-image-container d-flex justify-content-center align-items-center">
<?php if ($images): ?>
					<img class="img-fluid rounded w-100" src="<?= $item->image ?>" alt="<?= $item->title ?>">
<?php else: ?>
					<span class="text-center text-dark h3 py-2 px-3 d-block font-weight-bold"><?= $item->title ?></span>
<?php endif; ?>
				</div>
				<figcaption class="text-center text-muted">
					<time datetime="<?= date('c', $item->start) ?>"><small><?= date('l, M j', $item->start) ?>&ensp;•&ensp;<?= date('g:ia', $item->start) ?></small></time>
					<div class="event-info py-1 px-2 m-0">
						<div class="event-title h5 pt-2 mb-0"><?= $item->title ?></div>
						<p class="pt-2"><small><?= strip_tags(mb_substr(strip_tags($item->description, $truncate_options['keepTags']), 0, $truncate_options['maxLength']), $truncate_options['keepTags']) ?>&hellip;</small></p>
						<div class="event-footer">
							<button tabindex="-1" class="btn btn-sm btn-primary float-left">View <span class="sr-only">the <?= $item->title ?></span> Program</button>
							<?php if ($item->registration): ?><span class="badge badge-light float-right">Registration Required</span><?php endif; ?>
						</div>
					</div>
				</figcaption>
			</figure>
		</a>
	</div>
<?php
// BREAK IF LIMIT REACHED
	if ($limit && $limit == $count):
		break;
	endif;
	$count++;
	endforeach;
	$count = 1;
	if (isset($event_data->meetings) && $event_data->meetings):
		foreach ($event_data->meetings as $item):
?>
<div class="col-12 col-md-6 col-xl-4 event-grid-event">
	<figure class="event-grid-image rounded">
		<div class="event-image-container d-flex justify-content-center align-items-center">
			<span class="text-center text-dark h3 py-2 px-3 d-block font-weight-bold"><?= $item->title ?></span>
		</div>
		<figcaption class="text-center text-muted">
			<time datetime="<?= date('c', $item->start) ?>"><small><?= date('l, M j', $item->start) ?>&ensp;•&ensp;<?= date('g:ia', $item->start) ?></small></time>
			<div class="event-info py-1 px-2 m-0">
				<p class="pt-2"><small><?= "Contact {$item->firstname} {$item->lastname} at {$item->email} for more information on this public group's meeting." ?></small></p>
			</div>
		</figcaption>
	</figure>
</div>
<?php
	// BREAK IF LIMIT REACHED
		if ($limit && $limit == $count):
			break;
		endif;
		$count++;
		endforeach;
	endif; // meetings
?>
	</div>
<?php
	endif; //
endif; // events were found
?>
</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</html>