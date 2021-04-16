<?php

namespace JournyIO\WordPress;

use ElementorPro\Plugin;
use JournyIO\SDK\Client;
use JournyIO\SDK\TrackingSnippet;
use JournyIO\WordPress\Cf7\CF7CustomAction;
use JournyIO\WordPress\Elementor\ElementorCustomAction;
use stdClass;

final class Main
{
    private $isCF7ed;
    private $isElementor;
    private $isElementorPro;
    private $isMooveGdpr;

    public function __construct()
    {
        $this->name                     = 'journy-io';
        $this->displayName              = 'journy.io';
        $this->version                  = '2.0';
        $this->folder                   = plugin_dir_path(__FILE__);
        $this->url                      = plugin_dir_url(__FILE__);
        $this->db_welcome_dismissed_key = $this->name . '_welcome_dismissed_key';
        $this->body_open_supported              = function_exists('wp_body_open')
            && version_compare(get_bloginfo('version'), '5.2', '>=');

        $this->checkActivePlugins();

        $this->initGeneralActions();

        $this->initElementorAction();
        $this->initCF7Action();
    }

    private function initGeneralActions()
    {
        add_action('admin_init', array( &$this, 'registerSettings' ));
        add_action('admin_menu', array( &$this, 'adminPanelsAndMetaBoxes' ));
        add_action('admin_notices', array( &$this, 'dashboardNotices' ));
        add_action(
            'wp_ajax_' . $this->name . '_dismiss_dashboard_notices',
            array(
            &$this,
            'dismissDashboardNotices'
            )
        );

        // Frontend Hooks
        add_action('wp_head', array( &$this, 'frontendHeader' ));
        if ($this->body_open_supported) {
            add_action('wp_body_open', array( &$this, 'frontendBody' ), 1);
        }
    }

    private function checkActivePlugins()
    {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        $this->isCF7ed        = is_plugin_active('contact-form-7/wp-contact-form-7.php');
        $this->isElementor    = is_plugin_active('elementor/elementor.php');
        $this->isElementorPro = is_plugin_active('elementor-pro/elementor-pro.php');
        $this->isMooveGdpr    = is_plugin_active('gdpr-cookie-compliance/moove-gdpr.php');
    }

    private function initElementorAction()
    {
        if ($this->isElementor && $this->isElementorPro) {
            add_action(
                'elementor_pro/init',
                function () {
                    $journy_action = new ElementorCustomAction();

                    Plugin::instance()->modules_manager->get_modules('forms')->add_form_action(
                        $journy_action->get_name(),
                        $journy_action
                    );
                }
            );
        }
    }

    private function initCF7Action()
    {
        if ($this->isCF7ed) {
            add_action(
                "wpcf7_before_send_mail",
                function () {
                    $wpcf = WPCF7_ContactForm::get_current();

                    $action = new CF7CustomAction();

                    $action->sendData($wpcf);

                    return $wpcf;
                }
            );
        }
    }

    public function dashboardNotices()
    {
        global $pagenow;

        if (! get_option($this->db_welcome_dismissed_key)) {
            if (! ( $pagenow == 'options-general.php' && isset($_GET['page']) && $_GET['page'] == 'journy-io' )) {
                // Variable is used in /views/dashboard-notices.php
                $setting_page = admin_url('options-general.php?page=' . $this->name);

                include_once $this->folder . '/views/dashboard-notices.php';
            }
        }
    }

    public function dismissDashboardNotices()
    {
        check_ajax_referer($this->name . '-nonce', 'nonce');
        update_option($this->db_welcome_dismissed_key, 1);
        exit;
    }

    public function registerSettings()
    {
        register_setting($this->name, 'jio_api_key', 'trim');
        register_setting($this->name, 'jio_snippet', 'trim');
        register_setting($this->name, 'jio_cf7_submit_option', 'boolean');
        register_setting($this->name, 'jio_cf7_email_id', [ 'type' => 'trim', 'default' => 'your-email' ]);
        register_setting(
            $this->name,
            'jio_cf7_first_name_id',
            [
            'type'    => 'trim',
            'default' => 'first_name'
            ]
        );
        register_setting($this->name, 'jio_cf7_last_name_id', [ 'type' => 'trim', 'default' => 'last_name' ]);
        register_setting($this->name, 'jio_cf7_full_name_id', [ 'type' => 'trim', 'default' => 'your-name' ]);
    }

    public function adminPanelsAndMetaBoxes()
    {
        add_submenu_page(
            'options-general.php',
            $this->displayName,
            $this->displayName,
            'manage_options',
            $this->name,
            array( &$this, 'adminPanel' )
        );
    }

    public function adminPanel()
    {
        if (! current_user_can('administrator')) {
            echo '<p>' . __('Sorry, you are not allowed to access this page.', 'journy-io') . '</p>';

            return;
        }

        if (isset($_REQUEST['submit'])) {
            $this->handleSettingsSubmit();
        }

        $this->settings = array(
        'jio_api_key'           => esc_html(wp_unslash(get_option('jio_api_key'))),
        'jio_snippet'           => esc_html(wp_unslash(get_option('jio_snippet'))),
        'jio_cf7_submit_option' => esc_html(wp_unslash(get_option('jio_cf7_submit_option', '1'))),
        'jio_cf7_email_id'      => esc_html(wp_unslash(get_option('jio_cf7_email_id'))),
        'jio_cf7_first_name_id' => esc_html(wp_unslash(get_option('jio_cf7_first_name_id'))),
        'jio_cf7_last_name_id'  => esc_html(wp_unslash(get_option('jio_cf7_last_name_id'))),
        'jio_cf7_full_name_id'  => esc_html(wp_unslash(get_option('jio_cf7_full_name_id'))),
        );

        include_once $this->folder . '/views/settings.php';
    }

    private function handleSettingsSubmit()
    {
        if (! isset($_REQUEST[ $this->name . '_nonce' ])) {
            // Missing nonce
            $this->errorMessage = __('nonce field is missing. Settings NOT saved.', 'journy-io');
        } elseif (! wp_verify_nonce($_REQUEST[ $this->name . '_nonce' ], $this->name)) {
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
                    $errors       = "";

                    foreach ($journyErrors as $key => $value) {
                        $errors .= $value . "<br>";
                    }

                    $this->errorMessage = $errors;
                }
            }

            if ($this->isCF7ed) {
                update_option('jio_cf7_submit_option', sanitize_text_field($_REQUEST['jio_cf7_submit_option']));
                update_option('jio_cf7_email_id', sanitize_text_field($_REQUEST['jio_cf7_email_id']));
                update_option('jio_cf7_first_name_id', sanitize_text_field($_REQUEST['jio_cf7_first_name_id']));
                update_option('jio_cf7_last_name_id', sanitize_text_field($_REQUEST['jio_cf7_last_name_id']));
                update_option('jio_cf7_full_name_id', sanitize_text_field($_REQUEST['jio_cf7_full_name_id']));
            }

            update_option($this->db_welcome_dismissed_key, 1);
            $this->message = __('Settings Saved.', 'journy-io');
        }
    }

    public function outputHeaderSnippet()
    {
        if (! $this->body_open_supported) {
            if ($this->isMooveGdpr) {
                add_action(
                    'moove_gdpr_third_party_header_assets',
                    function ($scripts) {
                        $scripts .= $this->outputSnippet();

                        return $scripts;
                    }
                );
            } else {
                echo $this->outputSnippet();
            }
        }
    }

    public function outputBodySnippet()
    {
        if ($this->body_open_supported) {
            if ($this->isMooveGdpr) {
                add_action(
                    'moove_gdpr_third_party_body_assets',
                    function ($scripts) {
                        $scripts .= $this->outputSnippet();

                        return $scripts;
                    }
                );
            } else {
                echo $this->outputSnippet();
            }
        }
    }


    private function outputSnippet()
    {
        if (is_admin() || is_feed() || is_robots() || is_trackback()) {
            return "";
        }

        $jio_snippet = get_option('jio_snippet');

        if (empty($jio_snippet)) {
            return "";
        }

        return wp_unslash($jio_snippet);
    }


    private function addElementorAction()
    {
        $journy_action = new Elementor_Journy_IO_Form_Action($this);

        Plugin::instance()->modules_manager->get_modules('forms')->add_form_action(
            $journy_action->get_name(),
            $journy_action
        );
    }
}
