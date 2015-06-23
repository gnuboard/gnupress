<?php

if ( ! class_exists( 'G5_scrap' ) ) :

class G5_scrap extends G5_common {

    private $gaction;
    private $ms_id;

    public $current_url;

    public function __construct( $attr='' ) {

        parent::__construct( $attr );

        $this->skin_path = G5_DIR_PATH.'skin/member/basic';
        $this->skin_url = G5_DIR_URL.'skin/member/basic';

        $this->gaction = isset($_REQUEST['gaction']) ? sanitize_key($_REQUEST['gaction']) : '';
        $this->ms_id = isset($_REQUEST['ms_id']) ? (int) $_REQUEST['ms_id'] : 0;
        $this->current_url = home_url(add_query_arg(array()));

        $re_chk_array = array('scrap_popin_update', 'scrap_delete');

        if( $this->gaction && in_array($this->gaction, $re_chk_array) ){
            $this->header_process();
        }

        add_filter('g5_get_global_value', array( $this, 'extract_add' ) );
    }

    public function extract_add($v_extract){
        $v_extract['current_url'] = $this->current_url;
        return $v_extract;
    }


    public function header_process(){
        global $wpdb, $post;

        do_action('g5_head_scrap_process', $this->request_action, $this->gaction, $this );
        
        extract( $this->g5_global_value() );

        switch( $this->gaction ){
            
            case 'scrap_popin_update' :
                include_once( G5_DIR_PATH.'bbs/'.$this->gaction.'.php' );
                break;
            case 'scrap_delete' :
                if ( !isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'g5_scrap_delete' ) ){
                    return;
                }
                if (!$is_member)
                    g5_alert('회원만 이용하실 수 있습니다.');

                $sql = " delete from {$g5['scrap_table']} where user_id = '{$member['user_id']}' and ms_id = '{$this->ms_id}' ";
                $result = $wpdb->query(
                        $wpdb->prepare(" delete from {$g5['scrap_table']} where user_id = '%s' and ms_id = %d ", $member['user_id'], $this->ms_id)
                    );
                
                $url = add_query_arg(array('gaction'=>false, 'nonce'=>false, 'page'=>$page), $this->current_url);

                g5_goto_url($url);
                exit;

            default :
                break;
        }
    }

	public function shortcode() {
        
        if( isset($this->attr['page_mode']) ){
            $this->request_action = $this->attr['page_mode'];     //point
        }

		return $this->scrap_view();
	}

    public function scrap_view( $action = '' ) {
        global $wpdb, $post, $gnupress;
        
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
            case 'scrap_popin': // 스크랩팝업 페이지
                include_once( G5_DIR_PATH.'bbs/scrap_popin.php' );
                break;
            case 'scrap' : //스크랩 list
            default :
                include_once( G5_DIR_PATH.'bbs/scrap.php' );
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