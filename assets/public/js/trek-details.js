/**
 * JavaScript for managing Frontend Trek Details template [trek_details] (Tabs, Accordions)
 */

document.addEventListener('DOMContentLoaded', function() {

	// ==========================================
	// 0. Offset sticky elements below the theme header when it's itself
	//    sticky (e.g. Elementor's "sticky: top" option pins it with
	//    position:fixed/top:0 above everything). Without this, our own
	//    `top: 0` sticky elements end up stuck hidden underneath it instead
	//    of below it. Height is measured (not hardcoded) since it varies by
	//    breakpoint and whenever the header is edited in Elementor.
	// ==========================================
	function at_sync_sticky_header_offset() {
		const header = document.querySelector('header[data-elementor-type="header"]');
		let isSticky = false;

		if (header) {
			header.querySelectorAll('[data-settings]').forEach(function(el) {
				try {
					const settings = JSON.parse(el.getAttribute('data-settings'));
					if (settings && settings.sticky === 'top') {
						isSticky = true;
					}
				} catch (e) {}
			});
		}

		const offset = isSticky ? Math.round(header.getBoundingClientRect().height) : 0;
		document.documentElement.style.setProperty('--at-header-offset', offset + 'px');
	}
	at_sync_sticky_header_offset();
	window.addEventListener('resize', at_sync_sticky_header_offset);
	window.addEventListener('load', at_sync_sticky_header_offset);

	// ==========================================
	// 1. Tab switching: click a nav tab, only that section shows
	// ==========================================
	const tabLinks = document.querySelectorAll('.at-details-tabs-nav a');
	const tabPanels = document.querySelectorAll('.at-details-tab-panel');

	if (tabLinks.length > 0 && tabPanels.length > 0) {
		tabLinks.forEach(function(link) {
			link.addEventListener('click', function(e) {
				e.preventDefault();

				// If the tab bar has been scrolled out of view, a shorter tab would leave the
				// reader stranded below its content; bring the bar back to the top first.
				const nav = this.closest('.at-details-tabs-nav');
				if (nav) {
					const stickyOffset = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--at-header-offset'), 10) || 0;
					const navTop = nav.getBoundingClientRect().top;
					if (navTop < stickyOffset) {
						window.scrollBy({ top: navTop - stickyOffset - 10, left: 0, behavior: 'instant' });
					}
				}

				tabLinks.forEach(function(l) { l.parentElement.classList.remove('active'); });
				tabPanels.forEach(function(p) { p.classList.remove('active'); });

				this.parentElement.classList.add('active');
				const targetPanel = document.querySelector(this.getAttribute('href'));
				if (targetPanel) {
					targetPanel.classList.add('active');
				}
			});
		});
	}

	// ==========================================
	// 1b. Quick Links: Cancellation/Terms open their popup modals.
	// ==========================================
	const policyTriggers = document.querySelectorAll('[data-popup-target]');
	if (policyTriggers.length > 0) {
		function closePolicyModal(modal) {
			modal.classList.remove('active');
			document.body.style.overflow = '';
		}

		policyTriggers.forEach(function(btn) {
			btn.addEventListener('click', function() {
				const modal = document.getElementById(this.getAttribute('data-popup-target'));
				if (modal) {
					modal.classList.add('active');
					document.body.style.overflow = 'hidden';
				}
			});
		});

		document.querySelectorAll('.at-policy-modal').forEach(function(modal) {
			modal.querySelectorAll('[data-popup-close]').forEach(function(closer) {
				closer.addEventListener('click', function() { closePolicyModal(modal); });
			});
		});

		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape') {
				document.querySelectorAll('.at-policy-modal.active').forEach(closePolicyModal);
			}
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
	// 3. Day-wise Itinerary: Show More / Show Less
	// ==========================================
	// Delegated on `document` because the itinerary markup is re-rendered via
	// AJAX (innerHTML swap) whenever the departure city/date changes, which
	// would detach any listeners bound directly to the day-toggle buttons.
	document.addEventListener('click', function(e) {
		const toggleBtn = e.target.closest('.at-day-toggle-btn');
		if (!toggleBtn) return;
		e.preventDefault();

		const dayBlock = toggleBtn.closest('.at-timeline-day-block');
		const eventsEl = dayBlock ? dayBlock.querySelector('.at-timeline-events') : null;
		if (!eventsEl) return;

		const label = toggleBtn.querySelector('.at-toggle-label');
		const isExpanded = dayBlock.classList.contains('expanded');

		if (isExpanded) {
			eventsEl.style.maxHeight = '0px';
			dayBlock.classList.remove('expanded');
			if (label) label.textContent = toggleBtn.getAttribute('data-label-more');
		} else {
			eventsEl.style.maxHeight = eventsEl.scrollHeight + 'px';
			dayBlock.classList.add('expanded');
			if (label) label.textContent = toggleBtn.getAttribute('data-label-less');
		}
	});

});
