<?php
/**
 * Environment indicators.
 *
 * @package dm-base
 */

// Default to unknown if no environment is set.
defined( 'DM_ENVIRONMENT' ) || define( 'DM_ENVIRONMENT', 'UNKNOWN' );

/**
 * Add our indicator to the admin bar.
 *
 * @param  object $wp_admin_bar WordPress Admin Bar object.
 * @return void
 */
function dmbase_ei_add_adminbar_node( $wp_admin_bar ) {

	$message = apply_filters( 'dmbase_ei_environment_message', strtoupper( DM_ENVIRONMENT ) );

	$wp_admin_bar->add_node(
		[
			'title'  => esc_html( $message ),
			'id'     => 'dmei',
			'parent' => 'top-secondary',
			'meta'   => [
				'class' => 'dmei',
			],
		]
	);
}
add_action( 'admin_bar_menu', 'dmbase_ei_add_adminbar_node', 1 );

/**
 * Insert inline CSS to style our indicator.
 *
 * @return void
 */
function dmbase_ei_insert_css() {

	if ( ! is_user_logged_in() ) {
		return;
	}

	switch ( strtoupper( DM_ENVIRONMENT ) ) {

		case 'DEV':
		case 'LOCAL':
			$colour = '#00cc00';
			break;

		case 'STAGING':
		case 'STAGE':
		case 'TEST':
		case 'UAT':
		case 'PREPROD':
			$colour = '#ff9933';
			break;

		case 'LIVE':
		case 'PROD':
		case 'PRODUCTION':
			$colour = '#ff0000';
			break;

		default:
			$colour = '#666';
			break;
	}
	?>
	<style type="text/css">
		#wpadminbar .dmei .ab-item {
			background-color: <?php echo esc_html( $colour ); ?> !important;
			color: #fff !important;
		}
	</style>
	<?php
}
add_action( 'wp_head', 'dmbase_ei_insert_css' );
add_action( 'admin_head', 'dmbase_ei_insert_css' );

