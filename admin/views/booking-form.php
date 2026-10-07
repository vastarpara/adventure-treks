<?php
/**
 * Add/Edit Booking admin form view.
 *
 * @package    TrekPilot
 * @subpackage TrekPilot/Admin/Views
 * @author     Nilesh Vastarpara
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$trekpilot_is_edit  = ! empty( $booking );
$trekpilot_currency = get_option( 'trekpilot_currency_symbol', '$' );
?>
<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php echo $trekpilot_is_edit ? esc_html__( 'Edit Booking', 'trekpilot' ) : esc_html__( 'Add New Booking', 'trekpilot' ); ?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=trekpilot_trek&page=trekpilot-bookings' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Back to Bookings', 'trekpilot' ); ?></a>
	<hr class="wp-header-end">

	<?php if ( $trekpilot_is_edit ) : ?>
		<p><strong><?php esc_html_e( 'Booking ID:', 'trekpilot' ); ?></strong> <?php echo esc_html( \TrekPilot\Admin\Controllers\TrekBookingsController::format_booking_ref( $booking->id ) ); ?></p>
	<?php endif; ?>

	<?php
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['trekpilot_seats_error'] ) ) :
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$trekpilot_max_seats = absint( wp_unslash( $_GET['trekpilot_seats_error'] ) );
		?>
		<div class="notice notice-error is-dismissible">
			<p>
				<?php
				if ( $trekpilot_max_seats > 0 ) {
					printf(
						/* translators: %d: maximum number of seats available for the selected date. */
						esc_html__( 'Only %d seat(s) available for the selected departure date. Please reduce Adults/Children or choose another date.', 'trekpilot' ),
						(int) $trekpilot_max_seats
					);
				} else {
					esc_html_e( 'No seats are available for the selected departure date. Please choose another date.', 'trekpilot' );
				}
				?>
			</p>
		</div>
		<?php
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	elseif ( isset( $_GET['trekpilot_error'] ) ) :
		?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Please fill in all the required fields before saving the booking.', 'trekpilot' ); ?></p></div>
		<?php
	endif;
	?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="trekpilot_booking_form">
		<?php wp_nonce_field( 'trekpilot_save_booking', 'trekpilot_booking_nonce' ); ?>
		<input type="hidden" name="action" value="trekpilot_save_booking" />
		<input type="hidden" name="booking_id" value="<?php echo esc_attr( $trekpilot_is_edit ? $booking->id : 0 ); ?>" />
		<input type="hidden" name="total_amount" id="trekpilot_b_amount" value="<?php echo esc_attr( $trekpilot_is_edit ? $booking->total_amount : '0.00' ); ?>" />

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="trekpilot_b_trek"><?php esc_html_e( 'Trek', 'trekpilot' ); ?> <span class="trekpilot-required">*</span></label></th>
					<td>
						<select name="trek_id" id="trekpilot_b_trek" class="regular-text" required>
							<option value=""><?php esc_html_e( '-- Select Trek --', 'trekpilot' ); ?></option>
							<?php foreach ( $treks as $trekpilot_trek ) : ?>
								<option value="<?php echo esc_attr( $trekpilot_trek->ID ); ?>" <?php selected( $trekpilot_is_edit ? $booking->trek_id : 0, $trekpilot_trek->ID ); ?>>
									<?php echo esc_html( $trekpilot_trek->post_title ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="trekpilot_b_city"><?php esc_html_e( 'Departure City', 'trekpilot' ); ?> <span class="trekpilot-required">*</span></label></th>
					<td>
						<select name="city_id" id="trekpilot_b_city" class="regular-text" data-selected="<?php echo esc_attr( $trekpilot_is_edit ? $booking->city_id : '' ); ?>" required>
							<option value=""><?php esc_html_e( '-- Select Trek First --', 'trekpilot' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="trekpilot_b_date"><?php esc_html_e( 'Travel Date', 'trekpilot' ); ?> <span class="trekpilot-required">*</span></label></th>
					<td>
						<select name="date_id" id="trekpilot_b_date" class="regular-text" data-selected="<?php echo esc_attr( $trekpilot_is_edit ? $booking->date_id : '' ); ?>" required>
							<option value=""><?php esc_html_e( '-- Select City First --', 'trekpilot' ); ?></option>
						</select>
					</td>
				</tr>
				<tr id="trekpilot_b_pickup_row" style="display:none;">
					<th scope="row"><label for="trekpilot_b_pickup"><?php esc_html_e( 'Pickup Point', 'trekpilot' ); ?></label></th>
					<td>
						<select name="pickup_point" id="trekpilot_b_pickup" class="regular-text" data-selected="<?php echo esc_attr( $trekpilot_is_edit ? $booking->pickup_point : '' ); ?>">
							<option value=""><?php esc_html_e( '-- Select Pickup Point --', 'trekpilot' ); ?></option>
						</select>
					</td>
				</tr>
				<tr id="trekpilot_b_transport_row" style="display:none;">
					<th scope="row"><label for="trekpilot_b_transport"><?php esc_html_e( 'Transportation Type', 'trekpilot' ); ?></label></th>
					<td>
						<select name="transport_type" id="trekpilot_b_transport" class="regular-text" data-selected="<?php echo esc_attr( $trekpilot_is_edit ? $booking->transport_type : '' ); ?>">
							<option value=""><?php esc_html_e( '-- Select Transportation Type --', 'trekpilot' ); ?></option>
						</select>
						<input type="hidden" name="transport_price" id="trekpilot_b_transport_price" value="<?php echo esc_attr( $trekpilot_is_edit ? $booking->transport_price : '0.00' ); ?>" />
					</td>
				</tr>
				<tr id="trekpilot_b_addons_row" style="display:none;">
					<th scope="row"><?php esc_html_e( 'Add-ons', 'trekpilot' ); ?></th>
					<td>
						<div id="trekpilot_b_addons_container" data-selected="<?php echo esc_attr( $trekpilot_is_edit && ! empty( $booking->addons ) ? $booking->addons : '[]' ); ?>"></div>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="trekpilot_b_name"><?php esc_html_e( 'Customer Name', 'trekpilot' ); ?> <span class="trekpilot-required">*</span></label></th>
					<td><input type="text" name="cust_name" id="trekpilot_b_name" class="regular-text" required value="<?php echo esc_attr( $trekpilot_is_edit ? $booking->cust_name : '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="trekpilot_b_email"><?php esc_html_e( 'Email Address', 'trekpilot' ); ?> <span class="trekpilot-required">*</span></label></th>
					<td><input type="email" name="cust_email" id="trekpilot_b_email" class="regular-text" required value="<?php echo esc_attr( $trekpilot_is_edit ? $booking->cust_email : '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="trekpilot_b_phone"><?php esc_html_e( 'Phone Number', 'trekpilot' ); ?> <span class="trekpilot-required">*</span></label></th>
					<td><input type="text" name="cust_phone" id="trekpilot_b_phone" class="regular-text" required value="<?php echo esc_attr( $trekpilot_is_edit ? $booking->cust_phone : '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="trekpilot_b_adults"><?php esc_html_e( 'Adults', 'trekpilot' ); ?> <span class="trekpilot-required">*</span></label></th>
					<td><input type="number" name="num_adults" id="trekpilot_b_adults" min="1" required value="<?php echo esc_attr( $trekpilot_is_edit ? $booking->num_adults : 1 ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="trekpilot_b_children"><?php esc_html_e( 'Children', 'trekpilot' ); ?></label></th>
					<td><input type="number" name="num_children" id="trekpilot_b_children" min="0" value="<?php echo esc_attr( $trekpilot_is_edit ? $booking->num_children : 0 ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Price Breakdown', 'trekpilot' ); ?></th>
					<td>
						<div id="trekpilot_b_price_breakdown" class="trekpilot-price-breakdown-box">
							<p class="description"><?php esc_html_e( 'Select a trek, city, and date to calculate pricing automatically.', 'trekpilot' ); ?></p>
						</div>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="trekpilot_b_status"><?php esc_html_e( 'Status', 'trekpilot' ); ?> <span class="trekpilot-required">*</span></label></th>
					<td>
						<select name="status" id="trekpilot_b_status" required>
							<?php
							$trekpilot_status_options = array(
								'pending'   => __( 'Pending', 'trekpilot' ),
								'confirmed' => __( 'Confirmed', 'trekpilot' ),
								'cancelled' => __( 'Cancelled', 'trekpilot' ),
							);
							$trekpilot_current_status = $trekpilot_is_edit ? $booking->status : 'pending';
							foreach ( $trekpilot_status_options as $trekpilot_status_val => $trekpilot_status_label ) :
								?>
								<option value="<?php echo esc_attr( $trekpilot_status_val ); ?>" <?php selected( $trekpilot_current_status, $trekpilot_status_val ); ?>><?php echo esc_html( $trekpilot_status_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="trekpilot_b_payment_status"><?php esc_html_e( 'Payment Status', 'trekpilot' ); ?> <span class="trekpilot-required">*</span></label></th>
					<td>
						<select name="payment_status" id="trekpilot_b_payment_status" required>
							<?php
							$trekpilot_payment_status_options = array(
								'pending' => __( 'Pending', 'trekpilot' ),
								'paid'    => __( 'Paid', 'trekpilot' ),
							);
							$trekpilot_current_payment_status = $trekpilot_is_edit ? $booking->payment_status : 'pending';
							foreach ( $trekpilot_payment_status_options as $trekpilot_payment_status_val => $trekpilot_payment_status_label ) :
								?>
								<option value="<?php echo esc_attr( $trekpilot_payment_status_val ); ?>" <?php selected( $trekpilot_current_payment_status, $trekpilot_payment_status_val ); ?>><?php echo esc_html( $trekpilot_payment_status_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</tbody>
		</table>

		<?php submit_button( $trekpilot_is_edit ? __( 'Update Booking', 'trekpilot' ) : __( 'Add Booking', 'trekpilot' ) ); ?>
	</form>
</div>
