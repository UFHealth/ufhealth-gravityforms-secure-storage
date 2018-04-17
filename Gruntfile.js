module.exports = function (grunt) {

	// Start out by loading the grunt modules we'll need
	require('load-grunt-tasks')(grunt);

	// Show elapsed time
	require('time-grunt')(grunt);

	grunt.initConfig(
		{

			/**
			 * Clean existing files
			 */
			clean: {
				styles:  {
					src: [
						'assets/css/*.css',
						'assets/css/*.map'
					]
				},
				scripts: {
					src: [
						'assets/js/*.js',
						'assets/js/*.map'
					]
				}
			},

			/**
			 * Processes and compresses JavaScript.
			 */
			uglify: {

				production: {

					options: {
						beautify:         false,
						preserveComments: false,
						sourceMap:        false,
						mangle:           {
							reserved: ['jQuery']
						}
					},

					files: {
						'assets/js/ufhealth-gravity-forms-secure-storage.min.js': [
							'assets/js/src/ufhealth-gravity-forms-secure-storage.js'
						]
					}
				},

				dev: {

					options: {
						beautify:         true,
						preserveComments: true,
						sourceMap:        true,
						mangle:           {
							reserved: ['jQuery']
						}
					},

					files: {
						'assets/js/ufhealth-gravity-forms-secure-storage.js': [
							'assets/js/src/ufhealth-gravity-forms-secure-storage.js'
						]
					}
				}
			},

			/**
			 * Auto-prefix CSS Elements after SASS is processed.
			 */
			autoprefixer: {

				options: {
					browsers: ['last 5 versions'],
					map:      true
				},

				files: {
					expand:  true,
					flatten: true,
					src:     ['assets/css/ufhealth-gravity-forms-secure-storage.css'],
					dest:    'assets/css'
				}
			},

			/**
			 * Minify CSS after prefixes are added
			 */
			cssmin: {

				target: {

					files: [{
						expand: true,
						cwd:    'assets/css',
						src:    ['ufhealth-gravity-forms-secure-storage.css'],
						dest:   'assets/css',
						ext:    '.min.css'
					}]

				}
			},

			/**
			 * Process SASS
			 */
			sass: {

				dist: {

					options: {
						style:     'expanded',
						sourceMap: true,
						noCache:   true
					},

					files: {
						'assets/css/ufhealth-gravity-forms-secure-storage.css': 'assets/css/scss/ufhealth-gravity-forms-secure-storage.scss'
					}
				}
			},

			/**
			 * Update translation file.
			 */
			makepot: {

				target: {
					options: {
						type:        'wp-plugin',
						domainPath:  '/languages',
						mainFile:    'ufhealth-gravity-forms-secure-storage.php',
						potFilename: 'ufhealth-gravity-forms-secure-storage.pot',
						exclude: ['vendor']
					}
				}
			},

			/**
			 * Clean up the JavaScript
			 */
			jshint: {
				options: {
					jshintrc: true
				},
				all:     ['assets/js/src/ufhealth-gravity-forms-secure-storage.js']
			},

			/**
			 * Watch scripts and styles for changes
			 */
			watch: {

				options: {
					livereload: true
				},

				scripts: {

					files: [
						'assets/js/src/*'
					],

					tasks: ['uglify:production']

				},

				styles: {

					files: [
						'assets/css/scss/*'
					],

					tasks: ['sass', 'autoprefixer', 'cssmin']

				}
			}
		}
	);

	// A very basic default task.
	grunt.registerTask('default', [ 'jshint', 'uglify:production', 'uglify:dev', 'sass', 'autoprefixer', 'cssmin', 'makepot']);
	grunt.registerTask('dev', ['default', 'watch']);

};