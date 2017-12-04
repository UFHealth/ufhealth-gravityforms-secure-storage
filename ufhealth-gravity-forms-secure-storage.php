<?php
/**
 * Plugin Name: UF Health Gravity Forms Secure Storage
 * Plugin URI: http://webservices.ufhealth.org/
 * Description: Adds a secure storage layer to Gravity Forms to fascilitate our ability to handle various data requirements.
 * Version: 1.0
 * Text Domain: ufhealth-gravity-forms-secure-storage
 * Domain Path: /languages
 * Author: UF Health
 * Author URI: http://webservices.ufhealth.org/
 * License: GPLv2
 *
 * @package UFHealth\gravity_forms_secure_storage
 */

define( 'UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_VERSION', '1.0' );
define( 'UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_URL', plugin_dir_url( __FILE__ ) );

add_action( 'plugins_loaded', 'ufhealth_gravity_forms_secure_storage_loader' );

/**
 * Load plugin functionality.
 */
function ufhealth_gravity_forms_secure_storage_loader() {

	// Remember the text domain.
	load_plugin_textdomain( 'ufhealth-gravity-forms-secure-storage', false, dirname( dirname( __FILE__ ) ) . '/languages' );

}
