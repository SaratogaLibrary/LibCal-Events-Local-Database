# LibCal-Events-Local-Database
Access your Springshare's LibCal event calendar events and public space reservations locally for embedding on your own website.

## Getting Started

You may want to begin by reviewing the [Overview of the LibCal API](https://ask.springshare.com/libcal/faq/1407) from the Springshare Help Center. This client uses the API v1.1 endpoints.

This solution is designed to use PHP and SQLite. There are no external dependencies required.

### Configuration

Copy this repository to a web server location that supports PHP. There is an `example.config.php` file in the root folder of this project; this should be renamed to `config.php` and the values therein should be customized for your use. Once complete, visit the root folder's `index.php` file to prime the SQLite database file.

The majority of this repository is primarily just a massive example of how one might use the LibCal API to enable access to one's own data that would otherwise be inaccessible except via the vendor's direct web interface and domain.

```php
<?php

// Configure custom values
// Create an Application to get necessary Client ID and Secret values, and set proper access
// https://<YOUR-DOMAIN>.libcal.com/admin/api/authentication#s-lc-tab-authentication
$total_days    = 31 * 2;
$client_id     = 0;
$client_secret = '';
$cal_prefix    = 'https://<YOUR-DOMAIN>.libcal.com/';
$timezone_str  = 'America/New_York'; // (https://www.php.net/manual/en/timezones.php)


// Define constants
const TOKEN_LIFETIME      = 60;           // minutes until invalid
const TOKEN_FILE          = 'token';      // local file to store the API token
const DATABASE_FILE       = __DIR__ . DIRECTORY_SEPARATOR . 'db.sqlite3'; // local file to store the SQLite database
const DB_STRING_DELIMITER = '|';          // Seperates strings in combined TEXT fields
const PDO_OPTIONS         = [PDO::ATTR_TIMEOUT => 0, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

// For demonstration of the examples.
// Set this to your location of this file's containing folder when accessed from a web page:
const CONTENT_URL         = 'https://example.com/libcal/';
const IMAGE_LOC           = CONTENT_URL . 'examples/library-svgrepo-com.svg';
const BRAND_COLOR         = '#808080'; // must be defined in HTML HEX for the brochure example
```

- **$total_days**: Defines how far out the local database should retrieve, and store values from LibCal
- **$client_id**: The client ID as provided from the LibCal API Application creation process (see the Overview of the LibCal API link above if confused)
- **$client_secret**: The application _secret_ as provided from the LibCal API application creation process
- **$cal_prefix**: The web URL that precedes your instance of LibCal; the default template is provided
- **$timezone_str**: The textual representation, as defined by a governing body, to set your local timezone

## Check the Examples

Once the `example.config.php` file has been renamed to `config.php` and your custom values have been supplied, take a look at the [Examples](examples/). These showcase different ways to take advantage of the data that you now have locally on your own server.

> [!NOTE]
> This currently does not take advantage of the Seats' module API endpoints. Our library does not subscribe to that module, so cannot test against it. We'd love some pull requests to help add support, however. This solution also currently does not provide access to *creating* data within LibCal, it is only reading data *from* LibCal. BGSU may have [some](https://github.com/BGSU-LITS/libcal) [solutions](https://github.com/BGSU-LITS/book) in that regard if that particular functionality is needed.

## Keeping Data Up-to-Date

It is recommended to setup a [cron job](https://en.wikipedia.org/wiki/Cron) (or Scheduled Task, if your webserver is IIS) to call the repository's `index.php` file on a regular basis. Our library calls it every 30 minutes due to the number of events we maintain, but once a day may be enough for your needs. To limit complexity in maintaining expired and/or deleted events, the database this solution manages wipes and recreates itself once a day; the task is not too terribly complex or inefficient so long as the **$total_days** is not ridiculously large.
