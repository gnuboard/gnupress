<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$refer_page = isset($_REQUEST['ms_url']) ? esc_url_raw(urldecode($_REQUEST['ms_url'])) : wp_get_referer();

$new_open_url = apply_filters('g5_new_open_url', $gnupress->new_url);

$scrap_href = add_query_arg( array('action'=>'scrap', 'gaction'=>false) );

if ($is_guest) {

    $href = wp_login_url( $refer_page );

    $href2 = str_replace('&amp;', '&', $href);
    $msg1 = __('Only registered members can access.', 'gnupress');
    echo <<<HEREDOC
    <script>
        alert("$msg1");
        opener.location.href = '$href2';
        window.close();
    </script>
    <noscript>
    <p>$msg1</p>
    <a href="$href">Login</a>
    </noscript>
HEREDOC;
    exit;
}

echo <<<HEREDOC
<script>
    if (window.name != 'win_scrap') {
        //alert('올바른 방법으로 사용해 주십시오.');
        //window.close();
    }
</script>
HEREDOC;

$sql = $wpdb->prepare(" select count(*) as cnt from {$g5['scrap_table']}
            where user_id = '%s'
            and bo_table = '%s'
            and wr_id = %d ", $member['user_id'], $bo_table, $wr_id);

$row_cnt = $wpdb->get_var($sql);

if ($row_cnt) {
    $esc_url = 'esc_url';

    $msg1 = __('Your article is already scrap.\\n\\nDo you want to scrap it now?', G5_NAME);
    echo <<<HEREDOC
    <script>
    if (confirm('$msg1'))
        document.location.href = '$scrap_href';
    else
        window.close();
    </script>
    <noscript>
    <p>Your article is already scrap.</p>
    <a href="{$esc_url($scrap_href)}">Confirm scrap</a>
    <a href="{$esc_url($refer_page)}">Go back</a>
    </noscript>
HEREDOC;
    exit;
}

include_once($member_skin_path.'/scrap_popin.skin.php');
?>