<?php
/**
 * WP-CLI commands for TC Events.
 *
 * Usage: wp tc-events generate --count=10
 *
 * @package TC_Events
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_CLI_Command' ) ) {
	return;
}

class TC_Events_CLI extends WP_CLI_Command {

	/**
	 * Generate sample events.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<number>]
	 * : Number of events to generate.
	 * ---
	 * default: 10
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp tc-events generate --count=5
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function generate( $args, $assoc_args ) {
		$count = absint( $assoc_args['count'] ?? 10 );

		if ( $count < 1 || $count > 100 ) {
			WP_CLI::error( 'Count must be between 1 and 100.' );
		}

		$locations = array(
			'Main Conference Hall',
			'Room A - Workshop Space',
			'Auditorium',
			'Outdoor Stage',
			'Virtual / Online',
			'Community Center',
			'Downtown Park',
			'University Campus',
		);

		$titles = array(
			'Annual Tech Conference',
			'Community Meetup',
			'Workshop: Getting Started',
			'Networking Evening',
			'Hackathon Weekend',
			'Panel Discussion',
			'Product Launch',
			'Charity Gala',
			'Training Session',
			'Open Mic Night',
			'Film Screening',
			'Art Exhibition',
			'Book Club Meeting',
			'Yoga in the Park',
			'Cooking Class',
		);

		// Ensure event_type terms exist.
		$types = array( 'conference', 'workshop', 'social', 'webinar' );
		foreach ( $types as $type_slug ) {
			if ( ! term_exists( $type_slug, 'event_type' ) ) {
				wp_insert_term( ucfirst( $type_slug ), 'event_type', array( 'slug' => $type_slug ) );
			}
		}

		$progress = \WP_CLI\Utils\make_progress_bar( 'Generating events', $count );

		for ( $i = 0; $i < $count; $i++ ) {
			$title = $titles[ array_rand( $titles ) ] . ' #' . ( $i + 1 );
			$start = gmdate( 'Y-m-d\TH:i', strtotime( '+' . wp_rand( 1, 90 ) . ' days +' . wp_rand( 9, 18 ) . ' hours' ) );
			$end   = gmdate( 'Y-m-d\TH:i', strtotime( $start . ' +' . wp_rand( 1, 4 ) . ' hours' ) );

			$post_id = wp_insert_post( array(
				'post_type'    => 'tc_event',
				'post_title'   => $title,
				'post_content' => sprintf(
					'<!-- wp:paragraph --><p>%s</p><!-- /wp:paragraph -->',
					'This is a sample event generated for testing purposes. Join us for an exciting event!'
				),
				'post_excerpt' => 'A sample event for testing the TC Events plugin.',
				'post_status'  => 'publish',
			) );

			if ( is_wp_error( $post_id ) ) {
				WP_CLI::warning( 'Failed to create event: ' . $post_id->get_error_message() );
				continue;
			}

			update_post_meta( $post_id, '_tc_event_date', $start );
			update_post_meta( $post_id, '_tc_event_end_date', $end );
			update_post_meta( $post_id, '_tc_event_location', $locations[ array_rand( $locations ) ] );
			update_post_meta( $post_id, '_tc_event_capacity', wp_rand( 10, 200 ) );

			// Assign random type.
			$type_slug = $types[ array_rand( $types ) ];
			wp_set_object_terms( $post_id, $type_slug, 'event_type' );

			$progress->tick();
		}

		$progress->finish();

		TC_Events_Cache::flush_archive();

		WP_CLI::success( sprintf( 'Generated %d events.', $count ) );
	}

	/**
	 * List event statistics.
	 *
	 * ## EXAMPLES
	 *
	 *     wp tc-events stats
	 */
	public function stats( $args, $assoc_args ) {
		$total = wp_count_posts( 'tc_event' );

		$table_data = array(
			array(
				'Status'    => 'Published',
				'Count'     => $total->publish ?? 0,
			),
			array(
				'Status'    => 'Draft',
				'Count'     => $total->draft ?? 0,
			),
			array(
				'Status'    => 'Trash',
				'Count'     => $total->trash ?? 0,
			),
		);

		WP_CLI\Utils\format_items( 'table', $table_data, array( 'Status', 'Count' ) );

		global $wpdb;
		$rsvp_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}tc_event_rsvps WHERE status = 'confirmed'" );
		WP_CLI::log( sprintf( 'Total confirmed RSVPs: %d', $rsvp_count ) );
	}
}

WP_CLI::add_command( 'tc-events', 'TC_Events_CLI' );
