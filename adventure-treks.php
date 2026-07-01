<?php
/**
 * Plugin Name:       Adventure Treks
 * Plugin URI:        https://github.com/nileshvastarpara/adventure-treks
 * Description:       A premium trekking and adventure trip management plugin for WordPress.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Nilesh Vastarpara
 * Author URI:        https://github.com/nileshvastarpara
 * License:           GPL-2.0+
 * Text Domain:       adventure-treks
 * Domain Path:       /languages
 *
 * @package           AdventureTreks
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently active version of the plugin.
 */
define( 'ADVENTURE_TREKS_VERSION', '1.0.0' );

/**
 * Base directory path for the plugin.
 */
define( 'ADVENTURE_TREKS_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Base directory URL for the plugin.
 */
define( 'ADVENTURE_TREKS_URL', plugin_dir_url( __FILE__ ) );

// Load Autoloader.
require_once ADVENTURE_TREKS_PATH . 'includes/Autoloader.php';
AdventureTreks\Includes\Autoloader::register();

/**
 * Activation code runner.
 */
function adventure_treks_activate() {
	AdventureTreks\Includes\Activator::activate();
}

/**
 * Deactivation code runner.
 */
function adventure_treks_deactivate() {
	AdventureTreks\Includes\Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'adventure_treks_activate' );
register_deactivation_hook( __FILE__, 'adventure_treks_deactivate' );

/**
 * Begins execution of the plugin.
 *
 * @return void
 */
function adventure_treks_run() {
	$plugin = AdventureTreks\Includes\Plugin::get_instance();
	$plugin->run();
}
adventure_treks_run();
