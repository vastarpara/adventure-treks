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
	<hr class="wp-header-end">

	<form method="post" action="options.php" style="background: #fff; padding: 20px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 20px;">
		<?php
		settings_fields( 'adventure_treks_settings_group' );
		do_settings_sections( 'adventure_treks_settings_group' );
		?>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="at_currency_symbol"><?php esc_html_e( 'Currency Symbol', 'adventure-treks' ); ?></label>
					</th>
					<td>
						<input name="at_currency_symbol" type="text" id="at_currency_symbol" value="<?php echo esc_attr( get_option( 'at_currency_symbol', '₹' ) ); ?>" class="small-text" />
						<p class="description"><?php esc_html_e( 'The currency symbol shown alongside trek prices.', 'adventure-treks' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="at_booking_email"><?php esc_html_e( 'Notification Email', 'adventure-treks' ); ?></label>
					</th>
					<td>
						<input name="at_booking_email" type="email" id="at_booking_email" value="<?php echo esc_attr( get_option( 'at_booking_email', get_option( 'admin_email' ) ) ); ?>" class="regular-text" />
						<p class="description"><?php esc_html_e( 'Email address that will receive reservation alerts.', 'adventure-treks' ); ?></p>
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
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Uninstall Options', 'adventure-treks' ); ?>
					</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span><?php esc_html_e( 'Uninstall Options', 'adventure-treks' ); ?></span></legend>
							<label for="at_remove_data_on_uninstall">
								<input name="at_remove_data_on_uninstall" type="checkbox" id="at_remove_data_on_uninstall" value="1" <?php checked( '1', get_option( 'at_remove_data_on_uninstall', '0' ) ); ?> />
								<span style="color: #b32d2e;"><strong><?php esc_html_e( 'Remove all plugin data and tables upon uninstallation.', 'adventure-treks' ); ?></strong></span>
								<br>
								<small><?php esc_html_e( 'Warning: If checked, deleting the plugin will permanently erase all treks, cities, dates, bookings, and custom tables.', 'adventure-treks' ); ?></small>
							</label>
						</fieldset>
					</td>
				</tr>
			</tbody>
		</table>

		<?php submit_button(); ?>
	</form>
</div>
