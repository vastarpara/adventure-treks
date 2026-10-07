<?php
/**
 * Trek Meta Box view template
 *
 * @package    TrekPilot
 * @subpackage TrekPilot/Admin/Views
 * @author     Nilesh Vastarpara
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="trekpilot-meta-tabs-wrapper">
	<!-- Tab Navigation -->
	<nav class="nav-tab-wrapper trekpilot-meta-tabs-nav">
		<a href="#trekpilot-tab-general" class="nav-tab nav-tab-active"><?php esc_html_e( 'General Info', 'trekpilot' ); ?></a>
		<a href="#trekpilot-tab-gallery" class="nav-tab"><?php esc_html_e( 'Gallery', 'trekpilot' ); ?></a>
		<a href="#trekpilot-tab-highlights" class="nav-tab"><?php esc_html_e( 'Inclusions, Exclusions & Packing', 'trekpilot' ); ?></a>
		<a href="#trekpilot-tab-faq" class="nav-tab"><?php esc_html_e( 'FAQ Repeater', 'trekpilot' ); ?></a>
		<a href="#trekpilot-tab-policies" class="nav-tab"><?php esc_html_e( 'Policies', 'trekpilot' ); ?></a>
	</nav>

	<!-- Tab Panels -->
	<div class="trekpilot-meta-tabs-content">
		
		<!-- TAB 1: GENERAL INFO -->
		<div id="trekpilot-tab-general" class="trekpilot-meta-tab-panel active">
			<div class="trekpilot-form-grid-2" style="margin-top: 15px;">
				<div class="trekpilot-form-row">
					<label for="trekpilot_difficulty"><?php esc_html_e( 'Difficulty Level', 'trekpilot' ); ?></label>
					<select name="trekpilot_difficulty" id="trekpilot_difficulty" style="width:100%;">
						<option value="Easy" <?php selected( $trek['difficulty'], 'Easy' ); ?>><?php esc_html_e( 'Easy', 'trekpilot' ); ?></option>
						<option value="Moderate" <?php selected( $trek['difficulty'], 'Moderate' ); ?>><?php esc_html_e( 'Moderate', 'trekpilot' ); ?></option>
						<option value="Difficult" <?php selected( $trek['difficulty'], 'Difficult' ); ?>><?php esc_html_e( 'Difficult', 'trekpilot' ); ?></option>
						<option value="Strenuous" <?php selected( $trek['difficulty'], 'Strenuous' ); ?>><?php esc_html_e( 'Strenuous', 'trekpilot' ); ?></option>
					</select>
				</div>
				<div class="trekpilot-form-row">
					<label for="trekpilot_duration"><?php esc_html_e( 'Duration (e.g. 5 Days / 4 Nights)', 'trekpilot' ); ?></label>
					<input type="text" name="trekpilot_duration" id="trekpilot_duration" data-trekpilot-plain-text="1" value="<?php echo esc_attr( $trek['duration'] ); ?>" style="width:100%;" placeholder="<?php esc_attr_e( 'e.g. 5 Days / 4 Nights', 'trekpilot' ); ?>" />
				</div>
				<div class="trekpilot-form-row">
					<label for="trekpilot_altitude"><?php esc_html_e( 'Max Altitude (e.g. 12,500 ft)', 'trekpilot' ); ?></label>
					<input type="text" name="trekpilot_altitude" id="trekpilot_altitude" data-trekpilot-plain-text="1" value="<?php echo esc_attr( $trek['altitude'] ); ?>" style="width:100%;" placeholder="<?php esc_attr_e( 'e.g. 12,500 ft', 'trekpilot' ); ?>" />
				</div>
				<div class="trekpilot-form-row">
					<label for="trekpilot_region"><?php esc_html_e( 'Region / State', 'trekpilot' ); ?></label>
					<input type="text" name="trekpilot_region" id="trekpilot_region" data-trekpilot-plain-text="1" value="<?php echo esc_attr( $trek['region'] ); ?>" style="width:100%;" placeholder="<?php esc_attr_e( 'e.g. Himachal Pradesh', 'trekpilot' ); ?>" />
				</div>
				<div class="trekpilot-form-row">
					<label for="trekpilot_season"><?php esc_html_e( 'Best Season', 'trekpilot' ); ?></label>
					<input type="text" name="trekpilot_season" id="trekpilot_season" data-trekpilot-plain-text="1" value="<?php echo esc_attr( $trek['season'] ); ?>" style="width:100%;" placeholder="<?php esc_attr_e( 'e.g. May to October', 'trekpilot' ); ?>" />
				</div>
				<div class="trekpilot-form-row">
					<label for="trekpilot_distance"><?php esc_html_e( 'Trekking Distance (e.g. 26 km)', 'trekpilot' ); ?></label>
					<input type="text" name="trekpilot_distance" id="trekpilot_distance" data-trekpilot-plain-text="1" value="<?php echo esc_attr( $trek['distance'] ); ?>" style="width:100%;" placeholder="<?php esc_attr_e( 'e.g. 26 km', 'trekpilot' ); ?>" />
				</div>
				<div class="trekpilot-form-row">
					<label for="trekpilot_fitness_level"><?php esc_html_e( 'Fitness Level Required', 'trekpilot' ); ?></label>
					<?php
					$trekpilot_fitness_options = array( 'Beginner', 'Moderate', 'Good', 'High', 'Very High' );
					// Keep a previously saved free-text value selectable instead of silently dropping it.
					if ( '' !== $trek['fitness_level'] && ! in_array( $trek['fitness_level'], $trekpilot_fitness_options, true ) ) {
						$trekpilot_fitness_options[] = $trek['fitness_level'];
					}
					?>
					<select name="trekpilot_fitness_level" id="trekpilot_fitness_level" style="width:100%;">
						<option value=""><?php esc_html_e( '— Select —', 'trekpilot' ); ?></option>
						<?php foreach ( $trekpilot_fitness_options as $trekpilot_fitness_option ) : ?>
							<option value="<?php echo esc_attr( $trekpilot_fitness_option ); ?>" <?php selected( $trek['fitness_level'], $trekpilot_fitness_option ); ?>><?php echo esc_html( $trekpilot_fitness_option ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="trekpilot-form-row">
					<label for="trekpilot_age_limit"><?php esc_html_e( 'Age Limit (e.g. 10 - 55 Years)', 'trekpilot' ); ?></label>
					<input type="text" name="trekpilot_age_limit" id="trekpilot_age_limit" data-trekpilot-plain-text="plus" value="<?php echo esc_attr( $trek['age_limit'] ); ?>" style="width:100%;" placeholder="<?php esc_attr_e( 'e.g. 10 to 60 Years', 'trekpilot' ); ?>" />
				</div>
				<div class="trekpilot-form-row">
					<label for="trekpilot_adult_age"><?php esc_html_e( 'Adults Age (booking widget, e.g. 12+)', 'trekpilot' ); ?></label>
					<input type="text" name="trekpilot_adult_age" id="trekpilot_adult_age" data-trekpilot-plain-text="plus" value="<?php echo esc_attr( get_post_meta( $post->ID, '_trekpilot_adult_age', true ) ); ?>" style="width:100%;" placeholder="<?php esc_attr_e( '12+', 'trekpilot' ); ?>" />
				</div>
				<div class="trekpilot-form-row">
					<label for="trekpilot_child_age"><?php esc_html_e( 'Children Age (booking widget, e.g. 5-11)', 'trekpilot' ); ?></label>
					<input type="text" name="trekpilot_child_age" id="trekpilot_child_age" data-trekpilot-plain-text="plus" value="<?php echo esc_attr( get_post_meta( $post->ID, '_trekpilot_child_age', true ) ); ?>" style="width:100%;" placeholder="<?php esc_attr_e( '5-11', 'trekpilot' ); ?>" />
				</div>
				<div class="trekpilot-form-row">
					<label for="trekpilot_group_size"><?php esc_html_e( 'Ideal Group Size', 'trekpilot' ); ?></label>
					<input type="text" name="trekpilot_group_size" id="trekpilot_group_size" data-trekpilot-plain-text="1" value="<?php echo esc_attr( $trek['group_size'] ); ?>" style="width:100%;" placeholder="<?php esc_attr_e( 'e.g. 12 to 20 Trekkers', 'trekpilot' ); ?>" />
				</div>
			</div>
		</div>

		<!-- TAB 2: GALLERY -->
		<div id="trekpilot-tab-gallery" class="trekpilot-meta-tab-panel">
			<div class="trekpilot-gallery-container">
				<input type="hidden" name="trekpilot_gallery" id="trekpilot_gallery_ids" value="<?php echo esc_attr( $trek['gallery'] ); ?>" />
				<div class="trekpilot-gallery-thumbs" id="trekpilot_gallery_thumbs_wrapper">
					<?php
					if ( ! empty( $trek['gallery'] ) ) {
						$gallery_ids = explode( ',', $trek['gallery'] );
						foreach ( $gallery_ids as $img_id ) {
							$img_src = wp_get_attachment_image_src( $img_id, 'thumbnail' );
							if ( $img_src ) {
								echo '<div class="trekpilot-gallery-thumb-item" data-id="' . esc_attr( $img_id ) . '">';
								echo '<img src="' . esc_url( $img_src[0] ) . '" alt="" />';
								echo '<a href="#" class="trekpilot-gallery-remove-btn" title="' . esc_attr__( 'Remove', 'trekpilot' ) . '">&times;</a>';
								echo '</div>';
							}
						}
					}
					?>
				</div>
				<button type="button" class="button button-primary button-large" id="trekpilot_select_gallery_btn">
					<?php esc_html_e( 'Add / Manage Gallery Images', 'trekpilot' ); ?>
				</button>
				<p class="description"><?php esc_html_e( 'Select multiple images from the media library to display in the trek slider.', 'trekpilot' ); ?></p>
			</div>
		</div>

		<!-- TAB 3: HIGHLIGHTS & PACKING -->
		<div id="trekpilot-tab-highlights" class="trekpilot-meta-tab-panel">
			<table class="form-table">
				<tr>
					<th><label for="trekpilot_highlights"><?php esc_html_e( 'Inclusions / Key Highlights (One per line)', 'trekpilot' ); ?></label></th>
					<td>
						<textarea name="trekpilot_highlights" id="trekpilot_highlights" rows="8" class="large-text" placeholder="<?php esc_attr_e( "Trek through lush green pine valleys\nExperience camping under starry sky\nStunning views of Mt. Trishul", 'trekpilot' ); ?>"><?php echo esc_textarea( $trek['highlights'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Shown on the front-end as "Inclusions", with a green checkmark next to each line.', 'trekpilot' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="trekpilot_exclusions"><?php esc_html_e( 'Exclusions (One per line)', 'trekpilot' ); ?></label></th>
					<td>
						<textarea name="trekpilot_exclusions" id="trekpilot_exclusions" rows="8" class="large-text" placeholder="<?php esc_attr_e( "Personal trekking gear/equipment rental\nTravel insurance\nMeals not mentioned in the itinerary\nAny costs due to natural calamities or delays", 'trekpilot' ); ?>"><?php echo esc_textarea( $trek['exclusions'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Shown next to Inclusions on the front-end, with a red cross next to each line.', 'trekpilot' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="trekpilot_things_to_carry"><?php esc_html_e( 'Things to Carry Checklist (One per line)', 'trekpilot' ); ?></label></th>
					<td>
						<textarea name="trekpilot_things_to_carry" id="trekpilot_things_to_carry" rows="8" class="large-text" placeholder="<?php esc_attr_e( "Trekking shoes with good grip\nWarm jacket / fleece\nWater bottles 2L\nHeadlamp / Torch", 'trekpilot' ); ?>"><?php echo esc_textarea( $trek['things_to_carry'] ); ?></textarea>
					</td>
				</tr>
			</table>
		</div>

		<!-- TAB 4: FAQ REPEATER -->
		<div id="trekpilot-tab-faq" class="trekpilot-meta-tab-panel">
			<div id="trekpilot_faq_repeater_list">
				<?php if ( ! empty( $faq_items ) ) : ?>
					<?php foreach ( $faq_items as $index => $item ) : ?>
						<div class="trekpilot-faq-repeater-row" data-index="<?php echo esc_attr( $index ); ?>">
							<span class="trekpilot-drag-handle" style="cursor: move;">☰</span>
							<div class="trekpilot-faq-row-fields">
								<input type="text" name="trekpilot_faq[<?php echo esc_attr( $index ); ?>][q]" value="<?php echo esc_attr( $item['q'] ); ?>" placeholder="<?php esc_attr_e( 'Question', 'trekpilot' ); ?>" aria-label="<?php esc_attr_e( 'FAQ question', 'trekpilot' ); ?>" class="large-text" />
								<textarea name="trekpilot_faq[<?php echo esc_attr( $index ); ?>][a]" rows="3" placeholder="<?php esc_attr_e( 'Answer', 'trekpilot' ); ?>" aria-label="<?php esc_attr_e( 'FAQ answer', 'trekpilot' ); ?>" class="large-text"><?php echo esc_textarea( $item['a'] ); ?></textarea>
							</div>
							<button type="button" class="button trekpilot-remove-faq-row-btn"><?php esc_html_e( 'Remove', 'trekpilot' ); ?></button>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<button type="button" class="button button-primary" id="trekpilot_add_faq_row_btn">
				<?php esc_html_e( 'Add New FAQ Item', 'trekpilot' ); ?>
			</button>
		</div>

		<!-- TAB 5: POLICIES -->
		<div id="trekpilot-tab-policies" class="trekpilot-meta-tab-panel">
			<p class="description"><?php esc_html_e( 'Leave a policy empty and it will not be shown on the trek page.', 'trekpilot' ); ?></p>
			<table class="form-table">
				<?php foreach ( \TrekPilot\Admin\Controllers\TrekMetaBoxController::get_policy_types() as $trekpilot_policy_key => $trekpilot_policy ) : ?>
					<tr>
						<th><label for="trekpilot_policy_<?php echo esc_attr( $trekpilot_policy_key ); ?>"><?php echo esc_html( $trekpilot_policy['label'] ); ?></label></th>
						<td>
							<?php
							wp_editor(
								isset( $policies[ $trekpilot_policy_key ] ) ? $policies[ $trekpilot_policy_key ] : '',
								'trekpilot_policy_' . $trekpilot_policy_key,
								array(
									'textarea_name' => 'trekpilot_policy_' . $trekpilot_policy_key,
									'textarea_rows' => 8,
									'media_buttons' => false,
									'teeny'         => true,
								)
							);
							?>
							<p class="description"><?php esc_html_e( 'Shown to visitors in a popup below the trek details.', 'trekpilot' ); ?></p>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		</div>

	</div>
</div>
