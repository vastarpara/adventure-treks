/**
 * Admin Bookings screen behaviour: cascading Trek -> City -> Date/Pickup
 * dropdowns on the Add/Edit booking form, and the booking details "View" popup
 * on the bookings list table.
 */
document.addEventListener('DOMContentLoaded', function () {
	'use strict';

	if (typeof at_bookings_obj === 'undefined') {
		return;
	}

	function ajaxGet(action, nonce, params, callback) {
		const url = new URL(at_bookings_obj.ajax_url, window.location.origin);
		url.searchParams.set('action', action);
		url.searchParams.set('nonce', nonce);
		Object.keys(params || {}).forEach(function (key) {
			url.searchParams.set(key, params[key]);
		});

		fetch(url.toString(), { credentials: 'same-origin' })
			.then(function (res) { return res.json(); })
			.then(function (json) { callback(json && json.success ? json.data : null, json); })
			.catch(function () { callback(null, null); });
	}

	// ==========================================
	// Add/Edit Booking Form: cascading dropdowns
	// ==========================================
	const trekSelect        = document.getElementById('at_b_trek');
	const citySelect        = document.getElementById('at_b_city');
	const dateSelect        = document.getElementById('at_b_date');
	const pickupSelect      = document.getElementById('at_b_pickup');
	const addonsContainer   = document.getElementById('at_b_addons_container');
	const adultsInput       = document.getElementById('at_b_adults');
	const childrenInput     = document.getElementById('at_b_children');
	const amountInput       = document.getElementById('at_b_amount');
	const breakdownEl       = document.getElementById('at_b_price_breakdown');

	if (trekSelect && citySelect && dateSelect) {
		let currentPricing = null; // Holds the last fetched date/city pricing payload.

		function loadCities(trekId, preselectCityId) {
			citySelect.innerHTML = '<option value="">-- Loading cities... --</option>';
			dateSelect.innerHTML = '<option value="">-- Select City First --</option>';
			if (pickupSelect) {
				pickupSelect.innerHTML = '<option value="">-- None --</option>';
			}
			resetAddonsAndPricing();

			if (!trekId) {
				citySelect.innerHTML = '<option value="">-- Select Trek First --</option>';
				return;
			}

			ajaxGet('at_get_departure_cities', at_bookings_obj.cities_nonce, { trek_id: trekId }, function (cities) {
				citySelect.innerHTML = '<option value="">-- Select City --</option>';
				(cities || []).forEach(function (city) {
					const opt = document.createElement('option');
					opt.value = city.id;
					opt.textContent = city.city_name;
					if (preselectCityId && parseInt(preselectCityId, 10) === parseInt(city.id, 10)) {
						opt.selected = true;
					}
					citySelect.appendChild(opt);
				});

				if (citySelect.value) {
					loadDatesAndPickups(citySelect.value, dateSelect.getAttribute('data-selected'), pickupSelect ? pickupSelect.getAttribute('data-selected') : '');
				}
			});
		}

		function resetAddonsAndPricing() {
			currentPricing = null;
			if (addonsContainer) {
				addonsContainer.innerHTML = '<p class="description">Select a trek, city, and date to see available add-ons.</p>';
			}
			if (breakdownEl) {
				breakdownEl.innerHTML = '<p class="description" style="margin:0;">Select a trek, city, and date to calculate pricing automatically.</p>';
			}
		}

		function loadDatesAndPickups(cityId, preselectDateId, preselectPickup) {
			dateSelect.innerHTML = '<option value="">-- Loading dates... --</option>';
			resetAddonsAndPricing();

			if (!cityId) {
				dateSelect.innerHTML = '<option value="">-- Select City First --</option>';
				return;
			}

			ajaxGet('at_get_departure_dates', at_bookings_obj.dates_nonce, { city_id: cityId }, function (dates) {
				dateSelect.innerHTML = '<option value="">-- Select Date --</option>';
				(dates || []).forEach(function (date) {
					const opt = document.createElement('option');
					opt.value = date.id;
					opt.textContent = date.departure_date + ' (Available: ' + date.available_seats + ')';
					if (preselectDateId && parseInt(preselectDateId, 10) === parseInt(date.id, 10)) {
						opt.selected = true;
					}
					dateSelect.appendChild(opt);
				});

				if (dateSelect.value) {
					const initialAddons = addonsContainer ? addonsContainer.getAttribute('data-selected') : '[]';
					loadPricingAndAddons(cityId, dateSelect.value, initialAddons);
				}
			});

			if (pickupSelect) {
				pickupSelect.innerHTML = '<option value="">-- Loading pickup points... --</option>';
				ajaxGet('at_get_pickups', at_bookings_obj.cities_nonce, { city_id: cityId }, function (pickups) {
					pickupSelect.innerHTML = '<option value="">-- None --</option>';
					(pickups || []).forEach(function (pickup) {
						const opt = document.createElement('option');
						opt.value = pickup.location_name;
						opt.textContent = pickup.location_name + ' (' + pickup.pickup_time + ')';
						if (preselectPickup && preselectPickup === pickup.location_name) {
							opt.selected = true;
						}
						pickupSelect.appendChild(opt);
					});
				});
			}
		}

		function loadPricingAndAddons(cityId, dateId, preselectAddons) {
			let selectedAddons = [];
			try {
				selectedAddons = JSON.parse(preselectAddons || '[]');
			} catch (e) {
				selectedAddons = [];
			}

			if (addonsContainer) {
				addonsContainer.innerHTML = '<p class="description">Loading add-ons...</p>';
			}
			if (breakdownEl) {
				breakdownEl.innerHTML = '<p class="description" style="margin:0;">Loading pricing...</p>';
			}

			ajaxGet('at_get_booking_details', at_bookings_obj.public_pricing_nonce, { city_id: cityId, date_id: dateId }, function (pricing) {
				currentPricing = pricing;

				const addons = (pricing && pricing.optional_addons) ? pricing.optional_addons : [];

				if (!addonsContainer) {
					calculateTotal();
					return;
				}

				if (!addons.length) {
					addonsContainer.innerHTML = '<p class="description">No add-ons configured for this departure city.</p>';
					calculateTotal();
					return;
				}

				addonsContainer.innerHTML = '';
				addons.forEach(function (addon, idx) {
					const label = document.createElement('label');
					label.style.display = 'block';
					label.style.marginBottom = '6px';

					const checkbox = document.createElement('input');
					checkbox.type = 'checkbox';
					checkbox.name = 'addons[]';
					checkbox.className = 'at-b-addon-check';
					checkbox.value = addon.name;
					checkbox.id = 'at_b_addon_' + idx;
					checkbox.setAttribute('data-price', addon.price);
					checkbox.setAttribute('data-type', addon.type);
					checkbox.checked = selectedAddons.indexOf(addon.name) !== -1;
					checkbox.addEventListener('change', calculateTotal);

					const priceText = document.createTextNode(
						' ' + addon.name + ' (' + at_bookings_obj.currency + ' ' + parseFloat(addon.price).toFixed(2) +
						(addon.type === 'person' ? ' / person' : ' flat') + ')'
					);

					label.appendChild(checkbox);
					label.appendChild(priceText);
					addonsContainer.appendChild(label);
				});

				calculateTotal();
			});
		}

		function calculateTotal() {
			if (!currentPricing || !breakdownEl) {
				return;
			}

			const adults = parseInt(adultsInput.value, 10) || 0;
			const children = parseInt(childrenInput.value, 10) || 0;
			const totalPax = adults + children;
			const currency = at_bookings_obj.currency;

			const rateAdult = parseFloat(currentPricing.offer_price) > 0 ? parseFloat(currentPricing.offer_price) : parseFloat(currentPricing.adult_price);
			const priceAdultsSum = adults * rateAdult;

			const rateChild = parseFloat(currentPricing.child_price) || 0;
			const priceChildrenSum = children * rateChild;

			let subtotal = priceAdultsSum + priceChildrenSum;
			let markup = '';

			markup += '<div style="display:flex; justify-content:space-between;"><span>Adults (' + adults + ' x ' + currency + rateAdult.toFixed(2) + ')</span><span>' + currency + priceAdultsSum.toFixed(2) + '</span></div>';
			if (children > 0 && rateChild > 0) {
				markup += '<div style="display:flex; justify-content:space-between;"><span>Children (' + children + ' x ' + currency + rateChild.toFixed(2) + ')</span><span>' + currency + priceChildrenSum.toFixed(2) + '</span></div>';
			}

			// Group discount: pick the rule with the highest min_seats that totalPax still qualifies for.
			if (currentPricing.group_discount && currentPricing.group_discount.length > 0) {
				let bestRule = null;
				currentPricing.group_discount.forEach(function (rule) {
					const minSeats = parseInt(rule.min_seats, 10) || 0;
					if (totalPax >= minSeats && (!bestRule || minSeats > (parseInt(bestRule.min_seats, 10) || 0))) {
						bestRule = rule;
					}
				});

				if (bestRule) {
					let discountSum = 0;
					if (bestRule.type === 'percent') {
						discountSum = subtotal * (parseFloat(bestRule.value) / 100);
						markup += '<div style="display:flex; justify-content:space-between; color:#385723;"><span>Group Discount (' + bestRule.min_seats + '+ Pax: ' + bestRule.value + '%)</span><span>-' + currency + discountSum.toFixed(2) + '</span></div>';
					} else {
						discountSum = parseFloat(bestRule.value) || 0;
						markup += '<div style="display:flex; justify-content:space-between; color:#385723;"><span>Group Discount Flat (' + bestRule.min_seats + '+ Pax)</span><span>-' + currency + discountSum.toFixed(2) + '</span></div>';
					}
					subtotal -= discountSum;
				}
			}

			// Mandatory extra charges configured on the trek's pricing.
			if (currentPricing.extra_charges && currentPricing.extra_charges.length > 0) {
				currentPricing.extra_charges.forEach(function (charge) {
					let amt = 0;
					if (charge.type === 'person') {
						amt = totalPax * parseFloat(charge.price);
						markup += '<div style="display:flex; justify-content:space-between;"><span>' + charge.name + ' (' + totalPax + ' x ' + currency + parseFloat(charge.price).toFixed(2) + ')</span><span>+' + currency + amt.toFixed(2) + '</span></div>';
					} else {
						amt = parseFloat(charge.price) || 0;
						markup += '<div style="display:flex; justify-content:space-between;"><span>' + charge.name + ' (Flat)</span><span>+' + currency + amt.toFixed(2) + '</span></div>';
					}
					subtotal += amt;
				});
			}

			// Optional add-ons the admin has checked.
			const checkedAddons = addonsContainer ? addonsContainer.querySelectorAll('.at-b-addon-check:checked') : [];
			checkedAddons.forEach(function (chk) {
				const price = parseFloat(chk.getAttribute('data-price')) || 0;
				const type = chk.getAttribute('data-type');
				let amt = 0;
				if (type === 'person') {
					amt = totalPax * price;
					markup += '<div style="display:flex; justify-content:space-between;"><span>' + chk.value + ' (' + totalPax + ' x ' + currency + price.toFixed(2) + ')</span><span>+' + currency + amt.toFixed(2) + '</span></div>';
				} else {
					amt = price;
					markup += '<div style="display:flex; justify-content:space-between;"><span>' + chk.value + ' (Flat)</span><span>+' + currency + amt.toFixed(2) + '</span></div>';
				}
				subtotal += amt;
			});

			markup += '<div style="display:flex; justify-content:space-between; font-weight:700; border-top:1px solid #dcdcde; margin-top:6px; padding-top:6px;"><span>Total</span><span>' + currency + subtotal.toFixed(2) + '</span></div>';

			breakdownEl.innerHTML = markup;
			if (amountInput) {
				amountInput.value = subtotal.toFixed(2);
			}
		}

		if (adultsInput) {
			adultsInput.addEventListener('input', calculateTotal);
		}
		if (childrenInput) {
			childrenInput.addEventListener('input', calculateTotal);
		}

		trekSelect.addEventListener('change', function () {
			loadCities(trekSelect.value, '');
		});

		citySelect.addEventListener('change', function () {
			loadDatesAndPickups(citySelect.value, '', '');
		});

		dateSelect.addEventListener('change', function () {
			if (citySelect.value && dateSelect.value) {
				loadPricingAndAddons(citySelect.value, dateSelect.value, '[]');
			} else {
				resetAddonsAndPricing();
			}
		});

		// Bootstrap for Edit mode: pre-fill cascading selects using the stored values.
		const initialCityId  = citySelect.getAttribute('data-selected');
		const initialDateId  = dateSelect.getAttribute('data-selected');
		const initialPickup  = pickupSelect ? pickupSelect.getAttribute('data-selected') : '';

		if (trekSelect.value) {
			loadCities(trekSelect.value, initialCityId);
			if (initialCityId) {
				loadDatesAndPickups(initialCityId, initialDateId, initialPickup);
			}
		}
	}

	// ==========================================
	// Bookings List: "View" details popup
	// ==========================================
	const viewModal   = document.getElementById('at_booking_view_modal');
	const viewLoading = document.getElementById('at_booking_view_loading');
	const viewTable   = document.getElementById('at_booking_view_table');
	const viewTbody   = document.getElementById('at_booking_view_tbody');
	const viewRefTag  = document.getElementById('at_booking_view_ref');
	const viewCloseBtn1 = document.getElementById('at_booking_view_close_btn');
	const viewCloseBtn2 = document.getElementById('at_booking_view_close_btn2');

	function closeViewModal() {
		if (viewModal) {
			viewModal.style.display = 'none';
		}
	}

	if (viewModal) {
		document.querySelectorAll('.at-view-booking-btn').forEach(function (btn) {
			btn.addEventListener('click', function () {
				const bookingId = btn.getAttribute('data-booking-id');

				viewModal.style.display = 'flex';
				viewLoading.style.display = 'block';
				viewTable.style.display = 'none';
				viewTbody.innerHTML = '';
				viewRefTag.textContent = '';

				ajaxGet('at_get_single_booking_details', at_bookings_obj.details_nonce, { booking_id: bookingId }, function (data) {
					viewLoading.style.display = 'none';

					if (!data) {
						viewTbody.innerHTML = '<tr><td>Unable to load booking details.</td></tr>';
						viewTable.style.display = 'table';
						return;
					}

					viewRefTag.textContent = data.booking_ref;

					const addonsText = (data.addons && data.addons.length) ? data.addons.join(', ') : '-';
					const rows = [
						['Booking ID', data.booking_ref],
						['Customer Name', data.cust_name],
						['Email', data.cust_email],
						['Phone', data.cust_phone],
						['Trek', data.trek_title],
						['Departure City', data.city_name],
						['Travel Date', data.departure_date],
						['Pickup Point', data.pickup_point || '-'],
						['Adults', data.num_adults],
						['Children', data.num_children],
						['Total Seats', data.seats],
						['Add-ons', addonsText],
						['Total Amount', data.currency + ' ' + data.total_amount],
						['Status', data.status],
						['Payment Status', data.payment_status],
						['Booked On', data.created_at],
					];

					viewTbody.innerHTML = '';
					rows.forEach(function (row) {
						const tr = document.createElement('tr');

						const th = document.createElement('th');
						th.style.width = '180px';
						th.style.textAlign = 'left';
						th.textContent = row[0];

						const td = document.createElement('td');
						td.textContent = row[1];

						tr.appendChild(th);
						tr.appendChild(td);
						viewTbody.appendChild(tr);
					});

					viewTable.style.display = 'table';
				});
			});
		});

		if (viewCloseBtn1) {
			viewCloseBtn1.addEventListener('click', closeViewModal);
		}
		if (viewCloseBtn2) {
			viewCloseBtn2.addEventListener('click', closeViewModal);
		}
		viewModal.addEventListener('click', function (e) {
			if (e.target === viewModal) {
				closeViewModal();
			}
		});
	}
});
