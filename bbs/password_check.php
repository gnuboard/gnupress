<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$add_hash = '';
if ($w == 's') {
    $qstr = g5_null_array_check(array('sfl'=>$sfl, 'stx'=>$stx, 'sop' => $sop, 'wr_id' => $wr_id, 'page'=> $page));
    
    if( isset($write) ){
        $wr = $write;
    } else {
        $wr = g5_get_write($write_table, $wr_id);
    }

    if (g5_sql_password(trim($_POST['user_pass'])) != $wr['user_pass']){
        g5_alert('비밀번호가 틀립니다.');
        exit;
    }
    // 세션에 아래 정보를 저장. 하위번호는 비밀번호없이 보아야 하기 때문임.
    $ss_name = 'ss_secret_'.$bo_table.'_'.$wr['wr_num'];
    g5_set_session($ss_name, TRUE);

} else if ($w == 'sc') {
    $cm = g5_get_write($g5['comment_table'], $cm_id, 'cm_id');

    if (g5_sql_password(trim($_POST['user_pass'])) != $cm['user_pass']){
        g5_alert('비밀번호가 틀립니다.');
        exit;
    }

    // 세션에 아래 정보를 저장. 하위번호는 비밀번호없이 보아야 하기 때문임.
    $ss_name = 'ss_secret_comment_'.$bo_table.'_'.$cm['cm_id'];
    g5_set_session($ss_name, TRUE);

    $qstr = g5_null_array_check(array('sfl'=>$sfl, 'stx'=>$stx, 'sop' => $sop, 'wr_id' => $cm['wr_id'], 'cm_id' => $cm_id , 'page'=> $page));
    $add_hash = '#c_'.$cm_id;
} else {
    g5_alert('w 값이 제대로 넘어오지 않았습니다.');
}

$tmp_url = add_query_arg( (array) $qstr , $default_href ).$add_hash;
g5_goto_url($tmp_url);
?>