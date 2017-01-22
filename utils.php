<?php
function distance($lat1, $long1, $lat2, $long2) {
	$theta = $long1 - $long2;
	$dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
	$dist = acos($dist);
	$dist = rad2deg($dist);
	$miles = $dist * 60 * 1.1515;

	// Return in meters
	return ($miles * 1.609344 * 1000);
}

function getFilter($fbMsgId) {
	$filter = Array();

	$filter['distance'] = 5000;

	return $filter;
}
?>
