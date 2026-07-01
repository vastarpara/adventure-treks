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

get_header();

// Ensure we have the post.
if ( ! have_posts() ) {
	echo '<p style="padding:40px;text-align:center;">' . esc_html__( 'Trek not found.', 'adventure-treks' ) . '</p>';
	get_footer();
	return;
}

while ( have_posts() ) :
	the_post();
	$trek_id = get_the_ID();
?>

<style>
/* ── Single Trek Layout ───────────────────────────────────────── */
.at-single-trek-wrap {
	max-width: 1200px;
	margin: 30px auto;
	padding: 0 20px;
	font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Hero */
.at-single-hero {
	position: relative;
	border-radius: 16px;
	overflow: hidden;
	margin-bottom: 36px;
	min-height: 380px;
	background: linear-gradient(135deg, #1a3c5e 0%, #0f2d4a 100%);
	display: flex;
	align-items: flex-end;
}
.at-single-hero-img {
	position: absolute;
	inset: 0;
	width: 100%;
	height: 100%;
	object-fit: cover;
	opacity: .55;
}
.at-single-hero-content {
	position: relative;
	z-index: 2;
	padding: 40px;
	width: 100%;
	background: linear-gradient(to top, rgba(0,0,0,.75) 0%, transparent 100%);
}
.at-single-hero-content h1 {
	color: #fff;
	font-size: clamp(24px, 4vw, 42px);
	font-weight: 800;
	margin: 0 0 10px;
	line-height: 1.15;
	text-shadow: 0 2px 8px rgba(0,0,0,.4);
}
.at-single-hero-content .at-hero-excerpt {
	color: rgba(255,255,255,.85);
	font-size: 16px;
	max-width: 680px;
	margin: 0;
	line-height: 1.6;
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
	top: 30px;
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

	<!-- ── HERO ─────────────────────────────────────────────── -->
	<div class="at-single-hero">
		<?php if ( has_post_thumbnail() ) : ?>
			<img src="<?php echo esc_url( get_the_post_thumbnail_url( $trek_id, 'full' ) ); ?>"
				 alt="<?php echo esc_attr( get_the_title() ); ?>"
				 class="at-single-hero-img" />
		<?php endif; ?>
		<div class="at-single-hero-content">
			<h1><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p class="at-hero-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<!-- ── TWO-COLUMN BODY ──────────────────────────────────── -->
	<div class="at-single-body">

		<!-- MAIN -->
		<div class="at-single-main">

			<?php if ( get_the_content() ) : ?>
			<div class="at-section-card">
				<h2><span class="dashicons dashicons-text-page"></span><?php esc_html_e( 'About This Trek', 'adventure-treks' ); ?></h2>
				<div class="at-post-content"><?php the_content(); ?></div>
			</div>
			<?php endif; ?>

			<!-- Trek Details Shortcode (specs, highlights, itinerary, FAQs, policies, gallery) -->
			<?php echo do_shortcode( '[trek_details id="' . $trek_id . '"]' ); ?>

		</div>

		<!-- SIDEBAR -->
		<div class="at-single-sidebar">
			<!-- Booking Widget Shortcode -->
			<?php echo do_shortcode( '[trek_booking id="' . $trek_id . '"]' ); ?>
		</div>

	</div><!-- /.at-single-body -->

</div><!-- /.at-single-trek-wrap -->

<?php
endwhile;

get_footer();
