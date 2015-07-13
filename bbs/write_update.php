<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if ( ! isset( $_POST['g5_nonce_field'] ) || ! wp_verify_nonce( $_POST['g5_nonce_field'], 'g5_write' ) ) {
    g5_alert(__('잘못된 접근입니다.', G5_NAME), get_permalink() );
}

include_once(G5_PLUGIN_PATH.'/kcaptcha/captcha.lib.php');
include_once(G5_DIR_PATH.'lib/naver_syndi.lib.php');

$g5['title'] = '게시글 저장';

$g5_data_path = g5_get_upload_path();
$after_update = $after_formats = array();
$after_update['wr_page_id'] = get_the_ID();
$after_formats[] = '%d';
$msg = array();

$post_data = $_POST;

$wr_subject = '';
if (isset($_POST['wr_subject'])) {
    $wr_subject = substr(sanitize_text_field(trim($_POST['wr_subject'])),0,255);
    $wr_subject = preg_replace("#[\\\]+$#", "", $wr_subject);
}

if ($wr_subject == '') {
    $msg[] = '제목을 입력하세요.';
}

$ca_name = '';
if (isset($_POST['ca_name'])) {
    $ca_name = substr(sanitize_text_field(trim($_POST['ca_name'])),0,255);
}

$html = '';
if (isset($_POST['html']) && $_POST['html']) {
    $html = sanitize_text_field($_POST['html']);
}

$wp_html = '';
// 워드 프레스 에디터인 경우
if (isset($_POST['wp_html']) && $_POST['wp_html'] && $html ) {
    $wp_html = sanitize_text_field($_POST['wp_html']);
}

// youtube 나 video 때문에 allow tag 에 iframe 을 추가
add_filter('wp_kses_allowed_html', 'g5_allow_tags', 15, 2);
function g5_allow_tags($allow_tags, $context) {
    if( $context == 'post' ){
        $allow_tags += array(
            'iframe' => array(
            'width' => array(),
            'height' => array(),
            'frameborder' => array(),
            'src' => array(),
            'frameborder' => array(),
            'marginwidth' => array(),
            'marginheight' => array(),
            'allowfullscreen' => array()
            )
        );
    }
    return $allow_tags;
}

$wr_content = '';
if (isset($_POST['wr_content'])) {
    if($html){
        $wr_content = sanitize_post_field('post_content', $_POST['wr_content'], $after_update['wr_page_id'], 'db');
    } else {
        $wr_content = implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $_POST['wr_content'] ) ) );
    }
    $wr_content = preg_replace("#[\\\]+$#", "", $wr_content);
} else {
    if( empty($_POST['wr_content']) ){  //is empty
        $msg[] = '내용을 입력하세요.';
    }
}

remove_filter('wp_kses_allowed_html', 'g5_allow_tags', 15);

$wr_link1 = '';
if (isset($_POST['wr_link1'])) {
    $wr_link1 = substr(sanitize_text_field($_POST['wr_link1']),0,1000);
    $wr_link1 = trim(strip_tags($wr_link1));
    $wr_link1 = preg_replace("#[\\\]+$#", "", $wr_link1);
}

$wr_link2 = '';
if (isset($_POST['wr_link2'])) {
    $wr_link2 = substr(sanitize_text_field($_POST['wr_link2']),0,1000);
    $wr_link2 = trim(strip_tags($wr_link2));
    $wr_link2 = preg_replace("#[\\\]+$#", "", $wr_link2);
}

$msg = implode('<br>', $msg);
if ($msg) {
    g5_alert(strip_tags($msg));
}

// 090710
if (substr_count($wr_content, '&#') > 50) {
    g5_alert('내용에 올바르지 않은 코드가 다수 포함되어 있습니다.');
    exit;
}

$upload_max_filesize = ini_get('upload_max_filesize');

if (empty($_POST)) {
    g5_alert("파일 또는 글내용의 크기가 서버에서 설정한 값을 넘어 오류가 발생하였습니다.\\npost_max_size=".ini_get('post_max_size')." , upload_max_filesize=".$upload_max_filesize."\\n게시판관리자 또는 서버관리자에게 문의 바랍니다.");
}

$notice_array = explode(",", $board['bo_notice']);

if ($w == 'u' || $w == 'r') {
    if( isset($write) && isset($write['wr_id']) ){
        $wr = $write;
    } else {
        $wr = $write = g5_get_write($write_table, $wr_id);
    }

    if (! isset($wr['wr_id']) ) {
        g5_alert(__("글이 존재하지 않습니다.\\n글이 삭제되었거나 이동하였을 수 있습니다.", G5_NAME));
    }
}

// 외부에서 글을 등록할 수 있는 버그가 존재하므로 비밀글은 사용일 경우에만 가능해야 함
if (!$is_admin && !$board['bo_use_secret'] && $secret) {
	g5_alert('비밀글 미사용 게시판 이므로 비밀글로 등록할 수 없습니다.');
}

// 외부에서 글을 등록할 수 있는 버그가 존재하므로 비밀글 무조건 사용일때는 관리자를 제외(공지)하고 무조건 비밀글로 등록
if (!$is_admin && $board['bo_use_secret'] == 2) {
    $secret = 'secret';
}

$mail = '';
if (isset($_POST['mail']) && $_POST['mail']) {
    $mail = sanitize_text_field($_POST['mail']);
}

$notice = '';
if (isset($_POST['notice']) && $_POST['notice']) {
    $notice = sanitize_text_field($_POST['notice']);
}

for ($i=1; $i<=10; $i++) {
    $var = "wr_$i";
    $$var = "";
    if (isset($_POST['wr_'.$i]) && settype($_POST['wr_'.$i], 'string')) {
        $$var = sanitize_text_field(trim($_POST['wr_'.$i]));
    }
}

@include_once($board_skin_path.'/write_update.head.skin.php');

if ($w == '' || $w == 'u') {

    // 김선용 1.00 : 글쓰기 권한과 수정은 별도로 처리되어야 함
    if($w =='u' && $member['user_id'] && $wr['user_id'] == $member['user_id']) {
        ;
    } else if ($member['user_level'] < $board['bo_write_level']) {
        g5_alert('글을 쓸 권한이 없습니다.');
    }

	// 외부에서 글을 등록할 수 있는 버그가 존재하므로 공지는 관리자만 등록이 가능해야 함
	if (!$is_admin && $notice) {
		g5_alert('관리자만 공지할 수 있습니다.');
    }

} else if ($w == 'r') {

    if (in_array((int)$wr_id, $notice_array)) {
        g5_alert('공지에는 답변 할 수 없습니다.');
    }

    if ($member['user_level'] < $board['bo_reply_level']) {
        g5_alert('글을 답변할 권한이 없습니다.');
    }

    if ( $wr['wr_parent'] && $config['cf_parent_limit'] ){
        g5_alert(__('더 이상 답변하실 수 없습니다.\\n\\n답변은 1단계 까지만 가능합니다.', G5_NAME));
    }

} else {
    g5_alert('w 값이 제대로 넘어오지 않았습니다.');
}

if ($is_guest && !g5_chk_captcha()) {
    g5_alert('자동등록방지 숫자가 틀렸습니다.');
}

if ($w == '' || $w == 'r') {

    if (isset($_SESSION['ss_datetime'])) {
        if ($_SESSION['ss_datetime'] >= (G5_SERVER_TIME - $config['cf_delay_sec']) && !$is_admin)
            g5_alert('너무 빠른 시간내에 게시물을 연속해서 올릴 수 없습니다.');
    }
    g5_set_session("ss_datetime", G5_SERVER_TIME);
}

if (!$wr_subject)
    g5_alert('제목을 입력하여 주십시오.');

if ($w == '' || $w == 'r') {
    if ($member['user_id']) {
        $user_id = $member['user_id'];
        $user_name = addslashes(g5_clean_xss_tags($board['bo_use_name'] ? $member['user_name'] : $member['user_display_name']));
        $user_pass = g5_sql_password($member['user_pass']);
        $user_email = addslashes($member['user_email']);
    } else {
        $user_id = '';
        // 비회원의 경우 이름이 누락되는 경우가 있음
        $user_name = sanitize_text_field(trim($_POST['user_name']));
        if (!$user_name)
            g5_alert('이름은 필히 입력하셔야 합니다.');
        $user_pass = g5_sql_password(sanitize_text_field(trim($_POST['user_pass'])));
        $user_email = sanitize_text_field(trim($_POST['user_email']));
    }

    if ($w == 'r') {
        // 답변의 원글이 비밀글이라면 비밀번호는 원글과 동일하게 넣는다.
        if ($secret)
            $user_pass = $wr['user_pass'];

        $wr_id = $wr_id . $reply;
        $wr_num = $write['wr_num'];
        $wr_reply = $reply;
    } else {
        $wr_num = g5_get_next_num($write_table, $bo_table);
        $wr_reply = '';
    }

    $g5_data = array(
        'bo_table' => $board['bo_table'],
        'wr_num' => $wr_num,
        'ca_name' => $ca_name,
        'wr_option' => g5_get_arg_array($html,$wp_html,$secret,$mail),
        'wr_subject' => $wr_subject,
        'wr_content' => $wr_content,
        'user_id' => $user_id,
        'user_pass' => $user_pass,
        'user_display_name' => $user_name,
        'user_email' => $user_email,
        'wr_link1' => $wr_link1,
        'wr_link2' => $wr_link2,
        'wr_datetime' => G5_TIME_YMDHIS,
        'wr_last' => G5_TIME_YMDHIS,
        'wr_hit' => 0,
        'wr_ip' => $_SERVER['REMOTE_ADDR']
        );

    $formats = array(
        '%s',
        '%d',
        '%s',
        '%s',
        '%s',
        '%s',
        '%d',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%s',
        '%d',
        '%s'
        );

    if($w == 'r' && isset($wr['wr_id']) ){  //답변글이면
        $g5_data['wr_parent'] = $wr['wr_id'];
    }

    $g5_data = apply_filters('g5_insert_data_filters', wp_unslash($g5_data), $post_data);

    $formats = apply_filters('g5_insert_format_filters', $formats);

    // insert
    $result = $wpdb->insert($write_table, $g5_data, $formats);

    // db insert에 실패한 경우
    if ( $result === false ){
        g5_show_db_error();
    }

    $wr_id = $wpdb->insert_id;

    // bo_table 에 게시글 1 증가
    $result = $wpdb->query(
        $wpdb->prepare(" update `{$g5['board_table']}` set bo_count_write = bo_count_write + 1 where bo_table = '%s' ", $bo_table)
        );

    // 쓰기 포인트 부여
    if ($w == '') {
        if ($notice) {
            $bo_notice = $wr_id.($board['bo_notice'] ? ",".$board['bo_notice'] : '');
            $result = $wpdb->query(
                $wpdb->prepare(" update {$g5['board_table']} set bo_notice = '%s' where bo_table = '%s' ", $bo_notice, $bo_table)
                );
        }

        g5_insert_point($member['user_id'], $board['bo_write_point'], "{$board['bo_subject']} {$wr_id} 글쓰기", $bo_table, $wr_id, '쓰기');
    } else {
        // 답변은 코멘트 포인트를 부여함
        // 답변 포인트가 많은 경우 코멘트 대신 답변을 하는 경우가 많음
        g5_insert_point($member['user_id'], $board['bo_comment_point'], "{$board['bo_subject']} {$wr_id} 글답변", $bo_table, $wr_id, '쓰기');
    }

}  else if ($w == 'u') {

    $return_url = add_query_arg( array('wr_id'=>$wr_id), $default_href );

    if ($is_admin == 'super') // 최고관리자 통과
        ;
    else if ($is_admin == 'board') { // 게시판관리자이면
        $mb = g5_get_member($write['user_id']);
        if ($member['user_id'] != $board['bo_admin']) // 자신이 관리하는 게시판인가?
            g5_alert('자신이 관리하는 게시판이 아니므로 수정할 수 없습니다.', $return_url);
        else if ($member['user_level'] < $mb['user_level']) // 자신의 레벨이 크거나 같다면 통과
            g5_alert('자신의 권한보다 높은 권한의 회원이 작성한 글은 수정할 수 없습니다.', $return_url);
    } else if ($member['user_id']) {
        if ($member['user_id'] != $write['user_id'])
            g5_alert('자신의 글이 아니므로 수정할 수 없습니다.', $return_url);
    } else {
        if ($write['user_id'])
            g5_alert('로그인 후 수정하세요.', wp_login_url($return_url));
    }

    if ($member['user_id']) {
        // 자신의 글이라면
        if ($member['user_id'] == $wr['user_id']) {
            $user_id = $member['user_id'];
            $user_name = addslashes(g5_clean_xss_tags($board['bo_use_name'] ? $member['user_name'] : $member['user_display_name']));
            $user_email = addslashes($member['user_email']);
        } else {
            $user_id = $wr['user_id'];
            if(isset($_POST['user_name']) && $_POST['user_name'])
                $user_name = sanitize_text_field(trim($_POST['user_name']));
            else
                $user_name = addslashes(g5_clean_xss_tags($wr['user_name']));
            if(isset($_POST['user_email']) && $_POST['user_email'])
                $user_email = sanitize_email(trim($_POST['user_email']));
            else
                $user_email = addslashes($wr['user_email']);
        }
    } else {
        $user_id = "";
        $user_name = sanitize_text_field(trim($_POST['user_name']));
        // 비회원의 경우 이름이 누락되는 경우가 있음
        if (!trim($user_name)) g5_alert("이름은 필히 입력하셔야 합니다.");
        $user_name = sanitize_text_field(trim($_POST['user_name']));
        $user_email = sanitize_email(trim($_POST['user_email']));
    }

    $sql_ip = '';

    $g5_data = array(
            'ca_name' => $ca_name,
            'wr_option' => g5_get_arg_array($html,$wp_html,$secret,$mail),
            'wr_subject' => $wr_subject,
            'wr_content' => $wr_content,
            'wr_link1' => $wr_link1,
            'wr_link2' => $wr_link2,
            'user_id' => $user_id,
            'user_display_name' => $user_name,
            'user_email' => $user_email
        );

    $formats = array(
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
            '%s',
            '%s'
        );

    if (!$is_admin){
        $g5_data['wr_ip'] = $_SERVER['REMOTE_ADDR'];
        $formats[] = '%s';
    }

    if( isset($user_pass) && !empty($user_pass) ){
        $g5_data['user_pass'] = g5_sql_password($user_pass);
        $formats[] = '%s';
    }

    $where = array( 'wr_id' => $wr['wr_id'] );

    $where_formats = array( '%d' );

    $g5_data = apply_filters('g5_update_data_filters', wp_unslash($g5_data), $post_data);

    $result = $wpdb->update($write_table, $g5_data, $where, $formats, $where_formats);

    if ( $result === false ){
        g5_show_db_error();
    }

    $bo_notice = g5_board_notice($board['bo_notice'], $wr_id, $notice);
    $result = $wpdb->query(
        $wpdb->prepare(" update `{$g5['board_table']}` set bo_notice = '%s' where bo_table = '%s' ", $bo_notice, $bo_table)
        );
}

// 비회원 글읽기가 가능해야 하며 비밀글이 아니어야 합니다.
if ((int) $board['bo_read_level'] === -1 && !$secret) {
    g5_naver_syndi_ping($bo_table, $wr_id, add_query_arg( array('wr_id'=>$wr_id) , $default_href) );
}

$old_meta_data = $file_meta_data = ( $w == '' ? array() : get_metadata(G5_META_TYPE, $wr_id, G5_FILE_META_KEY , true) );
$upload_count = 0;
$file_upload_msg = '';

if( $g5_data_path ){
    // 디렉토리가 없다면 생성합니다. (퍼미션도 변경하구요.)
    @mkdir($g5_data_path.'/file/'.$bo_table, G5_DIR_PERMISSION);
    @chmod($g5_data_path.'/file/'.$bo_table, G5_DIR_PERMISSION);

    $chars_array = array_merge(range(0,9), range('a','z'), range('A','Z'));

    // 가변 파일 업로드
    $upload = array();
    $bf_content = (isset($_POST['bf_content']) && !empty($_POST['bf_content'])) ? array_map('esc_textarea', $_POST['bf_content']) : array();

    for ($i=0; $i<count($_FILES['bf_file']['name']); $i++) {
        $upload[$i]['file']     = '';
        $upload[$i]['source']   = '';
        $upload[$i]['filesize'] = 0;
        $upload[$i]['image']    = array();
        $upload[$i]['image'][0] = '';
        $upload[$i]['image'][1] = '';
        $upload[$i]['image'][2] = '';

        // 삭제에 체크가 되어있다면 파일을 삭제합니다.
        if (isset($_POST['bf_file_del'][$i]) && $_POST['bf_file_del'][$i]) {
            $upload[$i]['del_check'] = true;

            // metadata에 있으면 삭제
            if( isset($file_meta_data[$i]['bf_file']) && !empty($file_meta_data[$i]['bf_file']) ){
                $row = $file_meta_data[$i];
                @unlink($g5_data_path.'/file/'.$bo_table.'/'.$row['bf_file']);
                // 썸네일삭제
                if(preg_match("/\.({$config['cf_image_extension']})$/i", $row['bf_file'])) {
                    g5_delete_board_thumbnail($bo_table, $row['bf_file']);
                }
            }
        }
        else
            $upload[$i]['del_check'] = false;

        $tmp_file  = $_FILES['bf_file']['tmp_name'][$i];
        $filesize  = $_FILES['bf_file']['size'][$i];
        $filename  = $_FILES['bf_file']['name'][$i];
        $filename  = g5_get_safe_filename($filename);

        // 서버에 설정된 값보다 큰파일을 업로드 한다면
        if ($filename) {
            if ($_FILES['bf_file']['error'][$i] == 1) {
                $file_upload_msg .= '\"'.$filename.'\" 파일의 용량이 서버에 설정('.$upload_max_filesize.')된 값보다 크므로 업로드 할 수 없습니다.\\n';
                continue;
            }
            else if ($_FILES['bf_file']['error'][$i] != 0) {
                $file_upload_msg .= '\"'.$filename.'\" 파일이 정상적으로 업로드 되지 않았습니다.\\n';
                continue;
            }
        }

        if (is_uploaded_file($tmp_file)) {
            // 관리자가 아니면서 설정한 업로드 사이즈보다 크다면 건너뜀
            if (!$is_admin && $filesize > $board['bo_upload_size']) {
                $file_upload_msg .= '\"'.$filename.'\" 파일의 용량('.number_format($filesize).' 바이트)이 게시판에 설정('.number_format($board['bo_upload_size']).' 바이트)된 값보다 크므로 업로드 하지 않습니다.\\n';
                continue;
            }

            //=================================================================\
            // 090714
            // 이미지나 플래시 파일에 악성코드를 심어 업로드 하는 경우를 방지
            // 에러메세지는 출력하지 않는다.
            //-----------------------------------------------------------------
            $timg = @getimagesize($tmp_file);
            // image type
            if ( preg_match("/\.({$config['cf_image_extension']})$/i", $filename) ||
                 preg_match("/\.({$config['cf_flash_extension']})$/i", $filename) ) {
                if ($timg['2'] < 1 || $timg['2'] > 16)
                    continue;
            }
            //=================================================================

            $upload[$i]['image'] = $timg;

            // 4.00.11 - 글답변에서 파일 업로드시 원글의 파일이 삭제되는 오류를 수정
            if ($w == 'u') {
                // 존재하는 파일이 있다면 삭제합니다.
                // metadata에 있으면 삭제
                if( isset($file_meta_data[$i]['bf_file']) && !empty($file_meta_data[$i]['bf_file']) ){
                    $row = $file_meta_data[$i];
                    @unlink($g5_data_path.'/file/'.$bo_table.'/'.$row['bf_file']);
                    // 이미지파일이면 썸네일삭제
                    if(preg_match("/\.({$config['cf_image_extension']})$/i", $row['bf_file'])) {
                        g5_delete_board_thumbnail($bo_table, $row['bf_file']);
                    }
                }
            }

            // 프로그램 원래 파일명
            $upload[$i]['source'] = $filename;
            $upload[$i]['filesize'] = $filesize;

            // 아래의 문자열이 들어간 파일은 -x 를 붙여서 웹경로를 알더라도 실행을 하지 못하도록 함
            $filename = preg_replace("/\.(php|phtm|htm|cgi|pl|exe|jsp|asp|inc)/i", "$0-x", $filename);

            shuffle($chars_array);
            $shuffle = implode('', $chars_array);

            // 첨부파일 첨부시 첨부파일명에 공백이 포함되어 있으면 일부 PC에서 보이지 않거나 다운로드 되지 않는 현상이 있습니다. (길상여의 님 090925)
            $upload[$i]['file'] = abs(ip2long($_SERVER['REMOTE_ADDR'])).'_'.substr($shuffle,0,8).'_'.str_replace('%', '', urlencode(str_replace(' ', '_', $filename)));

            $dest_file = $g5_data_path.'/file/'.$bo_table.'/'.$upload[$i]['file'];

            // 업로드가 안된다면 에러메세지 출력하고 죽어버립니다.
            $error_code = move_uploaded_file($tmp_file, $dest_file) or die($_FILES['bf_file']['error'][$i]);

            // 올라간 파일의 퍼미션을 변경합니다.
            chmod($dest_file, G5_FILE_PERMISSION);
        }
    }

    // 나중에 테이블에 저장하는 이유는 $wr_id 값을 저장해야 하기 때문입니다.
    for ($i=0; $i<count($upload); $i++)
    {
        if (!get_magic_quotes_gpc()) {
            $upload[$i]['source'] = addslashes($upload[$i]['source']);
        }

        $bf_file_content = isset($bf_content[$i]) ? $bf_content[$i] : '';
        if( isset($file_meta_data[$i]) && !empty($file_meta_data[$i]) ) //데이터가 있다면 데이터 인덱스 내용 수정
        {
            // 삭제에 체크가 있거나 파일이 있다면 업데이트를 합니다.
            // 그렇지 않다면 내용만 업데이트 합니다.
            if ($upload[$i]['del_check'] || $upload[$i]['file'])
            {
                if($tmp_data = g5_get_file_data($bo_table, $wr_id, $i, $upload[$i] , $bf_file_content)){
                    $file_meta_data[$i] = $tmp_data;
                }
            }
            else
            {
                $file_meta_data[$i]['bf_content'] = $bf_content[$i];
            }
        }
        else
        {
            if($tmp_data = g5_get_file_data($bo_table, $wr_id, $i, $upload[$i] , $bf_file_content)){
                $file_meta_data[$i] = $tmp_data;
            }
        }
    }

    $upload_count = 0;
    // 파일이 있고, array 형식이면
    if( is_array($file_meta_data) && $upload_count = count($file_meta_data) ){

        // 업로드된 파일 내용에서 가장 큰 번호를 얻어 거꾸로 확인해 가면서
        // 파일 정보가 없다면 배열 key를 삭제합니다.
        $max_bf_no = max(array_keys($file_meta_data));

        for ($i=$max_bf_no; $i>=0; $i--)
        {
            // 정보가 있다면 빠집니다.
            if( isset($file_meta_data[$i]['bf_file']) && !empty($file_meta_data[$i]['bf_file']) ){
                break;
            }

            // 그렇지 않다면 정보를 삭제합니다.
            unset( $file_meta_data[$i] );
        }
    }
}

$after_update['wr_file'] = $upload_count;
$after_formats[] = '%d';

if( $old_meta_data !== $file_meta_data ){
    // 변경된 내용이 있다면 메타 데이터를 업데이트 한다.
    if( $file_meta_data ){
        update_metadata( G5_META_TYPE, $wr_id, G5_FILE_META_KEY, $file_meta_data );
    } else {
        // 값이 비워 있다면 삭제한다.
        delete_metadata( G5_META_TYPE, $wr_id, G5_FILE_META_KEY );
    }
}

// 비밀글이라면 세션에 비밀글의 아이디를 저장한다. 자신의 글은 다시 비밀번호를 묻지 않기 위함
if ($secret)
    g5_set_session("ss_secret_{$bo_table}_{$wr_num}", TRUE);

// 메일발송 사용 (수정글은 발송하지 않음)
if (!($w == 'u' || $w == 'cu') && $config['cf_email_use'] && $board['bo_use_email']) {
    
    $mail_subject = g5_get_text(stripslashes($wr_subject));

    $tmp_html = 0;
    if (strstr($html, 'html1'))
        $tmp_html = 1;
    else if (strstr($html, 'html2'))
        $tmp_html = 2;

    $mail_content = g5_conv_content(g5_conv_unescape_nl($wr_content), $tmp_html);

    $warr = array( ''=>'입력', 'u'=>'수정', 'r'=>'답변', 'c'=>'코멘트', 'cu'=>'코멘트 수정' );
    $str = $warr[$w];

    $subject = '['.get_bloginfo( 'name' ).'] '.$board['bo_subject'].' 게시판에 '.$str.'글이 올라왔습니다.';

    $link_url = add_query_arg( array_merge((array) $qstr, array('wr_id'=>$wr_id)), $default_href);

    ob_start();
    include_once (G5_DIR_PATH.'bbs/write_update_mail.php');
    $content = ob_get_contents();
    ob_end_clean();

    $array_email = array();

    // 게시판관리자에게 보내는 메일
    if ($config['cf_email_wr_board_admin']){
        $bo_admins = explode(',' , $board['bo_admin']);

        foreach($bo_admins as $user_id){
            if( empty($user_id) ) continue;

            $tmb = g5_get_memeber($user_id);
            if( isset($tmb['user_email']) ) $array_email[] = $tmb['user_email'];
        }
    }
    // 최고관리자에게 보내는 메일
    if ($config['cf_email_wr_super_admin']) $array_email[] = get_bloginfo('admin_email');

    // 원글게시자에게 보내는 메일
    if ($config['cf_email_wr_write']) {
        if($w == '')
            $wr['user_email'] = $user_email;

        $array_email[] = $wr['user_email'];
    }

    // 옵션에 메일받기가 체크되어 있고, 게시자의 메일이 있다면
    if (isset($wr) && strstr($wr['wr_option'], 'mail') && $wr['user_email'])
        $array_email[] = $wr['user_email'];

    // 중복된 메일 주소는 제거
    $unique_email = array_unique($array_email);
    $unique_email = array_values($unique_email);

    $attachments = apply_filters( 'g5_mail_write_attach', array() );

    $headers = 'From: '.$user_name.' <'.$user_email.'>' . "\r\n";
    
    add_filter( 'wp_mail_content_type', 'g5_set_html_content_type' );
    foreach( $unique_email as $email ){
        if( empty($email) ) continue;
        wp_mail($unique_email, $subject, $content, $headers, $attachments );
    }
    remove_filter( 'wp_mail_content_type', 'g5_set_html_content_type' );
}

//태그 저장
include_once( G5_DIR_PATH.'lib/g5_taxonomy.lib.php' );

$tax_input = array();
$write_taxonomy = g5_get_taxonomy($bo_table);

if ( isset($_POST['wr_tag'])) {
    foreach ( $_POST['wr_tag'] as $tax_name => $terms ) {
        if ( empty($terms) )
            continue;
        if ( is_taxonomy_hierarchical( $tax_name ) ) {
            $tax_input[ $tax_name ] = array_map( 'absint', $terms );
        } else {
            $comma = _x( ',', 'tag delimiter' );
            if ( ',' !== $comma )
                $terms = str_replace( $comma, ',', $terms );
            $tax_input[ $tax_name ] = explode( ',', trim( $terms, " \n\t\r\0\x0B," ) );
        }
    }
}

if ( ! empty( $tax_input ) ) {
    foreach ( $tax_input as $tags ) {
        if ( is_array( $tags ) ) {
            $tags = array_filter($tags);
        }
        $tag_ids = g5_set_post_terms( $wr_id, $tags, $write_taxonomy, false, 'term_id' );
    }

    if( count( $tag_ids ) ){
        $after_update['wr_tag'] = implode(',' ,$tag_ids);
        $after_formats[] = '%s';
    }
}

$where = array( 'wr_id' => $wr_id );
$where_format = array( '%d' );
$result = $wpdb->update($write_table, $after_update, $where, $after_formats, $where_format);

if ( $result === false ){
    g5_show_db_error();
}

// 사용자 코드 실행
@include_once($board_skin_path.'/write_update.skin.php');
@include_once($board_skin_path.'/write_update.tail.skin.php');

g5_delete_cache_latest($bo_table);
wp_cache_delete( 'g5_bo_table_'.$bo_table );
wp_cache_delete( 'g5_'.$g5['write_table'].'_'.$wr_id );

$redirect_to = apply_filters( 'write_update_move_url', add_query_arg( array( 'wr_id'=>$wr_id ), $default_href ) );

do_action( 'write_update_move_url', $redirect_to , $board, $g5_data, $action );

if ($file_upload_msg)
    g5_alert($file_upload_msg, $redirect_to);
else
    wp_safe_redirect( $redirect_to );

exit;
?>