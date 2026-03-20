<?php
/**
 * Transient caching helpers.
 *
 * @package TC_Events
 */

defined( 'ABSPATH' ) || exit;

class TC_Events_Cache {

	const PREFIX = 'tc_events_';
	const TTL    = HOUR_IN_SECONDS;

	/**
	 * Get a cached value or compute it.
	 *
	 * @param string   $key      Cache key (auto-prefixed).
	 * @param callable $callback Function that returns the value to cache.
	 * @param int      $ttl      Time-to-live in seconds.
	 * @return mixed
	 */
	public static function remember( string $key, callable $callback, int $ttl = 0 ) {
		$ttl       = $ttl ?: self::TTL;
		$cache_key = self::PREFIX . $key;
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$value = $callback();
		set_transient( $cache_key, $value, $ttl );

		return $value;
	}

	/**
	 * Delete a specific cache key.
	 */
	public static function forget( string $key ): bool {
		return delete_transient( self::PREFIX . $key );
	}

	/**
	 * Flush all caches related to a specific event.
	 */
	public static function flush_event( int $event_id ): void {
		self::forget( 'event_' . $event_id );
		self::forget( 'rsvp_count_' . $event_id );
		self::forget( 'attendees_' . $event_id );
		self::flush_archive();
	}

	/**
	 * Flush archive/listing caches.
	 */
	public static function flush_archive(): void {
		global $wpdb;

		$wpdb->query(
			"DELETE FROM {$wpdb->options}
			 WHERE option_name LIKE '_transient_tc_events_archive_%'
			    OR option_name LIKE '_transient_timeout_tc_events_archive_%'
			    OR option_name LIKE '_transient_tc_events_shortcode_%'
			    OR option_name LIKE '_transient_timeout_tc_events_shortcode_%'"
		);
	}
}
