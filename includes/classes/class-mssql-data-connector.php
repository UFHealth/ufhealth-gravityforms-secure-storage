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
	 * MSSSQL_Data_Connector constructor.
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

			while ( $row = $result->fetch( \PDO::FETCH_NUM ) ) {
				$table_list[] = $row[0];
			}
		} catch ( \PDOException $e ) {

			echo $e->getMessage();

		}

		if ( ! in_array( $table_name, $table_list, true ) ) {

			$sql = "CREATE TABLE " . $table_name . " ("
			       . " ID INT IDENTITY(1,1) PRIMARY KEY"
			       . ", Submitted DATETIME NOT NULL DEFAULT (GETDATE())"
			       . ")";

			$this->_mssql_connection->query( $sql );

		}

		// Add each field to the table if needed.
		$fields           = $form_meta['fields'];
		$column_statement = $this->_mssql_connection->query( "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='" . $table_name . "';" );
		$raw_columns      = $column_statement->fetchAll( \PDO::FETCH_ASSOC );
		$columns          = array();

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

			$columns                                .= $column_names[ $field ] . ', ';
			$values                                 .= ':' . $column_names[ $field ] . ', ';
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

			if ( false === $this->_mssql_connection ) {
				$this->set_client();
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

		add_filter( 'ufhealth_gf_secure_data_connectors', array( '\UFHealth\Gravity_Forms_Secure_Storage\MSSSQL_Data_Connector', 'filter_ufhealth_gf_secure_data_connectors' ) );

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

		$connectors['mssql'] = new MSSSQL_Data_Connector();

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

		return esc_html__( 'MS SQL Database', 'ufhealth-gravity-forms-secure-storage' );

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

			$host    = $this->settings['secure_database_host'];
			$db      = $this->settings['secure_database_name'];
			$user    = $this->settings['secure_database_username'];
			$pass    = $this->settings['secure_database_password'];
			$charset = 'utf8mb4';

			$dsn = "dblib:host=$host;dbname=$db;charset=$charset";
			$opt = array(
				\PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
				\PDO::ATTR_EMULATE_PREPARES   => false,
			);

			$this->_mssql_connection = new \PDO( $dsn, $user, $pass, $opt );

		}

		return $this->_mssql_connection;

	}
}
