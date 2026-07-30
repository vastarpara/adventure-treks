<?php
/**
 * Controller for managing City-Level Default Pricing and repeaters.
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
 * TrekPricingController class.
 */
class TrekPricingController {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Register AJAX endpoints.
		add_action( 'wp_ajax_at_get_city_pricing', array( $this, 'ajax_get_pricing' ) );
		add_action( 'wp_ajax_at_save_city_pricing', array( $this, 'ajax_save_pricing' ) );

		// Load assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue pricing assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		global $post_type;

		if ( 'adventure_trek' !== $post_type ) {
			return;
		}

		wp_enqueue_script(
			'at-admin-pricing-js',
			ADVENTURE_TREKS_URL . 'assets/admin/js/admin-pricing.js',
			array(),
			ADVENTURE_TREKS_VERSION,
			true
		);

		wp_localize_script(
			'at-admin-pricing-js',
			'at_pricing_obj',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'at_pricing_nonce_action' ),
			)
		);
	}

	/**
	 * AJAX: Get pricing configuration for a city (date_id = 0).
	 */
	public function ajax_get_pricing() {
		check_ajax_referer( 'at_pricing_nonce_action', 'nonce' );

		$city_id = isset( $_GET['city_id'] ) ? intval( $_GET['city_id'] ) : 0;
		if ( ! $city_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid City ID', 'adventure-treks' ) ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'at_pricing';

		// Get city default pricing (where date_id = 0).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$pricing = $wpdb->get_row(
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->prepare( "SELECT * FROM $table_name WHERE city_id = %d AND date_id = 0", $city_id ),
			ARRAY_A
		);

		if ( ! $pricing ) {
			// If not configured, load parent base prices from departure cities table.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$city = $wpdb->get_row(
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$wpdb->prepare( "SELECT base_price, offer_price, trek_id FROM {$wpdb->prefix}at_departure_cities WHERE id = %d", $city_id )
			);

			$pricing = array(
				'id'              => 0,
				'city_id'         => $city_id,
				'date_id'         => 0,
				'trek_id'         => $city ? intval( $city->trek_id ) : 0,
				'adult_price'     => $city ? floatval( $city->base_price ) : 0.00,
				'child_price'     => 0.00,
				'offer_price'     => $city ? floatval( $city->offer_price ) : 0.00,
				'group_discount'  => '[]',
				'extra_charges'   => '[]',
				'optional_addons' => '[]',
			);
		} else {
			$pricing['id']              = intval( $pricing['id'] );
			$pricing['adult_price']     = floatval( $pricing['adult_price'] );
			$pricing['child_price']     = floatval( $pricing['child_price'] );
			$pricing['offer_price']     = floatval( $pricing['offer_price'] );
			$pricing['group_discount']  = ! empty( $pricing['group_discount'] ) ? json_decode( $pricing['group_discount'], true ) : array();
			$pricing['extra_charges']   = ! empty( $pricing['extra_charges'] ) ? json_decode( $pricing['extra_charges'], true ) : array();
			$pricing['optional_addons'] = ! empty( $pricing['optional_addons'] ) ? json_decode( $pricing['optional_addons'], true ) : array();
		}

		wp_send_json_success( $pricing );
	}

	/**
	 * AJAX: Save pricing config for a city.
	 */
	public function ajax_save_pricing() {
		check_ajax_referer( 'at_pricing_nonce_action', 'nonce' );

		$city_id = isset( $_POST['city_id'] ) ? intval( wp_unslash( $_POST['city_id'] ) ) : 0;
		if ( ! $city_id ) {
			wp_send_json_error( array( 'message' => __( 'Missing required IDs', 'adventure-treks' ) ) );
		}

		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$trek_id = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT trek_id FROM {$wpdb->prefix}at_departure_cities WHERE id = %d", $city_id )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery
		if ( ! $trek_id || ! current_user_can( 'edit_post', $trek_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'adventure-treks' ) ) );
		}

		$adult_price = isset( $_POST['adult_price'] ) ? floatval( wp_unslash( $_POST['adult_price'] ) ) : 0.00;
		$child_price = isset( $_POST['child_price'] ) ? floatval( wp_unslash( $_POST['child_price'] ) ) : 0.00;
		$offer_price = isset( $_POST['offer_price'] ) ? floatval( wp_unslash( $_POST['offer_price'] ) ) : 0.00;

		// Repeaters.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$group_discount_input = isset( $_POST['group_discount'] ) ? wp_unslash( $_POST['group_discount'] ) : array();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$extra_charges_input = isset( $_POST['extra_charges'] ) ? wp_unslash( $_POST['extra_charges'] ) : array();
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$optional_addons_input = isset( $_POST['optional_addons'] ) ? wp_unslash( $_POST['optional_addons'] ) : array();

		// Sanitize Group Discounts.
		$group_discount_data = array();
		if ( is_array( $group_discount_input ) ) {
			foreach ( $group_discount_input as $rule ) {
				if ( intval( $rule['min_seats'] ) > 0 && floatval( $rule['value'] ) > 0 ) {
					$group_discount_data[] = array(
						'min_seats' => intval( $rule['min_seats'] ),
						'type'      => sanitize_text_field( $rule['type'] ), // flat, percent.
						'value'     => floatval( $rule['value'] ),
					);
				}
			}
		}

		// Sanitize Extra Charges.
		$extra_charges_data = array();
		if ( is_array( $extra_charges_input ) ) {
			foreach ( $extra_charges_input as $charge ) {
				if ( ! empty( $charge['name'] ) && floatval( $charge['price'] ) > 0 ) {
					$extra_charges_data[] = array(
						'name'  => sanitize_text_field( $charge['name'] ),
						'price' => floatval( $charge['price'] ),
						'type'  => sanitize_text_field( $charge['type'] ), // person, flat.
					);
				}
			}
		}

		// Sanitize Optional Add-ons.
		$optional_addons_data = array();
		if ( is_array( $optional_addons_input ) ) {
			foreach ( $optional_addons_input as $addon ) {
				if ( ! empty( $addon['name'] ) && floatval( $addon['price'] ) > 0 ) {
					$optional_addons_data[] = array(
						'name'  => sanitize_text_field( $addon['name'] ),
						'price' => floatval( $addon['price'] ),
						'desc'  => sanitize_text_field( $addon['desc'] ),
						'type'  => sanitize_text_field( $addon['type'] ), // person, flat.
					);
				}
			}
		}

		$table_name = $wpdb->prefix . 'at_pricing';

		// Check if record exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$exists = $wpdb->get_var(
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->prepare( "SELECT id FROM $table_name WHERE city_id = %d AND date_id = 0", $city_id )
		);

		$db_data = array(
			'city_id'         => $city_id,
			'date_id'         => 0, // Default city pricing.
			'trek_id'         => $trek_id,
			'adult_price'     => $adult_price,
			'child_price'     => $child_price,
			'offer_price'     => $offer_price,
			'group_discount'  => wp_json_encode( $group_discount_data ),
			'extra_charges'   => wp_json_encode( $extra_charges_data ),
			'optional_addons' => wp_json_encode( $optional_addons_data ),
		);

		if ( $exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->update(
				$table_name,
				$db_data,
				array( 'id' => $exists ),
				array( '%d', '%d', '%d', '%f', '%f', '%f', '%s', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->insert(
				$table_name,
				$db_data,
				array( '%d', '%d', '%d', '%f', '%f', '%f', '%s', '%s', '%s' )
			);
		}

		// Sync prices to the main Departure Cities table to ensure cache indexes remain aligned.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$wpdb->update(
			$wpdb->prefix . 'at_departure_cities',
			array(
				'base_price'  => $adult_price,
				'offer_price' => $offer_price,
			),
			array( 'id' => $city_id ),
			array( '%f', '%f' ),
			array( '%d' )
		);

		wp_send_json_success( array( 'message' => __( 'City pricing rules saved successfully!', 'adventure-treks' ) ) );
	}
}
