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
		$this->hooks_civicrm_wordpress_add();

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
		// No need to remove sync hooks.

	}

	/**
	 * Register CiviCRM hooks.
	 *
	 * These hooks are called when Post ACF Fields are saved. They are also called
	 * when Manual Sync runs in CiviCRM Profile Sync and CiviCRM Event Organiser.
	 *
	 * We use these hooks because the sync relationship relies on CiviCRM Profile
	 * Sync and CiviCRM Event Organiser for the mapping between WordPress Post
	 * Types and CiviCRM Entity Types.
	 *
	 * Note: this plugin could actually be configured for CiviCRM Event Organiser
	 * to work without the need for CiviCRM Profile Sync and ACF, but this hasn't
	 * been done yet.
	 *
	 * Note: this plugin cannot be used for WordPress Users because they do not
	 * have Taxonomies. A plugin might enable this, but it's not available through
	 * vanilla WordPress.
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
		add_action( 'cwps/acf/civicrm/custom_field/custom_edited', [ $this, 'custom_edited' ], 10, 2 );

	}

	/**
	 * Remove CiviCRM hooks.
	 *
	 * @since 1.0
	 */
	public function hooks_civicrm_remove() {

		// Remove CiviCRM callbacks.
		remove_action( 'cwps/acf/civicrm/custom_field/custom_edited', [ $this, 'custom_edited' ], 10 );

	}

	/**
	 * Register CiviCRM-to-WordPress sync hooks.
	 *
	 * @since 1.0
	 */
	public function hooks_civicrm_wordpress_add() {

		// Listen for CiviCRM Entities being synced to WordPress Posts.
		add_action( 'cwps/acf/post/activity/sync', [ $this, 'entity_sync_to_post' ], 10 );
		add_action( 'cwps/acf/post/contact/sync', [ $this, 'entity_sync_to_post' ], 10 );
		add_action( 'cwps/acf/post/participant/sync', [ $this, 'entity_sync_to_post' ], 10 );
		add_action( 'civicrm_event_organiser_admin_civi_to_eo_sync', [ $this, 'entity_sync_to_post' ], 10 );

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
		if ( empty( $args['post_id'] ) || ! is_numeric( $args['post_id'] ) ) {
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

		// The current action defines the Entity and ID source.
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
				$entity_id = $args['civi_event_id'];
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
			$params['contact_type'] = $entity['contact_type'];
		}

		// The Activity API requires "source_contact_id".
		if ( $entity_name === 'Activity' ) {
			$params['source_contact_id'] = $entity['source_contact_id'];
		}

		// The Participant API requires "contact_id" and "event_id".
		if ( $entity_name === 'Participant' ) {
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
			//'backtrace' => $trace,
		], true ) );
		*/

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
		$new_values = [];
		foreach( $synced_custom_fields as $field ) {

			// Convert if the value has the special CiviCRM array-like format.
			$values = $field['value'];
			if ( is_string( $values ) && false !== strpos( $values, CRM_Core_DAO::VALUE_SEPARATOR ) ) {
				$values = CRM_Utils_Array::explodePadded( $field['value'] );
			} else {
				$values = [];
			}

			// Assign the value after the edit.
			$new_values[ (int) $field['custom_field_id'] ] = $values;

		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'new_values' => $new_values,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Build mappings array.
		$mappings = [];
		foreach ( $new_values as $custom_field_id => $values ) {

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

	// -------------------------------------------------------------------------

	/**
	 * Intercept when a CiviCRM Entity is synced to a WordPress Post.
	 *
	 * @since 1.0
	 *
	 * @param array $args The array of data.
	 */
	public function entity_sync_to_post( $args ) {

		// Bail early if this is not a Post, e.g. User "user_N".
		if ( empty( $args['post_id'] ) || ! is_numeric( $args['post_id'] ) ) {
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

		// The current action defines the Entity and ID.
		switch ( current_action() ) {

			// Handle Profile Sync actions.
			case 'cwps/acf/post/activity/sync':
				$entity_name = 'Activity';
				$entity_id = $args['objectId'];
				$entity = $args['objectRef'];
				break;

			case 'cwps/acf/post/contact/sync':
				$entity_name = 'Contact';
				$entity_id = $args['objectId'];
				$entity = $args['objectRef'];
				break;

			case 'cwps/acf/post/participant/sync':
				$entity_name = 'Participant';
				$entity_id = $args['objectId'];
				$entity = $args['objectRef'];
				break;

			// Handle CEO action.
			case 'civicrm_event_organiser_admin_civi_to_eo_sync':
				$entity_name = 'Event';
				$entity_id = $args['civi_event_id'];
				$entity = (object) $args['civi_event'];
				break;

		}

		/*
		$e = new Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'entity_name' => $entity_name,
			'entity_id' => $entity_id,
			'entity' => $entity,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Get the Custom Field if it's not in the data.
		$code = 'custom_' . $this->civicrm->custom_field_id;
		if ( ! isset( $entity->$code ) ) {
			$custom_field_ids = [ $this->civicrm->custom_field_id ];
			$result = $this->civicrm->custom_field_values_get_for_entity( $entity_name, $entity_id, $custom_field_ids );
			$values = [];
			if ( isset( $result[ $this->civicrm->custom_field_id ] ) ) {
				$values = $result[ $this->civicrm->custom_field_id ];
			}
		} else {
			$values = $entity->$code;
		}

		/*
		$e = new Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'values' => $values,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Get the Option Values for the synced Custom Field ID.
		$option_values = $this->civicrm->option_values_get_by_field_id( $this->civicrm->custom_field_id );
		if ( empty( $option_values ) ) {
			return;
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

		/*
		$e = new Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'mapping' => $mapping,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Construct array of Term IDs to apply to Post.
		$term_ids = [];
		foreach ( $mapping as $option_value_id => $value ) {
			$term = $this->wordpress->term_get_by_option_value_id( $option_value_id );
			if ( ! empty( $term ) ) {
				$term_ids[] = $term->term_id;
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

		// Apply Terms to Post.
		$this->wordpress->terms_update_for_post( $args['post_id'], $term_ids );

		// Add WordPress hooks.
		$this->hooks_wordpress_add();

	}

}
