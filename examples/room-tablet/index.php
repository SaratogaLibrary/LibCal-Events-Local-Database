<?php

require_once('../../config.php');
date_default_timezone_set($timezone_str);

// Language-specific constants
const NOT_IN_USE_MESSAGE = 'AVAILABLE';
const UNTIL_NEXT_PREFIX = 'Until'; // ex: "Until 10:00 AM"
const UNTIL_DAY_END_MESSAGE = 'Until end of day';
const NOTHING_SCHEDULED_MESSAGE = 'Nothing further is scheduled in this space today.';
const SCHEDULE_TITLE = 'Room Schedule';
const MENU_LINK_TEXT = 'Menu';

// Include the appropriate file based on the URL parameters
if (isset($_GET['id'])) {
	include('main.php');
} elseif (isset($_GET['room_status'])) {
	include 'rooms_status.php';
} elseif (isset($_GET['room_report'])) {
	include 'room_setup.php';
} else {
	include 'rooms_status.php';
}