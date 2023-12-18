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
const DATABASE_FILE       = 'db.sqlite3'; // local file to store the SQLite database
const DB_STRING_DELIMITER = '|';          // Seperates strings in combined TEXT fields
const PDO_OPTIONS         = [PDO::ATTR_TIMEOUT => 0, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];