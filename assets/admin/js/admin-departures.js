/**
 * JavaScript for managing Departure Cities Admin UI (AJAX & drag-and-drop)
 */

document.addEventListener('DOMContentLoaded', function() {

	var root = document.getElementById('trekpilot_departures_module_root');
	if (!root) return;

	// Number fields with min="0" (prices, seats, ...) never accept a minus sign or a negative value,
	// whether typed, pasted or stepped with the spinner. Delegated so fields created later are covered too.
	document.addEventListener('keydown', function(e) {
		var t = e.target;
		if (t && t.matches && t.matches('input[type="number"][min="0"]') && (e.key === '-' || e.key === 'e' || e.key === 'E')) {
			e.preventDefault();
		}
	});
	document.addEventListener('input', function(e) {
		var t = e.target;
		if (t && t.matches && t.matches('input[type="number"][min="0"]') && parseFloat(t.value) < 0) {
			t.value = 0;
		}
	});

	// Whole-number fields (seats, deadline, pax, day number) accept digits only: no decimal point, comma, sign or exponent.
	document.addEventListener('keydown', function(e) {
		var t = e.target;
		if (t && t.matches && t.matches('input[data-trekpilot-integer]') && ['.', ',', '-', '+', 'e', 'E'].indexOf(e.key) !== -1) {
			e.preventDefault();
		}
	});
	document.addEventListener('input', function(e) {
		var t = e.target;
		if (t && t.matches && t.matches('input[data-trekpilot-integer]') && /[^0-9]/.test(t.value)) {
			// Pasted "100.0000" becomes 100 (cut at the decimal point), not 1000000.
			t.value = String(t.value).split(/[.,]/)[0].replace(/[^0-9]/g, '');
		}
	});

	// In the block editor the meta box lives in a scrolling, clipped container, which breaks
	// position:fixed overlays (modal cut off / offset). Re-parent every modal to <body> so they
	// always cover the whole screen. Element identity is kept, so existing lookups still work.
	root.querySelectorAll('.trekpilot-modal-overlay').forEach(function(modal) {
		document.body.appendChild(modal);
	});


	var trekId  = root.getAttribute('data-trek-id');
	var ajaxUrl = trekpilot_departures_obj.ajax_url;
	var nonce   = trekpilot_departures_obj.nonce;

	var tbody   = document.getElementById('trekpilot_cities_tbody');
	var loading = document.getElementById('trekpilot_cities_loading');
	if (!tbody || !loading) return;

	/* Lazy lookup with console logging */
	function g(id) {
		var node = document.getElementById(id);
		if (!node) { console.error('TrekPilot: #' + id + ' not found.'); }
		return node;
	}

	var citiesList = [];

	/* ---- helpers ---- */
	function openCityModal() {
		var m = g('trekpilot_city_modal');
		if (m) m.style.display = 'flex';
	}


	/* ---- fetch & render ---- */
	function fetchCities() {
		loading.style.display = 'block';
		tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px;color:#666;">Loading...</td></tr>';
		fetch(ajaxUrl + '?action=trekpilot_get_departure_cities&trek_id=' + trekId + '&nonce=' + nonce)
			.then(function(r){ return r.json(); })
			.then(function(data){
				loading.style.display = 'none';
				if (data.success) { citiesList = data.data; renderCities(); }
				else { tbody.innerHTML = '<tr><td colspan="8" style="color:#b32d2e;text-align:center;">Error: ' + data.data.message + '</td></tr>'; }
			})
			.catch(function(){
				loading.style.display = 'none';
				tbody.innerHTML = '<tr><td colspan="8" style="color:#b32d2e;text-align:center;">Network error.</td></tr>';
			});
	}

	/* ---- setup progress: what each city still needs ---- */
	function esc(text) {
		return String(text == null ? '' : text).replace(/[&<>"']/g, function(ch) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
		});
	}

	function setupSteps(city) {
		var s = city.setup || {};
		return [
			{ key: 'dates', title: 'Departure dates', required: true, done: s.dates > 0,
				text: 'Add at least one upcoming date and its seats. Customers cannot book a city that has no dates.', button: 'Add dates' },
			{ key: 'pricing', title: 'Pricing', required: false, done: !!s.pricing,
				text: 'Optional: child price, group discounts, add-ons, extra charges and transport options. Until you save it, the base price above is used.', button: 'Set pricing' },
			{ key: 'pickups', title: 'Pickup points', required: false, done: s.pickups > 0,
				text: 'Where trekkers are picked up, with time and a map link. Customers choose one while booking.', button: 'Add pickup points' },
			{ key: 'itinerary', title: 'Itinerary', required: false, done: s.itinerary > 0,
				text: 'The day-wise plan shown on the trek page for this city.', button: 'Build itinerary' }
		];
	}

	function setupChips(city) {
		var s = city.setup;
		if (!s) return '';
		function chip(key, label, ok, level) {
			return '<a href="#" class="trekpilot-action-link trekpilot-setup-chip ' + key + ' ' + (ok ? 'ok' : level) + '" data-id="' + city.id + '" data-name="' + esc(city.city_name) + '">' + label + '</a>';
		}
		return '<div class="trekpilot-city-setup">'
			+ chip('dates', s.dates > 0 ? 'Dates: ' + s.dates : 'Dates: none', s.dates > 0, 'missing')
			+ chip('pricing', s.pricing ? 'Pricing: set' : 'Pricing: default', s.pricing, 'warn')
			+ chip('pickups', s.pickups > 0 ? 'Pickups: ' + s.pickups : 'Pickups: none', s.pickups > 0, 'warn')
			+ chip('itinerary', s.itinerary > 0 ? 'Itinerary: ' + s.itinerary + ' day' + (s.itinerary > 1 ? 's' : '') : 'Itinerary: none', s.itinerary > 0, 'warn')
			+ '</div>';
	}

	/* Guide shown right after a new city is added; it follows the city until every step is done. */
	var nextStepsCityId = 0;
	var nextStepsPanel = document.getElementById('trekpilot_city_next_steps');

	function renderNextSteps() {
		if (!nextStepsPanel) return;
		var city = nextStepsCityId ? citiesList.find(function(c){ return parseInt(c.id) === nextStepsCityId; }) : null;
		if (!city) { nextStepsPanel.style.display = 'none'; return; }

		var steps = setupSteps(city);
		var pending = steps.filter(function(st){ return !st.done; });
		var html = '<button type="button" class="trekpilot-next-steps-close" aria-label="Dismiss">&times;</button>';

		if (!pending.length) {
			html += '<h4>&#10003; ' + esc(city.city_name) + ' is fully set up</h4>'
				+ '<p>Dates, pricing, pickup points and itinerary are all in place. Remember to <strong>Update</strong> the trek.</p>';
		} else {
			var doneCount = steps.length - pending.length;
			html += '<h4>' + esc(city.city_name) + ' was added. Finish setting it up:</h4>'
				+ '<p>Customers book through the departure city, so each city needs its own dates, pricing, pickup points and itinerary.</p>'
				+ '<div class="trekpilot-next-steps-progress"><span class="trekpilot-next-steps-bar"><span style="width:' + Math.round(doneCount / steps.length * 100) + '%"></span></span><span class="trekpilot-next-steps-count">' + doneCount + ' of ' + steps.length + ' done</span></div>'
				+ '<ol class="trekpilot-next-steps-list">';
			steps.forEach(function(st) {
				html += '<li class="' + (st.done ? 'done' : '') + '">'
					+ '<span class="trekpilot-step-mark">' + (st.done ? '&#10003;' : '&#9675;') + '</span>'
					+ '<span class="trekpilot-step-body"><strong>' + st.title + '</strong>'
					+ (st.required ? ' <em class="trekpilot-step-req">required</em>' : ' <em class="trekpilot-step-opt">recommended</em>')
					+ '<span class="trekpilot-step-text">' + st.text + '</span></span>'
					+ (st.done ? '<span class="trekpilot-step-done">Done</span>' : '<button type="button" class="button button-primary button-small" data-step="' + st.key + '">' + st.button + '</button>')
					+ '</li>';
			});
			html += '</ol>';
		}
		nextStepsPanel.innerHTML = html;
		nextStepsPanel.style.display = 'block';
	}

	if (nextStepsPanel) {
		nextStepsPanel.addEventListener('click', function(e) {
			if (e.target.classList.contains('trekpilot-next-steps-close')) {
				nextStepsCityId = 0;
				renderNextSteps();
				return;
			}
			var key = e.target.getAttribute && e.target.getAttribute('data-step');
			if (!key || !nextStepsCityId) return;
			var link = tbody.querySelector('.trekpilot-action-link.' + key + '[data-id="' + nextStepsCityId + '"]:not(.trekpilot-setup-chip)');
			if (link) link.click();
		});
	}

	/* Refresh the chips and the guide whenever a dates / pricing / pickups / itinerary dialog closes. */
	['trekpilot_dates_modal', 'trekpilot_pricing_modal', 'trekpilot_pickups_modal', 'trekpilot_itinerary_modal'].forEach(function(id) {
		var modal = document.getElementById(id);
		if (!modal || typeof MutationObserver === 'undefined') return;
		var wasOpen = false;
		new MutationObserver(function() {
			var open = modal.style.display !== 'none' && modal.style.display !== '';
			if (wasOpen && !open) { fetchCities(); }
			wasOpen = open;
		}).observe(modal, { attributes: true, attributeFilter: ['style'] });
	});

	function renderCities() {
		if (!citiesList.length) {
			tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px;color:#666;">No departure cities configured. Click the button above to add one.</td></tr>';
			return;
		}
		tbody.innerHTML = '';
		citiesList.forEach(function(city, index) {
			var tr = document.createElement('tr');
			tr.className = 'trekpilot-city-row';
			tr.setAttribute('draggable','true');
			tr.setAttribute('data-id', city.id);
			tr.setAttribute('data-index', index);
			tr.innerHTML = '<td class="trekpilot-row-drag-handle" style="vertical-align:middle;">&#9776;</td>'
				+ '<td style="font-weight:600;vertical-align:middle;">' + esc(city.city_name) + setupChips(city) + '</td>'
				+ '<td style="vertical-align:middle;">' + parseInt(city.base_price || 0) + '</td>'
				+ '<td style="vertical-align:middle;">' + (city.offer_price ? parseInt(city.offer_price) : '0') + '</td>'
				+ '<td style="vertical-align:middle;">' + esc(city.transport_type || '-') + '</td>'
				+ '<td style="vertical-align:middle;">' + esc(city.booking_deadline) + ' Days</td>'
				+ '<td style="vertical-align:middle;"><span class="trekpilot-status-badge ' + (city.status === 'active' ? 'active' : 'inactive') + '">' + esc(city.status) + '</span></td>'
				+ '<td style="text-align:right;vertical-align:middle;">'
				+ '<div style="display:flex;flex-wrap:wrap;justify-content:flex-end;gap:4px;margin-bottom:4px;">'
				+ '<a href="#" class="trekpilot-action-link dates button button-small" data-id="' + city.id + '" data-name="' + esc(city.city_name) + '" style="color:#2271b1;border-color:#2271b1;">Dates</a>'
				+ '<a href="#" class="trekpilot-action-link itinerary button button-small" data-id="' + city.id + '" data-name="' + esc(city.city_name) + '" style="color:#137a7f;border-color:#137a7f;">Itinerary</a>'
				+ '<a href="#" class="trekpilot-action-link pricing button button-small" data-id="' + city.id + '" data-name="' + esc(city.city_name) + '" style="color:#c65911;border-color:#c65911;">Pricing</a>'
				+ '<a href="#" class="trekpilot-action-link pickups button button-small" data-id="' + city.id + '" data-name="' + esc(city.city_name) + '" style="color:#8f22b1;border-color:#8f22b1;">Pickups</a>'
				+ '</div>'
				+ '<div style="display:flex;justify-content:flex-end;gap:8px;font-size:12px;">'
				+ '<a href="#" class="trekpilot-action-link edit" data-id="' + city.id + '" style="color:#2271b1;">Edit</a>'
				+ '<a href="#" class="trekpilot-action-link duplicate" data-id="' + city.id + '" style="color:#646970;">Duplicate</a>'
				+ '<a href="#" class="trekpilot-action-link delete" data-id="' + city.id + '" style="color:#b32d2e;">Delete</a>'
				+ '</div>'
				+ '</td>';
			tbody.appendChild(tr);
		});
		initDragAndDrop();
		renderNextSteps();
	}

	/* ---- Add city button ---- */
	var addBtn = g('trekpilot_add_city_btn');
	if (addBtn) {
		addBtn.addEventListener('click', function(e) {
			e.preventDefault();
			var t = g('trekpilot_modal_title');
			if (t) t.textContent = 'Add Departure City';
			clearCityModalInputs();
			openCityModal();
		});
	}

	function clearCityModalInputs() {
		var f = g('trekpilot_city_form');
		if (!f) return;
		var inputs = f.querySelectorAll('input, select');
		inputs.forEach(function(i) {
			if (i.type === 'number') i.value = (i.name === 'booking_deadline' ? '3' : '0');
			else if (i.tagName === 'SELECT') i.value = 'active';
			else if (i._flatpickr) i._flatpickr.clear();
			else i.value = '';
		});
	}

	// Also update closeCityModal which is defined earlier
	function closeCityModal() {
		var m = g('trekpilot_city_modal');
		if (m) m.style.display = 'none';
		clearCityModalInputs();
	}

	/* ---- Close buttons ---- */
	var cancelBtn = g('trekpilot_modal_cancel_btn');
	var closeBtn  = g('trekpilot_modal_close_btn');
	if (cancelBtn) cancelBtn.addEventListener('click', closeCityModal);
	if (closeBtn)  closeBtn.addEventListener('click', closeCityModal);

	/* ---- Save city button ---- */
	var saveBtn = g('trekpilot_modal_save_btn');
	if (saveBtn) {
		saveBtn.addEventListener('click', function(e) {
			e.preventDefault();
			var f = g('trekpilot_city_form');
			if (!f) return;

			// Manual validation for required fields
			var nameFld = g('trekpilot_form_city_name');
			var basePriceFld = g('trekpilot_form_base_price');
			var offerPriceFld = g('trekpilot_form_offer_price');
			var deadlineFld = g('trekpilot_form_booking_deadline');

			if (!nameFld.value.trim()) { trekpilot_admin_toast('City Name is required'); nameFld.focus(); return; }
			
			var bp = parseFloat(basePriceFld.value) || 0;
			var op = offerPriceFld.value.trim() !== '' ? parseFloat(offerPriceFld.value) : 0;

			if (bp <= 0) {
				trekpilot_admin_toast('Base Price must be greater than 0'); basePriceFld.focus(); return;
			}
			if (op > 0 && op >= bp) {
				trekpilot_admin_toast('Offer Price must be less than Base Price'); offerPriceFld.focus(); return;
			}

			var ddl = parseInt(deadlineFld.value) || 0;
			if (ddl < 0) {
				trekpilot_admin_toast('Booking Deadline cannot be negative'); deadlineFld.focus(); return;
			}
			var fd = new FormData();
			fd.append('action', 'trekpilot_save_departure_city');
			fd.append('trek_id', trekId);
			fd.append('nonce', nonce);

			// Map inputs from the div manually
			var inputs = f.querySelectorAll('input, select');
			inputs.forEach(function(i) {
				if (i.name) {
					// Map city_id to id for the PHP handler
					if (i.name === 'city_id') {
						if (i.value) fd.append('id', i.value);
					} else {
						fd.append(i.name, i.value);
					}
				}
			});

			loading.style.display = 'block';
			closeCityModal();
			fetch(ajaxUrl, { method:'POST', body:fd })
				.then(function(r){ return r.json(); })
				.then(function(data){
					if (data.success) {
						// A newly added city (no id was sent) starts the "next steps" guide.
						if (!fd.get('id') && data.data && data.data.id) { nextStepsCityId = parseInt(data.data.id); }
						fetchCities();
					}
					else { trekpilot_admin_toast('Error: ' + data.data.message); loading.style.display='none'; openCityModal(); }
				})
				.catch(function(){ trekpilot_admin_toast('Network error.'); loading.style.display='none'; openCityModal(); });
		});
	}

	/* ---- Row action delegation ---- */
	tbody.addEventListener('click', function(e) {
		if (!e.target.classList.contains('trekpilot-action-link')) return;
		e.preventDefault();
		var actionId = parseInt(e.target.getAttribute('data-id'));
		var city = citiesList.find(function(c){ return parseInt(c.id) === actionId; });
		var fld = function(id){ return document.getElementById(id); };

		if (e.target.classList.contains('edit')) {
			if (!city) return;
			var mt = g('trekpilot_modal_title');
			if (mt) mt.textContent = 'Edit Departure City';
			if (fld('trekpilot_form_city_id'))          fld('trekpilot_form_city_id').value = city.id;
			if (fld('trekpilot_form_city_name'))         fld('trekpilot_form_city_name').value = city.city_name;
			if (fld('trekpilot_form_base_price'))        fld('trekpilot_form_base_price').value = city.base_price;
			if (fld('trekpilot_form_offer_price'))       fld('trekpilot_form_offer_price').value = city.offer_price;
			if (fld('trekpilot_form_transport_type'))    fld('trekpilot_form_transport_type').value = city.transport_type;
			if (fld('trekpilot_form_reporting_time')) {
				if (fld('trekpilot_form_reporting_time')._flatpickr) fld('trekpilot_form_reporting_time')._flatpickr.setDate(city.reporting_time || '');
				else fld('trekpilot_form_reporting_time').value = city.reporting_time || '';
			}
			if (fld('trekpilot_form_google_map_link'))   fld('trekpilot_form_google_map_link').value = city.google_map_link;
			if (fld('trekpilot_form_booking_deadline'))  fld('trekpilot_form_booking_deadline').value = city.booking_deadline;
			if (fld('trekpilot_form_status'))            fld('trekpilot_form_status').value = city.status;
			openCityModal();

		} else if (e.target.classList.contains('duplicate')) {
			trekpilot_admin_confirm('Duplicate this city with all its prices, dates & itineraries?', function() {
				loading.style.display = 'block';
				var fd2 = new FormData();
				fd2.append('action','trekpilot_duplicate_departure_city');
				fd2.append('id', actionId);
				fd2.append('nonce', nonce);
				fetch(ajaxUrl, {method:'POST',body:fd2}).then(function(r){return r.json();}).then(function(data){
					if (data.success) fetchCities();
					else { trekpilot_admin_toast('Duplication failed: '+data.data.message); loading.style.display='none'; }
				});
			});
		} else if (e.target.classList.contains('delete')) {
			trekpilot_admin_confirm('WARNING: This will delete the city and all associated dates, itineraries, pickups and pricing. Proceed?', function() {
				loading.style.display = 'block';
				var fd3 = new FormData();
				fd3.append('action','trekpilot_delete_departure_city');
				fd3.append('id', actionId);
				fd3.append('nonce', nonce);
				fetch(ajaxUrl, {method:'POST',body:fd3}).then(function(r){return r.json();}).then(function(data){
					if (data.success) fetchCities();
					else { trekpilot_admin_toast('Deletion failed: '+data.data.message); loading.style.display='none'; }
				});
			});
		}
	});

	/* ---- Drag & Drop ---- */
	var dragEl = null;

	function initDragAndDrop() {
		tbody.querySelectorAll('.trekpilot-city-row').forEach(function(row) {
			row.addEventListener('dragstart', onDragStart);
			row.addEventListener('dragover',  onDragOver);
			row.addEventListener('drop',      onDrop);
			row.addEventListener('dragend',   onDragEnd);
		});
	}
	function onDragStart(e) { dragEl=this; this.style.opacity='0.4'; e.dataTransfer.effectAllowed='move'; e.dataTransfer.setData('text/html',this.innerHTML); }
	function onDragOver(e)  { e.preventDefault(); e.dataTransfer.dropEffect='move'; if (this!==dragEl){var r=this.getBoundingClientRect(); tbody.insertBefore(dragEl, e.clientY<r.top+r.height/2?this:this.nextSibling);} }
	function onDrop(e)      { e.stopPropagation(); }
	function onDragEnd()    { this.style.opacity='1'; saveRowOrder(); }

	function saveRowOrder() {
		var order=[]; tbody.querySelectorAll('.trekpilot-city-row').forEach(function(r){order.push(r.getAttribute('data-id'));});
		var fd=new FormData(); fd.append('action','trekpilot_reorder_departure_cities'); order.forEach(function(id){fd.append('order[]',id);}); fd.append('nonce',nonce);
		fetch(ajaxUrl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(data){if(!data.success)trekpilot_admin_toast('Sorting failed: '+data.data.message);});
	}

	/* ---- Kick off ---- */
	fetchCities();

	/* ---- Initialize Timepickers ---- */
	if (typeof flatpickr !== 'undefined') {
		flatpickr('.trekpilot-timepicker', {
			enableTime: true,
			noCalendar: true,
			dateFormat: "h:i K",
			minuteIncrement: 5,
		});
	}

});
