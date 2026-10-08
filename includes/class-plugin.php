<?php
/**
 * Core plugin class definition
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
 * Main plugin class.
 */
class Plugin {

	/**
	 * Unique identifier of this plugin.
	 *
	 * @var string
	 */
	protected $plugin_name = 'trekpilot';

	/**
	 * Current version of the plugin.
	 *
	 * @var string
	 */
	protected $version = '1.0.2';

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

		// Register Gutenberg blocks (Trek Details, Trek Booking, Trek Archive).
		Blocks::register();

		// Front end: derive Primary/Secondary from the theme's own button/link colors when its variables are unknown.
		add_action( 'wp_footer', array( __CLASS__, 'print_theme_color_detector' ), 99 );
		add_action( 'wp_ajax_trekpilot_save_detected_colors', array( __CLASS__, 'ajax_save_detected_colors' ) );

		// Multisite: create the tables for each newly added sub-site.
		add_action( 'wp_initialize_site', array( Database::class, 'create_tables_for_new_site' ), 20 );

		// Self-healing database table check.
		if ( is_admin() && get_option( 'trekpilot_db_version' ) !== $this->version ) {
			Database::create_tables();
			update_option( 'trekpilot_db_version', $this->version );
		}
		if ( is_admin() ) {
			Database::upgrade_schema();
		}

		// Handle Admin hooks.
		if ( is_admin() ) {
			new \TrekPilot\Admin\Controllers\AdminController();
			new \TrekPilot\Admin\Controllers\TrekMetaBoxController();
			new \TrekPilot\Admin\Controllers\TrekDepartureCitiesController();
			new \TrekPilot\Admin\Controllers\TrekDepartureDatesController();
			new \TrekPilot\Admin\Controllers\TrekItineraryController();
			new \TrekPilot\Admin\Controllers\TrekPickupPointsController();
			new \TrekPilot\Admin\Controllers\TrekPricingController();
			new \TrekPilot\Admin\Controllers\TrekBookingsController();
			new \TrekPilot\Admin\Controllers\TrekImportExportController();
			new \TrekPilot\Admin\Controllers\TrekListController();
		}

		// Handle frontend/global hooks.
		new \TrekPilot\Frontend\Controllers\TrekBookingController();
		new \TrekPilot\Frontend\Controllers\TrekShortcodesController();
		new \TrekPilot\Includes\Elementor();
		new \TrekPilot\Includes\RestApi();
	}

	/**
	 * URL of the WordPress site logo (Appearance > Customize > Site Identity, or the Site Editor's
	 * Site Logo block) when "Use the site logo in emails" is on; empty otherwise.
	 *
	 * @return string
	 */
	public static function get_site_logo_url() {
		if ( ! get_option( 'trekpilot_use_site_logo', true ) ) {
			return '';
		}

		$logo_id = (int) get_theme_mod( 'custom_logo' );
		if ( ! $logo_id ) {
			$logo_id = (int) get_option( 'site_logo' );
		}
		$url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';

		/**
		 * Filters the logo used in booking emails.
		 *
		 * @param string $url Logo URL, or an empty string for none.
		 */
		return (string) apply_filters( 'trekpilot_site_logo_url', $url ? $url : '' );
	}

	/**
	 * Cache-busting version for a bundled asset: the plugin version plus the file's modification time,
	 * so browsers fetch a changed CSS/JS file straight away instead of using a stale cached copy.
	 *
	 * @param string $relative_path Path of the file relative to the plugin folder, e.g. assets/admin/css/x.css.
	 * @return string
	 */
	public static function asset_version( $relative_path ) {
		$file  = TREKPILOT_PATH . ltrim( $relative_path, '/' );
		$mtime = file_exists( $file ) ? filemtime( $file ) : false;
		return $mtime ? TREKPILOT_VERSION . '.' . $mtime : TREKPILOT_VERSION;
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
	 * Palette slugs / Elementor global color ids that stand in for each brand role in a theme.
	 *
	 * @return array<string,array<string,string[]>>
	 */
	private static function theme_color_map() {
		return array(
			'primary'   => array(
				'css'       => array( '--ast-global-color-0', '--wp--preset--color--primary', '--e-global-color-primary', '--wp--preset--color--accent-1' ),
				'palette'   => array( 'astra-primary', 'primary', 'accent-1', 'accent', 'brand' ),
				'elementor' => array( 'primary' ),
			),
			'secondary' => array(
				'css'       => array( '--ast-global-color-1', '--wp--preset--color--secondary', '--e-global-color-secondary', '--wp--preset--color--accent-2' ),
				'palette'   => array( 'astra-secondary', 'secondary', 'accent-2', 'contrast' ),
				'elementor' => array( 'secondary' ),
			),
		);
	}

	/**
	 * Look up a brand color in the active theme (block-theme palette, classic editor palette
	 * or the Elementor kit) as a hex value. Returns an empty string when the theme has none.
	 *
	 * @param string $role 'primary' or 'secondary'.
	 * @return string
	 */
	public static function get_theme_color_hex( $role ) {
		$map = self::theme_color_map();
		if ( ! isset( $map[ $role ] ) ) {
			return '';
		}

		$palette = array();

		// Astra keeps its Global Colors as a numbered list: 0 = accent (primary), 1 = accent hover (secondary).
		if ( function_exists( 'astra_get_option' ) ) {
			$astra = astra_get_option( 'global-color-palette' );
			if ( is_array( $astra ) && ! empty( $astra['palette'] ) && is_array( $astra['palette'] ) ) {
				$palette['astra-primary']   = isset( $astra['palette'][0] ) ? $astra['palette'][0] : '';
				$palette['astra-secondary'] = isset( $astra['palette'][1] ) ? $astra['palette'][1] : '';
			}
		}

		if ( function_exists( 'wp_get_global_settings' ) ) {
			$global = wp_get_global_settings( array( 'color', 'palette' ) );
			if ( is_array( $global ) ) {
				foreach ( array( 'theme', 'custom' ) as $origin ) {
					if ( ! empty( $global[ $origin ] ) && is_array( $global[ $origin ] ) ) {
						foreach ( $global[ $origin ] as $entry ) {
							if ( isset( $entry['slug'], $entry['color'] ) ) {
								$palette[ $entry['slug'] ] = $entry['color'];
							}
						}
					}
				}
			}
		}

		$support = get_theme_support( 'editor-color-palette' );
		if ( ! empty( $support[0] ) && is_array( $support[0] ) ) {
			foreach ( $support[0] as $entry ) {
				if ( isset( $entry['slug'], $entry['color'] ) && ! isset( $palette[ $entry['slug'] ] ) ) {
					$palette[ $entry['slug'] ] = $entry['color'];
				}
			}
		}

		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->kits_manager ) ) {
			$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit_for_frontend();
			if ( $kit ) {
				foreach ( (array) $kit->get_settings( 'system_colors' ) as $entry ) {
					if ( isset( $entry['_id'], $entry['color'] ) && in_array( $entry['_id'], $map[ $role ]['elementor'], true ) ) {
						$palette[ 'elementor-' . $entry['_id'] ] = $entry['color'];
					}
				}
			}
		}

		// The theme's own palette outranks the Elementor kit, whose untouched defaults
		// (e.g. #6EC1E4) would otherwise hide the theme's real colors.
		$candidates = array_merge(
			$map[ $role ]['palette'],
			array_map(
				static function ( $id ) {
					return 'elementor-' . $id;
				},
				$map[ $role ]['elementor']
			)
		);

		foreach ( $candidates as $slug ) {
			if ( ! empty( $palette[ $slug ] ) ) {
				$hex = sanitize_hex_color( $palette[ $slug ] );
				if ( $hex ) {
					return $hex;
				}
			}
		}

		return '';
	}

	/**
	 * The brand color for a role: the value saved in Settings > Color, otherwise the theme's
	 * color. Returns an empty string when neither exists (the plugin sets no color of its own).
	 *
	 * @param string $role 'primary' or 'secondary'.
	 * @return string Hex value or ''.
	 */
	public static function get_brand_color( $role ) {
		$saved = sanitize_hex_color( (string) get_option( 'trekpilot_' . $role . '_color', '' ) );
		if ( $saved ) {
			return $saved;
		}

		$theme = self::get_theme_color_hex( $role );
		if ( $theme ) {
			return $theme;
		}

		// Colors read from the theme's rendered buttons/links by an admin visit (see below).
		$cache = get_option( 'trekpilot_detected_colors', array() );
		$key   = get_stylesheet();
		if ( is_array( $cache ) && isset( $cache[ $key ][ $role ] ) ) {
			$hex = sanitize_hex_color( $cache[ $key ][ $role ] );
			if ( $hex ) {
				return $hex;
			}
		}

		return '';
	}

	/**
	 * AJAX: store the colors an administrator's browser detected from the active theme, so the
	 * server can output them directly for every visitor from then on.
	 *
	 * @return void
	 */
	public static function ajax_save_detected_colors() {
		check_ajax_referer( 'trekpilot_detected_colors', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}

		$primary   = isset( $_POST['primary'] ) ? sanitize_hex_color( wp_unslash( $_POST['primary'] ) ) : '';
		$secondary = isset( $_POST['secondary'] ) ? sanitize_hex_color( wp_unslash( $_POST['secondary'] ) ) : '';
		if ( ! $primary || ! $secondary ) {
			wp_send_json_error( null, 400 );
		}

		$cache                      = get_option( 'trekpilot_detected_colors', array() );
		$cache                      = is_array( $cache ) ? $cache : array();
		$cache[ get_stylesheet() ] = array(
			'primary'   => $primary,
			'secondary' => $secondary,
		);
		update_option( 'trekpilot_detected_colors', $cache, false );

		wp_send_json_success();
	}

	/**
	 * Build the :root CSS custom properties block for the Primary/Secondary brand colors.
	 *
	 * A color saved in Settings > Color always wins. When it is left empty, the public site
	 * follows the active theme (its CSS color variables, live) and the admin screens use the
	 * theme's hex value. If the theme has none either, no variable is output.
	 *
	 * @return string
	 */
	public static function get_dynamic_color_css() {
		$map   = self::theme_color_map();
		$rules = '';

		foreach ( array( 'primary', 'secondary' ) as $role ) {
			$value = self::get_brand_color( $role );

			if ( ! sanitize_hex_color( (string) get_option( 'trekpilot_' . $role . '_color', '' ) ) && ! is_admin() ) {
				$inner = $value;
				foreach ( array_reverse( $map[ $role ]['css'] ) as $variable ) {
					$inner = 'var(' . $variable . ( '' !== $inner ? ', ' . $inner : '' ) . ')';
				}
				$value = $inner;
			}

			if ( '' !== $value ) {
				$rules .= '--trekpilot-' . $role . '-color:' . $value . ';';
			}
		}

		return '' !== $rules ? ':root{' . $rules . '}' : '';
	}

	/**
	 * Last-resort theme colors for themes whose color variables the plugin doesn't know.
	 *
	 * When Settings > Color is empty and none of the known theme variables resolved, read the
	 * theme's own button background / link color from the browser and use that as Primary
	 * (Secondary is a darker shade of it). Runs only on pages that load a TrekPilot stylesheet.
	 *
	 * @return void
	 */
	public static function print_theme_color_detector() {
		$handles = array( 'trekpilot-public-details-css', 'trekpilot-public-archive-css', 'trekpilot-public-booking-css', 'trekpilot-blocks-details-css', 'trekpilot-blocks-booking-css', 'trekpilot-blocks-archive-css' );
		$loaded  = false;
		foreach ( $handles as $handle ) {
			if ( wp_style_is( $handle, 'enqueued' ) || wp_style_is( $handle, 'done' ) ) {
				$loaded = true;
				break;
			}
		}
		if ( ! $loaded ) {
			return;
		}

		$need_primary   = ! sanitize_hex_color( (string) get_option( 'trekpilot_primary_color', '' ) );
		$need_secondary = ! sanitize_hex_color( (string) get_option( 'trekpilot_secondary_color', '' ) );
		if ( ! $need_primary && ! $need_secondary ) {
			return;
		}

		$report = current_user_can( 'manage_options' )
			? wp_json_encode(
				array(
					'url'   => admin_url( 'admin-ajax.php' ),
					'nonce' => wp_create_nonce( 'trekpilot_detected_colors' ),
				)
			)
			: 'null';

		$js = "(function(){var rep=" . $report . ";var r=document.documentElement,cs=getComputedStyle(r);" .
			"function has(n){return cs.getPropertyValue(n).trim()!=='';}" .
			"function rgb(c){var m=/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?/.exec(c||'');return m&&(m[4]===undefined||parseFloat(m[4])>0)?[+m[1],+m[2],+m[3]]:null;}" .
			"function pick(){var b=document.createElement('button');b.className='button wp-element-button';var a=document.createElement('a');a.href='#';a.textContent='x';" .
			"b.style.cssText=a.style.cssText='position:absolute;left:-9999px;';document.body.appendChild(b);document.body.appendChild(a);" .
			"var bg=rgb(getComputedStyle(b).backgroundColor),ln=rgb(getComputedStyle(a).color);document.body.removeChild(b);document.body.removeChild(a);" .
			"if(bg&&!(bg[0]===239&&bg[1]===239&&bg[2]===239)){return bg;}return ln;}" .
			"function css(c){return 'rgb('+c[0]+','+c[1]+','+c[2]+')';}" .
			"var needP=!has('--trekpilot-primary-color'),needS=!has('--trekpilot-secondary-color');if(!needP&&!needS){return;}" .
			"var p=pick();if(!p){return;}" .
			"if(needP){r.style.setProperty('--trekpilot-primary-color',css(p));}" .
			"if(needS){r.style.setProperty('--trekpilot-secondary-color',css(p.map(function(v){return Math.round(v*0.8);})));}" .
			"if(rep&&needP&&needS){function hx(c){return '#'+c.map(function(v){return ('0'+v.toString(16)).slice(-2);}).join('');}" .
			"var q=new URLSearchParams({action:'trekpilot_save_detected_colors',nonce:rep.nonce,primary:hx(p),secondary:hx(p.map(function(v){return Math.round(v*0.8);}))});" .
			"fetch(rep.url,{method:'POST',credentials:'same-origin',body:q});}" .
			"})();";

		wp_print_inline_script_tag( $js );
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
		// Emails can't use CSS variables, so they need a hex; neutral dark when no color is configured.
		$primary   = self::get_brand_color( 'primary' ) ?: '#333333';
		$secondary = self::get_brand_color( 'secondary' ) ?: '#333333';

		$site_name = get_bloginfo( 'name' );
		$logo_url  = self::get_site_logo_url();
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
			$rows_html .= '<td style="padding:10px 15px; font-size:12px; font-weight:700; color:#000000; border-bottom:1px solid #eee; width:150px; vertical-align:top;">' . esc_html( $row['label'] ) . '</td>';
			$rows_html .= '<td style="padding:10px 15px; font-size:13px; font-weight:700; color:#000000; border-bottom:1px solid #eee; vertical-align:top;">' . esc_html( $row['value'] ) . '</td>';
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
		$html .= '<h2 style="margin:0 0 12px 0; font-size:20px; font-weight:700; color:#000000;">' . esc_html( $heading ) . '</h2>';
		$html .= '<p style="margin:0 0 20px 0; font-size:13px; font-weight:700; color:#000000; line-height:1.6;">' . esc_html( $intro ) . '</p>';
		$html .= '</td></tr>';
		$html .= '<tr><td style="padding:0 25px 10px 25px;">';
		$html .= '<table role="presentation" width="100%" style="border-collapse:collapse; background:#fafafa; border:1px solid #eee; border-radius:6px; overflow:hidden;">' . $rows_html . '</table>';
		$html .= '</td></tr>';
		if ( $cta_html ) {
			$html .= '<tr><td style="padding:0 25px;"><table role="presentation" width="100%">' . $cta_html . '</table></td></tr>';
		}
		$html .= '<tr><td style="padding:20px 25px 30px 25px;"><p style="margin:0; font-size:12px; font-weight:700; color:#000000;">' . esc_html__( 'If you have any questions, simply reply to this email and our team will be happy to assist you.', 'trekpilot' ) . '</p></td></tr>';
		$html .= '<tr><td style="background:' . esc_attr( $secondary ) . '; padding:15px 20px; text-align:center;"><span style="color:#ffffff; font-size:11px;">&copy; ' . esc_html( $year ) . ' ' . esc_html( $site_name ) . '</span></td></tr>';
		$html .= '</table>';
		$html .= '</div>';

		return $html;
	}
}
