<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if ( ! isset( $_POST['g5_nonce_field'] ) || ! wp_verify_nonce( $_POST['g5_nonce_field'], 'g5_list' ) ) {
    g5_alert(__('잘못된 요청입니다.', G5_NAME));
}

include_once( G5_DIR_PATH.'lib/g5_taxonomy.lib.php' );
include_once( G5_DIR_PATH.'lib/g5_board_delete.class.php' );

$post_arr = array('wr_password');

foreach($post_arr as $v){
    $$v = isset( $_POST[$v] ) ? $_POST[$v] : '';
}

// 4.11
@include_once($board_skin_path.'/delete_all.head.skin.php');

$count_write = 0;
$count_comment = 0;

$tmp_array = array();
if ($wr_id) // 건별삭제
    $tmp_array[0] = $wr_id;
else // 일괄삭제
    $tmp_array = $_POST['chk_wr_id'];

// 사용자 코드 실행
@include_once($board_skin_path.'/delete_all.skin.php');

// 거꾸로 읽는 이유는 답변글부터 삭제가 되어야 하기 때문임
for ($i=count($tmp_array)-1; $i>=0; $i--)
{
    $write = g5_sql_fetch(" select * from `{$write_table}` where wr_id = '".esc_sql($tmp_array[$i])."' ");

    if ($is_admin == 'super') // 최고관리자 통과
        ;
    else if ($is_admin == 'board') // 게시판관리자이면
    {
        $mb = g5_get_member($write['user_id']);
        if ($member['user_id'] == $board['bo_admin']) // 자신이 관리하는 게시판인가?
            if ($member['mb_level'] >= $mb['mb_level']) // 자신의 레벨이 크거나 같다면 통과
                ;
            else
                continue;
        else
            continue;
    }
    else if ($member['user_id'] && $member['user_id'] == $write['user_id']) // 자신의 글이라면
    {
        ;
    }
    else if ($wr_password && !$write['user_id'] && sql_password($wr_password) == $write['wr_password']) // 비밀번호가 같다면
    {
        ;
    }
    else
        continue;   // 나머지는 삭제 불가

    // 답변이 있는 글인지 확인한다.

    $sql = " select count(*) as cnt from `{$write_table}` where wr_parent = '{$write['wr_id']}' ";

    $row = g5_sql_fetch($sql);
    if ($row['cnt'])
            continue;

    $g5_board_delete = new G5_Board_delete;

    //답글 및 코멘트를 체크하여 포인트 삭제 및 파일 삭제 등을 처리
    $check_delete_array = $g5_board_delete->check_delete($write, $board, $g5);

    $count_write += isset($check_delete_array['count_write']) ? $check_delete_array['count_write'] : 0;
    $count_comment += isset($check_delete_array['count_comment']) ? $check_delete_array['count_comment'] : 0;

    // 게시글 삭제
    $sql = apply_filters('g5_document_delete_sql', "delete from `$write_table` where wr_id = '{$write['wr_id']}'" , $write , $write_table , $member );

    if( $sql ){
        if( $result = $wpdb->query($sql) ){

            //태그 기록 삭제
            g5_delete_object_term_relationships( $write['wr_id'] , g5_get_taxonomy($bo_table) );

            // 최근게시물 삭제

            // 스크랩 삭제
            //sql_query(" delete from {$g5['scrap_table']} where bo_table = '$bo_table' and wr_id = '{$write['wr_id']}' ");

            $count_write++;
            $count_comment++;
        }
    }

    $bo_notice = g5_board_notice($board['bo_notice'], $write['wr_id']);
    g5_sql_query(" update {$g5['board_table']} set bo_notice = '$bo_notice' where bo_table = '$bo_table' ");
    $board['bo_notice'] = $bo_notice;
}

// 글숫자 감소
if ($count_write > 0 || $count_comment > 0)
    g5_sql_query(" update {$g5['board_table']} set bo_count_write = bo_count_write - '$count_write', bo_count_comment = bo_count_comment - '$count_comment' where bo_table = '$bo_table' ");

// 4.11
@include_once($board_skin_path.'/delete_all.tail.skin.php');

do_action('g5_document_all_delete', $write, $board );

g5_goto_url( add_query_arg( array_merge( (array) $qstr, array('page'=>$page)) , $default_href ) );
exit;
?>