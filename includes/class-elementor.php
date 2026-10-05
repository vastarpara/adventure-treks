<?php
/**
 * Elementor integration bootstrap class.
 *
 * @package    AdventureTreks
 * @subpackage AdventureTreks/Includes
 * @author     Nilesh Vastarpara
 */

namespace AdventureTreks\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor class.
 */
class Elementor {

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Hook into Elementor initialization.
		add_action( 'init', array( $this, 'check_and_hook' ) );
	}

	/**
	 * Verify Elementor is loaded and register hooks.
	 */
	public function check_and_hook() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		// Register custom widgets category.
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );

		// Register widgets.
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
	}

	/**
	 * Register Custom Elementor Category.
	 *
	 * @param object $elements_manager Elementor elements manager instance.
	 */
	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'adventure-treks',
			array(
				'title' => esc_html__( 'Adventure Treks', 'adventure-treks' ),
				'icon'  => 'fa fa-mountain',
			)
		);

		$this->move_category_to_top( $elements_manager, 'adventure-treks' );
	}

	/**
	 * Elementor's public add_category() only ever appends, with no way for a
	 * 3rd-party plugin to place a category near the top of the widgets panel.
	 * This reorders the manager's internal (private) categories array so ours
	 * shows right under "Favorites" instead of at the very bottom of the list.
	 *
	 * @param object $elements_manager Elementor elements manager instance.
	 * @param string $category_name    Category key to promote.
	 * @return void
	 */
	private function move_category_to_top( $elements_manager, $category_name ) {
		try {
			$reflection = new \ReflectionClass( $elements_manager );
			$property   = $reflection->getProperty( 'categories' );
			$property->setAccessible( true );
			$categories = $property->getValue( $elements_manager );

			if ( ! is_array( $categories ) || ! isset( $categories[ $category_name ] ) ) {
				return;
			}

			$category = $categories[ $category_name ];
			unset( $categories[ $category_name ] );

			$reordered = array();
			if ( isset( $categories['favorites'] ) ) {
				$reordered['favorites'] = $categories['favorites'];
				unset( $categories['favorites'] );
			}
			$reordered[ $category_name ] = $category;

			$property->setValue( $elements_manager, $reordered + $categories );
		} catch ( \ReflectionException $e ) {
			// Elementor's internal structure changed; leave the category in its default (appended) position.
			return;
		}
	}

	/**
	 * Register Trek widgets.
	 *
	 * @param object $widgets_manager Elementor widgets manager instance.
	 */
	public function register_widgets( $widgets_manager ) {
		// Include widget files.
		require_once ADVENTURE_TREKS_PATH . 'includes/elementor/widgets/class-trekdetailswidget.php';
		require_once ADVENTURE_TREKS_PATH . 'includes/elementor/widgets/class-trekbookingwidget.php';
		require_once ADVENTURE_TREKS_PATH . 'includes/elementor/widgets/class-trekarchivewidget.php';

		// Instantiate and register.
		$widgets_manager->register( new \AdventureTreks\Includes\Elementor\Widgets\TrekDetailsWidget() );
		$widgets_manager->register( new \AdventureTreks\Includes\Elementor\Widgets\TrekBookingWidget() );
		$widgets_manager->register( new \AdventureTreks\Includes\Elementor\Widgets\TrekArchiveWidget() );
	}
}
