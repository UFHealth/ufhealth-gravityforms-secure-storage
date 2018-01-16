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

use Tozny\E3DB\Client;
use Tozny\E3DB\Config;
use Tozny\E3DB\Connection\GuzzleConnection;
use Tozny\E3DB\Exceptions\ConflictException;

/**
 * Class GF_Secure_Storage_Addon
 */
class GF_Secure_Storage_Addon extends \GFAddOn {

	/**
	 * The local instance to avoid duplication.
	 *
	 * @since 1.0
	 *
	 * @var null|mixed
	 */
	private static $_instance = null;

	/**
	 * Array of retrieved entries for display. Used to reduce API calls.
	 *
	 * @since 1.0
	 *
	 * @var array
	 */
	private $_entries = array();

	/**
	 * The instance of the Tozny client.
	 *
	 * @since 1.0
	 *
	 * @var bool|\Tozny\E3DB\Client
	 */
	private $_inno_client = false;

	/**
	 * The innovault API Url
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	protected $_api_url = 'https://api.e3db.com';

	/**
	 * The connector to the secure data store
	 *
	 * @since 1.0
	 *
	 * @var \UFHealth\Gravity_Forms_Secure_Storage\GF_Secure_Data_Connector
	 */
	protected $_data_connector;

	/**
	 * Full path the the plugin. Example: __FILE__
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	protected $_full_path = __FILE__;

	/**
	 * Gravity Forms minimum version requirement
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	protected $_min_gravityforms_version = '2.2';

	/**
	 * Relative path to the plugin from the plugins folder. Example "gravityforms/gravityforms.php"
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	protected $_path = 'ufhealth-gravity-forms-secure-storage/ufhealth-gravity-forms-secure-storage.php';

	/**
	 * The values we need to secure
	 *
	 * @since 1.0
	 *
	 * @var array
	 */
	protected $_secure_values;

	/**
	 * Short version of the plugin title to be used on menus and other places where a less verbose string is useful. Example: 'MailChimp'
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	protected $_short_title = 'Secure Storage';

	/**
	 * URL-friendly identifier used for form settings, add-on settings, text domain localization...
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	protected $_slug = 'ufhealth-gravity-forms-secure-storage';

	/**
	 * Title of the plugin to be used on the settings page, form settings and plugins page. Example: 'Gravity Forms MailChimp Add-On'
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	protected $_title = 'UF Health Gravity Forms Secure Storage';

	/**
	 * Version number of the Add-On
	 *
	 * @since 1.0
	 *
	 * @var string
	 */
	protected $_version = UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_VERSION;

	/**
	 * Plugin starting point. Handles hooks and loading of language files.
	 *
	 * @since 1.0
	 */
	public function init() {

		parent::init();

		require dirname( __FILE__ ) . '/class-gf-secure-data-connector.php';
		$this->_data_connector = new GF_Secure_Data_Connector();

		add_action( 'gform_after_submission', array( $this, 'action_gform_after_submission' ), 10, 2 );
		add_action( 'gform_delete_entries', array( $this, 'action_gform_delete_entries' ), 10, 2 );
		add_action( 'gform_delete_lead', array( $this, 'action_gform_delete_lead' ) );
		add_action( 'gform_pre_submission', array( $this, 'action_gform_pre_submission' ) );

		add_filter( 'gform_entry_field_value', array( $this, 'filter_gform_entry_field_value' ), 10, 4 );
		add_filter( 'gform_get_field_value', array( $this, 'filter_gform_get_field_value' ), 10, 3 );

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

			$client = $this->set_client( $form );

			$client->write( 'form_submission', $this->_secure_values, $meta_values );

			// Make sure we clean out the secured values locally to prevent it saving anywhere.
			$this->_secure_values = array();

		}
	}

	/**
	 * Action gform_delete_entries
	 *
	 * Delete secured info during bulk deletion of entries.
	 *
	 * @since 1.0
	 *
	 * @param int    $form_id The ID of the current form.
	 * @param string $status  The status we're deleting (such as during empty trash).
	 */
	public function action_gform_delete_entries( $form_id, $status ) {

		global $wpdb;

		$form     = \GFAPI::get_form( $form_id );
		$settings = $this->get_form_settings( $form );

		if ( isset( $settings['enabled'] ) && '1' === $settings['enabled'] ) {

			$lead_table    = \GFFormsModel::get_lead_table_name();
			$status_filter = empty( $status ) ? '' : $wpdb->prepare( 'AND status=%s', $status );

			// Get the entries.
			$sql     = $wpdb->prepare( "SELECT * FROM $lead_table WHERE form_id=%d {$status_filter}", $form_id );
			$results = $wpdb->get_results( $sql ); // WPCS: db call ok.

			if ( is_array( $results ) && ! empty( $results ) ) {

				foreach ( $results as $result ) {

					try {

						$this->_data_connector->delete_record( $result->id );

					} catch ( \Exception $e ) {

						// @todo This should stop everything as this could be really bad for regulatory compliance.
						return;

					}
				}
			}
		}
	}

	/**
	 * Action gform_delete_lead
	 *
	 * Delete secured info from individual entries.
	 *
	 * @since 1.0
	 *
	 * @param int $entry_id The id of the entry we're deleting from.
	 */
	public function action_gform_delete_lead( $entry_id ) {

		$entry = \GFAPI::get_entry( $entry_id );
		$form  = \GFAPI::get_form( $entry['form_id'] );

		$settings = $this->get_form_settings( $form );

		if ( isset( $settings['enabled'] ) && '1' === $settings['enabled'] ) {

			$client = $this->set_client( $form );

			$query = array(
				'eq' =>
					array(
						'name'  => 'post_id',
						'value' => $entry_id,
					),
			);

			$data   = true;
			$raw    = false;
			$writer = null;
			$record = null;
			$type   = null;

			$results = $client->query( $data, $raw, $writer, $record, $type, $query );

			foreach ( $results as $record ) {

				try {

					$client->delete( $record->meta->record_id );

				} catch ( ConflictException $e ) {

					return;

				}
			}
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
					if ( null === $field->inputs ) {

						if ( isset( $_POST[ 'input_' . $field->id ] ) ) { // WPCS: CSRF ok.
							$this->_secure_values[ $field->id ] = sanitize_text_field( $_POST[ 'input_' . $field->id ] ); // WPCS: input var ok. Sanitization ok.
						}

						$_POST[ 'input_' . $field->id ] = 'ufh-gf-secured/' . $field->id;

					} else {

						foreach ( $field->inputs as $input ) {

							$input_id     = explode( '.', $input['id'] );
							$field_sub_id = $input_id[1];
							$post_id      = 'input_' . $field->id . '_' . $field_sub_id;

							if ( isset( $_POST[ $post_id ] ) ) { // WPCS: CSRF ok.

								$this->_secure_values[ $field->id . '.' . $field_sub_id ] = sanitize_text_field( $_POST[ $post_id ] ); // WPCS: input var ok. Sanitization ok.
								$_POST[ $post_id ]                                        = 'ufh-gf-secured/' . $field->id . '.' . $field_sub_id;

							}
						}
					}
				}
			}
		}
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

				$client = $this->set_client( $form );

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
					$this->_entries[ $lead['id'] ] = $record->data;
				}
			}

			// Populate the display value with the value from the secured data.
			if ( is_array( $field['inputs'] ) ) {

				$display_value = '';

				foreach ( $field['inputs'] as $input ) {

					if ( isset( $this->_entries[ $lead['id'] ][ $input['id'] ] ) ) {
						$display_value .= ' ' . $this->_entries[ $lead['id'] ][ $input['id'] ];
					}

					$display_value = trim( $display_value );

				}
			} else {

				$display_value = $this->_entries[ $lead['id'] ][ $field['id'] ];

			}
		}

		return $display_value;

	}

	/**
	 * Filter gform_get_field_value
	 *
	 * Restore secure values to lead.
	 *
	 * @since 1.0
	 */
	public function filter_gform_get_field_value( $value, $lead, $field ) {

		$form     = \GFAPI::get_form( $field['formId'] );
		$settings = $this->get_form_settings( $form );

		if ( isset( $settings['enabled'] ) && '1' === $settings['enabled'] ) {

			// If we haven't already, query Innovault for the entry by id.
			if ( ! isset( $this->_entries[ $lead['id'] ] ) ) {

				if ( is_array( $this->_secure_values ) && ! empty( $this->_secure_values ) ) {

					$this->_entries[ $lead['id'] ] = $this->_secure_values;

				} else {

					$client = $this->set_client( $form );

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
						$this->_entries[ $lead['id'] ] = $record->data;
					}
				}
			}

			// Populate the display value with the value from the secured data.
			if ( is_array( $field['inputs'] ) ) {

				foreach ( $field['inputs'] as $input ) {

					if ( isset( $this->_entries[ $lead['id'] ][ $input['id'] ] ) ) {

						if ( is_array( $value ) ) {

							$value[ $input['id'] ] = $this->_entries[ $lead['id'] ][ $input['id'] ];

						} else { // Complex fields display differently depending on which view so we have to set the right value by using the saved ID.

							$id_array = explode( '/', $value );

							if ( isset( $id_array[1] ) && $input['id'] === $id_array[1] ) {
								$value = $this->_entries[ $lead['id'] ][ $input['id'] ];
							}
						}
					}
				}

				if ( is_array( $value ) ) {

					foreach ( $value as $index => $item ) {

						if ( 'ufh-gf-secured' === $item ) {
							$value[ $index ] = '';
						}
					}
				}
			} else {

				$value = $this->_entries[ $lead['id'] ][ $field['id'] ];

			}
		}

		return $value;

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

		return $this->_data_connector->get_settings_fields();

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
