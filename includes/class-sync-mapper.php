<?php
/**
 * Mapper Class.
 *
 * Handles sync of Custom Field values and Post Taxonomy Terms.
 *
 * @package WPCV_Tax_Field_Sync
 * @since 1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Mapper Class.
 *
 * A class that encapsulates sync of Custom Field values and Post Taxonomy Terms.
 *
 * @package WPCV_Tax_Field_Sync
 */
class WPCV_Tax_Field_Sync_Mapper {

	/**
	 * Sync object.
	 *
	 * @since 1.0
	 * @access public
	 * @var object $sync The Sync object.
	 */
	public $sync;

	/**
	 * CiviCRM object.
	 *
	 * @since 1.0
	 * @access public
	 * @var object $civicrm The CiviCRM object.
	 */
	public $civicrm;

	/**
	 * WordPress object.
	 *
	 * @since 1.0
	 * @access public
	 * @var object $wordpress The WordPress object.
	 */
	public $wordpress;

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param object $sync The Sync object.
	 */
	public function __construct( $sync ) {

		// Store reference to Sync object.
		$this->sync = $sync;

		// Init when this plugin is loaded.
		add_action( 'wpcv_tax_field_sync/base/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Initialises this object.
	 *
	 * @since 1.0
	 */
	public function initialise() {

		// Store references.
		$this->civicrm = $this->sync->civicrm;
		$this->wordpress = $this->sync->wordpress;

		// Bootstrap class.
		$this->register_hooks();

		/**
		 * Broadcast that this class is active.
		 *
		 * @since 1.0
		 */
		do_action( 'wpcv_tax_field_sync/mapper/loaded' );

	}

	/**
	 * Registers hooks.
	 *
	 * @since 1.0
	 */
	public function register_hooks() {

		// Add all hooks.
		$this->hooks_civicrm_add();
		$this->hooks_wordpress_add();

	}

	/**
	 * Unregisters hooks.
	 *
	 * @since 1.0
	 */
	public function unregister_hooks() {

		// Remove all hooks.
		$this->hooks_civicrm_remove();
		$this->hooks_wordpress_remove();

	}

	/**
	 * Register CiviCRM hooks.
	 *
	 * @since 1.0
	 */
	public function hooks_wordpress_add() {

		// Intercept Post ACF Fields saved.
		add_action( 'cwps/acf/activity/acf_fields_saved', [ $this, 'post_saved' ], 20 );
		add_action( 'cwps/acf/contact/acf_fields_saved', [ $this, 'post_saved' ], 20 );
		add_action( 'cwps/acf/participant-cpt/acf_fields_saved', [ $this, 'post_saved' ], 20 );
		add_action( 'cwps/acf/participant/acf_fields_saved', [ $this, 'post_saved' ], 20 );
		add_action( 'ceo/acf/event/acf_fields_saved', [ $this, 'post_saved' ], 20 );

	}

	/**
	 * Remove WordPress hooks.
	 *
	 * @since 1.0
	 */
	public function hooks_wordpress_remove() {

		// Remove WordPress callbacks.
		remove_action( 'cwps/acf/activity/acf_fields_saved', [ $this, 'post_saved' ], 20 );
		remove_action( 'cwps/acf/contact/acf_fields_saved', [ $this, 'post_saved' ], 20 );
		remove_action( 'cwps/acf/participant-cpt/acf_fields_saved', [ $this, 'post_saved' ], 20 );
		remove_action( 'cwps/acf/participant/acf_fields_saved', [ $this, 'post_saved' ], 20 );
		remove_action( 'ceo/acf/event/acf_fields_saved', [ $this, 'post_saved' ], 20 );

	}

	/**
	 * Register CiviCRM hooks.
	 *
	 * @since 1.0
	 */
	public function hooks_civicrm_add() {

		// Hook into Profile Sync after Custom Fields have been edited.
		add_action( 'cwps/acf/mapper/civicrm/custom/edit/pre', [ $this, 'custom_pre_edit' ], 10 );
		add_action( 'cwps/acf/civicrm/custom_field/custom_edited', [ $this, 'custom_edited' ], 10, 2 );

	}

	/**
	 * Remove CiviCRM hooks.
	 *
	 * @since 1.0
	 */
	public function hooks_civicrm_remove() {

		// Remove CiviCRM callbacks.
		remove_action( 'cwps/acf/mapper/civicrm/custom/edit/pre', [ $this, 'custom_pre_edit' ], 10 );
		remove_action( 'cwps/acf/civicrm/custom_field/custom_edited', [ $this, 'custom_edited' ], 10 );

	}

	// -------------------------------------------------------------------------

	/**
	 * Intercept when changes have been made to the Post.
	 *
	 * @since 1.0
	 *
	 * @param array $args The array of data.
	 */
	public function post_saved( $args ) {

		// Bail early if this is not a Post, e.g. User "user_N".
		if ( ! is_numeric( $args['post_id'] ) ) {
			return;
		}

		// Bail if the edited Post does not have the synced Taxonomy.
		$taxonomies = get_post_taxonomies( $args['post_id'] );
		if ( ! in_array( $this->wordpress->taxonomy, $taxonomies ) ) {
			return;
		}

		/*
		$e = new Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'args' => $args,
			'current_action' => current_action(),
			//'backtrace' => $trace,
		], true ) );
		*/

		// Get the Terms assigned to the Post.
		$terms = $this->wordpress->terms_get_for_post( $args['post_id'] );
		$term_names = wp_list_pluck( $terms, 'name' );

		/*
		$e = new Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'terms' => $terms,
			'term_names' => $term_names,
			//'backtrace' => $trace,
		], true ) );
		*/

		/*
		// Get the full synced Custom Field.
		$custom_field = $this->civicrm->custom_field_get_by_id( $this->civicrm->custom_field_id );
		if ( empty( $custom_field ) ) {
			return false;
		}

		// Get the full Custom Group.
		$custom_group = $this->civicrm->custom_group_get_by_id( $custom_field['custom_group_id'] );
		if ( empty( $custom_group ) ) {
			return false;
		}
		*/

		// Let's Entity from the current action.
		switch ( current_action() ) {

			case 'cwps/acf/activity/acf_fields_saved':
				$entity_name = 'Activity';
				$entity_id = $args['activity_id'];
				break;

			case 'cwps/acf/contact/acf_fields_saved':
				$entity_name = 'Contact';
				$entity_id = $args['contact_id'];
				break;

			case 'cwps/acf/participant-cpt/acf_fields_saved':
			case 'cwps/acf/participant/acf_fields_saved':
				$entity_name = 'Participant';
				$entity_id = $args['participant_id'];
				break;

			case 'ceo/acf/event/acf_fields_saved':
				$entity_name = 'Event';
				$entity_id = $args['event_id'];
				break;

		}

		/*
		$e = new Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'entity_name' => $entity_name,
			'entity_id' => $entity_id,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Get the full Entity so we're not guessing.
		$entity = $this->civicrm->entity_get( $entity_id, $entity_name );

		/*
		$e = new Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'entity' => $entity,
			//'backtrace' => $trace,
		], true ) );
		*/

		if ( empty( $entity ) ) {
			return;
		}

		// Init params.
		$params = [
			'version' => 3,
			'id' => $entity_id,
			'custom_' . $this->civicrm->custom_field_id => $term_names,
		];

		// The Contact API requires Contact Type.
		if ( $entity_name === 'Contact' ) {
			$params['contact_type'] = $custom_group['extends'];
		}

		// The Activity API requires "source_contact_id".
		if ( $entity_name === 'Activity' ) {
			$params['source_contact_id'] = $entity['source_contact_id'];
		}

		// The Particpant API requires "contact_id" and "event_id".
		if ( $entity_name === 'Particpant' ) {
			$params['contact_id'] = $entity['contact_id'];
			$params['event_id'] = $entity['event_id'];
		}

		/*
		$e = new Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'params' => $params,
			'entity_name' => $entity_name,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Remove CiviCRM hooks.
		$this->hooks_civicrm_remove();

		// Okay, let's update it.
		$result = $this->civicrm->entity_update( $entity_name, $params );

		// Add CiviCRM hooks.
		$this->hooks_civicrm_add();

	}

	// -------------------------------------------------------------------------

	/**
	 * Acts when a set of CiviCRM Custom Fields is about to be updated.
	 *
	 * We need to retrieve the Custom Field values for any synced Fields before
	 * they are updated so that we can remove any corresponding Terms.
	 *
	 * @since 1.0
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function custom_pre_edit( $args ) {

		// Filter the edited Custom Fields to leave just those which are synced.
		$synced_custom_fields = $this->civicrm->custom_fields_filter( $args['custom_fields'] );
		if ( empty( $synced_custom_fields ) ) {
			return;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'synced_custom_fields' => $synced_custom_fields,
			//'backtrace' => $trace,
		], true ) );
		*/

		// The CiviCRM Entity can be deduced from the "entity_table" entry.
		$entity = '';
		foreach( $synced_custom_fields as $synced_custom_field ) {
			if ( ! empty( $synced_custom_field['entity_table'] ) ) {
				$entity_table = $synced_custom_field['entity_table'];
				$entity = str_replace( 'civicrm_', '', $entity_table );
				break;
			}
		}

		// Sanity check.
		if ( empty( $entity ) ) {
			return;
		}

		// Grab the IDs of the Custom Fields.
		$custom_field_ids = wp_list_pluck( $synced_custom_fields, 'custom_field_id' );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'entity' => $entity,
			'entity_id' => $args['entity_id'],
			'custom_field_ids' => $custom_field_ids,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Get the current value of the Custom Field.
		$custom_values = $this->civicrm->custom_field_values_get_for_entity( $entity, $args['entity_id'], $custom_field_ids );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'custom_values' => $custom_values,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Sanity check.
		if ( empty( $custom_values ) ) {
			$custom_values = [];
		}

		// Sanity check values.
		array_walk( $custom_values, function( &$item ) {
			if ( empty( $item ) ) {
				$item = [];
			}
		} );

		// Store values before they are edited.
		$this->custom_pre_edit = [
			//'args' => $args,
			'entity' => $entity,
			'entity_id' => $args['entity_id'],
			'values' => $custom_values,
		];

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'custom_pre_edit' => $this->custom_pre_edit,
			//'backtrace' => $trace,
		], true ) );
		*/

	}

	/**
	 * Acts when a set of CiviCRM Custom Fields has been updated.
	 *
	 * @since 1.0
	 *
	 * @param array|bool $post_ids The array of mapped Post IDs, or false if not mapped.
	 * @param array $args The array of CiviCRM params.
	 */
	public function custom_edited( $post_ids, $args ) {

		/*
		$e = new Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'post_ids' => $post_ids,
			'args' => $args,
			'custom_pre_edit' => empty( $this->custom_pre_edit['values'] ) ? 'nope' : $this->custom_pre_edit['values'],
			//'backtrace' => $trace,
		], true ) );
		*/

		// Bail if there are no previous Custom Field values.
		if ( empty( $this->custom_pre_edit['values'] ) ) {
			return;
		}

		// Bail if there are no Post IDs.
		if ( empty( $post_ids ) ) {
			return;
		}

		// Filter the edited Custom Fields to leave just those which are synced.
		$synced_custom_fields = $this->civicrm->custom_fields_filter( $args['custom_fields'] );
		if ( empty( $synced_custom_fields ) ) {
			return;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'synced_custom_fields' => $synced_custom_fields,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Build array of new values.
		$new = [];
		foreach( $synced_custom_fields as $field ) {

			// Convert if the value has the special CiviCRM array-like format.
			$values = $field['value'];
			if ( is_string( $values ) && false !== strpos( $values, CRM_Core_DAO::VALUE_SEPARATOR ) ) {
				$values = CRM_Utils_Array::explodePadded( $field['value'] );
			} else {
				$values = [];
			}

			// Assign the value after the edit.
			$new[ (int) $field['custom_field_id'] ] = $values;

		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'old' => $this->custom_pre_edit['values'],
			'new' => $new,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Init mappings array.
		$mappings = [];

		// Build mappings array.
		foreach ( $this->custom_pre_edit['values'] as $custom_field_id => $item_old ) {

			// Get the Option Values for the synced Custom Field ID.
			$option_values = $this->civicrm->option_values_get_by_field_id( $custom_field_id );

			/*
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'option_values' => $option_values,
				//'backtrace' => $trace,
			], true ) );
			*/

			if ( empty( $option_values ) ) {
				continue;
			}

			// Get the matching item.
			$item_new = $new[ $custom_field_id ];

			/*
			 * Keey those that appear in both arrays and add those which are new.
			 * We can ignore those to remove because the Terms are overwritten.
			 */
			$keep = array_intersect( $item_old, $item_new );
			$add =  array_diff( $item_new, $item_old );
			$values = array_merge( $keep, $add );
			if ( empty( $values ) ) {
				continue;
			}

			// Build mapping between the Option Value ID and the value.
			$mapping = [];
			foreach ( $values as $key => $value ) {
				foreach ( $option_values as $option_value ) {
					if ( $option_value['value'] === $value ) {
						$mapping[ $option_value['id'] ] = $value;
					}
				}
			}

			// Add to mappings.
			$mappings[ $custom_field_id ] = $mapping;

		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'mappings' => $mappings,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Construct array of Term IDs to apply to Post.
		$term_ids = [];
		foreach ( $mappings as $custom_field_id => $mapping ) {
			foreach ( $mapping as $option_value_id => $value ) {
				$term = $this->wordpress->term_get_by_option_value_id( $option_value_id );
				if ( ! empty( $term ) ) {
					$term_ids[] = $term->term_id;
				}
			}
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'term_ids' => $term_ids,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Remove WordPress hooks.
		$this->hooks_wordpress_remove();

		// Handle each Post ID in turn.
		foreach ( $post_ids as $post_id ) {
			$taxonomies = get_post_taxonomies( $post_id );

			/*
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'taxonomies' => $taxonomies,
				//'backtrace' => $trace,
			], true ) );
			*/

			if ( in_array( $this->wordpress->taxonomy, $taxonomies ) ) {
				$this->wordpress->terms_update_for_post( $post_id, $term_ids );
			}

		}

		// Add WordPress hooks.
		$this->hooks_wordpress_add();

	}

}
