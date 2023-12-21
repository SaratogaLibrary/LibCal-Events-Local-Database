<?php

require_once('../../config.php');

function getCategories() {
	// the API doesn't have a way to query for categories...
	return [
		59573 => 'Art, Music, and Movies',
		64132 => 'Art, Music, and Movies > Crafting',
		64286 => 'Art, Music, and Movies > Take Home Kits',
		42787 => 'Board Meetings',
		59572 => 'Book Groups and Literature Discussions',
		42785 => 'Book Sales',
		42786 => 'Closures and Notices',
		42794 => 'Computers and Technology',
		64133 => 'Computers and Technology > STEM / STEAM',
		59574 => 'Health and Wellness',
		59575 => 'Home and Garden',
		64134 => 'Home and Garden > Food and Cooking',
		64138 => 'Literacy and Languages',
		64139 => 'Literacy and Languages > Story Times',
		59576 => 'Local Interests and Community',
		64136 => 'Local Interests and Community > Games',
		64135 => 'Local Interests and Community > Parenting',
		64137 => 'Local Interests and Community > Saratoga History',
		68613 => 'Mobile Library',
		59577 => 'Travel and the Great Outdoors',
	];
}

function getActiveCategories() {
	// Returns categories that are found within the current version of the SQLite db
	try {
		//open the database
		$db = new PDO('sqlite:' . DATABASE_FILE);
		$result = $db->query('SELECT DISTINCT cat_id, category FROM events WHERE private != 1 AND cat_id IS NOT NULL AND category IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC);

		// close the database connection
		$db = NULL;

		$categories = [];
		foreach ($result as $category_match) {
			if (str_contains($category_match['cat_id'], '|')) {
				$categories = $categories + array_combine(explode('|', $category_match['cat_id']), explode('|', $category_match['category']));
			} else {
				$categories[$category_match['cat_id']] = $category_match['category'];
			}
		}
		asort($categories);
		return $categories;
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
	echo json_encode(getActiveCategories());
} else if (isset($_GET['mergeActive']) && $_GET['mergeActive'] == 1) {
	// This will handle additions to LibCal that aren't updated statically
	// It won't remove categories that were deleted from LibCal, however
	// Modifications to categories (if ID kept) will be represented
	echo json_encode(getCategories() + getActiveCategories());
} else {
	echo json_encode(getCategories());
}