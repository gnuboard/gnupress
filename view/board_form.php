<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

wp_enqueue_script('jquery-ui-dialog');
wp_enqueue_style("wp-jquery-ui-dialog");

//게시판 썸네일 삭제 url 설정
$thumbnail_delete_url = home_url(add_query_arg( array('g5_rq'=>'board_thumbnail_delete') ));

$readonly = '';
$sound_only = '';
$pg_anchor = '<ul class="anchor">
    <li><a href="#anc_bo_basic">기본 설정</a></li>
    <li><a href="#anc_bo_auth">권한 설정</a></li>
    <li><a href="#anc_bo_function">기능 설정</a></li>
    <li><a href="#anc_bo_design">디자인/양식</a></li>
    <li><a href="#anc_bo_point">포인트 설정</a></li>
</ul>';

$frm_submit = '<div class="btn_confirm">
    <input type="submit" value="확인" class="btn btn-primary" accesskey="s">
    <a href="'.$list_page_url.'" class="btn btn-info">목록</a>'.PHP_EOL;

if ($w == 'u'){
    $frm_submit .= ' <button class="btn btn-info board_copy" title="게시판복사" >게시판복사</button>';
    if($bbs_direct_url){
        $frm_submit .= ' <a href="'.$bbs_direct_url.'" class="btn btn-info">게시판 바로가기</a>';
    }
    $frm_submit .= ' <a href="'.$thumbnail_delete_url.'" class="btn btn-info" onclick="return delete_confirm2(\'게시판 썸네일 파일을 삭제하시겠습니까?\');">게시판 썸네일 삭제</a>'.PHP_EOL;
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

<section id="anc_bo_basic">
    <h2 class="h2_frm">게시판 기본 설정</h2>
    <?php echo $pg_anchor ?>

    <div class="tbl_frm01 tbl_wrap">
        <table class="table-bordered table-striped table-condensed">
        <caption>게시판 기본 설정</caption>
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
                    영문자, 숫자, _ 만 가능 (공백없이 20자 이내)
                <?php } else { ?>
                    <?php if($bbs_direct_url){ ?>
                        <a href="<?php echo $bbs_direct_url ?>" class="btn_frmline">게시판 바로가기</a>
                    <?php } ?>
                    <a href="<?php echo $list_page_url ?>" class="btn_frmline">목록으로</a>
                <?php } ?>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_subject">게시판 제목<strong class="sound_only">필수</strong></label></th>
            <td colspan="2">
                <input type="text" name="bo_subject" value="<?php echo g5_get_text($board['bo_subject']) ?>" id="bo_subject" required class="required frm_input" size="80" maxlength="120">
            </td>
        </tr>
        <?php if(!$w){ ?>
        <tr>
            <th scope="row">페이지 생성 여부</th>
            <td colspan="2">
                <input type="checkbox" name="bo_auto_install" id="bo_auto_install" value="1" checked="checked" >
                <label for="bo_auto_install">체크시 게시판 페이지를 자동으로 생성합니다.</label>
            </td>
        </tr>
        <?php } ?>
        <tr>
            <th scope="row"><label for="bo_category_list">분류</label></th>
            <td>
                <?php echo g5_help('분류와 분류 사이는 | 로 구분하세요. (예: 질문|답변) 첫자로 #은 입력하지 마세요. (예: #질문|#답변 [X])') ?>
                <input type="text" name="bo_category_list" value="<?php echo g5_get_text($board['bo_category_list']) ?>" id="bo_category_list" class="frm_input" size="70">
                <input type="checkbox" name="bo_use_category" value="1" id="bo_use_category" <?php echo $board['bo_use_category']?'checked':''; ?>>
                <label for="bo_use_category">사용</label>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_category_list" value="1" id="chk_all_category_list">
                <label for="chk_all_category_list">전체적용</label>
            </td>
        </tr>
        <?php if ($w == 'u') { ?>
        <tr>
            <th scope="row"><label for="bo_wp_pageid">적용할페이지</label></th>
            <td colspan="2">
                <?php echo g5_help('게시판을 적용할 페이지를 선택해 주세요. 게시판 적용은 페이지당 1개만 적용할수 있습니다.'); ?>
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
            <th scope="row">게시판 Shortcode( 고정 )</th>
            <td colspan="2">
                <?php echo g5_help('글 또는 페이지에 게시판을 적용시킬수 있습니다.'); ?>
                <strong><?php echo '['.G5_NAME.' bo_table='.$bo_table.']' ; ?></strong> <button type="button" class="copy_shortcode button">복사하기</button>
            </td>
        </tr>

        <tr>
            <th scope="row">최신글 Shortcode( 고정 )</th>
            <td colspan="2">
                <?php echo g5_help('위의 적용할 페이지를 설정하였다면 url=페이지주소를 생략하셔도 됩니다. rows는 게시물갯수입니다.'); ?>
                <strong><?php echo '['.G5_NAME.'_latest bo_table='.$bo_table.' url=페이지주소 rows=5]' ; ?></strong> <button type="button" class="copy_shortcode button">복사하기</button>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="proc_count">카운트 조정</label></th>
            <td colspan="2">
                <?php echo g5_help('현재 원글수 : '.number_format($board['bo_count_write']).', 현재 댓글수 : '.number_format($board['bo_count_comment'])."\n".'게시판 목록에서 글의 번호가 맞지 않을 경우에 체크하십시오.') ?>
                <input type="checkbox" name="proc_count" value="1" id="proc_count">
            </td>
        </tr>
        <?php } ?>
        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_bo_auth">
    <h2 class="h2_frm">게시판 권한 설정</h2>
    <?php echo $pg_anchor ?>

    <div class="tbl_frm01 tbl_wrap">
        <table class="table-bordered table-striped table-condensed">
        <caption>게시판 권한 설정</caption>
        <colgroup>
            <col class="grid_4">
            <col>
            <col class="grid_3">
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="bo_admin">게시판 관리자</label></th>
            <td>
                <input type="text" name="bo_admin" value="<?php echo $board['bo_admin'] ?>" id="bo_admin" class="frm_input" maxlength="20">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_admin" value="1" id="chk_all_admin">
                <label for="chk_all_admin">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_list_level">목록보기 권한</label></th>
            <td>
                <?php echo g5_help('워드프레스 레벨 기본권한은 다음과 같습니다. <br>권한 -1 : 비회원<br>권한 0 : 구독자 (Subscriber) <br>권한 1 : 기여자 (Contributor) <br>권한 2 : 글쓴이 (Author) <br>권한 7 : 편집자 (Editor) <br>권한 10 : 관리자 (Administrator)') ?>
                <?php echo g5_get_number_select('bo_list_level', -1, 10, $board['bo_list_level']) ?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_list_level" value="1" id="chk_all_list_level">
                <label for="chk_all_list_level">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_read_level">글읽기 권한</label></th>
            <td>
                <?php echo g5_get_number_select('bo_read_level', -1, 10, $board['bo_read_level']) ?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_read_level" value="1" id="chk_all_read_level">
                <label for="chk_all_read_level">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_write_level">글쓰기 권한</label></th>
            <td>
                <?php echo g5_get_number_select('bo_write_level', -1, 10, $board['bo_write_level']) ?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_write_level" value="1" id="chk_all_write_level">
                <label for="chk_all_write_level">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_comment_level">댓글쓰기 권한</label></th>
            <td>
                <?php echo g5_get_number_select('bo_comment_level', -1, 10, $board['bo_comment_level']) ?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_comment_level" value="1" id="chk_all_comment_level">
                <label for="chk_all_comment_level">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_link_level">링크 권한</label></th>
            <td>
                <?php echo g5_get_number_select('bo_link_level', -1, 10, $board['bo_link_level']) ?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_link_level" value="1" id="chk_all_link_level">
                <label for="chk_all_link_level">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_upload_level">업로드 권한</label></th>
            <td>
                <?php echo g5_get_number_select('bo_upload_level', -1, 10, $board['bo_upload_level']) ?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_upload_level" value="1" id="chk_all_upload_level">
                <label for="chk_all_upload_level">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_download_level">다운로드 권한</label></th>
            <td>
                <?php echo g5_get_number_select('bo_download_level', -1, 10, $board['bo_download_level']) ?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_download_level" value="1" id="chk_all_download_level">
                <label for="chk_all_download_level">전체적용</label>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_bo_function">
    <h2 class="h2_frm">게시판 기능 설정</h2>
    <?php echo $pg_anchor ?>

    <div class="tbl_frm01 tbl_wrap">
        <table class="table-bordered table-striped table-condensed">
        <caption>게시판 기능 설정</caption>
        <colgroup>
            <col class="grid_4">
            <col>
            <col class="grid_3">
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="bo_count_modify">원글 수정 불가<strong class="sound_only">필수</strong></label></th>
            <td>
                 <?php echo g5_help('댓글의 수가 설정 수 이상이면 원글을 수정할 수 없습니다. 0으로 설정하시면 댓글 수에 관계없이 수정할 수있습니다.'); ?>
                댓글 <input type="text" name="bo_count_modify" value="<?php echo $board['bo_count_modify'] ?>" id="bo_count_modify" required class="required numeric frm_input" size="3">개 이상 달리면 수정불가
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_count_modify" value="1" id="chk_all_count_modify">
                <label for="chk_all_count_modify">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_count_delete">원글 삭제 불가<strong class="sound_only">필수</strong></label></th>
            <td>
                댓글 <input type="text" name="bo_count_delete" value="<?php echo $board['bo_count_delete'] ?>" id="bo_count_delete" required class="required numeric frm_input" size="3">개 이상 달리면 삭제불가
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_count_delete" value="1" id="chk_all_count_delete">
                <label for="chk_all_count_delete">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_sideview">글쓴이 사이드뷰</label></th>
            <td>
                <input type="checkbox" name="bo_use_sideview" value="1" id="bo_use_sideview" <?php echo $board['bo_use_sideview']?'checked':''; ?>>
                사용 (글쓴이 클릭시 나오는 레이어 메뉴)
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_sideview" value="1" id="chk_all_use_sideview">
                <label for="chk_all_use_sideview">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_secret">비밀글 사용</label></th>
            <td>
                <?php echo g5_help('"체크박스"는 글작성시 비밀글 체크가 가능합니다. "무조건"은 작성되는 모든글을 비밀글로 작성합니다. (관리자는 체크박스로 출력합니다.) 스킨에 따라 적용되지 않을 수 있습니다.') ?>
                <select id="bo_use_secret" name="bo_use_secret">
                    <?php echo g5_option_selected(0, $board['bo_use_secret'], "사용하지 않음"); ?>
                    <?php echo g5_option_selected(1, $board['bo_use_secret'], "체크박스"); ?>
                    <?php echo g5_option_selected(2, $board['bo_use_secret'], "무조건"); ?>
                </select>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_secret" value="1" id="chk_all_use_secret">
                <label for="chk_all_use_secret">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_dhtml_editor">DHTML 에디터 사용</label></th>
            <td>
                <?php echo g5_help('글작성시 내용을 DHTML 에디터 기능으로 사용할 것인지 설정합니다. 스킨에 따라 적용되지 않을 수 있습니다.') ?>
                <input type="checkbox" name="bo_use_dhtml_editor" value="1" <?php echo $board['bo_use_dhtml_editor']?'checked':''; ?> id="bo_use_dhtml_editor">
                사용
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_dhtml_editor" value="1" id="chk_all_use_dhtml_editor">
                <label for="chk_all_use_dhtml_editor">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_rss_view">RSS 보이기 사용</label></th>
            <td>
                <?php echo g5_help('비회원 글읽기가 가능하고 RSS 보이기 사용에 체크가 되어야만 RSS 지원을 합니다.') ?>
                <input type="checkbox" name="bo_use_rss_view" value="1" <?php echo $board['bo_use_rss_view']?'checked':''; ?> id="bo_use_rss_view">
                사용
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_rss_view" value="1" id="chk_all_use_rss_view">
                <label for="chk_all_use_rss_view">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_good">추천 사용</label></th>
            <td>
                <input type="checkbox" name="bo_use_good" value="1" <?php echo $board['bo_use_good']?'checked':''; ?> id="bo_use_good">
                사용
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_good" value="1" id="chk_all_use_good">
                <label for="chk_all_use_good">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_nogood">비추천 사용</label></th>
            <td>
                <input type="checkbox" name="bo_use_nogood" value="1" id="bo_use_nogood" <?php echo $board['bo_use_nogood']?'checked':''; ?>>
                사용
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_nogood" value="1" id="chk_all_use_nogood">
                <label for="chk_all_use_nogood">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_ip_view">IP 보이기 사용</label></th>
            <td>
                <input type="checkbox" name="bo_use_ip_view" value="1" id="bo_use_ip_view" <?php echo $board['bo_use_ip_view']?'checked':''; ?>>
                사용
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_ip_view" value="1" id="chk_all_use_ip_view">
                <label for="chk_all_use_ip_view">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_list_content">목록에서 내용 사용</label></th>
            <td>
                <?php echo g5_help("목록에서 게시판 제목외에 내용도 읽어와야 할 경우에 설정하는 옵션입니다. 기본은 사용하지 않습니다."); ?>
                <input type="checkbox" name="bo_use_list_content" value="1" id="bo_use_list_content" <?php echo $board['bo_use_list_content']?'checked':''; ?>>
                사용 (사용시 속도가 느려질 수 있습니다.)
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_list_content" value="1" id="chk_all_use_list_content">
                <label for="chk_all_use_list_content">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_list_file">목록에서 파일 사용</label></th>
            <td>
                <?php echo g5_help("목록에서 게시판 첨부파일을 읽어와야 할 경우에 설정하는 옵션입니다. 기본은 사용하지 않습니다."); ?>
                <input type="checkbox" name="bo_use_list_file" value="1" id="bo_use_list_file" <?php echo $board['bo_use_list_file']?'checked':''; ?>>
                사용 (사용시 속도가 느려질 수 있습니다.)
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_list_file" value="1" id="chk_all_use_list_file">
                <label for="chk_all_use_list_file">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_list_view">전체목록보이기 사용</label></th>
            <td>
                <input type="checkbox" name="bo_use_list_view" value="1" id="bo_use_list_view" <?php echo $board['bo_use_list_view']?'checked':''; ?>>
                사용
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_list_view" value="1" id="chk_all_use_list_view">
                <label for="chk_all_use_list_view">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_email">메일발송 사용</label></th>
            <td>
                <input type="checkbox" name="bo_use_email" value="1" id="bo_use_email" <?php echo $board['bo_use_email']?'checked':''; ?>>
                사용
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_email" value="1" id="chk_all_use_email">
                <label for="chk_all_use_email">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_upload_count">파일 업로드 개수<strong class="sound_only">필수</strong></label></th>
            <td>
                <?php echo g5_help('게시물 한건당 업로드 할 수 있는 파일의 최대 개수 (0 은 파일첨부 사용하지 않음)') ?>
                <input type="text" name="bo_upload_count" value="<?php echo $board['bo_upload_count'] ?>" id="bo_upload_count" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_upload_count" value="1" id="chk_all_upload_count">
                <label for="chk_all_upload_count">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_upload_size">파일 업로드 용량<strong class="sound_only">필수</strong></label></th>
            <td>
                <?php echo g5_help('최대 '.ini_get("upload_max_filesize").' 이하 업로드 가능, 1 MB = 1,048,576 bytes') ?>
                업로드 파일 한개당 <input type="text" name="bo_upload_size" value="<?php echo $board['bo_upload_size'] ?>" id="bo_upload_size" required class="required numeric frm_input"  size="10"> bytes 이하
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_upload_size" value="1" id="chk_all_upload_size">
                <label for="chk_all_upload_size">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_file_content">파일 설명 사용</label></th>
            <td>
                <input type="checkbox" name="bo_use_file_content" value="1" id="bo_use_file_content" <?php echo $board['bo_use_file_content']?'checked':''; ?>>사용
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_file_content" value="1" id="chk_all_use_file_content">
                <label for="chk_all_use_file_content">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_write_min">최소 글수 제한</label></th>
            <td>
                <?php echo g5_help('글 입력시 최소 글자수를 설정. 0을 입력하거나 최고관리자, DHTML 에디터 사용시에는 검사하지 않음') ?>
                <input type="text" name="bo_write_min" value="<?php echo $board['bo_write_min'] ?>" id="bo_write_min" class="numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_write_min" value="1" id="chk_all_write_min">
                <label for="chk_all_write_min">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_write_max">최대 글수 제한</label></th>
            <td>
                <?php echo g5_help('글 입력시 최대 글자수를 설정. 0을 입력하거나 최고관리자, DHTML 에디터 사용시에는 검사하지 않음') ?>
                <input type="text" name="bo_write_max" value="<?php echo $board['bo_write_max'] ?>" id="bo_write_max" class="numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_write_max" value="1" id="chk_all_write_max">
                <label for="chk_all_write_max">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_comment_min">최소 댓글수 제한</label></th>
            <td>
                <?php echo g5_help('댓글 입력시 최소 글자수를 설정. 0을 입력하면 검사하지 않음') ?>
                <input type="text" name="bo_comment_min" value="<?php echo $board['bo_comment_min'] ?>" id="bo_comment_min" class="numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_comment_min" value="1" id="chk_all_comment_min">
                <label for="chk_all_comment_min">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_comment_max">최대 댓글수 제한</label></th>
            <td>
                <?php echo g5_help('댓글 입력시 최대 글자수를 설정. 0을 입력하면 검사하지 않음') ?>
                <input type="text" name="bo_comment_max" value="<?php echo $board['bo_comment_max'] ?>" id="bo_comment_max" class="numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_comment_max" value="1" id="chk_all_comment_max">
                <label for="chk_all_comment_max">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_search">전체 검색 사용</label></th>
            <td>
                <input type="checkbox" name="bo_use_search" value="1" id="bo_use_search" <?php echo $board['bo_use_search']?'checked':''; ?>>
                사용
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_search" value="1" id="chk_all_use_search">
                <label for="chk_all_use_search">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_use_search">태그 기능 사용</label></th>
            <td>
                <input type="checkbox" name="bo_use_tag" value="1" id="bo_use_tag" <?php echo $board['bo_use_tag']?'checked':''; ?>>
                사용
                <?php if( isset($board['bo_use_tag']) && !empty($board['bo_use_tag']) ){ ?>
                <a href="<?php echo admin_url('admin.php?page=g5_tag_form&amp;bo_table='.$board['bo_table']) ?>" class="button">게시판 태그 설정관리</a>
                <?php } ?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_use_tag" value="1" id="chk_all_use_tag">
                <label for="chk_all_use_tag">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row">번호, 작성자, 조회, 작성일 설정</th>
            <td>
                <input type="hidden" name="bo_chk_fields" value="<?php echo $board['bo_sh_fields']?>" >
                <input type="checkbox" name="bo_sh_fields[]" value="num" id="bo_chk_fields_1" <?php echo $chk_bo_sh_array['num']?> ><label for="bo_chk_fields_1">번호 표시</label>
                <input type="checkbox" name="bo_sh_fields[]" value="writer" id="bo_chk_fields_2" <?php echo $chk_bo_sh_array['writer']?> > <label for="bo_chk_fields_2">작성자 표시</label>
                <input type="checkbox" name="bo_sh_fields[]" value="visit" id="bo_chk_fields_3" <?php echo $chk_bo_sh_array['visit']?> > <label for="bo_chk_fields_3">조회 표시</label>
                <input type="checkbox" name="bo_sh_fields[]" value="wdate" id="bo_chk_fields_4" <?php echo $chk_bo_sh_array['wdate']?> > <label for="bo_chk_fields_4">작성일 표시</label>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_sh_fields" value="1" id="chk_all_sh_fields">
                <label for="chk_all_sh_fields">전체적용</label>
            </td>
        </tr>

        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_bo_design">
    <h2 class="h2_frm">게시판 디자인/양식</h2>
    <?php echo $pg_anchor ?>

    <div class="tbl_frm01 tbl_wrap">
        <table class="table-bordered table-striped table-condensed">
        <caption>게시판 디자인/양식</caption>
        <colgroup>
            <col class="grid_4">
            <col>
            <col class="grid_3">
        </colgroup>
        <tbody>
            <tr>
            <th scope="row"><label for="bo_skin">스킨 디렉토리<strong class="sound_only">필수</strong></label></th>
            <td>
                <?php echo g5_get_skin_select('board', 'bo_skin', 'bo_skin', $board['bo_skin'], 'required'); ?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_skin" value="1" id="chk_all_skin">
                <label for="chk_all_skin">전체적용</label>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="bo_content_head">상단 내용</label></th>
            <td>
                <?php echo g5_editor_html("bo_content_head", g5_get_editor_content($board['bo_content_head'])); ?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_content_head" value="1" id="chk_all_content_head">
                <label for="chk_all_content_head">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_content_tail">하단 내용</label></th>
            <td>
                <?php echo g5_editor_html("bo_content_tail", g5_get_editor_content($board['bo_content_tail'])); ?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_content_tail" value="1" id="chk_all_content_tail">
                <label for="chk_all_content_tail">전체적용</label>
            </td>
        </tr>

         <tr>
            <th scope="row"><label for="bo_insert_content">글쓰기 기본 내용</label></th>
            <td>
                <textarea id="bo_insert_content" name="bo_insert_content" rows="5"><?php echo $board['bo_insert_content'] ?></textarea>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_insert_content" value="1" id="chk_all_insert_content">
                <label for="chk_all_insert_content">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_subject_len">제목 길이<strong class="sound_only">필수</strong></label></th>
            <td>
                <?php echo g5_help('목록에서의 제목 글자수. 잘리는 글은 … 로 표시') ?>
                <input type="text" name="bo_subject_len" value="<?php echo $board['bo_subject_len'] ?>" id="bo_subject_len" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_subject_len" value="1" id="chk_all_subject_len">
                <label for="chk_all_subject_len">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_mobile_subject_len">모바일 제목 길이<strong class="sound_only">필수</strong></label></th>
            <td>
                <?php echo g5_help('목록에서의 제목 글자수. 잘리는 글은 … 로 표시') ?>
                <input type="text" name="bo_mobile_subject_len" value="<?php echo $board['bo_mobile_subject_len'] ?>" id="bo_mobile_subject_len" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_mobile_subject_len" value="1" id="chk_all_mobile_subject_len">
                <label for="chk_all_mobile_subject_len">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_page_rows">페이지당 목록 수<strong class="sound_only">필수</strong></label></th>
            <td>
                <input type="text" name="bo_page_rows" value="<?php echo $board['bo_page_rows'] ?>" id="bo_page_rows" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_page_rows" value="1" id="chk_all_page_rows">
                <label for="chk_all_page_rows">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_mobile_page_rows">모바일 페이지당 목록 수<strong class="sound_only">필수</strong></label></th>
            <td>
                <input type="text" name="bo_mobile_page_rows" value="<?php echo $board['bo_mobile_page_rows'] ?>" id="bo_mobile_page_rows" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_mobile_page_rows" value="1" id="chk_all_mobile_page_rows">
                <label for="chk_all_mobile_page_rows">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_gallery_cols">갤러리 이미지 수<strong class="sound_only">필수</strong></label></th>
            <td>
                <?php echo g5_help('갤러리 형식의 게시판 목록에서 이미지를 한줄에 몇장씩 보여 줄 것인지를 설정하는 값') ?>
                <?php echo g5_get_number_select('bo_gallery_cols', 1, 10, $board['bo_gallery_cols']) ?>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_gallery_cols" value="1" id="chk_all_gallery_cols">
                <label for="chk_all_gallery_cols">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_gallery_width">갤러리 이미지 폭<strong class="sound_only">필수</strong></label></th>
            <td>
                <?php echo g5_help('갤러리 형식의 게시판 목록에서 썸네일 이미지의 폭을 설정하는 값') ?>
                <input type="text" name="bo_gallery_width" value="<?php echo $board['bo_gallery_width'] ?>" id="bo_gallery_width" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_gallery_width" value="1" id="chk_all_gallery_width">
                <label for="chk_all_gallery_width">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_gallery_height">갤러리 이미지 높이<strong class="sound_only">필수</strong></label></th>
            <td>
                <?php echo g5_help('갤러리 형식의 게시판 목록에서 썸네일 이미지의 높이를 설정하는 값') ?>
                <input type="text" name="bo_gallery_height" value="<?php echo $board['bo_gallery_height'] ?>" id="bo_gallery_height" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_gallery_height" value="1" id="chk_all_gallery_height">
                <label for="chk_all_gallery_height">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_mobile_gallery_width">모바일<br>갤러리 이미지 폭<strong class="sound_only">필수</strong></label></th>
            <td>
                <?php echo g5_help('모바일로 접속시 갤러리 형식의 게시판 목록에서 썸네일 이미지의 폭을 설정하는 값') ?>
                <input type="text" name="bo_mobile_gallery_width" value="<?php echo $board['bo_mobile_gallery_width'] ?>" id="bo_mobile_gallery_width" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_mobile_gallery_width" value="1" id="chk_all_mobile_gallery_width">
                <label for="chk_all_mobile_gallery_width">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_mobile_gallery_height">모바일<br>갤러리 이미지 높이<strong class="sound_only">필수</strong></label></th>
            <td>
                <?php echo g5_help('모바일로 접속시 갤러리 형식의 게시판 목록에서 썸네일 이미지의 높이를 설정하는 값') ?>
                <input type="text" name="bo_mobile_gallery_height" value="<?php echo $board['bo_mobile_gallery_height'] ?>" id="bo_mobile_gallery_height" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_mobile_gallery_height" value="1" id="chk_all_mobile_gallery_height">
                <label for="chk_all_mobile_gallery_height">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_table_width">게시판 폭<strong class="sound_only">필수</strong></label></th>
            <td>
                <?php echo g5_help('100 이하는 %') ?>
                <input type="text" name="bo_table_width" value="<?php echo $board['bo_table_width'] ?>" id="bo_table_width" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_table_width" value="1" id="chk_all_table_width">
                <label for="chk_all_table_width">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_image_width">이미지 폭 크기<strong class="sound_only">필수</strong></label></th>
            <td>
                <?php echo g5_help('게시판에서 출력되는 이미지의 폭 크기') ?>
                <input type="text" name="bo_image_width" value="<?php echo $board['bo_image_width'] ?>" id="bo_image_width" required class="required numeric frm_input" size="4"> 픽셀
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_image_width" value="1" id="chk_all_image_width">
                <label for="chk_all_image_width">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_new">새글 아이콘<strong class="sound_only">필수</strong></label></th>
            <td>
                <?php echo g5_help('글 입력후 new 이미지를 출력하는 시간. 0을 입력하시면 아이콘을 출력하지 않습니다.') ?>
                <input type="text" name="bo_new" value="<?php echo $board['bo_new'] ?>" id="bo_new" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_new" value="1" id="chk_all_new">
                <label for="chk_all_new">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_hot">인기글 아이콘<strong class="sound_only">필수</strong></label></th>
            <td>
                <?php echo g5_help('조회수가 설정값 이상이면 hot 이미지 출력. 0을 입력하시면 아이콘을 출력하지 않습니다.') ?>
                <input type="text" name="bo_hot" value="<?php echo $board['bo_hot'] ?>" id="bo_hot" required class="required numeric frm_input" size="4">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_hot" value="1" id="chk_all_hot">
                <label for="chk_all_hot">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_sort_field">리스트 정렬 필드</label></th>
            <td>
                <?php echo g5_help('리스트에서 기본으로 정렬에 사용할 필드를 선택합니다. "기본"으로 사용하지 않으시는 경우 속도가 느려질 수 있습니다.') ?>
                <select id="bo_sort_field" name="bo_sort_field">
                    <option value="" <?php echo g5_get_selected($board['bo_sort_field'], ""); ?>>wr_num, wr_parent : 기본</option>
                    <option value="wr_datetime asc" <?php echo g5_get_selected($board['bo_sort_field'], "wr_datetime asc"); ?>>wr_datetime asc : 날짜 이전것 부터</option>
                    <option value="wr_datetime desc" <?php echo g5_get_selected($board['bo_sort_field'], "wr_datetime desc"); ?>>wr_datetime desc : 날짜 최근것 부터</option>
                    <option value="wr_hit asc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "wr_hit asc, wr_num, wr_parent"); ?>>wr_hit asc : 조회수 낮은것 부터</option>
                    <option value="wr_hit desc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "wr_hit desc, wr_num, wr_parent"); ?>>wr_hit desc : 조회수 높은것 부터</option>
                    <option value="wr_last asc" <?php echo g5_get_selected($board['bo_sort_field'], "wr_last asc"); ?>>wr_last asc : 최근글 이전것 부터</option>
                    <option value="wr_last desc" <?php echo g5_get_selected($board['bo_sort_field'], "wr_last desc"); ?>>wr_last desc : 최근글 최근것 부터</option>
                    <option value="wr_comment asc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "wr_comment asc, wr_num, wr_parent"); ?>>wr_comment asc : 댓글수 낮은것 부터</option>
                    <option value="wr_comment desc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "wr_comment desc, wr_num, wr_parent"); ?>>wr_comment desc : 댓글수 높은것 부터</option>
                    <option value="wr_good asc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "wr_good asc, wr_num, wr_parent"); ?>>wr_good asc : 추천수 낮은것 부터</option>
                    <option value="wr_good desc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "wr_good desc, wr_num, wr_parent"); ?>>wr_good desc : 추천수 높은것 부터</option>
                    <option value="wr_nogood asc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "wr_nogood asc, wr_num, wr_parent"); ?>>wr_nogood asc : 비추천수 낮은것 부터</option>
                    <option value="wr_nogood desc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "wr_nogood desc, wr_num, wr_parent"); ?>>wr_nogood desc : 비추천수 높은것 부터</option>
                    <option value="wr_subject asc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "wr_subject asc, wr_num, wr_parent"); ?>>wr_subject asc : 제목 내림차순</option>
                    <option value="wr_subject desc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "wr_subject desc, wr_num, wr_parent"); ?>>wr_subject desc : 제목 오름차순</option>
                    <option value="wr_name asc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "wr_name asc, wr_num, wr_parent"); ?>>wr_name asc : 글쓴이 내림차순</option>
                    <option value="wr_name desc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "wr_name desc, wr_num, wr_parent"); ?>>wr_name desc : 글쓴이 오름차순</option>
                    <option value="ca_name asc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "ca_name asc, wr_num, wr_parent"); ?>>ca_name asc : 분류명 내림차순</option>
                    <option value="ca_name desc, wr_num, wr_parent" <?php echo g5_get_selected($board['bo_sort_field'], "ca_name desc, wr_num, wr_parent"); ?>>ca_name desc : 분류명 오름차순</option>
                </select>
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_sort_field" value="1" id="chk_all_sort_field">
                <label for="chk_all_sort_field">전체적용</label>
            </td>
        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_bo_point">
    <h2 class="h2_frm">게시판 포인트 설정</h2>
    <?php echo $pg_anchor ?>

    <div class="tbl_frm01 tbl_wrap">
        <table class="table-bordered table-striped table-condensed">
        <caption>게시판 포인트 설정</caption>
        <colgroup>
            <col class="grid_4">
            <col>
            <col class="grid_3">
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="chk_grp_point">기본값으로 설정</label></th>
            <td colspan="2">
                <?php echo g5_help('환경설정에 입력된 포인트로 설정') ?>
                <input type="checkbox" name="chk_grp_point" id="chk_grp_point" onclick="set_point(this.form)">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_read_point">글읽기 포인트<strong class="sound_only">필수</strong></label></th>
            <td>
                <input type="text" name="bo_read_point" value="<?php echo $board['bo_read_point'] ?>" id="bo_read_point" required class="required frm_input" size="5">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_read_point" value="1" id="chk_all_read_point">
                <label for="chk_all_read_point">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_write_point">글쓰기 포인트<strong class="sound_only">필수</strong></label></th>
            <td>
                <input type="text" name="bo_write_point" value="<?php echo $board['bo_write_point'] ?>" id="bo_write_point" required class="required frm_input" size="5">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_write_point" value="1" id="chk_all_write_point">
                <label for="chk_all_write_point">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_comment_point">댓글쓰기 포인트<strong class="sound_only">필수</strong></label></th>
            <td>
                <input type="text" name="bo_comment_point" value="<?php echo $board['bo_comment_point'] ?>" id="bo_comment_point" required class="required frm_input" size="5">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_comment_point" value="1" id="chk_all_comment_point">
                <label for="chk_all_comment_point">전체적용</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="bo_download_point">다운로드 포인트<strong class="sound_only">필수</strong></label></th>
            <td>
                <input type="text" name="bo_download_point" value="<?php echo $board['bo_download_point'] ?>" id="bo_download_point" required class="required frm_input" size="5">
            </td>
            <td class="td_grpset">
                <input type="checkbox" name="chk_all_download_point" value="1" id="chk_all_download_point">
                <label for="chk_all_download_point">전체적용</label>
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
    console.log( f );

    if (f.bo_table.value == f.target_table.value) {
        alert("원본 테이블명과 복사할 테이블명이 달라야 합니다.");
        return false;
    }

    return true;
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
            prompt(" Ctrl+C를 눌러 shortcode를 복사해 주세요.", s);
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
            <th scope="col">원본 테이블명</th>
            <td><?php echo $bo_table ?></td>
        </tr>
        <tr>
            <th scope="col"><label for="target_table">복사 테이블명<strong class="sound_only">필수</strong></label></th>
            <td><input type="text" name="target_table" id="target_table" required class="required alnum_ frm_input" maxlength="20"><br />영문자, 숫자, _ 만 가능 (공백없이)</td>
        </tr>
        <tr>
            <th scope="col"><label for="target_subject">게시판 제목<strong class="sound_only">필수</strong></label></th>
            <td><input type="text" name="target_subject" value="[복사본] <?php echo $board['bo_subject'] ?>" id="target_subject" required class="required frm_input" maxlength="120"></td>
        </tr>
        <tr>
            <th scope="col">복사 유형</th>
            <td>
                <input type="radio" name="copy_case" value="schema_only" id="copy_case" checked>
                <label for="copy_case">구조만</label>
                <input type="radio" name="copy_case" value="schema_data_both" id="copy_case2">
                <label for="copy_case2">구조와 데이터</label>
            </td>
        </tr>
        </tbody>
        </table>
    </div>
    <div class="btn_confirm01 btn_confirm">
        <input type="submit" class="btn_submit" value="복사">
    </div>
    </form>
</div>