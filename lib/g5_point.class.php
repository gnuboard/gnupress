<?php

if ( ! class_exists( 'G5_point' ) ) :

class G5_point {

    protected $v_extract = array();

    public $request_action;

    public $errors = array();

    private $g5 = array();
    private $configs = array();
    private $member = array();
    private $attr = array();
    private $qstr = array();

    private $is_load_script = false;

    private $is_admin;
    private $is_member;
    private $is_guest;
    private $skin_path;
    private $skin_url;

    private $cache = array();

    public function __construct( $attr='' ) {

        if( $attr ){
            $this->attr = wp_parse_args( $attr );
        }
        $this->set_config();

        $this->skin_path = G5_DIR_PATH.'skin/member/basic';
        $this->skin_url = G5_DIR_URL.'skin/member/basic';

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

        $this->action_process( $this->request_action );
    }

    public function action_process( $action ){

        $action_arr = array('download', 'delete', 'delete_all', 'write_update', 'write_comment_update');

        if( in_array( $action, $action_arr ) ){
            add_action( 'template_redirect', array( $this, 'header_process' ) );
        }
    }

    public function header_script($attr){
        if( isset($attr['page_mode']) && !empty($attr['page_mode']) ){
            wp_enqueue_style ( 'g5-'.$attr['page_mode'].'-style' , $this->skin_url.'/style.css', '', G5_VERSION );
            $this->is_load_script = true;
            do_action( 'g5_'.$attr['page_mode'].'_style_script' );
        }
    }

	public function shortcode() {
        
		$attr = $this->attr;

        if( isset($attr['page_mode']) ){
            $this->request_action = $attr['page_mode'];     //point
        }

		return $this->point_view();
	}

    public function g5_global_value(){
        $arr = array(
            'qstr' => $this->qstr,
            'wr_id' => isset($_REQUEST['wr_id']) ? (int)$_REQUEST['wr_id'] : 0 ,
            'cm_id' => isset($_REQUEST['cm_id']) ? (int) $_REQUEST['cm_id'] : 0,
            'page' => isset($_REQUEST['page']) ? (int) $_REQUEST['page'] : 0,
            'config' => $this->config,
            'member' => $this->member,
            'is_admin' => $this->is_admin,
            'is_guest' => $this->is_guest,
			'is_member' => $this->is_member,
            'g5' => $this->g5,
            'default_href' => apply_filters('g5_view_default_href' , get_permalink())
            );

        if( !$arr['page'] ){
            $arr['page'] = ( get_query_var( 'page' ) ) ? get_query_var( 'page' ) : 1;
        }

        $this->v_extract = $arr;

        return $this->v_extract;
    }

    public function header_process(){
        global $wpdb, $post;

        do_action('g5_header_process', $this->request_action, $this );

        switch( $this->request_action ){
            
            case 'write_comment_update' :
            case 'write_update' :

                if ( empty( $action ) )
                    $action = $this->request_action;

                $get_array = array('w', 'secret');
                foreach( $get_array as $v ){
                    $$v = isset($_REQUEST[$v]) ? $_REQUEST[$v] : '';
                }
                break;
            default :
                break;
        }
        
        if( $this->request_action ){
            extract( $this->g5_global_value() );
            include_once( G5_DIR_PATH.'bbs/'.$this->request_action.'.php' );
            exit;
        }
    }

    public function memeber_view ( $action = '' ){
        global $wpdb, $post;

        add_action('g5_error_display' , array( & $this, 'g5_error_display'));

    }

    public function point_view( $action = '' ) {
        global $wpdb, $post;
        
        add_action('g5_error_display' , array( & $this, 'g5_error_display'));

        $check_key_array = apply_filters('g5_board_view_request_check', array('w', 'sop', 'stx', 'sca', 'sst', 'sca', 'sfl', 'spt', 'sod', 'sw', 'board_page_id', 'tag') );
        
        $g5_param_array = array();

        foreach( $check_key_array as &$v ){
            $g5_param_array[$v] = isset($_REQUEST[$v]) ? g5_request_check($_REQUEST[$v]) : '';
        }

        $g5_param_array = wp_parse_args( $this->g5_global_value(), $g5_param_array );
        
        extract( $g5_param_array );

        unset( $check_key_array );

        $member_skin_path = $this->skin_path;

		if ( empty( $action ) )
			$action = $this->request_action;

        ob_start();
        
        $skins = array();
        
        //css 또는 js 파일을 불러오지 않았으면 footer 영역에 불러온다.
        //if( !$this->is_load_script ) $this->g5_style_script();

        switch ( $action ) {
            case 'point': // 포인트 페이지
            case 'list': // 리스트 페이지
            default :
                include_once( G5_DIR_PATH.'bbs/point.php' );
                break;
        }

        $output = ob_get_contents();
		ob_end_clean();
        
        do_action('g5_error_display');

        return apply_filters_ref_array( 'g5_skin_view', array( $output, $action, &$this ) );
    }
    
    public function g5_error_display(){
        if( count($this->errors) ){
            g5_pre( $this->errors );
        }
    }
}

endif;

?>