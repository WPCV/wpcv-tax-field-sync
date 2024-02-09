<?php
/**
 * WP-CLI integration for this plugin.
 *
 * @package WPCV_Tax_Field_Sync
 */

// Bail if WP-CLI is not present.
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Set up WP-CLI commands for this plugin.
 *
 * @since 1.0.1
 */
function wpcv_tax_field_sync_cli_bootstrap() {

	// Include files.
	require_once __DIR__ . '/commands/command-base.php';
	require_once __DIR__ . '/commands/command.php';
	require_once __DIR__ . '/commands/command-connect.php';

	// -----------------------------------------------------------------------------------
	// Add commands.
	// -----------------------------------------------------------------------------------

	// Add top-level command.
	WP_CLI::add_command( 'wpcvtfs', 'WPCV_Tax_Field_Sync_CLI_Command' );

	// Add Connect command.
	WP_CLI::add_command( 'wpcvtfs connect', 'WPCV_Tax_Field_Sync_CLI_Command_Connect', [ 'before_invoke' => 'WPCV_Tax_Field_Sync_CLI_Command_Connect::check_dependencies' ] );

}

// Set up commands.
WP_CLI::add_hook( 'before_wp_load', 'wpcv_tax_field_sync_cli_bootstrap' );
