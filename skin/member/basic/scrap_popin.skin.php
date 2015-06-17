<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
?>

<!-- 스크랩 시작 { -->
<div id="scrap_do" class="new_win mbskin">
    <h1 id="win_title">스크랩하기</h1>

    <form name="f_scrap_popin" action="<?php echo g5_form_action_url(add_query_arg(array()));?>" method="post">
    <?php wp_nonce_field( 'g5_scrap', '_wpnonce_g5_field' ); ?>
    <input type="hidden" name="gaction" value="scrap_popin_update">
    <input type="hidden" name="bo_table" value="<?php echo esc_attr( $bo_table ); ?>">
    <input type="hidden" name="wr_id" value="<?php echo esc_attr( intval($wr_id) ); ?>">

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <caption>제목 확인 및 댓글 쓰기</caption>
        <tbody>
        <tr>
            <th scope="row">제목</th>
            <td><?php echo g5_get_text(g5_cut_str($write['wr_subject'], 255)) ?></td>
        </tr>
        <tr>
            <th scope="row"><label for="wr_content">댓글</label></th>
            <td><textarea name="cm_content" id="cm_content"></textarea></td>
        </tr>
        </tbody>
        </table>
    </div>

    <p class="win_desc">
        스크랩을 하시면서 감사 혹은 격려의 댓글을 남기실 수 있습니다.
    </p>

    <div class="win_btn">
        <input type="submit" value="스크랩 확인" class="btn_submit">
    </div>
    </form>
</div>
<!-- } 스크랩 끝 -->