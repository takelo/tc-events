<?php
/**
 * Custom admin list columns for tc_event.
 *
 * @package TC_Events
 */

defined( 'ABSPATH' ) || exit;

class TC_Events_Admin_Columns {

	public static function init(): void {
		add_filter( 'manage_tc_event_posts_columns', array( __CLASS__, 'set_columns' ) );
		add_action( 'manage_tc_event_posts_custom_column', array( __CLASS__, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-tc_event_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'sort_by_meta' ) );
	}

	public static function set_columns( array $columns ): array {
		$new = array();
		$new['cb']              = $columns['cb'];
		$new['title']           = $columns['title'];
		$new['event_date']      = __( 'Event Date', 'tc-events' );
		$new['event_location']  = __( 'Location', 'tc-events' );
		$new['taxonomy-event_type'] = __( 'Type', 'tc-events' );
		$new['rsvp_count']      = __( 'RSVPs', 'tc-events' );
		$new['date']            = $columns['date'];

		return $new;
	}

	public static function render_column( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'event_date':
				$date = get_post_meta( $post_id, '_tc_event_date', true );
				echo $date ? esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $date ) ) ) : '&mdash;';
				break;

			case 'event_location':
				$location = get_post_meta( $post_id, '_tc_event_location', true );
				echo $location ? esc_html( $location ) : '&mdash;';
				break;

			case 'rsvp_count':
				echo absint( TC_Events_RSVP::get_count( $post_id ) );
				break;
		}
	}

	public static function sortable_columns( array $columns ): array {
		$columns['event_date']     = 'event_date';
		$columns['event_location'] = 'event_location';
		return $columns;
	}

	public static function sort_by_meta( WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() || 'tc_event' !== $query->get( 'post_type' ) ) {
			return;
		}

		$orderby = $query->get( 'orderby' );

		if ( 'event_date' === $orderby ) {
			$query->set( 'meta_key', '_tc_event_date' );
			$query->set( 'orderby', 'meta_value' );
		} elseif ( 'event_location' === $orderby ) {
			$query->set( 'meta_key', '_tc_event_location' );
			$query->set( 'orderby', 'meta_value' );
		}
	}
}
