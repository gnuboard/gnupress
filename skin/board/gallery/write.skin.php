<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
if($board['bo_use_tag'])    //게시판 설정에서 태그 기능을 사용한다면
    wp_enqueue_script( $bo_table.'-view-skin-js', $board_skin_url.'/js/write.tag.it.js' );
?>

<section id="bo_w">
    <h2 id="container_title"><?php echo $g5['title'] ?></h2>

    <!-- 게시물 작성/수정 시작 { -->
    <form name="fwrite" id="fwrite" action="<?php echo $action_url ?>" method="post" enctype="multipart/form-data" autocomplete="off" style="width:<?php echo $width; ?>" onsubmit="return gnupress.fwrite_submit(this);">
    <?php wp_nonce_field( 'g5_write', 'g5_nonce_field' ); ?>
    <input type="hidden" name="w" value="<?php echo esc_attr( $w ); ?>">
    <input type="hidden" name="action" value="write_update">
    <input type="hidden" name="bo_table" value="<?php echo esc_attr( $bo_table ); ?>">
    <input type="hidden" name="wr_id" value="<?php echo esc_attr( intval($wr_id) ); ?>">
    <input type="hidden" name="sca" value="<?php echo esc_attr( $sca ); ?>">
    <input type="hidden" name="sfl" value="<?php echo esc_attr( $sfl ); ?>">
    <input type="hidden" name="stx" value="<?php echo esc_attr( $stx ); ?>">
    <input type="hidden" name="spt" value="<?php echo esc_attr( $spt ); ?>">
    <input type="hidden" name="sst" value="<?php echo esc_attr( $sst ); ?>">
    <input type="hidden" name="sod" value="<?php echo esc_attr( $sod ); ?>">
    <input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>">
    <input type="hidden" name="page_id" value="<?php echo get_the_ID(); ?>">
    <?php
    $option = '';
    $option_hidden = '';
    if ($is_notice || $is_html || $is_secret || $is_mail) {
        $option = '';
        if ($is_notice) {
            $option .= "\n".'<input type="checkbox" id="notice" name="notice" value="1" '.$notice_checked.'>'."\n".'<label for="notice">'.__('notice', 'gnupress').'</label>';
        }

        if ($is_html) {
            if ($is_dhtml_editor) {
                $option_hidden .= '<input type="hidden" value="html1" name="html">';
                $option_hidden .= "\n".'<input type="hidden" value="wp_html" name="wp_html">';
            } else {
                $option .= "\n".'<input type="checkbox" id="html" name="html" onclick="html_auto_br(this);" value="'.$html_value.'" '.$html_checked.'>'."\n".'<label for="html">html</label>';
            }
        }

        if ($is_secret) {
            if ($is_admin || $is_secret==1) {
                $option .= "\n".'<input type="checkbox" id="secret" name="secret" value="secret" '.$secret_checked.'>'."\n".'<label for="secret">'.__('secret').'</label>';
            } else {
                $option_hidden .= '<input type="hidden" name="secret" value="secret">';
            }
        }

        if ($is_mail) {
            $option .= "\n".'<input type="checkbox" id="mail" name="mail" value="mail" '.$recv_email_checked.'>'."\n".'<label for="mail">'.__('Receive e-mail reply', 'gnupress').'</label>';
        }
    }

    echo $option_hidden;
    ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <tbody>
        <?php if ($is_name) { ?>
        <tr>
            <th scope="row"><label for="user_name"><?php _e('Name', 'gnupress');?><strong class="sound_only"><?php _e('required', 'gnupress');?></strong></label></th>
            <td><input type="text" name="user_name" value="<?php echo esc_attr( $name ); ?>" id="user_name" required class="frm_input required" size="10" maxlength="20"></td>
        </tr>
        <?php } ?>

        <?php if ($is_password) { ?>
        <tr>
            <th scope="row"><label for="user_pass"><?php _e('Password', 'gnupress');?><strong class="sound_only"><?php _e('required', 'gnupress');?></strong></label></th>
            <td><input type="password" name="user_pass" id="user_pass" <?php echo $password_required ?> class="frm_input <?php echo $password_required ?>" maxlength="20"></td>
        </tr>
        <?php } ?>

        <?php if ($is_email) { ?>
        <tr>
            <th scope="row"><label for="user_email"><?php _e('Email', 'gnupress');?></label></th>
            <td><input type="text" name="user_email" value="<?php echo esc_attr( $email ); ?>" id="user_email" class="frm_input email" size="50" maxlength="100"></td>
        </tr>
        <?php } ?>

        <?php if ($option) { ?>
        <tr>
            <th scope="row"><?php _e('Option', 'gnupress');?></th>
            <td><?php echo $option ?></td>
        </tr>
        <?php } ?>

        <?php if ($is_category) { ?>
        <tr>
            <th scope="row"><label for="ca_name"><?php _e('Category', 'gnupress');?><strong class="sound_only"><?php _e('required', 'gnupress');?></strong></label></th>
            <td>
                <select name="ca_name" id="ca_name" required class="required" >
                    <option value=""><?php _e('— Select —', 'gnupress');?></option>
                    <?php echo $category_option ?>
                </select>
            </td>
        </tr>
        <?php } ?>

        <tr>
            <th scope="row"><label for="wr_subject"><?php _e('Subject', 'gnupress');?><strong class="sound_only"><?php _e('required', 'gnupress');?></strong></label></th>
            <td>
                <div id="autosave_wrapper">
                    <input type="text" name="wr_subject" value="<?php echo esc_attr( $subject ); ?>" id="wr_subject" required class="frm_input required" size="50" maxlength="255">
                </div>
            </td>
        </tr>

        <tr>
            <td class="wr_content" colspan="2">
                <label for="wr_content" class="block_label"><strong><?php _e('Content', 'gnupress');?></strong><strong class="sound_only"><?php _e('required', 'gnupress');?></strong></label>
                <?php if($write_min || $write_max) { ?>
                <!-- 최소/최대 글자 수 사용 시 -->
                <p id="char_count_desc"><?php echo sprintf(__('This board is write at least %s characters long, posts up to %s characters or less.', 'gnupress'), '<strong>'.$write_min.'</strong>', '<strong>'.$write_max.'</strong>');?></p>
                <?php } ?>
                <?php echo $editor_html; // 에디터 사용시는 에디터로, 아니면 textarea 로 노출 ?>
                <?php if($write_min || $write_max) { ?>
                <!-- 최소/최대 글자 수 사용 시 -->
                <div id="char_count_wrap"><span id="char_count"></span><?php _e('Length', 'gnupress');?></div>
                <?php } ?>
            </td>
        </tr>

        <?php for ($i=1; $is_link && $i<=G5_LINK_COUNT; $i++) { ?>
        <tr>
            <th scope="row"><label for="wr_link<?php echo $i ?>"><?php _e('Link', 'gnupress');?> #<?php echo $i ?></label></th>
            <td><input type="text" name="wr_link<?php echo $i ?>" value="<?php if($w=="u"){echo$write['wr_link'.$i];} ?>" id="wr_link<?php echo $i ?>" class="frm_input" size="50"></td>
        </tr>
        <?php } ?>

        <?php for ($i=0; $is_file && $i<$file_count; $i++) { ?>
        <tr>
            <th scope="row"><?php _e('File', 'gnupress');?> #<?php echo $i+1 ?></th>
            <td>
                <input type="file" name="bf_file[]" title="<?php _e('Attachments', 'gnupress');?> <?php echo $i+1 ?> : <?php echo sprintf(__('%s bytes or less capacity can be uploaded', 'gnupress'), $upload_max_filesize);?>" class="frm_file frm_input">
                <?php if ($is_file_content) { ?>
                <input type="text" name="bf_content[]" value="<?php echo ($w == 'u' && isset($file[$i]['bf_content'])) ? $file[$i]['bf_content'] : ''; ?>" title="<?php _e('enter a file description.' , 'gnupress');?>" class="frm_file frm_input" size="50">
                <?php } ?>
                <?php if($w == 'u' && isset($file[$i]['file']) ) { ?>
                <input type="checkbox" id="bf_file_del<?php echo $i ?>" name="bf_file_del[<?php echo $i;  ?>]" value="1"> <label for="bf_file_del<?php echo $i ?>"><?php echo $file[$i]['source'].'('.$file[$i]['size'].')';  ?> <?php _e('Delete File', 'gnupress');?></label>
                <?php } ?>
            </td>
        </tr>
        <?php } ?>

        <?php if ($is_guest) { //자동등록방지  ?>
        <tr>
            <th scope="row"><?php _e('Captcha', 'gnupress');?></th>
            <td>
                <?php echo $captcha_html ?>
            </td>
        </tr>
        <?php } ?>

        </tbody>
        </table>
    </div>

	<?php if($is_use_tag){ // tag를 사용한다면 ( 원글을 쓸때만 가능 ) ?>
    <div id="tagsdiv-post_tag" class="postbox">
        <h3><span><?php _e('Tags'); ?></span></h3>
        <div class="inside">
            <div class="tagsdiv" id="post_tag">
                <div class="jaxtag">
                    <label class="screen-reader-text" for="newtag"><?php _e('Tags'); ?></label>
                    <input type="text" name="wr_tag[post_tag]" class="the-tags" id="wr_tag_input" value="<?php echo $string_wr_tags ?>" />
                    <ul id="g5_singleFieldTags" class="qa_tag_el"></ul>
                </div>
            </div>
        </div>
    </div>
	<?php } ?>

    <div class="btn_confirm">
        <input type="submit" value="<?php _e('Submit', 'gnupress')?>" id="btn_submit" accesskey="s" class="btn_submit">
        <a href="<?php echo esc_url( $default_href ); ?>" class="btn_cancel"><?php _e('Cancel', 'gnupress')?></a>
    </div>
    </form>
</section>
<!-- } 게시물 작성/수정 끝 -->

<script>
<?php if($write_min || $write_max) { ?>
// 글자수 제한
var char_min = parseInt(<?php echo $write_min; ?>); // 최소
var char_max = parseInt(<?php echo $write_max; ?>); // 최대
check_byte("wr_content", "char_count");

jQuery(function($) {
    $("#wr_content").on("keyup", function() {
        check_byte("wr_content", "char_count");
    });
});

<?php } ?>
function html_auto_br(obj)
{
    if (obj.checked) {
        result = confirm("<?php _e('Enable wrap it?', 'gnupress');?>\n\n<?php _e('Word wrap is a feature that converts contents of the posts where the line changes to <br> tags', 'gnupress');?>");
        if (result)
            obj.value = "html2";
        else
            obj.value = "html1";
    }
    else
        obj.value = "";
}

jQuery(function($){
    gnupress.fwrite_submit = function(f)
    {
            <?php echo $editor_js; // 에디터 사용시 자바스크립트에서 내용을 폼필드로 넣어주며 내용이 입력되었는지 검사함   ?>
            
            var subject = "";
            var content = "";
            $.ajax({
                url: gnupress.ajax_url,
                type: "POST",
                data: {
                    "action": "g5_bss_filter",
                    "subject": f.wr_subject.value,
                    "content": f.wr_content.value
                },
                dataType: "json",
                async: false,
                cache: false,
                success: function(data, textStatus) {
                    subject = data.subject;
                    content = data.content;
                }
            });

            if (subject) {
                alert( gnupress.sprintf("<?php _e('It contains a banned word in the subject %s', 'gnupress');?>", subject) );
                f.wr_subject.focus();
                return false;
            }

            if (content) {
                alert( gnupress.sprintf("<?php _e('It contains a banned word in the content %s', 'gnupress');?>", content) );
                if (typeof(ed_wr_content) != "undefined")
                    ed_wr_content.returnFalse();
                else
                    f.wr_content.focus();
                return false;
            }

            if (document.getElementById("char_count")) {
                if (char_min > 0 || char_max > 0) {
                    var cnt = parseInt(check_byte("wr_content", "char_count"));
                    if (char_min > 0 && char_min > cnt) {
						alert( gnupress.sprintf("<?php _e('Contents, you must write at least %d words.', 'gnupress');?>", char_min) );
                        return false;
                    }
                    else if (char_max > 0 && char_max < cnt) {
						alert( gnupress.sprintf("<?php _e('Contents, you must write in %d words or less.', 'gnupress');?>", char_max) );
                        return false;
                    }
                }
            }

            <?php echo $captcha_js; // 캡챠 사용시 자바스크립트에서 입력된 캡챠를 검사함  ?>
            
            document.getElementById("btn_submit").disabled = "disabled";
            return true;
    }
});
</script>