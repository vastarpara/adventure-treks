<?php
/**
 * Frontend Booking Widget view template.
 *
 * @package    TrekPilot
 * @subpackage TrekPilot/Public/Views
 * @author     Nilesh Vastarpara
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Age ranges shown beside the Adults / Children counters (set per trek; defaults respect the trek's Age Limit when blank).
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
$trekpilot_age_limit    = (string) $wpdb->get_var( $wpdb->prepare( "SELECT age_limit FROM {$wpdb->prefix}trekpilot_treks WHERE post_id = %d", $trek_id ) );
$trekpilot_adult_age    = get_post_meta( $trek_id, '_trekpilot_adult_age', true );
$trekpilot_child_age    = get_post_meta( $trek_id, '_trekpilot_child_age', true );
$trekpilot_age_defaults = \TrekPilot\Admin\Controllers\TrekMetaBoxController::default_ages( $trekpilot_age_limit );
$trekpilot_adult_age    = '' !== $trekpilot_adult_age ? $trekpilot_adult_age : $trekpilot_age_defaults['adult'];
$trekpilot_child_age    = '' !== $trekpilot_child_age ? $trekpilot_child_age : $trekpilot_age_defaults['child'];
?>
<div class="trekpilot-booking-widget-wrapper" id="trekpilot_booking_widget_root" data-trek-id="<?php echo esc_attr( $trek_id ); ?>">
	
	<!-- Header Block -->
	<div class="trekpilot-widget-header">
		<h3><?php esc_html_e( 'Book Your Trek', 'trekpilot' ); ?></h3>
		<p class="trekpilot-widget-subtitle"><?php esc_html_e( 'Select departure city and dates to check availability and calculate price.', 'trekpilot' ); ?></p>
	</div>

	<!-- Step 1: Select Departure City -->
	<div class="trekpilot-widget-section">
		<h4 class="trekpilot-section-title"><span class="trekpilot-step-badge">1</span> <?php esc_html_e( 'Select Departure City', 'trekpilot' ); ?></h4>
		<div class="trekpilot-city-pills-grid" id="trekpilot_widget_city_pills">
			<?php if ( ! empty( $cities ) ) : ?>
				<?php foreach ( $cities as $c ) : ?>
					<button type="button" class="trekpilot-city-pill-btn" data-id="<?php echo esc_attr( $c->id ); ?>">
						<span class="dashicons dashicons-location"></span> <?php echo esc_html( $c->city_name ); ?>
					</button>
				<?php endforeach; ?>
			<?php else : ?>
				<p style="color:#666; font-style:italic;"><?php esc_html_e( 'No departure cities configured for this trek.', 'trekpilot' ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<!-- Step 2: Select Transportation Type (Hidden initially until City is selected) -->
	<div class="trekpilot-widget-section" id="trekpilot_widget_transport_section" style="display: none;">
		<h4 class="trekpilot-section-title"><span class="trekpilot-step-badge">2</span> <?php esc_html_e( 'Select Transportation Type', 'trekpilot' ); ?></h4>
		<div class="trekpilot-transport-options-list" id="trekpilot_widget_transport_list">
			<!-- Populated via AJAX -->
		</div>
	</div>

	<!-- Step 3: Select Date (Hidden initially until City is selected) -->
	<div class="trekpilot-widget-section" id="trekpilot_widget_date_section" style="display: none;">
		<h4 class="trekpilot-section-title"><span class="trekpilot-step-badge">3</span> <?php esc_html_e( 'Select Departure Date', 'trekpilot' ); ?></h4>
		<div class="trekpilot-loading-indicator" id="trekpilot_widget_dates_loading" style="display:none; padding: 15px 0; align-items: center; color: var(--trekpilot-primary-color, #137a7f); font-weight: 600;">
			<span class="dashicons dashicons-update" style="animation: spin 2s linear infinite; margin-right: 8px;"></span> <?php esc_html_e( 'Finding scheduled slots...', 'trekpilot' ); ?>
		</div>
		<div class="trekpilot-dates-grid" id="trekpilot_widget_dates_grid">
			<!-- Populated via AJAX -->
		</div>
	</div>

	<!-- Loader for Booking Details -->
	<div class="trekpilot-loading-indicator" id="trekpilot_widget_details_loading" style="display:none; padding: 20px 0; justify-content: center; align-items: center; color: var(--trekpilot-primary-color, #137a7f); font-weight: 600;">
		<span class="dashicons dashicons-update" style="animation: spin 2s linear infinite; margin-right: 8px;"></span> <?php esc_html_e( 'Loading booking details...', 'trekpilot' ); ?>
	</div>

	<!-- Step 3: Details Panel & Booking Calculator (Hidden until Date is selected) -->
	<div class="trekpilot-widget-section" id="trekpilot_widget_details_section" style="display: none;">
		<h4 class="trekpilot-section-title"><span class="trekpilot-step-badge">4</span> <?php esc_html_e( 'Configure Booking Details', 'trekpilot' ); ?></h4>
		
		<!-- Availability Badge & Transports Grid -->
		<div class="trekpilot-widget-details-grid">
			<div class="trekpilot-detail-card">
				<span class="trekpilot-detail-label"><?php esc_html_e( 'Seat Availability', 'trekpilot' ); ?></span>
				<div class="trekpilot-detail-value" id="trekpilot_widget_avail_seats">--</div>
			</div>
			<div class="trekpilot-detail-card">
				<span class="trekpilot-detail-label"><?php esc_html_e( 'Transport Type', 'trekpilot' ); ?></span>
				<div class="trekpilot-detail-value" id="trekpilot_widget_transport">--</div>
			</div>
			<div class="trekpilot-detail-card">
				<span class="trekpilot-detail-label"><?php esc_html_e( 'Reporting Time', 'trekpilot' ); ?></span>
				<div class="trekpilot-detail-value" id="trekpilot_widget_reporting">--</div>
			</div>
		</div>

		<!-- Pricing Info Box -->
		<div class="trekpilot-pricing-infobox">
			<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap: wrap; gap: 5px;">
				<span style="font-weight:600; flex-shrink: 0;"><?php esc_html_e( 'Per Person Rate', 'trekpilot' ); ?>:</span>
				<div style="text-align:right; white-space:nowrap; flex-shrink: 0;">
					<span id="trekpilot_widget_base_price_cross" style="text-decoration:line-through; color:#999; margin-right:8px; display:none; white-space:nowrap;"></span>
					<span id="trekpilot_widget_price_tag" style="font-size:18px; font-weight:700; color:var(--trekpilot-primary-color, #137a7f); white-space:nowrap;"></span>
				</div>
			</div>
			<p class="description" id="trekpilot_widget_child_price_tag" style="margin:5px 0 0 0; text-align:right; font-size:11px; display:none;"></p>
			<p class="description" id="trekpilot_widget_date_notes" style="margin:8px 0 0 0; font-style:italic; font-size:11px; color:#b32d2e; display:none;"></p>
		</div>

		<!-- Pax Count Inputs -->
		<div class="trekpilot-pax-calculator">
			<div class="trekpilot-pax-row">
				<div class="trekpilot-pax-label">
					<strong><?php esc_html_e( 'Adults', 'trekpilot' ); ?></strong>
					<span class="description"><?php echo esc_html( sprintf( /* translators: %s: age range, e.g. 12+ */ __( 'Age %s', 'trekpilot' ), $trekpilot_adult_age ) ); ?></span>
				</div>
				<div class="trekpilot-pax-counter">
					<button type="button" class="trekpilot-counter-btn minus" data-type="adults">-</button>
					<input type="number" id="trekpilot_widget_pax_adults" value="1" min="1" readonly />
					<button type="button" class="trekpilot-counter-btn plus" data-type="adults">+</button>
				</div>
			</div>
			<div class="trekpilot-pax-row" id="trekpilot_widget_child_row" style="display:none;">
				<div class="trekpilot-pax-label">
					<strong><?php esc_html_e( 'Children', 'trekpilot' ); ?></strong>
					<?php if ( '' !== $trekpilot_child_age ) : ?>
						<span class="description"><?php echo esc_html( sprintf( /* translators: %s: age range, e.g. 5-11 */ __( 'Age %s', 'trekpilot' ), $trekpilot_child_age ) ); ?></span>
					<?php endif; ?>
				</div>
				<div class="trekpilot-pax-counter">
					<button type="button" class="trekpilot-counter-btn minus" data-type="children">-</button>
					<input type="number" id="trekpilot_widget_pax_children" value="0" min="0" readonly />
					<button type="button" class="trekpilot-counter-btn plus" data-type="children">+</button>
				</div>
			</div>
		</div>

		<!-- Optional Add-ons checklists -->
		<div class="trekpilot-widget-addons-container" id="trekpilot_widget_addons_section" style="display:none;">
			<h5 style="margin:0 0 10px 0; font-size:12px; font-weight:600; color:#3c434a;"><?php esc_html_e( 'Choose Optional Add-ons', 'trekpilot' ); ?></h5>
			<div id="trekpilot_widget_addons_list">
				<!-- Populated dynamically -->
			</div>
		</div>

		<!-- Receipt Breakdown List -->
		<div class="trekpilot-booking-receipt">
			<h5 style="margin:0 0 8px 0; font-size:12px; font-weight:600; color:#3c434a;"><?php esc_html_e( 'Price Breakdown', 'trekpilot' ); ?></h5>
			<div class="trekpilot-receipt-rows" id="trekpilot_widget_receipt_rows">
				<!-- Dynamically generated receipt -->
			</div>
			<div class="trekpilot-receipt-total">
				<span><?php esc_html_e( 'Total', 'trekpilot' ); ?>:</span>
				<strong id="trekpilot_widget_grand_total">--</strong>
			</div>
		</div>

		<!-- Trigger Checkout Button -->
		<button type="button" class="trekpilot-book-now-btn" id="trekpilot_widget_checkout_btn">
			<?php esc_html_e( 'Book Trek Now', 'trekpilot' ); ?>
		</button>
	</div>

	<!-- Step 4: Checkout Form Dialog (Modal popup overlay) -->
	<div class="trekpilot-booking-modal" id="trekpilot_checkout_modal" style="display: none;">
		<div class="trekpilot-booking-modal-content">
			<div class="trekpilot-booking-modal-header">
				<h4><?php esc_html_e( 'Complete Your Booking', 'trekpilot' ); ?></h4>
				<span class="trekpilot-booking-modal-close" id="trekpilot_checkout_modal_close">&times;</span>
			</div>
			<div class="trekpilot-booking-modal-body">
				<form id="trekpilot_checkout_form" novalidate>
					<div class="trekpilot-form-field">
						<label for="trekpilot_checkout_name"><?php esc_html_e( 'Full Name *', 'trekpilot' ); ?></label>
						<input type="text" id="trekpilot_checkout_name" name="cust_name" required placeholder="<?php esc_attr_e( 'e.g. Nilesh Vastarpara', 'trekpilot' ); ?>" />
					</div>
					<div class="trekpilot-form-field">
						<label for="trekpilot_checkout_email"><?php esc_html_e( 'Email Address *', 'trekpilot' ); ?></label>
						<input type="email" id="trekpilot_checkout_email" name="cust_email" required placeholder="<?php esc_attr_e( 'e.g. nilesh@example.com', 'trekpilot' ); ?>" />
					</div>
					<div class="trekpilot-form-field">
						<label for="trekpilot_checkout_phone"><?php esc_html_e( 'Phone Number *', 'trekpilot' ); ?></label>
						<input type="tel" id="trekpilot_checkout_phone" name="cust_phone" required placeholder="<?php esc_attr_e( 'e.g. +91 98765 43210', 'trekpilot' ); ?>" />
					</div>
					<div class="trekpilot-form-field" id="trekpilot_checkout_pickup_field" style="display:none;">
						<label for="trekpilot_checkout_pickup"><?php esc_html_e( 'Preferred Pickup Location *', 'trekpilot' ); ?></label>
						<select id="trekpilot_checkout_pickup" name="pickup_point">
							<!-- Populated via AJAX -->
						</select>
						<span class="description" id="trekpilot_checkout_pickup_instructions" style="font-size:11px; margin-top:5px; display:block; color:#666;"></span>
					</div>
				</form>
				<div class="trekpilot-checkout-terms" id="trekpilot_checkout_terms_wrap">
					<label style="display:flex; align-items:flex-start; gap:8px; font-size:12px; line-height:1.5; color:#666; margin:0; cursor:pointer;">
						<input type="checkbox" id="trekpilot_checkout_terms_agree" style="margin:2px 0 0; flex-shrink:0;" />
						<span><?php esc_html_e( 'By booking, you agree to the cancellation, refund policies, and terms and conditions configured for this trek.', 'trekpilot' ); ?></span>
					</label>
				</div>
			</div>
			<div class="trekpilot-booking-modal-footer">
				<button type="button" class="trekpilot-modal-btn cancel" id="trekpilot_checkout_cancel_btn"><?php esc_html_e( 'Cancel', 'trekpilot' ); ?></button>
				<button type="button" class="trekpilot-modal-btn confirm" id="trekpilot_checkout_confirm_btn"><?php esc_html_e( 'Proceed to Payment', 'trekpilot' ); ?></button>
			</div>
		</div>
	</div>

	<!-- Step 4.5: Payment Modal (price breakdown + admin-configured payment method) -->
	<div class="trekpilot-booking-modal" id="trekpilot_payment_modal" style="display: none;">
		<div class="trekpilot-booking-modal-content">
			<div class="trekpilot-booking-modal-header">
				<h4><?php esc_html_e( 'Payment', 'trekpilot' ); ?></h4>
				<span class="trekpilot-booking-modal-close" id="trekpilot_payment_modal_close">&times;</span>
			</div>
			<div class="trekpilot-booking-modal-body">
				<div class="trekpilot-booking-receipt">
					<h5 style="margin:0 0 8px 0; font-size:12px; font-weight:600; color:#3c434a;"><?php esc_html_e( 'Price Breakdown', 'trekpilot' ); ?></h5>
					<div class="trekpilot-receipt-rows" id="trekpilot_payment_receipt_rows">
						<!-- Cloned from the booking calculator receipt -->
					</div>
					<div class="trekpilot-receipt-total">
						<span><?php esc_html_e( 'Total', 'trekpilot' ); ?>:</span>
						<strong id="trekpilot_payment_grand_total">--</strong>
					</div>
				</div>

				<div class="trekpilot-payment-method-box" id="trekpilot_payment_method_box">
					<!-- Populated dynamically based on admin Payment settings -->
				</div>
			</div>
			<div class="trekpilot-booking-modal-footer">
				<button type="button" class="trekpilot-modal-btn cancel" id="trekpilot_payment_back_btn"><?php esc_html_e( 'Back', 'trekpilot' ); ?></button>
				<button type="button" class="trekpilot-modal-btn confirm" id="trekpilot_payment_confirm_btn"><?php esc_html_e( 'Confirm Reservation', 'trekpilot' ); ?></button>
			</div>
		</div>
	</div>

	<!-- Step 5: Success Receipt Summary Overlay -->
	<div class="trekpilot-booking-modal" id="trekpilot_success_modal" style="display: none;">
		<div class="trekpilot-booking-modal-content" style="max-width:450px; text-align:center;">
			<div class="trekpilot-booking-modal-body" style="padding:30px 20px;">
				<span class="trekpilot-success-check" aria-hidden="true">
					<svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
				</span>
				<h3 class="trekpilot-success-title"><?php esc_html_e( 'Thank you for your booking!', 'trekpilot' ); ?></h3>
				
				<p style="font-size:13px; color:#555; margin-bottom:20px; line-height:1.4;">
					<?php esc_html_e( 'Your reservation is pending confirmation. We have sent your booking receipt by email, and you will receive another email once your reservation is confirmed.', 'trekpilot' ); ?>
				</p>
				<div class="trekpilot-success-receipt" id="trekpilot_success_receipt_body" style="margin-bottom:20px;">
					<!-- Loaded via response -->
				</div>
				<button type="button" class="trekpilot-book-now-btn" id="trekpilot_success_close_btn" style="margin:0; width:100%;">
					<?php esc_html_e( 'Close & Return', 'trekpilot' ); ?>
				</button>
			</div>
		</div>
	</div>

</div>

<!-- Sticky Bottom Booking Bar (mobile + desktop) -->
<div class="trekpilot-sticky-booking-bar" id="trekpilot_sticky_booking_bar" style="display: none;">
	<div class="trekpilot-sticky-bar-price">
		<span class="trekpilot-sticky-bar-label"><?php esc_html_e( 'Total', 'trekpilot' ); ?>:</span>
		<span class="trekpilot-sticky-bar-amount" id="trekpilot_sticky_bar_amount">--</span>
		<span class="trekpilot-sticky-bar-unit" id="trekpilot_sticky_bar_unit">/ <?php esc_html_e( 'Person', 'trekpilot' ); ?></span>
	</div>
	<button type="button" class="trekpilot-sticky-bar-cta" id="trekpilot_sticky_bar_cta">
		<?php esc_html_e( 'Book Now', 'trekpilot' ); ?>
	</button>
</div>
