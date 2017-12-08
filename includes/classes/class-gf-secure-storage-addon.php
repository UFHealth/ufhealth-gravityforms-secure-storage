<?php
/**
 * GF Secure Storage Addon
 *
 * Provides connection to Gravity Forms as well as admin and related items.
 *
 * @package UFHealth\Gravity_Forms_Secure_Storage
 *
 * @since   1.0
 *
 * @author  Chris Wiegman <cwiegman@ufl.edu>
 */

namespace UFHealth\Gravity_Forms_Secure_Storage;

/**
 * Class GF_Secure_Storage_Addon
 */
class GF_Secure_Storage_Addon extends \GFAddOn {

	/**
	 * Version number of the Add-On
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	protected $_version = UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_VERSION;

	/**
	 * Gravity Forms minimum version requirement
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	protected $_min_gravityforms_version = '2.2';

	/**
	 * URL-friendly identifier used for form settings, add-on settings, text domain localization...
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	protected $_slug = 'ufhealth-gravity-forms-secure-storage';

	/**
	 * Relative path to the plugin from the plugins folder. Example "gravityforms/gravityforms.php"
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	protected $_path = 'ufhealth-gravity-forms-secure-storage/ufhealth-gravity-forms-secure-storage.php';

	/**
	 * Full path the the plugin. Example: __FILE__
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	protected $_full_path = __FILE__;

	/**
	 * Title of the plugin to be used on the settings page, form settings and plugins page. Example: 'Gravity Forms MailChimp Add-On'
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	protected $_title = 'UF Health Gravity Forms Secure Storage';

	/**
	 * Short version of the plugin title to be used on menus and other places where a less verbose string is useful. Example: 'MailChimp'
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	protected $_short_title = 'Secure Storage';

	/**
	 * The innovault API Url
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	protected $_api_url = 'https://api.e3db.com';

	/**
	 * The local instance to avoid duplication.
	 *
	 * @since 1.0
	 *
	 * @var null|mixed
	 */
	private static $_instance = null;

	/**
	 * The values we need to secure
	 *
	 * @since 1.0
	 *
	 * @var array
	 */
	protected $_secure_values;

	/**
	 * The instance of the Tozny client.
	 *
	 * @since 1.0
	 *
	 * @var bool|\Tozny\E3DB\Client
	 */
	private $_inno_client = false;

	/**
	 * Array of retrieved entries for display. Used to reduce API calls.
	 *
	 * @since 1.0
	 *
	 * @var array
	 */
	private $_entries = array();

	/**
	 * Retrieve the current instance of the Tozny client.
	 *
	 * @since 1.0
	 *
	 * @param array $form The current Form object.
	 *
	 * @return bool|\Tozny\E3DB\Client
	 */
	protected function get_client( $form ) {

		$settings = $this->get_form_settings( $form );

		if ( false === $this->_inno_client ) {

			$config = new \Tozny\E3DB\Config(
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
			$connection = new \Tozny\E3DB\Connection\GuzzleConnection( $config );

			/**
			 * Pass both the configuration and connection handler when building
			 * a new client instance.
			 */
			$this->_inno_client = new \Tozny\E3DB\Client( $config, $connection );

		}

		return $this->_inno_client;

	}

	/**
	 * Create the local instance if needed.
	 *
	 * @since 1.0
	 *
	 * @return mixed|null|\UFHealth\Gravity_Forms_Secure_Storage\GF_Secure_Storage_Addon
	 */
	public static function get_instance() {

		if ( null === self::$_instance ) {
			self::$_instance = new GF_Secure_Storage_Addon();
		}

		return self::$_instance;

	}

	/**
	 * Plugin starting point. Handles hooks and loading of language files.
	 *
	 * @since 1.0
	 */
	public function init() {

		parent::init();

		add_action( 'gform_after_submission', array( $this, 'action_gform_after_submission' ), 10, 2 );
		add_action( 'gform_pre_submission', array( $this, 'action_gform_pre_submission' ) );

		add_filter( 'gform_entry_field_value', array( $this, 'filter_gform_entry_field_value' ), 10, 4 );

	}

	/**
	 * Filters a field value displayed within an entry.
	 *
	 * @since 1.5
	 *
	 * @param string    $display_value The value to be displayed.
	 * @param \GF_Field $field         The Field Object.
	 * @param array     $lead          The Entry Object.
	 * @param array     $form          The Form Object.
	 *
	 * @return string
	 */
	public function filter_gform_entry_field_value( $display_value, $field, $lead, $form ) {

		$settings = $this->get_form_settings( $form );

		if ( isset( $settings['enabled'] ) && '1' === $settings['enabled'] ) {

			// If we haven't already, query Innovault for the entry by id.
			if ( ! isset( $this->_entries[ $lead['id'] ] ) ) {

				$client = $this->get_client( $form );

				$query = array(
					'eq' =>
						array(
							'name'  => 'post_id',
							'value' => $lead['id'],
						),
				);

				$data   = true;
				$raw    = false;
				$writer = null;
				$record = null;
				$type   = null;

				$results = $client->query( $data, $raw, $writer, $record, $type, $query );

				foreach ( $results as $record ) {
					$this->_entries[ $lead['id'] ] = $record;
				}
			}

			// Populate the display value with the value from the secured data.
			$display_value = $this->_entries[ $lead['id'] ]->data[ $field['id'] ];

		}

		return $display_value;

	}

	/**
	 * Action gform_after_submission
	 *
	 * Add the entry id to the secure data after it is available.
	 *
	 * @since 1.0
	 *
	 * @param array $entry An array of the saved entry information.
	 * @param array $form  An array of the saved form information.
	 */
	public function action_gform_after_submission( $entry, $form ) {

		$settings = $this->get_form_settings( $form );

		if ( isset( $settings['enabled'] ) && '1' === $settings['enabled'] ) {

			// Send the data to Innovault using post_id as an indexable item.
			$meta_values = array(
				'post_id' => $entry['id'],
			);

			$client = $this->get_client( $form );

			$client->write( 'form_submission', $this->_secure_values, $meta_values );

			// Make sure we clean out the secured values locally to prevent it saving anywhere.
			$this->_secure_values = array();

		}
	}

	/**
	 * Action gform_pre_submission
	 *
	 * Send entry info to Innovault and prevent local save.
	 *
	 * @since 1.0
	 *
	 * @param array $form The current form.
	 */
	public function action_gform_pre_submission( $form ) {

		$settings = $this->get_form_settings( $form );

		if ( isset( $settings['enabled'] ) && '1' === $settings['enabled'] ) {

			$this->_secure_values = array();

			if ( isset( $form['fields'] ) && is_array( $form['fields'] ) ) {

				foreach ( $form['fields'] as $field ) {

					// Save the secured values for later use being careful not to cache them anywhere.
					$this->_secure_values[ $field->id ] = sanitize_text_field( $_POST[ 'input_' . $field->id ] ); // WPCS: input var ok. Sanitization ok.
					$_POST[ 'input_' . $field->id ]     = 'ufh-gf-secured';

				}
			}
		}
	}

	/**
	 * Configures the settings which should be rendered on the Form Settings
	 *
	 * @since 1.0
	 *
	 * @param array $form Array of form information.
	 *
	 * @return array
	 */
	public function form_settings_fields( $form ) {

		return array(
			array(
				'title'  => esc_html__( 'Secure Storage Settings', 'simpleaddon' ),
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
	 * The feedback callback for the 'text' settings on the form settings page.
	 *
	 * @since 1.0
	 *
	 * @param string $value The setting value.
	 *
	 * @return bool
	 */
	public function is_valid_setting( $value ) {

		return ( strlen( $value ) > 10 );

	}
}
