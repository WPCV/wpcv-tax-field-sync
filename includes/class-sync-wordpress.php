<?php
/**
 * WordPress Class.
 *
 * Handles WordPress functionality.
 *
 * @package WPCV_Tax_Field_Sync
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * WordPress Class.
 *
 * A class that encapsulates WordPress functionality.
 *
 * @since 1.0
 */
class WPCV_Tax_Field_Sync_WordPress {

	/**
	 * Sync object.
	 *
	 * @since 1.0
	 * @access public
	 * @var object
	 */
	public $sync;

	/**
	 * CiviCRM object.
	 *
	 * @since 1.0
	 * @access public
	 * @var object
	 */
	public $civicrm;

	/**
	 * Taxonomy.
	 *
	 * @since 1.0
	 * @access public
	 * @var string
	 */
	public $taxonomy = '';

	/**
	 * Term Meta key.
	 *
	 * @since 1.0
	 * @access public
	 * @var string
	 */
	public $term_meta_key_option_value = '_wpcv_tax_field_sync_option_value_id';

	/**
	 * An array of Term objects prior to edit.
	 *
	 * There are situations where nested updates take place (e.g. via CiviRules)
	 * so we keep copies of the Terms in an array and try and match them up in
	 * the post edit hook.
	 *
	 * @since 1.0
	 * @access private
	 * @var array
	 */
	private $term_edited = [];

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 *
	 * @param object $sync The Sync object.
	 * @param string $taxonomy The slug of the WordPress Taxonomy.
	 */
	public function __construct( $sync, $taxonomy ) {

		// Store reference to Sync object.
		$this->sync = $sync;

		// Save Taxonomy slug.
		$this->taxonomy = $taxonomy;

		// Init when this plugin is loaded.
		add_action( 'wpcv_tax_field_sync/base/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Initialises this object.
	 *
	 * @since 1.0
	 */
	public function initialise() {

		// Bail if there's no Taxonomy.
		if ( empty( $this->taxonomy ) ) {
			return;
		}

		// Store references.
		$this->civicrm = $this->sync->civicrm;

		// Bootstrap class.
		$this->register_hooks();

		/**
		 * Broadcast that this class is active.
		 *
		 * @since 1.0
		 */
		do_action( 'wpcv_tax_field_sync/wordpress/loaded' );

	}

	/**
	 * Register WordPress hooks.
	 *
	 * @since 1.0
	 */
	public function register_hooks() {

		// Intercept WordPress Term operations.
		$this->hooks_wordpress_add();

	}

	/**
	 * Register WordPress hooks.
	 *
	 * @since 1.0
	 */
	public function hooks_wordpress_add() {

		// Intercept changes to Terms.
		add_action( 'created_term', [ $this, 'term_created' ], 20, 3 );
		add_action( 'edit_terms', [ $this, 'term_edited_pre' ], 20, 2 );
		add_action( 'edited_term', [ $this, 'term_edited' ], 20, 3 );
		add_action( 'pre_delete_term', [ $this, 'term_deleted_pre' ], 20, 2 );

	}


	/**
	 * Remove WordPress hooks.
	 *
	 * @since 1.0
	 */
	public function hooks_wordpress_remove() {

		// Remove all previously added callbacks.
		remove_action( 'created_term', [ $this, 'term_created' ], 20 );
		remove_action( 'edit_terms', [ $this, 'term_edited_pre' ], 20 );
		remove_action( 'edited_term', [ $this, 'term_edited' ], 20 );
		remove_action( 'pre_delete_term', [ $this, 'term_deleted_pre' ], 20 );

	}

	// -------------------------------------------------------------------------

	/**
	 * Acts on the creation of a synced Taxonomy Term.
	 *
	 * @since 1.0
	 *
	 * @param array  $term_id The numeric ID of the new Term.
	 * @param array  $tt_id The numeric ID of the Term Taxonomy.
	 * @param string $taxonomy Should be (an array containing) the Taxonomy slug.
	 * @return int $taxonomy Should be (an array containing) the Taxonomy slug.
	 */
	public function term_created( $term_id, $tt_id, $taxonomy ) {

		// Init return.
		$option_value_id = false;

		// Only act on Terms in the synced Taxonomy.
		if ( $taxonomy !== $this->taxonomy ) {
			return $option_value_id;
		}

		// Get Term object.
		$term = get_term_by( 'id', $term_id, $this->taxonomy );

		// Update CiviCRM Option Value - or create if it doesn't exist.
		$option_value_id = $this->civicrm->option_value_update( $term );

		// Bail if something went wrong.
		if ( empty( $option_value_id ) ) {
			return $option_value_id;
		}

		// Add the Option Value ID to the Term's meta.
		$this->option_value_id_set( $term_id, (int) $option_value_id );

		// --<
		return $option_value_id;

	}

	/**
	 * Acts before updates to a synced Taxonomy Term because we need to get the
	 * corresponding CiviCRM Custom Field before the Term is updated.
	 *
	 * @since 1.0
	 *
	 * @param int    $term_id The numeric ID of the new Term.
	 * @param string $taxonomy The Taxonomy containing the Term.
	 */
	public function term_edited_pre( $term_id, $taxonomy ) {

		// Get full Term.
		$term = get_term_by( 'id', $term_id, $taxonomy );

		// Error check.
		if ( is_null( $term ) ) {
			return;
		}
		if ( is_wp_error( $term ) ) {
			return;
		}
		if ( ! is_object( $term ) ) {
			return;
		}

		// Check Taxonomy.
		if ( $term->taxonomy !== $this->taxonomy ) {
			return;
		}

		// Store for reference in term_edited().
		$this->term_edited[ $term->term_id ] = clone $term;

	}

	/**
	 * Acts on updates to a synced Taxonomy Term.
	 *
	 * @since 1.0
	 *
	 * @param int    $term_id The numeric ID of the edited Term.
	 * @param array  $tt_id The numeric ID of the edited Term Taxonomy.
	 * @param string $taxonomy Should be (an array containing) the Taxonomy slug.
	 */
	public function term_edited( $term_id, $tt_id, $taxonomy ) {

		// Only act on Terms in the synced Taxonomy.
		if ( $taxonomy !== $this->taxonomy ) {
			return;
		}

		// Get current Term object.
		$new_term = get_term_by( 'id', $term_id, $this->taxonomy );

		// Populate "Old Term" if we have it stored.
		$old_term = null;
		if ( ! empty( $this->term_edited[ $new_term->term_id ] ) ) {
			$old_term = $this->term_edited[ $new_term->term_id ];
			unset( $this->term_edited[ $new_term->term_id ] );
		}

		// Update the CiviCRM Option Value - or create if it doesn't exist.
		$option_value_id = $this->civicrm->option_value_update( $new_term, $old_term );

	}

	/**
	 * Acts just before the deletion of a synced Taxonomy Term.
	 *
	 * @since 1.0
	 *
	 * @param int    $deleted_term_id The numeric ID of the Term about to be deleted.
	 * @param string $taxonomy The name of the Taxonomy.
	 */
	public function term_deleted_pre( $deleted_term_id, $taxonomy ) {

		// Only act on Terms in the synced Taxonomy.
		if ( $taxonomy !== $this->taxonomy ) {
			return;
		}

		// Disable the CiviCRM Option Value if it exists.
		$option_value_id = $this->civicrm->option_value_disable( $deleted_term_id );

	}

	// -------------------------------------------------------------------------

	/**
	 * Creates a synced Taxonomy Term.
	 *
	 * @since 1.0
	 *
	 * @param array $option_value The array of CiviCRM Option Value data.
	 * @return array $result The array of synced Taxonomy Term data.
	 */
	public function term_create( $option_value ) {

		// Sanity check.
		if ( ! is_array( $option_value ) ) {
			return false;
		}

		// Don't create if disabled.
		if ( false === $option_value['is_active'] ) {
			return false;
		}

		// Define description if present.
		$description = isset( $option_value['description'] ) ? $option_value['description'] : '';

		// Construct args.
		$args = [
			'slug'        => sanitize_title( $option_value['name'] ),
			'description' => $description,
		];

		// Unhook listeners.
		$this->hooks_wordpress_remove();

		// Insert the Term.
		$result = wp_insert_term( $option_value['label'], $this->taxonomy, $args );

		// Rehook listeners.
		$this->hooks_wordpress_add();

		/*
		 * If all goes well, we get an array like:
		 *
		 * array( 'term_id' => 12, 'term_taxonomy_id' => 34 )
		 *
		 * If something goes wrong, we get a WP_Error object.
		 */
		if ( is_wp_error( $result ) ) {
			return false;
		}

		// Add the Option Value ID to the Term's meta.
		$this->option_value_id_set( $result['term_id'], (int) $option_value['id'] );

		/*
		 * WordPress does not have an "Active/Inactive" Term state by default,
		 * but we can add a "term meta" value to hold this.
		 */

		// TODO: Use "term meta" to save "Active/Inactive" state.

		// --<
		return $result;

	}

	/**
	 * Updates a synced Taxonomy Term.
	 *
	 * @since 1.0
	 *
	 * @param array $new_option_value The CiviCRM Option Value.
	 * @param array $old_option_value The CiviCRM Option Value prior to the update.
	 * @return int|bool $term_id The ID of the updated Term.
	 */
	public function term_update( $new_option_value, $old_option_value = null ) {

		// Sanity check.
		if ( ! is_array( $new_option_value ) ) {
			return false;
		}

		// First, query Term meta.
		$term = $this->term_get_by_option_value( $new_option_value );

		// Grab the found Term ID if the query finds a Term.
		$term_id = false;
		if ( $term instanceof WP_Term ) {
			$term_id = $term->term_id;
		}

		// If we don't get one.
		if ( false === $term_id ) {

			// Create the Term.
			$result = $this->term_create( $new_option_value );
			if ( false === $result ) {
				return $result;
			}

			// --<
			return $result['term_id'];

		}

		// Delete the Term if the Option Value is disabled.
		if ( false === $new_option_value['is_active'] ) {

			// Delete the Term.
			$result = $this->term_delete( $term_id );

			// Always return false.
			return false;

		}

		// Define description if present.
		$description = isset( $new_option_value['description'] ) ? $new_option_value['description'] : '';

		// Construct Term.
		$args = [
			'name'        => $new_option_value['label'],
			'slug'        => sanitize_title( $new_option_value['name'] ),
			'description' => $description,
		];

		// Unhook listeners.
		$this->hooks_wordpress_remove();

		// Update the Term.
		$result = wp_update_term( $term_id, $this->taxonomy, $args );

		// Rehook listeners.
		$this->hooks_wordpress_add();

		/*
		 * If all goes well, we get an array like:
		 *
		 * array( 'term_id' => 12, 'term_taxonomy_id' => 34 )
		 *
		 * If something goes wrong, we get a WP_Error object.
		 */
		if ( is_wp_error( $result ) ) {
			return false;
		}

		/*
		 * WordPress does not have an "Active/Inactive" Term state by default,
		 * but we can add a "term meta" value to hold this.
		 */

		// TODO: Use "term meta" to save "Active/Inactive" state.

		// --<
		return $result['term_id'];

	}

	/**
	 * Deletes a synced Taxonomy Term.
	 *
	 * @since 1.0
	 *
	 * @param int $term_id The numeric ID of the Term to delete.
	 * @return int|bool|WP_Error $result The result of the operation.
	 */
	public function term_delete( $term_id ) {

		// Unhook listeners.
		$this->hooks_wordpress_remove();

		// Delete the Term.
		$result = wp_delete_term( $term_id, $this->taxonomy );

		// Rehook listeners.
		$this->hooks_wordpress_add();

		/*
		 * True on success.
		 * False if Term does not exist.
		 * Zero on attempted deletion of default Category.
		 * WP_Error if the Taxonomy does not exist.
		 */
		return $result;

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets a synced Taxomomy Term for a given CiviCRM Option Value.
	 *
	 * @since 1.0
	 *
	 * @param array|object $option_value The array of CiviCRM Option Value data.
	 * @return WP_Term|bool $term The Term object, or false on failure.
	 */
	public function term_get_by_option_value( $option_value ) {

		// Extract value.
		if ( is_array( $option_value ) ) {
			$option_value_id = $option_value['id'];
		} elseif ( is_object( $option_value ) ) {
			$option_value_id = $option_value->id;
		} else {
			return false;
		}

		// Get the Term.
		$term = $this->term_get_by_option_value_id( $option_value_id );

		// --<
		return $term;

	}

	/**
	 * Gets a synced Taxomomy Term for a given CiviCRM Option Value ID.
	 *
	 * @since 1.0
	 *
	 * @param int $option_value_id The numeric ID of the CiviCRM Option Value.
	 * @return WP_Term|bool $term The Term object, or false on failure.
	 */
	public function term_get_by_option_value_id( $option_value_id ) {

		// Query Terms for the Term with the ID of the Option Value in meta data.
		$args = [
			'taxonomy'   => $this->taxonomy,
			'hide_empty' => false,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query' => [
				[
					'key'     => $this->term_meta_key_option_value,
					'value'   => $option_value_id,
					'compare' => '=',
				],
			],
		];

		// Get what should only be a single Term.
		$terms = get_terms( $args );

		// Bail if there are no results.
		if ( empty( $terms ) ) {
			return false;
		}

		// Log a message and bail if there's an error.
		if ( is_wp_error( $terms ) ) {
			$e     = new Exception();
			$trace = $e->getTraceAsString();
			$this->sync->log_error( [
				'method'       => __METHOD__,
				'message'      => $terms->get_error_message(),
				'term'         => $term,
				'option_value' => $option_value,
				'backtrace'    => $trace,
			] );
			return false;
		}

		// If we get more than one, WTF?
		if ( count( $terms ) > 1 ) {
			return false;
		}

		// Init return.
		$term = false;

		// Grab Term data.
		if ( count( $terms ) === 1 ) {
			$term = array_pop( $terms );
		}

		// --<
		return $term;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the synced Terms for a given WordPress Post ID.
	 *
	 * @since 1.0
	 *
	 * @param integer|string $post_id The ACF "Post ID".
	 * @return array $terms The array of Term objects. Empty array on failure.
	 */
	public function terms_get_for_post( $post_id ) {

		// Grab the Terms.
		$terms = get_the_terms( $post_id, $this->taxonomy );

		// Bail if there are no Terms or there's an error.
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return [];
		}

		// Let's add the Option Value ID to the Term object.
		foreach ( $terms as $term ) {
			$term->option_value_id = $this->option_value_id_get( $term->term_id );
		}

		// --<
		return $terms;

	}

	/**
	 * Overwrites the Terms for a given Post ID.
	 *
	 * @since 1.0
	 *
	 * @param int   $post_id The numeric ID of the Post.
	 * @param array $term_ids The array of numeric Term IDs.
	 */
	public function terms_update_for_post( $post_id, $term_ids ) {

		// Overwrite with new set of Terms.
		wp_set_object_terms( $post_id, $term_ids, $this->taxonomy, false );

		// Clear cache.
		clean_object_term_cache( $post_id, $this->taxonomy );

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets the CiviCRM Option Value ID for a synced Taxonomy Term.
	 *
	 * @since 1.0
	 *
	 * @param int $term_id The numeric ID of the Term.
	 * @return int|bool $option_value_id The ID of the CiviCRM Option Value, or false on failure.
	 */
	public function option_value_id_get( $term_id ) {

		// Get the Option Value ID from the Term's meta.
		$option_value_id = get_term_meta( $term_id, $this->term_meta_key_option_value, true );

		// Bail if there is no result.
		if ( empty( $option_value_id ) ) {
			return false;
		}

		// --<
		return $option_value_id;

	}

	/**
	 * Adds meta data to a synced Taxonomy Term.
	 *
	 * @since 1.0
	 *
	 * @param int $term_id The numeric ID of the Term.
	 * @param int $option_value_id The numeric ID of the CiviCRM Option Value.
	 * @return int|bool $meta_id The ID of the meta, or false on failure.
	 */
	public function option_value_id_set( $term_id, $option_value_id ) {

		// Add the Option Value ID to the Term's meta.
		$meta_id = add_term_meta( $term_id, $this->term_meta_key_option_value, (int) $option_value_id, true );

		// Log something if there's an error.
		// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
		if ( false === $meta_id ) {

			/*
			 * This probably means that the Term already has its "term meta" set.
			 * Uncomment the following to debug if you need to.
			 */

			/*
			$e = new Exception();
			$trace = $e->getTraceAsString();
			$this->sync->log_error( [
				'method' => __METHOD__,
				'message' => __( 'Could not add Term meta', 'wpcv-tax-field-sync' ),
				'term_id' => $term_id,
				'option_value_id' => $option_value_id,
				'backtrace' => $trace,
			] );
			*/

		}

		// Log a message if the Term ID is ambiguous between Taxonomies.
		if ( is_wp_error( $meta_id ) ) {

			// Log error message.
			$e     = new Exception();
			$trace = $e->getTraceAsString();
			$this->sync->log_error( [
				'method'          => __METHOD__,
				'message'         => $meta_id->get_error_message(),
				'term_id'         => $term_id,
				'option_value_id' => $option_value_id,
				'backtrace'       => $trace,
			] );

			// Also overwrite return.
			$meta_id = false;

		}

		// --<
		return $meta_id;

	}

}
