<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if ( ! isset( $_POST['g5_nonce_field'] ) || ! wp_verify_nonce( $_POST['g5_nonce_field'], 'g5_move' ) ) {
    wp_die( __('잘못된 요청입니다.', G5_NAME) );
}

if( !isset($bo_table) ){
    wp_die( __('bo_table값이 없습니다.', G5_NAME) );
}

$act = isset($_POST['act']) ? $_POST['act'] : '';

//g5_move_update.class.php
include_once( G5_DIR_PATH.'lib/g5_move_update.class.php' );

// 게시판 관리자 이상 복사, 이동 가능
if ($is_admin != 'board' && $is_admin != 'super')
    wp_die('게시판 관리자 이상 접근이 가능합니다.');

if ($sw != 'move' && $sw != 'copy')
    g5_alert('sw 값이 제대로 넘어오지 않았습니다.');

if(!count($_POST['chk_bo_table']))
    g5_alert('게시물을 '.$act.'할 게시판을 한개 이상 선택해 주십시오.', $url);

$check_arr = array('wr_id_list');

foreach($check_arr as $v){
    $$v = isset($_REQUEST[$v]) ? stripslashes(trim($_REQUEST[$v])) : '';
}

unset($check_arr);

$save = array();
$save_count_write = 0;
$save_count_comment = 0;

$sql = " select distinct wr_num from `$write_table` where wr_id in ({$wr_id_list}) order by wr_id ";
$rows = $wpdb->get_results($sql, ARRAY_A);

foreach( $rows as $row ){
    $wr_num = $row['wr_num'];
    foreach( $_POST['chk_bo_table'] as $bo ){
        $move_bo_table = $bo;

        $count_write = 0;
        $count_comment = 0;

        $g5_move_update = new G5_Move_update($member, $config, $board, $sw);    //인스턴스 생성 및 초기화

        $sql2 = " select * from `$write_table` where bo_table = '$bo_table' and wr_num = '$wr_num' and wr_parent = 0 ";  //원글만 셀렉트

        $rows2 = $wpdb->get_results($sql2, ARRAY_A);

        foreach($rows2 as $row2){
            
            $move_array = $g5_move_update->check_move_update($row2, $write_table, $move_bo_table);
            $count_write += (int) $move_array['count_write'];
            $count_comment += (int) $move_array['count_comment'];
            
            if ($sw == 'move'){
                foreach( $move_array['insert_ids'] as $arr )
                {
                    if( !in_array($arr, $save) )
                        array_push($save,(array) $arr);
                }
            }
        }
        g5_sql_query(" update {$g5['board_table']} set bo_count_write = bo_count_write + '$count_write' where bo_table = '$move_bo_table' ");
        g5_sql_query(" update {$g5['board_table']} set bo_count_comment = bo_count_comment + '$count_comment' where bo_table = '$move_bo_table' ");
    }

    $save_count_write += $count_write;
    $save_count_comment += $count_comment;
}

//게시물 이동이면
if ($sw == 'move')
{
    include_once( G5_DIR_PATH.'lib/g5_taxonomy.lib.php' );

    $src_dir = g5_get_upload_path().'/file/'.$bo_table; // 원본 디렉토리
    $taxonomy = g5_get_taxonomy($bo_table); //태그 분류값을 가져온다.

    foreach($save as $sv)
    {
        if ( empty($sv) ) continue;

        //태그 데이터 삭제
        g5_delete_object_term_relationships( $sv['wr_id'], $taxonomy );
        
        //첨부파일 삭제
        if( $file_meta_data = get_metadata(G5_META_TYPE, $sv['wr_id'], G5_FILE_META_KEY, true ) ){
            foreach((array) $file_meta_data as $key=>$f){
                if( !isset($f['bf_file']) ) continue;
                @unlink($src_dir.'/'.$f['bf_file']);
            }
        }
        //메타 데이터 삭제
        $wpdb->query(" delete from `{$g5['meta_table']}` where g5_wr_id = '{$sv['wr_id']}' ");

        //게시물 삭제
        $wpdb->query(" delete from `$write_table` where wr_id = '{$sv['wr_id']}' ");
    }

    $wpdb->query(" update {$g5['board_table']} set bo_count_write = bo_count_write - '$save_count_write', bo_count_comment = bo_count_comment - '$save_count_comment' where bo_table = '$bo_table' ");
}

$msg = '해당 게시물을 선택한 게시판으로 '.$act.' 하였습니다.';
$opener_href = add_query_arg( array_merge( (array) $qstr, array('page'=>$page)), $default_href );

$charset = get_bloginfo('charset');

do_action('g5_move_update', $opener_href );

echo <<<HEREDOC
<meta http-equiv="content-type" content="text/html; charset=$charset">
<script>
alert("$msg");
opener.document.location.href = "$opener_href";
window.close();
</script>
<noscript>
<p>
    "$msg"
</p>
<a href="$opener_href">돌아가기</a>
</noscript>
HEREDOC;
?>