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
class GF_Secure_Data_Connector {

	/**
	 * Returns the settings fields needed to configure the secure form.
	 *
	 * @since 1.0
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
						'tooltip' => esc_html__( 'Enables the Innovault back-end allowing secure storage on this form.', 'ufhealth-gravity-forms-secure-storage' ),
						'choices' => array(
							array(
								'label' => esc_html__( 'Enabled', 'ufhealth-gravity-forms-secure-storage' ),
								'name'  => 'enabled',
							),
						),
					),
					array(
						'label'             => esc_html__( 'Client ID', 'ufhealth-gravity-forms-secure-storage' ),
						'type'              => 'text',
						'name'              => 'secure_client_id',
						'tooltip'           => esc_html__( 'Register your client at https://console.tozny.com/clients', 'ufhealth-gravity-forms-secure-storage' ),
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_setting' ),
					),
					array(
						'label'             => esc_html__( 'API Key ID', 'ufhealth-gravity-forms-secure-storage' ),
						'type'              => 'text',
						'name'              => 'secure_api_key_id',
						'tooltip'           => esc_html__( 'Register your client at https://console.tozny.com/clients', 'ufhealth-gravity-forms-secure-storage' ),
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_setting' ),
					),
					array(
						'label'             => esc_html__( 'API Secret', 'ufhealth-gravity-forms-secure-storage' ),
						'type'              => 'text',
						'name'              => 'secure_api_secret',
						'tooltip'           => esc_html__( 'Register your client at https://console.tozny.com/clients', 'ufhealth-gravity-forms-secure-storage' ),
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_setting' ),
					),
					array(
						'label'             => esc_html__( 'Public Key', 'ufhealth-gravity-forms-secure-storage' ),
						'type'              => 'text',
						'name'              => 'secure_api_public_key',
						'tooltip'           => esc_html__( 'Register your client at https://console.tozny.com/clients', 'ufhealth-gravity-forms-secure-storage' ),
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_setting' ),
					),
					array(
						'label'             => esc_html__( 'Private Key', 'ufhealth-gravity-forms-secure-storage' ),
						'type'              => 'text',
						'name'              => 'secure_api_private_key',
						'tooltip'           => esc_html__( 'Register your client at https://console.tozny.com/clients', 'ufhealth-gravity-forms-secure-storage' ),
						'class'             => 'medium',
						'feedback_callback' => array( $this, 'is_valid_setting' ),
					),
				),
			),
		);

	}
}
