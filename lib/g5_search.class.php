<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

Class G5_Board_Search{
    public function __construct() {
        add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
    }

    public function pre_get_posts($q){
		if ( ! $q->is_main_query() ) {  //메인쿼리가 아니면
			return;
		}

        global $wp_rewrite, $gnupress;

        if( isset($q->query_vars['name']) && $q->query_vars['name'] == G5_NAME && !$q->post_count && isset($q->query_vars['page']) ){
            $wr_id = preg_replace('/\D/', '', $q->query_vars['page']);
            $gnupress->board_redirect($wr_id);
        }

        return $q;
    }
}

new G5_Board_Search();
?>