<?php
/**
 * Registers the tc_event CPT and event_type taxonomy.
 *
 * @package TC_Events
 */

defined( 'ABSPATH' ) || exit;

class TC_Events_Post_Type {

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( __CLASS__, 'register_taxonomy' ) );
	}

	public static function register_post_type(): void {
		$labels = array(
			'name'                  => __( 'Events', 'tc-events' ),
			'singular_name'        => __( 'Event', 'tc-events' ),
			'add_new'              => __( 'Add New', 'tc-events' ),
			'add_new_item'         => __( 'Add New Event', 'tc-events' ),
			'edit_item'            => __( 'Edit Event', 'tc-events' ),
			'new_item'             => __( 'New Event', 'tc-events' ),
			'view_item'            => __( 'View Event', 'tc-events' ),
			'search_items'         => __( 'Search Events', 'tc-events' ),
			'not_found'            => __( 'No events found.', 'tc-events' ),
			'not_found_in_trash'   => __( 'No events found in Trash.', 'tc-events' ),
			'all_items'            => __( 'All Events', 'tc-events' ),
			'menu_name'            => __( 'Events', 'tc-events' ),
		);

		register_post_type( 'tc_event', array(
			'labels'             => $labels,
			'public'             => true,
			'has_archive'        => true,
			'rewrite'            => array( 'slug' => 'events' ),
			'menu_icon'          => 'dashicons-calendar-alt',
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'show_in_rest'       => true,
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
		) );
	}

	public static function register_taxonomy(): void {
		$labels = array(
			'name'              => __( 'Event Types', 'tc-events' ),
			'singular_name'     => __( 'Event Type', 'tc-events' ),
			'search_items'      => __( 'Search Event Types', 'tc-events' ),
			'all_items'         => __( 'All Event Types', 'tc-events' ),
			'parent_item'       => __( 'Parent Event Type', 'tc-events' ),
			'parent_item_colon' => __( 'Parent Event Type:', 'tc-events' ),
			'edit_item'         => __( 'Edit Event Type', 'tc-events' ),
			'update_item'       => __( 'Update Event Type', 'tc-events' ),
			'add_new_item'      => __( 'Add New Event Type', 'tc-events' ),
			'new_item_name'     => __( 'New Event Type Name', 'tc-events' ),
			'menu_name'         => __( 'Event Types', 'tc-events' ),
		);

		register_taxonomy( 'event_type', 'tc_event', array(
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'event-type' ),
			'show_admin_column' => true,
		) );
	}
}
