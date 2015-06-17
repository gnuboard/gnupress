<?php
if ( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') ) {
    exit();
 }

require_once( plugin_dir_path( __FILE__ ). 'gnupress.php');
include_once( G5_DIR_PATH.'adm/admin.lib.php' );

global $wpdb;
//커스텀 테이블 삭제

include_once( G5_DIR_PATH.'lib/g5_var.class.php' );

$g5 = G5_var::getInstance()->get_options();

$sql = "DROP TABLE IF EXISTS ".$g5['taxonomy_table'];
$wpdb->query($sql);

$sql = "DROP TABLE IF EXISTS ".$g5['relation_table'];
$wpdb->query($sql);

$sql = "DROP TABLE IF EXISTS ".$g5['comment_table'];
$wpdb->query($sql);

$sql = "DROP TABLE IF EXISTS ".$g5['meta_table'];
$wpdb->query($sql);

$sql = "DROP TABLE IF EXISTS ".$g5['write_table'];
$wpdb->query($sql);

$sql = "DROP TABLE IF EXISTS ".$g5['board_table'];
$wpdb->query($sql);

$sql = "DROP TABLE IF EXISTS ".$g5['point_table'];
$wpdb->query($sql);

$sql = "DROP TABLE IF EXISTS ".$g5['scrap_table'];
$wpdb->query($sql);

$sql = "DROP TABLE IF EXISTS ".$g5['board_good_table'];
$wpdb->query($sql);

delete_post_meta_by_key(G5_META_KEY);

// 게시판 폴더 전체 삭제
if( $data_path = g5_get_upload_path() ){
    g5_rm_rf($data_path);
}

//옵션테이블에서 옵션을 제거한다.
delete_option(G5_OPTION_KEY);

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
dbDelta( $sql );

?>
