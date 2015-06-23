<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$refer_page = isset($_REQUEST['ms_url']) ? esc_url_raw(urldecode($_REQUEST['ms_url'])) : wp_get_referer();

$new_open_url = apply_filters('g5_new_open_url', $gnupress->new_url);

$scrap_href = add_query_arg( array('action'=>'scrap', 'gaction'=>false) );

if ($is_guest) {

    $href = wp_login_url( $refer_page );

    $href2 = str_replace('&amp;', '&', $href);
    echo <<<HEREDOC
    <script>
        alert('회원만 접근 가능합니다.');
        opener.location.href = '$href2';
        window.close();
    </script>
    <noscript>
    <p>회원만 접근 가능합니다.</p>
    <a href="$href">로그인하기</a>
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

    echo <<<HEREDOC
    <script>
    if (confirm('이미 스크랩하신 글 입니다.\\n\\n지금 스크랩을 확인하시겠습니까?'))
        document.location.href = '$scrap_href';
    else
        window.close();
    </script>
    <noscript>
    <p>이미 스크랩하신 글 입니다.</p>
    <a href="{$esc_url($scrap_href)}">스크랩 확인하기</a>
    <a href="{$esc_url($refer_page)}">돌아가기</a>
    </noscript>
HEREDOC;
    exit;
}

include_once($member_skin_path.'/scrap_popin.skin.php');
?>