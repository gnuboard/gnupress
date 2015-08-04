<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
include_once( G5_DIR_PATH.'lib/thumbnail.lib.php' );

    if (!isset($board['bo_table']) ) {
        $this->errors[] = __('The board does not exist.', G5_NAME);
        return;
    }

    // wr_id 값이 있으면 글읽기
    if (isset($wr_id) && $wr_id) {
        // 글이 없을 경우 해당 게시판 목록으로 이동
        if ( !isset($write['wr_id']) ) {
            $this->errors[] = __('This article does not exist.\\n\\nIf this article is moved or deleted.', G5_NAME);    //글이 존재하지 않습니다.\\n\\n글이 삭제되었거나 이동된 경우입니다.
            return;
        }

        // 로그인된 회원의 권한이 설정된 읽기 권한보다 작다면
        if ($member['user_level'] < $board['bo_read_level']) {
            if ($is_member){
                $this->errors[] = __('You do not have permission to read.', G5_NAME);    //글을 읽을 권한이 없습니다.
                return;
            } else {
                $this->errors[] = array( __('You do not have permission to read.\\n\\nMember Login if you are after, try using.', G5_NAME), wp_login_url( $current_url) );
                //글을 읽을 권한이 없습니다.\\n\\n회원이시라면 로그인 후 이용해 보십시오.
                return;
            }
        }

        do_action('g5_board_head_check', $board, $is_admin );

        // 자신의 글이거나 관리자라면 통과
        if (($write['user_id'] && $write['user_id'] == $member['user_id']) || $is_admin) {
            ;
        } else {
            // 비밀글이라면
            if (strstr($write['wr_option'], "secret"))
            {
                // 회원이 비밀글을 올리고 관리자가 답변글을 올렸을 경우
                // 회원이 관리자가 올린 답변글을 바로 볼 수 없던 오류를 수정
                $is_owner = false;
                if ($write['wr_parent'] && $member['user_id'])
                {
                    $sql = $wpdb->prepare(" select user_id from `{$write_table}`
                                where wr_id = %d ", $write['wr_parent']);


                    $row = $wpdb->get_row($sql, ARRAY_A);
                    if ($row['user_id'] == $member['user_id']){
                        $is_owner = true;
                    }
                }

                $ss_name = 'ss_secret_'.$bo_table.'_'.$write['wr_num'];

                if (!$is_owner)
                {
                    // 한번 읽은 게시물의 번호는 세션에 저장되어 있고 같은 게시물을 읽을 경우는 다시 비밀번호를 묻지 않습니다.
                    // 이 게시물이 저장된 게시물이 아니면서 관리자가 아니라면
                    if (!g5_get_session($ss_name)){
                        $tmp_href = add_query_arg( array_merge((array) $qstr, array('action'=>'password', 'w' => 's', 'wr_id'=> $wr_id)) , $default_href );
                        g5_goto_url($tmp_href);
                        exit;
                    }
                }

                g5_set_session($ss_name, TRUE);
            }
        }

        // 한번 읽은글은 브라우저를 닫기전까지는 카운트를 증가시키지 않음
        $ss_name = 'ss_view_'.$bo_table.'_'.$wr_id;

        if (!g5_get_session($ss_name))
        {
            $sql = $wpdb->prepare("update {$write_table} set wr_hit = IFNULL(wr_hit, 0) + 1 where wr_id = %d ", $wr_id);

            $wpdb->query($sql);

            // 자신의 글이면 통과
            if ($write['user_id'] && $write['user_id'] == $member['user_id']) {
                ;
            } else if ($is_guest && !$board['bo_read_level'] && $write['wr_ip'] == $_SERVER['REMOTE_ADDR']) {
                // 비회원이면서 읽기레벨이 0이고 등록된 아이피가 같다면 자신의 글이므로 통과
                ;
            } else {
                // 글읽기 포인트가 설정되어 있다면
                if ($config['cf_use_point'] && $board['bo_read_point'] && $member['mb_point'] + $board['bo_read_point'] < 0){
                    alert(
                        sprintf(__('Because your point is %s points less or missing, not read ( %s points required ) the article.\\n\\nAfter a point collect, please read article again.', 'gnupress'), number_format($member['mb_point']), number_format($board['bo_read_point']) )
                    );
                }
                g5_insert_point($member['user_id'], $board['bo_read_point'], "{$board['bo_subject']} {$wr_id} ".__('Read article', G5_NAME), $bo_table, $wr_id, __('READ', G5_NAME));
            }

            g5_set_session($ss_name, TRUE);
        }
        $g5['title'] = strip_tags(g5_conv_subject($write['wr_subject'], 255))." > ".$board['bo_subject'];

    } else {
        if ($member['user_level'] < $board['bo_list_level']) {
            if ($member['user_id']){
                $this->errors[] = __('You do not have permission to view the list.', G5_NAME);    //목록을 볼 권한이 없습니다.
                return;
            } else {
                $this->errors[] = array( __('You do not have permission to view the list.\\n\\nMember Login if you are after, try using.', G5_NAME), wp_login_url( $current_url ) ) ;
                return;
            }
        }

        if (!isset($page) || (isset($page) && $page == 0)) $page = 1;

        $g5['title'] = $board['bo_subject']." ".$page." ".__('page', G5_NAME);
    }

    $width = $board['bo_table_width'];
    if ($width <= 100)
        $width .= '%';
    else
        $width .='px';

    // IP보이기 사용 여부
    $ip = "";
    $is_ip_view = $board['bo_use_ip_view'];
    if ($is_admin) {
        $is_ip_view = true;
        if (array_key_exists('wr_ip', $write)) {
            $ip = $write['wr_ip'];
        }
    } else {
        // 관리자가 아니라면 IP 주소를 감춘후 보여줍니다.
        if (isset($write['wr_ip'])) {
            $ip = preg_replace("/([0-9]+).([0-9]+).([0-9]+).([0-9]+)/", G5_IP_DISPLAY, $write['wr_ip']);
        }
    }

    // 분류 사용
    $is_category = false;
    $category_name = '';
    if ($board['bo_use_category']) {
        $is_category = true;
        if (array_key_exists('ca_name', $write)) {
            $category_name = $write['ca_name']; // 분류명
        }
    }

    // 추천 사용
    $is_good = false;
    if ($board['bo_use_good'])
        $is_good = true;

    // 비추천 사용
    $is_nogood = false;
    if ($board['bo_use_nogood'])
        $is_nogood = true;

    $admin_href = "";
    // 최고관리자 또는 그룹관리자라면
    if ($member['user_id'] && ($is_admin == 'super')){
        $admin_href = admin_url( 'admin.php?page=g5_board_form&amp;w=u&amp;bo_table='.$bo_table );
    }

    //게시판 설정의 상단 내용 출력
    g5_board_head_print($board, $wr_id);

    // 게시물 아이디가 있다면 게시물 보기를 INCLUDE
    if (isset($wr_id) && $wr_id) {
        include_once(G5_DIR_PATH.'bbs/view.php');
    }

    // 전체목록보이기 사용이 "예" 또는 wr_id 값이 없다면 목록을 보임
    if ($member['user_level'] >= $board['bo_list_level'] && $board['bo_use_list_view'] || empty($wr_id))
        include_once (G5_DIR_PATH.'bbs/list.php');

    //게시판 설정의 하단 내용 출력
    g5_board_tail_print($board, $wr_id);
?>