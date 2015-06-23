<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_PLUGIN_PATH.'/kcaptcha/captcha.lib.php');

if ( ! isset( $_POST['g5_nonce_field'] ) || ! wp_verify_nonce( $_POST['g5_nonce_field'], 'g5_formmail' ) ) {
    g5_alert(__('잘못된 접근입니다.', G5_NAME));
}

if (!$config['cf_email_use'])
    g5_alert('환경설정에서 "메일발송 사용"에 체크하셔야 메일을 발송할 수 있습니다.\\n\\n관리자에게 문의하시기 바랍니다.');

if (!$is_member && $config['cf_formmail_is_member'])
    g5_alert_close('회원만 이용하실 수 있습니다.');

$to = isset($_POST['to']) ? base64_decode(sanitize_text_field($_POST['to'])) : '';
$attach = isset($_POST['attach']) ? sanitize_text_field($_POST['attach']) : '';
$subject = isset($_POST['subject']) ? sanitize_text_field($_POST['subject']) : '';
$content = isset($_POST['content']) ? wp_kses_post(trim($_POST['content'])) : '';
$type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
$fnick = isset($_POST['fnick']) ? sanitize_text_field($_POST['fnick']) : '';
$fmail = isset($_POST['fmail']) ? sanitize_email($_POST['fmail']) : '';

if (substr_count($to, "@") > 1)
    g5_alert_close('한번에 한사람에게만 메일을 발송할 수 있습니다.');

if (!g5_chk_captcha()) {
    g5_alert('자동등록방지 숫자가 틀렸습니다.');
}

$attachments = $file = array();
for ($i=1; $i<=$attach; $i++) {
    if ($_FILES['file'.$i]['name']){
        $file[] = g5_attach_file($_FILES['file'.$i]['name'], $_FILES['file'.$i]['tmp_name']);
    }
}

foreach( $file as $f ){
    if( ! isset($f['path']) ) continue;
    $attachments[] = $f['path'];
}

$content = stripslashes($content);
if ($type == 2) {
    $type = 1;
    $content = str_replace("\n", "<br>", $content);
}

// html 이면
if ($type) {
    $current_url = home_url();
    $mail_content = '<!doctype html><html lang="ko"><head><meta charset="utf-8"><title>메일보내기</title><link rel="stylesheet" href="'.$current_url.'/style.css"></head><body>'.$content.'</body></html>';
} else {
    $mail_content = $content;
}

$headers = 'From: '.$fnick.' <'.$fmail.'>' . "\r\n";

add_filter( 'wp_mail_content_type', 'g5_set_html_content_type' );
wp_mail($to, $subject, $mail_content, $headers, $attachments );
remove_filter( 'wp_mail_content_type', 'g5_set_html_content_type' );

// 임시 첨부파일 삭제
if(!empty($file)) {
    foreach($file as $f) {
        @unlink($f['path']);
    }
}

g5_alert_close('메일을 정상적으로 발송하였습니다.');

?>