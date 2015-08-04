<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

wp_enqueue_script('jquery-ui-dialog');
wp_enqueue_style("wp-jquery-ui-dialog");

//게시판 썸네일 삭제 url 설정
$thumbnail_delete_url = home_url(add_query_arg( array('g5_rq'=>'board_thumbnail_delete') ));

$readonly = '';
$sound_only = '';
$pg_anchor = '<ul class="anchor">
    <li><a href="#anc_bo_basic">'.__('Defalut Setting', 'gnupress').'</a></li>
    <li><a href="#anc_bo_auth">'.__('Setting authority', 'gnupress').'</a></li>
    <li><a href="#anc_bo_function">'.__('Setting Function', 'gnupress').'</a></li>
    <li><a href="#anc_bo_design">'.__('Design and style', 'gnupress').'</a></li>
    <li><a href="#anc_bo_point">'.__('Setting Point', 'gnupress').'</a></li>
</ul>';

$frm_submit = '<div class="btn_confirm">
    <input type="submit" value="'.__('Submit', 'gnupress').'" class="btn btn-primary" accesskey="s">
    <a href="'.$list_page_url.'" class="btn btn-info">'.__('Go List', 'gnupress').'</a>'.PHP_EOL;

if ($w == 'u'){
    $frm_submit .= ' <button class="btn btn-info board_copy" title="'.__('Copy board', 'gnupress').'" >'.__('Copy board', 'gnupress').'</button>';
    if($bbs_direct_url){
        $frm_submit .= ' <a href="'.$bbs_direct_url.'" class="btn btn-info">'.__('Go direct bbs', 'gnupress').'</a>';
    }
    $frm_submit .= ' <a href="'.$thumbnail_delete_url.'" class="btn btn-info" onclick="return delete_confirm2(\''.__('Are you sure you want to delete the thumbnail files?', 'gnupress').'\');">'.__('Thumbnail delete', 'gnupress').'</a>'.PHP_EOL;
}
$frm_submit .= '</div>';

$arr_params = array();
if( isset($bo_table) && !empty($bo_table) ) $arr_params['bo_table'] = $bo_table;
if( isset($w) && !empty($w) ) $arr_params['w'] = $w;
$form_action_url = add_query_arg($arr_params, admin_url('admin.php?page=g5_board_form'));

$chk_fields_array = array('num', 'writer', 'visit', 'wdate' ); //번호, 작성자, 조회, 작성일 체크
$chk_bo_sh_array = array();

foreach( $chk_fields_array as $key=>$v ){
    if( isset($board['bo_sh_fields']) && strstr( $board['bo_sh_fields'], $v ) ){
        $chk_bo_sh_array[$v] = "";
    } else {
        $chk_bo_sh_array[$v] = " checked='checked' ";
    }
}
?>

<form name="fboardform" id="fboardform" action="<?php echo g5_form_action_url($form_action_url);?>" onsubmit="return fboardform_submit(this)" method="post" enctype="multipart/form-data" class="bootstrap">
<?php wp_nonce_field( 'bbs-update-fields' ); ?>
<input type="hidden" name="g5_admin_post" value="bbs_update" />
<input type="hidden" name="w" value="<?php echo esc_attr( $w ); ?>">
<input type="hidden" name="sfl" value="<?php echo esc_attr( $sfl ); ?>">
<input type="hidden" name="stx" value="<?php echo esc_attr( $stx ); ?>">
<input type="hidden" name="sst" value="<?php echo esc_attr( $sst ); ?>">
<input type="hidden" name="sod" value="<?php echo esc_attr( $sod ); ?>">
<input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>">

<section id="anc_bo_basic" class="g5_section_top">
    <h2 class="h2_frm"><?php _e('BBS default setting', 'gnupress');?></h2>
    <?php echo $pg_anchor ?>

    <div class="tbl_frm01 tbl_wrap">
        <table class="table-bordered table-striped table-condensed">
        <caption><?php _e('BBS default setting', 'gnupress');?></caption>
        <colgroup>
            <col class="grid_4">
            <col>
            <col class="grid_3">
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="bo_table">TABLE<?php echo $sound_only ?></label></th>
            <td colspan="2">
                <input type="text" name="bo_table" value="<?php echo $board['bo_table'] ?>" id="bo_table" <?php echo $required ?> <?php echo $readonly ?> class="frm_input <?php echo $readonly ?> <?php echo $required ?> <?php //echo $required_valid ?>" maxlength="20">
                <?php if ($w == '') { ?>
                    <?php _e('Allowed characters Alphabetic and Number and underbar( no whitespace with 20 characters limit )', 'gnupress'); ?>
                <?php } else { ?>
                    <?php if($bbs_direct_url){ ?>
                        <a href="<?php echo $bbs_direct_url ?>" class="button"><?php _e('Go direct bbs', 'gnupress');?></a>
                    <?php } ?>
                    <a href="<?php echo $list_page_url ?>" class="button"><?php _e('Go List', 'gnupress');?></a>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_subject"><?php _e('Board Subject', 'gnupress');?><strong class="sound_only"><?php _e('required', 'gnupress');?></strong></label></th>
            <td colspan="2">
                <input type="text" name="bo_subject" value="<?php echo g5_get_text($board['bo_subject']) ?>" id="bo_subject" required class="required frm_input" size="80" maxlength="120">
            </td>
        </tr>
        <?php if(!$w){ ?>
        <tr>
            <th scope="row"><?php _e('Check in page creation', 'gnupress');   //페이지 생성여부?></th>
            <td colspan="2">
                <input type="checkbox" name="bo_auto_install" id="bo_auto_install" value="1" checked="checked" >
                <label for="bo_auto_install"><?php _e('Automatically generates a page at the check-Bulletin', 'gnupress');   //체크시 게시판 페이지를 자동으로 생성합니다.?></label>
            </td>
        </tr>
        <?php } ?>
        <tr>
            <th scope="row"><label for="bo_category_list"><?php _e('Category', 'gnupress');?></label></th>
            <td>
                <?php echo g5_help(__('divide by vertical bar character( example : question|answer )', 'gnupress')) ?>
                <input type="text" name="bo_category_list" value="<?php echo g5_get_text($board['bo_category_list']) ?>" id="bo_category_list" class="frm_input" size="70">
                <input type="checkbox" name="bo_use_category" value="1" id="bo_use_category" <?php echo $board['bo_use_category']?'checked':''; ?>>
                <label for="bo_use_category"><?php _e('use', 'gnupress');?></label>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_category_list" value="1" id="chk_all_category_list">
                <label for="chk_all_category_list"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <?php if ($w == 'u') { ?>
        <tr>
            <th scope="row"><label for="bo_wp_pageid"><?php _e('Page to apply', 'gnupress');?></label></th>
            <td colspan="2">
                <?php echo g5_help(__('Please select the page you want to apply the Bulletin. you can apply only one per page.', 'gnupress')); ?>
                <select name="bo_wp_pageid" id="bo_wp_pageid" >
                  <?php
                    $wp_pages = get_pages(array('post_status'=>'publish,private'));  
                    echo '<option value="0" selected>&lt;None&gt;</option>';
                    foreach($wp_pages as $wp_page)
                       echo '<option value="'.$wp_page->ID.'"'. ($wp_page->ID==$g5_get_page_id?' selected':'').'>'.$wp_page->post_title.'</option>'."\n";
                  ?>
                </select>
            </td>
        </tr>

        <tr>
            <th scope="row"><?php _e('Board', 'gnupress');?> Shortcode( <?php _e('Fix', 'gnupress');?> )</th>
            <td colspan="2">
                <?php echo g5_help(__('You apply the bulletin board in the post or page.', 'gnupress'));  //글 또는 페이지에 게시판을 적용시킬수 있습니다. ?>
                <strong><?php echo '['.G5_NAME.' bo_table='.$bo_table.']' ; ?></strong> <button type="button" class="copy_shortcode button"><?php _e('Copy', 'gnupress');?></button>
            </td>
        </tr>

        <tr>
            <th scope="row"><?php _e('Latest', 'gnupress');?> Shortcode( <?php _e('Fix', 'gnupress');?> )</th>
            <td colspan="2">
                <?php echo g5_help(__("If you set the page to apply the above is acceptable to omit 'url=page_url'. rows is post number.", 'gnupress')); //위의 적용할 페이지를 설정하였다면 url=페이지주소를 생략하셔도 됩니다. rows는 게시물갯수입니다. ?>
                <strong><?php echo '['.G5_NAME.'_latest bo_table='.$bo_table.' '.__('url=page_url', 'gnupress').' rows=5]' ; ?></strong> <button type="button" class="copy_shortcode button"><?php _e('Copy', 'gnupress');?></button>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="proc_count"><?php _e('Count Adjustment', 'gnupress');?></label></th>
            <td colspan="2">
                <?php echo g5_help( sprintf(__('Current Posts number : %s, Current Comments number : %s', 'gnupress'), number_format($board['bo_count_write']), number_format($board['bo_count_comment']))."\n".__('Check the list on the board if the number of the article does not fit.', 'gnupress') ); ?>
                <input type="checkbox" name="proc_count" value="1" id="proc_count">
            </td>
        </tr>
        <?php } ?>
        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_bo_auth" class="g5_section_top">
    <h2 class="h2_frm"><?php _e('Board permissions setting', 'gnupress');   //게시판 권한 설정 ?></h2>
    <?php echo $pg_anchor ?>

    <div class="tbl_frm01 tbl_wrap">
        <table class="table-bordered table-striped table-condensed">
        <caption><?php _e('Board permissions setting', 'gnupress'); ?></caption>
        <colgroup>
            <col class="grid_4">
            <col>
            <col class="grid_3">
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="bo_admin"><?php _e('board administrator', 'gnupress');?></label></th>
            <td>
                <input type="text" name="bo_admin" value="<?php echo $board['bo_admin'] ?>" id="bo_admin" class="frm_input" maxlength="20">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_admin" value="1" id="chk_all_admin">
                <label for="chk_all_admin"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_list_level"><?php _e('List permissions', 'gnupress'); //목록보기 권한?></label></th>
            <td>
                <?php echo g5_help(
                    __('WordPress default permission levels are as follows:', 'gnupress').'<br>'.
                    __('level -1 : Guest', 'gnupress').'<br>'.
                    __('level 0 : Subscriber', 'gnupress').'<br>'.
                    __('level 1 : Contributor', 'gnupress').'<br>'.
                    __('level 2 : Author', 'gnupress').'<br>'.
                    __('level 7 : Editor', 'gnupress').'<br>'.
                    __('level 10 : Administrator', 'gnupress').'<br>'
                    );
                ?>
                <?php echo g5_get_number_select('bo_list_level', -1, 10, $board['bo_list_level']) ?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_list_level" value="1" id="chk_all_list_level">
                <label for="chk_all_list_level"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_read_level"><?php _e('Read permissions', 'gnupress'); //글읽기 권한?></label></th>
            <td>
                <?php echo g5_get_number_select('bo_read_level', -1, 10, $board['bo_read_level']) ?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_read_level" value="1" id="chk_all_read_level">
                <label for="chk_all_read_level"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_write_level"><?php _e('Write permissions', 'gnupress'); //글쓰기 권한?></label></th>
            <td>
                <?php echo g5_get_number_select('bo_write_level', -1, 10, $board['bo_write_level']) ?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_write_level" value="1" id="chk_all_write_level">
                <label for="chk_all_write_level"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_comment_level"><?php _e('Comment permissions', 'gnupress'); //글쓰기 권한?></label></th>
            <td>
                <?php echo g5_get_number_select('bo_comment_level', -1, 10, $board['bo_comment_level']) ?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_comment_level" value="1" id="chk_all_comment_level">
                <label for="chk_all_comment_level"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_link_level"><?php _e('Link permissions', 'gnupress'); //링크 권한?></label></th>
            <td>
                <?php echo g5_get_number_select('bo_link_level', -1, 10, $board['bo_link_level']) ?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_link_level" value="1" id="chk_all_link_level">
                <label for="chk_all_link_level"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_upload_level"><?php _e('Upload permissions', 'gnupress'); //업로드 권한?></label></th>
            <td>
                <?php echo g5_get_number_select('bo_upload_level', -1, 10, $board['bo_upload_level']) ?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_upload_level" value="1" id="chk_all_upload_level">
                <label for="chk_all_upload_level"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_download_level"><?php _e('Download permissions', 'gnupress'); //다운로드 권한?></label></th>
            <td>
                <?php echo g5_get_number_select('bo_download_level', -1, 10, $board['bo_download_level']) ?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_download_level" value="1" id="chk_all_download_level">
                <label for="chk_all_download_level"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_bo_function" class="g5_section_top">
    <h2 class="h2_frm"><?php _e('Board functions setting', 'gnupress'); //게시판 기능 설정?></h2>
    <?php echo $pg_anchor ?>

    <div class="tbl_frm01 tbl_wrap">
        <table class="table-bordered table-striped table-condensed">
        <caption><?php _e('Board functions setting', 'gnupress'); //게시판 기능 설정?></caption>
        <colgroup>
            <col class="grid_4">
            <col>
            <col class="grid_3">
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="bo_count_modify"><?php _e('No modifications original', 'gnupress'); //원글 수정 불가?><strong class="sound_only"><?php _e('required', 'gnupress');?></strong></label></th>
            <td>
                 <?php echo g5_help(__('If more than the set number of comments can not be modified original. If you set to 0, you can modify any number of comments.', 'gnupress')); ?>
                <?php _e('comments', 'gnupress');?> <input type="text" name="bo_count_modify" value="<?php echo $board['bo_count_modify'] ?>" id="bo_count_modify" required class="required numeric frm_input" size="3"><?php _e('items over it can\'t modification', 'gnupress')  //개 이상 달리면 수정 불가;?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_count_modify" value="1" id="chk_all_count_modify">
                <label for="chk_all_count_modify"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_count_delete"><?php _e('No delete original', 'gnupress'); //원글 삭제 불가?><strong class="sound_only"><?php _e('required', 'gnupress');?></strong></label></th>
            <td>
                <?php _e('comment', 'gnupress');?> <input type="text" name="bo_count_delete" value="<?php echo $board['bo_count_delete'] ?>" id="bo_count_delete" required class="required numeric frm_input" size="3"><?php _e('items over it can\'t delete', 'gnupress')  //개 이상 달리면 삭제 불가;?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_count_delete" value="1" id="chk_all_count_delete">
                <label for="chk_all_count_delete"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_sideview"><?php _e('user sideview', 'gnupress'); //회원 사이드뷰?></label></th>
            <td>
                <input type="checkbox" name="bo_use_sideview" value="1" id="bo_use_sideview" <?php echo $board['bo_use_sideview']?'checked':''; ?>>
                <label for="bo_use_sideview"><?php _e('use', 'gnupress'); //사용?></label>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_sideview" value="1" id="chk_all_use_sideview">
                <label for="chk_all_use_sideview"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_secret"><?php _e('Use Secret', 'gnupress'); //비밀글 사용?></label></th>
            <td>
                <?php echo g5_help(
                __('"Checkbox" You can have articles written during checking Secret. "Compulsion" is written into all of that Secret. (Administrators output checkbox.) It may not be applied depending on the skin.', 'gnupress')
                ) ?>
                <select id="bo_use_secret" name="bo_use_secret">
                    <?php echo g5_option_selected(0, $board['bo_use_secret'], __('Not use', 'gnupress'));  //사용하지 않음 ?>
                    <?php echo g5_option_selected(1, $board['bo_use_secret'], __('Checkbox', 'gnupress'));  //체크박스 ?>
                    <?php echo g5_option_selected(2, $board['bo_use_secret'], __('Compulsion', 'gnupress'));    //무조건 ?>
                </select>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_secret" value="1" id="chk_all_use_secret">
                <label for="chk_all_use_secret"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_dhtml_editor"><?php _e('Using the DHTML editor', 'gnupress');    //에디터사용?></label></th>
            <td>
                <?php echo g5_help(__('Set the paper to use when creating content as the editor functions. It may not be applied depending on the skin.', 'gnupress')) ?>
                <input type="checkbox" name="bo_use_dhtml_editor" value="1" <?php echo $board['bo_use_dhtml_editor']?'checked':''; ?> id="bo_use_dhtml_editor">
                <label for="bo_use_dhtml_editor"><?php _e('use', 'gnupress'); //사용?></label>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_dhtml_editor" value="1" id="chk_all_use_dhtml_editor">
                <label for="chk_all_use_dhtml_editor"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_rss_view"><?php _e('Using the RSS', 'gnupress');    //RSS 사용?></label></th>
            <td>
                <?php echo g5_help(__('Guest can read the article and must be checked using RSS to the RSS support.', 'gnupress')); ?>
                <input type="checkbox" name="bo_use_rss_view" value="1" <?php echo $board['bo_use_rss_view']?'checked':''; ?> id="bo_use_rss_view">
                <label for="bo_use_rss_view"><?php _e('use', 'gnupress'); //사용?></label>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_rss_view" value="1" id="chk_all_use_rss_view">
                <label for="chk_all_use_rss_view"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_good"><?php _e('Using the recommend', 'gnupress');    //추천 사용?></label></th>
            <td>
                <input type="checkbox" name="bo_use_good" value="1" <?php echo $board['bo_use_good']?'checked':''; ?> id="bo_use_good">
                <label for="bo_use_good"><?php _e('use', 'gnupress'); //사용?></label>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_good" value="1" id="chk_all_use_good">
                <label for="chk_all_use_good"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_nogood"><?php _e('Using the nonrecommend', 'gnupress');    //비추천 사용?></label></th>
            <td>
                <input type="checkbox" name="bo_use_nogood" value="1" id="bo_use_nogood" <?php echo $board['bo_use_nogood']?'checked':''; ?>>
                <label for="bo_use_nogood"><?php _e('use', 'gnupress'); //사용?></label>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_nogood" value="1" id="chk_all_use_nogood">
                <label for="chk_all_use_nogood"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_ip_view"><?php _e('Use IP show', 'gnupress');    //IP 보이기 사용?></label></th>
            <td>
                <input type="checkbox" name="bo_use_ip_view" value="1" id="bo_use_ip_view" <?php echo $board['bo_use_ip_view']?'checked':''; ?>>
                <label for="bo_use_ip_view"><?php _e('use', 'gnupress'); //사용?></label>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_ip_view" value="1" id="chk_all_use_ip_view">
                <label for="chk_all_use_ip_view"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_list_content"><?php _e('Use IP show', 'gnupress');    //IP 보이기 사용?></label></th>
            <td>
                <?php echo g5_help("List is the option to set when you need to read the contents. The default is not used."); ?>
                <input type="checkbox" name="bo_use_list_content" value="1" id="bo_use_list_content" <?php echo $board['bo_use_list_content']?'checked':''; ?>>
                <label for="bo_use_list_content"><?php _e('use', 'gnupress'); //사용?> (<?php _e('Can be slow', 'gnupress'); //속도가 느려질수 있음?>)</label>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_list_content" value="1" id="chk_all_use_list_content">
                <label for="chk_all_use_list_content"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_list_file"><?php _e('Load uploads in List', 'gnupress');    //목록에서 파일 사용?></label></th>
            <td>
                <?php echo g5_help(__('The options you set when you need to read Upload files in the list. The default is not used.', 'gnupress')); ?>
                <input type="checkbox" name="bo_use_list_file" value="1" id="bo_use_list_file" <?php echo $board['bo_use_list_file']?'checked':''; ?>>
                <label for="bo_use_list_file"><?php _e('use', 'gnupress'); //사용?> (<?php _e('Can be slow', 'gnupress'); //속도가 느려질수 있음?>)</label>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_list_file" value="1" id="chk_all_use_list_file">
                <label for="chk_all_use_list_file"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_list_view"><?php _e('Use Show all list', 'gnupress'); //전체목록보이기 사용?></label></th>
            <td>
                <input type="checkbox" name="bo_use_list_view" value="1" id="bo_use_list_view" <?php echo $board['bo_use_list_view']?'checked':''; ?>>
                <label for="bo_use_list_view"><?php _e('use', 'gnupress'); //사용?></label>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_list_view" value="1" id="chk_all_use_list_view">
                <label for="chk_all_use_list_view"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_email"><?php _e('Using mailings', 'gnupress'); //메일발송 사용?></label></th>
            <td>
                <input type="checkbox" name="bo_use_email" value="1" id="bo_use_email" <?php echo $board['bo_use_email']?'checked':''; ?>>
                <label for="bo_use_email"><?php _e('use', 'gnupress'); //사용?></label>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_email" value="1" id="chk_all_use_email">
                <label for="chk_all_use_email"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_upload_count"><?php _e('Upload File Number', 'gnupress'); //파일 업로드 개수?><strong class="sound_only"><?php _e('required', 'gnupress');?></strong></label></th>
            <td>
                <?php echo g5_help(__('The maximum number of files that can be uploaded one posts (0 is not used attachments)', 'gnupress')); ?>
                <input type="text" name="bo_upload_count" value="<?php echo $board['bo_upload_count'] ?>" id="bo_upload_count" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_upload_count" value="1" id="chk_all_upload_count">
                <label for="chk_all_upload_count"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_upload_size"><?php _e('uploads max_size', 'gnupress');  //파일 업로드 용량?><strong class="sound_only"><?php _e('required', 'gnupress');?></strong></label></th>
            <td>
                <?php echo g5_help( sprintf( __('Max_filesize is %s', 'gnupress'), ini_get("upload_max_filesize")).', 1 MB = 1,048,576 bytes') ?>
                <?php _e('a uploads max_size', 'gnupress');?> <input type="text" name="bo_upload_size" value="<?php echo $board['bo_upload_size'] ?>" id="bo_upload_size" required class="required numeric frm_input"  size="10"> bytes <?php _e('less then or equal to', 'gnupress');?>            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_upload_size" value="1" id="chk_all_upload_size">
                <label for="chk_all_upload_size"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_file_content"><?php _e('Using uploads description', 'gnupress');  //파일 설명 사용?></label></th>
            <td>
                <input type="checkbox" name="bo_use_file_content" value="1" id="bo_use_file_content" <?php echo $board['bo_use_file_content']?'checked':''; ?>><label for="bo_use_file_content"><?php _e('use', 'gnupress');?></label>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_file_content" value="1" id="chk_all_use_file_content">
                <label for="chk_all_use_file_content"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_write_min"><?php _e('Limit minimum text', 'gnupress');  //최소 글수 제한?></label></th>
            <td>
                <?php echo g5_help(__('Setting a minimum length text input. Enter 0 or top managers, DHTML editor does not check when using include', 'gnupress')); ?>
                <input type="text" name="bo_write_min" value="<?php echo $board['bo_write_min'] ?>" id="bo_write_min" class="numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_write_min" value="1" id="chk_all_write_min">
                <label for="chk_all_write_min"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_write_max"><?php _e('Limit maximum text', 'gnupress');  //최대 글수 제한?></label></th>
            <td>
                <?php echo g5_help(__('Setting a maximum length text input. Enter 0 or top managers, DHTML editor does not check when using include', 'gnupress')); ?>
                <input type="text" name="bo_write_max" value="<?php echo $board['bo_write_max'] ?>" id="bo_write_max" class="numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_write_max" value="1" id="chk_all_write_max">
                <label for="chk_all_write_max"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_comment_min"><?php _e('Limit minimum Comments', 'gnupress');  //최소 댓글수 제한?></label></th>
            <td>
                <?php echo g5_help(__('Comments Enter setting a minimum length. No inspection Entering 0', 'gnupress')); ?>
                <input type="text" name="bo_comment_min" value="<?php echo $board['bo_comment_min'] ?>" id="bo_comment_min" class="numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_comment_min" value="1" id="chk_all_comment_min">
                <label for="chk_all_comment_min"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_comment_max"><?php _e('Limit maximum Comments', 'gnupress');  //최대 댓글수 제한?></label></th>
            <td>
                <?php echo g5_help(__('Comments Enter setting a maximum length. No inspection Entering 0', 'gnupress')) ?>
                <input type="text" name="bo_comment_max" value="<?php echo $board['bo_comment_max'] ?>" id="bo_comment_max" class="numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_comment_max" value="1" id="chk_all_comment_max">
                <label for="chk_all_comment_max"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_search"><?php _e('Include WP Search', 'gnupress');  //전체 검색 사용?></label></th>
            <td>
                <input type="checkbox" name="bo_use_search" value="1" id="bo_use_search" <?php echo $board['bo_use_search']?'checked':''; ?>>
                <label for="bo_use_search"><?php _e('use', 'gnupress');?></label>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_search" value="1" id="chk_all_use_search">
                <label for="chk_all_use_search"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_search"><?php _e('Use tag', 'gnupress');  //태그 기능 사용?></label></th>
            <td>
                <input type="checkbox" name="bo_use_tag" value="1" id="bo_use_tag" <?php echo $board['bo_use_tag']?'checked':''; ?>>
                <label for="bo_use_tag"><?php _e('use', 'gnupress');?></label>
                <?php if( isset($board['bo_use_tag']) && !empty($board['bo_use_tag']) ){ ?>
                <a href="<?php echo admin_url('admin.php?page=g5_tag_form&amp;bo_table='.$board['bo_table']) ?>" class="button"><?php _e('Manage BBS tag', 'gnupress');    //게시판 태그 설정관리?></a>
                <?php } ?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_tag" value="1" id="chk_all_use_tag">
                <label for="chk_all_use_tag"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php _e('Show Number, author, hit, date', 'gnupress');    //번호, 작성자, 조회, 작성일 설정 ?></th>
            <td>
                <input type="hidden" name="bo_chk_fields" value="<?php echo $board['bo_sh_fields']?>" >
                <input type="checkbox" name="bo_sh_fields[]" value="num" id="bo_chk_fields_1" <?php echo $chk_bo_sh_array['num']?> ><label for="bo_chk_fields_1"><?php _e('Show Number', 'gnupress');?></label>
                <input type="checkbox" name="bo_sh_fields[]" value="writer" id="bo_chk_fields_2" <?php echo $chk_bo_sh_array['writer']?> > <label for="bo_chk_fields_2"><?php _e('Show author', 'gnupress');?></label>
                <input type="checkbox" name="bo_sh_fields[]" value="visit" id="bo_chk_fields_3" <?php echo $chk_bo_sh_array['visit']?> > <label for="bo_chk_fields_3"><?php _e('Show hit', 'gnupress');?></label>
                <input type="checkbox" name="bo_sh_fields[]" value="wdate" id="bo_chk_fields_4" <?php echo $chk_bo_sh_array['wdate']?> > <label for="bo_chk_fields_4"><?php _e('Show date', 'gnupress');?></label>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_sh_fields" value="1" id="chk_all_sh_fields">
                <label for="chk_all_sh_fields"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>

        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_bo_design" class="g5_section_top">
    <h2 class="h2_frm"><?php _e('Board design and pattern', 'gnupress');    //게시판 디자인/양식 ?></h2>
    <?php echo $pg_anchor ?>

    <div class="tbl_frm01 tbl_wrap">
        <table class="table-bordered table-striped table-condensed">
        <caption><?php _e('Board design and pattern', 'gnupress');    //게시판 디자인/양식 ?></caption>
        <colgroup>
            <col class="grid_4">
            <col>
            <col class="grid_3">
        </colgroup>
        <tbody>
            <tr>
            <th scope="row"><label for="bo_skin"><?php _e('Skin directory', 'gnupress'); //스킨 디렉토리?><strong class="sound_only"><?php _e('required', 'gnupress');?></strong></label></th>
            <td>
                <?php echo g5_get_skin_select('board', 'bo_skin', 'bo_skin', $board['bo_skin'], 'required'); ?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_skin" value="1" id="chk_all_skin">
                <label for="chk_all_skin"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="bo_content_head"><?php _e('Add Before contents', 'gnupress'); //상단 내용?></label></th>
            <td>
                <?php echo g5_editor_html("bo_content_head", g5_get_editor_content($board['bo_content_head'])); ?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_content_head" value="1" id="chk_all_content_head">
                <label for="chk_all_content_head"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_content_tail"><?php _e('Add After contents', 'gnupress'); //하단 내용?></label></th>
            <td>
                <?php echo g5_editor_html("bo_content_tail", g5_get_editor_content($board['bo_content_tail'])); ?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_content_tail" value="1" id="chk_all_content_tail">
                <label for="chk_all_content_tail"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>

         <tr>
            <th scope="row"><label for="bo_insert_content"><?php _e('Writing basic information', 'gnupress'); ?></label></th>
            <td>
                <textarea id="bo_insert_content" name="bo_insert_content" rows="5"><?php echo $board['bo_insert_content'] ?></textarea>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_insert_content" value="1" id="chk_all_insert_content">
                <label for="chk_all_insert_content"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_subject_len"><?php _e('Subject length', 'gnupress'); ?><strong class="sound_only"><?php _e('required', 'gnupress'); ?></strong></label></th>
            <td>
                <?php echo g5_help(__('Cut text are displayed in \'...\' when overflow', 'gnupress')); //목록에서의 제목 글자수. 잘리는 글은 … 로 표시?>
                <input type="text" name="bo_subject_len" value="<?php echo $board['bo_subject_len'] ?>" id="bo_subject_len" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_subject_len" value="1" id="chk_all_subject_len">
                <label for="chk_all_subject_len"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_mobile_subject_len"><?php _e('Mobile Subject length', 'gnupress'); ?><strong class="sound_only"><?php _e('required', 'gnupress'); ?></strong></label></th>
            <td>
                <?php echo g5_help(__('Cut text are displayed in \'...\' when overflow', 'gnupress')); //목록에서의 제목 글자수. 잘리는 글은 … 로 표시?>
                <input type="text" name="bo_mobile_subject_len" value="<?php echo $board['bo_mobile_subject_len'] ?>" id="bo_mobile_subject_len" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_mobile_subject_len" value="1" id="chk_all_mobile_subject_len">
                <label for="chk_all_mobile_subject_len"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_page_rows"><?php _e('Lists per page', 'gnupress'); //페이지당 목록수 ?><strong class="sound_only"><?php _e('required', 'gnupress'); ?></strong></label></th>
            <td>
                <input type="text" name="bo_page_rows" value="<?php echo $board['bo_page_rows'] ?>" id="bo_page_rows" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_page_rows" value="1" id="chk_all_page_rows">
                <label for="chk_all_page_rows"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_mobile_page_rows"><?php _e('Mobile Lists per page', 'gnupress'); //페이지당 목록수 ?><strong class="sound_only"><?php _e('required', 'gnupress'); ?></strong></label></th>
            <td>
                <input type="text" name="bo_mobile_page_rows" value="<?php echo $board['bo_mobile_page_rows'] ?>" id="bo_mobile_page_rows" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_mobile_page_rows" value="1" id="chk_all_mobile_page_rows">
                <label for="chk_all_mobile_page_rows"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_gallery_cols"><?php _e('Gallery images per line', 'gnupress'); //갤러리 이미지 수 ?><strong class="sound_only"><?php _e('required', 'gnupress'); ?></strong></label></th>
            <td>
                <?php echo g5_help(__('The values which set how many pages to be displayed in the list of the gallery types', 'gnupress')); //갤러리 형식의 게시판 목록에서 이미지를 한줄에 몇장씩 보여 줄 것인지를 설정하는 값 ?>
                <?php echo g5_get_number_select('bo_gallery_cols', 1, 10, $board['bo_gallery_cols']) ?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_gallery_cols" value="1" id="chk_all_gallery_cols">
                <label for="chk_all_gallery_cols"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_gallery_width"><?php _e('Set gallery width', 'gnupress'); //갤러리 이미지 폭 ?><strong class="sound_only"><?php _e('required', 'gnupress'); ?></strong></label></th>
            <td>
                <?php echo g5_help(__('The values which set the width of thumb nale images in the board list of the gallery types', 'gnupress')); //갤러리 형식의 게시판 목록에서 썸네일 이미지의 폭을 설정하는 값 ?>
                <input type="text" name="bo_gallery_width" value="<?php echo $board['bo_gallery_width'] ?>" id="bo_gallery_width" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_gallery_width" value="1" id="chk_all_gallery_width">
                <label for="chk_all_gallery_width"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_gallery_height"><?php _e('Set gallery height', 'gnupress'); //갤러리 이미지 높이 ?><strong class="sound_only"><?php _e('required', 'gnupress'); ?></strong></label></th>
            <td>
                <?php echo g5_help(__('The  values which set the heighth of thumb nale images in the board list of the gallery types', 'gnupress')); //갤러리 형식의 게시판 목록에서 썸네일 이미지의 높이를 설정하는 값 ?>
                <input type="text" name="bo_gallery_height" value="<?php echo $board['bo_gallery_height'] ?>" id="bo_gallery_height" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_gallery_height" value="1" id="chk_all_gallery_height">
                <label for="chk_all_gallery_height"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_mobile_gallery_width"><?php _e('Set Mobile gallery width', 'gnupress'); //모바일 갤러리 이미지 폭 ?><strong class="sound_only"><?php _e('required', 'gnupress'); ?></strong></label></th>
            <td>
                <?php echo g5_help(__('The values which set the width of thumbnale images in the board list of the gallery types when you connect on a mobile', 'gnupress')); //모바일로 접속시 갤러리 형식의 게시판 목록에서 썸네일 이미지의 폭을 설정하는 값 ?>
                <input type="text" name="bo_mobile_gallery_width" value="<?php echo $board['bo_mobile_gallery_width'] ?>" id="bo_mobile_gallery_width" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_mobile_gallery_width" value="1" id="chk_all_mobile_gallery_width">
                <label for="chk_all_mobile_gallery_width"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_mobile_gallery_height"><?php _e('Set Mobile gallery height', 'gnupress'); //모바일 갤러리 이미지 높이 ?><strong class="sound_only"><?php _e('required', 'gnupress'); ?></strong></label></th>
            <td>
                <?php echo g5_help(__('The values which set the height of thumbnale images in the board list of the gallery types when you connect on a mobile', 'gnupress')); //모바일로 접속시 갤러리 형식의 게시판 목록에서 썸네일 이미지의 높이를 설정하는 값 ?>
                <input type="text" name="bo_mobile_gallery_height" value="<?php echo $board['bo_mobile_gallery_height'] ?>" id="bo_mobile_gallery_height" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_mobile_gallery_height" value="1" id="chk_all_mobile_gallery_height">
                <label for="chk_all_mobile_gallery_height"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_table_width"><?php _e('SET Board Width', 'gnupress'); //게시판 폭 ?><strong class="sound_only"><?php _e('required', 'gnupress'); ?></strong></label></th>
            <td>
                <?php echo g5_help(__('If less than 100, set it by %', 'gnupress')); //100 이하는 % ?>
                <input type="text" name="bo_table_width" value="<?php echo $board['bo_table_width'] ?>" id="bo_table_width" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_table_width" value="1" id="chk_all_table_width">
                <label for="chk_all_table_width"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_image_width"><?php _e('SET image_view width', 'gnupress'); //이미지 폭 크기 ?><strong class="sound_only"><?php _e('required', 'gnupress'); ?></strong></label></th>
            <td>
                <?php echo g5_help(__('Width size of the image outputted from the board', 'gnupress')); //게시판에서 출력되는 이미지의 폭 크기?>
                <input type="text" name="bo_image_width" value="<?php echo $board['bo_image_width'] ?>" id="bo_image_width" required class="required numeric frm_input" size="4"> pixel
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_image_width" value="1" id="chk_all_image_width">
                <label for="chk_all_image_width"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_new"><?php _e('Set new icon', 'gnupress');   //새글 아이콘 ?><strong class="sound_only"><?php _e('required', 'gnupress'); ?></strong></label></th>
            <td>
                <?php echo g5_help(__('Time to output the new image and text input. If you enter 0, no output icons.')); //글 입력후 new 이미지를 출력하는 시간. 0을 입력하시면 아이콘을 출력하지 않습니다. ?>
                <input type="text" name="bo_new" value="<?php echo $board['bo_new'] ?>" id="bo_new" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_new" value="1" id="chk_all_new">
                <label for="chk_all_new"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_hot"><?php _e('Set hit icon', 'gnupress');   //인기글 아이콘 ?><strong class="sound_only"><?php _e('required', 'gnupress'); ?></strong></label></th>
            <td>
                <?php echo g5_help(__('If Hits is setting over hot image outputs. If you enter 0, no output icons.', 'gnupress')); //조회수가 설정값 이상이면 hot 이미지 출력. 0을 입력하시면 아이콘을 출력하지 않습니다. ?>
                <input type="text" name="bo_hot" value="<?php echo $board['bo_hot'] ?>" id="bo_hot" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_hot" value="1" id="chk_all_hot">
                <label for="chk_all_hot"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_sort_field"><?php _e('Set order by filed', 'gnupress');   //리스트 정렬 필드 ?></label></th>
            <td>
                <?php echo g5_help(__('Select the field you want to sort the list by default. If you are not used as a "Default" it may be slow.', 'gnupress'));    //리스트에서 기본으로 정렬에 사용할 필드를 선택합니다. "기본"으로 사용하지 않으시는 경우 속도가 느려질 수 있습니다. ?>
                <select id="bo_sort_field" name="bo_sort_field">
                    <option value="" <?php echo g5_get_selected($board['bo_sort_field'], ""); ?>>wr_num, wr_parent : <?php _e('Default', 'gnupress');?></option>
                    <option value="wr_datetime asc" <?php echo g5_get_selected($board['bo_sort_field'], "wr_datetime asc"); ?>>wr_datetime asc : <?php _e('Ascending order date', 'gnupress');   //날짜 이전것 부터?></option>
                    <option value="wr_datetime desc" <?php echo g5_get_selected($board['bo_sort_field'], "wr_datetime desc"); ?>>wr_datetime desc : <?php _e('Descending order date', 'gnupress');   //날짜 최근것 부터?></option>
                    <option value="wr_hit asc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "wr_hit asc, wr_num, wr_parent"); ?>>wr_hit asc : <?php _e('Ascending order hit', 'gnupress');   //조회수 낮은것 부터?></option>
                    <option value="wr_hit desc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "wr_hit desc, wr_num, wr_parent"); ?>>wr_hit desc : <?php _e('Descending order hit', 'gnupress');   //조회수 높은것 부터?></option>
                    <option value="wr_last asc" <?php echo g5_get_selected($board['bo_sort_field'], "wr_last asc"); ?>>wr_last asc : <?php _e('Ascending order last', 'gnupress');   //최근글 이전것 부터?></option>
                    <option value="wr_last desc" <?php echo g5_get_selected($board['bo_sort_field'], "wr_last desc"); ?>>wr_last desc : <?php _e('Descending order last', 'gnupress');   //최근글 최근것 부터?></option>
                    <option value="wr_comment asc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "wr_comment asc, wr_num, wr_parent"); ?>>wr_comment asc : <?php _e('Ascending order Comments', 'gnupress');   //댓글수 낮은것 부터?></option>
                    <option value="wr_comment desc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "wr_comment desc, wr_num, wr_parent"); ?>>wr_comment desc : <?php _e('Descending order Comments', 'gnupress');   //댓글수 높은것 부터?></option>
                    <option value="wr_good asc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "wr_good asc, wr_num, wr_parent"); ?>>wr_good asc : <?php _e('Ascending order recommend', 'gnupress');   //추천수 낮은것 부터?></option>
                    <option value="wr_good desc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "wr_good desc, wr_num, wr_parent"); ?>>wr_good desc : <?php _e('Descending order recommend', 'gnupress');   //추천수 높은것 부터?></option>
                    <option value="wr_nogood asc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "wr_nogood asc, wr_num, wr_parent"); ?>>wr_nogood asc : <?php _e('Ascending order nonrecommend', 'gnupress');   //비추천수 낮은것 부터?></option>
                    <option value="wr_nogood desc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "wr_nogood desc, wr_num, wr_parent"); ?>>wr_nogood desc : <?php _e('Descending order nonrecommend', 'gnupress');   //비추천수 높은것 부터?></option>
                    <option value="wr_subject asc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "wr_subject asc, wr_num, wr_parent"); ?>>wr_subject asc : <?php _e('Ascending order subject', 'gnupress');   //제목 오름차순?></option>
                    <option value="wr_subject desc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "wr_subject desc, wr_num, wr_parent"); ?>>wr_subject desc : <?php _e('Descending order nonrecommend', 'gnupress');   //제목 내림차순?></option>
                    <option value="wr_name asc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "wr_name asc, wr_num, wr_parent"); ?>>wr_name asc : <?php _e('Ascending order Author', 'gnupress');   //글쓴이 오름차순?></option>
                    <option value="wr_name desc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "wr_name desc, wr_num, wr_parent"); ?>>wr_name desc : <?php _e('Descending order Author', 'gnupress');   //글쓴이 내림차순?></option>
                    <option value="ca_name asc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "ca_name asc, wr_num, wr_parent"); ?>>ca_name asc : <?php _e('Ascending order Category', 'gnupress');   //분류명 오름차순?></option>
                    <option value="ca_name desc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "ca_name desc, wr_num, wr_parent"); ?>>ca_name desc : <?php _e('Descending order Category', 'gnupress');   //분류명 내림차순?></option>
                </select>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_sort_field" value="1" id="chk_all_sort_field">
                <label for="chk_all_sort_field"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_bo_point" class="g5_section_top">
    <h2 class="h2_frm"><?php _e('Board point setting', 'gnupress');   //게시판 포인트 설정?></h2>
    <?php echo $pg_anchor ?>

    <div class="tbl_frm01 tbl_wrap">
        <table class="table-bordered table-striped table-condensed">
        <caption><?php _e('Board point setting', 'gnupress');   //게시판 포인트 설정?></caption>
        <colgroup>
            <col class="grid_4">
            <col>
            <col class="grid_3">
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="chk_grp_point"><?php _e('Set as Default', 'gnupress');?></label></th>
            <td colspan="2">
                <?php echo g5_help(__('Set point input to the gnupress config', 'gnupress'));  //환경설정에 입력된 포인트로 설정 ?>
                <input type="checkbox" name="chk_grp_point" id="chk_grp_point" onclick="set_point(this.form)">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_read_point"><?php _e('Read points', 'gnupress'); //글읽기 포인트?><strong class="sound_only"><?php _e('required', 'gnupress'); ?></strong></label></th>
            <td>
                <input type="text" name="bo_read_point" value="<?php echo $board['bo_read_point'] ?>" id="bo_read_point" required class="required frm_input" size="5">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_read_point" value="1" id="chk_all_read_point">
                <label for="chk_all_read_point"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_write_point"><?php _e('Write points', 'gnupress'); //글쓰기 포인트?><strong class="sound_only"><?php _e('required', 'gnupress'); ?></strong></label></th>
            <td>
                <input type="text" name="bo_write_point" value="<?php echo $board['bo_write_point'] ?>" id="bo_write_point" required class="required frm_input" size="5">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_write_point" value="1" id="chk_all_write_point">
                <label for="chk_all_write_point"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_comment_point"><?php _e('Comment points', 'gnupress'); //댓글쓰기 포인트?><strong class="sound_only"><?php _e('required', 'gnupress'); ?></strong></label></th>
            <td>
                <input type="text" name="bo_comment_point" value="<?php echo $board['bo_comment_point'] ?>" id="bo_comment_point" required class="required frm_input" size="5">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_comment_point" value="1" id="chk_all_comment_point">
                <label for="chk_all_comment_point"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_download_point"><?php _e('Download points', 'gnupress'); //다운로드 포인트?><strong class="sound_only"><?php _e('required', 'gnupress'); ?></strong></label></th>
            <td>
                <input type="text" name="bo_download_point" value="<?php echo $board['bo_download_point'] ?>" id="bo_download_point" required class="required frm_input" size="5">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_download_point" value="1" id="chk_all_download_point">
                <label for="chk_all_download_point"><?php _e('all apply', 'gnupress');?></label>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

</form>
<script>
function delete_confirm2(msg)
{
    if(confirm(msg))
        return true;
    else
        return false;
}

function fboardcopy_check(f)
{
    if (f.bo_table.value == f.target_table.value) {
        alert("<?php _e('copy name must be different from original', 'gnupress');?>");
        return false;
    }

    return true;
}

function set_point(f) {
    if (f.chk_grp_point.checked) {
        f.bo_read_point.value = "<?php echo $config['cf_read_point'] ?>";
        f.bo_write_point.value = "<?php echo $config['cf_write_point'] ?>";
        f.bo_comment_point.value = "<?php echo $config['cf_comment_point'] ?>";
        f.bo_download_point.value = "<?php echo $config['cf_download_point'] ?>";
    } else {
        f.bo_read_point.value     = f.bo_read_point.defaultValue;
        f.bo_write_point.value    = f.bo_write_point.defaultValue;
        f.bo_comment_point.value  = f.bo_comment_point.defaultValue;
        f.bo_download_point.value = f.bo_download_point.defaultValue;
    }
}

jQuery(document).ready(function($) {
    var $c_box = $("#g5_board_copy_el").dialog({
        'dialogClass' : 'wp-dialog',
        'modal' : false,
        'autoOpen' : false,
        'closeOnEscape' : true,
        'minWidth' : 310,
        'width' : 440,
        'buttons' : [
            {
            'text' : 'Close',
            'class' : 'button-primary',
            'click' : function() {
                            $(this).dialog('close');
                        }
            }
        ]
    });
    
    $(".board_copy").on("click", function(e){
        e.preventDefault();
        var title = $(this).attr("title");
        $("span.ui-dialog-title").text(title);
        ($c_box.dialog("isOpen") == false) ? $c_box.dialog("open") : $c_box.dialog("close");
    });

    $(".copy_shortcode").on("click", function(e){
        e.preventDefault();
        var s = $(this).prev("strong").text();
        if (window.clipboardData && clipboardData.setData) {
            clipboardData.setData('text', s);
        } else {
            prompt("<?php _e('Press Ctrl + C to copy the shortcode.', 'gnupress');?>", s);
        }
    });
});
</script>

<div id="g5_board_copy_el">

    <form action="<?php echo g5_form_action_url($form_action_url);?>" onsubmit="return fboardcopy_check(this);" method="post">
    <?php wp_nonce_field( 'bbs-adm-copy' ); ?>
    <input type="hidden" name="bo_table" value="<?php echo esc_attr( $bo_table ); ?>">
    <input type="hidden" name="g5_admin_post" value="bbs_copy" />
    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption><?php echo $g5['title']; ?></caption>
        <tbody>
        <tr>
            <th scope="col"><?php _e('Original table name', 'gnupress');?></th>
            <td><?php echo $bo_table ?></td>
        </tr>
        <tr>
            <th scope="col"><label for="target_table"><?php _e('Copy the table name', 'gnupress');?><strong class="sound_only"><?php _e('required', 'gnupress');?></strong></label></th>
            <td><input type="text" name="target_table" id="target_table" required class="required alnum_ frm_input" maxlength="20"><br /><?php _e('Allowed characters Alphabetic and Number and underbar( no whitespace )', 'gnupress'); ?></td>
        </tr>
        <tr>
            <th scope="col"><label for="target_subject"><?php _e('Board Subject', 'gnupress');?><strong class="sound_only"><?php _e('required', 'gnupress');?></strong></label></th>
            <td><input type="text" name="target_subject" value="[<?php echo _e('carbon copy', 'gnupress');?>] <?php echo $board['bo_subject'] ?>" id="target_subject" required class="required frm_input" maxlength="120"></td>
        </tr>
        <tr>
            <th scope="col"><?php _e('Copy Type', 'gnupress');  //복사유형?></th>
            <td>
                <input type="radio" name="copy_case" value="schema_only" id="copy_case" checked>
                <label for="copy_case"><?php _e('Only Structure', 'gnupress');  //구조만?></label>
                <input type="radio" name="copy_case" value="schema_data_both" id="copy_case2">
                <label for="copy_case2"><?php _e('Structure and Data', 'gnupress');  //구조와 데이터?></label>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
    <div class="btn_confirm01 btn_confirm">
        <input type="submit" class="btn_submit" value="<?php _e('Copy', 'gnupress');?>">
    </div>
    </form>
</div>