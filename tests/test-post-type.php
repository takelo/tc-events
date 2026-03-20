<?php
/**
 * Tests for CPT and taxonomy registration.
 *
 * @package TC_Events
 */

class Test_TC_Events_Post_Type extends WP_UnitTestCase {

	public function test_post_type_exists() {
		$this->assertTrue( post_type_exists( 'tc_event' ) );
	}

	public function test_post_type_is_public() {
		$pt = get_post_type_object( 'tc_event' );
		$this->assertTrue( $pt->public );
	}

	public function test_post_type_has_archive() {
		$pt = get_post_type_object( 'tc_event' );
		$this->assertTrue( $pt->has_archive );
	}

	public function test_post_type_supports_title() {
		$this->assertTrue( post_type_supports( 'tc_event', 'title' ) );
	}

	public function test_post_type_supports_editor() {
		$this->assertTrue( post_type_supports( 'tc_event', 'editor' ) );
	}

	public function test_post_type_supports_thumbnail() {
		$this->assertTrue( post_type_supports( 'tc_event', 'thumbnail' ) );
	}

	public function test_taxonomy_exists() {
		$this->assertTrue( taxonomy_exists( 'event_type' ) );
	}

	public function test_taxonomy_is_hierarchical() {
		$tax = get_taxonomy( 'event_type' );
		$this->assertTrue( $tax->hierarchical );
	}

	public function test_taxonomy_assigned_to_cpt() {
		$tax = get_taxonomy( 'event_type' );
		$this->assertContains( 'tc_event', $tax->object_type );
	}

	public function test_create_event() {
		$post_id = $this->factory->post->create( array(
			'post_type'  => 'tc_event',
			'post_title' => 'Test Event',
		) );

		$this->assertIsInt( $post_id );
		$this->assertEquals( 'tc_event', get_post_type( $post_id ) );
	}

	public function test_event_meta_fields() {
		$post_id = $this->factory->post->create( array(
			'post_type' => 'tc_event',
		) );

		update_post_meta( $post_id, '_tc_event_date', '2026-06-15T10:00' );
		update_post_meta( $post_id, '_tc_event_location', 'Main Hall' );
		update_post_meta( $post_id, '_tc_event_capacity', 100 );

		$this->assertEquals( '2026-06-15T10:00', get_post_meta( $post_id, '_tc_event_date', true ) );
		$this->assertEquals( 'Main Hall', get_post_meta( $post_id, '_tc_event_location', true ) );
		$this->assertEquals( 100, (int) get_post_meta( $post_id, '_tc_event_capacity', true ) );
	}
}
