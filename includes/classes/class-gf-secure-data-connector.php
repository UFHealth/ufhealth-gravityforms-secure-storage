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

use Tozny\E3DB\Client;
use Tozny\E3DB\Config;
use Tozny\E3DB\Connection\GuzzleConnection;
use Tozny\E3DB\Exceptions\ConflictException;

/**
 * Class GF_Secure_Data_Connector
 */
class GF_Secure_Data_Connector {

	protected $form;

	protected $settings;

	/**
	 * The instance of the Tozny client.
	 *
	 * @since 1.0
	 *
	 * @var bool|\Tozny\E3DB\Client
	 */
	protected $_inno_client = false;

	public function add_record( $form ) {

	}

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

	public function delete_record( $lead_id, $form ) {

		$query = array(
			'eq' =>
				array(
					'name'  => 'post_id',
					'value' => $lead_id,
				),
		);

		$data   = true;
		$raw    = false;
		$writer = null;
		$record = null;
		$type   = null;

		$results = $this->_inno_client->query( $data, $raw, $writer, $record, $type, $query );

		foreach ( $results as $record ) {

			try {

				if ( false === $this->_inno_client ) {
					$this->set_client();
				}

				$this->_inno_client->delete( $record->meta->record_id );

			} catch ( ConflictException $e ) {

				return;

			}
		}

	}

	public function get_record( $lead_id, $form ) {

	}

	/**
	 * Retrieve the current instance of the Tozny client.
	 *
	 * @since 1.0
	 *
	 * @param array $form The current Form object.
	 *
	 * @return bool|\Tozny\E3DB\Client
	 */
	protected function set_client( $form = null ) {

		$settings = $this->get_form_settings( $form );

		if ( false === $this->_inno_client ) {

			$config = new Config(
				$settings['secure_client_id'],
				$settings['secure_api_key_id'],
				$settings['secure_api_secret'],
				$settings['secure_api_public_key'],
				$settings['secure_api_private_key'],
				$this->_api_url
			);

			/**
			 * Pass the configuration to the default coonection handler, which
			 * uses Guzzle for requests. If you need a different library for
			 * requests, subclass `\Tozny\E3DB\Connection` and pass an instance
			 * of your custom implementation to the client instead.
			 */
			$connection = new GuzzleConnection( $config );

			/**
			 * Pass both the configuration and connection handler when building
			 * a new client instance.
			 */
			$this->_inno_client = new Client( $config, $connection );

		}

		return $this->_inno_client;

	}

	public function update_record( $lead_id, $form ) {

	}
}
