<?php
/**
 * Trek Pickup Points Controller
 *
 * @package    TrekPilot
 * @subpackage TrekPilot/Admin/Controllers
 * @author     Nilesh Vastarpara
 */

namespace TrekPilot\Admin\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * TrekPickupPointsController class.
 */
class TrekPickupPointsController {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_trekpilot_get_pickups', array( $this, 'ajax_get_pickups' ) );
		add_action( 'wp_ajax_trekpilot_save_pickup', array( $this, 'ajax_save_pickup' ) );
		add_action( 'wp_ajax_trekpilot_delete_pickup', array( $this, 'ajax_delete_pickup' ) );
		add_action( 'wp_ajax_trekpilot_reorder_pickups', array( $this, 'ajax_reorder_pickups' ) );
	}

	/**
	 * Get trek ID from city ID.
	 *
	 * @param int $city_id City ID.
	 * @return int Trek ID.
	 */
	private function get_city_trek_id( $city_id ) {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT trek_id FROM {$wpdb->prefix}trekpilot_departure_cities WHERE id = %d", $city_id )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * Get trek ID from pickup ID.
	 *
	 * @param int $pickup_id Pickup ID.
	 * @return int Trek ID.
	 */
	private function get_pickup_trek_id( $pickup_id ) {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT trek_id FROM {$wpdb->prefix}trekpilot_pickup_points WHERE id = %d", $pickup_id )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery
	}

	/**
	 * AJAX: Get pickup points for a city.
	 */
	public function ajax_get_pickups() {
		check_ajax_referer( 'trekpilot_departures_nonce_action', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'trekpilot' ) ) );
		}

		$city_id = isset( $_GET['city_id'] ) ? intval( $_GET['city_id'] ) : 0;
		if ( ! $city_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid City ID', 'trekpilot' ) ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'trekpilot_pickup_points';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $table_name WHERE city_id = %d ORDER BY menu_order ASC", $city_id ),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		wp_send_json_success( $results );
	}

	/**
	 * AJAX: Save pickup point.
	 */
	public function ajax_save_pickup() {
		check_ajax_referer( 'trekpilot_departures_nonce_action', 'nonce' );

		$id      = isset( $_POST['id'] ) ? intval( wp_unslash( $_POST['id'] ) ) : 0;
		$city_id = isset( $_POST['city_id'] ) ? intval( wp_unslash( $_POST['city_id'] ) ) : 0;

		$trek_id = $id ? $this->get_pickup_trek_id( $id ) : $this->get_city_trek_id( $city_id );

		if ( ! $trek_id || ! current_user_can( 'edit_post', $trek_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'trekpilot' ) ) );
		}

		$location_name   = isset( $_POST['location_name'] ) ? sanitize_text_field( wp_unslash( $_POST['location_name'] ) ) : '';
		$pickup_time     = isset( $_POST['pickup_time'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_time'] ) ) : '';
		$google_maps_url = isset( $_POST['google_maps_url'] ) ? esc_url_raw( wp_unslash( $_POST['google_maps_url'] ) ) : '';
		$instructions    = isset( $_POST['instructions'] ) ? sanitize_textarea_field( wp_unslash( $_POST['instructions'] ) ) : '';

		if ( empty( $location_name ) || ! $city_id ) {
			wp_send_json_error( array( 'message' => __( 'Location name is required', 'trekpilot' ) ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'trekpilot_pickup_points';

		$data = array(
			'city_id'         => $city_id,
			'trek_id'         => $trek_id,
			'location_name'   => $location_name,
			'pickup_time'     => $pickup_time,
			'google_maps_url' => $google_maps_url,
			'instructions'    => $instructions,
		);

		if ( $id ) {
			// Update.
			// phpcs:disable WordPress.DB.DirectDatabaseQuery
			$updated = $wpdb->update(
				$table_name,
				$data,
				array( 'id' => $id ),
				array( '%d', '%d', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery

			if ( false === $updated ) {
				wp_send_json_error( array( 'message' => __( 'Failed to update pickup point', 'trekpilot' ) ) );
			}
			wp_send_json_success(
				array(
					'message' => __( 'Pickup point updated successfully', 'trekpilot' ),
					'id'      => $id,
				)
			);
		} else {
			// Get max menu_order.
			// phpcs:disable WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$max_order          = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(menu_order) FROM $table_name WHERE city_id = %d", $city_id ) );
			$data['menu_order'] = intval( $max_order ) + 1;

			$inserted = $wpdb->insert(
				$table_name,
				$data,
				array( '%d', '%d', '%s', '%s', '%s', '%s', '%d' )
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			if ( ! $inserted ) {
				wp_send_json_error( array( 'message' => __( 'Failed to insert pickup point', 'trekpilot' ) ) );
			}
			wp_send_json_success(
				array(
					'message' => __( 'Pickup point added successfully', 'trekpilot' ),
					'id'      => $wpdb->insert_id,
				)
			);
		}
	}

	/**
	 * AJAX: Delete pickup point.
	 */
	public function ajax_delete_pickup() {
		check_ajax_referer( 'trekpilot_departures_nonce_action', 'nonce' );

		$id = isset( $_POST['id'] ) ? intval( wp_unslash( $_POST['id'] ) ) : 0;
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid Pickup ID', 'trekpilot' ) ) );
		}

		$trek_id = $this->get_pickup_trek_id( $id );
		if ( ! $trek_id || ! current_user_can( 'edit_post', $trek_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'trekpilot' ) ) );
		}

		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $wpdb->prefix . 'trekpilot_pickup_points', array( 'id' => $id ), array( '%d' ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery

		wp_send_json_success( array( 'message' => __( 'Pickup point deleted successfully', 'trekpilot' ) ) );
	}

	/**
	 * AJAX: Reorder pickup points.
	 */
	public function ajax_reorder_pickups() {
		check_ajax_referer( 'trekpilot_departures_nonce_action', 'nonce' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$order = isset( $_POST['order'] ) ? array_map( 'intval', (array) $_POST['order'] ) : array();
		if ( empty( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'No order data', 'trekpilot' ) ) );
		}

		// Verify capability for first item.
		$first_item = $order[0];
		$trek_id    = $this->get_pickup_trek_id( $first_item );
		if ( ! $trek_id || ! current_user_can( 'edit_post', $trek_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'trekpilot' ) ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'trekpilot_pickup_points';

		foreach ( $order as $index => $id ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery
			$wpdb->update(
				$table_name,
				array( 'menu_order' => $index ),
				array( 'id' => $id ),
				array( '%d' ),
				array( '%d' )
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery
		}

		wp_send_json_success();
	}
}
