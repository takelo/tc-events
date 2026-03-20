<?php
/**
 * Plugin activator – creates RSVP table and flushes rewrite rules.
 *
 * @package TC_Events
 */

defined( 'ABSPATH' ) || exit;

class TC_Events_Activator {

	public static function activate(): void {
		self::create_rsvp_table();

		// Register CPT so rewrite rules exist before flush.
		require_once TC_EVENTS_PLUGIN_DIR . 'includes/class-tc-events-post-type.php';
		TC_Events_Post_Type::register_post_type();
		TC_Events_Post_Type::register_taxonomy();
		self::create_default_terms();

		flush_rewrite_rules();
	}

	private static function create_default_terms(): void {
		$defaults = array(
			'Conference'  => 'conference',
			'Workshop'    => 'workshop',
			'Meetup'      => 'meetup',
			'Webinar'     => 'webinar',
			'Social'      => 'social',
			'Networking'  => 'networking',
		);

		foreach ( $defaults as $name => $slug ) {
			if ( ! term_exists( $slug, 'event_type' ) ) {
				wp_insert_term( $name, 'event_type', array( 'slug' => $slug ) );
			}
		}
	}

	private static function create_rsvp_table(): void {
		global $wpdb;

		$table   = $wpdb->prefix . 'tc_event_rsvps';
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			event_id BIGINT UNSIGNED NOT NULL,
			document_number VARCHAR(50) NOT NULL,
			full_name VARCHAR(255) NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'confirmed',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY event_document (event_id, document_number),
			KEY event_id (event_id),
			KEY document_number (document_number)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'tc_events_db_version', '1.0.0' );
	}
}
