<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

    switch( $action ) {

        case( 'bbs_copy' ) :

            include_once( G5_DIR_PATH.'adm/board_copy_update.php' );

            break;
        case( 'bbs_list_update' ) :
            // check nonce
            if( ! check_admin_referer( 'bbs_list_admin' ) )
                return;
            
            //g5_auth_check( $action, 'w' );

            //파라미터
            $param_arr = array('w', 'chk', 'bo_subject', 'bo_device', 'bo_skin', 'bo_read_point', 'bo_write_point', 'bo_comment_point', 'bo_download_point', 'bo_use_search', 'bo_use_sns', 'bo_order', 'board_table');

            //post 형식이 배열인지 체크
            foreach( $param_arr as $v ){
                $$v = isset($_POST[$v]) ? ( is_array($_POST[$v]) ? array_map('sanitize_text_field', $_POST[$v]) : sanitize_text_field(trim($_POST[$v])) ) : null;
            }

            $act_buttion_title = sanitize_text_field($_POST['act_button']);

            if ($act_buttion_title == __('choose-Modify', 'gnupress') ) {

                for ($i=0, $post_count = count($chk); $i<$post_count; $i++) {
                    // 실제 번호를 넘김
                    $k = $chk[$i];
                    $data = array(
                            'bo_subject' => $bo_subject[$k],
                            'bo_skin' => $bo_skin[$k],
                            'bo_read_point' => $bo_read_point[$k],
                            'bo_write_point' => $bo_write_point[$k],
                            'bo_comment_point' => $bo_comment_point[$k],
                            'bo_download_point' => $bo_download_point[$k]
                        );

                    $formats = array(
                            '%s',
                            '%s',
                            '%d',
                            '%d',
                            '%d',
                            '%d'
                        );
                    $where = array('bo_table' => $board_table[$k]);
                    $where_format = array( '%s' );

                    $result = $wpdb->update($g5['board_table'], $data, $where, $formats, $where_format);

                    if ( $result === false ){
                        g5_show_db_error();
                    }
                }

            } else if ($act_buttion_title == __('choose-Delete', 'gnupress')) {
                if ($is_admin != 'super')
                    g5_alert(__('You can delete only the top management board.', G5_NAME));    //게시판 삭제는 최고관리자만 가능합니다.

                $is_chage_option = false;

                for ($i=0, $post_count = count($chk); $i<$post_count; $i++) {
                    // 실제 번호를 넘김
                    $k = $chk[$i];

                    // $bo_table 값을 반드시 넘겨야 함
                    $tmp_bo_table = sanitize_text_field(trim($_POST['board_table'][$k]));
                    if (!$tmp_bo_table) { continue; }
                    // 게시판 설정 삭제
                    $result = $wpdb->query(
                        $wpdb->prepare(" delete from `{$g5['board_table']}` where bo_table = '%s' ", $tmp_bo_table)
                        );

                    // 게시판 태그 데이터 삭제
                    $result = $wpdb->query(
                        $wpdb->prepare(" delete relation from `{$g5['relation_table']}` as relation inner join `{$g5['write_table']}` as wr on wr.wr_id = relation.object_id where wr.bo_table = '%s' ", $tmp_bo_table)
                        );
                    
                    $result = $wpdb->query(
                        $wpdb->prepare(" delete from `{$g5['comment_table']}` where bo_table = '%s' ", $tmp_bo_table)
                        );

                    $result = $wpdb->query(
                        $wpdb->prepare(" delete from `{$g5['taxonomy_table']}` where taxonomy = '%s' ", g5_get_taxonomy($tmp_bo_table))
                        );

                    // 게시판 메타 데이터 삭제
                    $result = $wpdb->query(
                        $wpdb->prepare(" delete meta from `{$g5['meta_table']}` as meta inner join `{$g5['write_table']}` as wr on wr.wr_id = meta.g5_wr_id where wr.bo_table = '%s' ", $tmp_bo_table)
                        );

                    // 게시판 테이블에서 데이터 삭제
                    $result = $wpdb->query(
                        $wpdb->prepare(" delete from `{$g5['write_table']}` where bo_table = '%s' ", $tmp_bo_table)
                        );

                    g5_delete_cache_latest($tmp_bo_table);

                    $page_action = g5_page_get_by($tmp_bo_table, 'name');
                    if( $pageid = g5_get_page_id($page_action) ){
                        delete_post_meta( $pageid, G5_META_KEY, $page_action );
                        wp_cache_delete( $page_action, 'g5_page_id');
                    }

                    if( $g5_data_path = g5_get_upload_path() ){
                        // 게시판 폴더 전체 삭제
                        g5_rm_rf($g5_data_path.'/file/'.$tmp_bo_table);
                    }

                    if( isset( $g5_options['board_page'][$tmp_bo_table] ) ){
                        $is_chage_option = true;
                        $pageid = $g5_options['board_page'][$tmp_bo_table];
                        unset($g5_options['board_page'][$tmp_bo_table]);
                    }
                }
                
                //g5_options 값에 변경된 내용이 있다면 업데이트
                if( $is_chage_option ){
                    update_option( G5_OPTION_KEY, $g5_options );
                }
            }

            break;

        case( 'bbs_update' ):
        
            // check nonce
            if( ! check_admin_referer( 'bbs-update-fields' ) )
                return;

            //파라미터
            $param_arr = array('w', 'stx', 'sfl', 'sst', 'sod', 'page', 'bo_table');
            
            foreach( $param_arr as $v ){
                $$v = isset($_POST[$v]) ? sanitize_text_field($_POST[$v]) : null;
            }

            if( !$bo_table ){
                $gnupress->add_err_msg = __( 'Enter your \"bo_table\" value.', G5_NAME );
                break;
            }

            $chk_arr = array('bo_subject', 
                            'bo_list_level',
                            'bo_read_level',
                            'bo_write_level',
                            'bo_comment_level',
                            'bo_link_level',
                            'bo_count_modify',
                            'bo_count_delete',
                            'bo_upload_level',
                            'bo_download_level',
                            'bo_read_point',
                            'bo_write_point',
                            'bo_comment_point',
                            'bo_download_point',
                            'bo_use_category',
                            'bo_category_list',
                            'bo_use_sideview',
                            'bo_use_file_content',
                            'bo_use_secret',
                            'bo_use_dhtml_editor',
                            'bo_use_rss_view',
                            'bo_use_good',
                            'bo_use_nogood',
                            'bo_use_name',
                            'bo_use_signature',
                            'bo_use_ip_view',
                            'bo_use_list_view',
                            'bo_use_list_file',
                            'bo_use_list_content',
                            'bo_use_email',
                            'bo_use_cert',
                            'bo_use_sns',
                            'bo_table_width',
                            'bo_subject_len',
                            'bo_mobile_subject_len',
                            'bo_page_rows',
                            'bo_mobile_page_rows',
                            'bo_new',
                            'bo_hot',
                            'bo_image_width',
                            'bo_skin',
                            'bo_mobile_skin',
                            'bo_include_head',
                            'bo_include_tail',
                            'bo_content_head',
                            'bo_content_tail',
                            'bo_mobile_content_head',
                            'bo_mobile_content_tail',
                            'bo_insert_content',
                            'bo_gallery_cols',
                            'bo_gallery_width',
                            'bo_gallery_height',
                            'bo_mobile_gallery_width',
                            'bo_mobile_gallery_height',
                            'bo_upload_count',
                            'bo_upload_size',
                            'bo_reply_order',
                            'bo_write_min',
                            'bo_write_max',
                            'bo_comment_min',
                            'bo_comment_max',
                            'bo_sort_field',
                            'bo_use_tag',
                            'bo_admin'
            );

            $formats = array(
                    '%s',   //bo_subject
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%d',   //bo_gallery_cols
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%d',   //bo_reply_order
                    '%d',
                    '%d',
                    '%d',
                    '%d',
                    '%s',   //bo_sort_field
                    '%s',
                    '%s'
                );
            $data = array();

            foreach( $chk_arr as $v ){
                $data[$v] = isset($_POST[$v]) ? sanitize_text_field(trim($_POST[$v])) : '';

                if( $v == 'bo_content_head' || $v == 'bo_content_tail' ){
                    $data[$v] = wp_kses_post( trim($_POST[$v]) );
                } else if ( $v == 'bo_insert_content' ){
                    $data[$v] = implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $_POST[$v] ) ) );
                }
            }

            $chk_fields_array = array('num', 'writer', 'visit', 'wdate' );

            $bo_sh_fields = isset($_POST['bo_sh_fields']) ? array_map('sanitize_text_field', $_POST['bo_sh_fields']) : array();

            if( ! empty($bo_sh_fields) ){ //번호, 작성자, 조회, 작성일 설정
                $tmp_fields = array_diff($chk_fields_array, $bo_sh_fields);
                $data['bo_sh_fields'] = $bo_sh_fields = implode(",", $tmp_fields);
            } else {
                $data['bo_sh_fields'] = $bo_sh_fields = implode(",", $chk_fields_array);
            }
            //bo_sh_fields추가
            $formats[] = '%s';  //bo_sh_fields

            if ($w == '') {

                $sql = $wpdb->prepare("select count(*) as cnt from {$g5['board_table']} where bo_table = '%s'", $bo_table);
                $row = $wpdb->get_row($sql, ARRAY_A);

                if ($row['cnt']){
                    $gnupress->add_err_msg = __($bo_table.' table already exist.', G5_NAME);  //은(는) 이미 존재하는 TABLE 입니다.
                    break;
                }
                
                //페이지를 생성하는 훅을 걸어준다
                if( isset($_POST['bo_auto_install']) && !empty($_POST['bo_auto_install']) ) {
                    add_action('g5_admin_board_create', 'g5_mk_page');
                }

                $data['bo_table'] = $bo_table;
                $data['bo_count_write'] = 0;
                $data['bo_count_comment'] = 0;
                //추가
                $formats[] = '%s';
                $formats[] = '%d';
                $formats[] = '%d';

                $data = apply_filters('g5_insert_bbs_settings', wp_unslash($data), $bo_table);
                $db_result = $wpdb->insert($g5['board_table'], $data, $formats);
                
                if( $db_result === false ){
                    g5_show_db_error();
                } else {
                    do_action('g5_admin_board_create' , $data);
                }

                $arr_params = array( 'w' => 'u', 'bo_table' => $bo_table );

                g5_javascript_location_replace( add_query_arg($arr_params, admin_url('admin.php?page=g5_board_form')) );

            } else if( $w == 'u' ){
                
                if( !isset($board) )
                    $board = g5_get_board_config($bo_table);

                if (isset($_POST['proc_count'])) {

                    // 게시판의 글 수
                    $sql = $wpdb->prepare("select count(*) as cnt from {$g5['write_table']} where bo_table = '%s'", $bo_table);
                    $data['bo_count_write'] = $wpdb->get_var($sql);
                    $formats[] = '%d';

                    // 게시판의 코멘트 수
                    $sql = $wpdb->prepare("select count(*) as cnt from {$g5['comment_table']} where bo_table = '%s'", $bo_table);
                    $data['bo_count_comment'] = $wpdb->get_var($sql);
                    $formats[] = '%d';
                    
                }

                // 공지사항에는 등록되어 있지만 실제 존재하지 않는 글 아이디는 삭제합니다.
                $bo_notice = "";
                $lf = "";
                if ($board['bo_notice']) {
                    $tmp_array = explode(",", $board['bo_notice']);
                    foreach( $tmp_array as $nt ){
                        if( empty($nt) ) continue;
                        if( $row_cnt = $wpdb->get_var($wpdb->prepare("select count(*) as cnt from {$g5['write_table']} where wr_id = %d ", (int) $nt)) ){
                            $bo_notice .= $lf.(int)$nt;
                            $lf = ",";
                        }

                    }
                    $data['bo_notice'] = $bo_notice;
                    $formats[] = '%s';
                }

                $bo_wp_pageid = isset($_POST['bo_wp_pageid']) ? (int) $_POST['bo_wp_pageid'] : 0;

                $where = array('bo_table'=>$bo_table);
                $where_format = array( '%s' );

                $data = apply_filters('g5_update_bbs_settings', wp_unslash($data), $bo_table);
                $db_result = $wpdb->update($g5['board_table'], $data, $where, $formats, $where_format);
                if( $db_result === false ){

                    g5_show_db_error();

                } else {
                    
                    $page_action = g5_page_get_by($bo_table, 'name');
                    $before_pageid = g5_get_page_id( $page_action );

                    //적용할 페이지가 변경 되었다면
                    if( ($bo_wp_pageid != $before_pageid) ){

                        $result = g5_page_update_metakey( $bo_table, $bo_wp_pageid );

                        if( $result === 1 ){

                            delete_post_meta( $before_pageid, G5_META_KEY, $page_action );

                            wp_cache_delete( $page_action, 'g5_page_id');

                            if( $bo_wp_pageid > 0 ){
                                update_post_meta( $bo_wp_pageid, G5_META_KEY, $page_action );
                            }
                        }
                        
                        if( ! $result ){
                            $gnupress->add_err_msg = __( 'Page application failed.', G5_NAME ); //페이지 적용에 실패 했습니다.
                        } else if( $result == '2' ){
                            $gnupress->add_err_msg = __( 'There is a board that has already been applied to the page you want to apply.<br > Board can not be applied to the page to duplicate.', G5_NAME );  //적용할 페이지에 이미 적용된 게시판이 있습니다., 적용할 페이지에 게시판은 중복될수 없습니다.
                        }
                    }

                    do_action('g5_admin_board_update' , $data );
                }
            }

            $return_value = __( $return_value, G5_NAME );
            break;
    }


// 모든 게시판 동일 옵션 적용
$all_fields = '';

if (g5_is_checked('chk_all_category_list')) {  //분류
    $all_fields .= g5_adm_post_check('bo_category_list');
    $all_fields .= g5_adm_post_check('bo_use_category');
}

//게시판 관리자
if (g5_is_checked('chk_all_admin'))                $all_fields .= g5_adm_post_check('bo_admin');
//목록보기 권한
if (g5_is_checked('chk_all_list_level'))           $all_fields .= g5_adm_post_check('bo_list_level');
//글읽기 권한
if (g5_is_checked('chk_all_read_level'))           $all_fields .= g5_adm_post_check('bo_read_level');
//글쓰기 권한
if (g5_is_checked('chk_all_write_level'))          $all_fields .= g5_adm_post_check('bo_write_level');
//댓글쓰기 권한
if (g5_is_checked('chk_all_comment_level'))        $all_fields .= g5_adm_post_check('bo_comment_level');
//링크 권한
if (g5_is_checked('chk_all_link_level'))           $all_fields .= g5_adm_post_check('bo_link_level');
//업로드 권한
if (g5_is_checked('chk_all_upload_level'))         $all_fields .= g5_adm_post_check('bo_upload_level');
//다운로드 권한
if (g5_is_checked('chk_all_download_level'))       $all_fields .= g5_adm_post_check('bo_download_level');

//원글 수정 불가
if (g5_is_checked('chk_all_count_modify'))         $all_fields .= g5_adm_post_check('bo_count_modify');
//원글 삭제 불가
if (g5_is_checked('chk_all_count_delete'))         $all_fields .= g5_adm_post_check('bo_count_delete');
//글쓴이 사이드뷰
if (g5_is_checked('chk_all_use_sideview'))         $all_fields .= g5_adm_post_check('bo_use_sideview');
//비밀글 적용
if (g5_is_checked('chk_all_use_secret'))           $all_fields .= g5_adm_post_check('bo_use_secret');
//DHTML 에디터 사용
if (g5_is_checked('chk_all_use_dhtml_editor'))     $all_fields .= g5_adm_post_check('bo_use_dhtml_editor');
//RSS 보이기 사용
if (g5_is_checked('chk_all_use_rss_view'))         $all_fields .= g5_adm_post_check('bo_use_rss_view');
//추천 사용
if (g5_is_checked('chk_all_use_good'))             $all_fields .= g5_adm_post_check('bo_use_good');
//비추천 사용
if (g5_is_checked('chk_all_use_nogood'))           $all_fields .= g5_adm_post_check('bo_use_nogood');
//IP 보이기 사용
if (g5_is_checked('chk_all_use_ip_view'))          $all_fields .= g5_adm_post_check('bo_use_ip_view');
//목록에서 내용 사용
if (g5_is_checked('chk_all_use_list_content'))     $all_fields .= g5_adm_post_check('bo_use_list_content');
//목록에서 파일 사용
if (g5_is_checked('chk_all_use_list_file'))        $all_fields .= g5_adm_post_check('bo_use_list_file');
//전체목록 보이기 사용
if (g5_is_checked('chk_all_use_list_view'))        $all_fields .= g5_adm_post_check('bo_use_list_view');
//메일 발송 사용
if (g5_is_checked('chk_all_use_email'))            $all_fields .= g5_adm_post_check('bo_use_email');
//파일 업로드 개수
if (g5_is_checked('chk_all_upload_count'))         $all_fields .= g5_adm_post_check('bo_upload_count');
//파일 업로드 용량
if (g5_is_checked('chk_all_upload_size'))          $all_fields .= g5_adm_post_check('bo_upload_size');
//파일 설명 사용
if (g5_is_checked('chk_all_use_file_content'))     $all_fields .= g5_adm_post_check('bo_use_file_content');
//최소 글수 제한
if (g5_is_checked('chk_all_write_min'))            $all_fields .= g5_adm_post_check('bo_write_min');
//최대 글수 제한
if (g5_is_checked('chk_all_write_max'))            $all_fields .= g5_adm_post_check('bo_write_max');
//최소 댓글수 제한
if (g5_is_checked('chk_all_comment_min'))          $all_fields .= g5_adm_post_check('bo_comment_min');
//최대 댓글수 제한
if (g5_is_checked('chk_all_comment_max'))          $all_fields .= g5_adm_post_check('bo_comment_max');

//전체 검색 사용
if (g5_is_checked('chk_all_use_search'))           $all_fields .= g5_adm_post_check('bo_use_search');
//태그 기능 사용
if (g5_is_checked('chk_all_use_tag'))           $all_fields .= g5_adm_post_check('bo_use_tag');

//스킨 디렉토리
if (g5_is_checked('chk_all_skin'))                 $all_fields .= g5_adm_post_check('bo_skin');
//상단내용
if (g5_is_checked('chk_all_content_head'))         $all_fields .= g5_adm_post_check('bo_content_head');
//하단내용
if (g5_is_checked('chk_all_content_tail'))         $all_fields .= g5_adm_post_check('bo_content_tail');

//제목길이
if (g5_is_checked('chk_all_subject_len'))          $all_fields .= g5_adm_post_check('bo_subject_len');
//모바일 제목 길이
if (g5_is_checked('chk_all_mobile_subject_len'))          $all_fields .= g5_adm_post_check('bo_mobile_subject_len');

//페이지당 목록 수
if (g5_is_checked('chk_all_page_rows'))            $all_fields .= g5_adm_post_check('bo_page_rows');
//모바일 페이지장 목록 수
if (g5_is_checked('chk_all_mobile_page_rows'))            $all_fields .= g5_adm_post_check('bo_mobile_page_rows');

//갤러리 이미지 폭
if (g5_is_checked('chk_all_gallery_width'))        $all_fields .= g5_adm_post_check('bo_gallery_width');
//갤러리 이미지 높이
if (g5_is_checked('chk_all_gallery_height'))       $all_fields .= g5_adm_post_check('bo_gallery_height');

//모바일 갤러리 이미지 폭
if (g5_is_checked('chk_all_mobile_gallery_width')) $all_fields .= g5_adm_post_check('bo_mobile_gallery_width');
//모바일 갤러리 이미지 높이
if (g5_is_checked('chk_all_mobile_gallery_height'))$all_fields .= g5_adm_post_check('bo_mobile_gallery_height');

//게시판 폭 크기
if (g5_is_checked('chk_all_table_width'))          $all_fields .= g5_adm_post_check('bo_table_width');

//새글 아이콘
if (g5_is_checked('chk_all_new'))                  $all_fields .= g5_adm_post_check('bo_new');

//인기글 아이콘
if (g5_is_checked('chk_all_hot'))                  $all_fields .= g5_adm_post_check('bo_hot');

//리스트 정렬 필드
if (g5_is_checked('chk_all_sort_field'))           $all_fields .= g5_adm_post_check('bo_sort_field');

//글읽기 포인트
if (g5_is_checked('chk_all_read_point'))           $all_fields .= g5_adm_post_check('bo_read_point');

//글쓰기 포인트
if (g5_is_checked('chk_all_write_point'))          $all_fields .= g5_adm_post_check('bo_write_point');

//댓글쓰기 포인트
if (g5_is_checked('chk_all_comment_point'))        $all_fields .= g5_adm_post_check('bo_comment_point');

//다운로드 포인트
if (g5_is_checked('chk_all_download_point'))       $all_fields .= g5_adm_post_check('bo_download_point');

//번호, 작성자, 조회, 작성일 설정
if (g5_is_checked('chk_all_sh_fields'))       $all_fields .= " , bo_sh_fields = '$bo_sh_fields' ";

if( $all_fields ){
    $result = $wpdb->query(" update {$g5['board_table']} set bo_table = bo_table {$all_fields} ");
    if ( $result === false ){
        g5_show_db_error();
    }
}

if( $gnupress->bo_table ){
    g5_delete_cache_latest($gnupress->bo_table);
}

?>