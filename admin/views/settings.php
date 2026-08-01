<?php
/**
 * Settings admin page view template
 *
 * @package    AdventureTreks
 * @subpackage AdventureTreks/Admin/Views
 * @author     Nilesh Vastarpara
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<h2 class="nav-tab-wrapper">
		<a href="#" class="nav-tab nav-tab-active" data-at-tab="general"><?php esc_html_e( 'General', 'adventure-treks' ); ?></a>
		<a href="#" class="nav-tab" data-at-tab="payment"><?php esc_html_e( 'Payment', 'adventure-treks' ); ?></a>
		<a href="#" class="nav-tab" data-at-tab="email"><?php esc_html_e( 'Email', 'adventure-treks' ); ?></a>
	</h2>

	<form method="post" action="options.php" class="at-settings-form-card">
		<?php
		settings_fields( 'adventure_treks_settings_group' );
		do_settings_sections( 'adventure_treks_settings_group' );
		?>

		<div id="at-settings-tab-general" class="at-settings-tab">
			<h2><?php esc_html_e( 'Appearance Settings', 'adventure-treks' ); ?></h2>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="at_site_logo"><?php esc_html_e( 'Site Logo', 'adventure-treks' ); ?></label>
						</th>
						<td>
							<input type="hidden" name="at_site_logo" id="at_site_logo" value="<?php echo esc_attr( get_option( 'at_site_logo', '' ) ); ?>" />
							<div id="at_site_logo_preview">
								<?php $adventure_treks_logo_url = get_option( 'at_site_logo', '' ); ?>
								<?php if ( $adventure_treks_logo_url ) : ?>
									<img src="<?php echo esc_url( $adventure_treks_logo_url ); ?>" class="at-logo-preview-img" />
								<?php endif; ?>
							</div>
							<p>
								<button type="button" class="button" id="at_site_logo_select_btn"><?php esc_html_e( 'Select Logo Image', 'adventure-treks' ); ?></button>
								<button type="button" class="button" id="at_site_logo_remove_btn" <?php echo $adventure_treks_logo_url ? '' : 'style="display:none;"'; ?>><?php esc_html_e( 'Remove', 'adventure-treks' ); ?></button>
							</p>
							<p class="description"><?php esc_html_e( 'Shown in the header of booking confirmation and status update emails.', 'adventure-treks' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="at_currency_symbol"><?php esc_html_e( 'Currency Symbol', 'adventure-treks' ); ?></label>
						</th>
						<td>
							<?php
							$adventure_treks_current_currency = get_option( 'at_currency_symbol', '₹' );
							$adventure_treks_currencies       = \AdventureTreks\Admin\Controllers\AdminController::get_currencies();
							?>
							<select name="at_currency_symbol" id="at_currency_symbol">
								<?php foreach ( $adventure_treks_currencies as $adventure_treks_symbol => $adventure_treks_label ) : ?>
									<option value="<?php echo esc_attr( $adventure_treks_symbol ); ?>" <?php selected( $adventure_treks_current_currency, $adventure_treks_symbol ); ?>>
										<?php echo esc_html( $adventure_treks_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'The currency symbol shown alongside trek prices.', 'adventure-treks' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'SEO Options', 'adventure-treks' ); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span><?php esc_html_e( 'SEO Options', 'adventure-treks' ); ?></span></legend>
								<label for="at_enable_schema">
									<input name="at_enable_schema" type="checkbox" id="at_enable_schema" value="1" <?php checked( '1', get_option( 'at_enable_schema', '1' ) ); ?> />
									<?php esc_html_e( 'Generate Schema.org structured JSON-LD data for Trek posts.', 'adventure-treks' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Color', 'adventure-treks' ); ?></h2>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="at_primary_color"><?php esc_html_e( 'Primary', 'adventure-treks' ); ?></label>
						</th>
						<td>
							<input type="text" name="at_primary_color" id="at_primary_color" class="at-color-picker" value="<?php echo esc_attr( get_option( 'at_primary_color', '#137a7f' ) ); ?>" data-default-color="#137a7f" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="at_secondary_color"><?php esc_html_e( 'Secondary', 'adventure-treks' ); ?></label>
						</th>
						<td>
							<input type="text" name="at_secondary_color" id="at_secondary_color" class="at-color-picker" value="<?php echo esc_attr( get_option( 'at_secondary_color', '#0f6165' ) ); ?>" data-default-color="#0f6165" />
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div id="at-settings-tab-payment" class="at-settings-tab" style="display:none;">
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Payment Method', 'adventure-treks' ); ?>
						</th>
						<td>
							<?php $adventure_treks_payment_method = get_option( 'at_payment_method', 'cash' ); ?>
							<fieldset>
								<legend class="screen-reader-text"><span><?php esc_html_e( 'Payment Method', 'adventure-treks' ); ?></span></legend>
								<label for="at_payment_method_cash" style="margin-right:20px !important;">
									<input type="radio" name="at_payment_method" id="at_payment_method_cash" value="cash" <?php checked( 'cash', $adventure_treks_payment_method ); ?> />
									<?php esc_html_e( 'Cash', 'adventure-treks' ); ?>
								</label>
								<label for="at_payment_method_upi">
									<input type="radio" name="at_payment_method" id="at_payment_method_upi" value="upi" <?php checked( 'upi', $adventure_treks_payment_method ); ?> />
									<?php esc_html_e( 'UPI', 'adventure-treks' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>

			<table class="form-table" role="presentation" id="at_upi_fields" style="display:none;">
				<tbody>
					<tr>
						<th scope="row">
							<label for="at_upi_id"><?php esc_html_e( 'UPI ID', 'adventure-treks' ); ?></label>
						</th>
						<td>
							<input type="text" name="at_upi_id" id="at_upi_id" class="regular-text" value="<?php echo esc_attr( get_option( 'at_upi_id', '' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. yourname@upi', 'adventure-treks' ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="at_upi_qr_code"><?php esc_html_e( 'UPI QR Code', 'adventure-treks' ); ?></label>
						</th>
						<td>
							<input type="hidden" name="at_upi_qr_code" id="at_upi_qr_code" value="<?php echo esc_attr( get_option( 'at_upi_qr_code', '' ) ); ?>" />
							<div id="at_upi_qr_code_preview">
								<?php $adventure_treks_qr_code_url = get_option( 'at_upi_qr_code', '' ); ?>
								<?php if ( $adventure_treks_qr_code_url ) : ?>
									<img src="<?php echo esc_url( $adventure_treks_qr_code_url ); ?>" class="at-qr-code-preview-img" />
								<?php endif; ?>
							</div>
							<p>
								<button type="button" class="button" id="at_upi_qr_code_select_btn"><?php esc_html_e( 'Select QR Code Image', 'adventure-treks' ); ?></button>
								<button type="button" class="button" id="at_upi_qr_code_remove_btn" <?php echo $adventure_treks_qr_code_url ? '' : 'style="display:none;"'; ?>><?php esc_html_e( 'Remove', 'adventure-treks' ); ?></button>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div id="at-settings-tab-email" class="at-settings-tab" style="display:none;">
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="at_from_name"><?php esc_html_e( 'From Name', 'adventure-treks' ); ?></label>
						</th>
						<td>
							<input name="at_from_name" type="text" id="at_from_name" value="<?php echo esc_attr( get_option( 'at_from_name', get_bloginfo( 'name' ) ) ); ?>" class="regular-text" required />
							<p class="description"><?php esc_html_e( 'The name that emails are sent from.', 'adventure-treks' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="at_booking_email"><?php esc_html_e( 'Notification Email', 'adventure-treks' ); ?></label>
						</th>
						<td>
							<input name="at_booking_email" type="email" id="at_booking_email" value="<?php echo esc_attr( get_option( 'at_booking_email', get_option( 'admin_email' ) ) ); ?>" class="regular-text" required />
							<p class="description"><?php esc_html_e( 'Email address that will receive reservation alerts, and used as the sender address on booking emails.', 'adventure-treks' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<?php submit_button(); ?>
	</form>
</div>
