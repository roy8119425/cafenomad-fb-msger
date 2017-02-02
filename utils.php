<?php
function getRealDistance($lat1, $long1, $lat2, $long2) {
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
		$destStr = $destStr . '|' . $cafe['latitude'] . ',' . $cafe['longitude'];
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
	return addDistanceUnit($cafe['distance']) . ' | ' . $cafe['name'] . ' (' . $cafe['address'] . ')';
}

function getSubtitleText($cafe) {
	return
	'網路 ' . number_format($cafe['wifi'], 1) . '🌟  空位 ' . number_format($cafe['seat'], 1) . '🌟
' . '寧靜 ' . number_format($cafe['quiet'], 1) . '🌟  好喝 ' . number_format($cafe['tasty'], 1) . '🌟
' . '便宜 ' . number_format($cafe['cheap'], 1) . '🌟  氣氛 ' . number_format($cafe['music'], 1) . '🌟';
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
