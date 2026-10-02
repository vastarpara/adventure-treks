<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package    AdventureTreks
 * @author     Nilesh Vastarpara
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load Autoloader to resolve database class.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-autoloader.php';
AdventureTreks\Includes\Autoloader::register();

// Check if the setting to remove data is enabled.
if ( get_option( 'at_remove_data_on_uninstall' ) ) {
	// Drop custom database tables.
	AdventureTreks\Includes\Database::drop_tables();

	// Delete all 'adventure_trek' posts.
	$treks = get_posts(
		array(
			'post_type'      => 'adventure_trek',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		)
	);

	if ( ! empty( $treks ) ) {
		foreach ( $treks as $trek_id ) {
			wp_delete_post( $trek_id, true );
		}
	}

	// Delete plugin options (if any are saved in options table).
	delete_option( 'adventure_treks_version' );
	delete_option( 'at_currency_symbol' );
	delete_option( 'at_currency_position' );
	delete_option( 'at_thousand_separator' );
	delete_option( 'at_decimal_separator' );
	delete_option( 'at_price_decimals' );
	delete_option( 'at_booking_email' );
	delete_option( 'at_enable_schema' );
	delete_option( 'at_remove_data_on_uninstall' );

	// Clear scheduled actions or transients if any, e.g. delete_expired_transients( true ).
}
