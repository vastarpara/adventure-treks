/**
 * JavaScript for Frontend Booking Widget (AJAX checkout workflow)
 */

document.addEventListener('DOMContentLoaded', function() {

	const root = document.getElementById('at_booking_widget_root');
	if (!root) return;

	const trekId = root.getAttribute('data-trek-id');
	const ajaxUrl = at_booking_obj.ajax_url;
	const nonce = at_booking_obj.nonce;
	const currency = at_booking_obj.currency_symbol;

	// Widget Sections
	const cityPills = document.getElementById('at_widget_city_pills');
	const dateSection = document.getElementById('at_widget_date_section');
	const datesGrid = document.getElementById('at_widget_dates_grid');
	const datesLoading = document.getElementById('at_widget_dates_loading');
	const detailsSection = document.getElementById('at_widget_details_section');
	const detailsLoading = document.getElementById('at_widget_details_loading');

	// Details elements
	const textAvail = document.getElementById('at_widget_avail_seats');
	const textTransport = document.getElementById('at_widget_transport');
	const textReporting = document.getElementById('at_widget_reporting');
	const priceCross = document.getElementById('at_widget_base_price_cross');
	const priceTag = document.getElementById('at_widget_price_tag');
	const childPriceTag = document.getElementById('at_widget_child_price_tag');
	const dateNotes = document.getElementById('at_widget_date_notes');

	// Pax Counters
	const inputAdults = document.getElementById('at_widget_pax_adults');
	const inputChildren = document.getElementById('at_widget_pax_children');
	const childRow = document.getElementById('at_widget_child_row');

	// Addons
	const addonsSection = document.getElementById('at_widget_addons_section');
	const addonsList = document.getElementById('at_widget_addons_list');

	// Price Receipt
	const receiptRows = document.getElementById('at_widget_receipt_rows');
	const grandTotalTag = document.getElementById('at_widget_grand_total');

	// Checkout / Modals
	const checkoutBtn = document.getElementById('at_widget_checkout_btn');
	const checkoutModal = document.getElementById('at_checkout_modal');
	const checkoutClose = document.getElementById('at_checkout_modal_close');
	const checkoutCancel = document.getElementById('at_checkout_cancel_btn');
	const checkoutConfirm = document.getElementById('at_checkout_confirm_btn');
	const checkoutForm = document.getElementById('at_checkout_form');

	const pickupField = document.getElementById('at_checkout_pickup_field');
	const pickupSelect = document.getElementById('at_checkout_pickup');
	const pickupInstructions = document.getElementById('at_checkout_pickup_instructions');

	// Success Modal
	const successModal = document.getElementById('at_success_modal');
	const successClose = document.getElementById('at_success_close_btn');
	const successReceiptBody = document.getElementById('at_success_receipt_body');

	// Internal State
	let selectedCityId = null;
	let selectedDateId = null;
	let dateDetails = null; // Holds the currently loaded AJAX date specs

	// ==========================================
	// 1. Step 1: Click City Pill
	// ==========================================
	if (cityPills) {
		cityPills.addEventListener('click', function(e) {
			const button = e.target.closest('.at-city-pill-btn');
			if (!button) return;

			// Highlight active pill
			cityPills.querySelectorAll('.at-city-pill-btn').forEach(btn => btn.classList.remove('active'));
			button.classList.add('active');

			selectedCityId = parseInt(button.getAttribute('data-id'));
			selectedDateId = null;
			dateDetails = null;

			// Close panels
			dateSection.style.display = 'none';
			detailsSection.style.display = 'none';
			if (detailsLoading) detailsLoading.style.display = 'none';

			// Load dates
			fetchDates();
		});
	}

	function fetchDates() {
		if (!selectedCityId) return;

		datesLoading.style.display = 'flex';
		datesGrid.innerHTML = '';
		dateSection.style.display = 'block';

		const url = `${ajaxUrl}?action=at_get_booking_dates&city_id=${selectedCityId}&nonce=${nonce}`;

		fetch(url)
			.then(res => res.json())
			.then(data => {
				datesLoading.style.display = 'none';
				if (data.success && data.data.length > 0) {
					renderDates(data.data);
				} else {
					datesGrid.innerHTML = `<p style="grid-column: span 3; color:#666; font-style:italic; padding:10px 0;">No active dates available for this city.</p>`;
				}
			});
	}

	function renderDates(dates) {
		datesGrid.innerHTML = '';
		dates.forEach(d => {
			const btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'at-date-select-btn';
			btn.setAttribute('data-id', d.id);

			// Check sold out
			const isSoldOut = d.status === 'sold_out' || parseInt(d.available_seats) <= 0;
			if (isSoldOut) {
				btn.classList.add('sold-out');
				btn.disabled = true;
			}

			// Resolve badge class
			let badgeClass = 'open';
			let badgeText = 'Seats open';
			if (isSoldOut) {
				badgeClass = 'sold';
				badgeText = 'Sold Out';
			} else if (d.status === 'few_seats' || parseInt(d.available_seats) <= 3) {
				badgeClass = 'few';
				badgeText = `${d.available_seats} left`;
			}

			btn.innerHTML = `
				<span class="at-date-val">${d.formatted_date}</span>
				<span class="at-date-status-badge ${badgeClass}">${badgeText}</span>
			`;

			btn.addEventListener('click', function() {
				if (isSoldOut) return;
				datesGrid.querySelectorAll('.at-date-select-btn').forEach(b => b.classList.remove('active'));
				btn.classList.add('active');
				
				selectedDateId = parseInt(d.id);
				fetchBookingDetails();
			});

			datesGrid.appendChild(btn);
		});
	}

	// ==========================================
	// 2. Step 2: Fetch and Configure Date Details
	// ==========================================
	function fetchBookingDetails() {
		if (!selectedCityId || !selectedDateId) return;

		detailsSection.style.display = 'none';
		if (detailsLoading) detailsLoading.style.display = 'flex';

		const url = `${ajaxUrl}?action=at_get_booking_details&city_id=${selectedCityId}&date_id=${selectedDateId}&nonce=${nonce}`;

		fetch(url)
			.then(res => res.json())
			.then(data => {
				if (detailsLoading) detailsLoading.style.display = 'none';
				if (data.success) {
					dateDetails = data.data;
					configureDetailsPanel();
				} else {
					alert('Failed to load booking details.');
				}
			})
			.catch(err => {
				if (detailsLoading) detailsLoading.style.display = 'none';
				alert('Network error loading details.');
			});
	}

	function configureDetailsPanel() {
		if (!dateDetails) return;

		// 1. Configure Seats
		let seatsMsg = `${dateDetails.available_seats} Seats`;
		if (dateDetails.available_seats <= 3) {
			seatsMsg = `<span style="color:#b32d2e; font-weight:700;">Only ${dateDetails.available_seats} Left!</span>`;
		} else if (dateDetails.available_seats <= 8) {
			seatsMsg = `<span style="color:#d68100; font-weight:700;">Few Seats Left</span>`;
		}
		textAvail.innerHTML = seatsMsg;

		// 2. Transport & Reporting
		textTransport.textContent = dateDetails.transport_type || 'Self';
		textReporting.textContent = dateDetails.reporting_time || 'N/A';

		// 3. Price Display
		const price = parseFloat(dateDetails.offer_price) > 0 ? dateDetails.offer_price : dateDetails.adult_price;
		priceTag.textContent = `${currency} ${parseFloat(price).toFixed(2)}`;

		if (parseFloat(dateDetails.offer_price) > 0 && parseFloat(dateDetails.adult_price) > 0) {
			priceCross.textContent = `${currency} ${parseFloat(dateDetails.adult_price).toFixed(2)}`;
			priceCross.style.display = 'inline';
		} else {
			priceCross.style.display = 'none';
		}

		// Child Price details
		if (parseFloat(dateDetails.child_price) > 0) {
			childPriceTag.textContent = `Child Rate: ${currency} ${parseFloat(dateDetails.child_price).toFixed(2)}`;
			childPriceTag.style.display = 'block';
			childRow.style.display = 'flex';
		} else {
			childPriceTag.style.display = 'none';
			childRow.style.display = 'none';
			inputChildren.value = 0; // Reset
		}

		// 4. Add-ons List
		if (dateDetails.optional_addons && dateDetails.optional_addons.length > 0) {
			addonsSection.style.display = 'block';
			addonsList.innerHTML = '';
			dateDetails.optional_addons.forEach((addon, idx) => {
				const div = document.createElement('div');
				div.className = 'at-addon-checkbox-item';
				
				const scopeLabel = addon.type === 'person' ? '/Person' : ' flat';
				const descHTML = addon.desc ? `<span class="addon-desc">${addon.desc}</span>` : '';

				div.innerHTML = `
					<label>
						<input type="checkbox" name="addon_check" value="${addon.name}" data-price="${addon.price}" data-type="${addon.type}" />
						<div>
							<strong>${addon.name}</strong>
							${descHTML}
						</div>
					</label>
					<span class="at-addon-price-label">+${currency}${parseFloat(addon.price).toFixed(2)}${scopeLabel}</span>
				`;

				div.querySelector('input').addEventListener('change', calculateTotal);
				addonsList.appendChild(div);
			});
		} else {
			addonsSection.style.display = 'none';
			addonsList.innerHTML = '';
		}

		// 5. If itinerary integration exists on page, refresh it dynamically
		const pageItinerary = document.querySelector('.at-frontend-itinerary-timeline');
		const pageItineraryContainer = document.getElementById('trek_itinerary_container'); // Standard template ID
		
		const targetItinerary = pageItineraryContainer ? pageItineraryContainer : (pageItinerary ? pageItinerary.parentElement : null);
		if (targetItinerary && dateDetails.itinerary_html) {
			targetItinerary.innerHTML = dateDetails.itinerary_html;
		}

		detailsSection.style.display = 'block';
		calculateTotal();
	}

	// ==========================================
	// 3. Plus/Minus Counters & Calculator
	// ==========================================
	document.querySelectorAll('.at-counter-btn').forEach(btn => {
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			const type = btn.getAttribute('data-type');
			const isPlus = btn.classList.contains('plus');
			
			if (type === 'adults') {
				let val = parseInt(inputAdults.value);
				val = isPlus ? val + 1 : val - 1;
				if (val < 1) val = 1;
				
				// Verify seat cap limits
				if (dateDetails && val + parseInt(inputChildren.value) > dateDetails.available_seats) {
					alert('Cannot exceed remaining seat capacity limit.');
					return;
				}
				inputAdults.value = val;
			} else if (type === 'children') {
				let val = parseInt(inputChildren.value);
				val = isPlus ? val + 1 : val - 1;
				if (val < 0) val = 0;

				// Verify seat cap limits
				if (dateDetails && parseInt(inputAdults.value) + val > dateDetails.available_seats) {
					alert('Cannot exceed remaining seat capacity limit.');
					return;
				}
				inputChildren.value = val;
			}

			calculateTotal();
		});
	});

	function calculateTotal() {
		if (!dateDetails) return;

		const adults = parseInt(inputAdults.value) || 1;
		const children = parseInt(inputChildren.value) || 0;
		const totalPax = adults + children;

		// 1. Adults price
		const rateAdult = parseFloat(dateDetails.offer_price) > 0 ? parseFloat(dateDetails.offer_price) : parseFloat(dateDetails.adult_price);
		const priceAdultsSum = adults * rateAdult;

		// 2. Children price
		const rateChild = parseFloat(dateDetails.child_price);
		const priceChildrenSum = children * rateChild;

		let subtotal = priceAdultsSum + priceChildrenSum;
		let markup = '';

		markup += `<div style="display:flex; justify-content:space-between;"><span>Adults Price (${adults} x ${currency}${rateAdult.toFixed(2)})</span><span>${currency}${priceAdultsSum.toFixed(2)}</span></div>`;
		if (children > 0 && rateChild > 0) {
			markup += `<div style="display:flex; justify-content:space-between;"><span>Children Price (${children} x ${currency}${rateChild.toFixed(2)})</span><span>${currency}${priceChildrenSum.toFixed(2)}</span></div>`;
		}

		// 3. Group Discounts
		let discountSum = 0;
		if (dateDetails.group_discount && dateDetails.group_discount.length > 0) {
			// Find applicable rule with highest min_seats <= totalPax
			let bestRule = null;
			dateDetails.group_discount.forEach(rule => {
				if (totalPax >= intval(rule.min_seats)) {
					if (!bestRule || intval(rule.min_seats) > intval(bestRule.min_seats)) {
						bestRule = rule;
					}
				}
			});

			if (bestRule) {
				if (bestRule.type === 'percent') {
					discountSum = subtotal * (parseFloat(bestRule.value) / 100);
					markup += `<div style="display:flex; justify-content:space-between; color:#385723;"><span>Group Discount (${bestRule.min_seats}+ Pax: ${bestRule.value}%)</span><span>-${currency}${discountSum.toFixed(2)}</span></div>`;
				} else {
					discountSum = parseFloat(bestRule.value); // Flat deduction
					markup += `<div style="display:flex; justify-content:space-between; color:#385723;"><span>Group Discount Flat (${bestRule.min_seats}+ Pax)</span><span>-${currency}${discountSum.toFixed(2)}</span></div>`;
				}
				subtotal -= discountSum;
			}
		}

		// Helper to ensure integers
		function intval(val) {
			return parseInt(val) || 0;
		}

		// 4. Extra Charges
		let extraSum = 0;
		if (dateDetails.extra_charges && dateDetails.extra_charges.length > 0) {
			dateDetails.extra_charges.forEach(charge => {
				let amt = 0;
				if (charge.type === 'person') {
					amt = totalPax * parseFloat(charge.price);
					markup += `<div style="display:flex; justify-content:space-between;"><span>${charge.name} (${totalPax} x ${currency}${parseFloat(charge.price).toFixed(2)})</span><span>+${currency}${amt.toFixed(2)}</span></div>`;
				} else {
					amt = parseFloat(charge.price);
					markup += `<div style="display:flex; justify-content:space-between;"><span>${charge.name} (Flat)</span><span>+${currency}${amt.toFixed(2)}</span></div>`;
				}
				extraSum += amt;
			});
		}
		subtotal += extraSum;

		// 5. Optional Checked Addons
		let addonsSum = 0;
		const checkedAddons = addonsList ? addonsList.querySelectorAll('input[name="addon_check"]:checked') : [];
		checkedAddons.forEach(chk => {
			const price = parseFloat(chk.getAttribute('data-price'));
			const type = chk.getAttribute('data-type');
			const name = chk.value;
			let amt = 0;

			if (type === 'person') {
				amt = totalPax * price;
				markup += `<div style="display:flex; justify-content:space-between;"><span>${name} (${totalPax} x ${currency}${price.toFixed(2)})</span><span>+${currency}${amt.toFixed(2)}</span></div>`;
			} else {
				amt = price;
				markup += `<div style="display:flex; justify-content:space-between;"><span>${name} (Flat)</span><span>+${currency}${amt.toFixed(2)}</span></div>`;
			}
			addonsSum += amt;
		});
		subtotal += addonsSum;

		// Set totals
		receiptRows.innerHTML = markup;
		grandTotalTag.textContent = `${currency} ${subtotal.toFixed(2)}`;
		grandTotalTag.setAttribute('data-raw', subtotal);
	}

	// ==========================================
	// 4. Step 4: Checkout Popup Modal
	// ==========================================
	checkoutBtn.addEventListener('click', function() {
		if (!dateDetails) return;

		// Populate pickup select options
		if (dateDetails.pickups && dateDetails.pickups.length > 0) {
			pickupField.style.display = 'block';
			pickupSelect.innerHTML = '<option value="">-- Select Pickup Point --</option>';
			
			dateDetails.pickups.forEach(p => {
				const opt = document.createElement('option');
				opt.value = p.location_name;
				opt.setAttribute('data-time', p.pickup_time);
				opt.setAttribute('data-instructions', p.instructions || '');
				opt.textContent = `${p.location_name} (Pickup: ${p.pickup_time})`;
				pickupSelect.appendChild(opt);
			});

			pickupSelect.required = true;
		} else {
			pickupField.style.display = 'none';
			pickupSelect.innerHTML = '';
			pickupSelect.required = false;
		}

		pickupInstructions.textContent = '';
		checkoutModal.style.display = 'flex';
	});

	// Pickup instruction updates
	pickupSelect.addEventListener('change', function() {
		const selected = pickupSelect.options[pickupSelect.selectedIndex];
		if (!selected || !selected.value) {
			pickupInstructions.textContent = '';
			return;
		}
		const time = selected.getAttribute('data-time');
		const note = selected.getAttribute('data-instructions');
		pickupInstructions.innerHTML = `<strong>Reporting</strong>: ${time}. ${note}`;
	});

	function closeCheckout() {
		checkoutModal.style.display = 'none';
		checkoutForm.reset();
	}

	checkoutClose.addEventListener('click', closeCheckout);
	checkoutCancel.addEventListener('click', closeCheckout);

	// ==========================================
	// 5. Booking Form Submission
	// ==========================================
	checkoutConfirm.addEventListener('click', function() {
		if (!checkoutForm.reportValidity()) return;

		const adults = parseInt(inputAdults.value) || 1;
		const children = parseInt(inputChildren.value) || 0;
		const total = grandTotalTag.getAttribute('data-raw');

		// Retrieve checked addons names
		const chosenAddons = [];
		const checkedAddons = addonsList ? addonsList.querySelectorAll('input[name="addon_check"]:checked') : [];
		checkedAddons.forEach(chk => {
			chosenAddons.push(chk.value);
		});

		const fd = new FormData(checkoutForm);
		fd.append('action', 'at_submit_booking');
		fd.append('trek_id', trekId);
		fd.append('city_id', selectedCityId);
		fd.append('date_id', selectedDateId);
		fd.append('num_adults', adults);
		fd.append('num_children', children);
		fd.append('total_price', total);
		fd.append('nonce', nonce);

		chosenAddons.forEach(addon => {
			fd.append('addons[]', addon);
		});

		checkoutModal.style.display = 'none';
		datesLoading.style.display = 'flex';
		datesLoading.innerHTML = '<span class="dashicons dashicons-update" style="animation: spin 2s linear infinite; margin-right: 8px;"></span> Creating your booking reservation...';

		fetch(ajaxUrl, {
			method: 'POST',
			body: fd
		})
			.then(res => res.json())
			.then(data => {
				datesLoading.style.display = 'none';
				if (data.success) {
					showSuccessReceipt(data.data);
				} else {
					alert('Booking failed: ' + data.data.message);
					checkoutModal.style.display = 'flex';
				}
			})
			.catch(err => {
				alert('Network error during checkout.');
				datesLoading.style.display = 'none';
				checkoutModal.style.display = 'flex';
			});
	});

	function showSuccessReceipt(res) {
		successReceiptBody.innerHTML = `
			<p><strong>Customer Name</strong>: ${res.cust_name}</p>
			<p><strong>Trek</strong>: ${res.trek_title}</p>
			<p><strong>From City</strong>: ${res.city_name}</p>
			<p><strong>Date</strong>: ${res.date}</p>
			<p><strong>Seats Booked</strong>: ${res.seats}</p>
			${res.pickup_point ? `<p><strong>Pickup Location</strong>: ${res.pickup_point}</p>` : ''}
			<p style="border-top:1px solid #ddd; padding-top:8px; margin-top:8px; font-weight:bold; color:#137a7f; font-size:14px;"><strong>Amount Paid</strong>: ${currency} ${parseFloat(res.total).toFixed(2)}</p>
		`;
		successModal.style.display = 'flex';
	}

	successClose.addEventListener('click', function() {
		successModal.style.display = 'none';
		// Reset Widget
		selectedCityId = null;
		selectedDateId = null;
		dateDetails = null;

		cityPills.querySelectorAll('.at-city-pill-btn').forEach(btn => btn.classList.remove('active'));
		dateSection.style.display = 'none';
		detailsSection.style.display = 'none';
		if (detailsLoading) detailsLoading.style.display = 'none';
	});

});
