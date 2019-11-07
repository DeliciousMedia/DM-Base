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
		if ( defined( 'DM_ENVIRONMENT' ) && 'DEV' == DM_ENVIRONMENT ) {
			return true;
		}
		return false;
	}
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

/**
 * Return the user object for the Delicious Media user.
 *
 * @return bool|object
 */
function get_dm_user() {
	return get_user_by( 'login', 'deliciousmedia' );
}


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
