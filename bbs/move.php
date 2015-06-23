<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if( !isset($bo_table) ){
    wp_die( __('bo_table값이 없습니다.', G5_NAME) );
}

add_filter('show_admin_bar', '__return_false');
//show_admin_bar(false);

add_action('wp_enqueue_scripts', 'g5_new_style_script', 99);
add_filter('wp_head','g5_remove_admin_bar_style', 99);

if ($sw == 'move')
    $act = '이동';
else if ($sw == 'copy')
    $act = '복사';
else
    g5_alert( __('sw 값이 제대로 넘어오지 않았습니다.', G5_NAME) );

// 게시판 관리자 이상 복사, 이동 가능
if ($is_admin != 'board' && $is_admin != 'super')
    wp_die( __('게시판 관리자 이상 접근이 가능합니다.', G5_NAME) );

$g5['title'] = '게시물 ' . $act;

$wr_id_list = '';
if ($wr_id)
    $wr_id_list = $wr_id;
else {
    $comma = '';
    foreach((array) $_POST['chk_wr_id'] as $v ){
        if( empty($v) ) continue;
        $wr_id_list .= $comma . intval($v);
        $comma = ',';
    }
}

$sql = "select * from {$g5['board_table']} a ";

if ($is_admin == 'board'){
    $sql .= $wpdb->prepare(" and a.bo_admin = '%s' ", $member['user_id']);
}

$rows = $wpdb->get_results($sql, ARRAY_A);
$move_action_url = apply_filters('move_action_url', get_permalink() );
?>
<?php echo g5_new_html_header(); ?>
<div id="copymove" class="new_win">
    <h1 id="win_title"><?php echo $g5['title'] ?></h1>

    <form name="fboardmoveall" method="post" action="<?php echo esc_url($move_action_url); ?>" onsubmit="return fboardmoveall_submit(this);">
    <?php wp_nonce_field( 'g5_move', 'g5_nonce_field' ); ?>
    <input type="hidden" name="sw" value="<?php echo esc_attr($sw); ?>">
    <input type="hidden" name="board_page_id" value="<?php echo esc_attr($board_page_id); ?>">
    <input type="hidden" name="bo_table" value="<?php echo esc_attr($bo_table); ?>">
    <input type="hidden" name="wr_id_list" value="<?php echo esc_attr($wr_id_list); ?>">
    <input type="hidden" name="sfl" value="<?php echo esc_attr($sfl); ?>">
    <input type="hidden" name="stx" value="<?php echo esc_attr($stx); ?>">
    <input type="hidden" name="spt" value="<?php echo esc_attr($spt); ?>">
    <input type="hidden" name="page" value="<?php echo esc_attr($page); ?>">
    <input type="hidden" name="act" value="<?php echo esc_attr($act); ?>">
    <input type="hidden" name="action" value="move_update" >
    <input type="hidden" name="url" value="<?php echo esc_url($_SERVER['HTTP_REFERER']) ?>">

    <div class="tbl_head01 tbl_wrap">
        <table>
        <caption><?php echo $act ?>할 게시판을 한개 이상 선택하여 주십시오.</caption>
        <thead>
        <tr>
            <th scope="col">
                <label for="chkall" class="sound_only">현재 페이지 게시판 전체</label>
                <input type="checkbox" id="chkall" onclick="if (this.checked) all_checked(true); else all_checked(false);">
            </th>
            <th scope="col">게시판</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $i = 0;
        foreach($rows as $row) {
            $atc_mark = '';
            $atc_bg = '';
            if ($row['bo_table'] == $bo_table) { // 게시물이 현재 속해 있는 게시판이라면
                $atc_mark = '<span class="copymove_current">현재<span class="sound_only">게시판</span></span>';
                $atc_bg = 'copymove_currentbg';
            }
        ?>
        <tr class="<?php echo $atc_bg; ?>">
            <td class="td_chk">
                <label for="chk<?php echo $i ?>" class="sound_only"><?php echo $row['bo_table'] ?></label>
                <input type="checkbox" value="<?php echo $row['bo_table'] ?>" id="chk<?php echo $i ?>" name="chk_bo_table[]">
            </td>
            <td>
                <label for="chk<?php echo $i ?>">
                    <?php echo $row['bo_subject'] ?> (<?php echo $row['bo_table'] ?>)
                    <?php echo $atc_mark; ?>
                </label>
            </td>
        </tr>
        <?php
        $i++;
        } 
        ?>
        </tbody>
        </table>
    </div>

    <div class="win_btn">
        <input type="submit" value="<?php echo $act ?>" id="btn_submit" class="btn_submit">
    </div>
    </form>

</div>

<script>
(function($) {
    $(".win_btn").append("<button type=\"button\" class=\"btn_cancel\">창닫기</button>");

    $(".win_btn button").click(function() {
        window.close();
    });
})(jQuery);

function all_checked(sw) {
    var f = document.fboardmoveall;

    for (var i=0; i<f.length; i++) {
        if (f.elements[i].name == "chk_bo_table[]")
            f.elements[i].checked = sw;
    }
}

function fboardmoveall_submit(f)
{
    var check = false;

    if (typeof(f.elements['chk_bo_table[]']) == 'undefined')
        ;
    else {
        if (typeof(f.elements['chk_bo_table[]'].length) == 'undefined') {
            if (f.elements['chk_bo_table[]'].checked)
                check = true;
        } else {
            for (i=0; i<f.elements['chk_bo_table[]'].length; i++) {
                if (f.elements['chk_bo_table[]'][i].checked) {
                    check = true;
                    break;
                }
            }
        }
    }

    if (!check) {
        alert('게시물을 '+f.act.value+'할 게시판을 한개 이상 선택해 주십시오.');
        return false;
    }

    document.getElementById('btn_submit').disabled = true;

    return true;
}
</script>
<?php echo g5_new_html_footer(); ?>
<?php
exit;
?>