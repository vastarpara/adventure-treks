<?php
/**
 * Bookings Controller.
 *
 * @package AdventureTreks
 */

namespace AdventureTreks\Admin\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Custom WP_List_Table for Bookings.
 */
class Bookings_List_Table extends \WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'booking',
				'plural'   => 'bookings',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Message shown when the list is empty.
	 *
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'No trek bookings found.', 'adventure-treks' );
	}

	/**
	 * Get columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'             => '<input type="checkbox" />',
			'cust_name'      => __( 'Customer', 'adventure-treks' ),
			'trek_title'     => __( 'Trek', 'adventure-treks' ),
			'city_name'      => __( 'Departure City', 'adventure-treks' ),
			'date_val'       => __( 'Travel Date', 'adventure-treks' ),
			'seats'          => __( 'Seats', 'adventure-treks' ),
			'addons'         => __( 'Add-ons', 'adventure-treks' ),
			'total_amount'   => __( 'Amount', 'adventure-treks' ),
			'created_at'     => __( 'Booked On', 'adventure-treks' ),
			'status'         => __( 'Status', 'adventure-treks' ),
			'payment_status' => __( 'Payment Status', 'adventure-treks' ),
			'view'           => __( 'Details', 'adventure-treks' ),
		);
	}

	/**
	 * Sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'created_at' => array( 'created_at', true ),
			'date_val'   => array( 'date_val', false ),
		);
	}

	/**
	 * Column cb.
	 *
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="booking_ids[]" value="%d" />', $item->id );
	}

	/**
	 * Column Default.
	 *
	 * @param object $item        Item.
	 * @param string $column_name Column Name.
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'seats':
				return esc_html( $item->$column_name );
			case 'total_amount':
				return esc_html( \AdventureTreks\Admin\Controllers\AdminController::format_price( $item->$column_name ) );
			case 'status':
				$color = 'confirmed' === $item->status ? 'green' : 'red';
				return '<span style="color:' . esc_attr( $color ) . ';font-weight:bold;">' . esc_html( ucfirst( $item->$column_name ) ) . '</span>';
			case 'payment_status':
				$color = 'paid' === $item->payment_status ? 'green' : 'red';
				return '<span style="color:' . esc_attr( $color ) . ';font-weight:bold;">' . esc_html( ucfirst( $item->$column_name ) ) . '</span>';
			case 'created_at':
				return esc_html( gmdate( 'd M Y, h:i A', strtotime( $item->created_at ) ) );
			case 'view':
				return sprintf(
					'<button type="button" class="button at-view-booking-btn" data-booking-id="%d">%s</button>',
					intval( $item->id ),
					esc_html__( 'View', 'adventure-treks' )
				);
			default:
				return '';
		}
	}

	/**
	 * Customer column.
	 *
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_cust_name( $item ) {
		$name  = '<strong>' . esc_html( $item->cust_name ) . '</strong>';
		$name .= '<br><a href="mailto:' . esc_attr( $item->cust_email ) . '">' . esc_html( $item->cust_email ) . '</a>';
		$name .= '<br>' . esc_html( $item->cust_phone );

		$action_nonce = wp_create_nonce( 'at_delete_booking' );
		$base         = 'edit.php?post_type=adventure_trek&page=at-bookings';
		$action_url   = static function ( $action ) use ( $base, $item, $action_nonce ) {
			return admin_url( sprintf( '%s&action=%s&booking=%d&_wpnonce=%s', $base, $action, $item->id, $action_nonce ) );
		};

		if ( $this->is_trash_view() ) {
			$actions = array(
				'restore' => sprintf( '<a href="%s">%s</a>', esc_url( $action_url( 'restore' ) ), esc_html__( 'Restore', 'adventure-treks' ) ),
				'delete'  => sprintf( '<a href="%s" class="submitdelete" onclick="return confirm(\'%s\');">%s</a>', esc_url( $action_url( 'delete' ) ), esc_attr__( 'Delete this booking permanently? This cannot be undone.', 'adventure-treks' ), esc_html__( 'Delete Permanently', 'adventure-treks' ) ),
			);
		} else {
			$actions = array(
				'edit'  => sprintf( '<a href="%s">%s</a>', esc_url( admin_url( sprintf( '%s&action=edit&booking=%d', $base, $item->id ) ) ), esc_html__( 'Edit', 'adventure-treks' ) ),
				'trash' => sprintf( '<a href="%s" class="submitdelete">%s</a>', esc_url( $action_url( 'trash' ) ), esc_html__( 'Trash', 'adventure-treks' ) ),
			);
		}
		return $name . $this->row_actions( $actions );
	}

	/**
	 * Trek column.
	 *
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_trek_title( $item ) {
		return '<strong>' . esc_html( get_the_title( $item->trek_id ) ) . '</strong>';
	}

	/**
	 * City column.
	 *
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_city_name( $item ) {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$city_name = $wpdb->get_var( $wpdb->prepare( "SELECT city_name FROM {$wpdb->prefix}at_departure_cities WHERE id = %d", $item->city_id ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery
		return esc_html( $city_name );
	}

	/**
	 * Date column.
	 *
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_date_val( $item ) {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$date_val = $wpdb->get_var( $wpdb->prepare( "SELECT departure_date FROM {$wpdb->prefix}at_departure_dates WHERE id = %d", $item->date_id ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery
		return $date_val ? esc_html( gmdate( 'd M Y', strtotime( $date_val ) ) ) : '-';
	}

	/**
	 * Addons column.
	 *
	 * @param object $item Item.
	 * @return string
	 */
	protected function column_addons( $item ) {
		if ( empty( $item->addons ) ) {
			return '-';
		}

		$addons = json_decode( $item->addons, true );
		if ( ! is_array( $addons ) || empty( $addons ) ) {
			return '-';
		}

		$html = '<ul style="margin: 0; padding-left: 15px; list-style-type: disc;">';
		foreach ( $addons as $addon ) {
			$html .= '<li>' . esc_html( $addon ) . '</li>';
		}
		$html .= '</ul>';

		return $html;
	}

	/**
	 * Bulk actions.
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		if ( $this->is_trash_view() ) {
			return array(
				'bulk-restore' => __( 'Restore', 'adventure-treks' ),
				'bulk-delete'  => __( 'Delete Permanently', 'adventure-treks' ),
			);
		}

		return array(
			'bulk-trash' => __( 'Move to Trash', 'adventure-treks' ),
		);
	}

	/**
	 * Whether the Trash view is being shown.
	 *
	 * @return bool
	 */
	private function is_trash_view() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['booking_view'] ) && 'trash' === $_GET['booking_view'];
	}

	/**
	 * "All | Trash" views with counts.
	 *
	 * @return array
	 */
	protected function get_views() {
		global $wpdb;
		$table = $wpdb->prefix . 'at_bookings';
		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$all   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE trashed_at IS NULL" );
		$trash = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE trashed_at IS NOT NULL" );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$base     = admin_url( 'edit.php?post_type=adventure_trek&page=at-bookings' );
		$in_trash = $this->is_trash_view();

		$views = array(
			'all' => sprintf( '<a href="%s"%s>%s <span class="count">(%d)</span></a>', esc_url( $base ), $in_trash ? '' : ' class="current" aria-current="page"', esc_html__( 'All', 'adventure-treks' ), $all ),
		);
		if ( $trash || $in_trash ) {
			$views['trash'] = sprintf( '<a href="%s"%s>%s <span class="count">(%d)</span></a>', esc_url( $base . '&booking_view=trash' ), $in_trash ? ' class="current" aria-current="page"' : '', esc_html__( 'Trash', 'adventure-treks' ), $trash );
		}

		return $views;
	}
	/**
	 * Extra table nav (Filter by Trek, Filter by Status).
	 *
	 * @param string $which Top or bottom.
	 */
	protected function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$selected_trek = isset( $_GET['filter_trek_id'] ) ? absint( wp_unslash( $_GET['filter_trek_id'] ) ) : 0;
			$treks         = get_posts(
				array(
					'post_type'      => 'adventure_trek',
					'posts_per_page' => -1,
					'post_status'    => 'publish',
					'orderby'        => 'title',
					'order'          => 'ASC',
				)
			);

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$selected_status = isset( $_GET['filter_status'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_status'] ) ) : '';
			$date_from       = isset( $_GET['filter_date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_date_from'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$date_to         = isset( $_GET['filter_date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_date_to'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$statuses        = array(
				'pending'   => __( 'Pending', 'adventure-treks' ),
				'confirmed' => __( 'Confirmed', 'adventure-treks' ),
				'cancelled' => __( 'Cancelled', 'adventure-treks' ),
			);
			?>
			<div class="alignleft actions">
				<select name="filter_trek_id">
					<option value="0"><?php esc_html_e( 'All Treks', 'adventure-treks' ); ?></option>
					<?php foreach ( $treks as $trek ) : ?>
						<option value="<?php echo esc_attr( $trek->ID ); ?>" <?php selected( $selected_trek, $trek->ID ); ?>>
							<?php echo esc_html( $trek->post_title ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<select name="filter_status">
					<option value=""><?php esc_html_e( 'All Statuses', 'adventure-treks' ); ?></option>
					<?php foreach ( $statuses as $status_val => $status_label ) : ?>
						<option value="<?php echo esc_attr( $status_val ); ?>" <?php selected( $selected_status, $status_val ); ?>>
							<?php echo esc_html( $status_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<input type="text" id="at_filter_date_range" class="at-filter-date" value="" placeholder="<?php esc_attr_e( 'Travel date range', 'adventure-treks' ); ?>" autocomplete="off" readonly style="width:230px;" />
				<input type="hidden" name="filter_date_from" value="<?php echo esc_attr( $date_from ); ?>" />
				<input type="hidden" name="filter_date_to" value="<?php echo esc_attr( $date_to ); ?>" />
				<?php submit_button( __( 'Filter', 'adventure-treks' ), '', 'filter_action', false, array( 'id' => 'post-query-submit' ) ); ?>
				<?php
				$pdf_url = wp_nonce_url(
					add_query_arg(
						array_filter(
							array(
								'action'           => 'at_export_bookings_pdf',
								'filter_trek_id'   => $selected_trek,
								'filter_status'    => $selected_status,
								'filter_date_from' => $date_from,
								'filter_date_to'   => $date_to,
								// phpcs:ignore WordPress.Security.NonceVerification.Recommended
								's'                => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
								'booking_view'     => $this->is_trash_view() ? 'trash' : '',
							)
						),
						admin_url( 'admin-post.php' )
					),
					'at_export_bookings_pdf'
				);
				?>
				<a href="<?php echo esc_url( $pdf_url ); ?>" class="button"><?php esc_html_e( 'Download PDF', 'adventure-treks' ); ?></a>
				<?php if ( $this->is_trash_view() ) : ?>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'edit.php?post_type=adventure_trek&page=at-bookings&booking_view=trash&action=empty-trash' ), 'at_empty_trash' ) ); ?>" class="button" onclick="return confirm('<?php echo esc_js( __( 'Permanently delete every booking in the Trash? This cannot be undone.', 'adventure-treks' ) ); ?>');"><?php esc_html_e( 'Empty Trash', 'adventure-treks' ); ?></a>
				<?php endif; ?>
				<?php if ( $selected_trek || '' !== $selected_status || '' !== $date_from || '' !== $date_to ) : ?>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=adventure_trek&page=at-bookings' . ( $this->is_trash_view() ? '&booking_view=trash' : '' ) ) ); ?>" class="button"><?php esc_html_e( 'Reset', 'adventure-treks' ); ?></a>
				<?php endif; ?>
			</div>
			<?php
		}
	}

	/**
	 * Fetch bookings by ID, optionally limited to trashed / live ones.
	 *
	 * @param int[]     $ids     Booking IDs.
	 * @param bool|null $trashed true = only trashed, false = only live, null = any.
	 * @return object[]
	 */
	private function get_bookings_by_ids( $ids, $trashed = null ) {
		global $wpdb;
		$ids = array_filter( array_map( 'absint', $ids ) );
		if ( empty( $ids ) ) {
			return array();
		}
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$extra        = '';
		if ( true === $trashed ) {
			$extra = ' AND trashed_at IS NOT NULL';
		} elseif ( false === $trashed ) {
			$extra = ' AND trashed_at IS NULL';
		}
		// $extra is one of two hard-coded fragments above and the IDs are bound as %d placeholders.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, PluginCheck.Security.DirectDB.UnescapedDBParameter
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}at_bookings WHERE id IN ($placeholders)" . $extra, $ids ) );
	}

	/**
	 * Move bookings to / out of the Trash, keeping seat availability in sync.
	 *
	 * @param int[] $ids      Booking IDs.
	 * @param bool  $to_trash true to trash, false to restore.
	 * @return int Number of bookings changed.
	 */
	private function set_trashed( $ids, $to_trash ) {
		global $wpdb;
		$bookings = $this->get_bookings_by_ids( $ids, ! $to_trash );
		foreach ( $bookings as $booking ) {
			if ( $to_trash ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->update( $wpdb->prefix . 'at_bookings', array( 'trashed_at' => current_time( 'mysql' ) ), array( 'id' => $booking->id ), array( '%s' ), array( '%d' ) );
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}at_bookings SET trashed_at = NULL WHERE id = %d", $booking->id ) );
			}
			// A trashed booking no longer holds its seats; restoring takes them back.
			if ( 'cancelled' !== $booking->status ) {
				TrekBookingsController::sync_availability( $booking->date_id, ( $to_trash ? -1 : 1 ) * intval( $booking->seats ) );
			}
		}
		return count( $bookings );
	}

	/**
	 * Permanently delete trashed bookings (their seats were already released when trashed).
	 *
	 * @param int[]|null $ids Booking IDs, or null for every trashed booking.
	 * @return int Number deleted.
	 */
	private function delete_trashed( $ids = null ) {
		global $wpdb;
		$table = $wpdb->prefix . 'at_bookings';
		if ( null === $ids ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			return (int) $wpdb->query( "DELETE FROM $table WHERE trashed_at IS NOT NULL" );
		}
		$bookings = $this->get_bookings_by_ids( $ids, true );
		foreach ( $bookings as $booking ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->delete( $table, array( 'id' => $booking->id ), array( '%d' ) );
		}
		return count( $bookings );
	}

	/**
	 * Print a success notice.
	 *
	 * @param string $message Message.
	 * @return void
	 */
	private function notice( $message ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * Process single and bulk actions (trash, restore, delete permanently, empty trash).
	 */
	public function process_bulk_action() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$action  = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
		$action2 = isset( $_GET['action2'] ) ? sanitize_text_field( wp_unslash( $_GET['action2'] ) ) : '';
		$nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// Single actions.
		if ( in_array( $action, array( 'trash', 'restore', 'delete' ), true ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$booking_id = isset( $_GET['booking'] ) ? absint( wp_unslash( $_GET['booking'] ) ) : 0;
			if ( ! $booking_id || ! wp_verify_nonce( $nonce, 'at_delete_booking' ) ) {
				return;
			}
			if ( 'trash' === $action && $this->set_trashed( array( $booking_id ), true ) ) {
				$this->notice( __( 'Booking moved to the Trash.', 'adventure-treks' ) );
			} elseif ( 'restore' === $action && $this->set_trashed( array( $booking_id ), false ) ) {
				$this->notice( __( 'Booking restored.', 'adventure-treks' ) );
			} elseif ( 'delete' === $action && $this->delete_trashed( array( $booking_id ) ) ) {
				$this->notice( __( 'Booking deleted permanently.', 'adventure-treks' ) );
			}
			return;
		}

		if ( 'empty-trash' === $action ) {
			if ( wp_verify_nonce( $nonce, 'at_empty_trash' ) ) {
				$count = $this->delete_trashed();
				/* translators: %d: number of bookings */
				$this->notice( sprintf( _n( '%d booking deleted permanently.', '%d bookings deleted permanently.', $count, 'adventure-treks' ), $count ) );
			}
			return;
		}

		// Bulk actions (top or bottom dropdown).
		$bulk_actions = array( 'bulk-trash', 'bulk-restore', 'bulk-delete' );
		$bulk         = in_array( $action, $bulk_actions, true ) ? $action : $action2;
		if ( ! in_array( $bulk, $bulk_actions, true ) || ! wp_verify_nonce( $nonce, 'bulk-bookings' ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$booking_ids = isset( $_GET['booking_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_GET['booking_ids'] ) ) : array();
		if ( empty( $booking_ids ) ) {
			return;
		}

		if ( 'bulk-trash' === $bulk ) {
			$count = $this->set_trashed( $booking_ids, true );
			/* translators: %d: number of bookings */
			$this->notice( sprintf( _n( '%d booking moved to the Trash.', '%d bookings moved to the Trash.', $count, 'adventure-treks' ), $count ) );
		} elseif ( 'bulk-restore' === $bulk ) {
			$count = $this->set_trashed( $booking_ids, false );
			/* translators: %d: number of bookings */
			$this->notice( sprintf( _n( '%d booking restored.', '%d bookings restored.', $count, 'adventure-treks' ), $count ) );
		} else {
			$count = $this->delete_trashed( $booking_ids );
			/* translators: %d: number of bookings */
			$this->notice( sprintf( _n( '%d booking deleted permanently.', '%d bookings deleted permanently.', $count, 'adventure-treks' ), $count ) );
		}
	}
	/**
	 * Build the WHERE clause shared by the list table and the PDF export.
	 *
	 * @param array $filters trash (bool), trek_id (int), status, date_from, date_to (Y-m-d), search.
	 * @return string Prepared SQL starting with "WHERE".
	 */
	public static function build_where_clause( $filters ) {
		global $wpdb;

		$where = ! empty( $filters['trash'] ) ? 'WHERE trashed_at IS NOT NULL' : 'WHERE trashed_at IS NULL';

		if ( ! empty( $filters['trek_id'] ) ) {
			$where .= $wpdb->prepare( ' AND trek_id = %d', absint( $filters['trek_id'] ) );
		}

		if ( ! empty( $filters['status'] ) && in_array( $filters['status'], array( 'pending', 'confirmed', 'cancelled' ), true ) ) {
			$where .= $wpdb->prepare( ' AND status = %s', $filters['status'] );
		}

		// Travel (departure) date range, inclusive. Either end may be left empty.
		$date_table = $wpdb->prefix . 'at_departure_dates';
		if ( ! empty( $filters['date_from'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $filters['date_from'] ) ) {
			$where .= $wpdb->prepare( " AND date_id IN (SELECT id FROM $date_table WHERE departure_date >= %s)", $filters['date_from'] ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		if ( ! empty( $filters['date_to'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $filters['date_to'] ) ) {
			$where .= $wpdb->prepare( " AND date_id IN (SELECT id FROM $date_table WHERE departure_date <= %s)", $filters['date_to'] ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		if ( ! empty( $filters['search'] ) ) {
			$like   = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
			$where .= $wpdb->prepare( ' AND (cust_name LIKE %s OR cust_email LIKE %s OR cust_phone LIKE %s)', $like, $like, $like );
		}

		return $where;
	}
	/**
	 * Prepare items.
	 */
	public function prepare_items() {
		global $wpdb;

		$per_page = $this->get_items_per_page( 'at_bookings_per_page', 20 );
		$columns  = $this->get_columns();
		$hidden   = get_hidden_columns( $this->screen );
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'created_at';
		$order   = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'DESC';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		$valid_orderby = array( 'id', 'created_at', 'date_val' );
		if ( ! in_array( $orderby, $valid_orderby, true ) ) {
			$orderby = 'created_at';
		}

		$order = ( 'ASC' === strtoupper( $order ) ) ? 'ASC' : 'DESC';

		$table_name = $wpdb->prefix . 'at_bookings';

		// Process actions before querying.
		$this->process_bulk_action();

		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$where_clause = self::build_where_clause(
			array(
				'trash'     => $this->is_trash_view(),
				'trek_id'   => isset( $_GET['filter_trek_id'] ) ? absint( wp_unslash( $_GET['filter_trek_id'] ) ) : 0,
				'status'    => isset( $_GET['filter_status'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_status'] ) ) : '',
				'date_from' => isset( $_GET['filter_date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_date_from'] ) ) : '',
				'date_to'   => isset( $_GET['filter_date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_date_to'] ) ) : '',
				'search'    => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
			)
		);
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		// We cannot easily order by date_val directly because date_val is in another table, but for simplicity, we will just order by the booking ID or created_at. If ordered by date_val, we ignore it and fallback to created_at to avoid complex JOINs for this simple admin panel.
		if ( 'date_val' === $orderby ) {
			$orderby = 'created_at';
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name $where_clause" );
		$this->items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name $where_clause ORDER BY {$orderby} {$order} LIMIT %d, %d",
				$offset,
				$per_page
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}
}
