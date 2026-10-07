/**
 * JavaScript for managing Pickup Points Admin UI (AJAX & drag-and-drop)
 */

document.addEventListener('DOMContentLoaded', function() {
	var root = document.getElementById('trekpilot_departures_module_root');
	if (!root) return;

	var ajaxUrl = trekpilot_departures_obj.ajax_url;
	var nonce   = trekpilot_departures_obj.nonce;

	var modal        = document.getElementById('trekpilot_pickups_modal');
	var title        = document.getElementById('trekpilot_pickups_modal_city_title');
	var closeBtn     = document.getElementById('trekpilot_pickups_modal_close_btn');
	var loading      = document.getElementById('trekpilot_pickups_loading');
	var tbody        = document.getElementById('trekpilot_pickups_tbody');
	var addBtn       = document.getElementById('trekpilot_add_pickup_btn');

	var formContainer= document.getElementById('trekpilot_pickup_form_container');
	var formTitle    = document.getElementById('trekpilot_pickup_form_title');
	var saveBtn      = document.getElementById('trekpilot_pickup_form_save_btn');
	var cancelBtn    = document.getElementById('trekpilot_pickup_form_cancel_btn');

	var currentCityId = 0;
	var pickupsList   = [];

	/* ---- helpers ---- */
	function g(id) { return document.getElementById(id); }

	function clearForm() {
		g('trekpilot_pickup_id').value = '';
		g('trekpilot_pickup_location_name').value = '';
		
		if (g('trekpilot_pickup_time')._flatpickr) g('trekpilot_pickup_time')._flatpickr.clear();
		else g('trekpilot_pickup_time').value = '';
		
		g('trekpilot_pickup_map_url').value = '';
		g('trekpilot_pickup_instructions').value = '';
	}

	function hideForm() {
		formContainer.style.display = 'none';
		clearForm();
	}

	function showForm(isEdit) {
		formContainer.style.display = 'block';
		formTitle.textContent = isEdit ? 'Edit Pickup Point' : 'Add Pickup Point';
		g('trekpilot_pickup_location_name').focus();
	}

	function closeModal() {
		modal.style.display = 'none';
		currentCityId = 0;
		hideForm();
	}

	if (closeBtn) closeBtn.addEventListener('click', closeModal);
	if (cancelBtn) cancelBtn.addEventListener('click', hideForm);

	/* ---- open modal hook ---- */
	document.getElementById('trekpilot_cities_tbody').addEventListener('click', function(e) {
		if (!e.target.classList.contains('pickups')) return;
		e.preventDefault();
		currentCityId = parseInt(e.target.getAttribute('data-id'));
		title.textContent = e.target.getAttribute('data-name');
		modal.style.display = 'flex';
		hideForm();
		fetchPickups();
	});

	/* ---- fetch & render ---- */
	function fetchPickups() {
		loading.style.display = 'block';
		tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#666;">Loading...</td></tr>';
		fetch(ajaxUrl + '?action=trekpilot_get_pickups&city_id=' + currentCityId + '&nonce=' + nonce)
			.then(function(r) { return r.json(); })
			.then(function(data) {
				loading.style.display = 'none';
				if (data.success) {
					pickupsList = data.data;
					renderPickups();
				} else {
					tbody.innerHTML = '<tr><td colspan="5" style="color:#b32d2e;text-align:center;">Error: ' + esc(data.data.message) + '</td></tr>';
				}
			})
			.catch(function() {
				loading.style.display = 'none';
				tbody.innerHTML = '<tr><td colspan="5" style="color:#b32d2e;text-align:center;">Network error.</td></tr>';
			});
	}

	function esc(text) {
		return String(text == null ? '' : text).replace(/[&<>"']/g, function(ch) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
		});
	}

	function renderPickups() {
		if (!pickupsList.length) {
			tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;color:#666;">No pickup points configured. Click Add Pickup Point.</td></tr>';
			return;
		}
		tbody.innerHTML = '';
		pickupsList.forEach(function(pick, index) {
			var tr = document.createElement('tr');
			tr.className = 'trekpilot-pickup-row';
			tr.setAttribute('draggable', 'true');
			tr.setAttribute('data-id', pick.id);
			tr.innerHTML = '<td class="trekpilot-row-drag-handle" style="vertical-align:middle; cursor:move;">&#9776;</td>'
				+ '<td style="vertical-align:middle; font-weight:600;">' + esc(pick.location_name) + '</td>'
				+ '<td style="vertical-align:middle;">' + esc(pick.pickup_time || '-') + '</td>'
				+ '<td style="vertical-align:middle;">' + (pick.google_maps_url ? '<a href="' + esc(pick.google_maps_url) + '" target="_blank" rel="noopener noreferrer">View Map</a>' : '-') + '</td>'
				+ '<td style="text-align:right;vertical-align:middle;">'
				+ '<a href="#" class="trekpilot-pickup-edit button button-small" data-id="' + pick.id + '">Edit</a> '
				+ '<a href="#" class="trekpilot-pickup-delete button button-small" data-id="' + pick.id + '" style="color:#b32d2e;border-color:#b32d2e;">Delete</a>'
				+ '</td>';
			tbody.appendChild(tr);
		});
		initDragAndDrop();
	}

	/* ---- actions ---- */
	addBtn.addEventListener('click', function() {
		clearForm();
		showForm(false);
	});

	tbody.addEventListener('click', function(e) {
		if (e.target.classList.contains('trekpilot-pickup-edit')) {
			e.preventDefault();
			var id = parseInt(e.target.getAttribute('data-id'));
			var pick = pickupsList.find(function(p) { return parseInt(p.id) === id; });
			if (pick) {
				g('trekpilot_pickup_id').value = pick.id;
				g('trekpilot_pickup_location_name').value = pick.location_name;
				if (g('trekpilot_pickup_time')._flatpickr) {
					g('trekpilot_pickup_time')._flatpickr.setDate(pick.pickup_time || '');
				} else {
					g('trekpilot_pickup_time').value = pick.pickup_time || '';
				}
				g('trekpilot_pickup_map_url').value = pick.google_maps_url;
				g('trekpilot_pickup_instructions').value = pick.instructions;
				showForm(true);
			}
		} else if (e.target.classList.contains('trekpilot-pickup-delete')) {
			e.preventDefault();
			var id = parseInt(e.target.getAttribute('data-id'));
			trekpilot_admin_confirm('Are you sure you want to delete this pickup point?', function() {
				loading.style.display = 'block';
				var fd = new FormData();
				fd.append('action', 'trekpilot_delete_pickup');
				fd.append('id', id);
				fd.append('nonce', nonce);
				fetch(ajaxUrl, { method: 'POST', body: fd }).then(function(r){return r.json();}).then(function(data){
					if (data.success) fetchPickups();
					else { trekpilot_admin_toast('Failed to delete: ' + data.data.message); loading.style.display = 'none'; }
				});
			});
		}
	});

	saveBtn.addEventListener('click', function() {
		var locName = g('trekpilot_pickup_location_name').value.trim();
		if (!locName) {
			trekpilot_admin_toast('Location Name is required.');
			g('trekpilot_pickup_location_name').focus();
			return;
		}

		loading.style.display = 'block';
		var fd = new FormData();
		fd.append('action', 'trekpilot_save_pickup');
		fd.append('nonce', nonce);
		fd.append('city_id', currentCityId);
		fd.append('id', g('trekpilot_pickup_id').value);
		fd.append('location_name', locName);
		fd.append('pickup_time', g('trekpilot_pickup_time').value);
		fd.append('google_maps_url', g('trekpilot_pickup_map_url').value);
		fd.append('instructions', g('trekpilot_pickup_instructions').value);

		fetch(ajaxUrl, { method: 'POST', body: fd })
			.then(function(r){ return r.json(); })
			.then(function(data){
				if (data.success) {
					hideForm();
					fetchPickups();
				} else {
					trekpilot_admin_toast('Error: ' + data.data.message);
					loading.style.display = 'none';
				}
			})
			.catch(function(){
				trekpilot_admin_toast('Network error.');
				loading.style.display = 'none';
			});
	});

	/* ---- Drag & Drop ---- */
	var dragEl = null;

	function initDragAndDrop() {
		tbody.querySelectorAll('.trekpilot-pickup-row').forEach(function(row) {
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
		var order=[]; tbody.querySelectorAll('.trekpilot-pickup-row').forEach(function(r){order.push(r.getAttribute('data-id'));});
		var fd=new FormData(); fd.append('action','trekpilot_reorder_pickups'); order.forEach(function(id){fd.append('order[]',id);}); fd.append('nonce',nonce);
		fetch(ajaxUrl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(data){if(!data.success)trekpilot_admin_toast('Sorting failed: '+data.data.message);});
	}

});
