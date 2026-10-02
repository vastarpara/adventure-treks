<?php
/**
 * Controller for managing Trek CPT Meta Box UI and saving custom DB records.
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
 * TrekMetaBoxController class.
 */
class TrekMetaBoxController {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
		add_action( 'save_post_adventure_trek', array( $this, 'save_trek_details' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register the meta box.
	 *
	 * @return void
	 */
	public function register_meta_box() {
		add_meta_box(
			'at_trek_details_meta_box',
			__( 'Trek Settings & Detailed Specifications', 'adventure-treks' ),
			array( $this, 'render_meta_box' ),
			'adventure_trek',
			'normal',
			'high'
		);
	}

	/**
	 * Enqueue stylesheet and JS for meta box.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		global $post_type;

		if ( 'adventure_trek' !== $post_type ) {
			return;
		}

		// Enqueue WordPress Media Library uploader scripts.
		wp_enqueue_media();

		// Enqueue custom CSS and JS.
		wp_enqueue_style(
			'at-admin-meta-box-css',
			ADVENTURE_TREKS_URL . 'assets/admin/css/admin-meta-box.css',
			array(),
			ADVENTURE_TREKS_VERSION
		);

		wp_enqueue_script(
			'at-admin-meta-box-js',
			ADVENTURE_TREKS_URL . 'assets/admin/js/admin-meta-box.js',
			array(),
			ADVENTURE_TREKS_VERSION . '.' . filemtime( ADVENTURE_TREKS_PATH . 'assets/admin/js/admin-meta-box.js' ),
			true
		);
	}

	/**
	 * Render meta box HTML interface.
	 *
	 * @param \WP_Post $post Current post object.
	 * @return void
	 */
	public function render_meta_box( $post ) {
		global $wpdb;

		// Add security nonce.
		wp_nonce_field( 'save_at_trek_details', 'at_trek_details_nonce' );

		// Query custom DB record.
		$table_name = $wpdb->prefix . 'at_treks';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$trek = $wpdb->get_row(
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->prepare( "SELECT * FROM $table_name WHERE post_id = %d", $post->ID ),
			ARRAY_A
		);

		// If no record exists, define defaults.
		if ( ! $trek ) {
			$trek = array(
				'difficulty'      => '',
				'duration'        => '',
				'altitude'        => '',
				'region'          => '',
				'season'          => '',
				'distance'        => '',
				'fitness_level'   => '',
				'age_limit'       => '',
				'group_size'      => '',
				'highlights'      => '',
				'exclusions'      => '',
				'things_to_carry' => '',
				'faq'             => '',
				'policies'        => '',
				'gallery'         => '',
			);
		}

		// Parse JSON fields to associative arrays for form use.
		$faq_items = ! empty( $trek['faq'] ) ? json_decode( $trek['faq'], true ) : array();
		if ( ! is_array( $faq_items ) ) {
			$faq_items = array();
		}

		$policies_decoded = ! empty( $trek['policies'] ) ? json_decode( $trek['policies'], true ) : array();
		$policies         = array_merge(
			array(
				'cancellation' => '',
				'terms'        => '',
			),
			is_array( $policies_decoded ) ? $policies_decoded : array()
		);

		// Include UI View.
		$view_path = plugin_dir_path( __DIR__ ) . 'views/trek-meta-box.php';
		if ( file_exists( $view_path ) ) {
			include $view_path;
		}
	}

	/**
	 * Save trek data into the custom DB table.
	 *
	 * @param int $post_id The CPT post ID.
	 * @return int
	 */
	public function save_trek_details( $post_id ) {
		// Verify autosave or revision.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Check nonce.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! isset( $_POST['at_trek_details_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['at_trek_details_nonce'] ), 'save_at_trek_details' ) ) {
			return $post_id;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'at_treks';

		// Sanitize standard text inputs.
		// Short spec fields are plain labels (e.g. "5 Days / 4 Nights"): letters, numbers, spaces and / - , . only
		// (plus "+" for the age limit, as in "10+ years"). Anything else, such as !@#$%^&*()=, is stripped.
		$at_plain = static function ( $key, $allow_plus = false ) {
			$value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified by the caller.
			$regex = $allow_plus ? '/[^\p{L}\p{N}\s\/\-,.+]/u' : '/[^\p{L}\p{N}\s\/\-,.]/u';
			return trim( (string) preg_replace( $regex, '', $value ) );
		};
		$difficulty    = isset( $_POST['at_difficulty'] ) ? sanitize_text_field( wp_unslash( $_POST['at_difficulty'] ) ) : '';
		$duration      = $at_plain( 'at_duration' );
		$altitude      = $at_plain( 'at_altitude' );
		$region        = $at_plain( 'at_region' );
		$season        = $at_plain( 'at_season' );
		$distance      = $at_plain( 'at_distance' );
		$fitness_level = isset( $_POST['at_fitness_level'] ) ? sanitize_text_field( wp_unslash( $_POST['at_fitness_level'] ) ) : '';
		$age_limit     = $at_plain( 'at_age_limit', true );
		$group_size    = $at_plain( 'at_group_size' );

		// Highlights, exclusions and carry list (saved as newline separated in form, serialized/processed cleanly).
		$highlights      = isset( $_POST['at_highlights'] ) ? sanitize_textarea_field( wp_unslash( $_POST['at_highlights'] ) ) : '';
		$exclusions      = isset( $_POST['at_exclusions'] ) ? sanitize_textarea_field( wp_unslash( $_POST['at_exclusions'] ) ) : '';
		$things_to_carry = isset( $_POST['at_things_to_carry'] ) ? sanitize_textarea_field( wp_unslash( $_POST['at_things_to_carry'] ) ) : '';
		$gallery         = isset( $_POST['at_gallery'] ) ? sanitize_text_field( wp_unslash( $_POST['at_gallery'] ) ) : '';

		// Sanitize FAQ array.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$faq_input = isset( $_POST['at_faq'] ) ? wp_unslash( $_POST['at_faq'] ) : array();
		$faq_data  = array();
		if ( is_array( $faq_input ) ) {
			foreach ( $faq_input as $item ) {
				if ( ! empty( $item['q'] ) || ! empty( $item['a'] ) ) {
					$faq_data[] = array(
						'q' => sanitize_text_field( $item['q'] ),
						'a' => sanitize_textarea_field( $item['a'] ),
					);
				}
			}
		}
		$faq = ! empty( $faq_data ) ? wp_json_encode( $faq_data ) : '';

		// Sanitize Policies.
		$policies_data = array(
			'cancellation' => isset( $_POST['at_policy_cancellation'] ) ? wp_kses_post( wp_unslash( $_POST['at_policy_cancellation'] ) ) : '',
			'terms'        => isset( $_POST['at_policy_terms'] ) ? wp_kses_post( wp_unslash( $_POST['at_policy_terms'] ) ) : '',
		);
		$policies      = wp_json_encode( $policies_data );

		// Check if record exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE post_id = %d", $post_id ) );

		$db_data = array(
			'post_id'         => $post_id,
			'difficulty'      => $difficulty,
			'duration'        => $duration,
			'altitude'        => $altitude,
			'region'          => $region,
			'season'          => $season,
			'distance'        => $distance,
			'fitness_level'   => $fitness_level,
			'age_limit'       => $age_limit,
			'group_size'      => $group_size,
			'highlights'      => $highlights,
			'exclusions'      => $exclusions,
			'things_to_carry' => $things_to_carry,
			'faq'             => $faq,
			'policies'        => $policies,
			'gallery'         => $gallery,
		);

		if ( $exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->update(
				$table_name,
				$db_data,
				array( 'post_id' => $post_id ),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->insert(
				$table_name,
				$db_data,
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
			);
		}

		return $post_id;
	}
}
