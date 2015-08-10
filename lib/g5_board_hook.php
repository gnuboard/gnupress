<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if( ! function_exists('wp_super_cache_clear_g5') ){
    add_action('write_update_move_url', 'wp_super_cache_clear_g5');  //글 업데이트 hook
    add_action('write_comment_update_move_url', 'wp_super_cache_clear_g5');  //코멘트 업데이트 hook
    add_action('g5_move_update', 'wp_super_cache_clear_g5');  //글 복사, 이동 hook
    add_action('g5_document_delete', 'wp_super_cache_clear_g5');  //글 삭제 hook
    add_action('g5_document_all_delete', 'wp_super_cache_clear_g5');  //글 여러개 삭제 hook
    add_action('g5_comment_delete', 'wp_super_cache_clear_g5');  //코멘트 여러개 삭제 hook

    function wp_super_cache_clear_g5($url=''){
        global $cache_path, $post;
        if( function_exists('wp_cache_post_change') && isset($post->ID) ){
            wp_cache_post_change($post->ID);
        }
        return $url;
    }
}
?>