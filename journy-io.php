<?php
/**
 * Plugin Name: journy.io
 * Plugin URI: https://www.journy.io/
 * Version: 2.0.14
 * Author: journy.io
 * Authro URI: https://jtm.journy.io/?utm_source=wordpress&utm_medium=wordpress-list-view&utm_campaign=yall&jtm_event=wordpress-list-clicked
 * Description: Activates and tracks WordPress events into journy.io
 * License: GPL2
 * Text Domain: journy-io
 */

use JournyIO\WordPress\Main;

require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload.php';

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

new Main();
