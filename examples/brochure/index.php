<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once('../../config.php');
date_default_timezone_set($timezone_str);          // Find your timezone here: https://www.php.net/manual/en/timezones.php

// Customize your generated PDF's branding and usage
$library_name   = 'Your Library';                       // Library name
$lib_www_home   = 'www.library.org';                    // Used in the PDF footer, for branding
$event_cal_url  = 'https://www.library.org/events';     // Webpage to point to your event calendar
$contact_phone  = '555-555-5555';                       // Primary contact phone for event information
$brand_color    = BRAND_COLOR;                          // Library brand color from config.php
$light_color    = '';                                   // Light color used as event detail (text) background
$dark_color     = '';                                   // Darker color used as event detail border color
$register_color = '';                                   // Background color for notice of registration required
$waitlist_color = '';                                   // Background color for when the event waitlist is active/in use
$register_msg   = '';                                   // Custom text to use for the "Registration is Required" message
$waitlist_msg   = '';                                   // Custom text to use for when an event is full, but there's a waitlist
$render_images  = false;                                // Do, or don't, render *some* event images; images severely hurt rendering
$notice_cat_ids = [42786];                              // IDs of categories that can be assigned a different event title text color (ex: closings/holidays)
$notice_colors  = ['red'];                              // Matching color to IDs of "notice_catories" that will assign text color
$show_if_full   = false;                                // Show events if they are full AND there is no waitlist
$show_waitlist  = true;                                 // Show events if they are full, but there's a waitlist
$filename       = 'Program Brochure ' . date('n-j-Y');  // The name of the file that will be provided to the end user
$logo_img_path  = '../library-svgrepo-com.svg';

// If $brand_color is not yet set, define a fallback
$brand_color = $brand_color ? $brand_color : '#000000';

// The following are generated automatically; you can change them, but it is not recommended
$text_color     = getContrastColor($brand_color);       // Text color for Library Name, determined by brand color, for accessibility
list($r,$g,$b)  = hex_to_rgb($brand_color);             // Used to draw column lines in PDF using brand color

###########################################################################################################
// Do not change things below this line without knowing what you're doing ;)
###########################################################################################################
$options = [
    'brand_color'    => $brand_color,
    'light_color'    => $light_color,
    'dark_color'     => $dark_color,
    'render_images'  => $render_images,
    'notice_cat_ids' => $notice_cat_ids,
    'notice_colors'  => $notice_colors,
    'register_color' => $register_color,
    'waitlist_color' => $waitlist_color,
    'register_msg'   => $register_msg,
    'waitlist_msg'   => $waitlist_msg,
    'show_full'      => $show_if_full,
    'show_waitlist'  => $show_waitlist
];

// Clean POST values...
// Assign POST values...
function cleanArrVal($key) {
    if (!empty($_POST[$key])) {
        if (is_numeric($_POST[$key])) {
            $_POST[$key] = [$_POST[$key]];
        }
    } else {
        $_POST[$key] = [];
    }
}
cleanArrVal('locations');
cleanArrVal('categories');
cleanArrVal('audiences');

if (!isset($_POST['event-search-start']) || !isset($_POST['event-search-end'])
  || empty($_POST['event-search-start']) || empty($_POST['event-search-end'])) {
    $_POST['event-search-start'] = date('Y-m-d');
    $_POST['event-search-end']   = date('Y-m-d', strtotime('+6 days'));
}
$date_range = "{$_POST['event-search-start']} - {$_POST['event-search-end']}";

$matches = [];
$matched_regex = preg_match('/(\d{4}\-\d{2}\-\d{2})(?: - (\d{4}\-\d{2}\-\d{2}))?/', trim($date_range), $matches);
$date_start = $date_end = '';
if ($matched_regex && count($matches)) {
    if (count($matches) == 2) {
        $date_start = $date_end = $matches[1];
        $date_start .= ' 00:00';
        $date_end .= ' 23:59';
    } else {
        $date_start = $matches[1] . ' 00:00';
        $date_end   = $matches[2] . ' 23:59';
    }
} else {
    $date_start = date('Y-m-d 00:00');
    $date_end   = date('Y-m-d 23:59', strtotime('+7 days'));
}

$values = [
    'locations'  => $_POST['locations'] ?? null,
    'categories' => $_POST['categories'] ?? null,
    'audiences'  => $_POST['audiences'] ?? null,
    'start_date' => $date_start,
    'end_date'   => $date_end
];

/**
* Helper function: Convert hex color string into RGB array
*
* @param string HEX color
* @return array HSL color set
*/
function hex_to_rgb($hex) {
    $hex = str_replace('#', '', $hex);
    $hex = (strlen($hex) == 3) ? $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2] : substr(str_pad($hex, 6, '0', STR_PAD_LEFT), 0, 6);
    // bitwise conversion
    $colorval = hexdec($hex);
    $r = 0xFF & $colorval >> 0x10;
    $g = 0xFF & $colorval >> 0x8;
    $b = 0xFF & $colorval;

    return array($r, $g, $b);
}

// Found: https://stackoverflow.com/questions/6284553/using-an-array-as-needles-in-strpos/9220624#9220624
/**
* Search for an occurence of any values within an array of strings from haystack
*
*/
function striposa(string $haystack, array $needles, int $offset = 0): bool
{
    foreach($needles as $needle) {
        if(stripos($haystack, $needle, $offset) !== false) {
            return true; // stop on first true result
        }
    }
    return false;
}

// Found: https://stackoverflow.com/questions/1331591/given-a-background-color-black-or-white-text#answer-42921358
function getContrastColor($hexColor) {
    // hexColor RGB
    $R1 = hexdec(substr($hexColor, 1, 2));
    $G1 = hexdec(substr($hexColor, 3, 2));
    $B1 = hexdec(substr($hexColor, 5, 2));

    // Black RGB
    $blackColor = "#000000";
    $R2BlackColor = hexdec(substr($blackColor, 1, 2));
    $G2BlackColor = hexdec(substr($blackColor, 3, 2));
    $B2BlackColor = hexdec(substr($blackColor, 5, 2));

    // Calculate the contrast ratio
    $L1 = 0.2126 * pow($R1 / 255, 2.2) +
          0.7152 * pow($G1 / 255, 2.2) +
          0.0722 * pow($B1 / 255, 2.2);

    $L2 = 0.2126 * pow($R2BlackColor / 255, 2.2) +
          0.7152 * pow($G2BlackColor / 255, 2.2) +
          0.0722 * pow($B2BlackColor / 255, 2.2);

    $contrastRatio = 0;
    if ($L1 > $L2) {
        $contrastRatio = (int)(($L1 + 0.05) / ($L2 + 0.05));
    } else {
        $contrastRatio = (int)(($L2 + 0.05) / ($L1 + 0.05));
    }

    // If contrast is more than 5, return black color
    if ($contrastRatio > 5) {
        return '#000000';
    } else {
        // if not, return white color.
        return '#FFFFFF';
    }
}

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'Letter',
    'orientation' => 'P',
    'img_dpi' => 300,
    'debug' => true,
    'fontdata' => [
        'dejavusans' => [
            'R'  => 'DejaVuSans.ttf',
            'B'  => 'DejaVuSans-Bold.ttf',
            'I'  => 'DejaVuSans-Oblique.ttf',
            'BI' => 'DejaVuSans-BoldOblique.ttf'
        ]
    ],
    'default_font' => 'dejavusans, chelvetica, carial, helvetica, arial, sans-serif',
    'backupSubsFont' => ['dejavusanscondensed']
]);

$layout_settings = '<!--mpdf
<htmlpagefooter name="footer">
    <table width="100%" style="position:absolute; bottom:0; right:0; left:0; vertical-align: bottom; font-size: 9pt; color: #FFFFFF; background-color:#000000; padding:1.5rem; margin-bottom:0;">
        <tr>
            <td width="33%">'.$lib_www_home.'</td>
            <td width="33%" align="center">Generated on {DATE F j, Y}</td>
            <td width="33%" style="text-align: right;">Page {PAGENO} of {nbpg}</td>
        </tr>
    </table>
</htmlpagefooter>
mpdf-->
<style>
    @page {
        margin:0;
        footer: html_footer;
        margin-top:.2in;
        margin-footer:0;
		line-height:1.5;
    }
    body {
        font-family:dejavusans, carial, chelvetica, serif;
        font-size:8pt;
		line-height:1.3;
    }
    .center-column-logo {
        text-align:center;
        max-width:2.68in;
    }
    .pb {
        padding-bottom:1.5rem;
    }
	table {
		line-height:1.5;
	}
    a.event-link {
        color:#000000;
        text-decoration:none;
    }
    .event-info th {
        padding:.5rem;
        background-color:gray;
        color:white;
        font-size:1.2rem;
        text-align:center;
        font-weight:bold;
    }
    .event-info .pt {
        padding-top:1rem;
    }
    .event-info {
        width:100%;
        margin-left:2rem;
        margin-right:2rem;
        padding-bottom:2.25rem;
        page-break-inside:avoid;
    }
    .event-location-text {
        font-weight:bold;
        text-transform:lowercase;
        font-variant:small-caps;
    }
    .event-icon { height:.1in; }
    .icon-clock { padding-right:.05in; }
    .icon-location { padding-right:.08in; }
    .icon-categories { padding-right:.05in; }
    .icon-audience { padding-right:.022in; }
</style>';

$mpdf->SetTitle('Events at ' . $library_name);
$mpdf->SetAuthor($library_name);
$mpdf->SetCreator('Brendon Kozlowski');
$mpdf->SetDisplayMode('fullpage');
$mpdf->setAutoTopMargin = 'stretch';
$mpdf->setAutoBottomMargin = 'stretch';
$mpdf->simpleTables = true;
$mpdf->packTableData = true;
$mpdf->keepColumns = true;
$mpdf->useSubstitutions = false;
$mpdf->keep_table_proportions = true;
$mpdf->shrink_tables_to_fit=1;

// Database values are in timestamp format
$start_ts = strtotime($date_start);
$end_ts   = strtotime($date_end);

// Set query values
$locations = $categories = $audiences = '';
if ($values['locations']) {
    $location_str = [];
    foreach ($values['locations'] as $location) {
        $location_str[] = "location_id LIKE \"%{$location}%\"";
    }
    $locations = ' AND (' . implode(' OR ', $location_str) . ') ';
} else {
    $locations = '';
}
if ($values['categories']) {
    $category_str = [];
    foreach ($values['categories'] as $category) {
        $category_str[] = "cat_id LIKE \"%{$category}%\"";
    }
    $categories = ' AND (' . implode(' OR ', $category_str) . ') ';
} else {
    $categories = '';
}
if ($values['audiences']) {
    $audience_str = [];
    foreach ($values['audiences'] as $audience) {
        $audience_str[] = "audience_id LIKE \"%{$audience}%\"";
    }
    $audiences = ' AND (' . implode(' OR ', $audience_str) . ') ';
} else {
    $audiences = '';
}

// Get our data!
$db = new PDO('sqlite:' . DATABASE_FILE);
$query = 'SELECT * FROM events WHERE private=0 AND start < "'.$end_ts.'" AND end > "'.$start_ts.'" '.$locations.$categories.$audiences.' ORDER BY start';
// file_put_contents('log.txt', time() . "\n{$query}\n", FILE_APPEND);
// die();
$result = $db->query($query);
$result = $result->fetchAll(PDO::FETCH_ASSOC);
$db = null; // Close the connection

if (!$result) {
    // Force a display that allows the end user to return to the form and try again...
    echo "<p>Sorry! No programs or events were found that matched your search. <a href='javascript:history.back();'>Go back and try again</a> with less limitations, a larger timeframe, or just <a href='{$event_cal_url}'>browse our event calendar</a>!</p>";
    die();
}

if ($locations == '') {
    $locations = 'All Locations';
} else {
    $locations = array_keys(array_flip(explode('|', implode('|', array_column($result, 'audience')))));
    sort($locations);
    $locations = implode(' | ', $locations);
}
if ($categories == '') {
    $categories = 'All Events';
} else {
    $categories = array_keys(array_flip(explode('|', implode('|', array_column($result, 'audience')))));
    sort($categories);
    $categories = implode(' | ', $categories);
}
if ($audiences == '') {
    $audiences = 'All Audience Types';
} else {
    $audiences = array_keys(array_flip(explode('|', implode('|', array_column($result, 'audience')))));
    sort($audiences);
    $audiences = implode(' | ', $audiences);
}

$branding = <<<EOT
<html>
<body>
<div style="margin:0 0 1px;">
    <table style="width:100%;border-collapse:collapse;">
        <tr>
			<td style="width:100%;height:.62in;background-color:{$brand_color};padding:1rem;padding-left:2rem;padding-right:2rem;font-size:8pt;">
				<span style="font-weight:bold; margin:0; font-size:18pt; color:{$text_color};">{$library_name}</span>
			</td>
        </tr>
    </table>
</div>
EOT;

$start_date_str = date('n/d/Y', strtotime($date_start));
$end_date_str   = date('n/d/Y', strtotime($date_end));
$header_col1 = <<<EOT
	<div style="width:100%; padding-left:.25in;">
		<table width="100%" style="height:2in;">
			<tr>
				<td style="height:2in;">
					<table style="border-spacing:.75rem; font-size:8pt;">
						<tr>
							<td style="width:.75in;text-align:center;"><img src="icons/calendar.svg" style="height:.24in;" /></td>
							<td><strong>Date Range</strong><br>{$start_date_str} - {$end_date_str}</td>
						</tr>
						<tr>
							<td style="width:.75in;text-align:center;"><img src="icons/map-marker-alt.svg" style="height:.24in;" /></td>
							<td><strong>Location</strong><!--<br>{$locations}--></td>
						</tr>
						<tr>
							<td style="width:.75in;text-align:center;"><img src="icons/list-alt.svg" style="height:.24in;" /></td>
							<td><strong>Event Type</strong><!--<br>{$categories}--></td>
						</tr>
						<tr>
							<td style="width:.75in;text-align:center;"><img src="icons/user-friends.svg" style="height:.24in;" /></td>
							<td><strong>Audience</strong><!--<br>{$audiences}--></td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</div>
EOT;

$header_col2 = <<<EOT
	<div class="center-column-logo">
        <table style="width:100%;height:2in;max-width:2.7in;">
            <tr>
                <td align="center">
                    <img src="{$logo_img_path}" style="height:100%; margin-top:.25in;max-width:2.7in;max-height:1.5in;" />
                </td>
            </tr>
        </table>
	</div>
EOT;

$header_col3 = <<<EOT
	<div style="width:100%; padding-right:.25in; background-color:white;">
		<table width="100%" style="width:100%;height:2in;vertical-align:middle;">
			<tr>
				<td align="middle" style="height:2in;">
					<p style="font-size:8pt;">
						LIBRARY EVENTS<br />Thank you for your interest in our events!
						<br><br>
						Find and register for events at:<br /><a href="{$event_cal_url}">{$event_cal_url}</a><br />Also call {$contact_phone} or pop into the library!
					</p>
				</td>
			</tr>
		</table>
	</div>
EOT;

$header_bottom = <<<EOT
	<div style="border-bottom:2px solid {$brand_color};margin-bottom:2rem;"></div>
EOT;


/**
* Connect to an SQLite DB to loop through some events to create an example document
*/
function WriteEvents($mpdf, $result, $values = [], $options = []) {
    $defaults = [
        'brand_color'    => '#CCCCCC',
        'light_color'    => '#EEEEEE',
        'dark_color'     => '#333333',
        'register_color' => '#DDDDDD',
        'waitlist_color' => '#EEEEEE',
        'register_msg'   => 'Registration is Required',
        'waitlist_msg'   => 'A waitlist is active',
        'render_images'  => false,
        'show_full'      => true,
        'show_waitlist'  => true
    ];
    // Clear out any unset values from the $options array prior to merge
    $options = array_filter($options, function($val) { return !is_null($val) && $val !== ''; });
    $options = array_merge($defaults, $options);
    extract($options);
    extract($values);

// 'locations'
// 'categories'
// 'audiences'
// 'start_date'
// 'end_date'

    // Database values are in timestamp format
    $start_ts = strtotime($start_date);
    $end_ts   = strtotime($end_date);

    static $page = 1;

    $last_event_date = '';
    $display_time = '';
    $date_format = 'l F j, Y';
    list($r,$g,$b) = hex_to_rgb($brand_color);
    $total_count = count($result);
    $images_every = floor(sqrt($total_count));
    $count = 0;
    foreach ($result as $event) {
        $count++;
        $category = '';
        $audience = '';
        // Skip any events that we suspect are not happening on this date
        if (striposa($event['title'], ['cancel', 'reschedule', 'postpone'])) {
            continue;
        }
        // Skip events that are full (if desired))
        if ($event['registration'] && ($event['seats'] - $event['seats_taken'] == 0) && !$show_full && !$show_waitlist) {
            continue;
        }
        $this_event_date = $event['start'] < time() ? date($date_format, time()) : date($date_format, $event['start']);
        $description     = strip_tags($event['description'], '<br><i><em>');
        $start_time      = date('g:ia', $event['start']);
        $end_time        = date('g:ia', $event['end']);
        if ($start_time == '12:00am' && $end_time == '11:59pm') {
            $display_time = 'All Day';
        } else {
            $display_time = "{$start_time} - {$end_time}";
        }
        $tbl_header      = '';
        $tbl_header_pad  = '';
        $info            = '';
        $image           = '';
        $registration    = '';
        $waitlist        = '';
        if (isset($notice_cat_ids) && isset($notice_colors) && !empty($notice_cat_ids) && !empty($notice_colors)) {
            if (in_array($event['cat_id'], $notice_cat_ids)) {
                $color = $notice_colors[array_search($event['cat_id'], $notice_cat_ids)];
                $title = '<a class="event-link" href="'.$event['url'].'"><strong style="color:'.$color.'";>'.$event['title'].'</strong></a>';
            } else {
                $title = "<a class='event-link' href='{$event['url']}'><strong>{$event['title']}</strong></a>";
            }
        }

        if ($this_event_date !== $last_event_date) {
            $tbl_header      = '<tr><th>' . $this_event_date . '</td></th>';
            $tbl_header_pad  = ' class="pt"';
            $last_event_date = $this_event_date;
        }
        if ($render_images && $count == $images_every) {
            $image = "<table cellspacing='0' cellpadding='0'><tr><td align='center'><img src='{$event['image']}' style='max-width:2.25in; margin-bottom:5px;' /></td></tr></table>";
            $count = 0;
        }
        if ($event['registration']) {
            $registration = "<table cellspacing='0' cellpadding='0' width='100%' style='margin-bottom:5px;'><tr><td align='center' style='background-color:{$register_color};'><a class='event-link' href='{$event['url']}'>{$register_msg}</a></td></tr></table>";
        }
        if ($event['seats_taken'] && ($event['seats'] - $event['seats_taken'] == 0) && $event['wait_list'] && $show_waitlist) {
            $waitlist = "<table cellspacing='0' cellpadding='0' width='100%' style='margin-bottom:5px;'><tr><td align='center' style='background-color:{$waitlist_color};'><a class='event-link' href='{$event['url']}'>{$waitlist_msg}</a></td></tr></table>";
        }

        $info .= <<<EOT
        <table cellspacing="0" cellpadding="0" class="event-info">
            {$tbl_header}
            <tr>
                <td{$tbl_header_pad}>
                    <strong>{$title}</strong><br />
                    <table bgcolor="{$light_color}" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid {$dark_color}; padding:3px; margin:2px 0 5px;"><tr><td>
                    <img src="icons/clock.svg" class="event-icon icon-clock" /> {$display_time}<br />
EOT;
    if ($event['location']) {
        $info .= <<<EOT
                    <img src="icons/map-marker-alt.svg" class="event-icon icon-location" /> <span style="event-location-text">{$event['location']}</span><br />
EOT;
    }
        $category = !empty($event['category']) ? implode(', ', explode('|', $event['category'])) : '';
        $audience = !empty($event['category']) ? implode(', ', explode('|', $event['audience'])) : '';

    if ($category) {
        $info .= <<<EOT
                    <img src="icons/list-alt.svg" class="event-icon icon-categories" /> {$category}<br />
EOT;
    }
    if ($audience) {
        $info .= <<<EOT
                    <img src="icons/user-friends.svg" class="event-icon icon-audience" /> {$audience}<br />
EOT;
    }
    $info .= <<<EOT
                    </td></tr></table>
                    {$registration}
                    {$waitlist}
                    {$image}
                    {$description}
                </td>
            </tr>
        </table>
EOT;
        $mpdf->WriteHTML($info);
        // Draw additional columns if needed, depending on number of pages
        // The first page's columns have already been drawn
        if ($mpdf->page > $page) {
            // Keep track of the current page as soon as it's reached
            $page = $mpdf->page;

            // Draw the column borders manually with the chosen brand color
            // Array of all current pages: $mpdf->pages; ... current page $mpdf->page;
            $mpdf->SetDrawColor($r,$g,$b);
            $mpdf->Line(71,5.15,71,258);
            $mpdf->Line(145,5.15,145,258);
            // The below draws a rectangle instead of a thin line (as above)...
            #$mpdf->SetFillColor($r,$g,$b);
            #$mpdf->Rect(71,5.1,.1,252.5, 'F');
            #$mpdf->Rect(145,5.1,.1,252.5, 'F');
        }
    }
}

$end = <<<EOT
</body>
</html>
EOT;

// https://github.com/mpdf/mpdf/issues/127
// https://github.com/mpdf/mpdf/issues/771
// --> Draw a column line: $this->Rect( $iXPos, $iTop, .1, $lowest_bottom_y - $iTop, 'F' );
// --> Must determine how/where to insert that code... Columns start at line 24322 of mpdf/src/Mpdf.php

try {
    $mpdf->WriteHTML($layout_settings);

    $mpdf->WriteHTML($branding);
    $mpdf->lasth = 0;
    $mpdf->SetColumns(3, 'JUSTIFY', 5);
    $mpdf->WriteHTML($header_col1);
    $mpdf->AddColumn();
    $mpdf->WriteHTML($header_col2);
    $mpdf->AddColumn();
    $mpdf->WriteHTML($header_col3);
    $mpdf->lasth = 0;
    $mpdf->SetColumns(1);
    $mpdf->WriteHTML($header_bottom);
    $mpdf->SetColumns(3, '', 5);

    // Draw the column borders manually with the chosen brand color
    // Array of all current pages: $mpdf->pages; ... current page $mpdf->page;
    $mpdf->SetDrawColor($r,$g,$b);
    $mpdf->Line(71,78.65,71,258);
    $mpdf->Line(145,78.65,145,258);
    // The below draws a rectangle instead of a thin line (as above)...
    #$mpdf->SetFillColor($r,$g,$b);
    #$mpdf->Rect(71,78.6,.1,179, 'F');
    #$mpdf->Rect(145,78.6,.1,179, 'F');

    WriteEvents($mpdf, $result, $values, $options);

    $mpdf->SetColumns(1);
    $mpdf->WriteHTML($end);

    $mpdf->Output($filename.'.pdf', 'D');
} catch (\Mpdf\MpdfException $e) { // Note: safer fully qualified exception name used for catch
    // Process the exception, log, print etc.
    echo $e->getMessage();
}
