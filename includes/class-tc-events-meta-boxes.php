<?php
/**
 * Meta boxes for event date, location, and capacity.
 *
 * @package TC_Events
 */

defined( 'ABSPATH' ) || exit;

class TC_Events_Meta_Boxes {

	public static function init(): void {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_tc_event', array( __CLASS__, 'save_meta' ), 10, 2 );
	}

	public static function add_meta_boxes(): void {
		add_meta_box(
			'tc_event_details',
			__( 'Event Details', 'tc-events' ),
			array( __CLASS__, 'render_meta_box' ),
			'tc_event',
			'side',
			'high'
		);

		add_meta_box(
			'tc_event_attendees',
			__( 'Registered Attendees', 'tc-events' ),
			array( __CLASS__, 'render_attendees_box' ),
			'tc_event',
			'normal',
			'default'
		);
	}

	public static function render_meta_box( WP_Post $post ): void {
		wp_nonce_field( 'tc_event_meta', 'tc_event_meta_nonce' );

		$date     = get_post_meta( $post->ID, '_tc_event_date', true );
		$end_date = get_post_meta( $post->ID, '_tc_event_end_date', true );
		$location = get_post_meta( $post->ID, '_tc_event_location', true );
		$capacity = get_post_meta( $post->ID, '_tc_event_capacity', true );
		$color    = get_post_meta( $post->ID, '_tc_event_color', true );
		if ( empty( $color ) ) {
			$color = '#ff56ac';
		}
		?>
		<p>
			<label for="tc_event_date"><strong><?php esc_html_e( 'Start Date & Time', 'tc-events' ); ?></strong></label><br>
			<input type="datetime-local" id="tc_event_date" name="_tc_event_date"
				   value="<?php echo esc_attr( $date ); ?>" class="widefat" required>
		</p>
		<p>
			<label for="tc_event_end_date"><strong><?php esc_html_e( 'End Date & Time', 'tc-events' ); ?></strong></label><br>
			<input type="datetime-local" id="tc_event_end_date" name="_tc_event_end_date"
				   value="<?php echo esc_attr( $end_date ); ?>" class="widefat">
		</p>
		<p>
			<label for="tc_event_location"><strong><?php esc_html_e( 'Location', 'tc-events' ); ?></strong></label><br>
			<input type="text" id="tc_event_location" name="_tc_event_location"
				   value="<?php echo esc_attr( $location ); ?>" class="widefat">
		</p>
		<p>
			<label for="tc_event_capacity"><strong><?php esc_html_e( 'Capacity', 'tc-events' ); ?></strong></label><br>
			<input type="number" id="tc_event_capacity" name="_tc_event_capacity"
				   value="<?php echo esc_attr( $capacity ); ?>" class="widefat" min="0" step="1">
			<span class="description"><?php esc_html_e( 'Leave empty or 0 for unlimited.', 'tc-events' ); ?></span>
		</p>
		<p>
			<label for="tc_event_color"><strong><?php esc_html_e( 'Card Color', 'tc-events' ); ?></strong></label><br>
			<input type="color" id="tc_event_color" name="_tc_event_color"
				   value="<?php echo esc_attr( $color ); ?>">
			<span class="description"><?php esc_html_e( 'Hover effect color for the event card.', 'tc-events' ); ?></span>
		</p>
		<?php
	}

	public static function render_attendees_box( WP_Post $post ): void {
		$attendees = TC_Events_RSVP::get_attendees( $post->ID );
		$count     = count( $attendees );
		$capacity  = (int) get_post_meta( $post->ID, '_tc_event_capacity', true );
		?>
		<div class="tc-attendees-summary">
			<strong><?php esc_html_e( 'Total:', 'tc-events' ); ?></strong>
			<?php echo esc_html( $count ); ?>
			<?php if ( $capacity > 0 ) : ?>
				/ <?php echo esc_html( $capacity ); ?>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $attendees ) ) : ?>
			<table class="widefat striped tc-attendees-table">
				<thead>
					<tr>
						<th>#</th>
						<th><?php esc_html_e( 'Full Name', 'tc-events' ); ?></th>
						<th><?php esc_html_e( 'Document Number', 'tc-events' ); ?></th>
						<th><?php esc_html_e( 'Date', 'tc-events' ); ?></th>
						<th><?php esc_html_e( 'Status', 'tc-events' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $attendees as $i => $attendee ) : ?>
						<tr>
							<td><?php echo esc_html( $i + 1 ); ?></td>
							<td><?php echo esc_html( $attendee->full_name ); ?></td>
							<td><?php echo esc_html( $attendee->document_number ); ?></td>
							<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $attendee->created_at ) ) ); ?></td>
							<td>
								<span class="tc-attendee-status tc-attendee-status--<?php echo esc_attr( $attendee->status ); ?>">
									<?php echo esc_html( ucfirst( $attendee->status ) ); ?>
								</span>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'No attendees registered yet.', 'tc-events' ); ?></p>
		<?php endif;
	}

	public static function save_meta( int $post_id, WP_Post $post ): void {
		if ( ! isset( $_POST['tc_event_meta_nonce'] ) ||
			 ! wp_verify_nonce( $_POST['tc_event_meta_nonce'], 'tc_event_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			'_tc_event_date'     => 'sanitize_text_field',
			'_tc_event_end_date' => 'sanitize_text_field',
			'_tc_event_location' => 'sanitize_text_field',
			'_tc_event_capacity' => 'absint',
			'_tc_event_color'    => 'sanitize_hex_color',
		);

		foreach ( $fields as $key => $sanitize ) {
			if ( isset( $_POST[ $key ] ) ) {
				$value = call_user_func( $sanitize, wp_unslash( $_POST[ $key ] ) );
				update_post_meta( $post_id, $key, $value );
			}
		}

		// Invalidate cache.
		TC_Events_Cache::flush_event( $post_id );
	}
}
