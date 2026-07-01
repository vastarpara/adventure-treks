<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals
/**
 * Trek Meta Box view template
 *
 * @package    AdventureTreks
 * @subpackage AdventureTreks/Admin/Views
 * @author     Nilesh Vastarpara
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="at-meta-tabs-wrapper">
	<!-- Tab Navigation -->
	<ul class="at-meta-tabs-nav">
		<li class="active"><a href="#at-tab-general"><?php esc_html_e( 'General Info', 'adventure-treks' ); ?></a></li>
		<li><a href="#at-tab-gallery"><?php esc_html_e( 'Gallery', 'adventure-treks' ); ?></a></li>
		<li><a href="#at-tab-highlights"><?php esc_html_e( 'Highlights & Packing', 'adventure-treks' ); ?></a></li>
		<li><a href="#at-tab-faq"><?php esc_html_e( 'FAQ Repeater', 'adventure-treks' ); ?></a></li>
		<li><a href="#at-tab-policies"><?php esc_html_e( 'Policies', 'adventure-treks' ); ?></a></li>
	</ul>

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
					<input type="text" name="at_duration" id="at_duration" value="<?php echo esc_attr( $trek['duration'] ); ?>" style="width:100%;" placeholder="e.g. 5 Days / 4 Nights" />
				</div>
				<div class="at-form-row">
					<label for="at_altitude"><?php esc_html_e( 'Max Altitude (e.g. 12,500 ft)', 'adventure-treks' ); ?></label>
					<input type="text" name="at_altitude" id="at_altitude" value="<?php echo esc_attr( $trek['altitude'] ); ?>" style="width:100%;" placeholder="e.g. 12,500 ft" />
				</div>
				<div class="at-form-row">
					<label for="at_region"><?php esc_html_e( 'Region / State', 'adventure-treks' ); ?></label>
					<input type="text" name="at_region" id="at_region" value="<?php echo esc_attr( $trek['region'] ); ?>" style="width:100%;" placeholder="e.g. Himachal Pradesh" />
				</div>
				<div class="at-form-row">
					<label for="at_season"><?php esc_html_e( 'Best Season', 'adventure-treks' ); ?></label>
					<input type="text" name="at_season" id="at_season" value="<?php echo esc_attr( $trek['season'] ); ?>" style="width:100%;" placeholder="e.g. May to October" />
				</div>
				<div class="at-form-row">
					<label for="at_distance"><?php esc_html_e( 'Trekking Distance (e.g. 26 km)', 'adventure-treks' ); ?></label>
					<input type="text" name="at_distance" id="at_distance" value="<?php echo esc_attr( $trek['distance'] ); ?>" style="width:100%;" placeholder="e.g. 26 km" />
				</div>
				<div class="at-form-row">
					<label for="at_fitness_level"><?php esc_html_e( 'Fitness Level Required', 'adventure-treks' ); ?></label>
					<input type="text" name="at_fitness_level" id="at_fitness_level" value="<?php echo esc_attr( $trek['fitness_level'] ); ?>" style="width:100%;" placeholder="e.g. Average / Good physical health" />
				</div>
				<div class="at-form-row">
					<label for="at_age_limit"><?php esc_html_e( 'Age Limit (e.g. 10 - 55 Years)', 'adventure-treks' ); ?></label>
					<input type="text" name="at_age_limit" id="at_age_limit" value="<?php echo esc_attr( $trek['age_limit'] ); ?>" style="width:100%;" placeholder="e.g. 10 to 60 Years" />
				</div>
				<div class="at-form-row">
					<label for="at_group_size"><?php esc_html_e( 'Ideal Group Size', 'adventure-treks' ); ?></label>
					<input type="text" name="at_group_size" id="at_group_size" value="<?php echo esc_attr( $trek['group_size'] ); ?>" style="width:100%;" placeholder="e.g. 12 to 20 Trekkers" />
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
								echo '<img src="' . esc_url( $img_src[0] ) . '" />';
								echo '<a href="#" class="at-gallery-remove-btn" title="Remove">&times;</a>';
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
					<th><label for="at_highlights"><?php esc_html_e( 'Key Highlights (One per line)', 'adventure-treks' ); ?></label></th>
					<td>
						<textarea name="at_highlights" id="at_highlights" rows="8" class="large-text" placeholder="<?php esc_attr_e( "Trek through lush green pine valleys\nExperience camping under starry sky\nStunning views of Mt. Trishul", 'adventure-treks' ); ?>"><?php echo esc_textarea( $trek['highlights'] ); ?></textarea>
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
			<table class="form-table">
				<tr>
					<th><label for="at_policy_cancellation"><?php esc_html_e( 'Cancellation Policy', 'adventure-treks' ); ?></label></th>
					<td>
						<textarea name="at_policy_cancellation" id="at_policy_cancellation" rows="4" class="large-text"><?php echo esc_textarea( $policies['cancellation'] ); ?></textarea>
					</td>
				</tr>
				<tr>
					<th><label for="at_policy_refund"><?php esc_html_e( 'Refund Policy', 'adventure-treks' ); ?></label></th>
					<td>
						<textarea name="at_policy_refund" id="at_policy_refund" rows="4" class="large-text"><?php echo esc_textarea( $policies['refund'] ); ?></textarea>
					</td>
				</tr>
				<tr>
					<th><label for="at_policy_medical"><?php esc_html_e( 'Medical Disclaimer', 'adventure-treks' ); ?></label></th>
					<td>
						<textarea name="at_policy_medical" id="at_policy_medical" rows="4" class="large-text"><?php echo esc_textarea( $policies['medical'] ); ?></textarea>
					</td>
				</tr>
				<tr>
					<th><label for="at_policy_terms"><?php esc_html_e( 'Terms & Conditions', 'adventure-treks' ); ?></label></th>
					<td>
						<textarea name="at_policy_terms" id="at_policy_terms" rows="4" class="large-text"><?php echo esc_textarea( $policies['terms'] ); ?></textarea>
					</td>
				</tr>
			</table>
		</div>

	</div>
</div>
