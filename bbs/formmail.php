<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_PLUGIN_PATH.'/kcaptcha/captcha.lib.php');

if (!$config['cf_email_use'])
    g5_alert_close('환경설정에서 \"메일발송 사용\"에 체크하셔야 메일을 발송할 수 있습니다.\\n\\n관리자에게 문의하시기 바랍니다.');

if (!$is_member && $config['cf_formmail_is_member'])
    g5_alert_close('회원만 이용하실 수 있습니다.');

if (isset($_REQUEST['user_id']))
{
    $user_id = sanitize_text_field($_REQUEST['user_id']);
    $mb = g5_get_member($user_id);
    if (!$mb['user_id'])
        g5_alert_close('회원정보가 존재하지 않습니다.');
}

$sendmail_count = (int)g5_get_session('ss_sendmail_count') + 1;

if ($sendmail_count > 3)
    g5_alert_close('한번 접속후 일정수의 메일만 발송할 수 있습니다.\\n\\n계속해서 메일을 보내시려면 다시 로그인 또는 접속하여 주십시오.');

$g5['title'] = '메일 쓰기';

$name = isset($_REQUEST['name']) ? sanitize_text_field($_REQUEST['name']) : '';
$email = isset($_REQUEST['email']) ? sanitize_email($_REQUEST['email']) : '';

if (! isset($_REQUEST['name']) )
    $name = base64_decode($email);

if (!isset($type))
    $type = 0;

$type_checked[0] = $type_checked[1] = $type_checked[2] = "";
$type_checked[$type] = 'checked';

include_once($member_skin_path.'/formmail.skin.php');
?>