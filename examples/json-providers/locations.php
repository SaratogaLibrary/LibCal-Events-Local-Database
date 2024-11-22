<?php

require_once('../../config.php');

function getActiveLocations() {
	// Returns locations that are found within the current version of the SQLite db
	try {
		// open the database
		$db = new PDO('sqlite:' . DATABASE_FILE);
		$result = $db->query('SELECT * FROM spaces')->fetchAll(PDO::FETCH_ASSOC);

		// close the database connection
		$db = NULL;

		$locations = [];
		foreach ($result as $location_match) {
			$locations[$location_match['id']] = $location_match;
		}
		asort($locations);
		return $locations;
	}
	catch (PDOException $e) {
		if ($e->getCode() === 14) {
			return false;
		}
	}
}

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo json_encode(getActiveLocations());