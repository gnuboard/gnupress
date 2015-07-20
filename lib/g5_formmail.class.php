<?php

if ( ! class_exists( 'G5_formmail' ) ) :

class G5_formmail extends G5_common {

    public $gaction;
    public $html = null;

    public function __construct( $attr='' ) {

        parent::__construct( $attr );

        $this->skin_path = G5_DIR_PATH.'skin/member/basic';
        $this->skin_url = G5_DIR_URL.'skin/member/basic';
        
        $this->gaction = isset($_REQUEST['gaction']) ? sanitize_key($_REQUEST['gaction']) : '';
        $re_chk_array = array('form_mail_update');

        if( $this->gaction && in_array($this->gaction, $re_chk_array) ){
            $this->header_process();
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

        //중복으로 저장하는것을 막는다.
        if( $this->html === null ){
		    $this->html = $this->formmail_view();
        }

        return $this->html;
	}

    public function formmail_view( $action = '' ) {
        global $wpdb, $post;
        
        add_action('g5_error_display' , array( & $this, 'g5_error_display'));

        $g5_param_array = wp_parse_args( $this->g5_global_value(), g5_request_param_keys() );
        
        extract( $g5_param_array );

        $user_name = isset($_REQUEST['user_name']) ?  sanitize_text_field($_REQUEST['user_name']) : '';

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