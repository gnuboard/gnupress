<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if(!function_exists('g5_make_mp3')){
    function g5_make_mp3()
    {
        $config = G5_var::getInstance()->get_options('config');

        $number = g5_get_session("ss_captcha_key");

        if ($number == "") return;
        if ($number == g5_get_session("ss_captcha_save")) return;

        if( !g5_get_upload_path() )
            return;

        $mp3s = array();
        for($i=0;$i<strlen($number);$i++){
            $file = G5_PLUGIN_PATH.'/kcaptcha/mp3/'.$config['cf_captcha_mp3'].'/'.$number[$i].'.mp3';
            $mp3s[] = $file;
        }

        $ip = sprintf("%u", ip2long($_SERVER['REMOTE_ADDR']));
        $mp3_file_name = 'kcaptcha-'.$ip.'_'.G5_SERVER_TIME.'.mp3';
        $mp3_file = g5_get_upload_path().'/cache/'.$mp3_file_name;

        $contents = '';
        foreach ($mp3s as $mp3) {
            $contents .= file_get_contents($mp3);
        }

        file_put_contents($mp3_file, $contents);

        // 지난 캡챠 파일 삭제
        if (rand(0,99) == 0) {
            foreach (glob(g5_get_upload_path().'/cache/kcaptcha-*.mp3') as $file) {
                if (filemtime($file) + 86400 < G5_SERVER_TIME) {
                    @unlink($file);
                }
            }
        }

        g5_set_session("ss_captcha_save", $number);

        return g5_get_upload_path('url').'/cache/'.$mp3_file_name;
    }

    echo g5_make_mp3();
}
?>