<?php
require_once('cafenomad_api.php');
require_once('config.php');
require_once('db.php');
require_once('error.php');
require_once('evt_handler.php');
require_once('utils.php');

//Set this Verify Token Value on your Facebook App
if (isset($_REQUEST['hub_verify_token']) &&
		$_REQUEST['hub_verify_token'] === $WEBHOOK_VERIFY_TOKEN) {
	echo $_REQUEST['hub_challenge'];
	return;
}

$data = json_decode(file_get_contents('php://input'), true);

// Make sure this is a page subscription
if ('page' === $data['object']) {
	// Iterate over each entry - there may be multiple if batched
	foreach ($data['entry'] as $entry) {
		$pageId = $entry['id'];
		$timeOfEvent = $entry['time'];

		// Iterate over each messaging event
		foreach ($entry['messaging'] as $event) {
			if ($event['message']) {
				receivedMessage($event);
			} else if ($event['optin']) {
				receivedAuthentication($event);
			} else if ($event['delivery']) {
				receivedDeliveryConfirmation($event);
			} else if ($event['postback']) {
				receivedPostback($event);
			} else if ($event['read']) {
				receivedMessageRead($event);
			} else if ($event['account_linking']) {
				receivedAccountLink($event);
			} else {
				trigger_error('Unknown event: '.json_encode($event));
			}
		}
	}
}
?>
