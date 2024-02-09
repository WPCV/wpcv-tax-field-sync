<?php
/**
 * Connect command class.
 *
 * @package WPCV_Tax_Field_Sync
 */

// Bail if WP-CLI is not present.
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Connect a CiviCRM Custom Field with a WordPress Taxonomy.
 *
 * ## EXAMPLES
 *
 *     $ wp wpcvtfs connect to-wp --tax=event-type --cf=6
 *     Success: Connection complete.
 *
 *     $ wp wpcvtfs connect to-civicrm --cf=6 --tax=2
 *     Success: Connection complete.
 *
 * @since 1.0.1
 *
 * @package WPCV_Tax_Field_Sync
 */
class WPCV_Tax_Field_Sync_CLI_Command_Connect extends WPCV_Tax_Field_Sync_CLI_Command {

	/**
	 * Connect a CiviCRM Custom Field to a WordPress Taxonomy.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp wpcvtfs connect to-wp --tax=event-type --cf=7
	 *     Success: Connection complete.
	 *
	 * ## OPTIONS
	 *
	 * [--tax=<tax>]
	 * : The slug of the Taxonomy.
	 *
	 * [--cf=<cf>]
	 * : The numeric ID of the CiviCRM Custom field.
	 *
	 * @subcommand to-wp
	 * @alias civicrm-to-wp
	 *
	 * @since 1.0.1
	 *
	 * @param array $args The WP-CLI positional arguments.
	 * @param array $assoc_args The WP-CLI associative arguments.
	 */
	public function to_wp( $args, $assoc_args ) {

		// Grab associative arguments.
		$taxonomy = (string) \WP_CLI\Utils\get_flag_value( $assoc_args, 'tax', '' );
		$custom_field_id = (int) \WP_CLI\Utils\get_flag_value( $assoc_args, 'cf', 0 );

		// Sanity checks.
		if ( empty( $taxonomy ) ) {
			WP_CLI::error( 'You must provide a Taxonomy.' );
		}
		if ( empty( $custom_field_id ) ) {
			WP_CLI::error( 'You must provide a CiviCRM Custom Field ID.' );
		}

		// Bootstrap CiviCRM.
		$this->bootstrap_civicrm();

		// Show existing information.
		WP_CLI::log( '' );
		WP_CLI::log( WP_CLI::colorize( '%GGathering source information:%n' ) );

		// Get the full taxonomy data.
		$tax_object = get_taxonomy( $taxonomy );
		if ( is_wp_error( $tax_object ) ) {
			WP_CLI::error( $tax_object->get_error_message() );
		}

		// Bail if there are none.
		if ( empty( $tax_object ) ) {
			WP_CLI::error( 'Cannot find the Taxonomy.' );
		}

		// Get the full Custom Field data.
		try {
			$custom_fields = \Civi\Api4\CustomField::get( false )
				->addSelect( '*' )
				->addWhere( 'id', '=', $custom_field_id )
				->setLimit( 1 )
				->execute();
		} catch ( CRM_Core_Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}

		// Bail if there are none.
		if ( $custom_fields->count() === 0 ) {
			WP_CLI::error( 'Cannot find the CiviCRM Custom Field ID.' );
		}

		// Convert the ArrayObject to a simple array.
		$array = array_values( $custom_fields->getArrayCopy() );
		$custom_field = array_pop( $array );

		// Build the rows.
		$rows = [];
		$fields = [ 'Source', 'Name' ];
		$rows[] = [
			'Source' => 'Custom Field',
			'Name' => $custom_field['label'],
		];
		$rows[] = [
			'Source' => 'Taxonomy',
			'Name' => $tax_object->label,
		];

		// Render feedback.
		$args = [ 'format' => 'table' ];
		$formatter = new \WP_CLI\Formatter( $args, $fields );
		$formatter->display_items( $rows );

		// Let's give folks a chance to exit.
		WP_CLI::confirm(
			sprintf(
				WP_CLI::colorize( '%gDo you want to sync the "%s" Custom Field to the "%s" Taxonomy?%n' ),
				$tax_object->label,
				$custom_field['label']
			)
		);

		// Show existing information.
		WP_CLI::log( '' );
		WP_CLI::log( WP_CLI::colorize( '%GGathering Sync Object information:%n' ) );

		// New Sync Object flag.
		$new_sync_object = false;

		// Get the Sync Object.
		$sync_object = wpcv_tax_field_get( $taxonomy, $custom_field_id );

		// Bail if we find a Sync Object that does not allow WordPress-to-CiviCRM sync.
		if ( ! empty( $sync_object ) && 'wp_to_civicrm' === $sync_object->mapper->sync_direction ) {
			WP_CLI::log( '' );
			WP_CLI::error( 'Existing Sync Object found with "' . $sync_object->mapper->sync_direction . '" sync direction.' );
		}

		// Create a sync object if none exists.
		if ( empty( $sync_object ) ) {

			$sync_direction = 'civicrm_to_wp'; // Can be "both", "wp_to_civicrm" or "wp_to_civicrm".
			$sync_object = wpcv_tax_field_register( $taxonomy, $custom_field_id, $sync_direction );
			$new_sync_object = true;

			// Show some helpful code.
			WP_CLI::log( 'This Taxonomy and CiviCRM Custom Field are not currently synced.' );
			WP_CLI::log( 'To set up the Sync Object, use the following code:' );
			WP_CLI::log( '' );
			WP_CLI::log( '<?php' );
			WP_CLI::log( '' );
			WP_CLI::log( sprintf( '$taxonomy = \'%s\';', $taxonomy ) );
			WP_CLI::log( sprintf( '$custom_field_id = %d;', $custom_field_id ) );
			WP_CLI::log( '$sync_direction = \'civicrm_to_wp\'; // You can use "both" if you want bi-directional sync.' );
			WP_CLI::log( '$sync_object = wpcv_tax_field_register( $taxonomy, $custom_field_id, $sync_direction );' );
			WP_CLI::log( '' );
			WP_CLI::log( '?>' );
			WP_CLI::log( '' );
			WP_CLI::log( 'See the readme for details:' );
			WP_CLI::log( 'https://github.com/WPCV/wpcv-tax-field-sync?tab=readme-ov-file#synchronisation' );

		} else {

			WP_CLI::log( 'Existing Sync Object found with "' . $sync_object->mapper->sync_direction . '" sync direction.' );

		}

		WP_CLI::log( '' );
		WP_CLI::log( WP_CLI::colorize( '%GGathering Option Value information:%n' ) );

		// Get the Option Value data.
		$option_values = $sync_object->civicrm->option_values_get_by_field_id( $custom_field_id );
		if ( empty( $option_values ) ) {
			WP_CLI::log( '' );
			WP_CLI::log( 'No Option Values found.' );
			WP_CLI::halt( 0 );
		}

		// Build the rows.
		$rows = [];
		$fields = [ 'Option Value', 'Option Value ID', 'Term', 'Term ID' ];
		$option_values_to_sync = [];
		foreach ( $option_values as $option_value ) {

			// Get synced Term and maybe add to Option Values to sync.
			$term = $sync_object->wordpress->term_get_by_option_value( $option_value );
			if ( empty( $term ) ) {
				$option_values_to_sync[] = $option_value;
			}

			$rows[] = [
				'Option Value' => $option_value['label'],
				'Option Value ID' => $option_value['id'],
				'Term' => ! empty( $term ) ? $term->name : '',
				'Term ID' => ! empty( $term->term_id ) ? $term->term_id : '',
			];

		}

		// Skip if nothing needs doing.
		if ( empty( $option_values_to_sync ) ) {
			WP_CLI::success( 'All Option Values properly synced.' );
			WP_CLI::log( '' );
			WP_CLI::halt( 0 );
		}

		// Render feedback.
		$args = [ 'format' => 'table' ];
		$formatter = new \WP_CLI\Formatter( $args, $fields );
		$formatter->display_items( $rows );

		// Sync the Option Values that need syncing.
		WP_CLI::log( '' );
		WP_CLI::log( WP_CLI::colorize( '%GCreating Terms...%n' ) );
		foreach ( $option_values_to_sync as $option_value ) {
			$term_created = $sync_object->wordpress->term_create( $option_value );
			if ( ! empty( $term_created ) ) {
				WP_CLI::log( sprintf( WP_CLI::colorize( '%gCreated Term%n (ID: %d)' ), (int) $term_created['term_id'] ) );
			}
		}

		WP_CLI::log( '' );
		WP_CLI::success( 'Connection complete.' );

	}

	/**
	 * Connect a WordPress Taxonomy to a CiviCRM Custom Field.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp wpcvtfs connect to-civicrm --tax=event-type --cf=7
	 *     Success: Connection complete.
	 *
	 * ## OPTIONS
	 *
	 * [--tax=<tax>]
	 * : The slug of the Taxonomy.
	 *
	 * [--cf=<cf>]
	 * : The numeric ID of the CiviCRM Custom field.
	 *
	 * @subcommand to-civicrm
	 * @alias wp-to-civicrm
	 *
	 * @since 1.0.1
	 *
	 * @param array $args The WP-CLI positional arguments.
	 * @param array $assoc_args The WP-CLI associative arguments.
	 */
	public function to_civicrm( $args, $assoc_args ) {

		// Grab associative arguments.
		$taxonomy = (string) \WP_CLI\Utils\get_flag_value( $assoc_args, 'tax', '' );
		$custom_field_id = (int) \WP_CLI\Utils\get_flag_value( $assoc_args, 'cf', 0 );

		// Sanity checks.
		if ( empty( $taxonomy ) ) {
			WP_CLI::error( 'You must provide a Taxonomy.' );
		}
		if ( empty( $custom_field_id ) ) {
			WP_CLI::error( 'You must provide a CiviCRM Custom Field ID.' );
		}

		// Bootstrap CiviCRM.
		$this->bootstrap_civicrm();

		// Show existing information.
		WP_CLI::log( '' );
		WP_CLI::log( WP_CLI::colorize( '%GGathering source information:%n' ) );

		// Get the full taxonomy data.
		$tax_object = get_taxonomy( $taxonomy );
		if ( is_wp_error( $tax_object ) ) {
			WP_CLI::error( $tax_object->get_error_message() );
		}

		// Bail if there are none.
		if ( empty( $tax_object ) ) {
			WP_CLI::error( 'Cannot find the Taxonomy.' );
		}

		// Get the full Custom Field data.
		try {
			$custom_fields = \Civi\Api4\CustomField::get( false )
				->addSelect( '*' )
				->addWhere( 'id', '=', $custom_field_id )
				->setLimit( 1 )
				->execute();
		} catch ( CRM_Core_Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}

		// Bail if there are none.
		if ( $custom_fields->count() === 0 ) {
			WP_CLI::error( 'Cannot find the CiviCRM Custom Field ID.' );
		}

		// Convert the ArrayObject to a simple array.
		$array = array_values( $custom_fields->getArrayCopy() );
		$custom_field = array_pop( $array );

		// Build the rows.
		$rows = [];
		$fields = [ 'Source', 'Name' ];
		$rows[] = [
			'Source' => 'Taxonomy',
			'Name' => $tax_object->label,
		];
		$rows[] = [
			'Source' => 'Custom Field',
			'Name' => $custom_field['label'],
		];

		// Render feedback.
		$args = [ 'format' => 'table' ];
		$formatter = new \WP_CLI\Formatter( $args, $fields );
		$formatter->display_items( $rows );

		// Let's give folks a chance to exit.
		WP_CLI::confirm(
			sprintf(
				WP_CLI::colorize( '%gDo you want to sync the "%s" Taxonomy to the "%s" Custom Field?%n' ),
				$tax_object->label,
				$custom_field['label']
			)
		);

		// Show existing information.
		WP_CLI::log( '' );
		WP_CLI::log( WP_CLI::colorize( '%GGathering Sync Object information:%n' ) );

		// New Sync Object flag.
		$new_sync_object = false;

		// Get the Sync Object.
		$sync_object = wpcv_tax_field_get( $taxonomy, $custom_field_id );

		// Bail if we find a Sync Object that does not allow WordPress-to-CiviCRM sync.
		if ( ! empty( $sync_object ) && 'civicrm_to_wp' === $sync_object->mapper->sync_direction ) {
			WP_CLI::log( '' );
			WP_CLI::error( 'Existing Sync Object found with "' . $sync_object->mapper->sync_direction . '" sync direction.' );
		}

		// Create a sync object if none exists.
		if ( empty( $sync_object ) ) {

			$sync_direction = 'wp_to_civicrm'; // Can be "both", "wp_to_civicrm" or "wp_to_civicrm".
			$sync_object = wpcv_tax_field_register( $taxonomy, $custom_field_id, $sync_direction );
			$new_sync_object = true;

			// Show some helpful code.
			WP_CLI::log( 'This Taxonomy and CiviCRM Custom Field are not currently synced.' );
			WP_CLI::log( 'To set up the Sync Object, use the following code:' );
			WP_CLI::log( '' );
			WP_CLI::log( '<?php' );
			WP_CLI::log( '' );
			WP_CLI::log( sprintf( '$taxonomy = \'%s\';', $taxonomy ) );
			WP_CLI::log( sprintf( '$custom_field_id = %d;', $custom_field_id ) );
			WP_CLI::log( '$sync_direction = \'wp_to_civicrm\'; // You can use "both" if you want bi-directional sync.' );
			WP_CLI::log( '$sync_object = wpcv_tax_field_register( $taxonomy, $custom_field_id, $sync_direction );' );
			WP_CLI::log( '' );
			WP_CLI::log( '?>' );
			WP_CLI::log( '' );
			WP_CLI::log( 'See the readme for details:' );
			WP_CLI::log( 'https://github.com/WPCV/wpcv-tax-field-sync?tab=readme-ov-file#synchronisation' );

		} else {

			WP_CLI::log( 'Existing Sync Object found with "' . $sync_object->mapper->sync_direction . '" sync direction.' );

		}

		WP_CLI::log( '' );
		WP_CLI::log( WP_CLI::colorize( '%GGathering Term information:%n' ) );

		// Query for all Terms.
		$args = [
			'taxonomy' => $taxonomy,
			'hide_empty' => false,
		];
		$terms = get_terms( $args );

		// Sanity checks.
		if ( is_wp_error( $terms ) ) {
			WP_CLI::log( '' );
			WP_CLI::error( $terms->get_error_message() );
		}
		if ( empty( $terms ) ) {
			WP_CLI::log( '' );
			WP_CLI::log( 'No Terms found.' );
			WP_CLI::halt( 0 );
		}

		// Do we have any Terms to sync?
		$terms_to_sync = [];
		$key = $sync_object->wordpress->term_meta_key_option_value;

		// Build the rows.
		$rows = [];
		$fields = [ 'Term', 'Term ID', 'Option Value ID' ];
		foreach ( $terms as $term ) {

			// Get meta and maybe add to Terms to sync.
			$option_value_id = get_term_meta( $term->term_id, $key, true );
			if ( empty( $option_value_id ) ) {
				$terms_to_sync[] = $term->term_id;
			}

			$rows[] = [
				'Term' => $term->name,
				'Term ID' => $term->term_id,
				'Option Value ID' => ! empty( $option_value_id ) ? $option_value_id : '',
			];

		}

		// Skip if nothing needs doing.
		if ( empty( $terms_to_sync ) ) {
			WP_CLI::success( 'All Terms properly synced.' );
			WP_CLI::log( '' );
			WP_CLI::halt( 0 );
		}

		// Render feedback.
		$args = [ 'format' => 'table' ];
		$formatter = new \WP_CLI\Formatter( $args, $fields );
		$formatter->display_items( $rows );

		// Sync the Terms that need syncing.
		WP_CLI::log( '' );
		WP_CLI::log( WP_CLI::colorize( '%GCreating Option Values...%n' ) );
		foreach ( $terms_to_sync as $term_id ) {
			$option_value_created = $sync_object->wordpress->term_created( $term_id, null, $taxonomy );
			if ( ! empty( $option_value_created ) ) {
				WP_CLI::log( sprintf( WP_CLI::colorize( '%gCreated Option Value%n (ID: %d)' ), (int) $option_value_created ) );
			}
		}

		WP_CLI::log( '' );
		WP_CLI::success( 'Connection complete.' );

	}

}
