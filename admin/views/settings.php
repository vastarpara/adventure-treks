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
		<a href="#" class="nav-tab" data-at-tab="currency"><?php esc_html_e( 'Currency', 'adventure-treks' ); ?></a>
		<a href="#" class="nav-tab" data-at-tab="payment"><?php esc_html_e( 'Payment', 'adventure-treks' ); ?></a>
		<a href="#" class="nav-tab" data-at-tab="email"><?php esc_html_e( 'Email', 'adventure-treks' ); ?></a>
		<a href="#" class="nav-tab" data-at-tab="import-export"><?php esc_html_e( 'Import / Export', 'adventure-treks' ); ?></a>
	</h2>

	<?php
	$adventure_treks_ie_notice = get_transient( 'at_import_export_notice_' . get_current_user_id() );
	if ( $adventure_treks_ie_notice ) {
		delete_transient( 'at_import_export_notice_' . get_current_user_id() );
		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( 'success' === $adventure_treks_ie_notice[0] ? 'success' : 'error' ),
			esc_html( $adventure_treks_ie_notice[1] )
		);
	}
	?>

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
							<?php esc_html_e( 'Site Logo', 'adventure-treks' ); ?>
						</th>
						<td>
							<?php
							$adventure_treks_logo_url = \AdventureTreks\Includes\Plugin::get_site_logo_url();
							// The preview ignores the checkbox, so it shows which logo would be used.
							$adventure_treks_theme_logo_id = (int) get_theme_mod( 'custom_logo' );
							if ( ! $adventure_treks_theme_logo_id ) {
								$adventure_treks_theme_logo_id = (int) get_option( 'site_logo' );
							}
							$adventure_treks_preview_url = $adventure_treks_theme_logo_id ? wp_get_attachment_image_url( $adventure_treks_theme_logo_id, 'medium' ) : '';
							?>
							<fieldset>
								<legend class="screen-reader-text"><span><?php esc_html_e( 'Site Logo', 'adventure-treks' ); ?></span></legend>
								<label for="at_use_site_logo">
									<input name="at_use_site_logo" type="checkbox" id="at_use_site_logo" value="1" <?php checked( '1', get_option( 'at_use_site_logo', '1' ) ); ?> />
									<?php esc_html_e( 'Use the site logo in the header of booking confirmation and status update emails.', 'adventure-treks' ); ?>
								</label>
							</fieldset>
							<?php if ( $adventure_treks_preview_url ) : ?>
								<p class="at-site-logo-preview"><img src="<?php echo esc_url( $adventure_treks_preview_url ); ?>" class="at-logo-preview-img" alt="" /></p>
								<p class="description"><?php esc_html_e( 'This is your WordPress site logo.', 'adventure-treks' ); ?></p>
							<?php else : ?>
								<p class="description">
									<?php esc_html_e( 'No site logo is set, so emails show your site name instead.', 'adventure-treks' ); ?>
									<?php if ( current_theme_supports( 'custom-logo' ) ) : ?>
										<a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=title_tagline' ) ); ?>"><?php esc_html_e( 'Set a site logo', 'adventure-treks' ); ?></a>
									<?php endif; ?>
								</p>
							<?php endif; ?>
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
			<p class="description"><?php esc_html_e( 'Leave a color empty to use your theme\'s own colors (Primary and Secondary).', 'adventure-treks' ); ?></p>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="at_primary_color"><?php esc_html_e( 'Primary', 'adventure-treks' ); ?></label>
						</th>
						<td>
							<input type="text" name="at_primary_color" id="at_primary_color" class="at-color-picker" value="<?php echo esc_attr( get_option( 'at_primary_color', '' ) ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="at_secondary_color"><?php esc_html_e( 'Secondary', 'adventure-treks' ); ?></label>
						</th>
						<td>
							<input type="text" name="at_secondary_color" id="at_secondary_color" class="at-color-picker" value="<?php echo esc_attr( get_option( 'at_secondary_color', '' ) ); ?>" />
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div id="at-settings-tab-currency" class="at-settings-tab" style="display:none;">
			<h2><?php esc_html_e( 'Currency options', 'adventure-treks' ); ?></h2>
			<p><?php esc_html_e( 'The following options affect how prices are displayed on the frontend, in booking emails and in the admin bookings screens.', 'adventure-treks' ); ?></p>
			<?php
			$adventure_treks_current_currency = get_option( 'at_currency_symbol', '$' );
			$adventure_treks_currencies       = \AdventureTreks\Admin\Controllers\AdminController::get_currencies();
			$adventure_treks_positions        = \AdventureTreks\Admin\Controllers\AdminController::get_currency_positions();
			$adventure_treks_price_fmt        = \AdventureTreks\Admin\Controllers\AdminController::get_price_format();
			?>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="at_currency_symbol"><?php esc_html_e( 'Currency', 'adventure-treks' ); ?></label>
						</th>
						<td>
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
							<label for="at_currency_position"><?php esc_html_e( 'Currency position', 'adventure-treks' ); ?></label>
						</th>
						<td>
							<select name="at_currency_position" id="at_currency_position">
								<?php foreach ( $adventure_treks_positions as $adventure_treks_pos_key => $adventure_treks_pos_label ) : ?>
									<option value="<?php echo esc_attr( $adventure_treks_pos_key ); ?>" <?php selected( $adventure_treks_price_fmt['position'], $adventure_treks_pos_key ); ?>>
										<?php echo esc_html( $adventure_treks_pos_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Where the currency symbol appears relative to the amount.', 'adventure-treks' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="at_thousand_separator"><?php esc_html_e( 'Thousand separator', 'adventure-treks' ); ?></label>
						</th>
						<td>
							<input name="at_thousand_separator" type="text" id="at_thousand_separator" value="<?php echo esc_attr( $adventure_treks_price_fmt['thousand'] ); ?>" maxlength="1" class="small-text" />
							<p class="description"><?php esc_html_e( 'Separates thousands, e.g. 11,499. Leave empty for none.', 'adventure-treks' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="at_decimal_separator"><?php esc_html_e( 'Decimal separator', 'adventure-treks' ); ?></label>
						</th>
						<td>
							<input name="at_decimal_separator" type="text" id="at_decimal_separator" value="<?php echo esc_attr( $adventure_treks_price_fmt['decimal'] ); ?>" maxlength="1" class="small-text" />
							<p class="description"><?php esc_html_e( 'Separates the decimal part, e.g. 11,499.00. Cannot be empty.', 'adventure-treks' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="at_price_decimals"><?php esc_html_e( 'Number of decimals', 'adventure-treks' ); ?></label>
						</th>
						<td>
							<input name="at_price_decimals" type="number" id="at_price_decimals" value="<?php echo esc_attr( $adventure_treks_price_fmt['decimals'] ); ?>" min="0" max="4" step="1" class="small-text" />
							<p class="description"><?php esc_html_e( 'How many digits to show after the decimal point (0 - 4).', 'adventure-treks' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Preview', 'adventure-treks' ); ?></th>
						<td>
							<strong id="at_currency_preview"><?php echo esc_html( \AdventureTreks\Admin\Controllers\AdminController::format_price( 1234567.891 ) ); ?></strong>
							<p class="description"><?php esc_html_e( 'Updates as you change the options above. Save to apply.', 'adventure-treks' ); ?></p>
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
									<img src="<?php echo esc_url( $adventure_treks_qr_code_url ); ?>" class="at-qr-code-preview-img" alt="<?php esc_attr_e( 'UPI QR code preview', 'adventure-treks' ); ?>" />
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

	<?php // Outside the settings form: these post to admin-post.php, and forms cannot be nested. ?>
	<div id="at-settings-tab-import-export" class="at-settings-tab at-settings-form-card" style="display:none;">
		<h2><?php esc_html_e( 'Export Treks', 'adventure-treks' ); ?></h2>
		<p><?php esc_html_e( 'Download all treks as a JSON file, including specifications, departure cities, itineraries, pickup points, dates, seats and pricing. Bookings are not included.', 'adventure-treks' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="at_export_treks" />
			<?php wp_nonce_field( 'at_export_treks' ); ?>
			<?php submit_button( __( 'Export Treks', 'adventure-treks' ), 'primary', 'submit', false ); ?>
		</form>

		<hr />

		<h2><?php esc_html_e( 'Import Treks', 'adventure-treks' ); ?></h2>
		<p><?php esc_html_e( 'Choose a JSON file exported from Adventure Treks. Every trek in the file is created as a new trek; existing treks are never overwritten. Images are downloaded into the media library.', 'adventure-treks' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
			<input type="hidden" name="action" value="at_import_treks" />
			<?php wp_nonce_field( 'at_import_treks' ); ?>
			<p><input type="file" name="at_import_file" accept=".json,application/json" required /></p>
			<?php submit_button( __( 'Import Treks', 'adventure-treks' ), 'primary', 'submit', false ); ?>
		</form>
	</div>
</div>
