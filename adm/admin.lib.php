<?php
// 입력 폼 안내문
function g5_help($help="")
{
    $str  = '<span class="frm_info">'.str_replace("\n", "<br>", $help).'</span>';
    return $str;
}

// 숫자 범위를 SELECT 형식으로 얻음
function g5_get_number_select($name, $start_id=-1, $end_id=10, $selected=-1, $event="")
{
    global $gnupress;

    $str = "\n<select id=\"{$name}\" name=\"{$name}\"";
    if ($event) $str .= " $event";
    $str .= ">\n";
    for ($i=$start_id; $i<=$end_id; $i++) {
        $str .= '<option value="'.$i.'"';
        if ((int) $i === (int) $selected)
            $str .= ' selected="selected"';
        $str .= ">{$i}</option>\n";
    }
    $str .= "</select>\n";
    return $str;
}

// 스킨디렉토리를 SELECT 형식으로 얻음
function g5_get_skin_select($skin_gubun, $id, $name, $selected='', $event='')
{
    $skins = g5_get_skin_dir($skin_gubun);
    $str = "<select id=\"$id\" name=\"$name\" $event>\n";
    for ($i=0; $i<count($skins); $i++) {
        if ($i == 0) $str .= "<option value=\"\">".__('— Select —', 'gnupress')."</option>";
        $str .= g5_option_selected($skins[$i], $selected);
    }
    $str .= "</select>";
    return $str;
}

// 스킨경로를 얻는다
function g5_get_skin_dir($skin, $skin_path=G5_SKIN_PATH)
{
    global $gnupress;

    $result_array = array();

    $dirname = $skin_path.'/'.$skin.'/';
    $handle = opendir($dirname);
    while ($file = readdir($handle)) {
        if($file == '.'||$file == '..') continue;

        if (is_dir($dirname.$file)) $result_array[] = $file;
    }
    closedir($handle);
    sort($result_array);

    return $result_array;
}


function g5_admin_warnings( $msg )
{
    global $gnupress;
    
    $add_err_msg = $gnupress->add_err_msg;
	
	if( $msg != false ) {
        echo '<div class="updated fade"><p><strong>'.$msg.'</strong></p></div>';
    }

    if( $add_err_msg ){
        echo '<div class="error"><p><strong>'.$add_err_msg.'</strong></p></div>';
    }
}

// 그누보드5 페이지가 없다면 생성
function g5_mk_page($data=array()){
    global $gnupress;
    
    $add_err_msg = $gnupress->add_err_msg;

    $g5_options = get_option(G5_OPTION_KEY);
    $page_id = '';
    
    $page_action = g5_page_get_by($data['bo_table'], 'name');
    if ( ! $page = g5_get_page_id( $page_action ) ) {
        $page_id = wp_insert_post( array(
            'post_title'     => isset($data['bo_subject']) ? $data['bo_subject'] : __('GNUPress', G5_NAME),
            'post_name'      => $data['bo_subject'],
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'post_content'   => '['.G5_NAME.' bo_table='.$data['bo_table'].']',
            'comment_status' => 'closed',
            'ping_status'    => 'closed'
        ) );
        update_post_meta( $page_id, G5_META_KEY, $page_action );
        
        g5_page_update_metakey( $data['bo_table'], $page_id );
    }
    
    return $page_id;
}

function g5_page_update_metakey($bo_table, $page_id=0 ){

    if( !$bo_table) return false;

    $g5_options = get_option(G5_OPTION_KEY);

    $op_key = 'board_page';
    $g5_options[$op_key] = isset( $g5_options[$op_key] ) ? $g5_options[$op_key] : array();

    if( $page_id && (in_array( $page_id, $g5_options[$op_key]) ) ){
        return 2;
    } else {
        $g5_options[$op_key][$bo_table] = $page_id;
    }
    
    update_option( G5_OPTION_KEY, $g5_options );
    return 1;
}

function g5_javascript_location_replace($url){
    die("<script>location.replace('$url');</script>");
}

function g5_is_admin(){
    $is_admin = current_user_can( 'administrator' ) ? 'super' : '';
    return $is_admin;
}

function g5_update_options_by($data, $by='config'){
    if(!$data) return false;

    $g5_options = get_option(G5_OPTION_KEY);

    switch ( $by ) {
        case 'all' :
            $tmp_options = wp_parse_args((array) $data, $g5_options);
            update_option( G5_OPTION_KEY, $tmp_options );
            break;
        case 'config' :
        default :
            $tmp_config = wp_parse_args((array) $data, G5_var::getInstance()->get_options('config'));
            $g5_options[$by] = $tmp_config;
            update_option( G5_OPTION_KEY, $g5_options );
            break;
    }

    return true;
}

// rm -rf 옵션 : exec(), system() 함수를 사용할 수 없는 서버 또는 win32용 대체
// www.php.net 참고 : pal at degerstrom dot com
function g5_rm_rf($file)
{
    if (file_exists($file)) {
        if (is_dir($file)) {
            $handle = opendir($file);
            while($filename = readdir($handle)) {
                if ($filename != '.' && $filename != '..')
                    g5_rm_rf($file.'/'.$filename);
            }
            closedir($handle);

            @chmod($file, G5_DIR_PERMISSION);
            @rmdir($file);
        } else {
            @chmod($file, G5_FILE_PERMISSION);
            @unlink($file);
        }
    }
}

function g5_adm_post_check($value){
    if( isset($_POST[$value]) && !empty($_POST[$value]) ){
        return " , $value = '".sanitize_text_field($_POST[$value])."' ";
    }
    return '';
}
?>