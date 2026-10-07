<?php
/**
 * Elementor Trek Archive Widget.
 *
 * @package    TrekPilot
 * @subpackage TrekPilot/Includes/Elementor/Widgets
 * @author     Nilesh Vastarpara
 */

namespace TrekPilot\Includes\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * TrekArchiveWidget class.
 */
class TrekArchiveWidget extends \Elementor\Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'trekpilot_trek_archive';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Trek List', 'trekpilot' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-posts-grid';
	}

	/**
	 * Get widget categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		return array( 'trekpilot' );
	}

	/**
	 * Declare the stylesheet this widget needs so Elementor loads it
	 * reliably in both the editor preview and the live frontend, instead
	 * of depending on the ad-hoc wp_enqueue_style() call inside render().
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'trekpilot-public-archive-css' );
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls() {
		// ── Query: which treks to show, matching Elementor's own Source /
		// Manual Selection ("Search & Select") pattern used by its Posts
		// widget and WooCommerce's Products widget. ──────────────────────
		$this->start_controls_section(
			'query_section',
			array(
				'label' => esc_html__( 'Query', 'trekpilot' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'source',
			array(
				'label'   => esc_html__( 'Source', 'trekpilot' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'all',
				'options' => array(
					'all'    => esc_html__( 'All', 'trekpilot' ),
					'manual' => esc_html__( 'Manual Selection', 'trekpilot' ),
				),
			)
		);

		$this->add_control(
			'selected_treks',
			array(
				'label'       => esc_html__( 'Search & Select', 'trekpilot' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $this->get_trek_options(),
				'condition'   => array(
					'source' => 'manual',
				),
			)
		);

		$this->add_control(
			'orderby',
			array(
				'label'     => esc_html__( 'Order By', 'trekpilot' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'date',
				'options'   => array(
					'date'       => esc_html__( 'Publish Date', 'trekpilot' ),
					'title'      => esc_html__( 'Title', 'trekpilot' ),
					'menu_order' => esc_html__( 'Menu Order', 'trekpilot' ),
					'rand'       => esc_html__( 'Random', 'trekpilot' ),
				),
				'condition' => array(
					'source' => 'all',
				),
			)
		);

		$this->add_control(
			'order',
			array(
				'label'     => esc_html__( 'Order', 'trekpilot' ),
				'type'      => \Elementor\Controls_Manager::SELECT,
				'default'   => 'DESC',
				'options'   => array(
					'DESC' => esc_html__( 'Descending', 'trekpilot' ),
					'ASC'  => esc_html__( 'Ascending', 'trekpilot' ),
				),
				'condition' => array(
					'source' => 'all',
				),
			)
		);

		$this->end_controls_section();

		// ── Layout ─────────────────────────────────────────────────────
		$this->start_controls_section(
			'layout_section',
			array(
				'label' => esc_html__( 'Layout', 'trekpilot' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'posts_per_page',
			array(
				'label'     => esc_html__( 'Treks Per Page', 'trekpilot' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'min'       => 1,
				'max'       => 100,
				'default'   => 9,
				'condition' => array(
					'source' => 'all',
				),
			)
		);

		$this->add_control(
			'columns',
			array(
				'label'   => esc_html__( 'Columns', 'trekpilot' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 6,
				'default' => 3,
			)
		);

		$this->add_control(
			'show_excerpt',
			array(
				'label'        => esc_html__( 'Show Excerpt', 'trekpilot' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'trekpilot' ),
				'label_off'    => esc_html__( 'No', 'trekpilot' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_price',
			array(
				'label'        => esc_html__( 'Show Price', 'trekpilot' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'trekpilot' ),
				'label_off'    => esc_html__( 'No', 'trekpilot' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();

		// ── Pagination (only meaningful when showing "All" treks) ──────
		$this->start_controls_section(
			'pagination_section',
			array(
				'label'     => esc_html__( 'Pagination', 'trekpilot' ),
				'tab'       => \Elementor\Controls_Manager::TAB_CONTENT,
				'condition' => array(
					'source' => 'all',
				),
			)
		);

		$this->add_control(
			'show_pagination',
			array(
				'label'        => esc_html__( 'Show Pagination', 'trekpilot' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'trekpilot' ),
				'label_off'    => esc_html__( 'No', 'trekpilot' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Build post ID => title options for the "Search & Select" control.
	 *
	 * @return array
	 */
	private function get_trek_options() {
		$treks   = get_posts(
			array(
				'post_type'      => 'trekpilot_trek',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		$options = array();
		foreach ( $treks as $trek ) {
			$options[ $trek->ID ] = $trek->post_title;
		}
		return $options;
	}

	/**
	 * Render widget output on the frontend.
	 */
	protected function render() {
		$settings  = $this->get_settings_for_display();
		$is_manual = 'manual' === $settings['source'];

		if ( $is_manual ) {
			$selected_ids = array_filter( array_map( 'intval', (array) $settings['selected_treks'] ) );

			$shortcode = sprintf(
				'[trekpilot_list include="%1$s" columns="%2$d" show_excerpt="%3$s" show_price="%4$s" pagination="no"]',
				implode( ',', $selected_ids ),
				intval( $settings['columns'] ),
				'yes' === $settings['show_excerpt'] ? 'yes' : 'no',
				'yes' === $settings['show_price'] ? 'yes' : 'no'
			);
		} else {
			$shortcode = sprintf(
				'[trekpilot_list posts_per_page="%1$d" columns="%2$d" orderby="%3$s" order="%4$s" show_excerpt="%5$s" show_price="%6$s" pagination="%7$s"]',
				intval( $settings['posts_per_page'] ),
				intval( $settings['columns'] ),
				sanitize_key( $settings['orderby'] ),
				sanitize_key( $settings['order'] ),
				'yes' === $settings['show_excerpt'] ? 'yes' : 'no',
				'yes' === $settings['show_price'] ? 'yes' : 'no',
				'yes' === $settings['show_pagination'] ? 'yes' : 'no'
			);
		}

		echo do_shortcode( $shortcode );
	}
}
