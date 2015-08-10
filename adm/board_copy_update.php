<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if( ! check_admin_referer( 'bbs-adm-copy' ) )
return;

if( ! isset($bo_table) )
    $bo_table = $gnupress->bo_table;

if( ! isset($board) )
    $board = g5_get_board_config( $bo_table );

$check_param = array('copy_case', 'target_table', 'target_subject', 'copy_case');
foreach($check_param as $v){
    $$v = isset($_POST[$v]) ? sanitize_text_field(trim($_POST[$v])) : '';
}

if (!preg_match('/[A-Za-z0-9_]{1,20}/', $target_table)) {
    g5_alert(__('Board table available only letters, numbers, _ with no spaces. (20 characters)', 'gnupresss'));  //게시판 TABLE명은 공백없이 영문자, 숫자, _ 만 사용 가능합니다. (20자 이내)
}

$row_cnt = $wpdb->get_var($wpdb->prepare("select count(*) as cnt from {$g5['board_table']} where bo_table = %s ", $target_table));

if ($row_cnt)
    g5_alert($target_table.__('is a board table names that already exist', 'gnupress').'\\n'.__('You can not use a table name you want to copy.', 'gnupress'));

//은(는) 이미 존재하는 게시판 테이블명 입니다.\\n복사할 테이블명으로 사용할 수 없습니다.

$file_copy = array();

// 구조만 복사시에는 공지사항 번호는 복사하지 않는다. ( 원글수, 댓글수 포함 )
if ($copy_case == 'schema_only') {
    $board['bo_notice'] = '';
    $board['bo_count_write'] = 0;
    $board['bo_count_comment'] = 0;
}

$data = array(
    'bo_table' => $target_table,
    'bo_subject' => $target_subject,
    'bo_admin' => $board['bo_admin'],
    'bo_list_level' => $board['bo_list_level'],
    'bo_read_level' => $board['bo_read_level'],
    'bo_write_level' => $board['bo_write_level'],
    'bo_reply_level' => $board['bo_reply_level'],
    'bo_comment_level' => $board['bo_comment_level'],
    'bo_upload_level' => $board['bo_upload_level'],
    'bo_download_level' => $board['bo_download_level'],
    'bo_html_level' => $board['bo_html_level'],
    'bo_link_level' => $board['bo_link_level'],
    'bo_count_modify' => $board['bo_count_modify'],
    'bo_count_delete' => $board['bo_count_delete'],
    'bo_read_point' => $board['bo_read_point'],
    'bo_write_point' => $board['bo_write_point'],
    'bo_comment_point' => $board['bo_comment_point'],
    'bo_download_point' => $board['bo_download_point'],
    'bo_use_category' => $board['bo_use_category'],
    'bo_category_list' => $board['bo_category_list'],
    'bo_use_sideview' => $board['bo_use_sideview'],
    'bo_use_file_content' => $board['bo_use_file_content'],
    'bo_use_secret' => $board['bo_use_secret'],
    'bo_use_dhtml_editor' => $board['bo_use_dhtml_editor'],
    'bo_use_rss_view' => $board['bo_use_rss_view'],
    'bo_use_good' => $board['bo_use_good'],
    'bo_use_nogood' => $board['bo_use_nogood'],
    'bo_use_ip_view' => $board['bo_use_ip_view'],
    'bo_use_list_view' => $board['bo_use_list_view'],
    'bo_use_list_content' => $board['bo_use_list_content'],
    'bo_table_width' => $board['bo_table_width'],
    'bo_subject_len' => $board['bo_subject_len'],
    'bo_mobile_subject_len' => $board['bo_mobile_subject_len'],
    'bo_page_rows' => $board['bo_page_rows'],
    'bo_mobile_page_rows' => $board['bo_mobile_page_rows'],
    'bo_new' => $board['bo_new'],
    'bo_hot' => $board['bo_hot'],
    'bo_skin' => $board['bo_skin'],
    'bo_mobile_skin' => $board['bo_mobile_skin'],
    'bo_content_head' => addslashes($board['bo_content_head']),
    'bo_content_tail' => addslashes($board['bo_content_tail']),
    'bo_insert_content' => addslashes($board['bo_insert_content']),
    'bo_upload_size' => $board['bo_upload_size'],
    'bo_use_search' => $board['bo_use_search'],
    'bo_notice' => $board['bo_notice'],
    'bo_upload_count' => $board['bo_upload_count'],
    'bo_use_email' => $board['bo_use_email'],
    'bo_sort_field' => $board['bo_sort_field'],
    'bo_gallery_width' => $board['bo_gallery_width'],
    'bo_gallery_height' => $board['bo_gallery_height'],
    'bo_mobile_gallery_width' => $board['bo_mobile_gallery_width'],
    'bo_mobile_gallery_height' => $board['bo_mobile_gallery_height'],
    'bo_use_tag' => $board['bo_use_tag'],
    'bo_count_write' => $board['bo_count_write'],
    'bo_count_comment' => $board['bo_count_comment']
);

$formats = array(
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
    '%d',
    '%d',
    '%s',
    '%d',
    '%s',
    '%s',
    '%d',
    '%d',
    '%d',
    '%d',
    '%s',
    '%d',
    '%d'
);

$data = apply_filters('g5_board_setting_filters', $g5_data, $board);
$result = $wpdb->insert($g5['board_table'], $data, $formats);
if ( $result === false ){
    g5_show_db_error();
}

$g5_data_path = g5_get_upload_path();

//업로드 폴더 경로를 얻어온 경우에만
if( $g5_data_path ){
    // 게시판 폴더 생성
    @mkdir($g5_data_path.'/file/'.$target_table, G5_DIR_PERMISSION);
    @chmod($g5_data_path.'/file/'.$target_table, G5_DIR_PERMISSION);

    // 디렉토리에 있는 파일의 목록을 보이지 않게 한다.
    $board_path = $g5_data_path.'/file/'.$target_table;
    $file = $board_path . '/index.php';
    $f = @fopen($file, 'w');
    @fwrite($f, '');
    @fclose($f);
    @chmod($file, G5_FILE_PERMISSION);
}

$copy_file = 0;
if ($copy_case == 'schema_data_both') {

    //업로드 폴더 경로를 얻어온 경우에만
    if( $g5_data_path ){
        $d = dir($g5_data_path.'/file/'.$bo_table);

        while ($entry = $d->read()) {
            if ($entry == '.' || $entry == '..') continue;

            if(is_dir($g5_data_path.'/file/'.$bo_table.'/'.$entry)){
                $dd = dir($g5_data_path.'/file/'.$bo_table.'/'.$entry);
                @mkdir($g5_data_path.'/file/'.$target_table.'/'.$entry, G5_DIR_PERMISSION);
                @chmod($g5_data_path.'/file/'.$target_table.'/'.$entry, G5_DIR_PERMISSION);
                while ($entry2 = $dd->read()) {
                    if ($entry2 == '.' || $entry2 == '..') continue;
                    @copy($g5_data_path.'/file/'.$bo_table.'/'.$entry.'/'.$entry2, $g5_data_path.'/file/'.$target_table.'/'.$entry.'/'.$entry2);
                    @chmod($g5_data_path.'/file/'.$target_table.'/'.$entry.'/'.$entry2, G5_DIR_PERMISSION);
                    $copy_file++;
                }
                $dd->close();
            }
            else {
                @copy($g5_data_path.'/file/'.$bo_table.'/'.$entry, $g5_data_path.'/file/'.$target_table.'/'.$entry);
                @chmod($g5_data_path.'/file/'.$target_table.'/'.$entry, G5_DIR_PERMISSION);
                $copy_file++;
            }
        }
        $d->close();
    }
    // 글복사
    $sql = $wpdb->prepare("select * from {$g5['write_table']} where bo_table = '%s' order by wr_num, wr_parent", $bo_table);
    if( $rows = $wpdb->get_results( $sql, ARRAY_A ) ){
        $before_parent_arr = array();

        foreach($rows as $row){
            if( empty($row) ) continue;
            
            $save_wr_id = $row['wr_id'];
            unset($row['wr_id']);

            $row['bo_table'] = $target_table;
            if( (int) $row['wr_parent'] > 0 ){
                if( isset($before_parent_arr['id_'.$row['wr_parent']]) ){
                    $row['wr_parent'] = $before_parent_arr['id_'.$row['wr_parent']];
                }
            }

            if( $result = $wpdb->insert($g5['write_table'], $row) ){
                $before_parent_arr['id_'.$save_wr_id] = $wpdb->insert_id;
                //글쓰기 메타 데이터 복사
                g5_writemeta_copy($wpdb->insert_id, $save_wr_id);
                //코멘트 데이터 복사
                g5_comment_copy($wpdb->insert_id, $save_wr_id, $target_table , $bo_table);
                //태그 데이터 복사
                g5_tagdata_copy($wpdb->insert_id, $save_wr_id, $target_table , $bo_table);
            }
        }
    }

}
g5_delete_cache_latest($bo_table);
g5_delete_cache_latest($target_table);
g5_check_super_cache();
$go_url = admin_url( 'admin.php?page=g5_board_list' );

g5_alert(__('Copy succeeded.', 'gnupress'), $go_url); //복사에 성공 했습니다.
?>