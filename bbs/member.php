<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

add_filter('show_admin_bar', '__return_false');
//show_admin_bar(false);

add_action('wp_enqueue_scripts', 'g5_new_style_script', 99);
add_filter('wp_head','g5_remove_admin_bar_style', 99);

add_action( 'wp_head', 'wp_no_robots' );

echo g5_new_html_header($page_mode);

echo do_shortcode('['.G5_NAME.' page_mode='.$action.']');

echo g5_new_html_footer($page_mode);
exit;
?>