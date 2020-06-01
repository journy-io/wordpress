<?php
/**
* Plugin Name: journy.io Customer Intelligence Platform
* Plugin URI: https://www.journy.io/
* Version: 1.1.49
* Author: journy.io
* Author URI: https://www.journy.io/
* Description: Activates and tracks Wordpress events into journy.io
* License: GPL2
* Text Domain: journy-io
*/

/*  Copyright 2020 journy.io

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

DEFINE ('__JOURNY_DEFAULT_DOMAIN__', 'https://analytics.journy.io'); //production
//DEFINE ('__JOURNY_DEFAULT_DOMAIN__', 'https://analytics.journy.app', true); //staging

/**
* journy-io Class
*/

class JournyIO {

	public $IsWooCommerced = false;
	public $IsCF7ed = false;
	
	/**
	* Constructor
	*/
	public function __construct() {

		// Inheritance
        $this->plugin               = new stdClass;
        $this->plugin->name         = 'journy-io'; 
        $this->plugin->displayName  = 'journy.io';
        $this->plugin->version      = '1.0.49';
        $this->plugin->folder       = plugin_dir_path( __FILE__ );
        $this->plugin->url          = plugin_dir_url( __FILE__ );
        $this->plugin->db_welcome_dismissed_key = $this->plugin->name . '_welcome_dismissed_key';
        $this->body_open_supported	= function_exists( 'wp_body_open' ) && version_compare( get_bloginfo( 'version' ), '5.2' , '>=' );

		//Verify whether plugins are active
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		$this->IsWooCommerced = is_plugin_active( 'woocommerce/woocommerce.php');
		$this->IsCF7ed = is_plugin_active('contact-form-7/wp-contact-form-7.php');

		// Hooks
		add_action( 'admin_init', array( &$this, 'registerSettings' ) );
        add_action( 'admin_menu', array( &$this, 'adminPanelsAndMetaBoxes' ) );
        add_action( 'admin_notices', array( &$this, 'dashboardNotices' ) );
        add_action( 'wp_ajax_' . $this->plugin->name . '_dismiss_dashboard_notices', array( &$this, 'dismissDashboardNotices' ) );

		// Frontend Hooks
        add_action( 'wp_head', array( &$this, 'frontendHeader' ) );
		if ( $this->body_open_supported ) {
			add_action( 'wp_body_open', array( &$this, 'frontendBody' ), 1 );
		}
		if ( $this->IsCF7ed ) {
			add_action( 'wp_footer', array( &$this, 'frontendFooter' ) );
		}

		// Woo Hooks
		if ( $this->IsWooCommerced ) {
			add_action( 'woocommerce_after_add_to_cart_button', array( &$this, 'addToCartProcess' ) );
			add_action( 'woocommerce_after_cart', array( $this, 'reviewCartProcess' ) );
			add_action( 'woocommerce_after_mini_cart', array( $this, 'reviewCartProcess' ) );
			add_action( 'woocommerce_thankyou', array( &$this, 'checkOutProcess' ) );
		}

	}

    /**
     * Show relevant notices for the plugin
     */
    function dashboardNotices() {
        global $pagenow;

        if ( !get_option( $this->plugin->db_welcome_dismissed_key ) ) {
        	if ( ! ( $pagenow == 'options-general.php' && isset( $_GET['page'] ) && $_GET['page'] == 'journy-io' ) ) {
	            $setting_page = admin_url( 'options-general.php?page=' . $this->plugin->name );
	            // load the notices view
                include_once( $this->plugin->folder . '/views/dashboard-notices.php' );
        	}
        }
    }

    /**
     * Dismiss the welcome notice for the plugin
     */
    function dismissDashboardNotices() {
    	check_ajax_referer( $this->plugin->name . '-nonce', 'nonce' );
        // user has dismissed the welcome notice
        update_option( $this->plugin->db_welcome_dismissed_key, 1 );
        exit;
    }

	/**
	* Register Settings
	*/
	function registerSettings() {
		register_setting( $this->plugin->name, 'jio_tracking_ID', 'trim' );
		register_setting( $this->plugin->name, 'jio_tracking_URL', 'trim' );
		register_setting( $this->plugin->name, 'jio_woo_addcart_option', 'boolean' );
		register_setting( $this->plugin->name, 'jio_woo_reviewcart_option', 'boolean' );
		register_setting( $this->plugin->name, 'jio_woo_checkout_option', 'boolean' );
		register_setting( $this->plugin->name, 'jio_cf7_submit_option', 'boolean' );
	}

	/**
    * Register the plugin settings panel
    */
    function adminPanelsAndMetaBoxes() {
    	add_submenu_page( 'options-general.php', $this->plugin->displayName, $this->plugin->displayName, 'manage_options', $this->plugin->name, array( &$this, 'adminPanel' ) );
	}

    /**
    * Output the Administration Panel
    * Save POSTed data from the Administration Panel into a WordPress option
    */
    function adminPanel() {
		// only admin user can access this page
		if ( !current_user_can( 'administrator' ) ) {
			echo '<p>' . __( 'Sorry, you are not allowed to access this page.', 'journy-io' ) . '</p>';
			return;
		}

    	// Save Settings
        if ( isset( $_REQUEST['submit'] ) ) {
        	// Check nonce
			if ( !isset( $_REQUEST[$this->plugin->name.'_nonce'] ) ) {
	        	// Missing nonce
	        	$this->errorMessage = __( 'nonce field is missing. Settings NOT saved.', 'journy-io' );
        	} elseif ( !wp_verify_nonce( $_REQUEST[$this->plugin->name.'_nonce'], $this->plugin->name ) ) {
	        	// Invalid nonce
	        	$this->errorMessage = __( 'Invalid nonce specified. Settings NOT saved.', 'journy-io' );
        	} else {
	        	// Save
				// $_REQUEST has already been slashed by wp_magic_quotes in wp-settings
				// so do nothing before saving
	    		update_option( 'jio_tracking_ID', $_REQUEST['jio_tracking_ID'] );
	    		update_option( 'jio_tracking_URL', $_REQUEST['jio_tracking_URL'] );
				update_option( 'jio_woo_addcart_option', $_REQUEST['jio_woo_addcart_option'] );
				update_option( 'jio_woo_reviewcart_option', $_REQUEST['jio_woo_reviewcart_option'] );
				update_option( 'jio_woo_checkout_option', $_REQUEST['jio_woo_checkout_option'] );
				update_option( 'jio_cf7_submit_option', $_REQUEST['jio_cf7_submit_option'] );
				update_option( $this->plugin->db_welcome_dismissed_key, 1 );
				$this->message = __( 'Settings Saved.', 'journy-io' );
			}
        }

        // Get latest settings
        $this->settings = array(
			'jio_tracking_ID' => esc_html( wp_unslash( get_option( 'jio_tracking_ID' ) ) ),
			'jio_tracking_URL' => esc_html( wp_unslash( get_option( 'jio_tracking_URL' ) ) ),
			'jio_woo_addcart_option' => get_option( 'jio_woo_addcart_option', '1' ),
			'jio_woo_reviewcart_option' => get_option( 'jio_woo_reviewcart_option', '1' ),
			'jio_woo_checkout_option' => get_option( 'jio_woo_checkout_option', '1' ),
			'jio_cf7_submit_option' => get_option( 'jio_cf7_submit_option', '1' ),


        );

    	// Load Settings Form
        include_once( $this->plugin->folder . '/views/settings.php' );
    }

    /**
	* Loads plugin textdomain
	
	function loadLanguageFiles() {
		load_plugin_textdomain( 'journy-io', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
	*/

	/**
	* Outputs script / CSS to the frontend header
	*/
	function frontendHeader() {
		if ( !$this->body_open_supported ) {
			$this->outputSnippet();
		}
	}

	/**
	* Outputs script / CSS to the frontend below opening body
	*/
	function frontendBody() {
		if ( $this->body_open_supported ) {
			$this->outputSnippet();
		}
	}

	/**
	* Outputs script / CSS to the frontend footer
	*/
	function frontendFooter() {
		if ( $this->IsCF7ed && get_option( 'jio_cf7_submit_option')) {
			$this->outputDOMEventListenerToFooter();
		}
	}

	/**
	* Outputs the given setting, if conditions are met
	*
	* @return output
	*/
	function outputSnippet() {
		// Ignore admin, feed, robots or trackbacks
		if ( is_admin() || is_feed() || is_robots() || is_trackback() ) {
			return;
		}

		// Get options
		$jio_tracking_id = get_option( 'jio_tracking_ID' );
		$jio_tracking_url = get_option( 'jio_tracking_URL' );

		// check if tracking id is set
		if ( empty( $jio_tracking_id ) || ( trim( $jio_tracking_id ) == '' ) ) {
			return; //NO TRACKING ID

		}

		// check if tracking url is set
		if ( empty( $jio_tracking_url ) || ( trim( $jio_tracking_url ) == '' ) ) {
			$jio_tracking_url = __JOURNY_DEFAULT_DOMAIN__; // production
			//$jio_tracking_url = 'https://analytics.journy.app'; // staging

		}
		
		$outputTrackerString = '<script src="'.$jio_tracking_url.'/tracker.js" async></script>
<script>
  window.journy=window.journy||function(_,n,o){window.__journy_queue__||(window.__journy_queue__=[]),window.__journy_queue__.push({command:_,args:n,date:Date.now(),callback:o})};
  journy("init", { trackerId: "'.$jio_tracking_id.'", domain: "'.$jio_tracking_url.'" });
  journy("pageview");
</script>';

		// Output
		echo wp_unslash( $outputTrackerString );
	}

	/**
	* Outputs DOM Event Listener to catch CF7 events
	*
	* @return output
	*/
	function outputDOMEventListenerToFooter() {
		$jio_tracking_id = get_option( 'jio_tracking_ID' );
		if ( empty( $jio_tracking_id ) || ( trim( $jio_tracking_id ) == '' ) ) {
			return; // staging
		}
	?>
		<script type="text/javascript">
		document.addEventListener( 'wpcf7mailsent', function( event ) {
    		var inputs = event.detail.inputs;
			for ( var i = 0; i < inputs.length; i++ ) {
				if ( inputs[i].name.match(/mail/gi) && inputs[i].value.match(/.+\@.+\..+/)) {
					var theMail = inputs[i].value;
				}
			}
			var jEventName = 'form' + event.detail.contactFormId + '_submission';
			journy("event", { tag: jEventName });
			if (theMail) {
				journy("identify", { email: theMail.toLowerCase() });
			}
    	}, false );
		</script>
	<?php
	}


	/**
	* Processes woocommerce_after_add_to_cart_button event from WooCommerce
	*
	* @return output
	*/
	public function addToCartProcess( $orderID) {
		$order = wc_get_order( $orderID );
		if ( get_option('jio_woo_addcart_option') )
			wc_enqueue_js('journy("event", { tag: "add-to-cart" });');
	}

	/**
	* Processes woocommerce_after_cart and woocommerce_after_mini_cart event from WooCommerce
	*
	* @return output
	*/
	public function reviewCartProcess( $orderID) {
		$order = wc_get_order( $orderID );
		if ( get_option('jio_woo_reviewcart_option') )
			wc_enqueue_js('journy("event", { tag: "review-cart" });'); 
	}

	/**
	* Processes woocommerce_thankyou event from WooCommerce
	*
	* @return output
	*/
	public function checkOutProcess( $orderID) {
		$order = wc_get_order( $orderID );
		if ( get_option('jio_woo_checkout_option') ) {
			wc_enqueue_js('journy("event", { tag: "check-out" });');
			wc_enqueue_js('journy("identify", { email: "'.$order->get_billing_email().'" });');
		}
	}

}

$journyIO = new JournyIO();

