<?php
/**
 * View template for rendering Trek details frontend UI.
 *
 * @package    TrekPilot
 * @subpackage TrekPilot/Public/Views
 * @author     Nilesh Vastarpara
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="trekpilot-trek-details-container">
	
	<!-- 1. Specifications Icon Grid -->
	<div class="trekpilot-specs-header-grid">
		
		<?php if ( ! empty( $trek['difficulty'] ) ) : ?>
			<div class="trekpilot-spec-item">
				<span class="dashicons dashicons-performance"></span>
				<div class="trekpilot-spec-info">
					<span class="trekpilot-spec-label"><?php esc_html_e( 'Difficulty', 'trekpilot' ); ?></span>
					<strong class="trekpilot-spec-value"><?php echo esc_html( $trek['difficulty'] ); ?></strong>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $trek['duration'] ) ) : ?>
			<div class="trekpilot-spec-item">
				<span class="dashicons dashicons-clock"></span>
				<div class="trekpilot-spec-info">
					<span class="trekpilot-spec-label"><?php esc_html_e( 'Duration', 'trekpilot' ); ?></span>
					<strong class="trekpilot-spec-value"><?php echo esc_html( $trek['duration'] ); ?></strong>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $trek['altitude'] ) ) : ?>
			<div class="trekpilot-spec-item">
				<span class="dashicons dashicons-arrow-up-alt"></span>
				<div class="trekpilot-spec-info">
					<span class="trekpilot-spec-label"><?php esc_html_e( 'Max Altitude', 'trekpilot' ); ?></span>
					<strong class="trekpilot-spec-value"><?php echo esc_html( $trek['altitude'] ); ?></strong>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $trek['region'] ) ) : ?>
			<div class="trekpilot-spec-item">
				<span class="dashicons dashicons-location"></span>
				<div class="trekpilot-spec-info">
					<span class="trekpilot-spec-label"><?php esc_html_e( 'Region', 'trekpilot' ); ?></span>
					<strong class="trekpilot-spec-value"><?php echo esc_html( $trek['region'] ); ?></strong>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $trek['season'] ) ) : ?>
			<div class="trekpilot-spec-item">
				<span class="dashicons dashicons-calendar-alt"></span>
				<div class="trekpilot-spec-info">
					<span class="trekpilot-spec-label"><?php esc_html_e( 'Best Season', 'trekpilot' ); ?></span>
					<strong class="trekpilot-spec-value"><?php echo esc_html( $trek['season'] ); ?></strong>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $trek['distance'] ) ) : ?>
			<div class="trekpilot-spec-item">
				<span class="dashicons dashicons-location-alt"></span>
				<div class="trekpilot-spec-info">
					<span class="trekpilot-spec-label"><?php esc_html_e( 'Trek Distance', 'trekpilot' ); ?></span>
					<strong class="trekpilot-spec-value"><?php echo esc_html( $trek['distance'] ); ?></strong>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $trek['fitness_level'] ) ) : ?>
			<div class="trekpilot-spec-item">
				<span class="dashicons dashicons-heart"></span>
				<div class="trekpilot-spec-info">
					<span class="trekpilot-spec-label"><?php esc_html_e( 'Fitness Level', 'trekpilot' ); ?></span>
					<strong class="trekpilot-spec-value"><?php echo esc_html( $trek['fitness_level'] ); ?></strong>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $trek['age_limit'] ) ) : ?>
			<div class="trekpilot-spec-item">
				<span class="dashicons dashicons-admin-users"></span>
				<div class="trekpilot-spec-info">
					<span class="trekpilot-spec-label"><?php esc_html_e( 'Age Limit', 'trekpilot' ); ?></span>
					<strong class="trekpilot-spec-value"><?php echo esc_html( $trek['age_limit'] ); ?></strong>
				</div>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $trek['group_size'] ) ) : ?>
			<div class="trekpilot-spec-item">
				<span class="dashicons dashicons-groups"></span>
				<div class="trekpilot-spec-info">
					<span class="trekpilot-spec-label"><?php esc_html_e( 'Group Size', 'trekpilot' ); ?></span>
					<strong class="trekpilot-spec-value"><?php echo esc_html( $trek['group_size'] ); ?></strong>
				</div>
			</div>
		<?php endif; ?>

	</div>

	<!-- 2. Content Sections in Tab Layout -->
	<div class="trekpilot-details-tabs-wrapper">
		<ul class="trekpilot-details-tabs-nav">
			<li class="active"><a href="#trekpilot-f-tab-itinerary"><?php esc_html_e( 'Itinerary', 'trekpilot' ); ?></a></li>
			<?php if ( ! empty( $trek['highlights'] ) || ! empty( $trek['exclusions'] ) ) : ?>
				<li><a href="#trekpilot-f-tab-inclusions"><?php esc_html_e( 'Inclusions & Exclusions', 'trekpilot' ); ?></a></li>
			<?php endif; ?>
			<?php if ( ! empty( $faq_items ) ) : ?>
				<li><a href="#trekpilot-f-tab-faq"><?php esc_html_e( 'FAQs', 'trekpilot' ); ?></a></li>
			<?php endif; ?>
			<?php if ( ! empty( $trek['things_to_carry'] ) ) : ?>
				<li><a href="#trekpilot-f-tab-carry"><?php esc_html_e( 'Things To Carry', 'trekpilot' ); ?></a></li>
			<?php endif; ?>
		</ul>

		<div class="trekpilot-details-tabs-content">

			<!-- Section: Itinerary -->
			<div id="trekpilot-f-tab-itinerary" class="trekpilot-details-tab-panel active">
				<div class="trekpilot-tab-inner-content">
					<h3 class="trekpilot-tab-headline"><?php esc_html_e( 'Detailed Itinerary', 'trekpilot' ); ?></h3>
					<div id="trek_itinerary_container">
						<?php
						if ( $default_city_id ) {
							$trekpilot_itinerary_shortcode = '[trekpilot_itinerary city_id="' . intval( $default_city_id ) . '"';
							if ( ! empty( $default_departure_date ) ) {
								$trekpilot_itinerary_shortcode .= ' date="' . esc_attr( $default_departure_date ) . '"';
							}
							$trekpilot_itinerary_shortcode .= ']';
							echo do_shortcode( $trekpilot_itinerary_shortcode );
						} else {
							echo '<p style="color:#666; font-style:italic;">' . esc_html__( 'No itinerary found. Please contact the admin for details.', 'trekpilot' ) . '</p>';
						}
						?>
					</div>
				</div>
			</div>

			<!-- Section: Inclusions & Exclusions -->
			<?php if ( ! empty( $trek['highlights'] ) || ! empty( $trek['exclusions'] ) ) : ?>
				<div id="trekpilot-f-tab-inclusions" class="trekpilot-details-tab-panel">
					<div class="trekpilot-tab-inner-content">
						<h3 class="trekpilot-tab-headline"><?php esc_html_e( 'Inclusions & Exclusions', 'trekpilot' ); ?></h3>
						<div class="trekpilot-incl-excl-grid">

							<?php if ( ! empty( $trek['highlights'] ) ) : ?>
								<div class="trekpilot-highlights-list-box">
									<h4 class="trekpilot-incl-excl-heading included"><?php esc_html_e( "What's Included", 'trekpilot' ); ?></h4>
									<ul>
										<?php
										$trekpilot_incl_items = explode( "\n", str_replace( "\r", '', $trek['highlights'] ) );
										foreach ( $trekpilot_incl_items as $trekpilot_incl_item ) {
											$trekpilot_incl_item = trim( $trekpilot_incl_item );
											if ( ! empty( $trekpilot_incl_item ) ) {
												echo '<li><span class="dashicons dashicons-yes-alt"></span> ' . esc_html( $trekpilot_incl_item ) . '</li>';
											}
										}
										?>
									</ul>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $trek['exclusions'] ) ) : ?>
								<div class="trekpilot-exclusions-list-box">
									<h4 class="trekpilot-incl-excl-heading excluded"><?php esc_html_e( "What's Excluded", 'trekpilot' ); ?></h4>
									<ul>
										<?php
										$trekpilot_excl_items = explode( "\n", str_replace( "\r", '', $trek['exclusions'] ) );
										foreach ( $trekpilot_excl_items as $trekpilot_excl_item ) {
											$trekpilot_excl_item = trim( $trekpilot_excl_item );
											if ( ! empty( $trekpilot_excl_item ) ) {
												echo '<li><span class="dashicons dashicons-dismiss"></span> ' . esc_html( $trekpilot_excl_item ) . '</li>';
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
				<div id="trekpilot-f-tab-faq" class="trekpilot-details-tab-panel">
					<div class="trekpilot-tab-inner-content">
						<h3 class="trekpilot-tab-headline"><?php esc_html_e( 'Frequently Asked Questions', 'trekpilot' ); ?></h3>
						<div class="trekpilot-faq-accordion-container">
							<?php foreach ( $faq_items as $idx => $faq ) : ?>
								<div class="trekpilot-accordion-item">
									<button class="trekpilot-accordion-trigger" type="button">
										<?php echo esc_html( $faq['q'] ); ?>
										<span class="dashicons dashicons-arrow-down-alt2"></span>
									</button>
									<div class="trekpilot-accordion-content" style="max-height: 0;">
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
				<div id="trekpilot-f-tab-carry" class="trekpilot-details-tab-panel">
					<div class="trekpilot-tab-inner-content">
						<h3 class="trekpilot-tab-headline"><?php esc_html_e( 'Essential Items & Packing Guidelines', 'trekpilot' ); ?></h3>
						<div class="trekpilot-carry-items-grid">
							<?php
							$carry_items = explode( "\n", str_replace( "\r", '', $trek['things_to_carry'] ) );
							foreach ( $carry_items as $c_item ) {
								$c_item = trim( $c_item );
								if ( ! empty( $c_item ) ) {
									echo '<div class="trekpilot-carry-card">';
									echo '  <span class="dashicons dashicons-saved" style="color:var(--trekpilot-primary-color, #137a7f); font-size:18px; width:18px; height:18px; line-height:1;"></span>';
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
	$trekpilot_policy_types    = \TrekPilot\Admin\Controllers\TrekMetaBoxController::get_policy_types();
	$trekpilot_active_policies = array();
	foreach ( $trekpilot_policy_types as $trekpilot_policy_key => $trekpilot_policy ) {
		if ( ! empty( $policies[ $trekpilot_policy_key ] ) && '' !== trim( wp_strip_all_tags( $policies[ $trekpilot_policy_key ] ) ) ) {
			$trekpilot_active_policies[ $trekpilot_policy_key ] = $trekpilot_policy;
		}
	}
	?>

	<!-- Quick Links & Policies - always visible below the tabs, not tied to any single tab's content -->
	<?php if ( ! empty( $trekpilot_active_policies ) ) : ?>
		<div class="trekpilot-section-card trekpilot-quicklinks-card">
			<h2><?php esc_html_e( 'Quick Links & Policies', 'trekpilot' ); ?></h2>
			<div class="trekpilot-quicklinks-row">
				<?php foreach ( $trekpilot_active_policies as $trekpilot_policy_key => $trekpilot_policy ) : ?>
					<button type="button" class="trekpilot-quicklink-btn" data-popup-target="trekpilot-f-popup-<?php echo esc_attr( $trekpilot_policy_key ); ?>">
						<span class="trekpilot-quicklink-icon gray"><span class="dashicons <?php echo esc_attr( $trekpilot_policy['icon'] ); ?>"></span></span>
						<?php echo esc_html( $trekpilot_policy['label'] ); ?>
					</button>
				<?php endforeach; ?>
			</div>
		</div>

		<!-- Policy popups -->
		<?php foreach ( $trekpilot_active_policies as $trekpilot_policy_key => $trekpilot_policy ) : ?>
			<div class="trekpilot-policy-modal" id="trekpilot-f-popup-<?php echo esc_attr( $trekpilot_policy_key ); ?>">
				<div class="trekpilot-policy-modal-overlay" data-popup-close></div>
				<div class="trekpilot-policy-modal-box">
					<div class="trekpilot-policy-modal-header">
						<h3><?php echo esc_html( $trekpilot_policy['label'] ); ?></h3>
						<span class="trekpilot-policy-modal-close" data-popup-close>&times;</span>
					</div>
					<div class="trekpilot-policy-modal-body">
						<?php echo wp_kses_post( wpautop( $policies[ $trekpilot_policy_key ] ) ); ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
</div>
