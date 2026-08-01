<?php
/**
 * Elementor Trek Archive Widget.
 *
 * @package    AdventureTreks
 * @subpackage AdventureTreks/Includes/Elementor/Widgets
 * @author     Nilesh Vastarpara
 */

namespace AdventureTreks\Includes\Elementor\Widgets;

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
		return 'at_trek_archive';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Trek Archive Grid', 'adventure-treks' );
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
		return array( 'adventure-treks' );
	}

	/**
	 * Declare the stylesheet this widget needs so Elementor loads it
	 * reliably in both the editor preview and the live frontend, instead
	 * of depending on the ad-hoc wp_enqueue_style() call inside render().
	 *
	 * @return array
	 */
	public function get_style_depends() {
		return array( 'at-public-archive-css' );
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => esc_html__( 'Configuration', 'adventure-treks' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'posts_per_page',
			array(
				'label'   => esc_html__( 'Treks Per Page', 'adventure-treks' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 100,
				'default' => 9,
			)
		);

		$this->add_control(
			'columns',
			array(
				'label'   => esc_html__( 'Columns', 'adventure-treks' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '3',
				'options' => array(
					'2' => esc_html__( '2 Columns', 'adventure-treks' ),
					'3' => esc_html__( '3 Columns', 'adventure-treks' ),
					'4' => esc_html__( '4 Columns', 'adventure-treks' ),
				),
			)
		);

		$this->add_control(
			'orderby',
			array(
				'label'   => esc_html__( 'Order By', 'adventure-treks' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'date',
				'options' => array(
					'date'       => esc_html__( 'Publish Date', 'adventure-treks' ),
					'title'      => esc_html__( 'Title', 'adventure-treks' ),
					'menu_order' => esc_html__( 'Menu Order', 'adventure-treks' ),
					'rand'       => esc_html__( 'Random', 'adventure-treks' ),
				),
			)
		);

		$this->add_control(
			'order',
			array(
				'label'   => esc_html__( 'Order', 'adventure-treks' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => array(
					'DESC' => esc_html__( 'Descending', 'adventure-treks' ),
					'ASC'  => esc_html__( 'Ascending', 'adventure-treks' ),
				),
			)
		);

		$this->add_control(
			'show_excerpt',
			array(
				'label'        => esc_html__( 'Show Excerpt', 'adventure-treks' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'adventure-treks' ),
				'label_off'    => esc_html__( 'No', 'adventure-treks' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_price',
			array(
				'label'        => esc_html__( 'Show Starting Price', 'adventure-treks' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'adventure-treks' ),
				'label_off'    => esc_html__( 'No', 'adventure-treks' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_pagination',
			array(
				'label'        => esc_html__( 'Show Pagination', 'adventure-treks' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'adventure-treks' ),
				'label_off'    => esc_html__( 'No', 'adventure-treks' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output on the frontend.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$shortcode = sprintf(
			'[trek_archive posts_per_page="%1$d" columns="%2$d" orderby="%3$s" order="%4$s" show_excerpt="%5$s" show_price="%6$s" pagination="%7$s"]',
			intval( $settings['posts_per_page'] ),
			intval( $settings['columns'] ),
			sanitize_key( $settings['orderby'] ),
			sanitize_key( $settings['order'] ),
			'yes' === $settings['show_excerpt'] ? 'yes' : 'no',
			'yes' === $settings['show_price'] ? 'yes' : 'no',
			'yes' === $settings['show_pagination'] ? 'yes' : 'no'
		);

		echo do_shortcode( $shortcode );
	}
}
