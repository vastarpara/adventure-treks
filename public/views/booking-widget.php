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

// Age ranges shown beside the Adults / Children counters (set per trek; defaults respect the trek's Age Limit when blank).
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
$at_age_limit = (string) $wpdb->get_var( $wpdb->prepare( "SELECT age_limit FROM {$wpdb->prefix}at_treks WHERE post_id = %d", $trek_id ) );
$at_adult_age = get_post_meta( $trek_id, '_at_adult_age', true );
$at_child_age = get_post_meta( $trek_id, '_at_child_age', true );
$at_age_defaults = \AdventureTreks\Admin\Controllers\TrekMetaBoxController::default_ages( $at_age_limit );
$at_adult_age    = '' !== $at_adult_age ? $at_adult_age : $at_age_defaults['adult'];
$at_child_age    = '' !== $at_child_age ? $at_child_age : $at_age_defaults['child'];
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

	<!-- Step 2: Select Transportation Type (Hidden initially until City is selected) -->
	<div class="at-widget-section" id="at_widget_transport_section" style="display: none;">
		<h4 class="at-section-title"><span class="at-step-badge">2</span> <?php esc_html_e( 'Select Transportation Type', 'adventure-treks' ); ?></h4>
		<div class="at-transport-options-list" id="at_widget_transport_list">
			<!-- Populated via AJAX -->
		</div>
	</div>

	<!-- Step 3: Select Date (Hidden initially until City is selected) -->
	<div class="at-widget-section" id="at_widget_date_section" style="display: none;">
		<h4 class="at-section-title"><span class="at-step-badge">3</span> <?php esc_html_e( 'Select Departure Date', 'adventure-treks' ); ?></h4>
		<div class="at-loading-indicator" id="at_widget_dates_loading" style="display:none; padding: 15px 0; align-items: center; color: var(--at-primary-color, #137a7f); font-weight: 600;">
			<span class="dashicons dashicons-update" style="animation: spin 2s linear infinite; margin-right: 8px;"></span> <?php esc_html_e( 'Finding scheduled slots...', 'adventure-treks' ); ?>
		</div>
		<div class="at-dates-grid" id="at_widget_dates_grid">
			<!-- Populated via AJAX -->
		</div>
	</div>

	<!-- Loader for Booking Details -->
	<div class="at-loading-indicator" id="at_widget_details_loading" style="display:none; padding: 20px 0; justify-content: center; align-items: center; color: var(--at-primary-color, #137a7f); font-weight: 600;">
		<span class="dashicons dashicons-update" style="animation: spin 2s linear infinite; margin-right: 8px;"></span> <?php esc_html_e( 'Loading booking details...', 'adventure-treks' ); ?>
		<style>@keyframes spin { 100% { transform: rotate(360deg); } }</style>
	</div>

	<!-- Step 3: Details Panel & Booking Calculator (Hidden until Date is selected) -->
	<div class="at-widget-section" id="at_widget_details_section" style="display: none;">
		<h4 class="at-section-title"><span class="at-step-badge">4</span> <?php esc_html_e( 'Configure Booking Details', 'adventure-treks' ); ?></h4>
		
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
					<span id="at_widget_price_tag" style="font-size:18px; font-weight:700; color:var(--at-primary-color, #137a7f); white-space:nowrap;"></span>
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
					<span class="description"><?php echo esc_html( sprintf( /* translators: %s: age range, e.g. 12+ */ __( 'Age %s', 'adventure-treks' ), $at_adult_age ) ); ?></span>
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
					<?php if ( '' !== $at_child_age ) : ?>
						<span class="description"><?php echo esc_html( sprintf( /* translators: %s: age range, e.g. 5-11 */ __( 'Age %s', 'adventure-treks' ), $at_child_age ) ); ?></span>
					<?php endif; ?>
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
				<span><?php esc_html_e( 'Total', 'adventure-treks' ); ?>:</span>
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
				<form id="at_checkout_form" novalidate>
					<div class="at-form-field">
						<label for="at_checkout_name"><?php esc_html_e( 'Full Name *', 'adventure-treks' ); ?></label>
						<input type="text" id="at_checkout_name" name="cust_name" required placeholder="<?php esc_attr_e( 'e.g. Nilesh Vastarpara', 'adventure-treks' ); ?>" />
					</div>
					<div class="at-form-field">
						<label for="at_checkout_email"><?php esc_html_e( 'Email Address *', 'adventure-treks' ); ?></label>
						<input type="email" id="at_checkout_email" name="cust_email" required placeholder="<?php esc_attr_e( 'e.g. nilesh@example.com', 'adventure-treks' ); ?>" />
					</div>
					<div class="at-form-field">
						<label for="at_checkout_phone"><?php esc_html_e( 'Phone Number *', 'adventure-treks' ); ?></label>
						<input type="tel" id="at_checkout_phone" name="cust_phone" required placeholder="<?php esc_attr_e( 'e.g. +91 98765 43210', 'adventure-treks' ); ?>" />
					</div>
					<div class="at-form-field" id="at_checkout_pickup_field" style="display:none;">
						<label for="at_checkout_pickup"><?php esc_html_e( 'Preferred Pickup Location *', 'adventure-treks' ); ?></label>
						<select id="at_checkout_pickup" name="pickup_point">
							<!-- Populated via AJAX -->
						</select>
						<span class="description" id="at_checkout_pickup_instructions" style="font-size:11px; margin-top:5px; display:block; color:#666;"></span>
					</div>
				</form>
				<div class="at-checkout-terms" id="at_checkout_terms_wrap">
					<label style="display:flex; align-items:flex-start; gap:6px; font-size:11px; color:#666; margin:0; cursor:pointer;">
						<input type="checkbox" id="at_checkout_terms_agree" style="margin-top:2px;" />
						<span><?php esc_html_e( 'By booking, you agree to the cancellation, refund policies, and terms and conditions configured for this trek.', 'adventure-treks' ); ?></span>
					</label>
				</div>
			</div>
			<div class="at-booking-modal-footer">
				<button type="button" class="at-modal-btn cancel" id="at_checkout_cancel_btn"><?php esc_html_e( 'Cancel', 'adventure-treks' ); ?></button>
				<button type="button" class="at-modal-btn confirm" id="at_checkout_confirm_btn"><?php esc_html_e( 'Proceed to Payment', 'adventure-treks' ); ?></button>
			</div>
		</div>
	</div>

	<!-- Step 4.5: Payment Modal (price breakdown + admin-configured payment method) -->
	<div class="at-booking-modal" id="at_payment_modal" style="display: none;">
		<div class="at-booking-modal-content">
			<div class="at-booking-modal-header">
				<h4><?php esc_html_e( 'Payment', 'adventure-treks' ); ?></h4>
				<span class="at-booking-modal-close" id="at_payment_modal_close">&times;</span>
			</div>
			<div class="at-booking-modal-body">
				<div class="at-booking-receipt">
					<h5 style="margin:0 0 8px 0; font-size:12px; font-weight:600; color:#3c434a;"><?php esc_html_e( 'Price Breakdown', 'adventure-treks' ); ?></h5>
					<div class="at-receipt-rows" id="at_payment_receipt_rows">
						<!-- Cloned from the booking calculator receipt -->
					</div>
					<div class="at-receipt-total">
						<span><?php esc_html_e( 'Total', 'adventure-treks' ); ?>:</span>
						<strong id="at_payment_grand_total">--</strong>
					</div>
				</div>

				<div class="at-payment-method-box" id="at_payment_method_box">
					<!-- Populated dynamically based on admin Payment settings -->
				</div>
			</div>
			<div class="at-booking-modal-footer">
				<button type="button" class="at-modal-btn cancel" id="at_payment_back_btn"><?php esc_html_e( 'Back', 'adventure-treks' ); ?></button>
				<button type="button" class="at-modal-btn confirm" id="at_payment_confirm_btn"><?php esc_html_e( 'Confirm Reservation', 'adventure-treks' ); ?></button>
			</div>
		</div>
	</div>

	<!-- Step 5: Success Receipt Summary Overlay -->
	<div class="at-booking-modal" id="at_success_modal" style="display: none;">
		<div class="at-booking-modal-content" style="max-width:450px; text-align:center;">
			<div class="at-booking-modal-body" style="padding:30px 20px;">
				<span class="dashicons dashicons-saved" style="color:#385723; font-size:64px; width:64px; height:64px; display:block; margin:0 auto 15px auto;"></span>
				<h3 style="color:#385723; margin:0 0 10px 0; font-size:22px; font-weight:700;"><?php esc_html_e( 'Thank you for your booking!', 'adventure-treks' ); ?></h3>
				<p style="font-size:13px; color:#555; margin-bottom:20px; line-height:1.4;">
					<?php esc_html_e( 'Your reservation is pending confirmation. We have sent your booking receipt by email, and you will receive another email once your reservation is confirmed.', 'adventure-treks' ); ?>
				</p>
				<div class="at-success-receipt" id="at_success_receipt_body" style="margin-bottom:20px;">
					<!-- Loaded via response -->
				</div>
				<button type="button" class="at-book-now-btn" id="at_success_close_btn" style="margin:0; width:100%;">
					<?php esc_html_e( 'Close & Return', 'adventure-treks' ); ?>
				</button>
			</div>
		</div>
	</div>

</div>

<!-- Sticky Bottom Booking Bar (mobile + desktop) -->
<div class="at-sticky-booking-bar" id="at_sticky_booking_bar" style="display: none;">
	<div class="at-sticky-bar-price">
		<span class="at-sticky-bar-label"><?php esc_html_e( 'Total', 'adventure-treks' ); ?>:</span>
		<span class="at-sticky-bar-amount" id="at_sticky_bar_amount">--</span>
		<span class="at-sticky-bar-unit" id="at_sticky_bar_unit">/ <?php esc_html_e( 'Person', 'adventure-treks' ); ?></span>
	</div>
	<button type="button" class="at-sticky-bar-cta" id="at_sticky_bar_cta">
		<?php esc_html_e( 'Book Now', 'adventure-treks' ); ?>
	</button>
</div>
