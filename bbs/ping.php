<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if (!$write)
    wp_die(__('No posts', G5_NAME));   //게시글이 없습니다.

if ($board['bo_read_level'])
    wp_die(__('Guests can read the board that it supports Syndication.', G5_NAME));   //비회원 읽기가 가능한 게시판만 신디케이션을 지원합니다.

if (strstr($write['wr_option'], 'secret'))
    wp_die(__('Secret does not support syndication.', G5_NAME)); //비밀글은 신디케이션을 지원하지 않습니다.

if (preg_match('#^('.$config['cf_syndi_except'].')$#', $bo_table))
    wp_die(__('The board is excluded from the syndication.', G5_NAME));  //신디케이션에서 제외된 게시판입니다.

$title        = htmlspecialchars($write['wr_subject']);
$author       = htmlspecialchars($write['user_display_name']);
$published    = date('Y-m-d\TH:i:s\+09:00', strtotime($write['wr_datetime']));
$updated      = $published;
$link_href    = $default_href;
$id           = esc_url( add_query_arg( array('wr_id'=>$wr_id) , $link_href) );
$link_title   = htmlspecialchars($board['bo_subject']);
$feed_updated = date('Y-m-d\TH:i:s\+09:00', G5_SERVER_TIME);

$find         = array('&amp;', '&nbsp;'); # 찾아서
$replace      = array('&', ' '); # 바꾼다

$content      = str_replace( $find, $replace, $write['wr_content'] );
$summary      = str_replace( $find, $replace, strip_tags($write['wr_content']) );

Header("Content-type: text/xml");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
echo "<feed xmlns=\"http://webmastertool.naver.com\">\n";
echo "<id>" . home_url() . "</id>\n";
echo "<title>naver syndication feed document</title>\n";
echo "<author>\n";
    echo "<name>webmaster</name>\n";
echo "</author>\n";

echo "<updated>{$feed_updated}</updated>\n";

echo "<link rel=\"site\" href=\"" . home_url() . "\" title=\"".bloginfo_rss( 'name' )."\" />\n";
echo "<entry>\n";
    echo "<id>{$id}</id>\n";
    echo "<title><![CDATA[{$title}]]></title>\n";
    echo "<author>\n";
        echo "<name>{$author}</name>\n";
    echo "</author>\n";
    echo "<updated>{$updated}</updated>\n";
    echo "<published>{$published}</published>\n";
    echo "<link rel=\"via\" href=\"{$link_href}\" title=\"{$link_title}\" />\n";
    echo "<link rel=\"mobile\" href=\"{$id}\" />\n";
    echo "<content type=\"html\"><![CDATA[{$content}]]></content>\n";
    echo "<summary type=\"text\"><![CDATA[{$summary}]]></summary>\n";
    echo "<category term=\"{$bo_table}\" label=\"{$link_title}\" />\n";
echo "</entry>\n";
echo "</feed>";
die();
?>