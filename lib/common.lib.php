<?php
function g5_get_qstr(){
    global $gnupress;

    $qstr = array();
    if (isset($_REQUEST['sca']))  {
        $sca = sanitize_text_field(trim($_REQUEST['sca']));
        if( $sca ){
            $qstr['sca'] = urlencode($sca);
        }
    }

    if (isset($_REQUEST['sfl']))  {
        $sfl = sanitize_text_field(trim($_REQUEST['sfl']));
        $sfl = preg_replace("/[\<\>\'\"\%\=\(\)\s]/", "", $sfl);
        if( $sfl ){
            $qstr['sfl'] = urlencode($sfl); // search field (검색 필드)
        }
    }

    if (isset($_REQUEST['stx']))  { // search text (검색어)
        $stx = g5_get_search_string(sanitize_text_field(trim($_REQUEST['stx'])));
        if( $stx ){
            $qstr['stx'] = urlencode($stx);
        }
    }

    if (isset($_REQUEST['sst']))  {
        $sst = sanitize_text_field(trim($_REQUEST['sst']));
        $sst = preg_replace("/[\<\>\'\"\%\=\(\)\s]/", "", $sst);
        if( $sst ){
            $qstr['sst'] = urlencode($sst); // search sort (검색 정렬 필드)
        }
    }

    if (isset($_REQUEST['sod']))  { // search order (검색 오름, 내림차순)
        $sod = preg_match("/^(asc|desc)$/i", $_REQUEST['sod']) ? sanitize_text_field($_REQUEST['sod']) : '';
        if ($sod){
            $qstr['sod'] = urlencode($sod);
        }
    }

    if (isset($_REQUEST['sop']))  { // search operator (검색 or, and 오퍼레이터)
        $sop = preg_match("/^(or|and)$/i", $_REQUEST['sop']) ? sanitize_text_field($_REQUEST['sop']) : '';
        if ($sop){
            $qstr['sop'] = urlencode($sop);
        }
    }

    if (isset($_REQUEST['spt']))  { // search part (검색 파트[구간])
        $spt = (int)$_REQUEST['spt'];
        if ($spt)
            $qstr['stp'] = urlencode($spt);
    }

    if (isset($_REQUEST['page'])) { // 리스트 페이지
        $page = (int)$_REQUEST['page'];
        if ($page)
            $qstr['page'] = urlencode($page);
    }

    if (isset($_REQUEST['gpage'])) { // admin에서 사용되는 페이지 변수
        $gpage = (int)$_REQUEST['gpage'];
        if ($gpage)
            $qstr['gpage'] = urlencode($gpage);
    }

    if (isset($_REQUEST['tag'])) { // tag 검색
        if ( $tag = sanitize_text_field(trim($_REQUEST['tag'])) )
            $qstr['tag'] = urlencode($tag);
    }

    $gnupress->qstr = apply_filters('g5_get_qstr_filter' , $qstr);

    return $gnupress->qstr;
}

function g5_get_selected($field, $value)
{
    return ($field==$value) ? ' selected="selected"' : '';
}

function g5_get_checked($field, $value)
{
    return ($field==$value) ? ' checked="checked"' : '';
}

function g5_request_param_keys($add_array=array()){

    $params = array('w', 'sop', 'stx', 'sca', 'sst', 'sca', 'sfl', 'spt', 'sod', 'sw', 'board_page_id', 'tag');
    if( $add_array ){
        $params = array_merge($add_array, $params);
    }
    $tmps = array();
    foreach($params as $v){
        $tmps[$v] = isset($_REQUEST[$v]) ? sanitize_text_field(trim($_REQUEST[$v])) : '';
    }

    $tmps = apply_filters('g5_request_param_filters', $tmps, $params);
    return $tmps;
}

function g5_get_category_option($board, $is_admin, $ca_name='')
{
    $categories = explode("|", $board['bo_category_list'].($is_admin?"|공지":"")); // 구분자가 , 로 되어 있음
    $str = "";
    for ($i=0; $i<count($categories); $i++) {
        $category = trim($categories[$i]);
        if (!$category) continue;

        $str .= "<option value=\"$categories[$i]\"";
        if ($category == $ca_name) {
            $str .= ' selected="selected"';
        }
        $str .= ">$categories[$i]</option>\n";
    }

    return $str;
}

// TEXT 형식으로 변환
function g5_get_text($str, $html=0)
{
    /* 3.22 막음 (HTML 체크 줄바꿈시 출력 오류때문)
    $source[] = "/  /";
    $target[] = " &nbsp;";
    */

    // 3.31
    // TEXT 출력일 경우 &amp; &nbsp; 등의 코드를 정상으로 출력해 주기 위함
    if ($html == 0) {
        $str = g5_html_symbol($str);
    }

    $source[] = "/</";
    $target[] = "&lt;";
    $source[] = "/>/";
    $target[] = "&gt;";
    //$source[] = "/\"/";
    //$target[] = "&#034;";
    $source[] = "/\'/";
    $target[] = "&#039;";
    //$source[] = "/}/"; $target[] = "&#125;";
    if ($html) {
        $source[] = "/\n/";
        $target[] = "<br/>";
    }

    return preg_replace($source, $target, $str);
}


// 날짜, 조회수의 경우 높은 순서대로 보여져야 하므로 $flag 를 추가
// $flag : asc 낮은 순서 , desc 높은 순서
// 제목별로 컬럼 정렬하는 QUERY STRING
function g5_subject_sort_link($col, $qstr=array(), $flag='asc', $default_href='')
{
    $check_arr = array('sst', 'sod', 'sfl', 'stx', 'page');

    foreach($check_arr as $v){
        $$v = isset( $qstr[$v] ) ? $qstr[$v] : '';
    }

    $arr_query = array(
            'sst'=>$col,
            'sod' => ( $flag == 'asc' ) ? $flag : 'desc'
        );
    
    if ($sst == $col) {
        $arr_query['sod'] = ( $sod == 'asc' ) ? 'desc' : 'asc';
    }

    $tmp_query = wp_parse_args( $arr_query, (array) $qstr );

    if( !$default_href ){
        $default_href = get_permalink();
    }

    $tmp_href = apply_filters( 'g5_sort_link_href', add_query_arg( $arr_query, $default_href ) );

    return "<a href=\"$tmp_href\">";
}

// 3.31
// HTML SYMBOL 변환
// &nbsp; &amp; &middot; 등을 정상으로 출력
function g5_html_symbol($str)
{
    return preg_replace("/\&([a-z0-9]{1,20}|\#[0-9]{0,3});/i", "&#038;\\1;", $str);
}

function g5_option_selected($value, $selected, $text='')
{
    if (!$text) $text = $value;
    if ($value == $selected)
        return "<option value=\"$value\" selected=\"selected\">$text</option>\n";
    else
        return "<option value=\"$value\">$text</option>\n";
}

// 세션변수값 얻음
function g5_get_session($session_name)
{
    return isset($_SESSION[$session_name]) ? $_SESSION[$session_name] : '';
}

// 세션변수 생성
function g5_set_session($session_name, $value)
{
    if (PHP_VERSION < '5.3.0')
        session_register($session_name);
    // PHP 버전별 차이를 없애기 위한 방법
    $$session_name = $_SESSION[$session_name] = $value;
}

function g5_insert_point($user_id, $point, $content='', $rel_table='', $rel_id='', $rel_action='', $expire=0){
    global $wpdb, $gnupress;

    $config = $gnupress->config;
    $g5 = $gnupress->g5;

    // 포인트 사용을 하지 않는다면 return
    if (!$config['cf_use_point']) { return 0; }

    // 포인트가 없다면 업데이트 할 필요 없음
    if ($point == 0) { return 0; }

    // 회원아이디가 없다면 업데이트 할 필요 없음
    if ($user_id == '') { return 0; }
    $mb = g5_get_member( $user_id );

    if (!$mb['user_id']) { return 0; }
    if( is_string( $user_id ) ){
        $user_id = $mb['user_id'];
    }

    // 회원포인트
    $mb_point = g5_get_point_sum($mb['user_id']);

    // 이미 등록된 내역이라면 건너뜀
    if ($rel_table || $rel_id || $rel_action)
    {
        $sql = $wpdb->prepare(" select count(*) as cnt from {$g5['point_table']}
                  where user_id = %d
                    and po_rel_table = '%s'
                    and po_rel_id = '%s'
                    and po_rel_action = '%s' ", $user_id, $rel_table, $rel_id, $rel_action );

        $row_count = $wpdb->get_var($sql);
        if ($row_count)
            return -1;
    }

    // 포인트 건별 생성
    $po_expire_date = '9999-12-31';
    if($config['cf_point_term'] > 0) {
        if($expire > 0)
            $po_expire_date = date('Y-m-d', strtotime('+'.($expire - 1).' days', G5_SERVER_TIME));
        else
            $po_expire_date = date('Y-m-d', strtotime('+'.($config['cf_point_term'] - 1).' days', G5_SERVER_TIME));
    }

    $po_expired = 0;
    if($point < 0) {
        $po_expired = 1;
        $po_expire_date = G5_TIME_YMD;
    }
    $po_mb_point = $mb_point + $point;

    $data = array(
            'user_id' => $user_id,
            'po_datetime' => G5_TIME_YMDHIS,
            'po_content' => addslashes($content),
            'po_point' => $point,
            'po_use_point' => 0,
            'po_mb_point' => $po_mb_point,
            'po_expired' => $po_expired,
            'po_expire_date' => $po_expire_date,
            'po_rel_table' => $rel_table,
            'po_rel_id' => $rel_id,
            'po_rel_action' => $rel_action
        );

    $formats = array(
        '%s',
        '%s',
        '%s',
        '%d',
        '%d',
        '%d',
        '%d',
        '%s',
        '%s',
        '%s',
        '%s'
        );
    $result = $wpdb->insert( $g5['point_table'], $data, $formats);

    // db insert에 실패한 경우
    if ( $result === false ){
        g5_show_db_error();
    }

    // 포인트를 사용한 경우 포인트 내역에 사용금액 기록
    if($point < 0) {
        g5_insert_use_point($user_id, $point);
    }
    
    //메타 테이블에서 포인트 내역을 업데이트 한다.

    update_user_meta( $user_id, 'mb_point', $po_mb_point );

    return 1;
}

// 포인트 내역 합계
function g5_get_point_sum($user_id)
{
    global $wpdb, $gnupress;

    $config = $gnupress->config;
    $g5 = $gnupress->g5;

    // user_id가 숫자가 아닌 문자이면
    if( is_string( $user_id ) )
    {
        $member = g5_get_member( $user_id, 'login' );
        // 회원 테이블의 고유번호를 받아온다.
        $user_id = $member['user_id'];
    }   
    if( $config['cf_point_term'] > 0 ) {
        // 소멸포인트가 있으면 내역 추가
        $expire_point = g5_get_expire_point($user_id);
        if($expire_point > 0) {
            $mb = g5_get_member($user_id);
            $content = '포인트 소멸';
            $rel_table = '@expire';
            $rel_id = $user_id;
            $rel_action = 'expire'.'-'.uniqid('');
            $point = $expire_point * (-1);
            $po_mb_point = $mb['mb_point'] + $point;
            $po_expire_date = G5_TIME_YMD;
            $po_expired = 1;

            $data =  array( 'user_id' => $user_id,
                            'po_datetime' => G5_TIME_YMDHIS,
                            'po_content' => addslashes($content),
                            'po_point' => $point,
                            'po_use_point' => 0,
                            'po_mb_point' => $po_mb_point,
                            'po_expired' => $po_expired,
                            'po_expire_date' => $po_expire_date,
                            'po_rel_table' => $rel_table,
                            'po_rel_id' => $rel_id,
                            'po_rel_action' => $rel_action
                            );

            $formats = array(
                '%s',
                '%s',
                '%s',
                '%d',
                '%d',
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s'
                );

            // insert
            $result = $wpdb->insert($g5['point_table'], $data, $formats);

            // db insert에 실패한 경우
            if ( $result === false ){
                g5_show_db_error();
            }

            // 포인트를 사용한 경우 포인트 내역에 사용금액 기록
            if($point < 0) {
                g5_insert_use_point($user_id, $point);
            }
        }

        $data = array( 'po_expired' => 1 );

        $where = $wpdb->prepare(" user_id = '%s' and po_expired <> '1' and po_expire_date <> '9999-12-31' and po_expire_date < '%s' ", $user_id, G5_TIME_YMD);

        // 유효기간이 있을 때 기간이 지난 포인트 expired 체크

        $result = $wpdb->update($g5['point_table'], $data, $where);

        // db update에 실패한 경우
        if ( $result === false ){
            g5_show_db_error();
        }

    }

    // 포인트합
    $sql = $wpdb->prepare("select sum(po_point) as sum_po_point from {$g5['point_table']} where user_id = %d ", $user_id);
    $sum_po_point = $wpdb->get_var($sql);

    return $sum_po_point;
}

// 사용포인트 입력
function g5_insert_use_point($user_id, $point, $po_id='')
{
    global $wpdb, $gnupress;

    $config = $gnupress->config;
    $g5 = $gnupress->g5;

    if($config['cf_point_term'])
        $sql_order = " order by po_expire_date asc, po_id asc ";
    else
        $sql_order = " order by po_id asc ";

    $point1 = abs($point);

    $sql = $wpdb->prepare(" select po_id, po_point, po_use_point
                from {$g5['point_table']}
                where user_id = %d
                  and po_id <> %d
                  and po_expired = '0'
                  and po_point > po_use_point
                $sql_order ", $user_id, $po_id );

    $rows = $wpdb->get_results($sql, ARRAY_A);

    foreach( $rows as $row ){
        if( empty($row) ) continue;

        $point2 = $row['po_point'];
        $point3 = $row['po_use_point'];

        if(($point2 - $point3) > $point1) {
            
            $data = array( 'po_use_point' => "po_use_point + $point1" );
            $formats = array( '%d' );
            $where = array( 'po_id' => (int) $row['po_id'] );
            $where_format = array( '%d' );

            $result = $wpdb->update( $g5['point_table'], $data, $where, $formats, $where_format);

            // db update에 실패한 경우
            if ( $result === false ){
                g5_show_db_error();
            }
            break;
        } else {
            $point4 = $point2 - $point3;

            $data = array( 'po_use_point' => "po_use_point + $point4", 'po_expired' => '100' );
            $formats = array( '%d', '%d' );
            $where = array( 'po_id' => (int) $row['po_id'] );
            $where_format = array( '%d' );

            $result = $wpdb->update( $g5['point_table'], $data, $where, $formats, $where_format);
            // db update에 실패한 경우
            if ( $result === false ){
                g5_show_db_error();
            }

            $point1 -= $point4;
        }
    }
}

// 사용포인트 삭제
function g5_delete_use_point($user_id, $point)
{
    global $wpdb, $gnupress;

    $config = $gnupress->config;
    $g5 = $gnupress->g5;

    if($config['cf_point_term'])
        $sql_order = " order by po_expire_date desc, po_id desc ";
    else
        $sql_order = " order by po_id desc ";

    $point1 = abs($point);
    $sql = $wpdb->prepare(" select po_id, po_use_point, po_expired, po_expire_date
                from {$g5['point_table']}
                where user_id = %d
                  and po_expired <> '1'
                  and po_use_point > 0
                $sql_order ", $user_id );
    
    $rows = $wpdb->get_results($sql);

    foreach($rows as $row){
        if( empty($row) ) continue;

        $point2 = $row['po_use_point'];

        $po_expired = $row['po_expired'];
        if($row['po_expired'] == 100 && ($row['po_expire_date'] == '9999-12-31' || $row['po_expire_date'] >= G5_TIME_YMD))
            $po_expired = 0;

        if($point2 > $point1) {
            $sql = $wpdb->prepare(" update {$g5['point_table']}
                        set po_use_point = po_use_point - %d,
                            po_expired = %d
                        where po_id = %d ", (int) $point1, (int) $po_expired, (int) $row['po_id']);

            $result = $wpdb->query($sql);
            // db insert에 실패한 경우
            if ( $result === false ){
                g5_show_db_error();
            }
            break;
        } else {
            $sql = $wpdb->prepare(" update {$g5['point_table']}
                        set po_use_point = 0,
                            po_expired = %d
                        where po_id = %d ", (int) $po_expired, (int) $row['po_id']);

            $result = $wpdb->query($sql);
            // db insert에 실패한 경우
            if ( $result === false ){
                g5_show_db_error();
            }
            $point1 -= $point2;
        }
    }
}

// 소멸 포인트
function g5_get_expire_point($user_id)
{
    global $wpdb, $gnupress;

    $g5 = $gnupress->g5;
    $config = $gnupress->config;

    if($config['cf_point_term'] == 0)
        return 0;

    $sql = $wpdb->prepare(" select sum(po_point - po_use_point) as sum_point
                from {$g5['point_table']}
                where user_id = %d
                  and po_expired = '0'
                  and po_expire_date <> '%s'
                  and po_expire_date < '%s' ", $user_id, '9999-12-31', G5_TIME_YMD);
    $sum_point = $wpdb->get_var($sql);

    return $sum_point;
}

// 소멸포인트 삭제
function g5_delete_expire_point($user_id, $point)
{
    global $wpdb, $gnupress;

    $config = $gnupress->config;
    $g5 = $gnupress->g5;

    $point1 = abs($point);
    $sql = $wpdb->prepare(" select po_id, po_use_point, po_expired, po_expire_date
                from {$g5['point_table']}
                where user_id = %d
                  and po_expired = '1'
                  and po_point >= 0
                  and po_use_point > 0
                order by po_expire_date desc, po_id desc ", $user_id);

    $rows = $wpdb->get_results($sql);

    foreach( $rows as $row ){
        if( empty($row) ) continue;

        $point2 = $row['po_use_point'];
        $po_expired = '0';
        $po_expire_date = '9999-12-31';
        if($config['cf_point_term'] > 0)
            $po_expire_date = date('Y-m-d', strtotime('+'.($config['cf_point_term'] - 1).' days', G5_SERVER_TIME));

        if($point2 > $point1) {
            $sql = $wpdb->prepare(" update {$g5['point_table']}
                        set po_use_point = po_use_point - %d,
                            po_expired = %d,
                            po_expire_date = '%s'
                        where po_id = %d ", (int) $point1, (int) $po_expired, $po_expire_date, (int) $row['po_id']);

            $result = $wpdb->query($sql);
            // db insert에 실패한 경우
            if ( $result === false ){
                g5_show_db_error();
            }
            break;
        } else {
            $sql = $wpdb->prepare(" update {$g5['point_table']}
                        set po_use_point = '0',
                            po_expired = %d,
                            po_expire_date = '%s'
                        where po_id = %d ", (int) $po_expired, $po_expire_date, $row['po_id']);

            $result = $wpdb->query($sql);
            // db insert에 실패한 경우
            if ( $result === false ){
                g5_show_db_error();
            }

            $point1 -= $point2;
        }

    }
}

// 포인트 삭제
function g5_delete_point($user_id, $rel_table, $rel_id, $rel_action)
{
    global $wpdb, $gnupress;

    $g5 = $gnupress->g5;

    $result = false;
    if ($rel_table || $rel_id || $rel_action)
    {
        // 포인트 내역정보
        $sql = $wpdb->prepare("select * from {$g5['point_table']} where user_id = %d and po_rel_table = '%s' and po_rel_id = '%s' and po_rel_action = '%s' ", (int) $user_id, $rel_table, $rel_id, $rel_action);
        $row = $wpdb->get_row($sql, ARRAY_A);

        if($row['po_point'] < 0) {
            $user_id = $row['user_id'];
            $po_point = abs($row['po_point']);

            g5_delete_use_point($mb_id, $po_point);
        } else {
            if($row['po_use_point'] > 0) {
                g5_insert_use_point($row['user_id'], $row['po_use_point'], $row['po_id']);
            }
        }

        $result = $wpdb->query(
                        $wpdb->prepare(" delete from {$g5['point_table']} where user_id = '%s'
                                           and po_rel_table = '%s'
                                           and po_rel_id = '%s'
                                           and po_rel_action = '%s' ", $user_id, $rel_table, $rel_id, $rel_action)
                        );

        // po_mb_point에 반영
        $sql = $wpdb->prepare(" update {$g5['point_table']}
                    set po_mb_point = po_mb_point - %d
                    where user_id = '%s'
                      and po_id > %d ", (int) $row['po_point'], $user_id, (int) $row['po_id']);
        $wpdb->query($sql);

        // 포인트 내역의 합을 구하고
        $sum_point = g5_get_point_sum($user_id);

        //메타 테이블에서 포인트 내역을 업데이트 한다.
        update_user_meta( $user_id, 'mb_point', $sum_point );
        
        return true;
    }

    return $result;
}

// 검색 구문을 얻는다.
function g5_get_sql_search($search_ca_name, $search_field, $search_text, $search_operator='and', $tag='')
{
    global $wpdb;

    $str = "";
    if ($search_ca_name)
        $str = $wpdb->prepare(" ca_name = '%s' ", $search_ca_name);

    $search_text = strip_tags(($search_text));
    $search_text = trim(stripslashes($search_text));

    if (!$search_text) {
        if ($search_ca_name) {
            return $str;
        } else {
            if( $tag ){
                return '1=1';
            } else {
                return '0';
            }
        }
    }

    if ($str)
        $str .= " and ";

    // 쿼리의 속도를 높이기 위하여 ( ) 는 최소화 한다.
    $op1 = "";

    // 검색어를 구분자로 나눈다. 여기서는 공백
    $s = array();
    $s = explode(" ", $search_text);

    // 검색필드를 구분자로 나눈다. 여기서는 +
    $tmp = array();
    $tmp = explode(",", trim($search_field));
    $field = explode("||", $tmp[0]);
    $not_comment = "";
    if (!empty($tmp[1]))
        $not_comment = $tmp[1];

    $str .= "(";
    for ($i=0; $i<count($s); $i++) {
        // 검색어
        $search_str = trim($s[$i]);
        if ($search_str == "") continue;

        // 인기검색어
        do_action('g5_insert_popular', $field, $search_str );

        $str .= $op1;
        $str .= "(";

        $op2 = "";
        for ($k=0; $k<count($field); $k++) { // 필드의 수만큼 다중 필드 검색 가능 (필드1+필드2...)

            // SQL Injection 방지
            // 필드값에 a-z A-Z 0-9 _ , | 이외의 값이 있다면 검색필드를 wr_subject 로 설정한다.
            $field[$k] = preg_match("/^[\w\,\|]+$/", $field[$k]) ? $field[$k] : "wr_subject";

            $str .= $op2;
            switch ($field[$k]) {
                case "user_id" :
                    if(is_string($s[$i])){
                        $get_user = g5_get_member($s[$i]);
                        $s[$i] = ( isset($get_user['user_id']) && "" != $get_user['user_id'] ) ? $get_user['user_id'] : $s[$i];
                    }
                case "user_display_name" :
                    $str .= $wpdb->prepare(" $field[$k] = '%s' ", $s[$i]);
                    break;
                case "wr_hit" :
                case "wr_good" :
                case "wr_nogood" :
                    $str .= $wpdb->prepare(" $field[$k] >= %d ", intval($s[$i]) );
                    break;
                // 번호는 해당 검색어에 -1 을 곱함
                case "wr_num" :
                    $str .= "$field[$k] = ".((-1)*$s[$i]);
                    break;
                case "wr_ip" :
                case "wr_password" :
                    $str .= "1=0"; // 항상 거짓
                    break;
                // LIKE 보다 INSTR 속도가 빠름
                default :
                    if (preg_match("/[a-zA-Z]/", $search_str))
                        $str .= $wpdb->prepare("INSTR(LOWER($field[$k]), LOWER('%s'))", $search_str);
                    else
                        $str .= $wpdb->prepare("INSTR($field[$k], '%s')", $search_str);
                    break;
            }
            $op2 = " or ";
        }
        $str .= ")";

        $op1 = " $search_operator ";
    }
    $str .= " ) ";

    return $str;
}

// 파일명에서 특수문자 제거
function g5_get_safe_filename($name)
{
    $pattern = '/["\'<>=#&!%\\\\(\)\*\+\?]/';
    $name = preg_replace($pattern, '', $name);

    return $name;
}

// 제목을 변환
function g5_conv_subject($subject, $len, $suffix='')
{
    return g5_get_text(g5_cut_str($subject, $len, $suffix));
}

// 한페이지에 보여줄 행, 현재페이지, 총페이지수, URL
function g5_get_paging($write_pages, $cur_page, $total_page, $url, $add='', $naming='')
{
    $str = '';

    $naming = $naming ? $naming : 'page';

    if( $add && is_string($add) ){
        $add = wp_parse_args($add);
        $url = add_query_arg( $add, $url );
    }

    if ($cur_page > 1) {
        $str .= '<a href="'.add_query_arg( array( $naming => 1), $url).'" class="pg_page pg_start">처음</a>'.PHP_EOL;
    }

    $start_page = ( ( (int)( ($cur_page - 1 ) / $write_pages ) ) * $write_pages ) + 1;
    $end_page = $start_page + $write_pages - 1;

    if ($end_page >= $total_page) $end_page = $total_page;

    if ($start_page > 1) $str .= '<a href="'.add_query_arg( array( $naming => $start_page-1), $url).'" class="pg_page pg_prev">이전</a>'.PHP_EOL;

    if ($total_page > 1) {
        for ($k=$start_page;$k<=$end_page;$k++) {
            if ($cur_page != $k)
                $str .= '<a href="'.add_query_arg( array( $naming =>$k ), $url).'" class="pg_page">'.$k.'<span class="sound_only">페이지</span></a>'.PHP_EOL;
            else
                $str .= '<span class="sound_only">열린</span><strong class="pg_current">'.$k.'</strong><span class="sound_only">페이지</span>'.PHP_EOL;
        }
    }

    if ($total_page > $end_page) $str .= '<a href="'.add_query_arg( array( $naming => $end_page+1), $url).'" class="pg_page pg_next">다음</a>'.PHP_EOL;

    if ($cur_page < $total_page) {
        $str .= '<a href="'.add_query_arg( array( $naming => $total_page), $url).'" class="pg_page pg_end">맨끝</a>'.PHP_EOL;
    }

    
    if ($str){
        $str_html = "<nav class=\"pg_wrap\"><span class=\"pg\">{$str}</span></nav>";
        return apply_filters( 'g5_get_paging', $str_html, $cur_page, $total_page, $url, $add );
    } else {
        return "";
    }
}

function g5_cut_str($str, $len, $suffix="…")
{
    $arr_str = preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
    $str_len = count($arr_str);

    if ($str_len >= $len) {
        $slice_str = array_slice($arr_str, 0, $len);
        $str = join("", $slice_str);

        return $str . ($str_len > $len ? $suffix : '');
    } else {
        $str = join("", $arr_str);
        return $str;
    }
}

// 이메일 주소 추출
function g5_get_email_address($email)
{
    preg_match("/[0-9a-z._-]+@[a-z0-9._-]{4,}/i", $email, $matches);
    
    if( isset($matches[0]) ){
        return $matches[0];
    } else {
        return '';
    }
}

function g5_sql_password($value, $member='')
{
    global $wpdb;

    if( $member ){
        if( ! (is_array($member) && isset($member['user_pass'])) ){
            $member = g5_get_member($member);
        }
        $value = $member['user_pass'];
    }

    // mysql 4.0x 이하 버전에서는 password() 함수의 결과가 16bytes
    // mysql 4.1x 이상 버전에서는 password() 함수의 결과가 41bytes
    $row = $wpdb->get_row(" select password('$value') as pass ", ARRAY_A);

    return apply_filters('g5_sql_password', $row['pass'], $value, $member);
}

// 경고메세지를 경고창으로
function g5_alert($msg='', $url='')
{
    if (!$msg) $msg = __('올바른 방법으로 이용해 주십시오.', G5_NAME);

    $html = '<meta charset="utf-8">';
    $html .= '<script type="text/javascript">alert("'.$msg.'");';
    if (!$url)
        $html .= 'history.go(-1);';
    $html .= "</script>";

    $html = apply_filters( 'g5_alert', $html, $url );
    do_action( 'g5_alert', $html, $url );

    echo $html;

    if ($url){
        g5_goto_url($url);
    }
    exit;
}

// 경고메세지 출력후 창을 닫음
function g5_alert_close($msg, $error=true, $url='')
{
    $html = '<meta charset="utf-8">';
    $html .= '<script type="text/javascript">alert("'.$msg.'");';
    if (!$url)
        $html .= 'window.close();';
    $html .= "</script>";

    $html = apply_filters( 'g5_alert_close', $html, $error );
    do_action( 'g5_alert_close', $html, $error );

    echo $html;
    exit;
}

function g5_goto_url($url)
{
    $url = str_replace("&amp;", "&", $url);

    if (!headers_sent())
        header('Location: '.$url);
    else {
        echo '<script>';
        echo 'location.replace("'.$url.'");';
        echo '</script>';
        echo '<noscript>';
        echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
        echo '</noscript>';
    }
    exit;
}

function g5_sql_fetch($sql){
    global $wpdb;
    return $wpdb->get_row( $sql, ARRAY_A );
}

function g5_sql_query($sql){
    global $wpdb;
    $wpdb->query($sql);
}

// XSS 관련 태그 제거
function g5_clean_xss_tags($str)
{
    $str = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $str);

    return apply_filters( 'g5_clean_xss_tags', $str);
}

// unescape nl 얻기
function g5_conv_unescape_nl($str)
{
    $search = array('\\r', '\r', '\\n', '\n');
    $replace = array('', '', "\n", "\n");

    return str_replace($search, $replace, $str);
}

// 게시판의 다음글 번호를 얻는다. ( 또는 코멘트 )
function g5_get_next_num($table, $bo_table, $btype='post')
{
    global $wpdb;

    // 가장 작은 번호를 얻는다.

    if( $btype == 'comment' ){
        $db_field = 'cm_num';
        $where_field = 'wr_id';
    } else {
        $db_field = 'wr_num';
        $where_field = 'bo_table';
    }
    
    // 이 함수에는 wp_cache 적용을 하지 않는다.

    $sql = $wpdb->prepare(" select min($db_field) as min_num from $table where $where_field = '%s' ", $bo_table);
    $row_min_num = $wpdb->get_var($sql);

    // 가장 작은 번호에 1을 빼서 넘겨줌
    return (int)($row_min_num - 1);
}

// 회원 정보를 얻는다.
function g5_get_member($mb_id, $slug='')
{
    global $wpdb, $gnupress;

    $mb_data = array(  // member 배열변수 초기화
        'user_level' => -1, //회원레벨 ( 비회원 일 경우 -1 )
        'mb_point' => 0,    //회원포인트 
        'mb_certify'=>'' ,   //본인인증
        'mb_adult'=>'',    //성인인증
        'user_id'=>0
    );

    if( ! $mb_id ){
        return $mb_data;
    }

    $member_array = wp_cache_get( 'g5_get_member_'.$mb_id );

    if( false === $member_array ){
        if( ! $slug && preg_match('/^\d+$/',$mb_id) ) {
            $user = get_userdata( $mb_id );
        } else {
            if( ! $slug ) $slug = 'login';
            $user = get_user_by( $slug, $mb_id );
        }

        if( ! isset($user->data) ){
            return $mb_data;
        }
        
        /*
        $member_array['user_id'] = $member_array['mb_id'] = ( isset($user->ID) && $user->ID > 0 ) ? $user->ID : '';
        $member_array['user_email'] = $member_array['mb_email'] = ( isset($user->user_email) ) ? $user->user_email : '';
        $member_array['user_display_name'] = $member_array['mb_nick'] = ( isset($user->display_name) ) ? $user->display_name : '';
        $member_array['user_url'] = $member_array['mb_homepage'] = ( isset($user->user_url) ) ? $user->user_url : '';
        $member_array['user_pass'] = $member_array['mb_password'] = ( isset($user->user_pass) ) ? $user->user_pass : '';
        */

        if( isset($user->data) && !empty($user->data) ){
            $member_array = wp_parse_args((array) $user->data , $mb_data);
        }
        $member_array['user_id'] = ( isset($user->ID) && $user->ID > 0 ) ? $user->ID : '';
        $member_array['user_display_name'] = ( isset($user->display_name) ) ? $user->display_name : '';

        if( $member_array['user_id'] ){
            $member_meta = get_user_meta( $member_array['user_id'] );

            if( !function_exists('g5_member_meta_extends') ){
                function g5_member_meta_extends($a){
                    return $a[0];
                }
            }
            // Filter out empty meta data
            $member_meta = array_filter( array_map('g5_member_meta_extends', $member_meta) );
            
            $check_meta_arr = array('first_name', 'last_name', 'mb_today_login', 'mb_login_ip');

            foreach( $check_meta_arr as $key=>$v ){
                $member_meta[$v] = isset($member_meta[$v]) ? $member_meta[$v] : '';
            }

            $member_meta['user_name'] = apply_filters('g5_get_member_name', $member_meta['first_name'].$member_meta['last_name'], $member_meta );

            $key_array = array('mb_id', 'mb_email', 'mb_nick', 'mb_homepage', 'mb_password');
            // $key_array에 포함된 값이 있다면 뺀다.
            $member_meta = array_diff( (array) $member_meta, $key_array);
            if( isset($member_meta[$wpdb->prefix.'user_level']) ){
                $member_meta['user_level'] = $member_meta[$wpdb->prefix.'user_level'];
            } else {
                $member_meta['user_level'] = 0;
            }
            $member_array = apply_filters( 'g5_get_member', wp_parse_args($member_meta, $member_array) );
        }

        wp_cache_set( 'g5_get_member_'.$mb_id , $member_array );
    }
    
    return $member_array;
}

// 게시판의 공지사항을 , 로 구분하여 업데이트 한다.
function g5_board_notice($bo_notice, $wr_id, $insert=false)
{
    $notice_array = explode(",", trim($bo_notice));

    if($insert && in_array($wr_id, $notice_array))
        return $bo_notice;

    $notice_array = array_merge(array($wr_id), $notice_array);
    $notice_array = array_unique($notice_array);
    foreach ($notice_array as $key=>$value) {
        if (!trim($value))
            unset($notice_array[$key]);
    }
    if (!$insert) {
        foreach ($notice_array as $key=>$value) {
            if ((int)$value == (int)$wr_id)
                unset($notice_array[$key]);
        }
    }
    return implode(",", $notice_array);
}

// url에 http:// 를 붙인다
function g5_set_http($url)
{
    if (!trim($url)) return;

    if (!preg_match("/^(http|https|ftp|telnet|news|mms)\:\/\//i", $url))
        $url = "http://" . $url;

    return $url;
}

// set_search_font(), get_search_font() 함수를 search_font() 함수로 대체
function g5_search_font($stx, $str)
{
    // 문자앞에 \ 를 붙입니다.
    $src = array('/', '|');
    $dst = array('\/', '\|');

    if (!trim($stx)) return $str;

    // 검색어 전체를 공란으로 나눈다
    $s = explode(' ', $stx);

    // "/(검색1|검색2)/i" 와 같은 패턴을 만듬
    $pattern = '';
    $bar = '';
    for ($m=0; $m<count($s); $m++) {
        if (trim($s[$m]) == '') continue;
        // 태그는 포함하지 않아야 하는데 잘 안되는군. ㅡㅡa
        //$pattern .= $bar . '([^<])(' . quotemeta($s[$m]) . ')';
        //$pattern .= $bar . quotemeta($s[$m]);
        //$pattern .= $bar . str_replace("/", "\/", quotemeta($s[$m]));
        $tmp_str = quotemeta($s[$m]);
        $tmp_str = str_replace($src, $dst, $tmp_str);
        $pattern .= $bar . $tmp_str . "(?![^<]*>)";
        $bar = "|";
    }

    // 지정된 검색 폰트의 색상, 배경색상으로 대체
    $replace = "<b class=\"sch_word\">\\1</b>";

    return preg_replace("/($pattern)/i", $replace, $str);
}

// 내용을 변환
function g5_conv_content($content, $html, $filter=true)
{
    if ($html)
    {
        $source = array();
        $target = array();

        $source[] = "//";
        $target[] = "";

        if ($html == 2) { // 자동 줄바꿈
            $source[] = "/\n/";
            $target[] = "<br/>";
        }

        // 테이블 태그의 개수를 세어 테이블이 깨지지 않도록 한다.
        $table_begin_count = substr_count(strtolower($content), "<table");
        $table_end_count = substr_count(strtolower($content), "</table");
        for ($i=$table_end_count; $i<$table_begin_count; $i++)
        {
            $content .= "</table>";
        }

        $content = preg_replace($source, $target, $content);

        if($filter)
            $content = g5_html_purifier($content);

    }
    else // text 이면
    {
        // & 처리 : &amp; &nbsp; 등의 코드를 정상 출력함
        $content = g5_html_symbol($content);

        // 공백 처리
		//$content = preg_replace("/  /", "&nbsp; ", $content);
		$content = str_replace("  ", "&nbsp; ", $content);
		$content = str_replace("\n ", "\n&nbsp;", $content);

        $content = g5_get_text($content, 1);
        $content = g5_url_auto_link($content);
    }

    return $content;
}

// way.co.kr 의 wayboard 참고
function g5_url_auto_link($str)
{
    global $gnupress;

    $config = $gnupress->config;

    // 140326 유창화님 제안코드로 수정
    // http://sir.co.kr/bbs/board.php?bo_table=pg_lecture&wr_id=461
    // http://sir.co.kr/bbs/board.php?bo_table=pg_lecture&wr_id=463
    $str = str_replace(array("&lt;", "&gt;", "&amp;", "&quot;", "&nbsp;", "&#039;"), array("\t_lt_\t", "\t_gt_\t", "&", "\"", "\t_nbsp_\t", "'"), $str);
    //$str = preg_replace("`(?:(?:(?:href|src)\s*=\s*(?:\"|'|)){0})((http|https|ftp|telnet|news|mms)://[^\"'\s()]+)`", "<A HREF=\"\\1\" TARGET='{$config['cf_link_target']}'>\\1</A>", $str);
    $str = preg_replace("/([^(href=\"?'?)|(src=\"?'?)]|\(|^)((http|https|ftp|telnet|news|mms):\/\/[a-zA-Z0-9\.-]+\.[가-힣\xA1-\xFEa-zA-Z0-9\.:&#=_\?\/~\+%@;\-\|\,\(\)]+)/i", "\\1<A HREF=\"\\2\" TARGET=\"{$config['cf_link_target']}\">\\2</A>", $str);
    $str = preg_replace("/(^|[\"'\s(])(www\.[^\"'\s()]+)/i", "\\1<A HREF=\"http://\\2\" TARGET=\"{$config['cf_link_target']}\">\\2</A>", $str);
    $str = preg_replace("/[0-9a-z_-]+@[a-z0-9._-]{4,}/i", "<a href=\"mailto:\\0\">\\0</a>", $str);
    $str = str_replace(array("\t_nbsp_\t", "\t_lt_\t", "\t_gt_\t", "'"), array("&nbsp;", "&lt;", "&gt;", "&#039;"), $str);

    return $str;
}

// 게시판 테이블 또는 게시판 코멘트 테이블에서 하나의 행을 읽음
function g5_get_write($write_table, $id, $column='wr_id')
{
    global $wpdb;

    $row = wp_cache_get( 'g5_'.$write_table.'_'.$id );
    if( false === $row ){
        $row = $wpdb->get_row($wpdb->prepare(" select * from `{$write_table}` where $column = %d ", $id), ARRAY_A);
        wp_cache_set( 'g5_'.$write_table.'_'.$id , $row );
    }

    return $row;
}

//워드프레스 기본에디터를 사용해서 쓴 내용이면 아래 함수 사용
function g5_hook_conv_wp($content, $wr_content, $filter= true){
    $output = wpautop($wr_content);
    $output = str_replace( ']]>', ']]&gt;', $output );

    if($filter)
        $output = g5_html_purifier($output);

    return $output;
}

//게시판의 설정을 읽어온다.
function g5_get_board_config($bo_table, $group=''){
    global $wpdb, $gnupress;

    $g5 = $gnupress->g5;

    $bo_table = preg_replace('/[^a-z0-9_]/i', '', trim($bo_table));
    $bo_table = substr($bo_table, 0, 20);

    $board = wp_cache_get( 'g5_bo_table_'.$bo_table, $group );
    if ( false === $board ) {
        $board = $wpdb->get_row($wpdb->prepare(" select * from {$g5['board_table']} where bo_table = '%s' ", $bo_table), ARRAY_A);
        $g5_page_url = g5_page_get_by($bo_table, 'url' );
        $board['page_url'] = $g5_page_url ? $g5_page_url : '';

        //태그 기능을 활성화 하기 위해서 해당 값을 등록
        if( isset($board['bo_use_tag']) && $board['bo_use_tag'] ){
            g5_wp_taxonomies($bo_table);
        }
        wp_cache_set( 'g5_bo_table_'.$bo_table, $board, $group );
    }

    if( isset( $board['bo_table'] ) ){
        $board = apply_filters( 'get_board_'.$bo_table, $board, $group );
    }
    return $board;
}

// 회원 레이어
function g5_get_sideview($user_id, $name='', $email='', $homepage='', $default_href='')
{
    global $gnupress;

    $config = $gnupress->config;
    $g5 = $gnupress->g5;
    $is_board_page = $gnupress->is_g5_page;
    $sca = isset($gnupress->qstr['sca']) ? $gnupress->qstr['sca'] : '';

    $slug = preg_match('/^\d+$/',$user_id) ? '' : 'login';
    $member = g5_get_member( $user_id, $slug );
    $user_id = $member['user_id'];
    $is_admin = current_user_can( 'administrator' ) ? 'super' : '';
    
    $default_href = $default_href ? $default_href : get_permalink();

    $email = base64_encode($email);

    $name = preg_replace("/\&#039;/", "", $name);
    $name = preg_replace("/\'/", "", $name);
    $name = preg_replace("/\"/", "&#034;", $name);
    $title_name = $name;

    $tmp_name = "";

    $profile_arr = array('user_id'=>$user_id, 'vaction' => 'profile' );
    $g5_profile_url = apply_filters('g5_profile_url', add_query_arg( $profile_arr, G5_DIR_URL.'g5_new.php' ) , $profile_arr);

    if ($user_id) {
        $tmp_name = '<a href="'.$g5_profile_url.'" class="sv_member" title="'.$name.' 자기소개" target="_blank" onclick="return false;">';

        $tmp_name .= ' '.$name;

        $tmp_name .= '</a>';

        $title_mb_id = '['.$user_id.']';
    } else {
        $tmp_name = '<a href="'.add_query_arg( array('sca'=>$sca, 'sfl'=> 'user_sidname,1', 'stx' => $name ), $default_href ).'" title="'.$name.' 이름으로 검색" class="sv_guest" onclick="return false;">'.$name.'</a>';
        $title_mb_id = '[비회원]';
    }

    $name     = $name ? g5_get_text($name) : '';
    $email    = $email ? g5_get_text($email) : '';
    $homepage = $homepage ? g5_get_text($homepage) : '';

    $str = "<span class=\"sv_wrap\">\n";
    $str .= $tmp_name."\n";

    $str2 = "<span class=\"sv\">\n";

    if($email){
        $formmail_arr = array('action' => 'formmail', 'user_id'=>$user_id, 'user_name'=> urlencode($name), 'email' => $email);
        $formmail_url = apply_filters('g5_formmail_url', add_query_arg( $formmail_arr, $gnupress->new_url ) , $formmail_arr);
        $str2 .= "<a href=\"".$formmail_url."\" onclick=\"gnupress.win_email(this.href); return false;\">메일보내기</a>\n";
    }
    if($homepage)
        $str2 .= "<a href=\"".$homepage."\" target=\"_blank\">홈페이지</a>\n";

    if($is_board_page) {
        if($user_id)
            $str2 .= "<a href=\"".add_query_arg( array('sca'=>$sca, 'sfl'=> 'user_id,1', 'stx' => $user_id ), $default_href )."\">아이디로 검색</a>\n";
        else
            $str2 .= "<a href=\"".add_query_arg( array('sca'=>$sca, 'sfl'=> 'user_display_name,1', 'stx' => $name ), $default_href )."\">이름으로 검색</a>\n";
    }

    if($is_admin == "super" && $user_id) {
        $str2 .= "<a href=\"".admin_url( 'admin.php?page=g5_point_list&amp;sfl=user_id&amp;stx='.$user_id )."\" target=\"_blank\">포인트내역</a>\n";
    }

    $str2 .= "</span>\n";
    $str .= $str2;
    $str .= "\n<noscript class=\"sv_nojs\">".$str2."</noscript>";

    $str .= "</span>";

    return $str;
}

function g5_check_bo_admin($bo_admin, $user_id){
    return false;
}

// 페이징 코드의 <nav><span> 태그 다음에 코드를 삽입
function g5_page_insertbefore($paging_html, $insert_html)
{
    if(!$paging_html)
        $paging_html = '<nav class="pg_wrap"><span class="pg"></span></nav>';

    return preg_replace("/^(<nav[^>]+><span[^>]+>)/", '$1'.$insert_html.PHP_EOL, $paging_html);
}

// 페이징 코드의 </span></nav> 태그 이전에 코드를 삽입
function g5_page_insertafter($paging_html, $insert_html)
{
    if(!$paging_html)
        $paging_html = '<nav class="pg_wrap"><span class="pg"></span></nav>';

    if(preg_match("#".PHP_EOL."</span></nav>#", $paging_html))
        $php_eol = '';
    else
        $php_eol = PHP_EOL;

    return preg_replace("#(</span></nav>)$#", $php_eol.$insert_html.'$1', $paging_html);
}

//meta데이터에 들어갈 파일첨부 형식
function g5_get_file_data($bo_table, $wr_id, $i, $upload, $bf_content){

    $file_data = apply_filters('g5_get_file_data' ,array(
        'bo_table'=>$bo_table,
        'wr_id'=>$wr_id,
        'bf_no'=>$i,
        'bf_source'=>$upload['source'],
        'bf_file'=>$upload['file'],
        'bf_content'=>$bf_content,
        'bf_download'=>0,
        'bf_filesize'=>$upload['filesize'],
        'bf_width'=>$upload['image']['0'],
        'bf_height'=>$upload['image']['1'],
        'bf_type'=>$upload['image']['2'],
        'bf_datetime'=>G5_TIME_YMDHIS
        ));

    return $file_data;
}


// 게시글에 첨부된 파일을 얻는다. (배열로 반환)
function g5_get_file($board, $wr_id, $qstr=array(), $default_href='')
{
    if( !g5_get_upload_path() ){
        return array();
    }
    $bo_table = $board['bo_table'];
    $file = array('count'=>0);
    $file_meta_data = get_metadata(G5_META_TYPE, $wr_id, G5_FILE_META_KEY, true );

    foreach((array) $file_meta_data as $key=>$row){
        if( empty($row) ) continue;
        $no = $key;
        //$no = $row['bf_no'];

        $file[$no]['href'] = add_query_arg( array_merge( array('action'=>'download', 'wr_id'=>$wr_id, 'no'=>$no ), (array) $qstr ), $default_href );
        $file[$no]['download'] = $row['bf_download'];
        // 4.00.11 - 파일 path 추가
        $file[$no]['path'] = g5_get_upload_path().'/file/'.$bo_table;
        $file[$no]['size'] = g5_get_filesize($row['bf_filesize']);
        $file[$no]['datetime'] = $row['bf_datetime'];
        $file[$no]['source'] = addslashes($row['bf_source']);
        $file[$no]['bf_content'] = $row['bf_content'];
        $file[$no]['content'] = g5_get_text($row['bf_content']);
        //$file[$no]['view'] = view_file_link($row['bf_file'], $file[$no]['content']);
        $file[$no]['view'] = g5_view_file_link($board, $row['bf_file'], $row['bf_width'], $row['bf_height'], $file[$no]['content']);
        $file[$no]['file'] = $row['bf_file'];
        $file[$no]['image_width'] = $row['bf_width'] ? $row['bf_width'] : 640;
        $file[$no]['image_height'] = $row['bf_height'] ? $row['bf_height'] : 480;
        $file[$no]['image_type'] = $row['bf_type'];
        $file['count']++;
    }

    return $file;
}

// 파일을 보이게 하는 링크 (이미지, 플래쉬, 동영상)
function g5_view_file_link($board, $file, $width, $height, $content='')
{
    if( !g5_get_upload_path() ) return;

    global $gnupress;

    $config = $gnupress->config;
    static $ids;

    if (!$file) return;

    $ids++;

    // 파일의 폭이 게시판설정의 이미지폭 보다 크다면 게시판설정 폭으로 맞추고 비율에 따라 높이를 계산
    if ($width > $board['bo_image_width'] && $board['bo_image_width'])
    {
        $rate = $board['bo_image_width'] / $width;
        $width = $board['bo_image_width'];
        $height = (int)($height * $rate);
    }

    // 폭이 있는 경우 폭과 높이의 속성을 주고, 없으면 자동 계산되도록 코드를 만들지 않는다.
    if ($width)
        $attr = ' width="'.$width.'" height="'.$height.'" ';
    else
        $attr = '';

    if (preg_match("/\.({$config['cf_image_extension']})$/i", $file)) {
        $img = '<a href="'.g5_get_upload_path('url').'/file/'.$board['bo_table'].'/'.urlencode($file).'" target="_blank" class="view_image">';
        $img .= '<img src="'.g5_get_upload_path('url').'/file/'.$board['bo_table'].'/'.urlencode($file).'" alt="'.$content.'">';
        $img .= '</a>';

        return $img;
    }
}

//file 업로드 폴더 경로를 가져온다.
function g5_get_upload_path($type='dir'){
    $upload_dir = array('error'=>'error');
    try{
        $upload_dir = wp_upload_dir();
    } catch (Exception $e) {
    }
    $path = '';
    if( empty($upload_dir['error']) ){
        $path = apply_filters('g5_get_upload_'.$type, $upload_dir['base'.$type].'/'.G5_NAME, $upload_dir);
    }
    return $path;
}

// view_file_link() 함수에서 넘겨진 이미지를 보이게 합니다.
// {img:0} ... {img:n} 과 같은 형식
function g5_view_image($view, $number, $attribute)
{
    if ( isset($view['file'][$number]['view']) && !empty($view['file'][$number]['view']) ){
        return preg_replace("/>$/", " $attribute>", $view['file'][$number]['view']);
    } else {
        //return "{".$number."번 이미지 없음}";
        return "";
    }
}

// 파일의 용량을 구한다.
function g5_get_filesize($size)
{
    //$size = @filesize(addslashes($file));
    if ($size >= 1048576) {
        $size = number_format($size/1048576, 1) . "M";
    } else if ($size >= 1024) {
        $size = number_format($size/1024, 1) . "K";
    } else {
        $size = number_format($size, 0) . "byte";
    }
    return $size;
}

// wr_option 이나 cm_option에 들어갈때 쓰이는 함수
function g5_get_arg_array(){
    $tmp_arr = array();
    $arg_list = func_get_args();
    foreach( $arg_list as $arg ){
        if( empty($arg) ) continue;
        array_push($tmp_arr, $arg);
    }
    if( count($tmp_arr) ){
        return implode(',', $tmp_arr);
    }
    return '';
}

// 변수 또는 배열의 이름과 값을 얻어냄. print_r() 함수의 변형
function g5_print_r2($var)
{
    ob_start();
    print_r($var);
    $str = ob_get_contents();
    ob_end_clean();
    $str = str_replace(" ", "&nbsp;", $str);
    echo nl2br("<span style='font-family:Tahoma, 굴림; font-size:9pt;'>$str</span>");
}

// 쿠키변수값 얻음
function g5_get_cookie($cookie_name)
{
    $cookie = md5($cookie_name);
    if (array_key_exists($cookie, $_COOKIE))
        return base64_decode($_COOKIE[$cookie]);
    else
        return "";
}

// array 안에 값이 있는 키만 리턴한다.
function g5_null_array_check($arr=array()){
    $tmp = array();
    foreach( $arr as $key=>$v){
        if( empty($v) ) continue;
        $tmp[$key] = $v;
    }
    return $tmp;
}

// 에디터 이미지 얻기
function g5_get_editor_image($contents, $view=true){
    if(!$contents)
        return false;

    // $contents 중 img 태그 추출
    if ($view)
        $pattern = "/<img([^>]*)>/iS";
    else
        $pattern = "/<img[^>]*src=[\'\"]?([^>\'\"]+[^>\'\"]+)[\'\"]?[^>]*>/i";
    preg_match_all($pattern, $contents, $matchs);

    return $matchs;
}

function g5_js_escape($s) {
	return str_replace('"',"\\\"", $s);
}

function g5_is_login_page() {
    return in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'));
}

function _g5_get_list_table( $class, $taxonomy='', $bo_table='', $args = array() ) {
	$g5_classes = array(
		//Site Admin
        'G5_Terms_List_Table' => 'terms'
	);

	if ( isset( $g5_classes[ $class ] ) ) {
		foreach ( (array) $g5_classes[ $class ] as $required ){
			require_once( G5_DIR_PATH . 'adm/includes/class-g5-' . $required . '-list-table.php' );
        }

		if ( isset( $args['screen'] ) )
			$args['screen'] = convert_to_screen( $args['screen'] );
		elseif ( isset( $GLOBALS['hook_suffix'] ) )
			$args['screen'] = get_current_screen();
		else
			$args['screen'] = null;

        $args['screen']->taxonomy = $taxonomy;
        $args['screen']->bo_table = $bo_table;

		return new $class( $args );
	}

	return false;
}

function g5_register_cache_tag($bo_table, $term_ids=array()){
    global $wpdb, $gnupress;

    $g5 = $gnupress->g5;

    if( !count( $term_ids ) ) 
        return false;

    $taxonomy = g5_get_taxonomy($bo_table);
    $search_terms = array();
    $terms = array();

    foreach( $term_ids as $term_id ){
        if( ! $terms[] = wp_cache_get($term_id, $taxonomy) ){
            $search_terms[] = $term_id;
        }
    }

    if( ! $search_terms ){
        return $terms;
    }

    $arg = array( 'include' => implode(',' , $search_terms) );

    $terms = g5_get_terms( $taxonomy, $arg );

    return $terms;
}

function g5_get_tag_info($term_id, $bo_table){
    $taxonomy = g5_get_taxonomy($bo_table);

    $term = wp_cache_get( $term_id, $taxonomy );
    if( false === $term ){
        $result = g5_register_cache_tag( $bo_table, (array) $term_id );
        if( isset($result[0]) && !empty($result[0]) ){
            $term = $result[0];
        } else {
            $term = '';
        }
    }
    if( !$term ){
        return false;
    }
    return (array) $term;
}

function g5_get_current_page() {
 return substr($_SERVER["SCRIPT_NAME"],strrpos($_SERVER["SCRIPT_NAME"],"/")+1);
}

//태그 기능을 쓰기 위해서 wp_taxonomies 변수에 해당 값을 등록
function g5_wp_taxonomies($bo_table){
    global $wp_taxonomies;

    if( !isset($wp_taxonomies[g5_get_taxonomy($bo_table)]) ){
        $wp_taxonomies[g5_get_taxonomy($bo_table)] = $wp_taxonomies['post_tag'];
    }
}

if ( ! function_exists('g5_tag_class_link'))
{
    function g5_tag_class_link( $tag, $search_tag = '' , $classname = '', $classname2 = 'tags-txt', $span_text = '' ){
        $tag_html = g5_tag_html((array) $tag, '' , $search_tag);
        if( preg_match("(<b>(.*?)</b>)", $tag_html) ){
            $href = '<a href="'.$tag['href'].'" class="'.$classname.'"><span class="'.$classname2.'">'.$tag_html.'</span>'.$span_text.'</a>';
        } else {
            $href = '<a href="'.$tag['href'].'" ><span class="'.$classname2.'">'.$tag['name'].'</span>'.$span_text.'</a>';
        }
        return apply_filters('g5_tag_class_link', $href, $tag, $search_tag, $classname, $classname2, $span_text );
    }
}

if ( ! function_exists('g5_tag_html'))
{
    function g5_tag_html($tag, $multiline=false, $search_tag=''){
        $html=$tag['name'];
        
        if ($multiline) {
            $html=preg_replace('/\r\n?/', "\n", $html);
            $html=preg_replace('/(?<=\s) /', '&nbsp;', $html);
            $html=str_replace("\t", '&nbsp; &nbsp; ', $html);
            $html=nl2br($html);
        }
        
        if( $search_tag ){
            if(is_array($search_tag)){
                $array_search_tag = $search_tag;
            } else {
                $array_search_tag = array_unique( preg_split("/[\s,]+/", $search_tag) );
            }

            foreach( $array_search_tag as &$v ){
                $v = sanitize_title($v);
            }

            if($search_tag && in_array($tag['slug'], $array_search_tag) ){
                // 지정된 검색 폰트의 색상, 배경색상으로 대체
                $html = "<b>$html</b>";
            }
        }
        return $html;
    }
}

//JAVASCRIPT 문자열로 변환할때 특정문자열을 addcslashes 해 준다.
if ( !function_exists('g5_useskin_js_str'))
{
    function g5_useskin_js_str($s)
    {
        return '"' . addcslashes($s, "\0..\37\"\\") . '"';
    }
}

//PHP 배열을 JAVASCRIPT 문자열로 변환해 주는 함수
if ( !function_exists('g5_useskin_js_array'))
{
    function g5_useskin_js_array($array)
    {
        $temp = array_map('g5_useskin_js_str', $array);
        return '[' . implode(',', $temp) . ']';
    }
}

if ( !function_exists('g5_attach_file'))
{
    function g5_attach_file($filename, $tmp_name){

        if( !g5_get_upload_path() ) return '';

        $dir_path = g5_get_upload_path().'/tmp/';
        if( !file_exists($dir_path) ){
            @mkdir($dir_path, G5_DIR_PERMISSION);
            @chmod($dir_name, G5_DIR_PERMISSION);
        }
        // 서버에 업로드 되는 파일은 확장자를 주지 않는다. (보안 취약점)
        $dest_file = $dir_path.str_replace('/', '_', $filename);
        move_uploaded_file($tmp_name, $dest_file);
        /*
        $fp = fopen($tmp_name, "r");
        $tmpfile = array(
            "name" => $filename,
            "tmp_name" => $tmp_name,
            "data" => fread($fp, filesize($tmp_name)));
        fclose($fp);
        */
        $tmpfile = array("name" => $filename, "path" => $dest_file);
        return $tmpfile;
    }
}

// http://htmlpurifier.org/
// Standards-Compliant HTML Filtering
// Safe  : HTML Purifier defeats XSS with an audited whitelist
// Clean : HTML Purifier ensures standards-compliant output
// Open  : HTML Purifier is open-source and highly customizable
function g5_html_purifier($html)
{
    if( !g5_get_upload_path() ){
        return $html;
    }
    $f = file(G5_PLUGIN_PATH.'/htmlpurifier/safeiframe.txt');
    $domains = array();
    foreach($f as $domain){
        // 첫행이 # 이면 주석 처리
        if (!preg_match("/^#/", $domain)) {
            $domain = trim($domain);
            if ($domain)
                array_push($domains, $domain);
        }
    }
    // 내 도메인도 추가
    array_push($domains, $_SERVER['HTTP_HOST'].'/');
    $safeiframe = implode('|', $domains);

    if( ! class_exists('HTMLPurifier') ){
        include_once(G5_PLUGIN_PATH.'/htmlpurifier/HTMLPurifier.standalone.php');
    }
    $config = HTMLPurifier_Config::createDefault();
    // data/cache 디렉토리에 CSS, HTML, URI 디렉토리 등을 만든다.
    $config->set('Cache.SerializerPath', g5_get_upload_path().'/cache');
    $config->set('HTML.SafeEmbed', true);
    $config->set('HTML.SafeObject', true);
    $config->set('HTML.SafeIframe', true);
    $config->set('URI.SafeIframeRegexp','%^(https?:)?//('.$safeiframe.')%');
    $config->set('Attr.AllowedFrameTargets', array('_blank'));
    $purifier = new HTMLPurifier($config);
    return $purifier->purify($html);
}

// 검색어 특수문자 제거
function g5_get_search_string($stx)
{
    $stx_pattern = array();
    $stx_pattern[] = '#\.*/+#';
    $stx_pattern[] = '#\\\*#';
    $stx_pattern[] = '#\.{2,}#';
    $stx_pattern[] = '#[/\'\"%=*\#\(\)\|\+\&\!\$~\{\}\[\]`;:\?\^\,]+#';

    $stx_replace = array();
    $stx_replace[] = '';
    $stx_replace[] = '';
    $stx_replace[] = '.';
    $stx_replace[] = '';

    $stx = preg_replace($stx_pattern, $stx_replace, $stx);

    return $stx;
}

function g5_get_taxonomy( $bo_table, $type='tag', $prefix='g5' ){
    if( !$bo_table ){
        return null;
    }

    return apply_filters('g5_get_taxonomy_name', $prefix.'_'.$bo_table.'_'.$type , $bo_table, $type, $prefix );
}

function g5_show_db_error(){
    global $wpdb;

    // G5_DEBUG 가 TRUE 이면 db에러를 출력
    if( G5_DEBUG ){
        $wpdb->show_errors();
        $wpdb->print_error();
        exit;
    }
}

function g5_get_editor_content($content){
    global $gnupress;

    if( isset($gnupress->config['cf_editor']) && $gnupress->config['cf_editor'] == 'wordpress' ){
        return $content;
    } else {
        return g5_get_text($content, 0);
    }
}

// board.php 에서 게시판의 설정된 상단 내용을 불러옴
function g5_board_head_print($board, $wr_id=0){
    global $gnupress;

    if (G5_IS_MOBILE) {
        if($board['bo_mobile_content_head']){
            if( $gnupress->config['cf_editor'] == 'wordpress' ){
                echo g5_hook_conv_wp(stripslashes($board['bo_content_head']), $board['bo_content_head']);
            } else {
                echo stripslashes($board['bo_mobile_content_head']);
            }
        }
    } else {
        if($board['bo_content_head']){
            if( $gnupress->config['cf_editor'] == 'wordpress' ){
                echo g5_hook_conv_wp(stripslashes($board['bo_content_head']), $board['bo_content_head']);
            } else {
                echo stripslashes($board['bo_content_head']);
            }
        }
    }

    do_action('g5_board_head_action', $board, $wr_id );
}

// board.php 에서 게시판의 설정된 하단 내용을 불러옴
function g5_board_tail_print($board, $wr_id=0){
    global $gnupress;

    if (G5_IS_MOBILE) {
        if($board['bo_mobile_content_tail']){
            if( $gnupress->config['cf_editor'] == 'wordpress' ){
                echo g5_hook_conv_wp(stripslashes($board['bo_content_tail']), $board['bo_content_tail']);
            } else {
                echo stripslashes($board['bo_mobile_content_tail']);
            }
        }
    } else {
        if($board['bo_content_tail']){
            if( $gnupress->config['cf_editor'] == 'wordpress' ){
                echo g5_hook_conv_wp(stripslashes($board['bo_content_tail']), $board['bo_content_tail']);
            } else {
                echo stripslashes($board['bo_content_tail']);
            }
        }
    }

    do_action('g5_board_tail_action', $board, $wr_id );
}

function g5_delete_cache_latest($bo_table){
    if( $datapath = g5_get_upload_path() ){
        $files = glob($datapath.'/cache/latest-'.$bo_table.'-*');
        if (is_array($files)) {
            foreach ($files as $filename)
                @unlink($filename);
        }
    }
}

// http://stackoverflow.com/questions/6377147/sort-an-array-placing-children-beneath-parents 참고
function g5_parent_child_sort($idField, $parentField, $els, $parentID = 0, &$result = array(), &$depth = 0){
    foreach ($els as $key => $value):
        if ($value[$parentField] == $parentID){
            $value['wr_depth'] = $depth;
            array_push($result, $value);
            unset($els[$key]);
            $oldParent = $parentID; 
            $parentID = $value[$idField];
            $depth++;
            g5_parent_child_sort($idField,$parentField, $els, $parentID, $result, $depth);
            $parentID = $oldParent;
            $depth--;
        }
    endforeach;
    return $result;
}

function g5_get_content_text($content, $cut_str_length='60'){
    $content = strip_tags($content);
    $content = preg_replace('/\&nbsp\;/', '', $content);
    $content = g5_cut_str(addslashes(htmlspecialchars_decode($content)),$cut_str_length,"...");
    return $content;
}

function g5_get_page_id( $action ) {
    global $wpdb;

    if ( ! $page_id = wp_cache_get( $action, 'g5_page_id' ) ) {
        $page_id = $wpdb->get_var( $wpdb->prepare( "SELECT p.ID FROM $wpdb->posts p LEFT JOIN $wpdb->postmeta pmeta ON p.ID = pmeta.post_id WHERE p.post_type = 'page' AND pmeta.meta_key = '".G5_META_KEY."' AND pmeta.meta_value = %s", $action ) );
        if ( ! $page_id )
            return null;
        wp_cache_add( $action, $page_id, 'g5_page_id' );
    }
    return $page_id;
}

function g5_page_get_by($bo_table, $by='url'){
    global $gnupress;

    switch ( $by ) {
        case 'page_id' :
            $g5_options = get_option(G5_OPTION_KEY);
            
            $page_url = false;
            if( isset($g5_options['board_page'][$bo_table]) && !empty($g5_options['board_page'][$bo_table]) ){
                return $g5_options['board_page'][$bo_table];
            } else {
                return 0;
            }
            break;
        case 'name':
            return G5_NAME."-".$bo_table;
            break;
        case 'url' :
        default :
            $g5_options = get_option(G5_OPTION_KEY);
            
            $page_url = false;
            if( isset($g5_options['board_page'][$bo_table]) && !empty($g5_options['board_page'][$bo_table]) ){
                $page_url = get_permalink($g5_options['board_page'][$bo_table]);
            }
            return $page_url;
    }

}

function g5_get_plugin_path($name='editor'){
    switch ( $name ) {
        case 'editor' :
        default :
            return G5_PLUGIN_PATH.'/'.$name;
    }
}

function g5_get_plugin_url($name='editor'){
    switch ( $name ) {
        case 'editor' :
        default :
            return G5_PLUGIN_URL.'/'.$name;
    }
}

function g5_get_link_by( $by='point' ){
    global $gnupress;

    switch ( $by ) {
        case 'point' :
        default :
            return add_query_arg( array('action'=>$by), $gnupress->new_url );
    }
}

// $_POST 형식에서 checkbox 엘리먼트의 checked 속성에서 checked 가 되어 넘어 왔는지를 검사
function g5_is_checked($field)
{
    $checked = ( isset($_POST[$field]) && !empty($_POST[$field]) ) ? true : false;
    return $checked;
}

//글쓰기 메타 데이터 복사
function g5_writemeta_copy( $new_wr_id, $before_wr_id ){
    global $wpdb, $gnupress;

    $g5 = $gnupress->g5;
    $sql = $wpdb->prepare( "select * from {$g5['meta_table']} where g5_wr_id = %d", (int) $before_wr_id);

    if( $rows = $wpdb->get_results($sql, ARRAY_A) ){
        foreach( $rows as $row ){
            unset($row['meta_id']);
            $row['g5_wr_id'] = (int) $new_wr_id;
            
            $result = $wpdb->insert( $g5['meta_table'], $row );
        }
    }

}

//댓글 복사
function g5_comment_copy( $new_wr_id, $before_wr_id, $new_bo_table='', $before_bo_table='' ){
    global $wpdb, $gnupress;

    $g5 = $gnupress->g5;
    $sql = $wpdb->prepare("select * from {$g5['comment_table']} where wr_id = %d order by cm_num, cm_parent ", (int) $before_wr_id);
    $before_parent_arr = array();
    if( $rows = $wpdb->get_results($sql, ARRAY_A) ){
        
        foreach( $rows as $row ){
            $save_wr_id = $row['cm_id'];
            unset($row['cm_id']);
            $row['wr_id'] = $new_wr_id;
            
            if( $new_bo_table )
                $row['bo_table'] = $new_bo_table;

            if( (int) $row['cm_parent'] > 0 ){
                if( isset($before_parent_arr['id_'.$row['cm_parent']]) ){
                    $row['cm_parent'] = $before_parent_arr['id_'.$row['cm_parent']];
                }
            }
            if( $result = $wpdb->insert($g5['comment_table'], $row) ){
                $before_parent_arr['id_'.$save_wr_id] = $wpdb->insert_id;
            }
        }

    }
}

//태그 데이터 복사
function g5_tagdata_copy( $new_wr_id, $before_wr_id, $new_bo_table, $before_bo_table ){
    global $wpdb, $gnupress;

    $before_taxonomy = g5_get_taxonomy($before_bo_table);

    if( $term_info = g5_get_object_terms( $before_wr_id, $before_taxonomy ) ){
        
        $terms = array();
        foreach( $term_info as $v ){
            $terms[] = $v->name;
        }
        
        if( count( $terms ) > 0 ){
            g5_wp_taxonomies($new_bo_table);
            $new_taxonomy = g5_get_taxonomy($new_bo_table);

            $result = g5_set_object_terms( $new_wr_id, $terms, $new_taxonomy );
        }
    }

}

//메일 content_type
function g5_set_html_content_type(){
    return apply_filters('g5_mail_content_type', 'text/html');
}

//form action 을 검사한다.
function g5_form_action_url($action_url){
    return apply_filters('g5_form_action_filter', esc_url($action_url));
}

//게시판 thumbnail 삭제
function g5_delete_board_thumbnail($bo_table, $file){
    if(!$bo_table || !$file || !g5_get_upload_path() )
        return;

    $fn = preg_replace("/\.[^\.]+$/i", "", basename($file));
    $files = glob(g5_get_upload_path().'/file/'.$bo_table.'/thumb-'.$fn.'*');
    if (is_array($files)) {
        foreach ($files as $filename)
            unlink($filename);
    }
}

// 에디터 썸네일 삭제
function g5_delete_editor_thumbnail($contents)
{
    if(!$contents)
        return;

    // $contents 중 img 태그 추출
    $matchs = g5_get_editor_image($contents);

    if(!$matchs)
        return;

    for($i=0; $i<count($matchs[1]); $i++) {
        // 이미지 path 구함
        $imgurl = parse_url($matchs[1][$i]);
        $srcfile = $_SERVER['DOCUMENT_ROOT'].$imgurl['path'];

        $filename = preg_replace("/\.[^\.]+$/i", "", basename($srcfile));
        $filepath = dirname($srcfile);
        $files = glob($filepath.'/thumb-'.$filename.'*');
        if (is_array($files)) {
            foreach($files as $filename)
                unlink($filename);
        }
    }
}

function g5_pre( $msg ){
    if( G5_DEBUG ){
        echo "<pre>";
        print_r($msg);
        echo "</pre>";
    }
}

function g5_new_html_header($page_mode=''){
?>
<!DOCTYPE html>
<!--[if IE 8]>
    <html xmlns="http://www.w3.org/1999/xhtml" class="ie8" <?php language_attributes(); ?>>
<![endif]-->
<!--[if !(IE 8) ]><!-->
    <html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<!--<![endif]-->
<head>
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
<title><?php bloginfo('name'); ?> &rsaquo; <?php echo G5_NAME; ?></title>
<?php do_action( 'wp_head' ); ?>
<?php do_action( 'g5_head_new_'.$page_mode ); ?>
</head>
<div class="g5_new_shortcode">
<?php
}

function g5_new_html_footer($page_mode=''){
?>
</div>
<?php do_action( 'g5_footer_new_'.$page_mode ); ?>
<?php do_action( 'wp_footer' ); ?>
</body>
</html>
<?php
}

if( !function_exists('g5_new_style_script') ){
    function g5_new_style_script(){
        wp_enqueue_style ( 'g5-board-new-style' , G5_DIR_URL.'view/css/g5_new.css', '', G5_VERSION );
    }
}

if( !function_exists('g5_remove_admin_bar_style') ){
    function g5_remove_admin_bar_style() {
        echo '<style type="text/css" media="screen">
        html { margin-top: 0px !important; }
        * html body { margin-top: 0px !important; }
        </style>';
    }
}
?>