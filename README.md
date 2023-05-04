# WPCV Custom Field Taxonomy Sync

Keeps a WordPress Taxonomy and a CiviCRM Custom Field in sync.

## Description

*WPCV Custom Field Taxonomy Sync* is a WordPress plugin that keeps a WordPress Taxonomy and a CiviCRM Custom Field in sync.

### Requirements

This plugin requires the following plugins:

* [CiviCRM](https://civicrm.org/download)
* [CiviCRM Profile Sync](https://wordpress.org/plugins/civicrm-wp-profile-sync/)
* [Advanced Custom Fields](https://wordpress.org/plugins/advanced-custom-fields/) (preferably [ACF Pro](https://www.advancedcustomfields.com/pro/))

*WPCV Custom Field Taxonomy Sync* is also compatible with [CiviCRM Event Organiser](https://github.com/christianwach/civicrm-event-organiser). You will need version 0.7.2 or greater. For the moment, you will also need CiviCRM Profile Sync and Advanced Custom Fields for sync to take place.

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

### The Custom Field

Create a Custom Field in CiviCRM which allows multiple values, e.g. an Alphanumeric Checkbox maps nicely to a Taxonomy, but other types should work too. *Do not* create any values.

*Please note:* it seems that for some types of Custom Field [this commit](https://github.com/civicrm/civicrm-core/pull/23305/commits/04333740043a724d79867ab00412b48d6712b3de) is required. If you're on CiviCRM 5.58.0 or greater, you're fine. If not, you may need to patch CiviCRM to avoid fatal errors.

### The Taxonomy

Create a Custom Taxonomy in WordPress, either using a plugin or code. *Do not* create any terms.

## Synchronisation

There are two ways to create a sync relationship between a Taxonomy and a Custom Field.

The first is to  add the following to `wp-config.php` or similar on your site:

```php
/**
 * WPCV Custom Field Taxonomy Sync settings.
 *
 * @see https://github.com/wpcv/wpcv-tax-field-sync
 */
define( 'WPCV_TAX_FIELD_SYNC_TAXONOMY', 'my-tax-slug' );
define( 'WPCV_TAX_FIELD_SYNC_CUSTOM_FIELD_ID', 145 );
```

You can optionally define a sync direction:

```php
define( 'WPCV_TAX_FIELD_SYNC_DIRECTION', 'wp_to_civicrm' );
```

The value can be one of `both`, `wp_to_civicrm` or `civicrm_to_wp`. Default is "both".

The second (which is necessary of you want to add more than one sync relationship) is to use a filter:

```php
/**
 * Register some Sync relationships.
 *
 * @since 1.0
 */
function wpcv_tax_field_sync_init() {

	// Make sure that the "WPCV Custom Field Taxonomy Sync" plugin is active.
	if ( ! function_exists( 'wpcv_tax_field_register' ) ) {
		return;
	}

	// Register a Sync relationship.
	$taxonomy = 'event-type';
	$custom_field_id = 7;
	$sync_direction = 'wp_to_civicrm'; // Can be "both", "wp_to_civicrm" or "civicrm_to_wp". Default is "both".
	$sync_object = wpcv_tax_field_register( $taxonomy, $custom_field_id, $sync_direction );

	// Register more Sync relationships here...

}

// Load after WPCV Custom Field Taxonomy Sync has loaded. Must be priority 20 or greater.
add_action( 'cwps/acf/loaded', 'wpcv_tax_field_sync_init', 20 );
```

You will need to manually discover the Custom Field ID and Taxonomy slug at this stage.

The plugin is now ready to use, so go ahead and create your WordPress Terms or CiviCRM Custom Field Multiple Choice Options. You should see matching items in the synced Taxonomy or Custom Field.

*Tip:* when creating CiviCRM Custom Field Multiple Choice Options, think of the "Option Label" as the "Term Name" and "Option Value" as the "Term Slug".
