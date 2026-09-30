<?php
/**
 * Trek Archive Page Template
 *
 * Auto-loaded for the adventure_trek CPT archive page (works regardless of
 * the active theme/editor — Gutenberg, classic, or block themes). Renders
 * the trek grid using the real main query, so pagination follows WordPress's
 * standard /page/2/ structure.
 *
 * @package    AdventureTreks
 * @subpackage AdventureTreks/Public/Views
 * @author     Nilesh Vastarpara
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

\AdventureTreks\Public\Controllers\TrekShortcodesController::render_header();
?>

<div class="at-trek-archive-wrapper" style="max-width:1200px; margin:30px auto; padding:0 20px;">

	<header class="at-trek-archive-header" style="margin-bottom:24px;">
		<h1><?php post_type_archive_title(); ?></h1>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="at-trek-archive-grid" style="--at-archive-cols: 3;">
			<?php
			while ( have_posts() ) :
				the_post();
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- values are escaped inside get_trek_archive_card_html().
				echo \AdventureTreks\Public\Controllers\TrekShortcodesController::get_trek_archive_card_html( get_the_ID(), true, true );
			endwhile;
			?>
		</div>

		<div class="at-trek-archive-pagination">
			<?php
			the_posts_pagination(
				array(
					'prev_text' => esc_html__( '« Prev', 'adventure-treks' ),
					'next_text' => esc_html__( 'Next »', 'adventure-treks' ),
				)
			);
			?>
		</div>
	<?php else : ?>
		<p class="at-trek-archive-empty"><?php esc_html_e( 'No treks found.', 'adventure-treks' ); ?></p>
	<?php endif; ?>

</div>

<?php
\AdventureTreks\Public\Controllers\TrekShortcodesController::render_footer();
