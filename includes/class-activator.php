<?php
/**
 * Fired during plugin activation
 *
 * @package    TrekPilot
 * @subpackage TrekPilot/Includes
 * @author     Nilesh Vastarpara
 */

namespace TrekPilot\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class Activator {

	/**
	 * Run the activation routines.
	 *
	 * @return void
	 */
	public static function activate() {
		// Initialize the custom database schema.
		Database::create_tables();

		// Register post types before flushing rewrite rules.
		PostTypes::register_trek_post_type();

		// Flush rewrite rules for our Custom Post Type.
		flush_rewrite_rules();
	}
}
