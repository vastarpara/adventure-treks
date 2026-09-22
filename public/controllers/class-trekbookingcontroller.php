<?php
/**
 * Controller for managing Frontend Booking Widget and AJAX handlers.
 *
 * @package    AdventureTreks
 * @subpackage AdventureTreks/Public/Controllers
 * @author     Nilesh Vastarpara
 */

namespace AdventureTreks\Public\Controllers;

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
		// Register Shortcodes (adventure_booking is canonical; trek_booking kept as a legacy alias).
		add_shortcode( 'adventure_booking', array( $this, 'render_booking_widget' ) );
		add_shortcode( 'trek_booking', array( $this, 'render_booking_widget' ) );

		// Register AJAX actions (no privileges required for public visitors).
		add_action( 'wp_ajax_at_get_booking_dates', array( $this, 'ajax_get_dates' ) );
		add_action( 'wp_ajax_nopriv_at_get_booking_dates', array( $this, 'ajax_get_dates' ) );

		add_action( 'wp_ajax_at_get_transport_options', array( $this, 'ajax_get_transport_options' ) );
		add_action( 'wp_ajax_nopriv_at_get_transport_options', array( $this, 'ajax_get_transport_options' ) );

		add_action( 'wp_ajax_at_get_booking_details', array( $this, 'ajax_get_booking_details' ) );
		add_action( 'wp_ajax_nopriv_at_get_booking_details', array( $this, 'ajax_get_booking_details' ) );

		add_action( 'wp_ajax_at_submit_booking', array( $this, 'ajax_submit_booking' ) );
		add_action( 'wp_ajax_nopriv_at_submit_booking', array( $this, 'ajax_submit_booking' ) );

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
		wp_add_inline_style( 'at-public-booking-css', \AdventureTreks\Includes\Plugin::get_dynamic_color_css() );
	}

	/**
	 * Register frontend CSS and JS.
	 */
	public function enqueue_assets() {
		// Public stylesheets.
		wp_register_style(
			'at-public-booking-css',
			ADVENTURE_TREKS_URL . 'assets/public/css/booking-widget.css',
			array( 'dashicons' ),
			ADVENTURE_TREKS_VERSION
		);

		// Public JavaScripts.
		wp_register_script(
			'at-public-booking-js',
			ADVENTURE_TREKS_URL . 'assets/public/js/booking-widget.js',
			array(),
			ADVENTURE_TREKS_VERSION,
			true
		);

		// Localize frontend variables.
		wp_localize_script(
			'at-public-booking-js',
			'at_booking_obj',
			array(
				'ajax_url'        => admin_url( 'admin-ajax.php' ),
				'nonce'           => wp_create_nonce( 'at_booking_nonce_action' ),
				'currency_symbol' => get_option( 'at_currency_symbol', '₹' ),
				'payment_method'  => get_option( 'at_payment_method', 'cash' ),
				'upi_id'          => get_option( 'at_upi_id', '' ),
				'upi_qr_code'     => get_option( 'at_upi_qr_code', '' ),
			)
		);

		$at_post_content = get_post() ? get_post()->post_content : '';
		if ( is_singular( 'adventure_trek' )
			|| has_shortcode( $at_post_content, 'adventure_booking' ) || has_shortcode( $at_post_content, 'trek_booking' )
		) {
			wp_enqueue_style( 'at-public-booking-css' );
			wp_enqueue_script( 'at-public-booking-js' );
		}
	}

	/**
	 * Shortcode Renderer for [adventure_booking] (alias: [trek_booking]).
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_booking_widget( $atts ) {
		// Enqueue registered assets on demand.
		wp_enqueue_style( 'at-public-booking-css' );
		wp_enqueue_script( 'at-public-booking-js' );

		$args = shortcode_atts(
			array(
				'id' => get_the_ID(),
			),
			$atts
		);

		$trek_id = intval( $args['id'] );
		if ( ! $trek_id || get_post_type( $trek_id ) !== 'adventure_trek' ) {
			return '<p style="color:#b32d2e;">' . esc_html__( 'Error: Invalid Trek ID for booking widget.', 'adventure-treks' ) . '</p>';
		}

		global $wpdb;

		// Fetch departure cities for this trek.
		$table_cities = $wpdb->prefix . 'at_departure_cities';
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
		check_ajax_referer( 'at_booking_nonce_action', 'nonce' );

		$city_id = isset( $_GET['city_id'] ) ? intval( $_GET['city_id'] ) : 0;
		if ( ! $city_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid City', 'adventure-treks' ) ) );
		}

		global $wpdb;
		$table_dates = $wpdb->prefix . 'at_departure_dates';
		$table_avail = $wpdb->prefix . 'at_availability';

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
		check_ajax_referer( 'at_booking_nonce_action', 'nonce' );

		$city_id = isset( $_GET['city_id'] ) ? intval( $_GET['city_id'] ) : 0;
		if ( ! $city_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid City', 'adventure-treks' ) ) );
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$pricing = $wpdb->get_row(
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->prepare( "SELECT transport_options, adult_price, offer_price FROM {$wpdb->prefix}at_pricing WHERE city_id = %d AND date_id = 0", $city_id ),
			ARRAY_A
		);

		if ( $pricing ) {
			$options     = ! empty( $pricing['transport_options'] ) ? json_decode( $pricing['transport_options'], true ) : array();
			$adult_price = floatval( $pricing['adult_price'] );
			$offer_price = floatval( $pricing['offer_price'] );
		} else {
			// No pricing row configured yet — fall back to the city's own default pricing.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$city        = $wpdb->get_row( $wpdb->prepare( "SELECT base_price, offer_price FROM {$wpdb->prefix}at_departure_cities WHERE id = %d", $city_id ) );
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
		check_ajax_referer( 'at_booking_nonce_action', 'nonce' );

		$date_id = isset( $_GET['date_id'] ) ? intval( $_GET['date_id'] ) : 0;
		$city_id = isset( $_GET['city_id'] ) ? intval( $_GET['city_id'] ) : 0;

		if ( ! $date_id || ! $city_id ) {
			wp_send_json_error( array( 'message' => __( 'Missing parameters', 'adventure-treks' ) ) );
		}

		global $wpdb;
		$table_cities  = $wpdb->prefix . 'at_departure_cities';
		$table_dates   = $wpdb->prefix . 'at_departure_dates';
		$table_avail   = $wpdb->prefix . 'at_availability';
		$table_pricing = $wpdb->prefix . 'at_pricing';
		$table_pickups = $wpdb->prefix . 'at_pickup_points';

		// 1. Fetch default city specifications
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$city = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_cities WHERE id = %d", $city_id ), ARRAY_A );
		if ( ! $city ) {
			wp_send_json_error( array( 'message' => __( 'City not found', 'adventure-treks' ) ) );
		}

		// 2. Fetch date availability, status and the actual calendar date
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$avail = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_avail WHERE date_id = %d", $date_id ), ARRAY_A );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$date_row           = $wpdb->get_row( $wpdb->prepare( "SELECT status, departure_date FROM $table_dates WHERE id = %d", $date_id ), ARRAY_A );
		$date_status        = $date_row ? $date_row['status'] : 'open';
		$departure_date_val = $date_row ? $date_row['departure_date'] : '';

		// 3. Fetch pricing: look for date override, otherwise load default city rule
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$pricing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_pricing WHERE city_id = %d AND date_id = %d", $city_id, $date_id ), ARRAY_A );
		if ( ! $pricing ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$pricing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_pricing WHERE city_id = %d AND date_id = 0", $city_id ), ARRAY_A );
		}

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
	 * AJAX: Process booking submission, update seats counter, and dispatch alerts.
	 */
	public function ajax_submit_booking() {
		check_ajax_referer( 'at_booking_nonce_action', 'nonce' );

		$trek_id = isset( $_POST['trek_id'] ) ? intval( wp_unslash( $_POST['trek_id'] ) ) : 0;
		$city_id = isset( $_POST['city_id'] ) ? intval( wp_unslash( $_POST['city_id'] ) ) : 0;
		$date_id = isset( $_POST['date_id'] ) ? intval( wp_unslash( $_POST['date_id'] ) ) : 0;

		$cust_name  = isset( $_POST['cust_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cust_name'] ) ) : '';
		$cust_email = isset( $_POST['cust_email'] ) ? sanitize_email( wp_unslash( $_POST['cust_email'] ) ) : '';
		$cust_phone = isset( $_POST['cust_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['cust_phone'] ) ) : '';

		$num_adults   = isset( $_POST['num_adults'] ) ? intval( wp_unslash( $_POST['num_adults'] ) ) : 1;
		$num_children = isset( $_POST['num_children'] ) ? intval( wp_unslash( $_POST['num_children'] ) ) : 0;
		$pickup_point = isset( $_POST['pickup_point'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_point'] ) ) : '';

		$transport_name  = isset( $_POST['transport_name'] ) ? sanitize_text_field( wp_unslash( $_POST['transport_name'] ) ) : '';
		$transport_price = isset( $_POST['transport_price'] ) ? floatval( wp_unslash( $_POST['transport_price'] ) ) : 0.00;

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_addons  = isset( $_POST['addons'] ) ? wp_unslash( $_POST['addons'] ) : array();
		$addons      = is_array( $raw_addons ) ? array_map( 'sanitize_text_field', $raw_addons ) : array();
		$total_price = isset( $_POST['total_price'] ) ? floatval( wp_unslash( $_POST['total_price'] ) ) : 0.00;

		if ( ! $trek_id || ! $city_id || ! $date_id || empty( $cust_name ) || empty( $cust_email ) || empty( $cust_phone ) ) {
			wp_send_json_error( array( 'message' => __( 'Please fill all required customer contact details.', 'adventure-treks' ) ) );
		}

		$clean_phone = preg_replace( '/[\-\s]/', '', $cust_phone );
		if ( ! preg_match( '/^(?:\+91|91|0)?[6789]\d{9}$/', $clean_phone ) ) {
			wp_send_json_error( array( 'message' => __( 'Please enter a valid Indian phone number.', 'adventure-treks' ) ) );
		}

		$seats_requested = $num_adults + $num_children;
		if ( $seats_requested <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Please select at least 1 seat.', 'adventure-treks' ) ) );
		}

		global $wpdb;
		$table_avail = $wpdb->prefix . 'at_availability';

		// Verify seat availability under locks.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$avail = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_avail WHERE date_id = %d", $date_id ) );
		if ( ! $avail ) {
			wp_send_json_error( array( 'message' => __( 'Seat availability record not found for this date.', 'adventure-treks' ) ) );
		}

		$available_seats = intval( $avail->available_seats );
		if ( $available_seats < $seats_requested ) {
			wp_send_json_error(
				array(
					/* translators: %d: number of remaining seats. */
					'message' => sprintf( __( 'Sorry, only %d seats are remaining for this date.', 'adventure-treks' ), $available_seats ),
				)
			);
		}

		// Decrement availability counter.
		$new_booked    = intval( $avail->booked_seats ) + $seats_requested;
		$new_available = intval( $avail->total_seats ) - $new_booked;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$wpdb->update(
			$table_avail,
			array(
				'booked_seats'    => $new_booked,
				'available_seats' => $new_available,
			),
			array( 'id' => $avail->id ),
			array( '%d', '%d' ),
			array( '%d' )
		);

		// Record booking in the database.
		$table_bookings = $wpdb->prefix . 'at_bookings';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$wpdb->insert(
			$table_bookings,
			array(
				'trek_id'        => $trek_id,
				'city_id'        => $city_id,
				'date_id'        => $date_id,
				'cust_name'      => $cust_name,
				'cust_email'     => $cust_email,
				'cust_phone'     => $cust_phone,
				'seats'          => $seats_requested,
				'num_adults'     => $num_adults,
				'num_children'   => $num_children,
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
		$city_name = $wpdb->get_var( $wpdb->prepare( "SELECT city_name FROM {$wpdb->prefix}at_departure_cities WHERE id = %d", $city_id ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$date_val       = $wpdb->get_var( $wpdb->prepare( "SELECT departure_date FROM {$wpdb->prefix}at_departure_dates WHERE id = %d", $date_id ) );
		$date_formatted = gmdate( 'd M Y', strtotime( $date_val ) );

		$currency = get_option( 'at_currency_symbol', '₹' );

		$details_rows = array(
			array(
				'label' => __( 'Booking ID', 'adventure-treks' ),
				'value' => \AdventureTreks\Admin\Controllers\TrekBookingsController::format_booking_ref( $booking_id ),
			),
			array(
				'label' => __( 'Trek', 'adventure-treks' ),
				'value' => $trek_title,
			),
			array(
				'label' => __( 'Departure City', 'adventure-treks' ),
				'value' => $city_name,
			),
			array(
				'label' => __( 'Departure Date', 'adventure-treks' ),
				'value' => $date_formatted,
			),
			array(
				/* translators: 1: total seats, 2: adult count, 3: children count. */
				'label' => __( 'Seats Booked', 'adventure-treks' ),
				'value' => sprintf( '%1$d (Adults: %2$d, Children: %3$d)', $seats_requested, $num_adults, $num_children ),
			),
			array(
				'label' => __( 'Pickup Point', 'adventure-treks' ),
				'value' => $pickup_point,
			),
			array(
				'label' => __( 'Transportation Type', 'adventure-treks' ),
				'value' => $transport_name ? $transport_name . ( $transport_price > 0 ? ' (+' . $currency . ' ' . number_format( $transport_price, 2 ) . ')' : '' ) : '',
			),
			array(
				'label' => __( 'Add-ons', 'adventure-treks' ),
				'value' => ! empty( $addons ) ? implode( ', ', array_map( 'sanitize_text_field', $addons ) ) : '',
			),
			array(
				'label' => __( 'Total Amount', 'adventure-treks' ),
				'value' => $currency . ' ' . number_format( (float) $total_price, 2 ),
			),
			array(
				'label' => __( 'Status', 'adventure-treks' ),
				'value' => __( 'Pending Confirmation', 'adventure-treks' ),
			),
		);

		$trek_url = get_permalink( $trek_id );

		$customer_message = \AdventureTreks\Includes\Plugin::render_email_html(
			/* translators: %s: customer name. */
			sprintf( __( 'Thank You, %s!', 'adventure-treks' ), $cust_name ),
			__( 'Thank you for booking your adventure with us! Our team is currently reviewing your booking details, and we will confirm it shortly. Stay tuned!', 'adventure-treks' ),
			$details_rows,
			__( 'View Trek Details', 'adventure-treks' ),
			$trek_url
		);

		$admin_message = \AdventureTreks\Includes\Plugin::render_email_html(
			__( 'New Booking Received', 'adventure-treks' ),
			/* translators: %s: customer name. */
			sprintf( __( 'You have received a new booking request from %s! Please review the booking details and confirm or reject it as soon as possible.', 'adventure-treks' ), $cust_name ),
			$details_rows,
			__( 'Manage Booking', 'adventure-treks' ),
			admin_url( 'edit.php?post_type=adventure_trek&page=at-bookings&action=edit&booking=' . $booking_id )
		);

		$subject = sprintf( 'Booking Pending' );

		$from_name  = get_option( 'at_from_name', get_bloginfo( 'name' ) );
		$from_email = get_option( 'at_booking_email', get_option( 'admin_email' ) );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'From: %s <%s>', $from_name, $from_email ),
		);

		// Send customer confirmation.
		wp_mail( $cust_email, $subject, $customer_message, $headers );

		// Send admin notice alert.
		$admin_email = get_option( 'at_booking_email', get_option( 'admin_email' ) );
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
		$table_days  = $wpdb->prefix . 'at_itineraries';
		$table_items = $wpdb->prefix . 'at_itinerary_items';

		$has_valid_date = (bool) preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $departure_date );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$days = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->prepare( "SELECT * FROM $table_days WHERE city_id = %d ORDER BY menu_order ASC", $city_id )
		);

		if ( empty( $days ) ) {
			return '<p style="color:#666; font-style:italic;">' . esc_html__( 'No daily itinerary configured for this city.', 'adventure-treks' ) . '</p>';
		}

		$html = '<div class="at-frontend-itinerary-timeline">';
		foreach ( $days as $day ) {
			$day_id = intval( $day->id );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$items = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$wpdb->prepare( "SELECT * FROM $table_items WHERE itinerary_id = %d ORDER BY menu_order ASC", $day_id )
			);

			if ( $has_valid_date ) {
				$calendar_date = gmdate( 'd M Y', strtotime( $departure_date . ' +' . intval( $day->day_number ) . ' days' ) );
				/* translators: 1: Day number, 2: Calendar date */
				$badge_text = sprintf( esc_html__( 'Day %1$d — %2$s', 'adventure-treks' ), intval( $day->day_number ), $calendar_date );
			} else {
				/* translators: %d: Day number */
				$badge_text = sprintf( esc_html__( 'Day %d', 'adventure-treks' ), intval( $day->day_number ) );
			}

			$html .= '<div class="at-timeline-day-block">';
			$html .= '  <div class="at-timeline-day-header">';
			$html .= '     <span class="at-timeline-day-badge">' . $badge_text . '</span>';
			$html .= '     <h4 class="at-timeline-day-title">' . esc_html( $day->title ) . '</h4>';
			$html .= '  </div>';
			if ( ! empty( $day->description ) ) {
				$html .= '  <p class="at-timeline-day-summary">' . esc_html( $day->description ) . '</p>';
			}

			if ( ! empty( $items ) ) {
				$html .= '  <button type="button" class="at-day-toggle-btn" data-label-more="' . esc_attr__( 'Show More', 'adventure-treks' ) . '" data-label-less="' . esc_attr__( 'Show Less', 'adventure-treks' ) . '">';
				$html .= '     <span class="at-toggle-label">' . esc_html__( 'Show More', 'adventure-treks' ) . '</span>';
				$html .= '     <span class="dashicons dashicons-arrow-down-alt2"></span>';
				$html .= '  </button>';

				$html .= '  <div class="at-timeline-events">';
				foreach ( $items as $item ) {
					$img_html   = $item->image_url ? '<div class="at-event-media"><img src="' . esc_url( $item->image_url ) . '" /></div>' : '';
					$icon_class = $item->icon ? $item->icon : 'dashicons-palmtree';
					$html      .= '     <div class="at-timeline-event-card">';
					$html      .= '        <div class="at-event-icon-wrapper"><span class="dashicons ' . esc_attr( $icon_class ) . '"></span></div>';
					$html      .= '        <div class="at-event-content-box">';
					$html      .= '           <div class="at-event-meta">';
					$html      .= '              <span class="at-event-time">' . esc_html( $item->item_time ) . '</span>';
					$html      .= '           </div>';
					$html      .= '           <h5 class="at-event-title">' . esc_html( $item->title ) . '</h5>';
					if ( ! empty( $item->description ) ) {
						$html .= '           <p class="at-event-description">' . wp_kses_post( $item->description ) . '</p>';
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
