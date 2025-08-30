<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://mrkalathiya.wordpress.com/
 * @since      1.0.0
 *
 * @package    Wp_Hide_Backed_Notices
 * @subpackage Wp_Hide_Backed_Notices/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wp_Hide_Backed_Notices
 * @subpackage Wp_Hide_Backed_Notices/admin
 * @author     Hardik Kalathiya <hardikkalathiya93@gmail.com>
 */
class Wp_Hide_Backed_Notices_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // Add admin menu 
        add_action('admin_menu', array($this, 'add_custom_menu_in_dashboard'));
        add_shortcode('warning_notices_settings', array($this, 'warning_notices_settings'));

        add_action('admin_enqueue_scripts', array($this, 'hk_ds_admin_theme_style'));
        add_action('login_enqueue_scripts', array($this, 'hk_ds_admin_theme_style'));
    }

    public function add_custom_menu_in_dashboard() {
        add_menu_page('Manage Warnings', 'Manage Warnings', 'manage_options', 'manage_notices_settings', array($this, 'warning_notices_settings'), plugin_dir_url(__FILE__) . '/images/notice.png', 100);
    }

    public function warning_notices_settings() {
        if (isset($_POST['save_notice_box'])) {
            $manage_warnings_notice = serialize($_POST['hide_notice']);
            update_option('manage_warnings_notice', $manage_warnings_notice);

            echo "<meta http-equiv='refresh' content='0'>";
        }

        $custom_post_data = get_option('manage_warnings_notice');
        if (!empty($custom_post_data)) {
            $posts_from_db = unserialize($custom_post_data);
        }
        ?>
        <div class="main-wrap">
            <h1>Select what you want to hide.</h1>
            <hr>
            <div class="outer-gallery-box">
                <form method="POST" class="gallery_meta_form" id="gallery_meta_form_id">
                    <div class="checkboxes-manage" style="margin-top: 10px;">
                        <?php
                        if (in_array('Hide Updates', $posts_from_db)) {
                            $checked_update = 'checked';
                        } else {
                            $checked_update = '';
                        }
                        ?>
                        <label class="wp_gallery_container">Hide plugins and other updates.
                            <input  class="styled-checkbox" <?php echo $checked_update; ?> id="Hide-Updates" name="hide_notice[]" type="checkbox" value="Hide Updates">
                            <span class="checkmark"></span>
                        </label>
                        <?php
                        if (in_array('Hide Notices', $posts_from_db)) {
                            $checked_notice = 'checked';
                        } else {
                            $checked_notice = '';
                        }
                        ?>
                        <label class="wp_gallery_container">Hide notices, warning messages.
                            <input  class="styled-checkbox" <?php echo $checked_notice; ?> id="Hide-Notices" name="hide_notice[]" type="checkbox" value="Hide Notices">
                            <span class="checkmark"></span>
                        </label>
                    </div>
                    <div class="save_btn_wrapper">
                        <input type="submit" name="save_notice_box" id="save_post_gallery_box_id" class="save_post_gallery_box_cls" value="Save"> 
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

//     Hide warnings from the wordpress backend

    public function hk_ds_admin_theme_style() {
        $custom_post_data = get_option('manage_warnings_notice');
        $posts_from_db = unserialize($custom_post_data);

        // Hide Update notifications
        if (in_array('Hide Updates', $posts_from_db)) {
            echo '<style>body.wp-admin .update-plugins {display: none !important;} </style>';
        }
        // Hide notices from the wordpress backend
        if (in_array('Hide Notices', $posts_from_db)) {
            echo '<style> body.wp-admin .update-nag, body.wp-admin .updated, body.wp-admin .error, body.wp-admin .is-dismissible, body.wp-admin .notice{display: none !important;} </style>';
        }
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Wp_Hide_Backed_Notices_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Wp_Hide_Backed_Notices_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wp-hide-backed-notices -admin.css', array(), $this->version, 'all');
        wp_enqueue_style('manage_notice_hk', plugin_dir_url(__FILE__) . 'css/manage_notice.css', '', time());
        wp_enqueue_style('manage_notice_cstm_css', plugin_dir_url(__FILE__) . 'css/manage_notice_cstm.css', '', time());
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Wp_Hide_Backed_Notices_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Wp_Hide_Backed_Notices_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wp-hide-backed-notices -admin.js', array('jquery'), $this->version, false);
        wp_enqueue_script('manage_notice_js', plugin_dir_url(__FILE__) . 'js/manage-notice.js', '', time());
    }

}
