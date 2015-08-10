<?php
/**
 *  Plugin Name: GNUPress
 *  Description: 워드프레스 게시판 플러그인
 *  Author: SIR Soft
 *  Author URI: http://sir.co.kr
 *  Version: 0.2.2
 *  Text Domain: SIR Soft
 */

if ( !class_exists( 'GnuPress' ) ) :

//설정 파일을 불러옴
include_once( plugin_dir_path( __FILE__ ).'config.php' );

// 이 플러그인이 활성화 될때 설치
register_activation_hook( __FILE__, 'gnupress_install' );

function gnupress_install(){
    include_once( G5_DIR_PATH.'lib/g5_var.class.php' );
    include_once( G5_DIR_PATH.'lib/install.lib.php' );
    g5_install_do();
}

//initialize the plugin
add_action( 'init', 'gnupress_init', 10 );

function gnupress_init(){
    $GLOBALS['gnupress'] = new GnuPress();
}

add_action( 'plugins_loaded', 'g5_plugin_load_textdomain' );
function g5_plugin_load_textdomain() {
    //번역파일
    load_plugin_textdomain( 'gnupress', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' ); 
}

include_once( G5_DIR_PATH.'lib/g5_var.class.php' );
include_once( G5_DIR_PATH.'lib/common.lib.php' );
include_once( G5_DIR_PATH.'ajax_function.php' );
include_once( G5_DIR_PATH.'search.php' );

Class GnuPress {
    public $bo_table;
    public $is_g5_page = false;
    public $term_list = null;
    public $taxonomy = null;
    public $board_list = array();
    public $qstr;
    public $add_err_msg;
    public $new_url = '';    //새창 url
    public $member_page_array = array();
    public $wr_id = null;
    public $member_page_action = '';
    public $window_open = false;

    private $config;
    private $g5;
    private $attr;
    private $is_admin;
    private $is_shortcode_load = false;

    protected $instances = null;
    protected $latest = null;

    public $latest_skin = array();

    public function __construct() {

        if (!headers_sent())
            @session_start();

        define( 'G5_IS_MOBILE', wp_is_mobile() );

        define('G5_SERVER_TIME',    current_time( 'timestamp' ) );
        define('G5_TIME_YMDHIS',    date('Y-m-d H:i:s', G5_SERVER_TIME));
        define('G5_TIME_YMD',       substr(G5_TIME_YMDHIS, 0, 10));
        define('G5_TIME_HIS',       substr(G5_TIME_YMDHIS, 11, 8));

        //extend 폴더에서 파일들을 include한다.
        $this->load_extend_file();

        $this->config = G5_var::getInstance()->get_options('config');
        $this->g5 = G5_var::getInstance()->get_options();

        $redirect_id = isset( $_GET['g5_bbs_redirect'] ) ? (int) $_GET['g5_bbs_redirect'] : '';

        if( $redirect_id ){
            $this->board_redirect($redirect_id);
        }
        
        $this->member_page_array = apply_filters('g5_get_member_page', array('point', 'scrap', 'scrap_popin', 'formmail', 'kcaptcha_image'));

        $check_arr = array('g5_new', 'action');
        foreach($check_arr as $v){
            $$v = isset($_REQUEST[$v]) ? sanitize_text_field($_REQUEST[$v]) : '';
        }

        if( in_array($action, $this->member_page_array) ){
            $this->member_page_action = $action;
        }

        add_action('the_posts', array( $this, 'check_g5_page') );
        add_shortcode( G5_NAME, array( $this, 'g5_shortcode' ) );
        add_shortcode( G5_NAME.'_latest', array( $this, 'g5_latest_shortcode' ) );
        add_filter( 'widget_text' , 'do_shortcode' );

        add_action( 'admin_init', array( $this, 'g5_chk_admin' ) );
        add_action( 'admin_menu', array( $this, 'g5_admin_menu' ) );
        add_action('wp_head', array( $this, 'g5_initialize_head') );

        add_action( 'admin_bar_menu', array( $this, 'g5_custom_menu' ) );
        add_action( 'wp_login', array( $this, 'g5_login_check' ), 10, 2 );
        if( isset($this->config['cf_use_point']) && !empty($this->config['cf_use_point']) ){
            add_action( 'wp_footer', array( $this, 'user_check' ) );
        }

        $this->bo_table = isset($_REQUEST['bo_table']) ? esc_attr( wp_unslash($_REQUEST['bo_table']) ) : '';
        $this->is_admin = current_user_can( 'administrator' ) ? 'super' : '';
        
        $g5_options = get_option(G5_OPTION_KEY);

        if(isset($g5_options['cf_new_page_id'])){
            $this->new_url = get_permalink($g5_options['cf_new_page_id']);
        }
    }

    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }

    public function load_extend_file(){
        $extend_file = array();
        $extend_path = G5_DIR_PATH.'extend';
        $tmp = dir($extend_path);
        while ($entry = $tmp->read()) {
            // php 파일만 include 함
            if (preg_match("/(\.php)$/i", $entry))
                $extend_file[] = $entry;
        }

        if(!empty($extend_file) && is_array($extend_file)) {
            natsort($extend_file);

            foreach($extend_file as $file) {
                include_once($extend_path.'/'.$file);
            }
        }
        unset($extend_file);
    }

    public function load($attr=''){
        include_once( G5_DIR_PATH.'lib/g5_common.class.php' );
        include_once( G5_DIR_PATH.'lib/g5_board.class.php' );

        //add_action('wp_head', array( $this, 'g5_initialize_head') );

        $this->instances = new G5_Board($attr);
    }

    public function g5_admin_menu(){

        $g5_admin_page = $page_arr = array();

        //add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
        $page_arr[] = add_menu_page(G5_NAME, 'GNUPress', 'manage_options', 'g5_board_admin', 'g5_board_admin');
        
        //add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
        $g5_admin_page[0] = array( 'title'=>__('gnupress setting', G5_NAME), 'menu_slug'=> 'g5_board_admin', 'function'=>'g5_board_admin' );
        $g5_admin_page[1] = array( 'title'=>__('Manage board', G5_NAME), 'menu_slug'=> 'g5_board_list', 'function'=>'g5_board_list' );
        $g5_admin_page[2] = array( 'title'=>__('Add board', G5_NAME), 'menu_slug'=> 'g5_board_form', 'function'=>'g5_board_form' );
        $g5_admin_page[3] = array( 'title'=>__('Manage board tag', G5_NAME), 'menu_slug'=> 'g5_tag_form', 'function'=>'g5_tag_form' );
        $g5_admin_page[4] = array( 'title'=>__('Manage user points', G5_NAME), 'menu_slug'=> 'g5_point_list', 'function'=>'g5_point_list' );

        foreach( $g5_admin_page as $v ){
            $page_arr[] = add_submenu_page('g5_board_admin', G5_NAME, $v['title'], 'manage_options', $v['menu_slug'], $v['function']);
        }

        //add_options_page( $page_title, $menu_title, $capability, $menu_slug, $function);

        foreach( $page_arr as $v ){
            add_action( 'load-'.$v, 'g5_load_admin_js' ); // enqueues javascript for admin
        }

        $page = isset($_REQUEST['page']) ? sanitize_text_field( $_REQUEST['page'] ) : '';

        if( $page == 'g5_tag_form'){  // 태그 사용시 설정
            add_action( 'load-'.strtolower(G5_NAME).'_page_g5_tag_form', array( $this, '_page_g5_tag_form') );
        }
    }
    
    public function user_check(){
        global $current_user;

        if ( 0 == $current_user->ID )
            return;

        // 오늘 처음 로그인 인지 체크
        if (substr( get_user_meta($current_user->ID, 'mb_today_login', true) , 0, 10) != G5_TIME_YMD && $this->config['cf_login_point']) {
            // 첫 로그인 포인트 지급
            g5_insert_point($current_user->ID, $this->config['cf_login_point'], G5_TIME_YMD.' '.__('First Login', G5_NAME), '@login', $current_user->ID, G5_TIME_YMD);

            // 오늘의 로그인이 될 수도 있으며 마지막 로그인일 수도 있음
            // 해당 회원의 접근일시와 IP 를 저장
            update_user_meta($current_user->ID, 'mb_today_login', G5_TIME_YMDHIS );
            update_user_meta($current_user->ID, 'mb_login_ip', $_SERVER['REMOTE_ADDR'] );
        }

    }

    public function g5_login_check( $user_login, $user ){
        if( !$user->ID ){
            return;
        }

        if( $this->config['cf_use_point'] ){
            $sum_point = g5_get_point_sum($user->ID);
            update_user_meta( $user->ID, 'mb_point', $sum_point );
        }
    }

    public function g5_custom_menu( ) {
        global $wp_admin_bar, $current_user;

        if ( is_user_logged_in() ) {

            $my_account_menu_id = 'my-account-'.G5_NAME;

            $wp_admin_arr = apply_filters('g5_admin_bar_add' ,array(
                    array(
                        'parent' => 'my-account', 'id' => $my_account_menu_id, 'title' => __( 'My Account', G5_NAME ), 'group' => true, 'meta' => array('class' => 'ab-sub-secondary')
                    ),
                    array(
                        'parent' => $my_account_menu_id, 'id' => 'g5-account-point', 'title'  => __( 'My point', G5_NAME ).'( '.number_format((int) get_user_meta($current_user->ID ,'mb_point', true)).' )'
                        , 'href' => g5_get_link_by('point'), 'meta' => array('class' => 'g5_new_open', 'target' => '_blank')
                    ),
                    array(
                        'parent' => $my_account_menu_id, 'id' => 'g5-account-scrap', 'title'  => __( 'My scrap', G5_NAME ), 'href' => g5_get_link_by('scrap'), 'meta' => array('class' => 'g5_new_open', 'target' => '_blank')
                    )
            ));

            // Add each admin menu
            foreach( $wp_admin_arr as $menu ) {
                $wp_admin_bar->add_menu( $menu );
            }

            add_action('wp_footer', array( $this, 'g5_custom_menu_script' ), 3 );
            add_action('admin_footer', array( $this, 'g5_custom_menu_script' ) );
        }
    }

    public function g5_custom_menu_script(){
        //g5_custom_menu 에서 생성된 메뉴에 대한 자바스크립트 처리
        ?>
            <script>
            jQuery(document).ready(function($) {
                $(".ab-sub-secondary .g5_new_open").on("click", function(e){
                    e.preventDefault();
                    var href = $(this).children().attr("href"),
                        new_win = window.open(href, 'g5_new_open', 'left=100,top=100,width=600, height=600, scrollbars=1');
                    new_win.focus();
                });
            });
            </script>
        <?php
        do_action('g5_custom_menu_script_add');
    }

    public function _page_g5_tag_form($bo_table=''){
        global $wp_taxonomies;
        
        if( !$bo_table ){
            $bo_table = $this->bo_table;
        }
        g5_wp_taxonomies($bo_table);

        $GLOBALS['taxonomy'] = $this->taxonomy = g5_get_taxonomy($bo_table);
        $this->term_list = _g5_get_list_table('G5_Terms_List_Table', $this->taxonomy, $bo_table);
    }

    public function check_g5_page($posts){

        $g5_options = get_option(G5_OPTION_KEY);

        if( isset($g5_options['version']) && $g5_options['version'] != G5_VERSION ){
            include_once( G5_DIR_PATH.'lib/g5_update_check.php' );
        }

        $is_g5_page = false;

        if( $is_g5_page = $this->g5_member_page_check($posts) ){
            $this->is_g5_page = true;
            return $posts;
        } else {
            // is_page() 또는 is_singular() 또는 is_single() 를 잘 구분하자

            if ( empty($posts) || $this->is_g5_page || !is_singular() )
                return $posts;

            $board_page_exists = isset( $g5_options['board_page'] ) ? 1 : 0;

            foreach ($posts as $post) {

                if( $post->post_type != 'page' ) continue;

                if( $board_page_exists ){
                    $bo_table = array_search( $post->ID, $g5_options['board_page'] );
                    if( $bo_table !== false ){
                       $this->is_g5_page = true;
                       $this->attr = $attr = array('bo_table'=>$bo_table);

                       add_filter('the_content', array( $this, 'filter_the_content') );   //내용 관리 필터

                       break;break;break;
                    }
                }

                if( !empty($post->post_content) && has_shortcode( $post->post_content, G5_NAME ) ){
                    $attr = $this->check_attr($post->post_content);
                    $this->is_g5_page = true;
                    break;break;
                }
            }
        }

        if( $this->is_g5_page ){
            $this->load($attr);
        }

        return $posts;
    }

    public function check_attr($content){
        $attr = array();
        if ( preg_match_all( '/' . get_shortcode_regex() . '/s', $content, $matches, PREG_SET_ORDER ) ) {
            foreach ($matches as $shortcode) {
                if (G5_NAME === $shortcode[2]) {
                    $attr = shortcode_parse_atts($shortcode[3]);
                    break;break;
                }
            }
        }
        return $attr;
    }

    public function filter_the_content($content=''){
        return $content.$this->g5_shortcode($this->attr ,true);
    }

    public function g5_member_page_check($posts){

        if( !$this->member_page_action ){
            //return false;
        }

        if ( empty($posts) || $this->is_g5_page || !is_singular() )
            return false;

        $g5_options = get_option(G5_OPTION_KEY);
        
        foreach ($posts as $post) {

            if( $post->post_type != 'page' ) continue;

            if( isset($post->ID) && isset($g5_options['cf_new_page_id']) && $post->ID == $g5_options['cf_new_page_id'] ){
                if( !$this->member_page_action ){
                    $this->member_page_action = 'point';
                    $this->attr = array( 'page_mode'=>$this->member_page_action );
                    add_filter('the_content', array( $this, 'filter_the_content') );   //내용 관리 필터
                } else {
                    $this->window_open = true;
                    add_action( 'template_redirect', array( $this, 'g5_member_pageload' ) );
                }
                return true;
            }
        }

        return false;
    }

    public function g5_member_pageload(){

        $g5_options = get_option(G5_OPTION_KEY);
        $page_mode = $action = $this->member_page_action;
        if( $action == 'kcaptcha_image' ){
            include_once( G5_PLUGIN_PATH.'/kcaptcha/kcaptcha_image.php' );
        } else {
            include_once( G5_DIR_PATH.'bbs/member.php' );
        }

    }

    // 게시판 관련 숏코드 실행
    public function g5_shortcode($attr='', $is_setup_page=false){

        if( isset($attr['page_mode']) && in_array($attr['page_mode'], $this->member_page_array) ){
            return $this->g5_member_action( $attr );
        }

        if( ! isset($attr['bo_table']) && empty($attr['bo_table']) ){
            $g5_error = new WP_Error( 'broke', __( "Please enter a value bo_table on the shortcode.", G5_NAME ) );
            echo $g5_error->get_error_message();
            return;
        }
        
        if( $this->instances === null ){
            $this->load($attr);
        }
        
        if( $is_setup_page ){
            $this->is_shortcode_load = true;    //페이지가 지정 되었으면 무조건 리턴한다.
            return $this->instances->shortcode();
        } else {
            if( $this->is_shortcode_load === false ){   //한 페이지에 shortcode 중복 방지
                $this->is_shortcode_load = true;
                return $this->instances->shortcode();
            }
        }
    }

    //최신글 숏코드 실행
    public function g5_latest_shortcode($attr=array()){
        global $wpdb;

        $g5 = $this->g5;

        if( ! isset($attr['bo_table']) && empty($attr['bo_table']) ){
            $g5_error = new WP_Error( 'broke', __( "Please enter a value bo_table on the shortcode.", G5_NAME ) );   //shortcode에 반드시 bo_table값을 입력해 주세요.
            return $g5_error->get_error_message();
        }
        
        $arg = wp_parse_args($attr, array(
                'is_latest' => true,
                'skin_dir' => 'basic',
                'bo_table' => '',
                'rows' => 5,
                'subject_len' => 40,
                'cache_time' => 1,
                'options' => ''
        ));

        extract( $arg );

        $latest_skin_path = G5_DIR_PATH.'skin/latest/'.$skin_dir;
        $latest_skin_url  = G5_DIR_URL.'skin/latest/'.$skin_dir;
        
        $use_latest_cache = $this->config['cf_use_latest_cache'];

        $cache_file_path = g5_get_upload_path();

        $cache_fwrite = false;
        if( $use_latest_cache && $cache_file_path ){
            $cache_file = $cache_file_path."/cache/latest-{$bo_table}-{$skin_dir}-{$rows}-{$subject_len}.php";

            if(!file_exists($cache_file)) {
                $cache_fwrite = true;
            } else {
                if($cache_time > 0) {
                    $filetime = filemtime($cache_file);
                    if($filetime && $filetime < (G5_SERVER_TIME - 3600 * $cache_time)) {
                        @unlink($cache_file);
                        $cache_fwrite = true;
                    }
                }

                if(!$cache_fwrite)
                    include($cache_file);
            }
        }
        
        if( !$use_latest_cache || $cache_fwrite){
            $list = array();

            if( $this->latest === null ){
                include_once( G5_DIR_PATH.'lib/g5_common.class.php' );
                include_once( G5_DIR_PATH.'lib/g5_board.class.php' );
                include_once( G5_DIR_PATH.'lib/g5_taxonomy.lib.php' );

                $this->latest = new G5_Board($attr);
            }

            $board = g5_get_board_config($bo_table);

            if( !isset($board['bo_table']) || empty($board['bo_table']) ){
                $g5_error = new WP_Error( 'broke', __( "No data.", G5_NAME) );   //데이터가 없습니다.
                return $g5_error->get_error_message();
            }

            $bo_table = $board['bo_table'];
            $bo_subject = g5_get_text($board['bo_subject']);

            $sql = $wpdb->prepare("select * from {$g5['write_table']} where bo_table = '%s' and wr_parent = 0 order by wr_num limit 0, %d ", $bo_table, $rows);
            $lists = $wpdb->get_results($sql, ARRAY_A);
            
            $i = 0;
            foreach($lists as $row){

                if( empty($row) ) continue;

                $list[$i] = $this->latest->get_list($row, $board, $latest_skin_url, $subject_len);
                if( $board['page_url'] ){
                    $list[$i]['href'] = add_query_arg( array('wr_id'=>$row['wr_id']) , $board['page_url']);
                } else {
                    if( $row['wr_page_id'] ){
                        $list[$i]['href'] = add_query_arg( array('wr_id'=>$row['wr_id']) , get_permalink($row['wr_page_id']) );
                    } else {
                        $list[$i]['href'] = "#no_value_link";
                    }
                }
                $i++;
            }

            if($cache_fwrite) {
                $handle = fopen($cache_file, 'w');
                $cache_content = "<?php\nif (!defined('_GNUBOARD_')) exit;\n\$bo_subject='".$bo_subject."';\n\$list=".var_export($list, true)."?>";
                fwrite($handle, $cache_content);
                fclose($handle);
            }
        }
        
        //최신글 스킨별 css 중복 로드 방지
        if( !isset($this->latest_skin[$bo_table.'_css']) ){
            if( is_file( $latest_skin_path.'/style.css' ) ){
                wp_enqueue_style ( G5_NAME.'_latest_'.$bo_table , $latest_skin_url.'/style.css', '', G5_VERSION );
            }
            $this->latest_skin[$bo_table.'_css'] = true;
        }

        $g5_page_url = g5_page_get_by($bo_table, 'url' );
        ob_start();
        include $latest_skin_path.'/latest.skin.php';
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    // 회원 관련 숏코드 실행
    public function g5_member_action( $attr='', $header_script=false ){

        if( $this->instances === null && ( isset($attr['page_mode']) && in_array($attr['page_mode'], $this->member_page_array) ) ){
            include_once( G5_DIR_PATH.'lib/g5_common.class.php' );
            
            switch ( $attr['page_mode'] ) {
                case 'scrap_popin': // 스크랩팝업 페이지
                    $class = 'G5_scrap';
                    break;
                default :
                    $class = 'G5_'.$attr['page_mode'];
            }
            
            if( file_exists(G5_DIR_PATH.'lib/'.strtolower($class).'.class.php') ){
                include_once( G5_DIR_PATH.'lib/'.strtolower($class).'.class.php' );
                $this->instances = new $class( $attr );
            } else {
                $g5_error = new WP_Error( 'broke', __( "NOT EXISTS CLASS FILE", G5_NAME ) . $class );
                echo $g5_error->get_error_message();
                return;
            }
        }

        if( $this->instances !== null ){
            // header 에 js 및 css 파일들을 추가한다.
            if( $header_script && method_exists($this->instances, 'header_script' ) ){

                return $this->instances->header_script($attr);
            }

            return $this->instances->shortcode();
        }
    }

    public function board_redirect($wr_id){
        global $wpdb;
        
        $row = $wpdb->get_row($wpdb->prepare("select bo_table, wr_page_id from ".$this->g5['write_table']." where wr_id = %d ", $wr_id), ARRAY_A);
        
        $g5_options = get_option(G5_OPTION_KEY);

        $board_page = isset($g5_options['board_page'][$row['bo_table']]) ? $g5_options['board_page'][$row['bo_table']] : 0;
        
        $wr_page_id = $board_page ? $board_page : $row['wr_page_id'];

        if( $board_page ){
            wp_safe_redirect( add_query_arg(array( 'wr_id'=> $wr_id ), get_permalink($wr_page_id)) );
            die();
        }
    }

    public function g5_chk_admin(){

        do_action( 'g5_pre_admin_init', $this );

        if( current_user_can( 'edit_users' ) ) {
            if( $this->config['cf_editor'] ){
                //에디터를 사용할 경우 처리
                include_once( G5_DIR_PATH.'plugin/editor/'.$this->config['cf_editor'].'/editor.lib.php' );
            } else {
                //textarea를 사용할 경우 처리
                include_once( G5_DIR_PATH.'lib/editor.lib.php' );
            }
            include_once( G5_DIR_PATH.'bbs/db_table.optimize.php' );
            include_once( G5_DIR_PATH.'adm/admin.lib.php' );
            include_once( G5_DIR_PATH.'adm/admin.php' );
        }
    }

    public function g5_initialize_head(){

        $array_config = G5_var::getInstance()->get_options('js');
        $value = array_keys($array_config);
        $array_last_key = array_pop($value);
        echo '<script type="text/javascript">/* <![CDATA[ */';
        echo 'var gnupress = {';
        foreach($array_config as $key=>$v) {
            if( empty($key) ) continue;
            echo $key.':';
            echo '"'.g5_js_escape($v).'"';
            if( $key != $array_last_key ){
                echo ',';
            }
        }
        echo '};';
        echo '/* ]]> */</script>'.PHP_EOL;
    }

}   //end class

endif;
?>