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
		add_action( 'save_post_adventure_trek', array( $this, 'save_trek_details' ), 10, 2 );
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
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
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
			ADVENTURE_TREKS_VERSION,
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
		$trek = $wpdb->get_row(
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
		$policies = array_merge( array(
			'cancellation' => '',
			'refund'       => '',
			'medical'      => '',
			'terms'        => '',
		), is_array( $policies_decoded ) ? $policies_decoded : array() );



		// Include UI View.
		$view_path = plugin_dir_path( dirname( __FILE__ ) ) . 'Views/trek-meta-box.php';
		if ( file_exists( $view_path ) ) {
			include $view_path;
		}
	}

	/**
	 * Save trek data into the custom DB table.
	 *
	 * @param int      $post_id The CPT post ID.
	 * @param \WP_Post $post    The CPT post object.
	 * @return int
	 */
	public function save_trek_details( $post_id, $post ) {
		// Verify autosave or revision.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Check nonce.
		if ( ! isset( $_POST['at_trek_details_nonce'] ) || ! wp_verify_nonce( $_POST['at_trek_details_nonce'], 'save_at_trek_details' ) ) {
			return $post_id;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'at_treks';

		// Sanitize standard text inputs.
		$difficulty    = isset( $_POST['at_difficulty'] ) ? sanitize_text_field( $_POST['at_difficulty'] ) : '';
		$duration      = isset( $_POST['at_duration'] ) ? sanitize_text_field( $_POST['at_duration'] ) : '';
		$altitude      = isset( $_POST['at_altitude'] ) ? sanitize_text_field( $_POST['at_altitude'] ) : '';
		$region        = isset( $_POST['at_region'] ) ? sanitize_text_field( $_POST['at_region'] ) : '';
		$season        = isset( $_POST['at_season'] ) ? sanitize_text_field( $_POST['at_season'] ) : '';
		$distance      = isset( $_POST['at_distance'] ) ? sanitize_text_field( $_POST['at_distance'] ) : '';
		$fitness_level = isset( $_POST['at_fitness_level'] ) ? sanitize_text_field( $_POST['at_fitness_level'] ) : '';
		$age_limit     = isset( $_POST['at_age_limit'] ) ? sanitize_text_field( $_POST['at_age_limit'] ) : '';
		$group_size    = isset( $_POST['at_group_size'] ) ? sanitize_text_field( $_POST['at_group_size'] ) : '';

		// Highlights and carry list (saved as newline separated in form, serialized/processed cleanly).
		$highlights      = isset( $_POST['at_highlights'] ) ? sanitize_textarea_field( $_POST['at_highlights'] ) : '';
		$things_to_carry = isset( $_POST['at_things_to_carry'] ) ? sanitize_textarea_field( $_POST['at_things_to_carry'] ) : '';
		$gallery         = isset( $_POST['at_gallery'] ) ? sanitize_text_field( $_POST['at_gallery'] ) : '';

		// Sanitize FAQ array.
		$faq_input = isset( $_POST['at_faq'] ) ? $_POST['at_faq'] : array();
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
			'cancellation' => isset( $_POST['at_policy_cancellation'] ) ? wp_kses_post( $_POST['at_policy_cancellation'] ) : '',
			'refund'       => isset( $_POST['at_policy_refund'] ) ? wp_kses_post( $_POST['at_policy_refund'] ) : '',
			'medical'      => isset( $_POST['at_policy_medical'] ) ? wp_kses_post( $_POST['at_policy_medical'] ) : '',
			'terms'        => isset( $_POST['at_policy_terms'] ) ? wp_kses_post( $_POST['at_policy_terms'] ) : '',
		);
		$policies = wp_json_encode( $policies_data );

		// Check if record exists.
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
			'things_to_carry' => $things_to_carry,
			'faq'             => $faq,
			'policies'        => $policies,
			'gallery'         => $gallery,
		);

		if ( $exists ) {
			$wpdb->update(
				$table_name,
				$db_data,
				array( 'post_id' => $post_id ),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			$wpdb->insert(
				$table_name,
				$db_data,
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
			);
		}

		return $post_id;
	}
}
