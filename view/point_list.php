<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$sql_common = " from {$g5['point_table']} ";

$sql_search = " where (1) ";

$chk_request = array('stx', 'sfl', 'sst', 'sod', 'gpage');
$qstr = g5_get_qstr();

foreach( $chk_request as $v ){
    $$v = isset( $qstr[$v] ) ? $qstr[$v] : '';
}

if ($stx) {
    $sql_search .= " and ( ";
    switch ($sfl) {
        case 'user_id' :
            $sql_search .= " ({$sfl} = '{$stx}') ";
            break;
        default :
            $sql_search .= " ({$sfl} like '%{$stx}%') ";
            break;
    }
    $sql_search .= " ) ";
}

if (!$sst) {
    $sst  = "po_id";
    $sod = "desc";
}
$sql_order = " order by {$sst} {$sod} ";

$sql = " select count(*) as cnt
            {$sql_common}
            {$sql_search}
            {$sql_order} ";
$total_count = $wpdb->get_var($sql);

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);  // 전체 페이지 계산
if ($gpage < 1) $gpage = 1; // 페이지가 없으면 첫 페이지 (1 페이지)

$from_record = ($gpage - 1) * $rows; // 시작 열을 구함

$sql = " select *
            {$sql_common}
            {$sql_search}
            {$sql_order}
            limit {$from_record}, {$rows} ";

$rows = $wpdb->get_results($sql, ARRAY_A);

$listall = '<a href="'.add_query_arg( array('page' => 'g5_point_list'), admin_url('admin.php') ).'" class="ov_listall">'.__('All lists', 'gnupress').'</a>';

$mb = array();
if ($sfl == 'user_id' && $stx)
    $mb = g5_get_member($stx);

$user_login = isset($mb['user_login']) ? $mb['user_login'] : '';

$g5['title'] = __('Points management', 'gnupress'); //포인트 관리

$colspan = 8;

$po_expire_term = '';
if($config['cf_point_term'] > 0) {
    $po_expire_term = $config['cf_point_term'];
}

if (strstr($sfl, "user_id"))
    $user_id = $stx;
else
    $user_id = "";
?>

<div class="local_ov01 local_ov">
    <?php echo $listall ?>
    <?php _e('All', 'gnupress');?> <?php echo number_format($total_count) ?> <?php _e('number', 'gnupress');?>
    <?php
    if (isset($mb['user_id']) && $mb['user_id']) {
        echo '&nbsp;(' . $mb['user_display_name'] .' '.__('\'S Point totals', 'gnupress').' : ' . number_format($mb['mb_point']) .__('point', 'gnupress').')';
    } else {
        $sum_point = $wpdb->get_var(" select sum(po_point) as sum_point from {$g5['point_table']} ");
        echo '&nbsp;('.__('Point totals', 'gnupress').' '.number_format($sum_point).' '.__('point', 'gnupress').')';
    }
    ?>
    <?php if ($is_admin == 'super') { ?><!-- <a href="javascript:point_clear();">포인트정리</a> --><?php } ?>
</div>

<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">
<label for="sfl" class="sound_only"><?php _e('Search for', 'gnupress')?></label>
<select name="sfl" id="sfl">
    <option value="user_id"<?php echo g5_get_selected($sfl, "user_id"); ?>><?php _e('User_id', 'gnupress')?></option>
    <option value="po_content"<?php echo g5_get_selected($sfl, "po_content"); ?>><?php _e('Description', 'gnupress')?></option>
</select>
<label for="stx" class="sound_only"><?php _e('Search word', 'gnupress')?><strong class="sound_only"> <?php _e('required', 'gnupress')?></strong></label>
<input type="text" name="stx" value="<?php echo $stx ?>" id="stx" required class="required frm_input">
<input type="submit" class="button btn_submit" value="<?php _e('Submit', 'gnupress')?>">
</form>

<form name="fpointlist" id="fpointlist" method="post" action="<?php echo g5_form_action_url(admin_url("admin.php?page=g5_point_list"));?>" onsubmit="return fpointlist_submit(this);">
<?php wp_nonce_field( 'g5_point_plus', '_wpnonce_g5_field' ); ?>
<input type="hidden" name="point_action" value="point_list_delete" >
<input type="hidden" name="sst" value="<?php echo $sst ?>">
<input type="hidden" name="sod" value="<?php echo $sod ?>">
<input type="hidden" name="sfl" value="<?php echo $sfl ?>">
<input type="hidden" name="stx" value="<?php echo $stx ?>">
<input type="hidden" name="gpage" value="<?php echo $gpage ?>">

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?> <?php _e('List', 'gnupress')?></caption>
    <thead>
    <tr>
        <th scope="col">
            <label for="chkall" class="sound_only"><?php _e('Points Full Record', 'gnupress');    //포인트 내역 전체?></label>
            <input type="checkbox" name="chkall" value="1" id="chkall" onclick="check_all(this.form)">
        </th>
        <th scope="col"><?php echo g5_subject_sort_link('user_id', $qstr) ?><?php _e('user_id', 'gnupress'); //회원아이디?></a></th>
        <th scope="col"><?php _e('name', 'gnupress'); //이름?></th>
        <th scope="col"><?php echo g5_subject_sort_link('po_content', $qstr) ?><?php _e('point description', 'gnupress');?></a></th>
        <th scope="col"><?php echo g5_subject_sort_link('po_point', $qstr) ?><?php _e('point', 'gnupress');?></a></th>
        <th scope="col"><?php echo g5_subject_sort_link('po_datetime', $qstr) ?><?php _e('date', 'gnupress');?></a></th>
        <th scope="col"><?php _e('expire date', 'gnupress');    //만료일?></th>
        <th scope="col"><?php _e('totals', 'gnupress'); //합계?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    $i = 0;
    $row2 = array('user_id'=>false);
    foreach( $rows as $row ){
        if ($i==0 || ($row2['user_id'] != $row['user_id'])) {
            $row2 = g5_get_member( (int) $row['user_id'] );
        }

        $mb_nick = g5_get_sideview($row['user_id'], $row2['display_name'], $row2['user_email'], $row2['user_url']);

        $link1 = $link2 = '';
        if (!preg_match("/^\@/", $row['po_rel_table']) && $row['po_rel_table']) {
            $tmp_link = '#';

            if ( $find_board_link = g5_page_get_by($row['po_rel_table']) ){
                $tmp_link = add_query_arg( array('wr_id'=>$row['po_rel_id']) , $find_board_link );
            } else if( $row['po_rel_id'] ){
                $tmp_write = g5_get_write( $g5['write_table'], $row['po_rel_id'] );
                if( $tmp_write['wr_page_id'] )
                    $tmp_link = get_permalink($tmp_write['wr_page_id']);
            }
            $link1 = '<a href="'.$tmp_link.'" target="_blank">';
            $link2 = '</a>';
        }

        $expr = '';
        if($row['po_expired'] == 1)
            $expr = ' txt_expired';

        $bg = 'bg'.($i%2);

        $td_mbid_link = add_query_arg( array('sfl'=>'user_id', 'stx'=>$row['user_id'] ) );
    ?>

    <tr class="<?php echo $bg; ?>">
        <td class="td_chk">
            <input type="hidden" name="user_id[<?php echo $i ?>]" value="<?php echo $row['user_id'] ?>" id="user_id_<?php echo $i ?>">
            <input type="hidden" name="po_id[<?php echo $i ?>]" value="<?php echo $row['po_id'] ?>" id="po_id_<?php echo $i ?>">
            <label for="chk_<?php echo $i; ?>" class="sound_only"><?php echo $row['po_content'] ?> 내역</label>
            <input type="checkbox" name="chk[]" value="<?php echo $i ?>" id="chk_<?php echo $i ?>">
        </td>
        <td class="td_mbid"><a href="<?php echo $td_mbid_link ?>"><?php echo $row2['user_login'] ?></a></td>
        <td class="td_name sv_use"><div><?php echo $mb_nick ?></div></td>
        <td class="td_pt_log"><?php echo $link1 ?><?php echo $row['po_content'] ?><?php echo $link2 ?></td>
        <td class="td_num td_pt"><?php echo number_format($row['po_point']) ?></td>
        <td class="td_datetime"><?php echo $row['po_datetime'] ?></td>
        <td class="td_date<?php echo $expr; ?>">
            <?php if ($row['po_expired'] == 1) { ?>
            <?php _e('expire', 'gnupress')?><?php echo substr(str_replace('-', '', $row['po_expire_date']), 2); ?>
            <?php } else echo $row['po_expire_date'] == '9999-12-31' ? '&nbsp;' : $row['po_expire_date']; ?>
        </td>
        <td class="td_num td_pt"><?php echo number_format($row['po_mb_point']) ?></td>
    </tr>

    <?php
    $i++;
    }

    if ($i == 0)
        echo '<tr><td colspan="'.$colspan.'" class="empty_table">'.__('Empty data', 'gnupress').'</td></tr>';
    ?>
    </tbody>
    </table>
</div>

<div class="btn_list01 btn_list">
    <input type="submit" name="act_button" class="button" value="<?php _e('choose-Delete', 'gnupress'); ?>" onclick="document.pressed=this.value">
</div>

</form>

<?php echo g5_get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $gpage, $total_page, add_query_arg( array('gpage'=>false) ), '', 'gpage'); ?>

<section id="point_mng">
    <h2 class="h2_frm"><? _e('Individual members set point increase or decrease', 'gnupress'); //개별회원 포인트 증감 설정?></h2>

    <form name="fpointlist2" method="post" id="fpointlist2" action="<?php echo g5_form_action_url(admin_url("admin.php?page=g5_point_list"));?>" autocomplete="off">
    <?php wp_nonce_field( 'g5_point_plus', '_wpnonce_g5_field' ); ?>
    <input type="hidden" name="point_action" value="point_update" />
    <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
    <input type="hidden" name="stx" value="<?php echo $stx ?>">
    <input type="hidden" name="sst" value="<?php echo $sst ?>">
    <input type="hidden" name="sod" value="<?php echo $sod ?>">
    <input type="hidden" name="gpage" value="<?php echo $gpage ?>">

    <div class="tbl_frm01 tbl_wrap">
        <table>
        <colgroup>
            <col class="grid_4">
            <col>
        </colgroup>
        <tbody>
        <tr>
            <th scope="row"><label for="user_login"><?php _e('user_id', 'gnupress'); //회원아이디?><strong class="sound_only"><?php _e('required', 'gnupress'); ?></strong></label></th>
            <td><input type="text" name="user_login" value="<?php echo $user_login ?>" id="user_login" class="required frm_input" required></td>
        </tr>
        <tr>
            <th scope="row"><label for="po_content"><?php _e('point description', 'gnupress');?><strong class="sound_only"><?php _e('required', 'gnupress'); ?></strong></label></th>
            <td><input type="text" name="po_content" id="po_content" required class="required frm_input" size="80"></td>
        </tr>
        <tr>
            <th scope="row"><label for="po_point"><?php _e('point', 'gnupress');?><strong class="sound_only"><?php _e('required', 'gnupress'); ?></strong></label></th>
            <td><input type="text" name="po_point" id="po_point" required class="required frm_input"></td>
        </tr>
        <?php if($config['cf_point_term'] > 0) { ?>
        <tr>
            <th scope="row"><label for="po_expire_term"><?php _e('point_expire_term', 'gnupress'); //포인트 유효기간?></label></th>
            <td><input type="text" name="po_expire_term" value="<?php echo $po_expire_term; ?>" id="po_expire_term" class="frm_input" size="5"> <?php _e('days', 'gnupress');?></td>
        </tr>
        <?php } ?>
        </tbody>
        </table>
    </div>

    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="<?php _e('Submit', 'gnupress'); ?>" class="button btn_submit">
    </div>

    </form>

</section>

<script>
function fpointlist_submit(f)
{
    if (!g5_is_checked("chk[]")) {
        alert(document.pressed+" <?php _e('to be select least one item', 'gnupress'); ?>");
        return false;
    }

    if(document.pressed == "<?php _e('choose-Delete', 'gnupress'); ?>") {
        if(!confirm("<?php _e('Are you sure you want to delete the selected data?', 'gnupress'); ?>")) {
            return false;
        }
    }

    return true;
}
</script>