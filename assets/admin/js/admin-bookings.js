/**
 * Admin Bookings screen behaviour: cascading Trek -> City -> Date/Pickup
 * dropdowns on the Add/Edit booking form, and the booking details "View" popup
 * on the bookings list table.
 */
document.addEventListener('DOMContentLoaded', function () {

	// Price formatting driven by Settings > Currency (symbol, position, separators, decimals).
	const at_price_format = at_bookings_obj.price_format || { symbol: at_bookings_obj.currency_symbol || at_bookings_obj.currency || '', position: 'left', thousand: ',', decimal: '.', decimals: 2 };
	function atFormatPrice(amount) {
		const fmt = at_price_format;
		const value = Math.abs(parseFloat(amount) || 0).toFixed(fmt.decimals);
		const parts = value.split('.');
		parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, fmt.thousand);
		const number = parts.join(fmt.decimals > 0 ? fmt.decimal : '');
		const sign = (parseFloat(amount) || 0) < 0 ? '-' : '';
		switch (fmt.position) {
			case 'right': return sign + number + fmt.symbol;
			case 'left_space': return sign + fmt.symbol + ' ' + number;
			case 'right_space': return sign + number + ' ' + fmt.symbol;
			default: return sign + fmt.symbol + number;
		}
	}
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
	const pickupRow         = document.getElementById('at_b_pickup_row');
	const pickupSelect      = document.getElementById('at_b_pickup');
	const transportRow      = document.getElementById('at_b_transport_row');
	const transportSelect   = document.getElementById('at_b_transport');
	const transportPriceInput = document.getElementById('at_b_transport_price');
	const addonsRow         = document.getElementById('at_b_addons_row');
	const addonsContainer   = document.getElementById('at_b_addons_container');
	const adultsInput       = document.getElementById('at_b_adults');
	const childrenInput     = document.getElementById('at_b_children');
	const amountInput       = document.getElementById('at_b_amount');
	const breakdownEl       = document.getElementById('at_b_price_breakdown');

	if (trekSelect && citySelect && dateSelect) {
		let currentPricing = null; // Holds the last fetched date/city pricing payload.
		let currentTransportPrice = 0; // Additional price of the currently selected Transportation Type.
		let lastValidAdults = adultsInput ? (parseInt(adultsInput.value, 10) || 1) : 1;
		let lastValidChildren = childrenInput ? (parseInt(childrenInput.value, 10) || 0) : 0;

		function loadCities(trekId, preselectCityId) {
			citySelect.innerHTML = '<option value="">-- Loading cities... --</option>';
			dateSelect.innerHTML = '<option value="">-- Select City First --</option>';
			if (pickupSelect) {
				pickupSelect.innerHTML = '<option value="">-- Select Pickup Point --</option>';
				pickupSelect.required = false;
			}
			if (pickupRow) {
				pickupRow.style.display = 'none';
			}
			if (transportSelect) {
				transportSelect.innerHTML = '<option value="">-- Select Transportation Type --</option>';
			}
			if (transportRow) {
				transportRow.style.display = 'none';
			}
			currentTransportPrice = 0;
			if (transportPriceInput) {
				transportPriceInput.value = '0.00';
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
					loadDatesAndPickups(citySelect.value, dateSelect.getAttribute('data-selected'), pickupSelect ? pickupSelect.getAttribute('data-selected') : '', transportSelect ? transportSelect.getAttribute('data-selected') : '');
				}
			});
		}

		function resetAddonsAndPricing() {
			currentPricing = null;
			if (addonsRow) {
				addonsRow.style.display = 'none';
			}
			if (addonsContainer) {
				addonsContainer.innerHTML = '';
			}
			if (breakdownEl) {
				breakdownEl.innerHTML = '<p class="description">Select a trek, city, and date to calculate pricing automatically.</p>';
			}
		}

		function loadDatesAndPickups(cityId, preselectDateId, preselectPickup, preselectTransport) {
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
					pickups = pickups || [];

					if (!pickups.length) {
						pickupSelect.innerHTML = '<option value="">-- Select Pickup Point --</option>';
						pickupSelect.required = false;
						if (pickupRow) {
							pickupRow.style.display = 'none';
						}
						return;
					}

					pickupSelect.innerHTML = '<option value="">-- Select Pickup Point --</option>';
					pickups.forEach(function (pickup) {
						const opt = document.createElement('option');
						opt.value = pickup.location_name;
						opt.textContent = pickup.location_name + ' (' + pickup.pickup_time + ')';
						if (preselectPickup && preselectPickup === pickup.location_name) {
							opt.selected = true;
						}
						pickupSelect.appendChild(opt);
					});
					pickupSelect.required = true;
					if (pickupRow) {
						pickupRow.style.display = '';
					}
				});
			}

			if (transportSelect) {
				transportSelect.innerHTML = '<option value="">-- Loading transportation options... --</option>';
				ajaxGet('at_get_transport_options', at_bookings_obj.public_pricing_nonce, { city_id: cityId }, function (payload) {
					const options = (payload && Array.isArray(payload.options)) ? payload.options : [];

					currentTransportPrice = 0;
					if (transportPriceInput) {
						transportPriceInput.value = '0.00';
					}

					if (!options.length) {
						transportSelect.innerHTML = '<option value="">-- Select Transportation Type --</option>';
						if (transportRow) {
							transportRow.style.display = 'none';
						}
						return;
					}

					transportSelect.innerHTML = '';
					options.forEach(function (option, idx) {
						const opt = document.createElement('option');
						const price = parseFloat(option.price) || 0;
						opt.value = option.name;
						opt.setAttribute('data-price', price);
						opt.textContent = option.name + (price > 0 ? ' (+' + atFormatPrice(price) + ')' : '');
						if (preselectTransport ? preselectTransport === option.name : 0 === idx) {
							opt.selected = true;
							currentTransportPrice = price;
						}
						transportSelect.appendChild(opt);
					});
					if (transportPriceInput) {
						transportPriceInput.value = currentTransportPrice.toFixed(2);
					}
					if (transportRow) {
						transportRow.style.display = '';
					}
					calculateTotal();
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
					addonsContainer.innerHTML = '';
					if (addonsRow) {
						addonsRow.style.display = 'none';
					}
					calculateTotal();
					return;
				}

				if (addonsRow) {
					addonsRow.style.display = '';
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
						' ' + addon.name + ' (' + atFormatPrice(parseFloat(addon.price)) +
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

			const rateAdult = parseFloat(currentPricing.offer_price) > 0 ? parseFloat(currentPricing.offer_price) : parseFloat(currentPricing.adult_price);
			const priceAdultsSum = adults * rateAdult;

			const rateChild = parseFloat(currentPricing.child_price) || 0;
			const priceChildrenSum = children * rateChild;

			let subtotal = priceAdultsSum + priceChildrenSum;
			let markup = '';

			markup += '<div style="display:flex; justify-content:space-between;"><span>Adults (' + adults + ' x ' + atFormatPrice(rateAdult) + ')</span><span>' + atFormatPrice(priceAdultsSum) + '</span></div>';
			if (children > 0 && rateChild > 0) {
				markup += '<div style="display:flex; justify-content:space-between;"><span>Children (' + children + ' x ' + atFormatPrice(rateChild) + ')</span><span>' + atFormatPrice(priceChildrenSum) + '</span></div>';
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
						markup += '<div style="display:flex; justify-content:space-between; color:#385723;"><span>Group Discount (' + bestRule.min_seats + '+ Pax: ' + bestRule.value + '%)</span><span>-' + atFormatPrice(discountSum) + '</span></div>';
					} else {
						discountSum = parseFloat(bestRule.value) || 0;
						markup += '<div style="display:flex; justify-content:space-between; color:#385723;"><span>Group Discount Flat (' + bestRule.min_seats + '+ Pax)</span><span>-' + atFormatPrice(discountSum) + '</span></div>';
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
						markup += '<div style="display:flex; justify-content:space-between;"><span>' + charge.name + ' (' + totalPax + ' x ' + atFormatPrice(parseFloat(charge.price)) + ')</span><span>+' + atFormatPrice(amt) + '</span></div>';
					} else {
						amt = parseFloat(charge.price) || 0;
						markup += '<div style="display:flex; justify-content:space-between;"><span>' + charge.name + ' (Flat)</span><span>+' + atFormatPrice(amt) + '</span></div>';
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
					markup += '<div style="display:flex; justify-content:space-between;"><span>' + chk.value + ' (' + totalPax + ' x ' + atFormatPrice(price) + ')</span><span>+' + atFormatPrice(amt) + '</span></div>';
				} else {
					amt = price;
					markup += '<div style="display:flex; justify-content:space-between;"><span>' + chk.value + ' (Flat)</span><span>+' + atFormatPrice(amt) + '</span></div>';
				}
				subtotal += amt;
			});

			// Selected Transportation Type (added per person, on top of everything else).
			if (transportSelect && transportSelect.value && currentTransportPrice > 0) {
				const transportAmt = totalPax * currentTransportPrice;
				markup += '<div style="display:flex; justify-content:space-between;"><span>' + transportSelect.value + ' (' + totalPax + ' x ' + atFormatPrice(currentTransportPrice) + ')</span><span>+' + atFormatPrice(transportAmt) + '</span></div>';
				subtotal += transportAmt;
			}

			markup += '<div style="display:flex; justify-content:space-between; font-weight:700; border-top:1px solid #dcdcde; margin-top:6px; padding-top:6px;"><span>Total</span><span>' + atFormatPrice(subtotal) + '</span></div>';

			breakdownEl.innerHTML = markup;
			if (amountInput) {
				amountInput.value = subtotal.toFixed(2);
			}
		}

		function validateSeatCounts(changedInput) {
			let adults = parseInt(adultsInput.value, 10);
			let children = parseInt(childrenInput.value, 10);

			// Enforce minimums: at least 1 adult, never negative children.
			if (isNaN(adults) || adults < 1) {
				adults = 1;
			}
			if (isNaN(children) || children < 0) {
				children = 0;
			}

			// Enforce the selected date's remaining seat capacity, if known.
			const availableSeats = currentPricing ? parseInt(currentPricing.available_seats, 10) : null;
			if (availableSeats !== null && ! isNaN(availableSeats) && (adults + children) > availableSeats) {
				alert('Cannot exceed remaining seat capacity limit (' + availableSeats + ' seats available).');
				adults = Math.min(adults, lastValidAdults);
				children = Math.min(children, lastValidChildren);
				if ((adults + children) > availableSeats) {
					if (changedInput === adultsInput) {
						adults = Math.max(1, availableSeats - children);
					} else {
						children = Math.max(0, availableSeats - adults);
					}
				}
			}

			adultsInput.value = adults;
			childrenInput.value = children;
			lastValidAdults = adults;
			lastValidChildren = children;
		}

		if (adultsInput) {
			adultsInput.addEventListener('input', calculateTotal);
			adultsInput.addEventListener('change', function () {
				validateSeatCounts(adultsInput);
				calculateTotal();
			});
		}
		if (childrenInput) {
			childrenInput.addEventListener('input', calculateTotal);
			childrenInput.addEventListener('change', function () {
				validateSeatCounts(childrenInput);
				calculateTotal();
			});
		}

		const bookingForm = document.getElementById('at_booking_form');
		if (bookingForm) {
			bookingForm.addEventListener('submit', function () {
				validateSeatCounts(adultsInput);
				calculateTotal();
			});
		}

		trekSelect.addEventListener('change', function () {
			loadCities(trekSelect.value, '');
		});

		citySelect.addEventListener('change', function () {
			loadDatesAndPickups(citySelect.value, '', '', '');
		});

		dateSelect.addEventListener('change', function () {
			if (citySelect.value && dateSelect.value) {
				loadPricingAndAddons(citySelect.value, dateSelect.value, '[]');
			} else {
				resetAddonsAndPricing();
			}
		});

		if (transportSelect) {
			transportSelect.addEventListener('change', function () {
				const selectedOpt = transportSelect.options[transportSelect.selectedIndex];
				currentTransportPrice = selectedOpt ? (parseFloat(selectedOpt.getAttribute('data-price')) || 0) : 0;
				if (transportPriceInput) {
					transportPriceInput.value = currentTransportPrice.toFixed(2);
				}
				calculateTotal();
			});
		}

		// Bootstrap for Edit mode: pre-fill cascading selects using the stored values.
		const initialCityId  = citySelect.getAttribute('data-selected');
		const initialDateId  = dateSelect.getAttribute('data-selected');
		const initialPickup  = pickupSelect ? pickupSelect.getAttribute('data-selected') : '';
		const initialTransport = transportSelect ? transportSelect.getAttribute('data-selected') : '';

		if (trekSelect.value) {
			loadCities(trekSelect.value, initialCityId);
			if (initialCityId) {
				loadDatesAndPickups(initialCityId, initialDateId, initialPickup, initialTransport);
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
						['Transportation Type', data.transport_type ? data.transport_type + (parseFloat(data.transport_price) > 0 ? ' (+' + atFormatPrice(parseFloat(data.transport_price)) + ')' : '') : '-'],
						['Adults', data.num_adults],
						['Children', data.num_children],
						['Total Seats', data.seats],
						['Add-ons', addonsText],
						['Total Amount', atFormatPrice(data.total_amount)],
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
