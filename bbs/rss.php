<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

/**
 * 워드프레스 함수 wxr_cdata 참고
 *
 */
if ( ! function_exists('g5_wxr_cdata'))
{
    function g5_wxr_cdata( $str ) {
        if ( seems_utf8( $str ) == false )
            $str = utf8_encode( $str );

        $str = '<![CDATA[' . str_replace( ']]>', ']]]]><![CDATA[>', $str ) . ']]>';

        return $str;
    }
}

$board_title = g5_cut_str($board['bo_subject'], 255);
$lines = $board['bo_page_rows'];

// 비회원 읽기가 가능한 게시판만 RSS 지원

if ((int) $board['bo_read_level'] > 0 ) {
    echo '비회원 읽기가 가능한 게시판만 RSS 지원합니다.';
    exit;
}

// RSS 사용 체크
if (!$board['bo_use_rss_view']) {
    echo 'RSS 보기가 금지되어 있습니다.';
    exit;
}

header('Content-type: text/xml');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

echo '<?phpxml version="1.0" encoding="utf-8" ?>'."\n";
?>
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/">
<channel>
<title><?php echo esc_html(bloginfo_rss( 'name' ).' &gt; '.$board_title) ?></title>
<link><?php echo esc_url($default_href) ?></link>
<description><?php bloginfo_rss( 'description' ); ?></description>
<language><?php bloginfo_rss( 'language' ); ?></language>

<?php
$sql = $wpdb->prepare(" select wr_id, wr_subject, wr_content, user_display_name, wr_datetime, wr_option
            from {$g5['write_table']}
            where bo_table = '%s'
            and wr_option not like '%%secret%%'
            order by wr_num, wr_parent limit 0, %d ", $bo_table, $lines );

$rows = $wpdb->get_results( $sql, ARRAY_A );

foreach( $rows as $row ){
    $file = '';
    if (strstr($row['wr_option'], 'html'))
        $html = 1;
    else 
        $html = 0;
?>

<item>
<title><?php echo apply_filters( 'the_title_rss', $row['wr_subject']) ?></title>
<link><?php echo esc_url( add_query_arg( array('wr_id'=>$row['wr_id']), $default_href) ); ?></link>
<description><![CDATA[<?php echo $file ?><?php echo g5_conv_content($row['wr_content'], $html) ?>]]></description>
<dc:creator><?php echo g5_wxr_cdata($row['user_display_name']) ?></dc:creator>
<?php
$date = $row['wr_datetime'];
// rss 리더 스킨으로 호출하면 날짜가 제대로 표시되지 않음
//$date = substr($date,0,10) . "T" . substr($date,11,8) . "+09:00";
$date = date('r', strtotime($date));
?>
<dc:date><?php echo $date ?></dc:date>
</item>

<?php
}

echo '</channel>'."\n";
echo '</rss>'."\n";
exit;

?>