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
		add_action( 'plugins_loaded', 'dmbase_do_setup', 1 );
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
 *
 * @return void
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

/**
 * Remove the dm_developer role from the UI as it is only used internally.
 *
 * @param  array $roles User roles.
 * @return array
 */
function dmbase_hide_developer_role( $roles ) {
		unset( $roles['dm_developer'] );
		return $roles;
}
add_filter( 'editable_roles', 'dmbase_hide_developer_role', 10, 1 );

/**
 * If the SpinupWP plugin is active, it is likely it will have been activated by DM_Base_Enable_Plugins
 * and the activation hook won't have run; so we'll run it here.
 *
 * @return bool
 */
function dmbase_maybe_run_spinupwp_install() {
	// Check if the plugin is active.
	if ( ! is_plugin_active( 'spinupwp/spinupwp.php' ) ) {
		return false;
	}

	// Check if we are on a SpinupWP environment.
	if ( getenv( 'SPINUPWP_SITE' ) ) {
		return false;
	}

	dm_log( 'dmbase_maybe_run_spinupwp_install: running SpinupWP setup.' );
	$spinupwp = new SpinupWp\Plugin( 'dm-base' );
	$spinupwp->install();
	return true;
}

add_action( 'dmbase_setup', 'dmbase_maybe_run_spinupwp_install', 20 );
