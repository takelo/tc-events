<?php
/**
 * REST API endpoints for TC Events.
 *
 * Routes:
 *   GET  /tc-events/v1/events          - List events
 *   GET  /tc-events/v1/events/<id>     - Single event
 *   POST /tc-events/v1/events/<id>/rsvp - RSVP to event
 *   GET  /tc-events/v1/events/<id>/attendees - List attendees
 *
 * @package TC_Events
 */

defined( 'ABSPATH' ) || exit;

class TC_Events_REST_API {

	const NAMESPACE = 'tc-events/v1';

	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes(): void {
		register_rest_route( self::NAMESPACE, '/events', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( __CLASS__, 'get_events' ),
			'permission_callback' => '__return_true',
			'args'                => self::get_collection_params(),
		) );

		register_rest_route( self::NAMESPACE, '/events/(?P<id>\d+)', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( __CLASS__, 'get_event' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'id' => array(
					'validate_callback' => function ( $param ) {
						return is_numeric( $param ) && $param > 0;
					},
				),
			),
		) );

		register_rest_route( self::NAMESPACE, '/events/(?P<id>\d+)/rsvp', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( __CLASS__, 'create_rsvp' ),
			'permission_callback' => '__return_true',
			'args'                => array(
				'id' => array(
					'validate_callback' => function ( $param ) {
						return is_numeric( $param ) && $param > 0;
					},
				),
				'document_number' => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
				'full_name' => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		) );

		register_rest_route( self::NAMESPACE, '/events/(?P<id>\d+)/attendees', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( __CLASS__, 'get_attendees' ),
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
			'args'                => array(
				'id' => array(
					'validate_callback' => function ( $param ) {
						return is_numeric( $param ) && $param > 0;
					},
				),
			),
		) );
	}

	/**
	 * GET /events
	 */
	public static function get_events( WP_REST_Request $request ): WP_REST_Response {
		$args = array(
			'post_type'      => 'tc_event',
			'post_status'    => 'publish',
			'posts_per_page' => $request->get_param( 'per_page' ) ?: 10,
			'paged'          => $request->get_param( 'page' ) ?: 1,
		);

		$type = $request->get_param( 'type' );
		if ( $type ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'event_type',
					'field'    => 'slug',
					'terms'    => sanitize_text_field( $type ),
				),
			);
		}

		$search = $request->get_param( 'search' );
		if ( $search ) {
			$args['s'] = sanitize_text_field( $search );
		}

		// Default: order by event date ascending.
		$args['meta_key'] = '_tc_event_date';
		$args['orderby']  = 'meta_value';
		$args['order']    = 'ASC';

		$query  = new WP_Query( $args );
		$events = array();

		foreach ( $query->posts as $post ) {
			$events[] = self::prepare_event( $post );
		}

		$response = new WP_REST_Response( $events, 200 );
		$response->header( 'X-WP-Total', $query->found_posts );
		$response->header( 'X-WP-TotalPages', $query->max_num_pages );

		return $response;
	}

	/**
	 * GET /events/<id>
	 */
	public static function get_event( WP_REST_Request $request ): WP_REST_Response {
		$post = get_post( $request->get_param( 'id' ) );

		if ( ! $post || 'tc_event' !== $post->post_type || 'publish' !== $post->post_status ) {
			return new WP_REST_Response(
				array( 'message' => __( 'Event not found.', 'tc-events' ) ),
				404
			);
		}

		return new WP_REST_Response( self::prepare_event( $post ), 200 );
	}

	/**
	 * POST /events/<id>/rsvp
	 */
	public static function create_rsvp( WP_REST_Request $request ): WP_REST_Response {
		$event_id        = (int) $request->get_param( 'id' );
		$document_number = sanitize_text_field( $request->get_param( 'document_number' ) ?? '' );
		$full_name       = sanitize_text_field( $request->get_param( 'full_name' ) ?? '' );

		if ( empty( $document_number ) || empty( $full_name ) ) {
			return new WP_REST_Response(
				array( 'message' => __( 'Document number and full name are required.', 'tc-events' ) ),
				400
			);
		}

		if ( 'tc_event' !== get_post_type( $event_id ) ) {
			return new WP_REST_Response(
				array( 'message' => __( 'Invalid event.', 'tc-events' ) ),
				404
			);
		}

		$existing = TC_Events_RSVP::get_rsvp_by_document( $event_id, $document_number );

		if ( $existing ) {
			return new WP_REST_Response(
				array( 'message' => __( 'This document number is already registered for this event.', 'tc-events' ) ),
				409
			);
		}

		if ( TC_Events_RSVP::is_full( $event_id ) ) {
			return new WP_REST_Response(
				array( 'message' => __( 'Event is full.', 'tc-events' ) ),
				409
			);
		}

		TC_Events_RSVP::create_rsvp( $event_id, $document_number, $full_name );
		TC_Events_Cache::flush_event( $event_id );

		return new WP_REST_Response( array(
			'status'     => 'confirmed',
			'rsvp_count' => TC_Events_RSVP::get_count( $event_id ),
		), 200 );
	}

	/**
	 * GET /events/<id>/attendees
	 */
	public static function get_attendees( WP_REST_Request $request ): WP_REST_Response {
		$event_id = (int) $request->get_param( 'id' );

		if ( 'tc_event' !== get_post_type( $event_id ) ) {
			return new WP_REST_Response(
				array( 'message' => __( 'Invalid event.', 'tc-events' ) ),
				404
			);
		}

		$attendees = TC_Events_RSVP::get_attendees( $event_id );
		$data      = array();

		foreach ( $attendees as $attendee ) {
			$data[] = array(
				'id'              => (int) $attendee->id,
				'document_number' => $attendee->document_number,
				'full_name'       => $attendee->full_name,
				'status'          => $attendee->status,
				'created_at'      => $attendee->created_at,
			);
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Prepare event data for response.
	 */
	private static function prepare_event( WP_Post $post ): array {
		$capacity = (int) get_post_meta( $post->ID, '_tc_event_capacity', true );
		$count    = TC_Events_RSVP::get_count( $post->ID );

		$types = get_the_terms( $post->ID, 'event_type' );
		$type_data = array();
		if ( $types && ! is_wp_error( $types ) ) {
			foreach ( $types as $term ) {
				$type_data[] = array(
					'id'   => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				);
			}
		}

		return array(
			'id'         => $post->ID,
			'title'      => $post->post_title,
			'content'    => apply_filters( 'the_content', $post->post_content ),
			'excerpt'    => get_the_excerpt( $post ),
			'date'       => get_post_meta( $post->ID, '_tc_event_date', true ),
			'end_date'   => get_post_meta( $post->ID, '_tc_event_end_date', true ),
			'location'   => get_post_meta( $post->ID, '_tc_event_location', true ),
			'capacity'   => $capacity,
			'rsvp_count' => $count,
			'is_full'    => $capacity > 0 && $count >= $capacity,
			'types'      => $type_data,
			'thumbnail'  => get_the_post_thumbnail_url( $post->ID, 'medium' ) ?: null,
			'url'        => get_permalink( $post->ID ),
		);
	}

	/**
	 * Collection query parameters.
	 */
	private static function get_collection_params(): array {
		return array(
			'page'     => array(
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'default'           => 10,
				'sanitize_callback' => 'absint',
			),
			'type'     => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'search'   => array(
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}
}
