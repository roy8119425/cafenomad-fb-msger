<?php
require_once('security.php');

// Facebook api
$WEBHOOK_API_URL = 'https://graph.facebook.com/v2.6/me/messages?access_token=' . $FB_PAGE_ACCESS_TOKEN;
$FB_GRAPH_API_URL = 'https://graph.facebook.com/v2.6/%s?fields=%s&access_token=' . $FB_PAGE_ACCESS_TOKEN;
$FB_PAGE_URL = 'https://www.facebook.com/%s';
$FB_ABOUT_URL = 'https://www.facebook.com/pg/%s/about/';
$FB_REVIEW_URL = 'https://www.facebook.com/pg/%s/reviews/';

// Google api
$GOOGLE_DISTANCE = 'https://maps.googleapis.com/maps/api/distancematrix/json?origins=%s,%s&destinations=%s&key=' . $GOOGLE_API_KEY;
$GOOGLE_PLACE = 'https://www.google.com/maps/place/%s,%s/@%s,%s,17z';

// Cafenomad api
$CAFENOMAD_API_URL = 'https://cafenomad.tw/api/v1.1/cafes';
$CAFENOMAD_SHOP_INFO = 'https://cafenomad.tw/shop/%s';
$CAFENOMAD_REVIEW_URL = 'https://cafenomad.tw/review/%s';

// Database
$DB_ADDR = 'localhost';
$DB_USER = 'root';
$DB_NAME = 'cafenomad';
?>
