<?php

require_once('../../config.php');

function getAudiences() {
	// the API doesn't have a way to query for all audiences... Manually add your own audiences and IDs as a fallback
	return [
		1623 => 'Adults',
		1628 => 'All Ages',
		1604 => 'Children',
		1627 => 'Teens'
	];
}

function getActiveAudiences() {
	// Returns audiences that are found within the current version of the SQLite db
	try {
		// open the database
		$db = new PDO('sqlite:' . DATABASE_FILE);
		$result = $db->query('SELECT DISTINCT audience_id, audience FROM events WHERE private != 1 AND audience_id IS NOT NULL AND audience IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC);

		// close the database connection
		$db = NULL;

		$audiences = [];
		foreach ($result as $audience_match) {
			if (str_contains($audience_match['audience_id'], '|')) {
				$audiences = $audiences + array_combine(explode('|', $audience_match['audience_id']), explode('|', $audience_match['audience']));
			} else {
				$audiences[$audience_match['audience_id']] = $audience_match['audience'];
			}
		}
		asort($audiences);
		return $audiences;
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

if (isset($_GET['active']) && $_GET['active'] == 1) {
	echo json_encode(getAudiences());
} else if (isset($_GET['mergeActive']) && $_GET['mergeActive'] == 1) {
	// This will handle additions to LibCal that aren't updated statically
	// It won't remove Audiences that were deleted from LibCal, however
	// Modifications to audiences (if ID kept) will be represented
	echo json_encode(getAudiences() + getActiveAudiences());
} else {
	echo json_encode(getAudiences());
}