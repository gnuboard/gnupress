<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$home_url = home_url();

$pg_anchor = '<ul class="anchor">
    <li><a href="#anc_cf_basic">기본환경</a></li>
    <li><a href="#anc_cf_board">게시판기본</a></li>
    <li><a href="#anc_cf_mail">기본메일환경</a></li>
    <li><a href="#anc_cf_article_mail">글작성메일</a></li>
</ul>';

$frm_submit = '<div class="btn_confirm01 btn_confirm">
    <input type="submit" value="확인" class="btn btn-primary" accesskey="s">
    <a href="'.$home_url.'/" class="btn btn-info">메인으로</a>
</div>';
?>

<form name="fconfigform" id="fconfigform" method="post" action="<?php echo g5_form_action_url(admin_url("admin.php?page=g5_board_admin")); ?>" onsubmit="return fconfigform_submit(this);" class="bootstrap">
<?php wp_nonce_field( 'g5_config_form_check' ); ?>
<input type="hidden" name="g5_config_form" value="update" >

<section id="anc_cf_basic">
    <h2 class="h2_frm">홈페이지 기본환경 설정</h2>
    <?php echo $pg_anchor ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>홈페이지 기본환경 설정</caption>
        <colgroup>
            <col class="grid_4">
            <col>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row">현재버젼</th>
            <td colspan="3">
                <?php echo $g5_options['version']; ?>
            </td>
        </tr>
        <!--
        <tr>
            <th scope="row">파일 최적화 시간</th>
            <td colspan="3">
                <?php echo $g5_options['optimize_date']; ?>
            </td>
        </tr>
        -->
        <tr>
            <th scope="row"><label for="cf_use_point">포인트 사용</label></th>
            <td colspan="3"><input type="checkbox" name="cf_use_point" value="1" id="cf_use_point" <?php echo $config['cf_use_point']?'checked':''; ?>> 사용</td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_login_point">로그인시 포인트<strong class="sound_only">필수</strong></label></th>
            <td colspan="3">
                <?php echo g5_help('회원이 로그인시 하루에 한번만 적립') ?>
                <input type="text" name="cf_login_point" value="<?php echo $config['cf_login_point'] ?>" id="cf_login_point" required class="required frm_input" size="2"> 점
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="cf_cut_name">이름(닉네임) 표시</label></th>
            <td colspan="3">
                <input type="text" name="cf_cut_name" value="<?php echo $config['cf_cut_name'] ?>" id="cf_cut_name" class="frm_input" size="5"> 자리만 표시
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="cf_point_term">포인트 유효기간</label></th>
            <td colspan="3">
                <?php echo g5_help('기간을 0으로 설정시 포인트 유효기간이 적용되지 않습니다.') ?>
                <input type="text" name="cf_point_term" value="<?php echo $config['cf_point_term']; ?>" id="cf_point_term" required class="required frm_input" size="5"> 일
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="cf_write_pages">페이지 표시 수<strong class="sound_only">필수</strong></label></th>
            <td><input type="text" name="cf_write_pages" value="<?php echo $config['cf_write_pages'] ?>" id="cf_write_pages" required class="required numeric frm_input" size="3"> 페이지씩 표시</td>
            <th scope="row"><label for="cf_mobile_pages">모바일 페이지 표시 수<strong class="sound_only">필수</strong></label></th>
            <td><input type="text" name="cf_mobile_pages" value="<?php echo $config['cf_mobile_pages'] ?>" id="cf_mobile_pages" required class="required numeric frm_input" size="3"> 페이지씩 표시</td>
        </tr>

        <tr>
            <th scope="row"><label for="cf_editor">에디터 선택</label></th>
            <td colspan="3">
                <?php echo g5_help(g5_get_plugin_url('editor').' 밑의 DHTML 에디터 폴더를 선택합니다.') ?>
                <select name="cf_editor" id="cf_editor">
                <?php
                $arr = g5_get_skin_dir('', g5_get_plugin_path('editor') );
                for ($i=0; $i<count($arr); $i++) {
                    if ($i == 0) echo "<option value=\"\">사용안함</option>";
                    echo "<option value=\"".$arr[$i]."\"".g5_get_selected($config['cf_editor'], $arr[$i]).">".$arr[$i]."</option>\n";
                }
                ?>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_captcha_mp3">음성캡챠 선택<strong class="sound_only">필수</strong></label></th>
            <td colspan="3">
                <?php echo g5_help(g5_get_plugin_url('captcha').'/mp3 밑의 음성 폴더를 선택합니다.') ?>
                <select name="cf_captcha_mp3" id="cf_captcha_mp3" required class="required">
                <?php
                $arr = g5_get_skin_dir('mp3', g5_get_plugin_path('kcaptcha') );
                for ($i=0; $i<count($arr); $i++) {
                    if ($i == 0) echo "<option value=\"\">선택</option>";
                    echo "<option value=\"".$arr[$i]."\"".g5_get_selected($config['cf_captcha_mp3'], $arr[$i]).">".$arr[$i]."</option>\n";
                }
                ?>
                </select>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="cf_new_page_id">스크랩 및 포인트 적용페이지(새창)<strong class="sound_only">필수</strong></label></th>
            <td colspan="3">
                <?php echo g5_help('스크랩 및 포인트 등을 새창으로 적용할 페이지를 선택해 주세요.'); ?>
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
            <th scope="row"><label for="cf_use_copy_log">복사, 이동시 로그</label></th>
            <td colspan="3">
                <?php echo g5_help('게시물 아래에 누구로 부터 복사, 이동됨 표시') ?>
                <input type="checkbox" name="cf_use_copy_log" value="1" id="cf_use_copy_log" <?php echo $config['cf_use_copy_log']?'checked':''; ?>> 남김
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="cf_use_search_include">전체 검색시 게시판 포함 여부</label></th>
            <td colspan="3">
                <?php echo g5_help('워드프레스 전체 검색시 게시판 내용을 검색에 포함시킵니다. ( 검색에 문제가 있을시 체크 해제해 주세요. )') ?>
                <input type="checkbox" name="cf_use_search_include" value="1" id="cf_use_search_include" <?php echo $config['cf_use_search_include']?'checked':''; ?>> 체크시 포함
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="cf_syndi_token">네이버 신디케이션 연동키</label></th>
            <td colspan="3">
                <?php if (!function_exists('curl_init')) echo g5_help('<b>경고) curl이 지원되지 않아 네이버 신디케이션을 사용할수 없습니다.</b>'); ?>
                <?php echo g5_help('네이버 신디케이션 연동키(token)을 입력하면 네이버 신디케이션을 사용할 수 있습니다.<br>연동키는 <a href="http://webmastertool.naver.com/" target="_blank"><u>네이버 웹마스터도구</u></a> -> 네이버 신디케이션에서 발급할 수 있습니다.') ?>
                <input type="text" name="cf_syndi_token" value="<?php echo $config['cf_syndi_token'] ?>" id="cf_syndi_token" class="frm_input" size="70">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_syndi_except">네이버 신디케이션 제외게시판</label></th>
            <td colspan="3">
                <?php echo g5_help('네이버 신디케이션 수집에서 제외할 게시판 아이디를 | 로 구분하여 입력하십시오. 예) notice|adult<br>참고로 글읽기 권한이 -1 인 게시판만 수집되며, 비밀글은 신디케이션 수집에서 제외됩니다.') ?>
                <input type="text" name="cf_syndi_except" value="<?php echo $config['cf_syndi_except'] ?>" id="cf_syndi_except" class="frm_input" size="70">
            </td>
        </tr>

        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_cf_board">
    <h2 class="h2_frm">게시판 기본 설정</h2>
    <?php echo $pg_anchor ?>
    <div class="local_desc02 local_desc">
        <p>각 게시판 관리에서 개별적으로 설정 가능합니다.</p>
    </div>
    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>게시판 기본 설정</caption>
        <colgroup>
            <col class="grid_4">
            <col>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>

        <tr>
            <th scope="row"><label for="cf_delay_sec">글쓰기 간격<strong class="sound_only">필수</strong></label></th>
            <td><input type="text" name="cf_delay_sec" value="<?php echo $config['cf_delay_sec'] ?>" id="cf_delay_sec" required class="required numeric frm_input" size="3"> 초 지난후 가능</td>
            <th scope="row"><label for="cf_link_target">새창 링크</label></th>
            <td>
                <?php echo g5_help('글내용중 자동 링크되는 타켓을 지정합니다.') ?>
                <select name="cf_link_target" id="cf_link_target">
                    <option value="_blank"<?php echo g5_get_selected($config['cf_link_target'], '_blank') ?>>_blank</option>
                    <option value="_self"<?php echo g5_get_selected($config['cf_link_target'], '_self') ?>>_self</option>
                    <option value="_top"<?php echo g5_get_selected($config['cf_link_target'], '_top') ?>>_top</option>
                    <option value="_new"<?php echo g5_get_selected($config['cf_link_target'], '_new') ?>>_new</option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_read_point">글읽기 포인트<strong class="sound_only">필수</strong></label></th>
            <td><input type="text" name="cf_read_point" value="<?php echo $config['cf_read_point'] ?>" id="cf_read_point" required class="required frm_input" size="3"> 점</td>
            <th scope="row"><label for="cf_write_point">글쓰기 포인트</label></th>
            <td><input type="text" name="cf_write_point" value="<?php echo $config['cf_write_point'] ?>" id="cf_write_point" required class="required frm_input" size="3"> 점</td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_comment_point">댓글쓰기 포인트</label></th>
            <td><input type="text" name="cf_comment_point" value="<?php echo $config['cf_comment_point'] ?>" id="cf_comment_point" required class="required frm_input" size="3"> 점</td>
            <th scope="row"><label for="cf_download_point">다운로드 포인트</label></th>
            <td><input type="text" name="cf_download_point" value="<?php echo $config['cf_download_point'] ?>" id="cf_download_point" required class="required frm_input" size="3"> 점</td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_search_part">검색 단위</label></th>
            <td colspan="3"><input type="text" name="cf_search_part" value="<?php echo $config['cf_search_part'] ?>" id="cf_search_part" class="frm_input" size="4"> 건 단위로 검색</td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_image_extension">이미지 업로드 확장자</label></th>
            <td colspan="3">
                <?php echo g5_help('게시판 글작성시 이미지 파일 업로드 가능 확장자. | 로 구분') ?>
                <input type="text" name="cf_image_extension" value="<?php echo $config['cf_image_extension'] ?>" id="cf_image_extension" class="frm_input" size="70">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_flash_extension">플래쉬 업로드 확장자</label></th>
            <td colspan="3">
                <?php echo g5_help('게시판 글작성시 플래쉬 파일 업로드 가능 확장자. | 로 구분') ?>
                <input type="text" name="cf_flash_extension" value="<?php echo $config['cf_flash_extension'] ?>" id="cf_flash_extension" class="frm_input" size="70">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_movie_extension">동영상 업로드 확장자</label></th>
            <td colspan="3">
                <?php echo g5_help('게시판 글작성시 동영상 파일 업로드 가능 확장자. | 로 구분') ?>
                <input type="text" name="cf_movie_extension" value="<?php echo $config['cf_movie_extension'] ?>" id="cf_movie_extension" class="frm_input" size="70">
            </td>
        </tr>

        </tbody>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_cf_mail">
    <h2 class="h2_frm">기본 메일 환경 설정</h2>
    <?php echo $pg_anchor ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>기본 메일 환경 설정</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="cf_email_use">메일발송 사용</label></th>
            <td>
                <?php echo g5_help('체크하지 않으면 메일발송을 아예 사용하지 않습니다. 메일 테스트도 불가합니다.') ?>
                <input type="checkbox" name="cf_email_use" value="1" id="cf_email_use" <?php echo $config['cf_email_use']?'checked':''; ?>> 사용
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_formmail_is_member">폼메일 사용 여부</label></th>
            <td>
                <?php echo g5_help('체크하지 않으면 비회원도 사용 할 수 있습니다.') ?>
                <input type="checkbox" name="cf_formmail_is_member" value="1" id="cf_formmail_is_member" <?php echo $config['cf_formmail_is_member']?'checked':''; ?>> 회원만 사용
            </td>
        </tr>
        </table>
    </div>
</section>

<?php echo $frm_submit; ?>

<section id="anc_cf_article_mail">
    <h2 class="h2_frm">게시판 글 작성 시 메일 설정</h2>
    <?php echo $pg_anchor ?>

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>게시판 글 작성 시 메일 설정</caption>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="cf_email_wr_super_admin">최고관리자</label></th>
            <td>
                <?php echo g5_help('최고관리자에게 메일을 발송합니다.') ?>
                <input type="checkbox" name="cf_email_wr_super_admin" value="1" id="cf_email_wr_super_admin" <?php echo $config['cf_email_wr_super_admin']?'checked':''; ?>> 사용
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_email_wr_board_admin">게시판관리자</label></th>
            <td>
                <?php echo g5_help('게시판관리자에게 메일을 발송합니다.') ?>
                <input type="checkbox" name="cf_email_wr_board_admin" value="1" id="cf_email_wr_board_admin" <?php echo $config['cf_email_wr_board_admin']?'checked':''; ?>> 사용
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_email_wr_write">원글작성자</label></th>
            <td>
                <?php echo g5_help('게시자님께 메일을 발송합니다.') ?>
                <input type="checkbox" name="cf_email_wr_write" value="1" id="cf_email_wr_write" <?php echo $config['cf_email_wr_write']?'checked':''; ?>> 사용
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="cf_email_wr_comment_all">댓글작성자</label></th>
            <td>
                <?php echo g5_help('원글에 댓글이 올라오는 경우 댓글 쓴 모든 분들께 메일을 발송합니다.') ?>
                <input type="checkbox" name="cf_email_wr_comment_all" value="1" id="cf_email_wr_comment_all" <?php echo $config['cf_email_wr_comment_all']?'checked':''; ?>> 사용
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