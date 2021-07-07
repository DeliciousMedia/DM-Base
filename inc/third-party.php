<?php
/**
 * Modifications to third party plugins.
 *
 * @package dm-base
 */

// Prevent GravityForms from tracking form views.
add_filter( 'gform_disable_view_counter', '__return_true' );

/**
 * Shift the Yoast metabox to the bottom of post screens.
 */
add_filter(
	'wpseo_metabox_prio',
	function() {
		return 'low';
	}
);

/**
 * Remove aggressive advertising inserted into wp-admin by Yoast plugin when deleting posts or terms.
 */
if ( defined( 'DM_REMOVE_YOAST_ADS' ) && DM_REMOVE_YOAST_ADS ) {

	// Don't need to remove this is the "Premium" version is installed.
	if ( class_exists( 'WPSEO_Utils' ) && WPSEO_Utils::is_yoast_seo_premium() ) {
		return;
	}

	// Sadly Yoast won't provide a clean way of doing this, so....
	add_action(
		'init',
		function() {

			dm_remove_filters_for_anonymous_class( 'wp_trash_post', 'WPSEO_Slug_Change_Watcher', 'detect_post_trash', 10 );
			dm_remove_filters_for_anonymous_class( 'before_delete_post', 'WPSEO_Slug_Change_Watcher', 'detect_post_delete', 10 );
			dm_remove_filters_for_anonymous_class( 'delete_term_taxonomy', 'WPSEO_Slug_Change_Watcher', 'detect_term_delete', 10 );
			dm_remove_filters_for_anonymous_class( 'admin_enqueue_scripts', 'WPSEO_Slug_Change_Watcher', 'enqueue_assets', 10 );

		}
	);

	add_filter( 'wpseo_enable_notification_post_trash', '__return_false' );
	add_filter( 'wpseo_enable_notification_post_slug_change', '__return_false' );
	add_filter( 'wpseo_enable_notification_term_delete', '__return_false' );
	add_filter( 'wpseo_enable_notification_term_slug_change', '__return_false' );

}

/**
 * Delete GravityForms entries upon submission to avoid retaining data we don't need.
 */
if ( defined( 'DM_GFORM_DELETE' ) && DM_GFORM_DELETE ) {
	add_action(
		'gform_after_submission',
		function( $entry, $form ) {
			GFAPI::delete_entry( $entry['id'] );
		},
		99,
		2
	);
}

/**
 * Don't show WooCommerce Extension suggestions.
 *
 * @link: https://woocommerce.wordpress.com/2019/04/03/extension-suggestions-in-3-6/
 */
add_filter( 'woocommerce_allow_marketplace_suggestions', '__return_false' );

/**
 * Hide ACF from admin area unless we have the dm_developer role.
 */
if ( defined( 'DM_HIDE_ACF_UI' ) && DM_HIDE_ACF_UI ) {
	add_filter(
		'acf/settings/show_admin',
		function() {
			$current_user = wp_get_current_user();
			if ( in_array( 'dm_developer', (array) $current_user->roles ) ) {
				return true;
			}
			return false;
		}
	);
}

