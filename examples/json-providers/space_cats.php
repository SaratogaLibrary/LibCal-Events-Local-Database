<?php

require_once('../../config.php');

function getSpaceCategories() {
	// Returns categories that are found within the current version of the SQLite db
	try {
		//open the database
		$db = new PDO('sqlite:' . DATABASE_FILE);
		$result = $db->query('SELECT id, lid, name, formid, public, admin_only, terms_and_conditions, description, google FROM space_categories')->fetchAll(PDO::FETCH_ASSOC);

		// close the database connection
		$db = NULL;
		
		return $result;
	}
	catch (PDOException $e) {
		if ($e->getCode() === 14) {
			die('<pre>' . print_r($e,1).'</pre>');
			return 'false';
		}
	}
}

// Set headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if (isset($_GET['active']) && $_GET['active'] == 1) {
	echo json_encode(getSpaceCategories());
} else {
	echo json_encode(getSpaceCategories());
}