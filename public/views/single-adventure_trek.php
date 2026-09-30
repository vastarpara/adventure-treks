<?php
/**
 * Single Trek Page Template
 *
 * Auto-loaded for all adventure_trek CPT single pages.
 * Uses the active theme's header and footer, renders full trek details
 * and the AJAX booking widget in a two-column premium layout.
 *
 * @package    AdventureTreks
 * @subpackage AdventureTreks/Public/Views
 * @author     Nilesh Vastarpara
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

\AdventureTreks\Public\Controllers\TrekShortcodesController::render_header();

// Ensure we have the post.
if ( ! have_posts() ) {
	echo '<p style="padding:40px;text-align:center;">' . esc_html__( 'Trek not found.', 'adventure-treks' ) . '</p>';
	\AdventureTreks\Public\Controllers\TrekShortcodesController::render_footer();
	return;
}

while ( have_posts() ) :
	the_post();
	$trek_id = get_the_ID();

	// Resolve the hero images: the trek's Photo Gallery when configured,
	// falling back to the Featured Image for treks without one yet.
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	$at_trek_row     = $wpdb->get_row( $wpdb->prepare( "SELECT gallery, duration FROM {$wpdb->prefix}at_treks WHERE post_id = %d", $trek_id ), ARRAY_A );
	$at_gallery_raw  = $at_trek_row ? $at_trek_row['gallery'] : '';
	$at_trek_duration = $at_trek_row ? $at_trek_row['duration'] : '';
	$at_gallery_ids  = ! empty( $at_gallery_raw ) ? array_filter( array_map( 'intval', explode( ',', $at_gallery_raw ) ) ) : array();

	$at_hero_images = array();
	foreach ( $at_gallery_ids as $at_img_id ) {
		$at_main_src = wp_get_attachment_image_src( $at_img_id, 'large' );
		$at_full_src = wp_get_attachment_image_src( $at_img_id, 'full' );
		if ( ! $at_main_src ) {
			continue;
		}
		$at_hero_images[] = array(
			'url'  => $at_main_src[0],
			'full' => $at_full_src ? $at_full_src[0] : $at_main_src[0],
			'alt'  => get_post_meta( $at_img_id, '_wp_attachment_image_alt', true ),
		);
	}

	if ( empty( $at_hero_images ) && has_post_thumbnail() ) {
		$at_hero_images[] = array(
			'url'  => get_the_post_thumbnail_url( $trek_id, 'full' ),
			'full' => get_the_post_thumbnail_url( $trek_id, 'full' ),
			'alt'  => get_the_title(),
		);
	}
	?>

<style>
/* ── Single Trek Layout ───────────────────────────────────────── */
.at-single-trek-wrap {
	max-width: 1200px;
	margin: 30px auto;
	padding: 0 20px;
	font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Hero photo gallery — Airbnb-style mosaic: one large image + a 2x2 grid of
   smaller ones, with a "View All Images" overlay when there are more than 5. */
.at-hero-gallery-card {
	margin-bottom: 24px;
}
.at-hero-mosaic {
	display: grid;
	grid-template-columns: 1.4fr 1fr;
	gap: 8px;
	height: 420px;
	border-radius: 16px;
	overflow: hidden;
}
.at-mosaic-main {
	grid-column: 1;
	grid-row: 1;
}
.at-mosaic-main,
.at-mosaic-cell {
	overflow: hidden;
	cursor: zoom-in;
	background: #f1f3f5;
}
.at-mosaic-main img,
.at-mosaic-cell img {
	width: 100%;
	height: 100%;
	object-fit: cover;
	display: block;
	transition: transform .3s;
}
.at-mosaic-main:hover img,
.at-mosaic-cell:hover img {
	transform: scale(1.04);
}
.at-mosaic-side {
	display: grid;
	grid-template-columns: repeat(2, 1fr);
	grid-template-rows: repeat(2, 1fr);
	gap: 8px;
	height: 100%;
}
.at-mosaic-side[data-count="1"] .at-mosaic-cell:nth-child(1) {
	grid-column: 1 / -1;
	grid-row: 1 / -1;
}
.at-mosaic-side[data-count="2"] .at-mosaic-cell {
	grid-row: 1 / -1;
}
.at-mosaic-side[data-count="3"] .at-mosaic-cell:nth-child(1) {
	grid-row: 1 / -1;
}
/* CSS Grid auto-placement actively dodges any cell an explicit item has claimed —
   it does NOT overlap by default. So the 4th cell needs the *same* explicit
   grid-column/row as the "View All Images" button below, or auto-placement pushes
   it into a new row instead of letting the two share the cell. */
.at-mosaic-side[data-count="4"] .at-mosaic-cell:nth-child(4) {
	grid-column: 2;
	grid-row: 2;
}

/* "View All Images" buttons are placed as plain CSS Grid items sharing the same
   grid cell as their image (NOT position:absolute), so they can't be broken by a
   theme rule resetting `position` on an ancestor — grid placement doesn't depend
   on that at all. The image's hover :hover transform (below) creates its own
   stacking context, so the button still needs an explicit z-index to stay on top
   through the hover animation — DOM order alone isn't reliable once a sibling's
   descendant is transformed. */
.at-mosaic-viewall-btn {
	grid-column: 2;
	grid-row: 2;
	align-self: end;
	justify-self: end;
	margin: 12px;
	z-index: 2;
	display: flex;
	align-items: center;
	gap: 6px;
	background: #fff !important;
	color: #222 !important;
	border: 1px solid #d0d5dd;
	border-radius: 8px;
	padding: 8px 14px;
	font-size: 13px;
	font-weight: 700;
	text-decoration: none;
	cursor: pointer;
	box-shadow: 0 2px 8px rgba(0,0,0,.15);
}
.at-mosaic-viewall-btn .dashicons {
	font-size: 16px;
	width: 16px;
	height: 16px;
}
.at-mosaic-viewall-btn:hover,
.at-mosaic-viewall-btn:focus {
	background: #f6f7f7 !important;
	color: #222 !important;
	text-decoration: none;
}
/* Mobile: a 5-panel mosaic doesn't fit a narrow screen, so collapse to just the
   main image with an always-visible "View All Images" affordance instead. This
   button is a grid sibling of .at-mosaic-main, sharing its (only) grid cell. */
.at-mosaic-viewall-mobile {
	display: none;
}
@media (max-width: 767px) {
	.at-hero-mosaic {
		grid-template-columns: 1fr;
		grid-template-rows: 1fr;
		height: 260px;
	}
	.at-mosaic-side {
		display: none;
	}
	.at-mosaic-viewall-mobile {
		display: flex;
		grid-column: 1;
		grid-row: 1;
	}
}

/* Hero gallery lightbox — full, uncropped view of the tapped photo */
.at-hero-lightbox {
	display: none;
	position: fixed;
	inset: 0;
	z-index: 100000;
	align-items: center;
	justify-content: center;
}
.at-hero-lightbox.active {
	display: flex;
}
.at-hero-lightbox-overlay {
	position: absolute;
	inset: 0;
	background: rgba(0,0,0,.9);
	cursor: pointer;
}
.at-hero-lightbox-content {
	position: relative;
	z-index: 1;
	max-width: 92vw;
	max-height: 90vh;
	display: flex;
	align-items: center;
	justify-content: center;
}
.at-hero-lightbox-content img {
	max-width: 100%;
	max-height: 90vh;
	object-fit: contain;
	border-radius: 4px;
	box-shadow: 0 5px 25px rgba(0,0,0,.5);
}
.at-hero-lightbox-counter {
	position: absolute;
	left: 50%;
	bottom: -34px;
	transform: translateX(-50%);
	z-index: 2;
	color: #fff;
	font-size: 13px;
	font-weight: 600;
	opacity: .85;
}
.at-hero-lightbox-close,
.at-hero-lightbox-prev,
.at-hero-lightbox-next {
	position: absolute;
	z-index: 2;
	background: transparent !important;
	color: #fff;
	border: none;
	cursor: pointer;
	display: flex;
	align-items: center;
	justify-content: center;
	filter: drop-shadow(0 1px 4px rgba(0,0,0,.7));
	transition: opacity .2s;
}
.at-hero-lightbox-close:hover,
.at-hero-lightbox-prev:hover,
.at-hero-lightbox-next:hover {
	background: transparent !important;
	opacity: .75;
}
.at-hero-lightbox-close {
	top: -46px;
	right: 0;
	width: 36px;
	height: 36px;
	font-size: 26px;
	border-radius: 50%;
}
.at-hero-lightbox-prev,
.at-hero-lightbox-next {
	top: 50%;
	transform: translateY(-50%);
	width: 46px;
	height: 46px;
	font-size: 22px;
	border-radius: 50%;
}
.at-hero-lightbox-prev { left: -60px; }
.at-hero-lightbox-next { right: -60px; }
@media (max-width: 768px) {
	.at-hero-lightbox-close { top: -40px; right: 0; }
	.at-hero-lightbox-prev { left: 6px; }
	.at-hero-lightbox-next { right: 6px; }
}

/* Trek title card — sits as the first card in the left column, so the sidebar
   starts at the same height, instead of a full-width block above both columns. */
.at-hero-heading-card {
	display: flex;
	align-items: flex-start;
	justify-content: space-between;
	gap: 20px;
	background: #fff;
	border: 1px solid #e5e7eb;
	border-radius: 12px;
	padding: 24px 28px;
	margin-bottom: 24px;
	box-shadow: 0 2px 8px rgba(0,0,0,.05);
}
.at-hero-heading-text {
	min-width: 0;
}
.at-hero-duration {
	display: block;
	font-size: 13px;
	color: #6b7280;
	font-weight: 600;
	margin-bottom: 6px;
}
.at-hero-heading-card h1 {
	color: var(--at-primary-color, #137a7f);
	font-size: clamp(22px, 3.4vw, 32px);
	font-weight: 800;
	margin: 0 0 8px;
	line-height: 1.2;
}
.at-hero-heading-card .at-hero-excerpt {
	color: #4b5563;
	font-size: 15px;
	margin: 0;
	line-height: 1.6;
}
.at-hero-share-btn {
	flex-shrink: 0;
	display: flex !important;
	align-items: center;
	gap: 6px;
	background: #fff !important;
	color: #333 !important;
	border: 1px solid #d0d5dd !important;
	border-radius: 30px !important;
	padding: 10px 18px !important;
	font-size: 13px;
	font-weight: 600;
	text-decoration: none !important;
	cursor: pointer;
	white-space: nowrap;
}
.at-hero-share-btn:hover,
.at-hero-share-btn:focus {
	background: #f6f7f7 !important;
	text-decoration: none !important;
}
.at-hero-share-btn .dashicons {
	font-size: 16px;
	width: 16px;
	height: 16px;
}
@media (max-width: 600px) {
	.at-hero-heading-card {
		flex-direction: column;
		align-items: stretch;
		padding: 20px;
	}
	.at-hero-share-btn {
		justify-content: center;
	}
}

/* Two-column body */
.at-single-body {
	display: grid;
	grid-template-columns: 1fr 360px;
	gap: 32px;
	align-items: flex-start;
}
@media (max-width: 900px) {
	.at-single-body { grid-template-columns: 1fr; }
	.at-single-sidebar { order: -1; }
}

/* Main content */
.at-single-main {}

/* Sidebar sticky */
.at-single-sidebar {
	position: sticky;
	top: calc(var(--at-header-offset, 0px) + 30px);
}

/* Section Cards */
.at-section-card {
	background: #fff;
	border: 1px solid #e5e7eb;
	border-radius: 12px;
	padding: 28px;
	margin-bottom: 24px;
	box-shadow: 0 2px 8px rgba(0,0,0,.05);
}
.at-section-card h2 {
	font-size: 18px;
	font-weight: 700;
	color: #1a3c5e;
	margin: 0 0 20px;
	padding-bottom: 12px;
	border-bottom: 2px solid #e8f4fd;
	display: flex;
	align-items: center;
	gap: 10px;
}
.at-section-card h2 .dashicons {
	color: #2a7ae2;
	font-size: 20px;
	width: 20px;
	height: 20px;
}

/* Post content */
.at-post-content {
	color: #374151;
	font-size: 16px;
	line-height: 1.75;
}
.at-post-content p { margin-bottom: 16px; }
</style>

<div class="at-single-trek-wrap">

	<!-- ── HERO PHOTO GALLERY (mosaic: 1 large + 2x2 grid, "View All Images") ──── -->
	<?php
	$at_total_images = count( $at_hero_images );
	$at_main_image   = $at_total_images > 0 ? $at_hero_images[0] : null;
	$at_side_images  = $at_total_images > 1 ? array_slice( $at_hero_images, 1, 4 ) : array();
	$at_side_count   = count( $at_side_images );
	$at_hidden_count = max( 0, $at_total_images - 5 );
	?>
	<?php if ( $at_main_image ) : ?>
		<div class="at-hero-gallery-card" id="at_hero_gallery">
			<div class="at-hero-mosaic">

				<div class="at-mosaic-main at-hero-lightbox-trigger" data-full="<?php echo esc_url( $at_main_image['full'] ); ?>" data-index="0">
					<img src="<?php echo esc_url( $at_main_image['url'] ); ?>"
						alt="<?php echo esc_attr( $at_main_image['alt'] ? $at_main_image['alt'] : get_the_title() ); ?>"
						loading="eager" />
				</div>
				<?php if ( $at_total_images > 1 ) : ?>
					<!-- Sibling grid item sharing .at-mosaic-main's cell (mobile only) — not
					     nested inside it, so it never depends on that element's `position`. -->
					<button type="button" class="at-mosaic-viewall-btn at-mosaic-viewall-mobile" data-start-index="0">
						<span class="dashicons dashicons-images-alt2"></span> <?php esc_html_e( 'View All Images', 'adventure-treks' ); ?>
					</button>
				<?php endif; ?>

				<?php if ( $at_side_count > 0 ) : ?>
					<div class="at-mosaic-side" data-count="<?php echo esc_attr( $at_side_count ); ?>">
						<?php foreach ( $at_side_images as $at_side_idx => $at_side_img ) : ?>
							<?php $at_real_idx = $at_side_idx + 1; ?>
							<div class="at-mosaic-cell at-hero-lightbox-trigger" data-full="<?php echo esc_url( $at_side_img['full'] ); ?>" data-index="<?php echo esc_attr( $at_real_idx ); ?>">
								<img src="<?php echo esc_url( $at_side_img['url'] ); ?>"
									alt="<?php echo esc_attr( $at_side_img['alt'] ? $at_side_img['alt'] : get_the_title() ); ?>"
									loading="lazy" />
							</div>
						<?php endforeach; ?>
						<?php if ( $at_hidden_count > 0 ) : ?>
							<!-- Sibling grid item sharing the last cell's grid area (row2/col2) —
							     not nested inside that cell, so no `position` dependency at all. -->
							<button type="button" class="at-mosaic-viewall-btn" data-start-index="5">
								<span class="dashicons dashicons-images-alt2"></span> <?php esc_html_e( 'View All Images', 'adventure-treks' ); ?>
							</button>
						<?php endif; ?>
					</div>
				<?php endif; ?>

			</div>

			<?php if ( $at_hidden_count > 0 ) : ?>
				<!-- Photos beyond the 5 visible mosaic tiles: not shown as tiles, but the
				     lightbox needs their URLs so "View All Images" can actually reach them. -->
				<div style="display:none;" aria-hidden="true">
					<?php for ( $at_hidden_i = 5; $at_hidden_i < $at_total_images; $at_hidden_i++ ) : ?>
						<span class="at-hero-lightbox-source" data-full="<?php echo esc_url( $at_hero_images[ $at_hidden_i ]['full'] ); ?>" data-index="<?php echo esc_attr( $at_hidden_i ); ?>"></span>
					<?php endfor; ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<!-- ── HERO GALLERY LIGHTBOX (full, uncropped photo view) ──── -->
	<?php if ( ! empty( $at_hero_images ) ) : ?>
		<div class="at-hero-lightbox" id="at_hero_lightbox">
			<div class="at-hero-lightbox-overlay" data-lightbox-close></div>
			<div class="at-hero-lightbox-content">
				<button type="button" class="at-hero-lightbox-close" data-lightbox-close aria-label="<?php esc_attr_e( 'Close', 'adventure-treks' ); ?>">&times;</button>
				<?php if ( count( $at_hero_images ) > 1 ) : ?>
					<button type="button" class="at-hero-lightbox-prev" id="at_hero_lightbox_prev" aria-label="<?php esc_attr_e( 'Previous photo', 'adventure-treks' ); ?>"><span class="dashicons dashicons-arrow-left-alt2"></span></button>
					<button type="button" class="at-hero-lightbox-next" id="at_hero_lightbox_next" aria-label="<?php esc_attr_e( 'Next photo', 'adventure-treks' ); ?>"><span class="dashicons dashicons-arrow-right-alt2"></span></button>
				<?php endif; ?>
				<img id="at_hero_lightbox_img" src="" alt="" />
				<?php if ( count( $at_hero_images ) > 1 ) : ?>
					<span class="at-hero-lightbox-counter" id="at_hero_lightbox_counter"></span>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<!-- ── TWO-COLUMN BODY ──────────────────────────────────── -->
	<div class="at-single-body">

		<!-- MAIN -->
		<div class="at-single-main">

			<!-- TREK TITLE CARD (duration, title, tagline, share) -->
			<div class="at-hero-heading-card">
				<div class="at-hero-heading-text">
					<?php if ( ! empty( $at_trek_duration ) ) : ?>
						<span class="at-hero-duration"><?php echo esc_html( $at_trek_duration ); ?></span>
					<?php endif; ?>
					<h1><?php the_title(); ?></h1>
					<?php if ( has_excerpt() ) : ?>
						<p class="at-hero-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
					<?php endif; ?>
				</div>
				<button type="button" class="at-hero-share-btn" id="at_hero_share_btn" data-share-url="<?php echo esc_url( get_permalink( $trek_id ) ); ?>" data-share-title="<?php echo esc_attr( get_the_title() ); ?>">
					<span class="dashicons dashicons-share"></span> <?php esc_html_e( 'Share', 'adventure-treks' ); ?>
				</button>
			</div>

			<?php if ( get_the_content() ) : ?>
			<div class="at-section-card">
				<h2><span class="dashicons dashicons-text-page"></span><?php esc_html_e( 'About This Trek', 'adventure-treks' ); ?></h2>
				<div class="at-post-content"><?php the_content(); ?></div>
			</div>
			<?php endif; ?>

			<!-- Trek Details Shortcode (specs, highlights, itinerary, FAQs, policies, gallery) -->
			<?php echo do_shortcode( '[adventure_details id="' . $trek_id . '"]' ); ?>

		</div>

		<!-- SIDEBAR -->
		<div class="at-single-sidebar">
			<!-- Booking Widget Shortcode -->
			<?php echo do_shortcode( '[adventure_booking id="' . $trek_id . '"]' ); ?>
		</div>

	</div><!-- /.at-single-body -->

</div><!-- /.at-single-trek-wrap -->

<script>
(function() {
	var root = document.getElementById('at_hero_gallery');
	if (!root) return;

	// Lightbox: full, uncropped view of any gallery photo.
	var lightbox = document.getElementById('at_hero_lightbox');
	if (!lightbox) return;

	var lightboxImg = document.getElementById('at_hero_lightbox_img');
	var lightboxCounter = document.getElementById('at_hero_lightbox_counter');
	var lightboxPrev = document.getElementById('at_hero_lightbox_prev');
	var lightboxNext = document.getElementById('at_hero_lightbox_next');
	var triggers = root.querySelectorAll('.at-hero-lightbox-trigger');

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
	root.querySelectorAll('.at-mosaic-viewall-btn').forEach(function(btn) {
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

// Share button: native share sheet where supported (mobile), otherwise copy the
// trek's URL to the clipboard. Independent of the gallery/lightbox above so it
// still works on treks with no photos configured yet.
(function() {
	var shareBtn = document.getElementById('at_hero_share_btn');
	if (!shareBtn) return;

	shareBtn.addEventListener('click', function() {
		var url = shareBtn.getAttribute('data-share-url');
		var title = shareBtn.getAttribute('data-share-title');

		if (navigator.share) {
			navigator.share({ title: title, url: url }).catch(function() {});
			return;
		}

		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(url).then(function() {
				var original = shareBtn.innerHTML;
				shareBtn.innerHTML = '<span class="dashicons dashicons-yes"></span> Link Copied';
				setTimeout(function() { shareBtn.innerHTML = original; }, 2000);
			}).catch(function() {});
		}
	});
})();
</script>

	<?php
endwhile;

\AdventureTreks\Public\Controllers\TrekShortcodesController::render_footer();
