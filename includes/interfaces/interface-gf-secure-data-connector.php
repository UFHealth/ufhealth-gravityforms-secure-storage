<?php
/**
 * GF Secure Data Connector
 *
 * Allows for abstraction to connect to the data layer of choice for secure storage.
 *
 * @package UFHealth\Gravity_Forms_Secure_Storage
 *
 * @since   1.0
 *
 * @author  Chris Wiegman <cwiegman@ufl.edu>
 */

namespace UFHealth\Gravity_Forms_Secure_Storage;

/**
 * Class GF_Secure_Data_Connector
 */
interface GF_Secure_Data_Connector {

	/**
	 * Write a record to secure storage.
	 *
	 * @since 1.0
	 *
	 * @param array $secure_values Array of secure values.
	 * @param int   $post_id       The post ID to index the secure values.
	 * @param int   $form_id       The ID of the submitted form.
	 * @param array $column_names  Array of column names for writing straight to an external database.
	 */
	public function add_record( $secure_values, $post_id, $form_id, $column_names = array() );

	/**
	 * Returns the settings fields needed to configure the secure form.
	 *
	 * @since 1.0
	 *
	 * @return array Array of settings fields.
	 */
	public function get_settings_fields();

	/**
	 * Delete a record from secure storage.
	 *
	 * @since 1.0
	 *
	 * @param int $lead_id The id of the lead to delete from secure storage.
	 */
	public function delete_record( $lead_id );

	/**
	 * Retrieve secure record data from data store.
	 *
	 * @since 1.0
	 *
	 * @param int $lead_id The id of the record to retrieve.
	 *
	 * @return array|bool Array of secure data or False on failure.
	 */
	public function get_record( $lead_id );

	/**
	 * Setup information for the current form.
	 *
	 * @since 1.0
	 *
	 * @param array $form_settings The settings for the current form.
	 *
	 * @return bool True on success or false.
	 */
	public function init( $form_settings );

}
