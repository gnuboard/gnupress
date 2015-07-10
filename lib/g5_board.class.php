<?php

if ( ! class_exists( 'G5_Board' ) ) :

class G5_Board extends G5_common {
    
    private $html = null;

    protected $view = array();
    
    public $is_board_load_script = false;

    public function __construct( $attr='' ) {

        parent::__construct( $attr );

        $this->board_config();

        add_filter( 'g5_member_filters', array( &$this, 'g5_member_filters') , 10, 2 );

        add_action('wp_enqueue_scripts', array( &$this, 'g5_style_script' ), 99 );
        add_action('g5_error_display', array( &$this, 'error_display_print' ), 10 ) ;
        
        add_filter( 'g5_get_list_array', array( &$this, 'bbs_list_sort' ), 10, 2 ) ; 
    }

    public function g5_style_script(){

        if( $this->is_board_load_script || ! isset($this->board['bo_table']) ) return;

        $board_skin_url = G5_DIR_URL.'skin/board/'.$this->board['bo_skin'];
        wp_enqueue_style ( 'g5-board-'.$this->board['bo_table'].'-style' , $board_skin_url.'/style.css', '', G5_VERSION );

        $load_skin_js = array();
        $load_skin_js[] = array('handle'=>'g5-common-js', 'src'=>G5_DIR_URL.'js/common.js', 'deps'=>'', 'ver'=>G5_VERSION);
        
        if( $this->request_action == 'write' && $this->board['bo_use_tag'] ){
            wp_enqueue_style ( 'jquery-ui-css', '//ajax.googleapis.com/ajax/libs/jqueryui/1/themes/flick/jquery-ui.css', '', G5_VERSION );
            wp_enqueue_style ( 'jquery-tagit-css' , $board_skin_url.'/js/jquery.tagit.css', '', G5_VERSION );
            wp_enqueue_script( 'jquery-ui-core', site_url(  '/wp-includes/js/jquery/ui/core.min.js' ), array('jquery') );
            wp_enqueue_script( 'jquery-ui-widget', site_url(  '/wp-includes/js/jquery/ui/widget.min.js' ), array('jquery-ui-core') );
            wp_enqueue_script( 'jquery-ui-autocomplete', site_url(  '/wp-includes/js/jquery/ui/autocomplete.min.js' ), array('jquery-ui-widget') );

            $load_skin_js[] = array(
                    'handle'=>'g5-board-'.$this->board['bo_table'].'_tagjs',
                    'src'=>$board_skin_url.'/js/tag-it.js',
                    'deps'=>array('jquery-ui-autocomplete'),
                    'ver'=>G5_VERSION
                );
        }
        
        $load_skin_js = apply_filters( 'g5_load_skin_js', $load_skin_js, $this );
        if( count($load_skin_js) ){
            foreach( $load_skin_js as $js){
                wp_enqueue_script( $js['handle'], $js['src'], $js['deps'], $js['ver'] );
            }
        }

        $this->is_board_load_script = true;

        do_action( 'g5_load_style_script', 'g5-board-'.$this->board['bo_table'].'-style' );
    }

    public function g5_redirect($wr_id){
        $write = $this->get_write($arr['wr_id']);
    }

    public function board_config(){

        if ( ! $this->request_action ){
           $wr_id = isset($_REQUEST['wr_id']) ? (int)$_REQUEST['wr_id'] : 0;
           if( $wr_id ){
                $this->request_action = 'view';
           }
        }

        $this->action_process( $this->request_action );
    }

    public function action_process( $action ){

        $action_arr = apply_filters('g5_head_check_action', array('download', 'delete', 'delete_all', 'write_update', 'write_comment_update', 'good', 'nogood', 'move', 'move_update', 'rss', 'ping', 'password_check', 'delete_comment', 'link'));
        
        if( in_array( $action, $action_arr ) ){
            add_action( 'template_redirect', array( $this, 'header_process' ) );
        }
    }

	public function shortcode() {
        
		$attr = $this->attr;

        if( isset($attr['page_mode']) ){
            $this->request_action = $attr['page_mode'];     //게시물 복사 및 이동 및 새창에서 이루어 지는 작업
        }
        
        //중복으로 저장하는것을 막는다.
        if( $this->html === null ){
		    $this->html = $this->board_view();
        }

        return $this->html;
	}

    public function g5_board_value(){

        $this->g5_global_value();

        $arr = array(
            'page' => isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 0,
            'search_tag_str' => isset($_REQUEST['tag']) ? sanitize_text_field($_REQUEST['tag']) : '',
            'search_tag' => array(),
            'board_skin_path' => G5_DIR_PATH.'skin/board/'.$this->board['bo_skin'],
            'board_skin_url' => G5_DIR_URL.'skin/board/'.$this->board['bo_skin']
        );
        
        if( $arr['search_tag_str'] ){
            $arr['search_tag'] = array_unique( preg_split("/[\s,]+/", $arr['search_tag_str']) );
        }

        if( !$arr['page'] ){
            $arr['page'] = ( get_query_var( 'page' ) ) ? get_query_var( 'page' ) : 1;
        }

        $this->v_extract = wp_parse_args($arr, $this->v_extract);

        return $this->v_extract;
    }

    public function header_process(){
        global $wpdb, $post, $gnupress;

        do_action('g5_header_process', $this->request_action, $this );

        $g5_param_array = g5_request_param_keys(array('secret'));
        
        extract( $g5_param_array );

        /*
        $get_array = array('w', 'sw', 'secret');
        foreach( $get_array as $v ){
            $$v = isset($_REQUEST[$v]) ? esc_attr( wp_unslash($_REQUEST[$v]) ) : '';
        }
        */

        switch( $this->request_action ){
            
            case 'write_comment_update' :
            case 'write_update' :
                if ( empty( $action ) )
                    $action = $this->request_action;
                break;

            case 'good' :
            case 'nogood' :
                $this->request_action = 'good';
                break;
            default :
                break;
        }
        
        if( $this->request_action ){
            extract( $this->g5_board_value() );
            $file_path = G5_DIR_PATH.'bbs/'.$this->request_action.'.php';
            if( file_exists($file_path) )
                include_once($file_path);
        }
    }

    public function board_view( $action = '' ) {
        global $wpdb, $post, $gnupress;

        if( ! isset($this->board['bo_table']) ){
            $msg = __('게시판이 존재하지 않습니다.', G5_NAME);

            $this->error_display_print((array) $msg);
            return;
        }
        
        $g5_param_array = g5_request_param_keys();

        if( isset($g5_param_array['gw']) && !empty($g5_param_array['gw']) ){
            $g5_param_array['w'] = $g5_param_array['gw'];
        }

        $g5_param_array = wp_parse_args( $this->g5_board_value(), $g5_param_array );
        
        extract( $g5_param_array );

        $board['board_skin_path'] = $board_skin_path;

		if ( empty( $action ) )
			$action = $this->request_action;

        ob_start();
        
        $skins = array();
        
        //css 또는 js 파일을 불러오지 않았으면 footer 영역에 불러온다.
        if( !$this->is_board_load_script ) $this->g5_style_script();

        //게시판 태그 설정에서 태그가 사용함으로 설정되어 있다면 파일을 불러온다.
        if( $board['bo_use_tag'] ){
            include_once( G5_DIR_PATH.'lib/g5_taxonomy.lib.php' );
        }

        switch ( $action ) {
            case 'write': // 글쓰기 페이지
                if($board['bo_use_dhtml_editor'] && $config['cf_editor']){  //에디터를 사용한다면
                    //워드프레스 에디터를 사용할 경우 처리
                    include_once( G5_DIR_PATH.'plugin/editor/'.$config['cf_editor'].'/editor.lib.php' );
                } else {
                    //textarea를 사용할 경우 처리
                    include_once( G5_DIR_PATH.'lib/editor.lib.php' );
                }
                include_once( G5_DIR_PATH.'bbs/write.php' );
                break;
            case 'password' : // 패스워드 페이지
                include_once( G5_DIR_PATH.'bbs/password.php' );
                break;
            case 'move': // 글 복사 및 삭제 view ( 새창 )
                include_once( G5_DIR_PATH.'bbs/move.php' );
                break;
            case 'move_update': // 글 복사 및 삭제 실행 ( 새창 )
                include_once( G5_DIR_PATH.'bbs/move_update.php' );
                break;
            case 'delete_comment' : // 코멘트 삭제
                include_once( G5_DIR_PATH.'bbs/delete_comment.php' );
                break;
            case 'view': // 글 뷰 페이지
            case 'list': // 리스트 페이지
            default :
                include_once( G5_DIR_PATH.'lib/g5_walker.class.php' );
                include_once( G5_DIR_PATH.'bbs/board.php' );
                break;
        }
        
        do_action('g5_error_display', $this->errors, $this);

        $output = ob_get_contents();
		ob_end_clean();

        return apply_filters_ref_array( 'g5_skin_view', array( $output, $action, &$this ) );
    }

    // 게시물 정보($write_row)를 출력하기 위하여 $list로 가공된 정보를 복사 및 가공
    function get_list($write_row, $board, $skin_url, $subject_len=40, $board_page_url='')
    {
        $g5 = $this->g5;
        $config = $this->config;

        // 배열전체를 복사
        $list = $write_row;
        unset($write_row);

        $board_notice = array_map('trim', explode(',', $board['bo_notice']));
        
        if( $board_page_url ){
            $page_url = $board_page_url;
        } else {
            if( isset($board['page_url']) && !empty($board['page_url']) ){
                $page_url = $board['page_url'];
            } else {
                $page_url = $list['wr_page_id'] ? get_permalink($list['wr_page_id']) : '#';
            }
        }

        $list['is_notice'] = in_array($list['wr_id'], $board_notice);

        if ($subject_len)
            $list['subject'] = g5_conv_subject($list['wr_subject'], $subject_len, '…');
        else
            $list['subject'] = g5_conv_subject($list['wr_subject'], $board['bo_subject_len'], '…');

        // 목록에서 내용 미리보기 사용한 게시판만 내용을 변환함 (속도 향상) : kkal3(커피)님께서 알려주셨습니다.
        if ($board['bo_use_list_content'])
        {
            $html = 0;
            if (strstr($list['wr_option'], 'html1'))
                $html = 1;
            else if (strstr($list['wr_option'], 'html2'))
                $html = 2;

            $list['content'] = g5_conv_content($list['wr_content'], $html);
        }

        $list['comment_cnt'] = '';
        if ($list['wr_comment'])
            $list['comment_cnt'] = "<span class=\"cnt_cmt\">".$list['wr_comment']."</span>";

        // 당일인 경우 시간으로 표시함
        $list['datetime'] = substr($list['wr_datetime'] ,0,10);
        $list['datetime2'] = $list['wr_datetime'];
        if ($list['datetime'] == G5_TIME_YMD)
            $list['datetime2'] = substr($list['datetime2'] ,11,5);
        else
            $list['datetime2'] = substr($list['datetime2'] ,5,5);
        // 4.1
        $list['last'] = substr($list['wr_last'] ,0,10);
        $list['last2'] = $list['wr_last'];
        if ($list['last'] == G5_TIME_YMD)
            $list['last2'] = substr($list['last2'] ,11,5);
        else
            $list['last2'] = substr($list['last2'] ,5,5);

        $tmp_name = g5_get_text(g5_cut_str($list['user_display_name'] , $config['cf_cut_name'])); // 설정된 자리수 만큼만 이름 출력

        if ($board['bo_use_sideview']){
            $list['name'] = g5_get_sideview($list['user_id'], $tmp_name, $list['user_email'] );
        } else {
            $list['name'] = '<span class="'.($list['user_id'] ?'sv_member':'sv_guest').'">'.$tmp_name.'</span>';
        }
        
        $list['icon_reply'] = '';
        if ($list['wr_parent']){
            $depth = isset($list['wr_depth']) ? (int) $list['wr_depth'] * 10 : 10;
            $list['icon_reply'] = '<img src="'.$skin_url.'/img/icon_reply.gif" style="margin-left:'.$depth.'px;" alt="답변글">';
        }

        $list['icon_link'] = '';
        if ( $list['wr_link1'] || $list['wr_link2'] )
            $list['icon_link'] = '<img src="'.$skin_url.'/img/icon_link.gif" alt="관련링크">';

        // 분류명 링크
        $list['ca_name_href'] = add_query_arg( array('sca'=>urlencode($list['ca_name']) ), $page_url );

        $tmp_href = array_merge( $this->qstr, array('wr_id' => $list['wr_id'] ) );
        $list['href'] = add_query_arg( $tmp_href , $page_url );

        $list['comment_href'] = $list['href'];

        $list['icon_new'] = '';
        if ($board['bo_new'] && $list['wr_datetime'] >= date("Y-m-d H:i:s", G5_SERVER_TIME - ($board['bo_new'] * 3600)))
            $list['icon_new'] = '<img src="'.$skin_url.'/img/icon_new.gif" alt="새글">';

        $list['icon_hot'] = '';
        if ($board['bo_hot'] && $list['wr_hit'] >= $board['bo_hot'])
            $list['icon_hot'] = '<img src="'.$skin_url.'/img/icon_hot.gif" alt="인기글">';

        $list['icon_secret'] = '';
        if (strstr($list['wr_option'], 'secret'))
            $list['icon_secret'] = '<img src="'.$skin_url.'/img/icon_secret.gif" alt="비밀글">';

        // 링크
        for ($i=1; $i<=G5_LINK_COUNT; $i++) {
            $list['link'][$i] = g5_set_http(g5_get_text($list["wr_link{$i}"]));

            $list['link_href'][$i] = add_query_arg( array_merge((array) $this->qstr, array('wr_id'=>$list['wr_id'], 'no' => $i, 'action'=>'link' )) , $page_url );
            $list['link_hit'][$i] = (int)$list["wr_link{$i}_hit"];
        }
        
        $list['wr_tag_array'] = array();

        if( $board['bo_use_tag'] && isset($list['wr_tag']) && !empty($list['wr_tag']) ){
            if( !isset($this->cache['wr_tag']) ){
                $this->cache['wr_tag'] = array();
            }

            $term_ids = explode(',', $list['wr_tag']);
            foreach( $term_ids as $term_id ){
                if( empty($term_id) ) continue;

                $term = g5_get_tag_info($term_id, $board['bo_table']);
                if( ! isset($term['slug']) ) continue;
                
                $term['href'] = add_query_arg( array_merge( (array) $this->qstr, array('tag'=>$term['slug'])) , $page_url );
                $list['wr_tag_array'][] = $term;

                if( !in_array($term_id, $this->cache['wr_tag']) ){
                    array_push( $this->cache['wr_tag'], $term_id );
                }
            }
        }
        
        $list['file'] = array();

        // 가변 파일
        if ($board['bo_use_list_file'] || ( isset($list['wr_file']) && !empty($list['wr_file']) ) ) { //view 인 경우
            $list['file'] = g5_get_file($board, $list['wr_id'], $this->qstr, $page_url );
        } else {
            $list['file']['count'] = $list['wr_file'];
        }

        if (isset($list['file']['count']) && !empty($list['file']['count']))
            $list['icon_file'] = '<img src="'.$skin_url.'/img/icon_file.gif" alt="첨부파일">';

        return $list;

    }

    // /bbs/view.php 에서 실행됨
    function get_view($write_row, $board, $skin_url, $board_page_url='')
    {
        return $this->get_list($write_row, $board, $skin_url, 255, $board_page_url);
    }

    //게시물 답변의 depth를 구한다.
    function bbs_list_sort($list, $sql){
        return g5_parent_child_sort('wr_id','wr_parent', $list);
    }
}

endif;

?>