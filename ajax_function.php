<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

add_action("wp_ajax_g5_bss_filter", "g5_bbs_filter");
add_action("wp_ajax_nopriv_g5_bss_filter", "g5_bbs_filter");

//게시물 제목 및 내용 필터
function g5_bbs_filter(){

    include_once( G5_DIR_PATH.'lib/g5_var.class.php' );
    $subject = strtolower(sanitize_title($_POST['subject']));
    $content = strtolower(strip_tags(wp_kses_post($_POST['content'])));
    $config = G5_var::getInstance()->get_options('config');
    $filter = explode(",", trim($config['cf_filter']));
    for ($i=0; $i<count($filter); $i++) {
        $str = $filter[$i];

        // 제목 필터링 (찾으면 중지)
        $subj = "";
        $pos = strpos($subject, $str);
        if ($pos !== false) {
            $subj = $str;
            break;
        }

        // 내용 필터링 (찾으면 중지)
        $cont = "";
        $pos = strpos($content, $str);
        if ($pos !== false) {
            $cont = $str;
            break;
        }
    }
    die("{\"subject\":\"$subj\",\"content\":\"$cont\"}");

}

//태그 추가 admin 관련 ajax
add_action("wp_ajax_g5_add-tag", "g5_ajax_add_tag");

function g5_ajax_add_tag(){
	global $wp_list_table, $gnupress;

    check_ajax_referer( 'add-tag', '_wpnonce_add-tag' );

    if( ! $gnupress->bo_table ){
        wp_die( -1 );
    }

    g5_wp_taxonomies($gnupress->bo_table);

    include_once( G5_DIR_PATH.'lib/g5_var.class.php' );
    include_once( G5_DIR_PATH.'lib/g5_taxonomy.lib.php' );

	$taxonomy = !empty($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : 'post_tag';
	$tax = get_taxonomy($taxonomy);

	if ( !current_user_can( $tax->cap->edit_terms ) )
		wp_die( -1 );

	$x = new WP_Ajax_Response();

	$tag = g5_insert_term(sanitize_text_field($_POST['tag-name']), $taxonomy, $_POST );

	if ( !$tag || is_wp_error($tag) || (!$tag = g5_get_term( $tag['term_id'], $taxonomy )) ) {
		$message = __('An error has occurred. Please reload the page and try again.');
		if ( is_wp_error($tag) && $tag->get_error_message() )
			$message = $tag->get_error_message();

		$x->add( array(
			'what' => 'taxonomy',
			'data' => new WP_Error('error', $message )
		) );
		$x->send();
	}

	$wp_list_table = _g5_get_list_table( 'G5_Terms_List_Table', $taxonomy, $gnupress->bo_table, array( 'screen' => sanitize_text_field($_POST['screen']) ) );

	$level = 0;
	if ( is_taxonomy_hierarchical($taxonomy) ) {
		$level = count( get_ancestors( $tag->term_id, $taxonomy, 'taxonomy' ) );
		ob_start();
		$wp_list_table->single_row( $tag, $level );
		$noparents = ob_get_clean();
	}

	ob_start();
	$wp_list_table->single_row( $tag );
	$parents = ob_get_clean();

	$x->add( array(
		'what' => 'taxonomy',
		'supplemental' => compact('parents', 'noparents')
		) );
	$x->add( array(
		'what' => 'term',
		'position' => $level,
		'supplemental' => (array) $tag
		) );
	$x->send();
}

//태그 삭제 admin 관련 ajax
add_action("wp_ajax_g5_delete-tag", "g5_ajax_delete_tag");

function g5_ajax_delete_tag() {
    global $gnupress;

    $bo_table = ($_REQUEST['bo_table']) ? sanitize_text_field($_REQUEST['bo_table']) : '';

    if( !$bo_table ){
        wp_die( -1 );
    }

    g5_wp_taxonomies($bo_table);

	$tag_id = intval($_POST['tag_ID']);	

	$taxonomy = !empty($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : 'post_tag';
	$tax = get_taxonomy($taxonomy);

	if ( !current_user_can( $tax->cap->delete_terms ) )
		wp_die( -1 );

	$tag = g5_get_term( $tag_id, $taxonomy );
	if ( !$tag || is_wp_error( $tag ) )
		wp_die( 1 );

	if ( g5_delete_term($tag_id, $taxonomy))
		wp_die( 1 );
	else
		wp_die( 0 );
}

//캡챠 이미지 관련 ajax
add_action("wp_ajax_g5_kcaptcha_image", "g5_ajax_kcaptcha_image");
add_action("wp_ajax_nopriv_g5_kcaptcha_image", "g5_ajax_kcaptcha_image");
function g5_ajax_kcaptcha_image(){
    include_once( G5_DIR_PATH.'lib/g5_var.class.php' );
    include_once( G5_PLUGIN_PATH.'/kcaptcha/kcaptcha_session.php' );
    die();
}

//캡챠 mp3 관련 ajax
add_action("wp_ajax_g5_kcaptcha_mp3", "g5_ajax_kcaptcha_mp3");
add_action("wp_ajax_nopriv_g5_kcaptcha_mp3", "g5_ajax_kcaptcha_mp3");
function g5_ajax_kcaptcha_mp3(){
    include_once( G5_DIR_PATH.'lib/g5_var.class.php' );
    include_once( G5_PLUGIN_PATH.'/kcaptcha/kcaptcha_mp3.php' );
    die();
}

//캡챠 결과 관련 ajax
add_action("wp_ajax_g5_kcaptcha_result", "g5_ajax_kcaptcha_result");
add_action("wp_ajax_nopriv_g5_kcaptcha_result", "g5_ajax_kcaptcha_result");
function g5_ajax_kcaptcha_result(){
    include_once( G5_DIR_PATH.'lib/g5_var.class.php' );
    include_once( G5_PLUGIN_PATH.'/kcaptcha/kcaptcha_result.php' );
    die();
}

//태그 관련 ajax
add_action("wp_ajax_g5_get_tags", "g5_ajax_get_tags");
add_action("wp_ajax_nopriv_g5_get_tags", "g5_ajax_get_tags");
function g5_ajax_get_tags(){
    global $wpdb, $gnupress;

    check_ajax_referer( 'g5_write', 'security' );

    $bo_table = $gnupress->bo_table;

    if( !$bo_table ){ wp_die( -1 ); }
    
    g5_wp_taxonomies($gnupress->bo_table);

    include_once( G5_DIR_PATH.'lib/g5_var.class.php' );
    include_once( G5_DIR_PATH.'lib/g5_taxonomy.lib.php' );

	$board_tag_lists = g5_get_terms( g5_get_taxonomy($bo_table) , apply_filters('g5_list_get_tag_where', array( 'orderby' => 'count', 'order' => 'DESC' )) );

    $tags = array();

    foreach( $board_tag_lists as $v ){
        if( !isset($v->name) && empty($v->name) ) continue;
        $tags[] = $v->name;
    }

    wp_send_json($tags);

    die();
}

//캡챠관련 ajax
?>