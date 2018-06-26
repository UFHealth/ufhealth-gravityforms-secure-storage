UF Health Gravity Forms Secure Storage
=============

[![pipeline status](https://gitlab.ahc.ufl.edu/WebServices/WordPress-Plugins/ufhealth-gravityforms-secure-storage/badges/master/pipeline.svg)](https://gitlab.ahc.ufl.edu/WebServices/WordPress-Plugins/ufhealth-gravityforms-secure-storage/commits/master)
[![coverage report](https://gitlab.ahc.ufl.edu/WebServices/WordPress-Plugins/ufhealth-gravityforms-secure-storage/badges/master/coverage.svg)](https://gitlab.ahc.ufl.edu/WebServices/WordPress-Plugins/ufhealth-gravityforms-secure-storage/commits/master)

Adds a secure storage layer to Gravity Forms to fascilitate our ability to handle various data requirements.

## Installation and Usage

In order to improve efficiency processed files such as minified JS, CSS and .pot files are not stored in this repository. To use this plugin:

1. Clone the repository
2. Change to the repository directory
3. Run ```composer install```
4. Run ```npm install```
5. Run ```grunt```

## Setting up a local devopment environment

1. Run `composer install`
2. Run `npm install` 
3. If Grunt is not available on you system install it with `npm -g install grunt grunt-cli`
4. Run `grunt`
4. Clone and bring up [Ouroboros](https://github.com/UFHealth/ouroboros) using the instructions on its page
5. Bring up this projects Docker configuration with `./develop up`
6. Run the setup script in `./Docker/bin/setup`
7. Access the site at http://ufhealthgravity-forms-secure-storage.test

*Note: you might have to install Grunt globally first with ```npm -g install grunt```*

## Recommended Developer Workflow

1. Commit the initial plugin scaffolding to the *master* branch in a new repository
2. Branch to develop to work
3. Merge back to master and tag with the plugin version for release

## Configuration constants available:

1. *UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_CONNECTOR* - An integer defining the connector to use.
2. *UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_REQUIRE_SUPER_ADMIN* - in multisite this restricts changing any security settings on a form to a network administrator.

### MS SQL configuration constants
1. *UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_SECURE_DATABASE_HOST* - MSSQL database host.
2. *UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_SECURE_DATABASE_NAME* - MSSQL database name.
3. *UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_SECURE_DATABASE_USERNAME* - MSSQL database username.
4. *UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_SECURE_DATABASE_PASSWORD* - MSSQL database password.

### Innovault configuration constants
1. *UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_SECURE_CLIENT_ID* - Innovault secure client id.
2. *UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_SECURE_API_KEY_ID* - Innovault app key id.
3. *UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_SECURE_API_SECRET* - Innovault api secret.
4. *UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_SECURE_API_PUBLIC_KEY* - Innovault api public key
5. *UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_SECURE_API_PRIVATE_KEY* - Innovault api private key
1. *UFHEALTH_GRAVITY_FORMS_SECURE_STORAGE_SECURE_CLIENT_ID* - Innovault 

## Available filters

1. *ufhealth_gf_secure_data_connectors* _array_ Filters the array of available data connectors (allowing you to add your own).
2. *ufhealth_gf_secure_no_access_text* _string_ filter the display text when a user doesn't have access to the settings. 

## Changelog

##### 1.6
* Added ability to define settings in wp-config (or elsewhere) as well as ability to limit settings to super-admins in a multisite network.

##### 1.5
* Added Docker information for easier local development.
* Allows for all confirguation information to be stored in a file outside of the database for better protection of the credentials.

##### 1.4.1
* Complete CI information in readme and verify coverage whitelist in phpunit.xml

##### 1.4
* Ensure CI is working

##### 1.3
* Clean up for code review and code standards

##### 1.2
* Added better abstraction to allow for more dynamic data connectors
* Added an MS SQL data connector.

##### 1.1.2
* Better loading of Interface

##### 1.1.1
* Fix filter call to apply filter rather than add it.

##### 1.1
* Provide an interface for data connectors as well as a filter to override the default Tozny data connector.

##### 1.0
* Initial Release
