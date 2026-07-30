<?php
/**
 * Frontend Booking Widget view template.
 *
 * @package    AdventureTreks
 * @subpackage AdventureTreks/Public/Views
 * @author     Nilesh Vastarpara
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="at-booking-widget-wrapper" id="at_booking_widget_root" data-trek-id="<?php echo esc_attr( $trek_id ); ?>">
	
	<!-- Header Block -->
	<div class="at-widget-header">
		<h3><?php esc_html_e( 'Book Your Adventure', 'adventure-treks' ); ?></h3>
		<p class="at-widget-subtitle"><?php esc_html_e( 'Select departure city and dates to check availability and calculate price.', 'adventure-treks' ); ?></p>
	</div>

	<!-- Step 1: Select Departure City -->
	<div class="at-widget-section">
		<h4 class="at-section-title"><span class="at-step-badge">1</span> <?php esc_html_e( 'Select Departure City', 'adventure-treks' ); ?></h4>
		<div class="at-city-pills-grid" id="at_widget_city_pills">
			<?php if ( ! empty( $cities ) ) : ?>
				<?php foreach ( $cities as $c ) : ?>
					<button type="button" class="at-city-pill-btn" data-id="<?php echo esc_attr( $c->id ); ?>">
						<span class="dashicons dashicons-location"></span> <?php echo esc_html( $c->city_name ); ?>
					</button>
				<?php endforeach; ?>
			<?php else : ?>
				<p style="color:#666; font-style:italic;"><?php esc_html_e( 'No departure cities configured for this trek.', 'adventure-treks' ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<!-- Step 2: Select Date (Hidden initially until City is selected) -->
	<div class="at-widget-section" id="at_widget_date_section" style="display: none;">
		<h4 class="at-section-title"><span class="at-step-badge">2</span> <?php esc_html_e( 'Select Departure Date', 'adventure-treks' ); ?></h4>
		<div class="at-loading-indicator" id="at_widget_dates_loading" style="display:none; padding: 15px 0; align-items: center; color: #137a7f; font-weight: 600;">
			<span class="dashicons dashicons-update" style="animation: spin 2s linear infinite; margin-right: 8px;"></span> <?php esc_html_e( 'Finding scheduled slots...', 'adventure-treks' ); ?>
		</div>
		<div class="at-dates-grid" id="at_widget_dates_grid">
			<!-- Populated via AJAX -->
		</div>
	</div>

	<!-- Loader for Booking Details -->
	<div class="at-loading-indicator" id="at_widget_details_loading" style="display:none; padding: 20px 0; justify-content: center; align-items: center; color: #137a7f; font-weight: 600;">
		<span class="dashicons dashicons-update" style="animation: spin 2s linear infinite; margin-right: 8px;"></span> <?php esc_html_e( 'Loading booking details...', 'adventure-treks' ); ?>
		<style>@keyframes spin { 100% { transform: rotate(360deg); } }</style>
	</div>

	<!-- Step 3: Details Panel & Booking Calculator (Hidden until Date is selected) -->
	<div class="at-widget-section" id="at_widget_details_section" style="display: none;">
		<h4 class="at-section-title"><span class="at-step-badge">3</span> <?php esc_html_e( 'Configure Booking Details', 'adventure-treks' ); ?></h4>
		
		<!-- Availability Badge & Transports Grid -->
		<div class="at-widget-details-grid">
			<div class="at-detail-card">
				<span class="at-detail-label"><?php esc_html_e( 'Seat Availability', 'adventure-treks' ); ?></span>
				<div class="at-detail-value" id="at_widget_avail_seats">--</div>
			</div>
			<div class="at-detail-card">
				<span class="at-detail-label"><?php esc_html_e( 'Transport Type', 'adventure-treks' ); ?></span>
				<div class="at-detail-value" id="at_widget_transport">--</div>
			</div>
			<div class="at-detail-card">
				<span class="at-detail-label"><?php esc_html_e( 'Reporting Time', 'adventure-treks' ); ?></span>
				<div class="at-detail-value" id="at_widget_reporting">--</div>
			</div>
		</div>

		<!-- Pricing Info Box -->
		<div class="at-pricing-infobox">
			<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap: wrap; gap: 5px;">
				<span style="font-weight:600; flex-shrink: 0;"><?php esc_html_e( 'Per Person Rate', 'adventure-treks' ); ?>:</span>
				<div style="text-align:right; white-space:nowrap; flex-shrink: 0;">
					<span id="at_widget_base_price_cross" style="text-decoration:line-through; color:#999; margin-right:8px; display:none; white-space:nowrap;"></span>
					<span id="at_widget_price_tag" style="font-size:18px; font-weight:700; color:#137a7f; white-space:nowrap;"></span>
				</div>
			</div>
			<p class="description" id="at_widget_child_price_tag" style="margin:5px 0 0 0; text-align:right; font-size:11px; display:none;"></p>
			<p class="description" id="at_widget_date_notes" style="margin:8px 0 0 0; font-style:italic; font-size:11px; color:#b32d2e; display:none;"></p>
		</div>

		<!-- Pax Count Inputs -->
		<div class="at-pax-calculator">
			<div class="at-pax-row">
				<div class="at-pax-label">
					<strong><?php esc_html_e( 'Adults', 'adventure-treks' ); ?></strong>
					<span class="description"><?php esc_html_e( 'Age 12+', 'adventure-treks' ); ?></span>
				</div>
				<div class="at-pax-counter">
					<button type="button" class="at-counter-btn minus" data-type="adults">-</button>
					<input type="number" id="at_widget_pax_adults" value="1" min="1" readonly />
					<button type="button" class="at-counter-btn plus" data-type="adults">+</button>
				</div>
			</div>
			<div class="at-pax-row" id="at_widget_child_row" style="display:none;">
				<div class="at-pax-label">
					<strong><?php esc_html_e( 'Children', 'adventure-treks' ); ?></strong>
					<span class="description"><?php esc_html_e( 'Age 5-11', 'adventure-treks' ); ?></span>
				</div>
				<div class="at-pax-counter">
					<button type="button" class="at-counter-btn minus" data-type="children">-</button>
					<input type="number" id="at_widget_pax_children" value="0" min="0" readonly />
					<button type="button" class="at-counter-btn plus" data-type="children">+</button>
				</div>
			</div>
		</div>

		<!-- Optional Add-ons checklists -->
		<div class="at-widget-addons-container" id="at_widget_addons_section" style="display:none;">
			<h5 style="margin:0 0 10px 0; font-size:12px; font-weight:600; color:#3c434a;"><?php esc_html_e( 'Choose Optional Add-ons', 'adventure-treks' ); ?></h5>
			<div id="at_widget_addons_list">
				<!-- Populated dynamically -->
			</div>
		</div>

		<!-- Receipt Breakdown List -->
		<div class="at-booking-receipt">
			<h5 style="margin:0 0 8px 0; font-size:12px; font-weight:600; color:#3c434a;"><?php esc_html_e( 'Price Breakdown', 'adventure-treks' ); ?></h5>
			<div class="at-receipt-rows" id="at_widget_receipt_rows">
				<!-- Dynamically generated receipt -->
			</div>
			<div class="at-receipt-total">
				<span><?php esc_html_e( 'Estimated Total', 'adventure-treks' ); ?>:</span>
				<strong id="at_widget_grand_total">--</strong>
			</div>
		</div>

		<!-- Trigger Checkout Button -->
		<button type="button" class="at-book-now-btn" id="at_widget_checkout_btn">
			<?php esc_html_e( 'Book Adventure Now', 'adventure-treks' ); ?>
		</button>
	</div>

	<!-- Step 4: Checkout Form Dialog (Modal popup overlay) -->
	<div class="at-booking-modal" id="at_checkout_modal" style="display: none;">
		<div class="at-booking-modal-content">
			<div class="at-booking-modal-header">
				<h4><?php esc_html_e( 'Complete Your Booking', 'adventure-treks' ); ?></h4>
				<span class="at-booking-modal-close" id="at_checkout_modal_close">&times;</span>
			</div>
			<div class="at-booking-modal-body">
				<form id="at_checkout_form">
					<div class="at-form-field">
						<label for="at_checkout_name"><?php esc_html_e( 'Full Name *', 'adventure-treks' ); ?></label>
						<input type="text" id="at_checkout_name" name="cust_name" required placeholder="e.g. Nilesh Vastarpara" />
					</div>
					<div class="at-form-field">
						<label for="at_checkout_email"><?php esc_html_e( 'Email Address *', 'adventure-treks' ); ?></label>
						<input type="email" id="at_checkout_email" name="cust_email" required placeholder="e.g. nilesh@example.com" />
					</div>
					<div class="at-form-field">
						<label for="at_checkout_phone"><?php esc_html_e( 'Phone Number *', 'adventure-treks' ); ?></label>
						<input type="tel" id="at_checkout_phone" name="cust_phone" required placeholder="e.g. +91 98765 43210" />
					</div>
					<div class="at-form-field" id="at_checkout_pickup_field" style="display:none;">
						<label for="at_checkout_pickup"><?php esc_html_e( 'Preferred Pickup Location *', 'adventure-treks' ); ?></label>
						<select id="at_checkout_pickup" name="pickup_point">
							<!-- Populated via AJAX -->
						</select>
						<span class="description" id="at_checkout_pickup_instructions" style="font-size:11px; margin-top:5px; display:block; color:#666;"></span>
					</div>
				</form>
				<div class="at-checkout-terms">
					<p style="font-size:11px; color:#666; margin:0;">
						<?php esc_html_e( 'By booking, you agree to the cancellation, refund policies, and terms and conditions configured for this trek.', 'adventure-treks' ); ?>
					</p>
				</div>
			</div>
			<div class="at-booking-modal-footer">
				<button type="button" class="at-modal-btn cancel" id="at_checkout_cancel_btn"><?php esc_html_e( 'Cancel', 'adventure-treks' ); ?></button>
				<button type="button" class="at-modal-btn confirm" id="at_checkout_confirm_btn"><?php esc_html_e( 'Confirm Reservation', 'adventure-treks' ); ?></button>
			</div>
		</div>
	</div>

	<!-- Step 5: Success Receipt Summary Overlay -->
	<div class="at-booking-modal" id="at_success_modal" style="display: none;">
		<div class="at-booking-modal-content" style="max-width:450px; text-align:center;">
			<div class="at-booking-modal-body" style="padding:30px 20px;">
				<span class="dashicons dashicons-saved" style="color:#385723; font-size:64px; width:64px; height:64px; display:block; margin:0 auto 15px auto;"></span>
				<h3 style="color:#385723; margin:0 0 10px 0; font-size:22px; font-weight:700;"><?php esc_html_e( 'Booking Confirmed!', 'adventure-treks' ); ?></h3>
				<p style="font-size:13px; color:#555; margin-bottom:20px; line-height:1.4;">
					<?php esc_html_e( 'Your reservation is successful. An email containing your receipt breakdown and reporting details has been dispatched.', 'adventure-treks' ); ?>
				</p>
				<div class="at-success-receipt" id="at_success_receipt_body" style="text-align:left; background:#fafafa; border:1px solid #ddd; padding:15px; border-radius:4px; font-size:12px; margin-bottom:20px;">
					<!-- Loaded via response -->
				</div>
				<button type="button" class="at-book-now-btn" id="at_success_close_btn" style="margin:0; width:100%;">
					<?php esc_html_e( 'Close & Return', 'adventure-treks' ); ?>
				</button>
			</div>
		</div>
	</div>

</div>
