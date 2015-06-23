<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

require(dirname(__FILE__).'/captcha.lib.php');

$captcha = new KCAPTCHA();
$captcha->setKeyString(g5_get_session('ss_captcha_key'));
$captcha->getKeyString();
$captcha->image();

exit;
?>