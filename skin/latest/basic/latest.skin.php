<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// $g5_page_url 이 빈값으로 나올 경우 숏코드에 해당 url을 입력, 또는 게시판 기본 설정에서 적용할 페이지을 설정해 주시면 됩니다.
?>
<div class="g5_latest_lt">
    <strong class="lt_title"><a href="<?php echo esc_url( $g5_page_url ); ?>"><?php echo $bo_subject; ?></a></strong>
    <ul>
    <?php 
    foreach($list as $row) {
        if( !isset($row['wr_id']) ) continue;
    ?>
        <li>
            <?php
            echo "<a href=\"".esc_url($row['href'])."\">";
            if ($row['is_notice'])
                echo "<strong>".$row['subject']."</strong>";
            else
                echo $row['subject'];

            if ($row['comment_cnt'])
                echo $row['comment_cnt'];

            echo "</a>";

            // if ($row['link']['count']) { echo "[{$row['link']['count']}]"; }
            // if ($row['file']['count']) { echo "<{$row['file']['count']}>"; }

            if (isset($row['icon_new'])) echo " " . $row['icon_new'];
            if (isset($row['icon_hot'])) echo " " . $row['icon_hot'];
            if (isset($row['icon_file'])) echo " " . $row['icon_file'];
            if (isset($row['icon_link'])) echo " " . $row['icon_link'];
            if (isset($row['icon_secret'])) echo " " . $row['icon_secret'];
             ?>
        </li>
    <?php }  ?>
    <?php if (count($list) == 0) { //게시물이 없을 때  ?>
    <li><?php _e('No Content.', 'gnupress');?></li>
    <?php }  ?>
    </ul>
    <div class="lt_more"><a href="<?php echo esc_url( $g5_page_url ); ?>"><span class="sound_only"><?php echo $bo_subject ?></span><?php _e('more', 'gnupress');?></a></div>
</div>
<!-- } <?php echo $bo_subject; ?> 최신글 끝 -->