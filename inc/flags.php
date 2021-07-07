<?php
/**
 * Utility taxonomy registration and helpers.
 *
 * @package dm-base
 */

/**
 * Register a taxonomy to hold our flags.
 *
 * @return void
 */
function register_dm_flags_taxonomy() {

	$labels = [
		'name'                       => _x( 'Flags', 'Flag General Name', 'dm-base' ),
		'singular_name'              => _x( 'Flag', 'Flag Singular Name', 'dm-base' ),
		'menu_name'                  => __( 'Flags', 'dm-base' ),
		'all_items'                  => __( 'All Flags', 'dm-base' ),
		'parent_item'                => __( 'Parent Flag', 'dm-base' ),
		'parent_item_colon'          => __( 'Parent Flag:', 'dm-base' ),
		'new_item_name'              => __( 'New Flag Name', 'dm-base' ),
		'add_new_item'               => __( 'Add New Flag', 'dm-base' ),
		'edit_item'                  => __( 'Edit Flag', 'dm-base' ),
		'update_item'                => __( 'Update Flag', 'dm-base' ),
		'view_item'                  => __( 'View Flag', 'dm-base' ),
		'separate_items_with_commas' => __( 'Separate flags with commas', 'dm-base' ),
		'add_or_remove_items'        => __( 'Add or remove items', 'dm-base' ),
		'choose_from_most_used'      => __( 'Choose from the most used', 'dm-base' ),
		'popular_items'              => __( 'Popular Flags', 'dm-base' ),
		'search_items'               => __( 'Search Flags', 'dm-base' ),
		'not_found'                  => __( 'Not Found', 'dm-base' ),
		'no_terms'                   => __( 'No flags', 'dm-base' ),
		'items_list'                 => __( 'Flags list', 'dm-base' ),
		'items_list_navigation'      => __( 'Flags list navigation', 'dm-base' ),
	];
	$args = [
		'labels'             => $labels,
		'hierarchical'       => true,
		'public'             => false,
		'show_ui'            => dm_is_developer(),
		'show_admin_column'  => dm_is_developer(),
		'show_in_rest'       => dm_is_developer(),
		'show_in_quick_edit' => dm_is_developer(),
		'show_in_nav_menus'  => false,
		'show_tagcloud'      => false,
		'rewrite'            => false,
	];
	register_taxonomy( 'dm_flags', [ 'post' ], $args );

}
add_action( 'init', 'register_dm_flags_taxonomy', 0 );

/**
 * Register our taxonomy for all post types.
 *
 * @return void
 */
function dm_register_utility_tax_with_all_post_types() {
	foreach ( get_post_types() as $post_type ) {
		register_taxonomy_for_object_type( 'dm_flags', $post_type );
	}
}
add_action( 'init', 'dm_register_utility_tax_with_all_post_types', 99999 );

/**
 * Create taxonomy terms within the dm_flags taxonomy if they have changed.
 *
 * @return bool
 */
function dm_maybe_populate_flags_taxonomy() {

	if ( ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) ) {
		return false;
	}

	// Filter dm_flags_taxonomy_terms to add project specific flags.
	$terms = apply_filters(
		'dm_flags_taxonomy_terms',
		[
			'general' => [ '' ],
		]
	);

	// Check if our array of terms have changed, if so we don't need to update them.
	$terms_sum = md5( json_encode( $terms ) );
	$current_sum = get_option( 'dm_flags_checksum' );

	if ( $terms_sum === $current_sum ) {
		return false;
	}

	// Create parent terms if they don't exist.
	foreach ( $terms as $term => $childterms ) {
		if ( ! term_exists( $term, 'dm_flags' ) ) {
			$inserted_term = wp_insert_term(
				$term,
				'dm_flags',
				[
					'slug'        => $term,
					'description' => 'Parent for ' . $term . ' flags',
				]
			);
			if ( is_wp_error( $inserted_term ) ) {
				dm_log( [ 'dm_maybe_populate_flags_taxonomy failed to insert term', [ $term, $inserted_term ] ] );
				continue;
			}
			update_term_meta( $inserted_term['term_id'], 'protected', true );
		}
		// Get the parent term ID so we can create child terms underneath it.
		$parent = get_term_by( 'slug', $term, 'dm_flags', ARRAY_A );
		$parent_id = $parent['term_id'];

		// For this parent term, create any child terms that don't exist.
		foreach ( $childterms as $child_term => $child_description ) {
			if ( empty( $child_term ) ) {
				continue;
			}
			if ( ! term_exists( $child_term, 'dm_flags' ) ) {
				$inserted_term = wp_insert_term(
					$child_term,
					'dm_flags',
					[
						'slug'        => $child_term,
						'description' => $child_description,
						'parent'      => $parent_id,
					]
				);
				if ( is_wp_error( $inserted_term ) ) {
					dm_log( [ 'dm_maybe_populate_flags_taxonomy failed to insert term 2', [ $term, $inserted_term ] ] );
					continue;
				}
				update_term_meta( $inserted_term['term_id'], 'protected', true );
			}
		}
	}
	update_option( 'dm_flags_checksum', $terms_sum );
	dm_log( 'Updated dm_flags taxonomy terms.' );
	return true;

}
add_action( 'init', 'dm_maybe_populate_flags_taxonomy' );

/**
 * Set a flag for a object; removes previous flag of that type - except for the general
 * type where multiple terms may exist.
 *
 * @param  int    $post_id    Post to modify flags for.
 * @param  string $flag_type  Parent term of flag.
 * @param  string $flag_value Flag name to set.
 * @return bool|WP_Error
 */
function dm_set_flag( $post_id, $flag_type, $flag_value ) {

	if ( ! dm_post_id_exists( $post_id ) ) {
		return new WP_Error( 'dm_set_flag:non-existent post id', $post_id );
	}

	if ( dm_has_flag( $post_id, $flag_type, $flag_value ) ) {
		return true;
	}

	// Get the ID of the parent taxonomy term (flag_type).
	$type_term = get_term_by( 'slug', $flag_type, 'dm_flags', ARRAY_A );
	$parent_term_id = $type_term['term_id'];

	// Trying to set an non-existent term? Return.
	if ( ! term_exists( $flag_value, 'dm_flags', $parent_term_id ) ) {
		return new WP_Error( 'dm_set_flag:non-existent flag', $flag_value );
	}

	// Remove any previous terms of this type, unless we're in the general type .
	if ( 'general' !== $flag_type ) {
		// Find existing terms of this type and remove them.
		$existing = get_terms(
			'dm_flags',
			[
				'child_of' => $parent_term_id,
			]
		);
		foreach ( $existing as $existing_term ) {
			wp_remove_object_terms( $post_id, $existing_term->term_id, 'dm_flags' );
		}
	}

	// Set the new term for the object.
	wp_set_object_terms( $post_id, $flag_value, 'dm_flags', true );
	do_action( 'dm_flag_set', $post_id, $flag_type, $flag_value );
	do_action( 'dm_flag_set_' . $flag_type, $post_id, $flag_value );
	return true;

}

/**
 * Get a flag for a object of a specified type.
 *
 * @param  int    $post_id Object ID to fetch flag for.
 * @param  string $flag_type Parent term of flag.
 * @return string            Term slug for flag.
 */
function dm_get_flag( $post_id, $flag_type ) {

	if ( ! dm_post_id_exists( $post_id ) ) {
		return new WP_Error( 'dm_set_flag:non-existent post id', $post_id );
	}

	// Get the ID of the parent taxonomy term (flag_type).
	$type_term = get_term_by( 'slug', $flag_type, 'dm_flags', ARRAY_A );
	$parent_term_id = $type_term['term_id'];

	$flags = get_the_terms( $post_id, 'dm_flags' );

	// No flags set.
	if ( empty( $flags ) ) {
		return [];
	}

	foreach ( $flags as $this_flag ) {
		if ( $this_flag->parent === $parent_term_id ) {
			$terms[] = $this_flag->slug;
		}
	}

	// If there's nothing, return an empty array.
	if ( ! isset( $terms ) || empty( $terms ) ) {
		return [];
	}

	// For general terms, return an array as there may be multiple options.
	if ( 'general' === $flag_type ) {
		return $terms;
	}

	// Just return a single item for other flag types.
	return $terms[0];
}

/**
 * Helper to remove flag.
 *
 * @param  int    $post_id     Post ID.
 * @param  string $flag_type   Parent term in taxonomy.
 * @param  string $flag_value  Flag value to check.
 *
 * @return bool|WP_Error
 */
function dm_remove_flag( $post_id, $flag_type, $flag_value ) {
	if ( ! dm_post_id_exists( $post_id ) ) {
		return new WP_Error( 'dm_set_flag:non-existent post id', $post_id );
	}

	if ( dm_has_flag( $post_id, $flag_type, $flag_value ) ) {
		return false;
	}

	return wp_remove_object_terms( $post_id, $flag_value, 'dm_flags' );
	do_action( 'dm_flag_removed', $post_id, $flag_type, $flag_value );
	do_action( 'dm_flag_removed_' . $flag_type, $post_id, $flag_value );
}

/**
 * Does a given object have a given flag for a given flag type?
 *
 * @param  int    $post_id     Post ID.
 * @param  string $flag_type   Parent term in taxonomy.
 * @param  string $flag_value  Flag value to check.
 * @return bool
 */
function dm_has_flag( $post_id, $flag_type, $flag_value ) {

	if ( ! dm_post_id_exists( $post_id ) ) {
		return new WP_Error( 'dm_set_flag:non-existent post id', $post_id );
	}

	$current_flag = dm_get_flag( $post_id, $flag_type );

	if ( 'general' === $flag_type ) {
		if ( in_array( $flag_value, $current_flag ) ) {
			return true;
		}
	}

	// Otherwise, straight compare.
	if ( $flag_value === $current_flag ) {
		return true;
	}

	return false;
}

/**
 * Get all the child terms from a specified term in the flags taxonomy
 * and return as an array of slugs/descriptions.
 *
 * @param  string $type Parent term in dm_flags taxonomy.
 * @param  bool   $slugs_only If true, function return only slugs.
 * @return array
 */
function dm_get_all_flags_by_type( $type, $slugs_only = false ) {
	// Find the parent's term ID.
	$type_term = get_term_by( 'slug', $type, 'dm_flags', ARRAY_A );
	$type_term_id = $type_term['term_id'];
	// Get the child terms.
	$flag_ids = get_term_children( $type_term_id, 'dm_flags' );
	// Use the term data to create an array of slugs/descriptions.
	$flags_for_type = false;
	foreach ( $flag_ids as $flag ) {
		$flag_info = get_term( $flag );
		if ( $slugs_only ) {
			$flags_for_type[] = $flag_info->slug;
		} else {
			$flags_for_type[ $flag_info->slug ] = $flag_info->description;
		}
	}
	return $flags_for_type;
}

/**
 * Prevent deletion of terms which have the 'protected' term meta set.
 *
 * @param  string $required_cap Required capability.
 * @param  string $cap          Capability requested.
 * @param  int    $user_id      User ID of user being checked.
 * @param  array  $args         Additional data.
 * @return string
 */
function dm_flags_maybe_prevent_term_deletion( $required_cap, $cap, $user_id, $args ) {
	if ( 'delete_term' !== $cap || 'edit_term' !== $cap ) {
		return $required_cap;
	}

	if ( get_term_meta( $args[0], 'protected', false ) ) {
		$required_cap[] = 'do_not_allow';
	}

	return $required_cap;
}
add_filter( 'map_meta_cap', 'dm_flags_maybe_prevent_term_deletion', 10, 4 );

