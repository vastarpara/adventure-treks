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
			<?php if ( ! empty( $trek['highlights'] ) || ! empty( $trek['exclusions'] ) ) : ?>
				<li><a href="#at-f-tab-inclusions"><?php esc_html_e( 'Inclusions & Exclusions', 'adventure-treks' ); ?></a></li>
			<?php endif; ?>
			<?php if ( ! empty( $faq_items ) ) : ?>
				<li><a href="#at-f-tab-faq"><?php esc_html_e( 'FAQs', 'adventure-treks' ); ?></a></li>
			<?php endif; ?>
			<?php if ( ! empty( $trek['things_to_carry'] ) ) : ?>
				<li><a href="#at-f-tab-carry"><?php esc_html_e( 'Things To Carry', 'adventure-treks' ); ?></a></li>
			<?php endif; ?>
		</ul>

		<div class="at-details-tabs-content">

			<!-- Section: Itinerary -->
			<div id="at-f-tab-itinerary" class="at-details-tab-panel active">
				<div class="at-tab-inner-content">
					<h3 class="at-tab-headline"><?php esc_html_e( 'Detailed Itinerary', 'adventure-treks' ); ?></h3>
					<div id="trek_itinerary_container">
						<?php
						if ( $default_city_id ) {
							$at_itinerary_shortcode = '[adventure_itinerary city_id="' . intval( $default_city_id ) . '"';
							if ( ! empty( $default_departure_date ) ) {
								$at_itinerary_shortcode .= ' date="' . esc_attr( $default_departure_date ) . '"';
							}
							$at_itinerary_shortcode .= ']';
							echo do_shortcode( $at_itinerary_shortcode );
						} else {
							echo '<p style="color:#666; font-style:italic;">' . esc_html__( 'No itinerary found. Please contact the admin for details.', 'adventure-treks' ) . '</p>';
						}
						?>
					</div>
				</div>
			</div>

			<!-- Section: Inclusions & Exclusions -->
			<?php if ( ! empty( $trek['highlights'] ) || ! empty( $trek['exclusions'] ) ) : ?>
				<div id="at-f-tab-inclusions" class="at-details-tab-panel">
					<div class="at-tab-inner-content">
						<h3 class="at-tab-headline"><?php esc_html_e( 'Inclusions & Exclusions', 'adventure-treks' ); ?></h3>
						<div class="at-incl-excl-grid">

							<?php if ( ! empty( $trek['highlights'] ) ) : ?>
								<div class="at-highlights-list-box">
									<h4 class="at-incl-excl-heading included"><?php esc_html_e( "What's Included", 'adventure-treks' ); ?></h4>
									<ul>
										<?php
										$at_incl_items = explode( "\n", str_replace( "\r", '', $trek['highlights'] ) );
										foreach ( $at_incl_items as $at_incl_item ) {
											$at_incl_item = trim( $at_incl_item );
											if ( ! empty( $at_incl_item ) ) {
												echo '<li><span class="dashicons dashicons-yes-alt"></span> ' . esc_html( $at_incl_item ) . '</li>';
											}
										}
										?>
									</ul>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $trek['exclusions'] ) ) : ?>
								<div class="at-exclusions-list-box">
									<h4 class="at-incl-excl-heading excluded"><?php esc_html_e( "What's Excluded", 'adventure-treks' ); ?></h4>
									<ul>
										<?php
										$at_excl_items = explode( "\n", str_replace( "\r", '', $trek['exclusions'] ) );
										foreach ( $at_excl_items as $at_excl_item ) {
											$at_excl_item = trim( $at_excl_item );
											if ( ! empty( $at_excl_item ) ) {
												echo '<li><span class="dashicons dashicons-dismiss"></span> ' . esc_html( $at_excl_item ) . '</li>';
											}
										}
										?>
									</ul>
								</div>
							<?php endif; ?>

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

			<!-- Section: Things to Carry -->
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

		</div>
	</div>

	<?php
	// Only policies that have content are listed; with none, the whole section is hidden.
	$adventure_treks_policy_types    = \AdventureTreks\Admin\Controllers\TrekMetaBoxController::get_policy_types();
	$adventure_treks_active_policies = array();
	foreach ( $adventure_treks_policy_types as $adventure_treks_policy_key => $adventure_treks_policy ) {
		if ( ! empty( $policies[ $adventure_treks_policy_key ] ) && '' !== trim( wp_strip_all_tags( $policies[ $adventure_treks_policy_key ] ) ) ) {
			$adventure_treks_active_policies[ $adventure_treks_policy_key ] = $adventure_treks_policy;
		}
	}
	?>

	<!-- Quick Links & Policies - always visible below the tabs, not tied to any single tab's content -->
	<?php if ( ! empty( $adventure_treks_active_policies ) ) : ?>
		<div class="at-section-card at-quicklinks-card">
			<h2><?php esc_html_e( 'Quick Links & Policies', 'adventure-treks' ); ?></h2>
			<div class="at-quicklinks-row">
				<?php foreach ( $adventure_treks_active_policies as $adventure_treks_policy_key => $adventure_treks_policy ) : ?>
					<button type="button" class="at-quicklink-btn" data-popup-target="at-f-popup-<?php echo esc_attr( $adventure_treks_policy_key ); ?>">
						<span class="at-quicklink-icon gray"><span class="dashicons <?php echo esc_attr( $adventure_treks_policy['icon'] ); ?>"></span></span>
						<?php echo esc_html( $adventure_treks_policy['label'] ); ?>
					</button>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Policy popups -->
		<?php foreach ( $adventure_treks_active_policies as $adventure_treks_policy_key => $adventure_treks_policy ) : ?>
			<div class="at-policy-modal" id="at-f-popup-<?php echo esc_attr( $adventure_treks_policy_key ); ?>">
				<div class="at-policy-modal-overlay" data-popup-close></div>
				<div class="at-policy-modal-box">
					<div class="at-policy-modal-header">
						<h3><?php echo esc_html( $adventure_treks_policy['label'] ); ?></h3>
						<span class="at-policy-modal-close" data-popup-close>&times;</span>
					</div>
					<div class="at-policy-modal-body">
						<?php echo wp_kses_post( wpautop( $policies[ $adventure_treks_policy_key ] ) ); ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
