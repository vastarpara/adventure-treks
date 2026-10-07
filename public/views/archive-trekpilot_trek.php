<?php
/**
 * Trek Archive Page Template
 *
 * Auto-loaded for the trekpilot_trek CPT archive page (works regardless of
 * the active theme/editor — Gutenberg, classic, or block themes). Renders
 * the trek grid using the real main query, so pagination follows WordPress's
 * standard /page/2/ structure.
 *
 * @package    TrekPilot
 * @subpackage TrekPilot/Public/Views
 * @author     Nilesh Vastarpara
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

\TrekPilot\Frontend\Controllers\TrekShortcodesController::render_header();
?>

<div class="trekpilot-trek-archive-wrapper" style="max-width:1200px; margin:30px auto; padding:0 20px;">

	<header class="trekpilot-trek-archive-header" style="margin-bottom:24px;">
		<h1><?php post_type_archive_title(); ?></h1>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="trekpilot-trek-archive-grid" style="--trekpilot-archive-cols: 3;">
			<?php
			while ( have_posts() ) :
				the_post();
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- values are escaped inside get_trek_archive_card_html().
				echo \TrekPilot\Frontend\Controllers\TrekShortcodesController::get_trek_archive_card_html( get_the_ID(), true, true );
			endwhile;
			?>
		</div>

		<div class="trekpilot-trek-archive-pagination">
			<?php
			the_posts_pagination(
				array(
					'prev_text' => esc_html__( '« Prev', 'trekpilot' ),
					'next_text' => esc_html__( 'Next »', 'trekpilot' ),
				)
			);
			?>
		</div>
	<?php else : ?>
		<p class="trekpilot-trek-archive-empty"><?php esc_html_e( 'No treks found.', 'trekpilot' ); ?></p>
	<?php endif; ?>

</div>

<?php
\TrekPilot\Frontend\Controllers\TrekShortcodesController::render_footer();
