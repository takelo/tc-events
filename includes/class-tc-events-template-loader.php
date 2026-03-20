<?php
/**
 * WooCommerce-style template loader.
 * Allows themes to override templates via tc-events/ subdirectory.
 *
 * @package TC_Events
 */

defined( 'ABSPATH' ) || exit;

class TC_Events_Template_Loader {

	public static function init(): void {
		add_filter( 'single_template', array( __CLASS__, 'single_template' ) );
		add_filter( 'archive_template', array( __CLASS__, 'archive_template' ) );
	}

	public static function single_template( string $template ): string {
		if ( is_singular( 'tc_event' ) ) {
			$custom = self::locate_template( 'single-event.php' );
			if ( $custom ) {
				return $custom;
			}
		}
		return $template;
	}

	public static function archive_template( string $template ): string {
		if ( is_post_type_archive( 'tc_event' ) || is_tax( 'event_type' ) ) {
			$custom = self::locate_template( 'archive-event.php' );
			if ( $custom ) {
				return $custom;
			}
		}
		return $template;
	}

	/**
	 * Locate a template, checking theme first then plugin.
	 *
	 * @param string $template_name Template filename.
	 * @return string Full path to template, or empty string.
	 */
	public static function locate_template( string $template_name ): string {
		// Check theme/child-theme first.
		$theme_template = locate_template( 'tc-events/' . $template_name );
		if ( $theme_template ) {
			return $theme_template;
		}

		// Fall back to plugin template.
		$plugin_template = TC_EVENTS_PLUGIN_DIR . 'templates/' . $template_name;
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}

		return '';
	}
}
