<?php
if ( ! class_exists( 'G5_var' ) ) :

class G5_var {

    protected $db_tables = array();
    protected $config = array();
    public $js = array();

    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    protected function __construct() {
    }

    protected function get_table_array() {
        global $wpdb;

        $this->db_tables = apply_filters( 'g5_default_table', array(
            'board_table' => $wpdb->prefix.'g5_board',
            'meta_table' => $wpdb->prefix.'g5_writemeta',
            'write_table' => $wpdb->prefix.'g5_write',
            'comment_table' => $wpdb->prefix.'g5_write_comment',
            'relation_table' => $wpdb->prefix.'g5_term_relationships',
            'taxonomy_table' => $wpdb->prefix.'g5_term_taxonomy',
            'point_table' => $wpdb->prefix.'g5_point',
            'scrap_table' => $wpdb->prefix.'g5_scrap',
            'board_good_table' => $wpdb->prefix.'g5_board_good'
        ));

        $wpdb->{G5_META_TYPE.'meta'} = $this->db_tables['meta_table'];
    }

    public function get_config_js() {

        $g5_options = get_option(G5_OPTION_KEY);

        $this->js = apply_filters( 'g5_js_defaults', array(
            'bbs_url' => get_permalink( get_the_ID() ),
            'is_member' => get_current_user_id() ? true : false,
            'is_admin' => current_user_can( 'administrator' ) ? 'super' : '',
            'is_mobile' => wp_is_mobile(),
            'bo_table' => '',
            'new_url' => isset($g5_options['cf_new_page_id']) ? get_permalink($g5_options['cf_new_page_id']) : '',
            'ajax_url' => admin_url('admin-ajax.php')
        ));
    }

    protected function get_config() {
        
        $g5_options = get_option(G5_OPTION_KEY);
        $g5_options['config'] = isset($g5_options['config']) ? (array) $g5_options['config'] : array();

        $config_default = array(
            'cf_page_rows' => 15,   //한페이지당 라인수
            'cf_write_pages' => 10,     //PC 페이지 표시수
            'cf_mobile_pages' => 5,     //모바일 페이지 표시수
            'cf_read_point' => 0,   //글 읽기 포인트
            'cf_write_point' => 0,  //글쓰기 포인트
            'cf_comment_point' => 0,    //댓글쓰기 포인트
            'cf_download_point' => 0,   //다운로드 포인트
            'cf_editor' => '', //에디터 선택
            'cf_cut_name' => 15,    //이름(닉네임) 표시
            'cf_link_target' => '_blank',   //새창 링크
            'cf_use_copy_log' => 1, //복사 이동시 로그
            'cf_use_point' => 1,    //포인트 사용 여부
            'cf_login_point' => 100, //로그인시 포인트
            'cf_point_term' => 0,   //포인트 유효기간
            'cf_cert_use' => 0 , //본인인증
            'cf_captcha_mp3' => 'basic',    //음성캡챠선택
            'cf_search_part' => 10000, // 건 단위로 검색
            'cf_flash_extension' => 'swf',   // 플래쉬 업로드 확장자
            'cf_movie_extension' => 'asx|asf|wmv|wma|mpg|mpeg|mov|avi|mp3',     //동영상업로드 확장자
            'cf_email_use' => 1,    //메일 사용여부
            'cf_formmail_is_member' => 1,   //폼메일 사용 여부( 회원, 비회원 )
            'cf_email_wr_super_admin' => 0, //게시판 글 작성 시 메일 설정( 최고관리자 )
            'cf_email_wr_board_admin' => 0, //게시판 글 작성 시 메일 설정( 게시판관리자 )
            'cf_email_wr_write' => 0,   //게시판 글 작성시 메일 설정( 원글작성자)
            'cf_email_wr_comment_all' => 0, //게시판 글 작성 시 메일 설정( 댓글 작성자 )
            'cf_image_extension' => 'gif|jpg|jpeg|png', //이미지 업로드 확장자
            'cf_filter' => G5_CF_FILTER,
            'cf_delay_sec' => 1,    //게시물을 연속해서 올릴 경우 시간 제한
            'cf_include_search_tag' => 0, // 게시판의 태그 결과를 워드프레스의 태그 검색에 포함
            'cf_parent_limit' => 0,
            'cf_syndi_token' => '', //네이버 신디케이션 연동키
            'cf_syndi_except' => '', //네이버 신디케이션 제외게시판,
            'cf_use_search_include' => 1, //전체 검색시 게시판 내용을 포함
            'cf_use_latest_cache' => 1, //최신글 캐쉬 사용
            'cf_new_page_name' =>   'g5member' //새창 페이지 이름 등
        );

        $this->config = apply_filters( 'g5_config_add', wp_parse_args($g5_options['config'], $config_default) );
    }

    public function get_options($options='db_tables') {
        if( ! $this->db_tables && $options == 'db_tables' ){
            $this->get_table_array();
        } else if( ! $this->config && $options == 'config' ){
            $this->get_config();
        } else if( ! $this->js && $options == 'js' ){
            $this->get_config_js();
        }

        return $this->$options;
    }
}

endif;
?>