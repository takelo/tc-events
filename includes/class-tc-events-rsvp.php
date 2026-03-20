<?php
/**
 * RSVP CRUD with AJAX handler and capacity checks.
 * Uses document_number + full_name (no login required).
 *
 * @package TC_Events
 */

defined( 'ABSPATH' ) || exit;

class TC_Events_RSVP {

	public static function init(): void {
		add_action( 'wp_ajax_tc_events_rsvp', array( __CLASS__, 'handle_ajax_rsvp' ) );
		add_action( 'wp_ajax_nopriv_tc_events_rsvp', array( __CLASS__, 'handle_ajax_rsvp' ) );
	}

	/**
	 * Handle AJAX RSVP submission.
	 */
	public static function handle_ajax_rsvp(): void {
		check_ajax_referer( 'tc_events_rsvp', 'nonce' );

		$event_id        = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
		$document_number = isset( $_POST['document_number'] ) ? sanitize_text_field( wp_unslash( $_POST['document_number'] ) ) : '';
		$full_name       = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';

		if ( ! $event_id || empty( $document_number ) || empty( $full_name ) ) {
			wp_send_json_error( array( 'message' => __( 'All fields are required.', 'tc-events' ) ), 400 );
		}

		if ( 'tc_event' !== get_post_type( $event_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid event.', 'tc-events' ) ), 400 );
		}

		// Check if this document already has an RSVP for this event.
		$existing = self::get_rsvp_by_document( $event_id, $document_number );

		if ( $existing ) {
			wp_send_json_error( array(
				'message' => __( 'This document number is already registered for this event.', 'tc-events' ),
			), 409 );
		}

		if ( self::is_full( $event_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Event is full.', 'tc-events' ) ), 409 );
		}

		self::create_rsvp( $event_id, $document_number, $full_name );

		TC_Events_Cache::flush_event( $event_id );

		wp_send_json_success( array(
			'status'     => 'confirmed',
			'rsvp_count' => self::get_count( $event_id ),
			'message'    => __( 'RSVP confirmed! Thank you.', 'tc-events' ),
		) );
	}

	/**
	 * Create a new RSVP record.
	 */
	public static function create_rsvp( int $event_id, string $document_number, string $full_name, string $status = 'confirmed' ): int {
		global $wpdb;
		$table = $wpdb->prefix . 'tc_event_rsvps';

		$wpdb->insert(
			$table,
			array(
				'event_id'        => $event_id,
				'document_number' => $document_number,
				'full_name'       => $full_name,
				'status'          => $status,
			),
			array( '%d', '%s', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Update RSVP status.
	 */
	public static function update_rsvp( int $rsvp_id, string $status ): bool {
		global $wpdb;
		$table = $wpdb->prefix . 'tc_event_rsvps';

		return (bool) $wpdb->update(
			$table,
			array( 'status' => $status ),
			array( 'id' => $rsvp_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Get a single RSVP by event + document number.
	 */
	public static function get_rsvp_by_document( int $event_id, string $document_number ): ?object {
		global $wpdb;
		$table = $wpdb->prefix . 'tc_event_rsvps';

		$result = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE event_id = %d AND document_number = %s",
				$event_id,
				$document_number
			)
		);

		return $result ?: null;
	}

	/**
	 * Get confirmed RSVP count for an event.
	 */
	public static function get_count( int $event_id ): int {
		return (int) TC_Events_Cache::remember(
			'rsvp_count_' . $event_id,
			function () use ( $event_id ) {
				global $wpdb;
				$table = $wpdb->prefix . 'tc_event_rsvps';

				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$table} WHERE event_id = %d AND status = 'confirmed'",
						$event_id
					)
				);
			}
		);
	}

	/**
	 * Get all confirmed attendees for an event.
	 *
	 * @return object[]
	 */
	public static function get_attendees( int $event_id ): array {
		return TC_Events_Cache::remember(
			'attendees_' . $event_id,
			function () use ( $event_id ) {
				global $wpdb;
				$table = $wpdb->prefix . 'tc_event_rsvps';

				return $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM {$table}
						 WHERE event_id = %d AND status = 'confirmed'
						 ORDER BY created_at ASC",
						$event_id
					)
				);
			}
		);
	}

	/**
	 * Check if event is at capacity.
	 */
	public static function is_full( int $event_id ): bool {
		$capacity = (int) get_post_meta( $event_id, '_tc_event_capacity', true );

		if ( $capacity <= 0 ) {
			return false; // Unlimited.
		}

		return self::get_count( $event_id ) >= $capacity;
	}

	/**
	 * Delete all RSVPs for an event.
	 */
	public static function delete_by_event( int $event_id ): int {
		global $wpdb;
		$table = $wpdb->prefix . 'tc_event_rsvps';

		return (int) $wpdb->delete(
			$table,
			array( 'event_id' => $event_id ),
			array( '%d' )
		);
	}
}
