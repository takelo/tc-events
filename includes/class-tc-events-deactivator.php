<?php
/**
 * Plugin deactivator – flushes rewrite rules.
 *
 * @package TC_Events
 */

defined( 'ABSPATH' ) || exit;

class TC_Events_Deactivator {

	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
