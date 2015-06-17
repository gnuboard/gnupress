<?php
class G5_Walker extends Walker {
    var $db_fields = array( 'parent' => 'wr_parent', 'id' => 'wr_id' );
    var $cnt = 0;

    function start_lvl(&$output, $depth = 0, $args = array()) {
        $tag = apply_filters('g5_walker_start_lvl_tag', 'ul');
        $class = apply_filters('g5_walker_start_lvl_class', 'children', $depth);

        $indent = str_repeat("\t", $depth);
        $output .= PHP_EOL."$indent<$tag class=\"$class depth_".$depth."\">".PHP_EOL;
    }
 
    function end_lvl(&$output, $depth = 0, $args = array()) {
        $tag = apply_filters('g5_walker_end_lvl_tag', 'ul');

        $indent = str_repeat("\t", $depth);
        $output .= PHP_EOL."$indent</$tag>".PHP_EOL;
    }
 
    function start_el( &$output, $item, $depth = 0, $args = array(), $current_object_id = 0 ) {
        if ( $depth ) {
            $indent = str_repeat( "\t", $depth );
        } else {
            $indent = '';
        }
        
        $item->depth = $depth;

        if( $depth % 2 == 1 ){
            $class = 'odd';
        } else {
            $class = 'even';
        }
        $output .= PHP_EOL.$indent.'<li class="'.$class.' depth_'.$depth.' ">' . $this->custom_print($item, $args);
    }
 
    function end_el(&$output, $item, $depth=0, $args=array()) {
        $indent = str_repeat("\t", $depth);
        $output .= "$indent</li>".PHP_EOL;
    }

    function custom_print($item, $args=array()){
        static $i = 0;
        $is_checkbox = $args['is_checkbox'];
        if( !$item->is_notice ){
            $item->num = $args['list_num'] - $this->cnt;
            $this->cnt++;
        }
        $i++;
        ob_start();
        include($args['board_skin_path'].'/list_walker.skin.php');
        return ob_get_clean();
    }
}
?>