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
	return
	'網路 ' . number_format($cafe['wifi'], 1) . '🌟  空位 ' . number_format($cafe['seat'], 1) . '🌟
' . '寧靜 ' . number_format($cafe['quiet'], 1) . '🌟  好喝 ' . number_format($cafe['tasty'], 1) . '🌟
' . '便宜 ' . number_format($cafe['cheap'], 1) . '🌟  氣氛 ' . number_format($cafe['music'], 1) . '🌟
' . '粉絲團評價 ' . number_format($cafe['fb_rating']) . '🌟(' . $cafe['fb_rating_count'] . ' 個評分)';
}

function getHoursInfo($cafe) {
	if (0 === strlen($cafe['hours'])) {
		return '';
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

		if (strtotime($hours[$timeRange['open']]) < time() && $hours[$timeRange['close']] > time()) {
			$ret .= '(營業中)';
		} else {
			$ret .= '(已休息)';
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
