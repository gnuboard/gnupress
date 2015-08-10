<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

//wp-super-cache 사용시 이 페이지는 cache하지 않음
if ( ! defined( 'DONOTCACHEPAGE' ) ) {
    define( 'DONOTCACHEPAGE', true );
}

if (!$is_member)
    g5_alert_close(__('Only members can view.', G5_NAME)); //회원만 조회하실 수 있습니다.

$g5['title'] = $member['user_display_name'].__('\'S scrap');

$sql_common = $wpdb->prepare(" from {$g5['scrap_table']} where user_id = '%s' ", $member['user_id']);
$sql_order = " order by ms_id desc ";

$sql = " select count(*) as cnt $sql_common ";
$total_count = $wpdb->get_var($sql);

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($page < 1) $page = 1; // 페이지가 없으면 첫 페이지 (1 페이지)
$from_record = ($page - 1) * $rows; // 시작 열을 구함

$list = array();

$sql = " select *
            $sql_common
            $sql_order
            limit $from_record, $rows ";

$results = $wpdb->get_results($sql, ARRAY_A);

$i = 0;
$list = array();

foreach( $results as $row ){

    $list[$i] = $row;

    // 순차적인 번호 (순번)
    $num = $total_count - ($page - 1) * $rows - $i;

    // 게시판 제목
    $sql2 = $wpdb->prepare(" select bo_subject from {$g5['board_table']} where bo_table = '%s' ", $row['bo_table']);
    $row2 = $wpdb->get_row($sql2, ARRAY_A);

    if (!$row2['bo_subject']) $row2['bo_subject'] = '[게시판 없음]';

    // 게시물 제목
    $tmp_write_table = $g5['write_table'];
    $sql3 = $wpdb->prepare(" select wr_subject from $tmp_write_table where wr_id = %d ", intval($row['wr_id']));

    $row3 = $wpdb->get_row($sql3, ARRAY_A);

    $subject = g5_get_text(g5_cut_str($row3['wr_subject'], 100));

    if (!$row3['wr_subject'])
        $row3['wr_subject'] = '[글 없음]';

    $list[$i]['num'] = $num;

    if ( ! ($g5_bbs_get_url = g5_page_get_by($row['bo_table'])) ){
        $g5_bbs_get_url = add_query_arg(array('bo_table'=>false, 'wr_id'=>false), $row['ms_url']);
    }
    $list[$i]['opener_href'] = $g5_bbs_get_url;
    $list[$i]['opener_href_wr_id'] = add_query_arg(array('bo_table'=>false, 'wr_id'=>$row['wr_id']), $g5_bbs_get_url);
    $list[$i]['bo_subject'] = $row2['bo_subject'];
    $list[$i]['subject'] = $subject;
    $list[$i]['del_href'] = add_query_arg(array('gaction'=>'scrap_delete', 'ms_id'=>$row['ms_id'], 'page'=>$page, 'nonce' => wp_create_nonce('g5_scrap_delete') ), $current_url);

    $i++;
}

include_once($member_skin_path.'/scrap.skin.php');
?>
