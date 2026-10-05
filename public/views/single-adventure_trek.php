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

\AdventureTreks\Frontend\Controllers\TrekShortcodesController::render_header();

// Ensure we have the post.
if ( ! have_posts() ) {
	echo '<p style="padding:40px;text-align:center;">' . esc_html__( 'Trek not found.', 'adventure-treks' ) . '</p>';
	\AdventureTreks\Frontend\Controllers\TrekShortcodesController::render_footer();
	return;
}

while ( have_posts() ) :
	the_post();
	$adventure_treks_trek_id = get_the_ID();

	// Resolve the hero images: the trek's Photo Gallery when configured,
	// falling back to the Featured Image for treks without one yet.
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	$adventure_treks_trek_row      = $wpdb->get_row( $wpdb->prepare( "SELECT gallery, duration FROM {$wpdb->prefix}at_treks WHERE post_id = %d", $adventure_treks_trek_id ), ARRAY_A );
	$adventure_treks_gallery_raw   = $adventure_treks_trek_row ? $adventure_treks_trek_row['gallery'] : '';
	$adventure_treks_trek_duration = $adventure_treks_trek_row ? $adventure_treks_trek_row['duration'] : '';

	$adventure_treks_gallery_ids = ! empty( $adventure_treks_gallery_raw ) ? array_filter( array_map( 'intval', explode( ',', $adventure_treks_gallery_raw ) ) ) : array();

	$adventure_treks_hero_images = array();
	foreach ( $adventure_treks_gallery_ids as $adventure_treks_img_id ) {
		$adventure_treks_main_src = wp_get_attachment_image_src( $adventure_treks_img_id, 'large' );
		$adventure_treks_full_src = wp_get_attachment_image_src( $adventure_treks_img_id, 'full' );
		if ( ! $adventure_treks_main_src ) {
			continue;
		}
		$adventure_treks_hero_images[] = array(
			'url'  => $adventure_treks_main_src[0],
			'full' => $adventure_treks_full_src ? $adventure_treks_full_src[0] : $adventure_treks_main_src[0],
			'alt'  => get_post_meta( $adventure_treks_img_id, '_wp_attachment_image_alt', true ),
		);
	}

	if ( empty( $adventure_treks_hero_images ) && has_post_thumbnail() ) {
		$adventure_treks_hero_images[] = array(
			'url'  => get_the_post_thumbnail_url( $adventure_treks_trek_id, 'full' ),
			'full' => get_the_post_thumbnail_url( $adventure_treks_trek_id, 'full' ),
			'alt'  => get_the_title(),
		);
	}
	?>

<style>
/* - Single Trek Layout - */
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
	grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
	grid-template-rows: minmax(0, 1fr);
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
	min-height: 0;
	overflow: hidden;
	cursor: zoom-in;
	background: #f1f3f5;
}
.at-mosaic-main img,
.at-mosaic-cell img {
	width: 100% !important;
	max-width: none !important;
	height: 100% !important;
	object-fit: cover !important;
	display: block;
	transition: transform .3s;
}
.at-mosaic-main:hover img,
.at-mosaic-cell:hover img {
	transform: scale(1.04);
}
.at-mosaic-side {
	display: grid;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	grid-template-rows: repeat(2, minmax(0, 1fr));
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
.at-mosaic-side[data-count="2"] .at-mosaic-cell:nth-child(1) {
	grid-column: 1;
}
.at-mosaic-side[data-count="2"] .at-mosaic-cell:nth-child(2) {
	grid-column: 2;
}
.at-mosaic-side[data-count="3"] .at-mosaic-cell:nth-child(1) {
	grid-row: 1 / -1;
	grid-column: 1;
}
.at-mosaic-side[data-count="3"] .at-mosaic-cell:nth-child(2) {
	grid-column: 2;
	grid-row: 1;
}
.at-mosaic-side[data-count="3"] .at-mosaic-cell:nth-child(3) {
	grid-column: 2;
	grid-row: 2;
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
	white-space: nowrap;
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
	color: var(--at-secondary-color, #0f6165) !important;
	text-decoration: none;
}
/* Mobile: a 5-panel mosaic doesn't fit a narrow screen, so collapse to just the
	main image with an always-visible "View All Images" affordance instead. This
	button is a grid sibling of .at-mosaic-main, sharing its (only) grid cell. */
.at-mosaic-viewall-mobile {
	display: none;
}
/* Single photo: no side tiles, so one full-width column with the button on top. */
.at-hero-mosaic--single {
	grid-template-columns: 1fr;
}
.at-hero-mosaic--single .at-mosaic-viewall-mobile {
	display: flex;
	grid-column: 1;
	grid-row: 1;
}
@media (max-width: 767px) {
	/* Mobile keeps the same mosaic: wide main photo on top, 2x2 grid below,
		with the "View All Images" button on the last tile. */
	.at-hero-mosaic {
		grid-template-columns: 1fr;
		grid-template-rows: 220px auto;
		height: auto;
	}
	.at-mosaic-side {
		grid-template-rows: repeat(2, 120px);
		height: auto;
	}
	.at-hero-mosaic--single {
		grid-template-rows: 260px;
	}
	.at-mosaic-viewall-btn {
		margin: 6px;
		padding: 6px 10px;
		font-size: 12px;
		gap: 4px;
	}
	.at-mosaic-viewall-mobile {
		display: none;
	}
}

/* Share dialog: choose where to share (WhatsApp, Facebook, X, LinkedIn...) */
.at-share-modal {
	display: none;
	position: fixed;
	inset: 0;
	z-index: 100001;
	align-items: center;
	justify-content: center;
	padding: 16px;
	background: rgba(0, 0, 0, .55);
}
.at-share-modal.active {
	display: flex;
}
.at-share-dialog {
	width: 100%;
	max-width: 420px;
	background: #fff;
	border-radius: 16px;
	padding: 22px 22px 20px;
	box-shadow: 0 20px 60px rgba(0, 0, 0, .3);
	box-sizing: border-box;
}
.at-share-head {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 4px;
}
.at-share-head h3 {
	margin: 0;
	font-size: 18px;
	font-weight: 700;
	color: #1a1a1a;
}
.at-share-close {
	background: transparent !important;
	border: 0 !important;
	box-shadow: none !important;
	padding: 4px !important;
	color: #6b7280 !important;
	cursor: pointer;
	line-height: 1;
}
.at-share-close:hover,
.at-share-close:focus {
	color: var(--at-secondary-color, #0f6165) !important;
}
.at-share-sub {
	margin: 0 0 18px;
	font-size: 13px;
	color: #6b7280;
}
.at-share-grid {
	display: grid;
	grid-template-columns: repeat(3, 1fr);
	gap: 14px 8px;
	margin-bottom: 18px;
}
.at-share-option {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 8px;
	padding: 0;
	background: transparent !important;
	border: 0 !important;
	box-shadow: none !important;
	color: #333 !important;
	font-size: 12px;
	font-weight: 600;
	text-decoration: none !important;
	cursor: pointer;
}
.at-share-option:hover,
.at-share-option:focus {
	color: var(--at-secondary-color, #0f6165) !important;
}
.at-share-icon {
	width: 52px;
	height: 52px;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	color: #fff;
	transition: transform .15s ease;
}
.at-share-option:hover .at-share-icon {
	transform: translateY(-2px);
}
.at-share-icon .dashicons {
	font-size: 26px;
	width: 26px;
	height: 26px;
}
.at-share-icon svg {
	width: 22px;
	height: 22px;
	fill: currentColor;
}
.at-share-whatsapp { background: #25d366; }
.at-share-facebook { background: #1877f2; }
.at-share-x { background: #000; }
.at-share-linkedin { background: #0a66c2; }
.at-share-reddit { background: #ff4500; }
.at-share-email { background: #6b7280; }
.at-share-copy-row {
	display: flex;
	gap: 8px;
}
.at-share-copy-row input {
	flex: 1;
	min-width: 0;
	padding: 9px 12px;
	border: 1px solid #d0d5dd;
	border-radius: 8px;
	font-size: 12px;
	color: #444;
	background: #f6f7f7;
}
.at-share-copy-btn {
	flex-shrink: 0;
	padding: 9px 16px !important;
	border: 0 !important;
	border-radius: 8px !important;
	background: var(--at-primary-color, #137a7f) !important;
	color: #fff !important;
	font-size: 13px;
	font-weight: 700;
	cursor: pointer;
}
.at-share-copy-btn:hover,
.at-share-copy-btn:focus {
	background: var(--at-secondary-color, #0f6165) !important;
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
	color: var(--at-secondary-color, #0f6165) !important;
	text-decoration: none !important;
}
.at-hero-share-btn .dashicons {
	font-size: 18px;
	width: 18px;
	height: 18px;
	color: var(--at-primary-color, #137a7f);
}
.at-hero-share-btn:hover .dashicons,
.at-hero-share-btn:focus .dashicons {
	color: var(--at-secondary-color, #0f6165);
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
	.at-single-body { grid-template-columns: minmax(0, 1fr); }
	/* Stacked layout: a sticky sidebar would pin over the content below it. */
	.at-single-sidebar { position: static; }
	/* Mobile reading order: title, about, then the booking steps, then the rest of
		the trek details (specs, itinerary, FAQs...). .at-single-main dissolves into the
		flex column so its children and the sidebar can be interleaved. */
	.at-single-body { display: flex; flex-direction: column; gap: 0; }
	.at-single-main { display: contents; }
	.at-single-main > * { order: 4; }
	.at-single-main > *,
	.at-single-sidebar { min-width: 0; max-width: 100%; box-sizing: border-box; }
	.at-single-main > .at-hero-heading-card { order: 1; }
	.at-single-main > .at-about-card { order: 2; }
	.at-single-sidebar { order: 3; }
	.at-hero-heading-card,
	.at-about-card { margin-bottom: 16px; }
	/* Each booking step is its own rounded card instead of one big box. */
	.at-single-sidebar .at-booking-widget-wrapper {
		max-width: none;
		margin: 0;
		padding: 0;
		background: transparent;
		border: 0;
		box-shadow: none;
		overflow: visible;
	}
	.at-single-sidebar .at-widget-header,
	.at-single-sidebar .at-widget-section {
		background: #fff;
		border: 1px solid #e5e7eb;
		border-radius: 12px;
		box-shadow: 0 2px 8px rgba(0,0,0,.05);
		padding: 20px 16px;
		margin: 0 0 16px;
	}
	.at-single-sidebar .at-widget-header h3 { margin: 0 0 4px; }
}

/* Main content — min-width:0 lets wide children (tabs, tables) shrink/scroll instead of stretching the grid. */
.at-single-main,
.at-single-sidebar {
	min-width: 0;
}
@media (max-width: 600px) {
	.at-section-card { padding: 20px 16px; }
}

/* Sidebar sticky (side-by-side layout only) */
@media (min-width: 901px) {
	.at-single-sidebar {
		position: sticky;
		top: calc(var(--at-header-offset, 0px) + 30px);
		/* Never taller than the viewport (minus the fixed bottom Book Now bar): a sticky
			box taller than the screen rides its row's bottom edge, so it shifts whenever the
			main column changes height (Show More, tab switch). Scroll inside it instead. */
		max-height: calc(100vh - var(--at-header-offset, 0px) - 100px);
		overflow-y: auto;
		overscroll-behavior: contain;
		/* Still scrollable with wheel / touch / keyboard, just without the visible scrollbar. */
		scrollbar-width: none;
		-ms-overflow-style: none;
	}
	.at-single-sidebar::-webkit-scrollbar {
		display: none;
		width: 0;
	}
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
.at-post-content-wrap { position: relative; }
/* Teaser (first 250 characters) is shown until "Show More" swaps it for the full text. */
.at-post-content-wrap.at-collapsible .at-about-full { display: none; }
.at-post-content-wrap.at-collapsible.at-expanded .at-about-full { display: block; }
.at-post-content-wrap.at-collapsible.at-expanded .at-about-short { display: none; }
.at-show-more-btn {
	display: none;
	align-items: center;
	gap: 6px;
	margin: 8px 0 0;
	padding: 0 !important;
	background: transparent !important;
	border: none !important;
	border-radius: 0 !important;
	box-shadow: none !important;
	color: var(--at-primary-color, #137a7f) !important;
	font-size: 14px;
	font-weight: 700;
	text-transform: none;
	cursor: pointer;
}
.at-show-more-btn:hover,
.at-show-more-btn:focus {
	background: transparent !important;
	color: var(--at-secondary-color, #0f6165) !important;
	outline: none !important;
	box-shadow: none !important;
}
.at-show-more-btn .dashicons {
	font-size: 16px;
	width: 16px;
	height: 16px;
	transition: transform .2s ease;
}
.at-post-content-wrap.at-expanded + .at-show-more-btn .dashicons { transform: rotate(180deg); }
.at-post-content-wrap.at-collapsible + .at-show-more-btn { display: inline-flex; }
</style>

<div class="at-single-trek-wrap">

	<!-- ── HERO PHOTO GALLERY (mosaic: 1 large + 2x2 grid, "View All Images") ──── -->
	<?php
	$adventure_treks_total_images = count( $adventure_treks_hero_images );
	$adventure_treks_main_image   = $adventure_treks_total_images > 0 ? $adventure_treks_hero_images[0] : null;
	$adventure_treks_side_images  = $adventure_treks_total_images > 1 ? array_slice( $adventure_treks_hero_images, 1, 4 ) : array();
	$adventure_treks_side_count   = count( $adventure_treks_side_images );
	$adventure_treks_hidden_count = max( 0, $adventure_treks_total_images - 5 );
	?>
	<?php if ( $adventure_treks_main_image ) : ?>
		<div class="at-hero-gallery-card" id="at_hero_gallery">
			<div class="at-hero-mosaic<?php echo 0 === $adventure_treks_side_count ? ' at-hero-mosaic--single' : ''; ?>">

				<div class="at-mosaic-main at-hero-lightbox-trigger" data-full="<?php echo esc_url( $adventure_treks_main_image['full'] ); ?>" data-index="0">
					<img src="<?php echo esc_url( $adventure_treks_main_image['url'] ); ?>"
						alt="<?php echo esc_attr( $adventure_treks_main_image['alt'] ? $adventure_treks_main_image['alt'] : get_the_title() ); ?>"
						loading="eager" />
				</div>
				<?php if ( $adventure_treks_total_images >= 1 ) : ?>
					<!-- Sibling grid item sharing .at-mosaic-main's cell (mobile only) — not
						nested inside it, so it never depends on that element's `position`. -->
					<button type="button" class="at-mosaic-viewall-btn at-mosaic-viewall-mobile" data-start-index="0">
						<span class="dashicons dashicons-images-alt2"></span> <?php esc_html_e( 'View All Images', 'adventure-treks' ); ?>
					</button>
				<?php endif; ?>

				<?php if ( $adventure_treks_side_count > 0 ) : ?>
					<div class="at-mosaic-side" data-count="<?php echo esc_attr( $adventure_treks_side_count ); ?>">
						<?php foreach ( $adventure_treks_side_images as $adventure_treks_side_idx => $adventure_treks_side_img ) : ?>
							<?php $adventure_treks_real_idx = $adventure_treks_side_idx + 1; ?>
							<div class="at-mosaic-cell at-hero-lightbox-trigger" data-full="<?php echo esc_url( $adventure_treks_side_img['full'] ); ?>" data-index="<?php echo esc_attr( $adventure_treks_real_idx ); ?>">
								<img src="<?php echo esc_url( $adventure_treks_side_img['url'] ); ?>"
									alt="<?php echo esc_attr( $adventure_treks_side_img['alt'] ? $adventure_treks_side_img['alt'] : get_the_title() ); ?>"
									loading="lazy" />
							</div>
						<?php endforeach; ?>
						<?php if ( $adventure_treks_total_images > 1 ) : ?>
							<!-- Sibling grid item sharing the last cell's grid area (row2/col2) —
								not nested inside that cell, so no `position` dependency at all.
								Always shown on desktop; jumps past the 5 tiles only when more exist. -->
							<button type="button" class="at-mosaic-viewall-btn" data-start-index="<?php echo esc_attr( $adventure_treks_hidden_count > 0 ? 5 : 0 ); ?>">
								<span class="dashicons dashicons-images-alt2"></span> <?php esc_html_e( 'View All Images', 'adventure-treks' ); ?>
							</button>
						<?php endif; ?>
					</div>
				<?php endif; ?>

			</div>

			<?php if ( $adventure_treks_hidden_count > 0 ) : ?>
				<!-- Photos beyond the 5 visible mosaic tiles: not shown as tiles, but the
					lightbox needs their URLs so "View All Images" can actually reach them. -->
				<div style="display:none;" aria-hidden="true">
					<?php for ( $adventure_treks_hidden_i = 5; $adventure_treks_hidden_i < $adventure_treks_total_images; $adventure_treks_hidden_i++ ) : ?>
						<span class="at-hero-lightbox-source" data-full="<?php echo esc_url( $adventure_treks_hero_images[ $adventure_treks_hidden_i ]['full'] ); ?>" data-index="<?php echo esc_attr( $adventure_treks_hidden_i ); ?>"></span>
					<?php endfor; ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<!-- ── HERO GALLERY LIGHTBOX (full, uncropped photo view) ──── -->
	<?php if ( ! empty( $adventure_treks_hero_images ) ) : ?>
		<!-- Share dialog (opened by the Share button) -->
		<div class="at-share-modal" id="at_share_modal" role="dialog" aria-modal="true" aria-labelledby="at_share_title">
			<div class="at-share-dialog">
				<div class="at-share-head">
					<h3 id="at_share_title"><?php esc_html_e( 'Share this trek', 'adventure-treks' ); ?></h3>
					<button type="button" class="at-share-close" id="at_share_close" aria-label="<?php esc_attr_e( 'Close', 'adventure-treks' ); ?>"><span class="dashicons dashicons-no-alt"></span></button>
				</div>
				<p class="at-share-sub"><?php echo esc_html( get_the_title() ); ?></p>
				<div class="at-share-grid">
					<a class="at-share-option" data-share="whatsapp" target="_blank" rel="noopener noreferrer"><span class="at-share-icon at-share-whatsapp"><span class="dashicons dashicons-whatsapp"></span></span>WhatsApp</a>
					<a class="at-share-option" data-share="facebook" target="_blank" rel="noopener noreferrer"><span class="at-share-icon at-share-facebook"><span class="dashicons dashicons-facebook-alt"></span></span>Facebook</a>
					<a class="at-share-option" data-share="x" target="_blank" rel="noopener noreferrer"><span class="at-share-icon at-share-x"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z"/></svg></span>X</a>
					<a class="at-share-option" data-share="linkedin" target="_blank" rel="noopener noreferrer"><span class="at-share-icon at-share-linkedin"><span class="dashicons dashicons-linkedin"></span></span>LinkedIn</a>
					<a class="at-share-option" data-share="reddit" target="_blank" rel="noopener noreferrer"><span class="at-share-icon at-share-reddit"><span class="dashicons dashicons-reddit"></span></span>Reddit</a>
					<a class="at-share-option" data-share="email"><span class="at-share-icon at-share-email"><span class="dashicons dashicons-email-alt"></span></span><?php esc_html_e( 'Email', 'adventure-treks' ); ?></a>
				</div>
				<div class="at-share-copy-row">
					<input type="text" id="at_share_url" value="<?php echo esc_url( get_permalink( $adventure_treks_trek_id ) ); ?>" readonly />
					<button type="button" class="at-share-copy-btn" id="at_share_copy" data-copied="<?php esc_attr_e( 'Copied!', 'adventure-treks' ); ?>"><?php esc_html_e( 'Copy link', 'adventure-treks' ); ?></button>
				</div>
			</div>
		</div>

		<div class="at-hero-lightbox" id="at_hero_lightbox">
			<div class="at-hero-lightbox-overlay" data-lightbox-close></div>
			<div class="at-hero-lightbox-content">
				<button type="button" class="at-hero-lightbox-close" data-lightbox-close aria-label="<?php esc_attr_e( 'Close', 'adventure-treks' ); ?>">&times;</button>
				<?php if ( count( $adventure_treks_hero_images ) > 1 ) : ?>
					<button type="button" class="at-hero-lightbox-prev" id="at_hero_lightbox_prev" aria-label="<?php esc_attr_e( 'Previous photo', 'adventure-treks' ); ?>"><span class="dashicons dashicons-arrow-left-alt2"></span></button>
					<button type="button" class="at-hero-lightbox-next" id="at_hero_lightbox_next" aria-label="<?php esc_attr_e( 'Next photo', 'adventure-treks' ); ?>"><span class="dashicons dashicons-arrow-right-alt2"></span></button>
				<?php endif; ?>
				<img id="at_hero_lightbox_img" src="" alt="" />
				<?php if ( count( $adventure_treks_hero_images ) > 1 ) : ?>
					<span class="at-hero-lightbox-counter" id="at_hero_lightbox_counter"></span>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<!--  TWO-COLUMN BODY -->
	<div class="at-single-body">

		<!-- MAIN -->
		<div class="at-single-main">

			<!-- TREK TITLE CARD (duration, title, tagline, share) -->
			<div class="at-hero-heading-card">
				<div class="at-hero-heading-text">
					<?php if ( ! empty( $adventure_treks_trek_duration ) ) : ?>
						<span class="at-hero-duration"><?php echo esc_html( $adventure_treks_trek_duration ); ?></span>
					<?php endif; ?>
					<h1><?php the_title(); ?></h1>
					<?php if ( has_excerpt() ) : ?>
						<p class="at-hero-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
					<?php endif; ?>
				</div>
				<button type="button" class="at-hero-share-btn" id="at_hero_share_btn" data-share-url="<?php echo esc_url( get_permalink( $adventure_treks_trek_id ) ); ?>" data-share-title="<?php echo esc_attr( get_the_title() ); ?>">
					<span class="dashicons dashicons-share"></span> <?php esc_html_e( 'Share', 'adventure-treks' ); ?>
				</button>
			</div>

			<?php if ( get_the_content() ) : ?>
			<div class="at-section-card at-about-card">
				<h2><span class="dashicons dashicons-text-page"></span><?php esc_html_e( 'About This Trek', 'adventure-treks' ); ?></h2>
				<?php
				// Plain-text teaser: first 250 characters, then the full formatted content on demand.
				$adventure_treks_about_html  = apply_filters( 'the_content', get_the_content() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core hook.
				$adventure_treks_about_text  = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $adventure_treks_about_html ) ) );
				$adventure_treks_about_long  = mb_strlen( $adventure_treks_about_text ) > 250;
				$adventure_treks_about_short = $adventure_treks_about_long ? rtrim( mb_substr( $adventure_treks_about_text, 0, 250 ) ) . '...' : '';
				?>
				<div class="at-post-content-wrap<?php echo $adventure_treks_about_long ? ' at-collapsible' : ''; ?>" id="at_about_wrap">
					<?php if ( $adventure_treks_about_long ) : ?>
						<div class="at-post-content at-about-short"><p><?php echo esc_html( $adventure_treks_about_short ); ?></p></div>
					<?php endif; ?>
					<div class="at-post-content at-about-full"><?php echo $adventure_treks_about_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core the_content output. ?></div>
				</div>
				<button type="button" class="at-show-more-btn" id="at_about_toggle"
					data-more="<?php esc_attr_e( 'Show More', 'adventure-treks' ); ?>"
					data-less="<?php esc_attr_e( 'Show Less', 'adventure-treks' ); ?>">
					<span class="at-toggle-label"><?php esc_html_e( 'Show More', 'adventure-treks' ); ?></span>
					<span class="dashicons dashicons-arrow-down-alt2"></span>
				</button>
				<script>
				(function() {
					var wrap = document.getElementById('at_about_wrap');
					var btn = document.getElementById('at_about_toggle');
					if (!wrap || !btn) return;
					btn.addEventListener('click', function() {
						var expanded = wrap.classList.toggle('at-expanded');
						btn.querySelector('.at-toggle-label').textContent = btn.getAttribute(expanded ? 'data-less' : 'data-more');
					});
				})();
				</script>
			</div>
			<?php endif; ?>

			<!-- Trek Details Shortcode (specs, highlights, itinerary, FAQs, policies, gallery) -->
			<?php echo do_shortcode( '[adventure_details id="' . $adventure_treks_trek_id . '"]' ); ?>

		</div>

		<!-- SIDEBAR -->
		<div class="at-single-sidebar">
			<!-- Booking Widget Shortcode -->
			<?php echo do_shortcode( '[adventure_booking id="' . $adventure_treks_trek_id . '"]' ); ?>
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

// Share button: opens the share dialog (WhatsApp, Facebook, X, LinkedIn, Reddit, Email, copy link).
// Independent of the gallery/lightbox above so it still works on treks with no photos yet.
(function() {
	var shareBtn = document.getElementById('at_hero_share_btn');
	var modal = document.getElementById('at_share_modal');
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

	var copyBtn = document.getElementById('at_share_copy');
	var copyInput = document.getElementById('at_share_url');
	var copyLabel = copyBtn.textContent;

	function openModal() { modal.classList.add('active'); document.body.style.overflow = 'hidden'; }
	function closeModal() { modal.classList.remove('active'); document.body.style.overflow = ''; }

	shareBtn.addEventListener('click', openModal);
	document.getElementById('at_share_close').addEventListener('click', closeModal);
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
</script>

	<?php
endwhile;

\AdventureTreks\Frontend\Controllers\TrekShortcodesController::render_footer();
