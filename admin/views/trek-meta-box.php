<?php
/**
 * Trek Meta Box view template
 *
 * @package    AdventureTreks
 * @subpackage AdventureTreks/Admin/Views
 * @author     Nilesh Vastarpara
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="at-meta-tabs-wrapper">
	<!-- Tab Navigation -->
	<nav class="nav-tab-wrapper at-meta-tabs-nav">
		<a href="#at-tab-general" class="nav-tab nav-tab-active"><?php esc_html_e( 'General Info', 'adventure-treks' ); ?></a>
		<a href="#at-tab-gallery" class="nav-tab"><?php esc_html_e( 'Gallery', 'adventure-treks' ); ?></a>
		<a href="#at-tab-highlights" class="nav-tab"><?php esc_html_e( 'Inclusions, Exclusions & Packing', 'adventure-treks' ); ?></a>
		<a href="#at-tab-faq" class="nav-tab"><?php esc_html_e( 'FAQ Repeater', 'adventure-treks' ); ?></a>
		<a href="#at-tab-policies" class="nav-tab"><?php esc_html_e( 'Policies', 'adventure-treks' ); ?></a>
	</nav>

	<!-- Tab Panels -->
	<div class="at-meta-tabs-content">
		
		<!-- TAB 1: GENERAL INFO -->
		<div id="at-tab-general" class="at-meta-tab-panel active">
			<div class="at-form-grid-2" style="margin-top: 15px;">
				<div class="at-form-row">
					<label for="at_difficulty"><?php esc_html_e( 'Difficulty Level', 'adventure-treks' ); ?></label>
					<select name="at_difficulty" id="at_difficulty" style="width:100%;">
						<option value="Easy" <?php selected( $trek['difficulty'], 'Easy' ); ?>><?php esc_html_e( 'Easy', 'adventure-treks' ); ?></option>
						<option value="Moderate" <?php selected( $trek['difficulty'], 'Moderate' ); ?>><?php esc_html_e( 'Moderate', 'adventure-treks' ); ?></option>
						<option value="Difficult" <?php selected( $trek['difficulty'], 'Difficult' ); ?>><?php esc_html_e( 'Difficult', 'adventure-treks' ); ?></option>
						<option value="Strenuous" <?php selected( $trek['difficulty'], 'Strenuous' ); ?>><?php esc_html_e( 'Strenuous', 'adventure-treks' ); ?></option>
					</select>
				</div>
				<div class="at-form-row">
					<label for="at_duration"><?php esc_html_e( 'Duration (e.g. 5 Days / 4 Nights)', 'adventure-treks' ); ?></label>
					<input type="text" name="at_duration" id="at_duration" data-at-plain-text="1" value="<?php echo esc_attr( $trek['duration'] ); ?>" style="width:100%;" placeholder="<?php esc_attr_e( 'e.g. 5 Days / 4 Nights', 'adventure-treks' ); ?>" />
				</div>
				<div class="at-form-row">
					<label for="at_altitude"><?php esc_html_e( 'Max Altitude (e.g. 12,500 ft)', 'adventure-treks' ); ?></label>
					<input type="text" name="at_altitude" id="at_altitude" data-at-plain-text="1" value="<?php echo esc_attr( $trek['altitude'] ); ?>" style="width:100%;" placeholder="<?php esc_attr_e( 'e.g. 12,500 ft', 'adventure-treks' ); ?>" />
				</div>
				<div class="at-form-row">
					<label for="at_region"><?php esc_html_e( 'Region / State', 'adventure-treks' ); ?></label>
					<input type="text" name="at_region" id="at_region" data-at-plain-text="1" value="<?php echo esc_attr( $trek['region'] ); ?>" style="width:100%;" placeholder="<?php esc_attr_e( 'e.g. Himachal Pradesh', 'adventure-treks' ); ?>" />
				</div>
				<div class="at-form-row">
					<label for="at_season"><?php esc_html_e( 'Best Season', 'adventure-treks' ); ?></label>
					<input type="text" name="at_season" id="at_season" data-at-plain-text="1" value="<?php echo esc_attr( $trek['season'] ); ?>" style="width:100%;" placeholder="<?php esc_attr_e( 'e.g. May to October', 'adventure-treks' ); ?>" />
				</div>
				<div class="at-form-row">
					<label for="at_distance"><?php esc_html_e( 'Trekking Distance (e.g. 26 km)', 'adventure-treks' ); ?></label>
					<input type="text" name="at_distance" id="at_distance" data-at-plain-text="1" value="<?php echo esc_attr( $trek['distance'] ); ?>" style="width:100%;" placeholder="<?php esc_attr_e( 'e.g. 26 km', 'adventure-treks' ); ?>" />
				</div>
				<div class="at-form-row">
					<label for="at_fitness_level"><?php esc_html_e( 'Fitness Level Required', 'adventure-treks' ); ?></label>
					<?php
					$at_fitness_options = array( 'Beginner', 'Moderate', 'Good', 'High', 'Very High' );
					// Keep a previously saved free-text value selectable instead of silently dropping it.
					if ( '' !== $trek['fitness_level'] && ! in_array( $trek['fitness_level'], $at_fitness_options, true ) ) {
						$at_fitness_options[] = $trek['fitness_level'];
					}
					?>
					<select name="at_fitness_level" id="at_fitness_level" style="width:100%;">
						<option value=""><?php esc_html_e( '— Select —', 'adventure-treks' ); ?></option>
						<?php foreach ( $at_fitness_options as $at_fitness_option ) : ?>
							<option value="<?php echo esc_attr( $at_fitness_option ); ?>" <?php selected( $trek['fitness_level'], $at_fitness_option ); ?>><?php echo esc_html( $at_fitness_option ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="at-form-row">
					<label for="at_age_limit"><?php esc_html_e( 'Age Limit (e.g. 10 - 55 Years)', 'adventure-treks' ); ?></label>
					<input type="text" name="at_age_limit" id="at_age_limit" data-at-plain-text="plus" value="<?php echo esc_attr( $trek['age_limit'] ); ?>" style="width:100%;" placeholder="<?php esc_attr_e( 'e.g. 10 to 60 Years', 'adventure-treks' ); ?>" />
				</div>
				<div class="at-form-row">
					<label for="at_adult_age"><?php esc_html_e( 'Adults Age (booking widget, e.g. 12+)', 'adventure-treks' ); ?></label>
					<input type="text" name="at_adult_age" id="at_adult_age" data-at-plain-text="plus" value="<?php echo esc_attr( get_post_meta( $post->ID, '_at_adult_age', true ) ); ?>" style="width:100%;" placeholder="<?php esc_attr_e( '12+', 'adventure-treks' ); ?>" />
				</div>
				<div class="at-form-row">
					<label for="at_child_age"><?php esc_html_e( 'Children Age (booking widget, e.g. 5-11)', 'adventure-treks' ); ?></label>
					<input type="text" name="at_child_age" id="at_child_age" data-at-plain-text="plus" value="<?php echo esc_attr( get_post_meta( $post->ID, '_at_child_age', true ) ); ?>" style="width:100%;" placeholder="<?php esc_attr_e( '5-11', 'adventure-treks' ); ?>" />
				</div>
				<div class="at-form-row">
					<label for="at_group_size"><?php esc_html_e( 'Ideal Group Size', 'adventure-treks' ); ?></label>
					<input type="text" name="at_group_size" id="at_group_size" data-at-plain-text="1" value="<?php echo esc_attr( $trek['group_size'] ); ?>" style="width:100%;" placeholder="<?php esc_attr_e( 'e.g. 12 to 20 Trekkers', 'adventure-treks' ); ?>" />
				</div>
			</div>
		</div>

		<!-- TAB 2: GALLERY -->
		<div id="at-tab-gallery" class="at-meta-tab-panel">
			<div class="at-gallery-container">
				<input type="hidden" name="at_gallery" id="at_gallery_ids" value="<?php echo esc_attr( $trek['gallery'] ); ?>" />
				<div class="at-gallery-thumbs" id="at_gallery_thumbs_wrapper">
					<?php
					if ( ! empty( $trek['gallery'] ) ) {
						$gallery_ids = explode( ',', $trek['gallery'] );
						foreach ( $gallery_ids as $img_id ) {
							$img_src = wp_get_attachment_image_src( $img_id, 'thumbnail' );
							if ( $img_src ) {
								echo '<div class="at-gallery-thumb-item" data-id="' . esc_attr( $img_id ) . '">';
								echo '<img src="' . esc_url( $img_src[0] ) . '" alt="" />';
								echo '<a href="#" class="at-gallery-remove-btn" title="' . esc_attr__( 'Remove', 'adventure-treks' ) . '">&times;</a>';
								echo '</div>';
							}
						}
					}
					?>
				</div>
				<button type="button" class="button button-primary button-large" id="at_select_gallery_btn">
					<?php esc_html_e( 'Add / Manage Gallery Images', 'adventure-treks' ); ?>
				</button>
				<p class="description"><?php esc_html_e( 'Select multiple images from the media library to display in the trek slider.', 'adventure-treks' ); ?></p>
			</div>
		</div>

		<!-- TAB 3: HIGHLIGHTS & PACKING -->
		<div id="at-tab-highlights" class="at-meta-tab-panel">
			<table class="form-table">
				<tr>
					<th><label for="at_highlights"><?php esc_html_e( 'Inclusions / Key Highlights (One per line)', 'adventure-treks' ); ?></label></th>
					<td>
						<textarea name="at_highlights" id="at_highlights" rows="8" class="large-text" placeholder="<?php esc_attr_e( "Trek through lush green pine valleys\nExperience camping under starry sky\nStunning views of Mt. Trishul", 'adventure-treks' ); ?>"><?php echo esc_textarea( $trek['highlights'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Shown on the front-end as "Inclusions", with a green checkmark next to each line.', 'adventure-treks' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="at_exclusions"><?php esc_html_e( 'Exclusions (One per line)', 'adventure-treks' ); ?></label></th>
					<td>
						<textarea name="at_exclusions" id="at_exclusions" rows="8" class="large-text" placeholder="<?php esc_attr_e( "Personal trekking gear/equipment rental\nTravel insurance\nMeals not mentioned in the itinerary\nAny costs due to natural calamities or delays", 'adventure-treks' ); ?>"><?php echo esc_textarea( $trek['exclusions'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Shown next to Inclusions on the front-end, with a red cross next to each line.', 'adventure-treks' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="at_things_to_carry"><?php esc_html_e( 'Things to Carry Checklist (One per line)', 'adventure-treks' ); ?></label></th>
					<td>
						<textarea name="at_things_to_carry" id="at_things_to_carry" rows="8" class="large-text" placeholder="<?php esc_attr_e( "Trekking shoes with good grip\nWarm jacket / fleece\nWater bottles 2L\nHeadlamp / Torch", 'adventure-treks' ); ?>"><?php echo esc_textarea( $trek['things_to_carry'] ); ?></textarea>
					</td>
				</tr>
			</table>
		</div>

		<!-- TAB 4: FAQ REPEATER -->
		<div id="at-tab-faq" class="at-meta-tab-panel">
			<div id="at_faq_repeater_list">
				<?php if ( ! empty( $faq_items ) ) : ?>
					<?php foreach ( $faq_items as $index => $item ) : ?>
						<div class="at-faq-repeater-row" data-index="<?php echo esc_attr( $index ); ?>">
							<span class="at-drag-handle" style="cursor: move;">☰</span>
							<div class="at-faq-row-fields">
								<input type="text" name="at_faq[<?php echo esc_attr( $index ); ?>][q]" value="<?php echo esc_attr( $item['q'] ); ?>" placeholder="<?php esc_attr_e( 'Question', 'adventure-treks' ); ?>" class="large-text" />
								<textarea name="at_faq[<?php echo esc_attr( $index ); ?>][a]" rows="3" placeholder="<?php esc_attr_e( 'Answer', 'adventure-treks' ); ?>" class="large-text"><?php echo esc_textarea( $item['a'] ); ?></textarea>
							</div>
							<a href="#" class="button at-remove-faq-row-btn"><?php esc_html_e( 'Remove', 'adventure-treks' ); ?></a>
						</div>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<button type="button" class="button button-primary" id="at_add_faq_row_btn">
				<?php esc_html_e( 'Add New FAQ Item', 'adventure-treks' ); ?>
			</button>
		</div>

		<!-- TAB 5: POLICIES -->
		<div id="at-tab-policies" class="at-meta-tab-panel">
			<p class="description"><?php esc_html_e( 'Leave a policy empty and it will not be shown on the trek page.', 'adventure-treks' ); ?></p>
			<table class="form-table">
				<?php foreach ( \AdventureTreks\Admin\Controllers\TrekMetaBoxController::get_policy_types() as $adventure_treks_policy_key => $adventure_treks_policy ) : ?>
					<tr>
						<th><label for="at_policy_<?php echo esc_attr( $adventure_treks_policy_key ); ?>"><?php echo esc_html( $adventure_treks_policy['label'] ); ?></label></th>
						<td>
							<?php
							wp_editor(
								isset( $policies[ $adventure_treks_policy_key ] ) ? $policies[ $adventure_treks_policy_key ] : '',
								'at_policy_' . $adventure_treks_policy_key,
								array(
									'textarea_name' => 'at_policy_' . $adventure_treks_policy_key,
									'textarea_rows' => 8,
									'media_buttons' => false,
									'teeny'         => true,
								)
							);
							?>
							<p class="description"><?php esc_html_e( 'Shown to visitors in a popup below the trek details.', 'adventure-treks' ); ?></p>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
		</div>

	</div>
</div>
