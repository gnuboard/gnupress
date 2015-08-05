<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
define('G5_CAPTCHA', true);

if ( ! isset( $_POST['g5_nonce_field'] ) || ! wp_verify_nonce( $_POST['g5_nonce_field'], 'g5_comment_write' ) ) {
    g5_alert(__('Invalid Connection.', G5_NAME), get_permalink() );
}

include_once(G5_PLUGIN_PATH.'/kcaptcha/captcha.lib.php');

$post_data = $_POST;

add_filter( 'sanitize_text_field', 'g5_escape_post_content', 10, 2 );   //앵글브라켓(<, >) 이 문제되서...
$cm_content = implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $_POST['cm_content'] ) ) );
remove_filter( 'sanitize_text_field', 'g5_escape_post_content', 10 );

// 090710
if (substr_count($cm_content, "&#") > 50) {
    g5_alert(__('It did the code is incorrect, the content contains multiple.', G5_NAME));    //내용에 올바르지 않은 코드가 다수 포함되어 있습니다.
    exit;
}

@include_once($board_skin_path.'/write_comment_update.head.skin.php');

$check_param = array('user_name', 'user_pass');
foreach($check_param as $v){
    $$v = isset($_POST[$v]) ? sanitize_text_field($_POST[$v]) : '';
}

$cm_secret = ( isset($_POST['cm_secret']) && !empty($_POST['cm_secret']) ) ? 'secret' : '';

$user_email = '';
if ( isset($_POST['user_email']) && !empty($_POST['user_email']) )
    $user_email = sanitize_email($_POST['user_email']);

// 비회원의 경우 이름이 누락되는 경우가 있음
if ($is_guest) {
    if ($user_name == '')
        g5_alert(__('The name required.', G5_NAME)); //이름은 필히 입력하셔야 합니다.
    if(!g5_chk_captcha())
        g5_alert(__('Captcha incorrect.', G5_NAME));    //자동등록방지 숫자가 틀렸습니다.
}

if ($w == "c" || $w == "cu") {
    if ($member['user_level'] < $board['bo_comment_level'])
        g5_alert( __('You do not have permission to write a comment.', G5_NAME) );  //댓글을 쓸 권한이 없습니다.
}
else
    g5_alert('w value is invalid.'); //w 값이 유효하지 않습니다.

// 세션의 시간 검사
// 4.00.15 - 댓글 수정시 연속 게시물 등록 메시지로 인한 오류 수정
if ($w == 'c' && $_SESSION['ss_datetime'] >= (G5_SERVER_TIME - $config['cf_delay_sec']) && !$is_admin)
    g5_alert( __('Posts in too soon, you can not continuously post.', G5_NAME) );  //너무 빠른 시간내에 게시물을 연속해서 올릴 수 없습니다.

g5_set_session('ss_datetime', G5_SERVER_TIME);

if( isset($write) && !empty($write) ){
    $wr = &$write;
} else {
    $wr = g5_get_write($write_table, $wr_id);
}

if ( empty($wr['wr_id']) )
    g5_alert(__('This article does not exist.\\nThe article may have been deleted or moved.', G5_NAME));    //글이 존재하지 않습니다.\\n글이 삭제되었거나 이동하였을 수 있습니다.


// "인터넷옵션 > 보안 > 사용자정의수준 > 스크립팅 > Action 스크립팅 > 사용 안 함" 일 경우의 오류 처리
// 이 옵션을 사용 안 함으로 설정할 경우 어떤 스크립트도 실행 되지 않습니다.
if (!trim($_POST["cm_content"])) die ("You must enter the contents"); //내용을 입력하여 주십시오.

if ($is_member)
{
    $user_id = $member['user_id'];
    // 4.00.13 - 실명 사용일때 댓글에 닉네임으로 입력되던 오류를 수정
    $user_name = addslashes(g5_clean_xss_tags($board['bo_use_name'] ? $member['user_name'] : $member['user_display_name']));
    $user_pass = g5_sql_password($member['user_pass']);
    $user_email = addslashes($member['user_email']);
}
else
{
    $user_id = '';
    $user_pass = g5_sql_password($user_pass);
}

if ($w == 'c') // 댓글 입력
{
    // 댓글쓰기 포인트설정시 회원의 포인트가 음수인 경우 댓글을 쓰지 못하던 버그를 수정 (곱슬최씨님)
    $tmp_point = ($member['mb_point'] > 0) ? $member['mb_point'] : 0;
    if ($tmp_point + $board['bo_comment_point'] < 0 && !$is_admin)
        g5_alert(
        sprintf( __('Because your point is %s points less or missing, can not Comment ( %s points required ) the article.\n\nAfter a point collect, please comment article again.', G5_NAME), number_format($member['mb_point']), number_format($board['bo_comment_point']))
        );

    // 댓글 답변
    if ($cm_id)
    {
        $reply_array = g5_get_write($g5['comment_table'], $cm_id, 'cm_id');
        if (!$reply_array['cm_id']){
            g5_alert(__('There are no comments to answer.\\n\\nComments may be deleted during the response.', G5_NAME));     //답변할 댓글이 없습니다.\\n\\n답변하는 동안 댓글이 삭제되었을 수 있습니다.
        }

        if( $reply_array['cm_parent'] && $config['cf_parent_limit'] ){
            g5_alert(__('You can not answer anymore.\\n\\nThe answer can be only one step.', G5_NAME));    //더 이상 답변하실 수 없습니다.\\n\\n답변은 1단계 까지만 가능합니다.
        }

        $cm_num = $reply_array['cm_num'];
    } else {
        $cm_num = g5_get_next_num( $g5['comment_table'], $wr_id, 'comment' );
    }

    $cm_subject = g5_get_text(stripslashes($wr['wr_subject']));

    $cm_option = apply_filters('g5_comment_update_option', $cm_secret, $board);

    $cm_data = array(
            'wr_id' => $wr_id,
            'bo_table' => $bo_table,
            'cm_parent' => $cm_id,
            'cm_num' => $cm_num,
            'user_id' => $user_id,
            'user_pass' => $user_pass,
            'user_display_name' => $user_name,
            'user_email' => $user_email,
            'cm_subject' => '',
            'cm_content' => $cm_content,
            'cm_datetime' => G5_TIME_YMDHIS,
            'cm_ip' => $_SERVER['REMOTE_ADDR'],
            'cm_option' => $cm_option
        );

    $formats = array(
            '%d',
            '%s',
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
            '%s'
        );
    
    $cm_data = apply_filters('g5_insert_comment_filters', wp_unslash($cm_data), $post_data);

    // insert
    $result = $wpdb->insert( $g5['comment_table'], $cm_data, $formats );

    if ( $result === false ){
        g5_show_db_error();
    }

    $comment_id = $wpdb->insert_id;

    // 원글에 댓글수 증가 & 마지막 시간 반영
    g5_sql_query(" update `$write_table` set wr_comment = wr_comment + 1, wr_last = '".G5_TIME_YMDHIS."' where wr_id = '$wr_id' ");

    // 댓글 1 증가
    g5_sql_query(" update {$g5['board_table']} set bo_count_comment = bo_count_comment + 1 where bo_table = '$bo_table' ");

    // 포인트 부여
    g5_insert_point($member['user_id'], $board['bo_comment_point'], "{$board['bo_subject']} {$wr_id}-{$comment_id} ".__('Write a comment', G5_NAME), $bo_table, $comment_id, __('comment', G5_NAME));

    // 메일발송 사용
    if ($config['cf_email_use'] && $board['bo_use_email'])
    {

        $mail_content = nl2br(g5_get_text(stripslashes(__('Post', G5_NAME)."\n{$wr['wr_subject']}\n\n\n".__('Comment', G5_NAME)."\n$cm_content")));

        $warr = array( ''=>__('input', G5_NAME), 'u'=>__('modify', G5_NAME), 'r'=>__('reply', G5_NAME), 'c'=>__('comment', G5_NAME), 'cu'=>__('Comments modifiy', G5_NAME) );
        $str = $warr[$w];

        $mail_subject = '['.get_bloginfo('name').'] '.$board['bo_subject'].' - '. sprintf(__('This article has been %s to the board.', G5_NAME), $str); //게시판에 %s글이 올라왔습니다.
        // 4.00.15 - 메일로 보내는 댓글의 바로가기 링크 수정
        $link_url = add_query_arg( array_merge( (array) $qstr, array('wr_id'=>$wr_id) ) , $default_href )."#c_".$comment_id;

        ob_start();
        include_once (G5_DIR_PATH.'bbs/write_update_mail.php');
        $content = ob_get_contents();
        ob_end_clean();

        $array_email = array();
        // 게시판관리자에게 보내는 메일
        if ($config['cf_email_wr_board_admin']){
            $bo_admins = explode(',' , $board['bo_admin']);

            foreach($bo_admins as $user_id){
                if( empty($user_id) ) continue;

                $tmb = g5_get_memeber($user_id);
                if( isset($tmb['user_email']) ) $array_email[] = $tmb['user_email'];
            }
        }
        // 최고관리자에게 보내는 메일
        if ($config['cf_email_wr_super_admin']) $array_email[] = get_bloginfo('admin_email');

        // 원글게시자에게 보내는 메일
        if ($config['cf_email_wr_write']) $array_email[] = $wr['wr_email'];

        // 댓글 쓴 모든이에게 메일 발송이 되어 있다면 (자신에게는 발송하지 않는다)
        if ($config['cf_email_wr_comment_all']) {
            $sql = $wpdb->prepare(" select distinct user_email from `{$g5['comment_table']}`
                        where user_email not in ( '{$wr['user_email']}', '{$member['user_email']}', '' )
                        and wr_id = %d ", $wr_id);

            $rows = $wpdb->get_results($sql);

            foreach($rows as $row){
                if( empty($row) ) continue;
                $array_email[] = $row['user_email'];
            }
        }

        // 중복된 메일 주소는 제거
        $unique_email = array_unique($array_email);
        $unique_email = array_values($unique_email);

        $attachments = apply_filters( 'g5_mail_comment_attach', array() );
        $headers = 'From: '.$user_name.' <'.$user_email.'>' . "\r\n";

        add_filter( 'wp_mail_content_type', 'g5_set_html_content_type' );
        foreach($unique_email as $email){
            if( empty($email) ) continue;
            wp_mail($email, $mail_subject, $content, $headers, $attachments );
        }
        remove_filter( 'wp_mail_content_type', 'g5_set_html_content_type' );
    }
}
else if ($w == 'cu') // 댓글 수정
{
    $comment = $reply_array = g5_get_write($g5['comment_table'], $cm_id, 'cm_id');

    if ($is_admin == 'super') // 최고관리자 통과
        ;
    else if ($is_admin == 'board') { // 게시판관리자이면
        $mb = g5_get_member($comment['user_id']);
        if ( g5_check_bo_admin($board['bo_admin'], $member['user_id']) ) { // 자신이 관리하는 게시판인가?
            if ($member['user_level'] >= $mb['user_level']) // 자신의 레벨이 크거나 같다면 통과
                ;
            else
                g5_alert(__('Higher than the authority of the administrator of the board members commented and can not be modified.', G5_NAME));     //게시판관리자의 권한보다 높은 회원의 댓글이므로 수정할 수 없습니다.
        } else
            g5_alert(__('The board they manage to because you can not modify the comment.', G5_NAME));   //자신이 관리하는 게시판이 아니므로 댓글을 수정할 수 없습니다.
    } else if ($member['user_id']) {
        if ($member['user_id'] != $comment['user_id'])
            g5_alert(__('Because your posts is not then can not edit this posts.', G5_NAME));   //자신의 글이 아니므로 수정할 수 없습니다.
    } else {
        if($comment['user_pass'] != $user_pass)
            g5_alert(__('You do not have permission to edit the comment.', G5_NAME)); //댓글을 수정할 권한이 없습니다.
    }

    $sql = $wpdb->prepare("select count(*) as cnt from`{$g5['comment_table']}` where cm_parent = %d and cm_id <> %d and wr_id = %d", $cm_id, $cm_id, $wr_id );

    $row_cnt = $wpdb->get_var($sql);
    if ($row_cnt && !$is_admin){
        g5_alert(__('Comments and responses can not be modified because it related to comments there.', G5_NAME));   //이 댓글와 관련된 답변댓글이 존재하므로 수정 할 수 없습니다.
    }

    $cm_data = array(
        'cm_content' => $cm_content
    );

    if (!$is_admin){
        $cm_data['cm_ip'] = $_SERVER['REMOTE_ADDR'];
    }

    $cm_data['cm_option'] = apply_filters('g5_comment_update_option', $cm_secret, $board);

    $where = array( 'cm_id' => $cm_id );

    $cm_data = apply_filters('g5_update_comment_filters', wp_unslash($cm_data), $post_data);

    $result = $wpdb->update($g5['comment_table'], $cm_data, $where);
    if ( $result === false ){
        g5_show_db_error();
    }
    
    $comment_id = $cm_id;
}

// 사용자 코드 실행
@include_once($board_skin_path.'/write_comment_update.skin.php');
@include_once($board_skin_path.'/write_comment_update.tail.skin.php');

wp_cache_delete( 'g5_bo_table_'.$board['bo_table'] );
wp_cache_delete( 'g5_'.$g5['write_table'].'_'.$wr_id );

$redirect_to = add_query_arg( array( 'bo_table'=>$board['bo_table'], 'wr_id'=>$wr_id ), get_permalink() ).'&#c_'.$comment_id;

$redirect_to = apply_filters( 'write_comment_update_move_url', $redirect_to );

do_action( 'write_comment_update_move_url', $redirect_to , $board, $wr_id, $cm_data , $w );

wp_safe_redirect( $redirect_to );
exit;
?>