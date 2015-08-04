<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$home_url = home_url();

$pg_anchor = '<ul class="anchor">
    <li><a href="#anc_cf_basic">'.__('Default', 'gnupress').'</a></li>
    <li><a href="#anc_cf_board">'.__('BBS default', 'gnupress').'</a></li>
    <li><a href="#anc_cf_mail">'.__('Mail setting', 'gnupress').'</a></li>
    <li><a href="#anc_cf_article_mail">'.__('Email posts', 'gnupress').'</a></li>
</ul>';

$frm_submit = '<div class="btn_confirm01 btn_confirm">
    <input type="submit" value="'.__('Submit', 'gnupress').'" class="btn btn-primary" accesskey="s">
    <a href="'.$home_url.'/" class="btn btn-info">'.__('Go Main', 'gnupress').'</a>
</div>';
?>

<form name="fconfigform" id="fconfigform" method="post" action="<?php echo g5_form_action_url(admin_url("admin.php?page=g5_board_admin")); ?>" onsubmit="return fconfigform_submit(this);" class="bootstrap">
<?php wp_nonce_field( 'g5_config_form_check' ); ?>
<input type="hidden" name="g5_config_form" value="update" >

<section id="anc_cf_basic" class="g5_section_top">
    <h2 class="h2_frm"><?php _e('Site default setting', 'gnupress');?></h2>
    <?php echo $pg_anchor ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption><?php _e('Site default setting', 'gnupress');?></caption>
        <colgroup>
            <col class="grid_4">
            <col>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><?php _e('Current Version', 'gnupress'); ?></th>
            <td colspan="3">
                <?php echo $g5_options['version']; ?>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_use_point"><?php _e('Using Points', 'gnupress' );?></label></th>
            <td colspan="3"><input type="checkbox" name="cf_use_point" value="1" id="cf_use_point" <?php echo $config['cf_use_point']?'checked':''; ?>> <label for="cf_use_point"><?php _e('use', 'gnupress' );?></label></td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_login_point"><?php _e('Login point', 'gnupress' );?><strong class="sound_only"><?php _e('required', 'gnupress' ); ?></strong></label></th>
            <td colspan="3">
                <?php echo g5_help(__('Members saving point only once a day at login', 'gnupress')); //회원이 로그인시 하루에 한번만 적립 ?>
                <input type="text" name="cf_login_point" value="<?php echo $config['cf_login_point'] ?>" id="cf_login_point" required class="required frm_input" size="2"> <?php _e('point', 'gnupress'); ?>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="cf_cut_name"><?php _e('Display Name', 'gnupress');?></label></th>
            <td colspan="3">
                <input type="text" name="cf_cut_name" value="<?php echo $config['cf_cut_name'] ?>" id="cf_cut_name" class="frm_input" size="5"> <?php _e('Display Cut string', 'gnupress');?>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="cf_point_term"><?php _e('Point expiry date', 'gnupress'); ?></label></th>
            <td colspan="3">
                <?php echo g5_help(__('This point does not apply during the period of the validity period is set to 0', 'gnupress')); //기간을 0으로 설정시 포인트 유효기간이 적용되지 않습니다. ?>
                <input type="text" name="cf_point_term" value="<?php echo $config['cf_point_term']; ?>" id="cf_point_term" required class="required frm_input" size="5"> <?php _e('days', 'gnupress');?>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="cf_write_pages"><?php _e('Display setting for page number', 'gnupress'); ?><strong class="sound_only"><?php _e('required', 'gnupress' );?></strong></label></th>
            <td><input type="text" name="cf_write_pages" value="<?php echo $config['cf_write_pages'] ?>" id="cf_write_pages" required class="required numeric frm_input" size="3"> <?php _e('Show per page', 'gnupress');?></td>
            <th scope="row"><label for="cf_mobile_pages"><?php _e('Display setting for page number( MOBILE )', 'gnupress');?><strong class="sound_only"><?php _e('required', 'gnupress' );?></strong></label></th>
            <td><input type="text" name="cf_mobile_pages" value="<?php echo $config['cf_mobile_pages'] ?>" id="cf_mobile_pages" required class="required numeric frm_input" size="3"> <?php _e('Show per page', 'gnupress');?></td>
        </tr>

        <tr>
            <th scope="row"><label for="cf_editor"><?php _e('Select editor', 'gnupress'); ?></label></th>
            <td colspan="3">
                <?php echo g5_help(g5_get_plugin_url('editor').' '.__('For in that editor folder please select', 'gnupress')) ?>
                <select name="cf_editor" id="cf_editor">
                <?php
                $arr = g5_get_skin_dir('', g5_get_plugin_path('editor') );
                for ($i=0; $i<count($arr); $i++) {
                    if ($i == 0) echo "<option value=\"\">".__('not use', 'gnupress')."</option>";
                    echo "<option value=\"".$arr[$i]."\"".g5_get_selected($config['cf_editor'], $arr[$i]).">".$arr[$i]."</option>\n";
                }
                ?>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_captcha_mp3"><?php _e('Select captcha sound', 'gnupress');?><strong class="sound_only"><?php _e('required', 'gnupress' );?></strong></label></th>
            <td colspan="3">
                <?php echo g5_help(g5_get_plugin_url('captcha')._e('/mp3 For in that sound folder please select.', 'gnupress')) ?>
                <select name="cf_captcha_mp3" id="cf_captcha_mp3" required class="required">
                <?php
                $arr = g5_get_skin_dir('mp3', g5_get_plugin_path('kcaptcha') );
                for ($i=0; $i<count($arr); $i++) {
                    if ($i == 0) echo "<option value=\"\">".__('— Select —', 'gnupress')."</option>";
                    echo "<option value=\"".$arr[$i]."\"".g5_get_selected($config['cf_captcha_mp3'], $arr[$i]).">".$arr[$i]."</option>\n";
                }
                ?>
                </select>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="cf_new_page_id"><?php _e('Select scrap and point page( open )', 'gnupress'); ?><strong class="sound_only"><?php _e('required', 'gnupress' );?></strong></label></th>
            <td colspan="3">
                <?php echo g5_help(__('Please select the page you want to apply, such as scrap and points in a new window.', 'gnupress')); ?>
                <select name="cf_new_page_id" id="cf_new_page_id" >
                  <?php
                    $wp_pages = get_pages(array('post_status'=>'publish,private'));
                    foreach($wp_pages as $wp_page)
                       echo '<option value="'.$wp_page->ID.'"'. ($wp_page->ID==$g5_options['cf_new_page_id']?' selected':'').'>'.$wp_page->post_title.'</option>'."\n";
                  ?>
                </select>
                </select>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="cf_use_copy_log"><?php _e('Log for copy and move', 'gnupress') ?></label></th>
            <td colspan="3">
                <?php echo g5_help(__('Show the direction of the copy, move from someone under the board', 'gnupress'));    //게시물 아래에 누구로 부터 복사, 이동됨 표시 ?>
                <input type="checkbox" name="cf_use_copy_log" value="1" id="cf_use_copy_log" <?php echo $config['cf_use_copy_log']?'checked':''; ?>> <label for="cf_use_copy_log"><?php _e('display', 'gnupress');?></label>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="cf_use_search_include"><?php _e('Board whether or not to include the wp search', 'gnupress'); //전체 검색시 게시판 포함 여부?></label></th>
            <td colspan="3">
                <?php echo g5_help(__('Board contents to include the wp search', 'gnupress').' ( '.__('Please check off when there is a problem with the search.', 'gnupress').')') ?>
                <input type="checkbox" name="cf_use_search_include" value="1" id="cf_use_search_include" <?php echo $config['cf_use_search_include']?'checked':''; ?>> <label for="cf_use_search_include"><?php _e('include if check', 'gnupress');?></label>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="cf_syndi_token"><?php _e('Naver syndication key', 'gnupress');?></label></th>
            <td colspan="3">
                <?php if (!function_exists('curl_init')) echo g5_help('<b>warning) Need to install or enable CURL support for php.</b>'); ?>
                <?php echo g5_help(__('If you enter a key interlock Naver syndication (token) it can be use to Naver syndication.', 'gnupress').'<br>'.
                            sprintf( __('keys must be issued by naver syndication of %s.', 'gnupress'), '<a href="http://webmastertool.naver.com/" target="_blank"><u>webmastertool.naver.com</u></a>')) ?>
                <input type="text" name="cf_syndi_token" value="<?php echo $config['cf_syndi_token'] ?>" id="cf_syndi_token" class="frm_input" size="70">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_syndi_except"><?php _e('Set board excluded from Naver syndication', 'gnupress'); //네이버 신디케이션 제외게시판?></label></th>
            <td colspan="3">
                <?php echo g5_help(__('Please enter separated by | the board ID to be excluded from the Naver collection syndication.', 'gnupress').' '.__('example :) notice|adult', 'gnupress').'<br>'.__('For your information, the boards whose permission is -1 are collected and secret writtings are excluded from the syndication collecting.', 'gnupress')); ?>
                <input type="text" name="cf_syndi_except" value="<?php echo $config['cf_syndi_except'] ?>" id="cf_syndi_except" class="frm_input" size="70">
            </td>
        </tr>

        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_cf_board" class="g5_section_top">
    <h2 class="h2_frm"><?php _e('BBS default setting', 'gnupress'); ?></h2>
    <?php echo $pg_anchor ?>
    <div class="local_desc02 local_desc">
        <p><?php _e('It can be set individually for each management board.', 'gnupress');?></p>
    </div>
    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption><?php _e('BBS default setting', 'gnupress') ?></caption>
        <colgroup>
            <col class="grid_4">
            <col>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>

        <tr>
            <th scope="row"><label for="cf_delay_sec"><?php _e('Intervals during for writing', 'gnupress'); ?><strong class="sound_only"><?php _e('required', 'gnupress' );?></strong></label></th>
            <td><input type="text" name="cf_delay_sec" value="<?php echo $config['cf_delay_sec'] ?>" id="cf_delay_sec" required class="required numeric frm_input" size="3"> <?php _e('Intervals after', 'gnupress');?></td>
            <th scope="row"><label for="cf_link_target"><?php _e('Link for target', 'gnupress');?></label></th>
            <td>
                <?php echo g5_help(__('Specifies the target of the link is automatically posts content.', 'gnupress')); ?>
                <select name="cf_link_target" id="cf_link_target">
                    <option value="_blank"<?php echo g5_get_selected($config['cf_link_target'], '_blank') ?>>_blank</option>
                    <option value="_self"<?php echo g5_get_selected($config['cf_link_target'], '_self') ?>>_self</option>
                    <option value="_top"<?php echo g5_get_selected($config['cf_link_target'], '_top') ?>>_top</option>
                    <option value="_new"<?php echo g5_get_selected($config['cf_link_target'], '_new') ?>>_new</option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_read_point"><?php _e('Read points', 'gnupress'); ?><strong class="sound_only"><?php _e('required', 'gnupress' );?></strong></label></th>
            <td><input type="text" name="cf_read_point" value="<?php echo $config['cf_read_point'] ?>" id="cf_read_point" required class="required frm_input" size="3"> <?php _e('point', 'gnupress');?></td>
            <th scope="row"><label for="cf_write_point"><?php _e('Write points', 'gnupress'); ?></label></th>
            <td><input type="text" name="cf_write_point" value="<?php echo $config['cf_write_point'] ?>" id="cf_write_point" required class="required frm_input" size="3"> <?php _e('point', 'gnupress');?></td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_comment_point"><?php _e('Comment points', 'gnupress'); ?></label></th>
            <td><input type="text" name="cf_comment_point" value="<?php echo $config['cf_comment_point'] ?>" id="cf_comment_point" required class="required frm_input" size="3"> <?php _e('point', 'gnupress');?></td>
            <th scope="row"><label for="cf_download_point"><?php _e('Download points', 'gnupress'); ?></label></th>
            <td><input type="text" name="cf_download_point" value="<?php echo $config['cf_download_point'] ?>" id="cf_download_point" required class="required frm_input" size="3"> <?php _e('point', 'gnupress');?></td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_search_part"><?php _e('Search Units', 'gnupress');?></label></th>
            <td colspan="3"><input type="text" name="cf_search_part" value="<?php echo $config['cf_search_part'] ?>" id="cf_search_part" class="frm_input" size="4"> <?php _e('Units use Search', 'gnupress'); //건 단위로 검색  ?></td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_image_extension"><?php _e('Allowed image extensions', 'gnupress');?></label></th>
            <td colspan="3">
                <?php echo g5_help(__('Allowed image extensions separated by( period or vertical bar )', 'gnupress')); ?>
                <input type="text" name="cf_image_extension" value="<?php echo $config['cf_image_extension'] ?>" id="cf_image_extension" class="frm_input" size="70">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_flash_extension"><?php _e('Allowed flash extensions', 'gnupress');?></label></th>
            <td colspan="3">
                <?php echo g5_help(__('Allowed flash extensions separated by( period or vertical bar )', 'gnupress')); ?>
                <input type="text" name="cf_flash_extension" value="<?php echo $config['cf_flash_extension'] ?>" id="cf_flash_extension" class="frm_input" size="70">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_movie_extension"><?php _e('Allowed movie extensions', 'gnupress');   //동영상 업로드 확장자?></label></th>
            <td colspan="3">
                <?php echo g5_help(__('Allowed movie extensions separated by( period or vertical bar )', 'gnupress')); ?>
                <input type="text" name="cf_movie_extension" value="<?php echo $config['cf_movie_extension'] ?>" id="cf_movie_extension" class="frm_input" size="70">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_filter"><?php _e('Filter words', 'gnupress');   //단어 필터링?></label></th>
            <td colspan="3">
                <?php echo g5_help(__('The Contents contained is entered word can not published. The words between words, separated by commas.', G5_NAME)); ?>
                <textarea name="cf_filter" id="cf_filter" rows="7"><?php echo $config['cf_filter'] ?></textarea>
            </td>
        </tr>

        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_cf_mail" class="g5_section_top">
    <h2 class="h2_frm"><?php _e('Mail Default setting', 'gnupress');?></h2>
    <?php echo $pg_anchor ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption><?php _e('Mail Default setting', 'gnupress');?></caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="cf_email_use"><?php _e('Using mailings', 'gnupress');?></label></th>
            <td>
                <?php echo g5_help(__('If you do not check, The mailing does not use at all. No mail is also testing', 'gnupress')); ?>
                <input type="checkbox" name="cf_email_use" value="1" id="cf_email_use" <?php echo $config['cf_email_use']?'checked':''; ?>> <label for="cf_email_use"><?php _e('use', 'gnupress' );?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_formmail_is_member"><?php _e('Whether using formmail', 'gnupress');?></label></th>
            <td>
                <?php echo g5_help(__('If you do not check, guest can be use.', 'gnupress')); ?>
                <input type="checkbox" name="cf_formmail_is_member" value="1" id="cf_formmail_is_member" <?php echo $config['cf_formmail_is_member']?'checked':''; ?>> <label for="cf_formmail_is_member"><?php _e('Use only members', 'gnupress');?></label>
            </td>
        </tr>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_cf_article_mail" class="g5_section_top">
    <h2 class="h2_frm"><?php _e('Mail setting when posts write', 'gnupress'); ?></h2>
    <?php echo $pg_anchor ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption><?php _e('Mail setting when posts write', 'gnupress'); ?></caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="cf_email_wr_super_admin"><?php _e('Administrator', 'gnupress');?></label></th>
            <td>
                <?php echo g5_help(__('Send email to the administrator.', 'gnupress')); //최고 관리자에게 메일을 발송합니다. ?>
                <input type="checkbox" name="cf_email_wr_super_admin" value="1" id="cf_email_wr_super_admin" <?php echo $config['cf_email_wr_super_admin']?'checked':''; ?>> <label for="cf_email_wr_super_admin"><?php _e('use', 'gnupress' );?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_email_wr_board_admin"><?php _e('BBS manager', 'gnupress');?></label></th>
            <td>
                <?php echo g5_help(__('Send email to the BBS manager.', 'gnupress')); //'게시판관리자에게 메일을 발송합니다.' ?>
                <input type="checkbox" name="cf_email_wr_board_admin" value="1" id="cf_email_wr_board_admin" <?php echo $config['cf_email_wr_board_admin']?'checked':''; ?>> <label for="cf_email_wr_board_admin"><?php _e('use', 'gnupress' );?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_email_wr_write"><?php _e('Author', 'gnupress');?></label></th>
            <td>
                <?php echo g5_help(__('Send email to the Author.', 'gnupress')); //게시자님께 메일을 발송합니다. ?>
                <input type="checkbox" name="cf_email_wr_write" value="1" id="cf_email_wr_write" <?php echo $config['cf_email_wr_write']?'checked':''; ?>> <label for="cf_email_wr_write"><?php _e('use', 'gnupress' );?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_email_wr_comment_all"><?php _e('Commenter', 'gnupress');?></label></th>
            <td>
                <?php echo g5_help(__('If write a comment on original, will be sent mail to all those who wrote comments.', 'gnupress')); //원글에 댓글이 올라오는 경우 댓글 쓴 모든 분들께 메일을 발송합니다. ?>
                <input type="checkbox" name="cf_email_wr_comment_all" value="1" id="cf_email_wr_comment_all" <?php echo $config['cf_email_wr_comment_all']?'checked':''; ?>> <label for="cf_email_wr_comment_all"><?php _e('use', 'gnupress' );?></label>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

</form>

<script>
function fconfigform_submit(f){
    return true;
}
</script>