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
		'tasty = 0, cheap = 0, music = 0 ' .
		'WHERE fb_msg_id = \'' . $fbMsgId . '\''
	);

	mysqli_query($conn, $sqlcmd) or trigger_error(mysqli_error($conn));

	mysqli_close($conn);
}
?>
