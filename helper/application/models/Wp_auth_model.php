<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Wp_auth_model extends CI_Model
{


    public function __construct()
    {

        define('WP_USE_THEMES', false); // Do not use the theme files
        define('COOKIE_DOMAIN', false); // Do not append verify the domain to the cookie
        define('DISABLE_WP_CRON', true); // We don't want extra things running...

        //$_SERVER['HTTP_HOST'] = ""; // For multi-site ONLY. Provide the 
        // URL/blog you want to auth to.
if (!is_dir("/var/www/html/paintchip")) return;
        // Path (absolute or relative) to where your WP core is running
        require($_SERVER['DOCUMENT_ROOT'] . "/wp-load.php");
        if (is_user_logged_in()) {
            
            $user = wp_get_current_user();
            //die("<h3>Output</h3><pre>".print_r($user,1)."</pre>");
            
     //$this->session->set_userdata('user', $user);
      } else {
           
          die('<script>window.location="/wp-admin";</script>');
            /* die("<html><head><script>window.location='/wp-admin'</script></head></html>");
           return ; *///redirect('/wp-admin');
            $creds                  = array();
            // If you're not logged in, you should display a form or something
            // Use the submited information to populate the user_login & user_password
           
            $creds['user_login']    = "";
            $creds['user_password'] = "";
            $creds['remember']      = true; 
            
            if ($_POST['user_login'] || $_POST['user_password']) {
                $creds = $_POST;
            }
            $user                   = wp_signon($creds, false);
            if (is_wp_error($user)) {
                $data['message'] = "Please log in";
               if ($_POST) {
                   $data = array('message' => $user->get_error_message() );
               }
 
                $this->load->view('login', $data);
                die();
            } else {
  wp_set_auth_cookie($user->ID, true);
            }
        }

        if (!is_wp_error($user)) {
            // Success! We're logged in! Now let's test against EDD's purchase of my "service."

            
        }
    }

    function logout() {
        wp_logout();
    }
}
