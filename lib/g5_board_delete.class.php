<?php
if ( ! class_exists( 'G5_Board_delete' ) ) :

class G5_Board_delete extends G5_Board {

    public $count_write = 0;
    public $count_comment = 0;

    public function check_get_list($wr_id, $bo_table, $table){
        global $wpdb;

        $sql = $wpdb->prepare("select * from `$table` where wr_parent = %d order by wr_num ", $wr_id);
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
            global $wpdb, $gnupress;

            $g5 = $gnupress->g5;
            $config = $gnupress->config;
            // 원글 포인트 삭제
            
            if (!g5_delete_point($row['user_id'], $board['bo_table'], $row['wr_id'], '쓰기'))
                g5_insert_point($row['user_id'], $board['bo_write_point'] * (-1), "{$board['bo_subject']} {$row['wr_id']} 글삭제");

            // 업로드된 파일이 있다면 파일삭제
            $file_meta_data = get_metadata(G5_META_TYPE, $row['wr_id'], G5_FILE_META_KEY , true);

            // metadata에 있으면 삭제
            if( count($file_meta_data) && g5_get_upload_path() ){
                foreach((array) $file_meta_data as $row2 ){
                    if( !isset($row2['bf_file']) ) continue;
                    @unlink(g5_get_upload_path().'/file/'.$board['bo_table'].'/'.$row2['bf_file']);
                    // 썸네일삭제
                    if(preg_match("/\.({$config['cf_image_extension']})$/i", $row2['bf_file'])) {
                        g5_delete_board_thumbnail($board['bo_table'], $row2['bf_file']);
                    }
                }
            }

            // 에디터 썸네일 삭제
            g5_delete_editor_thumbnail($row['wr_content']);

            //메타데이터 삭제
            delete_metadata(G5_META_TYPE, $row['wr_id'], G5_FILE_META_KEY);

            // 스크랩 삭제
            $wpdb->query( 
                $wpdb->prepare("delete from {$g5['scrap_table']} where bo_table = '%s' and wr_id = %d ", $board['bo_table'], $row['wr_id'])
                );

            //태그 기록 삭제
            g5_delete_object_term_relationships($row['wr_id'], g5_get_taxonomy($board['bo_table']));

            // 코멘트 및 코멘트 포인트 삭제
            $this->sql_comment_delete($row['wr_id']);

            // 실행

            do_action('g5_etc_check_delete', $row, $board, $this);
    }

    //글 삭제
    public function sql_delete($wr_id, $table){
        global $wpdb;
        $sql_str = $wpdb->prepare(" delete from `$table` where wr_id = %d ", (int) $wr_id);
        $sql = apply_filters('g5_reply_delete_sql', $sql_str, $wr_id, $table, $this );

        if( $sql ){
            $wpdb->query($sql);
            return true;
        }
    }

    public function sql_comment_delete($wr_id){
        global $wpdb, $gnupress;

        $g5 = $gnupress->g5;
        
        if( !$wr_id ) return;

        $result = $wpdb->query(
                        $wpdb->prepare(" delete from `{$g5['comment_table']}` where wr_id = %d ", (int) $wr_id)
                    );
    }
}

endif;
?>