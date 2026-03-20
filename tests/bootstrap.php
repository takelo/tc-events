<?php
/**
 * PHPUnit bootstrap for TC Events tests.
 *
 * @package TC_Events
 */

// Load WordPress test suite.
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php\n";
	echo "Set WP_TESTS_DIR env var to point to WordPress test library.\n";
	exit( 1 );
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Load the plugin.
 */
function _manually_load_plugin() {
	require dirname( __DIR__ ) . '/tc-events.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
