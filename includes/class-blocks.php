<?php
/**
 * Gutenberg blocks: Trek Details, Trek Booking and Trek Archive.
 *
 * Dynamic (server-rendered) blocks that reuse the plugin's shortcodes, so the output is
 * identical to the shortcodes and the Elementor widgets. No build step is required.
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
 * Blocks class.
 */
class Blocks {

	/**
	 * Wire hooks.
	 *
	 * @return void
	 */
	public static function register() {
		add_filter( 'block_categories_all', array( __CLASS__, 'register_category' ) );
		add_action( 'init', array( __CLASS__, 'register_blocks' ) );
		add_action( 'enqueue_block_assets', array( __CLASS__, 'enqueue_editor_styles' ) );
	}

	/**
	 * Add the "Adventure Treks" block category (first in the inserter).
	 *
	 * @param array $categories Existing categories.
	 * @return array
	 */
	public static function register_category( $categories ) {
		array_unshift(
			$categories,
			array(
				'slug'  => 'adventure-treks',
				'title' => __( 'Adventure Treks', 'adventure-treks' ),
				'icon'  => 'palmtree',
			)
		);
		return $categories;
	}

	/**
	 * Register the editor script and the three blocks.
	 *
	 * @return void
	 */
	public static function register_blocks() {
		wp_register_script(
			'at-blocks-editor',
			ADVENTURE_TREKS_URL . 'assets/blocks/blocks.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-data', 'wp-core-data', 'wp-server-side-render', 'wp-i18n' ),
			ADVENTURE_TREKS_VERSION . '.' . filemtime( ADVENTURE_TREKS_PATH . 'assets/blocks/blocks.js' ),
			true
		);
		wp_set_script_translations( 'at-blocks-editor', 'adventure-treks' );

		$blocks = array(
			'trek-details' => 'render_details',
			'trek-booking' => 'render_booking',
			'trek-archive' => 'render_archive',
		);
		foreach ( $blocks as $dir => $callback ) {
			register_block_type(
				ADVENTURE_TREKS_PATH . 'blocks/' . $dir,
				array(
					'editor_script'   => 'at-blocks-editor',
					'render_callback' => array( __CLASS__, $callback ),
				)
			);
		}
	}

	/**
	 * Load the frontend stylesheets inside the block editor so previews look like the site.
	 *
	 * @return void
	 */
	public static function enqueue_editor_styles() {
		if ( ! is_admin() ) {
			return;
		}

		$styles = array(
			'at-blocks-details-css' => 'trek-details.css',
			'at-blocks-booking-css' => 'booking-widget.css',
			'at-blocks-archive-css' => 'trek-archive.css',
		);
		foreach ( $styles as $handle => $file ) {
			wp_enqueue_style(
				$handle,
				ADVENTURE_TREKS_URL . 'assets/public/css/' . $file,
				array( 'dashicons' ),
				ADVENTURE_TREKS_VERSION . '.' . filemtime( ADVENTURE_TREKS_PATH . 'assets/public/css/' . $file )
			);
			wp_add_inline_style( $handle, Plugin::get_dynamic_color_css() );
		}
		wp_add_inline_style( 'at-blocks-booking-css', '.at-block-preview{pointer-events:none;}' );
	}

	/**
	 * Resolve which trek a block targets: the chosen one, else the current post.
	 *
	 * @param array          $attributes Block attributes.
	 * @param \WP_Block|null $block      Block instance.
	 * @return int Trek post ID, or 0 if none is valid.
	 */
	private static function resolve_trek_id( $attributes, $block ) {
		$trek_id = isset( $attributes['trekId'] ) ? absint( $attributes['trekId'] ) : 0;
		if ( ! $trek_id && $block instanceof \WP_Block && ! empty( $block->context['postId'] ) ) {
			$trek_id = absint( $block->context['postId'] );
		}
		if ( ! $trek_id ) {
			$trek_id = (int) get_the_ID();
		}
		return ( $trek_id && 'adventure_trek' === get_post_type( $trek_id ) ) ? $trek_id : 0;
	}

	/**
	 * Message shown when no valid trek can be determined.
	 *
	 * @return string
	 */
	private static function no_trek_notice() {
		return '<p style="color:#666; font-style:italic;">' . esc_html__( 'Please select a valid Trek or place this block on a Trek page.', 'adventure-treks' ) . '</p>';
	}

	/**
	 * Wrap a block's output with the standard wrapper attributes (alignment, custom class...).
	 *
	 * @param string $html Inner HTML.
	 * @return string
	 */
	private static function wrap( $html ) {
		return '<div ' . get_block_wrapper_attributes() . '>' . $html . '</div>';
	}

	/**
	 * Render the Trek Details block.
	 *
	 * @param array          $attributes Block attributes.
	 * @param string         $content    Saved content (unused).
	 * @param \WP_Block|null $block      Block instance.
	 * @return string
	 */
	public static function render_details( $attributes, $content = '', $block = null ) {
		$trek_id = self::resolve_trek_id( $attributes, $block );
		if ( ! $trek_id ) {
			return self::wrap( self::no_trek_notice() );
		}
		return self::wrap( do_shortcode( '[adventure_details id="' . $trek_id . '"]' ) );
	}

	/**
	 * Render the Trek Booking block.
	 *
	 * @param array          $attributes Block attributes.
	 * @param string         $content    Saved content (unused).
	 * @param \WP_Block|null $block      Block instance.
	 * @return string
	 */
	public static function render_booking( $attributes, $content = '', $block = null ) {
		$trek_id = self::resolve_trek_id( $attributes, $block );
		if ( ! $trek_id ) {
			return self::wrap( self::no_trek_notice() );
		}
		return self::wrap( do_shortcode( '[adventure_booking id="' . $trek_id . '"]' ) );
	}

	/**
	 * Render the Trek Archive block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public static function render_archive( $attributes ) {
		$yes_no  = static function ( $value ) {
			return $value ? 'yes' : 'no';
		};
		$columns = isset( $attributes['columns'] ) ? (int) $attributes['columns'] : 3;

		if ( isset( $attributes['source'] ) && 'manual' === $attributes['source'] ) {
			$ids = array_filter( array_map( 'absint', (array) ( isset( $attributes['selectedTreks'] ) ? $attributes['selectedTreks'] : array() ) ) );
			if ( empty( $ids ) ) {
				return self::wrap( '<p style="color:#666; font-style:italic;">' . esc_html__( 'Select at least one trek to show.', 'adventure-treks' ) . '</p>' );
			}
			$shortcode = sprintf(
				'[adventure_list include="%1$s" columns="%2$d" show_excerpt="%3$s" show_price="%4$s" pagination="no"]',
				implode( ',', $ids ),
				$columns,
				$yes_no( ! isset( $attributes['showExcerpt'] ) || $attributes['showExcerpt'] ),
				$yes_no( ! isset( $attributes['showPrice'] ) || $attributes['showPrice'] )
			);
		} else {
			$shortcode = sprintf(
				'[adventure_list posts_per_page="%1$d" columns="%2$d" orderby="%3$s" order="%4$s" show_excerpt="%5$s" show_price="%6$s" pagination="%7$s"]',
				isset( $attributes['postsPerPage'] ) ? (int) $attributes['postsPerPage'] : 9,
				$columns,
				sanitize_key( isset( $attributes['orderby'] ) ? $attributes['orderby'] : 'date' ),
				sanitize_key( isset( $attributes['order'] ) ? $attributes['order'] : 'DESC' ),
				$yes_no( ! isset( $attributes['showExcerpt'] ) || $attributes['showExcerpt'] ),
				$yes_no( ! isset( $attributes['showPrice'] ) || $attributes['showPrice'] ),
				$yes_no( ! isset( $attributes['showPagination'] ) || $attributes['showPagination'] )
			);
		}

		return self::wrap( do_shortcode( $shortcode ) );
	}
}
