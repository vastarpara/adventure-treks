/**
 * Single trek page: About toggle, hero gallery lightbox and share dialog.
 */
(function() {
	var wrap = document.getElementById('trekpilot_about_wrap');
	var btn = document.getElementById('trekpilot_about_toggle');
	if (!wrap || !btn) return;
	btn.addEventListener('click', function() {
		var expanded = wrap.classList.toggle('trekpilot-expanded');
		btn.querySelector('.trekpilot-toggle-label').textContent = btn.getAttribute(expanded ? 'data-less' : 'data-more');
	});
})();

(function() {
	var root = document.getElementById('trekpilot_hero_gallery');
	if (!root) return;

	// Lightbox: full, uncropped view of any gallery photo.
	var lightbox = document.getElementById('trekpilot_hero_lightbox');
	if (!lightbox) return;

	var lightboxImg = document.getElementById('trekpilot_hero_lightbox_img');
	var lightboxCounter = document.getElementById('trekpilot_hero_lightbox_counter');
	var lightboxPrev = document.getElementById('trekpilot_hero_lightbox_prev');
	var lightboxNext = document.getElementById('trekpilot_hero_lightbox_next');
	var triggers = root.querySelectorAll('.trekpilot-hero-lightbox-trigger');

	// The lightbox must be able to reach every photo, not just the 5 mosaic tiles
	// that are actually visible — "View All Images" jumps past them. So build the
	// full list from every [data-full] element (visible tiles + hidden sources for
	// the rest), placed by its own data-index rather than DOM order.
	var allSources = root.querySelectorAll('[data-full]');
	var fullSrcs = [];
	allSources.forEach(function(el) {
		var idx = parseInt(el.getAttribute('data-index'), 10);
		if (!isNaN(idx)) fullSrcs[idx] = el.getAttribute('data-full');
	});
	var lbIndex = 0;

	function renderLightbox() {
		lightboxImg.src = fullSrcs[lbIndex];
		if (lightboxCounter) lightboxCounter.textContent = (lbIndex + 1) + ' / ' + fullSrcs.length;
	}

	function openLightbox(index) {
		lbIndex = index;
		renderLightbox();
		lightbox.classList.add('active');
		document.body.style.overflow = 'hidden';
	}

	function closeLightbox() {
		lightbox.classList.remove('active');
		document.body.style.overflow = '';
	}

	function showLightbox(offset) {
		lbIndex = (lbIndex + offset + fullSrcs.length) % fullSrcs.length;
		renderLightbox();
	}

	triggers.forEach(function(el) {
		el.addEventListener('click', function() {
			openLightbox(parseInt(el.getAttribute('data-index'), 10));
		});
	});

	// "View All Images" overlay buttons — stop the click from also opening the
	// tile underneath at its own index; jump straight to the requested start point.
	root.querySelectorAll('.trekpilot-mosaic-viewall-btn').forEach(function(btn) {
		btn.addEventListener('click', function(e) {
			e.stopPropagation();
			openLightbox(parseInt(btn.getAttribute('data-start-index'), 10) || 0);
		});
	});

	lightbox.querySelectorAll('[data-lightbox-close]').forEach(function(closer) {
		closer.addEventListener('click', closeLightbox);
	});
	if (lightboxPrev) lightboxPrev.addEventListener('click', function() { showLightbox(-1); });
	if (lightboxNext) lightboxNext.addEventListener('click', function() { showLightbox(1); });

	document.addEventListener('keydown', function(e) {
		if (!lightbox.classList.contains('active')) return;
		if (e.key === 'Escape') closeLightbox();
		if (e.key === 'ArrowRight') showLightbox(1);
		if (e.key === 'ArrowLeft') showLightbox(-1);
	});
})();

// Share button: opens the share dialog (WhatsApp, Facebook, X, LinkedIn, Reddit, Email, copy link).
// Independent of the gallery/lightbox above so it still works on treks with no photos yet.
(function() {
	var shareBtn = document.getElementById('trekpilot_hero_share_btn');
	var modal = document.getElementById('trekpilot_share_modal');
	if (!shareBtn || !modal) return;

	var url = shareBtn.getAttribute('data-share-url');
	var title = shareBtn.getAttribute('data-share-title');
	var text = encodeURIComponent(title);
	var link = encodeURIComponent(url);

	var targets = {
		whatsapp: 'https://wa.me/?text=' + encodeURIComponent(title + ' ' + url),
		facebook: 'https://www.facebook.com/sharer/sharer.php?u=' + link,
		x: 'https://twitter.com/intent/tweet?text=' + text + '&url=' + link,
		linkedin: 'https://www.linkedin.com/sharing/share-offsite/?url=' + link,
		reddit: 'https://www.reddit.com/submit?url=' + link + '&title=' + text,
		email: 'mailto:?subject=' + text + '&body=' + encodeURIComponent(title + '\n' + url)
	};
	modal.querySelectorAll('[data-share]').forEach(function(a) {
		a.setAttribute('href', targets[a.getAttribute('data-share')]);
	});

	var copyBtn = document.getElementById('trekpilot_share_copy');
	var copyInput = document.getElementById('trekpilot_share_url');
	var copyLabel = copyBtn.textContent;

	function openModal() { modal.classList.add('active'); document.body.style.overflow = 'hidden'; }
	function closeModal() { modal.classList.remove('active'); document.body.style.overflow = ''; }

	shareBtn.addEventListener('click', openModal);
	document.getElementById('trekpilot_share_close').addEventListener('click', closeModal);
	modal.addEventListener('click', function(e) { if (e.target === modal) closeModal(); });
	document.addEventListener('keydown', function(e) { if (e.key === 'Escape' && modal.classList.contains('active')) closeModal(); });
	modal.querySelectorAll('a[data-share]').forEach(function(a) { a.addEventListener('click', function() { setTimeout(closeModal, 150); }); });

	copyBtn.addEventListener('click', function() {
		function done() {
			copyBtn.textContent = copyBtn.getAttribute('data-copied');
			setTimeout(function() { copyBtn.textContent = copyLabel; }, 2000);
		}
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(url).then(done).catch(function() { copyInput.select(); });
		} else {
			copyInput.select();
			try { document.execCommand('copy'); done(); } catch (err) {}
		}
	});
})();
