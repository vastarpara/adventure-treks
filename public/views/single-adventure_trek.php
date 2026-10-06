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
	$adventure_treks_gallery_raw   = $adventure_treks_trek_row ? (string) $adventure_treks_trek_row['gallery'] : '';
	$adventure_treks_trek_duration = $adventure_treks_trek_row ? (string) $adventure_treks_trek_row['duration'] : '';

	$adventure_treks_gallery_ids = ! empty( $adventure_treks_gallery_raw ) ? array_filter( array_map( 'intval', explode( ',', $adventure_treks_gallery_raw ) ) ) : array();

	// One query for every image's alt text instead of one per image.
	update_meta_cache( 'post', $adventure_treks_gallery_ids );

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

	<!-- Share dialog (opened by the Share button). Rendered even without photos so sharing always works. -->
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

	<!-- ── HERO GALLERY LIGHTBOX (full, uncropped photo view) ──── -->
	<?php if ( ! empty( $adventure_treks_hero_images ) ) : ?>
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


	<?php
endwhile;

\AdventureTreks\Frontend\Controllers\TrekShortcodesController::render_footer();
