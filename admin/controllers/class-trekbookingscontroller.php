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
	}

	/**
	 * Register submenu.
	 */
	public function register_menu() {
		add_submenu_page(
			'edit.php?post_type=adventure_trek',
			__( 'Bookings', 'adventure-treks' ),
			__( 'Bookings', 'adventure-treks' ),
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
		include ADVENTURE_TREKS_PATH . 'admin/views/bookings-list.php';
	}
}
