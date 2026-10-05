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

/**
 * Controller for Bookings Admin Page.
 */
class TrekBookingsController {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_at_save_booking', array( $this, 'handle_save_booking' ) );
		add_action( 'admin_post_at_export_bookings_pdf', array( $this, 'handle_export_pdf' ) );
		add_action( 'wp_ajax_at_get_single_booking_details', array( $this, 'ajax_get_booking_details' ) );
		add_filter( 'set_screen_option_at_bookings_per_page', array( $this, 'save_screen_option' ), 10, 3 );
	}

	/**
	 * Screen Options: items per page and the column toggles for the bookings list.
	 *
	 * @return void
	 */
	public function register_screen_options() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['action'] ) && in_array( $_GET['action'], array( 'add', 'edit' ), true ) ) {
			return; // The Add/Edit form has no list to configure.
		}

		add_screen_option(
			'per_page',
			array(
				'label'   => __( 'Number of bookings per page:', 'adventure-treks' ),
				'default' => 20,
				'option'  => 'at_bookings_per_page',
			)
		);

		// Lists the columns as checkboxes in the Screen Options panel.
		$screen = get_current_screen();
		add_filter(
			'manage_' . $screen->id . '_columns',
			static function () {
				return ( new Bookings_List_Table() )->get_columns();
			}
		);
	}

	/**
	 * Persist the per-page value from Screen Options.
	 *
	 * @param mixed  $status Current filter value (false = not handled).
	 * @param string $option Option name.
	 * @param mixed  $value  Submitted value.
	 * @return int
	 */
	public function save_screen_option( $status, $option, $value ) {
		return max( 1, min( 200, (int) $value ) );
	}

	/**
	 * Format a booking ID into its display reference, e.g. 45 -> "AD: 45".
	 *
	 * @param int $booking_id Raw booking ID.
	 * @return string
	 */
	public static function format_booking_ref( $booking_id ) {
		return 'AD: ' . intval( $booking_id );
	}

	/**
	 * Apply a seat delta to a departure date's availability counters.
	 *
	 * Positive delta consumes seats (new/reactivated booking), negative delta
	 * releases seats (cancelled/deleted booking, or a seat-count reduction).
	 * Both counters are clamped to zero to avoid negative seat counts if data
	 * ever drifts out of sync.
	 *
	 * @param int $date_id    Departure date ID.
	 * @param int $seat_delta Signed number of seats to add to booked_seats.
	 * @return void
	 */
	public static function sync_availability( $date_id, $seat_delta ) {
		$date_id    = intval( $date_id );
		$seat_delta = intval( $seat_delta );

		if ( ! $date_id || 0 === $seat_delta ) {
			return;
		}

		global $wpdb;
		$table_avail = $wpdb->prefix . 'at_availability';

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$avail = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_avail WHERE date_id = %d", $date_id ) );
		if ( ! $avail ) {
			return;
		}

		$new_booked    = max( 0, intval( $avail->booked_seats ) + $seat_delta );
		$new_available = max( 0, intval( $avail->total_seats ) - $new_booked );

		$wpdb->update(
			$table_avail,
			array(
				'booked_seats'    => $new_booked,
				'available_seats' => $new_available,
			),
			array( 'id' => $avail->id ),
			array( '%d', '%d' ),
			array( '%d' )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	}


	/**
	 * Download the currently filtered bookings as an A4 (landscape) PDF.
	 *
	 * @return void
	 */
	public function handle_export_pdf() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'adventure-treks' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'at_export_bookings_pdf' );

		global $wpdb;

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$filters = array(
			'trash'     => isset( $_GET['booking_view'] ) && 'trash' === $_GET['booking_view'],
			'trek_id'   => isset( $_GET['filter_trek_id'] ) ? absint( wp_unslash( $_GET['filter_trek_id'] ) ) : 0,
			'status'    => isset( $_GET['filter_status'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_status'] ) ) : '',
			'date_from' => isset( $_GET['filter_date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_date_from'] ) ) : '',
			'date_to'   => isset( $_GET['filter_date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_date_to'] ) ) : '',
			'search'    => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
		);
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$where = Bookings_List_Table::build_where_clause( $filters );
		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$bookings = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}at_bookings $where ORDER BY id ASC" );
		$cities   = $wpdb->get_results( "SELECT id, city_name FROM {$wpdb->prefix}at_departure_cities", OBJECT_K );
		$dates    = $wpdb->get_results( "SELECT id, departure_date FROM {$wpdb->prefix}at_departure_dates", OBJECT_K );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		// Earliest travel date first, then booking order.
		usort(
			$bookings,
			static function ( $a, $b ) use ( $dates ) {
				$da = isset( $dates[ $a->date_id ] ) ? $dates[ $a->date_id ]->departure_date : '9999-12-31';
				$db = isset( $dates[ $b->date_id ] ) ? $dates[ $b->date_id ]->departure_date : '9999-12-31';
				return $da === $db ? (int) $a->id - (int) $b->id : strcmp( $da, $db );
			}
		);

		// Filter summary under the title.
		$summary   = array();
		$summary[] = $filters['trek_id'] ? get_the_title( $filters['trek_id'] ) : __( 'All treks', 'adventure-treks' );
		if ( $filters['status'] ) {
			$summary[] = ucfirst( $filters['status'] );
		}
		if ( $filters['date_from'] || $filters['date_to'] ) {
			$fmt       = static function ( $date ) {
				return $date ? wp_date( 'd M Y', strtotime( $date ) ) : '...';
			};
			$summary[] = __( 'Travel date', 'adventure-treks' ) . ': ' . $fmt( $filters['date_from'] ) . ' - ' . $fmt( $filters['date_to'] );
		}
		if ( $filters['search'] ) {
			$summary[] = __( 'Search', 'adventure-treks' ) . ': ' . $filters['search'];
		}
		if ( $filters['trash'] ) {
			$summary[] = __( 'Trash', 'adventure-treks' );
		}

		$pdf = new \AdventureTreks\Includes\BookingsPdf(
			array(
				$filters['trash'] ? __( 'Trek Bookings (Trash)', 'adventure-treks' ) : __( 'Trek Bookings', 'adventure-treks' ),
				implode( '  |  ', $summary ),
			),
			array(
				array( '#', 8, 'C' ),
				array( __( 'Trek', 'adventure-treks' ), 48, 'L' ),
				array( __( 'Customer Name', 'adventure-treks' ), 40, 'L' ),
				array( __( 'Phone', 'adventure-treks' ), 30, 'L' ),
				array( __( 'Departure City', 'adventure-treks' ), 30, 'L' ),
				array( __( 'Pickup Point', 'adventure-treks' ), 43, 'L' ),
				array( __( 'Total Seats', 'adventure-treks' ), 20, 'C' ),
				array( __( 'Payment Status', 'adventure-treks' ), 28, 'C' ),
				array( __( 'Balance Amount', 'adventure-treks' ), 30, 'R' ),
			)
		);
		$pdf->AddPage();

		$total_seats   = 0;
		$total_balance = 0.0;
		$n             = 0;
		foreach ( $bookings as $booking ) {
			++$n;
			$cancelled = 'cancelled' === $booking->status;
			// Only "pending" / "paid" are tracked, so an unpaid booking owes its full amount. Cancelled bookings owe nothing.
			$balance = ( 'paid' === $booking->payment_status || $cancelled ) ? 0.0 : (float) $booking->total_amount;
			if ( ! $cancelled ) {
				$total_seats += (int) $booking->seats;
			}
			$total_balance += $balance;

			$pdf->row(
				array(
					(string) $n,
					get_the_title( $booking->trek_id ),
					$booking->cust_name,
					$booking->cust_phone,
					isset( $cities[ $booking->city_id ] ) ? $cities[ $booking->city_id ]->city_name : '-',
					'' !== $booking->pickup_point ? $booking->pickup_point : '-',
					(string) (int) $booking->seats,
					$cancelled ? __( 'Cancelled', 'adventure-treks' ) : ucfirst( $booking->payment_status ),
					AdminController::format_price( $balance ),
				),
				0 === $n % 2
			);
		}

		if ( ! $n ) {
			$pdf->row( array( '', __( 'No bookings found for the selected filters.', 'adventure-treks' ) ) );
		} else {
			$pdf->row(
				array( '', __( 'Total', 'adventure-treks' ), '', '', '', '', (string) $total_seats, '', AdminController::format_price( $total_balance ) ),
				false,
				true
			);
		}

		if ( ob_get_length() ) {
			ob_end_clean();
		}
		$pdf->Output( 'D', 'trek-bookings-' . gmdate( 'Y-m-d' ) . '.pdf' );
		exit;
	}
	/**
	 * Register submenu.
	 */
	public function register_menu() {
		$hook = add_submenu_page(
			'edit.php?post_type=adventure_trek',
			__( 'Bookings', 'adventure-treks' ),
			__( 'Bookings', 'adventure-treks' ),
			'edit_posts',
			'at-bookings',
			array( $this, 'render_page' )
		);

		if ( $hook ) {
			add_action( 'load-' . $hook, array( $this, 'register_screen_options' ) );
		}
	}

	/**
	 * Enqueue CSS/JS for the bookings admin screen only.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( 'at-bookings' !== $page ) {
			return;
		}

		// Reuse the shared modal/form styling already used by the trek meta boxes.
		wp_enqueue_style(
			'at-admin-departures-css',
			ADVENTURE_TREKS_URL . 'assets/admin/css/admin-departures.css',
			array(),
			ADVENTURE_TREKS_VERSION
		);

		wp_enqueue_style(
			'at-admin-bookings-css',
			ADVENTURE_TREKS_URL . 'assets/admin/css/admin-bookings.css',
			array( 'at-admin-departures-css' ),
			ADVENTURE_TREKS_VERSION
		);

		wp_enqueue_style( 'flatpickr-css', ADVENTURE_TREKS_URL . 'assets/vendor/flatpickr/flatpickr.min.css', array(), '4.6.13' );
		wp_enqueue_script( 'flatpickr-js', ADVENTURE_TREKS_URL . 'assets/vendor/flatpickr/flatpickr.min.js', array(), '4.6.13', true );

		wp_enqueue_script(
			'at-admin-bookings-js',
			ADVENTURE_TREKS_URL . 'assets/admin/js/admin-bookings.js',
			array( 'flatpickr-js' ),
			ADVENTURE_TREKS_VERSION . '.' . filemtime( ADVENTURE_TREKS_PATH . 'assets/admin/js/admin-bookings.js' ),
			true
		);

		wp_localize_script(
			'at-admin-bookings-js',
			'at_bookings_obj',
			array(
				'ajax_url'             => admin_url( 'admin-ajax.php' ),
				'details_nonce'        => wp_create_nonce( 'at_bookings_nonce_action' ),
				'cities_nonce'         => wp_create_nonce( 'at_departures_nonce_action' ),
				'dates_nonce'          => wp_create_nonce( 'at_dates_nonce_action' ),
				'public_pricing_nonce' => wp_create_nonce( 'at_booking_nonce_action' ),
				'currency'             => get_option( 'at_currency_symbol', '$' ),
				'price_format'         => \AdventureTreks\Admin\Controllers\AdminController::get_price_format(),
			)
		);
	}

	/**
	 * Render page: list table, or the Add/Edit form depending on the requested action.
	 */
	public function render_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';

		if ( in_array( $action, array( 'add', 'edit' ), true ) ) {
			$this->render_form( $action );
			return;
		}

		$table = new Bookings_List_Table();
		$table->prepare_items();
		include ADVENTURE_TREKS_PATH . 'admin/views/bookings-list.php';
	}

	/**
	 * Render the Add/Edit booking form screen.
	 *
	 * @param string $mode 'add' or 'edit'.
	 * @return void
	 */
	private function render_form( $mode ) {
		global $wpdb;

		$booking = null;

		if ( 'edit' === $mode ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$booking_id = isset( $_GET['booking'] ) ? absint( wp_unslash( $_GET['booking'] ) ) : 0;
			if ( $booking_id ) {
				// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}at_bookings WHERE id = %d", $booking_id ) );
				// phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			}

			if ( ! $booking ) {
				wp_die( esc_html__( 'Booking not found.', 'adventure-treks' ) );
			}
		}

		$treks = get_posts(
			array(
				'post_type'      => 'adventure_trek',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		include ADVENTURE_TREKS_PATH . 'admin/views/booking-form.php';
	}

	/**
	 * Handle Add/Edit booking form submission (admin-post.php).
	 *
	 * @return void
	 */
	public function handle_save_booking() {
		if ( ! isset( $_POST['at_booking_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['at_booking_nonce'] ) ), 'at_save_booking' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'adventure-treks' ) );
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'adventure-treks' ) );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'at_bookings';

		$booking_id      = isset( $_POST['booking_id'] ) ? absint( wp_unslash( $_POST['booking_id'] ) ) : 0;
		$trek_id         = isset( $_POST['trek_id'] ) ? absint( wp_unslash( $_POST['trek_id'] ) ) : 0;
		$city_id         = isset( $_POST['city_id'] ) ? absint( wp_unslash( $_POST['city_id'] ) ) : 0;
		$date_id         = isset( $_POST['date_id'] ) ? absint( wp_unslash( $_POST['date_id'] ) ) : 0;
		$cust_name       = isset( $_POST['cust_name'] ) ? sanitize_text_field( wp_unslash( $_POST['cust_name'] ) ) : '';
		$cust_email      = isset( $_POST['cust_email'] ) ? sanitize_email( wp_unslash( $_POST['cust_email'] ) ) : '';
		$cust_phone      = isset( $_POST['cust_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['cust_phone'] ) ) : '';
		$num_adults      = isset( $_POST['num_adults'] ) ? absint( wp_unslash( $_POST['num_adults'] ) ) : 0;
		$num_children    = isset( $_POST['num_children'] ) ? absint( wp_unslash( $_POST['num_children'] ) ) : 0;
		$pickup_point    = isset( $_POST['pickup_point'] ) ? sanitize_text_field( wp_unslash( $_POST['pickup_point'] ) ) : '';
		$transport_type  = isset( $_POST['transport_type'] ) ? sanitize_text_field( wp_unslash( $_POST['transport_type'] ) ) : '';
		$transport_price = isset( $_POST['transport_price'] ) ? floatval( wp_unslash( $_POST['transport_price'] ) ) : 0.00;
		$total_amount    = isset( $_POST['total_amount'] ) ? floatval( wp_unslash( $_POST['total_amount'] ) ) : 0.00;
		$status          = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'pending';
		$payment_status  = isset( $_POST['payment_status'] ) ? sanitize_text_field( wp_unslash( $_POST['payment_status'] ) ) : 'pending';

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$addons_input = isset( $_POST['addons'] ) ? wp_unslash( $_POST['addons'] ) : array();
		$addons_data  = array();
		if ( is_array( $addons_input ) ) {
			foreach ( $addons_input as $addon_name ) {
				$addon_name = sanitize_text_field( $addon_name );
				if ( '' !== $addon_name ) {
					$addons_data[] = $addon_name;
				}
			}
		}
		$addons = ! empty( $addons_data ) ? wp_json_encode( $addons_data ) : '';

		$valid_statuses = array( 'pending', 'confirmed', 'cancelled' );
		if ( ! in_array( $status, $valid_statuses, true ) ) {
			$status = 'pending';
		}

		$valid_payment_statuses = array( 'pending', 'paid' );
		if ( ! in_array( $payment_status, $valid_payment_statuses, true ) ) {
			$payment_status = 'pending';
		}

		$seats = $num_adults + $num_children;

		$redirect_args = array(
			'post_type' => 'adventure_trek',
			'page'      => 'at-bookings',
		);

		if ( empty( $cust_name ) || empty( $cust_email ) || ! $trek_id || ! $city_id || ! $date_id || $seats < 1 ) {
			$redirect_args['action']   = $booking_id ? 'edit' : 'add';
			$redirect_args['booking']  = $booking_id;
			$redirect_args['at_error'] = 1;
			wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'edit.php' ) ) );
			exit;
		}

		// Capture prior state so seat availability can be reconciled after saving.
		$old_booking = null;
		if ( $booking_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$old_booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $booking_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		}

		// Authoritative server-side seat capacity check (never trust the browser alone for this).
		if ( 'cancelled' !== $status ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$target_avail = $wpdb->get_row( $wpdb->prepare( "SELECT available_seats FROM {$wpdb->prefix}at_availability WHERE date_id = %d", $date_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

			// If this same booking already holds seats on the same date, those seats are
			// being released and re-consumed, so they count back toward capacity for this save.
			$own_seats_on_target_date = ( $old_booking && intval( $old_booking->date_id ) === $date_id && 'cancelled' !== $old_booking->status )
				? intval( $old_booking->seats )
				: 0;

			$max_allowed_seats = $target_avail ? ( intval( $target_avail->available_seats ) + $own_seats_on_target_date ) : 0;

			if ( $target_avail && $seats > $max_allowed_seats ) {
				$redirect_args['action']         = $booking_id ? 'edit' : 'add';
				$redirect_args['booking']        = $booking_id;
				$redirect_args['at_seats_error'] = $max_allowed_seats;
				wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'edit.php' ) ) );
				exit;
			}
		}

		$data   = array(
			'trek_id'         => $trek_id,
			'city_id'         => $city_id,
			'date_id'         => $date_id,
			'cust_name'       => $cust_name,
			'cust_email'      => $cust_email,
			'cust_phone'      => $cust_phone,
			'seats'           => $seats,
			'num_adults'      => $num_adults,
			'num_children'    => $num_children,
			'pickup_point'    => $pickup_point,
			'addons'          => $addons,
			'transport_type'  => $transport_type,
			'transport_price' => $transport_price,
			'total_amount'    => $total_amount,
			'status'          => $status,
			'payment_status'  => $payment_status,
		);
		$format = array( '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%f', '%f', '%s', '%s' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		if ( $booking_id ) {
			$wpdb->update( $table_name, $data, array( 'id' => $booking_id ), $format, array( '%d' ) );
		} else {
			$data['created_at'] = current_time( 'mysql' );
			$format[]           = '%s';
			$wpdb->insert( $table_name, $data, $format );
			$booking_id = $wpdb->insert_id;
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery

		// Reconcile seat availability against whatever changed (status, seat count, or date).
		$old_effective_seats = ( $old_booking && 'cancelled' !== $old_booking->status ) ? intval( $old_booking->seats ) : 0;
		$new_effective_seats = ( 'cancelled' !== $status ) ? $seats : 0;

		if ( $old_booking && intval( $old_booking->date_id ) === $date_id ) {
			self::sync_availability( $date_id, $new_effective_seats - $old_effective_seats );
		} else {
			if ( $old_booking ) {
				self::sync_availability( intval( $old_booking->date_id ), -$old_effective_seats );
			}
			self::sync_availability( $date_id, $new_effective_seats );
		}

		// Notify the customer when a new booking is created from admin, or when an existing booking's status changes.
		if ( ! $old_booking || $old_booking->status !== $status ) {
			$this->send_status_update_email( $booking_id, $trek_id, $city_id, $date_id, $cust_name, $cust_email, $seats, $num_adults, $num_children, $pickup_point, $transport_type, $transport_price, $total_amount, $status );
		}

		$redirect_args['at_saved'] = 1;
		wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'edit.php' ) ) );
		exit;
	}

	/**
	 * Email the customer when an admin changes a booking's status (e.g. Pending -> Confirmed).
	 *
	 * @param int    $booking_id   Booking ID.
	 * @param int    $trek_id      Trek post ID.
	 * @param int    $city_id      Departure city ID.
	 * @param int    $date_id      Departure date ID.
	 * @param string $cust_name    Customer name.
	 * @param string $cust_email   Customer email.
	 * @param int    $seats        Total seats booked.
	 * @param int    $num_adults   Adult count.
	 * @param int    $num_children Children count.
	 * @param string $pickup_point    Pickup point label, if any.
	 * @param string $transport_type  Selected Transportation Type label, if any.
	 * @param float  $transport_price Additional price of the selected Transportation Type.
	 * @param float  $total_amount    Booking total amount.
	 * @param string $status          New status: pending, confirmed, or cancelled.
	 * @return void
	 */
	private function send_status_update_email( $booking_id, $trek_id, $city_id, $date_id, $cust_name, $cust_email, $seats, $num_adults, $num_children, $pickup_point, $transport_type, $transport_price, $total_amount, $status ) {
		if ( empty( $cust_email ) ) {
			return;
		}

		global $wpdb;

		$trek_title = get_the_title( $trek_id );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$city_name = $wpdb->get_var( $wpdb->prepare( "SELECT city_name FROM {$wpdb->prefix}at_departure_cities WHERE id = %d", $city_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$date_val       = $wpdb->get_var( $wpdb->prepare( "SELECT departure_date FROM {$wpdb->prefix}at_departure_dates WHERE id = %d", $date_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$date_formatted = $date_val ? gmdate( 'd M Y', strtotime( $date_val ) ) : '';

		$status_labels = array(
			'pending'   => __( 'Pending Confirmation', 'adventure-treks' ),
			'confirmed' => __( 'Confirmed', 'adventure-treks' ),
			'cancelled' => __( 'Cancelled', 'adventure-treks' ),
		);
		$status_label  = isset( $status_labels[ $status ] ) ? $status_labels[ $status ] : ucfirst( $status );

		$intro_messages = array(
			'pending'   => __( 'Thank you for booking your adventure with us! Our team is currently reviewing your booking details, and we will confirm it shortly. Stay tuned!', 'adventure-treks' ),
			'confirmed' => __( 'Great news! Our team has reviewed and confirmed your booking. Get ready for your exciting adventure!', 'adventure-treks' ),
			'cancelled' => __( 'Your booking has been successfully cancelled. If you have any questions or need further assistance, please feel free to reach out to our team.', 'adventure-treks' ),
		);
		$intro          = isset( $intro_messages[ $status ] ) ? $intro_messages[ $status ] : __( 'Your reservation status has been updated.', 'adventure-treks' );

		/* translators: %s: new booking status label. */
		$subject = sprintf( __( 'Booking %s', 'adventure-treks' ), $status_label );

		$details_rows = array(
			array(
				'label' => __( 'Booking ID', 'adventure-treks' ),
				'value' => self::format_booking_ref( $booking_id ),
			),
			array(
				'label' => __( 'Trek', 'adventure-treks' ),
				'value' => $trek_title,
			),
			array(
				'label' => __( 'Departure City', 'adventure-treks' ),
				'value' => $city_name,
			),
			array(
				'label' => __( 'Departure Date', 'adventure-treks' ),
				'value' => $date_formatted,
			),
			array(
				/* translators: 1: total seats, 2: adult count, 3: children count. */
				'label' => __( 'Seats Booked', 'adventure-treks' ),
				'value' => sprintf( '%1$d (Adults: %2$d, Children: %3$d)', $seats, $num_adults, $num_children ),
			),
			array(
				'label' => __( 'Pickup Point', 'adventure-treks' ),
				'value' => $pickup_point,
			),
			array(
				'label' => __( 'Transportation Type', 'adventure-treks' ),
				'value' => $transport_type ? $transport_type . ( $transport_price > 0 ? ' (+' . \AdventureTreks\Admin\Controllers\AdminController::format_price( $transport_price ) . ')' : '' ) : '',
			),
			array(
				'label' => __( 'Total Amount', 'adventure-treks' ),
				'value' => \AdventureTreks\Admin\Controllers\AdminController::format_price( (float) $total_amount ),
			),
			array(
				'label' => __( 'Status', 'adventure-treks' ),
				'value' => $status_label,
			),
		);

		$message = \AdventureTreks\Includes\Plugin::render_email_html(
			/* translators: %s: customer name. */
			sprintf( __( 'Hello, %s!', 'adventure-treks' ), $cust_name ),
			$intro,
			$details_rows,
			__( 'View Trek Details', 'adventure-treks' ),
			get_permalink( $trek_id )
		);

		$from_name  = get_option( 'at_from_name', get_bloginfo( 'name' ) );
		$from_email = get_option( 'at_booking_email', get_option( 'admin_email' ) );

		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'From: %s <%s>', $from_name, $from_email ),
		);

		wp_mail( $cust_email, $subject, $message, $headers );
	}

	/**
	 * AJAX: Get full details for a single booking (used by the "View" popup).
	 *
	 * @return void
	 */
	public function ajax_get_booking_details() {
		check_ajax_referer( 'at_bookings_nonce_action', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		$booking_id = isset( $_GET['booking_id'] ) ? absint( wp_unslash( $_GET['booking_id'] ) ) : 0;
		if ( ! $booking_id ) {
			wp_send_json_error( array( 'message' => 'Invalid booking ID' ) );
		}

		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$booking = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}at_bookings WHERE id = %d", $booking_id ), ARRAY_A );
		if ( ! $booking ) {
			wp_send_json_error( array( 'message' => 'Booking not found' ) );
		}

		$booking['booking_ref']    = self::format_booking_ref( $booking['id'] );
		$booking['trek_title']     = get_the_title( $booking['trek_id'] );
		$booking['city_name']      = $wpdb->get_var( $wpdb->prepare( "SELECT city_name FROM {$wpdb->prefix}at_departure_cities WHERE id = %d", $booking['city_id'] ) );
		$booking['departure_date'] = $wpdb->get_var( $wpdb->prepare( "SELECT departure_date FROM {$wpdb->prefix}at_departure_dates WHERE id = %d", $booking['date_id'] ) );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

		$addons              = ! empty( $booking['addons'] ) ? json_decode( $booking['addons'], true ) : array();
		$booking['addons']   = is_array( $addons ) ? $addons : array();
		$booking['currency'] = get_option( 'at_currency_symbol', '$' );

		wp_send_json_success( $booking );
	}
}
