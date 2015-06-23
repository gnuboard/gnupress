<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if ( ! isset( $_POST['_wpnonce_g5_field'] ) || ! wp_verify_nonce( $_POST['_wpnonce_g5_field'], 'g5_scrap' ) ) {
    return;
}

$refer_page = isset($_REQUEST['ms_url']) ? esc_url_raw(urldecode($_REQUEST['ms_url'])) : wp_get_referer();

$scrap_href = add_query_arg( array('action'=>'scrap', 'gaction'=>false) );

if (!$is_member)
{
    $refer_page = wp_get_referer();

    $href = wp_login_url( $refer_page );

    echo '<script> alert(\'회원만 접근 가능합니다.\'); top.location.href = \''.$href.'\'; </script>';
    exit;
}

$sql = $wpdb->prepare("select count(*) as cnt from {$g5['scrap_table']}
            where user_id = '{$member['user_id']}'
            and bo_table = '%s'
            and wr_id = %d ", $bo_table, $wr_id);

$row_cnt = $wpdb->get_var( $sql );

if ($row_cnt)
{
    $scrap_url = add_query_arg( array('action'=>'scrap'), $current_url );

    echo '
    <script>
    if (confirm(\'이미 스크랩하신 글 입니다.'."\\n\\n".'지금 스크랩을 확인하시겠습니까?\'))
        document.location.href = \''.$scrap_url.'\';
    else
        window.close();
    </script>
    <noscript>
    <p>이미 스크랩하신 글 입니다.</p>
    <a href="$scrap_url">스크랩 확인하기</a>
    <a href="$refer_page">돌아가기</a>
    </noscript>';
    exit;
}

$cm_content = '';
if (isset($_POST['cm_content'])) {
    $cm_content = implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $_POST['cm_content'] ) ) );
    $cm_content = substr(trim($cm_content),0,65536);
    $cm_content = preg_replace("#[\\\]+$#", "", $cm_content);
}

// 덧글이 넘어오고 코멘트를 쓸 권한이 있다면
if ($cm_content && ($member['user_level'] >= $board['bo_comment_level']))
{
    $wr = g5_get_write($write_table, $wr_id);
    // 원글이 존재한다면
    if ($wr['wr_id'])
    {
        $user_id = $member['user_id'];
        $user_name = addslashes(g5_clean_xss_tags($board['bo_use_name'] ? $member['user_name'] : $member['user_display_name']));
        $user_pass = $member['user_pass'];
        $user_email = addslashes($member['user_email']);
        
        $cm_num = g5_get_next_num( $g5['comment_table'], $wr_id, 'comment' );
        
        $cm_option = isset($_REQUEST['cm_option']) ? sanitize_text_field($_REQUEST['cm_option']) : '';

        $cm_data = array(
                'wr_id' => $wr_id,
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

        // insert
        $result = $wpdb->insert( $g5['comment_table'], $cm_data );
        $comment_id = $wpdb->insert_id;
        
        if( $result !== false ){
            // 원글에 코멘트수 증가
            $result = $wpdb->query(
                $wpdb->prepare(" update $write_table set wr_comment = wr_comment + 1 where wr_id = %d ", $wr_id)
                );

            // 코멘트 1 증가
            $result = $wpdb->query(
                $wpdb->prepare(" update {$g5['board_table']}  set bo_count_comment = bo_count_comment + 1 where bo_table = '%s' ", $bo_table)
                );

            // 포인트 부여
            g5_insert_point($member['user_id'], $board['bo_comment_point'], "{$board['bo_subject']} {$wr_id}-{$comment_id} 코멘트쓰기", $bo_table, $comment_id, '코멘트');
        }
    }
}

$i_data = array(
    'user_id' => $member['user_id'],
    'bo_table' => $bo_table,
    'wr_id' => $wr_id,
    'ms_datetime' => G5_TIME_YMDHIS
);
$formats = array(
                '%s',
                '%s',
                '%d',
                '%s'
            );
// insert
$result = $wpdb->insert( $g5['scrap_table'], $i_data, $formats);

if ( $result !== 'false') {
    g5_delete_cache_latest($bo_table);
}

echo <<<HEREDOC
<script>
    if (confirm('이 글을 스크랩 하였습니다.\\n\\n지금 스크랩을 확인하시겠습니까?'))
        document.location.href = '$scrap_href';
    else
        window.close();
</script>
<noscript>
<p>이 글을 스크랩 하였습니다.</p>
<a href="$scrap_href">스크랩 확인하기</a>
</noscript>
HEREDOC;

exit;
?>