<?php
/**
 * Bookings List View.
 *
 * @package TrekPilot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Trek Bookings', 'trekpilot' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=trekpilot_trek&page=trekpilot-bookings&action=add' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'trekpilot' ); ?></a>
	<hr class="wp-header-end">

	<?php
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['trekpilot_saved'] ) ) :
		?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Booking saved successfully.', 'trekpilot' ); ?></p></div>
		<?php
	endif;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['trekpilot_error'] ) ) :
		?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Please fill in all the required fields before saving the booking.', 'trekpilot' ); ?></p></div>
		<?php
	endif;
	?>

	<div id="poststuff">
		<div id="post-body" class="metabox-holder">
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<?php $table->views(); ?>
					<form method="get">
						<input type="hidden" name="post_type" value="trekpilot_trek" />
						<input type="hidden" name="page" value="trekpilot-bookings" />
						<?php
						// phpcs:ignore WordPress.Security.NonceVerification.Recommended
						if ( isset( $_GET['booking_view'] ) && 'trash' === $_GET['booking_view'] ) :
							?>
							<input type="hidden" name="booking_view" value="trash" />
						<?php endif; ?>
						<?php
						$table->search_box( __( 'Search', 'trekpilot' ), 'search_id' );
						$table->display();
						?>
					</form>
				</div>
			</div>
		</div>
		<br class="clear">
	</div>

	<!-- Booking Details View Modal -->
	<div class="trekpilot-modal-overlay" id="trekpilot_booking_view_modal" style="display:none;">
		<div class="trekpilot-modal-box" style="max-width:600px;">
			<div class="trekpilot-modal-header">
				<h2><?php esc_html_e( 'Booking Details', 'trekpilot' ); ?> - <span id="trekpilot_booking_view_ref"></span></h2>
				<span class="trekpilot-modal-close" id="trekpilot_booking_view_close_btn">&times;</span>
			</div>
			<div class="trekpilot-modal-body">
				<div class="trekpilot-loading-spinner" id="trekpilot_booking_view_loading">
					<span class="spinner is-active"></span> <?php esc_html_e( 'Loading booking details...', 'trekpilot' ); ?>
				</div>
				<table class="widefat striped" id="trekpilot_booking_view_table" style="display:none;">
					<tbody id="trekpilot_booking_view_tbody">
						<!-- Populated via AJAX -->
					</tbody>
				</table>
			</div>
			<div class="trekpilot-modal-footer">
				<button type="button" class="button" id="trekpilot_booking_view_close_btn2"><?php esc_html_e( 'Close', 'trekpilot' ); ?></button>
			</div>
		</div>
	</div>
</div>
