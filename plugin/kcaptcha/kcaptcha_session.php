<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(dirname(__FILE__).'/kcaptcha_config.php');
include_once(dirname(__FILE__).'/captcha.lib.php');

while(true){
    $keystring='';
    for($i=0;$i<$length;$i++){
        $keystring.=$allowed_symbols{mt_rand(0,strlen($allowed_symbols)-1)};
    }
    if(!preg_match('/cp|cb|ck|c6|c9|rn|rm|mm|co|do|cl|db|qp|qb|dp|ww/', $keystring)) break;
}

g5_set_session("ss_captcha_count", 0);
g5_set_session("ss_captcha_key", $keystring);
$captcha = new KCAPTCHA();
$captcha->setKeyString(g5_get_session("ss_captcha_key"));
?>