<?php
/**
 * Frontend Trek Archive Grid view template.
 *
 * @package    AdventureTreks
 * @subpackage AdventureTreks/Public/Views
 * @author     Nilesh Vastarpara
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>
<div class="at-trek-archive-wrapper">
	<?php if ( $query->have_posts() ) : ?>
		<div class="at-trek-archive-grid at-trek-archive-cols-<?php echo esc_attr( $columns ); ?>">
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- values are escaped inside get_trek_archive_card_html().
				echo \AdventureTreks\Public\Controllers\TrekShortcodesController::get_trek_archive_card_html( get_the_ID(), $show_excerpt, $show_price );
			endwhile;
			?>
		</div>

		<?php if ( $show_pagination && $query->max_num_pages > 1 ) : ?>
			<div class="at-trek-archive-pagination">
				<?php
				echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted output from WP core.
					array(
						'base'      => add_query_arg( 'at_trek_page', '%#%' ),
						'format'    => '',
						'current'   => $current_page,
						'total'     => $query->max_num_pages,
						'prev_text' => esc_html__( '« Prev', 'adventure-treks' ),
						'next_text' => esc_html__( 'Next »', 'adventure-treks' ),
						'type'      => 'plain',
					)
				);
				?>
			</div>
		<?php endif; ?>

	<?php else : ?>
		<p class="at-trek-archive-empty"><?php esc_html_e( 'No treks found.', 'adventure-treks' ); ?></p>
	<?php endif; ?>
</div>
