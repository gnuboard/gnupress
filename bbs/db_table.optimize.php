<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 최고관리자일 때만 실행
if ( ! current_user_can( 'administrator' ) ) {
    return;
}

// 음성 캡챠 파일 삭제
$captcha_mp3 = glob(g5_get_upload_path().'/cache/kcaptcha-*.mp3');
if($captcha_mp3 && is_array($captcha_mp3)) {
    foreach ($captcha_mp3 as $file) {
        if (filemtime($file) + 86400 < G5_SERVER_TIME) {
            @unlink($file);
        }
    }
}

// 실행일 기록
if(isset($config['cf_optimize_date'])) {
    sql_query(" update {$g5['config_table']} set cf_optimize_date = '".G5_TIME_YMD."' ");
}

?>