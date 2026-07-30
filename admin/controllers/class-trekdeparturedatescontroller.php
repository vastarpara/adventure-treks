<?php
/**
 * Controller for managing Departure Dates, Seats, and Date-Specific Pricing.
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
 * TrekDepartureDatesController class.
 */
class TrekDepartureDatesController {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Register AJAX endpoints.
		add_action( 'wp_ajax_at_get_departure_dates', array( $this, 'ajax_get_dates' ) );
		add_action( 'wp_ajax_at_save_departure_date', array( $this, 'ajax_save_date' ) );
		add_action( 'wp_ajax_at_delete_departure_date', array( $this, 'ajax_delete_date' ) );

		// Load assets hook.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue date management scripts.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		global $post_type;

		if ( 'adventure_trek' !== $post_type ) {
			return;
		}

		wp_enqueue_script(
			'at-admin-dates-js',
			ADVENTURE_TREKS_URL . 'assets/admin/js/admin-dates.js',
			array(),
			ADVENTURE_TREKS_VERSION,
			true
		);

		wp_localize_script(
			'at-admin-dates-js',
			'at_dates_obj',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'at_dates_nonce_action' ),
			)
		);
	}

	/**
	 * Resolve the trek ID that owns a given departure date.
	 *
	 * @param int $date_id Departure date ID.
	 * @return int
	 */
	private function get_date_trek_id( $date_id ) {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$trek_id = $wpdb->get_var(
			$wpdb->prepare( "SELECT trek_id FROM {$wpdb->prefix}at_departure_dates WHERE id = %d", $date_id )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery
		return (int) $trek_id;
	}

	/**
	 * AJAX: Get dates, availability, and pricing overrides for a city.
	 */
	public function ajax_get_dates() {
		check_ajax_referer( 'at_dates_nonce_action', 'nonce' );

		$city_id = isset( $_GET['city_id'] ) ? intval( $_GET['city_id'] ) : 0;
		if ( ! $city_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid City ID', 'adventure-treks' ) ) );
		}

		global $wpdb;
		$table_dates = $wpdb->prefix . 'at_departure_dates';
		$table_avail = $wpdb->prefix . 'at_availability';
		$table_price = $wpdb->prefix . 'at_pricing';

		// Get all dates.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$dates = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->prepare( "SELECT * FROM $table_dates WHERE city_id = %d ORDER BY departure_date ASC", $city_id ),
			ARRAY_A
		);

		// Hydrate with availability and pricing overrides.
		foreach ( $dates as &$date ) {
			$date_id = intval( $date['id'] );

			// Get seats availability.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$avail = $wpdb->get_row(
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$wpdb->prepare( "SELECT total_seats, booked_seats, available_seats FROM $table_avail WHERE date_id = %d", $date_id ),
				ARRAY_A
			);
			if ( $avail ) {
				$date['total_seats']     = intval( $avail['total_seats'] );
				$date['booked_seats']    = intval( $avail['booked_seats'] );
				$date['available_seats'] = intval( $avail['available_seats'] );
			} else {
				$date['total_seats']     = 0;
				$date['booked_seats']    = 0;
				$date['available_seats'] = 0;
			}

			// Get pricing overrides.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$price = $wpdb->get_row(
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$wpdb->prepare( "SELECT adult_price, child_price, offer_price FROM $table_price WHERE date_id = %d", $date_id ),
				ARRAY_A
			);
			if ( $price ) {
				$date['adult_price'] = floatval( $price['adult_price'] );
				$date['child_price'] = floatval( $price['child_price'] );
				$date['offer_price'] = floatval( $price['offer_price'] );
			} else {
				$date['adult_price'] = 0.00;
				$date['child_price'] = 0.00;
				$date['offer_price'] = 0.00;
			}
		}

		wp_send_json_success( $dates );
	}

	/**
	 * AJAX: Save a departure date (insert or update).
	 */
	public function ajax_save_date() {
		check_ajax_referer( 'at_dates_nonce_action', 'nonce' );

		$id      = isset( $_POST['id'] ) ? intval( wp_unslash( $_POST['id'] ) ) : 0;
		$city_id = isset( $_POST['city_id'] ) ? intval( wp_unslash( $_POST['city_id'] ) ) : 0;
		$trek_id = isset( $_POST['trek_id'] ) ? intval( wp_unslash( $_POST['trek_id'] ) ) : 0;

		$owner_trek_id = $id ? $this->get_date_trek_id( $id ) : $trek_id;
		if ( ! $owner_trek_id || ! current_user_can( 'edit_post', $owner_trek_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'adventure-treks' ) ) );
		}

		$departure_date = isset( $_POST['departure_date'] ) ? sanitize_text_field( wp_unslash( $_POST['departure_date'] ) ) : '';
		$status         = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'open';
		$notes          = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';

		// Availability.
		$total_seats  = isset( $_POST['total_seats'] ) ? intval( wp_unslash( $_POST['total_seats'] ) ) : 0;
		$booked_seats = isset( $_POST['booked_seats'] ) ? intval( wp_unslash( $_POST['booked_seats'] ) ) : 0;
		$avail_seats  = $total_seats - $booked_seats;

		// Pricing.
		$adult_price = isset( $_POST['adult_price'] ) ? floatval( wp_unslash( $_POST['adult_price'] ) ) : 0.00;
		$child_price = isset( $_POST['child_price'] ) ? floatval( wp_unslash( $_POST['child_price'] ) ) : 0.00;
		$offer_price = isset( $_POST['offer_price'] ) ? floatval( wp_unslash( $_POST['offer_price'] ) ) : 0.00;

		if ( empty( $departure_date ) || ! $city_id || ! $trek_id ) {
			wp_send_json_error( array( 'message' => __( 'Departure date is required', 'adventure-treks' ) ) );
		}

		global $wpdb;
		$table_dates = $wpdb->prefix . 'at_departure_dates';
		$table_avail = $wpdb->prefix . 'at_availability';
		$table_price = $wpdb->prefix . 'at_pricing';

		$date_data = array(
			'city_id'        => $city_id,
			'trek_id'        => $trek_id,
			'departure_date' => $departure_date,
			'status'         => $status,
			'notes'          => $notes,
		);

		if ( $id ) {
			// Update date.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->update(
				$table_dates,
				$date_data,
				array( 'id' => $id ),
				array( '%d', '%d', '%s', '%s', '%s' ),
				array( '%d' )
			);
			$date_id = $id;
		} else {
			// Insert date.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$inserted = $wpdb->insert(
				$table_dates,
				$date_data,
				array( '%d', '%d', '%s', '%s', '%s' )
			);
			if ( ! $inserted ) {
				wp_send_json_error( array( 'message' => __( 'Failed to add departure date', 'adventure-treks' ) ) );
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$date_id = $wpdb->insert_id;
		}

		// Save seats availability.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$avail_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_avail WHERE date_id = %d", $date_id ) );
		$avail_data   = array(
			'date_id'         => $date_id,
			'total_seats'     => $total_seats,
			'booked_seats'    => $booked_seats,
			'available_seats' => $avail_seats,
		);
		if ( $avail_exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->update( $table_avail, $avail_data, array( 'id' => $avail_exists ), array( '%d', '%d', '%d', '%d' ), array( '%d' ) );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->insert( $table_avail, $avail_data, array( '%d', '%d', '%d', '%d' ) );
		}

		// Save pricing overrides.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$price_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_price WHERE date_id = %d", $date_id ) );

		// We only insert or update if at least one price override is set.
		if ( $adult_price > 0 || $child_price > 0 || $offer_price > 0 ) {
			$price_data = array(
				'city_id'     => $city_id,
				'date_id'     => $date_id,
				'trek_id'     => $trek_id,
				'adult_price' => $adult_price,
				'child_price' => $child_price,
				'offer_price' => $offer_price,
			);
			if ( $price_exists ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$wpdb->update( $table_price, $price_data, array( 'id' => $price_exists ), array( '%d', '%d', '%d', '%f', '%f', '%f' ), array( '%d' ) );
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$wpdb->insert( $table_price, $price_data, array( '%d', '%d', '%d', '%f', '%f', '%f' ) );
			}
		} elseif ( $price_exists ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery
			$wpdb->delete( $table_price, array( 'id' => $price_exists ), array( '%d' ) );
			// phpcs:enable WordPress.DB.DirectDatabaseQuery
		}

		wp_send_json_success(
			array(
				'message' => __( 'Departure date configured successfully', 'adventure-treks' ),
				'id'      => $date_id,
			)
		);
	}

	/**
	 * AJAX: Delete a departure date.
	 */
	public function ajax_delete_date() {
		check_ajax_referer( 'at_dates_nonce_action', 'nonce' );

		$id = isset( $_POST['id'] ) ? intval( wp_unslash( $_POST['id'] ) ) : 0;
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid Date ID', 'adventure-treks' ) ) );
		}

		$trek_id = $this->get_date_trek_id( $id );
		if ( ! $trek_id || ! current_user_can( 'edit_post', $trek_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'adventure-treks' ) ) );
		}

		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $wpdb->prefix . 'at_departure_dates', array( 'id' => $id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'at_availability', array( 'date_id' => $id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'at_pricing', array( 'date_id' => $id ), array( '%d' ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery

		wp_send_json_success( array( 'message' => __( 'Departure date deleted successfully', 'adventure-treks' ) ) );
	}
}
