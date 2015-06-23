<?php
if (!defined('_GNUBOARD_')) exit;

if(version_compare(G5_VERSION, "0.0.4", "<")){
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

        $g5_options['cf_new_page_id'] = $page_id;
    }
}

//버젼 업데이트
$g5_options['version'] = G5_VERSION;
update_option( G5_OPTION_KEY, $g5_options );
?>