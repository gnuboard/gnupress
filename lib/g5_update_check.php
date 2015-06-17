<?php
if (!defined('_GNUBOARD_')) exit;

//버젼 업데이트
$g5_options['version'] = G5_VERSION;
update_option( G5_OPTION_KEY, $g5_options );

?>