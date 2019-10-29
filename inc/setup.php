<?php
/**
 * Helpers & utility functions.
 *
 * @package dm-base
 */

/**
 * Check to see if the DMBASE_SETUP_VERSION constant matches the version stored in our database.
 */
function dmbase_maybe_trigger_setup() {
	if ( DMBASE_SETUP_VERSION !== intval( get_option( 'dmbase_setup_version', 0 ) ) ) {
		add_action( 'init', 'dmbase_do_setup', 1 );
	}
}
add_action( 'plugins_loaded', 'dmbase_maybe_trigger_setup', 999 );

/**
 * Provide a point for setup function to hook into.
 *
 * @return void.
 */
function dmbase_do_setup() {
	dm_log( 'dmbase_do_setup: updating to version ' . DMBASE_SETUP_VERSION );
	do_action( 'dmbase_setup' );
	update_option( 'dmbase_setup_version', DMBASE_SETUP_VERSION );
}

/**
 * Setup the dm_developer role, and add our user.
 */
function dmbase_setup_developer_role() {

	$role_name = 'dm_developer';

	if ( null === get_role( $role_name ) ) {
		add_role( $role_name, 'Developer', [] );
		dm_log( 'dmbase_setup_developer_role: added dm_developer role' );
	}

	$dm_user_id = get_dm_user();

	if ( false !== $dm_user_id ) {
		$dm_user_id->add_role( $role_name );
	} else {
		dm_log( 'dmbase_setup_developer_role: DM user not found.' );
	}

}
add_action( 'dmbase_setup', 'dmbase_setup_developer_role', 10 );
