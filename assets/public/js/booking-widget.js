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
	const transportSection = document.getElementById('at_widget_transport_section');
	const transportList = document.getElementById('at_widget_transport_list');
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
	const checkoutTermsAgree = document.getElementById('at_checkout_terms_agree');

	const pickupField = document.getElementById('at_checkout_pickup_field');
	const pickupSelect = document.getElementById('at_checkout_pickup');
	const pickupInstructions = document.getElementById('at_checkout_pickup_instructions');

	// Payment Modal
	const paymentModal = document.getElementById('at_payment_modal');
	const paymentModalClose = document.getElementById('at_payment_modal_close');
	const paymentBackBtn = document.getElementById('at_payment_back_btn');
	const paymentConfirmBtn = document.getElementById('at_payment_confirm_btn');
	const paymentReceiptRows = document.getElementById('at_payment_receipt_rows');
	const paymentGrandTotal = document.getElementById('at_payment_grand_total');
	const paymentMethodBox = document.getElementById('at_payment_method_box');

	// Success Modal
	const successModal = document.getElementById('at_success_modal');
	const successClose = document.getElementById('at_success_close_btn');
	const successReceiptBody = document.getElementById('at_success_receipt_body');

	// Sticky Bottom Booking Bar
	const stickyBar = document.getElementById('at_sticky_booking_bar');
	const stickyBarAmount = document.getElementById('at_sticky_bar_amount');
	const stickyBarUnit = document.getElementById('at_sticky_bar_unit');
	const stickyBarCta = document.getElementById('at_sticky_bar_cta');

	// Internal State
	let selectedCityId = null;
	let selectedDateId = null;
	let dateDetails = null; // Holds the currently loaded AJAX date specs
	let selectedTransport = null; // { name, price } — currently chosen Transportation Type
	let stickyBarPriceKnown = false; // True once a real price has been computed at least once

	// URL ?date= sync: read once on load (consumed by the first renderDates() call),
	// and kept updated afterwards on every date selection so the page stays shareable/bookmarkable.
	let pendingUrlDate = new URLSearchParams(window.location.search).get('date');

	function updateUrlDate(dateStr) {
		const url = new URL(window.location.href);
		if (dateStr) {
			url.searchParams.set('date', dateStr);
		} else {
			url.searchParams.delete('date');
		}
		window.history.replaceState({}, '', url);
	}

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
			selectedTransport = null;

			// Close panels
			if (transportSection) transportSection.style.display = 'none';
			dateSection.style.display = 'none';
			detailsSection.style.display = 'none';
			if (detailsLoading) detailsLoading.style.display = 'none';

			// Load transportation options and dates in parallel.
			fetchTransportOptions();
			fetchDates();
		});

		// Auto-select the first available city on load so the widget never sits empty;
		// the user can still freely pick a different one.
		const firstCityBtn = cityPills.querySelector('.at-city-pill-btn');
		if (firstCityBtn) {
			firstCityBtn.click();
		}
	}

	// ==========================================
	// 1a. Step 2: Transportation Type (loaded right after City selection)
	// ==========================================
	function fetchTransportOptions() {
		if (!selectedCityId || !transportSection || !transportList) return;

		const url = `${ajaxUrl}?action=at_get_transport_options&city_id=${selectedCityId}&nonce=${nonce}`;

		fetch(url)
			.then(res => res.json())
			.then(data => {
				const payload = data.success ? data.data : {};
				const options = Array.isArray(payload.options) ? payload.options : [];
				renderTransportOptions(options, parseFloat(payload.adult_price) || 0, parseFloat(payload.offer_price) || 0);
			});
	}

	// ==========================================
	// Sticky Bottom Booking Bar
	// ==========================================
	function isBookingModalOpen() {
		return (checkoutModal && checkoutModal.style.display === 'flex')
			|| (paymentModal && paymentModal.style.display === 'flex')
			|| (successModal && successModal.style.display === 'flex');
	}

	// Single source of truth for whether the bar should currently be on screen:
	// a real price must be known, and no checkout/payment/success modal (which
	// already has its own form fields and CTA) may be open underneath it.
	let baseBodyPaddingBottom = null; // Theme's own body padding, captured once, so we add to it rather than clobber it.
	function refreshStickyBar() {
		if (!stickyBar) return;

		if (baseBodyPaddingBottom === null) {
			baseBodyPaddingBottom = parseFloat(window.getComputedStyle(document.body).paddingBottom) || 0;
		}

		if (stickyBarPriceKnown && !isBookingModalOpen()) {
			stickyBar.style.display = 'flex';
			document.body.style.paddingBottom = (baseBodyPaddingBottom + stickyBar.offsetHeight) + 'px';
		} else {
			stickyBar.style.display = 'none';
			document.body.style.paddingBottom = baseBodyPaddingBottom + 'px';
		}
	}

	// Shows the actual grand total the customer will pay, so it visibly moves the
	// instant participant count, transport, add-ons, etc. change — a per-person
	// average would often show the same number after a quantity change whenever no
	// group discount applies, which reads as "stuck"/broken even though it wasn't.
	// For a single traveller it reads as "/ Person" per the original spec; for more
	// than one it switches to "for N people" so the total is never ambiguous.
	function updateStickyBar(totalPrice, totalPax) {
		if (!stickyBarAmount) return;
		stickyBarAmount.textContent = `${currency}${totalPrice.toFixed(2)}`;
		if (stickyBarUnit) {
			stickyBarUnit.textContent = totalPax > 1
				? `for ${totalPax} people`
				: '/ Person';
		}
		stickyBarPriceKnown = true;
		refreshStickyBar();
	}

	if (stickyBarCta) {
		stickyBarCta.addEventListener('click', function() {
			if (dateDetails && checkoutBtn) {
				checkoutBtn.click();
			} else if (root) {
				root.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}
		});
	}

	window.addEventListener('resize', refreshStickyBar);

	// Watch the checkout/payment/success modals directly (rather than hooking every
	// individual open/close call site) so the bar reliably hides the instant any of
	// them opens — it must never sit on top of the checkout form's fields — and
	// reappears the instant they close.
	if ('MutationObserver' in window) {
		const modalObserver = new MutationObserver(refreshStickyBar);
		[checkoutModal, paymentModal, successModal].forEach(function(modal) {
			if (modal) modalObserver.observe(modal, { attributes: true, attributeFilter: ['style'] });
		});
	}

	// Keep the "Per Person Rate" card (and child rate) in sync with the customer's
	// Transportation Type choice: its surcharge is folded directly into the shown
	// per-person price rather than appearing as a separate line, so this must be
	// re-run whenever the transport selection changes, not just on date load.
	function updatePriceTag() {
		if (!dateDetails) return;

		const transportPrice = selectedTransport ? selectedTransport.price : 0;

		const adultBase = parseFloat(dateDetails.offer_price) > 0 ? parseFloat(dateDetails.offer_price) : parseFloat(dateDetails.adult_price);
		priceTag.textContent = `${currency} ${(adultBase + transportPrice).toFixed(2)}`;

		if (parseFloat(dateDetails.offer_price) > 0 && parseFloat(dateDetails.adult_price) > 0) {
			priceCross.textContent = `${currency} ${(parseFloat(dateDetails.adult_price) + transportPrice).toFixed(2)}`;
			priceCross.style.display = 'inline';
		} else {
			priceCross.style.display = 'none';
		}

		// Child Price details
		if (parseFloat(dateDetails.child_price) > 0) {
			childPriceTag.textContent = `Child Rate: ${currency} ${(parseFloat(dateDetails.child_price) + transportPrice).toFixed(2)}`;
			childPriceTag.style.display = 'block';
			childRow.style.display = 'flex';
		} else {
			childPriceTag.style.display = 'none';
			childRow.style.display = 'none';
			inputChildren.value = 0; // Reset
		}
	}

	// Keep the "Transport Type" detail card (Step 4) in sync with the customer's
	// actual radio choice from Step 2, instead of the city's static description text.
	function updateTransportDisplay() {
		if (!textTransport) return;
		if (selectedTransport) {
			textTransport.textContent = selectedTransport.name;
		} else if (dateDetails) {
			textTransport.textContent = dateDetails.transport_type || 'Self';
		}
	}

	function renderTransportOptions(options, cityAdultPrice, cityOfferPrice) {
		if (!transportSection || !transportList) return;

		if (!options.length) {
			// Nothing configured for this city — skip the step entirely.
			transportSection.style.display = 'none';
			transportList.innerHTML = '';
			selectedTransport = null;
			updateTransportDisplay();
			updatePriceTag();
			return;
		}

		transportSection.style.display = 'block';
		transportList.innerHTML = '';

		// Every option is shown as its extra cost on top of the base price (the full
		// per-person price is in the rate box below). Mixing a full price on one
		// option with "+extra" on another was confusing, so a ₹0 option reads "Included".
		options.forEach((opt, idx) => {
			const price = parseFloat(opt.price) || 0;
			const priceLabel = price > 0
				? '+' + currency + price.toFixed(2)
				: 'Included';
			const label = document.createElement('label');
			label.className = 'at-transport-option-item';

			label.innerHTML = `
				<input type="radio" name="at_widget_transport" value="${opt.name}" data-price="${price}" ${idx === 0 ? 'checked' : ''} />
				<span class="at-transport-option-name">${opt.name}</span>
				<span class="at-transport-option-price">${priceLabel}</span>
			`;

			label.querySelector('input').addEventListener('change', function() {
				selectedTransport = { name: opt.name, price: price };
				updateTransportDisplay();
				updatePriceTag();
				calculateTotal();
			});

			transportList.appendChild(label);
		});

		// Auto-select the first option; the user can still change it.
		selectedTransport = { name: options[0].name, price: parseFloat(options[0].price) || 0 };
		updateTransportDisplay();
		updatePriceTag();
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

	// Resolve the date-picker badge purely from the admin-selected status,
	// so "Few Seats" never leaks the exact count and "Seat Count" always shows it.
	function resolveDateBadge(d) {
		const seats = parseInt(d.available_seats) || 0;

		if (d.status === 'sold_out' || seats <= 0) {
			return { cls: 'sold', text: 'Sold Out', soldOut: true };
		}
		if (d.status === 'few_seats') {
			return { cls: 'few', text: 'Few Seats', soldOut: false };
		}
		if (d.status === 'seat_count') {
			return { cls: 'few', text: `${seats} left`, soldOut: false };
		}
		return { cls: 'open', text: 'Seats open', soldOut: false };
	}

	function renderDates(dates) {
		datesGrid.innerHTML = '';
		let urlMatchBtn = null;
		let firstAvailableBtn = null;

		dates.forEach(d => {
			const btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'at-date-select-btn';
			btn.setAttribute('data-id', d.id);

			const badge = resolveDateBadge(d);
			const isSoldOut = badge.soldOut;
			if (isSoldOut) {
				btn.classList.add('sold-out');
				btn.disabled = true;
			}

			const badgeClass = badge.cls;
			const badgeText = badge.text;

			btn.innerHTML = `
				<span class="at-date-val">${d.formatted_date}</span>
				<span class="at-date-status-badge ${badgeClass}">${badgeText}</span>
			`;

			btn.addEventListener('click', function() {
				if (isSoldOut) return;
				datesGrid.querySelectorAll('.at-date-select-btn').forEach(b => b.classList.remove('active'));
				btn.classList.add('active');

				selectedDateId = parseInt(d.id);
				updateUrlDate(d.departure_date);
				fetchBookingDetails();
			});

			if (!isSoldOut) {
				if (!firstAvailableBtn) firstAvailableBtn = btn;
				if (pendingUrlDate && d.departure_date === pendingUrlDate) urlMatchBtn = btn;
			}

			datesGrid.appendChild(btn);
		});

		// Auto-select: honor a shared/bookmarked ?date= on the very first render,
		// otherwise fall back to the first available date. The user can still change it.
		const autoBtn = urlMatchBtn || firstAvailableBtn;
		pendingUrlDate = null;
		if (autoBtn) {
			autoBtn.click();
		}
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

		// 1. Configure Seats — driven by the admin-selected departure date status.
		const availSeats = parseInt(dateDetails.available_seats) || 0;
		let seatsMsg;
		if (dateDetails.status === 'sold_out' || availSeats <= 0) {
			seatsMsg = `<span style="color:#b32d2e; font-weight:700;">Sold Out</span>`;
		} else if (dateDetails.status === 'few_seats') {
			seatsMsg = `<span style="color:#d68100; font-weight:700;">Few Seats Left</span>`;
		} else if (dateDetails.status === 'seat_count') {
			seatsMsg = availSeats <= 3
				? `<span style="color:#b32d2e; font-weight:700;">Only ${availSeats} Left!</span>`
				: `${availSeats} Seats`;
		} else {
			seatsMsg = `<span style="color:#385723; font-weight:700;">Seats Open</span>`;
		}
		textAvail.innerHTML = seatsMsg;

		// 2. Transport & Reporting — prefer the customer's Step 2 selection,
		// falling back to the city's default transport description if no
		// Transportation Options are configured for this city.
		updateTransportDisplay();
		textReporting.textContent = dateDetails.reporting_time || 'N/A';

		// 3. Price Display (includes the selected Transportation Type's surcharge)
		updatePriceTag();

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

		// The selected Transportation Type's surcharge is folded directly into the
		// per-person rate below rather than shown as its own breakdown line.
		const transportPrice = selectedTransport ? selectedTransport.price : 0;

		// 1. Adults price
		const rateAdultBase = parseFloat(dateDetails.offer_price) > 0 ? parseFloat(dateDetails.offer_price) : parseFloat(dateDetails.adult_price);
		const rateAdult = rateAdultBase + transportPrice;
		const priceAdultsSum = adults * rateAdult;

		// 2. Children price
		const rateChildBase = parseFloat(dateDetails.child_price) || 0;
		const rateChild = rateChildBase > 0 ? rateChildBase + transportPrice : 0;
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

		// Sticky bottom bar mirrors this total on every price-affecting change
		// (city, date, transport, pax, add-ons — calculateTotal() already runs on all of them).
		updateStickyBar(subtotal, totalPax);
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

			// Auto-select the first pickup point; the user can still change it.
			pickupSelect.selectedIndex = 1;
			pickupSelect.dispatchEvent(new Event('change'));
		} else {
			pickupField.style.display = 'none';
			pickupSelect.innerHTML = '';
			pickupSelect.required = false;
		}

		pickupInstructions.textContent = '';
		checkoutTermsAgree.checked = false;
		checkoutConfirm.disabled = true;
		checkoutModal.style.display = 'flex';
	});

	checkoutTermsAgree.addEventListener('change', function() {
		checkoutConfirm.disabled = !checkoutTermsAgree.checked;
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
		checkoutTermsAgree.checked = false;
		checkoutConfirm.disabled = true;
	}

	checkoutClose.addEventListener('click', closeCheckout);
	checkoutCancel.addEventListener('click', closeCheckout);

	// ==========================================
	// 5. Step 4 -> 4.5: Proceed to Payment
	// ==========================================
	checkoutConfirm.addEventListener('click', function() {
		if (!checkoutTermsAgree.checked) {
			alert('Please agree to the cancellation, refund policies, and terms and conditions before confirming.');
			return;
		}

		if (!checkoutForm.reportValidity()) return;

		const phoneInput = document.getElementById('at_checkout_phone');
		if (phoneInput) {
			const cleanPhone = phoneInput.value.replace(/[\-\s]/g, '');
			const indianPhoneRegex = /^(?:\+91|91|0)?[6789]\d{9}$/;
			if (!indianPhoneRegex.test(cleanPhone)) {
				alert('Please enter a valid Indian phone number (e.g. +91 98765 43210).');
				phoneInput.focus();
				return;
			}
		}

		openPaymentStep();
	});

	function openPaymentStep() {
		// Mirror the price breakdown from the calculator onto the payment step.
		paymentReceiptRows.innerHTML = receiptRows.innerHTML;
		paymentGrandTotal.textContent = grandTotalTag.textContent;
		paymentGrandTotal.setAttribute('data-raw', grandTotalTag.getAttribute('data-raw'));

		renderPaymentMethodBox();

		checkoutModal.style.display = 'none';
		paymentModal.style.display = 'flex';
	}

	function renderPaymentMethodBox() {
		const method = at_booking_obj.payment_method || 'cash';

		if (method === 'upi') {
			const upiId = at_booking_obj.upi_id || '';
			const qrCode = at_booking_obj.upi_qr_code || '';

			paymentMethodBox.innerHTML = `
				<h5 style="margin:0 0 8px 0; font-size:12px; font-weight:600; color:#3c434a;">Pay via UPI</h5>
				${qrCode ? `<img src="${qrCode}" alt="UPI QR Code" class="at-payment-qr-img" />` : ''}
				${upiId ? `<p class="at-payment-upi-id"><strong>UPI ID:</strong> <span class="at-upi-id-value">${upiId}</span> <button type="button" class="at-upi-copy-btn" data-upi="${upiId}" title="Copy UPI ID"><span class="dashicons dashicons-clipboard"></span></button></p>` : ''}
				<p style="margin:5px 0 0 0; font-size:11px; color:#666;">Scan the QR code or pay to the UPI ID above, then confirm your reservation below.</p>
			`;
		} else {
			paymentMethodBox.innerHTML = `
				<h5 style="margin:0 0 8px 0; font-size:12px; font-weight:600; color:#3c434a;">Pay via Cash</h5>
				<p style="margin:0; font-size:11px; color:#666;">Please pay the total amount in cash at the time of reporting/pickup.</p>
			`;
		}
	}

	function copyToClipboard(text) {
		if (navigator.clipboard && window.isSecureContext) {
			return navigator.clipboard.writeText(text);
		}
		// Fallback for non-secure contexts / older browsers.
		const textarea = document.createElement('textarea');
		textarea.value = text;
		textarea.style.position = 'fixed';
		textarea.style.opacity = '0';
		document.body.appendChild(textarea);
		textarea.focus();
		textarea.select();
		try {
			document.execCommand('copy');
		} catch (err) {
			// Ignore; nothing more we can do without Clipboard API support.
		}
		document.body.removeChild(textarea);
		return Promise.resolve();
	}

	paymentMethodBox.addEventListener('click', function(e) {
		const btn = e.target.closest('.at-upi-copy-btn');
		if (!btn) return;

		const upi = btn.getAttribute('data-upi');
		copyToClipboard(upi).then(function() {
			const original = btn.innerHTML;
			btn.innerHTML = '<span class="dashicons dashicons-yes"></span>';
			btn.classList.add('copied');
			setTimeout(function() {
				btn.innerHTML = original;
				btn.classList.remove('copied');
			}, 1500);
		});
	});

	function closePaymentStep() {
		paymentModal.style.display = 'none';
	}

	paymentBackBtn.addEventListener('click', function() {
		closePaymentStep();
		checkoutModal.style.display = 'flex';
	});

	paymentModalClose.addEventListener('click', function() {
		closePaymentStep();
		closeCheckout();
	});

	// ==========================================
	// 6. Booking Form Submission (final confirmation on the Payment step)
	// ==========================================
	paymentConfirmBtn.addEventListener('click', function() {
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
		fd.append('transport_name', selectedTransport ? selectedTransport.name : '');
		fd.append('transport_price', selectedTransport ? selectedTransport.price : 0);
		fd.append('nonce', nonce);

		chosenAddons.forEach(addon => {
			fd.append('addons[]', addon);
		});

		const originalConfirmBtnHtml = paymentConfirmBtn.innerHTML;
		paymentConfirmBtn.disabled = true;
		paymentBackBtn.disabled = true;
		paymentConfirmBtn.innerHTML = '<span class="dashicons dashicons-update" style="animation: spin 2s linear infinite; margin-right: 6px;"></span> Processing...';

		function restorePaymentButtons() {
			paymentConfirmBtn.disabled = false;
			paymentBackBtn.disabled = false;
			paymentConfirmBtn.innerHTML = originalConfirmBtnHtml;
		}

		fetch(ajaxUrl, {
			method: 'POST',
			body: fd
		})
			.then(res => res.json())
			.then(data => {
				restorePaymentButtons();
				if (data.success) {
					paymentModal.style.display = 'none';
					showSuccessReceipt(data.data);
				} else {
					alert('Booking failed: ' + data.data.message);
				}
			})
			.catch(err => {
				restorePaymentButtons();
				alert('Network error during checkout.');
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
			${res.transport_name ? `<p><strong>Transportation</strong>: ${res.transport_name}${parseFloat(res.transport_price) > 0 ? ' (+' + currency + parseFloat(res.transport_price).toFixed(2) + ')' : ''}</p>` : ''}
			<p style="border-top:1px solid #ddd; padding-top:8px; margin:8px 0 0 0; font-weight:bold; color:#137a7f; font-size:14px;"><strong>Amount Paid</strong>: ${currency} ${parseFloat(res.total).toFixed(2)}</p>
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
