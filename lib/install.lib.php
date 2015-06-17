<?php
function g5_install_do() {
    global $wpdb;

    $g5 = G5_var::getInstance()->get_options();

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    //새 테이블을 만드는 쿼리를 작성한다.
    $sql = "CREATE TABLE IF NOT EXISTS `{$g5['board_table']}` (
              `bo_table` varchar(20) NOT NULL COMMENT '',
              `bo_subject` varchar(255) NOT NULL DEFAULT '' COMMENT '',
              `bo_list_level` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_read_level` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_write_level` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_comment_level` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_link_level` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_upload_level` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_download_level` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_count_delete` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_count_modify` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_read_point` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_write_point` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_comment_point` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_download_point` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_use_category` char(2) NOT NULL DEFAULT '' COMMENT '',
              `bo_category_list` text NOT NULL COMMENT '',
              `bo_disable_tags` text NOT NULL COMMENT '',
              `bo_use_sideview` char(2) NOT NULL DEFAULT '' COMMENT '',
              `bo_use_file_content` char(2) NOT NULL DEFAULT '' COMMENT '',
              `bo_use_secret` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_use_dthml_editor` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_use_rss_view` char(2) NOT NULL DEFAULT '' COMMENT '',
              `bo_use_good` char(2) NOT NULL DEFAULT '' COMMENT '',
              `bo_use_nogood` char(2) NOT NULL DEFAULT '' COMMENT '',
              `bo_use_name` char(2) NOT NULL DEFAULT '' COMMENT '',
              `bo_use_signature` char(2) NOT NULL DEFAULT '' COMMENT '',
              `bo_use_ip_view` char(2) NOT NULL DEFAULT '' COMMENT '',
              `bo_use_list_view` char(2) NOT NULL DEFAULT '' COMMENT '',
              `bo_use_list_file` char(2) NOT NULL DEFAULT '' COMMENT '',
              `bo_use_list_content` char(2) NOT NULL DEFAULT '' COMMENT '',
              `bo_table_width` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_subject_len` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_mobile_subject_len` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_page_rows` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_mobile_page_rows` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_new` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_hot` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_image_width` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_skin` varchar(255) NOT NULL DEFAULT '' COMMENT '',
              `bo_mobile_skin` varchar(255) NOT NULL DEFAULT '' COMMENT '',
              `bo_image_head` varchar(255) NOT NULL DEFAULT '' COMMENT '',
              `bo_image_tail` varchar(255) NOT NULL DEFAULT '' COMMENT '',
              `bo_include_head` varchar(255) NOT NULL DEFAULT '' COMMENT '',
              `bo_include_tail` varchar(255) NOT NULL DEFAULT '' COMMENT '',
              `bo_content_head` text NOT NULL COMMENT '',
              `bo_mobile_content_head` text NOT NULL COMMENT '',
              `bo_content_tail` text NOT NULL COMMENT '',
              `bo_mobile_content_tail` text NOT NULL COMMENT '',
              `bo_insert_content` text NOT NULL COMMENT '',
              `bo_gallery_cols` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_gallery_width` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_gallery_height` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_mobile_gallery_width` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_mobile_gallery_height` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_upload_size` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_reply_order` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_count_write` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_count_comment` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_write_min` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_write_max` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_comment_min` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_comment_max` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_notice` text NOT NULL COMMENT '',
              `bo_upload_count` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `bo_use_email` char(2) NOT NULL DEFAULT '' COMMENT '',
              `bo_use_cert` enum('','cert','adult','hp-cert','hp-adult') NOT NULL DEFAULT '' COMMENT '',
              `bo_use_sns` char(2) NOT NULL DEFAULT '' COMMENT '',
              `bo_sort_field` varchar(255) NOT NULL DEFAULT '' COMMENT '',
              `bo_admin` varchar(255) NOT NULL,
              `bo_use_dhtml_editor` tinyint(4) NOT NULL DEFAULT '0',
              `bo_use_search` tinyint(4) NOT NULL DEFAULT '0',
              `bo_reply_level` int(11) NOT NULL DEFAULT '0',
              `bo_page_id` int(11) NOT NULL DEFAULT '0',
              `bo_html_level` int(11) NOT NULL DEFAULT '0',
              `bo_use_tag` char(2) NOT NULL DEFAULT '',
              `bo_sh_fields` varchar(255) NOT NULL DEFAULT '',
              PRIMARY KEY (`bo_table`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";

    //테이블을 만드는 쿼리를 실행한다.
    dbDelta( $sql );

    $sql = "CREATE TABLE IF NOT EXISTS `{$g5['write_table']}` (
              `wr_id` int(10) NOT NULL AUTO_INCREMENT,
              `bo_table` varchar(20) NOT NULL DEFAULT '',
              `wr_num` int(10) NOT NULL DEFAULT '0',
              `wr_comment` int(11) NOT NULL DEFAULT '0',
              `ca_name` varchar(255) NOT NULL DEFAULT '',
              `wr_subject` varchar(255) NOT NULL DEFAULT '',
              `wr_content` longtext NOT NULL ,
              `wr_hit` int(11) NOT NULL DEFAULT '0',
              `wr_good` int(11) NOT NULL DEFAULT '0',
              `wr_nogood` int(11) NOT NULL DEFAULT '0',
              `user_id` BIGINT(20) NOT NULL default '0',
              `user_pass` varchar(64) NOT NULL DEFAULT '',
              `user_display_name` varchar(50) NOT NULL,
              `user_email` varchar(100) NOT NULL DEFAULT '',
              `wr_link1` text NOT NULL,
              `wr_link2` text NOT NULL,
              `wr_link1_hit` int(11) NOT NULL DEFAULT '0',
              `wr_link2_hit` int(11) NOT NULL DEFAULT '0',
              `wr_datetime` datetime NOT NULL,
              `wr_ip` varchar(255) NOT NULL DEFAULT '',
              `wr_last` varchar(255) NOT NULL DEFAULT '',
              `wr_parent` int(11) NOT NULL DEFAULT '0',
              `wr_option` set('html1','html2','wp_html','secret','mail') NOT NULL,
              `wr_img` varchar(255) NOT NULL DEFAULT '',
              `wr_file` tinyint(4) NOT NULL DEFAULT '0',
              `wr_tag` varchar(255) NOT NULL DEFAULT '',
              `wr_page_id` BIGINT(20) NOT NULL default '0',
              PRIMARY KEY (`wr_id`),
              KEY `wr_num` (`wr_num`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";
    
    dbDelta( $sql );

    $sql = "CREATE TABLE IF NOT EXISTS `{$g5['meta_table']}` (
              `meta_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '',
              `g5_wr_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '',
              `meta_key` varchar(255) DEFAULT NULL COMMENT '',
              `meta_value` longtext COMMENT '',
              PRIMARY KEY (`meta_id`),
              KEY `meta_key` (`meta_key`),
              KEY `wr_id` (`g5_wr_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";

    dbDelta( $sql );

    $sql = "CREATE TABLE IF NOT EXISTS `{$g5['comment_table']}` (
              `cm_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '',
              `bo_table` varchar(100) NOT NULL DEFAULT '' COMMENT '',
              `wr_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '',
              `cm_parent` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '',
              `cm_num` int(11) NOT NULL DEFAULT '0',
              `user_id` BIGINT(20) NOT NULL default 0 COMMENT '',
              `user_pass` varchar(64) NOT NULL DEFAULT '' COMMENT '',
              `user_display_name` varchar(50) NOT NULL DEFAULT '' COMMENT '',
              `user_email` varchar(100) NOT NULL DEFAULT '' COMMENT '',
              `cm_subject` varchar(255) NOT NULL DEFAULT '' COMMENT '',
              `cm_content` text NOT NULL COMMENT '',
              `cm_good` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `cm_nogood` int(11) NOT NULL DEFAULT '0' COMMENT '',
              `cm_datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '',
              `cm_ip` varchar(255) NOT NULL DEFAULT '' COMMENT '',
              `cm_option` set('html1','html2','wp_html','secret','mail') NOT NULL DEFAULT '',
              `cm_last` varchar(45) NOT NULL DEFAULT '',
              PRIMARY KEY (`cm_id`),
              KEY `wr_id` (`wr_id`,`cm_parent`),
              KEY `cm_datetime` (`cm_datetime`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";
    dbDelta( $sql );

    $sql = "CREATE TABLE IF NOT EXISTS `{$g5['relation_table']}` (
              `object_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '',
              `term_taxonomy_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '',
              `term_order` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '',
              PRIMARY KEY (`object_id`,`term_taxonomy_id`),
              KEY `term_taxonomy_id` (`term_taxonomy_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";
    dbDelta( $sql );

    $sql = "CREATE TABLE IF NOT EXISTS `{$g5['taxonomy_table']}` (
              `term_taxonomy_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '',
              `term_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '',
              `taxonomy` varchar(32) DEFAULT NULL COMMENT '',
              `description` text COMMENT '',
              `parent` int(10) unsigned DEFAULT '0' COMMENT '',
              `count` int(10) unsigned DEFAULT '0' COMMENT '',
              PRIMARY KEY (`term_taxonomy_id`),
              UNIQUE KEY `term_id_taxonomy` (`term_id`,`taxonomy`),
              KEY `taxonomy` (`taxonomy`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";
    dbDelta( $sql );

    $sql = "CREATE TABLE IF NOT EXISTS `{$g5['point_table']}` (
              `po_id` int(11) NOT NULL auto_increment,
              `user_id` BIGINT(20) NOT NULL default 0,
              `po_datetime` datetime NOT NULL default '0000-00-00 00:00:00',
              `po_content` varchar(255) NOT NULL default '',
              `po_point` int(11) NOT NULL default '0',
              `po_use_point` int(11) NOT NULL default '0',
              `po_expired` tinyint(4) NOT NULL default '0',
              `po_expire_date` date NOT NULL default '0000-00-00',
              `po_mb_point` int(11) NOT NULL default '0',
              `po_rel_table` varchar(20) NOT NULL default '',
              `po_rel_id` varchar(20) NOT NULL default '',
              `po_rel_action` varchar(255) NOT NULL default '',
              PRIMARY KEY  (`po_id`),
              KEY `index1` (`user_id`,`po_rel_table`,`po_rel_id`),
              KEY `index2` (`po_expire_date`, `po_rel_action`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
    dbDelta( $sql );

    $sql = "CREATE TABLE IF NOT EXISTS `{$g5['scrap_table']}` (
              `ms_id` int(11) NOT NULL auto_increment,
              `user_id` BIGINT(20) NOT NULL default 0,
              `bo_table` varchar(20) NOT NULL default '',
              `ms_url` varchar(200) NOT NULL default '',
              `wr_id` varchar(15) NOT NULL default '',
              `ms_datetime` datetime NOT NULL default '0000-00-00 00:00:00',
              PRIMARY KEY  (`ms_id`),
              KEY `user_id` (`user_id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
    dbDelta( $sql );

    $sql = "CREATE TABLE IF NOT EXISTS `{$g5['board_good_table']}` (
              `bg_id` int(11) NOT NULL auto_increment,
              `bo_table` varchar(20) NOT NULL default '',
              `wr_id` int(11) NOT NULL default '0',
              `user_id` BIGINT(20) NOT NULL default 0,
              `bg_flag` varchar(255) NOT NULL default '',
              `bg_datetime` datetime NOT NULL default '0000-00-00 00:00:00',
              PRIMARY KEY  (`bg_id`),
              UNIQUE KEY `fkey1` (`bo_table`,`wr_id`,`user_id`)
            ) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
    dbDelta( $sql );

    $tmp_option = array('version'=> G5_VERSION);

    $upload_dir = array('error'=>'error');
    try{
        $upload_dir = wp_upload_dir();
    } catch (Exception $e) {
    }

    if( empty($upload_dir['error']) ){
        // 디렉토리 생성
        $dir_arr = array (
            $upload_dir['basedir'].'/'.G5_NAME,
            $upload_dir['basedir'].'/'.G5_NAME.'/cache',
            $upload_dir['basedir'].'/'.G5_NAME.'/editor',
            $upload_dir['basedir'].'/'.G5_NAME.'/tmp',
            $upload_dir['basedir'].'/'.G5_NAME.'/file'
        );

        foreach($dir_arr as $dir_name){
            @mkdir($dir_name, G5_DIR_PERMISSION);
            @chmod($dir_name, G5_DIR_PERMISSION);
        }
    }

    $config = G5_var::getInstance()->get_options('config');
    
    //point, scrap 등등 새창으로 여는 페이지 생성
    if( isset($config['cf_new_page_name']) ){
        $page_name = $config['cf_new_page_name'];
        $exists = get_page_by_path( $page_name );

		if ( ! empty( $exists ) ) {
			$page_id = $exists->ID;
		} else {
			$page_id = wp_insert_post( array(
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'post_status'    => 'publish',
				'post_title'     => $page_name,
				'post_type'      => 'page',
			) );
		}

        $tmp_option['cf_new_page_id'] = $page_id;
    }

    // 테이블 구조 버전 넘버를 저장한다.
    add_option(G5_OPTION_KEY, $tmp_option);
}
?>