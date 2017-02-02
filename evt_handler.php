<?php
function callSendAPI($messageData) {
	global $WEBHOOK_API_URL;

	$ch = curl_init($WEBHOOK_API_URL);

	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $messageData);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

	$result = json_decode(curl_exec($ch), true);
	if (isset($result['error'])) {
		trigger_error(json_encode($result));
	}

	curl_close($ch);
}

function sendTextMessage($recipientId, $text) {
	callSendAPI('{
		"recipient": {
			"id": "' . $recipientId . '"
		},
		"message": {
			"text": "' . $text . '"
		}
	}');
}

function sendButtons($recipientId, $text, $buttons) {
	callSendAPI('{
		"recipient": {
			"id": "' . $recipientId . '"
		},
		"message": {
			"attachment": {
				"type": "template",
				"payload": {
					"template_type": "button",
					"text": "' . $text . '",
					"buttons": ' . json_encode($buttons) . '
				}
			}
		}
	}');
}

function sendQuickReply($recipientId, $text, $quickReplies) {
	callSendAPI('{
		"recipient": {
			"id": "' . $recipientId . '"
		},
		"message": {
			"text": "' . $text . '",
			"quick_replies": ' . json_encode($quickReplies) . '
		}
	}');
}

function sendAction($recipientId, $action) {
	callSendAPI('{
		"recipient": {
			"id": "' . $recipientId . '"
		},
		"sender_action": "' . $action . '"
	}');
}

function sendHelp($recipientId) {
	$buttons = Array(
		Array(
			'type' => 'postback',
			'title' => '偏好設定',
			'payload' => 'show_pref'
		),
		Array(
			'type' => 'postback',
			'title' => '其他',
			'payload' => 'other_help'
		)
	);

	sendButtons($recipientId, '有什麼能幫助您的嗎？', $buttons);
}

function sendModifyPref($recipientId, $pref) {
	$prefText = Array(
		'wifi' => '網路',
		'seat' => '空位',
		'quiet' => '寧靜',
		'tasty' => '好喝',
		'cheap' => '便宜',
		'music' => '氣氛'
	);

	sendQuickReply($recipientId, '請選擇您希望的「' . $prefText[$pref] . '」最低標準', Array(
		Array(
			'content_type' => 'text',
			'title' => '3🌟 ',
			'payload' => $pref . '_3'
		),
		Array(
			'content_type' => 'text',
			'title' => '4🌟 ',
			'payload' => $pref . '_4'
		),
		Array(
			'content_type' => 'text',
			'title' => '5🌟 ',
			'payload' => $pref . '_5'
		),
		Array(
			'content_type' => 'text',
			'title' => '不在意',
			'payload' => $pref . '_0'
		)
	));
}

function sendLocationHint($recipientId, $text) {
	sendQuickReply($recipientId, $text, Array(
		Array(
			'content_type' => 'location'
		)
	));
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

function sendPref($recipientId) {
	$pref = getPref($recipientId);
	$msg = '您目前的偏好設定：\n';

	$msg .= ('網路：' . (0 < $pref['wifi'] ? $pref['wifi'] . '🌟 ' : '不在意') . '\n');
	$msg .= ('空位：' . (0 < $pref['seat'] ? $pref['seat'] . '🌟 ' : '不在意') . '\n');
	$msg .= ('寧靜：' . (0 < $pref['quiet'] ? $pref['quiet'] . '🌟 ' : '不在意') . '\n');
	$msg .= ('好喝：' . (0 < $pref['tasty'] ? $pref['tasty'] . '🌟 ' : '不在意') . '\n');
	$msg .= ('便宜：' . (0 < $pref['cheap'] ? $pref['cheap'] . '🌟 ' : '不在意') . '\n');
	$msg .= ('氣氛：' . (0 < $pref['music'] ? $pref['music'] . '🌟 ' : '不在意') . '\n');

	$buttons = Array(
		Array(
			'type' => 'postback',
			'title' => '我想修改',
			'payload' => 'modify_pref'
		),
		Array(
			'type' => 'postback',
			'title' => '全部清除',
			'payload' => 'clear_pref'
		)
	);

	sendButtons($recipientId, $msg, $buttons);
}

function receivedMessage($event) {
	$senderId = $event['sender']['id'];
	$message = $event['message'];

	if (isset($message['quick_reply']) && isset($message['quick_reply']['payload'])) {
		$payload = $message['quick_reply']['payload'];

		if (!empty($payload)) {
			processQuickReply($senderId, $payload);
		}
	} else if (isset($message['attachments'])) {
		foreach ($message['attachments'] as $attachment) {
			if ('location' === $attachment['type']) {
				$payload = $attachment['payload'];
				$lat = $payload['coordinates']['lat'];
				$long = $payload['coordinates']['long'];

				sendAction($senderId, 'typing_on');

				$nearestCafe = findNearestCafe($lat, $long, getFilter($senderId));
				if (0 < count($nearestCafe)) {
					sendCafeData($senderId, $nearestCafe);
				} else {
					sendTextMessage($senderId, '很抱歉，在您附近搜尋不到任何咖啡廳');
				}
			}
		}
	} else {
		// Normal text
		$text = $message['text'];
		$cmd = fetchCmd($text);

		if (!is_null($cmd)) {
			processCmd($senderId, $cmd);
		}
	}
}

function receivedAuthentication($event) {
	trigger_error('===== receivedAuthentication');
}

function receivedDeliveryConfirmation($event) {
	trigger_error('===== receivedDeliveryConfirmation');
}

function receivedPostback($event) {
	$senderId = $event['sender']['id'];
	$payload = $event['postback']['payload'];

	switch ($payload) {
		case 'get_started':
			sendQuickReply($senderId, '感謝您的使用，請問您想開始設定個人偏好嗎？', Array(
				Array(
					'content_type' => 'text',
					'title' => '立即開始',
					'payload' => 'set_pref_now'
				),
				Array(
					'content_type' => 'text',
					'title' => '稍後再說',
					'payload' => 'set_pref_later'
				)
			));
			break;
		case 'search_cafe':
			sendLocationHint($senderId, '請點擊下方按鈕，或傳送位置資訊給我們');
			break;
		case 'show_pref':
			sendPref($senderId);
			break;
		case 'modify_pref':
			sendModifyPref($senderId, 'wifi');
			break;
		case 'clear_pref':
			clearPref($senderId);
			sendPref($senderId);
			break;
		case 'other_help':
			sendTextMessage($senderId, '請直接在此留言告訴我們您需要什麼協助，我們會盡快回覆您');
			break;
		default:
			trigger_error('Known postback payload: ' . $payload);
	}
}

function receivedMessageRead($event) {
	trigger_error('===== receivedMessageRead');
}

function receivedAccountLink($event) {
	trigger_error('===== receivedAccountLink');
}

function processCmd($senderId, $cmd) {
	switch ($cmd) {
		case 'cafe':
			sendLocationHint($senderId, '請點擊下方按鈕，或傳送位置資訊給我們');
			break;
		case 'help':
			sendHelp($senderId);
			break;
		default:
			trigger_error('Known cmd: ' . $cmd);
	}
}

function processQuickReply($senderId, $payload) {
	sendAction($senderId, 'typing_on');

	$prefFlowNext = Array(
		'wifi' => 'seat',
		'seat' => 'quiet',
		'quiet' => 'tasty',
		'tasty' => 'cheap',
		'cheap' => 'music',
		'music' => NULL
	);

	switch ($payload) {
		case 'set_pref_now':
			sendModifyPref($senderId, 'wifi');
			break;
		case 'set_pref_later':
			sendLocationHint($senderId, '感謝您！之後若想設定嚴選條件，請點擊左下方選單或者直接輸入 help。現在您可以傳送位置資訊給我們，來找出附近的優質咖啡廳囉！');
			break;
		case 'wifi_0': case 'wifi_3': case 'wifi_4': case 'wifi_5':
		case 'seat_0': case 'seat_3': case 'seat_4': case 'seat_5':
		case 'quiet_0': case 'quiet_3': case 'quiet_4': case 'quiet_5':
		case 'tasty_0': case 'tasty_3': case 'tasty_4': case 'tasty_5':
		case 'cheap_0': case 'cheap_3': case 'cheap_4': case 'cheap_5':
		case 'music_0': case 'music_3': case 'music_4': case 'music_5':
			list($pref, $value) = explode('_', $payload);
			setPref($senderId, $pref, $value);
			if (!is_null($prefFlowNext[$pref])) {
				sendModifyPref($senderId, $prefFlowNext[$pref]);
			} else {
				sendLocationHint($senderId, '修改完成！若想重新設定，請點擊左下方選單或者直接輸入 help。現在您可以傳送位置資訊給我們，來找出附近的優質咖啡廳囉！');
			}
			break;
		default:
			trigger_error('Known quick reply payload: ' . $payload);
	}
}
?>
