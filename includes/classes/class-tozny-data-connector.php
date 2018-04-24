<?php
/**
 * Tozny Data Connector
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
 * Class Tozny_Data_Connector
 */
class Tozny_Data_Connector implements GF_Secure_Data_Connector {

	/**
	 * The innovault API Url
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	protected $_api_url = 'https://api.e3db.com';

	/**
	 * Array of retrieved entries for display. Used to reduce API calls.
	 *
	 * @since 1.0
	 *
	 * @var array
	 */
	private $_entries = array();

	/**
	 * Array of form settings.
	 *
	 * @since 1.0
	 *
	 * @var bool|array
	 */
	protected $settings = false;

	/**
	 * The instance of the Tozny client.
	 *
	 * @since 1.0
	 *
	 * @var bool|\Tozny\E3DB\Client
	 */
	protected $_inno_client = false;

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
	public function add_record( $secure_values, $post_id, $form_id, $column_names = array() ) {

		// Send the data to Innovault using post_id as an indexable item.
		$meta_values = array(
			'post_id' => $post_id,
		);

		$this->_inno_client->write( 'form_submission', $secure_values, $meta_values );

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

	/**
	 * Delete a record from secure storage.
	 *
	 * @since 1.0
	 *
	 * @param int $lead_id The id of the lead to delete from secure storage.
	 */
	public function delete_record( $lead_id ) {

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

				$this->_inno_client->delete( $record->meta->record_id );

			} catch ( ConflictException $e ) {

				return;

			}
		}

	}

	/**
	 * Retrieve secure record data from data store.
	 *
	 * @since 1.0
	 *
	 * @param int $lead_id The id of the record to retrieve.
	 *
	 * @return array|bool Array of secure data or False on failure.
	 */
	public function get_record( $lead_id ) {

		if ( ! isset( $this->_entries[ $lead_id ] ) ) {

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
				$this->_entries[ $lead_id ] = $record->data;
			}
		}

		if ( isset( $this->_entries[ $lead_id ] ) ) {
			return $this->_entries[ $lead_id ];
		}

		return false;

	}

	/**
	 * Setup information for the current form.
	 *
	 * @since 1.0
	 *
	 * @param array $form_settings The settings for the current form.
	 *
	 * @return bool True on success or false.
	 */
	public function init( $form_settings ) {

		if ( false === $this->settings ) {

			$this->settings = $form_settings;

			if ( false === $this->_inno_client ) {
				$this->set_client();
			}

			return true;

		}

		return false;

	}

	/**
	 * Retrieve the current instance of the Tozny client.
	 *
	 * @since 1.0
	 *
	 * @return bool|\Tozny\E3DB\Client
	 */
	protected function set_client() {

		if ( false === $this->_inno_client ) {

			$config = new Config(
				$this->settings['secure_client_id'],
				$this->settings['secure_api_key_id'],
				$this->settings['secure_api_secret'],
				$this->settings['secure_api_public_key'],
				$this->settings['secure_api_private_key'],
				$this->_api_url
			);

			/**
			 * Pass the configuration to the default connection handler, which
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
}
