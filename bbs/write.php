<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once(G5_PLUGIN_PATH.'/kcaptcha/captcha.lib.php');

$notice_array = explode(',', trim($board['bo_notice']));

if (!($w == '' || $w == 'u' || $w == 'r')) {
    $this->errors[] = __('w value is invalid.', G5_NAME);    //w값이 유효하지 않습니다.
    return;
}

if ($w == 'u' || $w == 'r') {
    if ( !$write['wr_id'] ){
        $this->errors[] = __('This article does not exist.\\n\\nThe case has been moved or deleted.', G5_NAME);  //글이 존재하지 않습니다.\\n\\n삭제되었거나 이동된 경우입니다.
        return;
    }
}

//글쓰기이면
if ($w == '') {
    if ($wr_id) {
        $this->errors[] = array(__('The writing does not use the \$wr_id value.', G5_NAME), $default_href ); //글쓰기에는 \$wr_id 값을 사용하지 않습니다.
        return;
    }

    //글을 쓸 권한이 없다면 
    if ($member['user_level'] < $board['bo_write_level']) {
        if ($member['user_id']) {
            $this->errors[] = __('You do not have permission to write.', G5_NAME);  //글을 쓸 권한이 없습니다.
        } else {
            $this->errors[] = array( __("You do not have permission to write.\\nMember Login if you are after, try using." , G5_NAME), wp_login_url($default_href) );     //글을 쓸 권한이 없습니다.\\n회원이시라면 로그인 후 이용해 보십시오.
        }
        return;
    }

    //회원인 경우
    if ($is_member) {
        $tmp_point = ($member['mb_point'] > 0) ? $member['mb_point'] : 0;
        //관리자가 아니고 && 포인트가 부족하면
        if ($tmp_point + $board['bo_write_point'] < 0 && !$is_admin) {
            $this->errors[] = sprintf(__('Because your point is %s points less or missing, not write ( %s points required ) the article.\n\nAfter a point collect, please write article again.', G5_NAME), number_format($member['mb_point']), number_format($board['bo_write_point']));
            return;
        }
    }
    $title_msg = __('Write', G5_NAME);

} else if ($w == 'u') {

    if($member['user_id'] && $write['user_id'] == $member['user_id']) {
        ;
    } else if ($member['user_level'] < $board['bo_write_level']) {
        if ($member['user_id']) {
            $this->errors[] = __('You do not have permission to edit.', G5_NAME);
        } else {
            $this->errors[] = array( __('You do not have permission to edit.\\nMember Login if you are after, try using.', G5_NAME), wp_login_url($default_href) );   //글을 수정할 권한이 없습니다.\\n\\n회원이시라면 로그인 후 이용해 보십시오.
        }
        return;
    }

    // 원글의 답변글이 있는지를 검사한다.
    $row_cnt = $wpdb->get_var($wpdb->prepare(" select count(*) as cnt from {$write_table} where wr_parent = %d ", $wr_id));

    if ($row_cnt && !$is_admin){
        $this->errors[] = __('Because replies are present related to this article then it can not be modified.', G5_NAME);   //이 글과 관련된 답변글이 존재하므로 수정 할 수 없습니다.
        return;
    }

    // 코멘트 달린 원글의 수정 여부
    $row_cnt = $wpdb->get_var($wpdb->prepare(" select count(*) as cnt from `{$g5['comment_table']}` where wr_id = %d and user_id <> '{$member['user_id']}' ", $wr_id));

    if ($board['bo_count_modify'] && $row_cnt >= $board['bo_count_modify'] && !$is_admin){
        $this->errors[] = sprintf(__('Because there are comments associated with this article it can not be modified.\\n\\nComments are at least %d cases with a post can not be modified.', G5_NAME), $board['bo_count_modify']); //이 글과 관련된 댓글이 존재하므로 수정 할 수 없습니다.\\n\\n댓글이 %d건 이상 달린 원글은 수정할 수 없습니다.
        return;
    }

    $title_msg = '글수정';
} else if ($w == 'r') {
    if ($member['user_level'] < $board['bo_reply_level']) {
        if ($member['user_id'])
            $this->errors[] = __('You do not have permission to reply to posts.', G5_NAME);     //글을 답변할 권한이 없습니다.
        else
            $this->errors[] = array( __('You do not have permission to reply to posts.\\n\\nMember Login if you are after, try using.', G5_NAME), wp_login_url(add_query_arg((array) $qstr,$default_href)));     //답변글을 작성할 권한이 없습니다.\\n\\n회원이시라면 로그인 후 이용해 보십시오.

        return;
    }

    $tmp_point = isset($member['mb_point']) ? $member['mb_point'] : 0;

    if ($tmp_point + $board['bo_write_point'] < 0 && !$is_admin){
        g5_alert( sprintf(__('Because your point is %s points less or missing, not reply ( %s points required ) the article.\\n\\nAfter a point collect, please reply article again.', G5_NAME), number_format($member['mb_point']), number_format($board['bo_comment_point'])) );
    }
    if (in_array((int)$wr_id, $notice_array))
        g5_alert(__('Notice can not be answered.', G5_NAME));  //공지에는 답변 할 수 없습니다.

    // 비밀글인지를 검사
    if (strstr($write['wr_option'], 'secret')) {
        if ($write['user_id']) {
            // 회원의 경우는 해당 글쓴 회원 및 관리자
            if (!($write['user_id'] == $member['user_id'] || $is_admin))
                g5_alert(__('Secret, you can answer your own or managers.', G5_NAME));   //비밀글에는 자신 또는 관리자만 답변이 가능합니다.
        } else {
            // 비회원의 경우는 비밀글에 답변이 불가함
            if (!$is_admin)
                g5_alert(__('Guest of the Secret must not have answers.', G5_NAME)); //비회원의 비밀글에는 답변이 불가합니다.
        }
    }
    //----------

    $title_msg = __('Reply', G5_NAME);

    $write['wr_subject'] = 'Re: '.$write['wr_subject'];
}

// 글자수 제한 설정값
if ($is_admin || $board['bo_use_dhtml_editor'])
{
    $write_min = $write_max = 0;
}
else
{
    $write_min = (int)$board['bo_write_min'];
    $write_max = (int)$board['bo_write_max'];
}

$g5['title'] = $board['bo_subject']." ".$title_msg;

$is_notice = false;
$notice_checked = '';
if ($is_admin && $w != 'r') {
    $is_notice = true;

    if ($w == 'u') {
        // 답변 수정시 공지 체크 없음
        if ($write['wr_parent']) {
            if (in_array((int)$wr_id, $notice_array)) {
                $notice_checked = 'checked';
            }
        }
    }
}

$is_html = true;

$is_secret = $board['bo_use_secret'];

$is_mail = false;
if ($config['cf_email_use'] && $board['bo_use_email'])
    $is_mail = true;

$recv_email_checked = '';
if ($w == '' || strstr($write['wr_option'], 'mail'))
    $recv_email_checked = 'checked';

$is_name     = false;
$is_password = false;
$is_email    = false;
//$is_homepage = false;

if ($is_guest || ($is_admin && $w == 'u' && $member['user_id'] != $write['user_id'])) {
    $is_name = true;
    $is_password = true;
    $is_email = true;
    //$is_homepage = true;
}

$is_category = false;
$category_option = '';

if ($board['bo_use_category']) {
    $ca_name = "";
    if (isset($write['ca_name']))
        $ca_name = $write['ca_name'];
    $category_option = g5_get_category_option($board, $is_admin, $ca_name);
    $is_category = true;
}

$is_link = false;
if ($member['user_level'] >= $board['bo_link_level']) {
    $is_link = true;
}

$is_file = false;
if ($member['user_level'] >= $board['bo_upload_level']) {
    $is_file = true;
}

$is_file_content = false;
if ($board['bo_use_file_content']) {
    $is_file_content = true;
}

$file_count = (int)$board['bo_upload_count'];

$name     = "";
$email    = "";
$homepage = "";
if ($w == "" || $w == "r") {
    if ($is_member) {
        if (isset($write['user_display_name'])) {
            $name = g5_get_text(g5_cut_str(stripslashes($write['user_display_name']),20));
        }
        $email = g5_get_email_address($member['user_email']);
    }
}

$html_checked   = "";
$html_value     = "";
$secret_checked = "";

if ($w == '') {
    $password_required = 'required';
} else if ($w == 'u') {
    $password_required = '';

    if (!$is_admin) {
        if (!($is_member && $member['user_id'] == $write['user_id'])) {
            if (g5_sql_password(trim($_POST['user_pass'])) != $write['user_pass']) {
                g5_alert(__('Incorrect password.', G5_NAME));   //비밀번호가 틀립니다.
            }
        }
    }

    $name = g5_get_text(g5_cut_str(stripslashes($write['user_display_name']),20));
    $email = g5_get_email_address($write['user_email']);

    for ($i=1; $i<=G5_LINK_COUNT; $i++) {
        $write['wr_link'.$i] = g5_get_text($write['wr_link'.$i]);
        $link[$i] = $write['wr_link'.$i];
    }

    if (strstr($write['wr_option'], 'html1')) {
        $html_checked = 'checked';
        $html_value = 'html1';
    } else if (strstr($write['wr_option'], 'html2')) {
        $html_checked = 'checked';
        $html_value = 'html2';
    }

    if (strstr($write['wr_option'], 'secret')) {
        $secret_checked = 'checked';
    }

    $file = g5_get_file($board, $wr_id, $qstr, $default_href);

} else if ($w == 'r') {
    if (strstr($write['wr_option'], 'secret')) {
        $is_secret = true;
        $secret_checked = 'checked';
    }

    $password_required = "required";

    for ($i=1; $i<=G5_LINK_COUNT; $i++) {
        $write['wr_link'.$i] = g5_get_text($write['wr_link'.$i]);
    }
}

/*  //되도록이면 nonce로 대체할 예정
if( isset($bo_table) )
    g5_set_session('ss_bo_table', $bo_table);

if( isset($wr_id) )
    g5_set_session('ss_wr_id', $wr_id);
*/

$subject = "";
if (isset($write['wr_subject'])) {
    $subject = str_replace("\"", "&#034;", g5_get_text(g5_cut_str($write['wr_subject'], 255), 0));
}

$content = '';
if ($w == '') {
    $content = $board['bo_insert_content'];
} else if ($w == 'r') {
    if (!strstr($write['wr_option'], 'html')) {
        $content = "\n\n\n &gt; "
                 ."\n &gt; "
                 ."\n &gt; ".str_replace("\n", "\n> ", g5_get_text($write['wr_content'], 0))
                 ."\n &gt; "
                 ."\n &gt; ";

    }
} else {
    $content = $write['wr_content'];
    
}

$upload_max_filesize = number_format($board['bo_upload_size']) . ' Bytes';

$width = $board['bo_table_width'];
if ($width <= 100)
    $width .= '%';
else
    $width .= 'px';


$captcha_html = '';
$captcha_js   = '';
if ($is_guest) {
    $captcha_html = g5_captcha_html();
    $captcha_js   = g5_chk_captcha_js();
}

$is_dhtml_editor = false;

if ($config['cf_editor'] && $board['bo_use_dhtml_editor']) {
    $is_dhtml_editor = true;
}

$is_use_tag = false;
//게시판에서 태그 설정이 되어 있다면, 답변이 아닌 원글에만 태그 적용
if($board['bo_use_tag'] && !(isset($qa_write['wr_parent']) && $qa_write['wr_parent']) && ($w == "" || $w == "u") ){
    $is_use_tag = true;
}
$string_wr_tags = '';
//글을 수정시 태그가 있다면
if( $w == 'u' && isset($write['wr_tag']) && !empty($write['wr_tag']) ){
    $wr_tags = explode(",", $write['wr_tag']);
    $tmp_array = array();
    if( !empty($wr_tags) && count($wr_tags) ){
        foreach( $wr_tags as $tag_id ){
            $term = g5_get_tag_info($tag_id, $bo_table);
            if( isset($term['name']) && !empty($term['name']) ){
                $tmp_array[] = $term['name'];
            }
        }

        $string_wr_tags = implode(',' , $tmp_array);
    }
}

$editor_html = g5_editor_html('wr_content', $content, $is_dhtml_editor);
$editor_js = '';
$editor_js .= g5_get_editor_js('wr_content', $is_dhtml_editor);
$editor_js .= g5_chk_editor_js('wr_content', $is_dhtml_editor);

@include_once ($board_skin_path.'/write.head.skin.php');

$action_url = apply_filters('g5_write_action_url', get_permalink() );

include_once ($board_skin_path.'/write.skin.php');

@include_once ($board_skin_path.'/write.tail.skin.php');
?>