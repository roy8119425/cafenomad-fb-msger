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

function sendModifyPref($recipientId, $pref) {
	$prefText = Array(
		'wifi' => '網路',
		'seat' => '空位',
		'quiet' => '寧靜',
		'tasty' => '好喝',
		'cheap' => '便宜',
		'music' => '氣氛'
	);

	if (isset($prefText[$pref])) {
		sendQuickReply($recipientId, '請選擇您希望的「' . $prefText[$pref] . '」最低標準', Array(
			Array(
				'content_type' => 'text',
				'title' => '5🌟 ',
				'payload' => $pref . '_5'
			),
			Array(
				'content_type' => 'text',
				'title' => '4🌟 ',
				'payload' => $pref . '_4'
			),
			Array(
				'content_type' => 'text',
				'title' => '3🌟 ',
				'payload' => $pref . '_3'
			),
			Array(
				'content_type' => 'text',
				'title' => '不在意',
				'payload' => $pref . '_0'
			)
		));
	} else {
		switch ($pref) {
			case 'opening':
				sendQuickReply($recipientId, '是否只列出「正在營業中」的店家？', Array(
					Array(
						'content_type' => 'text',
						'title' => '是',
						'payload' => 'opening_1'
					),
					Array(
						'content_type' => 'text',
						'title' => '否',
						'payload' => 'opening_0'
					)
				));
				break;
			default:
				trigger_error('Unsupported pref: ' . $pref);
		}
	}
}

function sendLocationHint($recipientId, $text) {
	sendQuickReply($recipientId, $text, Array(
		Array(
			'content_type' => 'location'
		)
	));
}

function sendCafeData($recipientId, $cafeData) {
	global $FB_PAGE_URL, $GOOGLE_PLACE;

	$elements = Array();

	foreach ($cafeData as $cafe) {
		$lat = $cafe['lat'];
		$long = $cafe['long'];
		$e = Array();

		$e['title'] = getTitleText($cafe);
		$e['subtitle'] = getSubtitleText($cafe);
		$e['item_url'] = sprintf($FB_PAGE_URL, $cafe['fb_id']);
		$e['image_url'] = $cafe['picture'];
		$e['buttons'] = Array();

		array_push($e['buttons'], Array(
			'type' => 'web_url',
			'title' => '開啟地圖',
			'url' => sprintf($GOOGLE_PLACE, $lat, $long, $lat, $long)
		));
		array_push($e['buttons'], Array(
			'type' => 'postback',
			'title' => '詳細資訊/評價',
			'payload' => 'details#' . $cafe['fb_id'] . '#' . $cafe['cafenomad_id']
		));
		array_push($e['buttons'], Array(
			'type' => 'element_share'
		));

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

function sendAddCafe($recipientId) {
	global $CAFENOMAD_CONTRIBUTE_URL;

	$msg = '請選擇新增方式';

	$buttons = Array(
		Array(
			'type' => 'postback',
			'title' => '透過粉絲團網址或 ID',
			'payload' => 'add_by_fb_page'
		),
		Array(
			'type' => 'web_url',
			'title' => '透過 Cafenomad.tw',
			'url' => $CAFENOMAD_CONTRIBUTE_URL
		)
	);
	sendButtons($recipientId, $msg, $buttons);
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
	$msg .= ('營業中：' . (0 < $pref['opening'] ? '是' : '否') . '\n');

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

function sendDetails($recipientId, $payload) {
	global $FB_ABOUT_URL, $FB_REVIEW_URL, $CAFENOMAD_SHOP_INFO;

	$msg = '您想看什麼資訊呢？';
	list($skip, $pageId, $cafenomadId) = explode('#', $payload);

	$buttons = Array(
		Array(
			'type' => 'web_url',
			'title' => '粉絲團詳細資訊',
			'url' => sprintf($FB_ABOUT_URL, $pageId)
		),
		Array(
			'type' => 'web_url',
			'title' => '粉絲團評價',
			'url' => sprintf($FB_REVIEW_URL, $pageId)
		)
	);

	if (0 < strlen($cafenomadId)) {
		array_push($buttons, Array(
			'type' => 'web_url',
			'title' => 'Cafenomad.tw 詳細資訊',
			'url' => sprintf($CAFENOMAD_SHOP_INFO, $cafenomadId)
		));
	}

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
					sendTextMessage($senderId, '很抱歉，在您附近搜尋不到任何符合的咖啡廳，可以將偏好設定放寬鬆點再試試看\n（也有可能是附近真的沒有咖啡廳～那麼可以透過左下方選單來新增咖啡廳喲！）');
				}
			} else if ('fallback' === $attachment['type'] && isset($attachment['url'])) {
				$waitingMsg = getWaitingMsg($senderId);

				if (!is_null($waitingMsg)) {
					processWaitingMsg($senderId, $waitingMsg, $attachment['url']);
				}
			}
		}
	} else {
		// Normal text
		$text = $message['text'];
		$waitingMsg = getWaitingMsg($senderId);

		if (!is_null($waitingMsg)) {
			processWaitingMsg($senderId, $waitingMsg, $text);
		} else {
			$googleLoc = getGoogleLocation($text);

			if ('TOO_MANY_RESULTS' === $googleLoc['status']) {
				sendTextMessage($senderId, '查到太多資料，無法分辨您想搜尋的地點，請提供更精確的位置');
			} else if ('SUCCESS' === $googleLoc['status']) {
				sendTextMessage($senderId, '搜尋地點：' . $googleLoc['address']);
				sendAction($senderId, 'typing_on');

				$nearestCafe = findNearestCafe($googleLoc['lat'], $googleLoc['long'], getFilter($senderId));

				if (0 < count($nearestCafe)) {
					sendCafeData($senderId, $nearestCafe);
				} else {
					sendTextMessage($senderId, '很抱歉，在此地點附近搜尋不到任何符合的咖啡廳，可以將偏好設定放寬鬆點再試試看\n（也有可能是該地點附近真的沒有咖啡廳～那麼可以透過左下方選單來新增咖啡廳喲！）');
				}
			}
		}
	}

	clearWaitingMsg($senderId);
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
			getPref($senderId);	// For preference initialization
			getMsgerUserInfo($senderId);

			sendQuickReply($senderId, '感謝您的使用，請問您想開始設定個人偏好嗎？\n（如果下面沒有出現按鈕，請使用左下方的選單也可以開始設定唷～）', Array(
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
		case 'add_cafe':
			sendAddCafe($senderId);
			break;
		case 'add_by_fb_page':
			setWaitingMsg($senderId, 'add_fb_page', NULL);
			sendTextMessage($senderId, '請輸入粉絲團網址或 ID：');
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
		default:
			if (0 === strpos($payload, 'details')) {
				sendDetails($senderId, $payload);
			} else {
				trigger_error('Unknown postback payload: ' . $payload);
			}
	}
}

function receivedMessageRead($event) {
	trigger_error('===== receivedMessageRead');
}

function receivedAccountLink($event) {
	trigger_error('===== receivedAccountLink');
}

function processQuickReply($senderId, $payload) {
	sendAction($senderId, 'typing_on');

	$prefFlowNext = Array(
		'wifi' => 'seat',
		'seat' => 'quiet',
		'quiet' => 'tasty',
		'tasty' => 'cheap',
		'cheap' => 'music',
		'music' => 'opening',
		'opening' => NULL
	);

	switch ($payload) {
		case 'set_pref_now':
			sendModifyPref($senderId, 'wifi');
			break;
		case 'set_pref_later':
			sendLocationHint($senderId, '感謝您！之後隨時可以在左下方選單中修改偏好設定喲。現在您可以傳送位置資訊給我們，來找出附近的優質咖啡廳囉！');
			break;
		case 'wifi_0': case 'wifi_3': case 'wifi_4': case 'wifi_5':
		case 'seat_0': case 'seat_3': case 'seat_4': case 'seat_5':
		case 'quiet_0': case 'quiet_3': case 'quiet_4': case 'quiet_5':
		case 'tasty_0': case 'tasty_3': case 'tasty_4': case 'tasty_5':
		case 'cheap_0': case 'cheap_3': case 'cheap_4': case 'cheap_5':
		case 'music_0': case 'music_3': case 'music_4': case 'music_5':
		case 'opening_0': case 'opening_1':
			list($pref, $value) = explode('_', $payload);
			setPref($senderId, $pref, $value);
			if (!is_null($prefFlowNext[$pref])) {
				sendModifyPref($senderId, $prefFlowNext[$pref]);
			} else {
				sendLocationHint($senderId, '修改完成！之後隨時可以在左下方選單中修改設定喲。現在您可以傳送位置資訊給我們，來找出附近的優質咖啡廳囉！');
			}
			break;
		default:
			trigger_error('Unknown quick reply payload: ' . $payload);
	}
}

function processWaitingMsg($senderId, $waitingMsg, $text) {
	sendAction($senderId, 'typing_on');

	switch ($waitingMsg['type']) {
		case 'add_fb_page':
			$pageId = (false === stripos($text, 'facebook.com') ? $text : getFbPageId($text));

			if (is_null($pageId) || 0 === strlen($pageId)) {
				sendTextMessage($senderId, '格式錯誤，請檢查粉絲團網址或 ID 是否正確');
			} else {
				$fbData = getFbData($pageId);

				if (isset($fbData['error']) || !isset($fbData['id'])) {
					sendTextMessage($senderId, '無法取得粉絲團資料，請檢查粉絲團網址或 ID 是否正確');
					return;
				}

				if (checkStoreExist($fbData['id'])) {
					sendTextMessage($senderId, '這間店已經存在囉～感謝您的熱心協助！');
					return;
				}

				addStore($fbData);
				sendTextMessage($senderId, '新增『' . $fbData['name'] . '』完成！非常感謝您提供的資訊，讓我們的咖啡廳資料庫越來越完善～\n(新增的店家不會馬上出現在搜尋結果中，需要一段時間審核後才會更新唷)');
			}
			break;
		default:
			trigger_error('Unknown waiting msg type: ' . $waitingMsg['type']);
	}
}
?>
