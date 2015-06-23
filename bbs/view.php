<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 게시판에서 두단어 이상 검색 후 검색된 게시물에 코멘트를 남기면 나오던 오류 수정
$sop = strtolower($sop);
if ($sop != 'and' && $sop != 'or')
    $sop = 'and';

if(file_exists($board_skin_path.'/view.head.skin.php')){
    include_once($board_skin_path.'/view.head.skin.php');
}

$sql_search = "";

// 검색이면
if ($sca || $stx || count($search_tag)) {
    // where 문을 얻음
    $sql_search = g5_get_sql_search($sca, $sfl, $stx, $sop);
    $search_href = add_query_arg(array_merge((array)$qstr, array('page'=>$page)) , $default_href);
    $list_href = $default_href;

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
    }
} else {
    $search_href = '';
    $list_href = add_query_arg( array('page'=>$page) , $default_href );
}

if (!$board['bo_use_list_view']) {
    if ($sql_search)
        $sql_search = " and " . $sql_search;

    $view_search_field = 'wr_id, wr_subject';
    // 윗글을 얻음 ( 이전글 )
    if ($sca || $stx || count($search_tag)) { //검색문 이면
        if( count($search_tag) && $board['bo_use_tag'] ){ //태그가 들어가 있다면 조인문
            $sub_sql = $wpdb->prepare("select wr_id from `$write_table` wr left join {$g5['relation_table']} t on wr.wr_id = t.object_id where wr.bo_table = '%s' and ( (wr.wr_num = '{$write['wr_num']}' and wr.wr_parent < '{$write['wr_parent']}') or wr.wr_num < '{$write['wr_num']}' ) $sql_search order by wr_num desc, wr_parent desc limit 1 ", $bo_table);
        } else {
            $sub_sql = $wpdb->prepare(" select wr_id from `$write_table` where bo_table = '%s' and ( (wr_num = '{$write['wr_num']}' and wr_parent < '{$write['wr_parent']}') or wr_num < '{$write['wr_num']}' ) $sql_search order by wr_num desc, wr_parent desc limit 1 ", $bo_table);
        }
        $sql = " select $view_search_field from `$write_table` where wr_id = ( $sub_sql ) ";
    } else { //검색 조건이 없을때
        $sql = $wpdb->prepare("select $view_search_field from $write_table where bo_table = '%s' and ( (wr_num = '{$write['wr_num']}' and wr_parent < '{$write['wr_parent']}') or wr_num < '{$write['wr_num']}' ) $sql_search order by wr_num desc, wr_parent desc limit 1", $bo_table);
    }
    
    $sql = apply_filters('g5_prev_get_sql', $sql, $board, $sca, $stx, $search_tag, $this );
    $prev = $wpdb->get_row($sql, ARRAY_A );

    // 아래글을 얻음 ( 다음글 )
    if ($sca || $stx || count($search_tag)) { //검색문 이면
        if( count($search_tag) && $board['bo_use_tag'] ){ //태그가 들어가 있다면 조인문
            $sub_sql = $wpdb->prepare("select wr_id from `$write_table` wr left join {$g5['relation_table']} t on wr.wr_id = t.object_id where wr.bo_table = '%s' and ( (wr.wr_num = '{$write['wr_num']}' and wr.wr_parent > '{$write['wr_parent']}') or wr.wr_num > '{$write['wr_num']}' ) $sql_search order by wr_num, wr_parent limit 1 ", $bo_table);
        } else {
            $sub_sql = $wpdb->prepare(" select wr_id from `$write_table` where bo_table = '%s' and ( (wr_num = '{$write['wr_num']}' and wr_parent > '{$write['wr_parent']}') or wr_num > '{$write['wr_num']}' ) $sql_search order by wr_num, wr_parent limit 1 ", $bo_table);
        }
        $sql = " select $view_search_field from `$write_table` where wr_id = ( $sub_sql ) ";
    } else { //검색 조건이 없을때
        $sql = $wpdb->prepare("select $view_search_field from $write_table where bo_table = '%s' and ( (wr_num = '{$write['wr_num']}' and wr_parent > '{$write['wr_parent']}') or wr_num > '{$write['wr_num']}' ) $sql_search order by wr_num, wr_parent limit 1", $bo_table);
    }

    $sql = apply_filters('g5_next_get_sql', $sql, $board, $sca, $stx, $search_tag, $this );
    $next = $wpdb->get_row($sql, ARRAY_A );
}



// 이전글 링크
$prev_href = '';
if (isset($prev['wr_id']) && $prev['wr_id']) {
    $prev_wr_subject = g5_get_text(g5_cut_str($prev['wr_subject'], 255));
    $prev_href = add_query_arg( array_merge((array) $qstr, array('wr_id'=> $prev['wr_id'])), $default_href );
}

// 다음글 링크
$next_href = '';
if (isset($next['wr_id']) && $next['wr_id']) {
    $next_wr_subject = g5_get_text(g5_cut_str($next['wr_subject'], 255));
    $next_href = add_query_arg( array_merge((array) $qstr, array('wr_id'=> $next['wr_id'])), $default_href );
}

// 쓰기 링크
$write_href = '';
if ($member['user_level'] >= $board['bo_write_level']){
    $write_href = add_query_arg( array('action'=>'write') , $default_href );
}

// 답변 링크
$reply_href = '';
if ($member['user_level'] >= $board['bo_reply_level'] && !$write['wr_parent'] ){
    $reply_href = add_query_arg( array_merge((array) $qstr, array('action'=>'write', 'w' => 'r', 'wr_id'=> $wr_id)) , $default_href );
}

// 수정, 삭제 링크
$update_href = $delete_href = '';
// 로그인중이고 자신의 글이라면 또는 관리자라면 비밀번호를 묻지 않고 바로 수정, 삭제 가능
if (($member['user_id'] && ($member['user_id'] == $write['user_id'])) || $is_admin) {

    $update_href = apply_filters('g5_view_update_href' , add_query_arg( array_merge((array) $qstr, array('action'=>'write', 'w'=>'u', 'wr_id'=>$wr_id, 'page'=> $page))) );
    
    $delete_href = apply_filters('g5_view_delete_href', add_query_arg( array_merge((array) $qstr, array('action'=>'delete', 'bo_table' => $bo_table, 'wr_id'=>$wr_id, 'page'=> $page, 'nonce' => wp_create_nonce('g5_board_delete')))) );

}
else if (!$write['user_id']) { // 회원이 쓴 글이 아니라면

    $update_href = apply_filters('g5_password_update_href', add_query_arg( array_merge((array) $qstr, array('action'=>'password', 'w'=>'u', 'wr_id'=>$wr_id, 'page'=> $page))) );

    $delete_href = apply_filters('g5_password_delete_href', add_query_arg( array_merge((array) $qstr, array('action'=>'password', 'w'=>'d', 'bo_table' => $bo_table, 'wr_id'=>$wr_id, 'page'=> $page))) );
}

//새창 열기 url
$new_open_url = get_permalink();

// 최고, 그룹관리자라면 글 복사, 이동 가능
$copy_href = $move_href = '';
if (!$write['wr_parent'] && ($is_admin == 'super')) {
    $copy_href = apply_filters('g5_copy_href', add_query_arg( array_merge((array) $qstr, array('action'=>'move', 'bo_table'=>$bo_table, 'sw'=>'copy', 'wr_id'=>$wr_id, 'page'=> $page)), $new_open_url) );
    $move_href = apply_filters('g5_move_href', add_query_arg( array_merge((array) $qstr, array('action'=>'move', 'bo_table'=>$bo_table, 'sw'=>'move', 'wr_id'=>$wr_id, 'page'=> $page)), $new_open_url) );
}

$scrap_href = '';
$good_href = '';
$nogood_href = '';
if ($is_member) {
    // 스크랩 링크

    $scrap_href = add_query_arg( array('action'=>'scrap_popin', 'bo_table'=>$bo_table, 'wr_id'=>$wr_id, 'ms_url'=>urlencode(home_url($current_url))), $gnupress->new_url);

    // 추천 링크
    if ($board['bo_use_good'])
        $good_href = add_query_arg( array('action'=>'good', 'good'=>'good', 'bo_table'=>$bo_table, 'wr_id'=>$wr_id) , $default_href);

    // 비추천 링크
    if ($board['bo_use_nogood'])
        $nogood_href = add_query_arg( array('action'=>'good', 'good'=>'nogood', 'bo_table'=>$bo_table, 'wr_id'=>$wr_id) , $default_href);
}

$view = $this->get_view($write, $board, $board_skin_path, $default_href);

if (strstr($sfl, 'subject'))
    $view['subject'] = g5_search_font($stx, $view['subject']);

$html = 0;
if (strstr($view['wr_option'], 'html1'))
    $html = 1;
else if (strstr($view['wr_option'], 'html2'))
    $html = 2;

if($html && strstr($view['wr_option'], 'wp_html') ){    //워드프레스 에디터를 사용했다면
    add_filter('g5_view_content', 'g5_hook_conv_wp' , 1 , 2 );
}

$view['content'] = apply_filters('g5_view_content', g5_conv_content($view['wr_content'], $html) , $view['wr_content'] );

if (strstr($sfl, 'content'))
    $view['content'] = g5_search_font($stx, $view['content']);

wp_cache_set( G5_NAME.'_view_'.$wr_id, $view );

//$view['rich_content'] = preg_replace("/{이미지\:([0-9]+)[:]?([^}]*)}/ie", "view_image(\$view, '\\1', '\\2')", $view['content']);
function g5_conv_rich_content($matches)
{
    global $gnupress;

    $cache_view = wp_cache_get( G5_NAME.'_view_'.$gnupress->wr_id );

    if( false === $cache_view ){
        return '';
    } else {
        return g5_view_image($cache_view, $matches[1], $matches[2]);
    }
}
$view['rich_content'] = preg_replace_callback("/{이미지\:([0-9]+)[:]?([^}]*)}/i", "g5_conv_rich_content", $view['content']);

$is_signature = false;
$signature = '';
if ($board['bo_use_signature'] && $view['user_id']) {
    $is_signature = true;
    $mb = g5_get_member($view['user_id']);
    $signature = $mb['mb_signature'];

    $signature = g5_conv_content($signature, 1);
}

$tag_array = array();

if( $board['bo_use_tag'] && isset($view['wr_tag']) && !empty($view['wr_tag']) ){   //태그가 존재한다면
    $wr_tags = explode(",", $view['wr_tag']);
    $i = 0;
    foreach( $wr_tags as $term_id ){
        if( empty($term_id) ) continue;
        $tag_array[$i] = g5_get_tag_info($term_id, $bo_table);
        $tag_array[$i]['href'] = add_query_arg( array_merge( (array) $qstr, array('tag'=>$tag_array[$i]['slug'])) , $default_href);
        $i++;
    }
}

include_once($board_skin_path.'/view.skin.php');

if(file_exists($board_skin_path.'/view.tail.skin.php')){
    include_once($board_skin_path.'/view.tail.skin.php');
}
?>