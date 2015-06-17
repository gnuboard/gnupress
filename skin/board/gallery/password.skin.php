<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
$delete_str = "";
if ($w == 'x') $delete_str = "댓";
if ($w == 'u') $g5['title'] = $delete_str."글 수정";
else if ($w == 'd' || $w == 'x') $g5['title'] = $delete_str."글 삭제";
else $g5['title'] = $g5['title'];


?>

<!-- 비밀번호 확인 시작 { -->
<div id="pw_confirm" class="mbskin">
    <h1><?php echo $g5['title'] ?></h1>
    <p>
        <?php if ($w == 'u') { ?>
        <strong>작성자만 글을 수정할 수 있습니다.</strong>
        작성자 본인이라면, 글 작성시 입력한 비밀번호를 입력하여 글을 수정할 수 있습니다.
        <?php } else if ($w == 'd' || $w == 'x') {  ?>
        <strong>작성자만 글을 삭제할 수 있습니다.</strong>
        작성자 본인이라면, 글 작성시 입력한 비밀번호를 입력하여 글을 삭제할 수 있습니다.
        <?php } else {  ?>
        <strong>비밀글 기능으로 보호된 글입니다.</strong>
        작성자와 관리자만 열람하실 수 있습니다. 본인이라면 비밀번호를 입력하세요.
        <?php }  ?>
    </p>

    <form name="fboardpassword" action="<?php echo $password_action_url;?>" method="post">
    <?php wp_nonce_field($nonce_name, $nonce_key); ?>
    <input type="hidden" name="action" value="<?php echo esc_attr( $action );?>">
    <input type="hidden" name="w" value="<?php echo esc_attr( $w ); ?>">
    <input type="hidden" name="bo_table" value="<?php echo esc_attr( $bo_table ); ?>">
    <input type="hidden" name="wr_id" value="<?php echo esc_attr( intval($wr_id) ); ?>">
    <input type="hidden" name="cm_id" value="<?php echo esc_attr( intval($cm_id) ); ?>">
    <input type="hidden" name="sfl" value="<?php echo esc_attr( $sfl ); ?>">
    <input type="hidden" name="stx" value="<?php echo esc_attr( $stx ); ?>">
    <input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>">

    <fieldset>
        <label for="pw_wr_password">비밀번호<strong class="sound_only">필수</strong></label>
        <input type="password" name="user_pass" id="password_user_pass" required class="frm_input required" size="15" maxLength="20">
        <input type="submit" value="확인" class="btn_submit">
    </fieldset>
    </form>

    <div class="btn_confirm">
        <a href="<?php echo esc_url( $return_url ); ?>">돌아가기</a>
    </div>

</div>
<!-- } 비밀번호 확인 끝 -->