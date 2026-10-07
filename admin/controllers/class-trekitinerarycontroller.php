<?php
/**
 * Controller for managing Dynamic Itineraries and Timeline Events.
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
 * TrekItineraryController class.
 */
class TrekItineraryController {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Register AJAX endpoints.
		add_action( 'wp_ajax_trekpilot_get_itinerary', array( $this, 'ajax_get_itinerary' ) );
		add_action( 'wp_ajax_trekpilot_save_itinerary_day', array( $this, 'ajax_save_day' ) );
		add_action( 'wp_ajax_trekpilot_delete_itinerary_day', array( $this, 'ajax_delete_day' ) );
		add_action( 'wp_ajax_trekpilot_reorder_itinerary_days', array( $this, 'ajax_reorder_days' ) );

		add_action( 'wp_ajax_trekpilot_save_itinerary_item', array( $this, 'ajax_save_item' ) );
		add_action( 'wp_ajax_trekpilot_delete_itinerary_item', array( $this, 'ajax_delete_item' ) );
		add_action( 'wp_ajax_trekpilot_reorder_itinerary_items', array( $this, 'ajax_reorder_items' ) );

		// Load assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue itinerary assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		global $post_type;

		if ( 'trekpilot_trek' !== $post_type ) {
			return;
		}

		wp_enqueue_script(
			'trekpilot-admin-itinerary-js',
			TREKPILOT_URL . 'assets/admin/js/admin-itinerary.js',
			array(),
			\TrekPilot\Includes\Plugin::asset_version( 'assets/admin/js/admin-itinerary.js' ),
			true
		);

		wp_localize_script(
			'trekpilot-admin-itinerary-js',
			'trekpilot_itinerary_obj',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'trekpilot_itinerary_nonce_action' ),
			)
		);
	}

	/**
	 * Resolve the trek ID that owns a given itinerary day.
	 *
	 * @param int $day_id Itinerary day ID.
	 * @return int
	 */
	private function get_day_trek_id( $day_id ) {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$trek_id = $wpdb->get_var(
			$wpdb->prepare( "SELECT trek_id FROM {$wpdb->prefix}trekpilot_itineraries WHERE id = %d", $day_id )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery
		return (int) $trek_id;
	}

	/**
	 * Resolve the trek ID that owns a given itinerary timeline item.
	 *
	 * @param int $item_id Itinerary item ID.
	 * @return int
	 */
	private function get_item_trek_id( $item_id ) {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$itinerary_id = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT itinerary_id FROM {$wpdb->prefix}trekpilot_itinerary_items WHERE id = %d", $item_id )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery
		return $itinerary_id ? $this->get_day_trek_id( $itinerary_id ) : 0;
	}

	/**
	 * AJAX: Get full itinerary structure for a city.
	 */
	public function ajax_get_itinerary() {
		check_ajax_referer( 'trekpilot_itinerary_nonce_action', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'trekpilot' ) ) );
		}

		$city_id = isset( $_GET['city_id'] ) ? intval( $_GET['city_id'] ) : 0;
		if ( ! $city_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid City ID', 'trekpilot' ) ) );
		}

		global $wpdb;
		$table_days = $wpdb->prefix . 'trekpilot_itineraries';

		// Fetch days.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$days = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->prepare( "SELECT * FROM $table_days WHERE city_id = %d ORDER BY menu_order ASC", $city_id ),
			ARRAY_A
		);

		// Hydrate days with timeline items.
		$items_by_day = \TrekPilot\Includes\Database::get_items_by_day( wp_list_pluck( $days, 'id' ), ARRAY_A );
		foreach ( $days as &$day ) {
			$day_id       = intval( $day['id'] );
			$day['items'] = isset( $items_by_day[ $day_id ] ) ? $items_by_day[ $day_id ] : array();
		}
		unset( $day );

		wp_send_json_success( $days );
	}

	/**
	 * AJAX: Save a day (add or update).
	 */
	public function ajax_save_day() {
		check_ajax_referer( 'trekpilot_itinerary_nonce_action', 'nonce' );

		$id      = isset( $_POST['id'] ) ? intval( wp_unslash( $_POST['id'] ) ) : 0;
		$city_id = isset( $_POST['city_id'] ) ? intval( wp_unslash( $_POST['city_id'] ) ) : 0;
		$trek_id = isset( $_POST['trek_id'] ) ? intval( wp_unslash( $_POST['trek_id'] ) ) : 0;

		$owner_trek_id = $id ? $this->get_day_trek_id( $id ) : $trek_id;
		if ( ! $owner_trek_id || ! current_user_can( 'edit_post', $owner_trek_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'trekpilot' ) ) );
		}

		$day_number  = isset( $_POST['day_number'] ) ? intval( wp_unslash( $_POST['day_number'] ) ) : 0;
		$title       = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';

		if ( empty( $title ) || ! $city_id || ! $trek_id ) {
			wp_send_json_error( array( 'message' => __( 'Day title is required', 'trekpilot' ) ) );
		}

		global $wpdb;
		$table_days = $wpdb->prefix . 'trekpilot_itineraries';

		$data = array(
			'city_id'     => $city_id,
			'trek_id'     => $trek_id,
			'day_number'  => $day_number,
			'title'       => $title,
			'description' => $description,
		);

		if ( $id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->update( $table_days, $data, array( 'id' => $id ), array( '%d', '%d', '%d', '%s', '%s' ), array( '%d' ) );
			wp_send_json_success(
				array(
					'message' => __( 'Day updated successfully', 'trekpilot' ),
					'id'      => $id,
				)
			);
		} else {
			// Find max order.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$max_order          = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(menu_order) FROM $table_days WHERE city_id = %d", $city_id ) );
			$data['menu_order'] = intval( $max_order ) + 1;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$inserted = $wpdb->insert( $table_days, $data, array( '%d', '%d', '%d', '%s', '%s', '%d' ) );
			if ( ! $inserted ) {
				wp_send_json_error( array( 'message' => __( 'Failed to add day', 'trekpilot' ) ) );
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			wp_send_json_success(
				array(
					'message' => __( 'Day added successfully', 'trekpilot' ),
					'id'      => $wpdb->insert_id,
				)
			);
		}
	}

	/**
	 * AJAX: Delete a day (deletes all child events too).
	 */
	public function ajax_delete_day() {
		check_ajax_referer( 'trekpilot_itinerary_nonce_action', 'nonce' );

		$id = isset( $_POST['id'] ) ? intval( wp_unslash( $_POST['id'] ) ) : 0;
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid Day ID', 'trekpilot' ) ) );
		}

		$trek_id = $this->get_day_trek_id( $id );
		if ( ! $trek_id || ! current_user_can( 'edit_post', $trek_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'trekpilot' ) ) );
		}

		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $wpdb->prefix . 'trekpilot_itineraries', array( 'id' => $id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'trekpilot_itinerary_items', array( 'itinerary_id' => $id ), array( '%d' ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery

		wp_send_json_success( array( 'message' => __( 'Day deleted successfully', 'trekpilot' ) ) );
	}

	/**
	 * AJAX: Save day reordering.
	 */
	public function ajax_reorder_days() {
		check_ajax_referer( 'trekpilot_itinerary_nonce_action', 'nonce' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_order = isset( $_POST['order'] ) ? wp_unslash( $_POST['order'] ) : array();
		$order     = is_array( $raw_order ) ? array_map( 'intval', $raw_order ) : array();
		if ( empty( $order ) || ! is_array( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'No order layout received', 'trekpilot' ) ) );
		}

		foreach ( $order as $id ) {
			$trek_id = $this->get_day_trek_id( $id );
			if ( ! $trek_id || ! current_user_can( 'edit_post', $trek_id ) ) {
				wp_send_json_error( array( 'message' => __( 'Unauthorized', 'trekpilot' ) ) );
			}
		}

		global $wpdb;
		$table_days = $wpdb->prefix . 'trekpilot_itineraries';

		foreach ( $order as $menu_order => $id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->update(
				$table_days,
				array( 'menu_order' => $menu_order ),
				array( 'id' => intval( $id ) ),
				array( '%d' ),
				array( '%d' )
			);
		}

		wp_send_json_success( array( 'message' => __( 'Days reordered successfully', 'trekpilot' ) ) );
	}

	/**
	 * AJAX: Save a timeline activity event (add or update).
	 */
	public function ajax_save_item() {
		check_ajax_referer( 'trekpilot_itinerary_nonce_action', 'nonce' );

		$id           = isset( $_POST['id'] ) ? intval( wp_unslash( $_POST['id'] ) ) : 0;
		$itinerary_id = isset( $_POST['itinerary_id'] ) ? intval( wp_unslash( $_POST['itinerary_id'] ) ) : 0;

		$owner_trek_id = $id ? $this->get_item_trek_id( $id ) : ( $itinerary_id ? $this->get_day_trek_id( $itinerary_id ) : 0 );
		if ( ! $owner_trek_id || ! current_user_can( 'edit_post', $owner_trek_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'trekpilot' ) ) );
		}

		$item_time   = isset( $_POST['item_time'] ) ? sanitize_text_field( wp_unslash( $_POST['item_time'] ) ) : '';
		$title       = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$description = isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '';
		$icon        = isset( $_POST['icon'] ) ? sanitize_text_field( wp_unslash( $_POST['icon'] ) ) : '';
		$image_url   = isset( $_POST['image_url'] ) ? esc_url_raw( wp_unslash( $_POST['image_url'] ) ) : '';

		if ( empty( $title ) || ! $itinerary_id ) {
			wp_send_json_error( array( 'message' => __( 'Activity title is required', 'trekpilot' ) ) );
		}

		global $wpdb;
		$table_items = $wpdb->prefix . 'trekpilot_itinerary_items';

		$data = array(
			'itinerary_id' => $itinerary_id,
			'item_time'    => $item_time,
			'title'        => $title,
			'description'  => $description,
			'icon'         => $icon,
			'image_url'    => $image_url,
		);

		if ( $id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->update( $table_items, $data, array( 'id' => $id ), array( '%d', '%s', '%s', '%s', '%s', '%s' ), array( '%d' ) );
			wp_send_json_success(
				array(
					'message' => __( 'Activity updated successfully', 'trekpilot' ),
					'id'      => $id,
				)
			);
		} else {
			// Find max order.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$max_order          = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(menu_order) FROM $table_items WHERE itinerary_id = %d", $itinerary_id ) );
			$data['menu_order'] = intval( $max_order ) + 1;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$inserted = $wpdb->insert( $table_items, $data, array( '%d', '%s', '%s', '%s', '%s', '%s', '%d' ) );
			if ( ! $inserted ) {
				wp_send_json_error( array( 'message' => __( 'Failed to add activity event', 'trekpilot' ) ) );
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			wp_send_json_success(
				array(
					'message' => __( 'Activity added successfully', 'trekpilot' ),
					'id'      => $wpdb->insert_id,
				)
			);
		}
	}

	/**
	 * AJAX: Delete a timeline activity event.
	 */
	public function ajax_delete_item() {
		check_ajax_referer( 'trekpilot_itinerary_nonce_action', 'nonce' );

		$id = isset( $_POST['id'] ) ? intval( wp_unslash( $_POST['id'] ) ) : 0;
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid Activity ID', 'trekpilot' ) ) );
		}

		$trek_id = $this->get_item_trek_id( $id );
		if ( ! $trek_id || ! current_user_can( 'edit_post', $trek_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'trekpilot' ) ) );
		}

		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $wpdb->prefix . 'trekpilot_itinerary_items', array( 'id' => $id ), array( '%d' ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery

		wp_send_json_success( array( 'message' => __( 'Activity deleted successfully', 'trekpilot' ) ) );
	}

	/**
	 * AJAX: Save timeline activities reordering.
	 */
	public function ajax_reorder_items() {
		check_ajax_referer( 'trekpilot_itinerary_nonce_action', 'nonce' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_order = isset( $_POST['order'] ) ? wp_unslash( $_POST['order'] ) : array();
		$order     = is_array( $raw_order ) ? array_map( 'intval', $raw_order ) : array();
		if ( empty( $order ) || ! is_array( $order ) ) {
			wp_send_json_error( array( 'message' => __( 'No order layout received', 'trekpilot' ) ) );
		}

		foreach ( $order as $id ) {
			$trek_id = $this->get_item_trek_id( $id );
			if ( ! $trek_id || ! current_user_can( 'edit_post', $trek_id ) ) {
				wp_send_json_error( array( 'message' => __( 'Unauthorized', 'trekpilot' ) ) );
			}
		}

		global $wpdb;
		$table_items = $wpdb->prefix . 'trekpilot_itinerary_items';

		foreach ( $order as $menu_order => $id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->update(
				$table_items,
				array( 'menu_order' => $menu_order ),
				array( 'id' => intval( $id ) ),
				array( '%d' ),
				array( '%d' )
			);
		}

		wp_send_json_success( array( 'message' => __( 'Activities reordered successfully', 'trekpilot' ) ) );
	}
}
