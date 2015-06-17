<?php

if ( ! class_exists( 'G5_formmail' ) ) :

class G5_formmail extends G5_common {

    public $gaction;

    public function __construct( $attr='' ) {

        parent::__construct( $attr );

        $this->skin_path = G5_DIR_PATH.'skin/member/basic';
        $this->skin_url = G5_DIR_URL.'skin/member/basic';
        
        $this->gaction = isset($_REQUEST['gaction']) ? $_REQUEST['gaction'] : '';
        $re_chk_array = array('form_mail_update');

        if( $this->gaction && in_array($this->gaction, $re_chk_array) ){
            add_action( 'wp_enqueue_scripts', array( $this, 'header_process' ) );
        }
    }

    public function header_process(){
        global $wpdb, $post;
        
        extract( $this->g5_global_value() );

        switch( $this->gaction ){
            case 'form_mail_update' :
            default :
                include_once( G5_DIR_PATH.'bbs/formmail_send.php' );
                break;
        }
    }

	public function shortcode() {
        
        if( isset($this->attr['page_mode']) ){
            $this->request_action = $this->attr['page_mode'];     //formmail
        }

		return $this->formmail_view();
	}

    public function formmail_view( $action = '' ) {
        global $wpdb, $post;
        
        add_action('g5_error_display' , array( & $this, 'g5_error_display'));

        $check_key_array = apply_filters('g5_formmail_request_check', array('w', 'sop', 'stx', 'sca', 'sst', 'sca', 'sfl', 'spt', 'sod', 'sw', 'board_page_id', 'tag') );
        
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
            default :
                include_once( G5_DIR_PATH.'bbs/formmail.php' );
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