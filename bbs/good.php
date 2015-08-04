<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

@include_once($board_skin_path.'/good.head.skin.php');

$good = isset($_REQUEST['good']) ? sanitize_key($_REQUEST['good']) : '';

$use_ajax = ( isset($_REQUEST['use_ajax']) && !empty($_REQUEST['use_ajax']) ) ? true : false;

if ( !function_exists('g5_print_result'))
{
    function g5_print_result($error, $count, $use_ajax=false, $url='')
    {
        if( !$use_ajax ){
            g5_alert($error, $url);
        } else {
            $msg = array('error'=>$error, 'count'=>$count);
            wp_send_json($msg);
        }
    }
}

$err = $count = '';

if( !$err && !$is_member ){
    $err = __('Only available members.', G5_NAME);    //회원만 가능합니다.
}

if ( !$err && !($bo_table && $wr_id))
{
    $err = __('This value bo_table or wr_id is invalid', G5_NAME); //값이 제대로 넘어오지 않았습니다.
}

$ss_name = 'ss_view_'.$bo_table.'_'.$wr_id;

if ( !$err && !g5_get_session($ss_name)) {
    $err = __('You can only recommend or Dislike this post.', G5_NAME); //해당 게시물에서만 추천 또는 비추천 하실 수 있습니다.
}

if( !$err ){
    $row_cnt = $wpdb->get_var($wpdb->prepare(" select count(*) as cnt from {$g5['write_table']} where bo_table = '%s' ", $bo_table));

    if (!$row_cnt)
        $err = __('The board dose not exist.', G5_NAME);   //존재하는 게시판이 아닙니다.
}

if ( !$err && ($good == 'good' || $good == 'nogood') ){

    if($write['user_id'] == $member['user_id']) {
        $err = __('Myself article it can not recommended or deprecated.', G5_NAME);  //자신의 글에는 추천 또는 비추천 하실 수 없습니다.
    }

    if ( !$err && (!$board['bo_use_good'] && $good == 'good') ) {
        $err = __('The board does not use a recommend.', G5_NAME);    //이 게시판은 추천 기능을 사용하지 않습니다.
    }

    if ( !$err && (!$board['bo_use_nogood'] && $good == 'nogood') ) {
        $err = __('The board does not use a nonrecommend.', G5_NAME);    //이 게시판은 비추천 기능을 사용하지 않습니다.
    }

    if( !$err ){
        $sql = $wpdb->prepare(" select bg_flag from {$g5['board_good_table']}
                    where bo_table = '%s'
                    and wr_id = %d
                    and user_id = '%s'
                    and bg_flag in ('good', 'nogood') ", $bo_table, $wr_id, $member['user_id']);

        $bg_flag = $wpdb->get_var($sql);

        if ($bg_flag)
        {
            if ($bg_flag == 'good')
                $status = __('recommend', G5_NAME);
            else
                $status = __('nonrecommend', G5_NAME);

            $err = sprintf(__('The articles have already %s.', G5_NAME), $status);  //이미 $status 하신 글 입니다.
        } else {
            // 내역 생성
            $result = $wpdb->query(
                        $wpdb->prepare(" insert {$g5['board_good_table']} set bo_table = '%s', wr_id = %d, user_id = %d, bg_flag = '%s', bg_datetime = '%s' ", $bo_table, $wr_id, $member['user_id'], $good, G5_TIME_YMDHIS)
                        );

            if( $result === false ){
                $err = "error";
            } else {
                // 추천(찬성), 비추천(반대) 카운트 증가
                $result = $wpdb->query($wpdb->prepare(" update {$g5['write_table']} set wr_{$good} = wr_{$good} + 1 where wr_id = '%d' ", $wr_id));

                $sql = $wpdb->prepare(" select wr_{$good} as count from {$g5['write_table']} where wr_id = %d ", $wr_id);
                $count = $wpdb->get_var($sql);
            }
        }

        g5_print_result($err, $count, $use_ajax);
        return;
    }
}

if( $err )
    g5_print_result($err, '', $use_ajax);

@include_once($board_skin_path.'/good.tail.skin.php');
?>