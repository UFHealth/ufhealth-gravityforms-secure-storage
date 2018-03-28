<?php
/**
 * MSSQL Data Connector
 *
 * Store secure Gravity Forms data in MSSQL
 *
 * @package UFHealth\Gravity_Forms_Secure_Storage
 *
 * @since   1.1.2
 *
 * @author  Chris Wiegman <cwiegman@ufl.edu>
 */

namespace UFHealth\Gravity_Forms_Secure_Storage;

/**
 * Class MSSSQL_Data_Connector
 */
class MSSSQL_Data_Connector implements GF_Secure_Data_Connector {

	/**
	 * Array of form settings.
	 *
	 * @since 1.1.2
	 *
	 * @var bool|array
	 */
	protected $settings = false;

	/**
	 * Connection to MSSQL
	 *
	 * @since 1.1.2
	 *
	 * @var mixed
	 */
	private $_mssql_connection = false;

	/**
	 * Write a record to secure storage.
	 *
	 * @since 1.1.2
	 *
	 * @param array $secure_values Array of secure values.
	 * @param int   $post_id       The post ID to index the secure values.
	 */
	public function add_record( $secure_values, $post_id ) {

	}

	/**
	 * Returns the settings fields needed to configure the secure form.
	 *
	 * @since 1.1.2
	 *
	 * @return array Array of settings fields.
	 */
	public function get_settings_fields() {

		return array(
			array(
				'title'  => esc_html__( 'Secure Storage Settings', 'ufhealth-gravity-forms-secure-storage' ),
				'fields' => array(
					array(
						'label'   => esc_html__( 'Enable Secure Storage', 'ufhealth-gravity-forms-secure-storage' ),
						'type'    => 'checkbox',
						'name'    => 'enabled',
						'tooltip' => esc_html__( 'Enables the MSSQL back-end allowing secure storage on this form.', 'ufhealth-gravity-forms-secure-storage' ),
						'choices' => array(
							array(
								'label' => esc_html__( 'Enabled', 'ufhealth-gravity-forms-secure-storage' ),
								'name'  => 'enabled',
							),
						),
					),
					array(
						'label'             => esc_html__( 'Database Host', 'ufhealth-gravity-forms-secure-storage' ),
						'type'              => 'text',
						'name'              => 'secure_database_host',
						'tooltip'           => esc_html__( 'The host server of the MSSQL Database', 'ufhealth-gravity-forms-secure-storage' ),
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_setting' ),
					),
					array(
						'label'             => esc_html__( 'Database Name', 'ufhealth-gravity-forms-secure-storage' ),
						'type'              => 'text',
						'name'              => 'secure_database_name',
						'tooltip'           => esc_html__( 'The name of the MSSQL Database', 'ufhealth-gravity-forms-secure-storage' ),
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_setting' ),
					),
					array(
						'label'             => esc_html__( 'Database Username', 'ufhealth-gravity-forms-secure-storage' ),
						'type'              => 'text',
						'name'              => 'secure_database_username',
						'tooltip'           => esc_html__( 'The username for the MSSQL Database', 'ufhealth-gravity-forms-secure-storage' ),
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_setting' ),
					),
					array(
						'label'             => esc_html__( 'Database Password', 'ufhealth-gravity-forms-secure-storage' ),
						'type'              => 'text',
						'name'              => 'secure_database_password',
						'tooltip'           => esc_html__( 'The user password for the MSSQL Database', 'ufhealth-gravity-forms-secure-storage' ),
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_setting' ),
					),
				),
			),
		);

	}

	/**
	 * Delete a record from secure storage.
	 *
	 * @since 1.1.2
	 *
	 * @param int $lead_id The id of the lead to delete from secure storage.
	 */
	public function delete_record( $lead_id ) {

	}

	/**
	 * Retrieve secure record data from data store.
	 *
	 * @since 1.1.2
	 *
	 * @param int $lead_id The id of the record to retrieve.
	 *
	 * @return array|bool Array of secure data or False on failure.
	 */
	public function get_record( $lead_id ) {

		return false;

	}

	/**
	 * Setup information for the current form.
	 *
	 * @since 1.1.2
	 *
	 * @param array $form_settings The settings for the current form.
	 *
	 * @return bool True on success or false.
	 */
	public function init( $form_settings ) {

		if ( false === $this->settings ) {

			$this->settings = $form_settings;

			return true;

		}

		return false;

	}

	/**
	 * Retrieve the current instance of the Tozny client.
	 *
	 * @since 1.1.2
	 *
	 * @return mixed
	 */
	protected function set_client() {

		if ( false === $this->_mssql_connection ) {

			$this->_mssql_connection = mssql_connect( $this->settings['secure_database_host'], $this->settings['secure_database_username'], $this->settings['secure_database_password'] );
			mssql_select_db( $this->settings['secure_database_name'], $this->_mssql_connection );

		}

		return $this->_mssql_connection;

	}
}
