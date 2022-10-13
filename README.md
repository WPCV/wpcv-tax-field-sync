# Custom Field Taxonomy Sync

Keeps a WordPress Taxonomy and a CiviCRM Custom Field in sync.

## Description

*Custom Field Taxonomy Sync* is a WordPress plugin that keeps a WordPress Taxonomy and a CiviCRM Custom Field in sync.

### Requirements

This plugin requires the following plugins:

* [CiviCRM](https://civicrm.org/download)
* [CiviCRM Profile Sync](https://wordpress.org/plugins/civicrm-wp-profile-sync/)
* [Advanced Custom Fields](https://wordpress.org/plugins/advanced-custom-fields/) (preferably [ACF Pro](https://www.advancedcustomfields.com/pro/))

*Custom Field Taxonomy Sync* is also compatible with [CiviCRM Event Organiser](https://github.com/christianwach/civicrm-event-organiser). You will need version 0.7.2 or greater. For the moment, you will also need CiviCRM Profile Sync and Advanced Custom Fields for sync to take place.

This plugin also requires the following patch to CiviCRM:

* [Call hooks when deleting an option value from CustomOption BAO](https://github.com/civicrm/civicrm-core/pull/23834)

## Installation

There are two ways to install from GitHub:

### ZIP Download

If you have downloaded this plugin as a ZIP file from the GitHub repository, do the following to install the plugin:

1. Unzip the .zip file and, if needed, rename the enclosing folder so that the plugin's files are located directly inside `/wp-content/plugins/wpcv-tax-field-sync`
2. Activate the plugin.
3. You're done.

### `git clone`

If you have cloned the code from GitHub, it is assumed that you know what you're doing.

## Setup

There are two ways to create a sync relationship between a Taxonomy and a Custom Field.

The first is to  add the following to `wp-config.php` or similar on your site:

```php
/**
 * Custom Field Taxonomy Sync settings.
 *
 * @see https://github.com/wpcv/wpcv-tax-field-sync
 */
define( 'WPCV_TAX_FIELD_SYNC_TAXONOMY', 'my-tax-slug' );
define( 'WPCV_TAX_FIELD_SYNC_CUSTOM_FIELD_ID', 145 );
```

The second (which is necessary of you want to add more than one sync relationship) is to use a filter:

```php
/**
 * Register some Sync relationships.
 *
 * @since 1.0
 */
function wpcv_tax_field_sync_init() {

	// Make sure that the "Custom Field Taxonomy Sync" plugin is active.
	if ( ! function_exists( 'wpcv_tax_field_register' ) ) {
		return;
	}

	// Register a Sync relationship.
	$taxonomy = 'event-type';
	$custom_field_id = 7;
	$sync_object = wpcv_tax_field_register( $taxonomy, $custom_field_id );

	// Register more Sync relationships here...

}

// Load after Custom Field Taxonomy Sync has loaded. Must be priority 20 or greater.
add_action( 'cwps/acf/loaded', 'wpcv_tax_field_sync_init', 20 );
```

You will need to manually discover the Custom Field ID and Taxonomy slug at this stage.

The plugin is now ready to use.
