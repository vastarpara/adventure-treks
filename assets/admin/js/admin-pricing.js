/**
 * JavaScript for managing Departure City Pricing rules (AJAX repeaters)
 */

document.addEventListener('DOMContentLoaded', function() {

	const tbodyCities = document.getElementById('at_cities_tbody');
	const root = document.getElementById('at_departures_module_root');
	if (!tbodyCities || !root) return;

	const trekId = root.getAttribute('data-trek-id');
	const ajaxUrl = at_pricing_obj.ajax_url;
	const nonce = at_pricing_obj.nonce;

	// Modal Elements
	const pricingModal = document.getElementById('at_pricing_modal');
	const pricingCityTitle = document.getElementById('at_pricing_modal_city_title');
	const pricingCloseBtn = document.getElementById('at_pricing_modal_close_btn');
	const pricingCancelBtn = document.getElementById('at_pricing_modal_cancel_btn');
	const pricingSaveBtn = document.getElementById('at_pricing_modal_save_btn');
	const pricingForm = document.getElementById('at_pricing_form');

	// List containers
	const groupList = document.getElementById('at_group_discount_rules_list');
	const extraList = document.getElementById('at_extra_charges_list');
	const addonList = document.getElementById('at_optional_addons_list');

	// Buttons
	const addGroupBtn = document.getElementById('at_add_group_discount_rule_btn');
	const addExtraBtn = document.getElementById('at_add_extra_charge_btn');
	const addAddonBtn = document.getElementById('at_add_optional_addon_btn');

	let activeCityId = null;

	// ==========================================
	// 1. Initial Launch
	// ==========================================
	tbodyCities.addEventListener('click', function(e) {
		if (!e.target.classList.contains('pricing')) return;
		e.preventDefault();

		activeCityId = parseInt(e.target.getAttribute('data-id'));
		const cityName = e.target.getAttribute('data-name');

		pricingCityTitle.textContent = cityName;
		document.getElementById('at_pricing_city_id').value = activeCityId;
		pricingModal.style.display = 'flex';

		fetchPricing();
	});

	function clearPricingForm() {
		const f = document.getElementById('at_pricing_form');
		if (!f) return;
		const inputs = f.querySelectorAll('input');
		inputs.forEach(i => {
			if (i.type === 'number') i.value = '0.00';
			else if (i.type === 'hidden') i.value = '';
			else i.value = '';
		});
	}

	function closePricingModal() {
		pricingModal.style.display = 'none';
		clearPricingForm();
		groupList.innerHTML = '';
		extraList.innerHTML = '';
		addonList.innerHTML = '';
		activeCityId = null;
	}

	pricingCloseBtn.addEventListener('click', closePricingModal);
	pricingCancelBtn.addEventListener('click', closePricingModal);

	// ==========================================
	// 2. Fetch Pricing Data
	// ==========================================
	function fetchPricing() {
		if (!activeCityId) return;

		const url = `${ajaxUrl}?action=at_get_city_pricing&city_id=${activeCityId}&nonce=${nonce}`;

		fetch(url)
			.then(res => res.json())
			.then(data => {
				if (data.success) {
					const p = data.data;
					document.getElementById('at_pricing_adult_price').value = p.adult_price;
					document.getElementById('at_pricing_child_price').value = p.child_price;
					document.getElementById('at_pricing_offer_price').value = p.offer_price;

					renderGroupDiscounts(p.group_discount || []);
					renderExtraCharges(p.extra_charges || []);
					renderOptionalAddons(p.optional_addons || []);
				} else {
					at_admin_toast('Failed to load pricing data: ' + data.data.message);
				}
			});
	}

	// ==========================================
	// 3. Render and Repeater Logics
	// ==========================================

	// --- Group Discounts ---
	function renderGroupDiscounts(rules) {
		groupList.innerHTML = '';
		rules.forEach((rule, idx) => {
			addGroupDiscountRow(rule.min_seats, rule.type, rule.value, idx);
		});
	}

	function addGroupDiscountRow(minSeats = '', type = 'percent', value = '', idx = Date.now()) {
		const row = document.createElement('div');
		row.className = 'at-pricing-row-item';
		row.style.cssText = 'display:grid; grid-template-columns: 1fr 1fr 1fr auto; gap:10px; margin-bottom:10px; align-items:center;';

		row.innerHTML = `
			<div>
				<input type="number" name="group_discount[${idx}][min_seats]" value="${minSeats}" placeholder="Min Pax (e.g. 5)" style="width:100%;" min="1" />
			</div>
			<div>
				<select name="group_discount[${idx}][type]" style="width:100%;">
					<option value="percent" ${type === 'percent' ? 'selected' : ''}>Percentage (%)</option>
					<option value="flat" ${type === 'flat' ? 'selected' : ''}>Flat Discount</option>
				</select>
			</div>
			<div>
				<input type="number" step="0.01" name="group_discount[${idx}][value]" value="${value}" placeholder="Discount Val" style="width:100%;" min="0" />
			</div>
			<div>
				<button type="button" class="button at-remove-pricing-row" style="color:#b32d2e; border-color:#b32d2e;">Remove</button>
			</div>
		`;
		groupList.appendChild(row);
	}

	addGroupBtn.addEventListener('click', () => addGroupDiscountRow());

	// --- Extra Charges ---
	function renderExtraCharges(charges) {
		extraList.innerHTML = '';
		charges.forEach((charge, idx) => {
			addExtraChargeRow(charge.name, charge.price, charge.type, idx);
		});
	}

	function addExtraChargeRow(name = '', price = '', type = 'person', idx = Date.now()) {
		const row = document.createElement('div');
		row.className = 'at-pricing-row-item';
		row.style.cssText = 'display:grid; grid-template-columns: 2fr 1fr 1fr auto; gap:10px; margin-bottom:10px; align-items:center;';

		row.innerHTML = `
			<div>
				<input type="text" name="extra_charges[${idx}][name]" value="${name}" placeholder="Charge Name (e.g. Permits)" style="width:100%;" />
			</div>
			<div>
				<input type="number" step="0.01" name="extra_charges[${idx}][price]" value="${price}" placeholder="Amount" style="width:100%;" min="0" />
			</div>
			<div>
				<select name="extra_charges[${idx}][type]" style="width:100%;">
					<option value="person" ${type === 'person' ? 'selected' : ''}>Per Person</option>
					<option value="flat" ${type === 'flat' ? 'selected' : ''}>Flat Fee</option>
				</select>
			</div>
			<div>
				<button type="button" class="button at-remove-pricing-row" style="color:#b32d2e; border-color:#b32d2e;">Remove</button>
			</div>
		`;
		extraList.appendChild(row);
	}

	addExtraBtn.addEventListener('click', () => addExtraChargeRow());

	// --- Optional Add-ons ---
	function renderOptionalAddons(addons) {
		addonList.innerHTML = '';
		addons.forEach((addon, idx) => {
			addOptionalAddonRow(addon.name, addon.desc, addon.price, addon.type, idx);
		});
	}

	function addOptionalAddonRow(name = '', desc = '', price = '', type = 'person', idx = Date.now()) {
		const row = document.createElement('div');
		row.className = 'at-pricing-row-item';
		row.style.cssText = 'display:grid; grid-template-columns: 2fr 2fr 1fr 1fr auto; gap:10px; margin-bottom:10px; align-items:center;';

		row.innerHTML = `
			<div>
				<input type="text" name="optional_addons[${idx}][name]" value="${name}" placeholder="Addon Name (e.g. Sleeping Bag)" style="width:100%;" />
			</div>
			<div>
				<input type="text" name="optional_addons[${idx}][desc]" value="${desc}" placeholder="Brief details" style="width:100%;" />
			</div>
			<div>
				<input type="number" step="0.01" name="optional_addons[${idx}][price]" value="${price}" placeholder="Amount" style="width:100%;" min="0" />
			</div>
			<div>
				<select name="optional_addons[${idx}][type]" style="width:100%;">
					<option value="person" ${type === 'person' ? 'selected' : ''}>Per Person</option>
					<option value="flat" ${type === 'flat' ? 'selected' : ''}>Flat Fee</option>
				</select>
			</div>
			<div>
				<button type="button" class="button at-remove-pricing-row" style="color:#b32d2e; border-color:#b32d2e;">Remove</button>
			</div>
		`;
		addonList.appendChild(row);
	}

	addAddonBtn.addEventListener('click', () => addOptionalAddonRow());

	// Remove row delegate
	const removeRowHandler = function(e) {
		if (e.target && e.target.classList.contains('at-remove-pricing-row')) {
			e.preventDefault();
			const row = e.target.closest('.at-pricing-row-item');
			if (row) row.remove();
		}
	};
	groupList.addEventListener('click', removeRowHandler);
	extraList.addEventListener('click', removeRowHandler);
	addonList.addEventListener('click', removeRowHandler);

	// ==========================================
	// 4. Save Pricing Data
	// ==========================================
	pricingSaveBtn.addEventListener('click', function(e) {
		e.preventDefault();
		const f = document.getElementById('at_pricing_form');
		if (!f) return;

		// Validation
		const adultPrice = document.getElementById('at_pricing_adult_price');
		if (!adultPrice.value.trim()) {
			at_admin_toast('Adult Price is required.');
			adultPrice.focus();
			return;
		}

		const formData = new FormData();
		formData.append('action', 'at_save_city_pricing');
		formData.append('city_id', activeCityId);
		formData.append('trek_id', trekId);
		formData.append('nonce', nonce);

		// Manually append
		const inputs = f.querySelectorAll('input, select');
		inputs.forEach(i => {
			if (i.name) formData.append(i.name, i.value);
		});

		pricingModal.style.display = 'none';

		fetch(ajaxUrl, {
			method: 'POST',
			body: formData
		})
			.then(res => res.json())
			.then(data => {
				if (data.success) {
					closePricingModal();
					// Trigger global cities list update if active
					if (window.fetchCities) {
						window.fetchCities();
					} else {
						// Simple fallback page reload to sync price view
						location.reload();
					}
				} else {
					at_admin_toast('Saving rules failed: ' + data.data.message);
					pricingModal.style.display = 'flex';
				}
			})
			.catch(err => {
				at_admin_toast('Network error while saving pricing.');
				pricingModal.style.display = 'flex';
			});
	});

});
