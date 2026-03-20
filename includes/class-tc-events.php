<?php
/**
 * Main plugin bootstrapper (singleton).
 *
 * @package TC_Events
 */

defined( 'ABSPATH' ) || exit;

class TC_Events {

	/** @var TC_Events|null */
	private static $instance = null;

	/**
	 * Return the single instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	private function load_dependencies(): void {
		$dir = TC_EVENTS_PLUGIN_DIR . 'includes/';

		require_once $dir . 'class-tc-events-cache.php';
		require_once $dir . 'class-tc-events-post-type.php';
		require_once $dir . 'class-tc-events-meta-boxes.php';
		require_once $dir . 'class-tc-events-admin-columns.php';
		require_once $dir . 'class-tc-events-rsvp.php';
		require_once $dir . 'class-tc-events-notifications.php';
		require_once $dir . 'class-tc-events-shortcode.php';
		require_once $dir . 'class-tc-events-rest-api.php';
		require_once $dir . 'class-tc-events-template-loader.php';

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once TC_EVENTS_PLUGIN_DIR . 'cli/class-tc-events-cli.php';
		}
	}

	private function init_hooks(): void {
		TC_Events_Post_Type::init();
		TC_Events_Meta_Boxes::init();
		TC_Events_Admin_Columns::init();
		TC_Events_RSVP::init();
		TC_Events_Notifications::init();
		TC_Events_Shortcode::init();
		TC_Events_REST_API::init();
		TC_Events_Template_Loader::init();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	public function enqueue_public_assets(): void {
		if ( ! is_singular( 'tc_event' ) && ! is_post_type_archive( 'tc_event' ) ) {
			return;
		}

		wp_enqueue_style(
			'tc-events-public',
			TC_EVENTS_PLUGIN_URL . 'assets/css/tc-events-public.css',
			array(),
			TC_EVENTS_VERSION
		);

		wp_enqueue_script(
			'tc-events-public',
			TC_EVENTS_PLUGIN_URL . 'assets/js/tc-events-public.js',
			array(),
			TC_EVENTS_VERSION,
			true
		);

		wp_localize_script( 'tc-events-public', 'tcEvents', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'tc_events_rsvp' ),
			'strings' => array(
				'confirm'        => __( 'RSVP Confirmed! Thank you.', 'tc-events' ),
				'full'           => __( 'Event is full.', 'tc-events' ),
				'fieldsRequired' => __( 'Please fill in all fields.', 'tc-events' ),
				'error'          => __( 'Something went wrong. Please try again.', 'tc-events' ),
			),
		) );
	}

	public function enqueue_admin_assets( string $hook ): void {
		$screen = get_current_screen();
		if ( ! $screen || 'tc_event' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style(
			'tc-events-admin',
			TC_EVENTS_PLUGIN_URL . 'assets/css/tc-events-admin.css',
			array(),
			TC_EVENTS_VERSION
		);

		wp_enqueue_script(
			'tc-events-admin',
			TC_EVENTS_PLUGIN_URL . 'assets/js/tc-events-admin.js',
			array(),
			TC_EVENTS_VERSION,
			true
		);
	}
}
