<?php
/**
 * Base command class.
 *
 * @package WPCV_Tax_Field_Sync
 */

// Bail if WP-CLI is not present.
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Base command class.
 *
 * @since 1.0.1
 *
 * @package WPCV_Tax_Field_Sync
 */
abstract class WPCV_Tax_Field_Sync_CLI_Command_Base extends \WP_CLI\CommandWithDBObject {

	/**
	 * Dependency check.
	 *
	 * @since 1.0.1
	 */
	public static function check_dependencies() {

		// Check for existence of CiviCRM.
		if ( ! function_exists( 'civicrm_initialize' ) ) {
			WP_CLI::error( 'Unable to find CiviCRM install.' );
		}

		// Check for existence of the CiviCRM Profile Sync plugin.
		if ( ! function_exists( 'civicrm_wp_profile_sync' ) ) {
			WP_CLI::error( 'Unable to find CiviCRM Profile Sync install.' );
		}

		// Check for existence of the ACF plugin.
		if ( ! function_exists( 'acf' ) ) {
			WP_CLI::error( 'Unable to find ACF install.' );
		}

	}

	/**
	 * Bootstrap CiviCRM.
	 *
	 * @since 1.0.1
	 */
	protected function bootstrap_civicrm() {
		self::check_dependencies();
		if ( ! civicrm_initialize() ) {
			WP_CLI::error( 'Unable to initialize CiviCRM.' );
		}
	}

	/**
	 * Returns the timezone string for the current site.
	 *
	 * If a timezone identifier is used, then return it.
	 * If an offset is used, build a suitable timezone.
	 * If all else fails, uses UTC.
	 *
	 * @since 1.0.1
	 *
	 * @return string $tzstring The site timezone string.
	 */
	protected function site_timezone_get() {

		// Check our cached value first.
		$tzstring = wp_cache_get( 'wpcvtfs_timezone' );

		// Build value if none is cached.
		if ( false === $tzstring ) {

			// Get relevant WordPress settings.
			$tzstring = get_option( 'timezone_string' );
			$offset   = get_option( 'gmt_offset' );

			/*
			 * Setting manual offsets should be discouraged.
			 *
			 * The IANA timezone database that provides PHP's timezone support
			 * uses (reversed) POSIX style signs.
			 *
			 * @see https://github.com/stephenharris/Event-Organiser/issues/287
			 * @see https://www.php.net/manual/en/timezones.others.php
			 * @see https://bugs.php.net/bug.php?id=45543
			 * @see https://bugs.php.net/bug.php?id=45528
			 */
			// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			if ( empty( $tzstring ) && 0 != $offset && floor( $offset ) == $offset ) {
				$offset_string = $offset > 0 ? "-$offset" : '+' . absint( $offset );
				$tzstring      = 'Etc/GMT' . $offset_string;
			}

			// Default to "UTC" if the timezone string is empty.
			if ( empty( $tzstring ) ) {
				$tzstring = 'UTC';
			}

			// Cache timezone string.
			wp_cache_set( 'wpcvtfs_timezone', $tzstring );

		}

		// --<
		return $tzstring;

	}

	/**
	 * Gets the Formatter object for a given set of arguments.
	 *
	 * @since 1.0.1
	 *
	 * @param array $assoc_args The params passed to a command. Determines the formatting.
	 * @return \WP_CLI\Formatter
	 */
	protected function formatter_get( &$assoc_args ) {
		return new \WP_CLI\Formatter( $assoc_args, $this->obj_fields );
	}

}
