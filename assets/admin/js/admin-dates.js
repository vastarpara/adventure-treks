/**
 * JavaScript for managing Departure Dates (AJAX & Date Form Modals)
 */

document.addEventListener('DOMContentLoaded', function() {

	// Make sure the departures table and root exist
	const tbodyCities = document.getElementById('at_cities_tbody');
	const root = document.getElementById('at_departures_module_root');
	if (!tbodyCities || !root) return;

	const trekId = root.getAttribute('data-trek-id');
	const ajaxUrl = at_dates_obj.ajax_url;
	const nonce = at_dates_obj.nonce;

	// Dates modal elements
	const datesModal = document.getElementById('at_dates_modal');
	const datesCityTitle = document.getElementById('at_dates_modal_city_title');
	const datesTbody = document.getElementById('at_dates_tbody');
	const datesLoading = document.getElementById('at_dates_loading');
	const datesModalCloseBtn = document.getElementById('at_dates_modal_close_btn');
	const datesModalBackBtn = document.getElementById('at_dates_modal_back_btn');

	// Date form modal elements
	const dateFormModal = document.getElementById('at_date_form_modal');
	const dateFormTitle = document.getElementById('at_date_form_title');
	const dateForm = document.getElementById('at_date_form');
	const addDateBtn = document.getElementById('at_add_date_btn');
	const saveDateBtn = document.getElementById('at_date_form_save_btn');
	const cancelDateBtn = document.getElementById('at_date_form_cancel_btn');
	const closeDateBtn = document.getElementById('at_date_form_close_btn');

	let activeCityId = null;
	let datesList = [];

	// ==========================================
	// 1. Event Delegation for "Dates" click on City row
	// ==========================================
	tbodyCities.addEventListener('click', function(e) {
		if (!e.target.classList.contains('dates')) return;
		e.preventDefault();

		activeCityId = parseInt(e.target.getAttribute('data-id'));
		const cityName = e.target.getAttribute('data-name');

		datesCityTitle.textContent = cityName;
		datesModal.style.display = 'flex';

		fetchDates();
	});

	// Close dates list modal
	function closeDatesModal() {
		datesModal.style.display = 'none';
		datesTbody.innerHTML = '';
		activeCityId = null;
	}

	datesModalCloseBtn.addEventListener('click', closeDatesModal);
	datesModalBackBtn.addEventListener('click', closeDatesModal);

	// ==========================================
	// 2. Fetch and Render Dates
	// ==========================================
	function fetchDates() {
		if (!activeCityId) return;

		datesLoading.style.display = 'block';
		datesTbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:15px; color:#666;">Loading dates...</td></tr>';

		const url = `${ajaxUrl}?action=at_get_departure_dates&city_id=${activeCityId}&nonce=${nonce}`;

		fetch(url)
			.then(res => res.json())
			.then(data => {
				datesLoading.style.display = 'none';
				if (data.success) {
					datesList = data.data;
					renderDates();
				} else {
					datesTbody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:#b32d2e;">Error: ${data.data.message}</td></tr>`;
				}
			})
			.catch(err => {
				datesLoading.style.display = 'none';
				datesTbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:#b32d2e;">Network error loading dates.</td></tr>';
			});
	}

	function renderDates() {
		if (datesList.length === 0) {
			datesTbody.innerHTML = `
				<tr>
					<td colspan="6" style="text-align:center; padding:20px; color:#666;">
						No departure dates scheduled for this city yet. Click "Add Departure Date" to create one.
					</td>
				</tr>
			`;
			return;
		}

		datesTbody.innerHTML = '';
		datesList.forEach(date => {
			const tr = document.createElement('tr');
			tr.setAttribute('data-id', date.id);

			// Format seats status string
			let seatStatusHTML = `<span style="font-weight:600;">${date.total_seats}</span> / <span>${date.booked_seats}</span> / <span style="color:${date.available_seats <= 3 ? '#b32d2e' : '#385723'}; font-weight:bold;">${date.available_seats}</span>`;

			// Price overrides rendering
			let priceOverridesText = 'None (City default)';
			if (parseFloat(date.adult_price) > 0 || parseFloat(date.offer_price) > 0) {
				priceOverridesText = `Adult: ${parseFloat(date.adult_price).toFixed(2)}`;
				if (parseFloat(date.offer_price) > 0) {
					priceOverridesText += ` (Offer: ${parseFloat(date.offer_price).toFixed(2)})`;
				}
			}

			// Format human readable date
			const jsDate = new Date(date.departure_date);
			const formattedDate = jsDate.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });

			tr.innerHTML = `
				<td style="font-weight:600; vertical-align:middle;">${formattedDate}</td>
				<td style="vertical-align:middle;">
					<span class="at-status-badge ${getStatusClass(date.status)}">
						${date.status.replace('_', ' ')}
					</span>
				</td>
				<td style="vertical-align:middle;">${seatStatusHTML}</td>
				<td style="font-size:11px; vertical-align:middle;">${priceOverridesText}</td>
				<td style="font-style:italic; font-size:11px; vertical-align:middle;">${date.notes || '-'}</td>
				<td style="text-align:right; vertical-align:middle;">
					<a href="#" class="at-action-link edit-date" data-id="${date.id}">Edit</a>
					<a href="#" class="at-action-link delete-date" data-id="${date.id}">Delete</a>
				</td>
			`;

			datesTbody.appendChild(tr);
		});
	}

	function getStatusClass(status) {
		switch (status) {
			case 'open': return 'active';
			case 'few_seats': return 'active'; // can style differently if needed
			case 'sold_out': return 'inactive';
			case 'cancelled': return 'inactive';
			default: return 'active';
		}
	}

	// ==========================================
	// 3. Date Add / Edit Form Actions
	// ==========================================
	function clearDateForm() {
		const f = document.getElementById('at_date_form');
		if (!f) return;
		const inputs = f.querySelectorAll('input, select');
		inputs.forEach(i => {
			if (i.type === 'number') {
				if (i.name === 'total_seats') i.value = '30';
				else i.value = '0.00';
			} else if (i.tagName === 'SELECT') {
				i.value = '';
			} else {
				i.value = '';
			}
		});
	}

	addDateBtn.addEventListener('click', function(e) {
		e.preventDefault();
		dateFormTitle.textContent = 'Add Departure Date';
		clearDateForm();
		document.getElementById('at_form_date_id').value = '';
		document.getElementById('at_form_date_city_id').value = activeCityId;
		dateFormModal.style.display = 'flex';
	});

	function closeDateFormModal() {
		dateFormModal.style.display = 'none';
		clearDateForm();
	}

	cancelDateBtn.addEventListener('click', closeDateFormModal);
	closeDateBtn.addEventListener('click', closeDateFormModal);

	saveDateBtn.addEventListener('click', function(e) {
		e.preventDefault();
		const f = document.getElementById('at_date_form');
		if (!f) return;

		// Manual Validation
		const dateFld = document.getElementById('at_form_departure_date');
		if (!dateFld.value.trim()) {
			alert('Departure Date is required');
			dateFld.focus();
			return;
		}

		const formData = new FormData();
		formData.append('action', 'at_save_departure_date');
		formData.append('trek_id', trekId);
		formData.append('city_id', activeCityId);
		formData.append('nonce', nonce);

		// Manually append form data
		const inputs = f.querySelectorAll('input, select');
		inputs.forEach(i => {
			if (i.name) {
				if (i.name === 'date_id') {
					if (i.value) formData.append('id', i.value);
				} else {
					formData.append(i.name, i.value);
				}
			}
		});

		datesLoading.style.display = 'block';
		dateFormModal.style.display = 'none';

		fetch(ajaxUrl, {
			method: 'POST',
			body: formData
		})
			.then(res => res.json())
			.then(data => {
				if (data.success) {
					fetchDates();
				} else {
					alert('Error: ' + data.data.message);
					datesLoading.style.display = 'none';
					dateFormModal.style.display = 'flex';
				}
			})
			.catch(err => {
				alert('Network error while saving date.');
				datesLoading.style.display = 'none';
				dateFormModal.style.display = 'flex';
			});
	});

	// Delegate Date Row Actions (Edit / Delete)
	datesTbody.addEventListener('click', function(e) {
		if (!e.target.classList.contains('at-action-link')) return;
		e.preventDefault();

		const actionId = parseInt(e.target.getAttribute('data-id'));
		const dateObj = datesList.find(d => parseInt(d.id) === actionId);

		if (e.target.classList.contains('edit-date')) {
			if (!dateObj) return;

			dateFormTitle.textContent = 'Edit Departure Date';
			document.getElementById('at_form_date_id').value = dateObj.id;
			document.getElementById('at_form_date_city_id').value = dateObj.city_id;
			document.getElementById('at_form_departure_date').value = dateObj.departure_date;
			document.getElementById('at_form_date_total_seats').value = dateObj.total_seats;
			document.getElementById('at_form_date_booked_seats').value = dateObj.booked_seats;
			document.getElementById('at_form_date_status').value = dateObj.status;
			document.getElementById('at_form_date_notes').value = dateObj.notes;

			// Pricing
			document.getElementById('at_form_date_adult_price').value = parseFloat(dateObj.adult_price).toFixed(2);
			document.getElementById('at_form_date_child_price').value = parseFloat(dateObj.child_price).toFixed(2);
			document.getElementById('at_form_date_offer_price').value = parseFloat(dateObj.offer_price).toFixed(2);

			dateFormModal.style.display = 'flex';
		} else if (e.target.classList.contains('delete-date')) {
			if (confirm('Are you sure you want to delete this scheduled date? This action cannot be undone.')) {
				datesLoading.style.display = 'block';

				const fd = new FormData();
				fd.append('action', 'at_delete_departure_date');
				fd.append('id', actionId);
				fd.append('nonce', nonce);

				fetch(ajaxUrl, { method: 'POST', body: fd })
					.then(res => res.json())
					.then(data => {
						if (data.success) {
							fetchDates();
						} else {
							alert('Deletion failed: ' + data.data.message);
							datesLoading.style.display = 'none';
						}
					});
			}
		}
	});

});
