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
	 * @var string Version number of the Add-On
	 */
	protected $_version = UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_VERSION;

	/**
	 * @var string Gravity Forms minimum version requirement
	 */
	protected $_min_gravityforms_version = '2.2';

	/**
	 * @var string URL-friendly identifier used for form settings, add-on settings, text domain localization...
	 */
	protected $_slug = 'ufhealth-gravity-forms-secure-storage';

	/**
	 * @var string Relative path to the plugin from the plugins folder. Example "gravityforms/gravityforms.php"
	 */
	protected $_path = 'ufhealth-gravity-forms-secure-storage/ufhealth-gravity-forms-secure-storage.php';

	/**
	 * @var string Full path the the plugin. Example: __FILE__
	 */
	protected $_full_path = __FILE__;

	/**
	 * @var string Title of the plugin to be used on the settings page, form settings and plugins page. Example: 'Gravity Forms MailChimp Add-On'
	 */
	protected $_title = 'UF Health Gravity Forms Secure Storage';

	/**
	 * @var string Short version of the plugin title to be used on menus and other places where a less verbose string is useful. Example: 'MailChimp'
	 */
	protected $_short_title = 'Gravity Forms Secure Storage';

	/**
	 * @var null|mixed The local instance to avoid duplication.
	 */
	private static $_instance = null;

	/**
	 * Create the local instance if needed.
	 *
	 * @since 1.0
	 *
	 * @return mixed|null|\UFHealth\Gravity_Forms_Secure_Storage\GF_Secure_Storage_Addon
	 */
	public static function get_instance() {

		if ( self::$_instance == null ) {
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

	}
}