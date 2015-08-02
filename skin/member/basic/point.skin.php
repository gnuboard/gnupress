<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
?>

<div id="point" class="new_win">
    <h1 id="win_title"><?php echo $g5['title'] ?></h1>

    <div class="tbl_head01 tbl_wrap">
        <table>
        <caption><?php _e('Point History List', 'gnupress');?></caption>
        <thead>
        <tr>
            <th scope="col"><?php _e('date', 'gnupress');?></th>
            <th scope="col"><?php _e('description', 'gnupress');?></th>
            <th scope="col"><?php _e('expire date', 'gnupress');?></th>
            <th scope="col"><?php _e('payments Point', 'gnupress');	//지급포인트?></th>
            <th scope="col"><?php _e('using Points', 'gnupress');	//사용포인트?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        $sum_point1 = $sum_point2 = $sum_point3 = 0;

        foreach( $rows as $row ) {
            $point1 = $point2 = 0;
            if ($row['po_point'] > 0) {
                $point1 = '+' .number_format($row['po_point']);
                $sum_point1 += $row['po_point'];
            } else {
                $point2 = number_format($row['po_point']);
                $sum_point2 += $row['po_point'];
            }

            $po_content = $row['po_content'];

            $expr = '';
            if($row['po_expired'] == 1)
                $expr = ' txt_expired';
        ?>
        <tr>
            <td class="td_datetime"><?php echo $row['po_datetime']; ?></td>
            <td><?php echo $po_content; ?></td>
            <td class="td_date<?php echo $expr; ?>">
                <?php if ($row['po_expired'] == 1) { ?>
                <?php _e('expire', 'gnupress');?><?php echo substr(str_replace('-', '', $row['po_expire_date']), 2); ?>
                <?php } else echo $row['po_expire_date'] == '9999-12-31' ? '&nbsp;' : $row['po_expire_date']; ?>
            </td>
            <td class="td_numbig"><?php echo $point1; ?></td>
            <td class="td_numbig"><?php echo $point2; ?></td>
        </tr>
        <?php
        }

        if ( !count($rows) )
            echo '<tr><td colspan="5" class="empty_table">'.__('No Contents.', 'gnupress').'</td></tr>';
        else {
            if ($sum_point1 > 0)
                $sum_point1 = "+" . number_format($sum_point1);
            $sum_point2 = number_format($sum_point2);
        }
        ?>
        </tbody>
        <tfoot>
        <tr>
            <th scope="row" colspan="3"><?php _e('Subtotal', 'gnupress');?></th>
            <td><?php echo $sum_point1; ?></td>
            <td><?php echo $sum_point2; ?></td>
        </tr>
        <tr>
            <th scope="row" colspan="3"><?php _e('Hold point', 'gnupress');	//보유포인트?></th>
            <td colspan="2"><?php echo number_format($member['mb_point']); ?></td>
        </tr>
        </tfoot>
        </table>
    </div>

    <?php
    if($gnupress->window_open){
        $page_array = array('page'=>false, 'action'=>'point');
    } else {
        $page_array = array('page'=>false);
    }
    $get_paging_url = add_query_arg( array_merge( (array) $qstr, $page_array ), $default_href );
    echo g5_get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, $get_paging_url );
    ?>

    <div class="win_btn"><button type="button" onclick="javascript:window.close();"><?php _e('Close', 'gnupress');?></button></div>
</div>