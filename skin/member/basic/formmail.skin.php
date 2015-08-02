<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
?>

<!-- 폼메일 시작 { -->
<div id="formmail" class="new_win mbskin">
    <h1 id="win_title"><?php echo $user_name ?><?php _e(' - Send Email', 'gnupress');?></h1>

    <form name="fformmail" action="<?php echo g5_form_action_url(add_query_arg(array()));?>" onsubmit="return fformmail_submit(this);" method="post" enctype="multipart/form-data" style="margin:0px;">
    <?php wp_nonce_field( 'g5_formmail', 'g5_nonce_field' ); ?>
    <input type="hidden" name="gaction" value="form_mail_update">
    <input type="hidden" name="to" value="<?php echo esc_attr( $email ); ?>">
    <input type="hidden" name="attach" value="2">
    <?php if ($is_member) { // 회원이면  ?>
        <input type="hidden" name="fnick" value="<?php echo esc_attr( $member['user_display_name'] ); ?>">
        <input type="hidden" name="fmail" value="<?php echo esc_attr( $member['user_email'] ); ?>">
    <?php }  ?>

    <div class="tbl_frm01 tbl_form">
        <table>
        <caption>메일쓰기</caption>
        <tbody>
        <?php if (!$is_member) {  ?>
        <tr>
            <th scope="row"><label for="fnick"><?php _e('Name', 'gnupress');?><strong class="sound_only"><?php _e('required', 'gnupress');?></strong></label></th>
            <td><input type="text" name="fnick" id="fnick" required class="frm_input required"></td>
        </tr>
        <tr>
            <th scope="row"><label for="fmail">E-mail<strong class="sound_only"><?php _e('required', 'gnupress');?></strong></label></th>
            <td><input type="text" name="fmail"  id="fmail" required class="frm_input required"></td>
        </tr>
        <?php }  ?>
        <tr>
            <th scope="row"><label for="subject"><?php _e('Subject', 'gnupress');?><strong class="sound_only"><?php _e('required', 'gnupress');?></strong></label></th>
            <td><input type="text" name="subject" id="subject" required class="frm_input required"></td>
        </tr>
        <tr>
            <th scope="row">형식</th>
            <td>
                <input type="radio" name="type" value="0" id="type_text" checked> <label for="type_text">TEXT</label>
                <input type="radio" name="type" value="1" id="type_html"> <label for="type_html">HTML</label>
                <input type="radio" name="type" value="2" id="type_both"> <label for="type_both">TEXT+HTML</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="content"><?php _e('Content', 'gnupress');?><strong class="sound_only"><?php _e('required', 'gnupress');?></strong></label></th>
            <td><textarea name="content" id="content" required class="required"></textarea></td>
        </tr>
        <tr>
            <th scope="row"><label for="file1"><?php _e('Attachments', 'gnupress');?> 1</label></th>
            <td>
                <input type="file" name="file1" id="file1"  class="frm_input">
				<?php _e('Please make sure that the attachments may be missing after sending the email file attachment.', 'gnupress');	//첨부 파일은 누락될 수 있으므로 메일을 보낸 후 파일이 첨부 되었는지 반드시 확인해 주시기 바랍니다.?>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="file2"><?php _e('Attachments', 'gnupress');?> 2</label></th>
            <td><input type="file" name="file2" id="file2" class="frm_input"></td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Captcha', 'gnupress');?></th>
            <td><?php echo g5_captcha_html(); ?></td>
        </tr>
        </tbody>
        </table>
    </div>

    <div class="win_btn">
        <input type="submit" value="<?php _e('Send Email', 'gnupress');?>" id="btn_submit" class="btn_submit">
        <button type="button" onclick="window.close();"><?php _e('Close', 'gnupress');?></button>
    </div>

    </form>
</div>

<script>
with (document.fformmail) {
    if (typeof fname != "undefined")
        fname.focus();
    else if (typeof subject != "undefined")
        subject.focus();
}

function fformmail_submit(f)
{
    <?php echo g5_chk_captcha_js();  ?>

    if (f.file1.value || f.file2.value) {
        // 4.00.11
        if (!confirm("<?php _e('If the capacity of the attachment larger the longer the transmission time required.', 'gnupress');?>\n\n<?php _e('Email close the window before it finishes Do not refresh.', 'gnupress');?>"))
            return false;
    }

    document.getElementById('btn_submit').disabled = true;

    return true;
}
</script>
<!-- } 폼메일 끝 -->