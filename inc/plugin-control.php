<?php
/**
 * Selectively enable/disable plugins based on environment or conditionals.
 *
 * Set rules for this in the site-config mu-plugin.
 *
 * @package dm-base
 */


class DM_Base_Disable_Plugins {
	protected $plugins = [];
	protected $message = 'Disabled in this environment';
	/**
	 * Sets up the options filter, and optionally handles an array of plugins to disable
	 *
	 * @param array $disables Optional array of plugin filenames to disable.
	 */
	public function __construct( array $plugins, $message = null ) {
		// Handle what was passed in
		foreach ( $plugins as $plugin ) {
				$this->choose( $plugin );
		}
		if ( ! is_null( $message ) ) {
			$this->message = $message;
		}
		// Add the filter.
		add_filter( 'option_active_plugins', [ $this, 'alter' ] );
	}
	/**
	 * Adds a filename to the list of plugins to disable.
	 */
	public function choose( $file ) {
		$this->plugins[] = $file;
		add_filter( 'plugin_action_links_' . plugin_basename( $file ), [ $this, 'change_action_links' ] );
	}
	function change_action_links( $actions ) {
		unset( $actions['activate'] );
		unset( $actions['delete'] );
		$actions['disabled'] = '<i>' . esc_html( $this->message ) . '</i>';
		return $actions;
	}
	/**
	 * Hooks in to the option_active_plugins filter and does the disabling.
	 *
	 * @param array $plugins WP-provided list of plugin filenames.
	 * @return array The filtered array of plugin filenames.
	 */
	public function alter( $plugins ) {
		if ( count( $this->plugins ) ) {
			foreach ( (array) $this->plugins as $plugin ) {
				$key = array_search( $plugin, $plugins );
				if ( false !== $key ) {
					unset( $plugins[ $key ] );
				}
			}
		}
		return $plugins;
	}
}
class DM_Base_Enable_Plugins extends DM_Base_Disable_Plugins {
	protected $message = 'Force-enabled';
	function change_action_links( $actions ) {
		unset( $actions['deactivate'] );
		unset( $actions['delete'] );
		$actions['enabled'] = '<i>' . esc_html( $this->message ) . '</i>';
		return $actions;
	}
	/**
	 * Hooks in to the option_active_plugins filter and does the enabling.
	 *
	 * @param array $plugins WP-provided list of plugin filenames.
	 * @return array The filtered array of plugin filenames.
	 */
	public function alter( $plugins ) {
		if ( count( $this->plugins ) ) {
			foreach ( (array) $this->plugins as $plugin ) {
				$key = array_search( $plugin, $plugins );
				if ( false === $key ) {
					$plugins[] = $plugin;
				}
			}
		}
		return $plugins;
	}
}
