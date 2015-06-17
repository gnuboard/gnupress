<?php
function g5_install_do() {
    global $wpdb;

    $g5 = G5_var::getInstance()->get_options();

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    //새 테이블을 만드는 쿼리를 작성한다.
    $sql = "CREATE TABLE IF NOT EXISTS `{$g5['board_table']}` (
              `bo_table` varchar(20) NOT NULL COMMENT '게시판id',
              `bo_subject` varchar(255) NOT NULL DEFAULT '' COMMENT '제목',
              `bo_list_level` int(11) NOT NULL DEFAULT '0' COMMENT '목록레벨',
              `bo_read_level` int(11) NOT NULL DEFAULT '0' COMMENT '읽기레벨',
              `bo_write_level` int(11) NOT NULL DEFAULT '0' COMMENT '쓰기레벨',
              `bo_comment_level` int(11) NOT NULL DEFAULT '0' COMMENT '댓글쓰기레벨',
              `bo_link_level` int(11) NOT NULL DEFAULT '0' COMMENT '링크쓰기레벨',
              `bo_upload_level` int(11) NOT NULL DEFAULT '0' COMMENT '업로드레벨',
              `bo_download_level` int(11) NOT NULL DEFAULT '0' COMMENT '다운로드레벨',
              `bo_count_delete` int(11) NOT NULL DEFAULT '0' COMMENT '삭제댓글수',
              `bo_count_modify` int(11) NOT NULL DEFAULT '0' COMMENT '수정댓글수',
              `bo_read_point` int(11) NOT NULL DEFAULT '0' COMMENT '읽기포인트',
              `bo_write_point` int(11) NOT NULL DEFAULT '0' COMMENT '쓰기포인트',
              `bo_comment_point` int(11) NOT NULL DEFAULT '0' COMMENT '댓글쓰기포인트',
              `bo_download_point` int(11) NOT NULL DEFAULT '0' COMMENT '다운로드포인트',
              `bo_use_category` char(2) NOT NULL DEFAULT '' COMMENT '카테고리사용',
              `bo_category_list` text NOT NULL COMMENT '카테고리목록',
              `bo_disable_tags` text NOT NULL COMMENT '금지태그',
              `bo_use_sideview` char(2) NOT NULL DEFAULT '' COMMENT '사이드뷰사용',
              `bo_use_file_content` char(2) NOT NULL DEFAULT '' COMMENT '파일컨텐츠사용',
              `bo_use_secret` int(11) NOT NULL DEFAULT '0' COMMENT '비밀글사용',
              `bo_use_dthml_editor` int(11) NOT NULL DEFAULT '0' COMMENT '에디터사용',
              `bo_use_rss_view` char(2) NOT NULL DEFAULT '' COMMENT 'RSS사용',
              `bo_use_good` char(2) NOT NULL DEFAULT '' COMMENT '추천사용',
              `bo_use_nogood` char(2) NOT NULL DEFAULT '' COMMENT '비추천사용',
              `bo_use_name` char(2) NOT NULL DEFAULT '' COMMENT '이름사용',
              `bo_use_signature` char(2) NOT NULL DEFAULT '' COMMENT '서명사용',
              `bo_use_ip_view` char(2) NOT NULL DEFAULT '' COMMENT '아이피보이기',
              `bo_use_list_view` char(2) NOT NULL DEFAULT '' COMMENT '목록보이기',
              `bo_use_list_file` char(2) NOT NULL DEFAULT '' COMMENT '목록파일사용',
              `bo_use_list_content` char(2) NOT NULL DEFAULT '' COMMENT '목록내용사용',
              `bo_table_width` int(11) NOT NULL DEFAULT '0' COMMENT '게시판폭',
              `bo_subject_len` int(11) NOT NULL DEFAULT '0' COMMENT '제목길이',
              `bo_mobile_subject_len` int(11) NOT NULL DEFAULT '0' COMMENT 'm제목길이',
              `bo_page_rows` int(11) NOT NULL DEFAULT '0' COMMENT '페이지당목록수',
              `bo_mobile_page_rows` int(11) NOT NULL DEFAULT '0' COMMENT 'm페이지당목록수',
              `bo_new` int(11) NOT NULL DEFAULT '0' COMMENT '새글아이콘',
              `bo_hot` int(11) NOT NULL DEFAULT '0' COMMENT '인기글아이콘',
              `bo_image_width` int(11) NOT NULL DEFAULT '0' COMMENT '이미지폭',
              `bo_skin` varchar(255) NOT NULL DEFAULT '' COMMENT '스킨',
              `bo_mobile_skin` varchar(255) NOT NULL DEFAULT '' COMMENT 'm스킨',
              `bo_image_head` varchar(255) NOT NULL DEFAULT '' COMMENT '상단이미지',
              `bo_image_tail` varchar(255) NOT NULL DEFAULT '' COMMENT '하단이미지',
              `bo_include_head` varchar(255) NOT NULL DEFAULT '' COMMENT '상단파일',
              `bo_include_tail` varchar(255) NOT NULL DEFAULT '' COMMENT '하단파일',
              `bo_content_head` text NOT NULL COMMENT '상단내용',
              `bo_mobile_content_head` text NOT NULL COMMENT 'm상단내용',
              `bo_content_tail` text NOT NULL COMMENT '하단내용',
              `bo_mobile_content_tail` text NOT NULL COMMENT 'm하단내용',
              `bo_insert_content` text NOT NULL COMMENT '글쓰기기본내용',
              `bo_gallery_cols` int(11) NOT NULL DEFAULT '0' COMMENT '갤러리이미지수',
              `bo_gallery_width` int(11) NOT NULL DEFAULT '0' COMMENT '갤러리이미지폭',
              `bo_gallery_height` int(11) NOT NULL DEFAULT '0' COMMENT '갤러리이미지높이',
              `bo_mobile_gallery_width` int(11) NOT NULL DEFAULT '0' COMMENT 'm갤러리이미지폭',
              `bo_mobile_gallery_height` int(11) NOT NULL DEFAULT '0' COMMENT 'm갤러리이미지높이',
              `bo_upload_size` int(11) NOT NULL DEFAULT '0' COMMENT '파일업로드용량',
              `bo_reply_order` int(11) NOT NULL DEFAULT '0' COMMENT '답변달기순서',
              `bo_count_write` int(11) NOT NULL DEFAULT '0' COMMENT '게시글수',
              `bo_count_comment` int(11) NOT NULL DEFAULT '0' COMMENT '댓글수',
              `bo_write_min` int(11) NOT NULL DEFAULT '0' COMMENT '최소글자수',
              `bo_write_max` int(11) NOT NULL DEFAULT '0' COMMENT '최대글자수',
              `bo_comment_min` int(11) NOT NULL DEFAULT '0' COMMENT '최소댓글수',
              `bo_comment_max` int(11) NOT NULL DEFAULT '0' COMMENT '최대댓글수',
              `bo_notice` text NOT NULL COMMENT '공지사항',
              `bo_upload_count` int(11) NOT NULL DEFAULT '0' COMMENT '파일업로드갯수',
              `bo_use_email` char(2) NOT NULL DEFAULT '' COMMENT '메일발송사용',
              `bo_use_cert` enum('','cert','adult','hp-cert','hp-adult') NOT NULL DEFAULT '' COMMENT '인증사용',
              `bo_use_sns` char(2) NOT NULL DEFAULT '' COMMENT 'SNS사용',
              `bo_sort_field` varchar(255) NOT NULL DEFAULT '' COMMENT '정렬필드',
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
              `meta_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '게시물메타id',
              `g5_wr_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '게시물id',
              `meta_key` varchar(255) DEFAULT NULL COMMENT '메타키',
              `meta_value` longtext COMMENT '메타값',
              PRIMARY KEY (`meta_id`),
              KEY `meta_key` (`meta_key`),
              KEY `wr_id` (`g5_wr_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";

    dbDelta( $sql );

    $sql = "CREATE TABLE IF NOT EXISTS `{$g5['comment_table']}` (
              `cm_id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '댓글id',
              `bo_table` varchar(100) NOT NULL DEFAULT '' COMMENT 'bo_table',
              `wr_id` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '게시물id',
              `cm_parent` int(11) unsigned NOT NULL DEFAULT '0' COMMENT '댓글부모id',
              `cm_num` int(11) NOT NULL DEFAULT '0',
              `user_id` BIGINT(20) NOT NULL default 0 COMMENT '사용자ID',
              `user_pass` varchar(64) NOT NULL DEFAULT '' COMMENT '사용자패스워드',
              `user_display_name` varchar(50) NOT NULL DEFAULT '' COMMENT '사용자명',
              `user_email` varchar(100) NOT NULL DEFAULT '' COMMENT '사용자메일',
              `cm_subject` varchar(255) NOT NULL DEFAULT '' COMMENT '제목',
              `cm_content` text NOT NULL COMMENT '내용',
              `cm_good` int(11) NOT NULL DEFAULT '0' COMMENT '추천수',
              `cm_nogood` int(11) NOT NULL DEFAULT '0' COMMENT '비추천수',
              `cm_datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT '댓글쓴시간',
              `cm_ip` varchar(255) NOT NULL DEFAULT '' COMMENT '댓글쓴IP',
              `cm_option` set('html1','html2','wp_html','secret','mail') NOT NULL DEFAULT '',
              `cm_last` varchar(45) NOT NULL DEFAULT '',
              PRIMARY KEY (`cm_id`),
              KEY `wr_id` (`wr_id`,`cm_parent`),
              KEY `cm_datetime` (`cm_datetime`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";
    dbDelta( $sql );

    $sql = "CREATE TABLE IF NOT EXISTS `{$g5['relation_table']}` (
              `object_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '게시물id',
              `term_taxonomy_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '분류명id',
              `term_order` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '분류명순서',
              PRIMARY KEY (`object_id`,`term_taxonomy_id`),
              KEY `term_taxonomy_id` (`term_taxonomy_id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8";
    dbDelta( $sql );

    $sql = "CREATE TABLE IF NOT EXISTS `{$g5['taxonomy_table']}` (
              `term_taxonomy_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '테이블id',
              `term_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '분류명id',
              `taxonomy` varchar(32) DEFAULT NULL COMMENT '분류구분값',
              `description` text COMMENT '설명',
              `parent` int(10) unsigned DEFAULT '0' COMMENT '부모 term_taxonomy_id',
              `count` int(10) unsigned DEFAULT '0' COMMENT '등록된글수',
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

    $upload_dir = wp_upload_dir();
    
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

    // 테이블 구조 버전 넘버를 저장한다.
    add_option(G5_OPTION_KEY, $tmp_option);
}

?>