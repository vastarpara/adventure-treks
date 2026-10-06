/**
 * Settings page tab switching.
 */
document.addEventListener('DOMContentLoaded', function () {
	'use strict';

	const tabLinks = document.querySelectorAll('.nav-tab-wrapper .nav-tab[data-at-tab]');
	if (!tabLinks.length) {
		return;
	}

	function activateTab(tabKey) {
		tabLinks.forEach(function (link) {
			link.classList.toggle('nav-tab-active', link.getAttribute('data-at-tab') === tabKey);
		});

		document.querySelectorAll('.at-settings-tab').forEach(function (panel) {
			panel.style.display = (panel.id === 'at-settings-tab-' + tabKey) ? '' : 'none';
		});

		// Import / Export has its own forms, so the settings form (and its Save button) is hidden there.
		const settingsForm = document.querySelector('form.at-settings-form-card');
		if (settingsForm) {
			settingsForm.style.display = (tabKey === 'import-export') ? 'none' : '';
		}
	}

	tabLinks.forEach(function (link) {
		link.addEventListener('click', function (e) {
			e.preventDefault();
			const tabKey = link.getAttribute('data-at-tab');
			activateTab(tabKey);
			window.location.hash = tabKey;
		});
	});

	const initialTab = window.location.hash ? window.location.hash.replace('#', '') : 'general';
	const validTabs = Array.prototype.map.call(tabLinks, function (link) {
		return link.getAttribute('data-at-tab');
	});
	activateTab(validTabs.indexOf(initialTab) !== -1 ? initialTab : 'general');

	// ==========================================
	// Payment tab: Cash / UPI toggle.
	// ==========================================
	const paymentMethodRadios = document.querySelectorAll('input[name="at_payment_method"]');
	const upiFields = document.getElementById('at_upi_fields');

	function toggleUpiFields() {
		if (!upiFields) {
			return;
		}
		const checked = document.querySelector('input[name="at_payment_method"]:checked');
		upiFields.style.display = (checked && checked.value === 'upi') ? '' : 'none';
	}

	paymentMethodRadios.forEach(function (radio) {
		radio.addEventListener('change', toggleUpiFields);
	});
	toggleUpiFields();
});

/**
 * Color picker fields.
 */
if (typeof jQuery !== 'undefined') {
	jQuery(function ($) {
		$('.at-color-picker').wpColorPicker();

		// An empty color means "follow the theme": give each picker a one-click way to get there
		// (the stock "Default" button only returns to a fixed colour, which we no longer set).
		$('.at-color-picker').each(function () {
			const $input = $(this);
			const $btn = $('<button type="button" class="button button-small at-color-theme-btn"></button>')
				.text('Use theme color')
				.css('margin-left', '6px');
			$input.closest('.wp-picker-container').append($btn);

			$btn.on('click', function () {
				$input.wpColorPicker('close');
				$input.val('').trigger('change');
				$input.closest('.wp-picker-container').find('.wp-color-result').css('background-color', '');
			});
		});
	});
}
/**
 * UPI QR Code media uploader.
 */
if (typeof jQuery !== 'undefined') {
	jQuery(function ($) {
		const qrInput = document.getElementById('at_upi_qr_code');
		const qrPreview = document.getElementById('at_upi_qr_code_preview');
		const qrSelectBtn = document.getElementById('at_upi_qr_code_select_btn');
		const qrRemoveBtn = document.getElementById('at_upi_qr_code_remove_btn');

		if (!qrInput || !qrSelectBtn || typeof wp === 'undefined' || !wp.media) {
			return;
		}

		let qrFrame;

		qrSelectBtn.addEventListener('click', function (e) {
			e.preventDefault();

			if (qrFrame) {
				qrFrame.open();
				return;
			}

			qrFrame = wp.media({
				title: 'Select UPI QR Code Image',
				button: { text: 'Use this image' },
				multiple: false,
				library: { type: 'image' }
			});

			qrFrame.on('select', function () {
				const attachment = qrFrame.state().get('selection').first().toJSON();
				qrInput.value = attachment.url;
				qrPreview.textContent = '';
				const qrImg = document.createElement('img');
				qrImg.src = attachment.url;
				qrImg.className = 'at-qr-code-preview-img';
				qrImg.alt = '';
				qrPreview.appendChild(qrImg);
				if (qrRemoveBtn) {
					qrRemoveBtn.style.display = '';
				}
			});

			qrFrame.open();
		});

		if (qrRemoveBtn) {
			qrRemoveBtn.addEventListener('click', function (e) {
				e.preventDefault();
				qrInput.value = '';
				qrPreview.innerHTML = '';
				qrRemoveBtn.style.display = 'none';
			});
		}
	});
}

// ==========================================
// Currency tab: live preview of the price format.
// ==========================================
document.addEventListener('DOMContentLoaded', function () {
	'use strict';

	const preview = document.getElementById('at_currency_preview');
	const symbolEl = document.getElementById('at_currency_symbol');
	const positionEl = document.getElementById('at_currency_position');
	const thousandEl = document.getElementById('at_thousand_separator');
	const decimalEl = document.getElementById('at_decimal_separator');
	const decimalsEl = document.getElementById('at_price_decimals');
	if (!preview || !symbolEl || !positionEl || !thousandEl || !decimalEl || !decimalsEl) {
		return;
	}

	const SAMPLE = 1234567.891;

	function render() {
		const decimals = Math.max(0, Math.min(4, parseInt(decimalsEl.value, 10) || 0));
		const parts = SAMPLE.toFixed(decimals).split('.');
		parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandEl.value.slice(0, 1));
		const number = parts.join(decimals > 0 ? decimalEl.value.slice(0, 1) : '');
		const symbol = symbolEl.value;

		switch (positionEl.value) {
			case 'right': preview.textContent = number + symbol; break;
			case 'left_space': preview.textContent = symbol + ' ' + number; break;
			case 'right_space': preview.textContent = number + ' ' + symbol; break;
			default: preview.textContent = symbol + number;
		}
	}

	[symbolEl, positionEl, thousandEl, decimalEl, decimalsEl].forEach(function (el) {
		el.addEventListener('input', render);
		el.addEventListener('change', render);
	});
	render();
});
