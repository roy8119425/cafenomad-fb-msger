<?php
function getRealDistance($lat1, $long1, $lat2, $long2) {
	if (0 === strlen($lat1) || 0 === strlen($long1) || 0 === strlen($lat2) || 0 === strlen($long2)) {
		return PHP_INT_MAX;
	}
	$theta = $long1 - $long2;
	$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
	$dist = acos($dist);
	$dist = rad2deg($dist);
	$miles = $dist * 60 * 1.1515;

	// Return in meters
	return ($miles * 1.609344 * 1000);
}

function getGoogleDistance($lat, $long, &$cafeData) {
	global $GOOGLE_DISTANCE;

	if (0 === count($cafeData)) {
		trigger_error('cafeData is empty');
		return;
	}

	$destStr = '';
	for ($i = 0; $i < count($cafeData); $i++) {
		$cafe = $cafeData[$i];
		$destStr = $destStr . '|' . $cafe['lat'] . ',' . $cafe['long'];
	}

	$ch = curl_init(sprintf($GOOGLE_DISTANCE, $lat, $long, $destStr));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$result = json_decode(curl_exec($ch), true);
	curl_close($ch);

	if ('OK' !== $result['status']) {
		trigger_error('Error code: ' . $result['status']);
		if (isset($result['error_message'])) {
			trigger_error('Error msg: ' . $result['error_message']);
		}
		return;
	}

	for ($i = 0; $i < count($result['rows'][0]['elements']); $i++) {
		$element = $result['rows'][0]['elements'][$i];

		if ('OK' === $element['status']) {
			$cafeData[$i]['distance'] = $element['distance']['value'];
		}
	}
}

function getMsgerUserInfo($fbMsgId) {
	global $FB_GRAPH_API_URL;

	$ch = curl_init(sprintf($FB_GRAPH_API_URL, $fbMsgId, 'first_name,last_name'));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$userInfo = json_decode(curl_exec($ch), true);
	curl_close($ch);

	$conn = init() or die();
	$sqlcmd = sprintf("UPDATE Preference SET name = '%s' WHERE fb_msg_id = '%s'",
		mysqli_real_escape_string($conn, $userInfo['first_name'] . ' ' . $userInfo['last_name']),
		$fbMsgId
	);
	mysqli_query($conn, $sqlcmd) or trigger_error(mysqli_error($conn));
	mysqli_close($conn);

	return $userInfo;
}

function getFbPageId($url) {
	if (false === stripos($url, 'facebook.com')) {
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

function getFilter($fbMsgId) {
	$filter = getPref($fbMsgId);

	// Do something about filter

	return $filter;
}

function addDistanceUnit($distance) {
	if (1000 > $distance) {
		return round($distance) . 'M';
	} else {
		return round($distance / 1000, 2) . 'KM';
	}
}

function getTitleText($cafe) {
	return addDistanceUnit($cafe['distance']) . ' | ' . getHoursInfo($cafe) . ' | ' . $cafe['name'] . ' (' . $cafe['address'] . ')';
}

function getSubtitleText($cafe) {
	$wifi = ('0' === $cafe['wifi'] ? '- -　　' : number_format($cafe['wifi'], 1) . '🌟 ');
	$seat = ('0' === $cafe['seat'] ? '- -　　' : number_format($cafe['seat'], 1) . '🌟 ');
	$quiet = ('0' === $cafe['quiet'] ? '- -　　' : number_format($cafe['quiet'], 1) . '🌟 ');
	$tasty = ('0' === $cafe['tasty'] ? '- -　　' : number_format($cafe['tasty'], 1) . '🌟 ');
	$cheap = ('0' === $cafe['cheap'] ? '- -　　' : number_format($cafe['cheap'], 1) . '🌟 ');
	$music = ('0' === $cafe['music'] ? '- -　　' : number_format($cafe['music'], 1) . '🌟 ');

	return
	'網路 ' . $wifi . ' 空位 ' . $seat . '
' . '寧靜 ' . $quiet . ' 好喝 ' . $tasty . '
' . '便宜 ' . $cheap . ' 氣氛 ' . $music . '
' . '粉絲團評價 ' . number_format($cafe['fb_rating']) . '🌟(' . $cafe['fb_rating_count'] . ' 個評分)';
}

function getHoursInfo($cafe) {
	if (0 === strlen($cafe['hours'])) {
		return '無營業資訊';
	}

	$dayMap = Array(
		0 => Array('open' => 'sun_1_open', 'close' => 'sun_1_close'),
		1 => Array('open' => 'mon_1_open', 'close' => 'mon_1_close'),
		2 => Array('open' => 'tue_1_open', 'close' => 'tue_1_close'),
		3 => Array('open' => 'wed_1_open', 'close' => 'wed_1_close'),
		4 => Array('open' => 'thu_1_open', 'close' => 'thu_1_close'),
		5 => Array('open' => 'fri_1_open', 'close' => 'fri_1_close'),
		6 => Array('open' => 'sat_1_open', 'close' => 'sat_1_close')
	);

	$hours = json_decode($cafe['hours'], true);
	$timeRange = $dayMap[date('w', time())];

	if (!isset($hours[$timeRange['open']]) && !isset($hours[$timeRange['close']])) {
		return '今日未營業';
	} else {
		$ret = $hours[$timeRange['open']] . '~' . $hours[$timeRange['close']];

		if (strtotime($hours[$timeRange['open']]) < time() &&
			strtotime($hours[$timeRange['close']]) > time()) {
			$ret .= '(營業中)';
		} else {
			$ret .= '(休息中)';
		}

		return $ret;
	}
}

function fetchCmd($text) {
	if (0 === strcasecmp($text, 'cafe')) {
		return 'cafe';
	}
	if (0 === strcasecmp($text, 'help')) {
		return 'help';
	}
	return NULL;
}
?>
