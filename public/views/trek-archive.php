<?php
/**
 * Frontend Trek Archive Grid view template.
 *
 * @package    TrekPilot
 * @subpackage TrekPilot/Public/Views
 * @author     Nilesh Vastarpara
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<div class="trekpilot-trek-archive-wrapper">
	<?php if ( $query->have_posts() ) : ?>
		<div class="trekpilot-trek-archive-grid" style="--trekpilot-archive-cols: <?php echo esc_attr( $columns ); ?>;">
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- values are escaped inside get_trek_archive_card_html().
				echo \TrekPilot\Frontend\Controllers\TrekShortcodesController::get_trek_archive_card_html( get_the_ID(), $show_excerpt, $show_price );
			endwhile;
			?>
		</div>

		<?php if ( $show_pagination && $query->max_num_pages > 1 ) : ?>
			<div class="trekpilot-trek-archive-pagination">
				<?php
				echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted output from WP core.
					array(
						'base'      => add_query_arg( 'trekpilot_trek_page', '%#%' ),
						'format'    => '',
						'current'   => $current_page,
						'total'     => $query->max_num_pages,
						'prev_text' => esc_html__( '« Prev', 'trekpilot' ),
						'next_text' => esc_html__( 'Next »', 'trekpilot' ),
						'type'      => 'plain',
					)
				);
				?>
			</div>
		<?php endif; ?>

	<?php else : ?>
		<p class="trekpilot-trek-archive-empty"><?php esc_html_e( 'No treks found.', 'trekpilot' ); ?></p>
	<?php endif; ?>
</div>
