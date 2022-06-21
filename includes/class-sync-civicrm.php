<?php
/**
 * CiviCRM Class.
 *
 * Handles CiviCRM functionality.
 *
 * @package WPCV_Tax_Field_Sync
 * @since 1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Class.
 *
 * A class that encapsulates CiviCRM functionality.
 *
 * @package WPCV_Tax_Field_Sync
 */
class WPCV_Tax_Field_Sync_CiviCRM {

	/**
	 * Sync object.
	 *
	 * @since 1.0
	 * @access public
	 * @var object $sync The Sync object.
	 */
	public $sync;

	/**
	 * WordPress object.
	 *
	 * @since 1.0
	 * @access public
	 * @var object $wordpress The WordPress object.
	 */
	public $wordpress;

	/**
	 * Custom Field ID.
	 *
	 * @since 1.0
	 * @access public
	 * @var int $custom_field_id The Custom Field ID.
	 */
	public $custom_field_id = 0;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param object $sync The Sync object.
	 * @param int $custom_field_id The numeric ID of the CiviCRM Custom Field.
	 */
	public function __construct( $sync, $custom_field_id = 0 ) {

		// Store reference to Sync object.
		$this->sync = $sync;

		// Save Custom Field ID.
		$this->custom_field_id = $custom_field_id;

		// Init when this plugin is loaded.
		add_action( 'wpcv_tax_field_sync/base/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Initialises this object.
	 *
	 * @since 1.0
	 */
	public function initialise() {

		// Bail if there's no Custom Field ID.
		if ( empty( $this->custom_field_id ) ) {
			return;
		}

		// Store references.
		$this->wordpress = $this->sync->wordpress;

		// Bootstrap class.
		$this->register_hooks();

		/**
		 * Broadcast that this class is active.
		 *
		 * @since 1.0
		 */
		do_action( 'wpcv_tax_field_sync/civicrm/loaded' );

	}

	/**
	 * Register WordPress hooks.
	 *
	 * @since 1.0
	 */
	public function register_hooks() {

		// Add CiviCRM listeners once CiviCRM is available.
		add_action( 'civicrm_config', [ $this, 'civicrm_config' ], 10 );

		/*
		// Intercept CiviCRM Custom Field settings udpates.
		add_action( 'civicrm_postSave_civicrm_custom_field', [ $this, 'custom_field_edited' ], 10 );
		*/

		/*
		// Trace utils.
		add_action( 'civicrm_pre', [ $this, 'trace_pre' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'trace_post' ], 10, 4 );
		*/

	}

	// -------------------------------------------------------------------------

	/**
	 * Check if CiviCRM is initialised.
	 *
	 * @since 1.0
	 *
	 * @return bool True if CiviCRM initialised, false otherwise.
	 */
	public function is_initialised() {

		// Init only when CiviCRM is fully installed.
		if ( ! defined( 'CIVICRM_INSTALLED' ) ) {
			return false;
		}
		if ( ! CIVICRM_INSTALLED ) {
			return false;
		}

		// Bail if no CiviCRM init function.
		if ( ! function_exists( 'civi_wp' ) ) {
			return false;
		}

		// Try and initialise CiviCRM.
		return civi_wp()->initialize();

	}

	// -------------------------------------------------------------------------

	/**
	 * Callback for "civicrm_config".
	 *
	 * @since 1.0
	 *
	 * @param object $config The CiviCRM config object.
	 */
	public function civicrm_config( &$config ) {

		// Add CiviCRM listeners once CiviCRM is available.
		$this->hooks_civicrm_add();

	}

	/**
	 * Add listeners for CiviCRM Option Value operations.
	 *
	 * @since 1.0
	 */
	public function hooks_civicrm_add() {

		// Add callback for CiviCRM "preInsert" hook.
		Civi::service( 'dispatcher' )->addListener(
			'civi.dao.preInsert',
			[ $this, 'option_value_created_pre' ],
			-100 // Default priority.
		);

		// Add callback for CiviCRM "postInsert" hook.
		Civi::service( 'dispatcher' )->addListener(
			'civi.dao.postInsert',
			[ $this, 'option_value_created' ],
			-100 // Default priority.
		);

		/*
		 * Add callback for CiviCRM "preUpdate" hook.
		 *
		 * @see https://lab.civicrm.org/dev/core/issues/1638
		 */
		Civi::service( 'dispatcher' )->addListener(
			'civi.dao.preUpdate',
			[ $this, 'option_value_edited_pre' ],
			-100 // Default priority.
		);

		// Add callback for CiviCRM "postUpdate" hook.
		Civi::service( 'dispatcher' )->addListener(
			'civi.dao.postUpdate',
			[ $this, 'option_value_edited' ],
			-100 // Default priority.
		);

		/*
		 * Add callback for CiviCRM "preDelete" hook.
		 *
		 * @see https://github.com/civicrm/civicrm-core/pull/23834
		 */
		Civi::service( 'dispatcher' )->addListener(
			'civi.dao.preDelete',
			[ $this, 'option_value_deleted_pre' ],
			-100 // Default priority.
		);

	}

	/**
	 * Remove listeners from CiviCRM Option Value operations.
	 *
	 * @since 1.0
	 */
	public function hooks_civicrm_remove() {

		// Remove callback for CiviCRM "preInsert" hook.
		Civi::service( 'dispatcher' )->removeListener(
			'civi.dao.preInsert',
			[ $this, 'option_value_created_pre' ]
		);

		// Remove callback for CiviCRM "postInsert" hook.
		Civi::service( 'dispatcher' )->removeListener(
			'civi.dao.postInsert',
			[ $this, 'option_value_created' ]
		);

		/*
		 * Remove callback for CiviCRM "preUpdate" hook.
		 *
		 * @see https://lab.civicrm.org/dev/core/issues/1638
		 */
		Civi::service( 'dispatcher' )->removeListener(
			'civi.dao.preUpdate',
			[ $this, 'option_value_edited_pre' ]
		);

		// Remove callback for CiviCRM "postUpdate" hook.
		Civi::service( 'dispatcher' )->removeListener(
			'civi.dao.postUpdate',
			[ $this, 'option_value_edited' ]
		);

		/*
		 * Remove callback for CiviCRM "preDelete" hook.
		 *
		 * @see https://github.com/civicrm/civicrm-core/pull/23834
		 */
		Civi::service( 'dispatcher' )->removeListener(
			'civi.dao.preDelete',
			[ $this, 'option_value_deleted_pre' ]
		);

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets the CiviCRM Option Group data for a given ID.
	 *
	 * @since 1.0
	 *
	 * @param string|integer $option_group_id The numeric ID of the Option Group.
	 * @return array|bool $option_group An array of Option Group data, or false on failure.
	 */
	public function option_group_get_by_id( $option_group_id ) {

		// Init return.
		$option_group = false;

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $option_group;
		}

		// Build params to get Option Group data.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $option_group_id,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'OptionGroup', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $option_group;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $option_group;
		}

		// The result set should contain only one item.
		$option_group = array_pop( $result['values'] );

		// --<
		return $option_group;

	}

	/**
	 * Gets the CiviCRM Option Group data for a given Custom Field ID.
	 *
	 * @since 1.0
	 *
	 * @param integer $custom_field_id The numeric ID of the Custom Field.
	 * @return array $option_group The array of Option Group data, or empty on failure.
	 */
	public function option_group_get_by_field_id( $custom_field_id ) {

		// Init return.
		$option_group = [];

		// Get the full Custom Field from the synced Custom Field ID.
		$custom_field = $this->custom_field_get_by_id( $custom_field_id );

		/*
		$e = new Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'custom_field' => $custom_field,
			//'backtrace' => $trace,
		], true ) );
		*/

		if ( empty( $custom_field ) ) {
			return $option_group;
		}

		// Get the Option Group to which this Option Value is attached.
		$option_group = $this->option_group_get_by_id( $custom_field['option_group_id'] );

		/*
		$e = new Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'option_group' => $option_group,
			//'backtrace' => $trace,
		], true ) );
		*/

		// --<
		return $option_group;

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets the CiviCRM Option Value data for a given Custom Field ID.
	 *
	 * @since 1.0
	 *
	 * @param integer $custom_field_id The numeric ID of the Custom Field.
	 * @return array $option_values The array of Option Value data, or empty on failure.
	 */
	public function option_values_get_by_field_id( $custom_field_id ) {

		// Init return.
		$option_values = [];

		// Get the Option Group for this Custom Field.
		$option_group = $this->option_group_get_by_field_id( $custom_field_id );

		/*
		$e = new Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'option_group' => $option_group,
			//'backtrace' => $trace,
		], true ) );
		*/

		if ( empty( $option_group ) ) {
			return $option_values;
		}

		// Get the Option Group to which this Option Value is attached.
		$option_values = $this->option_values_get_by_group_id( $option_group['id'] );

		/*
		$e = new Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'option_values' => $option_values,
			//'backtrace' => $trace,
		], true ) );
		*/

		// --<
		return $option_values;

	}

	/**
	 * Gets the CiviCRM Option Value data for a given ID.
	 *
	 * @since 1.0
	 *
	 * @param string|integer $option_group_id The numeric ID of the Option Group.
	 * @return array $option_values The array of Option Value data, or empty on failure.
	 */
	public function option_values_get_by_group_id( $option_group_id ) {

		// Init return.
		$option_values = [];

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $option_values;
		}

		// Build params to get Option Value data.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'option_group_id' => $option_group_id,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'OptionValue', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $option_values;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $option_values;
		}

		// We want the result set.
		$option_values = $result['values'];

		// --<
		return $option_values;

	}

	/**
	 * Gets the CiviCRM Option Value data for a given ID.
	 *
	 * @since 1.0
	 *
	 * @param string|integer $option_value_id The numeric ID of the Option Value.
	 * @return array|bool $option_value An array of Option Value data, or false on failure.
	 */
	public function option_value_get_by_id( $option_value_id ) {

		// Init return.
		$option_value = false;

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $option_value;
		}

		// Build params to get Option Value data.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $option_value_id,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'OptionValue', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $option_value;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $option_value;
		}

		// The result set should contain only one item.
		$option_value = array_pop( $result['values'] );

		// --<
		return $option_value;

	}

	/**
	 * Get a CiviCRM Option Value by "value".
	 *
	 * @since 1.0
	 *
	 * @param mixed $value The value of a CiviCRM Option Value.
	 * @param int $option_group_id The numeric ID of the CiviCRM Option Group.
	 * @return array|bool $option_value CiviCRM Event Type data, or false on failure.
	 */
	public function option_value_get_by_value( $value, $option_group_id ) {

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $option_value;
		}

		// Define params to get item.
		$params = [
			'version' => 3,
			'option_group_id' => $option_group_id,
			'value' => $value,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'OptionValue', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return false;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return false;
		}

		// The result set should contain only one item.
		$event_type = array_pop( $result['values'] );

		// --<
		return $event_type;

	}

	/**
	 * Gets an Option Value given a Term.
	 *
	 * @since 1.0
	 *
	 * @param object $term The Term object.
	 * @return array|bool $option_value The CiviCRM Option Value, or false on failure.
	 */
	public function option_value_get_by_term( $term ) {

		// Init return.
		$option_value = false;

		// Get the full Option Value.
		$option_value = $this->option_value_get_by_term_id( $term->term_id );

		// --<
		return $option_value;

	}

	/**
	 * Gets an Option Value given a Term.
	 *
	 * @since 1.0
	 *
	 * @param int $term_id The numeric ID of the Term.
	 * @return array|bool $option_value The CiviCRM Option Value, or false on failure.
	 */
	public function option_value_get_by_term_id( $term_id ) {

		// Init return.
		$option_value = false;

		// Get the ID from Term meta.
		$option_value_id = $this->wordpress->option_value_id_get( $term->term_id );
		if ( $option_value_id === false ) {
			return $option_value;
		}

		// Get the full Option Value.
		$option_value = $this->option_value_get_by_id( $option_value_id );

		// --<
		return $option_value;

	}

	/**
	 * Gets the ID of an Option Value given a Term.
	 *
	 * @since 1.0
	 *
	 * @param object $term The Term object.
	 * @return int|bool $option_value_id The CiviCRM Option Value ID, or false on failure.
	 */
	public function option_value_id_get_by_term( $term ) {

		// Get the ID from Term meta.
		$option_value_id = $this->option_value_id_get_by_term_id( $term->term_id );

		// --<
		return $option_value_id;

	}

	/**
	 * Gets the ID of an Option Value given a Term ID.
	 *
	 * @since 1.0
	 *
	 * @param int $term_id The numeric ID of the Term.
	 * @return int|bool $option_value_id The CiviCRM Option Value ID, or false on failure.
	 */
	public function option_value_id_get_by_term_id( $term_id ) {

		// Get the ID from Term meta.
		$option_value_id = $this->wordpress->option_value_id_get( $term_id );

		// --<
		return $option_value_id;

	}

	// -------------------------------------------------------------------------

	/**
	 * Callback for the CiviCRM 'civi.dao.preInsert' hook.
	 *
	 * This hook was introduced in CiviCRM 5.26.0:
	 *
	 * @see https://lab.civicrm.org/dev/core/issues/1638
	 *
	 * @since 1.0
	 *
	 * @param object $event The Event object.
	 * @param string $hook The hook name.
	 */
	public function option_value_created_pre( $event, $hook ) {

		// Extract Option Value for this hook.
		$option_value =& $event->object;

		// Bail if this isn't the type of object we're after.
		if ( ! ( $option_value instanceof CRM_Core_DAO_OptionValue ) ) {
			return;
		}

		// Bail if there's no Option Group ID.
		if ( empty( $option_value->option_group_id ) ) {
			return;
		}

		// Get the Custom Field to which this Option Value is attached.
		$custom_field = $this->custom_field_get_by_option_group_id( $option_value->option_group_id );
		if ( empty( $custom_field ) ) {
			return;
		}

		// Bail if it's not the synced Custom Field ID.
		if ( (int) $custom_field['id'] !== $this->custom_field_id ) {
			return;
		}

		// Make sure "value" is the same as "label".
		$option_value->value = $option_value->label;

	}

	/**
	 * Callback for the CiviCRM 'civi.dao.postInsert' hook.
	 *
	 * @since 1.0
	 *
	 * @param object $event The Event object.
	 * @param string $hook The hook name.
	 */
	public function option_value_created( $event, $hook ) {

		// Extract Option Value for this hook.
		$option_value =& $event->object;

		// Bail if this isn't the type of object we're after.
		if ( ! ( $option_value instanceof CRM_Core_DAO_OptionValue ) ) {
			return;
		}

		// Bail if there's no Option Value ID.
		if ( empty( $option_value->id ) ) {
			return;
		}

		// Bail if there's no Option Group ID.
		if ( empty( $option_value->option_group_id ) ) {
			return;
		}

		// Get the Custom Field to which this Option Value is attached.
		$custom_field = $this->custom_field_get_by_option_group_id( $option_value->option_group_id );
		if ( empty( $custom_field ) ) {
			return;
		}

		// Bail if it's not the synced Custom Field ID.
		if ( (int) $custom_field['id'] !== $this->custom_field_id ) {
			return;
		}

		// Get the full Option Value.
		$option_value_full = $this->option_value_get_by_id( $option_value->id );
		if ( $option_value_full === false ) {
			return;
		}

		// Add description if present.
		$description = '';
		if ( ! empty( $option_value_full['description'] ) && $option_value_full['description'] !== 'null' ) {
			$description = $option_value_full['description'];
		}

		// Construct Term data.
		$term_data = [
			'id' => $option_value->id,
			'label' => $option_value_full['label'],
			'name' => $option_value_full['label'],
			'description' => $description,
		];

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'option_value' => $option_value,
			'custom_field' => $custom_field,
			'option_value_full' => $option_value_full,
			'term_data' => $term_data,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Create the Term.
		$result = $this->wordpress->term_create( $term_data );

	}

	/**
	 * Callback for the CiviCRM 'civi.dao.preUpdate' hook.
	 *
	 * This hook was introduced in CiviCRM 5.26.0:
	 *
	 * @see https://lab.civicrm.org/dev/core/issues/1638
	 *
	 * @since 1.0
	 *
	 * @param object $event The Event object.
	 * @param string $hook The hook name.
	 */
	public function option_value_edited_pre( $event, $hook ) {

		// Extract Option Value for this hook.
		$option_value =& $event->object;

		// Bail if this isn't the type of object we're after.
		if ( ! ( $option_value instanceof CRM_Core_DAO_OptionValue ) ) {
			return;
		}

		// Bail if there's no Option Group ID.
		if ( empty( $option_value->option_group_id ) ) {
			return;
		}

		// Get the Custom Field to which this Option Value is attached.
		$custom_field = $this->custom_field_get_by_option_group_id( $option_value->option_group_id );
		if ( empty( $custom_field ) ) {
			return;
		}

		// Bail if it's not the synced Custom Field ID.
		if ( (int) $custom_field['id'] !== $this->custom_field_id ) {
			return;
		}

		// Make sure "value" is the same as "label".
		$option_value->value = $option_value->label;

	}

	/**
	 * Callback for the CiviCRM Add/Edit Option Value postSave hook.
	 *
	 * The idea here is to listen for Option Value changes in Custom Fields that
	 * are mapped to a Taxonomy and update the Terms accordingly.
	 *
	 * CiviCRM Option Groups and Custom Fields don't have a way of saving meta
	 * data, so the only approach that I can see right now is to use a plugin
	 * setting that holds the mapping data. For now, we use a constant.
	 *
	 * @since 1.0
	 *
	 * @param object $event The Event object.
	 * @param string $hook The hook name.
	 */
	public function option_value_edited( $event, $hook ) {

		// Extract Option Value for this hook.
		$option_value =& $event->object;

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'option_value' => $option_value,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Bail if this isn't the type of object we're after.
		if ( ! ( $option_value instanceof CRM_Core_DAO_OptionValue ) ) {
			return;
		}

		// Bail if there's no Option Value ID.
		if ( empty( $option_value->id ) ) {
			return;
		}

		// Bail if there's no Option Group ID.
		if ( empty( $option_value->option_group_id ) ) {
			return;
		}

		// Get the Custom Field to which this Option Value is attached.
		$custom_field = $this->custom_field_get_by_option_group_id( $option_value->option_group_id );
		if ( empty( $custom_field ) ) {
			return;
		}

		// Bail if it's not the synced Custom Field ID.
		if ( (int) $custom_field['id'] !== $this->custom_field_id ) {
			return;
		}

		// Get the full Option Value.
		$option_value_full = $this->option_value_get_by_id( $option_value->id );
		if ( $option_value_full === false ) {
			return;
		}

		// Add description if present.
		$description = '';
		if ( ! empty( $option_value_full['description'] ) && $option_value_full['description'] !== 'null' ) {
			$description = $option_value_full['description'];
		}

		// Construct Term data.
		$term_data = [
			'id' => $option_value->id,
			'label' => $option_value_full['label'],
			'name' => $option_value_full['label'],
			'description' => $description,
		];

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'option_value' => $option_value,
			'custom_field' => $custom_field,
			'option_value_full' => $option_value_full,
			'term_data' => $term_data,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Update the Term.
		$result = $this->wordpress->term_update( $term_data );

	}

	/**
	 * Callback for the CiviCRM 'civi.dao.preDelete' hook.
	 *
	 * @since 1.0
	 *
	 * @param object $event The Event object.
	 * @param string $hook The hook name.
	 */
	public function option_value_deleted_pre( $event, $hook ) {

		// Extract Option Value for this hook.
		$option_value =& $event->object;

		// Bail if this isn't the type of object we're after.
		if ( ! ( $option_value instanceof CRM_Core_DAO_OptionValue ) ) {
			return;
		}

		// Bail if there's no Option Value ID.
		if ( empty( $option_value->id ) ) {
			return;
		}

		// Bail if there's no Option Group ID.
		if ( empty( $option_value->option_group_id ) ) {
			return;
		}

		// Get the Custom Field to which this Option Value is attached.
		$custom_field = $this->custom_field_get_by_option_group_id( $option_value->option_group_id );
		if ( empty( $custom_field ) ) {
			return;
		}

		// Bail if it's not the synced Custom Field ID.
		if ( (int) $custom_field['id'] !== $this->custom_field_id ) {
			return;
		}

		// Bail if there's no Term to delete.
		$term = $this->wordpress->term_get_by_option_value_id( $option_value->id );
		if ( $term === false ) {
			return;
		}

		// Delete the Term.
		$success = $this->wordpress->term_delete( $term->term_id );

	}

	// -------------------------------------------------------------------------

	/**
	 * Updates a synced Custom Field's Option Value.
	 *
	 * The mappings are:
	 *
	 * * Term "Name" -> Option Value "Label"
	 * * Term "Name" -> Option Value "Value"
	 * * Term "Description" -> Option Value "Description"
	 *
	 * The Op
	 *
	 * @since 1.0
	 *
	 * @param WP_Term $new_term The new Term in the synced Taxonomy.
	 * @param WP_Term $old_term The Term in the synced Taxonomy as it was before the update.
	 * @return int|bool $option_value_id The CiviCRM Option Value ID, or false on failure.
	 */
	public function option_value_update( $new_term, $old_term = null ) {

		// Sanity check.
		if ( ! ( $new_term instanceof WP_Term ) ) {
			return false;
		}

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return false;
		}

		// Get the full Custom Field from the synced Custom Field ID.
		$custom_field = $this->custom_field_get_by_id( $this->custom_field_id );
		if ( empty( $custom_field ) ) {
			return false;
		}

		// Do not sync if it has no Option Group ID.
		if ( empty( $custom_field['option_group_id'] ) ) {
			return false;
		}

		// Define params for the Option Value.
		$params = [
			'version' => 3,
			'option_group_id' => $custom_field['option_group_id'],
			'label' => $new_term->name,
			'value' => $new_term->name,
		];

		// If there is a description, apply content filters and add to params.
		if ( ! empty( $new_term->description ) ) {
			$params['description'] = $new_term->description;
		}

		// Try and get the synced Option Value ID.
		$option_value_id = $this->option_value_id_get_by_term( $new_term );

		// Trigger update if we find a synced Option Value ID.
		if ( $option_value_id !== false ) {
			$params['id'] = $option_value_id;
		}

		// Unhook CiviCRM.
		$this->hooks_civicrm_remove();

		// Create (or update) the Option Value.
		$result = civicrm_api( 'OptionValue', 'create', $params );

		// Rehook CiviCRM.
		$this->hooks_civicrm_add();

		// Log and bail if there's an error.
		if ( ! empty( $result['is_error'] ) ) {
			$e = new Exception();
			$trace = $e->getTraceAsString();
			$this->sync->log_error( [
				'method' => __METHOD__,
				'message' => $result['error_message'],
				'params' => $params,
				'backtrace' => $trace,
			] );
			return false;
		}

		// Success, grab Option Value ID.
		if ( isset( $result['id'] ) && is_numeric( $result['id'] ) && $result['id'] > 0 ) {
			$option_value_id = intval( $result['id'] );
		}

		// --<
		return $option_value_id;

	}

	/**
	 * Deletes a synced Custom Field's Option Value.
	 *
	 * @since 1.0
	 *
	 * @param object $term The synced Taxonomy Term.
	 * @return array|bool CiviCRM API data array on success, false on failure.
	 */
	public function option_value_delete( $term ) {

		// Sanity check.
		if ( ! is_object( $term ) ) {
			return false;
		}

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return false;
		}

		// Get ID of Option Value to delete.
		$option_value_id = $this->option_value_id_get_by_term( $term );
		if ( $option_value_id === false ) {
			return false;
		}

		// Define Option Value.
		$params = [
			'version' => 3,
			'id' => $option_value_id,
		];

		// Unhook CiviCRM.
		$this->hooks_civicrm_remove();

		// Delete the Option Value.
		$result = civicrm_api( 'OptionValue', 'delete', $params );

		// Rehook CiviCRM.
		$this->hooks_civicrm_add();

		// Log and bail if there's an error.
		if ( ! empty( $result['is_error'] ) ) {
			$e = new Exception();
			$trace = $e->getTraceAsString();
			$this->sync->log_error( [
				'method' => __METHOD__,
				'message' => $result['error_message'],
				'params' => $params,
				'backtrace' => $trace,
			] );
			return false;
		}

		// --<
		return $result;

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets all the Custom Groups.
	 *
	 * @since 1.0
	 *
	 * @return array $custom_groups The array of Custom Groups.
	 */
	public function custom_groups_get_all() {

		// Only do this once.
		static $pseudocache;
		if ( isset( $pseudocache ) ) {
			return $pseudocache;
		}

		// Init array to build.
		$custom_groups = [];

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $custom_groups;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'is_active' => 1,
			'options' => [
				'limit' => 0,
			],
			'api.CustomField.get' => [
				'is_active' => 1,
				'options' => [
					'limit' => 0,
				],
			],
		];

		// Call the API.
		$result = civicrm_api( 'CustomGroup', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && (int) $result['is_error'] === 1 ) {
			return $custom_groups;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $custom_groups;
		}

		// The result set is what we want.
		$custom_groups = $result['values'];

		// Set "cache".
		$pseudocache = $custom_groups;

		// --<
		return $custom_groups;

	}

	/**
	 * Get a Custom Group by its ID.
	 *
	 * @since 1.0
	 *
	 * @param integer $custom_group_id The numeric ID of the Custom Group.
	 * @return array $custom_group The array of Custom Group data.
	 */
	public function custom_group_get_by_id( $custom_group_id ) {

		// Init return.
		$custom_group = [];

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $custom_group;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $custom_group_id,
		];

		// Call the API.
		$result = civicrm_api( 'CustomGroup', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $custom_group;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $custom_group;
		}

		// The result set should contain only one item.
		$custom_group = array_pop( $result['values'] );

		// --<
		return $custom_group;

	}

	// -------------------------------------------------------------------------

	/**
	 * Filters a set of CiviCRM Custom Fields to return only those which are synced.
	 *
	 * @since 1.0
	 *
	 * @param array $custom_fields The array of CiviCRM Custom Fields.
	 * @return array $filtered The filtered array of CiviCRM Custom Fields.
	 */
	public function custom_fields_filter( $custom_fields ) {

		// Init return,
		$filtered = [];

		// Let's look at each Custom Field.
		foreach ( $custom_fields as $key => $field ) {

			// Bail if it's not the synced Custom Field ID.
			if ( $this->custom_field_id !== (int) $field['custom_field_id'] ) {
				continue;
			}

			// Add to filtered array.
			$filtered[ $key ] = $field;

		}

		// --<
		return $filtered;

	}

	/**
	 * Gets the CiviCRM Custom Field data for a given Custom Group ID.
	 *
	 * @since 1.0
	 *
	 * @param integer $custom_group_id The numeric ID of the Custom Group.
	 * @return array $fields An array of Custom Field data.
	 */
	public function custom_fields_get_by_group_id( $custom_group_id ) {

		// Init return.
		$fields = [];

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $fields;
		}

		// Build params to get Custom Group data.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'custom_group_id' => $custom_group_id,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'CustomField', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $fields;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $fields;
		}

		// The result set is what we want.
		$fields = $result['values'];

		// --<
		return $fields;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get a Custom Field by its ID.
	 *
	 * @since 1.0
	 *
	 * @param integer $custom_field_id The numeric ID of the Custom Field.
	 * @return array $custom_field The array of Custom Field data.
	 */
	public function custom_field_get_by_id( $custom_field_id ) {

		// Init return.
		$custom_field = [];

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $custom_field;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $custom_field_id,
		];

		// Call the API.
		$result = civicrm_api( 'CustomField', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $custom_field;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $custom_field;
		}

		// The result set should contain only one item.
		$custom_field = array_pop( $result['values'] );

		// --<
		return $custom_field;

	}

	/**
	 * Gets a Custom Field by its Option Group ID.
	 *
	 * @since 1.0
	 *
	 * @param integer $option_group_id The numeric ID of the Option Group.
	 * @return array $custom_group The array of Custom Group data.
	 */
	public function custom_field_get_by_option_group_id( $option_group_id ) {

		// Init return.
		$custom_group = [];

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $custom_group;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'option_group_id' => $option_group_id,
		];

		// Call the API.
		$result = civicrm_api( 'CustomField', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $custom_group;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $custom_group;
		}

		// The result set should contain only one item.
		$custom_group = array_pop( $result['values'] );

		// --<
		return $custom_group;

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets the values for a given CiviCRM Entity, Entity ID and set of Custom Fields.
	 *
	 * @since 1.0
	 *
	 * @param string $entity The name of the CiviCRM Entity.
	 * @param integer $entity_id The numeric ID of the CiviCRM Entity.
	 * @param array $custom_field_ids The Custom Field IDs to query.
	 * @return array $values The array of values.
	 */
	public function custom_field_values_get_for_entity( $entity, $entity_id, $custom_field_ids = [] ) {

		// Init return.
		$values = [];

		// Bail if we have no Custom Field IDs.
		if ( empty( $custom_field_ids ) ) {
			return $values;
		}

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $values;
		}

		// Format codes.
		$codes = [];
		foreach ( $custom_field_ids as $custom_field_id ) {
			$codes[] = 'custom_' . $custom_field_id;
		}

		// Define params to get queried Contact.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $entity_id,
			'return' => $codes,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		try {
			// Call the API for this Entity.
			$result = civicrm_api( $entity, 'get', $params );
		} catch ( CiviCRM_API3_Exception $e ) {
			// Bail if there's an error.
			return $values;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'params' => $params,
			'result' => $result,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $values;
		}

		// Overwrite return.
		foreach ( $result['values'] as $item ) {
			foreach ( $item as $key => $value ) {
				if ( substr( $key, 0, 7 ) == 'custom_' ) {
					$index = (int) str_replace( 'custom_', '', $key );
					$values[ $index ] = $value;
				}
			}
		}

		// --<
		return $values;

	}

	// -------------------------------------------------------------------------

	/**
	 * Callback for the CiviCRM Add/Edit Custom Field postSave hook.
	 *
	 * This method listens for changes to Custom Field settings and if they are
	 * mapped to a Taxonomy, attempts to update the Taxonomy accordingly.
	 *
	 * The same limitations that apply to the Option Value postSave hook also
	 * apply here.
	 *
	 * @see self::option_value_edited()
	 *
	 * @since 1.0
	 *
	 * @param object $objectRef The DAO object.
	 */
	public function custom_field_edited( $objectRef ) {

		// Bail if not Option Value save operation.
		if ( ! ( $objectRef instanceof CRM_Core_DAO_CustomField ) ) {
			return;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'objectRef' => $objectRef,
			//'backtrace' => $trace,
		], true ) );
		*/

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets the Entity needed to call the CiviCRM API from Custom Group data.
	 *
	 * We don't need the extra data documented here, but it's useful to know what
	 * the variations of Custom Group configurations are in case we do need them.
	 *
	 * For Contact:
	 *
	 * The "extends" value can be:
	 *
	 * * "Contact": All Contacts.
	 * * "Individual": Contacts of type "Individual".
	 * * "Household": Contacts of type "Household".
	 * * "Organization": Contacts of type "Organization".
	 *
	 * For "Contacts of type":
	 *
	 * The "extends_entity_column_value" contains the array of Contact Sub-Types.
	 *
	 * In all of the above cases, we need to call "Contact".
	 *
	 * For Activity:
	 *
	 * The "extends_entity_column_value" contains the array of Activity Type IDs.
	 *
	 * For Case:
	 *
	 * The "extends_entity_column_value" contains the array of Case Type IDs.
	 *
	 * For Event:
	 *
	 * The "extends_entity_column_value" contains the array of Event Type IDs.
	 *
	 * For Relationship:
	 *
	 * The "extends_entity_column_value" contains the array of Relationship Type IDs.
	 *
	 * For Address:
	 *
	 * There is no "extends_entity_column_id" or "extends_entity_column_value".
	 *
	 * For Participant:
	 *
	 * * Missing "extends_entity_column_id": All Participants.
	 * * Missing "extends_entity_column_value": All Participants.
	 *
	 * When the "extends_entity_column_id" value is present, it means that
	 * "extends_entity_column_value" contains an array where each items is:
	 *
	 * 1: The VALUE of the 'participant_role'
	 * 2: The ID of the CiviCRM Event
	 * 3: The VALUE of the 'event_type'
	 *
	 * @since 1.0
	 *
	 * @param array $custom_group The array of CiviCRM Custom Group data.
	 * @return string $entity_name The name of CiviCRM API Entity.
	 */
	public function entity_name_get( $custom_group ) {

		// Init return.
		$entity_name = '';

		// Get the top-level CiviCRM Entity.
		$extends = $custom_group['extends'];

		// Default to the CiviCRM API Entity.
		$entity_name = $extends;

		// Account for Contact variations.
		if ( in_array( $entity_name, [ 'Individual', 'Household', 'Organization' ] ) ) {
			$entity_name = 'Contact';
		}

		// --<
		return $entity_name;

	}

	/**
	 * Gets the full CiviCRM Entity for a given Entity ID and Entity Type.
	 *
	 * @since 1.0
	 *
	 * @param int $entity_id The numeric ID of the CiviCRM Entity.
	 * @param string $entity_name The name of the CiviCRM Entity.
	 * @return array $entity The array of CiviCRM Entity data, or empty if not found.
	 */
	public function entity_get( $entity_id, $entity_name ) {

		// Init return.
		$entity = [];

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $entity;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $entity_id,
		];

		try {
			// Call the API for this Entity.
			$result = civicrm_api( $entity_name, 'get', $params );
		} catch ( CiviCRM_API3_Exception $e ) {
			// Bail if there's an error.
			return $entity;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'params' => $params,
			'result' => $result,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $entity;
		}

		// The result set should contain only one item.
		$entity = array_pop( $result['values'] );

		// --<
		return $entity;

	}

	/**
	 * Gets the full CiviCRM Entity for a given Entity ID and Entity Type.
	 *
	 * @since 1.0
	 *
	 * @param string $entity_name The name of CiviCRM API Entity.
	 * @param array $params The array of CiviCRM API params.
	 * @return array|bool $entity The array of CiviCRM Entity data, or false on failure.
	 */
	public function entity_update( $entity_name, $params ) {

		// Init return.
		$entity = false;

		// Bail if there is no Entity ID.
		if ( empty( $params['id'] ) ) {
			return $entity;
		}

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $entity;
		}

		try {
			// Call the API for this Entity.
			$result = civicrm_api( $entity_name, 'create', $params );
		} catch ( CiviCRM_API3_Exception $e ) {
			// Bail if there's an error.
			return $entity;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'params' => $params,
			'result' => $result,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $entity;
		}

		// The result set should contain only one item.
		$entity = array_pop( $result['values'] );

		// --<
		return $entity;

	}

	// -------------------------------------------------------------------------

	/**
	 * Utility for tracing calls to hook_civicrm_pre.
	 *
	 * @since 1.0
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function trace_pre( $op, $objectName, $objectId, $objectRef ) {

		$e = new Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
			//'backtrace' => $trace,
		], true ) );

	}

	/**
	 * Utility for tracing calls to hook_civicrm_post.
	 *
	 * @since 1.0
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function trace_post( $op, $objectName, $objectId, $objectRef ) {

		$e = new Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
			//'backtrace' => $trace,
		], true ) );

	}

}
