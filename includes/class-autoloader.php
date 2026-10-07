<?php
/**
 * Autoloader implementation for TrekPilot
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
	 * @param string $fully_qualified_class The fully qualified class name.
	 * @return void
	 */
	public static function autoload( $fully_qualified_class ) {
		// Project-specific namespace prefix.
		$prefix = 'TrekPilot\\';

		// Base directory for the namespace prefix.
		$base_dir = plugin_dir_path( __DIR__ );

		// Does the class use the namespace prefix?
		$len = strlen( $prefix );
		if ( strncmp( $prefix, $fully_qualified_class, $len ) !== 0 ) {
			// No, move to the next registered autoloader.
			return;
		}

		// Get the relative class name.
		$relative_class = substr( $fully_qualified_class, $len );

		// Split the relative class into parts.
		$parts = explode( '\\', $relative_class );

		// The last part is the class name itself; the rest are sub-namespace
		// directories ('Controllers', 'Widgets', etc.).
		$class_name = array_pop( $parts );

		// Lowercase every directory part to match the plugin's lowercase directory names.
		$parts = array_map( 'strtolower', $parts );

		// The Frontend namespace lives in the public/ directory ("Public" is a reserved word in PHP 7.x namespaces).
		if ( ! empty( $parts ) && 'frontend' === $parts[0] ) {
			$parts[0] = 'public';
		}

		// Rebuild the directory path with directory separators.
		$relative_path = implode( '/', $parts );

		// Build the full file path using the WordPress "class-name-here.php" file naming convention.
		$file = $base_dir . ( $relative_path ? $relative_path . '/' : '' ) . self::class_name_to_file_name( $class_name );

		// If the file exists, require it.
		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}

	/**
	 * Convert a PHP class name (e.g. "TrekPricingController") into its
	 * WordPress-convention file name (e.g. "class-trekpricingcontroller.php"),
	 * matching the WordPress.Files.FileName WPCS sniff exactly.
	 *
	 * @param string $class_name The unqualified class name.
	 * @return string
	 */
	private static function class_name_to_file_name( $class_name ) {
		return 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';
	}
}
