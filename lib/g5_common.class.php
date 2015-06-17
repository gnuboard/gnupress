<?php

if ( ! class_exists( 'G5_common' ) ) :

class G5_common {

    public $request_action;

    public $errors = array();
    public $qstr = array();

    protected $v_extract = array();
    protected $g5 = array();
    protected $member = array();
    protected $attr = array();
    protected $write = array();
    protected $board = array();

    protected $bo_table;
    protected $is_load_script = false;

    protected $is_admin = false;
    protected $is_member = false;
    protected $is_guest = false;
    protected $skin_path;
    protected $skin_url;

    protected $cache = array();

    public function __construct( $attr='' ) {

        if( $attr ){
            $this->attr = wp_parse_args( $attr );
        }

        $this->set_config();
        
        $this->get_board($attr);
        $this->qstr = g5_get_qstr();
    }

    public function set_config(){
        global $gnupress;

        $this->g5 = $gnupress->g5;
        $this->config = $gnupress->config;
        $this->is_admin = current_user_can( 'administrator' ) ? 'super' : '';
        $this->member = g5_get_member( get_current_user_id() );

        if( $this->member['user_id'] ){
            $this->is_member = true;
        } else {
            $this->is_guest = true;
        }

        $this->request_action = isset( $_REQUEST['action'] ) ? sanitize_key( $_REQUEST['action'] ) : '';
    }

    public function header_script($attr){

        if( $this->is_load_script ) return;

        $load_common_js = array();
        $load_common_js[] = array('handle'=>'g5-common-js', 'src'=>G5_DIR_URL.'js/common.js', 'deps'=>'', 'ver'=>G5_VERSION);

        wp_enqueue_script( 'jquery' );

        $load_common_js = apply_filters( 'g5_load_common_js', $load_common_js, $this );
        if( count($load_common_js) ){
            foreach( $load_common_js as $js){
                wp_enqueue_script( $js['handle'], $js['src'], $js['deps'], $js['ver'] );
            }
        }

        if( isset($attr['page_mode']) && !empty($attr['page_mode']) ){
            wp_enqueue_style ( 'g5-'.$attr['page_mode'].'-style' , $this->skin_url.'/style.css', '', G5_VERSION );
            do_action( 'g5_'.$attr['page_mode'].'_style_script' );
        }

        do_action( 'g5_common_style_script' );

        $this->is_load_script = true;
    }

    public function get_write( $wr_id , $bo_table='' ){
        global $wpdb, $gnupress;
        
        $g5 = $gnupress->g5;
        
        $add = $bo_table ? " and bo_table = '$bo_table'" : '';

        $this->write = wp_cache_get( 'g5_'.$g5['write_table'].'_'.$wr_id );
        
        if( false === $this->write ){
            $sql = $wpdb->prepare("select * from {$g5['write_table']} where wr_id = %d $add ", $wr_id);
            $this->write = $wpdb->get_row( $sql, ARRAY_A );
        }

        /* 나중에 이 코드를 지워야 함 ㅠㅠ */
        if( ! isset($this->write['wr_page_id']) ){

            $sql = "ALTER TABLE `{$g5['write_table']}` ADD `wr_page_id` BIGINT(20) NOT NULL default '0'";
            $result = $wpdb->query( $sql );

            // db insert에 실패한 경우
            if ( $result === false ){
                g5_show_db_error();
            }
        }
        /* 여기까지 나중에 이 코드를 지워야 함 ㅠㅠ */

        return $this->write;
    }

    public function get_board( $attr ){
        global $wpdb;
        
        $g5 = $this->g5;

        if( isset($attr['bo_table']) ){ // shortcode에서 bo_table을 지정하였다면...
            $this->bo_table = $bo_table = $attr['bo_table'];    
        } else {
            $this->bo_table = $bo_table = isset($_REQUEST['bo_table']) ? sanitize_key( $_REQUEST['bo_table'] ) : false;
        }

        if( $bo_table ){
            $this->board = g5_get_board_config( $bo_table, $g5['board_table'] );
        }
    }

    public function g5_global_value(){
        global $gnupress;

        $arr = apply_filters('g5_get_global_value', array(
            'qstr' => $this->qstr,
            'config' => $gnupress->config,
            'g5' => $gnupress->g5,
            'wr_id' => isset($_REQUEST['wr_id']) ? (int)$_REQUEST['wr_id'] : 0 ,
            'cm_id' => isset($_REQUEST['cm_id']) ? (int) $_REQUEST['cm_id'] : 0,
            'page' => isset($_REQUEST['page']) ? (int) $_REQUEST['page'] : 0,
            'board' => $this->board,
            'member' => $this->member,
            'is_admin' => $this->is_admin,
            'is_guest' => $this->is_guest,
			'is_member' => $this->is_member,
            'write' => array(),
            'write_table' => $this->g5['write_table'],
            'bo_table' => $this->bo_table,
            'default_href' => apply_filters('g5_view_default_href' , get_permalink()),
            'current_url' => add_query_arg(array())
            )
        );

        $gnupress->wr_id = $arr['wr_id'];

        if( !$arr['page'] ){
            $arr['page'] = ( get_query_var( 'page' ) ) ? get_query_var( 'page' ) : 1;
        }

        if( $arr['wr_id'] > 0 ){
            $arr['write'] = $this->get_write($arr['wr_id'], $this->bo_table);
        }

        $this->v_extract = $arr;

        return $this->v_extract;
    }

}

endif;

?>