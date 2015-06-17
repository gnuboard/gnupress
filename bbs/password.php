<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$nonce_name = '';
$nonce_key = '';
switch ($w) {
    case 'u' :
        $action = 'write';
        $return_url = add_query_arg( array('wr_id'=>$wr_id) , $default_href );
        break;
    case 'd' :
        $action = 'delete';
        $return_url = add_query_arg( array('wr_id'=>$wr_id) , $default_href );
        $nonce_name = 'g5_board_delete';
        $nonce_key = 'nonce';
        break;
    case 'x' :
        $action = 'delete_comment';
        $wr_id = $wpdb->get_var($wpdb->prepare(" select wr_id from `{$g5['comment_table']}` where cm_id = %d ", $cm_id));
        $return_url = add_query_arg( array('wr_id'=>$wr_id) , $default_href ).'#c_'.$cm_id;
        $nonce_name = 'g5_cm_delete';
        $nonce_key = 'nonce';
        break;
    case 's' :
        // 비밀번호 창에서 로그인 하는 경우 관리자 또는 자신의 글이면 바로 글보기로 감
        if ($is_admin || ($member['user_id'] == $write['user_id'] && $write['user_id'])){
            $tmp_url = add_query_arg( array('wr_id' => $wr_id) , $default_href );
            g5_goto_url($tmp_url);
        } else {
            $action = 'password_check';
            $return_url = $default_href;
        }
        break;
    case 'sc' :     //댓글 
        $cm_write = g5_get_write( $g5['comment_table'], $cm_id );
        $g5['title'] = g5_get_content_text( $cm_write['cm_content'] );
        
        // 비밀번호 창에서 로그인 하는 경우 관리자 또는 자신의 글이면 바로 글보기로 감
        if ($is_admin || ($member['user_id'] == $cm_write['user_id'] && $cm_write['user_id'])) {
            $tmp_url = add_query_arg( array('wr_id' => $wr_id) , $default_href );
            g5_goto_url($tmp_url);
        } else {
            $action = 'password_check';
            $return_url = add_query_arg( array('wr_id' => $wr_id) , $default_href );
        }
        break;
    default :
        g5_alert('w 값이 제대로 넘어오지 않았습니다.');
}

if( isset($write['wr_subject']) && "" != $write['wr_subject'] ) {
    $g5['title'] = $write['wr_subject'];
}

$password_action_url = apply_filters('g5_password_action_url', get_permalink() , $w );

include_once($board_skin_path.'/password.skin.php');
?>