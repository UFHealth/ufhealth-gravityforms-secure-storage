<?php
/**
 * Plugin Name: UF Health Gravity Forms Secure Storage
 * Plugin URI: http://webservices.ufhealth.org/
 * Description: Adds a secure storage layer to Gravity Forms to fascilitate our ability to handle various data requirements.
 * Version: 1.4.1
 * Text Domain: ufhealth-gravity-forms-secure-storage
 * Domain Path: /languages
 * Author: UF Health
 * Author URI: http://webservices.ufhealth.org/
 * License: GPLv2
 *
 * @package UFHealth\gravity_forms_secure_storage
 */

define( 'UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_VERSION', '1.4.1' );
define( 'UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_URL', plugin_dir_url( __FILE__ ) );
define( 'UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_INCLUDES', trailingslashit( plugin_dir_path( __FILE__ ) ) . 'includes/' );

require trailingslashit( plugin_dir_path( __FILE__ ) ) . 'vendor/autoload.php';
require UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_INCLUDES . '/interfaces/interface-gf-secure-data-connector.php';

add_action( 'plugins_loaded', 'ufhealth_gravity_forms_secure_storage_loader' );

/**
 * Load plugin functionality.
 */
function ufhealth_gravity_forms_secure_storage_loader() {

	// Remember the text domain.
	load_plugin_textdomain( 'ufhealth-gravity-forms-secure-storage', false, dirname( dirname( __FILE__ ) ) . '/languages' );

}

add_action( 'gform_loaded', 'ufhealth_gravity_forms_secure_storage_gf_loader', 5 );

/**
 * Action gform_loaded
 *
 * Handles loading and registering the add-on class
 *
 * @since 1.0
 */
function ufhealth_gravity_forms_secure_storage_gf_loader() {

	if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
		return;
	}

	GFForms::include_addon_framework();

	require_once UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_INCLUDES . 'classes/class-gf-secure-storage-addon.php';

	GFAddOn::register( '\UFHealth\Gravity_Forms_Secure_Storage\GF_Secure_Storage_Addon' );

}
