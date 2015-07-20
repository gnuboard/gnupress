<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 태그 사용 여부
$is_use_tag = false;

if( $board['bo_use_tag'] ){
	$is_use_tag = true;
	//게시판에 기록된 태그들을 가져온다.
	$board_tag_lists = g5_get_terms( g5_get_taxonomy($bo_table) , apply_filters('g5_list_get_tag_where', array( 'orderby' => 'count', 'order' => 'DESC' )) );

	foreach( $board_tag_lists as &$v ){
		if( empty( $v ) ) continue;
		$v = (array) $v;
		$v['href'] = add_query_arg( array_merge( (array) $qstr, array('tag'=>$v['slug'])) , $default_href);
	}
	unset($v);
}

// 분류 사용 여부
$is_category = false;
$category_option = '';
if ($board['bo_use_category']) {
    $is_category = true;
    $category_href = $default_href;

    $category_option .= '<li><a href="'.$default_href.'"';
    if ($sca=='')
        $category_option .= ' id="bo_cate_on"';
    $category_option .= '>전체</a></li>';

    $categories = explode('|', $board['bo_category_list']); // 구분자가 , 로 되어 있음
    for ($i=0; $i<count($categories); $i++) {
        $category = trim($categories[$i]);
        if ($category=='') continue;
        $category_option .= '<li><a href="'.add_query_arg( array('sca'=>urlencode($category)) , $default_href).'"';
        $category_msg = '';
        if ($category==$sca) { // 현재 선택된 카테고리라면
            $category_option .= ' id="bo_cate_on"';
            $category_msg = '<span class="sound_only">열린 분류 </span>';
        }
        $category_option .= '>'.$category_msg.$category.'</a></li>';
    }
}

$sop = strtolower($sop);
if ($sop != 'and' && $sop != 'or')
    $sop = 'and';

// 분류 선택 또는 검색어가 있다면
$stx = trim($stx);
if ($sca || $stx || count($search_tag)) {
    $sql_search = g5_get_sql_search($sca, $sfl, $stx, $sop, count($search_tag));

    // 가장 작은 번호를 얻어서 변수에 저장 (하단의 페이징에서 사용)
    $sql = $wpdb->prepare(" select MIN(wr_num) as min_wr_num from {$write_table} where bo_table = '%s' ", $bo_table);
    $row = $wpdb->get_row($sql, ARRAY_A);
    $min_spt = (int)$row['min_wr_num'];

    if (!$spt) $spt = $min_spt;
    
    $sql_search .= $wpdb->prepare(" and bo_table = '%s' and (wr_num between %d and (%d + %d)) ", $bo_table, $spt, $spt, $config['cf_search_part']);

    $sql = " select count(wr_id) as cnt from {$write_table} where {$sql_search} ";

    if( count($search_tag) && $board['bo_use_tag'] ){  //게시판에서 태그 기능을 사용하고, 태그 검색을 하고자 한다면...
        
        $arr_tmp_tag = array();
        $sql_search_add_sql = '';

        foreach( $search_tag as $v ){
            if( $tag_info = g5_term_exists($v, g5_get_taxonomy($bo_table)) ){
                $arr_tmp_tag[] = "t.term_taxonomy_id = '{$tag_info['term_taxonomy_id']}'";
            }
        }
        
        // 검색할 태그가 없다면
        if ( ! count( $arr_tmp_tag ) ){
            $sql_search .= 'and 0';
        }

        $sql_tag_text = implode( " or ", $arr_tmp_tag );
        if( count($arr_tmp_tag) > 1 ){ // 태그 AND검색
            $sql_search_add_sql = " group by wr.wr_id having COUNT( DISTINCT t.object_id ) = ".count($arr_tmp_tag);
        }
        if( $sql_tag_text ){
            $sql_search .= " and ( $sql_tag_text ) ";
        }
        if( !$sql_search_add_sql ){
            $sql = "select count(wr_id) as cnt from {$write_table} wr left join {$g5['relation_table']} t on wr.wr_id = t.object_id where {$sql_search}";
        } else {    //group by 절이 들어가므로...
            $sql = " select count( t.total ) as cnt from (select count(wr_id) as total from $write_table wr left join {$g5['relation_table']} t on wr.wr_id = t.object_id where $sql_search $sql_search_add_sql) t ";
        }
    }

    $sql = apply_filters('g5_list_search_total', $sql, $board, $sql_search, $search_tag, $sca, $stx );

    $total_count = $wpdb->get_var($sql);
} else {
    $sql_search = "";

    $total_count = $board['bo_count_write'];
}

if(G5_IS_MOBILE) {
    $page_rows = $board['bo_mobile_page_rows'];
    $list_page_rows = $board['bo_mobile_page_rows'];
} else {
    $page_rows = $board['bo_page_rows'];
    $list_page_rows = $board['bo_page_rows'];
}

if ($page < 1) { $page = 1; } // 페이지가 없으면 첫 페이지 (1 페이지)

// 년도 2자리
$today2 = G5_TIME_YMD;

$notice_count = 0;
$notice_array = array();
$notice_list = array();

$k = 0;
// 공지 처리
if (!$sca && !$stx && !count($search_tag)) {
    
    $from_notice_idx = ($page - 1) * $page_rows;
    if($from_notice_idx < 0)
        $from_notice_idx = 0;
    
    if( !empty($board['bo_notice']) ){ //공지가 있다면

        $arr_notice = explode(',', trim($board['bo_notice']));
        $board_notice_count = count($arr_notice);
        $wr_ids = join(',' , $arr_notice);

        if( !$wr_ids ) break;
        $sql = "select * from {$write_table} where wr_id in ( $wr_ids ) order by wr_num";
        $notice_list = $wpdb->get_results($sql, ARRAY_A);

        foreach( $notice_list as &$row ){
            if (!$row['wr_id']) continue;
            $notice_array[] = $row['wr_id'];
            if($k < $from_notice_idx) continue;
        
            $row = $this->get_list($row, $board, $board_skin_url, G5_IS_MOBILE ? $board['bo_mobile_subject_len'] : $board['bo_subject_len'], $default_href);
            $row['is_notice'] = true;
            $notice_count++;
            $k++;

            if($notice_count >= $list_page_rows)
                break;
        }
    }
}

$total_page  = ceil($total_count / $page_rows);  // 전체 페이지 계산
$from_record = ($page - 1) * $page_rows; // 시작 열을 구함

// 공지글이 있으면 변수에 반영
if(!empty($notice_array)) {
    $from_record -= count($notice_array);

    if($from_record < 0)
        $from_record = 0;

    if($notice_count > 0)
        $page_rows -= $notice_count;

    if($page_rows < 0)
        $page_rows = $list_page_rows;
}

// 관리자라면 CheckBox 보임
$is_checkbox = false;
if ($is_member && ($is_admin == 'super' || $is_admin == 'board'))
    $is_checkbox = true;

// 정렬에 사용하는 QUERY_STRING
$qstr2 = 'bo_table='.$bo_table.'&amp;sop='.$sop;

// 0 으로 나눌시 오류를 방지하기 위하여 값이 없으면 1 로 설정
$bo_gallery_cols = $board['bo_gallery_cols'] ? $board['bo_gallery_cols'] : 1;
$td_width = (int)(100 / $bo_gallery_cols);

// 정렬
// 인덱스 필드가 아니면 정렬에 사용하지 않음
//if (!$sst || ($sst && !(strstr($sst, 'wr_id') || strstr($sst, "wr_datetime")))) {
if (!$sst) {
    if ($board['bo_sort_field']) {
        $sst = $board['bo_sort_field'];
    } else {
        $sst  = "wr_num, wr_parent";
        $sod = "";
    }
} else {
    // 게시물 리스트의 정렬 대상 필드가 아니라면 공백으로 (nasca 님 09.06.16)
    // 리스트에서 다른 필드로 정렬을 하려면 아래의 코드에 해당 필드를 추가하세요.
    // $sst = preg_match("/^(wr_subject|wr_datetime|wr_hit|wr_good|wr_nogood)$/i", $sst) ? $sst : "";
    $sst = preg_match("/^(wr_datetime|wr_hit|wr_good|wr_nogood)$/i", $sst) ? $sst : "";
}

if ($sst) {
    $sql_order = " order by {$sst} {$sod} ";
}

if ($sca || $stx || count($search_tag)) {
    $sql = $wpdb->prepare(" select * from {$write_table} where {$sql_search} {$sql_order} limit %d, %d ", $from_record, $page_rows);

    if( count($search_tag) && $board['bo_use_tag'] ){  //태그 검색이 들어가 있다면...
        $sql = $wpdb->prepare(" select * from $write_table wr left join `{$g5['relation_table']}` t on wr.wr_id = t.object_id where $sql_search $sql_search_add_sql $sql_order limit %d, %d", $from_record, $page_rows);
    }
    $sql = apply_filters('g5_list_search_sql', $sql, $board, $sql_search, $sql_order, $search_tag, $from_record, $page_rows );

} else {
    $sql = $wpdb->prepare(" select * from {$write_table} where bo_table = '%s' ", $bo_table);
    if(!empty($notice_array))
        $sql .= " and wr_id not in (".implode(', ', $notice_array).") ";
    $sql .= " {$sql_order} limit {$from_record}, $page_rows ";
}

$tag_ids = array();

// 페이지의 공지개수가 목록수 보다 작을 때만 실행
if($page_rows > 0) {

    $list = $wpdb->get_results($sql, ARRAY_A);
    $list = apply_filters('g5_get_list_array', $list, $sql);

    $list_num = $total_count - ($page - 1) * $list_page_rows - $notice_count;
    $k = 0;
    foreach( $list as &$row )
    {
        // 검색일 경우 wr_id만 얻었으므로 다시 한행을 얻는다
        /*
        if ($sca || $stx || count($search_tag))
            $row = $wpdb->get_row(" select * from {$write_table} where wr_id = '{$row['wr_id']}' ");
        */

        $row = $this->get_list($row, $board, $board_skin_url, G5_IS_MOBILE ? $board['bo_mobile_subject_len'] : $board['bo_subject_len'], $default_href);

        if (strstr($sfl, 'subject')) {
            $row['subject'] = g5_search_font($stx, $row['subject']);
        }
        $row['is_notice'] = false;
        $row['num'] = $list_num - $k;
        $k++;
    }

    if( isset($this->cache['wr_tag']) && count($this->cache['wr_tag']) ){
        g5_register_cache_tag($bo_table, $this->cache['wr_tag']);
    }
}

if( $notice_count ){ // $notice_count 값이 있다면
    $list = array_merge($notice_list, $list); //공지와 리스트를 합친다.
}

$get_paging_url = add_query_arg( array_merge( (array) $qstr, array('page'=>false)), $default_href );

$write_pages = g5_get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, $get_paging_url );

$list_href = '';
$prev_part_href = '';
$next_part_href = '';
if ($sca || $stx) {
    $list_href = $default_href;

    $patterns = array('#&amp;page=[0-9]*#', '#&amp;spt=[0-9\-]*#');

    //if ($prev_spt >= $min_spt)
    $prev_spt = $spt - $config['cf_search_part'];
    if (isset($min_spt) && $prev_spt >= $min_spt) {
        $qstr1 = preg_replace($patterns, '', $qstr);
        $prev_part_href = './board.php?bo_table='.$bo_table.$qstr1.'&amp;spt='.$prev_spt.'&amp;page=1';
        $write_pages = g5_page_insertbefore($write_pages, '<a href="'.$prev_part_href.'" class="pg_page pg_prev">이전검색</a>');
    }

    $next_spt = $spt + $config['cf_search_part'];
    if ($next_spt < 0) {
        $qstr1 = preg_replace($patterns, '', $qstr);
        $next_part_href = './board.php?bo_table='.$bo_table.$qstr1.'&amp;spt='.$next_spt.'&amp;page=1';
        $write_pages = g5_page_insertafter($write_pages, '<a href="'.$next_part_href.'" class="pg_page pg_end">다음검색</a>');
    }
}


$write_href = '';
if ($member['user_level'] >= $board['bo_write_level']) {
    $write_href = add_query_arg( array( 'action'=>'write' ), $default_href );
}

$nobr_begin = $nobr_end = "";
if (preg_match("/gecko|firefox/i", $_SERVER['HTTP_USER_AGENT'])) {
    $nobr_begin = '<nobr>';
    $nobr_end   = '</nobr>';
}

// RSS 보기 사용에 체크가 되어 있어야 RSS 보기 가능
$rss_href = '';
if ($board['bo_use_rss_view']) {
    $rss_href = add_query_arg( array( 'action'=>'rss' ), $default_href );
}

$is_show_field = array(
'num' => true,
'writer' => true,
'visit' => true,
'wdate' => true
);

if( isset($board['bo_sh_fields']) && !empty($board['bo_sh_fields']) ){   //번호, 작성자, 조회, 작성일 체크가 되어 있다면
    foreach( $is_show_field as $key=>$v ){
        if( strstr( $board['bo_sh_fields'], $key ) ){
            $is_show_field[$key] = false;
        }
    }
}

$stx = g5_get_text(stripslashes($stx));

$fboardlist_action_url = apply_filters('g5_form_url', $default_href , 'list');

$new_move_url = apply_filters('g5_new_move_url', get_permalink() );

$search_form_var = $GLOBALS['wp_query']->query;

include_once($board_skin_path.'/list.skin.php');
?>