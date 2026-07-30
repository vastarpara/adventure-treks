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

$at_is_edit  = ! empty( $booking );
$at_currency = get_option( 'at_currency_symbol', '₹' );
?>
<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php echo $at_is_edit ? esc_html__( 'Edit Booking', 'adventure-treks' ) : esc_html__( 'Add New Booking', 'adventure-treks' ); ?>
	</h1>
	<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=adventure_trek&page=at-bookings' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Back to Bookings', 'adventure-treks' ); ?></a>
	<hr class="wp-header-end">

	<?php if ( $at_is_edit ) : ?>
		<p><strong><?php esc_html_e( 'Booking ID:', 'adventure-treks' ); ?></strong> <?php echo esc_html( \AdventureTreks\Admin\Controllers\TrekBookingsController::format_booking_ref( $booking->id ) ); ?></p>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="at_booking_form">
		<?php wp_nonce_field( 'at_save_booking', 'at_booking_nonce' ); ?>
		<input type="hidden" name="action" value="at_save_booking" />
		<input type="hidden" name="booking_id" value="<?php echo esc_attr( $at_is_edit ? $booking->id : 0 ); ?>" />

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="at_b_trek"><?php esc_html_e( 'Trek', 'adventure-treks' ); ?> <span class="at-required">*</span></label></th>
					<td>
						<select name="trek_id" id="at_b_trek" class="regular-text" required>
							<option value=""><?php esc_html_e( '-- Select Trek --', 'adventure-treks' ); ?></option>
							<?php foreach ( $treks as $at_trek ) : ?>
								<option value="<?php echo esc_attr( $at_trek->ID ); ?>" <?php selected( $at_is_edit ? $booking->trek_id : 0, $at_trek->ID ); ?>>
									<?php echo esc_html( $at_trek->post_title ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="at_b_city"><?php esc_html_e( 'Departure City', 'adventure-treks' ); ?> <span class="at-required">*</span></label></th>
					<td>
						<select name="city_id" id="at_b_city" class="regular-text" data-selected="<?php echo esc_attr( $at_is_edit ? $booking->city_id : '' ); ?>" required>
							<option value=""><?php esc_html_e( '-- Select Trek First --', 'adventure-treks' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="at_b_date"><?php esc_html_e( 'Travel Date', 'adventure-treks' ); ?> <span class="at-required">*</span></label></th>
					<td>
						<select name="date_id" id="at_b_date" class="regular-text" data-selected="<?php echo esc_attr( $at_is_edit ? $booking->date_id : '' ); ?>" required>
							<option value=""><?php esc_html_e( '-- Select City First --', 'adventure-treks' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="at_b_pickup"><?php esc_html_e( 'Pickup Point', 'adventure-treks' ); ?></label></th>
					<td>
						<select name="pickup_point" id="at_b_pickup" class="regular-text" data-selected="<?php echo esc_attr( $at_is_edit ? $booking->pickup_point : '' ); ?>">
							<option value=""><?php esc_html_e( '-- None --', 'adventure-treks' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Add-ons', 'adventure-treks' ); ?></th>
					<td>
						<div id="at_b_addons_container" data-selected="<?php echo esc_attr( $at_is_edit && ! empty( $booking->addons ) ? $booking->addons : '[]' ); ?>">
							<p class="description"><?php esc_html_e( 'Select a trek and departure city to see available add-ons.', 'adventure-treks' ); ?></p>
						</div>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="at_b_name"><?php esc_html_e( 'Customer Name', 'adventure-treks' ); ?> <span class="at-required">*</span></label></th>
					<td><input type="text" name="cust_name" id="at_b_name" class="regular-text" required value="<?php echo esc_attr( $at_is_edit ? $booking->cust_name : '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="at_b_email"><?php esc_html_e( 'Email Address', 'adventure-treks' ); ?> <span class="at-required">*</span></label></th>
					<td><input type="email" name="cust_email" id="at_b_email" class="regular-text" required value="<?php echo esc_attr( $at_is_edit ? $booking->cust_email : '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="at_b_phone"><?php esc_html_e( 'Phone Number', 'adventure-treks' ); ?> <span class="at-required">*</span></label></th>
					<td><input type="text" name="cust_phone" id="at_b_phone" class="regular-text" required value="<?php echo esc_attr( $at_is_edit ? $booking->cust_phone : '' ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="at_b_adults"><?php esc_html_e( 'Adults', 'adventure-treks' ); ?> <span class="at-required">*</span></label></th>
					<td><input type="number" name="num_adults" id="at_b_adults" min="1" required value="<?php echo esc_attr( $at_is_edit ? $booking->num_adults : 1 ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="at_b_children"><?php esc_html_e( 'Children', 'adventure-treks' ); ?></label></th>
					<td><input type="number" name="num_children" id="at_b_children" min="0" value="<?php echo esc_attr( $at_is_edit ? $booking->num_children : 0 ); ?>" /></td>
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
					<th scope="row"><label for="at_b_amount"><?php esc_html_e( 'Total Amount', 'adventure-treks' ); ?> <span class="at-required">*</span></label></th>
					<td>
						<?php echo esc_html( $at_currency ); ?>
						<input type="number" step="0.01" min="0" name="total_amount" id="at_b_amount" required value="<?php echo esc_attr( $at_is_edit ? $booking->total_amount : '0.00' ); ?>" />
						<p class="description at-field-hint"><?php esc_html_e( 'Auto-calculated from the trek pricing rules above. You may manually override it if needed.', 'adventure-treks' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="at_b_status"><?php esc_html_e( 'Status', 'adventure-treks' ); ?> <span class="at-required">*</span></label></th>
					<td>
						<select name="status" id="at_b_status" required>
							<?php
							$at_status_options = array(
								'pending'   => __( 'Pending', 'adventure-treks' ),
								'confirmed' => __( 'Confirmed', 'adventure-treks' ),
								'cancelled' => __( 'Cancelled', 'adventure-treks' ),
							);
							$at_current_status = $at_is_edit ? $booking->status : 'pending';
							foreach ( $at_status_options as $at_status_val => $at_status_label ) :
								?>
								<option value="<?php echo esc_attr( $at_status_val ); ?>" <?php selected( $at_current_status, $at_status_val ); ?>><?php echo esc_html( $at_status_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="at_b_payment_status"><?php esc_html_e( 'Payment Status', 'adventure-treks' ); ?> <span class="at-required">*</span></label></th>
					<td>
						<select name="payment_status" id="at_b_payment_status" required>
							<?php
							$at_payment_status_options = array(
								'pending' => __( 'Pending', 'adventure-treks' ),
								'paid'    => __( 'Paid', 'adventure-treks' ),
							);
							$at_current_payment_status = $at_is_edit ? $booking->payment_status : 'pending';
							foreach ( $at_payment_status_options as $at_payment_status_val => $at_payment_status_label ) :
								?>
								<option value="<?php echo esc_attr( $at_payment_status_val ); ?>" <?php selected( $at_current_payment_status, $at_payment_status_val ); ?>><?php echo esc_html( $at_payment_status_label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</tbody>
		</table>

		<?php submit_button( $at_is_edit ? __( 'Update Booking', 'adventure-treks' ) : __( 'Add Booking', 'adventure-treks' ) ); ?>
	</form>
</div>
