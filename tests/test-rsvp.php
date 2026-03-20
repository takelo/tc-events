<?php
/**
 * Tests for RSVP functionality.
 *
 * @package TC_Events
 */

class Test_TC_Events_RSVP extends WP_UnitTestCase {

	private $event_id;

	public function set_up() {
		parent::set_up();

		$this->event_id = $this->factory->post->create( array(
			'post_type'   => 'tc_event',
			'post_status' => 'publish',
		) );
		update_post_meta( $this->event_id, '_tc_event_capacity', 2 );
	}

	public function test_create_rsvp() {
		$rsvp_id = TC_Events_RSVP::create_rsvp( $this->event_id, '12345678', 'John Doe' );
		$this->assertGreaterThan( 0, $rsvp_id );
	}

	public function test_get_rsvp_by_document() {
		TC_Events_RSVP::create_rsvp( $this->event_id, '12345678', 'John Doe' );
		$rsvp = TC_Events_RSVP::get_rsvp_by_document( $this->event_id, '12345678' );

		$this->assertNotNull( $rsvp );
		$this->assertEquals( 'confirmed', $rsvp->status );
		$this->assertEquals( 'John Doe', $rsvp->full_name );
	}

	public function test_get_rsvp_returns_null_for_nonexistent() {
		$rsvp = TC_Events_RSVP::get_rsvp_by_document( $this->event_id, '99999999' );
		$this->assertNull( $rsvp );
	}

	public function test_update_rsvp_status() {
		$rsvp_id = TC_Events_RSVP::create_rsvp( $this->event_id, '12345678', 'John Doe' );
		TC_Events_RSVP::update_rsvp( $rsvp_id, 'cancelled' );

		$rsvp = TC_Events_RSVP::get_rsvp_by_document( $this->event_id, '12345678' );
		$this->assertEquals( 'cancelled', $rsvp->status );
	}

	public function test_get_count() {
		TC_Events_Cache::forget( 'rsvp_count_' . $this->event_id );

		TC_Events_RSVP::create_rsvp( $this->event_id, '12345678', 'John Doe' );

		TC_Events_Cache::forget( 'rsvp_count_' . $this->event_id );

		$count = TC_Events_RSVP::get_count( $this->event_id );
		$this->assertEquals( 1, $count );
	}

	public function test_is_full() {
		TC_Events_RSVP::create_rsvp( $this->event_id, '11111111', 'User One' );
		TC_Events_RSVP::create_rsvp( $this->event_id, '22222222', 'User Two' );

		TC_Events_Cache::forget( 'rsvp_count_' . $this->event_id );

		$this->assertTrue( TC_Events_RSVP::is_full( $this->event_id ) );
	}

	public function test_unlimited_capacity_never_full() {
		update_post_meta( $this->event_id, '_tc_event_capacity', 0 );
		TC_Events_RSVP::create_rsvp( $this->event_id, '12345678', 'John Doe' );

		$this->assertFalse( TC_Events_RSVP::is_full( $this->event_id ) );
	}

	public function test_get_attendees() {
		TC_Events_RSVP::create_rsvp( $this->event_id, '12345678', 'John Doe' );

		TC_Events_Cache::forget( 'attendees_' . $this->event_id );

		$attendees = TC_Events_RSVP::get_attendees( $this->event_id );
		$this->assertCount( 1, $attendees );
		$this->assertEquals( '12345678', $attendees[0]->document_number );
		$this->assertEquals( 'John Doe', $attendees[0]->full_name );
	}

	public function test_duplicate_document_prevented() {
		TC_Events_RSVP::create_rsvp( $this->event_id, '12345678', 'John Doe' );
		$existing = TC_Events_RSVP::get_rsvp_by_document( $this->event_id, '12345678' );

		$this->assertNotNull( $existing );
	}

	public function test_delete_by_event() {
		TC_Events_RSVP::create_rsvp( $this->event_id, '12345678', 'John Doe' );
		$deleted = TC_Events_RSVP::delete_by_event( $this->event_id );

		$this->assertEquals( 1, $deleted );
	}
}
