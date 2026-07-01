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
		register_setting( 'adventure_treks_settings_group', 'at_currency_symbol', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '₹',
		) );

		register_setting( 'adventure_treks_settings_group', 'at_booking_email', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_email',
			'default'           => get_option( 'admin_email' ),
		) );

		register_setting( 'adventure_treks_settings_group', 'at_enable_schema', array(
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => true,
		) );

		register_setting( 'adventure_treks_settings_group', 'at_remove_data_on_uninstall', array(
			'type'              => 'boolean',
			'sanitize_callback' => 'rest_sanitize_boolean',
			'default'           => false,
		) );
	}

	/**
	 * Render settings page view.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		// Output the settings form view.
		$view_path = plugin_dir_path( dirname( __FILE__ ) ) . 'Views/settings.php';
		if ( file_exists( $view_path ) ) {
			include $view_path;
		}
	}
}
