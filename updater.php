<?php
require_once('cafenomad_api.php');
require_once('config.php');
require_once('db.php');
require_once('error.php');

function getFbPageId($url) {
	if (!stripos($url, 'facebook.com')) {
		return NULL;
	}

	$pageId = preg_replace('/https?:\/\/(.*\.)?facebook\.com(\/pg|\/pages\/.*)?\//i', '', $url);
	$pageId = preg_replace('/\?.*/i', '', $pageId);
	$pageId = current(explode('/', $pageId));
	if ($pos = strrpos($pageId, '-')) {
		$pageId = substr($pageId, $pos + 1);
	}
	return $pageId;
}

function getFbData($pageId) {
	global $FB_EXPLORER_ACCESS_TOKEN;

	$ch = curl_init('https://graph.facebook.com/v2.8/' . $pageId . '?fields=id%2Cname%2Clocation%2Cpicture%2Chours%2Coverall_star_rating%2Crating_count&access_token=' . $FB_EXPLORER_ACCESS_TOKEN);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$result = curl_exec($ch);
	curl_close($ch);

	return json_decode($result, true);
}

function update($cafe) {
	$conn = init() or die();
	$searchCmd = sprintf("SELECT * FROM Store WHERE cafenomad_id = '%s'",
		mysqli_real_escape_string($conn, $cafe['id'])
	);
	$result = mysqli_query($conn, $searchCmd) or trigger_error(mysqli_error($conn));
	mysqli_close($conn);

	if (!$result || 0 >= mysqli_num_rows($result)) {
		$pageId = getFbPageId($cafe['url']);
		if (is_null($pageId)) {
			trigger_error($cafe['name'] . 'Failed to fetch page id from: ' . $cafe['url']);
			return;
		}
		$action = 'insert';
	} else if (1 === mysqli_num_rows($result)) {
		$record = mysqli_fetch_assoc($result);
		$pageId = $record['fb_id'];
		$action = 'update';
	} else {
		trigger_error('Find too many records of cafenomad id: ' . $cafe['id']);
		return;
	}

	$fbData = getFbData($pageId);

	if (isset($fbData['error']) || !isset($fbData['id'])) {
		trigger_error($cafe['name'] . 'Failed to get fb data by page id: ' . $pageId);
		return;
	}

	$conn = init() or die();
	switch ($action) {
	case 'insert':
		$sqlcmd = sprintf("INSERT INTO Store (fb_id, cafenomad_id, name, address, picture, hours, lat, `long`, fb_rating, fb_rating_count, wifi, seat, quiet, tasty, cheap, music, limited_time, socket, standing_desk) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
			mysqli_real_escape_string($conn, $fbData['id']),
			mysqli_real_escape_string($conn, $cafe['id']),
			mysqli_real_escape_string($conn, $fbData['name']),
			mysqli_real_escape_string($conn, $fbData['location']['street']),
			mysqli_real_escape_string($conn, $fbData['picture']['data']['url']),
			mysqli_real_escape_string($conn, json_encode($fbData['hours'])),
			mysqli_real_escape_string($conn, $fbData['location']['latitude']),
			mysqli_real_escape_string($conn, $fbData['location']['longitude']),
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
			mysqli_real_escape_string($conn, $fbData['picture']['data']['url']),
			mysqli_real_escape_string($conn, json_encode($fbData['hours'])),
			mysqli_real_escape_string($conn, $fbData['location']['latitude']),
			mysqli_real_escape_string($conn, $fbData['location']['longitude']),
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
