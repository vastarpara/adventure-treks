<?php
/**
 * Plugin Name:       TrekPilot – Trek Booking & Management
 * Description:       A premium trekking and adventure trip management plugin for WordPress.
 * Version:           1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Nilesh Vastarpara
 * Author URI:        https://nileshvastarpara.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       trekpilot
 * Domain Path:       /languages
 *
 * @package           TrekPilot
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently active version of the plugin.
 */
define( 'TREKPILOT_VERSION', '1.0' );

/**
 * Base directory path for the plugin.
 */
define( 'TREKPILOT_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Base directory URL for the plugin.
 */
define( 'TREKPILOT_URL', plugin_dir_url( __FILE__ ) );

// Load Autoloader.
require_once TREKPILOT_PATH . 'includes/class-autoloader.php';
TrekPilot\Includes\Autoloader::register();

/**
 * Activation code runner.
 */
function trekpilot_activate() {
	TrekPilot\Includes\Activator::activate();
}

/**
 * Deactivation code runner.
 */
function trekpilot_deactivate() {
	TrekPilot\Includes\Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'trekpilot_activate' );
register_deactivation_hook( __FILE__, 'trekpilot_deactivate' );

/**
 * Begins execution of the plugin.
 *
 * @return void
 */
function trekpilot_run() {
	$plugin = TrekPilot\Includes\Plugin::get_instance();
	$plugin->run();
}
trekpilot_run();
