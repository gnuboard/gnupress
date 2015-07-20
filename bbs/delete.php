<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if ( !isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'g5_board_delete' ) ){
    g5_alert(__('잘못된 요청입니다.', G5_NAME));
}

include_once( G5_DIR_PATH.'lib/g5_taxonomy.lib.php' );
include_once( G5_DIR_PATH.'lib/g5_board_delete.class.php' );

@include_once($board_skin_path.'/delete.head.skin.php');

if ($is_admin == 'super') // 최고관리자 통과
    ;
else if ($is_admin == 'board') { // 게시판관리자이면
    $mb = g5_get_member($write['user_id']);
    if ($member['user_login'] != $board['bo_admin']) // 자신이 관리하는 게시판인가?
        g5_alert('자신이 관리하는 게시판이 아니므로 삭제할 수 없습니다.');
    else if ($member['user_level'] < $mb['user_level']) // 자신의 레벨이 크거나 같다면 통과
        g5_alert('자신의 권한보다 높은 권한의 회원이 작성한 글은 삭제할 수 없습니다.');
} else if ($member['user_id']) {
    if ($member['user_id'] != $write['user_id'])
        g5_alert('자신의 글이 아니므로 삭제할 수 없습니다.');
} else {
    if ($write['user_id']){
        g5_alert('로그인 후 삭제하세요.', wp_login_url( add_query_arg( array('wr_id'=>$wr_id), $default_href) ) );
    } else if (g5_sql_password(trim($_POST['user_pass'])) != $write['user_pass']) {
        g5_alert('비밀번호가 틀리므로 삭제할 수 없습니다.');
    }
}

// 답변이 있는글인지 체크한다.
$row_cnt = $wpdb->get_var( 
                $wpdb->prepare(" select count(*) as cnt from `{$write_table}` where wr_parent = %d ", $write['wr_id'])
            );

if ($row_cnt && !$is_admin){
    g5_alert('이 글과 관련된 답변글이 존재하므로 삭제 할 수 없습니다.\\n\\n우선 답변글부터 삭제하여 주십시오.');
}

// 코멘트 달린 원글의 삭제 여부
$row_cnt = $wpdb->get_var( 
                    $wpdb->prepare(" select count(*) as cnt from `{$g5['comment_table']}` where wr_id = %d and user_id <> %d ", $write['wr_id'], $member['user_id'])
                );

if ($row_cnt >= $board['bo_count_delete'] && !$is_admin)
    g5_alert('이 글과 관련된 코멘트가 존재하므로 삭제 할 수 없습니다.\\n\\n코멘트가 '.$board['bo_count_delete'].'건 이상 달린 원글은 삭제할 수 없습니다.');


// 사용자 코드 실행
@include_once($board_skin_path.'/delete.skin.php');

$count_write = 0;
$count_comment = 0;

$g5_board_delete = new G5_Board_delete;

//답글 및 코멘트를 체크하여 포인트 삭제 및 파일 삭제 등을 처리
$check_delete_array = $g5_board_delete->check_delete($write, $board, $g5);

$count_write = isset($check_delete_array['count_write']) ? $check_delete_array['count_write'] : 0;
$count_comment = isset($check_delete_array['count_comment']) ? $check_delete_array['count_comment'] : 0;

// 게시글 삭제
$sql_str = $wpdb->prepare(" delete from `$write_table` where wr_id = %d ", $write['wr_id']);
$sql = apply_filters('g5_document_delete_sql', $sql_str, $write, $write_table, $member);

if( $sql ){
    if( $result = $wpdb->query($sql) ){

        //포인트, 메타데이터, 스크랩, 썸네일, 파일, 태그 기록, 코멘트 삭제
        $g5_board_delete->etc_check($write, $board);

        $count_write++;
        $count_comment++;
    }
}

$bo_notice = g5_board_notice($board['bo_notice'], $write['wr_id']);
$result = $wpdb->query(
    $wpdb->prepare(" update `{$g5['board_table']}` set bo_notice = '%s' where bo_table = '%s' ", $bo_notice, $bo_table)
    );

// 글숫자 감소
if ($count_write > 0 || $count_comment > 0){

    if( $count_write ){
        $count_write = $wpdb->get_var($wpdb->prepare("select count(wr_id) from `{$g5['write_table']}` where bo_table = '%s' ", $bo_table));
    }
    $result = $wpdb->query(
        $wpdb->prepare(" update `{$g5['board_table']}` set bo_count_write = %d, bo_count_comment = bo_count_comment - %d where bo_table = '%s' ", $count_write, $count_comment, $bo_table)
        );

    /*
    if( !$result ){
        $wpdb->show_errors();
        exit;
    }
    */
}

@include_once($board_skin_path.'/delete.tail.skin.php');

wp_cache_delete( 'g5_bo_table_'.$board['bo_table'] );
wp_cache_delete( 'g5_'.$g5['write_table'].'_'.$write['wr_id'] );
g5_delete_cache_latest($board['bo_table']);

do_action('g5_document_delete', $write, $board );

$goto_url = add_query_arg( array_merge( (array) $qstr, array('page'=>$page)) , $default_href );

g5_goto_url( $goto_url );
exit;
?>