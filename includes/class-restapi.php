<?php
/**
 * REST API Endpoints registration and controller.
 *
 * @package    TrekPilot
 * @subpackage TrekPilot/Includes
 * @author     Nilesh Vastarpara
 */

namespace TrekPilot\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * RestApi class.
 */
class RestApi {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes() {
		$namespace = 'trekpilot/v1';

		// Route: List all treks.
		register_rest_route(
			$namespace,
			'/treks',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_treks' ),
				'permission_callback' => '__return_true',
			)
		);

		// Route: Single trek details.
		register_rest_route(
			$namespace,
			'/treks/(?P<id>\d+)',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_single_trek' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);

		// Route: Departure cities for a specific trek.
		register_rest_route(
			$namespace,
			'/treks/(?P<id>\d+)/cities',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_trek_cities' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'id' => array(
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);

		// Route: Dates and availability for a city.
		register_rest_route(
			$namespace,
			'/cities/(?P<city_id>\d+)/dates',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_city_dates' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'city_id' => array(
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);
	}

	/**
	 * Whether the given post is a published, non-password-protected trek.
	 *
	 * @param int $post_id Trek post ID.
	 * @return bool
	 */
	private function is_public_trek( $post_id ) {
		return 'trekpilot_trek' === get_post_type( $post_id )
			&& 'publish' === get_post_status( $post_id )
			&& ! post_password_required( $post_id );
	}

	/**
	 * GET: Retrieve list of treks with custom specifications.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_treks() {
		global $wpdb;

		$posts = get_posts(
			array(
				'post_type'      => 'trekpilot_trek',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
			)
		);

		$table_name    = $wpdb->prefix . 'trekpilot_treks';
		$response_data = array();

		// Fetch every trek's details in one query instead of one per trek.
		$specs_by_post = array();
		$post_ids      = wp_list_pluck( $posts, 'ID' );
		if ( ! empty( $post_ids ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$spec_rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE post_id IN ( $placeholders )", $post_ids ), ARRAY_A );
			foreach ( (array) $spec_rows as $spec_row ) {
				$specs_by_post[ (int) $spec_row['post_id'] ] = $spec_row;
			}
		}

		foreach ( $posts as $post ) {
			$post_id = $post->ID;
			$specs   = isset( $specs_by_post[ $post_id ] ) ? $specs_by_post[ $post_id ] : null;

			$response_data[] = array(
				'id'            => $post_id,
				'title'         => $post->post_title,
				'slug'          => $post->post_name,
				'difficulty'    => $specs ? $specs['difficulty'] : '',
				'duration'      => $specs ? $specs['duration'] : '',
				'altitude'      => $specs ? $specs['altitude'] : '',
				'region'        => $specs ? $specs['region'] : '',
				'season'        => $specs ? $specs['season'] : '',
				'distance'      => $specs ? $specs['distance'] : '',
				'fitness_level' => $specs ? $specs['fitness_level'] : '',
				'age_limit'     => $specs ? $specs['age_limit'] : '',
				'group_size'    => $specs ? $specs['group_size'] : '',
			);
		}

		return rest_ensure_response( $response_data );
	}

	/**
	 * GET: Retrieve details of a single trek.
	 *
	 * @param \WP_REST_Request $request The REST request, expects the trek post ID in the 'id' URL param.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_single_trek( $request ) {
		$post_id = intval( $request['id'] );

		if ( ! $this->is_public_trek( $post_id ) ) {
			return new \WP_Error( 'rest_invalid_trek', esc_html__( 'Invalid trek ID', 'trekpilot' ), array( 'status' => 404 ) );
		}

		global $wpdb;
		$table_treks = $wpdb->prefix . 'trekpilot_treks';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$specs = $wpdb->get_row(
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->prepare( "SELECT * FROM $table_treks WHERE post_id = %d", $post_id ),
			ARRAY_A
		);

		if ( ! $specs ) {
			return new \WP_Error( 'rest_no_specs', esc_html__( 'Specifications not configured for this trek', 'trekpilot' ), array( 'status' => 404 ) );
		}

		$post = get_post( $post_id );

		$response_data = array(
			'id'              => $post_id,
			'title'           => $post->post_title,
			'content'         => $post->post_content,
			'difficulty'      => $specs['difficulty'],
			'duration'        => $specs['duration'],
			'altitude'        => $specs['altitude'],
			'region'          => $specs['region'],
			'season'          => $specs['season'],
			'distance'        => $specs['distance'],
			'fitness_level'   => $specs['fitness_level'],
			'age_limit'       => $specs['age_limit'],
			'group_size'      => $specs['group_size'],
			'highlights'      => array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", '', $specs['highlights'] ) ) ) ),
			'things_to_carry' => array_filter( array_map( 'trim', explode( "\n", str_replace( "\r", '', $specs['things_to_carry'] ) ) ) ),
			'faq'             => ! empty( $specs['faq'] ) ? json_decode( $specs['faq'], true ) : array(),
			'policies'        => ! empty( $specs['policies'] ) ? json_decode( $specs['policies'], true ) : array(),
			'gallery'         => array(),
		);

		// Populate gallery URLs.
		if ( ! empty( $specs['gallery'] ) ) {
			$gallery_ids = explode( ',', $specs['gallery'] );
			foreach ( $gallery_ids as $attachment_id ) {
				$url = wp_get_attachment_url( intval( $attachment_id ) );
				if ( $url ) {
					$response_data['gallery'][] = array(
						'id'  => intval( $attachment_id ),
						'url' => $url,
					);
				}
			}
		}

		return rest_ensure_response( $response_data );
	}

	/**
	 * GET: Retrieve departure cities for a specific trek.
	 *
	 * @param \WP_REST_Request $request The REST request, expects the trek post ID in the 'id' URL param.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_trek_cities( $request ) {
		$post_id = intval( $request['id'] );

		if ( ! $this->is_public_trek( $post_id ) ) {
			return new \WP_Error( 'rest_invalid_trek', esc_html__( 'Invalid trek ID', 'trekpilot' ), array( 'status' => 404 ) );
		}

		global $wpdb;
		$table_cities = $wpdb->prefix . 'trekpilot_departure_cities';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$cities = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->prepare( "SELECT * FROM $table_cities WHERE trek_id = %d AND status = 'active' ORDER BY menu_order ASC", $post_id ),
			ARRAY_A
		);

		return rest_ensure_response( $cities );
	}

	/**
	 * GET: Retrieve scheduled dates and available seats for a departure city.
	 *
	 * @param \WP_REST_Request $request The REST request, expects the departure city ID in the 'city_id' URL param.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_city_dates( $request ) {
		$city_id = intval( $request['city_id'] );

		global $wpdb;
		$table_cities = $wpdb->prefix . 'trekpilot_departure_cities';

		// Verify city exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$city_trek_id = $wpdb->get_var( $wpdb->prepare( "SELECT trek_id FROM $table_cities WHERE id = %d AND status = 'active'", $city_id ) );
		if ( ! $city_trek_id || ! $this->is_public_trek( (int) $city_trek_id ) ) {
			return new \WP_Error( 'rest_invalid_city', esc_html__( 'Invalid city ID', 'trekpilot' ), array( 'status' => 404 ) );
		}

		$table_dates = $wpdb->prefix . 'trekpilot_departure_dates';
		$table_avail = $wpdb->prefix . 'trekpilot_availability';

		// phpcs:disable WordPress.DB.PreparedSQL
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$dates = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT d.id, d.departure_date, d.status, d.notes, a.total_seats, a.booked_seats, a.available_seats 
				 FROM ' . $table_dates . ' d
				 LEFT JOIN ' . $table_avail . " a ON d.id = a.date_id
				 WHERE d.city_id = %d AND d.status != 'cancelled' AND d.departure_date >= CURDATE()
				 ORDER BY d.departure_date ASC",
				$city_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL

		// Format integer values.
		foreach ( $dates as &$date ) {
			$date['id']              = intval( $date['id'] );
			$date['total_seats']     = intval( $date['total_seats'] );
			$date['booked_seats']    = intval( $date['booked_seats'] );
			$date['available_seats'] = intval( $date['available_seats'] );
		}

		return rest_ensure_response( $dates );
	}
}
