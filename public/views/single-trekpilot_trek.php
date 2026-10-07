<?php
/**
 * Single Trek Page Template
 *
 * Auto-loaded for all trekpilot_trek CPT single pages.
 * Uses the active theme's header and footer, renders full trek details
 * and the AJAX booking widget in a two-column premium layout.
 *
 * @package    TrekPilot
 * @subpackage TrekPilot/Public/Views
 * @author     Nilesh Vastarpara
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

\TrekPilot\Frontend\Controllers\TrekShortcodesController::render_header();

// Ensure we have the post.
if ( ! have_posts() ) {
	echo '<p style="padding:40px;text-align:center;">' . esc_html__( 'Trek not found.', 'trekpilot' ) . '</p>';
	\TrekPilot\Frontend\Controllers\TrekShortcodesController::render_footer();
	return;
}

while ( have_posts() ) :
	the_post();
	$trekpilot_trek_id = get_the_ID();

	// Resolve the hero images: the trek's Photo Gallery when configured,
	// falling back to the Featured Image for treks without one yet.
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
	$trekpilot_trek_row      = $wpdb->get_row( $wpdb->prepare( "SELECT gallery, duration FROM {$wpdb->prefix}trekpilot_treks WHERE post_id = %d", $trekpilot_trek_id ), ARRAY_A );
	$trekpilot_gallery_raw   = $trekpilot_trek_row ? (string) $trekpilot_trek_row['gallery'] : '';
	$trekpilot_trek_duration = $trekpilot_trek_row ? (string) $trekpilot_trek_row['duration'] : '';

	$trekpilot_gallery_ids = ! empty( $trekpilot_gallery_raw ) ? array_filter( array_map( 'intval', explode( ',', $trekpilot_gallery_raw ) ) ) : array();

	// One query for every image's alt text instead of one per image.
	update_meta_cache( 'post', $trekpilot_gallery_ids );

	$trekpilot_hero_images = array();
	foreach ( $trekpilot_gallery_ids as $trekpilot_img_id ) {
		$trekpilot_main_src = wp_get_attachment_image_src( $trekpilot_img_id, 'large' );
		$trekpilot_full_src = wp_get_attachment_image_src( $trekpilot_img_id, 'full' );
		if ( ! $trekpilot_main_src ) {
			continue;
		}
		$trekpilot_hero_images[] = array(
			'url'  => $trekpilot_main_src[0],
			'full' => $trekpilot_full_src ? $trekpilot_full_src[0] : $trekpilot_main_src[0],
			'alt'  => get_post_meta( $trekpilot_img_id, '_wp_attachment_image_alt', true ),
		);
	}

	if ( empty( $trekpilot_hero_images ) && has_post_thumbnail() ) {
		$trekpilot_hero_images[] = array(
			'url'  => get_the_post_thumbnail_url( $trekpilot_trek_id, 'full' ),
			'full' => get_the_post_thumbnail_url( $trekpilot_trek_id, 'full' ),
			'alt'  => get_the_title(),
		);
	}
	?>

<div class="trekpilot-single-trek-wrap">

	<!-- ── HERO PHOTO GALLERY (mosaic: 1 large + 2x2 grid, "View All Images") ──── -->
	<?php
	$trekpilot_total_images = count( $trekpilot_hero_images );
	$trekpilot_main_image   = $trekpilot_total_images > 0 ? $trekpilot_hero_images[0] : null;
	$trekpilot_side_images  = $trekpilot_total_images > 1 ? array_slice( $trekpilot_hero_images, 1, 4 ) : array();
	$trekpilot_side_count   = count( $trekpilot_side_images );
	$trekpilot_hidden_count = max( 0, $trekpilot_total_images - 5 );
	?>
	<?php if ( $trekpilot_main_image ) : ?>
		<div class="trekpilot-hero-gallery-card" id="trekpilot_hero_gallery">
			<div class="trekpilot-hero-mosaic<?php echo 0 === $trekpilot_side_count ? ' trekpilot-hero-mosaic--single' : ''; ?>">

				<div class="trekpilot-mosaic-main trekpilot-hero-lightbox-trigger" data-full="<?php echo esc_url( $trekpilot_main_image['full'] ); ?>" data-index="0">
					<img src="<?php echo esc_url( $trekpilot_main_image['url'] ); ?>"
						alt="<?php echo esc_attr( $trekpilot_main_image['alt'] ? $trekpilot_main_image['alt'] : get_the_title() ); ?>"
						loading="eager" />
				</div>
				<?php if ( $trekpilot_total_images >= 1 ) : ?>
					<!-- Sibling grid item sharing .trekpilot-mosaic-main's cell (mobile only) — not
						nested inside it, so it never depends on that element's `position`. -->
					<button type="button" class="trekpilot-mosaic-viewall-btn trekpilot-mosaic-viewall-mobile" data-start-index="0">
						<span class="dashicons dashicons-images-alt2"></span> <?php esc_html_e( 'View All Images', 'trekpilot' ); ?>
					</button>
				<?php endif; ?>

				<?php if ( $trekpilot_side_count > 0 ) : ?>
					<div class="trekpilot-mosaic-side" data-count="<?php echo esc_attr( $trekpilot_side_count ); ?>">
						<?php foreach ( $trekpilot_side_images as $trekpilot_side_idx => $trekpilot_side_img ) : ?>
							<?php $trekpilot_real_idx = $trekpilot_side_idx + 1; ?>
							<div class="trekpilot-mosaic-cell trekpilot-hero-lightbox-trigger" data-full="<?php echo esc_url( $trekpilot_side_img['full'] ); ?>" data-index="<?php echo esc_attr( $trekpilot_real_idx ); ?>">
								<img src="<?php echo esc_url( $trekpilot_side_img['url'] ); ?>"
									alt="<?php echo esc_attr( $trekpilot_side_img['alt'] ? $trekpilot_side_img['alt'] : get_the_title() ); ?>"
									loading="lazy" />
							</div>
						<?php endforeach; ?>
						<?php if ( $trekpilot_total_images > 1 ) : ?>
							<!-- Sibling grid item sharing the last cell's grid area (row2/col2) —
								not nested inside that cell, so no `position` dependency at all.
								Always shown on desktop; jumps past the 5 tiles only when more exist. -->
							<button type="button" class="trekpilot-mosaic-viewall-btn" data-start-index="<?php echo esc_attr( $trekpilot_hidden_count > 0 ? 5 : 0 ); ?>">
								<span class="dashicons dashicons-images-alt2"></span> <?php esc_html_e( 'View All Images', 'trekpilot' ); ?>
							</button>
						<?php endif; ?>
					</div>
				<?php endif; ?>

			</div>

			<?php if ( $trekpilot_hidden_count > 0 ) : ?>
				<!-- Photos beyond the 5 visible mosaic tiles: not shown as tiles, but the
					lightbox needs their URLs so "View All Images" can actually reach them. -->
				<div style="display:none;" aria-hidden="true">
					<?php for ( $trekpilot_hidden_i = 5; $trekpilot_hidden_i < $trekpilot_total_images; $trekpilot_hidden_i++ ) : ?>
						<span class="trekpilot-hero-lightbox-source" data-full="<?php echo esc_url( $trekpilot_hero_images[ $trekpilot_hidden_i ]['full'] ); ?>" data-index="<?php echo esc_attr( $trekpilot_hidden_i ); ?>"></span>
					<?php endfor; ?>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<!-- Share dialog (opened by the Share button). Rendered even without photos so sharing always works. -->
	<div class="trekpilot-share-modal" id="trekpilot_share_modal" role="dialog" aria-modal="true" aria-labelledby="trekpilot_share_title">
		<div class="trekpilot-share-dialog">
			<div class="trekpilot-share-head">
				<h3 id="trekpilot_share_title"><?php esc_html_e( 'Share this trek', 'trekpilot' ); ?></h3>
				<button type="button" class="trekpilot-share-close" id="trekpilot_share_close" aria-label="<?php esc_attr_e( 'Close', 'trekpilot' ); ?>"><span class="dashicons dashicons-no-alt"></span></button>
			</div>
			<p class="trekpilot-share-sub"><?php echo esc_html( get_the_title() ); ?></p>
			<div class="trekpilot-share-grid">
				<a href="#" class="trekpilot-share-option" data-share="whatsapp" target="_blank" rel="noopener noreferrer"><span class="trekpilot-share-icon trekpilot-share-whatsapp"><span class="dashicons dashicons-whatsapp"></span></span>WhatsApp</a>
				<a href="#" class="trekpilot-share-option" data-share="facebook" target="_blank" rel="noopener noreferrer"><span class="trekpilot-share-icon trekpilot-share-facebook"><span class="dashicons dashicons-facebook-alt"></span></span>Facebook</a>
				<a href="#" class="trekpilot-share-option" data-share="x" target="_blank" rel="noopener noreferrer"><span class="trekpilot-share-icon trekpilot-share-x"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18.901 1.153h3.68l-8.04 9.19L24 22.846h-7.406l-5.8-7.584-6.638 7.584H.474l8.6-9.83L0 1.154h7.594l5.243 6.932ZM17.61 20.644h2.039L6.486 3.24H4.298Z"/></svg></span>X</a>
				<a href="#" class="trekpilot-share-option" data-share="linkedin" target="_blank" rel="noopener noreferrer"><span class="trekpilot-share-icon trekpilot-share-linkedin"><span class="dashicons dashicons-linkedin"></span></span>LinkedIn</a>
				<a href="#" class="trekpilot-share-option" data-share="reddit" target="_blank" rel="noopener noreferrer"><span class="trekpilot-share-icon trekpilot-share-reddit"><span class="dashicons dashicons-reddit"></span></span>Reddit</a>
				<a href="#" class="trekpilot-share-option" data-share="email"><span class="trekpilot-share-icon trekpilot-share-email"><span class="dashicons dashicons-email-alt"></span></span><?php esc_html_e( 'Email', 'trekpilot' ); ?></a>
			</div>
			<div class="trekpilot-share-copy-row">
				<input type="text" id="trekpilot_share_url" value="<?php echo esc_url( get_permalink( $trekpilot_trek_id ) ); ?>" readonly />
				<button type="button" class="trekpilot-share-copy-btn" id="trekpilot_share_copy" data-copied="<?php esc_attr_e( 'Copied!', 'trekpilot' ); ?>"><?php esc_html_e( 'Copy link', 'trekpilot' ); ?></button>
			</div>
		</div>
	</div>

	<!-- ── HERO GALLERY LIGHTBOX (full, uncropped photo view) ──── -->
	<?php if ( ! empty( $trekpilot_hero_images ) ) : ?>
		<div class="trekpilot-hero-lightbox" id="trekpilot_hero_lightbox">
			<div class="trekpilot-hero-lightbox-overlay" data-lightbox-close></div>
			<div class="trekpilot-hero-lightbox-content">
				<button type="button" class="trekpilot-hero-lightbox-close" data-lightbox-close aria-label="<?php esc_attr_e( 'Close', 'trekpilot' ); ?>">&times;</button>
				<?php if ( count( $trekpilot_hero_images ) > 1 ) : ?>
					<button type="button" class="trekpilot-hero-lightbox-prev" id="trekpilot_hero_lightbox_prev" aria-label="<?php esc_attr_e( 'Previous photo', 'trekpilot' ); ?>"><span class="dashicons dashicons-arrow-left-alt2"></span></button>
					<button type="button" class="trekpilot-hero-lightbox-next" id="trekpilot_hero_lightbox_next" aria-label="<?php esc_attr_e( 'Next photo', 'trekpilot' ); ?>"><span class="dashicons dashicons-arrow-right-alt2"></span></button>
				<?php endif; ?>
				<img id="trekpilot_hero_lightbox_img" src="" alt="" />
				<?php if ( count( $trekpilot_hero_images ) > 1 ) : ?>
					<span class="trekpilot-hero-lightbox-counter" id="trekpilot_hero_lightbox_counter"></span>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<!--  TWO-COLUMN BODY -->
	<div class="trekpilot-single-body">

		<!-- MAIN -->
		<div class="trekpilot-single-main">

			<!-- TREK TITLE CARD (duration, title, tagline, share) -->
			<div class="trekpilot-hero-heading-card">
				<div class="trekpilot-hero-heading-text">
					<?php if ( ! empty( $trekpilot_trek_duration ) ) : ?>
						<span class="trekpilot-hero-duration"><?php echo esc_html( $trekpilot_trek_duration ); ?></span>
					<?php endif; ?>
					<h1><?php the_title(); ?></h1>
					<?php if ( has_excerpt() ) : ?>
						<p class="trekpilot-hero-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
					<?php endif; ?>
				</div>
				<button type="button" class="trekpilot-hero-share-btn" id="trekpilot_hero_share_btn" data-share-url="<?php echo esc_url( get_permalink( $trekpilot_trek_id ) ); ?>" data-share-title="<?php echo esc_attr( get_the_title() ); ?>">
					<span class="dashicons dashicons-share"></span> <?php esc_html_e( 'Share', 'trekpilot' ); ?>
				</button>
			</div>

			<?php if ( get_the_content() ) : ?>
			<div class="trekpilot-section-card trekpilot-about-card">
				<h2><span class="dashicons dashicons-text-page"></span><?php esc_html_e( 'About This Trek', 'trekpilot' ); ?></h2>
				<?php
				// Plain-text teaser: first 250 characters, then the full formatted content on demand.
				$trekpilot_about_html  = apply_filters( 'the_content', get_the_content() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core hook.
				$trekpilot_about_text  = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $trekpilot_about_html ) ) );
				$trekpilot_about_long  = mb_strlen( $trekpilot_about_text ) > 250;
				$trekpilot_about_short = $trekpilot_about_long ? rtrim( mb_substr( $trekpilot_about_text, 0, 250 ) ) . '...' : '';
				?>
				<div class="trekpilot-post-content-wrap<?php echo $trekpilot_about_long ? ' trekpilot-collapsible' : ''; ?>" id="trekpilot_about_wrap">
					<?php if ( $trekpilot_about_long ) : ?>
						<div class="trekpilot-post-content trekpilot-about-short"><p><?php echo esc_html( $trekpilot_about_short ); ?></p></div>
					<?php endif; ?>
					<div class="trekpilot-post-content trekpilot-about-full"><?php echo $trekpilot_about_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core the_content output. ?></div>
				</div>
				<button type="button" class="trekpilot-show-more-btn" id="trekpilot_about_toggle"
					data-more="<?php esc_attr_e( 'Show More', 'trekpilot' ); ?>"
					data-less="<?php esc_attr_e( 'Show Less', 'trekpilot' ); ?>">
					<span class="trekpilot-toggle-label"><?php esc_html_e( 'Show More', 'trekpilot' ); ?></span>
					<span class="dashicons dashicons-arrow-down-alt2"></span>
				</button>
			</div>
			<?php endif; ?>

			<!-- Trek Details Shortcode (specs, highlights, itinerary, FAQs, policies, gallery) -->
			<?php echo do_shortcode( '[trekpilot_details id="' . $trekpilot_trek_id . '"]' ); ?>

		</div>

		<!-- SIDEBAR -->
		<div class="trekpilot-single-sidebar">
			<!-- Booking Widget Shortcode -->
			<?php echo do_shortcode( '[trekpilot_booking id="' . $trekpilot_trek_id . '"]' ); ?>
		</div>

	</div><!-- /.trekpilot-single-body -->

</div><!-- /.trekpilot-single-trek-wrap -->


	<?php
endwhile;

\TrekPilot\Frontend\Controllers\TrekShortcodesController::render_footer();
