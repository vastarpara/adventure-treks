<?php
/**
 * Controller for managing Departure Cities.
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
 * TrekDepartureCitiesController class.
 */
class TrekDepartureCitiesController {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Register Metabox.
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );

		// Register AJAX endpoints.
		add_action( 'wp_ajax_at_get_departure_cities', array( $this, 'ajax_get_cities' ) );
		add_action( 'wp_ajax_at_save_departure_city', array( $this, 'ajax_save_city' ) );
		add_action( 'wp_ajax_at_delete_departure_city', array( $this, 'ajax_delete_city' ) );
		add_action( 'wp_ajax_at_duplicate_departure_city', array( $this, 'ajax_duplicate_city' ) );
		add_action( 'wp_ajax_at_reorder_departure_cities', array( $this, 'ajax_reorder_cities' ) );

		// Load CSS/JS.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register departure cities meta box.
	 */
	public function register_meta_box() {
		add_meta_box(
			'at_trek_departure_cities_meta_box',
			__( 'Departure Cities Manager', 'adventure-treks' ),
			array( $this, 'render_meta_box' ),
			'adventure_trek',
			'normal',
			'high'
		);
	}

	/**
	 * Enqueue stylesheet and JS for departures.
	 */
	public function enqueue_assets( $hook ) {
		global $post_type;

		if ( 'adventure_trek' !== $post_type ) {
			return;
		}

		wp_enqueue_style(
			'at-admin-departures-css',
			ADVENTURE_TREKS_URL . 'assets/admin/css/admin-departures.css',
			array(),
			ADVENTURE_TREKS_VERSION
		);

		wp_enqueue_script(
			'at-admin-departures-js',
			ADVENTURE_TREKS_URL . 'assets/admin/js/admin-departures.js',
			array(),
			ADVENTURE_TREKS_VERSION,
			true
		);

		// Localize script with nonce and AJAX URL.
		wp_localize_script( 'at-admin-departures-js', 'at_departures_obj', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'at_departures_nonce_action' ),
		) );
	}

	/**
	 * Render departures meta box.
	 */
	public function render_meta_box( $post ) {
		$view_path = plugin_dir_path( dirname( __FILE__ ) ) . 'Views/departure-cities-meta-box.php';
		if ( file_exists( $view_path ) ) {
			include $view_path;
		}
	}

	/**
	 * AJAX: Get list of departure cities for a trek.
	 */
	public function ajax_get_cities() {
		check_ajax_referer( 'at_departures_nonce_action', 'nonce' );

		$trek_id = isset( $_GET['trek_id'] ) ? intval( $_GET['trek_id'] ) : 0;
		if ( ! $trek_id ) {
			wp_send_json_error( array( 'message' => 'Invalid Trek ID' ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'at_departure_cities';

		$results = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $table_name WHERE trek_id = %d ORDER BY menu_order ASC", $trek_id ),
			ARRAY_A
		);

		wp_send_json_success( $results );
	}

	/**
	 * AJAX: Add or Edit a departure city.
	 */
	public function ajax_save_city() {
		check_ajax_referer( 'at_departures_nonce_action', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$id               = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
		$trek_id          = isset( $_POST['trek_id'] ) ? intval( $_POST['trek_id'] ) : 0;
		$city_name        = isset( $_POST['city_name'] ) ? sanitize_text_field( $_POST['city_name'] ) : '';
		$base_price       = isset( $_POST['base_price'] ) ? floatval( $_POST['base_price'] ) : 0.00;
		$offer_price      = isset( $_POST['offer_price'] ) ? floatval( $_POST['offer_price'] ) : 0.00;
		$transport_type   = isset( $_POST['transport_type'] ) ? sanitize_text_field( $_POST['transport_type'] ) : '';
		$reporting_time   = isset( $_POST['reporting_time'] ) ? sanitize_text_field( $_POST['reporting_time'] ) : '';
		$google_map_link  = isset( $_POST['google_map_link'] ) ? esc_url_raw( $_POST['google_map_link'] ) : '';
		$booking_deadline = isset( $_POST['booking_deadline'] ) ? intval( $_POST['booking_deadline'] ) : 0;
		$status           = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'active';

		if ( empty( $city_name ) || ! $trek_id ) {
			wp_send_json_error( array( 'message' => 'City name is required' ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'at_departure_cities';

		$data = array(
			'trek_id'          => $trek_id,
			'city_name'        => $city_name,
			'base_price'       => $base_price,
			'offer_price'      => $offer_price,
			'transport_type'   => $transport_type,
			'reporting_time'   => $reporting_time,
			'google_map_link'  => $google_map_link,
			'booking_deadline' => $booking_deadline,
			'status'           => $status,
		);

		if ( $id ) {
			// Update.
			$updated = $wpdb->update(
				$table_name,
				$data,
				array( 'id' => $id ),
				array( '%d', '%s', '%f', '%f', '%s', '%s', '%s', '%d', '%s' ),
				array( '%d' )
			);
			if ( false === $updated ) {
				wp_send_json_error( array( 'message' => 'Failed to update city' ) );
			}
			wp_send_json_success( array( 'message' => 'City updated successfully', 'id' => $id ) );
		} else {
			// Get max menu_order.
			$max_order = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(menu_order) FROM $table_name WHERE trek_id = %d", $trek_id ) );
			$data['menu_order'] = intval( $max_order ) + 1;

			// Insert.
			$inserted = $wpdb->insert(
				$table_name,
				$data,
				array( '%d', '%s', '%f', '%f', '%s', '%s', '%s', '%d', '%s', '%d' )
			);
			if ( ! $inserted ) {
				wp_send_json_error( array( 'message' => 'Failed to insert city' ) );
			}
			wp_send_json_success( array( 'message' => 'City added successfully', 'id' => $wpdb->insert_id ) );
		}
	}

	/**
	 * AJAX: Delete departure city.
	 */
	public function ajax_delete_city() {
		check_ajax_referer( 'at_departures_nonce_action', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => 'Invalid City ID' ) );
		}

		global $wpdb;
		
		// Delete city.
		$wpdb->delete( $wpdb->prefix . 'at_departure_cities', array( 'id' => $id ), array( '%d' ) );

		// Delete child tables (dates, itineraries, etc. cascade delete is simulated here manually).
		$wpdb->delete( $wpdb->prefix . 'at_departure_dates', array( 'city_id' => $id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'at_itineraries', array( 'city_id' => $id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'at_pickup_points', array( 'city_id' => $id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'at_pricing', array( 'city_id' => $id ), array( '%d' ) );

		wp_send_json_success( array( 'message' => 'City deleted successfully' ) );
	}

	/**
	 * AJAX: Duplicate departure city.
	 */
	public function ajax_duplicate_city() {
		check_ajax_referer( 'at_departures_nonce_action', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => 'Invalid City ID' ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'at_departure_cities';

		// Get city to clone.
		$city = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ), ARRAY_A );
		if ( ! $city ) {
			wp_send_json_error( array( 'message' => 'City not found' ) );
		}

		// Insert duplicate city with modified name.
		unset( $city['id'] );
		$city['city_name']  .= ' (Copy)';
		$max_order           = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(menu_order) FROM $table_name WHERE trek_id = %d", $city['trek_id'] ) );
		$city['menu_order']  = intval( $max_order ) + 1;

		$inserted = $wpdb->insert( $table_name, $city );
		if ( ! $inserted ) {
			wp_send_json_error( array( 'message' => 'Failed to duplicate city' ) );
		}

		$new_city_id = $wpdb->insert_id;

		// Duplicate child itineraries.
		$itineraries = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}at_itineraries WHERE city_id = %d", $id ), ARRAY_A );
		foreach ( $itineraries as $it ) {
			$old_it_id = $it['id'];
			unset( $it['id'] );
			$it['city_id'] = $new_city_id;
			$wpdb->insert( $wpdb->prefix . 'at_itineraries', $it );
			$new_it_id = $wpdb->insert_id;

			// Duplicate itinerary items.
			$it_items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}at_itinerary_items WHERE itinerary_id = %d", $old_it_id ), ARRAY_A );
			foreach ( $it_items as $item ) {
				unset( $item['id'] );
				$item['itinerary_id'] = $new_it_id;
				$wpdb->insert( $wpdb->prefix . 'at_itinerary_items', $item );
			}
		}

		// Duplicate child pickup points.
		$pickups = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}at_pickup_points WHERE city_id = %d", $id ), ARRAY_A );
		foreach ( $pickups as $pick ) {
			unset( $pick['id'] );
			$pick['city_id'] = $new_city_id;
			$wpdb->insert( $wpdb->prefix . 'at_pickup_points', $pick );
		}

		// Duplicate child dates and availability.
		$dates = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}at_departure_dates WHERE city_id = %d", $id ), ARRAY_A );
		foreach ( $dates as $date ) {
			$old_date_id = $date['id'];
			unset( $date['id'] );
			$date['city_id'] = $new_city_id;
			$wpdb->insert( $wpdb->prefix . 'at_departure_dates', $date );
			$new_date_id = $wpdb->insert_id;

			// Duplicate availability.
			$avail = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}at_availability WHERE date_id = %d", $old_date_id ), ARRAY_A );
			if ( $avail ) {
				unset( $avail['id'] );
				$avail['date_id'] = $new_date_id;
				$wpdb->insert( $wpdb->prefix . 'at_availability', $avail );
			}
		}

		// Duplicate pricing rules.
		$prices = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}at_pricing WHERE city_id = %d", $id ), ARRAY_A );
		foreach ( $prices as $price ) {
			unset( $price['id'] );
			$price['city_id'] = $new_city_id;
			// If it's linked to an old date, we map it to the new date.
			if ( $price['date_id'] ) {
				// Find the corresponding cloned date.
				// This is a simple fallback: find the date with same value in the new city.
				$old_date = $wpdb->get_var( $wpdb->prepare( "SELECT departure_date FROM {$wpdb->prefix}at_departure_dates WHERE id = %d", $price['date_id'] ) );
				if ( $old_date ) {
					$new_date_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}at_departure_dates WHERE city_id = %d AND departure_date = %s", $new_city_id, $old_date ) );
					$price['date_id'] = $new_date_id ? $new_date_id : 0;
				}
			}
			$wpdb->insert( $wpdb->prefix . 'at_pricing', $price );
		}

		wp_send_json_success( array( 'message' => 'City and all scheduled settings duplicated successfully!' ) );
	}

	/**
	 * AJAX: Save city ordering.
	 */
	public function ajax_reorder_cities() {
		check_ajax_referer( 'at_departures_nonce_action', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$order = isset( $_POST['order'] ) ? $_POST['order'] : array();
		if ( empty( $order ) || ! is_array( $order ) ) {
			wp_send_json_error( array( 'message' => 'No order layout received' ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'at_departure_cities';

		foreach ( $order as $menu_order => $id ) {
			$wpdb->update(
				$table_name,
				array( 'menu_order' => $menu_order ),
				array( 'id' => intval( $id ) ),
				array( '%d' ),
				array( '%d' )
			);
		}

		wp_send_json_success( array( 'message' => 'Ordering updated successfully' ) );
	}
}
