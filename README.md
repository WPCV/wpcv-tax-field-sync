# Custom Field Taxonomy Sync

Keeps a WordPress Taxonomy and a CiviCRM Custom Field in sync.

## Description

*Custom Field Taxonomy Sync* is a WordPress plugin that keeps a WordPress Taxonomy and a CiviCRM Custom Field in sync.

### Requirements

This plugin requires the following plugins:

* [CiviCRM](https://civicrm.org/download)
* [CiviCRM Profile Sync](https://wordpress.org/plugins/civicrm-wp-profile-sync/)
* [Advanced Custom Fields](https://wordpress.org/plugins/advanced-custom-fields/) (preferably [ACF Pro](https://www.advancedcustomfields.com/pro/))

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

Add the following to `wp-config.php` or similar on your site:

```php
/**
 * Custom Field Taxonomy Sync settings.
 *
 * @see https://github.com/wpcv/wpcv-tax-field-sync
 */
define( 'WPCV_TAX_FIELD_SYNC_TAXONOMY', 'my-tax-slug' );
define( 'WPCV_TAX_FIELD_SYNC_CUSTOM_FIELD_ID', 145 );
```

The plugin is now ready to use.
