/**
 * JavaScript for Adventure Treks Meta Box Admin Interface
 */

document.addEventListener('DOMContentLoaded', function() {

	// ==========================================
	// 1. Tab switching logic
	// ==========================================
	const tabLinks = document.querySelectorAll('.at-meta-tabs-nav a');
	const tabPanels = document.querySelectorAll('.at-meta-tab-panel');

	if (tabLinks.length > 0) {
		tabLinks.forEach(function(link) {
			link.addEventListener('click', function(e) {
				e.preventDefault();

				// Remove active class from all tabs & panels
				tabLinks.forEach(l => l.parentElement.classList.remove('active'));
				tabPanels.forEach(p => p.classList.remove('active'));

				// Add active class to clicked tab and corresponding panel
				this.parentElement.classList.add('active');
				const activePanelId = this.getAttribute('href');
				const activePanel = document.querySelector(activePanelId);
				if (activePanel) {
					activePanel.classList.add('active');
				}
			});
		});
	}

	// ==========================================
	// 2. FAQ Repeater Logic
	// ==========================================
	const faqList = document.getElementById('at_faq_repeater_list');
	const addFaqBtn = document.getElementById('at_add_faq_row_btn');

	if (addFaqBtn && faqList) {
		addFaqBtn.addEventListener('click', function(e) {
			e.preventDefault();

			// Generate a unique index based on timestamp
			const index = Date.now();

			// Construct row HTML
			const rowHTML = `
				<div class="at-faq-repeater-row" data-index="${index}">
					<span class="at-drag-handle">☰</span>
					<div class="at-faq-row-fields">
						<input type="text" name="at_faq[${index}][q]" placeholder="Question" class="large-text" />
						<textarea name="at_faq[${index}][a]" rows="3" placeholder="Answer" class="large-text"></textarea>
					</div>
					<a href="#" class="button at-remove-faq-row-btn">Remove</a>
				</div>
			`;

			// Append new row
			faqList.insertAdjacentHTML('beforeend', rowHTML);
		});

		// Delegate delete event for dynamically created rows
		faqList.addEventListener('click', function(e) {
			if (e.target && e.target.classList.contains('at-remove-faq-row-btn')) {
				e.preventDefault();
				const row = e.target.closest('.at-faq-repeater-row');
				if (row) {
					row.remove();
				}
			}
		});
	}

	// ==========================================
	// 3. WordPress Media Library Gallery Selector
	// ==========================================
	const selectGalleryBtn = document.getElementById('at_select_gallery_btn');
	const galleryIdsInput = document.getElementById('at_gallery_ids');
	const galleryThumbsWrapper = document.getElementById('at_gallery_thumbs_wrapper');

	if (selectGalleryBtn && galleryIdsInput && galleryThumbsWrapper) {
		let galleryFrame;

		selectGalleryBtn.addEventListener('click', function(e) {
			e.preventDefault();

			// If the media frame already exists, reopen it.
			if (galleryFrame) {
				galleryFrame.open();
				return;
			}

			// Create the media frame.
			galleryFrame = wp.media({
				title: 'Select Gallery Images',
				button: {
					text: 'Use these images'
				},
				multiple: true
			});

			// When images are selected in the media frame...
			galleryFrame.on('select', function() {
				const selection = galleryFrame.state().get('selection');
				const ids = [];
				galleryThumbsWrapper.innerHTML = ''; // Reset markup

				selection.map(function(attachment) {
					attachment = attachment.toJSON();
					ids.push(attachment.id);

					// Render thumbnail
					const thumbnail = (attachment.sizes && attachment.sizes.thumbnail) ? attachment.sizes.thumbnail.url : attachment.url;
					const thumbHTML = `
						<div class="at-gallery-thumb-item" data-id="${attachment.id}">
							<img src="${thumbnail}" />
							<a href="#" class="at-gallery-remove-btn" title="Remove">&times;</a>
						</div>
					`;
					galleryThumbsWrapper.insertAdjacentHTML('beforeend', thumbHTML);
				});

				// Set hidden input value
				galleryIdsInput.value = ids.join(',');
			});

			// Finally, open the modal.
			galleryFrame.open();
		});

		// Remove image from gallery preview and update the hidden IDs field
		galleryThumbsWrapper.addEventListener('click', function(e) {
			if (e.target && e.target.classList.contains('at-gallery-remove-btn')) {
				e.preventDefault();
				const thumbItem = e.target.closest('.at-gallery-thumb-item');
				if (thumbItem) {
					const idToRemove = thumbItem.getAttribute('data-id');
					thumbItem.remove();

					// Update input value
					let currentIds = galleryIdsInput.value.split(',');
					currentIds = currentIds.filter(id => id !== idToRemove);
					galleryIdsInput.value = currentIds.join(',');
				}
			}
		});
	}

});
