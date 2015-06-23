<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

function g5_set_post_terms( $post_id = 0, $tags = '', $taxonomy = 'post_tag', $append = false, $get_field = '' ) {
	$post_id = (int) $post_id;

	if ( !$post_id )
		return false;

	if ( empty($tags) )
		$tags = array();

	if ( ! is_array( $tags ) ) {
		$comma = _x( ',', 'tag delimiter' );
		if ( ',' !== $comma )
			$tags = str_replace( $comma, ',', $tags );
		$tags = explode( ',', trim( $tags, " \n\t\r\0\x0B," ) );
	}

	/*
	 * Hierarchical taxonomies must always pass IDs rather than names so that
	 * children with the same names but different parents aren't confused.
	 */
	if ( is_taxonomy_hierarchical( $taxonomy ) ) {
		$tags = array_unique( array_map( 'intval', $tags ) );
	}

	return g5_set_object_terms( $post_id, $tags, $taxonomy, $append, $get_field );
}

/**
 * Create Term and Taxonomy Relationships.
 *
 * Relates an object (post, link etc) to a term and taxonomy type. Creates the
 * term and taxonomy relationship if it doesn't already exist. Creates a term if
 * it doesn't exist (using the slug).
 *
 * A relationship means that the term is grouped in or belongs to the taxonomy.
 * A term has no meaning until it is given context by defining which taxonomy it
 * exists under.
 *
 * @since 2.3.0
 * @uses wp_remove_object_terms()
 *
 * @param int              $object_id The object to relate to.
 * @param array|int|string $terms     A single term slug, single term id, or array of either term slugs or ids.
 *                                    Will replace all existing related terms in this taxonomy.
 * @param array|string     $taxonomy  The context in which to relate the term to the object.
 * @param bool             $append    Optional. If false will delete difference of terms. Default false.
 * @return array|WP_Error Affected Term IDs.
 */
function g5_set_object_terms( $object_id, $terms, $taxonomy, $append = false, $get_field = '' ) {
	global $wpdb, $gnupress;

    $g5 = $gnupress->g5;

	$object_id = (int) $object_id;

	if ( ! taxonomy_exists($taxonomy) )
		return new WP_Error('invalid_taxonomy', __('Invalid taxonomy'));

	if ( !is_array($terms) )
		$terms = array($terms);

	if ( ! $append )
		$old_tt_ids =  g5_get_object_terms($object_id, $taxonomy, array('fields' => 'tt_ids', 'orderby' => 'none'));
	else
		$old_tt_ids = array();

	$tt_ids = array();
	$term_ids = array();
	$new_tt_ids = array();

	foreach ( (array) $terms as $term) {
		if ( !strlen(trim($term)) )
			continue;

		if ( !$term_info = g5_term_exists($term, $taxonomy) ) {
			// Skip if a non-existent term ID is passed.
			if ( is_int($term) )
				continue;
			$term_info = g5_insert_term($term, $taxonomy);
		}
		if ( is_wp_error($term_info) )
			return $term_info;
		$term_ids[] = $term_info['term_id'];
		$tt_id = $term_info['term_taxonomy_id'];
		$tt_ids[] = $tt_id;

		if ( $wpdb->get_var( $wpdb->prepare( "SELECT term_taxonomy_id FROM `{$g5['relation_table']}` WHERE object_id = %d AND term_taxonomy_id = %d", $object_id, $tt_id ) ) )
			continue;

		/**
		 * Fires immediately before an object-term relationship is added.
		 *
		 * @since 2.9.0
		 *
		 * @param int $object_id Object ID.
		 * @param int $tt_id     Term taxonomy ID.
		 */
		do_action( 'g5_add_term_relationship', $object_id, $tt_id );
		$result = $wpdb->insert( $g5['relation_table'], array( 'object_id' => $object_id, 'term_taxonomy_id' => $tt_id ) );
        
        if( !$result ){
            //exit( var_dump( $wpdb->last_query ) );
        }
		/**
		 * Fires immediately after an object-term relationship is added.
		 *
		 * @since 2.9.0
		 *
		 * @param int $object_id Object ID.
		 * @param int $tt_id     Term taxonomy ID.
		 */
		do_action( 'g5_added_term_relationship', $object_id, $tt_id );
		$new_tt_ids[] = $tt_id;
	}

	if ( $new_tt_ids ){
		g5_update_term_count( $new_tt_ids, $taxonomy );
    }
	if ( ! $append ) {
		$delete_tt_ids = array_diff( $old_tt_ids, $tt_ids );

		if ( $delete_tt_ids ) {
			$in_delete_tt_ids = "'" . implode( "', '", $delete_tt_ids ) . "'";
			$delete_term_ids = $wpdb->get_col( $wpdb->prepare( "SELECT tt.term_id FROM `{$g5['taxonomy_table']}` AS tt WHERE tt.taxonomy = %s AND tt.term_taxonomy_id IN ($in_delete_tt_ids)", $taxonomy ) );
			$delete_term_ids = array_map( 'intval', $delete_term_ids );

			$remove = g5_remove_object_terms( $object_id, $delete_term_ids, $taxonomy );
			if ( is_wp_error( $remove ) ) {
				return $remove;
			}
		}
	}

	$t = get_taxonomy($taxonomy);
	if ( ! $append && isset($t->sort) && $t->sort ) {
		$values = array();
		$term_order = 0;
		$final_tt_ids = g5_get_object_terms($object_id, $taxonomy, array('fields' => 'tt_ids'));
		foreach ( $tt_ids as $tt_id )
			if ( in_array($tt_id, $final_tt_ids) )
				$values[] = $wpdb->prepare( "(%d, %d, %d)", $object_id, $tt_id, ++$term_order);
		if ( $values )
			if ( false === $wpdb->query( "INSERT INTO `{$g5['relation_table']}` (object_id, term_taxonomy_id, term_order) VALUES " . join( ',', $values ) . " ON DUPLICATE KEY UPDATE term_order = VALUES(term_order)" ) )
				return new WP_Error( 'db_insert_error', __( 'Could not insert term relationship into the database' ), $wpdb->last_error );
	}

	wp_cache_delete( $object_id, $taxonomy . '_relationships' );

	do_action( 'g5_set_object_terms', $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids );
    if( $get_field == 'term_id' ){
        return $term_ids;
    }
	return $tt_ids;
}

function g5_get_object_terms($object_ids, $taxonomies, $args = array()) {
	global $wpdb, $gnupress;

    $g5 = $gnupress->g5;

	if ( empty( $object_ids ) || empty( $taxonomies ) )
		return array();

	if ( !is_array($taxonomies) )
		$taxonomies = array($taxonomies);

	foreach ( $taxonomies as $taxonomy ) {
		if ( ! taxonomy_exists($taxonomy) )
			return new WP_Error('invalid_taxonomy', __('Invalid taxonomy'));
	}

	if ( !is_array($object_ids) )
		$object_ids = array($object_ids);
	$object_ids = array_map('intval', $object_ids);

	$defaults = array('orderby' => 'name', 'order' => 'ASC', 'fields' => 'all');
	$args = wp_parse_args( $args, $defaults );

	$terms = array();
	if ( count($taxonomies) > 1 ) {
		foreach ( $taxonomies as $index => $taxonomy ) {
			$t = get_taxonomy($taxonomy);
			if ( isset($t->args) && is_array($t->args) && $args != array_merge($args, $t->args) ) {
				unset($taxonomies[$index]);
				$terms = array_merge($terms, g5_get_object_terms($object_ids, $taxonomy, array_merge($args, $t->args)));
			}
		}
	} else {
		$t = get_taxonomy($taxonomies[0]);
		if ( isset($t->args) && is_array($t->args) )
			$args = array_merge($args, $t->args);
	}

	$orderby = $args['orderby'];
	$order = $args['order'];
	$fields = $args['fields'];

	if ( 'count' == $orderby )
		$orderby = 'tt.count';
	else if ( 'name' == $orderby )
		$orderby = 't.name';
	else if ( 'slug' == $orderby )
		$orderby = 't.slug';
	else if ( 'term_group' == $orderby )
		$orderby = 't.term_group';
	else if ( 'term_order' == $orderby )
		$orderby = 'tr.term_order';
	else if ( 'none' == $orderby ) {
		$orderby = '';
		$order = '';
	} else {
		$orderby = 't.term_id';
	}

	// tt_ids queries can only be none or tr.term_taxonomy_id
	if ( ('tt_ids' == $fields) && !empty($orderby) )
		$orderby = 'tr.term_taxonomy_id';

	if ( !empty($orderby) )
		$orderby = "ORDER BY $orderby";

	$order = strtoupper( $order );
	if ( '' !== $order && ! in_array( $order, array( 'ASC', 'DESC' ) ) )
		$order = 'ASC';

	$taxonomies = "'" . implode("', '", $taxonomies) . "'";
	$object_ids = implode(', ', $object_ids);

	$select_this = '';
	if ( 'all' == $fields )
		$select_this = 't.*, tt.*';
	else if ( 'ids' == $fields )
		$select_this = 't.term_id';
	else if ( 'names' == $fields )
		$select_this = 't.name';
	else if ( 'slugs' == $fields )
		$select_this = 't.slug';
	else if ( 'all_with_object_id' == $fields )
		$select_this = 't.*, tt.*, tr.object_id';

	$query = "SELECT $select_this FROM $wpdb->terms AS t INNER JOIN `{$g5['taxonomy_table']}` AS tt ON tt.term_id = t.term_id INNER JOIN `{$g5['relation_table']}` AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy IN ($taxonomies) AND tr.object_id IN ($object_ids) $orderby $order";

	$objects = false;
	if ( 'all' == $fields || 'all_with_object_id' == $fields ) {
		$_terms = $wpdb->get_results( $query );
		foreach ( $_terms as $key => $term ) {
			$_terms[$key] = sanitize_term( $term, $taxonomy, 'raw' );
		}
		$terms = array_merge( $terms, $_terms );
		update_term_cache( $terms );
		$objects = true;
	} else if ( 'ids' == $fields || 'names' == $fields || 'slugs' == $fields ) {
		$_terms = $wpdb->get_col( $query );
		$_field = ( 'ids' == $fields ) ? 'term_id' : 'name';
		foreach ( $_terms as $key => $term ) {
			$_terms[$key] = sanitize_term_field( $_field, $term, $term, $taxonomy, 'raw' );
		}
		$terms = array_merge( $terms, $_terms );
	} else if ( 'tt_ids' == $fields ) {
		$terms = $wpdb->get_col("SELECT tr.term_taxonomy_id FROM `{$g5['relation_table']}` AS tr INNER JOIN `{$g5['taxonomy_table']}` AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tr.object_id IN ($object_ids) AND tt.taxonomy IN ($taxonomies) $orderby $order");
		foreach ( $terms as $key => $tt_id ) {
			$terms[$key] = sanitize_term_field( 'term_taxonomy_id', $tt_id, 0, $taxonomy, 'raw' ); // 0 should be the term id, however is not needed when using raw context.
		}
	}

	if ( ! $terms ) {
		$terms = array();
	} elseif ( $objects && 'all_with_object_id' !== $fields ) {
		$_tt_ids = array();
		$_terms = array();
		foreach ( $terms as $term ) {
			if ( in_array( $term->term_taxonomy_id, $_tt_ids ) ) {
				continue;
			}

			$_tt_ids[] = $term->term_taxonomy_id;
			$_terms[] = $term;
		}
		$terms = $_terms;
	} elseif ( ! $objects ) {
		$terms = array_values( array_unique( $terms ) );
	}

	return apply_filters( 'g5_get_object_terms', $terms, $object_ids, $taxonomies, $args );
}

function g5_insert_term( $term, $taxonomy, $args = array() ) {
	global $wpdb;
    
    $g5 = G5_var::getInstance()->get_options();

	if ( ! taxonomy_exists($taxonomy) ) {
		return new WP_Error('invalid_taxonomy', __('Invalid taxonomy'));
	}
	/**
	 * Filter a term before it is sanitized and inserted into the database.
	 *
	 * @since 3.0.0
	 *
	 * @param string $term     The term to add or update.
	 * @param string $taxonomy Taxonomy slug.
	 */
	$term = apply_filters( 'pre_insert_term', $term, $taxonomy );
	if ( is_wp_error( $term ) ) {
		return $term;
	}
	if ( is_int($term) && 0 == $term ) {
		return new WP_Error('invalid_term_id', __('Invalid term ID'));
	}
	if ( '' == trim($term) ) {
		return new WP_Error('empty_term_name', __('A name is required for this term'));
	}
	$defaults = array( 'alias_of' => '', 'description' => '', 'parent' => 0, 'slug' => '');
	$args = wp_parse_args( $args, $defaults );

	if ( $args['parent'] > 0 && ! g5_term_exists( (int) $args['parent'] ) ) {
		return new WP_Error( 'missing_parent', __( 'Parent term does not exist.' ) );
	}
	$args['name'] = $term;
	$args['taxonomy'] = $taxonomy;
	$args = sanitize_term($args, $taxonomy, 'db');

	// expected_slashed ($name)
	$name = wp_unslash( $args['name'] );
	$description = wp_unslash( $args['description'] );
	$parent = (int) $args['parent'];

	$slug_provided = ! empty( $args['slug'] );
	if ( ! $slug_provided ) {
		$_name = trim( $name );
		$existing_term = g5_get_term_by( 'name', $_name, $taxonomy );
		if ( $existing_term ) {
			$slug = $existing_term->slug;
		} else {
			$slug = sanitize_title( $name );
		}
	} else {
		$slug = $args['slug'];
	}

	$term_group = 0;
	if ( $args['alias_of'] ) {
		$alias = $wpdb->get_row( $wpdb->prepare( "SELECT term_id, term_group FROM $wpdb->terms WHERE slug = %s", $args['alias_of'] ) );
		if ( $alias->term_group ) {
			// The alias we want is already in a group, so let's use that one.
			$term_group = $alias->term_group;
		} else {
			// The alias isn't in a group, so let's create a new one and firstly add the alias term to it.
			$term_group = $wpdb->get_var("SELECT MAX(term_group) FROM $wpdb->terms") + 1;

			/**
			 * Fires immediately before the given terms are edited.
			 *
			 * @since 2.9.0
			 *
			 * @param int    $term_id  Term ID.
			 * @param string $taxonomy Taxonomy slug.
			 */
			do_action( 'edit_terms', $alias->term_id, $taxonomy );
			$wpdb->update($wpdb->terms, compact('term_group'), array('term_id' => $alias->term_id) );

			/**
			 * Fires immediately after the given terms are edited.
			 *
			 * @since 2.9.0
			 *
			 * @param int    $term_id  Term ID
			 * @param string $taxonomy Taxonomy slug.
			 */
			do_action( 'edited_terms', $alias->term_id, $taxonomy );
		}
	}

	if ( $term_id = g5_term_exists($slug) ) {
		$existing_term = $wpdb->get_row( $wpdb->prepare( "SELECT name FROM $wpdb->terms WHERE term_id = %d", $term_id), ARRAY_A );
		// We've got an existing term in the same taxonomy, which matches the name of the new term:
		if ( is_taxonomy_hierarchical($taxonomy) && $existing_term['name'] == $name && $exists = g5_term_exists( (int) $term_id, $taxonomy ) ) {
			// Hierarchical, and it matches an existing term, Do not allow same "name" in the same level.
			$siblings = g5_get_terms($taxonomy, array('fields' => 'names', 'get' => 'all', 'parent' => $parent ) );
			if ( in_array($name, $siblings) ) {
				if ( $slug_provided ) {
					return new WP_Error( 'g5_term_exists', __( 'A term with the name and slug provided already exists with this parent.' ), $exists['term_id'] );
				} else {
					return new WP_Error( 'g5_term_exists', __( 'A term with the name provided already exists with this parent.' ), $exists['term_id'] );
				}
			} else {
				$slug = wp_unique_term_slug($slug, (object) $args);
				if ( false === $wpdb->insert( $wpdb->terms, compact( 'name', 'slug', 'term_group' ) ) ) {
					return new WP_Error('db_insert_error', __('Could not insert term into the database'), $wpdb->last_error);
				}
				$term_id = (int) $wpdb->insert_id;
			}
		} elseif ( $existing_term['name'] != $name ) {
			// We've got an existing term, with a different name, Create the new term.
			$slug = wp_unique_term_slug($slug, (object) $args);
			if ( false === $wpdb->insert( $wpdb->terms, compact( 'name', 'slug', 'term_group' ) ) ) {
				return new WP_Error('db_insert_error', __('Could not insert term into the database'), $wpdb->last_error);
			}
			$term_id = (int) $wpdb->insert_id;
		} elseif ( $exists = g5_term_exists( (int) $term_id, $taxonomy ) )  {
			// Same name, same slug.
			return new WP_Error( 'g5_term_exists', __( 'A term with the name and slug provided already exists.' ), $exists['term_id'] );
		}
	} else {
		// This term does not exist at all in the database, Create it.
		$slug = wp_unique_term_slug($slug, (object) $args);
		if ( false === $wpdb->insert( $wpdb->terms, compact( 'name', 'slug', 'term_group' ) ) ) {
			return new WP_Error('db_insert_error', __('Could not insert term into the database'), $wpdb->last_error);
		}
		$term_id = (int) $wpdb->insert_id;
	}

	// Seems unreachable, However, Is used in the case that a term name is provided, which sanitizes to an empty string.
	if ( empty($slug) ) {
		$slug = sanitize_title($slug, $term_id);

		/** This action is documented in wp-includes/taxonomy.php */
		do_action( 'edit_terms', $term_id, $taxonomy );
		$wpdb->update( $wpdb->terms, compact( 'slug' ), compact( 'term_id' ) );

		/** This action is documented in wp-includes/taxonomy.php */
		do_action( 'edited_terms', $term_id, $taxonomy );
	}

	$tt_id = $wpdb->get_var( $wpdb->prepare( "SELECT tt.term_taxonomy_id FROM `{$g5['taxonomy_table']}` AS tt INNER JOIN $wpdb->terms AS t ON tt.term_id = t.term_id WHERE tt.taxonomy = %s AND t.term_id = %d", $taxonomy, $term_id ) );

	if ( !empty($tt_id) ) {
		return array('term_id' => $term_id, 'term_taxonomy_id' => $tt_id);
	}
	$wpdb->insert( $g5['taxonomy_table'], compact( 'term_id', 'taxonomy', 'description', 'parent') + array( 'count' => 0 ) );
	$tt_id = (int) $wpdb->insert_id;

	/**
	 * Fires immediately after a new term is created, before the term cache is cleaned.
	 *
	 * @since 2.3.0
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	do_action( "create_term", $term_id, $tt_id, $taxonomy );

	/**
	 * Fires after a new term is created for a specific taxonomy.
	 *
	 * The dynamic portion of the hook name, $taxonomy, refers
	 * to the slug of the taxonomy the term was created for.
	 *
	 * @since 2.3.0
	 *
	 * @param int $term_id Term ID.
	 * @param int $tt_id   Term taxonomy ID.
	 */
	do_action( "create_$taxonomy", $term_id, $tt_id );

	/**
	 * Filter the term ID after a new term is created.
	 *
	 * @since 2.3.0
	 *
	 * @param int $term_id Term ID.
	 * @param int $tt_id   Taxonomy term ID.
	 */
	$term_id = apply_filters( 'term_id_filter', $term_id, $tt_id );

	clean_term_cache($term_id, $taxonomy);

	/**
	 * Fires after a new term is created, and after the term cache has been cleaned.
	 *
	 * @since 2.3.0
	 */
	do_action( "created_term", $term_id, $tt_id, $taxonomy );

	/**
	 * Fires after a new term in a specific taxonomy is created, and after the term
	 * cache has been cleaned.
	 *
	 * @since 2.3.0
	 *
	 * @param int $term_id Term ID.
	 * @param int $tt_id   Term taxonomy ID.
	 */
	do_action( "created_$taxonomy", $term_id, $tt_id );

	return array('term_id' => $term_id, 'term_taxonomy_id' => $tt_id);
}

function g5_term_exists($term, $taxonomy = '', $parent = 0) {
	global $wpdb;

    $g5 = G5_var::getInstance()->get_options();

	$select = "SELECT term_id FROM $wpdb->terms as t WHERE ";
	$tax_select = "SELECT tt.term_id, tt.term_taxonomy_id FROM $wpdb->terms AS t INNER JOIN `{$g5['taxonomy_table']}` as tt ON tt.term_id = t.term_id WHERE ";

	if ( is_int($term) ) {
		if ( 0 == $term )
			return 0;
		$where = 't.term_id = %d';
		if ( !empty($taxonomy) )
			return $wpdb->get_row( $wpdb->prepare( $tax_select . $where . " AND tt.taxonomy = %s", $term, $taxonomy ), ARRAY_A );
		else
			return $wpdb->get_var( $wpdb->prepare( $select . $where, $term ) );
	}

	$term = trim( wp_unslash( $term ) );

	if ( '' === $slug = sanitize_title($term) )
		return 0;

	$where = 't.slug = %s';
	$else_where = 't.name = %s';
	$where_fields = array($slug);
	$else_where_fields = array($term);
	if ( !empty($taxonomy) ) {
		$parent = (int) $parent;
		if ( $parent > 0 ) {
			$where_fields[] = $parent;
			$else_where_fields[] = $parent;
			$where .= ' AND tt.parent = %d';
			$else_where .= ' AND tt.parent = %d';
		}

		$where_fields[] = $taxonomy;
		$else_where_fields[] = $taxonomy;

		if ( $result = $wpdb->get_row( $wpdb->prepare("SELECT tt.term_id, tt.term_taxonomy_id FROM $wpdb->terms AS t INNER JOIN `{$g5['taxonomy_table']}` as tt ON tt.term_id = t.term_id WHERE $where AND tt.taxonomy = %s", $where_fields), ARRAY_A) )
			return $result;

		return $wpdb->get_row( $wpdb->prepare("SELECT tt.term_id, tt.term_taxonomy_id FROM $wpdb->terms AS t INNER JOIN `{$g5['taxonomy_table']}` as tt ON tt.term_id = t.term_id WHERE $else_where AND tt.taxonomy = %s", $else_where_fields), ARRAY_A);
	}

	if ( $result = $wpdb->get_var( $wpdb->prepare("SELECT term_id FROM $wpdb->terms as t WHERE $where", $where_fields) ) )
		return $result;

	return $wpdb->get_var( $wpdb->prepare("SELECT term_id FROM $wpdb->terms as t WHERE $else_where", $else_where_fields) );
}

function g5_update_term_count( $terms, $taxonomy, $do_deferred=false ) {

	static $_deferred = array();

	if ( $do_deferred ) {
		foreach ( (array) array_keys($_deferred) as $tax ) {
			g5_update_term_count_now( $_deferred[$tax], $tax );
			unset( $_deferred[$tax] );
		}
	}

	if ( empty($terms) )
		return false;

	if ( !is_array($terms) )
		$terms = array($terms);

	if ( g5_defer_term_counting() ) {
		if ( !isset($_deferred[$taxonomy]) )
			$_deferred[$taxonomy] = array();
		$_deferred[$taxonomy] = array_unique( array_merge($_deferred[$taxonomy], $terms) );
		return true;
	}

	return g5_update_term_count_now( $terms, $taxonomy );
}

function g5_update_term_count_now( $terms, $taxonomy ) {

	$terms = array_map('intval', $terms);

	$taxonomy = get_taxonomy($taxonomy);
	if ( !empty($taxonomy->update_count_callback) ) {
		call_user_func($taxonomy->update_count_callback, $terms, $taxonomy);
	} else {
		$object_types = (array) $taxonomy->object_type;
		foreach ( $object_types as &$object_type ) {
			if ( 0 === strpos( $object_type, 'attachment:' ) )
				list( $object_type ) = explode( ':', $object_type );
		}

		if ( $object_types == array_filter( $object_types, 'post_type_exists' ) ) {
			// Only post types are attached to this taxonomy
			g5_update_post_term_count( $terms, $taxonomy );
		} else {
			// Default count updater
			g5_update_generic_term_count( $terms, $taxonomy );
		}
	}

	g5_clean_term_cache($terms, '', false);

	return true;
}

function g5_update_post_term_count( $terms, $taxonomy ) {
	global $wpdb;

    $g5 = G5_var::getInstance()->get_options();

	$object_types = (array) $taxonomy->object_type;

	foreach ( $object_types as &$object_type )
		list( $object_type ) = explode( ':', $object_type );

	$object_types = array_unique( $object_types );

	if ( false !== ( $check_attachments = array_search( 'attachment', $object_types ) ) ) {
		unset( $object_types[ $check_attachments ] );
		$check_attachments = true;
	}

	if ( $object_types )
		$object_types = esc_sql( array_filter( $object_types, 'post_type_exists' ) );

	foreach ( (array) $terms as $term ) {
		$count = 0;

		// Attachments can be 'inherit' status, we need to base count off the parent's status if so
		if ( $check_attachments )
			$count += (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$g5['relation_table']}, {$g5['write_table']} p1 WHERE p1.wr_id = {$g5['relation_table']}.object_id AND term_taxonomy_id = %d", $term ) );

		if ( $object_types )
			$count += (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$g5['relation_table']}, {$g5['write_table']} WHERE {$g5['write_table']}.wr_id = {$g5['relation_table']}.object_id AND term_taxonomy_id = %d", $term ) );

		/** This action is documented in wp-includes/taxonomy.php */
		do_action( 'g5_edit_term_taxonomy', $term, $taxonomy );
		$wpdb->update( $g5['taxonomy_table'], compact( 'count' ), array( 'term_taxonomy_id' => $term ) );

		/** This action is documented in wp-includes/taxonomy.php */
		do_action( 'g5_edited_term_taxonomy', $term, $taxonomy );
	}
}

function g5_update_generic_term_count( $terms, $taxonomy ) {
	global $wpdb;

    $g5 = G5_var::getInstance()->get_options();

	foreach ( (array) $terms as $term ) {
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$g5['relation_table']} WHERE term_taxonomy_id = %d", $term ) );

		/** This action is documented in wp-includes/taxonomy.php */
		do_action( 'g5_edit_term_taxonomy', $term, $taxonomy );
		$wpdb->update( $g5['taxonomy_table'], compact( 'count' ), array( 'term_taxonomy_id' => $term ) );

		/** This action is documented in wp-includes/taxonomy.php */
		do_action( 'g5_edited_term_taxonomy', $term, $taxonomy );
	}
}

function g5_defer_term_counting($defer=null) {
	static $_defer = false;

	if ( is_bool($defer) ) {
		$_defer = $defer;
		// flush any deferred counts
		if ( !$defer )
			g5_update_term_count( null, null, true );
	}

	return $_defer;
}


function g5_clean_term_cache($ids, $taxonomy = '', $clean_taxonomy = true) {
	global $wpdb;

    $g5 = G5_var::getInstance()->get_options();

	if ( !is_array($ids) )
		$ids = array($ids);

	$taxonomies = array();
	// If no taxonomy, assume tt_ids.
	if ( empty($taxonomy) ) {
		$tt_ids = array_map('intval', $ids);
		$tt_ids = implode(', ', $tt_ids);
		$terms = $wpdb->get_results("SELECT term_id, taxonomy FROM `{$g5['taxonomy_table']}` WHERE term_taxonomy_id IN ($tt_ids)");
		$ids = array();
		foreach ( (array) $terms as $term ) {
			$taxonomies[] = $term->taxonomy;
			$ids[] = $term->term_id;
			wp_cache_delete($term->term_id, $term->taxonomy);
		}
		$taxonomies = array_unique($taxonomies);
	} else {
		$taxonomies = array($taxonomy);
		foreach ( $taxonomies as $taxonomy ) {
			foreach ( $ids as $id ) {
				wp_cache_delete($id, $taxonomy);
			}
		}
	}

	foreach ( $taxonomies as $taxonomy ) {
		if ( $clean_taxonomy ) {
			wp_cache_delete('all_ids', $taxonomy);
			wp_cache_delete('get', $taxonomy);
			delete_option("{$taxonomy}_children");
			// Regenerate {$taxonomy}_children
			_get_term_hierarchy($taxonomy);
		}

		/**
		 * Fires once after each taxonomy's term cache has been cleaned.
		 *
		 * @since 2.5.0
		 *
		 * @param array  $ids      An array of term IDs.
		 * @param string $taxonomy Taxonomy slug.
		 */
		do_action( 'clean_term_cache', $ids, $taxonomy );
	}

	wp_cache_set( 'g5_last_changed', microtime(), 'terms' );
}

function g5_get_terms( $taxonomies, $args = '' ) {
	global $wpdb;

    $g5 = G5_var::getInstance()->get_options();

	$empty_array = array();

	$single_taxonomy = ! is_array( $taxonomies ) || 1 === count( $taxonomies );
	if ( ! is_array( $taxonomies ) ) {
		$taxonomies = array( $taxonomies );
	}

	foreach ( $taxonomies as $taxonomy ) {
		if ( ! taxonomy_exists($taxonomy) ) {
			$error = new WP_Error('invalid_taxonomy', __('Invalid taxonomy'));
			return $error;
		}
	}

	$defaults = array('orderby' => 'name', 'order' => 'ASC',
		'hide_empty' => true, 'exclude' => array(), 'exclude_tree' => array(), 'include' => array(),
		'number' => '', 'fields' => 'all', 'slug' => '', 'parent' => '',
		'hierarchical' => true, 'child_of' => 0, 'get' => '', 'name__like' => '', 'description__like' => '',
		'pad_counts' => false, 'offset' => '', 'search' => '', 'cache_domain' => 'core' );
	$args = wp_parse_args( $args, $defaults );
	$args['number'] = absint( $args['number'] );
	$args['offset'] = absint( $args['offset'] );

	// Save queries by not crawling the tree in the case of multiple taxes or a flat tax.
	if ( ! $single_taxonomy || ! is_taxonomy_hierarchical( reset( $taxonomies ) ) ) {
		$args['hierarchical'] = false;
		$args['pad_counts'] = false;
	}

	// 'parent' overrides 'child_of'.
	if ( 0 < intval( $args['parent'] ) ) {
		$args['child_of'] = false;
	}

	if ( 'all' == $args['get'] ) {
		$args['child_of'] = 0;
		$args['hide_empty'] = 0;
		$args['hierarchical'] = false;
		$args['pad_counts'] = false;
	}

	/**
	 * Filter the terms query arguments.
	 *
	 * @since 3.1.0
	 *
	 * @param array        $args       An array of arguments.
	 * @param string|array $taxonomies A taxonomy or array of taxonomies.
	 */
	$args = apply_filters( 'get_terms_args', $args, $taxonomies );

	$child_of = $args['child_of'];
	if ( $child_of ) {
		$hierarchy = _get_term_hierarchy( reset( $taxonomies ) );
		if ( ! isset( $hierarchy[ $child_of ] ) ) {
			return $empty_array;
		}
	}

	$parent = $args['parent'];
	if ( $parent ) {
		$hierarchy = _get_term_hierarchy( reset( $taxonomies ) );
		if ( ! isset( $hierarchy[ $parent ] ) ) {
			return $empty_array;
		}
	}

	// $args can be whatever, only use the args defined in defaults to compute the key
	$filter_key = ( has_filter('list_terms_exclusions') ) ? serialize($GLOBALS['wp_filter']['list_terms_exclusions']) : '';
	$key = md5( serialize( wp_array_slice_assoc( $args, array_keys( $defaults ) ) ) . serialize( $taxonomies ) . $filter_key );
	$last_changed = wp_cache_get( 'g5_last_changed', 'terms' );
	if ( ! $last_changed ) {
		$last_changed = microtime();
		wp_cache_set( 'g5_last_changed', $last_changed, 'terms' );
	}
	$cache_key = "get_terms:$key:$last_changed";
	$cache = wp_cache_get( $cache_key, 'terms' );
	if ( false !== $cache ) {

		/**
		 * Filter the given taxonomy's terms cache.
		 *
		 * @since 2.3.0
		 *
		 * @param array        $cache      Cached array of terms for the given taxonomy.
		 * @param string|array $taxonomies A taxonomy or array of taxonomies.
		 * @param array        $args       An array of arguments to get terms.
		 */
		$cache = apply_filters( 'g5_get_terms', $cache, $taxonomies, $args );
		return $cache;
	}

	$_orderby = strtolower( $args['orderby'] );
	if ( 'count' == $_orderby ) {
		$orderby = 'tt.count';
	} else if ( 'name' == $_orderby ) {
		$orderby = 't.name';
	} else if ( 'slug' == $_orderby ) {
		$orderby = 't.slug';
	} else if ( 'include' == $_orderby && ! empty( $args['include'] ) ) {
		$include = implode( ',', array_map( 'absint', $args['include'] ) );
		$orderby = "FIELD( t.term_id, $include )";
	} else if ( 'term_group' == $_orderby ) {
		$orderby = 't.term_group';
	} else if ( 'none' == $_orderby ) {
		$orderby = '';
	} elseif ( empty($_orderby) || 'id' == $_orderby ) {
		$orderby = 't.term_id';
	} else {
		$orderby = 't.name';
	}
	/**
	 * Filter the ORDERBY clause of the terms query.
	 *
	 * @since 2.8.0
	 *
	 * @param string       $orderby    ORDERBY clause of the terms query.
	 * @param array        $args       An array of terms query arguments.
	 * @param string|array $taxonomies A taxonomy or array of taxonomies.
	 */
	$orderby = apply_filters( 'get_terms_orderby', $orderby, $args, $taxonomies );

	$order = strtoupper( $args['order'] );
	if ( ! empty( $orderby ) ) {
		$orderby = "ORDER BY $orderby";
	} else {
		$order = '';
	}

	if ( '' !== $order && ! in_array( $order, array( 'ASC', 'DESC' ) ) ) {
		$order = 'ASC';
	}

	$where = "tt.taxonomy IN ('" . implode("', '", $taxonomies) . "')";

	$exclude = $args['exclude'];
	$exclude_tree = $args['exclude_tree'];
	$include = $args['include'];

	$inclusions = '';
	if ( ! empty( $include ) ) {
		$exclude = '';
		$exclude_tree = '';
		$inclusions = implode( ',', wp_parse_id_list( $include ) );
	}

	if ( ! empty( $inclusions ) ) {
		$inclusions = ' AND t.term_id IN ( ' . $inclusions . ' )';
		$where .= $inclusions;
	}

	if ( ! empty( $exclude_tree ) ) {
		$exclude_tree = wp_parse_id_list( $exclude_tree );
		$excluded_children = $exclude_tree;
		foreach ( $exclude_tree as $extrunk ) {
			$excluded_children = array_merge(
				$excluded_children,
				(array) g5_get_terms( $taxonomies[0], array( 'child_of' => intval( $extrunk ), 'fields' => 'ids', 'hide_empty' => 0 ) )
			);
		}
		$exclusions = implode( ',', array_map( 'intval', $excluded_children ) );
	} else {
		$exclusions = '';
	}

	if ( ! empty( $exclude ) ) {
		$exterms = wp_parse_id_list( $exclude );
		if ( empty( $exclusions ) ) {
			$exclusions = implode( ',', $exterms );
		} else {
			$exclusions .= ', ' . implode( ',', $exterms );
		}
	}

	if ( ! empty( $exclusions ) ) {
		$exclusions = ' AND t.term_id NOT IN (' . $exclusions . ')';
	}

	/**
	 * Filter the terms to exclude from the terms query.
	 *
	 * @since 2.3.0
	 *
	 * @param string       $exclusions NOT IN clause of the terms query.
	 * @param array        $args       An array of terms query arguments.
	 * @param string|array $taxonomies A taxonomy or array of taxonomies.
	 */
	$exclusions = apply_filters( 'list_terms_exclusions', $exclusions, $args, $taxonomies );

	if ( ! empty( $exclusions ) ) {
		$where .= $exclusions;
	}

	if ( ! empty( $args['slug'] ) ) {
		if ( is_array( $args['slug'] ) ) {
			$slug = array_map( 'sanitize_title', $args['slug'] );
			$where .= " AND t.slug IN ('" . implode( "', '", $slug ) . "')";
		} else {
			$slug = sanitize_title( $args['slug'] );
			$where .= " AND t.slug = '$slug'";
		}
	}

	if ( ! empty( $args['name__like'] ) ) {
		$where .= $wpdb->prepare( " AND t.name LIKE %s", '%' . $wpdb->esc_like( $args['name__like'] ) . '%' );
	}

	if ( ! empty( $args['description__like'] ) ) {
		$where .= $wpdb->prepare( " AND tt.description LIKE %s", '%' . $wpdb->esc_like( $args['description__like'] ) . '%' );
	}

	if ( '' !== $parent ) {
		$parent = (int) $parent;
		$where .= " AND tt.parent = '$parent'";
	}

	$hierarchical = $args['hierarchical'];
	if ( 'count' == $args['fields'] ) {
		$hierarchical = false;
	}
	if ( $args['hide_empty'] && !$hierarchical ) {
		$where .= ' AND tt.count > 0';
	}

	$number = $args['number'];
	$offset = $args['offset'];

	// don't limit the query results when we have to descend the family tree
	if ( $number && ! $hierarchical && ! $child_of && '' === $parent ) {
		if ( $offset ) {
			$limits = 'LIMIT ' . $offset . ',' . $number;
		} else {
			$limits = 'LIMIT ' . $number;
		}
	} else {
		$limits = '';
	}

	if ( ! empty( $args['search'] ) ) {
		$like = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		$where .= $wpdb->prepare( ' AND ((t.name LIKE %s) OR (t.slug LIKE %s))', $like, $like );
	}

	$selects = array();
	switch ( $args['fields'] ) {
		case 'all':
			$selects = array( 't.*', 'tt.*' );
			break;
		case 'ids':
		case 'id=>parent':
			$selects = array( 't.term_id', 'tt.parent', 'tt.count' );
			break;
		case 'names':
			$selects = array( 't.term_id', 'tt.parent', 'tt.count', 't.name' );
			break;
		case 'count':
			$orderby = '';
			$order = '';
			$selects = array( 'COUNT(*)' );
			break;
		case 'id=>name':
			$selects = array( 't.term_id', 't.name', 'tt.count' );
			break;
		case 'id=>slug':
			$selects = array( 't.term_id', 't.slug', 'tt.count' );
			break;
	}

	$_fields = $args['fields'];

	/**
	 * Filter the fields to select in the terms query.
	 *
	 * @since 2.8.0
	 *
	 * @param array        $selects    An array of fields to select for the terms query.
	 * @param array        $args       An array of term query arguments.
	 * @param string|array $taxonomies A taxonomy or array of taxonomies.
	 */
	$fields = implode( ', ', apply_filters( 'get_terms_fields', $selects, $args, $taxonomies ) );

	$join = "INNER JOIN `{$g5['taxonomy_table']}` AS tt ON t.term_id = tt.term_id";

	$pieces = array( 'fields', 'join', 'where', 'orderby', 'order', 'limits' );

	/**
	 * Filter the terms query SQL clauses.
	 *
	 * @since 3.1.0
	 *
	 * @param array        $pieces     Terms query SQL clauses.
	 * @param string|array $taxonomies A taxonomy or array of taxonomies.
	 * @param array        $args       An array of terms query arguments.
	 */
	$clauses = apply_filters( 'terms_clauses', compact( $pieces ), $taxonomies, $args );
	$fields = isset( $clauses[ 'fields' ] ) ? $clauses[ 'fields' ] : '';
	$join = isset( $clauses[ 'join' ] ) ? $clauses[ 'join' ] : '';
	$where = isset( $clauses[ 'where' ] ) ? $clauses[ 'where' ] : '';
	$orderby = isset( $clauses[ 'orderby' ] ) ? $clauses[ 'orderby' ] : '';
	$order = isset( $clauses[ 'order' ] ) ? $clauses[ 'order' ] : '';
	$limits = isset( $clauses[ 'limits' ] ) ? $clauses[ 'limits' ] : '';

	$query = "SELECT $fields FROM $wpdb->terms AS t $join WHERE $where $orderby $order $limits";

	if ( 'count' == $_fields ) {
		$term_count = $wpdb->get_var($query);
		return $term_count;
	}

	$terms = $wpdb->get_results($query);
	if ( 'all' == $_fields ) {
		update_term_cache( $terms );
	}

	if ( empty($terms) ) {
		wp_cache_add( $cache_key, array(), 'terms', DAY_IN_SECONDS );

		/** This filter is documented in wp-includes/taxonomy.php */
		$terms = apply_filters( 'get_terms', array(), $taxonomies, $args );
		return $terms;
	}

	if ( $child_of ) {
		$children = _get_term_hierarchy( reset( $taxonomies ) );
		if ( ! empty( $children ) ) {
			$terms = _get_term_children( $child_of, $terms, reset( $taxonomies ) );
		}
	}

	// Update term counts to include children.
	if ( $args['pad_counts'] && 'all' == $_fields ) {
		_pad_term_counts( $terms, reset( $taxonomies ) );
	}
	// Make sure we show empty categories that have children.
	if ( $hierarchical && $args['hide_empty'] && is_array( $terms ) ) {
		foreach ( $terms as $k => $term ) {
			if ( ! $term->count ) {
				$children = get_term_children( $term->term_id, reset( $taxonomies ) );
				if ( is_array( $children ) ) {
					foreach ( $children as $child_id ) {
						$child = get_term( $child_id, reset( $taxonomies ) );
						if ( $child->count ) {
							continue 2;
						}
					}
				}

				// It really is empty
				unset($terms[$k]);
			}
		}
	}
	reset( $terms );

	$_terms = array();
	if ( 'id=>parent' == $_fields ) {
		while ( $term = array_shift( $terms ) ) {
			$_terms[$term->term_id] = $term->parent;
		}
	} elseif ( 'ids' == $_fields ) {
		while ( $term = array_shift( $terms ) ) {
			$_terms[] = $term->term_id;
		}
	} elseif ( 'names' == $_fields ) {
		while ( $term = array_shift( $terms ) ) {
			$_terms[] = $term->name;
		}
	} elseif ( 'id=>name' == $_fields ) {
		while ( $term = array_shift( $terms ) ) {
			$_terms[$term->term_id] = $term->name;
		}
	} elseif ( 'id=>slug' == $_fields ) {
		while ( $term = array_shift( $terms ) ) {
			$_terms[$term->term_id] = $term->slug;
		}
	}

	if ( ! empty( $_terms ) ) {
		$terms = $_terms;
	}

	if ( $number && is_array( $terms ) && count( $terms ) > $number ) {
		$terms = array_slice( $terms, $offset, $number );
	}

	wp_cache_add( $cache_key, $terms, 'terms', DAY_IN_SECONDS );

	/** This filter is documented in wp-includes/taxonomy */
	$terms = apply_filters( 'g5_get_terms', $terms, $taxonomies, $args );
	return $terms;
}

function g5_get_term($term, $taxonomy, $output = OBJECT, $filter = 'raw') {
	global $wpdb;

    $g5 = G5_var::getInstance()->get_options();
	if ( empty($term) ) {
		$error = new WP_Error('invalid_term', __('Empty Term'));
		return $error;
	}

	if ( ! taxonomy_exists($taxonomy) ) {
		$error = new WP_Error('invalid_taxonomy', __('Invalid taxonomy'));
		return $error;
	}

	if ( is_object($term) && empty($term->filter) ) {
		wp_cache_add( $term->term_id, $term, $taxonomy );
		$_term = $term;
	} else {
		if ( is_object($term) )
			$term = $term->term_id;
		if ( !$term = (int) $term )
			return null;
		if ( ! $_term = wp_cache_get( $term, $taxonomy ) ) {
			$_term = $wpdb->get_row( $wpdb->prepare( "SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN {$g5['taxonomy_table']} AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy = %s AND t.term_id = %d LIMIT 1", $taxonomy, $term) );
			if ( ! $_term )
				return null;
			wp_cache_add( $term, $_term, $taxonomy );
		}
	}

	/**
	 * Filter a term.
	 *
	 * @since 2.3.0
	 *
	 * @param int|object $_term    Term object or ID.
	 * @param string     $taxonomy The taxonomy slug.
	 */
	$_term = apply_filters( 'get_term', $_term, $taxonomy );

	/**
	 * Filter a taxonomy.
	 *
	 * The dynamic portion of the filter name, `$taxonomy`, refers
	 * to the taxonomy slug.
	 *
	 * @since 2.3.0
	 *
	 * @param int|object $_term    Term object or ID.
	 * @param string     $taxonomy The taxonomy slug.
	 */
	$_term = apply_filters( "get_$taxonomy", $_term, $taxonomy );
	$_term = sanitize_term($_term, $taxonomy, $filter);

	if ( $output == OBJECT ) {
		return $_term;
	} elseif ( $output == ARRAY_A ) {
		$__term = get_object_vars($_term);
		return $__term;
	} elseif ( $output == ARRAY_N ) {
		$__term = array_values(get_object_vars($_term));
		return $__term;
	} else {
		return $_term;
	}
}

function g5_get_term_by($field, $value, $taxonomy, $output = OBJECT, $filter = 'raw') {
	global $wpdb;

    $g5 = G5_var::getInstance()->get_options();

	if ( ! taxonomy_exists($taxonomy) )
		return false;

	if ( 'slug' == $field ) {
		$field = 't.slug';
		$value = sanitize_title($value);
		if ( empty($value) )
			return false;
	} else if ( 'name' == $field ) {
		// Assume already escaped
		$value = wp_unslash($value);
		$field = 't.name';
	} else if ( 'term_taxonomy_id' == $field ) {
		$value = (int) $value;
		$field = 'tt.term_taxonomy_id';
	} else {
		$term = g5_get_term( (int) $value, $taxonomy, $output, $filter );
		if ( is_wp_error( $term ) )
			$term = false;
		return $term;
	}

	$term = $wpdb->get_row( $wpdb->prepare( "SELECT t.*, tt.* FROM $wpdb->terms AS t INNER JOIN {$g5['taxonomy_table']} AS tt ON t.term_id = tt.term_id WHERE tt.taxonomy = %s AND $field = %s LIMIT 1", $taxonomy, $value ) );
	if ( ! $term )
		return false;

	wp_cache_add( $term->term_id, $term, $taxonomy );

	/** This filter is documented in wp-includes/taxonomy.php */
	$term = apply_filters( 'g5_get_term', $term, $taxonomy );

	/** This filter is documented in wp-includes/taxonomy.php */
	$term = apply_filters( "g5_get_$taxonomy", $term, $taxonomy );

	$term = sanitize_term($term, $taxonomy, $filter);

	if ( $output == OBJECT ) {
		return $term;
	} elseif ( $output == ARRAY_A ) {
		return get_object_vars($term);
	} elseif ( $output == ARRAY_N ) {
		return array_values(get_object_vars($term));
	} else {
		return $term;
	}
}

function g5_update_term( $term_id, $taxonomy, $args = array() ) {
	global $wpdb;

    $g5 = G5_var::getInstance()->get_options();

	if ( ! taxonomy_exists($taxonomy) )
		return new WP_Error('invalid_taxonomy', __('Invalid taxonomy'));

	$term_id = (int) $term_id;

	// First, get all of the original args
	$term = g5_get_term ($term_id, $taxonomy, ARRAY_A);

	if ( is_wp_error( $term ) )
		return $term;

	// Escape data pulled from DB.
	$term = wp_slash($term);

	// Merge old and new args with new args overwriting old ones.
	$args = array_merge($term, $args);

	$defaults = array( 'alias_of' => '', 'description' => '', 'parent' => 0, 'slug' => '');
	$args = wp_parse_args($args, $defaults);
	$args = sanitize_term($args, $taxonomy, 'db');
	$parsed_args = $args;

	// expected_slashed ($name)
	$name = wp_unslash( $args['name'] );
	$description = wp_unslash( $args['description'] );

	$parsed_args['name'] = $name;
	$parsed_args['description'] = $description;

	if ( '' == trim($name) )
		return new WP_Error('empty_term_name', __('A name is required for this term'));

	if ( $parsed_args['parent'] > 0 && ! g5_term_exists( (int) $parsed_args['parent'] ) ) {
		return new WP_Error( 'missing_parent', __( 'Parent term does not exist.' ) );
	}

	$empty_slug = false;
	if ( empty( $args['slug'] ) ) {
		$empty_slug = true;
		$slug = sanitize_title($name);
	} else {
		$slug = $args['slug'];
	}

	$parsed_args['slug'] = $slug;

	$term_group = isset( $parsed_args['term_group'] ) ? $parsed_args['term_group'] : 0;
	if ( $args['alias_of'] ) {
		$alias = g5_get_term_by( 'slug', $args['alias_of'], $taxonomy );
		if ( ! empty( $alias->term_group ) ) {
			// The alias we want is already in a group, so let's use that one.
			$term_group = $alias->term_group;
		} else if ( ! empty( $alias->term_id ) ) {
			/*
			 * The alias is not in a group, so we create a new one
			 * and add the alias to it.
			 */
			$term_group = $wpdb->get_var("SELECT MAX(term_group) FROM $wpdb->terms") + 1;

			g5_update_term( $alias->term_id, $taxonomy, array(
				'term_group' => $term_group,
			) );
		}

		$parsed_args['term_group'] = $term_group;
	}

	/**
	 * Filter the term parent.
	 *
	 * Hook to this filter to see if it will cause a hierarchy loop.
	 *
	 * @since 3.1.0
	 *
	 * @param int    $parent      ID of the parent term.
	 * @param int    $term_id     Term ID.
	 * @param string $taxonomy    Taxonomy slug.
	 * @param array  $parsed_args An array of potentially altered update arguments for the given term.
	 * @param array  $args        An array of update arguments for the given term.
	 */
	$parent = apply_filters( 'wp_update_term_parent', $args['parent'], $term_id, $taxonomy, $parsed_args, $args );

	// Check for duplicate slug
	$id = $wpdb->get_var( $wpdb->prepare( "SELECT term_id FROM $wpdb->terms WHERE slug = %s", $slug ) );
	if ( $id && ($id != $term_id) ) {
		// If an empty slug was passed or the parent changed, reset the slug to something unique.
		// Otherwise, bail.
		if ( $empty_slug || ( $parent != $term['parent']) )
			$slug = wp_unique_term_slug($slug, (object) $args);
		else
			return new WP_Error('duplicate_term_slug', sprintf(__('The slug &#8220;%s&#8221; is already in use by another term'), $slug));
	}

	$tt_id = $wpdb->get_var( $wpdb->prepare( "SELECT tt.term_taxonomy_id FROM {$g5['taxonomy_table']} AS tt INNER JOIN $wpdb->terms AS t ON tt.term_id = t.term_id WHERE tt.taxonomy = %s AND t.term_id = %d", $taxonomy, $term_id) );

	/**
	 * Fires immediately before the given terms are edited.
	 *
	 * @since 2.9.0
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	do_action( 'g5_edit_terms', $term_id, $taxonomy );
	$wpdb->update($wpdb->terms, compact( 'name', 'slug', 'term_group' ), compact( 'term_id' ) );
	if ( empty($slug) ) {
		$slug = sanitize_title($name, $term_id);
		$wpdb->update( $wpdb->terms, compact( 'slug' ), compact( 'term_id' ) );
	}

	/**
	 * Fires immediately after the given terms are edited.
	 *
	 * @since 2.9.0
	 *
	 * @param int    $term_id  Term ID
	 * @param string $taxonomy Taxonomy slug.
	 */
	do_action( 'g5_edited_terms', $term_id, $taxonomy );

	/**
	 * Fires immediate before a term-taxonomy relationship is updated.
	 *
	 * @since 2.9.0
	 *
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	do_action( 'g5_edit_term_taxonomy', $tt_id, $taxonomy );
	$wpdb->update( $g5['taxonomy_table'], compact( 'term_id', 'taxonomy', 'description', 'parent' ), array( 'term_taxonomy_id' => $tt_id ) );

	/**
	 * Fires immediately after a term-taxonomy relationship is updated.
	 *
	 * @since 2.9.0
	 *
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	do_action( 'g5_edited_term_taxonomy', $tt_id, $taxonomy );

	// Clean the relationship caches for all object types using this term
	$objects = $wpdb->get_col( $wpdb->prepare( "SELECT object_id FROM {$g5['relation_table']} WHERE term_taxonomy_id = %d", $tt_id ) );
	$tax_object = get_taxonomy( $taxonomy );
	foreach ( $tax_object->object_type as $object_type ) {
		clean_object_term_cache( $objects, $object_type );
	}

	/**
	 * Fires after a term has been updated, but before the term cache has been cleaned.
	 *
	 * @since 2.3.0
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	do_action( "g5_edit_term", $term_id, $tt_id, $taxonomy );

	/**
	 * Fires after a term in a specific taxonomy has been updated, but before the term
	 * cache has been cleaned.
	 *
	 * The dynamic portion of the hook name, `$taxonomy`, refers to the taxonomy slug.
	 *
	 * @since 2.3.0
	 *
	 * @param int $term_id Term ID.
	 * @param int $tt_id   Term taxonomy ID.
	 */
	do_action( "g5_edit_$taxonomy", $term_id, $tt_id );

	/** This filter is documented in wp-includes/taxonomy.php */
	$term_id = apply_filters( 'g5_term_id_filter', $term_id, $tt_id );

	clean_term_cache($term_id, $taxonomy);

	/**
	 * Fires after a term has been updated, and the term cache has been cleaned.
	 *
	 * @since 2.3.0
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	do_action( "g5_edited_term", $term_id, $tt_id, $taxonomy );

	/**
	 * Fires after a term for a specific taxonomy has been updated, and the term
	 * cache has been cleaned.
	 *
	 * The dynamic portion of the hook name, `$taxonomy`, refers to the taxonomy slug.
	 *
	 * @since 2.3.0
	 *
	 * @param int $term_id Term ID.
	 * @param int $tt_id   Term taxonomy ID.
	 */
	do_action( "g5_edited_$taxonomy", $term_id, $tt_id );

	return array('term_id' => $term_id, 'term_taxonomy_id' => $tt_id);
}

function g5_tag_cloud( $args = '' ) {
	$defaults = array(
		'smallest' => 8, 'largest' => 22, 'unit' => 'pt', 'number' => 45,
		'format' => 'flat', 'separator' => "\n", 'orderby' => 'name', 'order' => 'ASC',
		'exclude' => '', 'include' => '', 'link' => 'view', 'taxonomy' => 'post_tag', 'post_type' => '', 'echo' => true
	);
	$args = wp_parse_args( $args, $defaults );

	$tags = g5_get_terms( $args['taxonomy'], array_merge( $args, array( 'orderby' => 'count', 'order' => 'DESC' ) ) ); // Always query top tags

	if ( empty( $tags ) || is_wp_error( $tags ) )
		return;

	foreach ( $tags as $key => $tag ) {
		if ( 'edit' == $args['link'] )
			$link = get_edit_term_link( $tag->term_id, $tag->taxonomy, $args['post_type'] );
		else
			$link = get_term_link( intval($tag->term_id), $tag->taxonomy );
		if ( is_wp_error( $link ) )
			return false;

		$tags[ $key ]->link = $link;
		$tags[ $key ]->id = $tag->term_id;
	}

	$return = wp_generate_tag_cloud( $tags, $args ); // Here's where those top tags get sorted according to $args

	/**
	 * Filter the tag cloud output.
	 *
	 * @since 2.3.0
	 *
	 * @param string $return HTML output of the tag cloud.
	 * @param array  $args   An array of tag cloud arguments.
	 */
	$return = apply_filters( 'wp_tag_cloud', $return, $args );

	if ( 'array' == $args['format'] || empty($args['echo']) )
		return $return;

	echo $return;
}

function g5_get_edit_term_link( $term_id, $taxonomy, $object_type = '', $url='', $bo_table='', $action='edit' ) {
	$tax = get_taxonomy( $taxonomy );
	if ( !current_user_can( $tax->cap->edit_terms ) )
		return;

	$term = g5_get_term( $term_id, $taxonomy );

	$args = array(
		'action' => $action,
		'taxonomy' => $taxonomy,
		'tag_ID' => $term->term_id,
        'bo_table' => $bo_table
	);

	if ( $object_type )
		$args['post_type'] = $object_type;

    if( !$url ){
        $page = isset($_REQUEST['page']) ? sanitize_text_field($_REQUEST['page']) : '';
        if( $action == 'delete' ){
            $url = g5_get_current_page()."?page=".$page;
        } else {
            $url = admin_url( g5_get_current_page()."?page=".$page );
        }
    }
	$location = add_query_arg( $args, $url);

	/**
	 * Filter the edit link for a term.
	 *
	 * @since 3.1.0
	 *
	 * @param string $location    The edit link.
	 * @param int    $term_id     Term ID.
	 * @param string $taxonomy    Taxonomy name.
	 * @param string $object_type The object type (eg. the post type).
	 */
	return apply_filters( 'g5_get_edit_term_link', $location, $term_id, $taxonomy, $object_type, $bo_table, $action );
}

function g5_delete_object_term_relationships( $object_id, $taxonomies ) {
	$object_id = (int) $object_id;

	if ( !is_array($taxonomies) )
		$taxonomies = array($taxonomies);

	foreach ( (array) $taxonomies as $taxonomy ) {
		$term_ids = g5_get_object_terms( $object_id, $taxonomy, array( 'fields' => 'ids' ) );
        if( empty($term_ids->errors) ){
            $term_ids = array_map( 'intval', $term_ids );
            g5_remove_object_terms( $object_id, $term_ids, $taxonomy );
        }
	}
}

function g5_remove_object_terms( $object_id, $terms, $taxonomy ) {
	global $wpdb;

    $g5 = G5_var::getInstance()->get_options();

	$object_id = (int) $object_id;

	if ( ! taxonomy_exists( $taxonomy ) ) {
		return new WP_Error( 'invalid_taxonomy', __( 'Invalid Taxonomy' ) );
	}

	if ( ! is_array( $terms ) ) {
		$terms = array( $terms );
	}

	$tt_ids = array();

	foreach ( (array) $terms as $term ) {
		if ( ! strlen( trim( $term ) ) ) {
			continue;
		}

		if ( ! $term_info = g5_term_exists( $term, $taxonomy ) ) {
			// Skip if a non-existent term ID is passed.
			if ( is_int( $term ) ) {
				continue;
			}
		}

		if ( is_wp_error( $term_info ) ) {
			return $term_info;
		}

		$tt_ids[] = $term_info['term_taxonomy_id'];
	}

	if ( $tt_ids ) {
		$in_tt_ids = "'" . implode( "', '", $tt_ids ) . "'";

		/**
		 * Fires immediately before an object-term relationship is deleted.
		 *
		 * @since 2.9.0
		 *
		 * @param int   $object_id Object ID.
		 * @param array $tt_ids    An array of term taxonomy IDs.
		 */
		do_action( 'g5_delete_term_relationships', $object_id, $tt_ids );
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$g5['relation_table']} WHERE object_id = %d AND term_taxonomy_id IN ($in_tt_ids)", $object_id ) );

		/**
		 * Fires immediately after an object-term relationship is deleted.
		 *
		 * @since 2.9.0
		 *
		 * @param int   $object_id Object ID.
		 * @param array $tt_ids    An array of term taxonomy IDs.
		 */
		do_action( 'g5_deleted_term_relationships', $object_id, $tt_ids );
		g5_update_term_count( $tt_ids, $taxonomy );

		return (bool) $deleted;
	}

	return false;
}

function g5_delete_term( $term, $taxonomy, $args = array() ) {
	global $wpdb;

    $g5 = G5_var::getInstance()->get_options();

	$term = (int) $term;

	if ( ! $ids = g5_term_exists($term, $taxonomy) )
		return false;
	if ( is_wp_error( $ids ) )
		return $ids;

	$tt_id = $ids['term_taxonomy_id'];

	$defaults = array();

	$args = wp_parse_args($args, $defaults);

	if ( isset( $args['default'] ) ) {
		$default = (int) $args['default'];
		if ( ! term_exists( $default, $taxonomy ) ) {
			unset( $default );
		}
	}

	if ( isset( $args['force_default'] ) ) {
		$force_default = $args['force_default'];
	}

	/**
	 * Fires when deleting a term, before any modifications are made to posts or terms.
	 *
	 * @since 4.1.0
	 *
	 * @param int    $term     Term ID.
	 * @param string $taxonomy Taxonomy Name.
	 */
	do_action( 'g5_pre_delete_term', $term, $taxonomy );

	// Update children to point to new parent
	if ( is_taxonomy_hierarchical($taxonomy) ) {
		$term_obj = g5_get_term($term, $taxonomy);
		if ( is_wp_error( $term_obj ) )
			return $term_obj;
		$parent = $term_obj->parent;

		$edit_ids = $wpdb->get_results( "SELECT term_id, term_taxonomy_id FROM {$g5['taxonomy_table']} WHERE `parent` = " . (int)$term_obj->term_id );
		$edit_tt_ids = wp_list_pluck( $edit_ids, 'term_taxonomy_id' );

		/**
		 * Fires immediately before a term to delete's children are reassigned a parent.
		 *
		 * @since 2.9.0
		 *
		 * @param array $edit_tt_ids An array of term taxonomy IDs for the given term.
		 */
		do_action( 'g5_edit_term_taxonomies', $edit_tt_ids );
		$wpdb->update( $g5['taxonomy_table'], compact( 'parent' ), array( 'parent' => $term_obj->term_id) + compact( 'taxonomy' ) );

		// Clean the cache for all child terms.
		$edit_term_ids = wp_list_pluck( $edit_ids, 'term_id' );
		clean_term_cache( $edit_term_ids, $taxonomy );

		/**
		 * Fires immediately after a term to delete's children are reassigned a parent.
		 *
		 * @since 2.9.0
		 *
		 * @param array $edit_tt_ids An array of term taxonomy IDs for the given term.
		 */
		do_action( 'g5_edited_term_taxonomies', $edit_tt_ids );
	}

	$objects = $wpdb->get_col( $wpdb->prepare( "SELECT object_id FROM {$g5['relation_table']} WHERE term_taxonomy_id = %d", $tt_id ) );

	foreach ( (array) $objects as $object ) {
		$terms = g5_get_object_terms($object, $taxonomy, array('fields' => 'ids', 'orderby' => 'none'));
		if ( 1 == count($terms) && isset($default) ) {
			$terms = array($default);
		} else {
			$terms = array_diff($terms, array($term));
			if (isset($default) && isset($force_default) && $force_default)
				$terms = array_merge($terms, array($default));
		}
		$terms = array_map('intval', $terms);
		g5_set_object_terms($object, $terms, $taxonomy);
	}

	// Clean the relationship caches for all object types using this term
	$tax_object = get_taxonomy( $taxonomy );
	foreach ( $tax_object->object_type as $object_type )
		clean_object_term_cache( $objects, $object_type );

	// Get the object before deletion so we can pass to actions below
	$deleted_term = g5_get_term( $term, $taxonomy );

	/**
	 * Fires immediately before a term taxonomy ID is deleted.
	 *
	 * @since 2.9.0
	 *
	 * @param int $tt_id Term taxonomy ID.
	 */
	do_action( 'g5_delete_term_taxonomy', $tt_id );

    // taxonomy_table 테이블에서 삭제
	$wpdb->delete( $g5['taxonomy_table'], array( 'term_taxonomy_id' => $tt_id ) );

    foreach ( (array) $objects as $object ) {
        if( empty( $object ) ) continue;
        $wpdb->delete( $g5['relation_table'], array( 'object_id' => $object , 'term_taxonomy_id' => $tt_id ) );

        $tmp_terms = g5_get_object_terms( $object, $taxonomy );
        
        $tmp_arr = array();
        foreach( $tmp_terms as $tmp_term ){
            if( empty( $tmp_term ) ) continue;
            $tmp_term = (array) $tmp_term;
            $tmp_arr[] = $tmp_term['term_id'];
        }
        $wpdb->update( $g5['write_table'], array( 'wr_tag' => implode(',' , $tmp_arr) ), array( 'wr_id' => $object ) );
    }
	/**
	 * Fires immediately after a term taxonomy ID is deleted.
	 *
	 * @since 2.9.0
	 *
	 * @param int $tt_id Term taxonomy ID.
	 */
	do_action( 'deleted_term_taxonomy', $tt_id );

	clean_term_cache($term, $taxonomy);

	/**
	 * Fires after a term in a specific taxonomy is deleted.
	 *
	 * The dynamic portion of the hook name, `$taxonomy`, refers to the specific
	 * taxonomy the term belonged to.
	 *
	 * @since 2.3.0
	 *
	 * @param int     $term         Term ID.
	 * @param int     $tt_id        Term taxonomy ID.
	 * @param mixed   $deleted_term Copy of the already-deleted term, in the form specified
	 *                              by the parent function. {@see WP_Error} otherwise.
	 */
	do_action( "delete_$taxonomy", $term, $tt_id, $deleted_term );

	return true;
}

?>