<?php
/**
 * Plugin Name: WPCV Custom Field Taxonomy Sync
 * Plugin URI: https://github.com/WPCV/wpcv-tax-field-sync
 * GitHub Plugin URI: https://github.com/WPCV/wpcv-tax-field-sync
 * Description: Keeps a WordPress Taxonomy and a CiviCRM Custom Field in sync.
 * Author: WPCV
 * Version: 1.0.0
 * Author URI: https://github.com/WPCV
 * Requires at least: 5.7
 * Requires PHP: 7.1
 * Text Domain: wpcv-tax-field-sync
 * Domain Path: /languages
 *
 * @package WPCV_Tax_Field_Sync
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



// Set plugin version here.
define( 'WPCV_TAX_FIELD_SYNC_VERSION', '1.0.0' );

// Store reference to this file.
if ( ! defined( 'WPCV_TAX_FIELD_SYNC_FILE' ) ) {
	define( 'WPCV_TAX_FIELD_SYNC_FILE', __FILE__ );
}

// Store URL to this plugin's directory.
if ( ! defined( 'WPCV_TAX_FIELD_SYNC_URL' ) ) {
	define( 'WPCV_TAX_FIELD_SYNC_URL', plugin_dir_url( WPCV_TAX_FIELD_SYNC_FILE ) );
}

// Store path to this plugin's directory.
if ( ! defined( 'WPCV_TAX_FIELD_SYNC_PATH' ) ) {
	define( 'WPCV_TAX_FIELD_SYNC_PATH', plugin_dir_path( WPCV_TAX_FIELD_SYNC_FILE ) );
}

// Set debug flag.
if ( ! defined( 'WPCV_TAX_FIELD_SYNC_DEBUG' ) ) {
	define( 'WPCV_TAX_FIELD_SYNC_DEBUG', false );
}



/**
 * Plugin Class.
 *
 * A class that encapsulates this plugin's functionality.
 *
 * @since 1.0
 */
class WPCV_Tax_Field_Sync {

	/**
	 * Sync objects.
	 *
	 * @since 1.0
	 * @access public
	 * @var array
	 */
	public $sync_objects = [];

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 */
	public function __construct() {

		// Initialise this plugin.
		$this->initialise();

		/**
		 * Broadcast that this plugin is active.
		 *
		 * @since 1.0
		 */
		do_action( 'wpcv_tax_field_sync/loaded' );

	}

	/**
	 * Initialises this plugin.
	 *
	 * @since 1.0
	 */
	public function initialise() {

		// Only do this once.
		static $done;
		if ( isset( $done ) && $done === true ) {
			return;
		}

		// Bootstrap plugin.
		$this->translation();
		$this->include_files();
		$this->setup_objects();
		$this->register_hooks();

		// We're done.
		$done = true;

	}

	/**
	 * Includes class files.
	 *
	 * @since 1.0
	 */
	public function include_files() {

		// Load our class files.
		include_once WPCV_TAX_FIELD_SYNC_PATH . 'includes/class-sync-base.php';

	}

	/**
	 * Sets up this plugin's objects.
	 *
	 * @since 1.0
	 */
	public function setup_objects() {

		// Register a default Sync object when Profile Sync's ACF Loader is loaded.
		$this->register_sync_default();

	}

	/**
	 * Register WordPress hooks.
	 *
	 * @since 1.0
	 */
	public function register_hooks() {

	}

	/**
	 * Enables translation.
	 *
	 * @since 1.0
	 */
	public function translation() {

		// Load translations.
		// phpcs:ignore WordPress.WP.DeprecatedParameters.Load_plugin_textdomainParam2Found
		load_plugin_textdomain(
			'wpcv-tax-field-sync', // Unique name.
			false, // Deprecated argument.
			dirname( plugin_basename( WPCV_TAX_FIELD_SYNC_FILE ) ) . '/languages/' // Relative path to files.
		);

	}

	// -------------------------------------------------------------------------

	/**
	 * Instantiates a Sync object and returns reference.
	 *
	 * @since 1.0
	 *
	 * @param string $taxonomy The slug of the WordPress Taxonomy.
	 * @param int $custom_field_id The numeric ID of the CiviCRM Custom Field.
	 * @param string $sync_direction The sync direction. Can be 'both', 'wp_to_civicrm' or 'civicrm_to_wp'. Default 'both'.
	 * @return WPCV_Tax_Field_Sync_Base $sync The Sync object reference.
	 */
	public function register_sync( $taxonomy, $custom_field_id, $sync_direction = 'both' ) {

		// Instantiate object.
		$sync = new WPCV_Tax_Field_Sync_Base( $taxonomy, $custom_field_id, $sync_direction );

		// --<
		return $sync;

	}

	/**
	 * Instantiates a default Sync object.
	 *
	 * @since 1.0
	 */
	public function register_sync_default() {

		// Bail if the constants aren't defined.
		if ( ! defined( 'WPCV_TAX_FIELD_SYNC_CUSTOM_FIELD_ID' ) ) {
			return;
		}
		if ( ! defined( 'WPCV_TAX_FIELD_SYNC_TAXONOMY' ) ) {
			return;
		}

		// Set Custom Field ID.
		$custom_field_id = WPCV_TAX_FIELD_SYNC_CUSTOM_FIELD_ID;

		// Set Taxonomy slug.
		$taxonomy = WPCV_TAX_FIELD_SYNC_TAXONOMY;

		// Set sync direction.
		$sync_direction = 'both';
		if ( defined( 'WPCV_TAX_FIELD_SYNC_DIRECTION' ) ) {
			$sync_direction = WPCV_TAX_FIELD_SYNC_DIRECTION;
		}
		// Bootstrap initial Sync object.
		$this->sync_objects[] = $this->register_sync( $taxonomy, $custom_field_id, $sync_direction );

	}

}



/**
 * Loads plugin if not yet loaded and returns reference.
 *
 * @since 1.0
 *
 * @return WPCV_Tax_Field_Sync $plugin The plugin reference.
 */
function wpcv_tax_field_sync() {

	// Instantiate plugin if not yet instantiated.
	static $plugin;
	if ( ! isset( $plugin ) ) {
		$plugin = new WPCV_Tax_Field_Sync();
	}

	// --<
	return $plugin;

}

// Load after CiviCRM Profile Sync's ACF Loader has loaded.
add_action( 'cwps/acf/loaded', 'wpcv_tax_field_sync', 19 );

/**
 * Performs plugin activation tasks.
 *
 * @since 1.0
 */
function wpcv_tax_field_sync_activate() {

	/**
	 * Broadcast that this plugin has been activated.
	 *
	 * @since 1.0
	 */
	do_action( 'wpcv_tax_field_sync/activated' );

}

// Activation.
register_activation_hook( __FILE__, 'wpcv_tax_field_sync_activate' );

/**
 * Performs plugin deactivation tasks.
 *
 * @since 1.0
 */
function wpcv_tax_field_sync_deactivated() {

	/**
	 * Broadcast that this plugin has been deactivated.
	 *
	 * @since 1.0
	 */
	do_action( 'wpcv_tax_field_sync/deactivated' );

}

// Deactivation.
register_deactivation_hook( __FILE__, 'wpcv_tax_field_sync_deactivated' );

/*
 * Uninstall uses the 'uninstall.php' method.
 *
 * @see https://developer.wordpress.org/reference/functions/register_uninstall_hook/
 */



/**
 * Registers a Sync object and returns reference.
 *
 * This is a global scope function in case some people are uncomfortable with
 * addressing class methods.
 *
 * @since 1.0
 *
 * @param string $taxonomy The slug of the WordPress Taxonomy.
 * @param int $custom_field_id The numeric ID of the CiviCRM Custom Field.
 * @param string $sync_direction The sync direction. Can be 'both', 'wp_to_civicrm' or 'civicrm_to_wp'. Default 'both'.
 * @return WPCV_Tax_Field_Sync_Base $sync The Sync object reference.
 */
function wpcv_tax_field_register( $taxonomy, $custom_field_id, $sync_direction = 'both' ) {

	// Returns a Sync object.
	return wpcv_tax_field_sync()->register_sync( $taxonomy, $custom_field_id, $sync_direction );

}
