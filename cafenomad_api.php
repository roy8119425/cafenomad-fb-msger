<?php
function getAllCafeData() {
	global $CAFENOMAD_API_URL;

	$ch = curl_init($CAFENOMAD_API_URL);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$result = curl_exec($ch);
	curl_close($ch);

	return json_decode($result, true);
}

function findNearestCafe($lat, $long, $filter) {
	$cafeData = getAllCafeData();
	$filteredData = Array();

	foreach ($cafeData as &$cafe) {
		$cafe['distance'] = getRealDistance($lat, $long, $cafe['latitude'], $cafe['longitude']);

		// Do filter
		if (isset($filter['distance']) && $filter['distance'] < $cafe['distance']) {
			continue;
		}

		array_push($filteredData, $cafe);
	}

	usort($filteredData, function($a, $b) {
		return $a['distance'] - $b['distance'];
	});

	$filteredData = array_slice($filteredData, 0, 5);
	getGoogleDistance($lat, $long, $filteredData);
	return $filteredData;
}
?>
