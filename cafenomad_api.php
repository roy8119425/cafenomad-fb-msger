<?php
function getAllCafeData() {
	global $CAFENOMAD_API_URL;

	$ch = curl_init($CAFENOMAD_API_URL);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$result = curl_exec($ch);
	curl_close($ch);

	return json_decode($result, true);
}
?>
