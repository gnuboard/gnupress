<?php
$page_path = explode(DIRECTORY_SEPARATOR.'wp-content'.DIRECTORY_SEPARATOR, dirname(__FILE__));
include_once(str_replace('wp-content/' , '', $page_path[0] . '/wp-load.php'));

unset($page_path);

if (!session_id())
    session_start();

require(dirname(__FILE__).'/captcha.lib.php');

$captcha = new KCAPTCHA();
$captcha->setKeyString(g5_get_session('ss_captcha_key'));
$captcha->getKeyString();
$captcha->image();
?>