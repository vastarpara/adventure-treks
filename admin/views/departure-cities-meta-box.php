<?php
/**
 * Departure Cities meta box view template
 *
 * @package    TrekPilot
 * @subpackage TrekPilot/Admin/Views
 * @author     Nilesh Vastarpara
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="trekpilot-departures-container" id="trekpilot_departures_module_root" data-trek-id="<?php echo esc_attr( get_the_ID() ); ?>">

	<!-- Control Bar -->
	<div class="trekpilot-departures-header">
		<p class="description"><?php esc_html_e( 'Manage departure locations for this trek. Drag rows to change their display order on the frontend booking widget.', 'trekpilot' ); ?></p>
		<button type="button" class="button button-primary" id="trekpilot_add_city_btn">
			+ <?php esc_html_e( 'Add Departure City', 'trekpilot' ); ?>
		</button>
	</div>

	<!-- Loading Spinner -->
	<div class="trekpilot-loading-spinner" id="trekpilot_cities_loading" style="display:none;">
		<span class="spinner is-active"></span> <?php esc_html_e( 'Loading cities data...', 'trekpilot' ); ?>
	</div>

	<!-- Next steps after adding a city (filled by admin-departures.js) -->
	<div class="trekpilot-next-steps" id="trekpilot_city_next_steps" style="display:none;"></div>

	<!-- Cities Grid List -->
	<div class="trekpilot-cities-list-wrapper">
		<table class="widefat fixed striped trekpilot-admin-table" id="trekpilot_cities_table">
			<thead>
				<tr>
					<th class="column-order" style="width: 30px;"></th>
					<th class="column-name" style="width: 200px;"><?php esc_html_e( 'City Name', 'trekpilot' ); ?></th>
					<th class="column-price" style="width: 90px;"><?php esc_html_e( 'Base Price', 'trekpilot' ); ?></th>
					<th class="column-offer" style="width: 90px;"><?php esc_html_e( 'Offer Price', 'trekpilot' ); ?></th>
					<th class="column-transport" style="width: 130px;"><?php esc_html_e( 'Transport', 'trekpilot' ); ?></th>
					<th class="column-deadline" style="width: 110px;"><?php esc_html_e( 'Deadline (Days)', 'trekpilot' ); ?></th>
					<th class="column-status" style="width: 90px;"><?php esc_html_e( 'Status', 'trekpilot' ); ?></th>
					<th class="column-actions" style="text-align: right;"><?php esc_html_e( 'Actions', 'trekpilot' ); ?></th>
				</tr>
			</thead>
			<tbody id="trekpilot_cities_tbody">
				<!-- Loaded via AJAX -->
				<tr>
					<td colspan="8" style="text-align:center; padding:20px; color:#666;">
						<?php esc_html_e( 'No departure cities configured. Click the button above to add one.', 'trekpilot' ); ?>
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<!-- Add/Edit City Modal Dialog -->
	<div class="trekpilot-modal-overlay" id="trekpilot_city_modal" style="display: none;">
		<div class="trekpilot-modal-box">
			<div class="trekpilot-modal-header">
				<h3 id="trekpilot_modal_title"><?php esc_html_e( 'Add Departure City', 'trekpilot' ); ?></h3>
				<span class="trekpilot-modal-close" id="trekpilot_modal_close_btn">&times;</span>
			</div>
			<div class="trekpilot-modal-body">
				<div id="trekpilot_city_form" class="trekpilot-city-form-wrapper">
					<input type="hidden" name="city_id" id="trekpilot_form_city_id" value="" />
					
					<div class="trekpilot-form-row">
						<label for="trekpilot_form_city_name"><?php esc_html_e( 'City Name', 'trekpilot' ); ?> <span style="color: #d63638;">*</span></label>
						<input type="text" id="trekpilot_form_city_name" name="city_name" placeholder="<?php esc_attr_e( 'e.g. Surat', 'trekpilot' ); ?>" />
					</div>

					<div class="trekpilot-form-grid-2">
						<div class="trekpilot-form-row">
							<label for="trekpilot_form_base_price"><?php esc_html_e( 'Base Price', 'trekpilot' ); ?> <span style="color: #d63638;">*</span></label>
							<input type="number" step="1" id="trekpilot_form_base_price" name="base_price" value="0" min="0" />
						</div>
						<div class="trekpilot-form-row">
							<label for="trekpilot_form_offer_price"><?php esc_html_e( 'Offer Price', 'trekpilot' ); ?></label>
							<input type="number" step="1" id="trekpilot_form_offer_price" name="offer_price" value="0" min="0" />
						</div>
					</div>

					<div class="trekpilot-form-grid-2">
						<div class="trekpilot-form-row">
							<label for="trekpilot_form_transport_type"><?php esc_html_e( 'Transport Type', 'trekpilot' ); ?></label>
							<input type="text" id="trekpilot_form_transport_type" name="transport_type" placeholder="<?php esc_attr_e( 'e.g. AC Sleeper Bus / Train', 'trekpilot' ); ?>" />
						</div>
						<div class="trekpilot-form-row">
							<label for="trekpilot_form_reporting_time"><?php esc_html_e( 'Reporting Time', 'trekpilot' ); ?></label>
							<input type="text" id="trekpilot_form_reporting_time" name="reporting_time" class="trekpilot-timepicker" placeholder="<?php esc_attr_e( 'e.g. 09:30 AM', 'trekpilot' ); ?>" />
						</div>
					</div>

					<div class="trekpilot-form-row">
						<label for="trekpilot_form_google_map_link"><?php esc_html_e( 'Google Map Link for Pickup', 'trekpilot' ); ?></label>
						<input type="url" id="trekpilot_form_google_map_link" name="google_map_link" placeholder="<?php esc_attr_e( 'https://maps.google.com/...', 'trekpilot' ); ?>" />
					</div>

					<div class="trekpilot-form-grid-2">
						<div class="trekpilot-form-row">
							<label for="trekpilot_form_booking_deadline"><?php esc_html_e( 'Booking Deadline (Days Before)', 'trekpilot' ); ?></label>
							<input type="number" id="trekpilot_form_booking_deadline" name="booking_deadline" value="3" min="0" step="1" inputmode="numeric" data-trekpilot-integer="1" />
							<span class="description"><?php esc_html_e( 'Close booking X days prior to departure.', 'trekpilot' ); ?></span>
						</div>
						<div class="trekpilot-form-row">
							<label for="trekpilot_form_status"><?php esc_html_e( 'Status', 'trekpilot' ); ?></label>
							<select id="trekpilot_form_status" name="status">
								<option value="" disabled selected><?php esc_html_e( '-- Select Status --', 'trekpilot' ); ?></option>
								<option value="active"><?php esc_html_e( 'Active', 'trekpilot' ); ?></option>
								<option value="inactive"><?php esc_html_e( 'Inactive', 'trekpilot' ); ?></option>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class="trekpilot-modal-footer">
				<button type="button" class="button" id="trekpilot_modal_cancel_btn"><?php esc_html_e( 'Cancel', 'trekpilot' ); ?></button>
				<button type="button" class="button button-primary" id="trekpilot_modal_save_btn"><?php esc_html_e( 'Save City', 'trekpilot' ); ?></button>
			</div>
		</div>
	</div>
	<!-- Dates List Modal -->
	<div class="trekpilot-modal-overlay" id="trekpilot_dates_modal" style="display: none;">
		<div class="trekpilot-modal-box" style="max-width: 850px; width: 90%;">
			<div class="trekpilot-modal-header">
				<h3><?php esc_html_e( 'Manage Departure Dates', 'trekpilot' ); ?> - <span id="trekpilot_dates_modal_city_title"></span></h3>
				<span class="trekpilot-modal-close" id="trekpilot_dates_modal_close_btn">&times;</span>
			</div>
			<div class="trekpilot-modal-body">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
					<p class="description" style="margin:0;"><?php esc_html_e( 'Schedule departure dates for this city. Overridden prices will apply only to that specific date.', 'trekpilot' ); ?></p>
					<button type="button" class="button button-primary" id="trekpilot_add_date_btn">+ <?php esc_html_e( 'Add Departure Date', 'trekpilot' ); ?></button>
				</div>
				<div class="trekpilot-loading-spinner" id="trekpilot_dates_loading" style="display:none;">
					<span class="spinner is-active"></span> <?php esc_html_e( 'Loading dates...', 'trekpilot' ); ?>
				</div>
				<div class="trekpilot-table-scroll">
					<table class="widefat fixed striped trekpilot-admin-table" id="trekpilot_dates_table" style="margin-top:10px;">
					<thead>
						<tr>
							<th style="width: 130px;"><?php esc_html_e( 'Date', 'trekpilot' ); ?></th>
							<th style="width: 110px;"><?php esc_html_e( 'Status', 'trekpilot' ); ?></th>
							<th style="width: 140px;"><?php esc_html_e( 'Seats (Tot/Book/Avail)', 'trekpilot' ); ?></th>
							<th><?php esc_html_e( 'Price Overrides', 'trekpilot' ); ?></th>
							<th><?php esc_html_e( 'Notes', 'trekpilot' ); ?></th>
							<th style="width: 120px; text-align: right;"><?php esc_html_e( 'Actions', 'trekpilot' ); ?></th>
						</tr>
					</thead>
					<tbody id="trekpilot_dates_tbody">
						<!-- Loaded via AJAX -->
					</tbody>
				</table>
				</div>
			</div>
			<div class="trekpilot-modal-footer">
				<button type="button" class="button" id="trekpilot_dates_modal_back_btn"><?php esc_html_e( 'Close', 'trekpilot' ); ?></button>
			</div>
		</div>
	</div>

	<!-- Add/Edit Date Form Modal -->
	<div class="trekpilot-modal-overlay" id="trekpilot_date_form_modal" style="display: none; z-index: 100000;">
		<div class="trekpilot-modal-box" style="max-width: 500px;">
			<div class="trekpilot-modal-header">
				<h3 id="trekpilot_date_form_title"><?php esc_html_e( 'Add Departure Date', 'trekpilot' ); ?></h3>
				<span class="trekpilot-modal-close" id="trekpilot_date_form_close_btn">&times;</span>
			</div>
			<div class="trekpilot-modal-body">
				<div id="trekpilot_date_form" class="trekpilot-city-form-wrapper">
					<input type="hidden" name="date_id" id="trekpilot_form_date_id" value="" />
					<input type="hidden" name="date_city_id" id="trekpilot_form_date_city_id" value="" />

					<div class="trekpilot-form-row">
						<label for="trekpilot_form_departure_date"><?php esc_html_e( 'Departure Date', 'trekpilot' ); ?> <span style="color: #d63638;">*</span></label>
						<input type="date" id="trekpilot_form_departure_date" name="departure_date" />
					</div>

					<div class="trekpilot-form-grid-2">
						<div class="trekpilot-form-row">
							<label for="trekpilot_form_date_total_seats"><?php esc_html_e( 'Total Seats', 'trekpilot' ); ?> <span style="color: #d63638;">*</span></label>
							<input type="number" id="trekpilot_form_date_total_seats" name="total_seats" value="30" min="1" step="1" inputmode="numeric" data-trekpilot-integer="1" />
						</div>
						<div class="trekpilot-form-row">
							<label for="trekpilot_form_date_booked_seats"><?php esc_html_e( 'Booked Seats', 'trekpilot' ); ?></label>
							<input type="number" id="trekpilot_form_date_booked_seats" name="booked_seats" value="0" min="0" step="1" inputmode="numeric" data-trekpilot-integer="1" />
						</div>
					</div>

					<div class="trekpilot-form-row">
						<label for="trekpilot_form_date_status"><?php esc_html_e( 'Status', 'trekpilot' ); ?></label>
						<select id="trekpilot_form_date_status" name="status">
							<option value="" disabled selected><?php esc_html_e( '-- Select Status --', 'trekpilot' ); ?></option>
							<option value="open"><?php esc_html_e( 'Open (Available)', 'trekpilot' ); ?></option>
							<option value="seat_count"><?php esc_html_e( 'Seat Count (Show Remaining Seats)', 'trekpilot' ); ?></option>
							<option value="few_seats"><?php esc_html_e( 'Few Seats Remaining (Hide Exact Count)', 'trekpilot' ); ?></option>
							<option value="sold_out"><?php esc_html_e( 'Sold Out', 'trekpilot' ); ?></option>
							<option value="cancelled"><?php esc_html_e( 'Cancelled', 'trekpilot' ); ?></option>
						</select>
						<span class="description"><?php esc_html_e( 'Controls how availability is shown on the front-end. Total/Booked Seats above still manage the actual count.', 'trekpilot' ); ?></span>
					</div>

					<div class="trekpilot-form-row">
						<label for="trekpilot_form_date_notes"><?php esc_html_e( 'Notes / Warning Message', 'trekpilot' ); ?></label>
						<input type="text" id="trekpilot_form_date_notes" name="notes" placeholder="<?php esc_attr_e( 'e.g. Weather updates or custom notes', 'trekpilot' ); ?>" />
					</div>

					<div style="border-top: 1px solid #ddd; margin: 15px 0 10px 0; padding-top: 10px; font-weight: bold; color: #23282d;">
						<?php esc_html_e( 'Pricing Overrides (Optional)', 'trekpilot' ); ?>
					</div>

					<div class="trekpilot-form-grid-3" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
						<div class="trekpilot-form-row">
							<label for="trekpilot_form_date_adult_price"><?php esc_html_e( 'Adult Price', 'trekpilot' ); ?></label>
							<input type="number" step="1" id="trekpilot_form_date_adult_price" name="adult_price" value="0" min="0" />
						</div>
						<div class="trekpilot-form-row">
							<label for="trekpilot_form_date_child_price"><?php esc_html_e( 'Child Price', 'trekpilot' ); ?></label>
							<input type="number" step="1" id="trekpilot_form_date_child_price" name="child_price" value="0" min="0" />
						</div>
						<div class="trekpilot-form-row">
							<label for="trekpilot_form_date_offer_price"><?php esc_html_e( 'Offer Price', 'trekpilot' ); ?></label>
							<input type="number" step="1" id="trekpilot_form_date_offer_price" name="offer_price" value="0" min="0" />
						</div>
					</div>
					<span class="description" style="display:block; margin-top:-5px; font-size:11px; color:#666;">
						<?php esc_html_e( 'Leave at 0.00 to fall back to the default departure city prices.', 'trekpilot' ); ?>
					</span>
				</div>
			</div>
			<div class="trekpilot-modal-overlay-footer" style="display: flex; justify-content: flex-end; gap: 10px; padding: 15px 20px; background: #f0f0f1; border-top: 1px solid #ccd0d4;">
				<button type="button" class="button" id="trekpilot_date_form_cancel_btn"><?php esc_html_e( 'Cancel', 'trekpilot' ); ?></button>
				<button type="button" class="button button-primary" id="trekpilot_date_form_save_btn"><?php esc_html_e( 'Save Date Config', 'trekpilot' ); ?></button>
			</div>
		</div>
	</div>
	<!-- Itinerary Builder Modal -->
	<div class="trekpilot-modal-overlay" id="trekpilot_itinerary_modal" style="display: none;">
		<div class="trekpilot-modal-box" style="max-width: 900px; width: 95%; height: 90vh;">
			<div class="trekpilot-modal-header">
				<h3><?php esc_html_e( 'Itinerary Builder', 'trekpilot' ); ?> - <span id="trekpilot_itinerary_modal_city_title"></span></h3>
				<span class="trekpilot-modal-close" id="trekpilot_itinerary_modal_close_btn">&times;</span>
			</div>
			<div class="trekpilot-modal-body" style="display: flex; gap: 20px; overflow: hidden; height: 100%; padding: 15px;">
				
				<!-- Left Column: Days List -->
				<div style="flex: 0 0 280px; width: 280px; min-width: 280px; box-sizing: border-box; display: flex; flex-direction: column; border-right: 1px solid #ddd; padding-right: 15px; height: 100%;">
					<div style="display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 15px;">
						<h4 style="margin: 0; font-size: 14px; font-weight: 600; white-space: nowrap;"><?php esc_html_e( 'Itinerary Days', 'trekpilot' ); ?></h4>
						<button type="button" class="button button-small" id="trekpilot_add_day_btn" style="flex: 0 0 auto; white-space: nowrap;">+ <?php esc_html_e( 'Add Day', 'trekpilot' ); ?></button>
					</div>
					<div id="trekpilot_itinerary_days_list" style="flex-grow: 1; overflow-y: auto; padding-right: 5px; display: flex; flex-direction: column; gap: 8px;">
						<!-- Days populated via AJAX -->
					</div>
				</div>

				<!-- Right Column: Timeline Events inside Selected Day -->
				<div style="flex-grow: 1; display: flex; flex-direction: column; height: 100%;">
					<div id="trekpilot_no_day_selected_msg" style="display: flex; flex-grow: 1; justify-content: center; align-items: center; text-align: center; color: #888;">
						<div>
							<span class="dashicons dashicons-calendar-alt" style="font-size: 48px; width:48px; height:48px; display:block; margin:0 auto 10px auto;"></span>
							<?php esc_html_e( 'Select a day from the left sidebar to manage its activities timeline.', 'trekpilot' ); ?>
						</div>
					</div>

					<div id="trekpilot_day_timeline_wrapper" style="display: none; flex-direction: column; height: 100%;">
						<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
							<div>
								<h4 id="trekpilot_selected_day_title" style="margin: 0; font-size: 15px; font-weight: 600;"></h4>
								<p id="trekpilot_selected_day_desc" style="margin: 3px 0 0 0; font-size: 12px; color: #666; font-style: italic;"></p>
							</div>
							<div style="display:flex; align-items:center; gap:8px; flex-shrink:0; white-space:nowrap;">
								<button type="button" class="button" id="trekpilot_edit_selected_day_btn"><?php esc_html_e( 'Edit Day Settings', 'trekpilot' ); ?></button>
								<button type="button" class="button button-primary" id="trekpilot_add_activity_btn">+ <?php esc_html_e( 'Add Activity', 'trekpilot' ); ?></button>
							</div>
						</div>

						<div id="trekpilot_day_activities_timeline" style="flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; padding: 5px 0;">
							<!-- Activities populated via AJAX -->
						</div>
					</div>
				</div>

			</div>
			<div class="trekpilot-modal-footer">
				<button type="button" class="button" id="trekpilot_itinerary_modal_back_btn"><?php esc_html_e( 'Close Builder', 'trekpilot' ); ?></button>
			</div>
		</div>
	</div>

	<!-- Add/Edit Day Form Modal -->
	<div class="trekpilot-modal-overlay" id="trekpilot_day_form_modal" style="display: none; z-index: 100000;">
		<div class="trekpilot-modal-box" style="max-width: 450px;">
			<div class="trekpilot-modal-header">
				<h3 id="trekpilot_day_form_title"><?php esc_html_e( 'Add Day Settings', 'trekpilot' ); ?></h3>
				<span class="trekpilot-modal-close" id="trekpilot_day_form_close_btn">&times;</span>
			</div>
			<div class="trekpilot-modal-body">
				<div id="trekpilot_day_form" class="trekpilot-city-form-wrapper">
					<input type="hidden" name="day_id" id="trekpilot_form_day_id" value="" />
					
					<div class="trekpilot-form-row">
						<label for="trekpilot_form_day_number"><?php esc_html_e( 'Day Number', 'trekpilot' ); ?> <span style="color: #d63638;">*</span></label>
						<input type="number" id="trekpilot_form_day_number" name="day_number" value="1" min="0" step="1" inputmode="numeric" data-trekpilot-integer="1" />
						<span class="description"><?php esc_html_e( 'Use 0 for departure day assembly info, 1 for start, etc.', 'trekpilot' ); ?></span>
					</div>

					<div class="trekpilot-form-row">
						<label for="trekpilot_form_day_title"><?php esc_html_e( 'Day Title', 'trekpilot' ); ?> <span style="color: #d63638;">*</span></label>
						<input type="text" id="trekpilot_form_day_title" name="title" placeholder="<?php esc_attr_e( 'e.g. Arrival at Basecamp & Briefing', 'trekpilot' ); ?>" />
					</div>

					<div class="trekpilot-form-row">
						<label for="trekpilot_form_day_description"><?php esc_html_e( 'Day Overview / Description', 'trekpilot' ); ?></label>
						<textarea id="trekpilot_form_day_description" name="description" rows="4" placeholder="<?php esc_attr_e( 'Brief outline of this day\'s trekking milestones...', 'trekpilot' ); ?>"></textarea>
					</div>
				</div>
			</div>
			<div class="trekpilot-modal-overlay-footer" style="display: flex; justify-content: flex-end; gap: 10px; padding: 15px 20px; background: #f0f0f1; border-top: 1px solid #ccd0d4;">
				<button type="button" class="button" id="trekpilot_day_form_cancel_btn"><?php esc_html_e( 'Cancel', 'trekpilot' ); ?></button>
				<button type="button" class="button button-primary" id="trekpilot_day_form_save_btn"><?php esc_html_e( 'Save Day', 'trekpilot' ); ?></button>
			</div>
		</div>
	</div>

	<!-- Add/Edit Activity Item Form Modal -->
	<div class="trekpilot-modal-overlay" id="trekpilot_activity_form_modal" style="display: none; z-index: 100001;">
		<div class="trekpilot-modal-box" style="max-width: 500px;">
			<div class="trekpilot-modal-header">
				<h3 id="trekpilot_activity_form_title"><?php esc_html_e( 'Add Activity Event', 'trekpilot' ); ?></h3>
				<span class="trekpilot-modal-close" id="trekpilot_activity_form_close_btn">&times;</span>
			</div>
			<div class="trekpilot-modal-body">
				<div id="trekpilot_activity_form" class="trekpilot-city-form-wrapper">
					<input type="hidden" name="activity_id" id="trekpilot_form_activity_id" value="" />
					
					<div class="trekpilot-form-grid-2">
						<div class="trekpilot-form-row">
							<label for="trekpilot_form_activity_time"><?php esc_html_e( 'Time', 'trekpilot' ); ?></label>
							<input type="text" id="trekpilot_form_activity_time" name="item_time" class="trekpilot-timepicker" placeholder="<?php esc_attr_e( 'e.g. 08:30 AM', 'trekpilot' ); ?>" />
						</div>
						<div class="trekpilot-form-row">
							<label for="trekpilot_form_activity_icon"><?php esc_html_e( 'Event Icon', 'trekpilot' ); ?></label>
							<select id="trekpilot_form_activity_icon" name="icon">
								<option value="" disabled selected><?php esc_html_e( '-- Select Icon --', 'trekpilot' ); ?></option>
								<option value="dashicons-palmtree">🌴 <?php esc_html_e( 'Trek / Outdoors', 'trekpilot' ); ?></option>
								<option value="dashicons-car">🚗 <?php esc_html_e( 'Transport / Travel', 'trekpilot' ); ?></option>
								<option value="dashicons-food">🍴 <?php esc_html_e( 'Meals / Dining', 'trekpilot' ); ?></option>
								<option value="dashicons-location">📍 <?php esc_html_e( 'Sightseeing', 'trekpilot' ); ?></option>
								<option value="dashicons-admin-home">🏕️ <?php esc_html_e( 'Hotel / Camp Stay', 'trekpilot' ); ?></option>
								<option value="dashicons-clock">⏰ <?php esc_html_e( 'Reporting', 'trekpilot' ); ?></option>
								<option value="dashicons-marker">🚩 <?php esc_html_e( 'Summit / Milestone', 'trekpilot' ); ?></option>
							</select>
						</div>
					</div>

					<div class="trekpilot-form-row">
						<label for="trekpilot_form_activity_title"><?php esc_html_e( 'Activity Title', 'trekpilot' ); ?> <span style="color: #d63638;">*</span></label>
						<input type="text" id="trekpilot_form_activity_title" name="title" placeholder="<?php esc_attr_e( 'e.g. Hot Breakfast served at Camp', 'trekpilot' ); ?>" />
					</div>

					<div class="trekpilot-form-row">
						<label for="trekpilot_form_activity_image"><?php esc_html_e( 'Event Image', 'trekpilot' ); ?></label>
						<div style="display:flex; gap:10px; align-items:center;">
							<input type="text" id="trekpilot_form_activity_image" name="image_url" placeholder="<?php esc_attr_e( 'https://...', 'trekpilot' ); ?>" class="regular-text" style="flex-grow:1;" />
							<button type="button" class="button" id="trekpilot_select_activity_image_btn"><?php esc_html_e( 'Select', 'trekpilot' ); ?></button>
						</div>
						<div id="trekpilot_activity_image_preview" style="margin-top:10px;"></div>
					</div>

					<div class="trekpilot-form-row">
						<label for="trekpilot_form_activity_desc"><?php esc_html_e( 'Activity Details', 'trekpilot' ); ?></label>
						<textarea id="trekpilot_form_activity_desc" name="description" rows="4" placeholder="<?php esc_attr_e( 'Detailed outline of what trekkers do during this activity slot...', 'trekpilot' ); ?>"></textarea>
					</div>
				</div>
			</div>
			<div class="trekpilot-modal-overlay-footer" style="display: flex; justify-content: flex-end; gap: 10px; padding: 15px 20px; background: #f0f0f1; border-top: 1px solid #ccd0d4;">
				<button type="button" class="button" id="trekpilot_activity_form_cancel_btn"><?php esc_html_e( 'Cancel', 'trekpilot' ); ?></button>
				<button type="button" class="button button-primary" id="trekpilot_activity_form_save_btn"><?php esc_html_e( 'Save Activity', 'trekpilot' ); ?></button>
			</div>
		</div>
	</div>
	<!-- Pricing Manager Modal -->
	<div class="trekpilot-modal-overlay" id="trekpilot_pricing_modal" style="display: none;">
		<div class="trekpilot-modal-box" style="max-width: 750px; width: 90%; height: 85vh;">
			<div class="trekpilot-modal-header">
				<h3><?php esc_html_e( 'Configure Pricing Rules & Add-ons', 'trekpilot' ); ?> - <span id="trekpilot_pricing_modal_city_title"></span></h3>
				<span class="trekpilot-modal-close" id="trekpilot_pricing_modal_close_btn">&times;</span>
			</div>
			<div class="trekpilot-modal-body" style="overflow-y: auto;">
				<div id="trekpilot_pricing_form" class="trekpilot-city-form-wrapper">
					<input type="hidden" name="pricing_city_id" id="trekpilot_pricing_city_id" value="" />
					
					<!-- Base Pricing Section -->
					<div style="font-weight: bold; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-bottom: 15px; color: #23282d;">
						<?php esc_html_e( 'Base Pricing', 'trekpilot' ); ?>
					</div>
					<div class="trekpilot-form-grid-3" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 20px;">
						<div class="trekpilot-form-row" style="margin-bottom:0;">
							<label for="trekpilot_pricing_adult_price"><?php esc_html_e( 'Adult Price', 'trekpilot' ); ?> <span style="color: #d63638;">*</span></label>
							<input type="number" step="1" id="trekpilot_pricing_adult_price" name="adult_price" value="0" min="0" />
						</div>
						<div class="trekpilot-form-row" style="margin-bottom:0;">
							<label for="trekpilot_pricing_child_price"><?php esc_html_e( 'Child Price', 'trekpilot' ); ?></label>
							<input type="number" step="1" id="trekpilot_pricing_child_price" name="child_price" value="0" min="0" />
						</div>
						<div class="trekpilot-form-row" style="margin-bottom:0;">
							<label for="trekpilot_pricing_offer_price"><?php esc_html_e( 'Offer Price', 'trekpilot' ); ?></label>
							<input type="number" step="1" id="trekpilot_pricing_offer_price" name="offer_price" value="0" min="0" />
						</div>
					</div>

					<!-- Group Discounts Repeater -->
					<div style="display:flex; justify-content:space-between; align-items:center; font-weight: bold; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-bottom: 15px; color: #23282d; margin-top:20px;">
						<span><?php esc_html_e( 'Group Discounts', 'trekpilot' ); ?></span>
						<button type="button" class="button button-small" id="trekpilot_add_group_discount_rule_btn">+ <?php esc_html_e( 'Add Discount Rule', 'trekpilot' ); ?></button>
					</div>
					<div id="trekpilot_group_discount_rules_list" style="margin-bottom: 20px;">
						<!-- Group rules list -->
					</div>

					<!-- Extra Charges Repeater -->
					<div style="display:flex; justify-content:space-between; align-items:center; font-weight: bold; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-bottom: 15px; color: #23282d; margin-top:20px;">
						<span><?php esc_html_e( 'Mandatory Extra Charges', 'trekpilot' ); ?></span>
						<button type="button" class="button button-small" id="trekpilot_add_extra_charge_btn">+ <?php esc_html_e( 'Add Fee/Charge', 'trekpilot' ); ?></button>
					</div>
					<div id="trekpilot_extra_charges_list" style="margin-bottom: 20px;">
						<!-- Extra fees list -->
					</div>

					<!-- Optional Add-ons Repeater -->
					<div style="display:flex; justify-content:space-between; align-items:center; font-weight: bold; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-bottom: 15px; color: #23282d; margin-top:20px;">
						<span><?php esc_html_e( 'Optional Add-ons', 'trekpilot' ); ?></span>
						<button type="button" class="button button-small" id="trekpilot_add_optional_addon_btn">+ <?php esc_html_e( 'Add Optional Extra', 'trekpilot' ); ?></button>
					</div>
					<div id="trekpilot_optional_addons_list" style="margin-bottom: 20px;">
						<!-- Add-ons list -->
					</div>

					<!-- Transportation Options Repeater -->
					<div style="display:flex; justify-content:space-between; align-items:center; font-weight: bold; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-bottom: 15px; color: #23282d; margin-top:20px;">
						<span><?php esc_html_e( 'Transportation Options', 'trekpilot' ); ?></span>
						<button type="button" class="button button-small" id="trekpilot_add_transport_option_btn">+ <?php esc_html_e( 'Add Transportation Option', 'trekpilot' ); ?></button>
					</div>
					<p class="description" style="margin: -10px 0 12px;"><?php esc_html_e( 'Shown to visitors as a single choice right after they pick this city. The additional price is added per person on top of the base fare.', 'trekpilot' ); ?></p>
					<div id="trekpilot_transport_options_list" style="margin-bottom: 20px;">
						<!-- Transport options list -->
					</div>

				</div>
			</div>
			<div class="trekpilot-modal-overlay-footer" style="display: flex; justify-content: flex-end; gap: 10px; padding: 15px 20px; background: #f0f0f1; border-top: 1px solid #ccd0d4;">
				<button type="button" class="button" id="trekpilot_pricing_modal_cancel_btn"><?php esc_html_e( 'Cancel', 'trekpilot' ); ?></button>
				<button type="button" class="button button-primary" id="trekpilot_pricing_modal_save_btn"><?php esc_html_e( 'Save Pricing Setup', 'trekpilot' ); ?></button>
			</div>
		</div>
	</div>

	<!-- Pickup Points Manager Modal -->
	<div class="trekpilot-modal-overlay" id="trekpilot_pickups_modal" style="display: none;">
		<div class="trekpilot-modal-box" style="max-width: 800px; width: 90%; height: 85vh;">
			<div class="trekpilot-modal-header">
				<h3><?php esc_html_e( 'Manage Pickup Points', 'trekpilot' ); ?> - <span id="trekpilot_pickups_modal_city_title"></span></h3>
				<span class="trekpilot-modal-close" id="trekpilot_pickups_modal_close_btn">&times;</span>
			</div>
			<div class="trekpilot-modal-body" style="overflow-y: auto;">
				
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
					<p class="description" style="margin:0;"><?php esc_html_e( 'Configure pickup points for this city. You can drag and drop to reorder them.', 'trekpilot' ); ?></p>
					<button type="button" class="button button-primary" id="trekpilot_add_pickup_btn">+ <?php esc_html_e( 'Add Pickup Point', 'trekpilot' ); ?></button>
				</div>
				
				<div class="trekpilot-loading-spinner" id="trekpilot_pickups_loading" style="display:none;">
					<span class="spinner is-active"></span> <?php esc_html_e( 'Loading pickup points...', 'trekpilot' ); ?>
				</div>

				<div class="trekpilot-table-scroll">
					<table class="widefat fixed striped trekpilot-admin-table" id="trekpilot_pickups_table" style="margin-top:10px;">
					<thead>
						<tr>
							<th style="width: 40px;"></th>
							<th style="width: 200px;"><?php esc_html_e( 'Location Name', 'trekpilot' ); ?></th>
							<th style="width: 120px;"><?php esc_html_e( 'Time', 'trekpilot' ); ?></th>
							<th><?php esc_html_e( 'Map URL', 'trekpilot' ); ?></th>
							<th style="width: 120px; text-align: right;"><?php esc_html_e( 'Actions', 'trekpilot' ); ?></th>
						</tr>
					</thead>
					<tbody id="trekpilot_pickups_tbody">
						<!-- Loaded via AJAX -->
					</tbody>
				</table>
				</div>

				<!-- Pickup Point Form (Hidden by default) -->
				<div id="trekpilot_pickup_form_container" style="display: none; margin-top: 20px; padding: 15px; border: 1px solid #ccd0d4; background: #fff;">
					<h4 style="margin-top: 0;" id="trekpilot_pickup_form_title"><?php esc_html_e( 'Add Pickup Point', 'trekpilot' ); ?></h4>
					<input type="hidden" id="trekpilot_pickup_id" name="pickup_id" value="" />
					
					<div class="trekpilot-form-grid-2">
						<div class="trekpilot-form-row">
							<label for="trekpilot_pickup_location_name"><?php esc_html_e( 'Location Name', 'trekpilot' ); ?> <span style="color: #d63638;">*</span></label>
							<input type="text" id="trekpilot_pickup_location_name" name="location_name" placeholder="<?php esc_attr_e( 'e.g. Dehradun Railway Station', 'trekpilot' ); ?>" />
						</div>
						<div class="trekpilot-form-row">
							<label for="trekpilot_pickup_time"><?php esc_html_e( 'Pickup Time', 'trekpilot' ); ?></label>
							<input type="text" id="trekpilot_pickup_time" name="pickup_time" class="trekpilot-timepicker" placeholder="<?php esc_attr_e( 'e.g. 06:30 AM', 'trekpilot' ); ?>" />
						</div>
					</div>
					
					<div class="trekpilot-form-row">
						<label for="trekpilot_pickup_map_url"><?php esc_html_e( 'Google Maps URL', 'trekpilot' ); ?></label>
						<input type="url" id="trekpilot_pickup_map_url" name="google_maps_url" placeholder="<?php esc_attr_e( 'https://maps.google.com/...', 'trekpilot' ); ?>" class="regular-text" style="width: 100%;" />
					</div>
					
					<div class="trekpilot-form-row">
						<label for="trekpilot_pickup_instructions"><?php esc_html_e( 'Special Instructions', 'trekpilot' ); ?></label>
						<textarea id="trekpilot_pickup_instructions" name="instructions" rows="2" placeholder="<?php esc_attr_e( 'e.g. Wait near Gate 1...', 'trekpilot' ); ?>"></textarea>
					</div>
					
					<div style="margin-top: 15px; display: flex; justify-content: flex-end; gap: 10px;">
						<button type="button" class="button" id="trekpilot_pickup_form_cancel_btn"><?php esc_html_e( 'Cancel', 'trekpilot' ); ?></button>
						<button type="button" class="button button-primary" id="trekpilot_pickup_form_save_btn"><?php esc_html_e( 'Save Pickup Point', 'trekpilot' ); ?></button>
					</div>
				</div>

			</div>
		</div>
	</div>

</div>
