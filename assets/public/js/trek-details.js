/**
 * JavaScript for managing Frontend Trek Details template [trekpilot_details] (Tabs, Accordions)
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
	function trekpilot_sync_sticky_header_offset() {
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
		document.documentElement.style.setProperty('--trekpilot-header-offset', offset + 'px');
	}
	trekpilot_sync_sticky_header_offset();
	window.addEventListener('resize', trekpilot_sync_sticky_header_offset);
	window.addEventListener('load', trekpilot_sync_sticky_header_offset);

	// ==========================================
	// 1. Tab switching: click a nav tab, only that section shows
	// ==========================================
	const tabLinks = document.querySelectorAll('.trekpilot-details-tabs-nav a');
	const tabPanels = document.querySelectorAll('.trekpilot-details-tab-panel');

	if (tabLinks.length > 0 && tabPanels.length > 0) {
		// Show an arrow/fade on the right while more tabs are hidden beyond the visible edge.
		const tabsNav = tabLinks[0].closest('.trekpilot-details-tabs-nav');
		const tabsWrapper = tabLinks[0].closest('.trekpilot-details-tabs-wrapper');
		function updateTabsHint() {
			if (!tabsNav || !tabsWrapper) { return; }
			const more = tabsNav.scrollWidth - tabsNav.clientWidth - tabsNav.scrollLeft > 4;
			tabsWrapper.classList.toggle('has-more-tabs', more);
			tabsWrapper.classList.toggle('has-more-tabs-left', tabsNav.scrollLeft > 4);
		}
		if (tabsNav) {
			tabsNav.addEventListener('scroll', updateTabsHint, { passive: true });
			window.addEventListener('resize', updateTabsHint);
			updateTabsHint();
		}

		tabLinks.forEach(function(link) {
			link.addEventListener('click', function(e) {
				e.preventDefault();

				// If the tab bar has been scrolled out of view, a shorter tab would leave the
				// reader stranded below its content; bring the bar back to the top first.
				const nav = this.closest('.trekpilot-details-tabs-nav');
				if (nav) {
					const stickyOffset = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--trekpilot-header-offset'), 10) || 0;
					const navTop = nav.getBoundingClientRect().top;
					if (navTop < stickyOffset) {
						window.scrollBy({ top: navTop - stickyOffset - 10, left: 0, behavior: 'instant' });
					}
				}

				tabLinks.forEach(function(l) { l.parentElement.classList.remove('active'); });
				tabPanels.forEach(function(p) { p.classList.remove('active'); });

				this.parentElement.classList.add('active');
				if (tabsNav) {
					// Centre the chosen tab so its neighbours (and any further tabs) peek in.
					const left = this.parentElement.offsetLeft - (tabsNav.clientWidth - this.parentElement.offsetWidth) / 2;
					tabsNav.scrollTo({ left: left, behavior: 'smooth' });
				}
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

		document.querySelectorAll('.trekpilot-policy-modal').forEach(function(modal) {
			// Fixed-position popups inside a transformed/filtered ancestor are positioned against
			// that ancestor, not the screen (they showed at the bottom on mobile). Hoist to <body>.
			if (modal.parentNode !== document.body) {
				document.body.appendChild(modal);
			}
			modal.querySelectorAll('[data-popup-close]').forEach(function(closer) {
				closer.addEventListener('click', function() { closePolicyModal(modal); });
			});
		});

		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape') {
				document.querySelectorAll('.trekpilot-policy-modal.active').forEach(closePolicyModal);
			}
		});
	}

	// ==========================================
	// 2. Accordion logic (FAQs)
	// ==========================================
	const accordionTriggers = document.querySelectorAll('.trekpilot-accordion-trigger');

	if (accordionTriggers.length > 0) {
		accordionTriggers.forEach(function(trigger) {
			trigger.addEventListener('click', function(e) {
				e.preventDefault();

				const accordionItem = this.closest('.trekpilot-accordion-item');
				const content = accordionItem.querySelector('.trekpilot-accordion-content');

				if (accordionItem.classList.contains('active')) {
					// Collapse
					accordionItem.classList.remove('active');
					content.style.maxHeight = '0px';
				} else {
					// Expand (collapse other active items first if desired)
					const activeSiblings = accordionItem.parentElement.querySelectorAll('.trekpilot-accordion-item.active');
					activeSiblings.forEach(item => {
						item.classList.remove('active');
						item.querySelector('.trekpilot-accordion-content').style.maxHeight = '0px';
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
		const toggleBtn = e.target.closest('.trekpilot-day-toggle-btn');
		if (!toggleBtn) return;
		e.preventDefault();

		const dayBlock = toggleBtn.closest('.trekpilot-timeline-day-block');
		const eventsEl = dayBlock ? dayBlock.querySelector('.trekpilot-timeline-events') : null;
		if (!eventsEl) return;

		const label = toggleBtn.querySelector('.trekpilot-toggle-label');
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
