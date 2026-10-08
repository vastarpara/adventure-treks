<?php
/**
 * Controller for managing Frontend Booking Widget and AJAX handlers.
 *
 * @package    TrekPilot
 * @subpackage TrekPilot/Frontend/Controllers
 * @author     Nilesh Vastarpara
 */

namespace TrekPilot\Frontend\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * TrekBookingController class.
 */
class TrekBookingController {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Register shortcode.
		add_shortcode( 'trekpilot_booking', array( $this, 'render_booking_widget' ) );

		// Register AJAX actions (no privileges required for public visitors).
		add_action( 'wp_ajax_trekpilot_get_booking_dates', array( $this, 'ajax_get_dates' ) );
		add_action( 'wp_ajax_nopriv_trekpilot_get_booking_dates', array( $this, 'ajax_get_dates' ) );

		add_action( 'wp_ajax_trekpilot_get_transport_options', array( $this, 'ajax_get_transport_options' ) );
		add_action( 'wp_ajax_nopriv_trekpilot_get_transport_options', array( $this, 'ajax_get_transport_options' ) );

		add_action( 'wp_ajax_trekpilot_get_booking_details', array( $this, 'ajax_get_booking_details' ) );
		add_action( 'wp_ajax_nopriv_trekpilot_get_booking_details', array( $this, 'ajax_get_booking_details' ) );

		add_action( 'wp_ajax_trekpilot_submit_booking', array( $this, 'ajax_submit_booking' ) );
		add_action( 'wp_ajax_nopriv_trekpilot_submit_booking', array( $this, 'ajax_submit_booking' ) );

		// Load CSS/JS.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_dynamic_color_vars' ), 20 );
	}

	/**
	 * Attach the admin-configured Primary/Secondary colors as CSS custom
	 * properties to the booking widget stylesheet. No-ops on pages where
	 * that stylesheet was never registered.
	 *
	 * @return void
	 */
	public function add_dynamic_color_vars() {
		wp_add_inline_style( 'trekpilot-public-booking-css', \TrekPilot\Includes\Plugin::get_dynamic_color_css() );
	}

	/**
	 * Register frontend CSS and JS.
	 */
	public function enqueue_assets() {
		// Public stylesheets.
		wp_register_style(
			'trekpilot-public-booking-css',
			TREKPILOT_URL . 'assets/public/css/booking-widget.css',
			array( 'dashicons' ),
			TREKPILOT_VERSION . '.' . filemtime( TREKPILOT_PATH . 'assets/public/css/booking-widget.css' )
		);

		// Public JavaScripts.
		wp_register_script(
			'trekpilot-public-booking-js',
			TREKPILOT_URL . 'assets/public/js/booking-widget.js',
			array(),
			TREKPILOT_VERSION . '.' . filemtime( TREKPILOT_PATH . 'assets/public/js/booking-widget.js' ),
			true
		);

		// Localize frontend variables.
		wp_localize_script(
			'trekpilot-public-booking-js',
			'trekpilot_booking_obj',
			array(
				'ajax_url'        => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'trekpilot_booking_nonce_action' ),
				'currency_symbol' => get_option( 'trekpilot_currency_symbol', '$' ),
				'price_format'    => \TrekPilot\Admin\Controllers\AdminController::get_price_format(),
				'payment_method'  => get_option( 'trekpilot_payment_method', 'cash' ),
				'upi_id'          => get_option( 'trekpilot_upi_id', '' ),
				'upi_qr_code'     => get_option( 'trekpilot_upi_qr_code', '' ),
			)
		);

		$trekpilot_post_content = get_post() ? get_post()->post_content : '';
		if ( is_singular( 'trekpilot_trek' )
			|| has_shortcode( $trekpilot_post_content, 'trekpilot_booking' )
		) {
			wp_enqueue_style( 'trekpilot-public-booking-css' );
			wp_enqueue_script( 'trekpilot-public-booking-js' );
		}
	}

	/**
	 * Shortcode Renderer for [trekpilot_booking].
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_booking_widget( $atts ) {
		// Enqueue registered assets on demand.
		wp_enqueue_style( 'trekpilot-public-booking-css' );
		wp_enqueue_script( 'trekpilot-public-booking-js' );

		$args = shortcode_atts(
			array(
				'id' => get_the_ID(),
			),
			$atts
		);

		$trek_id = intval( $args['id'] );
		if ( ! $trek_id || get_post_type( $trek_id ) !== 'trekpilot_trek' ) {
			return '<p style="color:#b32d2e;">' . esc_html__( 'Error: Invalid Trek ID for booking widget.', 'trekpilot' ) . '</p>';
		}

		global $wpdb;

		// Fetch departure cities for this trek.
		$table_cities = $wpdb->prefix . 'trekpilot_departure_cities';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$cities = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->prepare( "SELECT * FROM $table_cities WHERE trek_id = %d AND status = 'active' ORDER BY menu_order ASC", $trek_id )
		);

		ob_start();
		$view_path = plugin_dir_path( __DIR__ ) . 'views/booking-widget.php';
		if ( file_exists( $view_path ) ) {
			include $view_path;
		}
		return ob_get_clean();
	}

	/**
	 * AJAX: Get scheduled dates for a selected Departure City.
	 */
	public function ajax_get_dates() {
		check_ajax_referer( 'trekpilot_booking_nonce_action', 'nonce' );

		$city_id = isset( $_GET['city_id'] ) ? intval( $_GET['city_id'] ) : 0;
		if ( ! $city_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid City', 'trekpilot' ) ) );
		}

		global $wpdb;
		$table_dates = $wpdb->prefix . 'trekpilot_departure_dates';
		$table_avail = $wpdb->prefix . 'trekpilot_availability';

		// Query active departure dates.
		// phpcs:disable WordPress.DB.PreparedSQL
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$dates = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT d.id, d.departure_date, d.status, d.notes, a.available_seats, a.total_seats 
				 FROM ' . $table_dates . ' d
				 LEFT JOIN ' . $table_avail . " a ON d.id = a.date_id
				 WHERE d.city_id = %d AND d.status != 'cancelled' AND d.departure_date >= CURDATE()
				 ORDER BY d.departure_date ASC",
				$city_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL

		// Format dates output.
		foreach ( $dates as &$date ) {
			$js_date                 = strtotime( $date['departure_date'] );
			$date['formatted_date']  = gmdate( 'd M Y', $js_date );
			$date['available_seats'] = intval( $date['available_seats'] );
			$date['total_seats']     = intval( $date['total_seats'] );
		}

		wp_send_json_success( $dates );
	}

	/**
	 * AJAX: Get the selectable Transportation Options for a departure city
	 * (Non AC Train, 3AC Train, Flight, etc.), shown right after city selection.
	 */
	public function ajax_get_transport_options() {
		check_ajax_referer( 'trekpilot_booking_nonce_action', 'nonce' );

		$city_id = isset( $_GET['city_id'] ) ? intval( $_GET['city_id'] ) : 0;
		if ( ! $city_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid City', 'trekpilot' ) ) );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$pricing = $wpdb->get_row(
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->prepare( "SELECT transport_options, adult_price, offer_price FROM {$wpdb->prefix}trekpilot_pricing WHERE city_id = %d AND date_id = 0", $city_id ),
			ARRAY_A
		);

		if ( $pricing ) {
			$options     = ! empty( $pricing['transport_options'] ) ? json_decode( $pricing['transport_options'], true ) : array();
			$adult_price = floatval( $pricing['adult_price'] );
			$offer_price = floatval( $pricing['offer_price'] );
		} else {
			// No pricing row configured yet — fall back to the city's own default pricing.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$city        = $wpdb->get_row( $wpdb->prepare( "SELECT base_price, offer_price FROM {$wpdb->prefix}trekpilot_departure_cities WHERE id = %d", $city_id ) );
			$options     = array();
			$adult_price = $city ? floatval( $city->base_price ) : 0.00;
			$offer_price = $city ? floatval( $city->offer_price ) : 0.00;
		}

		wp_send_json_success(
			array(
				'options'     => is_array( $options ) ? $options : array(),
				'adult_price' => $adult_price,
				'offer_price' => $offer_price,
			)
		);
	}

	/**
	 * AJAX: Get complete parameters (price overrides, seats availability, pickup list, transport, itinerary HTML) for a selected date.
	 */
	public function ajax_get_booking_details() {
		check_ajax_referer( 'trekpilot_booking_nonce_action', 'nonce' );

		$date_id = isset( $_GET['date_id'] ) ? intval( $_GET['date_id'] ) : 0;
		$city_id = isset( $_GET['city_id'] ) ? intval( $_GET['city_id'] ) : 0;

		if ( ! $date_id || ! $city_id ) {
			wp_send_json_error( array( 'message' => __( 'Missing parameters', 'trekpilot' ) ) );
		}

		global $wpdb;
		$table_cities  = $wpdb->prefix . 'trekpilot_departure_cities';
		$table_dates   = $wpdb->prefix . 'trekpilot_departure_dates';
		$table_avail   = $wpdb->prefix . 'trekpilot_availability';
		$table_pickups = $wpdb->prefix . 'trekpilot_pickup_points';

		// 1. Fetch default city specifications
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$city = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_cities WHERE id = %d", $city_id ), ARRAY_A );
		if ( ! $city ) {
			wp_send_json_error( array( 'message' => __( 'City not found', 'trekpilot' ) ) );
		}

		// 2. Fetch date availability, status and the actual calendar date
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$avail = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_avail WHERE date_id = %d", $date_id ), ARRAY_A );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$date_row           = $wpdb->get_row( $wpdb->prepare( "SELECT status, departure_date FROM $table_dates WHERE id = %d", $date_id ), ARRAY_A );
		$date_status        = $date_row ? $date_row['status'] : 'open';
		$departure_date_val = $date_row ? $date_row['departure_date'] : '';

		// 3. Fetch pricing: the date's override, otherwise the city default rule.
		$pricing = $this->get_effective_pricing( $city_id, $date_id );

		// 4. Fetch pickup list
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$pickups = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_pickups WHERE city_id = %d ORDER BY menu_order ASC", $city_id ), ARRAY_A );

		// 5. Generate formatted itinerary HTML for this city, dated against the selected departure.
		$itinerary_html = $this->generate_itinerary_html( $city_id, $departure_date_val );

		// Format output array.
		$details = array(
			'transport_type'  => $city['transport_type'],
			'reporting_time'  => $city['reporting_time'],
			'google_map_link' => $city['google_map_link'],
			'status'          => $date_status ? $date_status : 'open',
			'total_seats'     => $avail ? intval( $avail['total_seats'] ) : 0,
			'booked_seats'    => $avail ? intval( $avail['booked_seats'] ) : 0,
			'available_seats' => $avail ? intval( $avail['available_seats'] ) : 0,
			'adult_price'     => $pricing ? floatval( $pricing['adult_price'] ) : floatval( $city['base_price'] ),
			'child_price'     => $pricing ? floatval( $pricing['child_price'] ) : 0.00,
			'offer_price'     => $pricing ? floatval( $pricing['offer_price'] ) : floatval( $city['offer_price'] ),
			'group_discount'  => $pricing && ! empty( $pricing['group_discount'] ) ? json_decode( $pricing['group_discount'], true ) : array(),
			'extra_charges'   => $pricing && ! empty( $pricing['extra_charges'] ) ? json_decode( $pricing['extra_charges'], true ) : array(),
			'optional_addons' => $pricing && ! empty( $pricing['optional_addons'] ) ? json_decode( $pricing['optional_addons'], true ) : array(),
			'pickups'         => $pickups,
			'itinerary_html'  => $itinerary_html,
		);

		wp_send_json_success( $details );
	}

	/**
	 * The pricing row that applies to a city and date: the date's own override if there is one,
	 * otherwise the city default. A date override only carries prices, so add-ons, extra charges,
	 * group discounts and transport options are inherited from the city when the date has none.
	 *
	 * @param int $city_id City ID.
	 * @param int $date_id Departure date ID.
	 * @return array|null Pricing row, or null when none is configured.
	 */
	private function get_effective_pricing( $city_id, $date_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'trekpilot_pricing';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$pricing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE city_id = %d AND date_id = %d", $city_id, $date_id ), ARRAY_A );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$default_pricing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE city_id = %d AND date_id = 0", $city_id ), ARRAY_A );

		if ( ! $pricing ) {
			return $default_pricing;
		}
		if ( $default_pricing ) {
			foreach ( array( 'group_discount', 'extra_charges', 'optional_addons', 'transport_options' ) as $field ) {
				if ( empty( $pricing[ $field ] ) || '[]' === $pricing[ $field ] ) {
					$pricing[ $field ] = $default_pricing[ $field ];
				}
			}
		}
		return $pricing;
	}

	/**
	 * Decode a JSON list column into an array of arrays.
	 *
	 * @param mixed $json JSON string.
	 * @return array
	 */
	private function decode_list( $json ) {
		$list = ! empty( $json ) ? json_decode( $json, true ) : array();
		return is_array( $list ) ? array_values( array_filter( $list, 'is_array' ) ) : array();
	}

	/**
	 * Work out the booking price on the server, mirroring the booking widget's calculator
	 * (rate x travellers, group discount, add-ons, extra charges). Nothing the browser sends
	 * about prices is trusted: only the chosen city, date, traveller counts, transport name
	 * and add-on names are used, and each is looked up in the saved pricing.
	 *
	 * @param int      $city_id        City ID.
	 * @param int      $date_id        Departure date ID.
	 * @param int      $num_adults     Number of adults.
	 * @param int      $num_children   Number of children.
	 * @param string   $transport_name Chosen transport option name.
	 * @param string[] $addon_names    Chosen optional add-on names.
	 * @return array|\WP_Error array( total, transport_name, transport_price ) or an error.
	 */
	private function calculate_quote( $city_id, $date_id, $num_adults, $num_children, $transport_name, $addon_names ) {
		global $wpdb;

		$pricing = $this->get_effective_pricing( $city_id, $date_id );
		if ( ! $pricing ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$city = $wpdb->get_row( $wpdb->prepare( "SELECT base_price, offer_price FROM {$wpdb->prefix}trekpilot_departure_cities WHERE id = %d", $city_id ), ARRAY_A );
			if ( ! $city ) {
				return new \WP_Error( 'trekpilot_no_city', __( 'The selected departure city is not available.', 'trekpilot' ) );
			}
			$pricing = array(
				'adult_price'       => $city['base_price'],
				'child_price'       => 0,
				'offer_price'       => $city['offer_price'],
				'group_discount'    => '',
				'extra_charges'     => '',
				'optional_addons'   => '',
				'transport_options' => '',
			);
		}

		// Transport options come from the city default pricing, exactly as the widget lists them.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$transport_json = $wpdb->get_var( $wpdb->prepare( "SELECT transport_options FROM {$wpdb->prefix}trekpilot_pricing WHERE city_id = %d AND date_id = 0", $city_id ) );
		$transport_list = $this->decode_list( $transport_json );

		$transport_price = 0.0;
		if ( ! empty( $transport_list ) ) {
			$matched = null;
			foreach ( $transport_list as $option ) {
				if ( isset( $option['name'] ) && (string) $option['name'] === $transport_name ) {
					$matched = $option;
					break;
				}
			}
			if ( ! $matched ) {
				return new \WP_Error( 'trekpilot_bad_transport', __( 'Please choose a valid transportation option.', 'trekpilot' ) );
			}
			$transport_price = isset( $matched['price'] ) ? max( 0.0, (float) $matched['price'] ) : 0.0;
		} else {
			$transport_name = '';
		}

		$total_pax  = $num_adults + $num_children;
		$base_adult = (float) $pricing['offer_price'] > 0 ? (float) $pricing['offer_price'] : (float) $pricing['adult_price'];
		$rate_adult = $base_adult + $transport_price;
		$base_child = (float) $pricing['child_price'];
		$rate_child = $base_child > 0 ? $base_child + $transport_price : 0.0;

		$subtotal = ( $num_adults * $rate_adult ) + ( $num_children * $rate_child );

		// Best group discount: the rule with the highest min_seats that the party qualifies for.
		$best_rule = null;
		foreach ( $this->decode_list( $pricing['group_discount'] ) as $rule ) {
			$min_seats = isset( $rule['min_seats'] ) ? (int) $rule['min_seats'] : 0;
			if ( $total_pax >= $min_seats && ( ! $best_rule || $min_seats > (int) $best_rule['min_seats'] ) ) {
				$best_rule = $rule;
			}
		}
		if ( $best_rule ) {
			$value     = isset( $best_rule['value'] ) ? (float) $best_rule['value'] : 0.0;
			$subtotal -= ( isset( $best_rule['type'] ) && 'percent' === $best_rule['type'] ) ? $subtotal * ( $value / 100 ) : $value;
		}

		// Optional add-ons: only names that exist in the saved list count, each at most once.
		$chosen = array_unique( array_map( 'strval', (array) $addon_names ) );
		foreach ( $this->decode_list( $pricing['optional_addons'] ) as $addon ) {
			if ( isset( $addon['name'] ) && in_array( (string) $addon['name'], $chosen, true ) ) {
				$price     = isset( $addon['price'] ) ? (float) $addon['price'] : 0.0;
				$subtotal += ( isset( $addon['type'] ) && 'person' === $addon['type'] ) ? $total_pax * $price : $price;
			}
		}

		// Mandatory extra charges.
		foreach ( $this->decode_list( $pricing['extra_charges'] ) as $charge ) {
			$price     = isset( $charge['price'] ) ? (float) $charge['price'] : 0.0;
			$subtotal += ( isset( $charge['type'] ) && 'person' === $charge['type'] ) ? $total_pax * $price : $price;
		}

		return array(
			'total'           => round( max( 0.0, $subtotal ), 2 ),
			'transport_name'  => $transport_name,
			'transport_price' => round( $transport_price, 2 ),
		);
	}
	/**
	 * AJAX: Process booking submission, update seats counter, and dispatch alerts.
	 */
	public function ajax_submit_booking() {
		check_ajax_referer( 'trekpilot_booking_nonce_action', 'nonce' );

		$trek_id = isset( $_POST['trek_id'] ) ? intval( wp_unslash( $_POST['trek_id'] ) ) : 0;
		$city_id = isset( $_POST['city_id'] ) ? intval( wp_unslash( $_POST['city_id'] ) ) : 0;
		$date_id = isset( $_POST['date_id'] ) ? intval( wp_unslash( $_POST['date_id'] ) ) : 0;

		$cust_name  = isset( $_POST['cust_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cust_name'] ) ) : '';
		$cust_email = isset( $_POST['cust_email'] ) ? sanitize_email( wp_unslash( $_POST['cust_email'] ) ) : '';
		$cust_phone = isset( $_POST['cust_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['cust_phone'] ) ) : '';

		$num_adults   = isset( $_POST['num_adults'] ) ? intval( wp_unslash( $_POST['num_adults'] ) ) : 1;
		$num_children = isset( $_POST['num_children'] ) ? intval( wp_unslash( $_POST['num_children'] ) ) : 0;
		$pickup_point = isset( $_POST['pickup_point'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_point'] ) ) : '';

		$transport_name = isset( $_POST['transport_name'] ) ? sanitize_text_field( wp_unslash( $_POST['transport_name'] ) ) : '';

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_addons = isset( $_POST['addons'] ) ? wp_unslash( $_POST['addons'] ) : array();
		$addons     = is_array( $raw_addons ) ? array_map( 'sanitize_text_field', $raw_addons ) : array();
		// The total and transport surcharge are always recalculated on the server (see calculate_quote()).

		if ( ! $trek_id || ! $city_id || ! $date_id || empty( $cust_name ) || empty( $cust_email ) || empty( $cust_phone ) ) {
			wp_send_json_error( array( 'message' => __( 'Please fill all required customer contact details.', 'trekpilot' ) ) );
		}

		$clean_phone = preg_replace( '/[\s\-().]/', '', $cust_phone );
		if ( ! preg_match( '/^\+?\d{10,15}$/', $clean_phone ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid phone number with 10 to 15 digits.', 'trekpilot' ) ) );
		}

		if ( $num_adults < 1 || $num_children < 0 ) {
			wp_send_json_error( array( 'message' => __( 'Please select at least 1 adult.', 'trekpilot' ) ) );
		}

		$seats_requested = $num_adults + $num_children;

		global $wpdb;
		$table_avail = $wpdb->prefix . 'trekpilot_availability';

		// The city must belong to this published trek, and the date to this city and still be bookable.
		if ( 'trekpilot_trek' !== get_post_type( $trek_id ) || 'publish' !== get_post_status( $trek_id ) ) {
			wp_send_json_error( array( 'message' => __( 'This trek is not available for booking.', 'trekpilot' ) ) );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$valid_city = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}trekpilot_departure_cities WHERE id = %d AND trek_id = %d AND status = 'active'", $city_id, $trek_id ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$valid_date = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}trekpilot_departure_dates WHERE id = %d AND city_id = %d AND status != 'cancelled' AND departure_date >= CURDATE()", $date_id, $city_id ) );
		if ( ! $valid_city || ! $valid_date ) {
			wp_send_json_error( array( 'message' => __( 'The selected departure city or date is not available.', 'trekpilot' ) ) );
		}

		$quote = $this->calculate_quote( $city_id, $date_id, $num_adults, $num_children, $transport_name, $addons );
		if ( is_wp_error( $quote ) ) {
			wp_send_json_error( array( 'message' => $quote->get_error_message() ) );
		}
		$transport_name  = $quote['transport_name'];
		$transport_price = $quote['transport_price'];
		$total_price     = $quote['total'];

		// Verify seat availability under locks.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$avail = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_avail WHERE date_id = %d", $date_id ) );
		if ( ! $avail ) {
			wp_send_json_error( array( 'message' => __( 'Seat availability record not found for this date.', 'trekpilot' ) ) );
		}

		$available_seats = intval( $avail->available_seats );
		if ( $available_seats < $seats_requested ) {
			wp_send_json_error(
				array(
					/* translators: %d: number of remaining seats. */
					'message' => sprintf( __( 'Sorry, only %d seats are remaining for this date.', 'trekpilot' ), $available_seats ),
				)
			);
		}

		// Reserve the seats in a single conditional UPDATE: the WHERE clause re-checks capacity,
		// so two simultaneous bookings can never both take the last seats.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$reserved = $wpdb->query(
			$wpdb->prepare(
				"UPDATE $table_avail SET booked_seats = booked_seats + %d, available_seats = total_seats - booked_seats WHERE id = %d AND available_seats >= %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$seats_requested,
				$avail->id,
				$seats_requested
			)
		);
		if ( ! $reserved ) {
			wp_send_json_error( array( 'message' => __( 'Sorry, those seats were just taken. Please try again.', 'trekpilot' ) ) );
		}
		// Record booking in the database.
		$table_bookings = $wpdb->prefix . 'trekpilot_bookings';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$wpdb->insert(
			$table_bookings,
			array(
				'trek_id'         => $trek_id,
				'city_id'         => $city_id,
				'date_id'         => $date_id,
				'cust_name'       => $cust_name,
				'cust_email'      => $cust_email,
				'cust_phone'      => $cust_phone,
				'seats'           => $seats_requested,
				'num_adults'      => $num_adults,
				'num_children'    => $num_children,
				'pickup_point'    => $pickup_point,
				'addons'          => wp_json_encode( $addons ),
				'transport_type'  => $transport_name,
				'transport_price' => $transport_price,
				'total_amount'    => $total_price,
				'status'          => 'pending',
				'payment_status'  => 'pending',
				'created_at'      => current_time( 'mysql' ),
			),
			array(
				'%d',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%d',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%f',
				'%f',
				'%s',
				'%s',
				'%s',
			)
		);

		$booking_id = $wpdb->insert_id;

		// Format dynamic confirmation message details.
		$trek_title = get_the_title( $trek_id );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$city_name = $wpdb->get_var( $wpdb->prepare( "SELECT city_name FROM {$wpdb->prefix}trekpilot_departure_cities WHERE id = %d", $city_id ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$date_val       = $wpdb->get_var( $wpdb->prepare( "SELECT departure_date FROM {$wpdb->prefix}trekpilot_departure_dates WHERE id = %d", $date_id ) );
		$date_formatted = gmdate( 'd M Y', strtotime( $date_val ) );

		$details_rows = array(
			array(
				'label' => __( 'Booking ID', 'trekpilot' ),
				'value' => \TrekPilot\Admin\Controllers\TrekBookingsController::format_booking_ref( $booking_id ),
			),
			array(
				'label' => __( 'Trek', 'trekpilot' ),
				'value' => $trek_title,
			),
			array(
				'label' => __( 'Departure City', 'trekpilot' ),
				'value' => $city_name,
			),
			array(
				'label' => __( 'Departure Date', 'trekpilot' ),
				'value' => $date_formatted,
			),
			array(
				/* translators: 1: total seats, 2: adult count, 3: children count. */
				'label' => __( 'Seats Booked', 'trekpilot' ),
				'value' => sprintf( '%1$d (Adults: %2$d, Children: %3$d)', $seats_requested, $num_adults, $num_children ),
			),
			array(
				'label' => __( 'Pickup Point', 'trekpilot' ),
				'value' => $pickup_point,
			),
			array(
				'label' => __( 'Transportation Type', 'trekpilot' ),
				'value' => $transport_name ? $transport_name . ( $transport_price > 0 ? ' (+' . \TrekPilot\Admin\Controllers\AdminController::format_price( $transport_price ) . ')' : '' ) : '',
			),
			array(
				'label' => __( 'Add-ons', 'trekpilot' ),
				'value' => ! empty( $addons ) ? implode( ', ', array_map( 'sanitize_text_field', $addons ) ) : '',
			),
			array(
				'label' => __( 'Total Amount', 'trekpilot' ),
				'value' => \TrekPilot\Admin\Controllers\AdminController::format_price( (float) $total_price ),
			),
			array(
				'label' => __( 'Status', 'trekpilot' ),
				'value' => __( 'Pending Confirmation', 'trekpilot' ),
			),
		);

		$customer_message = \TrekPilot\Includes\Plugin::render_email_html(
			/* translators: %s: customer name. */
			sprintf( __( 'Thank You, %s!', 'trekpilot' ), $cust_name ),
			__( 'Thank you for booking your trek with us! Our team is currently reviewing your booking details, and we will confirm it shortly. Stay tuned!', 'trekpilot' ),
			$details_rows
		);

		$admin_message = \TrekPilot\Includes\Plugin::render_email_html(
			__( 'New Booking Received', 'trekpilot' ),
			/* translators: %s: customer name. */
			sprintf( __( 'You have received a new booking request from %s! Please review the booking details and confirm or reject it as soon as possible.', 'trekpilot' ), $cust_name ),
			$details_rows,
			__( 'Manage Booking', 'trekpilot' ),
			// Via the login page: logged-out admins sign in first and are then sent on to the booking;
			// already logged-in admins are forwarded straight to it.
			wp_login_url( admin_url( 'edit.php?post_type=trekpilot_trek&page=trekpilot-bookings&action=edit&booking=' . $booking_id ) )
		);

		$subject = sprintf( 'Booking Pending' );

		$from_name  = get_option( 'trekpilot_from_name', get_bloginfo( 'name' ) );
		$from_email = get_option( 'trekpilot_booking_email', get_bloginfo( 'admin_email' ) );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'From: %s <%s>', $from_name, $from_email ),
		);

		// Send customer confirmation.
		wp_mail( $cust_email, $subject, $customer_message, $headers );

		// Send admin notice alert.
		$admin_email = get_option( 'trekpilot_booking_email', get_bloginfo( 'admin_email' ) );
		wp_mail( $admin_email, 'New Booking Received', $admin_message, $headers );

		// Return booking summary receipt.
		wp_send_json_success(
			array(
				'message'         => 'Booking request received and pending confirmation!',
				'trek_title'      => $trek_title,
				'city_name'       => $city_name,
				'date'            => $date_formatted,
				'seats'           => $seats_requested,
				'total'           => $total_price,
				'cust_name'       => $cust_name,
				'pickup_point'    => $pickup_point,
				'transport_name'  => $transport_name,
				'transport_price' => $transport_price,
			)
		);
	}

	/**
	 * Helper: Generate Itinerary layout HTML for a city.
	 *
	 * @param int    $city_id        The departure city ID.
	 * @param string $departure_date Optional selected departure date (Y-m-d). When given, each
	 *                               day badge shows its actual calendar date instead of just "Day N".
	 * @return string
	 */
	private function generate_itinerary_html( $city_id, $departure_date = '' ) {
		global $wpdb;
		$table_days = $wpdb->prefix . 'trekpilot_itineraries';

		$has_valid_date = (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $departure_date );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$days = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->prepare( "SELECT * FROM $table_days WHERE city_id = %d ORDER BY menu_order ASC", $city_id )
		);

		if ( empty( $days ) ) {
			return '<p style="color:#666; font-style:italic;">' . esc_html__( 'No itinerary found. Please contact the admin for details.', 'trekpilot' ) . '</p>';
		}

		$items_by_day = \TrekPilot\Includes\Database::get_items_by_day( wp_list_pluck( $days, 'id' ) );

		$html = '<div class="trekpilot-frontend-itinerary-timeline">';
		foreach ( $days as $day ) {
			$day_id = intval( $day->id );
			$items  = isset( $items_by_day[ $day_id ] ) ? $items_by_day[ $day_id ] : array();

			if ( $has_valid_date ) {
				$calendar_date = gmdate( 'd M Y', strtotime( $departure_date . ' +' . intval( $day->day_number ) . ' days' ) );
				/* translators: 1: Day number, 2: Calendar date */
				$badge_text = sprintf( esc_html__( 'Day %1$d: %2$s', 'trekpilot' ), intval( $day->day_number ), $calendar_date );
			} else {
				/* translators: %d: Day number */
				$badge_text = sprintf( esc_html__( 'Day %d', 'trekpilot' ), intval( $day->day_number ) );
			}

			$html .= '<div class="trekpilot-timeline-day-block">';
			$html .= '  <div class="trekpilot-timeline-day-header">';
			$html .= '     <span class="trekpilot-timeline-day-badge">' . $badge_text . '</span>';
			$html .= '     <h4 class="trekpilot-timeline-day-title">' . esc_html( $day->title ) . '</h4>';
			$html .= '  </div>';
			if ( ! empty( $day->description ) ) {
				$html .= '  <p class="trekpilot-timeline-day-summary">' . esc_html( $day->description ) . '</p>';
			}

			if ( ! empty( $items ) ) {
				$html .= '  <button type="button" class="trekpilot-day-toggle-btn" data-label-more="' . esc_attr__( 'Show More', 'trekpilot' ) . '" data-label-less="' . esc_attr__( 'Show Less', 'trekpilot' ) . '">';
				$html .= '     <span class="trekpilot-toggle-label">' . esc_html__( 'Show More', 'trekpilot' ) . '</span>';
				$html .= '     <span class="dashicons dashicons-arrow-down-alt2"></span>';
				$html .= '  </button>';

				$html .= '  <div class="trekpilot-timeline-events">';
				foreach ( $items as $item ) {
					$img_html   = $item->image_url ? '<div class="trekpilot-event-media"><img src="' . esc_url( $item->image_url ) . '" alt="' . esc_attr( $item->title ) . '" /></div>' : '';
					$icon_class = $item->icon ? $item->icon : 'dashicons-palmtree';
					$html      .= '     <div class="trekpilot-timeline-event-card">';
					$html      .= '        <div class="trekpilot-event-icon-wrapper"><span class="dashicons ' . esc_attr( $icon_class ) . '"></span></div>';
					$html      .= '        <div class="trekpilot-event-content-box">';
					$html      .= '           <div class="trekpilot-event-meta">';
					$html      .= '              <span class="trekpilot-event-time">' . esc_html( $item->item_time ) . '</span>';
					$html      .= '           </div>';
					$html      .= '           <h5 class="trekpilot-event-title">' . esc_html( $item->title ) . '</h5>';
					if ( ! empty( $item->description ) ) {
						$html .= '           <p class="trekpilot-event-description">' . wp_kses_post( $item->description ) . '</p>';
					}
					$html .= '           ' . $img_html;
					$html .= '        </div>';
					$html .= '     </div>';
				}
				$html .= '  </div>';
			}
			$html .= '</div>';
		}
		$html .= '</div>';

		return $html;
	}
}
