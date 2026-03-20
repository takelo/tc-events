<?php
/**
 * Plugin Name: TC Events
 * Plugin URI:  https://github.com/tc-events/tc-events
 * Description: A complete event management plugin with RSVP, REST API, notifications, and more.
 * Version:     1.0.0
 * Author:      Tensai
 * Author URI:  https://tensai.dev
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: tc-events
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package TC_Events
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'TC_EVENTS_VERSION', '1.0.0' );
define( 'TC_EVENTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TC_EVENTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TC_EVENTS_PLUGIN_FILE', __FILE__ );
define( 'TC_EVENTS_BASENAME', plugin_basename( __FILE__ ) );

// Load text domain.
add_action( 'plugins_loaded', function () {
	load_plugin_textdomain( 'tc-events', false, dirname( TC_EVENTS_BASENAME ) . '/languages' );
} );

// Activation / Deactivation.
require_once TC_EVENTS_PLUGIN_DIR . 'includes/class-tc-events-activator.php';
require_once TC_EVENTS_PLUGIN_DIR . 'includes/class-tc-events-deactivator.php';

register_activation_hook( __FILE__, array( 'TC_Events_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'TC_Events_Deactivator', 'deactivate' ) );

// Bootstrap plugin.
require_once TC_EVENTS_PLUGIN_DIR . 'includes/class-tc-events.php';
TC_Events::instance();
