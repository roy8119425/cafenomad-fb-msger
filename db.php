<?php
function init() {
	global $DB_ADDR, $DB_USER, $DB_PASS, $DB_NAME;

	$conn = mysqli_connect($DB_ADDR, $DB_USER, $DB_PASS);
	if (!$conn) {
		trigger_error(mysqli_error($conn));
		return false;
	}

	mysqli_select_db($conn, $DB_NAME);

	return $conn;
}

function getPref($fbMsgId) {
	$conn = init() or die();
	$pref = Array();

	$addDefCmd = 'INSERT INTO Preference (fb_msg_id) VALUES (\'' . $fbMsgId . '\')';
	$searchCmd = 'SELECT * FROM Preference WHERE fb_msg_id = \'' . $fbMsgId . '\'';

	$result = mysqli_query($conn, $searchCmd) or trigger_error(mysqli_error($conn));

	if (!$result || 1 !== mysqli_num_rows($result)) {
		mysqli_query($conn, $addDefCmd) or trigger_error(mysqli_error($conn));
		$result = mysqli_query($conn, $searchCmd) or trigger_error(mysqli_error($conn));
	}

	$pref = mysqli_fetch_assoc($result);

	mysqli_close($conn);
	return $pref;
}

function setPref($fbMsgId, $pref, $value) {
	$conn = init() or die();
	$sqlcmd = 'UPDATE Preference SET ' . $pref . ' = ' . $value . ' WHERE fb_msg_id = \'' . $fbMsgId . '\'';

	mysqli_query($conn, $sqlcmd) or trigger_error(mysqli_error($conn));

	mysqli_close($conn);
}

function clearPref($fbMsgId) {
	$conn = init() or die();
	$sqlcmd = (
		'UPDATE Preference SET ' .
		'wifi = 0, seat = 0, quiet = 0, ' .
		'tasty = 0, cheap = 0, music = 0, ' .
		'opening = 0 ' .
		'WHERE fb_msg_id = \'' . $fbMsgId . '\''
	);

	mysqli_query($conn, $sqlcmd) or trigger_error(mysqli_error($conn));
	mysqli_close($conn);
}

function getWaitingMsg($fbMsgId) {
	$conn = init() or die();
	$sqlcmd = (
		'SELECT * FROM WaitingMsg WHERE fb_msg_id = \'' . $fbMsgId . '\''
	);

	$result = mysqli_query($conn, $sqlcmd) or trigger_error(mysqli_error($conn));
	mysqli_close($conn);

	return 1 === mysqli_num_rows($result) ? mysqli_fetch_assoc($result) : NULL;
}

function setWaitingMsg($fbMsgId, $type, $data) {
	$conn = init() or die();
	$sqlcmd = sprintf("INSERT INTO WaitingMsg (fb_msg_id, type, data) VALUES ('%s', '%s', '%s')",
		$fbMsgId, $type, (is_null($data) ? NULL : json_encode($data))
	);

	mysqli_query($conn, $sqlcmd) or trigger_error(mysqli_error($conn));
	mysqli_close($conn);
}

function clearWaitingMsg($fbMsgId) {
	$conn = init() or die();
	$sqlcmd = (
		'DELETE FROM WaitingMsg WHERE fb_msg_id = \'' . $fbMsgId . '\''
	);

	mysqli_query($conn, $sqlcmd) or trigger_error(mysqli_error($conn));
	mysqli_close($conn);
}

function checkStoreExist($fbId) {
	$conn = init() or die();
	$sqlcmd = (
		'SELECT * FROM Store WHERE fb_id = \'' . $fbId . '\''
	);

	$result = mysqli_query($conn, $sqlcmd) or trigger_error(mysqli_error($conn));
	mysqli_close($conn);

	return 0 < mysqli_num_rows($result);
}

function addStore($fbData) {
	$conn = init() or die();
	$sqlcmd = sprintf("INSERT INTO Store (fb_id, name, address, picture, hours, lat, `long`, fb_rating, fb_rating_count) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d)",
		mysqli_real_escape_string($conn, $fbData['id']),
		mysqli_real_escape_string($conn, $fbData['name']),
		mysqli_real_escape_string($conn, $fbData['location']['street']),
		mysqli_real_escape_string($conn, $fbData['picture']['data']['url']),
		mysqli_real_escape_string($conn, json_encode($fbData['hours'])),
		mysqli_real_escape_string($conn, $fbData['location']['latitude']),
		mysqli_real_escape_string($conn, $fbData['location']['longitude']),
		mysqli_real_escape_string($conn, $fbData['overall_star_rating']),
		mysqli_real_escape_string($conn, $fbData['rating_count'])
	);

	mysqli_query($conn, $sqlcmd) or trigger_error(mysqli_error($conn));
	mysqli_close($conn);
}

function findStoreBy($cond) {
	$conn = init() or die();
	$condArray = Array();

	if (isset($cond['fb_id'])) {
		$fbId = mysqli_real_escape_string($conn, $cond['fb_id']);
		array_push($condArray, sprintf("fb_id = '%s'", $fbId));
	}
	if (isset($cond['cafenomad_id'])) {
		$cafenomadId = mysqli_real_escape_string($conn, $cond['cafenomad_id']);
		array_push($condArray, sprintf("cafenomad_id = '%s'", $cafenomadId));
	}

	$sqlcmd = 'SELECT * FROM Store WHERE ' . join(' AND ', $condArray);

	$result = mysqli_query($conn, $sqlcmd) or trigger_error(mysqli_error($conn));
	mysqli_close($conn);

	return $result;
}

function findNearestCafe($lat, $long, $filter) {
	$conn = init() or die();
	$sqlcmd = 'SELECT * FROM Store';
	$result = mysqli_query($conn, $sqlcmd) or trigger_error(mysqli_error($conn));
	mysqli_close($conn);

	$filteredData = Array();

	while ($cafe = mysqli_fetch_assoc($result)) {
		$cafe['distance'] = getRealDistance($lat, $long, $cafe['lat'], $cafe['long']);

		// Do filter
		if (isset($filter['distance']) && $filter['distance'] < $cafe['distance']) {
			continue;
		}
		if (isset($filter['wifi']) && 0 < $cafe['wifi'] && $filter['wifi'] > $cafe['wifi']) {
			continue;
		}
		if (isset($filter['seat']) && 0 < $cafe['seat'] && $filter['seat'] > $cafe['seat']) {
			continue;
		}
		if (isset($filter['quiet']) && 0 < $cafe['quiet'] && $filter['quiet'] > $cafe['quiet']) {
			continue;
		}
		if (isset($filter['tasty']) && 0 < $cafe['tasty'] && $filter['tasty'] > $cafe['tasty']) {
			continue;
		}
		if (isset($filter['cheap']) && 0 < $cafe['cheap'] && $filter['cheap'] > $cafe['cheap']) {
			continue;
		}
		if (isset($filter['music']) && 0 < $cafe['music'] && $filter['music'] > $cafe['music']) {
			continue;
		}
		if (isset($filter['opening']) && 0 < $filter['opening']) {
			$blOpening = false;

			getHoursInfo($cafe, $blOpening);

			if (!$blOpening) {
				continue;
			}
		}

		array_push($filteredData, $cafe);
	}

	if (0 < count($filteredData)) {
		usort($filteredData, function($a, $b) {
			return $a['distance'] - $b['distance'];
		});

		$filteredData = array_slice($filteredData, 0, 5);
		getGoogleDistance($lat, $long, $filteredData);
	}

	return $filteredData;
}
?>
