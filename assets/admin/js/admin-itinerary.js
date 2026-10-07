/**
 * JavaScript for managing Dynamic Itinerary Builder (Days & Timeline Events)
 */

document.addEventListener('DOMContentLoaded', function() {

	const tbodyCities = document.getElementById('trekpilot_cities_tbody');
	const root = document.getElementById('trekpilot_departures_module_root');
	if (!tbodyCities || !root) return;

	const trekId = root.getAttribute('data-trek-id');
	const ajaxUrl = trekpilot_itinerary_obj.ajax_url;
	const nonce = trekpilot_itinerary_obj.nonce;

	// Itinerary Modal Elements
	const itineraryModal = document.getElementById('trekpilot_itinerary_modal');
	const itineraryCityTitle = document.getElementById('trekpilot_itinerary_modal_city_title');
	const itineraryCloseBtn = document.getElementById('trekpilot_itinerary_modal_close_btn');
	const itineraryBackBtn = document.getElementById('trekpilot_itinerary_modal_back_btn');

	const daysList = document.getElementById('trekpilot_itinerary_days_list');
	const addDayBtn = document.getElementById('trekpilot_add_day_btn');

	const noDaySelectedMsg = document.getElementById('trekpilot_no_day_selected_msg');
	const dayTimelineWrapper = document.getElementById('trekpilot_day_timeline_wrapper');
	const selectedDayTitle = document.getElementById('trekpilot_selected_day_title');
	const selectedDayDesc = document.getElementById('trekpilot_selected_day_desc');
	const editSelectedDayBtn = document.getElementById('trekpilot_edit_selected_day_btn');
	const addActivityBtn = document.getElementById('trekpilot_add_activity_btn');
	const activitiesTimeline = document.getElementById('trekpilot_day_activities_timeline');

	// Day Form Modal Elements
	const dayFormModal = document.getElementById('trekpilot_day_form_modal');
	const dayFormTitle = document.getElementById('trekpilot_day_form_title');
	const dayForm = document.getElementById('trekpilot_day_form');
	const dayFormCancelBtn = document.getElementById('trekpilot_day_form_cancel_btn');
	const dayFormSaveBtn = document.getElementById('trekpilot_day_form_save_btn');
	const dayFormCloseBtn = document.getElementById('trekpilot_day_form_close_btn');

	// Activity Form Modal Elements
	const activityFormModal = document.getElementById('trekpilot_activity_form_modal');
	const activityFormTitle = document.getElementById('trekpilot_activity_form_title');
	const activityForm = document.getElementById('trekpilot_activity_form');
	const activityFormCancelBtn = document.getElementById('trekpilot_activity_form_cancel_btn');
	const activityFormSaveBtn = document.getElementById('trekpilot_activity_form_save_btn');
	const activityFormCloseBtn = document.getElementById('trekpilot_activity_form_close_btn');
	const selectActivityImgBtn = document.getElementById('trekpilot_select_activity_image_btn');
	const activityImgInput = document.getElementById('trekpilot_form_activity_image');
	const activityImgPreview = document.getElementById('trekpilot_activity_image_preview');

	// Active State
	let activeCityId = null;
	let activeItinerary = []; // Full days & items data
	let activeDayId = null; // Currently selected Day ID

	// ==========================================
	// 1. Initial Launch
	// ==========================================
	tbodyCities.addEventListener('click', function(e) {
		if (!e.target.classList.contains('itinerary')) return;
		e.preventDefault();

		activeCityId = parseInt(e.target.getAttribute('data-id'));
		const cityName = e.target.getAttribute('data-name');

		itineraryCityTitle.textContent = cityName;
		itineraryModal.style.display = 'flex';

		resetTimelineView();
		fetchItinerary();
	});

	function closeItineraryModal() {
		itineraryModal.style.display = 'none';
		activeCityId = null;
		activeItinerary = [];
		activeDayId = null;
	}

	itineraryCloseBtn.addEventListener('click', closeItineraryModal);
	itineraryBackBtn.addEventListener('click', closeItineraryModal);

	function resetTimelineView() {
		activeDayId = null;
		noDaySelectedMsg.style.display = 'flex';
		dayTimelineWrapper.style.display = 'none';
		activitiesTimeline.innerHTML = '';
	}

	// ==========================================
	// 2. Fetch and Render Itinerary Layout
	// ==========================================
	function fetchItinerary(selectDayIdAfterLoad = null) {
		if (!activeCityId) return;

		daysList.innerHTML = '<div style="text-align:center; padding:10px; color:#666;">Loading...</div>';

		const url = `${ajaxUrl}?action=trekpilot_get_itinerary&city_id=${activeCityId}&nonce=${nonce}`;

		fetch(url)
			.then(res => res.json())
			.then(data => {
				if (data.success) {
					activeItinerary = data.data;
					renderDays();

					if (selectDayIdAfterLoad) {
						selectDay(selectDayIdAfterLoad);
					} else if (activeDayId) {
						// Maintain selection if still valid
						const exists = activeItinerary.some(d => parseInt(d.id) === activeDayId);
						if (exists) {
							selectDay(activeDayId);
						} else {
							resetTimelineView();
						}
					}
				} else {
					daysList.innerHTML = '<div style="color:#b32d2e;">Failed to load.</div>';
				}
			})
			.catch(err => {
				daysList.innerHTML = '<div style="color:#b32d2e;">Network error.</div>';
			});
	}

	function renderDays() {
		if (activeItinerary.length === 0) {
			daysList.innerHTML = '<div style="text-align:center; padding:15px; color:#888; font-style:italic;">No days configured.</div>';
			return;
		}

		daysList.innerHTML = '';
		activeItinerary.forEach((day) => {
			const div = document.createElement('div');
			div.className = 'trekpilot-itinerary-day-tab';
			div.setAttribute('draggable', 'true');
			div.setAttribute('data-id', day.id);
			if (activeDayId && parseInt(day.id) === activeDayId) {
				div.classList.add('active');
			}

			div.style.cssText = 'background:#fff; border:1px solid #ccd0d4; border-radius:4px; padding:10px; cursor:pointer; display:flex; justify-content:space-between; align-items:center; box-shadow:0 1px 2px rgba(0,0,0,0.03); transition:border-color 0.2s;';
			div.addEventListener('mouseenter', () => div.style.borderColor = '#2271b1');
			div.addEventListener('mouseleave', () => {
				if (!div.classList.contains('active')) div.style.borderColor = '#ccd0d4';
			});

			div.innerHTML = `
				<div style="display:flex; align-items:center; gap:8px;">
					<span class="trekpilot-day-drag-handle" style="cursor:move; color:#a7aaad;">☰</span>
					<div>
						<strong style="display:block; font-size:12px;">Day ${day.day_number}: ${day.title}</strong>
						<span style="font-size:10px; color:#666;">${day.items ? day.items.length : 0} activities</span>
					</div>
				</div>
				<div style="display:flex; gap:5px;">
					<a href="#" class="trekpilot-day-delete-action" data-id="${day.id}" style="color:#b32d2e; font-size:11px; text-decoration:none;">&times;</a>
				</div>
			`;

			// Click to select day
			div.addEventListener('click', function(e) {
				if (e.target.classList.contains('trekpilot-day-delete-action') || e.target.classList.contains('trekpilot-day-drag-handle')) {
					return;
				}
				selectDay(parseInt(day.id));
			});

			// Delete day handle
			div.querySelector('.trekpilot-day-delete-action').addEventListener('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				trekpilot_admin_confirm('Are you sure you want to delete this Day and ALL timeline activities configured inside it? This cannot be undone.', function() {
					deleteDay(parseInt(day.id));
				});
			});

			daysList.appendChild(div);
		});

		initDaysDragDrop();
	}

	function selectDay(dayId) {
		activeDayId = dayId;
		const dayObj = activeItinerary.find(d => parseInt(d.id) === dayId);
		if (!dayObj) return;

		// Highlight tab
		const tabs = daysList.querySelectorAll('.trekpilot-itinerary-day-tab');
		tabs.forEach(tab => {
			tab.classList.remove('active');
			tab.style.borderColor = '#ccd0d4';
			if (parseInt(tab.getAttribute('data-id')) === dayId) {
				tab.classList.add('active');
				tab.style.borderColor = '#2271b1';
			}
		});

		noDaySelectedMsg.style.display = 'none';
		dayTimelineWrapper.style.display = 'flex';

		selectedDayTitle.textContent = `Day ${dayObj.day_number}: ${dayObj.title}`;
		selectedDayDesc.textContent = dayObj.description || 'No description provided for this day.';

		renderActivities(dayObj.items || []);
	}

	function renderActivities(items) {
		if (items.length === 0) {
			activitiesTimeline.innerHTML = `
				<div style="text-align:center; padding:30px; border:2px dashed #ccd0d4; border-radius:4px; color:#888; background:#fafafa;">
					No timeline activities scheduled for this day yet. Click "+ Add Activity" to build the timeline.
				</div>
			`;
			return;
		}

		activitiesTimeline.innerHTML = '';
		items.forEach((item) => {
			const card = document.createElement('div');
			card.className = 'trekpilot-activity-timeline-card';
			card.setAttribute('draggable', 'true');
			card.setAttribute('data-id', item.id);

			card.style.cssText = 'background:#fafafa; border:1px solid #ccd0d4; border-radius:4px; padding:12px; display:flex; gap:12px; align-items:flex-start; box-shadow:0 1px 2px rgba(0,0,0,0.02);';

			const imgHTML = item.image_url ? `<img src="${item.image_url}" style="width:70px; height:70px; object-fit:cover; border:1px solid #ddd; border-radius:4px;" />` : '';
			
			// Resolve dashicon markup
			const iconClass = item.icon || 'dashicons-palmtree';
			const iconHTML = `<span class="dashicons ${iconClass}" style="color:#2271b1; font-size:18px; width:18px; height:18px; line-height:1;"></span>`;

			card.innerHTML = `
				<div class="trekpilot-activity-drag-handle" style="cursor:move; color:#a7aaad; font-size:18px; align-self:center;">☰</div>
				<div style="flex-grow:1;">
					<div style="display:flex; align-items:center; gap:6px;">
						${iconHTML}
						<span style="font-weight:600; font-size:11px; color:#50575e;">${item.item_time || 'No time set'}</span>
					</div>
					<h5 style="margin:5px 0; font-size:13px; font-weight:600;">${item.title}</h5>
					<p style="margin:0; font-size:11px; color:#666;">${item.description || ''}</p>
				</div>
				${imgHTML}
				<div style="display:flex; flex-direction:column; gap:5px; align-self:stretch; justify-content:space-between; align-items:flex-end;">
					<div style="display:flex; gap:4px;">
						<a href="#" class="trekpilot-activity-edit" data-id="${item.id}" title="Edit" aria-label="Edit" style="display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px; border-radius:4px; text-decoration:none; color:#2271b1;"><span class="dashicons dashicons-edit" style="font-size:16px; width:16px; height:16px;"></span></a>
						<a href="#" class="trekpilot-activity-delete" data-id="${item.id}" title="Delete" aria-label="Delete" style="display:inline-flex; align-items:center; justify-content:center; width:26px; height:26px; border-radius:4px; text-decoration:none; color:#b32d2e;"><span class="dashicons dashicons-trash" style="font-size:16px; width:16px; height:16px;"></span></a>
					</div>
				</div>
			`;

			// Delete event listener
			card.querySelector('.trekpilot-activity-delete').addEventListener('click', function(e) {
				e.preventDefault();
				trekpilot_admin_confirm('Are you sure you want to delete this timeline activity?', function() {
					deleteActivity(parseInt(item.id));
				});
			});

			// Edit event listener
			card.querySelector('.trekpilot-activity-edit').addEventListener('click', function(e) {
				e.preventDefault();
				openEditActivityModal(item);
			});

			activitiesTimeline.appendChild(card);
		});

		initActivitiesDragDrop();
	}

	// ==========================================
	// 3. Day CRUD Form operations
	// ==========================================
	function clearDayForm() {
		const f = document.getElementById('trekpilot_day_form');
		if (!f) return;
		const inputs = f.querySelectorAll('input, textarea');
		inputs.forEach(i => {
			if (i.type === 'number') i.value = '1';
			else i.value = '';
		});
	}

	addDayBtn.addEventListener('click', function(e) {
		e.preventDefault();
		dayFormTitle.textContent = 'Add Itinerary Day';
		clearDayForm();
		document.getElementById('trekpilot_form_day_id').value = '';
		// auto calculate day number
		const nextDayNumber = activeItinerary.length > 0 ? Math.max(...activeItinerary.map(d => parseInt(d.day_number))) + 1 : 1;
		document.getElementById('trekpilot_form_day_number').value = nextDayNumber;
		dayFormModal.style.display = 'flex';
	});

	editSelectedDayBtn.addEventListener('click', function() {
		const dayObj = activeItinerary.find(d => parseInt(d.id) === activeDayId);
		if (!dayObj) return;

		dayFormTitle.textContent = 'Edit Day Settings';
		document.getElementById('trekpilot_form_day_id').value = dayObj.id;
		document.getElementById('trekpilot_form_day_number').value = dayObj.day_number;
		document.getElementById('trekpilot_form_day_title').value = dayObj.title;
		document.getElementById('trekpilot_form_day_description').value = dayObj.description;

		dayFormModal.style.display = 'flex';
	});

	function closeDayModal() {
		dayFormModal.style.display = 'none';
		clearDayForm();
	}

	dayFormCancelBtn.addEventListener('click', closeDayModal);
	dayFormCloseBtn.addEventListener('click', closeDayModal);

	dayFormSaveBtn.addEventListener('click', function(e) {
		e.preventDefault();
		const f = document.getElementById('trekpilot_day_form');
		if (!f) return;

		// Manual Validation
		const titleFld = document.getElementById('trekpilot_form_day_title');
		if (!titleFld.value.trim()) {
			trekpilot_admin_toast('Day Title is required');
			titleFld.focus();
			return;
		}

		const formData = new FormData();
		formData.append('action', 'trekpilot_save_itinerary_day');
		formData.append('trek_id', trekId);
		formData.append('city_id', activeCityId);
		formData.append('nonce', nonce);

		// Manually append form data
		const inputs = f.querySelectorAll('input, textarea');
		inputs.forEach(i => {
			if (i.name) {
				if (i.name === 'day_id') {
					if (i.value) formData.append('id', i.value);
				} else {
					formData.append(i.name, i.value);
				}
			}
		});

		dayFormModal.style.display = 'none';

		fetch(ajaxUrl, { method: 'POST', body: formData })
			.then(res => res.json())
			.then(data => {
				if (data.success) {
					fetchItinerary(data.data.id);
				} else {
					trekpilot_admin_toast('Error: ' + data.data.message);
					dayFormModal.style.display = 'flex';
				}
			});
	});

	function deleteDay(dayId) {
		const fd = new FormData();
		fd.append('action', 'trekpilot_delete_itinerary_day');
		fd.append('id', dayId);
		fd.append('nonce', nonce);

		fetch(ajaxUrl, { method: 'POST', body: fd })
			.then(res => res.json())
			.then(data => {
				if (data.success) {
					if (activeDayId === dayId) {
						activeDayId = null;
					}
					fetchItinerary();
				} else {
					trekpilot_admin_toast('Error: ' + data.data.message);
				}
			});
	}

	// ==========================================
	// 4. Activity CRUD Form operations
	// ==========================================
	function clearActivityForm() {
		const f = document.getElementById('trekpilot_activity_form');
		if (!f) return;
		const inputs = f.querySelectorAll('input, select, textarea');
		inputs.forEach(i => {
			if (i.tagName === 'SELECT') i.value = 'dashicons-palmtree';
			else if (i._flatpickr) i._flatpickr.clear();
			else i.value = '';
		});
	}

	addActivityBtn.addEventListener('click', function(e) {
		e.preventDefault();
		activityFormTitle.textContent = 'Add Timeline Activity';
		clearActivityForm();
		document.getElementById('trekpilot_form_activity_id').value = '';
		activityImgPreview.innerHTML = '';
		activityFormModal.style.display = 'flex';
	});

	function openEditActivityModal(item) {
		activityFormTitle.textContent = 'Edit Timeline Activity';
		document.getElementById('trekpilot_form_activity_id').value = item.id;
		
		const timeFld = document.getElementById('trekpilot_form_activity_time');
		if (timeFld._flatpickr) {
			timeFld._flatpickr.setDate(item.item_time || '');
		} else {
			timeFld.value = item.item_time || '';
		}
		
		document.getElementById('trekpilot_form_activity_icon').value = item.icon;
		document.getElementById('trekpilot_form_activity_title').value = item.title;
		document.getElementById('trekpilot_form_activity_image').value = item.image_url;
		document.getElementById('trekpilot_form_activity_desc').value = item.description;

		if (item.image_url) {
			activityImgPreview.innerHTML = `<img src="${item.image_url}" style="max-width:100px; max-height:80px; border:1px solid #ddd; border-radius:4px; padding:3px; background:#fff;" />`;
		} else {
			activityImgPreview.innerHTML = '';
		}

		activityFormModal.style.display = 'flex';
	}

	function closeActivityModal() {
		activityFormModal.style.display = 'none';
		clearActivityForm();
		activityImgPreview.innerHTML = '';
	}

	activityFormCancelBtn.addEventListener('click', closeActivityModal);
	activityFormCloseBtn.addEventListener('click', closeActivityModal);

	activityFormSaveBtn.addEventListener('click', function(e) {
		e.preventDefault();
		const f = document.getElementById('trekpilot_activity_form');
		if (!f) return;

		// Manual Validation
		const titleFld = document.getElementById('trekpilot_form_activity_title');
		if (!titleFld.value.trim()) {
			trekpilot_admin_toast('Activity Title is required');
			titleFld.focus();
			return;
		}

		const formData = new FormData();
		formData.append('action', 'trekpilot_save_itinerary_item');
		formData.append('itinerary_id', activeDayId);
		formData.append('nonce', nonce);

		// Manually append form data
		const inputs = f.querySelectorAll('input, select, textarea');
		inputs.forEach(i => {
			if (i.name) {
				if (i.name === 'activity_id') {
					if (i.value) formData.append('id', i.value);
				} else {
					formData.append(i.name, i.value);
				}
			}
		});

		activityFormModal.style.display = 'none';

		fetch(ajaxUrl, { method: 'POST', body: formData })
			.then(res => res.json())
			.then(data => {
				if (data.success) {
					fetchItinerary();
				} else {
					trekpilot_admin_toast('Error: ' + data.data.message);
					activityFormModal.style.display = 'flex';
				}
			});
	});

	function deleteActivity(actId) {
		const fd = new FormData();
		fd.append('action', 'trekpilot_delete_itinerary_item');
		fd.append('id', actId);
		fd.append('nonce', nonce);

		fetch(ajaxUrl, { method: 'POST', body: fd })
			.then(res => res.json())
			.then(data => {
				if (data.success) {
					fetchItinerary();
				} else {
					trekpilot_admin_toast('Error: ' + data.data.message);
				}
			});
	}

	// wp.media uploader for activity single image selection
	let actImgFrame;
	selectActivityImgBtn.addEventListener('click', function(e) {
		e.preventDefault();
		if (actImgFrame) {
			actImgFrame.open();
			return;
		}

		actImgFrame = wp.media({
			title: 'Select Activity Image',
			button: { text: 'Use this image' },
			multiple: false
		});

		actImgFrame.on('select', function() {
			const attachment = actImgFrame.state().get('selection').first().toJSON();
			activityImgInput.value = attachment.url;
			activityImgPreview.innerHTML = `<img src="${attachment.url}" style="max-width:100px; max-height:80px; border:1px solid #ddd; border-radius:4px; padding:3px; background:#fff;" />`;
		});

		actImgFrame.open();
	});

	// ==========================================
	// 5. Native HTML5 Drag and Drop Sorting
	// ==========================================
	let dragDayEl = null;

	function initDaysDragDrop() {
		const dayTabs = daysList.querySelectorAll('.trekpilot-itinerary-day-tab');
		dayTabs.forEach(tab => {
			tab.addEventListener('dragstart', handleDayDragStart);
			tab.addEventListener('dragover', handleDayDragOver);
			tab.addEventListener('drop', handleDayDrop);
			tab.addEventListener('dragend', handleDayDragEnd);
		});
	}

	function handleDayDragStart(e) {
		dragDayEl = this;
		this.style.opacity = '0.5';
		e.dataTransfer.effectAllowed = 'move';
	}

	function handleDayDragOver(e) {
		e.preventDefault();
		e.dataTransfer.dropEffect = 'move';
		const targetTab = this;
		if (targetTab !== dragDayEl) {
			const rect = targetTab.getBoundingClientRect();
			const mid = rect.top + (rect.height / 2);
			if (e.clientY < mid) {
				daysList.insertBefore(dragDayEl, targetTab);
			} else {
				daysList.insertBefore(dragDayEl, targetTab.nextSibling);
			}
		}
	}

	function handleDayDrop(e) {
		e.stopPropagation();
	}

	function handleDayDragEnd() {
		this.style.opacity = '1';
		saveDaysOrder();
	}

	function saveDaysOrder() {
		const dayTabs = daysList.querySelectorAll('.trekpilot-itinerary-day-tab');
		const order = [];
		dayTabs.forEach(tab => {
			order.push(tab.getAttribute('data-id'));
		});

		const fd = new FormData();
		fd.append('action', 'trekpilot_reorder_itinerary_days');
		order.forEach(id => fd.append('order[]', id));
		fd.append('nonce', nonce);

		fetch(ajaxUrl, { method: 'POST', body: fd })
			.then(res => res.json())
			.then(data => {
				if (!data.success) {
					trekpilot_admin_toast('Day reordering failed: ' + data.data.message);
				}
			});
	}

	// drag & drop for activities
	let dragActEl = null;

	function initActivitiesDragDrop() {
		const cards = activitiesTimeline.querySelectorAll('.trekpilot-activity-timeline-card');
		cards.forEach(card => {
			card.addEventListener('dragstart', handleActDragStart);
			card.addEventListener('dragover', handleActDragOver);
			card.addEventListener('drop', handleActDrop);
			card.addEventListener('dragend', handleActDragEnd);
		});
	}

	function handleActDragStart(e) {
		dragActEl = this;
		this.style.opacity = '0.5';
		e.dataTransfer.effectAllowed = 'move';
	}

	function handleActDragOver(e) {
		e.preventDefault();
		e.dataTransfer.dropEffect = 'move';
		const targetCard = this;
		if (targetCard !== dragActEl) {
			const rect = targetCard.getBoundingClientRect();
			const mid = rect.top + (rect.height / 2);
			if (e.clientY < mid) {
				activitiesTimeline.insertBefore(dragActEl, targetCard);
			} else {
				activitiesTimeline.insertBefore(dragActEl, targetCard.nextSibling);
			}
		}
	}

	function handleActDrop(e) {
		e.stopPropagation();
	}

	function handleActDragEnd() {
		this.style.opacity = '1';
		saveActivitiesOrder();
	}

	function saveActivitiesOrder() {
		const cards = activitiesTimeline.querySelectorAll('.trekpilot-activity-timeline-card');
		const order = [];
		cards.forEach(card => {
			order.push(card.getAttribute('data-id'));
		});

		const fd = new FormData();
		fd.append('action', 'trekpilot_reorder_itinerary_items');
		order.forEach(id => fd.append('order[]', id));
		fd.append('nonce', nonce);

		fetch(ajaxUrl, { method: 'POST', body: fd })
			.then(res => res.json())
			.then(data => {
				if (!data.success) {
					trekpilot_admin_toast('Activity sorting failed: ' + data.data.message);
				}
			});
	}

});
