<?php
/**
 * Plugin Name: journy.io
 * Plugin URI: https://www.journy.io/
 * Version: 2.0.10
 * Author: journy.io
 * Description: Activates and tracks Wordpress events into journy.io
 * License: GPL2
 * Text Domain: journy-io
 */

require_once(plugin_dir_path(__FILE__) . '/vendor/autoload.php');

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

use ElementorPro\Plugin;
use JournyIO\SDK\Client;
use JournyIO\SDK\TrackingSnippet;


/*  Copyright 2021 journy.io

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

final class JournyIO
{
    private $IsCF7ed;
    private $IsElementor;
    private $IsElementorPro;

    public function __construct()
    {
        // Inheritance
        $this->plugin = new stdClass;
        $this->plugin->name = 'journy-io';
        $this->plugin->displayName = 'journy.io';
        $this->plugin->version = '2.0';
        $this->plugin->folder = plugin_dir_path(__FILE__);
        $this->plugin->url = plugin_dir_url(__FILE__);
        $this->plugin->db_welcome_dismissed_key = $this->plugin->name . '_welcome_dismissed_key';
        $this->body_open_supported = function_exists('wp_body_open') && version_compare(get_bloginfo('version'), '5.2', '>=');

        // Verify whether plugins are active
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        $this->IsCF7ed = is_plugin_active('contact-form-7/wp-contact-form-7.php');
        $this->IsElementor = is_plugin_active('elementor/elementor.php');
        $this->IsElementorPro = is_plugin_active('elementor-pro/elementor-pro.php');

        // Hooks
        add_action('admin_init', array(&$this, 'registerSettings'));
        add_action('admin_menu', array(&$this, 'adminPanelsAndMetaBoxes'));
        add_action('admin_notices', array(&$this, 'dashboardNotices'));
        add_action('wp_ajax_' . $this->plugin->name . '_dismiss_dashboard_notices', array(
            &$this,
            'dismissDashboardNotices'
        ));

        // Frontend Hooks
        add_action('wp_head', array(&$this, 'frontendHeader'));
        if ($this->body_open_supported) {
            add_action('wp_body_open', array(&$this, 'frontendBody'), 1);
        }

        if ($this->IsElementor && $this->IsElementorPro) {
            add_action('elementor_pro/init', function () {
                // Instantiate the action class
                include_once(plugin_dir_path(__FILE__) . '/elementor/CustomAction.php');

                $journy_action = new CustomAction();

                // Register the action with form widget
                Plugin::instance()->modules_manager->get_modules('forms')->add_form_action($journy_action->get_name(),
                    $journy_action);
            });
        }

        if ($this->IsCF7ed) {
            add_action("wpcf7_before_send_mail", function () {
                // get the contact form object
                $wpcf = WPCF7_ContactForm::get_current();

                include_once(plugin_dir_path(__FILE__) . '/cf7/CustomAction.php');

                $action = new CF7CustomAction();

                $action->sendData($wpcf);

                return $wpcf;
            });
        }
    }

    /**
     * Show relevant notices for the plugin
     */
    function dashboardNotices()
    {
        global $pagenow;

        if (!get_option($this->plugin->db_welcome_dismissed_key)) {
            if (!($pagenow == 'options-general.php' && isset($_GET['page']) && $_GET['page'] == 'journy-io')) {
                $setting_page = admin_url('options-general.php?page=' . $this->plugin->name);
                // load the notices view
                include_once($this->plugin->folder . '/views/dashboard-notices.php');
            }
        }
    }

    /**
     * Dismiss the welcome notice for the plugin
     */
    function dismissDashboardNotices()
    {
        check_ajax_referer($this->plugin->name . '-nonce', 'nonce');
        // user has dismissed the welcome notice
        update_option($this->plugin->db_welcome_dismissed_key, 1);
        exit;
    }

    /**
     * Register Settings
     */
    function registerSettings()
    {
	    register_setting( $this->plugin->name, 'jio_api_key', 'trim' );
	    register_setting( $this->plugin->name, 'jio_snippet', 'trim' );
	    register_setting( $this->plugin->name, 'jio_cf7_submit_option', 'boolean' );
	    register_setting( $this->plugin->name, 'jio_cf7_email_id', [ 'type' => 'trim', 'default' => 'your-email' ] );
	    register_setting( $this->plugin->name, 'jio_cf7_first_name_id', [ 'type'    => 'trim',
	                                                                      'default' => 'first_name'
	    ] );
	    register_setting( $this->plugin->name, 'jio_cf7_last_name_id', [ 'type' => 'trim', 'default' => 'last_name' ] );
	    register_setting( $this->plugin->name, 'jio_cf7_full_name_id', [ 'type' => 'trim', 'default' => 'your-name' ] );
    }

    /**
     * Register the plugin settings panel
     */
    function adminPanelsAndMetaBoxes()
    {
        add_submenu_page('options-general.php', $this->plugin->displayName, $this->plugin->displayName,
            'manage_options', $this->plugin->name, array(&$this, 'adminPanel'));
    }

    /**
     * Output the Administration Panel
     * Save POSTed data from the Administration Panel into a WordPress option
     */
    function adminPanel()
    {
        // only admin user can access this page
        if (!current_user_can('administrator')) {
            echo '<p>' . __('Sorry, you are not allowed to access this page.', 'journy-io') . '</p>';
            return;
        }

        // Save Settings
        if (isset($_REQUEST['submit'])) {
            // Check nonce
            if (!isset($_REQUEST[$this->plugin->name . '_nonce'])) {
                // Missing nonce
                $this->errorMessage = __('nonce field is missing. Settings NOT saved.', 'journy-io');
            } elseif (!wp_verify_nonce($_REQUEST[$this->plugin->name . '_nonce'], $this->plugin->name)) {
                // Invalid nonce
                $this->errorMessage = __('Invalid nonce specified. Settings NOT saved.', 'journy-io');
            } else {
                // Get API Key and save other settings
                $apiKey = sanitize_text_field($_REQUEST['jio_api_key']);
                update_option('jio_api_key', $apiKey);

                if (isset($_REQUEST['jio_api_key'])) {
                    $client = Client::withDefaults($apiKey);

                    $call = $client->getTrackingSnippet($_SERVER['HTTP_HOST']);

                    if ($call->succeeded()) {
                        $result = $call->result();

                        if ($result instanceof TrackingSnippet) {
                            update_option('jio_snippet', $result->getSnippet());
                        } else {
                            $this->errorMessage = __('No snippet found!', 'journy-io');
                        }
                    } else {
                        $journyErrors = $call->errors();
                        $errors = "";

                        foreach ($journyErrors as $key => $value) {
                            $errors .= $value . "<br>";
                        }

                        $this->errorMessage = $errors;
                    }
                }

                if ($this->IsCF7ed) {
	                update_option( 'jio_cf7_submit_option', sanitize_text_field( $_REQUEST['jio_cf7_submit_option'] ) );
	                update_option( 'jio_cf7_email_id', sanitize_text_field( $_REQUEST['jio_cf7_email_id'] ) );
	                update_option( 'jio_cf7_first_name_id', sanitize_text_field( $_REQUEST['jio_cf7_first_name_id'] ) );
	                update_option( 'jio_cf7_last_name_id', sanitize_text_field( $_REQUEST['jio_cf7_last_name_id'] ) );
	                update_option( 'jio_cf7_full_name_id', sanitize_text_field( $_REQUEST['jio_cf7_full_name_id'] ) );
                }

                update_option($this->plugin->db_welcome_dismissed_key, 1);
                $this->message = __('Settings Saved.', 'journy-io');
            }
        }

        // Get latest settings
        $this->settings = array(
	        'jio_api_key'           => esc_html( wp_unslash( get_option( 'jio_api_key' ) ) ),
	        'jio_snippet'           => esc_html( wp_unslash( get_option( 'jio_snippet' ) ) ),
	        'jio_cf7_submit_option' => esc_html( wp_unslash( get_option( 'jio_cf7_submit_option', '1' ) ) ),
	        'jio_cf7_email_id'      => esc_html( wp_unslash( get_option( 'jio_cf7_email_id' ) ) ),
	        'jio_cf7_first_name_id' => esc_html( wp_unslash( get_option( 'jio_cf7_first_name_id' ) ) ),
	        'jio_cf7_last_name_id'  => esc_html( wp_unslash( get_option( 'jio_cf7_last_name_id' ) ) ),
	        'jio_cf7_full_name_id'  => esc_html( wp_unslash( get_option( 'jio_cf7_full_name_id' ) ) ),
        );

        // Load Settings Form
        include_once($this->plugin->folder . '/views/settings.php');
    }

    /**
     * Outputs script / CSS to the frontend header
     */
    function frontendHeader()
    {
        if (!$this->body_open_supported) {
            $this->outputSnippet();
        }
    }

    /**
     * Outputs script / CSS to the frontend below opening body
     */
    function frontendBody()
    {
        if ($this->body_open_supported) {
            $this->outputSnippet();
        }
    }

    /**
     * Outputs the given setting, if conditions are met
     *
     */
    function outputSnippet()
    {
        // Ignore admin, feed, robots or trackbacks
        if (is_admin() || is_feed() || is_robots() || is_trackback()) {
            return;
        }

        // Get options
        $jio_snippet = get_option('jio_snippet');

        // check if snippet is set
        if (empty($jio_snippet)) {
            return; // NO SNIPPET

        }

        // Output
        echo wp_unslash($jio_snippet);
    }


    /**
     * Register a custom action for Elementor
     *
     */
    public function addElementorAction()
    {
        $journy_action = new Elementor_Journy_IO_Form_Action($this);

        Plugin::instance()->modules_manager->get_modules('forms')->add_form_action($journy_action->get_name(),
            $journy_action);
    }

}

new JournyIO();
