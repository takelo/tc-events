<?php
/**
 * [tc_events] shortcode with filtering.
 *
 * @package TC_Events
 */

defined( 'ABSPATH' ) || exit;

class TC_Events_Shortcode {

	public static function init(): void {
		add_shortcode( 'tc_events', array( __CLASS__, 'render' ) );
	}

	/**
	 * Render the shortcode.
	 *
	 * Attributes:
	 *   type      - Event type taxonomy slug(s), comma-separated.
	 *   date_from - Show events starting from this date (Y-m-d).
	 *   date_to   - Show events up to this date (Y-m-d).
	 *   search    - Search keyword.
	 *   limit     - Number of events to show (default 12).
	 *   columns   - Grid columns 1-4 (default 3).
	 *   orderby   - Order by: date, title, event_date (default event_date).
	 *   order     - ASC or DESC (default ASC).
	 */
	public static function render( $atts ): string {
		$atts = shortcode_atts( array(
			'type'      => '',
			'date_from' => '',
			'date_to'   => '',
			'search'    => '',
			'limit'     => 12,
			'columns'   => 3,
			'orderby'   => 'event_date',
			'order'     => 'ASC',
		), $atts, 'tc_events' );

		$args = array(
			'post_type'      => 'tc_event',
			'post_status'    => 'publish',
			'posts_per_page' => absint( $atts['limit'] ),
		);

		// Taxonomy filter.
		if ( ! empty( $atts['type'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'event_type',
					'field'    => 'slug',
					'terms'    => array_map( 'trim', explode( ',', $atts['type'] ) ),
				),
			);
		}

		// Search.
		if ( ! empty( $atts['search'] ) ) {
			$args['s'] = sanitize_text_field( $atts['search'] );
		}

		// Date range meta query.
		$meta_query = array();

		if ( ! empty( $atts['date_from'] ) ) {
			$meta_query[] = array(
				'key'     => '_tc_event_date',
				'value'   => sanitize_text_field( $atts['date_from'] ),
				'compare' => '>=',
				'type'    => 'DATETIME',
			);
		}

		if ( ! empty( $atts['date_to'] ) ) {
			$meta_query[] = array(
				'key'     => '_tc_event_date',
				'value'   => sanitize_text_field( $atts['date_to'] ) . ' 23:59:59',
				'compare' => '<=',
				'type'    => 'DATETIME',
			);
		}

		if ( ! empty( $meta_query ) ) {
			$meta_query['relation'] = 'AND';
			$args['meta_query']     = $meta_query;
		}

		// Ordering.
		if ( 'event_date' === $atts['orderby'] ) {
			$args['meta_key'] = '_tc_event_date';
			$args['orderby']  = 'meta_value';
		} else {
			$args['orderby'] = sanitize_text_field( $atts['orderby'] );
		}
		$args['order'] = in_array( strtoupper( $atts['order'] ), array( 'ASC', 'DESC' ), true )
			? strtoupper( $atts['order'] )
			: 'ASC';

		$query   = new WP_Query( $args );
		$columns = max( 1, min( 4, absint( $atts['columns'] ) ) );

		ob_start();

		$template = TC_Events_Template_Loader::locate_template( 'shortcode-events.php' );
		if ( $template ) {
			include $template;
		}

		return ob_get_clean();
	}
}
