/**
 * JavaScript for managing Departure Cities Admin UI (AJAX & drag-and-drop)
 */

document.addEventListener('DOMContentLoaded', function() {

	var root = document.getElementById('at_departures_module_root');
	if (!root) return;

	// In the block editor the meta box lives in a scrolling, clipped container, which breaks
	// position:fixed overlays (modal cut off / offset). Re-parent every modal to <body> so they
	// always cover the whole screen. Element identity is kept, so existing lookups still work.
	root.querySelectorAll('.at-modal-overlay').forEach(function(modal) {
		document.body.appendChild(modal);
	});


	var trekId  = root.getAttribute('data-trek-id');
	var ajaxUrl = at_departures_obj.ajax_url;
	var nonce   = at_departures_obj.nonce;

	var tbody   = document.getElementById('at_cities_tbody');
	var loading = document.getElementById('at_cities_loading');
	if (!tbody || !loading) return;

	/* Lazy lookup with console logging */
	function g(id) {
		var node = document.getElementById(id);
		if (!node) { console.error('Adventure Treks: #' + id + ' not found.'); }
		return node;
	}

	var citiesList = [];

	/* ---- helpers ---- */
	function openCityModal() {
		var m = g('at_city_modal');
		if (m) m.style.display = 'flex';
	}


	/* ---- fetch & render ---- */
	function fetchCities() {
		loading.style.display = 'block';
		tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px;color:#666;">Loading...</td></tr>';
		fetch(ajaxUrl + '?action=at_get_departure_cities&trek_id=' + trekId + '&nonce=' + nonce)
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

	function renderCities() {
		if (!citiesList.length) {
			tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;padding:20px;color:#666;">No departure cities configured. Click the button above to add one.</td></tr>';
			return;
		}
		tbody.innerHTML = '';
		citiesList.forEach(function(city, index) {
			var tr = document.createElement('tr');
			tr.className = 'at-city-row';
			tr.setAttribute('draggable','true');
			tr.setAttribute('data-id', city.id);
			tr.setAttribute('data-index', index);
			tr.innerHTML = '<td class="at-row-drag-handle" style="vertical-align:middle;">&#9776;</td>'
				+ '<td style="font-weight:600;vertical-align:middle;">' + city.city_name + '</td>'
				+ '<td style="vertical-align:middle;">' + parseInt(city.base_price || 0) + '</td>'
				+ '<td style="vertical-align:middle;">' + (city.offer_price ? parseInt(city.offer_price) : '0') + '</td>'
				+ '<td style="vertical-align:middle;">' + (city.transport_type || '-') + '</td>'
				+ '<td style="vertical-align:middle;">' + city.booking_deadline + ' Days</td>'
				+ '<td style="vertical-align:middle;"><span class="at-status-badge ' + (city.status === 'active' ? 'active' : 'inactive') + '">' + city.status + '</span></td>'
				+ '<td style="text-align:right;vertical-align:middle;white-space:nowrap;">'
				+ '<div style="display:flex;justify-content:flex-end;gap:4px;margin-bottom:4px;">'
				+ '<a href="#" class="at-action-link dates button button-small" data-id="' + city.id + '" data-name="' + city.city_name + '" style="color:#2271b1;border-color:#2271b1;">Dates</a>'
				+ '<a href="#" class="at-action-link itinerary button button-small" data-id="' + city.id + '" data-name="' + city.city_name + '" style="color:#137a7f;border-color:#137a7f;">Itinerary</a>'
				+ '<a href="#" class="at-action-link pricing button button-small" data-id="' + city.id + '" data-name="' + city.city_name + '" style="color:#c65911;border-color:#c65911;">Pricing</a>'
				+ '<a href="#" class="at-action-link pickups button button-small" data-id="' + city.id + '" data-name="' + city.city_name + '" style="color:#8f22b1;border-color:#8f22b1;">Pickups</a>'
				+ '</div>'
				+ '<div style="display:flex;justify-content:flex-end;gap:8px;font-size:12px;">'
				+ '<a href="#" class="at-action-link edit" data-id="' + city.id + '" style="color:#2271b1;">Edit</a>'
				+ '<a href="#" class="at-action-link duplicate" data-id="' + city.id + '" style="color:#646970;">Duplicate</a>'
				+ '<a href="#" class="at-action-link delete" data-id="' + city.id + '" style="color:#b32d2e;">Delete</a>'
				+ '</div>'
				+ '</td>';
			tbody.appendChild(tr);
		});
		initDragAndDrop();
	}

	/* ---- Add city button ---- */
	var addBtn = g('at_add_city_btn');
	if (addBtn) {
		addBtn.addEventListener('click', function(e) {
			e.preventDefault();
			var t = g('at_modal_title');
			if (t) t.textContent = 'Add Departure City';
			clearCityModalInputs();
			openCityModal();
		});
	}

	function clearCityModalInputs() {
		var f = g('at_city_form');
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
		var m = g('at_city_modal');
		if (m) m.style.display = 'none';
		clearCityModalInputs();
	}

	/* ---- Close buttons ---- */
	var cancelBtn = g('at_modal_cancel_btn');
	var closeBtn  = g('at_modal_close_btn');
	if (cancelBtn) cancelBtn.addEventListener('click', closeCityModal);
	if (closeBtn)  closeBtn.addEventListener('click', closeCityModal);

	/* ---- Save city button ---- */
	var saveBtn = g('at_modal_save_btn');
	if (saveBtn) {
		saveBtn.addEventListener('click', function(e) {
			e.preventDefault();
			var f = g('at_city_form');
			if (!f) return;

			// Manual validation for required fields
			var nameFld = g('at_form_city_name');
			var basePriceFld = g('at_form_base_price');
			var offerPriceFld = g('at_form_offer_price');
			var deadlineFld = g('at_form_booking_deadline');

			if (!nameFld.value.trim()) { at_admin_toast('City Name is required'); nameFld.focus(); return; }
			
			var bp = parseFloat(basePriceFld.value) || 0;
			var op = offerPriceFld.value.trim() !== '' ? parseFloat(offerPriceFld.value) : 0;

			if (bp <= 0) {
				at_admin_toast('Base Price must be greater than 0'); basePriceFld.focus(); return;
			}
			if (op > 0 && op >= bp) {
				at_admin_toast('Offer Price must be less than Base Price'); offerPriceFld.focus(); return;
			}

			var ddl = parseInt(deadlineFld.value) || 0;
			if (ddl < 0) {
				at_admin_toast('Booking Deadline cannot be negative'); deadlineFld.focus(); return;
			}
			var fd = new FormData();
			fd.append('action', 'at_save_departure_city');
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
					if (data.success) { fetchCities(); }
					else { at_admin_toast('Error: ' + data.data.message); loading.style.display='none'; openCityModal(); }
				})
				.catch(function(){ at_admin_toast('Network error.'); loading.style.display='none'; openCityModal(); });
		});
	}

	/* ---- Row action delegation ---- */
	tbody.addEventListener('click', function(e) {
		if (!e.target.classList.contains('at-action-link')) return;
		e.preventDefault();
		var actionId = parseInt(e.target.getAttribute('data-id'));
		var city = citiesList.find(function(c){ return parseInt(c.id) === actionId; });
		var fld = function(id){ return document.getElementById(id); };

		if (e.target.classList.contains('edit')) {
			if (!city) return;
			var mt = g('at_modal_title');
			if (mt) mt.textContent = 'Edit Departure City';
			if (fld('at_form_city_id'))          fld('at_form_city_id').value = city.id;
			if (fld('at_form_city_name'))         fld('at_form_city_name').value = city.city_name;
			if (fld('at_form_base_price'))        fld('at_form_base_price').value = city.base_price;
			if (fld('at_form_offer_price'))       fld('at_form_offer_price').value = city.offer_price;
			if (fld('at_form_transport_type'))    fld('at_form_transport_type').value = city.transport_type;
			if (fld('at_form_reporting_time')) {
				if (fld('at_form_reporting_time')._flatpickr) fld('at_form_reporting_time')._flatpickr.setDate(city.reporting_time || '');
				else fld('at_form_reporting_time').value = city.reporting_time || '';
			}
			if (fld('at_form_google_map_link'))   fld('at_form_google_map_link').value = city.google_map_link;
			if (fld('at_form_booking_deadline'))  fld('at_form_booking_deadline').value = city.booking_deadline;
			if (fld('at_form_status'))            fld('at_form_status').value = city.status;
			openCityModal();

		} else if (e.target.classList.contains('duplicate')) {
			at_admin_confirm('Duplicate this city with all its prices, dates & itineraries?', function() {
				loading.style.display = 'block';
				var fd2 = new FormData();
				fd2.append('action','at_duplicate_departure_city');
				fd2.append('id', actionId);
				fd2.append('nonce', nonce);
				fetch(ajaxUrl, {method:'POST',body:fd2}).then(function(r){return r.json();}).then(function(data){
					if (data.success) fetchCities();
					else { at_admin_toast('Duplication failed: '+data.data.message); loading.style.display='none'; }
				});
			});
		} else if (e.target.classList.contains('delete')) {
			at_admin_confirm('WARNING: This will delete the city and all associated dates, itineraries, pickups and pricing. Proceed?', function() {
				loading.style.display = 'block';
				var fd3 = new FormData();
				fd3.append('action','at_delete_departure_city');
				fd3.append('id', actionId);
				fd3.append('nonce', nonce);
				fetch(ajaxUrl, {method:'POST',body:fd3}).then(function(r){return r.json();}).then(function(data){
					if (data.success) fetchCities();
					else { at_admin_toast('Deletion failed: '+data.data.message); loading.style.display='none'; }
				});
			});
		}
	});

	/* ---- Drag & Drop ---- */
	var dragEl = null;

	function initDragAndDrop() {
		tbody.querySelectorAll('.at-city-row').forEach(function(row) {
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
		var order=[]; tbody.querySelectorAll('.at-city-row').forEach(function(r){order.push(r.getAttribute('data-id'));});
		var fd=new FormData(); fd.append('action','at_reorder_departure_cities'); order.forEach(function(id){fd.append('order[]',id);}); fd.append('nonce',nonce);
		fetch(ajaxUrl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(data){if(!data.success)at_admin_toast('Sorting failed: '+data.data.message);});
	}

	/* ---- Kick off ---- */
	fetchCities();

	/* ---- Initialize Timepickers ---- */
	if (typeof flatpickr !== 'undefined') {
		flatpickr('.at-timepicker', {
			enableTime: true,
			noCalendar: true,
			dateFormat: "h:i K",
			minuteIncrement: 5,
		});
	}

});
