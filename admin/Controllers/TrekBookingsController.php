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
			'id'           => 'ID',
			'cust_name'    => 'Customer',
			'trek_title'   => 'Trek',
			'city_name'    => 'Departure City',
			'date_val'     => 'Travel Date',
			'seats'        => 'Seats',
			'addons'       => 'Add-ons',
			'total_amount' => 'Amount',
			'created_at'   => 'Booked On',
			'status'       => 'Status',
		);
	}

	/**
	 * Sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'id'           => array( 'id', false ),
			'created_at'   => array( 'created_at', true ),
			'date_val'     => array( 'date_val', false ),
		);
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
			case 'id':
			case 'seats':
				return esc_html( $item->$column_name );
			case 'total_amount':
				$currency = get_option( 'at_currency_symbol', '₹' );
				return esc_html( $currency . ' ' . $item->$column_name );
			case 'status':
				$color = $item->status === 'confirmed' ? 'green' : 'red';
				return '<span style="color:' . esc_attr( $color ) . ';font-weight:bold;">' . esc_html( ucfirst( $item->$column_name ) ) . '</span>';
			case 'created_at':
				return esc_html( gmdate( 'd M Y, h:i A', strtotime( $item->created_at ) ) );
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
		return $name;
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

		$order = ( strtoupper( $order ) === 'ASC' ) ? 'ASC' : 'DESC';

		$table_name = $wpdb->prefix . 'at_bookings';

		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		// We cannot easily order by date_val directly because date_val is in another table, but for simplicity, we will just order by the booking ID or created_at. If ordered by date_val, we ignore it and fallback to created_at to avoid complex JOINs for this simple admin panel.
		if ( 'date_val' === $orderby ) {
			$orderby = 'created_at';
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
		$this->items = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $table_name ORDER BY {$orderby} {$order} LIMIT %d, %d",
				$offset,
				$per_page
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}
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
	}

	/**
	 * Register submenu.
	 */
	public function register_menu() {
		add_submenu_page(
			'edit.php?post_type=adventure_trek',
			'Bookings',
			'Bookings',
			'edit_posts',
			'at-bookings',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render page.
	 */
	public function render_page() {
		$table = new Bookings_List_Table();
		$table->prepare_items();
		include ADVENTURE_TREKS_PATH . 'admin/Views/bookings-list.php';
	}
}
