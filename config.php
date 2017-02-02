<?php
// Facebook messenger
$FB_PAGE_ACCESS_TOKEN = 'Your fb page access token';

$WEBHOOK_VERIFY_TOKEN = 'Your verify token for webhook';
$WEBHOOK_API_URL = 'https://graph.facebook.com/v2.6/me/messages?access_token=' . $FB_PAGE_ACCESS_TOKEN;

// Google api
$GOOGLE_API_KEY = 'Your google api key';
$GOOGLE_DISTANCE = 'https://maps.googleapis.com/maps/api/distancematrix/json?origins=%s,%s&destinations=%s&key=' . $GOOGLE_API_KEY;
$GOOGLE_PLACE = 'https://www.google.com/maps/place/%s,%s/@%s,%s,17z';

// Cafenomad api
$CAFENOMAD_API_URL = 'https://cafenomad.tw/api/v1.1/cafes';
$CAFENOMAD_SHOP_INFO = 'https://cafenomad.tw/shop/%s';

// Database
$DB_ADDR = 'localhost';
$DB_USER = 'root';
$DB_PASS = 'Your db password';
$DB_NAME = 'cafenomad';
?>
