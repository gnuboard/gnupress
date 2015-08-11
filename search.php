<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if ( !class_exists( 'G5_Add_Search_Data' ) ) :

if( !function_exists('g5_pre_get_posts') ){
    function g5_pre_get_posts($q){
        global $gnupress;
        
        //워드프레스 전체 검색시 게시판 내용을 포함 설정이 되어 있다면
        if( isset($gnupress->config['cf_use_search_include']) && $gnupress->config['cf_use_search_include'] && $q->is_main_query() && $q->is_search() && !is_admin() )
        {
            $c = new G5_Add_Search_Data;
            $c->init($q);
        }
    }
}
add_action( 'pre_get_posts', 'g5_pre_get_posts' );

/**
 * class WPSE_Add_Search_Data
 */
class G5_Add_Search_Data
{
    protected $search_where = '';
    protected $limits    = '';
    protected $sublimit  = 1000;
    protected $sub_sql = array();
    protected $args    = array();

    public function init($q)
    {
        add_filter( 'posts_search',  array( $this, 'posts_search'  ), PHP_INT_MAX );
        add_filter( 'posts_fields',  array( $this, 'posts_fields'  ), PHP_INT_MAX );
        add_filter( 'posts_clauses',  array( $this, 'posts_clauses' ), PHP_INT_MAX  );
        add_filter( 'posts_request', array( $this, 'posts_request' ), PHP_INT_MAX, 2 );
        add_filter( 'posts_orderby', '__return_null' );
    }

    public function posts_search( $search )
    {
        $this->search_where = $search;
        return $search;
    }

    public function posts_fields( $fields )
    {
        remove_filter( current_filter(), array( $this, __FUNCTION__ ), PHP_INT_MAX  );
        $search_fileds = apply_filters( 'g5_search_wp_fileds', array(
                            'ID', 
                            'post_author', 
                            'post_date', 
                            'post_date_gmt',
                            'post_content',
                            'post_title',
                            'post_excerpt',
                            'post_status',
                            'comment_status',
                            'ping_status',
                            'post_password',
                            'post_name',
                            'to_ping',
                            'pinged',
                            'post_modified',
                            'post_modified_gmt',
                            'post_content_filtered',
                            'post_parent',
                            'guid',
                            'menu_order',
                            'post_type',
                            'post_mime_type',
                            'comment_count'
                        ));
        return implode(',' , $search_fileds);
    }

    public function posts_clauses( $clauses )
    {
        remove_filter( current_filter(), array( $this, __FUNCTION__ ), PHP_INT_MAX  );
        $this->limits = $clauses['limits'];
        return $clauses;
    }

    protected function modify( $request )
    {
        $request = str_ireplace( 'SQL_CALC_FOUND_ROWS', '', $request );

        if( $this->sublimit > 0 )
            return str_ireplace( $this->limits, sprintf( 'LIMIT %d', $this->sublimit ), $request );
        else
            return $request;
    }

    public function posts_request( $request, $query )
    {
        remove_filter( current_filter(), array( $this, __FUNCTION__ ), PHP_INT_MAX  );

        global $wpdb, $gnupress, $wp_post_types;
        
        $this->args = $query->query_vars;
        
        $g5 = $gnupress->g5;

        if( !($query->is_main_query() && is_search()) ) {
            return $request;
        }
        
        if( !isset($query->query_vars['post_type']) || $query->query_vars['post_type'] != 'any' ){
            return $request;
        }

		register_post_type( G5_NAME , array(
			'labels' => array('name'=>'GNUpress'),
			'show_ui'=> true,
			'rewrite' => array( 'slug' => G5_NAME ),
			'query_var' => 'g5_bbs_redirect',
			'public'=> true
		));

        $search_where = str_ireplace(
            array( $wpdb->posts.".post_title", $wpdb->posts.".post_content", "AND (".$wpdb->posts.".post_password = '')" ),
            array( $g5['write_table'].".wr_subject", $g5['write_table'].".wr_content", ""),
            $this->search_where 
        );

        $search_where = $search_where." and ".$g5['write_table'].".wr_option not like '%secret%'";
        $search_where = apply_filters( 'g5_search_where', $search_where, $this->search_where );

        $select_fields = apply_filters( 'g5_search_as_fileds', array(
                            "wr_id as ID", 
                            "user_id as post_author", 
                            "wr_datetime as post_date", 
                            "wr_datetime as post_date_gmt",
                            "wr_content as post_content",
                            "wr_subject as post_title",
                            "'' as post_excerpt",
                            "'publish' as post_status",
                            "'open' as comment_status",
                            "'open' as ping_status",
                            "'' as post_password",
                            "wr_id as post_name",
                            "'' as to_ping",
                            "'' as pinged",
                            "wr_last as post_modified",
                            "wr_last as post_modified_gmt",
                            "'' as post_content_filtered",
                            "wr_parent as post_parent",
                            "'' as guid",
                            "0 as menu_order",
                            "'".G5_NAME."' as post_type",
                            "'' as post_mime_type",
                            "wr_comment as comment_count"
                        ));
        $sql = "SELECT ".implode(',' , $select_fields )." FROM `{$g5['write_table']}` where 1=1 {$search_where}";

        $this->sub_sql[] = $this->modify( $request );
        $this->sub_sql[] = apply_filters( 'g5_search_all_sql', $sql );

        // Append the external data with custom order:
        $orderby = 'ORDER BY post_date DESC';

        if ( count( $this->sub_sql ) > 1 )
        {
            $s = '(' . join( ') UNION (', $this->sub_sql ) . ' ) ';
            
            $paged = $this->args['paged'] ? $this->args['paged'] : 1;

            $return_sql = "SELECT SQL_CALC_FOUND_ROWS * FROM ( $s ) as combined".sprintf(" LIMIT %s,%s",
                $this->args['posts_per_page'] * ( $paged-1 ),
                $this->args['posts_per_page']
            );          
        }

        $return_sql = apply_filters( 'g5_request_sql_return' , $return_sql, $request, $sql, $orderby, $select_fields, $search_where );

        //echo $return_sql;

        return $return_sql;
    }

} // end class

endif;
?>