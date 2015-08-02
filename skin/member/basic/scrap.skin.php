<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
?>

<!-- 스크랩 목록 시작 { -->
<div id="scrap" class="new_win mbskin">
    <h1 id="win_title"><?php echo $g5['title'] ?></h1>

    <div class="tbl_head01 tbl_wrap">
        <table>
        <caption><?php _e('Scrap List', 'gnupress');?></caption>
        <thead>
        <tr>
            <th scope="col"><?php _e('Number', 'gnupress');?></th>
            <th scope="col"><?php _e('Board', 'gnupress');?></th>
            <th scope="col"><?php _e('Subject', 'gnupress');?></th>
            <th scope="col"><?php _e('Date', 'gnupress');?></th>
            <th scope="col"><?php _e('Delete', 'gnupress');?></th>
        </tr>
        </thead>
        <tbody>
        <?php for ($i=0; $i<count($list); $i++) {  ?>
        <tr>
            <td class="td_num"><?php echo $list[$i]['num'] ?></td>
            <td class="td_board"><a href="<?php echo $list[$i]['opener_href'] ?>" target="_blank" onclick="opener.document.location.href='<?php echo $list[$i]['opener_href'] ?>'; return false;"><?php echo $list[$i]['bo_subject'] ?></a></td>
            <td class="td_subject"><a href="<?php echo $list[$i]['opener_href_wr_id'] ?>" target="_blank" onclick="opener.document.location.href='<?php echo $list[$i]['opener_href_wr_id'] ?>'; return false;"><?php echo $list[$i]['subject'] ?></a></td>
            <td class="td_datetime"><?php echo $list[$i]['ms_datetime'] ?></td>
            <td class="td_mng"><a href="<?php echo $list[$i]['del_href'];  ?>" onclick="gnupress.del(this.href); return false;"><?php _e('Delete', 'gnupress');?></a></td>
        </tr>
        <?php }  ?>

        <?php if ($i == 0) echo "<tr><td colspan=\"5\" class=\"empty_table\">".__('No Contents.', 'gnupress')."</td></tr>";  ?>
        </tbody>
        </table>
    </div>

    <?php
    $get_paging_url = add_query_arg( array_merge( (array) $qstr, array('page'=>false)), $current_url );
    echo g5_get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, $get_paging_url );
    ?>

    <div class="win_btn">
        <button type="button" onclick="window.close();"><?php _e('Close', 'gnupress');?></button>
    </div>
</div>
<!-- } 스크랩 목록 끝 -->