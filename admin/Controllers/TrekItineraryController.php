<?php
/**
 * Controller for managing Dynamic Itineraries and Timeline Events.
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
 * TrekItineraryController class.
 */
class TrekItineraryController {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Register AJAX endpoints.
		add_action( 'wp_ajax_at_get_itinerary', array( $this, 'ajax_get_itinerary' ) );
		add_action( 'wp_ajax_at_save_itinerary_day', array( $this, 'ajax_save_day' ) );
		add_action( 'wp_ajax_at_delete_itinerary_day', array( $this, 'ajax_delete_day' ) );
		add_action( 'wp_ajax_at_reorder_itinerary_days', array( $this, 'ajax_reorder_days' ) );

		add_action( 'wp_ajax_at_save_itinerary_item', array( $this, 'ajax_save_item' ) );
		add_action( 'wp_ajax_at_delete_itinerary_item', array( $this, 'ajax_delete_item' ) );
		add_action( 'wp_ajax_at_reorder_itinerary_items', array( $this, 'ajax_reorder_items' ) );

		// Load assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue itinerary assets.
	 */
	public function enqueue_assets( $hook ) {
		global $post_type;

		if ( 'adventure_trek' !== $post_type ) {
			return;
		}

		wp_enqueue_script(
			'at-admin-itinerary-js',
			ADVENTURE_TREKS_URL . 'assets/admin/js/admin-itinerary.js',
			array(),
			ADVENTURE_TREKS_VERSION,
			true
		);

		wp_localize_script( 'at-admin-itinerary-js', 'at_itinerary_obj', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'at_itinerary_nonce_action' ),
		) );
	}

	/**
	 * AJAX: Get full itinerary structure for a city.
	 */
	public function ajax_get_itinerary() {
		check_ajax_referer( 'at_itinerary_nonce_action', 'nonce' );

		$city_id = isset( $_GET['city_id'] ) ? intval( $_GET['city_id'] ) : 0;
		if ( ! $city_id ) {
			wp_send_json_error( array( 'message' => 'Invalid City ID' ) );
		}

		global $wpdb;
		$table_days  = $wpdb->prefix . 'at_itineraries';
		$table_items = $wpdb->prefix . 'at_itinerary_items';

		// Fetch days
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$days = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->prepare( "SELECT * FROM $table_days WHERE city_id = %d ORDER BY menu_order ASC", $city_id ),
			ARRAY_A
		);

		// Hydrate days with timeline items
		foreach ( $days as &$day ) {
			$day_id = intval( $day['id'] );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$day['items'] = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$wpdb->prepare( "SELECT * FROM $table_items WHERE itinerary_id = %d ORDER BY menu_order ASC", $day_id ),
				ARRAY_A
			);
		}

		wp_send_json_success( $days );
	}

	/**
	 * AJAX: Save a day (add or update).
	 */
	public function ajax_save_day() {
		check_ajax_referer( 'at_itinerary_nonce_action', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$id          = isset( $_POST['id'] ) ? intval( wp_unslash( $_POST['id'] ) ) : 0;
		$city_id     = isset( $_POST['city_id'] ) ? intval( wp_unslash( $_POST['city_id'] ) ) : 0;
		$trek_id     = isset( $_POST['trek_id'] ) ? intval( wp_unslash( $_POST['trek_id'] ) ) : 0;
		$day_number  = isset( $_POST['day_number'] ) ? intval( wp_unslash( $_POST['day_number'] ) ) : 0;
		$title       = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$description = isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '';

		if ( empty( $title ) || ! $city_id || ! $trek_id ) {
			wp_send_json_error( array( 'message' => 'Day title is required' ) );
		}

		global $wpdb;
		$table_days = $wpdb->prefix . 'at_itineraries';

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
			wp_send_json_success( array( 'message' => 'Day updated successfully', 'id' => $id ) );
		} else {
			// Find max order
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$max_order = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(menu_order) FROM $table_days WHERE city_id = %d", $city_id ) );
			$data['menu_order'] = intval( $max_order ) + 1;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$inserted = $wpdb->insert( $table_days, $data, array( '%d', '%d', '%d', '%s', '%s', '%d' ) );
			if ( ! $inserted ) {
				wp_send_json_error( array( 'message' => 'Failed to add day' ) );
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			wp_send_json_success( array( 'message' => 'Day added successfully', 'id' => $wpdb->insert_id ) );
		}
	}

	/**
	 * AJAX: Delete a day (deletes all child events too).
	 */
	public function ajax_delete_day() {
		check_ajax_referer( 'at_itinerary_nonce_action', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$id = isset( $_POST['id'] ) ? intval( wp_unslash( $_POST['id'] ) ) : 0;
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => 'Invalid Day ID' ) );
		}

		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $wpdb->prefix . 'at_itineraries', array( 'id' => $id ), array( '%d' ) );
		$wpdb->delete( $wpdb->prefix . 'at_itinerary_items', array( 'itinerary_id' => $id ), array( '%d' ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery

		wp_send_json_success( array( 'message' => 'Day deleted successfully' ) );
	}

	/**
	 * AJAX: Save day reordering.
	 */
	public function ajax_reorder_days() {
		check_ajax_referer( 'at_itinerary_nonce_action', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_order = isset( $_POST['order'] ) ? wp_unslash( $_POST['order'] ) : array();
		$order     = is_array( $raw_order ) ? array_map( 'intval', $raw_order ) : array();
		if ( empty( $order ) || ! is_array( $order ) ) {
			wp_send_json_error( array( 'message' => 'No order layout received' ) );
		}

		global $wpdb;
		$table_days = $wpdb->prefix . 'at_itineraries';

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

		wp_send_json_success( array( 'message' => 'Days reordered successfully' ) );
	}

	/**
	 * AJAX: Save a timeline activity event (add or update).
	 */
	public function ajax_save_item() {
		check_ajax_referer( 'at_itinerary_nonce_action', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$id           = isset( $_POST['id'] ) ? intval( wp_unslash( $_POST['id'] ) ) : 0;
		$itinerary_id = isset( $_POST['itinerary_id'] ) ? intval( wp_unslash( $_POST['itinerary_id'] ) ) : 0;
		$item_time    = isset( $_POST['item_time'] ) ? sanitize_text_field( wp_unslash( $_POST['item_time'] ) ) : '';
		$title        = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$description  = isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '';
		$icon         = isset( $_POST['icon'] ) ? sanitize_text_field( wp_unslash( $_POST['icon'] ) ) : '';
		$image_url    = isset( $_POST['image_url'] ) ? esc_url_raw( wp_unslash( $_POST['image_url'] ) ) : '';

		if ( empty( $title ) || ! $itinerary_id ) {
			wp_send_json_error( array( 'message' => 'Activity title is required' ) );
		}

		global $wpdb;
		$table_items = $wpdb->prefix . 'at_itinerary_items';

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
			wp_send_json_success( array( 'message' => 'Activity updated successfully', 'id' => $id ) );
		} else {
			// Find max order
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$max_order = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(menu_order) FROM $table_items WHERE itinerary_id = %d", $itinerary_id ) );
			$data['menu_order'] = intval( $max_order ) + 1;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$inserted = $wpdb->insert( $table_items, $data, array( '%d', '%s', '%s', '%s', '%s', '%s', '%d' ) );
			if ( ! $inserted ) {
				wp_send_json_error( array( 'message' => 'Failed to add activity event' ) );
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			wp_send_json_success( array( 'message' => 'Activity added successfully', 'id' => $wpdb->insert_id ) );
		}
	}

	/**
	 * AJAX: Delete a timeline activity event.
	 */
	public function ajax_delete_item() {
		check_ajax_referer( 'at_itinerary_nonce_action', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$id = isset( $_POST['id'] ) ? intval( wp_unslash( $_POST['id'] ) ) : 0;
		if ( ! $id ) {
			wp_send_json_error( array( 'message' => 'Invalid Activity ID' ) );
		}

		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $wpdb->prefix . 'at_itinerary_items', array( 'id' => $id ), array( '%d' ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery

		wp_send_json_success( array( 'message' => 'Activity deleted successfully' ) );
	}

	/**
	 * AJAX: Save timeline activities reordering.
	 */
	public function ajax_reorder_items() {
		check_ajax_referer( 'at_itinerary_nonce_action', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$raw_order = isset( $_POST['order'] ) ? wp_unslash( $_POST['order'] ) : array();
		$order     = is_array( $raw_order ) ? array_map( 'intval', $raw_order ) : array();
		if ( empty( $order ) || ! is_array( $order ) ) {
			wp_send_json_error( array( 'message' => 'No order layout received' ) );
		}

		global $wpdb;
		$table_items = $wpdb->prefix . 'at_itinerary_items';

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

		wp_send_json_success( array( 'message' => 'Activities reordered successfully' ) );
	}
}
