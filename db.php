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

	$checkExistCmd = 'SELECT * FROM Preference WHERE fb_msg_id = \'' . $fbMsgId . '\'';
	$addDefCmd = 'INSERT INTO Preference (fb_msg_id, distance) VALUES (\'' . $fbMsgId . '\', 5000)';

	$result = mysqli_query($conn, $checkExistCmd) or trigger_error(mysqli_error($conn));

	if ($result && 1 === mysqli_num_rows($result)) {
		$pref = mysqli_fetch_assoc($result);
	} else if (mysqli_query($conn, $addDefCmd)) {
		$pref['distance'] = $DEF_PREF_DISTANCE;
	} else {
		trigger_error($fbMsgId . ' set filter error: ' . mysqli_error($conn));
	}

	mysqli_close($conn);
	return $pref;
}

function setPref($fbMsgId, $pref, $value) {
	$conn = init() or die();
	$sqlcmd = 'UPDATE Preference SET ' . $pref . ' = ' . $value . ' WHERE fb_msg_id = \'' . $fbMsgId . '\'';

	mysqli_query($conn, $sqlcmd) or trigger_error(mysqli_error($conn));

	mysqli_close($conn);
}
?>
