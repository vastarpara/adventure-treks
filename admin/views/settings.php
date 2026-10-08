<?php
/**
 * Settings admin page view template
 *
 * @package    TrekPilot
 * @subpackage TrekPilot/Admin/Views
 * @author     Nilesh Vastarpara
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php
	$trekpilot_ie_notice = get_transient( 'trekpilot_import_export_notice_' . get_current_user_id() );
	if ( $trekpilot_ie_notice ) {
		delete_transient( 'trekpilot_import_export_notice_' . get_current_user_id() );
		printf(
			'<div class="notice notice-%1$s inline is-dismissible"><p>%2$s</p></div>',
			esc_attr( 'success' === $trekpilot_ie_notice[0] ? 'success' : 'error' ),
			esc_html( $trekpilot_ie_notice[1] )
		);
	}
	?>

	<h2 class="nav-tab-wrapper">
		<a href="#" class="nav-tab nav-tab-active" data-trekpilot-tab="general"><?php esc_html_e( 'General', 'trekpilot' ); ?></a>
		<a href="#" class="nav-tab" data-trekpilot-tab="currency"><?php esc_html_e( 'Currency', 'trekpilot' ); ?></a>
		<a href="#" class="nav-tab" data-trekpilot-tab="payment"><?php esc_html_e( 'Payment', 'trekpilot' ); ?></a>
		<a href="#" class="nav-tab" data-trekpilot-tab="email"><?php esc_html_e( 'Email', 'trekpilot' ); ?></a>
		<a href="#" class="nav-tab" data-trekpilot-tab="import-export"><?php esc_html_e( 'Import / Export', 'trekpilot' ); ?></a>
	</h2>

	<form method="post" action="options.php" class="trekpilot-settings-form-card">
		<?php
		settings_fields( 'trekpilot_settings_group' );
		do_settings_sections( 'trekpilot_settings_group' );
		?>

		<div id="trekpilot-settings-tab-general" class="trekpilot-settings-tab">
			<h2><?php esc_html_e( 'Appearance Settings', 'trekpilot' ); ?></h2>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Site Logo', 'trekpilot' ); ?>
						</th>
						<td>
							<?php
							$trekpilot_logo_url = \TrekPilot\Includes\Plugin::get_site_logo_url();
							// The preview ignores the checkbox, so it shows which logo would be used.
							$trekpilot_theme_logo_id = (int) get_theme_mod( 'custom_logo' );
							if ( ! $trekpilot_theme_logo_id ) {
								$trekpilot_theme_logo_id = (int) get_option( 'site_logo' );
							}
							$trekpilot_preview_url = $trekpilot_theme_logo_id ? wp_get_attachment_image_url( $trekpilot_theme_logo_id, 'medium' ) : '';
							?>
							<fieldset>
								<legend class="screen-reader-text"><span><?php esc_html_e( 'Site Logo', 'trekpilot' ); ?></span></legend>
								<label for="trekpilot_use_site_logo">
									<input name="trekpilot_use_site_logo" type="checkbox" id="trekpilot_use_site_logo" value="1" <?php checked( '1', get_option( 'trekpilot_use_site_logo', '1' ) ); ?> />
									<?php esc_html_e( 'Use the site logo in the header of booking confirmation and status update emails.', 'trekpilot' ); ?>
								</label>
							</fieldset>
							<?php if ( $trekpilot_preview_url ) : ?>
								<p class="trekpilot-site-logo-preview"><img src="<?php echo esc_url( $trekpilot_preview_url ); ?>" class="trekpilot-logo-preview-img" alt="" /></p>
								<p class="description"><?php esc_html_e( 'This is your WordPress site logo.', 'trekpilot' ); ?></p>
							<?php else : ?>
								<p class="description">
									<?php esc_html_e( 'No site logo is set, so emails show your site name instead.', 'trekpilot' ); ?>
									<?php if ( current_theme_supports( 'custom-logo' ) ) : ?>
										<a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=title_tagline' ) ); ?>"><?php esc_html_e( 'Set a site logo', 'trekpilot' ); ?></a>
									<?php endif; ?>
								</p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'SEO Options', 'trekpilot' ); ?>
						</th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span><?php esc_html_e( 'SEO Options', 'trekpilot' ); ?></span></legend>
								<label for="trekpilot_enable_schema">
									<input name="trekpilot_enable_schema" type="checkbox" id="trekpilot_enable_schema" value="1" <?php checked( '1', get_option( 'trekpilot_enable_schema', '1' ) ); ?> />
									<?php esc_html_e( 'Generate Schema.org structured JSON-LD data for Trek posts.', 'trekpilot' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Color', 'trekpilot' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Leave a color empty to use your theme\'s own colors (Primary and Secondary).', 'trekpilot' ); ?></p>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="trekpilot_primary_color"><?php esc_html_e( 'Primary', 'trekpilot' ); ?></label>
						</th>
						<td>
							<input type="text" name="trekpilot_primary_color" id="trekpilot_primary_color" class="trekpilot-color-picker" value="<?php echo esc_attr( get_option( 'trekpilot_primary_color', '' ) ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="trekpilot_secondary_color"><?php esc_html_e( 'Secondary', 'trekpilot' ); ?></label>
						</th>
						<td>
							<input type="text" name="trekpilot_secondary_color" id="trekpilot_secondary_color" class="trekpilot-color-picker" value="<?php echo esc_attr( get_option( 'trekpilot_secondary_color', '' ) ); ?>" />
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div id="trekpilot-settings-tab-currency" class="trekpilot-settings-tab" style="display:none;">
			<h2><?php esc_html_e( 'Currency options', 'trekpilot' ); ?></h2>
			<p><?php esc_html_e( 'The following options affect how prices are displayed on the frontend, in booking emails and in the admin bookings screens.', 'trekpilot' ); ?></p>
			<?php
			$trekpilot_current_currency = get_option( 'trekpilot_currency_symbol', '$' );
			$trekpilot_currencies       = \TrekPilot\Admin\Controllers\AdminController::get_currencies();
			$trekpilot_positions        = \TrekPilot\Admin\Controllers\AdminController::get_currency_positions();
			$trekpilot_price_fmt        = \TrekPilot\Admin\Controllers\AdminController::get_price_format();
			?>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="trekpilot_currency_symbol"><?php esc_html_e( 'Currency', 'trekpilot' ); ?></label>
						</th>
						<td>
							<select name="trekpilot_currency_symbol" id="trekpilot_currency_symbol">
								<?php foreach ( $trekpilot_currencies as $trekpilot_symbol => $trekpilot_label ) : ?>
									<option value="<?php echo esc_attr( $trekpilot_symbol ); ?>" <?php selected( $trekpilot_current_currency, $trekpilot_symbol ); ?>>
										<?php echo esc_html( $trekpilot_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'The currency symbol shown alongside trek prices.', 'trekpilot' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="trekpilot_currency_position"><?php esc_html_e( 'Currency position', 'trekpilot' ); ?></label>
						</th>
						<td>
							<select name="trekpilot_currency_position" id="trekpilot_currency_position">
								<?php foreach ( $trekpilot_positions as $trekpilot_pos_key => $trekpilot_pos_label ) : ?>
									<option value="<?php echo esc_attr( $trekpilot_pos_key ); ?>" <?php selected( $trekpilot_price_fmt['position'], $trekpilot_pos_key ); ?>>
										<?php echo esc_html( $trekpilot_pos_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'Where the currency symbol appears relative to the amount.', 'trekpilot' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="trekpilot_thousand_separator"><?php esc_html_e( 'Thousand separator', 'trekpilot' ); ?></label>
						</th>
						<td>
							<input name="trekpilot_thousand_separator" type="text" id="trekpilot_thousand_separator" value="<?php echo esc_attr( $trekpilot_price_fmt['thousand'] ); ?>" maxlength="1" class="small-text" />
							<p class="description"><?php esc_html_e( 'Separates thousands, e.g. 11,499. Leave empty for none.', 'trekpilot' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="trekpilot_decimal_separator"><?php esc_html_e( 'Decimal separator', 'trekpilot' ); ?></label>
						</th>
						<td>
							<input name="trekpilot_decimal_separator" type="text" id="trekpilot_decimal_separator" value="<?php echo esc_attr( $trekpilot_price_fmt['decimal'] ); ?>" maxlength="1" class="small-text" />
							<p class="description"><?php esc_html_e( 'Separates the decimal part, e.g. 11,499.00. Cannot be empty.', 'trekpilot' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="trekpilot_price_decimals"><?php esc_html_e( 'Number of decimals', 'trekpilot' ); ?></label>
						</th>
						<td>
							<input name="trekpilot_price_decimals" type="number" id="trekpilot_price_decimals" value="<?php echo esc_attr( $trekpilot_price_fmt['decimals'] ); ?>" min="0" max="4" step="1" class="small-text" />
							<p class="description"><?php esc_html_e( 'How many digits to show after the decimal point (0 - 4).', 'trekpilot' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Preview', 'trekpilot' ); ?></th>
						<td>
							<strong id="trekpilot_currency_preview"><?php echo esc_html( \TrekPilot\Admin\Controllers\AdminController::format_price( 1234567.891 ) ); ?></strong>
							<p class="description"><?php esc_html_e( 'Updates as you change the options above. Save to apply.', 'trekpilot' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div id="trekpilot-settings-tab-payment" class="trekpilot-settings-tab" style="display:none;">
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<?php esc_html_e( 'Payment Method', 'trekpilot' ); ?>
						</th>
						<td>
							<?php $trekpilot_payment_method = get_option( 'trekpilot_payment_method', 'cash' ); ?>
							<fieldset>
								<legend class="screen-reader-text"><span><?php esc_html_e( 'Payment Method', 'trekpilot' ); ?></span></legend>
								<label for="trekpilot_payment_method_cash" style="margin-right:20px !important;">
									<input type="radio" name="trekpilot_payment_method" id="trekpilot_payment_method_cash" value="cash" <?php checked( 'cash', $trekpilot_payment_method ); ?> />
									<?php esc_html_e( 'Cash', 'trekpilot' ); ?>
								</label>
								<label for="trekpilot_payment_method_upi">
									<input type="radio" name="trekpilot_payment_method" id="trekpilot_payment_method_upi" value="upi" <?php checked( 'upi', $trekpilot_payment_method ); ?> />
									<?php esc_html_e( 'UPI', 'trekpilot' ); ?>
								</label>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>

			<table class="form-table" role="presentation" id="trekpilot_upi_fields" style="display:none;">
				<tbody>
					<tr>
						<th scope="row">
							<label for="trekpilot_upi_id"><?php esc_html_e( 'UPI ID', 'trekpilot' ); ?></label>
						</th>
						<td>
							<input type="text" name="trekpilot_upi_id" id="trekpilot_upi_id" class="regular-text" value="<?php echo esc_attr( get_option( 'trekpilot_upi_id', '' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. yourname@upi', 'trekpilot' ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="trekpilot_upi_qr_code"><?php esc_html_e( 'UPI QR Code', 'trekpilot' ); ?></label>
						</th>
						<td>
							<input type="hidden" name="trekpilot_upi_qr_code" id="trekpilot_upi_qr_code" value="<?php echo esc_attr( get_option( 'trekpilot_upi_qr_code', '' ) ); ?>" />
							<div id="trekpilot_upi_qr_code_preview">
								<?php $trekpilot_qr_code_url = get_option( 'trekpilot_upi_qr_code', '' ); ?>
								<?php if ( $trekpilot_qr_code_url ) : ?>
									<img src="<?php echo esc_url( $trekpilot_qr_code_url ); ?>" class="trekpilot-qr-code-preview-img" alt="<?php esc_attr_e( 'UPI QR code preview', 'trekpilot' ); ?>" />
								<?php endif; ?>
							</div>
							<p>
								<button type="button" class="button" id="trekpilot_upi_qr_code_select_btn"><?php esc_html_e( 'Select QR Code Image', 'trekpilot' ); ?></button>
								<button type="button" class="button" id="trekpilot_upi_qr_code_remove_btn" <?php echo $trekpilot_qr_code_url ? '' : 'style="display:none;"'; ?>><?php esc_html_e( 'Remove', 'trekpilot' ); ?></button>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div id="trekpilot-settings-tab-email" class="trekpilot-settings-tab" style="display:none;">
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">
							<label for="trekpilot_from_name"><?php esc_html_e( 'From Name', 'trekpilot' ); ?></label>
						</th>
						<td>
							<input name="trekpilot_from_name" type="text" id="trekpilot_from_name" value="<?php echo esc_attr( get_option( 'trekpilot_from_name', get_bloginfo( 'name' ) ) ); ?>" class="regular-text" required />
							<p class="description"><?php esc_html_e( 'The name that emails are sent from.', 'trekpilot' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="trekpilot_booking_email"><?php esc_html_e( 'Notification Email', 'trekpilot' ); ?></label>
						</th>
						<td>
							<input name="trekpilot_booking_email" type="email" id="trekpilot_booking_email" value="<?php echo esc_attr( get_option( 'trekpilot_booking_email', get_bloginfo( 'admin_email' ) ) ); ?>" class="regular-text" required />
							<p class="description"><?php esc_html_e( 'Email address that will receive reservation alerts, and used as the sender address on booking emails.', 'trekpilot' ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>

		<?php submit_button(); ?>
	</form>

	<?php // Outside the settings form: these post to admin-post.php, and forms cannot be nested. ?>
	<div id="trekpilot-settings-tab-import-export" class="trekpilot-settings-tab trekpilot-settings-form-card" style="display:none;">
		<h2><?php esc_html_e( 'Export Treks', 'trekpilot' ); ?></h2>
		<p><?php esc_html_e( 'Download all treks as a JSON file, including specifications, departure cities, itineraries, pickup points, dates, seats and pricing. Bookings are not included.', 'trekpilot' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="trekpilot_export_treks" />
			<?php wp_nonce_field( 'trekpilot_export_treks' ); ?>
			<?php submit_button( __( 'Export Treks', 'trekpilot' ), 'primary', 'submit', false ); ?>
		</form>

		<hr />

		<h2><?php esc_html_e( 'Import Treks', 'trekpilot' ); ?></h2>
		<p><?php esc_html_e( 'Choose a JSON file exported from TrekPilot. Every trek in the file is created as a new trek; existing treks are never overwritten. Images are downloaded into the media library.', 'trekpilot' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
			<input type="hidden" name="action" value="trekpilot_import_treks" />
			<?php wp_nonce_field( 'trekpilot_import_treks' ); ?>
			<p><input type="file" name="trekpilot_import_file" accept=".json,application/json" aria-label="<?php esc_attr_e( 'Export file to import', 'trekpilot' ); ?>" required /></p>
			<?php submit_button( __( 'Import Treks', 'trekpilot' ), 'primary', 'submit', false ); ?>
		</form>
	</div>
</div>
