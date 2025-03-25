<?php

// Configure custom values
// Create an Application to get necessary Client ID and Secret values, and set proper access
// https://<YOUR-DOMAIN>.libcal.com/admin/api/authentication#s-lc-tab-authentication
$total_days    = 31 * 2;
$client_id     = 0;
$client_secret = '';
$cal_prefix    = 'https://<YOUR-DOMAIN>.libcal.com/';
$timezone_str  = ''; // (https://www.php.net/manual/en/timezones.php)


// Define constants
const TOKEN_LIFETIME      = 60;           // minutes until invalid
const TOKEN_FILE          = 'token';      // local file to store the API token
const DATABASE_FILE       = __DIR__ . DIRECTORY_SEPARATOR . 'db.sqlite3'; // local file to store the SQLite database
const DB_STRING_DELIMITER = '|';          // Seperates strings in combined TEXT fields
const PDO_OPTIONS         = [PDO::ATTR_TIMEOUT => 0, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

// Event specific API settings
const GET_EVENT_NOTES    = true;

// Event, space booking, and equipment setting
const GET_INTERNAL_NOTES = true;

// Space and Equipment API settings
const INCLUDE_REMOTE_BOOKINGS    = true;  // excluded by default in the API calls (booking integrations such as from Outlook or Google Sync)
const INCLUDE_CANCELLED_BOOKINGS = false; // included by default in the API calls
const INCLUDE_TENTATIVE_BOOKINGS = false; // included by default in the API calls
const GET_FORM_ANSWERS           = true;
const GET_CHECKIN_STATUS         = true;
// NOTE: Mediated Denied bookings seem to not be able to be pulled from the API (2024-10-08)

// For demonstration of the examples:
const CONTENT_URL         = 'https://example.com/libcal/';
const IMAGE_LOC           = CONTENT_URL . 'examples/library-svgrepo-com.svg';
const BRAND_COLOR         = '#808080'; // must be defined in HTML HEX for the brochure example
const PASSCODE            = '12345';

// Time and Date Formats
const SHORT_TIME_FORMAT   = 'g:ia';
const LONG_DATE_FORMAT    = 'l, F j, Y';
