<?php
/**
 * Record the last login time for users.
 *
 * @package dm-base
 */

/**
 * Add a timestamp to user meta when a user logs in, preserving the previous value.
 *
 * @param  string $login Username of user logging in.
 * @param  object $user  User object of user logging in.
 *
 * @return void
 */
function dmbase_ll_record_login_time( $login, $user ) {

	$previous_login = absint( get_user_meta( $user->ID, '_dm_logins_last', true ) );

	if ( $previous_login ) {
		update_user_meta( $user->ID, '_dm_logins_previous', $previous_login );
	}

	update_user_meta( $user->ID, '_dm_logins_last', time() );
}
add_action( 'wp_login', 'dmbase_ll_record_login_time', 1, 2 );

/**
 * Get the last/previous login times for a given user ID.
 *
 * @param  int $user_id User ID to get login times for.
 *
 * @return array        Array containing last & previous login timestamps.
 */
function dmbase_ll_get_login_times( $user_id ) {
	if ( ! dm_does_user_exist( $user_id ) ) {
		return new WP_Error( 'invalid_user', __( 'The user ID specified does not exist' ) );
	}
	return ( [
		'last'     => absint( get_user_meta( absint( $user_id ), '_dm_logins_last', true ) ),
		'previous' => absint( get_user_meta( absint( $user_id ), '_dm_logins_previous', true ) ),
	] );
}

/**
 * Add our column to the user list.
 *
 * @param array $columns User meta columns.
 *
 * @return array Filtered columns.
 */
function dmbase_ll_add_admin_column( $columns ) {
	$columns['dm_last_login'] = __( 'Last Logins' );
	return $columns;
}
add_filter( 'manage_users_columns', 'dmbase_ll_add_admin_column' );

/**
 * Make our last login column sortable.
 *
 * @param  array $columns Sortable columns.
 * @return array
 */
function dmbase_ll_make_login_times_sortable( $columns ) {
	$columns['dm_last_login'] = 'dm_last_login';
	return $columns;
}
add_filter( 'manage_users_sortable_columns', 'dmbase_ll_make_login_times_sortable' );

/**
 * Populate our user meta column.
 *
 * @param  string $value       Existing column content.
 * @param  string $column_name Column name.
 * @param  int    $user_id     User ID for this row.
 *
 * @return string              Column content.
 */
function dmbase_ll_display_column_data( $value, $column_name, $user_id ) {

	if ( 'dm_last_login' == $column_name ) {
		$login_details = dmbase_ll_get_login_times( $user_id );
		if ( $login_details['last'] ) {
			$content = __( 'Last: ' ) . esc_html( gmdate( 'd/m/Y H:m:s', absint( $login_details['last'] ) ) ); }
		if ( $login_details['previous'] ) {
			$content .= '<br/>' . __( 'Prev: ' ) . esc_html( gmdate( 'd/m/Y H:m:s', absint( $login_details['previous'] ) ) ); }
		if ( ! isset( $content ) ) {
			$content = __( 'No logins recorded.' );
		}
		return $content;
	}
}

add_action( 'manage_users_custom_column', 'dmbase_ll_display_column_data', 10, 3 );
