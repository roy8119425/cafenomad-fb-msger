<?php
require_once('cafenomad_api.php');
require_once('config.php');
require_once('db.php');
require_once('error.php');
require_once('utils.php');

$dupIdArray = Array(
	'0afcce7c-0d43-466b-8430-286b9608dd5c', '45666880-16fa-40bd-919a-7d22e59d479c',
	'469220ff-8419-4d7e-9f16-f78d93f28953', '04111f17-09e5-4989-b31f-974fa2406518'
);

function update($cafe) {
	global $HOSTNAME;
	global $dupIdArray;

	// Skip duplicate cafenomad id
	if (in_array($cafe['id'], $dupIdArray)) {
		trigger_error(sprintf('Skip dup cafe: %s(%s)', $cafe['id'], $cafe['name']));
		return;
	}

	// Get fb page id from db first. If not exists, try to fetch from cafe url
	$result = listStore(Array('cafenomad_id' => $cafe['id']));
	if (1 === mysqli_num_rows($result)) {
		$record = mysqli_fetch_assoc($result);
		$pageId = $record['fb_id'];
		$action = 'update';
	} else {
		$pageId = getFbPageId($cafe['url']);
		if (is_null($pageId)) {
			trigger_error($cafe['name'] . 'Failed to fetch page id from: ' . $cafe['url']);
			return;
		}
		$action = 'insert';
	}
	mysqli_free_result($result);

	// Get fb data by explorer api
	$fbData = getFbData($pageId);
	if (isset($fbData['error']) || !isset($fbData['id'])) {
		trigger_error($cafe['name'] . 'Failed to get fb data by page id: ' . $pageId);
		return;
	}

	// If the fb page id is already in db, do udate only
	$result = listStore(Array('fb_id' => $fbData['id']));
	if (1 === mysqli_num_rows($result)) {
		$action = 'update';
	}

	$picName = downloadFbPicture($fbData['id']);
	$lat = (isset($fbData['location']['latitude']) ? $fbData['location']['latitude'] : $cafe['latitude']);
	$long = (isset($fbData['location']['longitude']) ? $fbData['location']['longitude'] : $cafe['longitude']);

	$conn = init() or die();
	switch ($action) {
	case 'insert':
		$sqlcmd = sprintf("INSERT INTO Store (fb_id, cafenomad_id, name, address, picture, hours, lat, `long`, fb_rating, fb_rating_count, wifi, seat, quiet, tasty, cheap, music, limited_time, socket, standing_desk) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
			mysqli_real_escape_string($conn, $fbData['id']),
			mysqli_real_escape_string($conn, $cafe['id']),
			mysqli_real_escape_string($conn, $fbData['name']),
			mysqli_real_escape_string($conn, $fbData['location']['street']),
			mysqli_real_escape_string($conn, $HOSTNAME . '/images/' . $picName),
			mysqli_real_escape_string($conn, json_encode($fbData['hours'])),
			mysqli_real_escape_string($conn, $lat),
			mysqli_real_escape_string($conn, $long),
			mysqli_real_escape_string($conn, $fbData['overall_star_rating']),
			mysqli_real_escape_string($conn, $fbData['rating_count']),
			mysqli_real_escape_string($conn, $cafe['wifi']),
			mysqli_real_escape_string($conn, $cafe['seat']),
			mysqli_real_escape_string($conn, $cafe['quiet']),
			mysqli_real_escape_string($conn, $cafe['tasty']),
			mysqli_real_escape_string($conn, $cafe['cheap']),
			mysqli_real_escape_string($conn, $cafe['music']),
			mysqli_real_escape_string($conn, $cafe['limited_time']),
			mysqli_real_escape_string($conn, $cafe['socket']),
			mysqli_real_escape_string($conn, $cafe['standing_desk'])
		);
		break;
	case 'update':
		$sqlcmd = sprintf("UPDATE Store SET cafenomad_id = '%s', name = '%s', address = '%s', picture = '%s', hours = '%s', lat = '%s', `long` = '%s', fb_rating = '%s', fb_rating_count = %d, wifi = '%s', seat = '%s', quiet = '%s', tasty = '%s', cheap = '%s', music = '%s', limited_time = '%s', socket = '%s', standing_desk = '%s' WHERE fb_id = '%s'",
			mysqli_real_escape_string($conn, $cafe['id']),
			mysqli_real_escape_string($conn, $fbData['name']),
			mysqli_real_escape_string($conn, $fbData['location']['street']),
			mysqli_real_escape_string($conn, $HOSTNAME . '/images/' . $picName),
			mysqli_real_escape_string($conn, json_encode($fbData['hours'])),
			mysqli_real_escape_string($conn, $lat),
			mysqli_real_escape_string($conn, $long),
			mysqli_real_escape_string($conn, $fbData['overall_star_rating']),
			mysqli_real_escape_string($conn, $fbData['rating_count']),
			mysqli_real_escape_string($conn, $cafe['wifi']),
			mysqli_real_escape_string($conn, $cafe['seat']),
			mysqli_real_escape_string($conn, $cafe['quiet']),
			mysqli_real_escape_string($conn, $cafe['tasty']),
			mysqli_real_escape_string($conn, $cafe['cheap']),
			mysqli_real_escape_string($conn, $cafe['music']),
			mysqli_real_escape_string($conn, $cafe['limited_time']),
			mysqli_real_escape_string($conn, $cafe['socket']),
			mysqli_real_escape_string($conn, $cafe['standing_desk']),
			mysqli_real_escape_string($conn, $fbData['id'])
		);
		break;
	default:
		trigger_error('Unknown flag of shop: ' . $cafe['name']);
	}

	if (!is_null($sqlcmd) && !mysqli_query($conn, $sqlcmd)) {
		trigger_error(mysqli_error($conn));
		trigger_error($sqlcmd);
	}
	mysqli_close($conn);
}

function main($argc, $argv) {
	$cafeData = getAllCafeData();

	if (1 < $argc && 0 < strlen($argv[1])) {
		$cafenomad_id = $argv[1];

		foreach ($cafeData as $cafe) {
			if ($cafe['id'] === $cafenomad_id) {
				if (2 < $argc && 0 < strlen($argv[2])) {
					$cafe['url'] = $argv[2];
				}
				update($cafe);
				break;
			} else {
				continue;
			}
		}
	} else {
		foreach ($cafeData as $cafe) {
			update($cafe);
		}
	}
}

main($argc, $argv);
?>
