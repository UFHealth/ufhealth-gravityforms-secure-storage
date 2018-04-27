module.exports = function (grunt) {

	// Start out by loading the grunt modules we'll need
	require('load-grunt-tasks')(grunt);

	// Show elapsed time
	require('time-grunt')(grunt);

	grunt.initConfig(
		{
			/**
			 * Update translation file.
			 */
			makepot: {

				target: {
					options: {
						type:        'wp-plugin',
						domainPath:  '/languages',
						mainFile:    'ufhealth-gravityforms-secure-storage.php',
						potFilename: 'ufhealth-gravity-forms-secure-storage.pot',
						exclude: ['vendor']
					}
				}
			}
		}
	);

	// A very basic default task.
	grunt.registerTask('default', ['makepot']);

};