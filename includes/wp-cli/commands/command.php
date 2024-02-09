<?php
/**
 * Command class.
 *
 * @package WPCV_Tax_Field_Sync
 */

// Bail if WP-CLI is not present.
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Manage WPCV Tax Field Sync through the command-line.
 *
 * ## EXAMPLES
 *
 *     $ wp wpcvtfs connect to-wp
 *     Success: Connection complete.
 *
 *     $ wp wpcvtfs connect to-civicrm
 *     Success: Connection complete.
 *
 * @since 1.0.1
 *
 * @package WPCV_Tax_Field_Sync
 */
class WPCV_Tax_Field_Sync_CLI_Command extends WPCV_Tax_Field_Sync_CLI_Command_Base {

	/**
	 * Adds our description and sub-commands.
	 *
	 * @since 1.0.1
	 *
	 * @param object $command The command.
	 * @return array $info The array of information about the command.
	 */
	private function command_to_array( $command ) {

		$info = [
			'name'        => $command->get_name(),
			'description' => $command->get_shortdesc(),
			'longdesc'    => $command->get_longdesc(),
		];

		foreach ( $command->get_subcommands() as $subcommand ) {
			$info['subcommands'][] = $this->command_to_array( $subcommand );
		}

		if ( empty( $info['subcommands'] ) ) {
			$info['synopsis'] = (string) $command->get_synopsis();
		}

		return $info;

	}

}
