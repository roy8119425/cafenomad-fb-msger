<?php
require_once('config.php');

function callSendAPI($messageData) {
	global $WEBHOOK_API_URL;

	$ch = curl_init($WEBHOOK_API_URL);

	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $messageData);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

	$result = curl_exec($ch);

	curl_close($ch);
}

function receivedMessage($event) {
	$senderId = $event['sender']['id'];

	callSendAPI('{
		"recipient": {
			"id": "' . $senderId . '"
		},
		"message": {
			"text": "yooo"
		}
	}');
}

function receivedAuthentication($event) {
	trigger_error('===== receivedAuthentication');
}

function receivedDeliveryConfirmation($event) {
	trigger_error('===== receivedDeliveryConfirmation');
}

function receivedPostback($event) {
	trigger_error('===== receivedPostback');
}

function receivedMessageRead($event) {
	trigger_error('===== receivedMessageRead');
}

function receivedAccountLink($event) {
	trigger_error('===== receivedAccountLink');
}
?>
