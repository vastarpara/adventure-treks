<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package    TrekPilot
 * @author     Nilesh Vastarpara
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Load Autoloader to resolve database class.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-autoloader.php';
TrekPilot\Includes\Autoloader::register();

/**
 * Remove the plugin's data when the "remove data" option is enabled.
 *
 * @return void
 */
function trekpilot_uninstall() {
	// Check if the setting to remove data is enabled.
	if ( get_option( 'trekpilot_remove_data_on_uninstall' ) ) {
		// Drop custom database tables.
		TrekPilot\Includes\Database::drop_tables();

		// Delete all 'trekpilot_trek' posts.
		$trekpilot_posts = get_posts(
			array(
				'post_type'      => 'trekpilot_trek',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $trekpilot_posts ) ) {
			foreach ( $trekpilot_posts as $trekpilot_post_id ) {
				wp_delete_post( $trekpilot_post_id, true );
			}
		}

		// Delete plugin options (if any are saved in options table).
		delete_option( 'trekpilot_version' );
		delete_option( 'trekpilot_currency_symbol' );
		delete_option( 'trekpilot_currency_position' );
		delete_option( 'trekpilot_thousand_separator' );
		delete_option( 'trekpilot_decimal_separator' );
		delete_option( 'trekpilot_price_decimals' );
		delete_option( 'trekpilot_booking_email' );
		delete_option( 'trekpilot_enable_schema' );
		delete_option( 'trekpilot_use_site_logo' );
		delete_option( 'trekpilot_site_logo' );
		delete_option( 'trekpilot_remove_data_on_uninstall' );

		// Clear scheduled actions or transients if any, e.g. delete_expired_transients( true ).
	}
}

trekpilot_uninstall();
