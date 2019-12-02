<?php
ob_start();
date_default_timezone_set('PRC');
@set_time_limit(10);
define('WX_ROOT', dirname(__FILE__));

require WX_ROOT . '/config.php';
require WX_ROOT . '/function.php';
require WX_ROOT . '/http.class.php';
require WX_ROOT . '/wxapi.class.php';
require WX_ROOT . '/app.class.php';
require WX_ROOT . '/FileCache.php';


if (isset($_GET['param'])) {
    parse_str($_GET['param'], $_GET);
}

if (isset($_GET['action']) && isset($_GET['openid'])) {
    $app = new app($_GET['openid']);
    $app->call_action($_GET['action']);
    die;
}


$wxapi = new wxapi();
$wxapi->valid($_T['token']);

$app = new app($wxapi->data['FromUserName']);

$app->call_wxapi($wxapi);


echo $wxapi->call_third_api($_T['third_api_url'], $_T['token']);
