<?php
/**
 * Register Custom Post Types for TrekPilot
 *
 * @package    TrekPilot
 * @subpackage TrekPilot/Includes
 * @author     Nilesh Vastarpara
 */

namespace TrekPilot\Includes;

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
		add_action( 'add_meta_boxes', array( __CLASS__, 'remove_extra_meta_boxes' ), 99 );
	}

	/**
	 * Register trekpilot_trek custom post type.
	 *
	 * @return void
	 */
	public static function register_trek_post_type() {
		$labels = array(
			'name'                  => _x( 'Treks', 'Post Type General Name', 'trekpilot' ),
			'singular_name'         => _x( 'Trek', 'Post Type Singular Name', 'trekpilot' ),
			'menu_name'             => __( 'TrekPilot', 'trekpilot' ),
			'name_admin_bar'        => __( 'Trek', 'trekpilot' ),
			'archives'              => __( 'Trek Archives', 'trekpilot' ),
			'attributes'            => __( 'Trek Attributes', 'trekpilot' ),
			'parent_item_colon'     => __( 'Parent Trek:', 'trekpilot' ),
			'all_items'             => __( 'All Treks', 'trekpilot' ),
			'add_new_item'          => __( 'Add New Trek', 'trekpilot' ),
			'add_new'               => __( 'Add New', 'trekpilot' ),
			'new_item'              => __( 'New Trek', 'trekpilot' ),
			'edit_item'             => __( 'Edit Trek', 'trekpilot' ),
			'update_item'           => __( 'Update Trek', 'trekpilot' ),
			'view_item'             => __( 'View Trek', 'trekpilot' ),
			'view_items'            => __( 'View Treks', 'trekpilot' ),
			'search_items'          => __( 'Search Trek', 'trekpilot' ),
			'not_found'             => __( 'No treks found.', 'trekpilot' ),
			'not_found_in_trash'    => __( 'No treks found in Trash.', 'trekpilot' ),
			'featured_image'        => __( 'Featured Image', 'trekpilot' ),
			'set_featured_image'    => __( 'Set featured image', 'trekpilot' ),
			'remove_featured_image' => __( 'Remove featured image', 'trekpilot' ),
			'use_featured_image'    => __( 'Use as featured image', 'trekpilot' ),
			'insert_into_item'      => __( 'Insert into trek', 'trekpilot' ),
			'uploaded_to_this_item' => __( 'Uploaded to this trek', 'trekpilot' ),
			'items_list'            => __( 'Treks list', 'trekpilot' ),
			'items_list_navigation' => __( 'Treks list navigation', 'trekpilot' ),
			'filter_items_list'     => __( 'Filter treks list', 'trekpilot' ),
		);

		$args = array(
			'label'               => __( 'Trek', 'trekpilot' ),
			'description'         => __( 'TrekPilot trek post type', 'trekpilot' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-palmtree',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
			'show_in_rest'        => true, // Required for Gutenberg and modern REST query capability.
		);

		register_post_type( 'trekpilot_trek', $args );
	}

	/**
	 * Remove generic meta boxes that other plugins/themes add to the trek editor
	 * (the Custom Fields box); a trek's data is managed through TrekPilot's own boxes.
	 *
	 * @return void
	 */
	public static function remove_extra_meta_boxes() {
		remove_meta_box( 'postcustom', 'trekpilot_trek', 'normal' );
		remove_meta_box( 'postcustom', 'trekpilot_trek', 'advanced' );
	}
}
