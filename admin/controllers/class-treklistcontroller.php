<?php
/**
 * Extra columns for the Treks list in wp-admin (All Treks).
 *
 * Shows, at a glance, the trek's key specs, its departure cities, starting price, next departure,
 * seats left, bookings and whether the setup is complete.
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
 * TrekListController class.
 */
class TrekListController {

	/**
	 * Per-trek figures for the rows on the current screen, loaded in one go by load_stats().
	 *
	 * @var array|null
	 */
	private $stats = null;

	/**
	 * Constructor: hook the list table.
	 */
	public function __construct() {
		add_filter( 'manage_trekpilot_trek_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_trekpilot_trek_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Insert the TrekPilot columns after the title.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['trekpilot_specs']    = __( 'Details', 'trekpilot' );
				$new['trekpilot_cities']   = __( 'Departure Cities', 'trekpilot' );
				$new['trekpilot_price']    = __( 'Starting Price', 'trekpilot' );
				$new['trekpilot_next']     = __( 'Next Departure', 'trekpilot' );
				$new['trekpilot_seats']    = __( 'Seats Left', 'trekpilot' );
				$new['trekpilot_bookings'] = __( 'Bookings', 'trekpilot' );
				$new['trekpilot_setup']    = __( 'Setup', 'trekpilot' );
			}
		}
		return $new;
	}

	/**
	 * Small stylesheet for the columns, only on the Treks list.
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public function enqueue_styles( $hook ) {
		$screen = get_current_screen();
		if ( 'edit.php' !== $hook || ! $screen || 'edit-trekpilot_trek' !== $screen->id ) {
			return;
		}

		wp_register_style( 'trekpilot-admin-trek-list', false, array(), TREKPILOT_VERSION );
		wp_enqueue_style( 'trekpilot-admin-trek-list' );
		wp_add_inline_style(
			'trekpilot-admin-trek-list',
			'.post-type-trekpilot_trek .wp-list-table{table-layout:auto}.post-type-trekpilot_trek .column-title{min-width:200px}.column-trekpilot_setup{min-width:170px}.column-trekpilot_cities{min-width:120px}.column-trekpilot_specs{min-width:130px}'
			. '.trekpilot-list-muted{color:#787c82}.trekpilot-list-pill{display:inline-block;margin:0 4px 4px 0;padding:0 8px;border-radius:10px;font-size:11px;line-height:18px;background:#f0f0f1;color:#3c434a}'
			. '.trekpilot-list-ok{background:#e6f4ea;color:#1e6b34}.trekpilot-list-warn{background:#fff4e0;color:#8a5a00}.trekpilot-list-bad{background:#fde8e8;color:#b32d2e}'
			. '.trekpilot-list-todo{display:block;margin:2px 0 0;font-size:12px;color:#8a5a00}.trekpilot-list-spec{display:block;line-height:1.5}'
		);
	}

	/**
	 * Load every figure for the posts on this page with a handful of grouped queries (no per-row queries).
	 *
	 * @return void
	 */
	private function load_stats() {
		global $wpdb, $wp_query;

		$this->stats = array();
		$ids         = array();
		if ( $wp_query && ! empty( $wp_query->posts ) ) {
			$ids = array_map( 'absint', wp_list_pluck( $wp_query->posts, 'ID' ) );
		}
		if ( empty( $ids ) ) {
			return;
		}

		$prefix       = $wpdb->prefix;
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$specs    = $wpdb->get_results( $wpdb->prepare( "SELECT post_id, difficulty, duration, region FROM {$prefix}trekpilot_treks WHERE post_id IN ($placeholders)", $ids ), OBJECT_K );
		$cities   = $wpdb->get_results( $wpdb->prepare( "SELECT id, trek_id, city_name, base_price, offer_price, status FROM {$prefix}trekpilot_departure_cities WHERE trek_id IN ($placeholders) ORDER BY menu_order ASC, id ASC", $ids ) );
		$dates    = $wpdb->get_results( $wpdb->prepare( "SELECT d.trek_id, d.city_id, MIN(d.departure_date) AS next_date, COALESCE(SUM(a.available_seats), 0) AS seats FROM {$prefix}trekpilot_departure_dates d LEFT JOIN {$prefix}trekpilot_availability a ON a.date_id = d.id WHERE d.trek_id IN ($placeholders) AND d.status != 'cancelled' AND d.departure_date >= CURDATE() GROUP BY d.trek_id, d.city_id", $ids ) );
		$pickups  = $wpdb->get_results( $wpdb->prepare( "SELECT trek_id, city_id, COUNT(*) AS n FROM {$prefix}trekpilot_pickup_points WHERE trek_id IN ($placeholders) GROUP BY trek_id, city_id", $ids ) );
		$itins    = $wpdb->get_results( $wpdb->prepare( "SELECT trek_id, city_id, COUNT(*) AS n FROM {$prefix}trekpilot_itineraries WHERE trek_id IN ($placeholders) GROUP BY trek_id, city_id", $ids ) );
		$bookings = $wpdb->get_results( $wpdb->prepare( "SELECT trek_id, COUNT(*) AS total, SUM(status = 'pending') AS pending FROM {$prefix}trekpilot_bookings WHERE trek_id IN ($placeholders) AND trashed_at IS NULL GROUP BY trek_id", $ids ), OBJECT_K );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter

		foreach ( $ids as $id ) {
			$this->stats[ $id ] = array(
				'specs'    => isset( $specs[ $id ] ) ? $specs[ $id ] : null,
				'cities'   => array(),
				'next'     => '',
				'seats'    => 0,
				'price'    => null,
				'bookings' => isset( $bookings[ $id ] ) ? $bookings[ $id ] : null,
				'dates'    => array(),
				'pickups'  => array(),
				'itins'    => array(),
			);
		}
		foreach ( $cities as $city ) {
			$trek_id                             = (int) $city->trek_id;
			$this->stats[ $trek_id ]['cities'][] = $city;
			if ( 'active' === $city->status ) {
				$price = (float) $city->offer_price > 0 ? (float) $city->offer_price : (float) $city->base_price;
				if ( $price > 0 && ( null === $this->stats[ $trek_id ]['price'] || $price < $this->stats[ $trek_id ]['price'] ) ) {
					$this->stats[ $trek_id ]['price'] = $price;
				}
			}
		}
		foreach ( $dates as $row ) {
			$trek_id = (int) $row->trek_id;
			$this->stats[ $trek_id ]['dates'][ (int) $row->city_id ] = true;
			$this->stats[ $trek_id ]['seats']                       += (int) $row->seats;
			if ( '' === $this->stats[ $trek_id ]['next'] || $row->next_date < $this->stats[ $trek_id ]['next'] ) {
				$this->stats[ $trek_id ]['next'] = $row->next_date;
			}
		}
		foreach ( $pickups as $row ) {
			$this->stats[ (int) $row->trek_id ]['pickups'][ (int) $row->city_id ] = (int) $row->n;
		}
		foreach ( $itins as $row ) {
			$this->stats[ (int) $row->trek_id ]['itins'][ (int) $row->city_id ] = (int) $row->n;
		}
	}

	/**
	 * Print one cell.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Trek post ID.
	 * @return void
	 */
	public function render_column( $column, $post_id ) {
		if ( 0 !== strpos( $column, 'trekpilot_' ) ) {
			return;
		}
		if ( null === $this->stats ) {
			$this->load_stats();
		}
		$post_id = (int) $post_id;
		$s       = isset( $this->stats[ $post_id ] ) ? $this->stats[ $post_id ] : null;
		$dash    = '<span class="trekpilot-list-muted">&mdash;</span>';
		if ( ! $s ) {
			echo $dash; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup.
			return;
		}

		switch ( $column ) {
			case 'trekpilot_specs':
				$specs = $s['specs'];
				$rows  = array();
				if ( $specs ) {
					foreach ( array(
						__( 'Difficulty', 'trekpilot' ) => $specs->difficulty,
						__( 'Duration', 'trekpilot' ) => $specs->duration,
						__( 'Region', 'trekpilot' ) => $specs->region,
					) as $label => $value ) {
						if ( '' !== (string) $value ) {
							$rows[] = '<span class="trekpilot-list-spec"><span class="trekpilot-list-muted">' . esc_html( $label ) . ':</span> ' . esc_html( $value ) . '</span>';
						}
					}
				}
				echo $rows ? wp_kses_post( implode( '', $rows ) ) : $dash; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup.
				break;

			case 'trekpilot_cities':
				if ( empty( $s['cities'] ) ) {
					echo '<span class="trekpilot-list-pill trekpilot-list-bad">' . esc_html__( 'None added', 'trekpilot' ) . '</span>';
					break;
				}
				foreach ( $s['cities'] as $city ) {
					$class = 'active' === $city->status ? 'trekpilot-list-pill' : 'trekpilot-list-pill trekpilot-list-warn';
					echo '<span class="' . esc_attr( $class ) . '">' . esc_html( $city->city_name ) . '</span>';
				}
				break;

			case 'trekpilot_price':
				echo null !== $s['price'] ? esc_html( AdminController::format_price( $s['price'] ) ) : $dash; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup.
				break;

			case 'trekpilot_next':
				echo '' !== $s['next'] ? esc_html( wp_date( 'd M Y', strtotime( $s['next'] ) ) ) : '<span class="trekpilot-list-pill trekpilot-list-bad">' . esc_html__( 'No dates', 'trekpilot' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				break;

			case 'trekpilot_seats':
				echo '' !== $s['next'] ? esc_html( (string) $s['seats'] ) : $dash; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup.
				break;

			case 'trekpilot_bookings':
				if ( $s['bookings'] ) {
					$url = add_query_arg(
						array(
							'post_type'      => 'trekpilot_trek',
							'page'           => 'trekpilot-bookings',
							'filter_trek_id' => $post_id,
						),
						admin_url( 'edit.php' )
					);
					/* translators: %d: total number of bookings for this trek */
					echo '<a href="' . esc_url( $url ) . '">' . sprintf( esc_html__( 'View bookings (%d)', 'trekpilot' ), (int) $s['bookings']->total ) . '</a>';
					if ( (int) $s['bookings']->pending > 0 ) {
						/* translators: %d: number of pending bookings */
						echo '<span class="trekpilot-list-todo">' . esc_html( sprintf( __( '%d pending', 'trekpilot' ), (int) $s['bookings']->pending ) ) . '</span>';
					}
				} else {
					echo '0';
				}
				break;

			case 'trekpilot_setup':
				$todo = array();
				if ( empty( $s['cities'] ) ) {
					$todo[] = __( 'Add a departure city', 'trekpilot' );
				}
				$no_dates   = 0;
				$no_pickups = 0;
				$no_itin    = 0;
				foreach ( $s['cities'] as $city ) {
					if ( 'active' !== $city->status ) {
						continue;
					}
					$cid         = (int) $city->id;
					$no_dates   += isset( $s['dates'][ $cid ] ) ? 0 : 1;
					$no_pickups += isset( $s['pickups'][ $cid ] ) ? 0 : 1;
					$no_itin    += isset( $s['itins'][ $cid ] ) ? 0 : 1;
				}
				if ( $no_dates ) {
					/* translators: %d: number of cities */
					$todo[] = sprintf( _n( 'Add dates (%d city)', 'Add dates (%d cities)', $no_dates, 'trekpilot' ), $no_dates );
				}
				if ( $no_pickups ) {
					/* translators: %d: number of cities */
					$todo[] = sprintf( _n( 'Add pickups (%d city)', 'Add pickups (%d cities)', $no_pickups, 'trekpilot' ), $no_pickups );
				}
				if ( $no_itin ) {
					/* translators: %d: number of cities */
					$todo[] = sprintf( _n( 'Add itinerary (%d city)', 'Add itinerary (%d cities)', $no_itin, 'trekpilot' ), $no_itin );
				}
				if ( ! has_post_thumbnail( $post_id ) ) {
					$todo[] = __( 'Set a featured image', 'trekpilot' );
				}

				if ( empty( $todo ) ) {
					echo '<span class="trekpilot-list-pill trekpilot-list-ok">' . esc_html__( 'Ready', 'trekpilot' ) . '</span>';
				} else {
					$blocking = empty( $s['cities'] ) || $no_dates;
					echo '<span class="trekpilot-list-pill ' . ( $blocking ? 'trekpilot-list-bad' : 'trekpilot-list-warn' ) . '">' . esc_html( $blocking ? __( 'Cannot be booked yet', 'trekpilot' ) : __( 'Almost ready', 'trekpilot' ) ) . '</span>';
					foreach ( $todo as $item ) {
						echo '<span class="trekpilot-list-todo">' . esc_html( $item ) . '</span>';
					}
				}
				break;
		}
	}
}
