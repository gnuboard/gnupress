<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$html_title = '링크 &gt; '.g5_conv_subject($write['wr_subject'], 255);

$no = isset( $_REQUEST['no'] ) ? (int) $_REQUEST['no'] : 0;

if (!($bo_table && $wr_id && $no))
    g5_alert_close(__('This value bo_table or wr_id is invalid', G5_NAME)); //값이 제대로 넘어오지 않았습니다.

// SQL Injection 예방
$row_cnt = $wpdb->get_var($wpdb->prepare(" select count(*) as cnt from {$g5['write_table']} where bo_table = '%s' ", $bo_table));

if (!$row_cnt)
    g5_alert_close(__('The board dose not exist.', G5_NAME));  //존재하는 게시판이 아닙니다.

if (!$write['wr_link'.$no])
    g5_alert_close(__('No exist link', G5_NAME)); //링크가 없습니다.

$ss_name = 'ss_link_'.$bo_table.'_'.$wr_id.'_'.$no;
if (empty($_SESSION[$ss_name]))
{
    $sql = $wpdb->prepare(" update {$g5['write_table']} set wr_link{$no}_hit = wr_link{$no}_hit + 1 where wr_id = %d ", $wr_id );
    $wpdb->query($sql);

    g5_set_session($ss_name, true);
}

g5_goto_url(g5_set_http($write['wr_link'.$no]));
exit;
?>