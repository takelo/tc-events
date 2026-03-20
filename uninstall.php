<?php
/**
 * Clean uninstall – removes all plugin data.
 *
 * @package TC_Events
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

// Delete RSVP table.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}tc_event_rsvps" );

// Delete all tc_event posts and their meta.
$posts = get_posts( array(
	'post_type'      => 'tc_event',
	'post_status'    => 'any',
	'posts_per_page' => -1,
	'fields'         => 'ids',
) );

foreach ( $posts as $post_id ) {
	wp_delete_post( $post_id, true );
}

// Delete event_type taxonomy terms.
$terms = get_terms( array(
	'taxonomy'   => 'event_type',
	'hide_empty' => false,
	'fields'     => 'ids',
) );

if ( ! is_wp_error( $terms ) ) {
	foreach ( $terms as $term_id ) {
		wp_delete_term( $term_id, 'event_type' );
	}
}

// Delete plugin options.
delete_option( 'tc_events_db_version' );

// Delete transients.
$wpdb->query(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_tc_events_%' OR option_name LIKE '_transient_timeout_tc_events_%'"
);
