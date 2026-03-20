<?php
/**
 * Single event template.
 *
 * Override by copying to your-theme/tc-events/single-event.php
 *
 * @package TC_Events
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="tc-events-single">
	<?php while ( have_posts() ) : the_post(); ?>
		<article id="event-<?php the_ID(); ?>" <?php post_class( 'tc-event-article' ); ?>>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="tc-event-thumbnail">
					<?php the_post_thumbnail( 'large' ); ?>
				</div>
			<?php endif; ?>

			<header class="tc-event-header">
				<h1 class="tc-event-title"><?php the_title(); ?></h1>

				<?php
				$terms = get_the_terms( get_the_ID(), 'event_type' );
				if ( $terms && ! is_wp_error( $terms ) ) :
				?>
					<div class="tc-event-types">
						<?php foreach ( $terms as $term ) : ?>
							<span class="tc-event-type-badge">
								<?php echo esc_html( $term->name ); ?>
							</span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</header>

			<div class="tc-event-meta">
				<?php
				$date     = get_post_meta( get_the_ID(), '_tc_event_date', true );
				$end_date = get_post_meta( get_the_ID(), '_tc_event_end_date', true );
				$location = get_post_meta( get_the_ID(), '_tc_event_location', true );
				$capacity = (int) get_post_meta( get_the_ID(), '_tc_event_capacity', true );
				$count    = TC_Events_RSVP::get_count( get_the_ID() );
				?>

				<?php if ( $date ) : ?>
					<div class="tc-event-meta-item">
						<strong><?php esc_html_e( 'Date:', 'tc-events' ); ?></strong>
						<?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $date ) ) ); ?>
						<?php if ( $end_date ) : ?>
							&mdash; <?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $end_date ) ) ); ?>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( $location ) : ?>
					<div class="tc-event-meta-item">
						<strong><?php esc_html_e( 'Location:', 'tc-events' ); ?></strong>
						<?php echo esc_html( $location ); ?>
					</div>
				<?php endif; ?>

				<div class="tc-event-meta-item">
					<strong><?php esc_html_e( 'Attendees:', 'tc-events' ); ?></strong>
					<?php
					echo esc_html( $count );
					if ( $capacity > 0 ) {
						echo ' / ' . esc_html( $capacity );
					}
					?>
				</div>
			</div>

			<div class="tc-event-content">
				<?php the_content(); ?>
			</div>

			<div class="tc-event-rsvp-section">
				<?php
				$is_full    = TC_Events_RSVP::is_full( get_the_ID() );
				$event_date = get_post_meta( get_the_ID(), '_tc_event_end_date', true );
				if ( empty( $event_date ) ) {
					$event_date = get_post_meta( get_the_ID(), '_tc_event_date', true );
				}
				$is_past = ! empty( $event_date ) && strtotime( $event_date ) < time();
				?>

				<?php if ( $is_past ) : ?>
					<p class="tc-event-past-notice">
						<?php esc_html_e( 'This event has already taken place.', 'tc-events' ); ?>
					</p>
				<?php elseif ( $is_full ) : ?>
					<p class="tc-event-full-notice">
						<?php esc_html_e( 'This event is full.', 'tc-events' ); ?>
					</p>
				<?php else : ?>
					<h3><?php esc_html_e( 'RSVP for this event', 'tc-events' ); ?></h3>
					<form class="tc-rsvp-form" data-event-id="<?php echo esc_attr( get_the_ID() ); ?>">
						<div class="tc-rsvp-field">
							<label for="tc_rsvp_full_name"><?php esc_html_e( 'Full Name', 'tc-events' ); ?> <span class="required">*</span></label>
							<input type="text" id="tc_rsvp_full_name" name="full_name" required>
						</div>
						<div class="tc-rsvp-field">
							<label for="tc_rsvp_document"><?php esc_html_e( 'Document Number', 'tc-events' ); ?> <span class="required">*</span></label>
							<input type="text" id="tc_rsvp_document" name="document_number" required>
						</div>
						<button type="submit" class="tc-rsvp-button">
							<?php esc_html_e( 'Confirm RSVP', 'tc-events' ); ?>
						</button>
					</form>
					<div class="tc-rsvp-message" style="display:none;"></div>
				<?php endif; ?>
			</div>

		</article>
	<?php endwhile; ?>
</div>

<?php
get_footer();
