<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_PLUGIN_PATH.'/kcaptcha/captcha.lib.php');

$captcha_html = "";

if ($is_guest && $board['bo_comment_level'] < 2) {
    $captcha_html = g5_captcha_html('_comment');
}

if(file_exists($board_skin_path.'/view_comment.head.skin.php')){
    include_once($board_skin_path.'/view_comment.head.skin.php');
}

$list = array();

$is_comment_write = false;

if ($member['user_level'] >= $board['bo_comment_level'])
    $is_comment_write = true;

$sql = $wpdb->prepare(" select * from `{$g5['comment_table']}` where wr_id = %d order by cm_num desc, cm_parent, cm_datetime desc ", $wr_id );
$sql = apply_filters('get_get_comment_sql', $sql);

$rows = $wpdb->get_results($sql, ARRAY_A);
$list = array();
$i = 0;
foreach($rows as $row)
{
    if( empty($row) ) continue;

    $list[$i] = $row;
    $list[$i]['is_reply'] = false;  //답변을 달수 있는지
    
    $tmp_name = g5_get_text(g5_cut_str($row['user_display_name'], $config['cf_cut_name'])); // 설정된 자리수 만큼만 이름 출력
    if ($board['bo_use_sideview'])
        $list[$i]['name'] = g5_get_sideview($row['user_id'], $tmp_name, $row['user_email']);
    else
        $list[$i]['name'] = '<span class="'.($row['user_id']?'member':'guest').'">'.$tmp_name.'</span>';


    $list[$i]['content'] = $list[$i]['content1']= '비밀글 입니다.';
    if (!strstr($row['cm_option'], 'secret') ||
        $is_admin ||
        ($write['user_id']==$member['user_id'] && $member['user_id']) ||
        ($row['user_id']==$member['user_id'] && $member['user_id'])) {
        $list[$i]['content1'] = $row['cm_content'];
        $list[$i]['content'] = g5_conv_content($row['cm_content'], 0, 'cm_content');
        $list[$i]['content'] = g5_search_font($stx, $list[$i]['content']);
    } else {
        $ss_name = 'ss_secret_comment_'.$bo_table.'_'.$list[$i]['cm_id'];

        if(!g5_get_session($ss_name)){
            $tmp_href = add_query_arg( array_merge((array) $qstr, array('action'=>'password', 'w' => 'sc', 'cm_id'=> $list[$i]['cm_id'])) , $default_href );
            $list[$i]['content'] = '<a href="'.$tmp_href.'" class="s_cmt">댓글내용 확인</a>';
        } else {
            $list[$i]['content'] = g5_conv_content($row['cm_content'], 0, 'cm_content');
            $list[$i]['content'] = g5_search_font($stx, $list[$i]['content']);
        }
    }

    $list[$i]['datetime'] = substr($row['cm_datetime'],2,14);

    // 관리자가 아니라면 중간 IP 주소를 감춘후 보여줍니다.
    $list[$i]['ip'] = $row['cm_ip'];
    if (!$is_admin)
        $list[$i]['ip'] = preg_replace("/([0-9]+).([0-9]+).([0-9]+).([0-9]+)/", G5_IP_DISPLAY, $row['cm_ip']);

    $list[$i]['is_edit'] = false;
    $list[$i]['is_del']  = false;
    if ($is_comment_write || $is_admin)
    {
        if ($member['user_id'])
        {
            if ($row['user_id'] == $member['user_id'] || $is_admin)
            {
                $list[$i]['del_link'] = apply_filters('g5_comment_delete_href', add_query_arg( array_merge(array('action'=>'delete_comment', 'bo_table' => $bo_table, 'cm_id'=>$row['cm_id'], 'page'=> $page, 'nonce' => wp_create_nonce('g5_cm_delete')), (array) $qstr)), $member );
                $list[$i]['is_edit']   = true;
                $list[$i]['is_del']    = true;
            }
        }
        else
        {
            if (!$row['user_id']) {
                $list[$i]['del_link'] = apply_filters('g5_comment_delete_href', add_query_arg( array_merge(array('action'=>'password', 'bo_table' => $bo_table, 'cm_id'=>$row['cm_id'], 'page'=> $page, 'w' => 'x'), (array) $qstr)), $member );
                $list[$i]['is_del']   = true;
            }
        }
        
        if( ! $row['cm_parent'] ){
            $list[$i]['is_reply'] = true;
        }

    }

    // 05.05.22
    // 답변있는 코멘트는 수정, 삭제 불가
    if ($i > 0 && !$is_admin)
    {
        if ($row['cm_parent'] && $row['cm_parent'] == $list[$i-1]['cm_id'] )
        {
            $list[$i-1]['is_edit'] = false;
            $list[$i-1]['is_del'] = false;
        }
    }
    $i++;
}

//  코멘트수 제한 설정값
if ($is_admin)
{
    $comment_min = $comment_max = 0;
}
else
{
    $comment_min = (int)$board['bo_comment_min'];
    $comment_max = (int)$board['bo_comment_max'];
}

$comment_action_url = apply_filters('g5_comment_action_url', get_permalink() );

include_once($board_skin_path.'/view_comment.skin.php');

/*
if (!$member['user_id']) // 비회원일 경우에만
    echo '<script src="'.G5_JS_URL.'/md5.js"></script>'."\n";
*/

if(file_exists($board_skin_path.'/view_comment.tail.skin.php')){
    include_once($board_skin_path.'/view_comment.tail.skin.php');
}
?>