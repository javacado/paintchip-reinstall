<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://bitofwp.com/
 * @since      1.1.0
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
 * @author     BitofWP <help@bitofwp.com>
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
        add_menu_page('Hide Notices', 'Hide Notices', 'manage_options', 'manage_notices_settings', array($this, 'warning_notices_settings'), plugin_dir_url(__FILE__) . 'images/hide-dash-menu.png', 100);
    }

    public function warning_notices_settings() {

        if (isset($_POST['save_notice_box'])) {
            if (empty($_POST['hide_notice']['Only_Admin'])) {
                $_POST['hide_notice']['Only_Admin'] = 'All Users';
            }
            $manage_warnings_notice = serialize($_POST['hide_notice']);
            update_option('manage_warnings_notice', $manage_warnings_notice);
            echo "<meta http-equiv='refresh' content='0'>";
        }

        $custom_post_data = get_option('manage_warnings_notice');
        if (!empty($custom_post_data)) {
            $posts_from_db = unserialize($custom_post_data);
        }
//        pr($posts_from_db);
        ?>
        <div class="main-wrap setting-top-wrap">
            <div class="tab">
                <button class="tablinks active" onclick="openCity(event, 'Settings')" id="defaultOpen">
                    <img src="<?php echo plugin_dir_url(__FILE__) . 'images/hide-setting-white.png' ?>">
                    Settings
                </button>
                <button class="tablinks" onclick="openCity(event, 'Notifications')">
                    <img src="<?php echo plugin_dir_url(__FILE__) . 'images/dash-hide-white.png' ?>">
                    Notifications
                </button> 
            </div>
            <div id="Settings" class="tabcontent" style="display: block;">
                <h3>Select what you want to hide</h3>
                <div class="outer-gallery-box">
                    <form method="POST" class="gallery_meta_form" id="gallery_meta_form_id">
                        <div class="checkboxes-manage" style="margin-top: 10px;">
                            <?php
                            $AccessedByAdmins = '';
                            if (!empty($posts_from_db) && $posts_from_db != '') {
                                if (in_array('Only Admin', $posts_from_db)) {
                                    $AccessedByAdmins = 'checked';
                                } else {
                                    $AccessedByAdmins = '';
                                }
                            } else {
                                $AccessedByAdmins = 'checked';
                            }
                            ?>

                            <h4>Hide Dashboard Notices Only for Admins</h4>
                            <label class="switch">
                                <input  class="styled-checkbox" <?php echo $AccessedByAdmins; ?> id="Hide-Accesse" name="hide_notice[Only_Admin]" type="checkbox" value="Only Admin">
                                <span class="slider round"></span>
                            </label>

                            <?php
                            $checked_notice = '';
                            if (!empty($posts_from_db) && $posts_from_db != '') {
                                if (in_array('Hide Notices', $posts_from_db)) {
                                    $checked_notice = 'checked';
                                } else {
                                    $checked_notice = '';
                                }
                            }
                            ?>

                            <h4>Hide Dashboard Notices and Warnings</h4>
                            <label class="switch">
                                <input  class="styled-checkbox" <?php echo $checked_notice; ?> id="Hide-Notices" name="hide_notice[Hide_Notices]" type="checkbox" value="Hide Notices">
                                <span class="slider round"></span>
                            </label>

                            <?php
                            $checked_update = '';
                            if (!empty($posts_from_db) && $posts_from_db != '') {
                                if (in_array('Hide Updates', $posts_from_db)) {
                                    $checked_update = 'checked';
                                } else {
                                    $checked_update = '';
                                }
                            }
                            ?>

                            <h4>Hide WordPress Update Notices</h4>
                            <label class="switch">
                                <input  class="styled-checkbox" <?php echo $checked_update; ?> id="Hide-Updates" name="hide_notice[Hide_Updates]" type="checkbox" value="Hide Updates">
                                <span class="slider round"></span>
                            </label>

                            <?php
                            $checked_update = '';
                            if (!empty($posts_from_db) && $posts_from_db != '') {
                                if (in_array('Hide PHP Updates', $posts_from_db)) {
                                    $checked_update = 'checked';
                                } else {
                                    $checked_update = '';
                                }
                            }
                            ?>

                            <h4> Hide PHP Update Required Notice</h4>
                            <label class="switch">
                                <input  class="styled-checkbox" <?php echo $checked_update; ?> id="hide-php-updates" name="hide_notice[Hide_PHP_Updates]" type="checkbox" value="Hide PHP Updates">
                                <span class="slider round"></span>
                            </label>

                        </div>
                        <div class="save_btn_wrapper">
                            <input type="submit" name="save_notice_box" id="save_post_gallery_box_id" class="save_post_gallery_box_cls" value="Save"> 
                        </div>
                    </form>
                </div>
            </div>

            <!--Notification Tab-->
            <div id="Notifications" class="tabcontent">
                <h3>Dashboard notifications</h3>
                <?php
                if (!empty($posts_from_db) && $posts_from_db != '') {
                    if (in_array('Hide Notices', $posts_from_db)) {
                        do_action('admin_notices');
                    }
                }
                ?>
            </div>
        </div>
        <?php
    }

    // Hide warnings from the wordpress backend
    public function hk_ds_admin_theme_style() {
        $custom_post_data = get_option('manage_warnings_notice');

        if (!empty($custom_post_data) && $custom_post_data != '') {
            $posts_from_db = unserialize($custom_post_data);

            if (!empty($posts_from_db)) {
                $user = wp_get_current_user();
                $CurentUserRoles = (array) $user->roles;
                if (in_array('Only Admin', $posts_from_db)) {
                    if (in_array('administrator', $CurentUserRoles)) {
//                      // Hide Update notifications
                        if (in_array('Hide Updates', $posts_from_db)) {
                            echo '<style>
                                body.wp-admin .update-plugins, 
                                body.wp-admin #wp-admin-bar-updates {display: none !important;} 
                            </style>';
                        }

                        // Hide notices from the wordpress backend
                        if (in_array('Hide Notices', $posts_from_db)) {
                            echo '<style> 
                                body.wp-admin .update-nag,
                                body.wp-admin .updated,
                                body.wp-admin .error,
                                body.wp-admin .is-dismissible,
                                body.wp-admin .notice,
                                #yoast-indexation-warning{display: none !important;}

                                body.wp-admin #loco-content .notice,
                                body.wp-admin #loco-notices .notice{display:block !important;}
                            </style>';
                        }

                        // Hide PHP Updates from the wordpress backend
                        if (in_array('Hide PHP Updates', $posts_from_db)) {
                            echo '<style>
                                #dashboard_php_nag {display:none;}
                            </style>';
                        }
                    }
                } else {
                    // Hide Update notifications
                    if (in_array('Hide Updates', $posts_from_db)) {
                        echo '<style>
                            body.wp-admin .update-plugins,
                            body.wp-admin #wp-admin-bar-updates {display: none !important;}
                        </style>';
                    }

                    // Hide notices from the wordpress backend
                    if (in_array('Hide Notices', $posts_from_db)) {
                        echo '<style>
                            body.wp-admin .update-nag,
                            body.wp-admin .updated,
                            body.wp-admin .error,
                            body.wp-admin .is-dismissible,
                            body.wp-admin .notice,
                            #yoast-indexation-warning{display: none !important;}

                            body.wp-admin #loco-content .notice,
                            body.wp-admin #loco-notices .notice{display:block !important;}
                        </style>';
                    }

                    // Hide PHP Updates from the wordpress backend
                    if (in_array('Hide PHP Updates', $posts_from_db)) {
                        echo '<style>#dashboard_php_nag {display:none;}</style>';
                    }
                }
            }
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
        wp_enqueue_style('manage_notice_hk', plugin_dir_url(__FILE__) . 'css/manage_notice.css', '', time());
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
        wp_enqueue_script('manage_notice_js', plugin_dir_url(__FILE__) . 'js/manage-notice.js', '', time());
    }

}
