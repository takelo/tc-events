<?php
/**
 * Email notifications on event publish and update.
 *
 * @package TC_Events
 */

defined( 'ABSPATH' ) || exit;

class TC_Events_Notifications {

	public static function init(): void {
		add_action( 'transition_post_status', array( __CLASS__, 'on_status_change' ), 10, 3 );
		add_action( 'post_updated', array( __CLASS__, 'on_event_updated' ), 10, 3 );
	}

	/**
	 * Send notification when event is published.
	 */
	public static function on_status_change( string $new_status, string $old_status, WP_Post $post ): void {
		if ( 'tc_event' !== $post->post_type ) {
			return;
		}

		if ( 'publish' === $new_status && 'publish' !== $old_status ) {
			self::send_admin_notification( $post, 'published' );
		}
	}

	/**
	 * Notify attendees when a published event is updated.
	 */
	public static function on_event_updated( int $post_id, WP_Post $post_after, WP_Post $post_before ): void {
		if ( 'tc_event' !== $post_after->post_type || 'publish' !== $post_after->post_status ) {
			return;
		}

		// Only notify on meaningful changes.
		$changed = false;
		$changes = array();

		foreach ( array( '_tc_event_date', '_tc_event_end_date', '_tc_event_location' ) as $key ) {
			$old = get_post_meta( $post_id, $key, true );
			$new = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : $old;
			if ( $old !== $new ) {
				$changed = true;
				$label   = str_replace( array( '_tc_event_', '_' ), array( '', ' ' ), $key );
				$changes[] = ucfirst( $label ) . ': ' . ( $old ?: '—' ) . ' → ' . ( $new ?: '—' );
			}
		}

		if ( $post_after->post_title !== $post_before->post_title ) {
			$changed = true;
		}

		if ( ! $changed ) {
			return;
		}

		self::notify_attendees( $post_after, $changes );
	}

	/**
	 * Send admin email about a new event.
	 */
	private static function send_admin_notification( WP_Post $post, string $action ): void {
		$admin_email = get_option( 'admin_email' );
		$site_name   = get_bloginfo( 'name' );

		$subject = sprintf(
			/* translators: 1: site name, 2: event title */
			__( '[%1$s] New event %2$s: %3$s', 'tc-events' ),
			$site_name,
			$action,
			$post->post_title
		);

		$date     = get_post_meta( $post->ID, '_tc_event_date', true );
		$location = get_post_meta( $post->ID, '_tc_event_location', true );

		$message = sprintf(
			/* translators: 1: event title, 2: date, 3: location, 4: edit URL */
			__( "A new event has been %1\$s.\n\nTitle: %2\$s\nDate: %3\$s\nLocation: %4\$s\n\nEdit: %5\$s", 'tc-events' ),
			$action,
			$post->post_title,
			$date ?: __( 'TBD', 'tc-events' ),
			$location ?: __( 'TBD', 'tc-events' ),
			get_edit_post_link( $post->ID, 'raw' )
		);

		wp_mail( $admin_email, $subject, $message );
	}

	/**
	 * Notify confirmed attendees about event changes.
	 */
	private static function notify_attendees( WP_Post $post, array $changes ): void {
		$admin_email = get_option( 'admin_email' );
		$site_name   = get_bloginfo( 'name' );
		$count       = TC_Events_RSVP::get_count( $post->ID );

		if ( $count < 1 ) {
			return;
		}

		$subject = sprintf(
			/* translators: 1: site name, 2: event title */
			__( '[%1$s] Event updated: %2$s', 'tc-events' ),
			$site_name,
			$post->post_title
		);

		$change_text = ! empty( $changes ) ? "\n" . implode( "\n", $changes ) : '';

		$message = sprintf(
			/* translators: 1: event title, 2: attendee count, 3: changes, 4: event URL */
			__( "The event \"%1\$s\" has been updated (%2\$d registered attendees).%3\$s\n\nView event: %4\$s", 'tc-events' ),
			$post->post_title,
			$count,
			$change_text,
			get_permalink( $post->ID )
		);

		// Notify admin since RSVP attendees register with document number, not email.
		wp_mail( $admin_email, $subject, $message );
	}
}
