<?php
/**
 * Autoloader implementation for Adventure Treks
 *
 * @package    AdventureTreks
 * @subpackage AdventureTreks/Includes
 * @author     Nilesh Vastarpara
 */

namespace AdventureTreks\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Autoloader class.
 */
class Autoloader {

	/**
	 * Register spl_autoload_register.
	 *
	 * @return void
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	/**
	 * Autoload class files.
	 *
	 * @param string $class The class name.
	 * @return void
	 */
	public static function autoload( $class ) {
		// Project-specific namespace prefix.
		$prefix = 'AdventureTreks\\';

		// Base directory for the namespace prefix.
		$base_dir = plugin_dir_path( dirname( __FILE__ ) );

		// Does the class use the namespace prefix?
		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			// No, move to the next registered autoloader.
			return;
		}

		// Get the relative class name.
		$relative_class = substr( $class, $len );

		// Split the relative class into parts.
		$parts = explode( '\\', $relative_class );

		// Lowercase the first part to match directory names ('includes', 'admin', 'public').
		if ( isset( $parts[0] ) ) {
			$parts[0] = strtolower( $parts[0] );
		}

		// Rebuild the relative path with directory separators.
		$relative_path = implode( '/', $parts );

		// Build the full file path.
		$file = $base_dir . $relative_path . '.php';

		// If the file exists, require it.
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
