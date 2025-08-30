<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://bitofwp.com/
 * @since             1.1.0
 * @package           Wp_Hide_Backed_Notices
 *
 * @wordpress-plugin
 * Plugin Name:       Hide Dashboard Notifications
 * Plugin URI:        bitofwp.com
 * Description:       Hide all those annoying and spammy notices from your WordfPress Dashboard. You can also enable the option to store  and view them under the Notifications Tab. 
 * Version:           1.2.0
 * Author:            BitofWP
 * Author URI:        https://bitofwp.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-hide-backed-notices
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('WP_HIDE_BACKED_NOTICES _VERSION', '1.2.0');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wp-hide-backed-notices-activator.php
 */
function activate_wp_hide_backed_notices() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-wp-hide-backed-notices-activator.php';
    Wp_Hide_Backed_Notices_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wp-hide-backed-notices-deactivator.php
 */
function deactivate_wp_hide_backed_notices() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-wp-hide-backed-notices-deactivator.php';
    Wp_Hide_Backed_Notices_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_wp_hide_backed_notices');
register_deactivation_hook(__FILE__, 'deactivate_wp_hide_backed_notices');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-wp-hide-backed-notices.php';

add_action('admin_bar_menu', 'add_toolbar_items', 100);
function add_toolbar_items($admin_bar){
    $admin_bar->add_menu( array(
        'id'    => 'hide-notifications',
        'title' => 'Notifications',
        'href'  => admin_url('admin.php?page=manage_notices_settings'),
        'meta'  => array(
        	//'html' => '<span class="hide-notifications"></span>',
            'title' => __('Hide Notifications', 'textdomain'),            
        ),
    ));
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wp_hide_backed_notices() {

    $plugin = new Wp_Hide_Backed_Notices();
    $plugin->run();
}

run_wp_hide_backed_notices();
