<?php
/**
 * Archive template for events.
 *
 * Override by copying to your-theme/tc-events/archive-event.php
 *
 * @package TC_Events
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="tc-events-archive">
	<header class="tc-events-archive-header">
		<h1>
			<?php
			if ( is_tax( 'event_type' ) ) {
				echo esc_html( single_term_title( '', false ) );
			} else {
				esc_html_e( 'Upcoming Events', 'tc-events' );
			}
			?>
		</h1>
	</header>

	<?php if ( have_posts() ) : ?>
		<div class="tc-events-grid tc-events-cols-3">
			<?php while ( have_posts() ) : the_post(); ?>
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
						<h2 class="tc-event-card-title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h2>

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

						<?php if ( has_excerpt() ) : ?>
							<div class="tc-event-card-excerpt">
								<?php the_excerpt(); ?>
							</div>
						<?php endif; ?>

						<a href="<?php the_permalink(); ?>" class="tc-event-card-link">
							<?php esc_html_e( 'View Event', 'tc-events' ); ?> &rarr;
						</a>
					</div>
				</article>
			<?php endwhile; ?>
		</div>

		<div class="tc-events-pagination">
			<?php the_posts_pagination( array(
				'mid_size'  => 2,
				'prev_text' => __( '&laquo; Previous', 'tc-events' ),
				'next_text' => __( 'Next &raquo;', 'tc-events' ),
			) ); ?>
		</div>

	<?php else : ?>
		<p class="tc-events-no-events">
			<?php esc_html_e( 'No events found.', 'tc-events' ); ?>
		</p>
	<?php endif; ?>
</div>

<?php
get_footer();
