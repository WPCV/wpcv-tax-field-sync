<?php
/**
 * Sync Base Class.
 *
 * Handles sync between a CiviCRM Custom Field and a WordPress Taxonomy.
 *
 * @package WPCV_Tax_Field_Sync
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Sync Base Class.
 *
 * A class that handles sync between a CiviCRM Custom Field and a WordPress Taxonomy.
 *
 * @since 1.0
 */
class WPCV_Tax_Field_Sync_Base {

	/**
	 * CiviCRM object.
	 *
	 * @since 1.0
	 * @access public
	 * @var object
	 */
	public $civicrm;

	/**
	 * WordPress object.
	 *
	 * @since 1.0
	 * @access public
	 * @var object
	 */
	public $wordpress;

	/**
	 * Mapper object.
	 *
	 * @since 1.0
	 * @access public
	 * @var object
	 */
	public $mapper;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param string $taxonomy The slug of the WordPress Taxonomy.
	 * @param int $custom_field_id The numeric ID of the CiviCRM Custom Field.
	 * @param string $sync_direction The sync direction. Can be 'both', 'wp_to_civicrm' or 'civicrm_to_wp'. Default 'both'.
	 */
	public function __construct( $taxonomy, $custom_field_id, $sync_direction = 'both' ) {

		// Initialise this object.
		$this->initialise( $taxonomy, $custom_field_id, $sync_direction );

	}

	/**
	 * Initialises this object.
	 *
	 * @since 1.0
	 *
	 * @param string $taxonomy The slug of the WordPress Taxonomy.
	 * @param int $custom_field_id The numeric ID of the CiviCRM Custom Field.
	 * @param string $sync_direction The sync direction. Can be 'both', 'wp_to_civicrm' or 'civicrm_to_wp'. Default 'both'.
	 */
	public function initialise( $taxonomy, $custom_field_id, $sync_direction = 'both' ) {

		// Bootstrap class.
		$this->include_files();
		$this->setup_objects( $taxonomy, $custom_field_id, $sync_direction );
		$this->register_hooks();

		/**
		 * Broadcast that this class is active.
		 *
		 * @since 1.0
		 *
		 * @param object $this The object being initialised.
		 * @param string $taxonomy The slug of the WordPress Taxonomy.
		 * @param int $custom_field_id The numeric ID of the CiviCRM Custom Field.
		 */
		do_action( 'wpcv_tax_field_sync/base/loaded', $this, $taxonomy, $custom_field_id );

	}

	/**
	 * Includes files.
	 *
	 * @since 1.0
	 */
	public function include_files() {

		// Load our class files.
		include_once WPCV_TAX_FIELD_SYNC_PATH . 'includes/class-sync-civicrm.php';
		include_once WPCV_TAX_FIELD_SYNC_PATH . 'includes/class-sync-wordpress.php';
		include_once WPCV_TAX_FIELD_SYNC_PATH . 'includes/class-sync-mapper.php';

	}

	/**
	 * Instantiates objects.
	 *
	 * @since 1.0
	 *
	 * @param string $taxonomy The slug of the WordPress Taxonomy.
	 * @param int $custom_field_id The numeric ID of the CiviCRM Custom Field.
	 * @param string $sync_direction The sync direction. Can be 'both', 'wp_to_civicrm' or 'civicrm_to_wp'. Default 'both'.
	 */
	public function setup_objects( $taxonomy, $custom_field_id, $sync_direction = 'both' ) {

		// Initialise objects.
		$this->civicrm = new WPCV_Tax_Field_Sync_CiviCRM( $this, $custom_field_id );
		$this->wordpress = new WPCV_Tax_Field_Sync_WordPress( $this, $taxonomy );
		$this->mapper = new WPCV_Tax_Field_Sync_Mapper( $this, $sync_direction );

	}

	/**
	 * Register WordPress hooks.
	 *
	 * @since 1.0
	 */
	public function register_hooks() {

	}

	/**
	 * Write to the error log.
	 *
	 * @since 1.0
	 *
	 * @param array $data The data to write to the log file.
	 */
	public function log_error( $data = [] ) {

		// Skip if not debugging.
		if ( WPCV_TAX_FIELD_SYNC_DEBUG === false ) {
			return;
		}

		// Skip if empty.
		if ( empty( $data ) ) {
			return;
		}

		// Format data.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		$error = print_r( $data, true );

		// Write to log file.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $error );

	}

}
