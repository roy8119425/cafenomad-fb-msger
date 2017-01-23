<?php
function callSendAPI($messageData) {
	global $WEBHOOK_API_URL;

	$ch = curl_init($WEBHOOK_API_URL);

	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $messageData);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

	$result = curl_exec($ch);

	curl_close($ch);
}

function sendCafeData($recipientId, $cafeData) {
	global $GOOGLE_PLACE, $CAFENOMAD_SHOP_INFO;

	$elements = Array();

	foreach ($cafeData as $cafe) {
		$lat = $cafe['latitude'];
		$long = $cafe['longitude'];
		$e = Array();

		$e['title'] = getTitleText($cafe);
		$e['subtitle'] = getSubtitleText($cafe);
		$e['buttons'] = Array();

		array_push($e['buttons'], Array(
			'type' => 'web_url',
			'title' => '開啟地圖',
			'url' => sprintf($GOOGLE_PLACE, $lat, $long, $lat, $long)
		));
		array_push($e['buttons'], Array(
			'type' => 'web_url',
			'title' => '詳細資訊',
			'url' => sprintf($CAFENOMAD_SHOP_INFO, $cafe['id'])
		));
		if (!empty($cafe['url'])) {
			array_push($e['buttons'], Array(
				'type' => 'web_url',
				'title' => '前往粉絲團',
				'url' => $cafe['url']
			));
		}

		array_push($elements, $e);
	}

	callSendAPI('{
		"recipient": {
			"id": "' . $recipientId . '"
		},
		"message": {
			"attachment": {
				"type": "template",
				"payload": {
					"template_type":"generic",
					"elements": ' . json_encode($elements) . '
				}
			}
		}
	}');
}

function receivedMessage($event) {
	$senderId = $event['sender']['id'];
	$message = $event['message'];

	if (isset($message['quick_reply'])) {
		$quickReply = $message['quick_reply'];
	} else if (isset($message['attachments'])) {
		foreach ($message['attachments'] as $attachment) {
			if ('location' === $attachment['type']) {
				$payload = $attachment['payload'];
				$lat = $payload['coordinates']['lat'];
				$long = $payload['coordinates']['long'];

				$nearestCafe = findNearestCafe($lat, $long, getFilter($senderId));
				sendCafeData($senderId, $nearestCafe);
			}
		}
	} else {
		// Normal text
		$text = $message['text'];
	}
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
