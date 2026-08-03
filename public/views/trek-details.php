<?php
/**
 * View template for rendering Trek details frontend UI.
 *
 * @package    AdventureTreks
 * @subpackage AdventureTreks/Public/Views
 * @author     Nilesh Vastarpara
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="at-trek-details-container">
	
	<!-- 1. Specifications Icon Grid -->
	<div class="at-specs-header-grid">
		
		<?php if ( ! empty( $trek['difficulty'] ) ) : ?>
			<div class="at-spec-item">
				<span class="dashicons dashicons-performance"></span>
				<div class="at-spec-info">
					<span class="at-spec-label"><?php esc_html_e( 'Difficulty', 'adventure-treks' ); ?></span>
					<strong class="at-spec-value"><?php echo esc_html( $trek['difficulty'] ); ?></strong>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $trek['duration'] ) ) : ?>
			<div class="at-spec-item">
				<span class="dashicons dashicons-clock"></span>
				<div class="at-spec-info">
					<span class="at-spec-label"><?php esc_html_e( 'Duration', 'adventure-treks' ); ?></span>
					<strong class="at-spec-value"><?php echo esc_html( $trek['duration'] ); ?></strong>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $trek['altitude'] ) ) : ?>
			<div class="at-spec-item">
				<span class="dashicons dashicons-arrow-up-alt"></span>
				<div class="at-spec-info">
					<span class="at-spec-label"><?php esc_html_e( 'Max Altitude', 'adventure-treks' ); ?></span>
					<strong class="at-spec-value"><?php echo esc_html( $trek['altitude'] ); ?></strong>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $trek['region'] ) ) : ?>
			<div class="at-spec-item">
				<span class="dashicons dashicons-location"></span>
				<div class="at-spec-info">
					<span class="at-spec-label"><?php esc_html_e( 'Region', 'adventure-treks' ); ?></span>
					<strong class="at-spec-value"><?php echo esc_html( $trek['region'] ); ?></strong>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $trek['season'] ) ) : ?>
			<div class="at-spec-item">
				<span class="dashicons dashicons-calendar-alt"></span>
				<div class="at-spec-info">
					<span class="at-spec-label"><?php esc_html_e( 'Best Season', 'adventure-treks' ); ?></span>
					<strong class="at-spec-value"><?php echo esc_html( $trek['season'] ); ?></strong>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $trek['distance'] ) ) : ?>
			<div class="at-spec-item">
				<span class="dashicons dashicons-location-alt"></span>
				<div class="at-spec-info">
					<span class="at-spec-label"><?php esc_html_e( 'Trek Distance', 'adventure-treks' ); ?></span>
					<strong class="at-spec-value"><?php echo esc_html( $trek['distance'] ); ?></strong>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $trek['fitness_level'] ) ) : ?>
			<div class="at-spec-item">
				<span class="dashicons dashicons-heart"></span>
				<div class="at-spec-info">
					<span class="at-spec-label"><?php esc_html_e( 'Fitness Level', 'adventure-treks' ); ?></span>
					<strong class="at-spec-value"><?php echo esc_html( $trek['fitness_level'] ); ?></strong>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $trek['age_limit'] ) ) : ?>
			<div class="at-spec-item">
				<span class="dashicons dashicons-admin-users"></span>
				<div class="at-spec-info">
					<span class="at-spec-label"><?php esc_html_e( 'Age Limit', 'adventure-treks' ); ?></span>
					<strong class="at-spec-value"><?php echo esc_html( $trek['age_limit'] ); ?></strong>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $trek['group_size'] ) ) : ?>
			<div class="at-spec-item">
				<span class="dashicons dashicons-groups"></span>
				<div class="at-spec-info">
					<span class="at-spec-label"><?php esc_html_e( 'Group Size', 'adventure-treks' ); ?></span>
					<strong class="at-spec-value"><?php echo esc_html( $trek['group_size'] ); ?></strong>
				</div>
			</div>
		<?php endif; ?>

	</div>

	<!-- 2. Content Sections in Tab Layout -->
	<div class="at-details-tabs-wrapper">
		<ul class="at-details-tabs-nav">
			<li class="active"><a href="#at-f-tab-itinerary"><?php esc_html_e( 'Itinerary', 'adventure-treks' ); ?></a></li>
			<?php if ( ! empty( $trek['highlights'] ) ) : ?>
				<li><a href="#at-f-tab-highlights"><?php esc_html_e( 'Highlights', 'adventure-treks' ); ?></a></li>
			<?php endif; ?>
			<?php if ( ! empty( $faq_items ) ) : ?>
				<li><a href="#at-f-tab-faq"><?php esc_html_e( 'FAQs', 'adventure-treks' ); ?></a></li>
			<?php endif; ?>
			<?php if ( ! empty( $trek['things_to_carry'] ) ) : ?>
				<li><a href="#at-f-tab-carry"><?php esc_html_e( 'Things To Carry', 'adventure-treks' ); ?></a></li>
			<?php endif; ?>
			<?php if ( ! empty( $policies ) ) : ?>
				<li><a href="#at-f-tab-policies"><?php esc_html_e( 'Policies', 'adventure-treks' ); ?></a></li>
			<?php endif; ?>
		</ul>

		<div class="at-details-tabs-content">
			
			<!-- Tab: Itinerary -->
			<div id="at-f-tab-itinerary" class="at-details-tab-panel active">
				<div class="at-tab-inner-content">
					<h3 class="at-tab-headline"><?php esc_html_e( 'Detailed Itinerary', 'adventure-treks' ); ?></h3>
					<div id="trek_itinerary_container">
						<?php
						if ( $default_city_id ) {
							echo do_shortcode( '[adventure_itinerary city_id="' . $default_city_id . '"]' );
						} else {
							echo '<p style="color:#666; font-style:italic;">' . esc_html__( 'Please select a departure city to load the itinerary.', 'adventure-treks' ) . '</p>';
						}
						?>
					</div>
				</div>
			</div>

			<!-- Tab: Highlights -->
			<?php if ( ! empty( $trek['highlights'] ) ) : ?>
				<div id="at-f-tab-highlights" class="at-details-tab-panel">
					<div class="at-tab-inner-content">
						<h3 class="at-tab-headline"><?php esc_html_e( 'Trek Highlights', 'adventure-treks' ); ?></h3>
						<div class="at-highlights-list-box">
							<?php
							$items = explode( "\n", str_replace( "\r", '', $trek['highlights'] ) );
							echo '<ul>';
							foreach ( $items as $item ) {
								$item = trim( $item );
								if ( ! empty( $item ) ) {
									echo '<li><span class="dashicons dashicons-yes-alt" style="color:var(--at-primary-color, #137a7f); margin-right:8px; font-size:16px; width:16px; height:16px;"></span> ' . esc_html( $item ) . '</li>';
								}
							}
							echo '</ul>';
							?>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<!-- Tab: FAQs -->
			<?php if ( ! empty( $faq_items ) ) : ?>
				<div id="at-f-tab-faq" class="at-details-tab-panel">
					<div class="at-tab-inner-content">
						<h3 class="at-tab-headline"><?php esc_html_e( 'Frequently Asked Questions', 'adventure-treks' ); ?></h3>
						<div class="at-faq-accordion-container">
							<?php foreach ( $faq_items as $idx => $faq ) : ?>
								<div class="at-accordion-item">
									<button class="at-accordion-trigger" type="button">
										<?php echo esc_html( $faq['q'] ); ?>
										<span class="dashicons dashicons-arrow-down-alt2"></span>
									</button>
									<div class="at-accordion-content" style="max-height: 0;">
										<p><?php echo wp_kses_post( $faq['a'] ); ?></p>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<!-- Tab: Things to Carry -->
			<?php if ( ! empty( $trek['things_to_carry'] ) ) : ?>
				<div id="at-f-tab-carry" class="at-details-tab-panel">
					<div class="at-tab-inner-content">
						<h3 class="at-tab-headline"><?php esc_html_e( 'Essential Items & Packing Guidelines', 'adventure-treks' ); ?></h3>
						<div class="at-carry-items-grid">
							<?php
							$carry_items = explode( "\n", str_replace( "\r", '', $trek['things_to_carry'] ) );
							foreach ( $carry_items as $c_item ) {
								$c_item = trim( $c_item );
								if ( ! empty( $c_item ) ) {
									echo '<div class="at-carry-card">';
									echo '  <span class="dashicons dashicons-saved" style="color:var(--at-primary-color, #137a7f); font-size:18px; width:18px; height:18px; line-height:1;"></span>';
									echo '  <span>' . esc_html( $c_item ) . '</span>';
									echo '</div>';
								}
							}
							?>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<!-- Tab: Policies -->
			<?php if ( ! empty( $policies ) ) : ?>
				<div id="at-f-tab-policies" class="at-details-tab-panel">
					<div class="at-tab-inner-content">
						<h3 class="at-tab-headline"><?php esc_html_e( 'Policies, Guidelines & Disclaimers', 'adventure-treks' ); ?></h3>
						<div class="at-policies-block-layout">
							
							<?php if ( ! empty( $policies['cancellation'] ) ) : ?>
								<div class="at-policy-card">
									<h5><?php esc_html_e( 'Cancellation Policy', 'adventure-treks' ); ?></h5>
									<p><?php echo wp_kses_post( $policies['cancellation'] ); ?></p>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $policies['refund'] ) ) : ?>
								<div class="at-policy-card">
									<h5><?php esc_html_e( 'Refund & Postponement Policy', 'adventure-treks' ); ?></h5>
									<p><?php echo wp_kses_post( $policies['refund'] ); ?></p>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $policies['medical'] ) ) : ?>
								<div class="at-policy-card">
									<h5><?php esc_html_e( 'Medical & Fitness Disclaimer', 'adventure-treks' ); ?></h5>
									<p><?php echo wp_kses_post( $policies['medical'] ); ?></p>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $policies['terms'] ) ) : ?>
								<div class="at-policy-card">
									<h5><?php esc_html_e( 'Terms & Conditions', 'adventure-treks' ); ?></h5>
									<p><?php echo wp_kses_post( $policies['terms'] ); ?></p>
								</div>
							<?php endif; ?>

						</div>
					</div>
				</div>
			<?php endif; ?>

		</div>
	</div>

	<!-- 3. Photo Gallery Grid -->
	<?php if ( ! empty( $gallery ) ) : ?>
		<div class="at-trek-photo-gallery-section">
			<h4 class="at-gallery-section-title"><?php esc_html_e( 'Captured Moments & Visual Gallery', 'adventure-treks' ); ?></h4>
			<div class="at-gallery-masonry-grid">
				<?php
				$img_index = 0; foreach ( $gallery as $attachment_id ) :
					$img_url = wp_get_attachment_url( intval( $attachment_id ) );
					$img_alt = get_post_meta( intval( $attachment_id ), '_wp_attachment_image_alt', true );
					if ( ! $img_url ) {
						continue;
					}
					?>
					<div class="at-gallery-grid-card">
						<a href="<?php echo esc_url( $img_url ); ?>" class="at-gallery-lightbox-link" data-index="<?php echo esc_attr( $img_index ); ?>">
							<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $img_alt ); ?>" loading="lazy" />
						</a>
					</div>
					<?php
					++$img_index;
endforeach;
				?>
			</div>
		</div>
	<?php endif; ?>

</div>
