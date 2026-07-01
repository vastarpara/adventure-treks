<?php
/**
 * Register Custom Post Types for Adventure Treks
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
 * PostTypes class.
 */
class PostTypes {

	/**
	 * Register actions.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'init', array( __CLASS__, 'register_trek_post_type' ) );
	}

	/**
	 * Register adventure_trek custom post type.
	 *
	 * @return void
	 */
	public static function register_trek_post_type() {
		$labels = array(
			'name'                  => _x( 'Treks', 'Post Type General Name', 'adventure-treks' ),
			'singular_name'         => _x( 'Trek', 'Post Type Singular Name', 'adventure-treks' ),
			'menu_name'             => __( 'Adventure Treks', 'adventure-treks' ),
			'name_admin_bar'        => __( 'Adventure Trek', 'adventure-treks' ),
			'archives'              => __( 'Trek Archives', 'adventure-treks' ),
			'attributes'            => __( 'Trek Attributes', 'adventure-treks' ),
			'parent_item_colon'     => __( 'Parent Trek:', 'adventure-treks' ),
			'all_items'             => __( 'All Treks', 'adventure-treks' ),
			'add_new_item'          => __( 'Add New Trek', 'adventure-treks' ),
			'add_new'               => __( 'Add New', 'adventure-treks' ),
			'new_item'              => __( 'New Trek', 'adventure-treks' ),
			'edit_item'             => __( 'Edit Trek', 'adventure-treks' ),
			'update_item'           => __( 'Update Trek', 'adventure-treks' ),
			'view_item'             => __( 'View Trek', 'adventure-treks' ),
			'view_items'            => __( 'View Treks', 'adventure-treks' ),
			'search_items'          => __( 'Search Trek', 'adventure-treks' ),
			'not_found'             => __( 'Not found', 'adventure-treks' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'adventure-treks' ),
			'featured_image'        => __( 'Featured Image', 'adventure-treks' ),
			'set_featured_image'    => __( 'Set featured image', 'adventure-treks' ),
			'remove_featured_image' => __( 'Remove featured image', 'adventure-treks' ),
			'use_featured_image'    => __( 'Use as featured image', 'adventure-treks' ),
			'insert_into_item'      => __( 'Insert into trek', 'adventure-treks' ),
			'uploaded_to_this_item' => __( 'Uploaded to this trek', 'adventure-treks' ),
			'items_list'            => __( 'Treks list', 'adventure-treks' ),
			'items_list_navigation' => __( 'Treks list navigation', 'adventure-treks' ),
			'filter_items_list'     => __( 'Filter treks list', 'adventure-treks' ),
		);

		$args = array(
			'label'                 => __( 'Trek', 'adventure-treks' ),
			'description'           => __( 'Adventure Trek CPT', 'adventure-treks' ),
			'labels'                => $labels,
			'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 5,
			'menu_icon'             => 'dashicons-palmtree',
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => true,
			'exclude_from_search'   => false,
			'publicly_queryable'    => true,
			'capability_type'       => 'post',
			'show_in_rest'          => true, // Required for Gutenberg and modern REST query capability.
		);

		register_post_type( 'adventure_trek', $args );
	}
}
