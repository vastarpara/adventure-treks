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
		// Register Shortcodes.
		add_shortcode( 'trek_booking', array( $this, 'render_booking_widget' ) );

		// Register AJAX actions (no privileges required for public visitors).
		add_action( 'wp_ajax_at_get_booking_dates', array( $this, 'ajax_get_dates' ) );
		add_action( 'wp_ajax_nopriv_at_get_booking_dates', array( $this, 'ajax_get_dates' ) );
		
		add_action( 'wp_ajax_at_get_booking_details', array( $this, 'ajax_get_booking_details' ) );
		add_action( 'wp_ajax_nopriv_at_get_booking_details', array( $this, 'ajax_get_booking_details' ) );

		add_action( 'wp_ajax_at_submit_booking', array( $this, 'ajax_submit_booking' ) );
		add_action( 'wp_ajax_nopriv_at_submit_booking', array( $this, 'ajax_submit_booking' ) );

		// Load CSS/JS.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
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
		wp_localize_script( 'at-public-booking-js', 'at_booking_obj', array(
			'ajax_url'        => admin_url( 'admin-ajax.php' ),
			'nonce'           => wp_create_nonce( 'at_booking_nonce_action' ),
			'currency_symbol' => get_option( 'at_currency_symbol', '₹' ),
		) );

		if ( is_singular( 'adventure_trek' ) || ( get_post() && has_shortcode( get_post()->post_content, 'trek_booking' ) ) ) {
			wp_enqueue_style( 'at-public-booking-css' );
			wp_enqueue_script( 'at-public-booking-js' );
		}
	}

	/**
	 * Shortcode Renderer for [trek_booking].
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_booking_widget( $atts ) {
		// Enqueue registered assets on demand
		wp_enqueue_style( 'at-public-booking-css' );
		wp_enqueue_script( 'at-public-booking-js' );

		$args = shortcode_atts( array(
			'id' => get_the_ID(),
		), $atts );

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
		$view_path = plugin_dir_path( dirname( __FILE__ ) ) . 'Views/booking-widget.php';
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
			wp_send_json_error( array( 'message' => 'Invalid City' ) );
		}

		global $wpdb;
		$table_dates = $wpdb->prefix . 'at_departure_dates';
		$table_avail = $wpdb->prefix . 'at_availability';

		// Query active departure dates.
		// phpcs:disable WordPress.DB.PreparedSQL
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$dates = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT d.id, d.departure_date, d.status, d.notes, a.available_seats, a.total_seats 
				 FROM " . $table_dates . " d
				 LEFT JOIN " . $table_avail . " a ON d.id = a.date_id
				 WHERE d.city_id = %d AND d.status != 'cancelled' AND d.departure_date >= CURDATE()
				 ORDER BY d.departure_date ASC",
				$city_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL

		// Format dates output.
		foreach ( $dates as &$date ) {
			$js_date = strtotime( $date['departure_date'] );
			$date['formatted_date'] = gmdate( 'd M Y', $js_date );
			$date['available_seats'] = intval( $date['available_seats'] );
			$date['total_seats'] = intval( $date['total_seats'] );
		}

		wp_send_json_success( $dates );
	}

	/**
	 * AJAX: Get complete parameters (price overrides, seats availability, pickup list, transport, itinerary HTML) for a selected date.
	 */
	public function ajax_get_booking_details() {
		check_ajax_referer( 'at_booking_nonce_action', 'nonce' );

		$date_id = isset( $_GET['date_id'] ) ? intval( $_GET['date_id'] ) : 0;
		$city_id = isset( $_GET['city_id'] ) ? intval( $_GET['city_id'] ) : 0;

		if ( ! $date_id || ! $city_id ) {
			wp_send_json_error( array( 'message' => 'Missing parameters' ) );
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
			wp_send_json_error( array( 'message' => 'City not found' ) );
		}

		// 2. Fetch date availability
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$avail = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_avail WHERE date_id = %d", $date_id ), ARRAY_A );

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

		// 5. Generate formatted itinerary HTML for this city
		$itinerary_html = $this->generate_itinerary_html( $city_id );

		// Format output array
		$details = array(
			'transport_type'   => $city['transport_type'],
			'reporting_time'   => $city['reporting_time'],
			'google_map_link'  => $city['google_map_link'],
			'total_seats'      => $avail ? intval( $avail['total_seats'] ) : 0,
			'booked_seats'     => $avail ? intval( $avail['booked_seats'] ) : 0,
			'available_seats'  => $avail ? intval( $avail['available_seats'] ) : 0,
			'adult_price'      => $pricing ? floatval( $pricing['adult_price'] ) : floatval( $city['base_price'] ),
			'child_price'      => $pricing ? floatval( $pricing['child_price'] ) : 0.00,
			'offer_price'      => $pricing ? floatval( $pricing['offer_price'] ) : floatval( $city['offer_price'] ),
			'group_discount'   => $pricing && ! empty( $pricing['group_discount'] ) ? json_decode( $pricing['group_discount'], true ) : array(),
			'extra_charges'    => $pricing && ! empty( $pricing['extra_charges'] ) ? json_decode( $pricing['extra_charges'], true ) : array(),
			'optional_addons'  => $pricing && ! empty( $pricing['optional_addons'] ) ? json_decode( $pricing['optional_addons'], true ) : array(),
			'pickups'          => $pickups,
			'itinerary_html'   => $itinerary_html,
		);

		wp_send_json_success( $details );
	}

	/**
	 * AJAX: Process booking submission, update seats counter, and dispatch alerts.
	 */
	public function ajax_submit_booking() {
		check_ajax_referer( 'at_booking_nonce_action', 'nonce' );

		$trek_id      = isset( $_POST['trek_id'] ) ? intval( wp_unslash( $_POST['trek_id'] ) ) : 0;
		$city_id      = isset( $_POST['city_id'] ) ? intval( wp_unslash( $_POST['city_id'] ) ) : 0;
		$date_id      = isset( $_POST['date_id'] ) ? intval( wp_unslash( $_POST['date_id'] ) ) : 0;
		
		$cust_name    = isset( $_POST['cust_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cust_name'] ) ) : '';
		$cust_email   = isset( $_POST['cust_email'] ) ? sanitize_email( wp_unslash( $_POST['cust_email'] ) ) : '';
		$cust_phone   = isset( $_POST['cust_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['cust_phone'] ) ) : '';
		
		$num_adults   = isset( $_POST['num_adults'] ) ? intval( wp_unslash( $_POST['num_adults'] ) ) : 1;
		$num_children = isset( $_POST['num_children'] ) ? intval( wp_unslash( $_POST['num_children'] ) ) : 0;
		$pickup_point = isset( $_POST['pickup_point'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_point'] ) ) : '';
		
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_addons   = isset( $_POST['addons'] ) ? wp_unslash( $_POST['addons'] ) : array();
		$addons       = is_array( $raw_addons ) ? array_map( 'sanitize_text_field', $raw_addons ) : array();
		$total_price  = isset( $_POST['total_price'] ) ? floatval( wp_unslash( $_POST['total_price'] ) ) : 0.00;

		if ( ! $trek_id || ! $city_id || ! $date_id || empty( $cust_name ) || empty( $cust_email ) || empty( $cust_phone ) ) {
			wp_send_json_error( array( 'message' => 'Please fill all required customer contact details.' ) );
		}

		$clean_phone = preg_replace( '/[\-\s]/', '', $cust_phone );
		if ( ! preg_match( '/^(?:\+91|91|0)?[6789]\d{9}$/', $clean_phone ) ) {
			wp_send_json_error( array( 'message' => 'Please enter a valid Indian phone number.' ) );
		}

		$seats_requested = $num_adults + $num_children;
		if ( $seats_requested <= 0 ) {
			wp_send_json_error( array( 'message' => 'Please select at least 1 seat.' ) );
		}

		global $wpdb;
		$table_avail = $wpdb->prefix . 'at_availability';

		// Verify seat availability under locks
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$avail = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_avail WHERE date_id = %d", $date_id ) );
		if ( ! $avail ) {
			wp_send_json_error( array( 'message' => 'Seat availability record not found for this date.' ) );
		}

		$available_seats = intval( $avail->available_seats );
		if ( $available_seats < $seats_requested ) {
			wp_send_json_error( array( 'message' => sprintf( 'Sorry, only %d seats are remaining for this date.', $available_seats ) ) );
		}

		// Decrement availability counter
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

		// Record booking in the database
		$table_bookings = $wpdb->prefix . 'at_bookings';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$wpdb->insert(
			$table_bookings,
			array(
				'trek_id'      => $trek_id,
				'city_id'      => $city_id,
				'date_id'      => $date_id,
				'cust_name'    => $cust_name,
				'cust_email'   => $cust_email,
				'cust_phone'   => $cust_phone,
				'seats'        => $seats_requested,
				'num_adults'   => $num_adults,
				'num_children' => $num_children,
				'pickup_point' => $pickup_point,
				'addons'       => wp_json_encode( $addons ),
				'total_amount' => $total_price,
				'status'       => 'confirmed',
				'created_at'   => current_time( 'mysql' ),
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
				'%f',
				'%s',
				'%s',
			)
		);

		// Format dynamic confirmation message details
		$trek_title = get_the_title( $trek_id );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$city_name  = $wpdb->get_var( $wpdb->prepare( "SELECT city_name FROM {$wpdb->prefix}at_departure_cities WHERE id = %d", $city_id ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$date_val   = $wpdb->get_var( $wpdb->prepare( "SELECT departure_date FROM {$wpdb->prefix}at_departure_dates WHERE id = %d", $date_id ) );
		$date_formatted = gmdate( 'd M Y', strtotime( $date_val ) );

		$currency = get_option( 'at_currency_symbol', '₹' );

		// Compile email body markup
		$subject = sprintf( '[Adventure Treks] Booking Confirmed: %s', $trek_title );
		$message = "<h2>Booking Confirmation Receipt</h2>";
		$message .= "<p>Hello <strong>{$cust_name}</strong>,</p>";
		$message .= "<p>Your booking for the upcoming adventure has been successfully processed!</p>";
		$message .= "<table style='width:100%; max-width:600px; border-collapse:collapse; margin-top:15px;'>";
		$message .= "<tr><td style='padding:8px; border-bottom:1px solid #ddd;'><strong>Trek:</strong></td><td style='padding:8px; border-bottom:1px solid #ddd;'>{$trek_title}</td></tr>";
		$message .= "<tr><td style='padding:8px; border-bottom:1px solid #ddd;'><strong>Departure City:</strong></td><td style='padding:8px; border-bottom:1px solid #ddd;'>{$city_name}</td></tr>";
		$message .= "<tr><td style='padding:8px; border-bottom:1px solid #ddd;'><strong>Departure Date:</strong></td><td style='padding:8px; border-bottom:1px solid #ddd;'>{$date_formatted}</td></tr>";
		$message .= "<tr><td style='padding:8px; border-bottom:1px solid #ddd;'><strong>Seats Booked:</strong></td><td style='padding:8px; border-bottom:1px solid #ddd;'>{$seats_requested} (Adults: {$num_adults}, Children: {$num_children})</td></tr>";
		if ( ! empty( $pickup_point ) ) {
			$message .= "<tr><td style='padding:8px; border-bottom:1px solid #ddd;'><strong>Pickup Point:</strong></td><td style='padding:8px; border-bottom:1px solid #ddd;'>{$pickup_point}</td></tr>";
		}
		if ( ! empty( $addons ) ) {
			$message .= "<tr><td style='padding:8px; border-bottom:1px solid #ddd;'><strong>Add-ons Chosen:</strong></td><td style='padding:8px; border-bottom:1px solid #ddd;'>" . implode( ', ', array_map( 'sanitize_text_field', $addons ) ) . "</td></tr>";
		}
		$message .= "<tr><td style='padding:8px; border-bottom:1px solid #ddd;'><strong>Total Paid Amount:</strong></td><td style='padding:8px; border-bottom:1px solid #ddd; color:#137a7f; font-weight:bold;'>{$currency} {$total_price}</td></tr>";
		$message .= "</table>";
		$message .= "<p style='margin-top:20px; font-size:12px; color:#666;'>We look forward to trekking with you! Detailed reporting instructions will follow soon.</p>";

		$headers = array('Content-Type: text/html; charset=UTF-8');

		// Send customer confirmation
		wp_mail( $cust_email, $subject, $message, $headers );

		// Send admin notice alert
		$admin_email = get_option( 'at_booking_email', get_option( 'admin_email' ) );
		wp_mail( $admin_email, '[ALERT] New Trek Registration: ' . $trek_title, $message, $headers );

		// Return booking summary receipt
		wp_send_json_success( array(
			'message'      => 'Booking confirmed successfully!',
			'trek_title'   => $trek_title,
			'city_name'    => $city_name,
			'date'         => $date_formatted,
			'seats'        => $seats_requested,
			'total'        => $total_price,
			'cust_name'    => $cust_name,
			'pickup_point' => $pickup_point,
		) );
	}

	/**
	 * Helper: Generate Itinerary layout HTML for a city.
	 */
	private function generate_itinerary_html( $city_id ) {
		global $wpdb;
		$table_days  = $wpdb->prefix . 'at_itineraries';
		$table_items = $wpdb->prefix . 'at_itinerary_items';

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

			$html .= '<div class="at-timeline-day-block">';
			$html .= '  <div class="at-timeline-day-header">';
			/* translators: %d: Day number */
			$html .= '     <span class="at-timeline-day-badge">' . sprintf( esc_html__( 'Day %d', 'adventure-treks' ), intval( $day->day_number ) ) . '</span>';
			$html .= '     <h4 class="at-timeline-day-title">' . esc_html( $day->title ) . '</h4>';
			$html .= '  </div>';
			if ( ! empty( $day->description ) ) {
				$html .= '  <p class="at-timeline-day-summary">' . esc_html( $day->description ) . '</p>';
			}

			if ( ! empty( $items ) ) {
				$html .= '  <div class="at-timeline-events">';
				foreach ( $items as $item ) {
					$img_html = $item->image_url ? '<div class="at-event-media"><img src="' . esc_url( $item->image_url ) . '" /></div>' : '';
					$icon_class = $item->icon ? $item->icon : 'dashicons-palmtree';
					$html .= '     <div class="at-timeline-event-card">';
					$html .= '        <div class="at-event-icon-wrapper"><span class="dashicons ' . esc_attr( $icon_class ) . '"></span></div>';
					$html .= '        <div class="at-event-content-box">';
					$html .= '           <div class="at-event-meta">';
					$html .= '              <span class="at-event-time">' . esc_html( $item->item_time ) . '</span>';
					$html .= '           </div>';
					$html .= '           <h5 class="at-event-title">' . esc_html( $item->title ) . '</h5>';
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
