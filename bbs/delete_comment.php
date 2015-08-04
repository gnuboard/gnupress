<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if ( !isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'g5_cm_delete' ) || !$cm_id ){
    g5_alert(__('Invalid request.', G5_NAME));    //잘못된 요청입니다.
}

// 4.1
@include_once($board_skin_path.'/delete_comment.head.skin.php');

$comment_write = $wpdb->get_row(
                            $wpdb->prepare(" select * from `{$g5['comment_table']}` where cm_id = %d ", $cm_id)
                    , ARRAY_A);

if (!$comment_write['cm_id'])
    g5_alert(__('No registered comments or not post comments.', G5_NAME));  //등록된 코멘트가 없거나 코멘트 글이 아닙니다.

if ($is_admin == 'super') // 최고관리자 통과
    ;
else if ($is_admin == 'board') { // 게시판관리자이면
    $mb = g5_get_member($comment_write['user_id']);
    if ($member['user_id'] == $board['bo_admin']) { // 자신이 관리하는 게시판인가?
        if ($member['user_level'] >= $mb['user_level']) // 자신의 레벨이 크거나 같다면 통과
            ;
        else
            g5_alert(__('Because of the high board members over the rights of the administrator can not delete comments.', G5_NAME));  //게시판관리자의 권한보다 높은 회원의 코멘트이므로 삭제할 수 없습니다.
    } else
        g5_alert(__('The Board they manage to because you can not delete the comments.', G5_NAME));    //자신이 관리하는 게시판이 아니므로 코멘트를 삭제할 수 없습니다.
} else if ($member['user_id']) {
    if ($member['user_id'] != $comment_write['user_id'])
        g5_alert(__('You can not delete your posts in this because.', G5_NAME));   //자신의 글이 아니므로 삭제할 수 없습니다.
} else {
    if (g5_sql_password(trim($_POST['user_pass'])) != $comment_write['user_pass'])
        g5_alert(__('Incorrect password.', G5_NAME));   //비밀번호가 틀립니다.
}

$row_cnt = $wpdb->get_var(
                        $wpdb->prepare(" select count(*) as cnt from `{$g5['comment_table']}` where cm_parent = %d ", $cm_id)
            );

if ($row_cnt && !$is_admin)
    g5_alert(__('You can not delete comments because the comments and responses relating to exist.', G5_NAME));   //이 코멘트와 관련된 답변코멘트가 존재하므로 삭제 할 수 없습니다.

// 코멘트 포인트 삭제
if (!g5_delete_point($comment_write['user_id'], $bo_table, $cm_id, __('Comment', G5_NAME)))
    g5_insert_point($comment_write['user_id'], $board['bo_comment_point'] * (-1), "{$board['bo_subject']} {$comment_write['cm_id']}-{$cm_id} 댓글삭제");

// 코멘트 삭제
$result = $wpdb->query(
                $wpdb->prepare(" delete from `{$g5['comment_table']}` where cm_id = %d ", $cm_id)
            );

// 코멘트가 삭제되므로 해당 게시물에 대한 최근 시간을 다시 얻는다.
$wr_last = $wpdb->get_var(
                        $wpdb->prepare(" select max(cm_datetime) as wr_last from `{$g5['comment_table']}` where wr_id = %d ", $comment_write['wr_id'])
            );

// 원글의 코멘트 숫자를 감소
$result = $wpdb->query(
                $wpdb->prepare(" update `{$write_table}` set wr_comment = case wr_comment when 0 then 0 else wr_comment - 1 end, wr_last = '%s' where wr_id = %d ", $wr_last, $comment_write['wr_id'])
            );

// 코멘트 숫자 감소
$result = $wpdb->query(
                $wpdb->prepare(" update `{$g5['board_table']}` set bo_count_comment = case bo_count_comment when 0 then 0 else bo_count_comment - 1 end where bo_table = '%s ", $bo_table)
            );

// 사용자 코드 실행
@include_once($board_skin_path.'/delete_comment.skin.php');
@include_once($board_skin_path.'/delete_comment.tail.skin.php');

do_action('g5_comment_delete', $comment_write, $board );

g5_goto_url( add_query_arg( array_merge( (array) $qstr, array('page'=>$page, 'wr_id'=>$comment_write['wr_id'])) , $default_href ) );
?>