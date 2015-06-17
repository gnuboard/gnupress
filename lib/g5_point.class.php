<?php

if ( ! class_exists( 'G5_point' ) ) :

class G5_point extends G5_common {

    public function __construct( $attr='' ) {

        parent::__construct( $attr );

        $this->skin_path = G5_DIR_PATH.'skin/member/basic';
        $this->skin_url = G5_DIR_URL.'skin/member/basic';
    }

	public function shortcode() {
        
		$attr = $this->attr;

        if( isset($attr['page_mode']) ){
            $this->request_action = $attr['page_mode'];     //point
        }

		return $this->point_view();
	}

    public function memeber_view ( $action = '' ){
        global $wpdb, $post;

        add_action('g5_error_display' , array( & $this, 'g5_error_display'));

    }

    public function point_view( $action = '' ) {
        global $wpdb, $post;
        
        add_action('g5_error_display' , array( & $this, 'g5_error_display'));

        $g5_param_array = wp_parse_args( $this->g5_global_value(), g5_request_param_keys() );
        
        extract( $g5_param_array );

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