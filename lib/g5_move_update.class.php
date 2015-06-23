<?php
if ( ! class_exists( 'G5_Board_delete' ) ) :

class G5_Move_update extends G5_Board {

    public $count_write = 0;
    public $count_comment = 0;
    public $insert_ids = array();
    public $sw;

	public function __construct( $member, $config, $board, $sw ) {
        $this->member = $member;
        $this->config = $config;
        $this->board = $board;
        $this->sw = $sw;
	}

    public function check_get_list($wr_id, $bo_table, $table){
        global $wpdb;

        $sql = $wpdb->prepare(" select * from `$table` where bo_table = '%s' and wr_parent = %d ", $bo_table, $wr_id );
        $rows = $wpdb->get_results($sql, ARRAY_A);
        return $rows;
    }

	public function check_move_update($write, $table, $move_bo_table){
        
        if( empty($write['wr_id']) ) return;
        $wr_id = $write['wr_id'];

        
        if( $insert_id = $this->update_loop_insert($write, $table, $move_bo_table) ){
            $this->update_reply($wr_id, $table, $insert_id, $move_bo_table);
            $this->count_write++;
        }

        $move_array = array('count_write'=>0, 'count_comment'=>0);
        $move_array['count_write'] = $this->count_write;
        $move_array['count_comment'] = $this->count_comment;
        $move_array['insert_ids'] = $this->insert_ids;

        do_action('g5_check_move_update', $write, $this, $move_array, $move_bo_table);

        return $move_array;
	}

	public function update_reply($wr_id, $table, $parent_id, $move_bo_table){
        global $wpdb, $gnupress;

        $member = $this->member;
        $board = $this->board;

		$rows = $this->check_get_list($wr_id, $board['bo_table'], $table);
        
        if( !$rows ) return false;

		foreach( $rows as $row ){
            
            if( $insert_id = $this->update_loop_insert($row, $table, $move_bo_table) ){
                
                $this->update_reply($row['wr_id'], $table, $insert_id, $move_bo_table);
                //답변글이라면 wr_parent 업데이트
                $sql = $wpdb->prepare("update `$table` set wr_parent = %d where wr_id = %d ", $parent_id, $insert_id);
                if( $wpdb->query($sql) ){
                    $this->count_write++;
                }
            }
        }

	}

    public function update_loop_insert($row, $move_write_table, $move_bo_table){
        global $wpdb, $gnupress;

        $member = $this->member;
        $config = $this->config;
        $board = $this->board;
        $sw = $this->sw;

        $g5 = $gnupress->g5;
        $bo_table = $this->board['bo_table'];

        $src_dir = $dst_dir = '';

        if( $g5_data_path = g5_get_upload_path() ){
            $src_dir = $g5_data_path.'/file/'.$bo_table; // 원본 디렉토리
            $dst_dir = $g5_data_path.'/file/'.$move_bo_table; // 복사본 디렉토리
        }

        static $kk = 0;

        $nick = g5_cut_str($member['user_display_name'], $config['cf_cut_name']);
        if ( $config['cf_use_copy_log'] ) {
            if(strstr($row['wr_option'], 'html')) {
                $log_tag1 = '<div class="content_'.$sw.'">';
                $log_tag2 = '</div>';
            } else {
                $log_tag1 = "\n";
                $log_tag2 = '';
            }

            $row['wr_content'] .= "\n".$log_tag1.'[이 게시물은 '.$nick.'님에 의해 '.G5_TIME_YMDHIS.' '.$board['bo_subject'].'에서 '.($sw == 'copy' ? '복사' : '이동').' 됨]'.$log_tag2;
        }

        // 게시글 추천, 비추천수
        $wr_good = $wr_nogood = 0;

        if ($sw == 'move') {
            $wr_good = $row['wr_good'];
            $wr_nogood = $row['wr_nogood'];
        }

        $next_wr_num = g5_get_next_num($move_write_table, $move_bo_table);

        $g5_data = array(
            'wr_num'=>$next_wr_num,
            'bo_table'=>$move_bo_table,
            'wr_comment'=>$row['wr_comment'],
            'ca_name'=>$row['ca_name'],
            'wr_option'=>$row['wr_option'],
            'wr_subject'=>$row['wr_subject'],
            'wr_content'=>$row['wr_content'],
            'wr_link1'=>$row['wr_link1'],
            'wr_link2'=>$row['wr_link2'],
            'wr_link1_hit'=>$row['wr_link1_hit'],
            'wr_link2_hit'=>$row['wr_link2_hit'],
            'wr_hit' => $row['wr_hit'],
            'wr_good' => $wr_good,
            'wr_nogood' => $wr_nogood,
            'user_id' => $row['user_id'],
            'user_pass' => $row['user_pass'],
            'user_display_name' => $row['user_display_name'],
            'user_email' => $row['user_email'],
            'wr_datetime' => $row['wr_datetime'],
            'wr_last' => $row['wr_last'],
            'wr_ip' => $row['wr_ip'],
            'wr_img' => $row['wr_img'],
            'wr_file' => $row['wr_file'],
            'wr_tag' => $row['wr_tag'],
            'wr_page_id' => g5_page_get_by($move_bo_table, 'page_id')
        );
        
        $formats = array(
                '%d',
                '%s',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%d',
                '%d',
                '%d',
                '%d',   //wr_good
                '%d',
                '%s',
                '%s',   //user_pass
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
                '%d'    //wr_page_id
            );
        $result = $wpdb->insert($move_write_table, $g5_data, $formats);

        if( $insert_id = $wpdb->insert_id ){

            //게시물에 태그 데이터가 있다면 태그 데이터 복사
            if( $row['wr_tag'] ){
                g5_tagdata_copy($insert_id, $row['wr_id'], $move_bo_table, $bo_table);
            }
            //코멘트 데이터 복사
            g5_comment_copy($insert_id, $row['wr_id'], $move_bo_table, $bo_table);

            //파일 이동 처리
            if( $file_meta_data = get_metadata(G5_META_TYPE, $row['wr_id'], G5_FILE_META_KEY, true ) ){

                if( $src_dir && $dst_dir ){
                    foreach((array) $file_meta_data as $key=>$f){
                        if( empty($f) ) continue;

                        if ( isset($f['bf_file']) && !empty($f['bf_file']) )
                        {
                            // 원본파일을 복사하고 퍼미션을 변경
                            @copy($src_dir.'/'.$f['bf_file'], $dst_dir.'/'.$f['bf_file']);
                            @chmod($dst_dir/$f['bf_file'], G5_FILE_PERMISSION);
                        }
                    }
                }
            }
            //메타 데이터 복사
            g5_writemeta_copy($insert_id, $row['wr_id']);

            if ($sw == 'move')
            {
                // 스크랩 이동
                $result = $wpdb->query(
                    $wpdb->prepare(" update {$g5['scrap_table']} set bo_table = '%s', wr_id = %d where bo_table = '%s' and wr_id = '%d' ", $move_bo_table, $insert_id, $bo_table, (int) $row['wr_id'])
                    );

                // 추천데이터 이동
                $result = $wpdb->query(
                    $wpdb->prepare(" update {$g5['board_good_table']} set bo_table = '%s', wr_id = %d where bo_table = '%s' and wr_id = %d ", $move_bo_table, $insert_id, $bo_table, (int) $row['wr_id'])
                );
            }

        }
        $this->insert_ids[$kk]['wr_id'] = $row['wr_id'];

        $kk++;

        return $insert_id;
    }

    //원글 삭제
    public function sql_delete($wr_id, $table){
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(" delete from `$table` where wr_id = %d ", (int) $wr_id)
            );
    }

    public function sql_comment_delete($wr_id){

    }
}

endif
?>