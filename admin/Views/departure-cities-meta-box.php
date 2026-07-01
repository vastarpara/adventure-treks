<?php
/**
 * Departure Cities meta box view template
 *
 * @package    AdventureTreks
 * @subpackage AdventureTreks/Admin/Views
 * @author     Nilesh Vastarpara
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="at-departures-container" id="at_departures_module_root" data-trek-id="<?php echo esc_attr( get_the_ID() ); ?>">

	<!-- Control Bar -->
	<div class="at-departures-header">
		<p class="description"><?php esc_html_e( 'Manage departure locations for this trek. Drag rows to change their display order on the frontend booking widget.', 'adventure-treks' ); ?></p>
		<button type="button" class="button button-primary" id="at_add_city_btn">
			+ <?php esc_html_e( 'Add Departure City', 'adventure-treks' ); ?>
		</button>
	</div>

	<!-- Loading Spinner -->
	<div class="at-loading-spinner" id="at_cities_loading" style="display:none;">
		<span class="spinner is-active"></span> <?php esc_html_e( 'Loading cities data...', 'adventure-treks' ); ?>
	</div>

	<!-- Cities Grid List -->
	<div class="at-cities-list-wrapper">
		<table class="wp-list-table widefat fixed striped posts" id="at_cities_table">
			<thead>
				<tr>
					<th class="column-order" style="width: 40px;"></th>
					<th class="column-name"><?php esc_html_e( 'City Name', 'adventure-treks' ); ?></th>
					<th class="column-price" style="width: 120px;"><?php esc_html_e( 'Base Price', 'adventure-treks' ); ?></th>
					<th class="column-offer" style="width: 120px;"><?php esc_html_e( 'Offer Price', 'adventure-treks' ); ?></th>
					<th class="column-transport" style="width: 150px;"><?php esc_html_e( 'Transport', 'adventure-treks' ); ?></th>
					<th class="column-deadline" style="width: 120px;"><?php esc_html_e( 'Deadline (Days)', 'adventure-treks' ); ?></th>
					<th class="column-status" style="width: 100px;"><?php esc_html_e( 'Status', 'adventure-treks' ); ?></th>
					<th class="column-actions" style="width: 280px; text-align: right;"><?php esc_html_e( 'Actions', 'adventure-treks' ); ?></th>
				</tr>
			</thead>
			<tbody id="at_cities_tbody">
				<!-- Loaded via AJAX -->
				<tr>
					<td colspan="8" style="text-align:center; padding:20px; color:#666;">
						<?php esc_html_e( 'No departure cities configured. Click the button above to add one.', 'adventure-treks' ); ?>
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<!-- Add/Edit City Modal Dialog -->
	<div class="at-modal-overlay" id="at_city_modal" style="display: none;">
		<div class="at-modal-box">
			<div class="at-modal-header">
				<h3 id="at_modal_title"><?php esc_html_e( 'Add Departure City', 'adventure-treks' ); ?></h3>
				<span class="at-modal-close" id="at_modal_close_btn">&times;</span>
			</div>
			<div class="at-modal-body">
				<div id="at_city_form" class="at-city-form-wrapper">
					<input type="hidden" name="city_id" id="at_form_city_id" value="" />
					
					<div class="at-form-row">
						<label for="at_form_city_name"><?php esc_html_e( 'City Name *', 'adventure-treks' ); ?></label>
						<input type="text" id="at_form_city_name" name="city_name" placeholder="e.g. Surat" />
					</div>

					<div class="at-form-grid-2">
						<div class="at-form-row">
							<label for="at_form_base_price"><?php esc_html_e( 'Base Price *', 'adventure-treks' ); ?></label>
							<input type="number" step="0.01" id="at_form_base_price" name="base_price" value="0.00" />
						</div>
						<div class="at-form-row">
							<label for="at_form_offer_price"><?php esc_html_e( 'Offer Price', 'adventure-treks' ); ?></label>
							<input type="number" step="0.01" id="at_form_offer_price" name="offer_price" value="0.00" />
						</div>
					</div>

					<div class="at-form-grid-2">
						<div class="at-form-row">
							<label for="at_form_transport_type"><?php esc_html_e( 'Transport Type', 'adventure-treks' ); ?></label>
							<input type="text" id="at_form_transport_type" name="transport_type" placeholder="e.g. AC Sleeper Bus / Train" />
						</div>
						<div class="at-form-row">
							<label for="at_form_reporting_time"><?php esc_html_e( 'Reporting Time', 'adventure-treks' ); ?></label>
							<input type="text" id="at_form_reporting_time" name="reporting_time" placeholder="e.g. 09:30 PM" />
						</div>
					</div>

					<div class="at-form-row">
						<label for="at_form_google_map_link"><?php esc_html_e( 'Google Map Link for Pickup', 'adventure-treks' ); ?></label>
						<input type="url" id="at_form_google_map_link" name="google_map_link" placeholder="https://maps.google.com/..." />
					</div>

					<div class="at-form-grid-2">
						<div class="at-form-row">
							<label for="at_form_booking_deadline"><?php esc_html_e( 'Booking Deadline (Days Before)', 'adventure-treks' ); ?></label>
							<input type="number" id="at_form_booking_deadline" name="booking_deadline" value="3" />
							<span class="description"><?php esc_html_e( 'Close booking X days prior to departure.', 'adventure-treks' ); ?></span>
						</div>
						<div class="at-form-row">
							<label for="at_form_status"><?php esc_html_e( 'Status', 'adventure-treks' ); ?></label>
							<select id="at_form_status" name="status">
								<option value="" disabled selected><?php esc_html_e( '-- Select Status --', 'adventure-treks' ); ?></option>
								<option value="active"><?php esc_html_e( 'Active', 'adventure-treks' ); ?></option>
								<option value="inactive"><?php esc_html_e( 'Inactive', 'adventure-treks' ); ?></option>
							</select>
						</div>
					</div>
				</div>
			</div>
			<div class="at-modal-footer">
				<button type="button" class="button" id="at_modal_cancel_btn"><?php esc_html_e( 'Cancel', 'adventure-treks' ); ?></button>
				<button type="button" class="button button-primary" id="at_modal_save_btn"><?php esc_html_e( 'Save City', 'adventure-treks' ); ?></button>
			</div>
		</div>
	</div>
	<!-- Dates List Modal -->
	<div class="at-modal-overlay" id="at_dates_modal" style="display: none;">
		<div class="at-modal-box" style="max-width: 850px; width: 90%;">
			<div class="at-modal-header">
				<h3><?php esc_html_e( 'Manage Departure Dates', 'adventure-treks' ); ?> - <span id="at_dates_modal_city_title"></span></h3>
				<span class="at-modal-close" id="at_dates_modal_close_btn">&times;</span>
			</div>
			<div class="at-modal-body">
				<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
					<p class="description" style="margin:0;"><?php esc_html_e( 'Schedule departure dates for this city. Overridden prices will apply only to that specific date.', 'adventure-treks' ); ?></p>
					<button type="button" class="button button-primary" id="at_add_date_btn">+ <?php esc_html_e( 'Add Departure Date', 'adventure-treks' ); ?></button>
				</div>
				<div class="at-loading-spinner" id="at_dates_loading" style="display:none;">
					<span class="spinner is-active"></span> <?php esc_html_e( 'Loading dates...', 'adventure-treks' ); ?>
				</div>
				<table class="wp-list-table widefat fixed striped posts" id="at_dates_table" style="margin-top:10px;">
					<thead>
						<tr>
							<th style="width: 130px;"><?php esc_html_e( 'Date', 'adventure-treks' ); ?></th>
							<th style="width: 110px;"><?php esc_html_e( 'Status', 'adventure-treks' ); ?></th>
							<th style="width: 140px;"><?php esc_html_e( 'Seats (Tot/Book/Avail)', 'adventure-treks' ); ?></th>
							<th><?php esc_html_e( 'Price Overrides', 'adventure-treks' ); ?></th>
							<th><?php esc_html_e( 'Notes', 'adventure-treks' ); ?></th>
							<th style="width: 120px; text-align: right;"><?php esc_html_e( 'Actions', 'adventure-treks' ); ?></th>
						</tr>
					</thead>
					<tbody id="at_dates_tbody">
						<!-- Loaded via AJAX -->
					</tbody>
				</table>
			</div>
			<div class="at-modal-footer">
				<button type="button" class="button" id="at_dates_modal_back_btn"><?php esc_html_e( 'Close', 'adventure-treks' ); ?></button>
			</div>
		</div>
	</div>

	<!-- Add/Edit Date Form Modal -->
	<div class="at-modal-overlay" id="at_date_form_modal" style="display: none; z-index: 100000;">
		<div class="at-modal-box" style="max-width: 500px;">
			<div class="at-modal-header">
				<h3 id="at_date_form_title"><?php esc_html_e( 'Add Departure Date', 'adventure-treks' ); ?></h3>
				<span class="at-modal-close" id="at_date_form_close_btn">&times;</span>
			</div>
			<div class="at-modal-body">
				<div id="at_date_form" class="at-city-form-wrapper">
					<input type="hidden" name="date_id" id="at_form_date_id" value="" />
					<input type="hidden" name="date_city_id" id="at_form_date_city_id" value="" />

					<div class="at-form-row">
						<label for="at_form_departure_date"><?php esc_html_e( 'Departure Date *', 'adventure-treks' ); ?></label>
						<input type="date" id="at_form_departure_date" name="departure_date" />
					</div>

					<div class="at-form-grid-2">
						<div class="at-form-row">
							<label for="at_form_date_total_seats"><?php esc_html_e( 'Total Seats *', 'adventure-treks' ); ?></label>
							<input type="number" id="at_form_date_total_seats" name="total_seats" value="30" min="1" />
						</div>
						<div class="at-form-row">
							<label for="at_form_date_booked_seats"><?php esc_html_e( 'Booked Seats', 'adventure-treks' ); ?></label>
							<input type="number" id="at_form_date_booked_seats" name="booked_seats" value="0" min="0" />
						</div>
					</div>

					<div class="at-form-row">
						<label for="at_form_date_status"><?php esc_html_e( 'Status', 'adventure-treks' ); ?></label>
						<select id="at_form_date_status" name="status">
							<option value="" disabled selected><?php esc_html_e( '-- Select Status --', 'adventure-treks' ); ?></option>
							<option value="open"><?php esc_html_e( 'Open (Available)', 'adventure-treks' ); ?></option>
							<option value="few_seats"><?php esc_html_e( 'Few Seats Remaining', 'adventure-treks' ); ?></option>
							<option value="sold_out"><?php esc_html_e( 'Sold Out', 'adventure-treks' ); ?></option>
							<option value="cancelled"><?php esc_html_e( 'Cancelled', 'adventure-treks' ); ?></option>
						</select>
					</div>

					<div class="at-form-row">
						<label for="at_form_date_notes"><?php esc_html_e( 'Notes / Warning Message', 'adventure-treks' ); ?></label>
						<input type="text" id="at_form_date_notes" name="notes" placeholder="e.g. Weather updates or custom notes" />
					</div>

					<div style="border-top: 1px solid #ddd; margin: 15px 0 10px 0; padding-top: 10px; font-weight: bold; color: #23282d;">
						<?php esc_html_e( 'Pricing Overrides (Optional)', 'adventure-treks' ); ?>
					</div>

					<div class="at-form-grid-3" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
						<div class="at-form-row">
							<label for="at_form_date_adult_price"><?php esc_html_e( 'Adult Price', 'adventure-treks' ); ?></label>
							<input type="number" step="0.01" id="at_form_date_adult_price" name="adult_price" value="0.00" />
						</div>
						<div class="at-form-row">
							<label for="at_form_date_child_price"><?php esc_html_e( 'Child Price', 'adventure-treks' ); ?></label>
							<input type="number" step="0.01" id="at_form_date_child_price" name="child_price" value="0.00" />
						</div>
						<div class="at-form-row">
							<label for="at_form_date_offer_price"><?php esc_html_e( 'Offer Price', 'adventure-treks' ); ?></label>
							<input type="number" step="0.01" id="at_form_date_offer_price" name="offer_price" value="0.00" />
						</div>
					</div>
					<span class="description" style="display:block; margin-top:-5px; font-size:11px; color:#666;">
						<?php esc_html_e( 'Leave at 0.00 to fall back to the default departure city prices.', 'adventure-treks' ); ?>
					</span>
				</div>
			</div>
			<div class="at-modal-overlay-footer" style="display: flex; justify-content: flex-end; gap: 10px; padding: 15px 20px; background: #f0f0f1; border-top: 1px solid #ccd0d4;">
				<button type="button" class="button" id="at_date_form_cancel_btn"><?php esc_html_e( 'Cancel', 'adventure-treks' ); ?></button>
				<button type="button" class="button button-primary" id="at_date_form_save_btn"><?php esc_html_e( 'Save Date Config', 'adventure-treks' ); ?></button>
			</div>
		</div>
	</div>
	<!-- Itinerary Builder Modal -->
	<div class="at-modal-overlay" id="at_itinerary_modal" style="display: none;">
		<div class="at-modal-box" style="max-width: 900px; width: 95%; height: 90vh;">
			<div class="at-modal-header">
				<h3><?php esc_html_e( 'Itinerary Builder', 'adventure-treks' ); ?> - <span id="at_itinerary_modal_city_title"></span></h3>
				<span class="at-modal-close" id="at_itinerary_modal_close_btn">&times;</span>
			</div>
			<div class="at-modal-body" style="display: flex; gap: 20px; overflow: hidden; height: 100%; padding: 15px;">
				
				<!-- Left Column: Days List -->
				<div style="width: 280px; display: flex; flex-direction: column; border-right: 1px solid #ddd; padding-right: 15px; height: 100%;">
					<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
						<h4 style="margin: 0; font-size: 14px; font-weight: 600;"><?php esc_html_e( 'Itinerary Days', 'adventure-treks' ); ?></h4>
						<button type="button" class="button button-small" id="at_add_day_btn">+ <?php esc_html_e( 'Add Day', 'adventure-treks' ); ?></button>
					</div>
					<div id="at_itinerary_days_list" style="flex-grow: 1; overflow-y: auto; padding-right: 5px; display: flex; flex-direction: column; gap: 8px;">
						<!-- Days populated via AJAX -->
					</div>
				</div>

				<!-- Right Column: Timeline Events inside Selected Day -->
				<div style="flex-grow: 1; display: flex; flex-direction: column; height: 100%;">
					<div id="at_no_day_selected_msg" style="display: flex; flex-grow: 1; justify-content: center; align-items: center; text-align: center; color: #888;">
						<div>
							<span class="dashicons dashicons-calendar-alt" style="font-size: 48px; width:48px; height:48px; display:block; margin:0 auto 10px auto;"></span>
							<?php esc_html_e( 'Select a day from the left sidebar to manage its activities timeline.', 'adventure-treks' ); ?>
						</div>
					</div>

					<div id="at_day_timeline_wrapper" style="display: none; flex-direction: column; height: 100%;">
						<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
							<div>
								<h4 id="at_selected_day_title" style="margin: 0; font-size: 15px; font-weight: 600;">Day 1: Arrival</h4>
								<p id="at_selected_day_desc" style="margin: 3px 0 0 0; font-size: 12px; color: #666; font-style: italic;"></p>
							</div>
							<div>
								<button type="button" class="button" id="at_edit_selected_day_btn" style="margin-right:5px;"><?php esc_html_e( 'Edit Day Settings', 'adventure-treks' ); ?></button>
								<button type="button" class="button button-primary" id="at_add_activity_btn">+ <?php esc_html_e( 'Add Activity', 'adventure-treks' ); ?></button>
							</div>
						</div>

						<div id="at_day_activities_timeline" style="flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; padding: 5px 0;">
							<!-- Activities populated via AJAX -->
						</div>
					</div>
				</div>

			</div>
			<div class="at-modal-footer">
				<button type="button" class="button" id="at_itinerary_modal_back_btn"><?php esc_html_e( 'Close Builder', 'adventure-treks' ); ?></button>
			</div>
		</div>
	</div>

	<!-- Add/Edit Day Form Modal -->
	<div class="at-modal-overlay" id="at_day_form_modal" style="display: none; z-index: 100000;">
		<div class="at-modal-box" style="max-width: 450px;">
			<div class="at-modal-header">
				<h3 id="at_day_form_title"><?php esc_html_e( 'Add Day Settings', 'adventure-treks' ); ?></h3>
				<span class="at-modal-close" id="at_day_form_close_btn">&times;</span>
			</div>
			<div class="at-modal-body">
				<div id="at_day_form" class="at-city-form-wrapper">
					<input type="hidden" name="day_id" id="at_form_day_id" value="" />
					
					<div class="at-form-row">
						<label for="at_form_day_number"><?php esc_html_e( 'Day Number *', 'adventure-treks' ); ?></label>
						<input type="number" id="at_form_day_number" name="day_number" value="1" min="0" />
						<span class="description"><?php esc_html_e( 'Use 0 for departure day assembly info, 1 for start, etc.', 'adventure-treks' ); ?></span>
					</div>

					<div class="at-form-row">
						<label for="at_form_day_title"><?php esc_html_e( 'Day Title *', 'adventure-treks' ); ?></label>
						<input type="text" id="at_form_day_title" name="title" placeholder="e.g. Arrival at Basecamp & Briefing" />
					</div>

					<div class="at-form-row">
						<label for="at_form_day_description"><?php esc_html_e( 'Day Overview / Description', 'adventure-treks' ); ?></label>
						<textarea id="at_form_day_description" name="description" rows="4" placeholder="Brief outline of this day's trekking milestones..."></textarea>
					</div>
				</div>
			</div>
			<div class="at-modal-overlay-footer" style="display: flex; justify-content: flex-end; gap: 10px; padding: 15px 20px; background: #f0f0f1; border-top: 1px solid #ccd0d4;">
				<button type="button" class="button" id="at_day_form_cancel_btn"><?php esc_html_e( 'Cancel', 'adventure-treks' ); ?></button>
				<button type="button" class="button button-primary" id="at_day_form_save_btn"><?php esc_html_e( 'Save Day', 'adventure-treks' ); ?></button>
			</div>
		</div>
	</div>

	<!-- Add/Edit Activity Item Form Modal -->
	<div class="at-modal-overlay" id="at_activity_form_modal" style="display: none; z-index: 100001;">
		<div class="at-modal-box" style="max-width: 500px;">
			<div class="at-modal-header">
				<h3 id="at_activity_form_title"><?php esc_html_e( 'Add Activity Event', 'adventure-treks' ); ?></h3>
				<span class="at-modal-close" id="at_activity_form_close_btn">&times;</span>
			</div>
			<div class="at-modal-body">
				<div id="at_activity_form" class="at-city-form-wrapper">
					<input type="hidden" name="activity_id" id="at_form_activity_id" value="" />
					
					<div class="at-form-grid-2">
						<div class="at-form-row">
							<label for="at_form_activity_time"><?php esc_html_e( 'Time (e.g. 08:30 AM)', 'adventure-treks' ); ?></label>
							<input type="text" id="at_form_activity_time" name="item_time" placeholder="e.g. 08:00 AM / Evening" />
						</div>
						<div class="at-form-row">
							<label for="at_form_activity_icon"><?php esc_html_e( 'Event Icon', 'adventure-treks' ); ?></label>
							<select id="at_form_activity_icon" name="icon">
								<option value="" disabled selected><?php esc_html_e( '-- Select Icon --', 'adventure-treks' ); ?></option>
								<option value="dashicons-palmtree">🌴 <?php esc_html_e( 'Trek / Outdoors', 'adventure-treks' ); ?></option>
								<option value="dashicons-car">🚗 <?php esc_html_e( 'Transport / Travel', 'adventure-treks' ); ?></option>
								<option value="dashicons-food">🍴 <?php esc_html_e( 'Meals / Dining', 'adventure-treks' ); ?></option>
								<option value="dashicons-location">📍 <?php esc_html_e( 'Sightseeing', 'adventure-treks' ); ?></option>
								<option value="dashicons-admin-home">🏕️ <?php esc_html_e( 'Hotel / Camp Stay', 'adventure-treks' ); ?></option>
								<option value="dashicons-clock">⏰ <?php esc_html_e( 'Reporting', 'adventure-treks' ); ?></option>
								<option value="dashicons-marker">🚩 <?php esc_html_e( 'Summit / Milestone', 'adventure-treks' ); ?></option>
							</select>
						</div>
					</div>

					<div class="at-form-row">
						<label for="at_form_activity_title"><?php esc_html_e( 'Activity Title *', 'adventure-treks' ); ?></label>
						<input type="text" id="at_form_activity_title" name="title" placeholder="e.g. Hot Breakfast served at Camp" />
					</div>

					<div class="at-form-row">
						<label for="at_form_activity_image"><?php esc_html_e( 'Event Image', 'adventure-treks' ); ?></label>
						<div style="display:flex; gap:10px; align-items:center;">
							<input type="text" id="at_form_activity_image" name="image_url" placeholder="https://..." class="regular-text" style="flex-grow:1;" />
							<button type="button" class="button" id="at_select_activity_image_btn"><?php esc_html_e( 'Select', 'adventure-treks' ); ?></button>
						</div>
						<div id="at_activity_image_preview" style="margin-top:10px;"></div>
					</div>

					<div class="at-form-row">
						<label for="at_form_activity_desc"><?php esc_html_e( 'Activity Details', 'adventure-treks' ); ?></label>
						<textarea id="at_form_activity_desc" name="description" rows="4" placeholder="Detailed outline of what trekkers do during this activity slot..."></textarea>
					</div>
				</div>
			</div>
			<div class="at-modal-overlay-footer" style="display: flex; justify-content: flex-end; gap: 10px; padding: 15px 20px; background: #f0f0f1; border-top: 1px solid #ccd0d4;">
				<button type="button" class="button" id="at_activity_form_cancel_btn"><?php esc_html_e( 'Cancel', 'adventure-treks' ); ?></button>
				<button type="button" class="button button-primary" id="at_activity_form_save_btn"><?php esc_html_e( 'Save Activity', 'adventure-treks' ); ?></button>
			</div>
		</div>
	</div>
	<!-- Pricing Manager Modal -->
	<div class="at-modal-overlay" id="at_pricing_modal" style="display: none;">
		<div class="at-modal-box" style="max-width: 750px; width: 90%; height: 85vh;">
			<div class="at-modal-header">
				<h3><?php esc_html_e( 'Configure Pricing Rules & Add-ons', 'adventure-treks' ); ?> - <span id="at_pricing_modal_city_title"></span></h3>
				<span class="at-modal-close" id="at_pricing_modal_close_btn">&times;</span>
			</div>
			<div class="at-modal-body" style="overflow-y: auto;">
				<div id="at_pricing_form" class="at-city-form-wrapper">
					<input type="hidden" name="pricing_city_id" id="at_pricing_city_id" value="" />
					
					<!-- Base Pricing Section -->
					<div style="font-weight: bold; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-bottom: 15px; color: #23282d;">
						<?php esc_html_e( 'Base Pricing', 'adventure-treks' ); ?>
					</div>
					<div class="at-form-grid-3" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 20px;">
						<div class="at-form-row" style="margin-bottom:0;">
							<label for="at_pricing_adult_price"><?php esc_html_e( 'Adult Price *', 'adventure-treks' ); ?></label>
							<input type="number" step="0.01" id="at_pricing_adult_price" name="adult_price" value="0.00" min="0" />
						</div>
						<div class="at-form-row" style="margin-bottom:0;">
							<label for="at_pricing_child_price"><?php esc_html_e( 'Child Price', 'adventure-treks' ); ?></label>
							<input type="number" step="0.01" id="at_pricing_child_price" name="child_price" value="0.00" min="0" />
						</div>
						<div class="at-form-row" style="margin-bottom:0;">
							<label for="at_pricing_offer_price"><?php esc_html_e( 'Offer Price', 'adventure-treks' ); ?></label>
							<input type="number" step="0.01" id="at_pricing_offer_price" name="offer_price" value="0.00" min="0" />
						</div>
					</div>

					<!-- Group Discounts Repeater -->
					<div style="display:flex; justify-content:space-between; align-items:center; font-weight: bold; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-bottom: 15px; color: #23282d; margin-top:20px;">
						<span><?php esc_html_e( 'Group Discounts', 'adventure-treks' ); ?></span>
						<button type="button" class="button button-small" id="at_add_group_discount_rule_btn">+ <?php esc_html_e( 'Add Discount Rule', 'adventure-treks' ); ?></button>
					</div>
					<div id="at_group_discount_rules_list" style="margin-bottom: 20px;">
						<!-- Group rules list -->
					</div>

					<!-- Extra Charges Repeater -->
					<div style="display:flex; justify-content:space-between; align-items:center; font-weight: bold; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-bottom: 15px; color: #23282d; margin-top:20px;">
						<span><?php esc_html_e( 'Mandatory Extra Charges', 'adventure-treks' ); ?></span>
						<button type="button" class="button button-small" id="at_add_extra_charge_btn">+ <?php esc_html_e( 'Add Fee/Charge', 'adventure-treks' ); ?></button>
					</div>
					<div id="at_extra_charges_list" style="margin-bottom: 20px;">
						<!-- Extra fees list -->
					</div>

					<!-- Optional Add-ons Repeater -->
					<div style="display:flex; justify-content:space-between; align-items:center; font-weight: bold; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 8px; margin-bottom: 15px; color: #23282d; margin-top:20px;">
						<span><?php esc_html_e( 'Optional Add-ons', 'adventure-treks' ); ?></span>
						<button type="button" class="button button-small" id="at_add_optional_addon_btn">+ <?php esc_html_e( 'Add Optional Extra', 'adventure-treks' ); ?></button>
					</div>
					<div id="at_optional_addons_list" style="margin-bottom: 20px;">
						<!-- Add-ons list -->
					</div>

				</div>
			</div>
			<div class="at-modal-overlay-footer" style="display: flex; justify-content: flex-end; gap: 10px; padding: 15px 20px; background: #f0f0f1; border-top: 1px solid #ccd0d4;">
				<button type="button" class="button" id="at_pricing_modal_cancel_btn"><?php esc_html_e( 'Cancel', 'adventure-treks' ); ?></button>
				<button type="button" class="button button-primary" id="at_pricing_modal_save_btn"><?php esc_html_e( 'Save Pricing Setup', 'adventure-treks' ); ?></button>
			</div>
		</div>
	</div>

</div>
