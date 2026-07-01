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
	protected $version = '1.0.0';

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
			new \AdventureTreks\Admin\Controllers\TrekPricingController();
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
}
