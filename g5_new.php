<?php
$page_path = explode('wp-content', $_SERVER['SCRIPT_FILENAME']);
include_once($page_path[0].'wp-load.php');

unset($page_path);

global $gnupress;

add_action('wp_head', array( $gnupress, 'g5_initialize_head') );

$check_arr = array('bo_table', 'sw', 'action');

foreach($check_arr as $v){
    $$v = isset($_REQUEST[$v]) ? sanitize_text_field($_REQUEST[$v]) : '';
}

if( ($sw == 'move' || $sw == 'copy') && !$action ){
    $page_mode = 'move';
} else {
    $page_mode = $action;
    $attr = array( 'page_mode'=>$page_mode );
    $gnupress->g5_member_action($attr, true);
    unset( $attr );
}

unset($check_arr);

add_action('wp_enqueue_scripts', 'g5_new_style_script', '99');

function g5_new_style_script(){
    wp_enqueue_style ( 'g5-board-new-style' , G5_DIR_URL.'view/css/g5_new.css', '', G5_VERSION );
}

add_filter('g5_view_default_href', 'g5_new_default_href');

function g5_new_default_href($href){
    
    $page_id = isset($_REQUEST['board_page_id']) ? $_REQUEST['board_page_id'] : '';
    if( $page_id ){
        $href = get_permalink($page_id);
    }
    return $href;
}
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
<body>
	<div class="g5_new_shortcode">
		<?php echo do_shortcode('['.G5_NAME.' page_mode='.$page_mode.' bo_table='.$bo_table.']');?>
	</div>
    <?php do_action( 'g5_footer_new_'.$page_mode ); ?>
	<?php do_action( 'wp_footer' ); ?>
</body>
</html>