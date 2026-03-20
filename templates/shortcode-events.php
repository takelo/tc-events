<?php
/**
 * Shortcode template for [tc_events].
 *
 * Variables available: $query (WP_Query), $columns (int), $atts (array).
 * Override by copying to your-theme/tc-events/shortcode-events.php
 *
 * @package TC_Events
 */

defined( 'ABSPATH' ) || exit;
?>

<?php if ( $query->have_posts() ) : ?>
	<div class="tc-events-grid tc-events-cols-<?php echo esc_attr( $columns ); ?>">
		<?php while ( $query->have_posts() ) : $query->the_post(); ?>
			<?php
			$card_color = get_post_meta( get_the_ID(), '_tc_event_color', true );
			if ( empty( $card_color ) ) {
				$card_color = '#ff56ac';
			}
			?>
			<article <?php post_class( 'tc-event-card' ); ?> style="--tc-card-color: <?php echo esc_attr( $card_color ); ?>">
				<?php if ( has_post_thumbnail() ) : ?>
					<a href="<?php the_permalink(); ?>" class="tc-event-card-thumbnail">
						<?php the_post_thumbnail( 'medium' ); ?>
					</a>
				<?php endif; ?>

				<div class="tc-event-card-content">
					<h3 class="tc-event-card-title">
						<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
					</h3>

					<?php
					$date     = get_post_meta( get_the_ID(), '_tc_event_date', true );
					$location = get_post_meta( get_the_ID(), '_tc_event_location', true );
					?>

					<?php if ( $date ) : ?>
						<p class="tc-event-card-date">
							<?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $date ) ) ); ?>
						</p>
					<?php endif; ?>

					<?php if ( $location ) : ?>
						<p class="tc-event-card-location">
							<?php echo esc_html( $location ); ?>
						</p>
					<?php endif; ?>

					<a href="<?php the_permalink(); ?>" class="tc-event-card-link">
						<?php esc_html_e( 'View Event', 'tc-events' ); ?> &rarr;
					</a>
				</div>
			</article>
		<?php endwhile; ?>
	</div>
	<?php wp_reset_postdata(); ?>
<?php else : ?>
	<p class="tc-events-no-events">
		<?php esc_html_e( 'No upcoming events.', 'tc-events' ); ?>
	</p>
<?php endif; ?>
