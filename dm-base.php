<?php
/**
 * Plugin Name: DM Base
 * Plugin URI: https://github.com/DeliciousMedia/DM-Base
 * Description: Base functionality, helpers and modifications to WordPress for Delicious Media projects.
 * Version: 1.3.2
 * Author: Delicious Media Limited
 * Author URI: https://www.deliciousmedia.co.uk/
 * Text Domain: dm-base
 * License: GPLv3 or later
 *
 * @package dm-base
 **/

define( 'DMBASE_SETUP_VERSION', 3 );

/**
 * Set our default settings.
 *
 * You should change these at a per-project level in wp-config.php.
 * Using the defined || define pattern there will allow you to override them in local-config.php if needed.
 */
defined( 'DM_DISABLE_COMMENTS' ) || define( 'DM_DISABLE_COMMENTS', true );
defined( 'DM_DISABLE_SEARCH' ) || define( 'DM_DISABLE_SEARCH', false );
defined( 'DM_DISABLE_EMOJIS' ) || define( 'DM_DISABLE_EMOJIS', true );
defined( 'DM_DISABLE_REST_ANON' ) || define( 'DM_DISABLE_REST_ANON', true );
defined( 'DM_DISABLE_RSS' ) || define( 'DM_DISABLE_RSS', true );
defined( 'DM_LASTLOGIN' ) || define( 'DM_LASTLOGIN', true );
defined( 'DM_EI' ) || define( 'DM_EI', true );
defined( 'DM_PREVENT_USER_ENUM' ) || define( 'DM_PREVENT_USER_ENUM', true );
defined( 'DM_ACF_SYNC' ) || define( 'DM_ACF_SYNC', true );
defined( 'DM_FLAGS_TAX' ) || define( 'DM_FLAGS_TAX', true );
defined( 'DM_REMOVE_YOAST_ADS' ) || define( 'DM_REMOVE_YOAST_ADS', true );
defined( 'DM_HIDE_ACF_UI' ) || define( 'DM_HIDE_ACF_UI', true );

require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/logging.php';
require_once __DIR__ . '/inc/setup.php';
require_once __DIR__ . '/inc/third-party.php';

if ( ! defined( 'DM_ENVIRONMENT' ) ) {
	new DM_AdminNotice( 'Warning: the DM_ENVIRONMENT constant was not set, defaulting to LIVE. You should set this in your local-config.php', 'warning', false );
	define( 'DM_ENVIRONMENT', 'LIVE' );
}

require_once __DIR__ . '/inc/modifications.php';
require_once __DIR__ . '/inc/plugin-control.php';

if ( true === DM_LASTLOGIN ) {
	require_once __DIR__ . '/inc/last-login.php';
}

if ( true === DM_EI ) {
	require_once __DIR__ . '/inc/ei.php';
}

if ( true === DM_ACF_SYNC ) {
	require_once __DIR__ . '/inc/acfsync.php';
}

if ( true === DM_FLAGS_TAX ) {
	require_once __DIR__ . '/inc/flags.php';
}
