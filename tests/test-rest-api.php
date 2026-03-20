<?php
/**
 * Tests for REST API endpoints.
 *
 * @package TC_Events
 */

class Test_TC_Events_REST_API extends WP_UnitTestCase {

	private $event_id;

	public function set_up() {
		parent::set_up();

		$this->event_id = $this->factory->post->create( array(
			'post_type'   => 'tc_event',
			'post_title'  => 'REST Test Event',
			'post_status' => 'publish',
		) );

		update_post_meta( $this->event_id, '_tc_event_date', '2026-07-01T14:00' );
		update_post_meta( $this->event_id, '_tc_event_location', 'API Hall' );
		update_post_meta( $this->event_id, '_tc_event_capacity', 50 );

		// Initialize REST server.
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init' );
	}

	public function tear_down() {
		global $wp_rest_server;
		$wp_rest_server = null;
		parent::tear_down();
	}

	public function test_get_events_route_registered() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/tc-events/v1/events', $routes );
	}

	public function test_get_single_event_route_registered() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/tc-events/v1/events/(?P<id>\\d+)', $routes );
	}

	public function test_get_events_returns_data() {
		$request  = new WP_REST_Request( 'GET', '/tc-events/v1/events' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertIsArray( $data );
		$this->assertGreaterThan( 0, count( $data ) );
	}

	public function test_get_single_event() {
		$request  = new WP_REST_Request( 'GET', '/tc-events/v1/events/' . $this->event_id );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'REST Test Event', $data['title'] );
		$this->assertEquals( 'API Hall', $data['location'] );
		$this->assertEquals( 50, $data['capacity'] );
	}

	public function test_get_nonexistent_event_returns_404() {
		$request  = new WP_REST_Request( 'GET', '/tc-events/v1/events/99999' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 404, $response->get_status() );
	}

	public function test_rsvp_requires_document_and_name() {
		$request = new WP_REST_Request( 'POST', '/tc-events/v1/events/' . $this->event_id . '/rsvp' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_rsvp_with_valid_data() {
		$request = new WP_REST_Request( 'POST', '/tc-events/v1/events/' . $this->event_id . '/rsvp' );
		$request->set_param( 'document_number', '12345678' );
		$request->set_param( 'full_name', 'Jane Doe' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'confirmed', $data['status'] );
		$this->assertEquals( 1, $data['rsvp_count'] );
	}

	public function test_rsvp_duplicate_document_rejected() {
		$request = new WP_REST_Request( 'POST', '/tc-events/v1/events/' . $this->event_id . '/rsvp' );
		$request->set_param( 'document_number', '12345678' );
		$request->set_param( 'full_name', 'Jane Doe' );
		rest_get_server()->dispatch( $request );

		$request2 = new WP_REST_Request( 'POST', '/tc-events/v1/events/' . $this->event_id . '/rsvp' );
		$request2->set_param( 'document_number', '12345678' );
		$request2->set_param( 'full_name', 'Other Name' );
		$response2 = rest_get_server()->dispatch( $request2 );

		$this->assertEquals( 409, $response2->get_status() );
	}

	public function test_attendees_requires_edit_posts_capability() {
		$subscriber = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );

		$request  = new WP_REST_Request( 'GET', '/tc-events/v1/events/' . $this->event_id . '/attendees' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->get_status() );
	}
}
