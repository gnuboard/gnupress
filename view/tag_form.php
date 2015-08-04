<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

global $current_screen;

$taxonomy = $gnupress->taxonomy;
$tax = get_taxonomy( $taxonomy );

$g5 = G5_var::getInstance()->get_options();

$bo_table = isset($_REQUEST['bo_table']) ? sanitize_text_field($_REQUEST['bo_table']) : '';
$page = isset($_REQUEST['page']) ? sanitize_text_field($_REQUEST['page']) : '';

$sql = "select * from {$g5['board_table']} where bo_use_tag = 1 ";
$board_list = $wpdb->get_results($sql, ARRAY_A);
/*
if( ! $bo_table && isset($board_list[0]) ){
    $bo_table = $board_list[0]['bo_table'];
}
*/
$location_url = add_query_arg( array('bo_table'=>false, 'taxonomy'=>false), admin_url('admin.php?page=g5_tag_form') );

$post_type = '';

if ( ! $tax )
	wp_die( __( 'Invalid taxonomy' ) );

if ( ! current_user_can( $tax->cap->manage_terms ) )
	wp_die( __( 'Cheatin&#8217; uh?' ) );

$wp_list_table = $gnupress->term_list;

$pagenum = $wp_list_table->get_pagenum();

$title = $tax->labels->name;

$current_url = admin_url( g5_get_current_page()."?page=".$page."&bo_table=".$bo_table."&taxonomy=".$taxonomy );

add_screen_option( 'per_page', array( 'label' => $title, 'default' => 20, 'option' => 'edit_' . $tax->name . '_per_page' ) );

switch ( $wp_list_table->current_action() ) {

case 'g5_add-tag':

	check_admin_referer( 'add-tag', '_wpnonce_add-tag' );

	if ( !current_user_can( $tax->cap->edit_terms ) )
		wp_die( __( 'Cheatin&#8217; uh?' ) );

	$ret = g5_insert_term( sanitize_text_field($_POST['tag-name']), $taxonomy, $_POST );
	$location = $current_url;

	if ( $referer = wp_get_original_referer() ) {
		$location = $referer;
	}

	if ( $ret && !is_wp_error( $ret ) )
		$location = add_query_arg( 'message', 1, $location );
	else
		$location = add_query_arg( 'message', 4, $location );
	g5_goto_url( $location );
	exit;

case 'delete':
	$location = $current_url;

	if ( $referer = wp_get_referer() ) {
		 $location = $referer;
	}

	if ( !isset( $_REQUEST['tag_ID'] ) ) {
		g5_goto_url( $location );
		exit;
	}

	$tag_ID = (int) $_REQUEST['tag_ID'];
	check_admin_referer( 'g5_delete-tag_' . $tag_ID );

	if ( !current_user_can( $tax->cap->delete_terms ) )
		wp_die( __( 'Cheatin&#8217; uh?' ) );

	g5_delete_term( $tag_ID, $taxonomy );

	$location = add_query_arg( 'message', 2, $location );
	g5_goto_url( $location );
    exit;
	break;

case 'bulk-delete':
	check_admin_referer( 'bulk-tags' );

	if ( !current_user_can( $tax->cap->delete_terms ) )
		wp_die( __( 'Cheatin&#8217; uh?' ) );

	$tags = (array) $_REQUEST['delete_tags'];
	foreach ( $tags as $tag_ID ) {
		g5_delete_term( $tag_ID, $taxonomy );
	}

	$location = $current_url;

	if ( $referer = wp_get_referer() ) {
		$location = $referer;
	}

	$location = add_query_arg( 'message', 6, $location );
	g5_goto_url( $location );
	exit;

case 'edit':
	$title = $tax->labels->edit_item;

	$tag_ID = (int) $_REQUEST['tag_ID'];

	$tag = g5_get_term( $tag_ID, $taxonomy, OBJECT, 'edit' );
	if ( ! $tag )
		wp_die( __( 'You attempted to edit an item that doesn&#8217;t exist. Perhaps it was deleted?' ) );

	include( G5_DIR_PATH . 'view/edit_tag_form.php' );

break;

case 'editedtag':
	$tag_ID = (int) $_POST['tag_ID'];
	check_admin_referer( 'update-tag_' . $tag_ID );

	if ( !current_user_can( $tax->cap->edit_terms ) )
		wp_die( __( 'Cheatin&#8217; uh?' ) );

	$tag = g5_get_term( $tag_ID, $taxonomy );
	if ( ! $tag )
		wp_die( __( 'You attempted to edit an item that doesn&#8217;t exist. Perhaps it was deleted?' ) );

	$ret = g5_update_term( $tag_ID, $taxonomy, $_POST );

	$location = $current_url;

	if ( $referer = wp_get_original_referer() ) {
			$location = $referer;
	}

	if ( $ret && !is_wp_error( $ret ) )
		$location = add_query_arg( 'message', 3, $location );
	else
		$location = add_query_arg( 'message', 5, $location );

	g5_goto_url( $location );
	exit;
    break;
default:
if ( ! empty($_REQUEST['_wp_http_referer']) ) {
	$location = remove_query_arg( array('_wp_http_referer', '_wpnonce'), wp_unslash($_SERVER['REQUEST_URI']) );

	if ( ! empty( $_REQUEST['paged'] ) )
		$location = add_query_arg( 'paged', (int) $_REQUEST['paged'] );

	g5_goto_url( $location );
	exit;
}

$wp_list_table->prepare_items();
$total_pages = $wp_list_table->get_pagination_arg( 'total_pages' );

if ( $pagenum > $total_pages && $total_pages > 0 ) {
	wp_redirect( add_query_arg( 'paged', $total_pages ) );
	exit;
}

wp_enqueue_script('admin-tags');
if ( current_user_can($tax->cap->edit_terms) )
	wp_enqueue_script('inline-edit-tax');

if ( 'category' == $taxonomy || 'link_category' == $taxonomy || 'post_tag' == $taxonomy  ) {
	$help ='';
	if ( 'category' == $taxonomy )
		$help = '<p>' . sprintf(__( 'You can use categories to define sections of your site and group related posts. The default category is &#8220;Uncategorized&#8221; until you change it in your <a href="%s">writing settings</a>.' ) , 'options-writing.php' ) . '</p>';
	elseif ( 'link_category' == $taxonomy )
		$help = '<p>' . __( 'You can create groups of links by using Link Categories. Link Category names must be unique and Link Categories are separate from the categories you use for posts.' ) . '</p>';
	else
		$help = '<p>' . __( 'You can assign keywords to your posts using <strong>tags</strong>. Unlike categories, tags have no hierarchy, meaning there&#8217;s no relationship from one tag to another.' ) . '</p>';

	if ( 'link_category' == $taxonomy )
		$help .= '<p>' . __( 'You can delete Link Categories in the Bulk Action pull-down, but that action does not delete the links within the category. Instead, it moves them to the default Link Category.' ) . '</p>';
	else
		$help .='<p>' . __( 'What&#8217;s the difference between categories and tags? Normally, tags are ad-hoc keywords that identify important information in your post (names, subjects, etc) that may or may not recur in other posts, while categories are pre-determined sections. If you think of your site like a book, the categories are like the Table of Contents and the tags are like the terms in the index.' ) . '</p>';

	get_current_screen()->add_help_tab( array(
		'id'      => 'overview',
		'title'   => __('Overview'),
		'content' => $help,
	) );

	if ( 'category' == $taxonomy || 'post_tag' == $taxonomy ) {
		if ( 'category' == $taxonomy )
			$help = '<p>' . __( 'When adding a new category on this screen, you&#8217;ll fill in the following fields:' ) . '</p>';
		else
			$help = '<p>' . __( 'When adding a new tag on this screen, you&#8217;ll fill in the following fields:' ) . '</p>';

		$help .= '<ul>' .
		'<li>' . __( '<strong>Name</strong> - The name is how it appears on your site.' ) . '</li>';

		if ( ! global_terms_enabled() )
			$help .= '<li>' . __( '<strong>Slug</strong> - The &#8220;slug&#8221; is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.' ) . '</li>';

		if ( 'category' == $taxonomy )
			$help .= '<li>' . __( '<strong>Parent</strong> - Categories, unlike tags, can have a hierarchy. You might have a Jazz category, and under that have child categories for Bebop and Big Band. Totally optional. To create a subcategory, just choose another category from the Parent dropdown.' ) . '</li>';

		$help .= '<li>' . __( '<strong>Description</strong> - The description is not prominent by default; however, some themes may display it.' ) . '</li>' .
		'</ul>' .
		'<p>' . __( 'You can change the display of this screen using the Screen Options tab to set how many items are displayed per screen and to display/hide columns in the table.' ) . '</p>';

		get_current_screen()->add_help_tab( array(
			'id'      => 'adding-terms',
			'title'   => 'category' == $taxonomy ? __( 'Adding Categories' ) : __( 'Adding Tags' ),
			'content' => $help,
		) );
	}

	$help = '<p><strong>' . __( 'For more information:' ) . '</strong></p>';

	if ( 'category' == $taxonomy )
		$help .= '<p>' . __( '<a href="http://codex.wordpress.org/Posts_Categories_Screen" target="_blank">Documentation on Categories</a>' ) . '</p>';
	elseif ( 'link_category' == $taxonomy )
		$help .= '<p>' . __( '<a href="http://codex.wordpress.org/Links_Link_Categories_Screen" target="_blank">Documentation on Link Categories</a>' ) . '</p>';
	else
		$help .= '<p>' . __( '<a href="http://codex.wordpress.org/Posts_Tags_Screen" target="_blank">Documentation on Tags</a>' ) . '</p>';

	$help .= '<p>' . __('<a href="https://wordpress.org/support/" target="_blank">Support Forums</a>') . '</p>';

	get_current_screen()->set_help_sidebar( $help );

	unset( $help );
}

if ( !current_user_can($tax->cap->edit_terms) )
	wp_die( __('You are not allowed to edit this item.') );

$messages = array();
$messages['_item'] = array(
	0 => '', // Unused. Messages start at index 1.
	1 => __( 'Item added.' ),
	2 => __( 'Item deleted.' ),
	3 => __( 'Item updated.' ),
	4 => __( 'Item not added.' ),
	5 => __( 'Item not updated.' ),
	6 => __( 'Items deleted.' )
);
$messages['category'] = array(
	0 => '', // Unused. Messages start at index 1.
	1 => __( 'Category added.' ),
	2 => __( 'Category deleted.' ),
	3 => __( 'Category updated.' ),
	4 => __( 'Category not added.' ),
	5 => __( 'Category not updated.' ),
	6 => __( 'Categories deleted.' )
);
$messages['post_tag'] = array(
	0 => '', // Unused. Messages start at index 1.
	1 => __( 'Tag added.' ),
	2 => __( 'Tag deleted.' ),
	3 => __( 'Tag updated.' ),
	4 => __( 'Tag not added.' ),
	5 => __( 'Tag not updated.' ),
	6 => __( 'Tags deleted.' )
);

/**
 * Filter the messages displayed when a tag is updated.
 *
 * @since 3.7.0
 *
 * @param array $messages The messages to be displayed.
 */
$messages = apply_filters( 'term_updated_messages', $messages );

$message = false;
if ( isset( $_REQUEST['message'] ) && ( $msg = (int) $_REQUEST['message'] ) ) {
	if ( isset( $messages[ $taxonomy ][ $msg ] ) )
		$message = $messages[ $taxonomy ][ $msg ];
	elseif ( ! isset( $messages[ $taxonomy ] ) && isset( $messages['_item'][ $msg ] ) )
		$message = $messages['_item'][ $msg ];
}

?>

<div class="wrap nosubsub">
<h2><?php echo esc_html( $title );
if ( !empty($_REQUEST['s']) )
	printf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;') . '</span>', esc_html( wp_unslash($_REQUEST['s']) ) ); ?>
</h2>

<?php if ( $message ) : ?>
<div id="message" class="updated"><p><?php echo $message; ?></p></div>
<?php //$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
endif; ?>
<div id="ajax-response"></div>

<div>

<select id="select_bo_table">
<option value=""><?php _e('Please select', 'gnupress'); ?></option>
<?php 
foreach( $board_list as $v ){ 
    $selected = '';
    if( $bo_table == $v['bo_table'] ){
        $selected = "selected='selected'";
    }
?>
<option value="<?php echo $v['bo_table']?>" <?php echo $selected?> ><?php echo $v['bo_subject']?></option>
<?php } ?>
</select>

</div>

<form class="search-form" action="" method="get">
<input type="hidden" name="taxonomy" value="<?php echo esc_attr($taxonomy); ?>" />
<input type="hidden" name="post_type" value="<?php echo esc_attr($post_type); ?>" />

<?php $wp_list_table->search_box( $tax->labels->search_items, 'tag' ); ?>

</form>
<br class="clear" />

<div id="col-container">

<div id="col-right">
<div class="col-wrap">
<form id="posts-filter" action="" method="post">
<input type="hidden" name="taxonomy" value="<?php echo esc_attr($taxonomy); ?>" />
<input type="hidden" name="post_type" value="<?php echo esc_attr($post_type); ?>" />

<?php $wp_list_table->display(); ?>

<br class="clear" />
</form>

<?php if ( 'category' == $taxonomy ) : ?>
<div class="form-wrap">
<p>
	<?php
	/** This filter is documented in wp-includes/category-template.php */
	printf( __( '<strong>Note:</strong><br />Deleting a category does not delete the posts in that category. Instead, posts that were only assigned to the deleted category are set to the category <strong>%s</strong>.' ), apply_filters( 'the_category', get_cat_name( get_option( 'default_category') ) ) );
	?>
</p>
<?php if ( current_user_can( 'import' ) ) : ?>
<p><?php printf(__('Categories can be selectively converted to tags using the <a href="%s">category to tag converter</a>.'), 'import.php') ?></p>
<?php endif; ?>
</div>
<?php elseif ( 'post_tag' == $taxonomy && current_user_can( 'import' ) ) : ?>
<div class="form-wrap">
<p><?php printf(__('Tags can be selectively converted to categories using the <a href="%s">tag to category converter</a>.'), 'import.php') ;?></p>
</div>
<?php endif;

/**
 * Fires after the taxonomy list table.
 *
 * The dynamic portion of the hook name, $taxonomy, refers to the taxonomy slug.
 *
 * @since 3.0.0
 *
 * @param string $taxonomy The taxonomy name.
 */
do_action( "after-{$taxonomy}-table", $taxonomy );
?>

</div>
</div><!-- /col-right -->

<div id="col-left">
<div class="col-wrap">

<?php

if ( !is_null( $tax->labels->popular_items ) ) {
	if ( current_user_can( $tax->cap->edit_terms ) )
		$tag_cloud = g5_tag_cloud( array( 'taxonomy' => $taxonomy, 'post_type' => $post_type, 'echo' => false, 'link' => 'edit' ) );
	else
		$tag_cloud = g5_tag_cloud( array( 'taxonomy' => $taxonomy, 'echo' => false ) );

	if ( $tag_cloud ) :
	?>
<div class="tagcloud">
<h3><?php echo $tax->labels->popular_items; ?></h3>
<?php echo $tag_cloud; unset( $tag_cloud ); ?>
</div>
<?php
endif;
}

if ( current_user_can($tax->cap->edit_terms) ) {
	if ( 'category' == $taxonomy ) {
		/**
 		 * Fires before the Add Category form.
		 *
		 * @since 2.1.0
		 * @deprecated 3.0.0 Use {$taxonomy}_pre_add_form instead.
		 *
		 * @param object $arg Optional arguments cast to an object.
		 */
		do_action( 'add_category_form_pre', (object) array( 'parent' => 0 ) );
	} elseif ( 'link_category' == $taxonomy ) {
		/**
		 * Fires before the link category form.
		 *
		 * @since 2.3.0
		 * @deprecated 3.0.0 Use {$taxonomy}_pre_add_form instead.
		 *
		 * @param object $arg Optional arguments cast to an object.
		 */
		do_action( 'add_link_category_form_pre', (object) array( 'parent' => 0 ) );
	} else {
		/**
		 * Fires before the Add Tag form.
		 *
		 * @since 2.5.0
		 * @deprecated 3.0.0 Use {$taxonomy}_pre_add_form instead.
		 *
		 * @param string $taxonomy The taxonomy slug.
		 */
		do_action( 'add_tag_form_pre', $taxonomy );
	}

	/**
	 * Fires before the Add Term form for all taxonomies.
	 *
	 * The dynamic portion of the hook name, $taxonomy, refers to the taxonomy slug.
	 *
	 * @since 3.0.0
	 *
	 * @param string $taxonomy The taxonomy slug.
	 */
	do_action( "{$taxonomy}_pre_add_form", $taxonomy );
?>

<div class="form-wrap">
<h3><?php echo $tax->labels->add_new_item; ?></h3>
<?php
/**
 * Fires at the beginning of the Add Tag form.
 *
 * The dynamic portion of the hook name, $taxonomy, refers to the taxonomy slug.
 *
 * @since 3.7.0
 */
if( $bo_table ){
?>
<form id="addtag" method="post" action="<?php echo $current_url; ?>" class="validate"<?php do_action( "{$taxonomy}_term_new_form_tag" ); ?>>
<input type="hidden" name="action" value="g5_add-tag" />
<input type="hidden" name="bo_table" value="<?php echo esc_attr($bo_table); ?>" />
<input type="hidden" name="screen" value="<?php echo esc_attr($current_screen->id); ?>" />
<input type="hidden" name="taxonomy" value="<?php echo esc_attr($taxonomy); ?>" />
<input type="hidden" name="post_type" value="<?php echo esc_attr($post_type); ?>" />
<input type="hidden" name="page" value="<?php echo esc_attr($page); ?>" />
<?php wp_nonce_field('add-tag', '_wpnonce_add-tag'); ?>

<div class="form-field form-required">
	<label for="tag-name"><?php _ex('Name', 'Taxonomy Name'); ?></label>
	<input name="tag-name" id="tag-name" type="text" value="" size="40" aria-required="true" />
	<p><?php _e('The name is how it appears on your site.'); ?></p>
</div>
<?php if ( ! global_terms_enabled() ) : ?>
<div class="form-field">
	<label for="tag-slug"><?php _ex('Slug', 'Taxonomy Slug'); ?></label>
	<input name="slug" id="tag-slug" type="text" value="" size="40" />
	<p><?php _e('The &#8220;slug&#8221; is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.'); ?></p>
</div>
<?php endif; // global_terms_enabled() ?>
<?php if ( is_taxonomy_hierarchical($taxonomy) ) : ?>
<div class="form-field">
	<label for="parent"><?php _ex('Parent', 'Taxonomy Parent'); ?></label>
	<?php
	$dropdown_args = array(
		'hide_empty'       => 0,
		'hide_if_empty'    => false,
		'taxonomy'         => $taxonomy,
		'name'             => 'parent',
		'orderby'          => 'name',
		'hierarchical'     => true,
		'show_option_none' => __( 'None' ),
	);

	/**
	 * Filter the taxonomy parent drop-down on the Edit Term page.
	 *
	 * @since 3.7.0
	 *
	 * @param array  $dropdown_args {
	 *     An array of taxonomy parent drop-down arguments.
	 *
	 *     @type int|bool $hide_empty       Whether to hide terms not attached to any posts. Default 0|false.
	 *     @type bool     $hide_if_empty    Whether to hide the drop-down if no terms exist. Default false.
	 *     @type string   $taxonomy         The taxonomy slug.
	 *     @type string   $name             Value of the name attribute to use for the drop-down select element.
	 *                                      Default 'parent'.
	 *     @type string   $orderby          The field to order by. Default 'name'.
	 *     @type bool     $hierarchical     Whether the taxonomy is hierarchical. Default true.
	 *     @type string   $show_option_none Label to display if there are no terms. Default 'None'.
	 * }
	 * @param string $taxonomy The taxonomy slug.
	 */
	$dropdown_args = apply_filters( 'taxonomy_parent_dropdown_args', $dropdown_args, $taxonomy );
	wp_dropdown_categories( $dropdown_args );
	?>
	<?php if ( 'category' == $taxonomy ) : // @todo: Generic text for hierarchical taxonomies ?>
		<p><?php _e('Categories, unlike tags, can have a hierarchy. You might have a Jazz category, and under that have children categories for Bebop and Big Band. Totally optional.'); ?></p>
	<?php endif; ?>
</div>
<?php endif; // is_taxonomy_hierarchical() ?>
<div class="form-field">
	<label for="tag-description"><?php _ex('Description', 'Taxonomy Description'); ?></label>
	<textarea name="description" id="tag-description" rows="5" cols="40"></textarea>
	<p><?php _e('The description is not prominent by default; however, some themes may show it.'); ?></p>
</div>

<?php
if ( ! is_taxonomy_hierarchical( $taxonomy ) ) {
	/**
	 * Fires after the Add Tag form fields for non-hierarchical taxonomies.
	 *
	 * @since 3.0.0
	 *
	 * @param string $taxonomy The taxonomy slug.
	 */
	do_action( 'add_tag_form_fields', $taxonomy );
}

/**
 * Fires after the Add Term form fields for hierarchical taxonomies.
 *
 * The dynamic portion of the hook name, $taxonomy, refers to the taxonomy slug.
 *
 * @since 3.0.0
 *
 * @param string $taxonomy The taxonomy slug.
 */
do_action( "{$taxonomy}_add_form_fields", $taxonomy );

submit_button( $tax->labels->add_new_item );

if ( 'category' == $taxonomy ) {
	/**
	 * Fires at the end of the Edit Category form.
	 *
	 * @since 2.1.0
	 * @deprecated 3.0.0 Use {$taxonomy}_add_form instead.
	 *
	 * @param object $arg Optional arguments cast to an object.
	 */
	do_action( 'edit_category_form', (object) array( 'parent' => 0 ) );
} elseif ( 'link_category' == $taxonomy ) {
	/**
	 * Fires at the end of the Edit Link form.
	 *
	 * @since 2.3.0
	 * @deprecated 3.0.0 Use {$taxonomy}_add_form instead.
	 *
	 * @param object $arg Optional arguments cast to an object.
	 */
	do_action( 'edit_link_category_form', (object) array( 'parent' => 0 ) );
} else {
	/**
	 * Fires at the end of the Add Tag form.
	 *
	 * @since 2.7.0
	 * @deprecated 3.0.0 Use {$taxonomy}_add_form instead.
	 *
	 * @param string $taxonomy The taxonomy slug.
	 */
	do_action( 'add_tag_form', $taxonomy );
}

/**
 * Fires at the end of the Add Term form for all taxonomies.
 *
 * The dynamic portion of the hook name, $taxonomy, refers to the taxonomy slug.
 *
 * @since 3.0.0
 *
 * @param string $taxonomy The taxonomy slug.
 */
do_action( "{$taxonomy}_add_form", $taxonomy );
?>
</form>
<?php } else { // end if $bo_table ?>
<div><?php _e('Please choose a board', 'gnupress');    //게시판을 선택해 주세요.?></div>
<?php } ?>
</div>
<?php } ?>

</div>
</div><!-- /col-left -->

</div><!-- /col-container -->
</div><!-- /wrap -->
<script type="text/javascript">
try{document.forms.addtag['tag-name'].focus();}catch(e){}

jQuery(document).ready(function($) {
    $("#select_bo_table").on("change", function(e){
        var current_url = "<?php echo $location_url; ?>";
        if( $(this).val() ){
            location.href= current_url+"&bo_table="+$(this).val();
        }
    });

	$( '#the-list' ).on( 'click', '.g5_delete-tag', function() {
		var t = $(this), tr = t.parents('tr'), r = true, data;
		if ( 'undefined' != showNotice )
			r = showNotice.warn();
		if ( r ) {
			data = t.attr('href').replace(/[^?]*\?/, '').replace(/action=delete/, 'action=g5_delete-tag');
			$.post(ajaxurl, data, function(r){
				if ( '1' == r ) {
					$('#ajax-response').empty();
					tr.fadeOut('normal', function(){ tr.remove(); });
					// Remove the term from the parent box and tag cloud
					$('select#parent option[value="' + data.match(/tag_ID=(\d+)/)[1] + '"]').remove();
					$('a.tag-link-' + data.match(/tag_ID=(\d+)/)[1]).remove();
				} else if ( '-1' == r ) {
					$('#ajax-response').empty().append('<div class="error"><p>' + tagsl10n.noPerm + '</p></div>');
					tr.children().css('backgroundColor', '');
				} else {
					$('#ajax-response').empty().append('<div class="error"><p>' + tagsl10n.broken + '</p></div>');
					tr.children().css('backgroundColor', '');
				}
			});
			tr.children().css('backgroundColor', '#f33');
		}
		return false;
	});

});
</script>
<?php $wp_list_table->inline_edit(); ?>

<?php
break;
}