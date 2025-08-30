<?php

/**
 * Fired during plugin activation
 *
 * @link       https://bitofwp.com/
 * @since      1.1.0
 *
 * @package    Wp_Hide_Backed_Notices
 * @subpackage Wp_Hide_Backed_Notices/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wp_Hide_Backed_Notices
 * @subpackage Wp_Hide_Backed_Notices/includes
 * @author     BitofWP <help@bitofwp.com>
 */
class Wp_Hide_Backed_Notices_Activator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate() {
        $manage_warnings_notice = 'a:1:{s:10:"Only_Admin";s:10:"Only Admin";}';
        update_option('manage_warnings_notice', $manage_warnings_notice);
    }

}
