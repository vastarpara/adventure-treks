<?php
/**
 * Core plugin class definition
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
 * Main plugin class.
 */
class Plugin {

	/**
	 * Unique identifier of this plugin.
	 *
	 * @var string
	 */
	protected $plugin_name = 'adventure-treks';

	/**
	 * Current version of the plugin.
	 *
	 * @var string
	 */
	protected $version = '1.0.1';

	/**
	 * The single instance of the class.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get main instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->set_locale();
	}

	/**
	 * Load text domain for translation.
	 *
	 * @return void
	 */
	private function set_locale() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Load translation files.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		// phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound
		load_plugin_textdomain(
			$this->plugin_name,
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}

	/**
	 * Run the plugin loop.
	 *
	 * Executes all WordPress hooks and registrations.
	 *
	 * @return void
	 */
	public function run() {
		// Register Custom Post Types.
		PostTypes::register();

		// Self-healing database table check.
		if ( is_admin() && get_option( 'adventure_treks_db_version' ) !== $this->version ) {
			Database::create_tables();
			update_option( 'adventure_treks_db_version', $this->version );
		}

		// Handle Admin hooks.
		if ( is_admin() ) {
			new \AdventureTreks\Admin\Controllers\AdminController();
			new \AdventureTreks\Admin\Controllers\TrekMetaBoxController();
			new \AdventureTreks\Admin\Controllers\TrekDepartureCitiesController();
			new \AdventureTreks\Admin\Controllers\TrekDepartureDatesController();
			new \AdventureTreks\Admin\Controllers\TrekItineraryController();
			new \AdventureTreks\Admin\Controllers\TrekPickupPointsController();
			new \AdventureTreks\Admin\Controllers\TrekPricingController();
			new \AdventureTreks\Admin\Controllers\TrekBookingsController();
		}

		// Handle Public/Global hooks.
		new \AdventureTreks\Public\Controllers\TrekBookingController();
		new \AdventureTreks\Public\Controllers\TrekShortcodesController();
		new \AdventureTreks\Includes\Elementor();
		new \AdventureTreks\Includes\RestApi();
	}

	/**
	 * Retrieve plugin name.
	 *
	 * @return string
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve plugin version.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Build the `:root` CSS custom properties block for the admin-configured
	 * Primary/Secondary brand colors, so the same setting drives styling
	 * across both the admin screens and the public-facing templates.
	 *
	 * @return string
	 */
	public static function get_dynamic_color_css() {
		$primary   = sanitize_hex_color( get_option( 'at_primary_color', '#137a7f' ) );
		$secondary = sanitize_hex_color( get_option( 'at_secondary_color', '#0f6165' ) );

		if ( ! $primary ) {
			$primary = '#137a7f';
		}
		if ( ! $secondary ) {
			$secondary = '#0f6165';
		}

		return ":root{--at-primary-color:{$primary};--at-secondary-color:{$secondary};}";
	}

	/**
	 * Build a branded HTML email (logo/site-name header bar, heading, intro
	 * copy, a details table, an optional CTA button, and a footer bar) used
	 * for booking confirmation and status update notifications.
	 *
	 * @param string $heading      Main heading shown under the header bar.
	 * @param string $intro        Intro paragraph (plain text; will be escaped).
	 * @param array  $details_rows List of ['label' => string, 'value' => string] pairs. Rows with an empty value are skipped.
	 * @param string $cta_label    Optional CTA button label.
	 * @param string $cta_url      Optional CTA button URL. Button is omitted if either this or $cta_label is empty.
	 * @return string
	 */
	public static function render_email_html( $heading, $intro, $details_rows, $cta_label = '', $cta_url = '' ) {
		$primary = sanitize_hex_color( get_option( 'at_primary_color', '#137a7f' ) );
		if ( ! $primary ) {
			$primary = '#137a7f';
		}
		$secondary = sanitize_hex_color( get_option( 'at_secondary_color', '#0f6165' ) );
		if ( ! $secondary ) {
			$secondary = '#0f6165';
		}

		$site_name = get_bloginfo( 'name' );
		$logo_url  = get_option( 'at_site_logo', '' );
		$year      = gmdate( 'Y' );

		$header_brand = $logo_url
			? '<img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( $site_name ) . '" style="max-height:60px; max-width:220px; display:block; margin:0 auto;" />'
			: '<span style="color:#ffffff; font-size:22px; font-weight:700; letter-spacing:0.5px;">' . esc_html( $site_name ) . '</span>';

		$rows_html = '';
		foreach ( $details_rows as $row ) {
			if ( ! isset( $row['value'] ) || '' === $row['value'] ) {
				continue;
			}
			$rows_html .= '<tr>';
			$rows_html .= '<td style="padding:10px 15px; font-size:12px; font-weight:700; color:' . esc_attr( $primary ) . '; border-bottom:1px solid #eee; width:150px; vertical-align:top;">' . esc_html( $row['label'] ) . '</td>';
			$rows_html .= '<td style="padding:10px 15px; font-size:13px; color:#333; border-bottom:1px solid #eee; vertical-align:top;">' . esc_html( $row['value'] ) . '</td>';
			$rows_html .= '</tr>';
		}

		$cta_html = '';
		if ( $cta_label && $cta_url ) {
			$cta_html  = '<tr><td align="center" style="padding:10px 0 5px 0;">';
			$cta_html .= '<a href="' . esc_url( $cta_url ) . '" style="background:' . esc_attr( $primary ) . '; color:#ffffff; text-decoration:none; font-weight:700; font-size:13px; padding:12px 28px; border-radius:5px; display:inline-block;">' . esc_html( $cta_label ) . '</a>';
			$cta_html .= '</td></tr>';
		}

		$html  = '<div style="background:#f4f4f5; padding:30px 15px; font-family:Arial, Helvetica, sans-serif;">';
		$html .= '<table role="presentation" width="100%" style="max-width:600px; margin:0 auto; border-collapse:collapse; background:#ffffff; border-radius:8px; overflow:hidden;">';
		$html .= '<tr><td style="background:' . esc_attr( $primary ) . '; padding:22px 20px; text-align:center;">' . $header_brand . '</td></tr>';
		$html .= '<tr><td style="padding:30px 25px 10px 25px;">';
		$html .= '<h2 style="margin:0 0 12px 0; font-size:20px; color:' . esc_attr( $secondary ) . ';">' . esc_html( $heading ) . '</h2>';
		$html .= '<p style="margin:0 0 20px 0; font-size:13px; color:#555; line-height:1.6;">' . esc_html( $intro ) . '</p>';
		$html .= '</td></tr>';
		$html .= '<tr><td style="padding:0 25px 10px 25px;">';
		$html .= '<table role="presentation" width="100%" style="border-collapse:collapse; background:#fafafa; border:1px solid #eee; border-radius:6px; overflow:hidden;">' . $rows_html . '</table>';
		$html .= '</td></tr>';
		if ( $cta_html ) {
			$html .= '<tr><td style="padding:0 25px;"><table role="presentation" width="100%">' . $cta_html . '</table></td></tr>';
		}
		$html .= '<tr><td style="padding:20px 25px 30px 25px;"><p style="margin:0; font-size:12px; color:#888;">' . esc_html__( 'If you have any questions, simply reply to this email and our team will be happy to assist you.', 'adventure-treks' ) . '</p></td></tr>';
		$html .= '<tr><td style="background:' . esc_attr( $secondary ) . '; padding:15px 20px; text-align:center;"><span style="color:#ffffff; font-size:11px;">&copy; ' . esc_html( $year ) . ' ' . esc_html( $site_name ) . '</span></td></tr>';
		$html .= '</table>';
		$html .= '</div>';

		return $html;
	}
}
