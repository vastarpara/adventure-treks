<?php
/**
 * Add/Edit Booking admin form view.
 *
 * @package    AdventureTreks
 * @subpackage AdventureTreks/Admin/Views
 * @author     Nilesh Vastarpara
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$adventure_treks_is_edit  = ! empty( $booking );
$adventure_treks_currency = get_option( 'at_currency_symbol', '₹' );
?>
<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php echo $adventure_treks_is_edit ? esc_html__( 'Edit Booking', 'adventure-treks' ) : esc_html__( 'Add New Booking', 'adventure-treks' ); ?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=adventure_trek&page=at-bookings' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Back to Bookings', 'adventure-treks' ); ?></a>
	<hr class="wp-header-end">

	<?php if ( $adventure_treks_is_edit ) : ?>
		<p><strong><?php esc_html_e( 'Booking ID:', 'adventure-treks' ); ?></strong> <?php echo esc_html( \AdventureTreks\Admin\Controllers\TrekBookingsController::format_booking_ref( $booking->id ) ); ?></p>
	<?php endif; ?>

	<?php
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['at_seats_error'] ) ) :
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$adventure_treks_max_seats = absint( wp_unslash( $_GET['at_seats_error'] ) );
		?>
		<div class="notice notice-error is-dismissible">
			<p>
				<?php
				if ( $adventure_treks_max_seats > 0 ) {
					printf(
						/* translators: %d: maximum number of seats available for the selected date. */
						esc_html__( 'Only %d seat(s) available for the selected departure date. Please reduce Adults/Children or choose another date.', 'adventure-treks' ),
						(int) $adventure_treks_max_seats
					);
				} else {
					esc_html_e( 'No seats are available for the selected departure date. Please choose another date.', 'adventure-treks' );
				}
				?>
			</p>
		</div>
		<?php
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	elseif ( isset( $_GET['at_error'] ) ) :
		?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Please fill in all the required fields before saving the booking.', 'adventure-treks' ); ?></p></div>
		<?php
	endif;
	?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="at_booking_form">
		<?php wp_nonce_field( 'at_save_booking', 'at_booking_nonce' ); ?>
		<input type="hidden" name="action" value="at_save_booking" />
		<input type="hidden" name="booking_id" value="<?php echo esc_attr( $adventure_treks_is_edit ? $booking->id : 0 ); ?>" />
		<input type="hidden" name="total_amount" id="at_b_amount" value="<?php echo esc_attr( $adventure_treks_is_edit ? $booking->total_amount : '0.00' ); ?>" />

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="at_b_trek"><?php esc_html_e( 'Trek', 'adventure-treks' ); ?> <span class="at-required">*</span></label></th>
					<td>
						<select name="trek_id" id="at_b_trek" class="regular-text" required>
							<option value=""><?php esc_html_e( '-- Select Trek --', 'adventure-treks' ); ?></option>
							<?php foreach ( $treks as $adventure_treks_trek ) : ?>
								<option value="<?php echo esc_attr( $adventure_treks_trek->ID ); ?>" <?php selected( $adventure_treks_is_edit ? $booking->trek_id : 0, $adventure_treks_trek->ID ); ?>>
									<?php echo esc_html( $adventure_treks_trek->post_title ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="at_b_city"><?php esc_html_e( 'Departure City', 'adventure-treks' ); ?> <span class="at-required">*</span></label></th>
					<td>
						<select name="city_id" id="at_b_city" class="regular-text" data-selected="<?php echo esc_attr( $adventure_treks_is_edit ? $booking->city_id : '' ); ?>" required>
							<option value=""><?php esc_html_e( '-- Select Trek First --', 'adventure-treks' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="at_b_date"><?php esc_html_e( 'Travel Date', 'adventure-treks' ); ?> <span class="at-required">*</span></label></th>
					<td>
						<select name="date_id" id="at_b_date" class="regular-text" data-selected="<?php echo esc_attr( $adventure_treks_is_edit ? $booking->date_id : '' ); ?>" required>
							<option value=""><?php esc_html_e( '-- Select City First --', 'adventure-treks' ); ?></option>
						</select>
					</td>
				</tr>
				<tr id="at_b_pickup_row" style="display:none;">
					<th scope="row"><label for="at_b_pickup"><?php esc_html_e( 'Pickup Point', 'adventure-treks' ); ?></label></th>
					<td>
						<select name="pickup_point" id="at_b_pickup" class="regular-text" data-selected="<?php echo esc_attr( $adventure_treks_is_edit ? $booking->pickup_point : '' ); ?>">
							<option value=""><?php esc_html_e( '-- Select Pickup Point --', 'adventure-treks' ); ?></option>
						</select>
					</td>
				</tr>
				<tr id="at_b_transport_row" style="display:none;">
					<th scope="row"><label for="at_b_transport"><?php esc_html_e( 'Transportation Type', 'adventure-treks' ); ?></label></th>
					<td>
						<select name="transport_type" id="at_b_transport" class="regular-text" data-selected="<?php echo esc_attr( $adventure_treks_is_edit ? $booking->transport_type : '' ); ?>">
							<option value=""><?php esc_html_e( '-- Select Transportation Type --', 'adventure-treks' ); ?></option>
						</select>
						<input type="hidden" name="transport_price" id="at_b_transport_price" value="<?php echo esc_attr( $adventure_treks_is_edit ? $booking->transport_price : '0.00' ); ?>" />
					</td>
				</tr>
				<tr id="at_b_addons_row" style="display:none;">
					<th scope="row"><?php esc_html_e( 'Add-ons', 'adventure-treks' ); ?></th>
					<td>
						<div id="at_b_addons_container" data-selected="<?php echo esc_attr( $adventure_treks_is_edit && ! empty( $booking->addons ) ? $booking->addons : '[]' ); ?>"></div>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="at_b_name"><?php esc_html_e( 'Customer Name', 'adventure-treks' ); ?> <span class="at-required">*</span></label></th>
					<td><input type="text" name="cust_name" id="at_b_name" class="regular-text" required value="<?php echo esc_attr( $adventure_treks_is_edit ? $booking->cust_name : '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="at_b_email"><?php esc_html_e( 'Email Address', 'adventure-treks' ); ?> <span class="at-required">*</span></label></th>
					<td><input type="email" name="cust_email" id="at_b_email" class="regular-text" required value="<?php echo esc_attr( $adventure_treks_is_edit ? $booking->cust_email : '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="at_b_phone"><?php esc_html_e( 'Phone Number', 'adventure-treks' ); ?> <span class="at-required">*</span></label></th>
					<td><input type="text" name="cust_phone" id="at_b_phone" class="regular-text" required value="<?php echo esc_attr( $adventure_treks_is_edit ? $booking->cust_phone : '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="at_b_adults"><?php esc_html_e( 'Adults', 'adventure-treks' ); ?> <span class="at-required">*</span></label></th>
					<td><input type="number" name="num_adults" id="at_b_adults" min="1" required value="<?php echo esc_attr( $adventure_treks_is_edit ? $booking->num_adults : 1 ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="at_b_children"><?php esc_html_e( 'Children', 'adventure-treks' ); ?></label></th>
					<td><input type="number" name="num_children" id="at_b_children" min="0" value="<?php echo esc_attr( $adventure_treks_is_edit ? $booking->num_children : 0 ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Price Breakdown', 'adventure-treks' ); ?></th>
					<td>
						<div id="at_b_price_breakdown" class="at-price-breakdown-box">
							<p class="description"><?php esc_html_e( 'Select a trek, city, and date to calculate pricing automatically.', 'adventure-treks' ); ?></p>
						</div>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="at_b_status"><?php esc_html_e( 'Status', 'adventure-treks' ); ?> <span class="at-required">*</span></label></th>
					<td>
						<select name="status" id="at_b_status" required>
							<?php
							$adventure_treks_status_options = array(
								'pending'   => __( 'Pending', 'adventure-treks' ),
								'confirmed' => __( 'Confirmed', 'adventure-treks' ),
								'cancelled' => __( 'Cancelled', 'adventure-treks' ),
							);
							$adventure_treks_current_status = $adventure_treks_is_edit ? $booking->status : 'pending';
							foreach ( $adventure_treks_status_options as $adventure_treks_status_val => $adventure_treks_status_label ) :
								?>
								<option value="<?php echo esc_attr( $adventure_treks_status_val ); ?>" <?php selected( $adventure_treks_current_status, $adventure_treks_status_val ); ?>><?php echo esc_html( $adventure_treks_status_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="at_b_payment_status"><?php esc_html_e( 'Payment Status', 'adventure-treks' ); ?> <span class="at-required">*</span></label></th>
					<td>
						<select name="payment_status" id="at_b_payment_status" required>
							<?php
							$adventure_treks_payment_status_options = array(
								'pending' => __( 'Pending', 'adventure-treks' ),
								'paid'    => __( 'Paid', 'adventure-treks' ),
							);
							$adventure_treks_current_payment_status = $adventure_treks_is_edit ? $booking->payment_status : 'pending';
							foreach ( $adventure_treks_payment_status_options as $adventure_treks_payment_status_val => $adventure_treks_payment_status_label ) :
								?>
								<option value="<?php echo esc_attr( $adventure_treks_payment_status_val ); ?>" <?php selected( $adventure_treks_current_payment_status, $adventure_treks_payment_status_val ); ?>><?php echo esc_html( $adventure_treks_payment_status_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</tbody>
		</table>

		<?php submit_button( $adventure_treks_is_edit ? __( 'Update Booking', 'adventure-treks' ) : __( 'Add Booking', 'adventure-treks' ) ); ?>
	</form>
</div>
