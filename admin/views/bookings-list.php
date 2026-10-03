<?php
/**
 * Bookings List View.
 *
 * @package AdventureTreks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Trek Bookings', 'adventure-treks' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=adventure_trek&page=at-bookings&action=add' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'adventure-treks' ); ?></a>
	<hr class="wp-header-end">

	<?php
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['at_saved'] ) ) :
		?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Booking saved successfully.', 'adventure-treks' ); ?></p></div>
		<?php
	endif;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['at_error'] ) ) :
		?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Please fill in all the required fields before saving the booking.', 'adventure-treks' ); ?></p></div>
		<?php
	endif;
	?>

	<div id="poststuff">
		<div id="post-body" class="metabox-holder">
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<?php $table->views(); ?>
					<form method="get">
						<input type="hidden" name="post_type" value="adventure_trek" />
						<input type="hidden" name="page" value="at-bookings" />
						<?php
						// phpcs:ignore WordPress.Security.NonceVerification.Recommended
						if ( isset( $_GET['booking_view'] ) && 'trash' === $_GET['booking_view'] ) :
							?>
							<input type="hidden" name="booking_view" value="trash" />
						<?php endif; ?>
						<?php
						$table->search_box( __( 'Search', 'adventure-treks' ), 'search_id' );
						$table->display();
						?>
					</form>
				</div>
			</div>
		</div>
		<br class="clear">
	</div>

	<!-- Booking Details View Modal -->
	<div class="at-modal-overlay" id="at_booking_view_modal" style="display:none;">
		<div class="at-modal-box" style="max-width:600px;">
			<div class="at-modal-header">
				<h3><?php esc_html_e( 'Booking Details', 'adventure-treks' ); ?> - <span id="at_booking_view_ref"></span></h3>
				<span class="at-modal-close" id="at_booking_view_close_btn">&times;</span>
			</div>
			<div class="at-modal-body">
				<div class="at-loading-spinner" id="at_booking_view_loading">
					<span class="spinner is-active"></span> <?php esc_html_e( 'Loading booking details...', 'adventure-treks' ); ?>
				</div>
				<table class="widefat striped" id="at_booking_view_table" style="display:none;">
					<tbody id="at_booking_view_tbody">
						<!-- Populated via AJAX -->
					</tbody>
				</table>
			</div>
			<div class="at-modal-footer">
				<button type="button" class="button" id="at_booking_view_close_btn2"><?php esc_html_e( 'Close', 'adventure-treks' ); ?></button>
			</div>
		</div>
	</div>
</div>
