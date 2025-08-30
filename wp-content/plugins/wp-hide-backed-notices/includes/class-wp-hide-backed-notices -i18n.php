<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://mrkalathiya.wordpress.com/
 * @since      1.0.0
 *
 * @package    Wp_Hide_Backed_Notices
 * @subpackage Wp_Hide_Backed_Notices/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Wp_Hide_Backed_Notices
 * @subpackage Wp_Hide_Backed_Notices/includes
 * @author     Hardik Kalathiya <hardikkalathiya93@gmail.com>
 */
class Wp_Hide_Backed_Notices_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'wp-hide-backed-notices ',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
