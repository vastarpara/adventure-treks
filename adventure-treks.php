<?php
/**
 * Plugin Name:       Adventure Treks
 * Plugin URI:        https://github.com/nileshvastarpara/adventure-treks
 * Description:       A premium trekking and adventure trip management plugin for WordPress.
 * Version:           1.0.3
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
define( 'ADVENTURE_TREKS_VERSION', '1.1.0' );

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
function activate_adventure_treks() {
	AdventureTreks\Includes\Activator::activate();
}

/**
 * Deactivation code runner.
 */
function deactivate_adventure_treks() {
	AdventureTreks\Includes\Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_adventure_treks' );
register_deactivation_hook( __FILE__, 'deactivate_adventure_treks' );

/**
 * Begins execution of the plugin.
 *
 * @return void
 */
function run_adventure_treks() {
	$plugin = AdventureTreks\Includes\Plugin::get_instance();
	$plugin->run();
}
run_adventure_treks();
