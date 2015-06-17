<?php
if ( ! class_exists( 'G5_Board_delete' ) ) :

class G5_Board_delete extends G5_Board {

    public $count_write = 0;
    public $count_comment = 0;

    public function check_get_list($wr_id, $bo_table, $table){
        global $wpdb;

        $sql = " select * from `$table` where wr_parent = '".esc_sql($wr_id)."' order by wr_num ";
        $rows = $wpdb->get_results($sql);
        return $rows;
    }

	public function check_delete($write, $board, $g5){
        
        if( !isset($write['wr_id']) ) return;
        $this->g5 = $g5;
        $this->board = $board;

        $table = $g5['write_table'];

        do_action('g5_check_delete', $write, $board, $table, $this);

        //글 리스트를 전부 삭제한다.

        $this->delete_reply($write['wr_id'], $board, $table);
        
        $count_array = array('count_write'=>0, 'count_comment'=>0);
        $count_array['count_write'] = $this->count_write;
        $count_array['count_comment'] = $this->count_comment;
        return $count_array;
	}

	public function delete_reply($wr_id, $board, $table){
		$rows = $this->check_get_list($wr_id, $board['bo_table'], $table);

        if( !$rows ) return false;

		foreach( $rows as $row ){
            
            $func_action = apply_filters( 'g5_delete_reply_filter', array( $this, 'etc_check' ) );

            if( !$func_action ){
                call_user_func_array( $func_action , array( $row , $board ) );
            }

            $this->delete_reply($row->wr_id, $board, $table);
            
            if( $this->sql_delete($row->wr_id, $table) ){
                $this->count_write++;
            }

        }
        return true;
	}

    public function etc_check($row, $board){

            // 원글 포인트 삭제
            
            
            //if (!g5_delete_point($row['user_id'], $board['bo_table'], $row['wr_id'], '쓰기'))
            //    g5_insert_point($row['user_id'], $board['bo_write_point'] * (-1), "{$board['bo_subject']} {$row['wr_id']} 글삭제");

            // 업로드된 파일이 있다면 파일삭제
            
            /*
            $sql2 = " select * from {$g5['board_file_table']} where bo_table = '$bo_table' and wr_id = '{$row['wr_id']}' ";
            $result2 = sql_query($sql2);
            while ($row2 = g5_sql_fetch_array($result2)) {
                @unlink(G5_DATA_PATH.'/file/'.$bo_table.'/'.$row2['bf_file']);
                // 썸네일삭제
                if(preg_match("/\.({$config['cf_image_extension']})$/i", $row2['bf_file'])) {
                    delete_board_thumbnail($bo_table, $row2['bf_file']);
                }
            }
            */

            // 에디터 썸네일 삭제
            //delete_editor_thumbnail($row['wr_content']);

            // 파일테이블 행 삭제
            //$wpdb->query(" delete from {$g5['board_file_table']} where bo_table = '$bo_table' and wr_id = '{$row['wr_id']}' ");

            // 코멘트 포인트 삭제
            // 실행

            do_action('g5_etc_check_delete', $row, $board, $this);
    }

    //글 삭제
    public function sql_delete($wr_id, $table){
        global $wpdb;
        $sql = apply_filters('g5_reply_delete_sql', " delete from `$table` where wr_id = '$wr_id' " , $wr_id, $table, $this );

        if( $sql ){
            $wpdb->query(" delete from `$table` where wr_id = '$wr_id' ");
        }
    }

    public function sql_comment_delete($wr_id){
        global $wpdb;
        if( $result = $wpdb->query(" delete from `$write_table` where wr_id = '$wr_id' ") ){
            //태그 기록 삭제
            g5_delete_object_term_relationships($write['wr_id'] , g5_get_taxonomy($bo_table));
        }
    }
}

endif;
?>