<?php
/**
 * Logging & error reporting.
 *
 * Define DM_LOGPATH in your local-config.php
 *
 * @package dm-base
 */

/**
 * Log an event in the application logs with fallback to error_log.
 *
 * @param  mixed  $message        message text, accepts string, array or object.
 * @param  string $category       category of log entry; determines which file is written to.
 */
function dm_log( $message, $category = 'general' ) {

	$timestamp = new DateTime();

	$elements['timestamp']   = $timestamp->format( 'd/m/y H:i:s' );
	$elements['remote_addr'] = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );

	if ( is_multisite() ) {
		$elements['blog_id'] = absint( get_current_blog_id() );
	}
	$elements['user_id'] = get_current_user_id();

	if ( is_array( $message ) || is_object( $message ) ) {
		$elements['message'] = print_r( $message, true );
	} else {
		$elements['message'] = sanitize_text_field( $message );
	}

	apply_filters( 'dm_log.elements', $elements );

	$file_name = sanitize_file_name( 'app_' . $category . '.log' );

	if ( defined( 'DM_LOGPATH' ) && is_writable( trailingslashit( DM_LOGPATH ) ) ) {
		file_put_contents( trailingslashit( DM_LOGPATH ) . $file_name, implode( '|', array_values( $elements ) ) . PHP_EOL, FILE_APPEND );
	} else {
		error_log( 'dmlog_' . $category . ': ' . $elements['message'] );
	}
}

/**
 * Handle fatal errors; display a friendly message and record details in the log file with a reference.
 *
 * @param string $message_text  A descriptive, user-friendly, error message.
 * @param mixed  $log_details   An optional string or array containing extra details for the log file.
 */
function dm_fatal_error( $message_text, $log_details = '' ) {

	$error_id = get_current_blog_id() . ':' . uniqid();

	$full_message = '<h1>Something has gone wrong...</h1>';
	$full_message .= '<p>There has been a problem and we aren\'t able to display this page.<p>';
	$full_message .= '<p><strong>%s</strong><p>';
	$full_message .= '<h3>What now?</h3>';
	$full_message .= '<p>You can try again by pressing the back button in your browser. If you see another error, please let us know. The ErrorID below will help us to find more details about this error.<p>';
	$full_message .= '<p>ErrorID: %s</p>';

	$full_message = sprintf( apply_filters( 'dm_fatal_error.message_template', $full_message ), $message_text, $error_id );

	do_action( 'dm_fatal_error.triggered', $error_id, $message_text, $log_details );

	dm_log( 'Fatal Error, ErrorID: (' . $error_id . ') Message: ' . $message_text );
	if ( ! empty( $log_details ) ) {
		if ( is_array( $log_details ) || is_object( $log_details ) ) {
			$log_details = print_r( $log_details, true );
		}
		dm_log( 'Fatal Error, ErrorID: (' . $error_id . ') Details: ' . $log_details );
	}
	wp_die( wp_kses_post( $full_message ), esc_html( 'Error.' ) );
}

/**
 * Admin notice helper.
 */
class DM_AdminNotice {

	public $_message_type;
	public $_message;
	public $_dismissible;

	/**
	 * Construct.
	 *
	 * @param string  $message      Message to display in the admin notice. May contain HTML.
	 * @param string  $message_type Message type, converted into WordPress's "notice-TYPE" class, choose from: error, warning, success, info.
	 * @param boolean $dismissible  Add the is-dismissible class?
	 */
	function __construct( $message, $message_type = 'success', $dismissible = true ) {
		$this->_message_type = $message_type;
		$this->_message = $message;
		$this->_dismissible = ( true === $dismissible ? ' is-dismissible' : '' );

		add_action( 'admin_notices', [ $this, 'render' ] );
	}

	/**
	 * Render an admin notice.
	 *
	 * @return void.
	 */
	function render() {
		printf( '<div class="%s"><p>%s</p></div>', esc_attr( 'notice notice-' . $this->_message_type . $this->_dismissible ), esc_html( $this->_message ) );
	}
}
