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
 * Class MSSQL_Data_Connector
 */
class MSSQL_Data_Connector implements GF_Secure_Data_Connector {

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
	 * MSSQL_Data_Connector constructor.
	 */
	public function __construct() {

		add_action( 'ufhealth_secure_gform_after_save_form', array( $this, 'action_ufhealth_secure_gform_after_save_form' ), 10, 2 );

	}

	/**
	 * Action gform_after_save_form
	 *
	 * Make sure the MSSQL is present and correct.
	 *
	 * @since 1.1.2
	 *
	 * @param array $form_meta The form meta.
	 * @param bool  $is_new    True if this is a new form being created. False if this is an existing form being updated.
	 */
	public function action_ufhealth_secure_gform_after_save_form( $form_meta, $is_new ) {

		// Create the database table if we need to.
		$table_list = array();
		$table_name = 'site_' . get_current_blog_id() . '_form_' . $form_meta['id'];

		try {

			$result = $this->_mssql_connection->query( 'SELECT Distinct TABLE_NAME FROM information_schema.TABLES' );

			// phpcs:disable
			while ( $row = $result->fetch( \PDO::FETCH_NUM ) ) {
				$table_list[] = $row[0];
			}
			// phpcs:enable
		} catch ( \PDOException $e ) {

			echo esc_html( $e->getMessage() );

		}

		if ( ! in_array( $table_name, $table_list, true ) ) {

			$sql = 'CREATE TABLE ' . $table_name . ' ( ID INT IDENTITY(1,1) PRIMARY KEY, Submitted DATETIME NOT NULL DEFAULT (GETDATE()))';

			$this->_mssql_connection->query( $sql );

		}

		// Add each field to the table if needed.
		$fields           = $form_meta['fields'];
		$column_statement = $this->_mssql_connection->query( sprintf( 'SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME=\'%s\';', $table_name ) );

		// phpcs:disable
		$raw_columns = $column_statement->fetchAll( \PDO::FETCH_ASSOC ); // WPCS: db call ok.
		// phpcs:enable

		$columns = array();

		foreach ( $raw_columns as $column ) {
			$columns[] = $column['COLUMN_NAME'];
		}

		foreach ( $fields as $field ) {

			// Save the secured values for later use being careful not to cache them anywhere.
			if ( null === $field->inputs ) {

				$column_name = strtolower( $field->label ) . '_' . $field->id;

				if ( ! in_array( $column_name, $columns, true ) ) {
					$this->_mssql_connection->query( 'ALTER TABLE dbo.' . $table_name . ' ADD ' . $column_name . ' TEXT NULL;' );
				}
			} else {

				foreach ( $field->inputs as $input ) {

					$input_id     = explode( '.', $input['id'] );
					$field_sub_id = $input_id[1];

					$column_name = strtolower( $field->label ) . '_' . $field->id . '_' . strtolower( $input['label'] ) . '_' . $field_sub_id;

					if ( ! in_array( $column_name, $columns, true ) ) {
						$this->_mssql_connection->query( 'ALTER TABLE dbo.' . $table_name . ' ADD ' . $column_name . ' TEXT NULL;' );
					}
				}
			}
		}
	}

	/**
	 * Write a record to secure storage.
	 *
	 * @since 1.1.2
	 *
	 * @param array $secure_values Array of secure values.
	 * @param int   $post_id       The post ID to index the secure values.
	 * @param int   $form_id       The ID of the submitted form.
	 * @param array $column_names  Array of column names for writing straight to an external database.
	 */
	public function add_record( $secure_values, $post_id, $form_id, $column_names = array() ) {

		$columns     = '';
		$values      = '';
		$table_name  = 'site_' . get_current_blog_id() . '_form_' . $form_id;
		$exec_values = array();

		foreach ( $secure_values as $field => $value ) {

			$columns .= $column_names[ $field ] . ', ';
			$values  .= ':' . $column_names[ $field ] . ', ';

			$exec_values[ $column_names[ $field ] ] = $value;

		}

		$sql_statement = $this->_mssql_connection->prepare( sprintf( 'INSERT INTO dbo.%s (%s) VALUES (%s);', $table_name, rtrim( trim( $columns ), ',' ), rtrim( trim( $values ), ',' ) ) );

		$sql_statement->execute( $exec_values );

	}

	/**
	 * Returns the settings fields needed to configure the secure form.
	 *
	 * @since 1.1.2
	 *
	 * @return array Array of settings fields.
	 */
	public function get_settings_fields() {

		$settings_fields = array();

		// Add database host setting.
		if ( ! defined( 'UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_SECURE_DATABASE_HOST' ) ) {

			$settings_fields[] = array(
				'label'             => esc_html__( 'Database Host', 'ufhealth-gravityforms-secure-storage' ),
				'required'          => true,
				'type'              => 'text',
				'name'              => 'secure_database_host',
				'tooltip'           => esc_html__( 'The host server of the MSSQL Database', 'ufhealth-gravityforms-secure-storage' ),
				'class'             => 'medium',
				'feedback_callback' => array( $this, 'is_valid_setting' ),
			);

		}

		// Add database name setting.
		if ( ! defined( 'UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_SECURE_DATABASE_NAME' ) ) {

			$settings_fields[] = array(
				'label'             => esc_html__( 'Database Name', 'ufhealth-gravityforms-secure-storage' ),
				'required'          => true,
				'type'              => 'text',
				'name'              => 'secure_database_name',
				'tooltip'           => esc_html__( 'The name of the MSSQL Database', 'ufhealth-gravityforms-secure-storage' ),
				'class'             => 'medium',
				'feedback_callback' => array( $this, 'is_valid_setting' ),
			);

		}

		// Add database username setting.
		if ( ! defined( 'UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_SECURE_DATABASE_USERNAME' ) ) {

			$settings_fields[] = array(
				'label'             => esc_html__( 'Database Username', 'ufhealth-gravityforms-secure-storage' ),
				'required'          => true,
				'type'              => 'text',
				'name'              => 'secure_database_username',
				'tooltip'           => esc_html__( 'The username for the MSSQL Database', 'ufhealth-gravityforms-secure-storage' ),
				'class'             => 'medium',
				'feedback_callback' => array( $this, 'is_valid_setting' ),
			);

		}

		// Add database password setting.
		if ( ! defined( 'UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_SECURE_DATABASE_PASSWORD' ) ) {

			$settings_fields[] = array(
				'label'             => esc_html__( 'Database Password', 'ufhealth-gravityforms-secure-storage' ),
				'required'          => true,
				'type'              => 'text',
				'name'              => 'secure_database_password',
				'tooltip'           => esc_html__( 'The user password for the MSSQL Database', 'ufhealth-gravityforms-secure-storage' ),
				'class'             => 'medium',
				'feedback_callback' => array( $this, 'is_valid_setting' ),
			);

		}

		return $settings_fields;

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

			// Allow settings to be permanently overridden via defines.
			if ( defined( 'UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_SECURE_DATABASE_HOST' ) ) {
				$this->settings['secure_database_host'] = UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_SECURE_DATABASE_HOST;
			}

			if ( defined( 'UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_SECURE_DATABASE_NAME' ) ) {
				$this->settings['secure_database_name'] = UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_SECURE_DATABASE_NAME;
			}

			if ( defined( 'UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_SECURE_DATABASE_USERNAME' ) ) {
				$this->settings['secure_database_username'] = UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_SECURE_DATABASE_USERNAME;
			}

			if ( defined( 'UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_SECURE_DATABASE_PASSWORD' ) ) {
				$this->settings['secure_database_password'] = UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_SECURE_DATABASE_PASSWORD;
			}

			if ( false === $this->_mssql_connection ) {
				return $this->set_client();
			}

			return true;

		}

		return false;

	}

	/**
	 * Adds the appropriate filter to register this data connector.
	 *
	 * @since 1.0
	 */
	public static function register_connector() {

		add_filter( 'ufhealth_gf_secure_data_connectors', array( '\UFHealth\Gravity_Forms_Secure_Storage\MSSQL_Data_Connector', 'filter_ufhealth_gf_secure_data_connectors' ) );

	}

	/**
	 * Register the connector itself.
	 *
	 * @since 1.0
	 *
	 * @param array $connectors Array of data connectors in a name:connector format.
	 *
	 * @return array
	 */
	public static function filter_ufhealth_gf_secure_data_connectors( $connectors ) {

		$connectors['mssql'] = new static();

		return $connectors;

	}

	/**
	 * Returns the label used to help select the data connector in settings.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_label() {

		return esc_html__( 'MS SQL Database', 'ufhealth-gravityforms-secure-storage' );

	}

	/**
	 * Retrieve the current instance of the MS SQL client.
	 *
	 * @since 1.1.2
	 *
	 * @return mixed
	 */
	protected function set_client() {

		if ( false === $this->_mssql_connection ) {

			$host    = $this->settings['secure_database_host'];
			$db      = $this->settings['secure_database_name'];
			$user    = $this->settings['secure_database_username'];
			$pass    = $this->settings['secure_database_password'];
			$charset = 'utf8mb4';

			$dsn = "dblib:host=$host;dbname=$db;charset=$charset";
			// phpcs:disable
			$opt = array(
				\PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
				\PDO::ATTR_EMULATE_PREPARES   => false,
			);

			try {

				$this->_mssql_connection = new \PDO( $dsn, $user, $pass, $opt );

			} catch ( \PDOException $e ) {

				wp_die( esc_html__( 'Unable to connect to MSSQL Server with the following error message: ', 'ufhealth-gravityforms-secure-storage' ) . esc_html( $e->getMessage() ), esc_html__( 'Secure Database Connection Error.', 'ufhealth-gravityforms-secure-storage' ) );

				return false;

			}
			// phpcs:enable

		}

		return true;

	}
}
