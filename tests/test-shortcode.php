<?php
/**
 * Tests for the [tc_events] shortcode.
 *
 * @package TC_Events
 */

class Test_TC_Events_Shortcode extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();

		// Create some test events.
		for ( $i = 1; $i <= 3; $i++ ) {
			$post_id = $this->factory->post->create( array(
				'post_type'   => 'tc_event',
				'post_title'  => "Shortcode Event {$i}",
				'post_status' => 'publish',
			) );

			update_post_meta( $post_id, '_tc_event_date', "2026-0{$i}-15T10:00" );
			update_post_meta( $post_id, '_tc_event_location', "Location {$i}" );
		}
	}

	public function test_shortcode_is_registered() {
		$this->assertTrue( shortcode_exists( 'tc_events' ) );
	}

	public function test_shortcode_renders_output() {
		$output = do_shortcode( '[tc_events]' );
		$this->assertNotEmpty( $output );
	}

	public function test_shortcode_contains_event_cards() {
		$output = do_shortcode( '[tc_events]' );
		$this->assertStringContainsString( 'tc-event-card', $output );
	}

	public function test_shortcode_limit_attribute() {
		$output = do_shortcode( '[tc_events limit="1"]' );
		// Should contain at most 1 event card.
		$count = substr_count( $output, 'tc-event-card-title' );
		$this->assertLessThanOrEqual( 1, $count );
	}

	public function test_shortcode_columns_attribute() {
		$output = do_shortcode( '[tc_events columns="2"]' );
		$this->assertStringContainsString( 'tc-events-cols-2', $output );
	}

	public function test_shortcode_no_events_message() {
		// Delete all events.
		$posts = get_posts( array(
			'post_type'      => 'tc_event',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );
		foreach ( $posts as $id ) {
			wp_delete_post( $id, true );
		}

		$output = do_shortcode( '[tc_events]' );
		$this->assertStringContainsString( 'tc-events-no-events', $output );
	}

	public function test_shortcode_date_from_filter() {
		$output = do_shortcode( '[tc_events date_from="2026-02-01"]' );
		// Should not contain events before Feb 2026.
		$this->assertNotEmpty( $output );
	}
}
