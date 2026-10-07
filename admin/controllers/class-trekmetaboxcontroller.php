<?php
/**
 * Controller for managing Trek CPT Meta Box UI and saving custom DB records.
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
 * TrekMetaBoxController class.
 */
class TrekMetaBoxController {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
		add_action( 'save_post_trekpilot_trek', array( $this, 'save_trek_details' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'render_age_notice' ) );
	}

	/**
	 * Parse an Age Limit label such as "10+ years" or "10 - 55 Years" into min / max ages.
	 *
	 * @param string $age_limit Age limit text.
	 * @return array{min:int|null,max:int|null}
	 */
	public static function parse_age_limit( $age_limit ) {
		preg_match_all( '/\d+/', (string) $age_limit, $matches );
		$numbers = array_map( 'intval', $matches[0] );

		return array(
			'min' => isset( $numbers[0] ) ? $numbers[0] : null,
			'max' => isset( $numbers[1] ) ? $numbers[1] : null,
		);
	}

	/**
	 * Read a short spec field from the request as a plain label.
	 *
	 * Letters, numbers, spaces and / - , . only (plus "+" for age fields such as "10+ years"); anything else is stripped.
	 *
	 * @param string $key        POST key.
	 * @param bool   $allow_plus Whether "+" is allowed.
	 * @return string
	 */
	private static function plain_text_field( $key, $allow_plus = false ) {
		$value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified by the caller.
		$regex = $allow_plus ? '/[^\p{L}\p{N}\s\/\-,.+]/u' : '/[^\p{L}\p{N}\s\/\-,.]/u';
		return trim( (string) preg_replace( $regex, '', $value ) );
	}

	/**
	 * Validate the Adults / Children age labels against the trek's Age Limit.
	 *
	 * Adults: "12" or "12+", never below the minimum age (nor above the maximum).
	 * Children: a range "a-b", starting at or above the minimum age and ending before the adults age.
	 * Blank values are valid (built-in defaults apply).
	 *
	 * @param string $age_limit Age limit text.
	 * @param string $adult_age Adults age label.
	 * @param string $child_age Children age label.
	 * @return array{adult:string,child:string} Error message per field, empty string when valid.
	 */
	public static function validate_ages( $age_limit, $adult_age, $child_age ) {
		$limit  = self::parse_age_limit( $age_limit );
		$errors = array(
			'adult' => '',
			'child' => '',
		);
		$adult  = null;

		$adult_age = trim( (string) $adult_age );
		if ( '' !== $adult_age ) {
			if ( ! preg_match( '/^(\d{1,2})\+?$/', $adult_age, $m ) ) {
				$errors['adult'] = __( 'Adults age must be a number, optionally followed by +, e.g. 12+.', 'trekpilot' );
			} else {
				$adult = (int) $m[1];
				if ( null !== $limit['min'] && $adult < $limit['min'] ) {
					/* translators: 1: entered adults age, 2: trek minimum age. */
					$errors['adult'] = sprintf( __( 'Adults age (%1$d) cannot be below the trek minimum age (%2$d).', 'trekpilot' ), $adult, $limit['min'] );
				} elseif ( null !== $limit['max'] && $adult > $limit['max'] ) {
					/* translators: 1: entered adults age, 2: trek maximum age. */
					$errors['adult'] = sprintf( __( 'Adults age (%1$d) cannot be above the trek maximum age (%2$d).', 'trekpilot' ), $adult, $limit['max'] );
				}
			}
		}

		$child_age = trim( (string) $child_age );
		if ( '' !== $child_age ) {
			if ( ! preg_match( '/^(\d{1,2})\s*-\s*(\d{1,2})$/', $child_age, $m ) ) {
				$errors['child'] = __( 'Children age must be a range, e.g. 10-11.', 'trekpilot' );
			} else {
				$from = (int) $m[1];
				$to   = (int) $m[2];
				if ( $from > $to ) {
					$errors['child'] = __( 'Children age range must go from the lower age to the higher age, e.g. 10-11.', 'trekpilot' );
				} elseif ( null !== $limit['min'] && $from < $limit['min'] ) {
					/* translators: 1: children range start, 2: trek minimum age. */
					$errors['child'] = sprintf( __( 'Children age cannot start at %1$d, the trek minimum age is %2$d.', 'trekpilot' ), $from, $limit['min'] );
				} elseif ( null !== $adult && $to >= $adult ) {
					/* translators: %d: adults age. */
					$errors['child'] = sprintf( __( 'Children age range must end before the adults age (%d).', 'trekpilot' ), $adult );
				}
			}
		}

		return $errors;
	}

	/**
	 * Default Adults / Children age labels that respect the trek's minimum age.
	 *
	 * @param string $age_limit Age limit text.
	 * @return array{adult:string,child:string} Child is empty when there is no room for a children range.
	 */
	public static function default_ages( $age_limit ) {
		$min   = self::parse_age_limit( $age_limit )['min'];
		$adult = max( 12, (int) $min );
		$from  = max( 5, (int) $min );
		$to    = $adult - 1;

		return array(
			'adult' => $adult . '+',
			'child' => $from <= $to ? $from . '-' . $to : '',
		);
	}

	/**
	 * Show the age validation message saved by the last trek update.
	 *
	 * @return void
	 */
	public function render_age_notice() {
		$key     = 'trekpilot_age_notice_' . get_current_user_id();
		$message = get_transient( $key );
		if ( ! $message ) {
			return;
		}
		delete_transient( $key );
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Register the meta box.
	 *
	 * @return void
	 */
	public function register_meta_box() {
		add_meta_box(
			'trekpilot_trek_details_meta_box',
			__( 'Trek Settings & Detailed Specifications', 'trekpilot' ),
			array( $this, 'render_meta_box' ),
			'trekpilot_trek',
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

		if ( 'trekpilot_trek' !== $post_type ) {
			return;
		}

		// Enqueue WordPress Media Library uploader scripts.
		wp_enqueue_media();

		// Enqueue custom CSS and JS.
		wp_enqueue_style(
			'trekpilot-admin-meta-box-css',
			TREKPILOT_URL . 'assets/admin/css/admin-meta-box.css',
			array(),
			\TrekPilot\Includes\Plugin::asset_version( 'assets/admin/css/admin-meta-box.css' )
		);

		wp_enqueue_script(
			'trekpilot-admin-meta-box-js',
			TREKPILOT_URL . 'assets/admin/js/admin-meta-box.js',
			array(),
			TREKPILOT_VERSION . '.' . filemtime( TREKPILOT_PATH . 'assets/admin/js/admin-meta-box.js' ),
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
		wp_nonce_field( 'save_trekpilot_trek_details', 'trekpilot_trek_details_nonce' );

		// Query custom DB record.
		$table_name = $wpdb->prefix . 'trekpilot_treks';
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

		// Columns such as highlights and exclusions are nullable; a NULL must never reach esc_html() or
		// esc_textarea() (PHP 8.1+ deprecation notice printed into the page).
		$trek = array_map(
			static function ( $value ) {
				return null === $value ? '' : $value;
			},
			$trek
		);

		// Parse JSON fields to associative arrays for form use.
		$faq_items = ! empty( $trek['faq'] ) ? json_decode( $trek['faq'], true ) : array();
		if ( ! is_array( $faq_items ) ) {
			$faq_items = array();
		}

		$policies_decoded = ! empty( $trek['policies'] ) ? json_decode( $trek['policies'], true ) : array();
		$policies         = array_merge(
			array_fill_keys( array_keys( self::get_policy_types() ), '' ),
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
		if ( ! isset( $_POST['trekpilot_trek_details_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['trekpilot_trek_details_nonce'] ) ), 'save_trekpilot_trek_details' ) ) {
			return $post_id;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'trekpilot_treks';

		// Sanitize standard text inputs.
		// Short spec fields are plain labels (e.g. "5 Days / 4 Nights"): letters, numbers, spaces and / - , . only
		// (plus "+" for the age limit, as in "10+ years"). Anything else, such as !@#$%^&*()=, is stripped.
		$difficulty    = isset( $_POST['trekpilot_difficulty'] ) ? sanitize_text_field( wp_unslash( $_POST['trekpilot_difficulty'] ) ) : '';
		$duration      = self::plain_text_field( 'trekpilot_duration' );
		$altitude      = self::plain_text_field( 'trekpilot_altitude' );
		$region        = self::plain_text_field( 'trekpilot_region' );
		$season        = self::plain_text_field( 'trekpilot_season' );
		$distance      = self::plain_text_field( 'trekpilot_distance' );
		$fitness_level = isset( $_POST['trekpilot_fitness_level'] ) ? sanitize_text_field( wp_unslash( $_POST['trekpilot_fitness_level'] ) ) : '';
		$age_limit     = self::plain_text_field( 'trekpilot_age_limit', true );
		$group_size    = self::plain_text_field( 'trekpilot_group_size' );

		// Age labels shown next to Adults / Children in the booking widget (blank = built-in defaults).
		$adult_age   = self::plain_text_field( 'trekpilot_adult_age', true );
		$child_age   = self::plain_text_field( 'trekpilot_child_age', true );
		$age_errors  = self::validate_ages( $age_limit, $adult_age, $child_age );
		$age_message = array();
		if ( '' !== $age_errors['adult'] ) {
			$adult_age     = '';
			$age_message[] = $age_errors['adult'];
		}
		if ( '' !== $age_errors['child'] ) {
			$child_age     = '';
			$age_message[] = $age_errors['child'];
		}
		if ( ! empty( $age_message ) ) {
			set_transient( 'trekpilot_age_notice_' . get_current_user_id(), __( 'Some age settings were not saved:', 'trekpilot' ) . ' ' . implode( ' ', $age_message ), 60 );
		}
		update_post_meta( $post_id, '_trekpilot_adult_age', $adult_age );
		update_post_meta( $post_id, '_trekpilot_child_age', $child_age );

		// Highlights, exclusions and carry list (saved as newline separated in form, serialized/processed cleanly).
		$highlights      = isset( $_POST['trekpilot_highlights'] ) ? sanitize_textarea_field( wp_unslash( $_POST['trekpilot_highlights'] ) ) : '';
		$exclusions      = isset( $_POST['trekpilot_exclusions'] ) ? sanitize_textarea_field( wp_unslash( $_POST['trekpilot_exclusions'] ) ) : '';
		$things_to_carry = isset( $_POST['trekpilot_things_to_carry'] ) ? sanitize_textarea_field( wp_unslash( $_POST['trekpilot_things_to_carry'] ) ) : '';
		$gallery         = isset( $_POST['trekpilot_gallery'] ) ? sanitize_text_field( wp_unslash( $_POST['trekpilot_gallery'] ) ) : '';

		// Sanitize FAQ array.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$faq_input = isset( $_POST['trekpilot_faq'] ) ? wp_unslash( $_POST['trekpilot_faq'] ) : array();
		$faq_data  = array();
		if ( is_array( $faq_input ) ) {
			foreach ( $faq_input as $item ) {
				$question = isset( $item['q'] ) ? sanitize_text_field( $item['q'] ) : '';
				$answer   = isset( $item['a'] ) ? sanitize_textarea_field( $item['a'] ) : '';
				if ( '' === $question && '' === $answer ) {
					continue;
				}
				// An FAQ needs both a question and an answer.
				if ( '' === trim( $question ) || '' === trim( $answer ) ) {
					continue;
				}
				$faq_data[] = array(
					'q' => $question,
					'a' => $answer,
				);
			}
		}
		$faq = ! empty( $faq_data ) ? wp_json_encode( $faq_data ) : '';

		// Sanitize Policies.
		$policies_data = array();
		foreach ( self::get_policy_types() as $policy_key => $policy_type ) {
			$field                        = 'trekpilot_policy_' . $policy_key;
			$policies_data[ $policy_key ] = isset( $_POST[ $field ] ) ? wp_kses_post( wp_unslash( $_POST[ $field ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified by the caller.
		}
		$policies = wp_json_encode( $policies_data );

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

	/**
	 * The policies a trek can have, in display order.
	 *
	 * @return array[] Policy key => array( label, description ).
	 */
	public static function get_policy_types() {
		return array(
			'cancellation' => array(
				'label' => __( 'Cancellation Policy', 'trekpilot' ),
				'icon'  => 'dashicons-warning',
			),
			'refund'       => array(
				'label' => __( 'Refund Policy', 'trekpilot' ),
				'icon'  => 'dashicons-money-alt',
			),
			'medical'      => array(
				'label' => __( 'Medical Disclaimer', 'trekpilot' ),
				'icon'  => 'dashicons-heart',
			),
			'terms'        => array(
				'label' => __( 'Terms & Conditions', 'trekpilot' ),
				'icon'  => 'dashicons-media-text',
			),
		);
	}
}
