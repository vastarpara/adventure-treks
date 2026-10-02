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

		$delete_nonce = wp_create_nonce( 'at_delete_booking' );
		$edit_url     = sprintf( '?post_type=adventure_trek&page=at-bookings&action=edit&booking=%d', $item->id );
		$delete_url   = sprintf( '?post_type=adventure_trek&page=at-bookings&action=delete&booking=%d&_wpnonce=%s', $item->id, $delete_nonce );
		$actions      = array(
			'edit'   => sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), esc_html__( 'Edit', 'adventure-treks' ) ),
			'delete' => sprintf( '<a href="%s" class="submitdelete" onclick="return confirm(\'%s\');">%s</a>', esc_url( $delete_url ), esc_attr__( 'Are you sure you want to delete this booking? This cannot be undone.', 'adventure-treks' ), esc_html__( 'Delete', 'adventure-treks' ) ),
		);

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
		return array(
			'bulk-delete' => __( 'Delete', 'adventure-treks' ),
		);
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
				<?php submit_button( __( 'Filter', 'adventure-treks' ), '', 'filter_action', false, array( 'id' => 'post-query-submit' ) ); ?>
			</div>
			<?php
		}
	}

	/**
	 * Process bulk action.
	 */
	public function process_bulk_action() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'at_bookings';

		// Handle single delete action.
		if ( 'delete' === $this->current_action() ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'at_delete_booking' ) ) {
				return;
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$booking_id = isset( $_GET['booking'] ) ? absint( wp_unslash( $_GET['booking'] ) ) : 0;
			if ( $booking_id ) {
				// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $booking_id ) );
				$wpdb->delete( $table_name, array( 'id' => $booking_id ), array( '%d' ) );
				// phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

				if ( $booking && 'cancelled' !== $booking->status ) {
					TrekBookingsController::sync_availability( $booking->date_id, -intval( $booking->seats ) );
				}

				echo '<div class="notice notice-success is-dismissible"><p>Booking deleted successfully.</p></div>';
			}
		}

		// Handle bulk delete action.
		if ( ( isset( $_GET['action'] ) && 'bulk-delete' === $_GET['action'] ) || ( isset( $_GET['action2'] ) && 'bulk-delete' === $_GET['action2'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$booking_ids = isset( $_GET['booking_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_GET['booking_ids'] ) ) : array();
			if ( ! empty( $booking_ids ) ) {
				// phpcs:disable WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$ids                = implode( ',', $booking_ids );
				$bookings_to_delete = $wpdb->get_results( "SELECT * FROM $table_name WHERE id IN ($ids)" );
				$wpdb->query( "DELETE FROM $table_name WHERE id IN ($ids)" );
				// phpcs:enable WordPress.DB.DirectDatabaseQuery, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

				foreach ( $bookings_to_delete as $booking ) {
					if ( 'cancelled' !== $booking->status ) {
						TrekBookingsController::sync_availability( $booking->date_id, -intval( $booking->seats ) );
					}
				}

				echo '<div class="notice notice-success is-dismissible"><p>Bookings deleted successfully.</p></div>';
			}
		}
	}

	/**
	 * Prepare items.
	 */
	public function prepare_items() {
		global $wpdb;

		$per_page = 20;
		$columns  = $this->get_columns();
		$hidden   = array();
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

		// Build WHERE clause for filter and search.
		$where_clause = 'WHERE 1=1';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$filter_trek_id = isset( $_GET['filter_trek_id'] ) ? absint( wp_unslash( $_GET['filter_trek_id'] ) ) : 0;
		if ( $filter_trek_id > 0 ) {
			$where_clause .= $wpdb->prepare( ' AND trek_id = %d', $filter_trek_id );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$filter_status  = isset( $_GET['filter_status'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_status'] ) ) : '';
		$valid_statuses = array( 'pending', 'confirmed', 'cancelled' );
		if ( in_array( $filter_status, $valid_statuses, true ) ) {
			$where_clause .= $wpdb->prepare( ' AND status = %s', $filter_status );
		}

		// Search handling.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$search_query = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		if ( ! empty( $search_query ) ) {
			$like          = '%' . $wpdb->esc_like( $search_query ) . '%';
			$where_clause .= $wpdb->prepare( ' AND (cust_name LIKE %s OR cust_email LIKE %s OR cust_phone LIKE %s)', $like, $like, $like );
		}

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
