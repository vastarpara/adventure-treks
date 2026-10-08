/**
 * Settings page tab switching.
 */
document.addEventListener('DOMContentLoaded', function () {
	'use strict';

	const tabLinks = document.querySelectorAll('.nav-tab-wrapper .nav-tab[data-trekpilot-tab]');
	if (!tabLinks.length) {
		return;
	}

	function activateTab(tabKey) {
		tabLinks.forEach(function (link) {
			link.classList.toggle('nav-tab-active', link.getAttribute('data-trekpilot-tab') === tabKey);
		});

		document.querySelectorAll('.trekpilot-settings-tab').forEach(function (panel) {
			panel.style.display = (panel.id === 'trekpilot-settings-tab-' + tabKey) ? '' : 'none';
		});

		// Import / Export has its own forms, so the settings form (and its Save button) is hidden there.
		const settingsForm = document.querySelector('form.trekpilot-settings-form-card');
		if (settingsForm) {
			settingsForm.style.display = (tabKey === 'import-export') ? 'none' : '';
		}
	}

	tabLinks.forEach(function (link) {
		link.addEventListener('click', function (e) {
			e.preventDefault();
			const tabKey = link.getAttribute('data-trekpilot-tab');
			activateTab(tabKey);
			window.location.hash = tabKey;
		});
	});

	const initialTab = window.location.hash ? window.location.hash.replace('#', '') : 'general';
	const validTabs = Array.prototype.map.call(tabLinks, function (link) {
		return link.getAttribute('data-trekpilot-tab');
	});
	activateTab(validTabs.indexOf(initialTab) !== -1 ? initialTab : 'general');

	// options.php redirects back to the referer, and browsers never send the #hash, so the
	// user would land on the General tab after saving. Put the active tab into the referer.
	const settingsFormEl = document.querySelector('form.trekpilot-settings-form-card');
	if (settingsFormEl) {
		settingsFormEl.addEventListener('submit', function () {
			const active = document.querySelector('.nav-tab-wrapper .nav-tab-active');
			const referer = settingsFormEl.querySelector('input[name="_wp_http_referer"]');
			if (active && referer) {
				referer.value = referer.value.split('#')[0] + '#' + active.getAttribute('data-trekpilot-tab');
			}
		});
	}

	// ==========================================
	// Payment tab: Cash / UPI toggle.
	// ==========================================
	const paymentMethodRadios = document.querySelectorAll('input[name="trekpilot_payment_method"]');
	const upiFields = document.getElementById('trekpilot_upi_fields');

	function toggleUpiFields() {
		if (!upiFields) {
			return;
		}
		const checked = document.querySelector('input[name="trekpilot_payment_method"]:checked');
		upiFields.style.display = (checked && checked.value === 'upi') ? '' : 'none';
	}

	paymentMethodRadios.forEach(function (radio) {
		radio.addEventListener('change', toggleUpiFields);
	});
	toggleUpiFields();

	// UPI needs a UPI ID and a QR code image: block the save and point at the empty field.
	const upiIdInput = document.getElementById('trekpilot_upi_id');
	const upiQrInput = document.getElementById('trekpilot_upi_qr_code');
	const upiQrBtn = document.getElementById('trekpilot_upi_qr_code_select_btn');
	const settingsFormForUpi = document.querySelector('form.trekpilot-settings-form-card');

	function setUpiError(anchor, id, text) {
		const old = document.getElementById(id);
		if (old) {
			old.remove();
		}
		if (!text || !anchor) {
			return;
		}
		const msg = document.createElement('p');
		msg.id = id;
		msg.className = 'description';
		msg.style.color = '#b32d2e';
		msg.textContent = text;
		anchor.insertAdjacentElement('afterend', msg);
	}

	if (upiIdInput && upiQrInput && upiQrBtn && settingsFormForUpi) {
		settingsFormForUpi.addEventListener('submit', function (e) {
			const checked = document.querySelector('input[name="trekpilot_payment_method"]:checked');
			setUpiError(upiIdInput, 'trekpilot_upi_id_error', '');
			setUpiError(upiQrBtn.parentNode, 'trekpilot_upi_qr_error', '');
			if (!checked || checked.value !== 'upi') {
				return;
			}
			if (!upiIdInput.value.trim()) {
				e.preventDefault();
				setUpiError(upiIdInput, 'trekpilot_upi_id_error', 'Please enter a UPI ID to use UPI as the payment method.');
				upiIdInput.focus();
			}
			if (!upiQrInput.value.trim()) {
				e.preventDefault();
				setUpiError(upiQrBtn.parentNode, 'trekpilot_upi_qr_error', 'Please select a QR code image to use UPI as the payment method.');
				if (upiIdInput.value.trim()) {
					upiQrBtn.focus();
				}
			}
		});
		upiIdInput.addEventListener('input', function () {
			setUpiError(upiIdInput, 'trekpilot_upi_id_error', '');
		});
		upiQrBtn.addEventListener('click', function () {
			setUpiError(upiQrBtn.parentNode, 'trekpilot_upi_qr_error', '');
		});
	}
});

/**
 * Color picker fields.
 */
if (typeof jQuery !== 'undefined') {
	jQuery(function ($) {
		$('.trekpilot-color-picker').wpColorPicker();

		// An empty color means "follow the theme": give each picker a one-click way to get there
		// (the stock "Default" button only returns to a fixed colour, which we no longer set).
		$('.trekpilot-color-picker').each(function () {
			const $input = $(this);
			const $btn = $('<button type="button" class="button button-small trekpilot-color-theme-btn"></button>')
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
		const qrInput = document.getElementById('trekpilot_upi_qr_code');
		const qrPreview = document.getElementById('trekpilot_upi_qr_code_preview');
		const qrSelectBtn = document.getElementById('trekpilot_upi_qr_code_select_btn');
		const qrRemoveBtn = document.getElementById('trekpilot_upi_qr_code_remove_btn');

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
				qrImg.className = 'trekpilot-qr-code-preview-img';
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

	const preview = document.getElementById('trekpilot_currency_preview');
	const symbolEl = document.getElementById('trekpilot_currency_symbol');
	const positionEl = document.getElementById('trekpilot_currency_position');
	const thousandEl = document.getElementById('trekpilot_thousand_separator');
	const decimalEl = document.getElementById('trekpilot_decimal_separator');
	const decimalsEl = document.getElementById('trekpilot_price_decimals');
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
