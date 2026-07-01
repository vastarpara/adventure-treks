<?php
/**
 * Controller for registering and rendering frontend shortcodes.
 *
 * @package    AdventureTreks
 * @subpackage AdventureTreks/Public/Controllers
 * @author     Nilesh Vastarpara
 */

namespace AdventureTreks\Public\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * TrekShortcodesController class.
 */
class TrekShortcodesController {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Register Shortcodes.
		add_shortcode( 'trek_details', array( $this, 'render_trek_details' ) );
		add_shortcode( 'trek_itinerary', array( $this, 'render_trek_itinerary' ) );

		// Override single template for adventure_trek CPT.
		add_filter( 'template_include', array( $this, 'load_single_trek_template' ) );

		// Load CSS/JS.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Load plugin's single-adventure_trek.php template for CPT single pages.
	 *
	 * Priority order: child theme → parent theme → plugin fallback.
	 *
	 * @param string $template Current template path.
	 * @return string Modified template path.
	 */
	public function load_single_trek_template( $template ) {
		if ( is_singular( 'adventure_trek' ) ) {
			// Allow theme override first.
			$theme_tpl = locate_template( array( 'single-adventure_trek.php', 'single.php' ) );
			if ( $theme_tpl && 'single-adventure_trek.php' === basename( $theme_tpl ) ) {
				return $theme_tpl;
			}
			// Fall back to plugin template.
			$plugin_tpl = plugin_dir_path( dirname( __FILE__ ) ) . 'Views/single-adventure_trek.php';
			if ( file_exists( $plugin_tpl ) ) {
				return $plugin_tpl;
			}
		}
		return $template;
	}

	/**
	 * Register frontend CSS and JS.
	 */
	public function enqueue_assets() {
		// Frontend CSS.
		wp_register_style(
			'at-public-details-css',
			ADVENTURE_TREKS_URL . 'assets/public/css/trek-details.css',
			array( 'dashicons' ),
			ADVENTURE_TREKS_VERSION
		);

		// Frontend JS.
		wp_register_script(
			'at-public-details-js',
			ADVENTURE_TREKS_URL . 'assets/public/js/trek-details.js',
			array(),
			ADVENTURE_TREKS_VERSION,
			true
		);

		if ( is_singular( 'adventure_trek' ) || ( get_post() && has_shortcode( get_post()->post_content, 'trek_details' ) ) || ( get_post() && has_shortcode( get_post()->post_content, 'trek_itinerary' ) ) ) {
			wp_enqueue_style( 'at-public-details-css' );
			wp_enqueue_script( 'at-public-details-js' );
		}
	}

	/**
	 * Shortcode Renderer for [trek_details].
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_trek_details( $atts ) {
		// Enqueue assets on demand
		wp_enqueue_style( 'at-public-details-css' );
		wp_enqueue_script( 'at-public-details-js' );

		$args = shortcode_atts( array(
			'id' => get_the_ID(),
		), $atts );

		$trek_id = intval( $args['id'] );
		if ( ! $trek_id || get_post_type( $trek_id ) !== 'adventure_trek' ) {
			return '<p style="color:#b32d2e;">' . esc_html__( 'Error: Invalid Trek ID for details widget.', 'adventure-treks' ) . '</p>';
		}

		global $wpdb;

		// 1. Fetch Trek specifications meta
		$table_treks = $wpdb->prefix . 'at_treks';
		$trek = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM $table_treks WHERE post_id = %d", $trek_id ),
			ARRAY_A
		);

		if ( ! $trek ) {
			return '<p style="color:#666; font-style:italic;">' . esc_html__( 'Trek specifications have not been configured yet.', 'adventure-treks' ) . '</p>';
		}

		// 2. Fetch active departure cities to default the itinerary Day list
		$table_cities = $wpdb->prefix . 'at_departure_cities';
		$cities = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $table_cities WHERE trek_id = %d AND status = 'active' ORDER BY menu_order ASC", $trek_id )
		);

		$default_city_id = ! empty( $cities ) ? intval( $cities[0]->id ) : 0;

		// Decode lists
		$faq_items = ! empty( $trek['faq'] ) ? json_decode( $trek['faq'], true ) : array();
		$policies  = ! empty( $trek['policies'] ) ? json_decode( $trek['policies'], true ) : array();
		$gallery   = ! empty( $trek['gallery'] ) ? explode( ',', $trek['gallery'] ) : array();

		ob_start();
		$view_path = plugin_dir_path( dirname( __FILE__ ) ) . 'Views/trek-details.php';
		if ( file_exists( $view_path ) ) {
			include $view_path;
		}
		return ob_get_clean();
	}

	/**
	 * Shortcode Renderer for [trek_itinerary].
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_trek_itinerary( $atts ) {
		wp_enqueue_style( 'at-public-details-css' );

		$args = shortcode_atts( array(
			'city_id' => 0,
			'trek_id' => get_the_ID(),
		), $atts );

		$city_id = intval( $args['city_id'] );
		$trek_id = intval( $args['trek_id'] );

		global $wpdb;

		// If city_id is not specified, lookup first active city for the current trek
		if ( ! $city_id && $trek_id ) {
			$table_cities = $wpdb->prefix . 'at_departure_cities';
			$city_id = intval( $wpdb->get_var(
				$wpdb->prepare( "SELECT id FROM $table_cities WHERE trek_id = %d AND status = 'active' ORDER BY menu_order ASC LIMIT 1", $trek_id )
			) );
		}

		if ( ! $city_id ) {
			return '<p style="color:#666; font-style:italic;">' . esc_html__( 'No active departure city configuration found to render itinerary.', 'adventure-treks' ) . '</p>';
		}

		$table_days  = $wpdb->prefix . 'at_itineraries';
		$table_items = $wpdb->prefix . 'at_itinerary_items';

		$days = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM $table_days WHERE city_id = %d ORDER BY menu_order ASC", $city_id )
		);

		if ( empty( $days ) ) {
			return '<p style="color:#666; font-style:italic;">' . esc_html__( 'No daily itinerary configured for this city.', 'adventure-treks' ) . '</p>';
		}

		ob_start();
		?>
		<div class="at-frontend-itinerary-timeline" id="trek_itinerary_container">
			<?php foreach ( $days as $day ) : 
				$day_id = intval( $day->id );
				$items = $wpdb->get_results(
					$wpdb->prepare( "SELECT * FROM $table_items WHERE itinerary_id = %d ORDER BY menu_order ASC", $day_id )
				);
				?>
				<div class="at-timeline-day-block">
					<div class="at-timeline-day-header">
						<span class="at-timeline-day-badge"><?php printf( esc_html__( 'Day %d', 'adventure-treks' ), intval( $day->day_number ) ); ?></span>
						<h4 class="at-timeline-day-title"><?php echo esc_html( $day->title ); ?></h4>
					</div>
					<?php if ( ! empty( $day->description ) ) : ?>
						<p class="at-timeline-day-summary"><?php echo esc_html( $day->description ); ?></p>
					<?php endif; ?>

					<?php if ( ! empty( $items ) ) : ?>
						<div class="at-timeline-events">
							<?php foreach ( $items as $item ) : 
								$icon_class = $item->icon ? $item->icon : 'dashicons-palmtree';
								?>
								<div class="at-timeline-event-card">
									<div class="at-event-icon-wrapper"><span class="dashicons <?php echo esc_attr( $icon_class ); ?>"></span></div>
									<div class="at-event-content-box">
										<div class="at-event-meta">
											<span class="at-event-time"><?php echo esc_html( $item->item_time ); ?></span>
										</div>
										<h5 class="at-event-title"><?php echo esc_html( $item->title ); ?></h5>
										<?php if ( ! empty( $item->description ) ) : ?>
											<p class="at-event-description"><?php echo wp_kses_post( $item->description ); ?></p>
										<?php endif; ?>
										<?php if ( ! empty( $item->image_url ) ) : ?>
											<div class="at-event-media"><img src="<?php echo esc_url( $item->image_url ); ?>" /></div>
										<?php endif; ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
