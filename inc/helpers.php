<?php
/**
 * Helpers & utility functions.
 *
 * @package dm-base
 */

if ( ! function_exists( 'dm_is_dev' ) ) {
	/**
	 * Are we running under a Delicious Media development environment?
	 *
	 * @return bool
	 */
	function dm_is_dev() {
		if ( defined( 'DM_ENVIRONMENT' ) && 'DEV' === DM_ENVIRONMENT ) {
			return true;
		}
		return false;
	}
}

/**
 * Does the current user have the dm_developer role?
 *
 * @return bool
 */
function dm_is_developer() {
	$current_user = wp_get_current_user();
	if ( in_array( 'dm_developer', (array) $current_user->roles ) ) {
		return true;
	}
	return false;
}

if ( ! function_exists( 'dm_is_blog_page' ) ) {
	/**
	 * Are we on a blog page?
	 *
	 * @return bool
	 */
	function dm_is_blog_page() {
		global $post;
		$posttype = get_post_type( $post );
		return ( ( ( is_archive() ) || ( is_author() ) || ( is_category() ) || ( is_home() ) || ( is_single() ) || ( is_tag() ) ) && ( 'post' == $posttype ) ) ? true : false;
	}
}

if ( ! function_exists( 'dm_does_user_exist' ) ) {
	/**
	 * Check if a given user ID exists.
	 *
	 * @param  int $user_id User ID to test.
	 *
	 * @return bool
	 */
	function dm_does_user_exist( $user_id ) {
		return (bool) get_user_by( 'id', absint( $user_id ) );
	}
}

if ( ! function_exists( 'get_dm_user' ) ) {
	/**
	 * Return the user object for the Delicious Media user.
	 *
	 * @return bool|object
	 */
	function get_dm_user() {
		return get_user_by( 'login', 'deliciousmedia' );
	}
}

if ( ! function_exists( 'dm_post_id_exists' ) ) {
	/**
	 * Helper, does a post id exist?
	 *
	 * @param  id $post_id Post ID to check.
	 *
	 * @return bool
	 */
	function dm_post_id_exists( $post_id ) {
		return is_string( get_post_status( $post_id ) );
	}
}

if ( ! function_exists( 'dm_remove_filters_for_anonymous_class' ) ) {
	/**
	 * Allow to remove method for an hook when, it's a class method used and class don't have variable, but you know the class name :)
	 *
	 * @param  string  $hook_name   Target hook.
	 * @param  string  $class_name  Name of class.
	 * @param  string  $method_name Name of method.
	 * @param  integer $priority    Priority assigned to filter/action.
	 *
	 * @return bool
	 * @link https://github.com/herewithme/wp-filters-extras/
	 */
	function dm_remove_filters_for_anonymous_class( $hook_name = '', $class_name = '', $method_name = '', $priority = 0 ) {
		global $wp_filter;
		// Take only filters on right hook name and priority.
		if ( ! isset( $wp_filter[ $hook_name ][ $priority ] ) || ! is_array( $wp_filter[ $hook_name ][ $priority ] ) ) {
			return false;
		}
		// Loop on filters registered.
		foreach ( (array) $wp_filter[ $hook_name ][ $priority ] as $unique_id => $filter_array ) {
			// Test if filter is an array ! (always for class/method).
			if ( isset( $filter_array['function'] ) && is_array( $filter_array['function'] ) ) {
				// Test if object is a class, class and method is equal to param !
				if ( is_object( $filter_array['function'][0] ) && get_class( $filter_array['function'][0] ) && get_class( $filter_array['function'][0] ) == $class_name && $filter_array['function'][1] == $method_name ) {
					// Test for WordPress >= 4.7 WP_Hook class (https://make.wordpress.org/core/2016/09/08/wp_hook-next-generation-actions-and-filters/) .
					if ( is_a( $wp_filter[ $hook_name ], 'WP_Hook' ) ) {
						unset( $wp_filter[ $hook_name ]->callbacks[ $priority ][ $unique_id ] );
					} else {
						unset( $wp_filter[ $hook_name ][ $priority ][ $unique_id ] );
					}
				}
			}
		}
		return false;
	}
}

/**
 * Replacement for get_page_by_title() which is deprecated as of 6.2.
 *
 * @param string       $page_title      Page title.
 * @param string       $output     Optional. The required return type. One of OBJECT, ARRAY_A, or ARRAY_N, which
 *                                 correspond to a WP_Post object, an associative array, or a numeric array,
 *                                 respectively. Default OBJECT.
 * @param string|array $post_type  Optional. Post type or array of post types. Default 'page'.
 * @return WP_Post|array|null WP_Post (or array) on success, or null on failure.
 *
 * @link   https://make.wordpress.org/core/2023/03/06/get_page_by_title-deprecated/
 */
function dm_get_page_by_title( $page_title, $output = OBJECT, $post_type = 'page' ) {

	$query = new WP_Query(
		[
			'post_type'              => $post_type,
			'title'                  => $page_title,
			'post_status'            => 'all',
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
			'orderby'                => 'post_date ID',
			'order'                  => 'ASC',
		]
	);

	if ( ARRAY_A === $output ) {
		return $query->post->to_array();
	} elseif ( ARRAY_N === $output ) {
		return array_values( $query->post->to_array() );
	}

	if ( ! empty( $query->post ) ) {
		return $query->post;
	}

	return null;

}
