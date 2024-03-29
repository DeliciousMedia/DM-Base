<?php
/**
 * ACF field synchronisation.
 *
 * @package dm-base
 */

// If we're on a site where we only have control of the child theme,
// use that rather than the parent as the default location.
if ( defined( 'DMACFS_CHILD_ONLY' ) && true === DMACFS_CHILD_ONLY ) {
	$dmacfs_theme_dir = get_stylesheet_directory();
} else {
	$dmacfs_theme_dir = get_template_directory();
}

// By default we store the field data in our theme folder.
defined( 'DMACFS_DATA_DIR' ) || define( 'DMACFS_DATA_DIR', $dmacfs_theme_dir . '/acf-field-data' );

/**
 * Create our directory if needed.
 */
function dmbase_acfsync_directory_setup() {
	if ( ! file_exists( DMACFS_DATA_DIR ) ) {
		mkdir( DMACFS_DATA_DIR, 0740, true );
	}
}
add_action( 'dmbase_setup', 'dmbase_acfsync_directory_setup' );

/**
 * Set the path for ACF field groups and settings to be saved in.
 *
 * @param  string $path Save paths.
 *
 * @return string
 */
function dmacfs_json_save_point( $path ) {
	return DMACFS_DATA_DIR;
}
add_filter( 'acf/settings/save_json', 'dmacfs_json_save_point' );

/**
 * Set the paths for ACF field group settings to be loaded from.
 *
 * @param  array $paths Load paths.
 *
 * @return array
 */
function dmacfs_json_load_point( $paths ) {
	unset( $paths[0] );
	$paths[] = DMACFS_DATA_DIR;

	// If we're on a site where we only control the child theme, return now.
	if ( defined( 'DMACFS_CHILD_ONLY' ) && true === DMACFS_CHILD_ONLY ) {
		return $paths;
	}

	// If this is a child theme (and we control the parent),
	// add the child theme path as well as the parent theme path.
	if ( is_child_theme() ) {
		$paths[] = get_stylesheet_directory() . '/acf-field-data';
	}

	return $paths;
}
add_filter( 'acf/settings/load_json', 'dmacfs_json_load_point' );

/**
 *
 * Function that will automatically remove ACF field groups via JSON file update.
 *
 * @return void
 */
function dmacfs_remove_old_fields() {

	$groups = acf_get_field_groups();
	if ( empty( $groups ) ) {
		return;
	}

	$delete = [];
	foreach ( $groups as $group ) {
		$found = false;

		$json_file = rtrim( DMACFS_DATA_DIR, '/' ) . '/' . $group['key'] . '.json';
		$json_file_child = '';

		// If this is a child theme, check for file in
		// the child theme path as well as the parent theme path.
		if ( is_child_theme() ) {
			$json_file_child = rtrim( get_stylesheet_directory() . '/acf-field-data', '/' ) . '/' . $group['key'] . '.json';
		}

		if ( is_file( $json_file ) || is_file( $json_file_child ) ) {
			$found = true;

			break;
		}

		if ( ! $found ) {
			$delete[] = $group['key'];
		}
	}
	if ( ! empty( $delete ) ) {
		foreach ( $delete as $group_key ) {
			acf_delete_field_group( $group_key );
		}
	}
}

/**
 * Add or update fields and field groups which are present in the JSON files but not in the database.
 *
 * @return void
 */
function dmacfs_add_update_fields() {

	$groups = acf_get_field_groups();
	if ( empty( $groups ) ) {
		return;
	}

	// Find JSON field groups which have not yet been imported.
	$to_sync   = [];
	foreach ( $groups as $group ) {
		$local      = acf_maybe_get( $group, 'local', false );
		$modified   = acf_maybe_get( $group, 'modified', 0 );
		$private    = acf_maybe_get( $group, 'private', false );

		// Fields which are private, stored in the database or defined in PHP.
		if ( 'json' !== $local || $private ) {
			// do nothing.
		} elseif ( ! $group['ID'] ) {
			$to_sync[ $group['key'] ] = $group;
		} elseif ( $modified && $modified > get_post_modified_time( 'U', true, $group['ID'], true ) ) {
			$to_sync[ $group['key'] ]  = $group;
		}
	}

	if ( empty( $to_sync ) ) {
		return;
	}

	foreach ( $to_sync as $key => $group ) {
		// Append modified fields.
		if ( acf_have_local_fields( $key ) ) {
			$group['fields'] = acf_get_fields( $key );
		}

		// Import new groups.
		$field_group = acf_import_field_group( $group );
	}
}

/**
 * On non-development environments trigger an import of ACF field settings if the value of the
 * DMACFS_DATA_VERSION constant is different to that in the options table.
 *
 * @return bool
 */
function dmacfs_trigger_update() {

	if ( ( defined( 'DOING_AJAX' ) && true === DOING_AJAX ) || ( defined( 'WP_INSTALLING' ) && true === WP_INSTALLING ) || dm_is_dev() ) {
		return false;
	}

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return false;
	}

	// Allow auto-updates to be disabled.
	if ( defined( 'DMACFS_SKIP_AUTOMATIC_UPDATES' ) && true === DMACFS_SKIP_AUTOMATIC_UPDATES ) {
		return false;
	}

	if ( ! defined( 'DMACFS_DATA_VERSION' ) ) {
		return false;
	}

	return dmacfs_maybe_update_acf_fields();
}
add_action( 'acf/init', 'dmacfs_trigger_update' );


/**
 * Update the ACF fields if the version string in the DB differs from the DMACFS_DATA_VERSION constant.
 *
 * @return bool
 */
function dmacfs_maybe_update_acf_fields() {
	$acf_field_version = intval( get_option( 'dmacfs_active_version', 'not_setup' ) );

	if ( ! defined( 'DMACFS_DATA_VERSION' ) ) {
		return false;
	}

	if ( DMACFS_DATA_VERSION !== $acf_field_version ) {
		dmacfs_remove_old_fields();
		dmacfs_add_update_fields();
		update_option( 'dmacfs_active_version', (int) DMACFS_DATA_VERSION );
		return true;
	}
	return false;
}


/**
 * Updates the ACF fields in the database from the JSON files, if an update is required.
 */
function dmacfs_update_acf_fields_command() {
	$result = dmacfs_maybe_update_acf_fields();
	if ( true === $result ) {
		WP_CLI::success( 'ACF fields updated' );
	} else {
		WP_CLI::success( 'ACF fields not updated, DMACFS_DATA_VERSION is not set or already constant matches version in database.' );
	}
}
if ( class_exists( 'WP_CLI' ) ) {
	WP_CLI::add_command( 'dm acfsync', 'dmacfs_update_acf_fields_command' );
}
