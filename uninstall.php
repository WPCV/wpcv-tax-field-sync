<?php
/**
 * Uninstaller.
 *
 * Handles uninstallation functionality.
 *
 * @package WPCV_Tax_Field_Sync
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Bail if uninstall not called from WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

/*
// Delete options.
delete_option( 'wpcv_tax_field_sync_version' );
*/
