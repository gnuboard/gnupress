<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
?>
<div class="local_desc02 local_desc">
    <p>
        완료 메세지가 나오기 전에 프로그램의 실행을 중지하지 마십시오.
    </p>
</div>
<?php
if( !g5_get_upload_path() ){
    echo '<p>첨부파일 디렉토리에 접근할수 없습니다.</p>';
} else {
    $dir = g5_get_upload_path().'/file/'.$board['bo_table'];

    $cnt = 0;
    if(is_dir($dir)) {
        echo '<ul>';
        $files = glob($dir.'/thumb-*');
        if (is_array($files)) {
            foreach($files as $thumbnail) {
                $cnt++;
                @unlink($thumbnail);

                echo '<li>'.$thumbnail.'</li>'.PHP_EOL;

                flush();

                if ($cnt%10==0)
                    echo PHP_EOL;
            }
        }

        echo '<li>완료됨</li></ul>'.PHP_EOL;
        echo '<div class="local_desc01 local_desc"><p><strong>썸네일 '.$cnt.'건의 삭제 완료됐습니다.</strong></p></div>'.PHP_EOL;
    } else {
        echo '<p>첨부파일 디렉토리가 존재하지 않습니다.</p>';
    }
}

if( wp_get_referer() ){
    $go_url = wp_get_referer();
} else {
    $go_url = home_url(add_query_arg( array('g5_rq'=>false) ));
}
?>
<div class="bootstrap btn_confirm01 btn_confirm"><a href="<?php echo $go_url; ?>" class="btn btn-info">게시판 수정으로 돌아가기</a></div>