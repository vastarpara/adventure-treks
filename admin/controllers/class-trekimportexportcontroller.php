<?php
/**
 * Import / Export Treks Controller
 *
 * Exports treks (post, spec fields, departure cities with their itineraries, pickup points,
 * dates, availability and pricing) to a JSON file and imports such a file back as new treks.
 * Bookings are never exported.
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
 * TrekImportExportController class.
 */
class TrekImportExportController {

	const FORMAT_VERSION = 1;

	/**
	 * Constructor to wire admin actions.
	 */
	public function __construct() {
		add_action( 'admin_post_at_export_treks', array( $this, 'handle_export' ) );
		add_action( 'admin_post_at_import_treks', array( $this, 'handle_import' ) );
	}

	/**
	 * Redirect back to the Import / Export settings tab with a result notice.
	 *
	 * @param string $type    Notice type: success|error.
	 * @param string $message Notice text.
	 * @return void
	 */
	private function redirect( $type, $message ) {
		set_transient( 'at_import_export_notice_' . get_current_user_id(), array( $type, $message ), 60 );
		wp_safe_redirect( admin_url( 'edit.php?post_type=adventure_trek&page=adventure-treks-settings#import-export' ) );
		exit;
	}

	/**
	 * Fetch all rows of a table matching a column value.
	 *
	 * @param string $table  Table name without prefix.
	 * @param string $column Column to match.
	 * @param int    $value  Value to match.
	 * @param string $order  ORDER BY clause (trusted, hard-coded by callers).
	 * @return array
	 */
	private function get_rows( $table, $column, $value, $order = 'id ASC' ) {
		global $wpdb;
		$table_name = $wpdb->prefix . $table;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name WHERE $column = %d ORDER BY $order", $value ), ARRAY_A );
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Remove the given keys from a row.
	 *
	 * @param array $row  Row.
	 * @param array $keys Keys to drop.
	 * @return array
	 */
	private function strip( $row, $keys ) {
		foreach ( $keys as $key ) {
			unset( $row[ $key ] );
		}
		return $row;
	}

	/**
	 * Build the export array for a single trek.
	 *
	 * @param \WP_Post $post Trek post.
	 * @return array
	 */
	private function export_trek( $post ) {
		$trek = array(
			'title'          => $post->post_title,
			'slug'           => $post->post_name,
			'status'         => $post->post_status,
			'content'        => $post->post_content,
			'excerpt'        => $post->post_excerpt,
			'date'           => $post->post_date,
			'featured_image' => (string) get_the_post_thumbnail_url( $post->ID, 'full' ),
			'adult_age'      => (string) get_post_meta( $post->ID, '_at_adult_age', true ),
			'child_age'      => (string) get_post_meta( $post->ID, '_at_child_age', true ),
			'details'        => null,
			'cities'         => array(),
		);

		$details = $this->get_rows( 'at_treks', 'post_id', $post->ID );
		if ( ! empty( $details ) ) {
			$details = $this->strip( $details[0], array( 'id', 'post_id' ) );
			// Gallery holds attachment IDs, which mean nothing on another site: export URLs instead.
			$gallery_urls = array();
			foreach ( array_filter( array_map( 'absint', explode( ',', (string) $details['gallery'] ) ) ) as $attachment_id ) {
				$url = wp_get_attachment_url( $attachment_id );
				if ( $url ) {
					$gallery_urls[] = $url;
				}
			}
			$details['gallery'] = $gallery_urls;
			$trek['details']    = $details;
		}

		foreach ( $this->get_rows( 'at_departure_cities', 'trek_id', $post->ID, 'menu_order ASC, id ASC' ) as $city ) {
			$city_id = (int) $city['id'];
			$entry   = $this->strip( $city, array( 'id', 'trek_id' ) );

			$entry['itineraries'] = array();
			foreach ( $this->get_rows( 'at_itineraries', 'city_id', $city_id, 'menu_order ASC, id ASC' ) as $day ) {
				$day_entry          = $this->strip( $day, array( 'id', 'city_id', 'trek_id' ) );
				$day_entry['items'] = array();
				foreach ( $this->get_rows( 'at_itinerary_items', 'itinerary_id', (int) $day['id'], 'menu_order ASC, id ASC' ) as $item ) {
					$day_entry['items'][] = $this->strip( $item, array( 'id', 'itinerary_id' ) );
				}
				$entry['itineraries'][] = $day_entry;
			}

			$entry['pickup_points'] = array();
			foreach ( $this->get_rows( 'at_pickup_points', 'city_id', $city_id, 'menu_order ASC, id ASC' ) as $pickup ) {
				$entry['pickup_points'][] = $this->strip( $pickup, array( 'id', 'city_id', 'trek_id' ) );
			}

			// Dates are keyed by their old ID so date-specific pricing can be re-linked on import.
			$entry['dates'] = array();
			foreach ( $this->get_rows( 'at_departure_dates', 'city_id', $city_id, 'departure_date ASC, id ASC' ) as $date ) {
				$date_entry          = $this->strip( $date, array( 'city_id', 'trek_id' ) );
				$avail               = $this->get_rows( 'at_availability', 'date_id', (int) $date['id'] );
				$date_entry['seats'] = ! empty( $avail ) ? $this->strip( $avail[0], array( 'id', 'date_id' ) ) : null;
				$entry['dates'][]    = $date_entry;
			}

			$entry['pricing'] = array();
			foreach ( $this->get_rows( 'at_pricing', 'city_id', $city_id ) as $price ) {
				$entry['pricing'][] = $this->strip( $price, array( 'id', 'city_id', 'trek_id' ) );
			}

			$trek['cities'][] = $entry;
		}

		return $trek;
	}

	/**
	 * Stream the JSON export as a download.
	 *
	 * @return void
	 */
	public function handle_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to export treks.', 'adventure-treks' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'at_export_treks' );

		$posts = get_posts(
			array(
				'post_type'      => 'adventure_trek',
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page' => -1,
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		$data = array(
			'plugin'      => 'adventure-treks',
			'format'      => self::FORMAT_VERSION,
			'exported_at' => gmdate( 'c' ),
			'source'      => home_url(),
			'treks'       => array_map( array( $this, 'export_trek' ), $posts ),
		);

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="adventure-treks-export-' . gmdate( 'Y-m-d' ) . '.json"' );
		echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON download.
		exit;
	}

	/**
	 * Resolve an image URL to an attachment ID, downloading it into the media library if needed.
	 *
	 * @param string $url   Image URL.
	 * @param int    $post_id Post ID to attach to.
	 * @return int Attachment ID, or 0 on failure.
	 */
	private function image_to_attachment( $url, $post_id ) {
		$url = esc_url_raw( (string) $url );
		if ( '' === $url ) {
			return 0;
		}

		$existing = attachment_url_to_postid( $url );
		if ( $existing ) {
			return (int) $existing;
		}

		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$id = media_sideload_image( $url, $post_id, null, 'id' );
		return is_wp_error( $id ) ? 0 : (int) $id;
	}

	/**
	 * Insert a row built from imported data, keeping only known columns.
	 *
	 * @param string $table   Table name without prefix.
	 * @param array  $row     Row data.
	 * @param array  $columns Allowed column => sanitizer callback.
	 * @return int Insert ID (0 on failure).
	 */
	private function insert_row( $table, $row, $columns ) {
		global $wpdb;

		$data = array();
		foreach ( $columns as $column => $sanitizer ) {
			if ( isset( $row[ $column ] ) && ! is_array( $row[ $column ] ) ) {
				$data[ $column ] = call_user_func( $sanitizer, $row[ $column ] );
			}
		}
		if ( empty( $data ) ) {
			return 0;
		}
		return $wpdb->insert( $wpdb->prefix . $table, $data ) ? (int) $wpdb->insert_id : 0; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	/**
	 * Import one trek as a new post. Returns the new post ID or 0.
	 *
	 * @param array $trek Trek data from the export file.
	 * @return int
	 */
	private function import_trek( $trek ) {
		if ( ! is_array( $trek ) || empty( $trek['title'] ) ) {
			return 0;
		}

		$allowed_status = array( 'publish', 'draft', 'pending', 'private', 'future' );
		$status         = isset( $trek['status'] ) && in_array( $trek['status'], $allowed_status, true ) ? $trek['status'] : 'draft';

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'adventure_trek',
				'post_title'   => sanitize_text_field( $trek['title'] ),
				'post_name'    => isset( $trek['slug'] ) ? sanitize_title( $trek['slug'] ) : '',
				'post_status'  => $status,
				'post_content' => isset( $trek['content'] ) ? wp_kses_post( $trek['content'] ) : '',
				'post_excerpt' => isset( $trek['excerpt'] ) ? wp_kses_post( $trek['excerpt'] ) : '',
			),
			true
		);
		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return 0;
		}

		update_post_meta( $post_id, '_at_adult_age', isset( $trek['adult_age'] ) ? sanitize_text_field( $trek['adult_age'] ) : '' );
		update_post_meta( $post_id, '_at_child_age', isset( $trek['child_age'] ) ? sanitize_text_field( $trek['child_age'] ) : '' );

		if ( ! empty( $trek['featured_image'] ) ) {
			$thumb_id = $this->image_to_attachment( $trek['featured_image'], $post_id );
			if ( $thumb_id ) {
				set_post_thumbnail( $post_id, $thumb_id );
			}
		}

		$text     = 'sanitize_text_field';
		$textarea = 'sanitize_textarea_field';
		$kses     = 'wp_kses_post';
		$int      = 'absint';
		$decimal  = static function ( $value ) {
			return number_format( (float) $value, 2, '.', '' );
		};
		$url      = 'esc_url_raw';

		// Trek specifications.
		if ( ! empty( $trek['details'] ) && is_array( $trek['details'] ) ) {
			$details = $trek['details'];

			$gallery_ids = array();
			if ( ! empty( $details['gallery'] ) && is_array( $details['gallery'] ) ) {
				foreach ( $details['gallery'] as $gallery_url ) {
					$attachment_id = $this->image_to_attachment( $gallery_url, $post_id );
					if ( $attachment_id ) {
						$gallery_ids[] = $attachment_id;
					}
				}
			}
			$details['gallery'] = implode( ',', $gallery_ids );

			// FAQ and policies are stored as JSON strings: re-sanitise their decoded content.
			$faq = '';
			if ( ! empty( $details['faq'] ) ) {
				$faq_items = json_decode( $details['faq'], true );
				$clean     = array();
				if ( is_array( $faq_items ) ) {
					foreach ( $faq_items as $faq_item ) {
						$clean[] = array(
							'q' => sanitize_text_field( isset( $faq_item['q'] ) ? $faq_item['q'] : '' ),
							'a' => sanitize_textarea_field( isset( $faq_item['a'] ) ? $faq_item['a'] : '' ),
						);
					}
				}
				$faq = $clean ? wp_json_encode( $clean ) : '';
			}
			$details['faq'] = $faq;

			$policies = array(
				'cancellation' => '',
				'terms'        => '',
			);
			if ( ! empty( $details['policies'] ) ) {
				$decoded = json_decode( $details['policies'], true );
				if ( is_array( $decoded ) ) {
					foreach ( $policies as $key => $unused ) {
						$policies[ $key ] = isset( $decoded[ $key ] ) ? wp_kses_post( $decoded[ $key ] ) : '';
					}
				}
			}
			$details['policies'] = wp_json_encode( $policies );

			$details['post_id'] = $post_id;
			$this->insert_row(
				'at_treks',
				$details,
				array(
					'post_id'         => $int,
					'difficulty'      => $text,
					'duration'        => $text,
					'altitude'        => $text,
					'region'          => $text,
					'season'          => $text,
					'distance'        => $text,
					'fitness_level'   => $text,
					'age_limit'       => $text,
					'group_size'      => $text,
					'highlights'      => $textarea,
					'exclusions'      => $textarea,
					'things_to_carry' => $textarea,
					'faq'             => static function ( $v ) {
						return $v;
					},
					'policies'        => static function ( $v ) {
						return $v;
					},
					'gallery'         => $text,
				)
			);
		}

		// Departure cities and everything hanging off them.
		$cities = isset( $trek['cities'] ) && is_array( $trek['cities'] ) ? $trek['cities'] : array();
		foreach ( $cities as $city ) {
			if ( ! is_array( $city ) ) {
				continue;
			}
			$city['trek_id'] = $post_id;
			$city_id         = $this->insert_row(
				'at_departure_cities',
				$city,
				array(
					'trek_id'          => $int,
					'city_name'        => $text,
					'base_price'       => $decimal,
					'offer_price'      => $decimal,
					'transport_type'   => $text,
					'reporting_time'   => $text,
					'google_map_link'  => $url,
					'booking_deadline' => $int,
					'status'           => $text,
					'menu_order'       => $int,
				)
			);
			if ( ! $city_id ) {
				continue;
			}

			$scope = array(
				'city_id' => $city_id,
				'trek_id' => $post_id,
			);

			foreach ( isset( $city['itineraries'] ) && is_array( $city['itineraries'] ) ? $city['itineraries'] : array() as $day ) {
				if ( ! is_array( $day ) ) {
					continue;
				}
				$day_id = $this->insert_row(
					'at_itineraries',
					array_merge( $day, $scope ),
					array(
						'city_id'     => $int,
						'trek_id'     => $int,
						'day_number'  => $int,
						'title'       => $text,
						'description' => $textarea,
						'menu_order'  => $int,
					)
				);
				if ( ! $day_id ) {
					continue;
				}
				foreach ( isset( $day['items'] ) && is_array( $day['items'] ) ? $day['items'] : array() as $item ) {
					if ( ! is_array( $item ) ) {
						continue;
					}
					$item['itinerary_id'] = $day_id;
					$this->insert_row(
						'at_itinerary_items',
						$item,
						array(
							'itinerary_id' => $int,
							'item_time'    => $text,
							'title'        => $text,
							'description'  => $textarea,
							'icon'         => $text,
							'image_url'    => $url,
							'menu_order'   => $int,
						)
					);
				}
			}

			foreach ( isset( $city['pickup_points'] ) && is_array( $city['pickup_points'] ) ? $city['pickup_points'] : array() as $pickup ) {
				if ( ! is_array( $pickup ) ) {
					continue;
				}
				$this->insert_row(
					'at_pickup_points',
					array_merge( $pickup, $scope ),
					array(
						'city_id'         => $int,
						'trek_id'         => $int,
						'location_name'   => $text,
						'pickup_time'     => $text,
						'google_maps_url' => $url,
						'instructions'    => $textarea,
						'menu_order'      => $int,
					)
				);
			}

			$date_map = array(); // Old date ID => new date ID.
			foreach ( isset( $city['dates'] ) && is_array( $city['dates'] ) ? $city['dates'] : array() as $date ) {
				if ( ! is_array( $date ) || empty( $date['departure_date'] ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date['departure_date'] ) ) {
					continue;
				}
				$date_id = $this->insert_row(
					'at_departure_dates',
					array_merge( $date, $scope ),
					array(
						'city_id'        => $int,
						'trek_id'        => $int,
						'departure_date' => $text,
						'status'         => $text,
						'notes'          => $textarea,
					)
				);
				if ( ! $date_id ) {
					continue;
				}
				if ( isset( $date['id'] ) ) {
					$date_map[ (int) $date['id'] ] = $date_id;
				}
				if ( ! empty( $date['seats'] ) && is_array( $date['seats'] ) ) {
					$date['seats']['date_id'] = $date_id;
					$this->insert_row(
						'at_availability',
						$date['seats'],
						array(
							'date_id'         => $int,
							'total_seats'     => $int,
							'booked_seats'    => $int,
							'available_seats' => $int,
						)
					);
				}
			}

			foreach ( isset( $city['pricing'] ) && is_array( $city['pricing'] ) ? $city['pricing'] : array() as $price ) {
				if ( ! is_array( $price ) ) {
					continue;
				}
				$old_date_id = isset( $price['date_id'] ) ? (int) $price['date_id'] : 0;
				if ( $old_date_id && ! isset( $date_map[ $old_date_id ] ) ) {
					continue; // Date-specific price whose date was not imported.
				}
				$price['date_id'] = $old_date_id ? $date_map[ $old_date_id ] : 0;

				// JSON structure columns are plain strings in the export; keep them only if they are valid JSON.
				foreach ( array( 'group_discount', 'extra_charges', 'optional_addons', 'transport_options' ) as $json_column ) {
					if ( isset( $price[ $json_column ] ) && ( ! is_string( $price[ $json_column ] ) || null === json_decode( $price[ $json_column ] ) ) ) {
						unset( $price[ $json_column ] );
					}
				}

				$raw = static function ( $v ) {
					return $v;
				};
				$this->insert_row(
					'at_pricing',
					array_merge( $price, $scope ),
					array(
						'city_id'           => $int,
						'date_id'           => $int,
						'trek_id'           => $int,
						'adult_price'       => $decimal,
						'child_price'       => $decimal,
						'offer_price'       => $decimal,
						'group_discount'    => $raw,
						'extra_charges'     => $raw,
						'optional_addons'   => $raw,
						'transport_options' => $raw,
					)
				);
			}
		}

		return (int) $post_id;
	}

	/**
	 * Read an uploaded JSON file and create the treks it contains.
	 *
	 * @return void
	 */
	public function handle_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to import treks.', 'adventure-treks' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'at_import_treks' );

		if ( empty( $_FILES['at_import_file']['tmp_name'] ) || ! empty( $_FILES['at_import_file']['error'] ) ) {
			$this->redirect( 'error', __( 'Please choose a valid export file to import.', 'adventure-treks' ) );
		}

		$name = isset( $_FILES['at_import_file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['at_import_file']['name'] ) ) : '';
		if ( 'json' !== strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) ) {
			$this->redirect( 'error', __( 'The import file must be a .json file exported from Adventure Treks.', 'adventure-treks' ) );
		}

		$contents = file_get_contents( $_FILES['at_import_file']['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$data     = json_decode( (string) $contents, true );
		if ( ! is_array( $data ) || ! isset( $data['plugin'] ) || 'adventure-treks' !== $data['plugin'] || ! isset( $data['treks'] ) || ! is_array( $data['treks'] ) ) {
			$this->redirect( 'error', __( 'This file is not a valid Adventure Treks export.', 'adventure-treks' ) );
		}

		// Importing downloads images and writes many rows: allow it to take a while.
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 300 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
		}

		$imported = 0;
		$failed   = 0;
		foreach ( $data['treks'] as $trek ) {
			if ( $this->import_trek( $trek ) ) {
				++$imported;
			} else {
				++$failed;
			}
		}

		$message = sprintf(
			/* translators: %d: number of treks */
			_n( '%d trek imported.', '%d treks imported.', $imported, 'adventure-treks' ),
			$imported
		);
		if ( $failed ) {
			$message .= ' ' . sprintf(
				/* translators: %d: number of treks */
				_n( '%d trek was skipped because its data was invalid.', '%d treks were skipped because their data was invalid.', $failed, 'adventure-treks' ),
				$failed
			);
		}
		$this->redirect( $imported ? 'success' : 'error', $message );
	}
}
