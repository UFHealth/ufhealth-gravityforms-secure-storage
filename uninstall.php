<?php
/**
 * UF Health Gravity Forms Secure Storage uninstaller
 *
 * Used when clicking "Delete" from inside of WordPress's plugins page.
 *
 * @package UFHealth\gravity_forms_secure_storage
 *
 * @since   1.0
 *
 * @author  UF Health <webservices@ahc.ufl.edu>
 */

namespace UFHealth\Gravity_Forms_Secure_Storage\Uninstall;

/**
 * Initialize uninstaller
 *
 * Perform some checks to make sure plugin can/should be uninstalled
 *
 * @since 1.0
 */
function perform_uninstall() {

	// Exit if accessed directly.
	if ( ! defined( 'ABSPATH' ) ) {
		exit_uninstaller();
	}

	// Not uninstalling.
	if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
		exit_uninstaller();
	}

	// Not uninstalling.
	if ( ! WP_UNINSTALL_PLUGIN ) {
		exit_uninstaller();
	}

	// Not uninstalling this plugin.
	if ( dirname( WP_UNINSTALL_PLUGIN ) !== dirname( plugin_basename( __FILE__ ) ) ) {
		exit_uninstaller();
	}

	// Uninstall Gravity Forms Secure Storage.
	clean_data();
}

/**
 * Cleanup options
 *
 * Deletes plugin options and post_meta.
 *
 * @since 1.0
 */
function clean_data() {

}

/**
 * Exit uninstaller
 *
 * Gracefully exit the uninstaller if we should not be here
 *
 * @since 1.0
 */
function exit_uninstaller() {

	status_header( 404 );
	exit;

}

perform_uninstall();
