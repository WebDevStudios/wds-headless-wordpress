<?php
/**
 * Application.
 *
 * @since 1.0.0
 * @package  WebDevStudios\SSO\AddonWDS
 */

namespace WebDevStudios\SSO\AddonWDS;

use \Exception;

/**
 * Application Loader.
 *
 * Everything starts here. If you create a new class,
 * attach it to this class using attach() below.
 *
 * @since 1.0.0
 */
class App {

	/**
	 * Plugin basename.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @var    string
	 * @since  1.0.0
	 */
	public $basename = '';

	/**
	 * URL of plugin directory.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @var    string
	 * @since  1.0.0
	 */
	public $url = '';

	/**
	 * Path of plugin directory.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @var    string
	 * @since  1.0.0
	 */
	public $path = '';

	/**
	 * Is WP_DEBUG set?
	 *
	 * @since  1.0.0
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 *
	 * @var boolean
	 */
	public $wp_debug = false;

	/**
	 * The plugin file.
	 *
	 * @since  1.0.0
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 *
	 * @var string
	 */
	public $plugin_file = '';

	/**
	 * The plugin headers.
	 *
	 * @since  1.0.0
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 *
	 * @var string
	 */
	public $plugin_headers = '';

	/**
	 * Roles object.
	 *
	 * @var Roles
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  1.0.0
	 */
	private $roles;

	/**
	 * Settings object.
	 *
	 * @var Settings
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  1.0.0
	 */
	private $settings;

	/**
	 * Construct.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.0.0
	 *
	 * @param string $plugin_file The plugin file, usually __FILE__ of the base plugin.
	 *
	 * @throws Exception If $plugin_file parameter is invalid (prevents plugin from loading).
	 */
	public function __construct( $plugin_file ) {

		// Check input validity.
		if ( empty( $plugin_file ) || ! stream_resolve_include_path( $plugin_file ) ) {

			// Translators: Displays a message if a plugin file is not passed.
			throw new Exception( sprintf( esc_html__( 'Invalid plugin file %1$s supplied to %2$s', 'wds-sso-addon-wds' ), $plugin_file, __METHOD__ ) );
		}

		// Plugin setup.
		$this->plugin_file = $plugin_file;
		$this->basename    = plugin_basename( $plugin_file );
		$this->url         = plugin_dir_url( $plugin_file );
		$this->path        = plugin_dir_path( $plugin_file );
		$this->wp_debug    = defined( 'WP_DEBUG' ) && WP_DEBUG;

		// Plugin information.
		$this->plugin_headers = get_file_data( $plugin_file, array(
			'Plugin Name' => 'Plugin Name',
			'Description' => 'Description',
			'Version'     => 'Version',
			'Author'      => 'Author',
			'Author URI'  => 'Author URI',
			'Text Domain' => 'Text Domain',
			'Network'     => 'Network',
			'License'     => 'License',
			'License URI' => 'License URI',
		), 'plugin' );

		// Load language files.
		load_plugin_textdomain( 'wds-office-localization-manager', false, basename( dirname( $plugin_file ) ) . '/languages' );

		// Loaders.
		$this->auto_loader();
	}

	/**
	 * Register the autoloader.
	 *
	 * @since 1.0.0
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 */
	private function auto_loader() {

		// Register our autoloader.
		spl_autoload_register( array( $this, 'autoload' ) );
	}

	/**
	 * Require classes.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.0.0
	 *
	 * @param string $class_name Fully qualified name of class to try and load.
	 *
	 * @return  void Early exit if we can't load the class.
	 */
	public function autoload( $class_name ) {

		// If our class doesn't have our namespace, don't load it.
		if ( 0 !== strpos( $class_name, 'WebDevStudios\\SSO\\AddonWDS\\' ) ) {
			return;
		}

		// Autoload files from parts.
		$this->autoload_from_parts( explode( '\\', $class_name ) );
	}

	/**
	 * Autoload files from self::autoload() parts.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.0.0
	 *
	 * @param  array $parts  The parts from self::autoload().
	 * @return void
	 */
	private function autoload_from_parts( $parts ) {

		// includes/.
		if ( stream_resolve_include_path( $this->autoload_include_file( $parts, 'includes' ) ) ) {
			require_once $this->autoload_include_file( $parts );
			return;
		}

		// feature/.
		if ( stream_resolve_include_path( $this->autoload_feature_file( $parts ) ) ) {
			require_once $this->autoload_feature_file( $parts );
			return;
		}
	}

	/**
	 * Autoload a feature e.g. feature/class-feature.php.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.0.0
	 *
	 * @param  array $parts The parts from self::autoload().
	 * @return string       The path to that feature class file.
	 */
	private function autoload_feature_file( $parts ) {
		$last = end( $parts );
		if ( $last ) {

			// Where would it be?
			$file = $this->autoload_class_file( $parts );
			$dir  = $this->autoload_dir( 'features/' . strtolower( str_replace( '_', '-', $last ) ) );

			// Pass back that path.
			return "{$dir}{$file}";
		}

		return '';
	}

	/**
	 * Get a file for including from includes/.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.0.0
	 *
	 * @param  array $parts The parts from self::autoload().
	 * @return string       The path to that file.
	 */
	private function autoload_include_file( $parts ) {
		return $this->autoload_dir( 'includes' ) . $this->autoload_class_file( $parts );
	}

	/**
	 * Get a directory for autoload.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.0.0
	 *
	 * @param  string $dir What dir, e.g. includes.
	 * @return string      The path to that directory.
	 */
	private function autoload_dir( $dir = '' ) {
		return trailingslashit( $this->path ) . trailingslashit( $dir );
	}

	/**
	 * Generate a class filename to autoload.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.0.0
	 *
	 * @param  array $parts  The parts from self::autoload().
	 * @return string        The class filename.
	 */
	private function autoload_class_file( $parts ) {
		return 'class-' . strtolower( str_replace( '_', '-', end( $parts ) ) ) . '.php';
	}

	/**
	 * Get the plugin version.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.0.0
	 *
	 * @return string The version of this plugin.
	 */
	public function version() {
		return $this->header( 'Version' );
	}

	/**
	 * Get a header.
	 *
	 * @author Aubrey Portwood
	 * @since  1.0.0
	 *
	 * @param  string $header The header you want, e.g. Version, Author, etc.
	 * @return string         The value of the header.
	 */
	public function header( $header ) {
		return isset( $this->plugin_headers[ $header ] )
			? trim( (string) $this->plugin_headers[ $header ] )
			: '';
	}

	/**
	 * Load and attach app elements to the app class.
	 *
	 * Make your classes/element small and do only one thing. If you
	 * need to pass $this to it so you can access other classes
	 * functionality.
	 *
	 * When you add something that gets attached
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.0.0
	 */
	public function attach() {
		$this->settings = new Settings();
		$this->roles    = new Roles();
		$this->login    = new Login();
	}

	/**
	 * Setup components.
	 *
	 * Add any setup method calls we need here.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  Thursday, December 6, 2018
	 */
	public function setup() {
		$this->auto_setup(); // This will automatically load class setup methods.
		// $this->attached_thing->setup(); // You can also do it this way, if you want.
	}

	/**
	 * Fire hooks!
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.0.0
	 */
	public function hooks() {
		$this->autoload_hooks(); // If you want to run your own hook methods, just strip this.
		// $this->attached_thing->hooks(); // You could do it this way if you want.
	}

	/**
	 * Automatically run any setup() methods on features/classes.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  Thursday, December 6, 2018
	 */
	private function auto_setup() {
		foreach ( get_object_vars( $this ) as $prop ) {
			if ( is_object( $prop ) ) {
				if ( method_exists( $prop, 'setup' ) ) {
					$prop->setup();
				}
			}
		}
	}

	/**
	 * Autoload hooks method.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.0.0
	 */
	private function autoload_hooks() {
		foreach ( get_object_vars( $this ) as $prop ) {
			if ( is_object( $prop ) ) {
				if ( method_exists( $prop, 'hooks' ) ) {
					$prop->hooks();
				}
			}
		}
	}

	/**
	 * This plugin's url.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.0.0
	 *
	 * @param  string $path (Optional) appended path.
	 * @return string       URL and path.
	 */
	public function url( $path = '' ) {
		return is_string( $path ) && ! empty( $path ) ?
			trailingslashit( $this->url ) . $path :
			trailingslashit( $this->url );
	}

	/**
	 * Re-attribute user content to site author.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.0.0
	 */
	public function deactivate_plugin() {
		foreach ( get_object_vars( $this ) as $prop ) {
			if ( is_object( $prop ) ) {
				if ( method_exists( $prop, 'deactivate_plugin' ) ) {
					$prop->deactivate_plugin();
				}
			}
		}
	}
}
