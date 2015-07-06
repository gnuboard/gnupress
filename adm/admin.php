<?php
include_once( G5_DIR_PATH.'lib/g5_taxonomy.lib.php' );

function g5_load_admin_js($v)
{
    $load_skin_js = array();
    $load_skin_js[] = array('handle'=>'g5-common-js', 'src'=>G5_DIR_URL.'js/common.js', 'deps'=>'', 'ver'=>G5_VERSION);
    $load_skin_js[] = array('handle'=>'g5-admin-js', 'src'=>G5_DIR_URL.'view/js/g5_admin.js', 'deps'=>'', 'ver'=>G5_VERSION);
    $load_skin_js[] = array('handle'=>'html5-js', 'src'=>G5_DIR_URL.'view/js/html5.js', 'deps'=>'', 'ver'=>G5_VERSION);

    $load_skin_js = apply_filters( 'g5_admin_load_js', $load_skin_js );
    if( count($load_skin_js) ){
        foreach( $load_skin_js as $js){
            wp_enqueue_script( $js['handle'], $js['src'], $js['deps'], $js['ver'] );
        }
    }
	wp_enqueue_style ( 'g5-admin-css', G5_DIR_URL . 'view/css/g5_admin.css', '', G5_VERSION );
    wp_script_add_data( 'html5-js', 'conditional', 'if lte IE 8' );
}

function g5_tag_form(){
    global $wpdb, $gnupress;

    include_once( G5_DIR_PATH.'view/tag_form.php' );
}

function g5_board_thumbnail_delete($board){
    include_once( G5_DIR_PATH.'view/board_thumbnail_delete.php' );
}

function g5_point_list(){
    global $wpdb, $gnupress;

    $g5 = $gnupress->g5;
    $config = $gnupress->config;
    $is_admin = $gnupress->is_admin;

    if( isset($_POST['point_action']) && !empty( $_POST['point_action']) ){
        g5_point_action(sanitize_key($_POST['point_action']), $g5, $config);
    }

    include_once( G5_DIR_PATH.'view/point_list.php' );
}

if ( ! function_exists('g5_point_action'))
{
    function g5_point_action( $action , $g5=array(), $config=array() ){
        global $wpdb;

        $current_user = wp_get_current_user();

        $location = admin_url('admin.php?page=g5_point_list');

        check_admin_referer( 'g5_point_plus', '_wpnonce_g5_field' );

        switch( $action ) {
            case( 'point_update' ) :
                
                $request_arr = array('user_login', 'po_point', 'po_content', 'po_expire_term');

                foreach( $request_arr as $v ){
                    $$v = isset( $_POST[$v] ) ? sanitize_text_field($_POST[$v]) : '';
                }

                $expire = preg_replace('/[^0-9]/', '', $po_expire_term);

                $mb = g5_get_member($user_login);
                
                if (!$mb['user_id'])
                    g5_alert( '존재하는 회원아이디가 아닙니다.', wp_get_referer() );

                if (($po_point < 0) && ($po_point * (-1) > $mb['mb_point']))
                    g5_alert('포인트를 깎는 경우 현재 포인트보다 작으면 안됩니다.', wp_get_referer() );

                g5_insert_point($user_login, $po_point, $po_content, '@passive', $user_login, $current_user->user_login.'-'.uniqid(''), $expire);
                
                g5_goto_url( $location );
                exit;
                break;
            
            case( 'point_list_delete' ) :

                $count = count($_POST['chk']);
                if(!$count)
                    g5_alert(sanitize_title($_POST['act_button']).' 하실 항목을 하나 이상 체크하세요.');

                for ($i=0; $i<$count; $i++)
                {
                    // 실제 번호를 넘김
                    $k = intval($_POST['chk'][$i]);
                    $po_id = intval($_POST['po_id'][$k]);
                    $user_id = intval($_POST['user_id'][$k]);

                    // 포인트 내역정보
                    $sql = $wpdb->prepare(
                                "
                                select * from {$g5['point_table']} where po_id = %d
                                ",
                                $po_id
                            );

                    $row = $wpdb->get_row($sql, ARRAY_A);

                    if(!$row['po_id'])
                        continue;

                    if($row['po_point'] < 0) {
                        $user_id = $row['user_id'];
                        $po_point = abs($row['po_point']);

                        if($row['po_rel_table'] == '@expire')
                            g5_delete_expire_point($user_id, $po_point);
                        else
                            g5_delete_use_point($user_id, $po_point);
                    } else {
                        if($row['po_use_point'] > 0) {
                            g5_insert_use_point($row['user_id'], $row['po_use_point'], $row['po_id']);
                        }
                    }

                    // 포인트 내역삭제
                    $sql = $wpdb->prepare(
                                "
                                delete from {$g5['point_table']} where po_id = %d
                                ",
                                $po_id
                            );
                    $result = $wpdb->query($sql);

                    // po_mb_point에 반영

                    $sql = $wpdb->prepare(" update {$g5['point_table']}
                                    set po_mb_point = po_mb_point - '{$row['po_point']}'
                                    where user_id = %d
                                    and po_id > %d ",
                                    $user_id, $po_id
                                  );
                    $result = $wpdb->query($sql);

                    // 회원 메타 테이블에 포인트 UPDATE
                    update_user_meta( $user_id, 'mb_point', g5_get_point_sum($user_id) );
                }

                break;
        }

    }
}

function g5_board_admin(){
    global $wpdb, $gnupress;
    
    $config = $gnupress->config;
    $add_err_msg = $gnupress->add_err_msg;
    $g5_options = get_option(G5_OPTION_KEY);

    if( isset($_POST['_wpnonce']) && (isset($_POST['g5_config_form']) && 'update' == sanitize_key($_POST['g5_config_form']) ) ){
        g5_config_form_update();
    }
    
    include_once( G5_DIR_PATH.'view/config_form.php' );
}

function g5_config_form_update(){
    
    if( ! check_admin_referer( 'g5_config_form_check' ) ){
        return;
    }

    global $wpdb, $gnupress;

    $g5 = $gnupress->g5;

    $pages = $tmp_config = array();

    foreach( $_POST as $key=>$v ){
        if( in_array($key, array('_wp_http_referer', '_wpnonce')) ) continue;
        $tmp_config[$key] = sanitize_text_field($v);
    }

    $checkbox_array = array('cf_use_point', 'cf_use_copy_log', 'cf_use_search_include', 'cf_email_use', 'cf_formmail_is_member', 'cf_email_wr_super_admin', 'cf_email_wr_board_admin', 'cf_email_wr_write', 'cf_email_wr_comment_all');

    foreach( $checkbox_array as $key=>$v ){
        if( !isset($_POST[$v]) ){
            $v = sanitize_key($v);
            $tmp_config[$v] = 0;
        }
    }

    $sql = "select bo_table from {$g5['board_table']} ";
    $board_list = $wpdb->get_results($sql, ARRAY_A);
    
    foreach( $board_list as $b ){
        if( ! isset($b['bo_table']) ) continue; 
        if( $page_id = g5_get_page_id(g5_page_get_by($b['bo_table'],'name')) ){
            $pages[$b['bo_table']] = $page_id;
        }
    }

    $options = array('version'=>G5_VERSION, 'board_page'=>$pages, 'config'=>$tmp_config);

    if( isset($tmp_config['cf_new_page_id']) ){
        $options['cf_new_page_id'] = $tmp_config['cf_new_page_id'];
    }

    g5_update_options_by($options, 'all');

    $location = admin_url('admin.php?page=g5_board_admin');

    g5_javascript_location_replace($location);

    exit;
}

function g5_board_form(){
    global $wpdb, $gnupress, $wpdb;

    //설정
    $g5 = $gnupress->g5;
    $config = $gnupress->config;
    $add_err_msg = $gnupress->add_err_msg;
    $is_admin = g5_is_admin();

    $check_post_msg = ( isset( $_POST['g5_admin_post'] ) ) ? g5_admin_post( sanitize_title($_POST['g5_admin_post']) ) : false;
    
    $bbs_direct_url = '';

    $required = '';
    $readonly = '';
    $html_title = '게시판';
    $qstr = '';
    //파라미터
    $param_arr = array('w', 'stx', 'sfl', 'sst', 'sod', 'page', 'bo_table', 'g5_rq');
    foreach( $param_arr as $v ){
        $$v = isset($_REQUEST[$v]) ? sanitize_text_field($_REQUEST[$v]) : '';

        if($v == 'bo_table' && $$v != null ){
            $bo_table = preg_replace('/[^a-z0-9_]/i', '', $bo_table);
            $bo_table = substr($bo_table, 0, 20);
            if( $bo_table ){
                $board = $wpdb->get_row($wpdb->prepare(" select * from {$g5['board_table']} where bo_table = '%s' ", $bo_table), ARRAY_A);
            }
            if( ! isset($board['bo_table']) && empty($board['bo_table']) )
                g5_alert('존재하지 않는 게시판입니다.');
        }
    }

    if( $g5_rq == 'board_thumbnail_delete' ){
        g5_board_thumbnail_delete( $board );
        return;
    }

    if ($w == '') {
        $html_title .= ' 생성';
        $required = 'required';
        $required_valid = 'alnum_';
        $sound_only = '<strong class="sound_only">필수</strong>';
        
        if( !isset($board) || ! is_array($board) ){
            $board = array();
        }

        $board['bo_count_delete'] = 1;
        $board['bo_count_modify'] = 1;
        $board['bo_read_point'] = $config['cf_read_point'];
        $board['bo_write_point'] = $config['cf_write_point'];
        $board['bo_comment_point'] = $config['cf_comment_point'];
        $board['bo_download_point'] = $config['cf_download_point'];

        $board['bo_gallery_cols'] = 4;
        $board['bo_gallery_width'] = 174;
        $board['bo_gallery_height'] = 124;
        $board['bo_mobile_gallery_width'] = 125;
        $board['bo_mobile_gallery_height'] = 100;
        $board['bo_table_width'] = 100;
        $board['bo_page_rows'] = $config['cf_page_rows'];
        $board['bo_mobile_page_rows'] = $config['cf_page_rows'];
        $board['bo_subject_len'] = 60;
        $board['bo_mobile_subject_len'] = 30;
        $board['bo_new'] = 24;
        $board['bo_hot'] = 100;
        $board['bo_image_width'] = 600;
        $board['bo_upload_count'] = 2;
        $board['bo_upload_size'] = 1048576;
        $board['bo_reply_order'] = 1;
        $board['bo_use_search'] = 1;
        $board['bo_skin'] = 'basic';
        $board['bo_mobile_skin'] = 'basic';
        $board['bo_use_secret'] = 0;
        $board['bo_list_level'] = -1;   //목록보기 권한
        $board['bo_read_level'] = -1;   //글읽기 권한
        $board['bo_write_level'] = -1;   //글쓰기 권한
        $board['bo_comment_level'] = -1;   //댓글쓰기 권한
        $board['bo_link_level'] = -1;   //링크 권한
        $board['bo_upload_level'] = -1;   //업로드 권한
        $board['bo_download_level'] = -1;   //다운로드 권한

        $check_arr = array('bo_subject', 'bo_table', 'bo_mobile_subject', 'bo_device', 'bo_category_list', 'bo_use_category', 'bo_admin', 'bo_list_level', 'bo_read_level', 'bo_write_level', 'bo_reply_level', 'bo_comment_level', 'bo_link_level', 'bo_upload_level', 'bo_download_level', 'bo_html_level', 'bo_use_sideview', 'bo_use_dhtml_editor', 'bo_use_rss_view', 'bo_use_good', 'bo_use_nogood', 'bo_use_name', 'bo_use_signature', 'bo_use_ip_view', 'bo_use_list_content', 'bo_use_list_file', 'bo_use_list_view', 'bo_use_email', 'bo_use_file_content', 'bo_use_sns', 'bo_use_cert', 'cf_cert_use', 'bo_write_min', 'bo_write_max', 'bo_comment_min', 'bo_comment_max', 'bo_order', 'bo_content_head', 'bo_content_tail', 'bo_mobile_content_head', 'bo_mobile_content_tail', 'bo_insert_content', 'bo_sort_field', 'bo_1_subj', 'bo_1', 'bo_2_subj', 'bo_2', 'bo_3_subj', 'bo_3', 'bo_4_subj', 'bo_4', 'bo_5_subj', 'bo_5', 'bo_6_subj', 'bo_6', 'bo_7_subj', 'bo_7', 'bo_8_subj', 'bo_8', 'bo_9_subj', 'bo_9', 'bo_10_subj', 'bo_10', 'bo_skin', 'bo_use_tag' );

        foreach( $check_arr as $v){

            $v = sanitize_key($v);

            if( !isset( $board[$v] ))
                $board[$v] = '';

            if( $add_err_msg && isset($_POST[$v]) ){
                if( $v == 'bo_content_head' || $v == 'bo_content_tail' ){
                    $board[$v] = wp_kses_post( trim($_POST[$v]) );
                } else if ( $v == 'bo_insert_content' ){
                    $board[$v] = implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $_POST[$v] ) ) );
                } else {
                    $board[$v] = sanitize_text_field( trim($_POST[$v]) );
                }
            }
        }

    } else if ($w == 'u') {

        $html_title .= ' 수정';

        if ( !isset($board['bo_table']) )
            $gnupress->add_err_msg = __( '존재하지 않는 게시판입니다.', G5_NAME );

        $readonly = 'readonly';

        wp_cache_delete( 'g5_bo_table_'.$board['bo_table'] );

        if( $g5_get_page_id = g5_get_page_id( g5_page_get_by($board['bo_table'],'name') )){     //게시판이 적용된 페이지가 존재한다면
            $bbs_direct_url = add_query_arg( array(), get_permalink($g5_get_page_id) );
        }
    }

    $g5['title'] = $html_title;

    g5_admin_warnings( $check_post_msg );

    $list_page_url = add_query_arg( array('page'=>'g5_board_list', 'w'=>false, 'bo_table'=>false ) );

    include_once( G5_DIR_PATH.'view/board_form.php' );
}

function g5_admin_post($action){
    global $wpdb, $gnupress;

    //설정
    $g5 = $gnupress->g5;
    $is_admin = g5_is_admin();
    $g5_options = get_option(G5_OPTION_KEY);
    $return_value = '';

    include_once(G5_DIR_PATH.'adm/board_form_update.php');

    return $return_value;
}

function g5_board_list(){
    global $wpdb, $gnupress;

    $qstr = g5_get_qstr();
    //설정
    $g5 = $gnupress->g5;
    $config = $gnupress->config;
    $is_admin = g5_is_admin();

    $check_post_msg = ( isset( $_POST['g5_admin_post'] ) ) ? g5_admin_post( sanitize_key($_POST['g5_admin_post']) ) : false;

    $g5['title'] = __('게시판관리', G5_NAME );
    //파라미터
    $param_arr = array('stx', 'sfl', 'sst', 'sod', 'page');
    foreach( $param_arr as $v ){
        $$v = isset($_REQUEST[$v]) ? sanitize_text_field($_REQUEST[$v]) : '';
    }

    $paged = isset($_REQUEST['paged']) ? intval($_REQUEST['paged']) : 0;

    $sql_common = " from {$g5['board_table']} a ";
    $sql_search = " where (1) ";

    if ($stx) {
        $sql_search .= " and ( ";
        switch ($sfl) {
            case "bo_table" :
                $sql_search .= $wpdb->prepare(" ($sfl like '%s') ", $stx.'%');
                break;
            default :
                $sql_search .= $wpdb->prepare(" ($sfl like '%s') ", '%'.$stx.'%');
                break;
        }
        $sql_search .= " ) ";
    }

    if (!$sst) {
        $sst  = "a.bo_table";
        $sod = "asc";
    }
    
    $sql_order = " order by $sst $sod ";

    $sql = " select count(*) as cnt {$sql_common} {$sql_search} {$sql_order} ";

    $row = $wpdb->get_row($sql, ARRAY_A);
    $total_count = $row['cnt'];

    $limit = $config['cf_page_rows'];
    $total_page  = ceil($total_count / $limit);  // 전체 페이지 계산
    if ($paged < 1) { $paged = 1; } // 페이지가 없으면 첫 페이지 (1 페이지)
    $from_record = ($paged - 1) * $limit; // 시작 열을 구함

    $sql = " select * {$sql_common} {$sql_search} {$sql_order} limit ". intval($from_record).", ".intval($limit);

    $rows = $wpdb->get_results( $sql , ARRAY_A );

    include_once( G5_DIR_PATH.'view/board_list.php' );
}
?>