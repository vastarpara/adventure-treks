<?php
/**
 * Main Admin Area Controller
 *
 * @package    AdventureTreks
 * @subpackage AdventureTreks/Admin/Controllers
 * @author     Nilesh Vastarpara
 */

namespace AdventureTreks\Admin\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * AdminController class.
 */
class AdminController {

	/**
	 * Constructor to wire admin actions.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_admin_menus' ) );
		add_action( 'admin_init', array( $this, 'register_settings_fields' ) );
	}

	/**
	 * Register submenus under edit.php?post_type=adventure_trek.
	 *
	 * @return void
	 */
	public function register_admin_menus() {
		add_submenu_page(
			'edit.php?post_type=adventure_trek',
			__( 'Adventure Treks Settings', 'adventure-treks' ),
			__( 'Settings', 'adventure-treks' ),
			'manage_options',
			'adventure-treks-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Registers setting groups and fields.
	 *
	 * @return void
	 */
	public function register_settings_fields() {
		register_setting(
			'adventure_treks_settings_group',
			'at_currency_symbol',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_currency_symbol' ),
				'default'           => '₹',
			)
		);

		register_setting(
			'adventure_treks_settings_group',
			'at_booking_email',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_email',
				'default'           => get_option( 'admin_email' ),
			)
		);

		register_setting(
			'adventure_treks_settings_group',
			'at_enable_schema',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => true,
			)
		);

		register_setting(
			'adventure_treks_settings_group',
			'at_remove_data_on_uninstall',
			array(
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'default'           => false,
			)
		);
	}

	/**
	 * Render settings page view.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		// Output the settings form view.
		$view_path = plugin_dir_path( __DIR__ ) . 'views/settings.php';
		if ( file_exists( $view_path ) ) {
			include $view_path;
		}
	}

	/**
	 * Get predefined currencies.
	 *
	 * @return array
	 */
	public static function get_currencies() {
		return array(
			'₹'   => '₹ (Indian Rupee)',
			'$'   => '$ (US Dollar)',
			'€'   => '€ (Euro)',
			'£'   => '£ (British Pound)',
			'¥'   => '¥ (Japanese Yen / Chinese Yuan)',
			'A$'  => 'A$ (Australian Dollar)',
			'C$'  => 'C$ (Canadian Dollar)',
			'Fr'  => 'Fr (Swiss Franc)',
			'NZ$' => 'NZ$ (New Zealand Dollar)',
			'kr'  => 'kr (Swedish/Norwegian/Danish Krone)',
			'R$'  => 'R$ (Brazilian Real)',
			'R'   => 'R (South African Rand)',
			'AED' => 'AED (UAE Dirham)',
			'฿'   => '฿ (Thai Baht)',
			'Rp'  => 'Rp (Indonesian Rupiah)',
		);
	}

	/**
	 * Sanitize currency symbol.
	 *
	 * @param string $input Input currency symbol.
	 * @return string
	 */
	public function sanitize_currency_symbol( $input ) {
		$input = sanitize_text_field( $input );

		$currencies = self::get_currencies();
		if ( array_key_exists( $input, $currencies ) ) {
			return $input;
		}

		return get_option( 'at_currency_symbol', '₹' );
	}
}
