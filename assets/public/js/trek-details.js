/**
 * JavaScript for managing Frontend Trek Details template [trek_details] (Tabs, Accordions)
 */

document.addEventListener('DOMContentLoaded', function() {

	// ==========================================
	// 1. Tab switching logic
	// ==========================================
	const tabLinks = document.querySelectorAll('.at-details-tabs-nav a');
	const tabPanels = document.querySelectorAll('.at-details-tab-panel');

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
	// 2. Accordion logic (FAQs)
	// ==========================================
	const accordionTriggers = document.querySelectorAll('.at-accordion-trigger');

	if (accordionTriggers.length > 0) {
		accordionTriggers.forEach(function(trigger) {
			trigger.addEventListener('click', function(e) {
				e.preventDefault();

				const accordionItem = this.closest('.at-accordion-item');
				const content = accordionItem.querySelector('.at-accordion-content');

				if (accordionItem.classList.contains('active')) {
					// Collapse
					accordionItem.classList.remove('active');
					content.style.maxHeight = '0px';
				} else {
					// Expand (collapse other active items first if desired)
					const activeSiblings = accordionItem.parentElement.querySelectorAll('.at-accordion-item.active');
					activeSiblings.forEach(item => {
						item.classList.remove('active');
						item.querySelector('.at-accordion-content').style.maxHeight = '0px';
					});

					accordionItem.classList.add('active');
					content.style.maxHeight = content.scrollHeight + 'px';
				}
			});
		});
	}

	// ==========================================
	// 3. Gallery Lightbox Logic
	// ==========================================
	const galleryLinks = document.querySelectorAll('.at-gallery-lightbox-link');
	if (galleryLinks.length > 0) {
		// Create Lightbox DOM
		const lightbox = document.createElement('div');
		lightbox.id = 'at-gallery-lightbox';
		lightbox.innerHTML = `
			<div class="at-lightbox-overlay"></div>
			<div class="at-lightbox-content">
				<button class="at-lightbox-close" aria-label="Close">&times;</button>
				<button class="at-lightbox-prev" aria-label="Previous">&#10094;</button>
				<img id="at-lightbox-img" src="" alt="">
				<button class="at-lightbox-next" aria-label="Next">&#10095;</button>
			</div>
		`;
		document.body.appendChild(lightbox);

		const lightboxImg = lightbox.querySelector('#at-lightbox-img');
		const closeBtn = lightbox.querySelector('.at-lightbox-close');
		const prevBtn = lightbox.querySelector('.at-lightbox-prev');
		const nextBtn = lightbox.querySelector('.at-lightbox-next');
		
		let currentIndex = 0;
		const images = Array.from(galleryLinks).map(link => link.getAttribute('href'));

		function openLightbox(index) {
			currentIndex = parseInt(index, 10);
			lightboxImg.src = images[currentIndex];
			lightbox.classList.add('active');
			document.body.style.overflow = 'hidden'; // Prevent background scrolling
		}

		function closeLightbox() {
			lightbox.classList.remove('active');
			document.body.style.overflow = '';
		}

		function showNext() {
			currentIndex = (currentIndex + 1) % images.length;
			lightboxImg.src = images[currentIndex];
		}

		function showPrev() {
			currentIndex = (currentIndex - 1 + images.length) % images.length;
			lightboxImg.src = images[currentIndex];
		}

		galleryLinks.forEach((link) => {
			link.addEventListener('click', function(e) {
				e.preventDefault();
				const index = this.getAttribute('data-index');
				openLightbox(index);
			});
		});

		closeBtn.addEventListener('click', closeLightbox);
		nextBtn.addEventListener('click', showNext);
		prevBtn.addEventListener('click', showPrev);

		// Close on overlay click
		lightbox.querySelector('.at-lightbox-overlay').addEventListener('click', closeLightbox);

		// Keyboard navigation
		document.addEventListener('keydown', function(e) {
			if (!lightbox.classList.contains('active')) return;
			if (e.key === 'Escape') closeLightbox();
			if (e.key === 'ArrowRight') showNext();
			if (e.key === 'ArrowLeft') showPrev();
		});
	}

});
