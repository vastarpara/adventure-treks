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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_dynamic_color_vars' ), 20 );
	}

	/**
	 * Attach the admin-configured Primary/Secondary colors as CSS custom
	 * properties to the admin stylesheet that uses them. Runs late (after the
	 * registering controllers) and no-ops on any screen where that stylesheet
	 * was never registered.
	 *
	 * @return void
	 */
	public function add_dynamic_color_vars() {
		wp_add_inline_style( 'at-admin-departures-css', \AdventureTreks\Includes\Plugin::get_dynamic_color_css() );
	}

	/**
	 * Enqueue CSS/JS for the settings screen only.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'adventure-treks-settings' !== $page ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_media();

		wp_enqueue_style(
			'at-admin-settings-css',
			ADVENTURE_TREKS_URL . 'assets/admin/css/admin-settings.css',
			array(),
			ADVENTURE_TREKS_VERSION
		);

		wp_enqueue_script(
			'at-admin-settings-js',
			ADVENTURE_TREKS_URL . 'assets/admin/js/admin-settings.js',
			array( 'wp-color-picker' ),
			ADVENTURE_TREKS_VERSION . '.' . filemtime( ADVENTURE_TREKS_PATH . 'assets/admin/js/admin-settings.js' ),
			true
		);
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
			'at_currency_position',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_currency_position' ),
				'default'           => 'left',
			)
		);

		register_setting(
			'adventure_treks_settings_group',
			'at_thousand_separator',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_separator' ),
				'default'           => ',',
			)
		);

		register_setting(
			'adventure_treks_settings_group',
			'at_decimal_separator',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_decimal_separator' ),
				'default'           => '.',
			)
		);

		register_setting(
			'adventure_treks_settings_group',
			'at_price_decimals',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_price_decimals' ),
				'default'           => 2,
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
			'at_from_name',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => get_bloginfo( 'name' ),
			)
		);

		register_setting(
			'adventure_treks_settings_group',
			'at_site_logo',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
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
			'at_primary_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '',
			)
		);

		register_setting(
			'adventure_treks_settings_group',
			'at_secondary_color',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_hex_color',
				'default'           => '',
			)
		);

		register_setting(
			'adventure_treks_settings_group',
			'at_payment_method',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_payment_method' ),
				'default'           => 'cash',
			)
		);

		register_setting(
			'adventure_treks_settings_group',
			'at_upi_id',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			'adventure_treks_settings_group',
			'at_upi_qr_code',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'esc_url_raw',
				'default'           => '',
			)
		);
	}

	/**
	 * Sanitize payment method selection.
	 *
	 * @param string $input Input payment method.
	 * @return string
	 */
	public function sanitize_payment_method( $input ) {
		$input = sanitize_text_field( $input );

		return in_array( $input, array( 'cash', 'upi' ), true ) ? $input : 'cash';
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
			'₹'   => __( '₹ (Indian Rupee)', 'adventure-treks' ),
			'$'   => __( '$ (US Dollar)', 'adventure-treks' ),
			'€'   => __( '€ (Euro)', 'adventure-treks' ),
			'£'   => __( '£ (British Pound)', 'adventure-treks' ),
			'¥'   => __( '¥ (Japanese Yen / Chinese Yuan)', 'adventure-treks' ),
			'A$'  => __( 'A$ (Australian Dollar)', 'adventure-treks' ),
			'C$'  => __( 'C$ (Canadian Dollar)', 'adventure-treks' ),
			'Fr'  => __( 'Fr (Swiss Franc)', 'adventure-treks' ),
			'NZ$' => __( 'NZ$ (New Zealand Dollar)', 'adventure-treks' ),
			'kr'  => __( 'kr (Swedish/Norwegian/Danish Krone)', 'adventure-treks' ),
			'R$'  => __( 'R$ (Brazilian Real)', 'adventure-treks' ),
			'R'   => __( 'R (South African Rand)', 'adventure-treks' ),
			'AED' => __( 'AED (UAE Dirham)', 'adventure-treks' ),
			'฿'   => __( '฿ (Thai Baht)', 'adventure-treks' ),
			'Rp'  => __( 'Rp (Indonesian Rupiah)', 'adventure-treks' ),
		);
	}

	/**
	 * Allowed positions of the currency symbol relative to the amount.
	 *
	 * @return array
	 */
	public static function get_currency_positions() {
		return array(
			'left'        => __( 'Left (₹99.99)', 'adventure-treks' ),
			'right'       => __( 'Right (99.99₹)', 'adventure-treks' ),
			'left_space'  => __( 'Left with space (₹ 99.99)', 'adventure-treks' ),
			'right_space' => __( 'Right with space (99.99 ₹)', 'adventure-treks' ),
		);
	}

	/**
	 * Currency display options saved on the Currency settings tab.
	 *
	 * @return array{symbol:string,position:string,thousand:string,decimal:string,decimals:int}
	 */
	public static function get_price_format() {
		$position = get_option( 'at_currency_position', 'left' );
		if ( ! array_key_exists( $position, self::get_currency_positions() ) ) {
			$position = 'left';
		}

		return array(
			'symbol'   => get_option( 'at_currency_symbol', '₹' ),
			'position' => $position,
			'thousand' => (string) get_option( 'at_thousand_separator', ',' ),
			'decimal'  => (string) ( get_option( 'at_decimal_separator', '.' ) ?: '.' ),
			'decimals' => max( 0, min( 4, (int) get_option( 'at_price_decimals', 2 ) ) ),
		);
	}

	/**
	 * Format an amount using the Currency settings (symbol, position, separators, decimals).
	 *
	 * @param float|string $amount Amount to format.
	 * @return string Plain text, not escaped.
	 */
	public static function format_price( $amount ) {
		$fmt    = self::get_price_format();
		$number = number_format( abs( (float) $amount ), $fmt['decimals'], $fmt['decimal'], $fmt['thousand'] );
		$sign   = (float) $amount < 0 ? '-' : '';

		switch ( $fmt['position'] ) {
			case 'right':
				return $sign . $number . $fmt['symbol'];
			case 'left_space':
				return $sign . $fmt['symbol'] . ' ' . $number;
			case 'right_space':
				return $sign . $number . ' ' . $fmt['symbol'];
			default:
				return $sign . $fmt['symbol'] . $number;
		}
	}

	/**
	 * Sanitize currency position.
	 *
	 * @param string $input Input position key.
	 * @return string
	 */
	public function sanitize_currency_position( $input ) {
		$input = sanitize_text_field( $input );

		return array_key_exists( $input, self::get_currency_positions() ) ? $input : 'left';
	}

	/**
	 * Sanitize a thousand / decimal separator (a single non-alphanumeric character, or empty).
	 *
	 * @param string $input Input separator.
	 * @return string
	 */
	public function sanitize_separator( $input ) {
		$input = (string) $input;
		$input = '' === $input ? '' : mb_substr( $input, 0, 1 );

		return preg_match( '/^[\p{L}\p{N}]$/u', $input ) ? '' : $input;
	}

	/**
	 * Sanitize the decimal separator: like the thousand separator, but it can never be empty.
	 *
	 * @param string $input Input separator.
	 * @return string
	 */
	public function sanitize_decimal_separator( $input ) {
		$separator = $this->sanitize_separator( $input );

		return '' === $separator ? '.' : $separator;
	}

	/**
	 * Sanitize number of decimals (0 - 4).
	 *
	 * @param mixed $input Input value.
	 * @return int
	 */
	public function sanitize_price_decimals( $input ) {
		return max( 0, min( 4, (int) $input ) );
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
