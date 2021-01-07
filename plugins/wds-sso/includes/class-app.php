<?php
/**
 * Main Application Instance.
 *
 * @since 1.0.0
 * @package  WebDevStudios\SSO
 */

namespace WebDevStudios\SSO;

use Exception;

/**
 * Application Loader.
 *
 * Everything starts here. If you create a new class,
 * attach it to this class.
 *
 * @author Aubrey Portwood
 * @since  1.0.0
 */
class App {

	/**
	 * Instance of the user class.
	 *
	 * @author Jay Wood
	 * @since 1.0.0
	 *
	 * @var User
	 */
	public $user;

	/**
	 * Instance of the settings class.
	 *
	 * @author Jay Wood
	 * @since 1.0.0
	 *
	 * @var Settings
	 */
	public $settings;

	/**
	 * Instance of the shared class.
	 *
	 * @author Jay Wood
	 * @since 1.0.0
	 *
	 * @var Shared
	 */
	public $shared;

	/**
	 * Instance of the login class.
	 *
	 * @author Jay Wood
	 * @since 1.0.0
	 *
	 * @var Login
	 */
	protected $login;

	/**
	 * Instance of the Auth class
	 *
	 * @author Jay Wood
	 * @since 1.0.0
	 *
	 * @var Auth
	 */
	public $auth;

	/**
	 * Instance of the Proxy class
	 *
	 * @author Justin Foell
	 * @since 1.0.0
	 *
	 * @var Proxy
	 */
	public $proxy;

	/**
	 * Instance of the CLI class
	 *
	 * @author Justin Foell
	 * @since 1.1.0
	 *
	 * @var CLI
	 */
	public $cli;

	/**
	 * Plugin basename.
	 *
	 * @author Justin Foell
	 * @var    string
	 * @since  1.0.0
	 */
	protected $basename = '';

	/**
	 * URL of plugin directory.
	 *
	 * @author Justin Foell
	 * @var    string
	 * @since  1.0.0
	 */
	protected $url = '';

	/**
	 * Path of plugin directory.
	 *
	 * @author Justin Foell
	 * @var    string
	 * @since  1.0.0
	 */
	protected $path = '';

	/**
	 * Whether this plugin is the proxy.
	 *
	 * @var boolean
	 * @author Justin Foell
	 * @since  1.0.0
	 */
	protected $is_proxy = false;

	/**
	 * Construct.
	 *
	 * @author Aubrey Portwood, Justin Foell
	 * @since  1.0.0
	 *
	 * @param string  $plugin_file Full path to main plugin file.
	 * @param boolean $is_proxy If this plugin should be proxying requests.
	 *
	 * @throws Exception If $plugin_file parameter is invalid (prevents plugin from loading).
	 */
	public function __construct( $plugin_file, $is_proxy = false ) {

		// Check input validity.
		if ( empty( $plugin_file ) || ! stream_resolve_include_path( $plugin_file ) ) {

			// Translators: Displays a message if a plugin file is not passed.
			throw new Exception( sprintf( esc_html__( 'Invalid plugin file %1$s supplied to %2$s', 'wds-sso' ), $plugin_file, __METHOD__ ) );
		}

		// Assignments.
		$this->is_proxy    = $is_proxy;
		$this->basename    = plugin_basename( $plugin_file );
		$this->url         = plugin_dir_url( $plugin_file );
		$this->path        = plugin_dir_path( $plugin_file );
		$this->plugin_file = $plugin_file;

		// Loaders.
		$this->auto_loader();
		$this->vendor();
		$this->attach();

		if ( ! $this->is_proxy() ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'deactivate_scripts' ) );
			add_action( 'all_admin_notices', array( $this, 'no_ssl_notice' ) );
		}
	}

	/**
	 * Show any deactivation notice.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.2
	 * @since  1.2.4 This notice shows, but deactivation no longer happens.
	 *
	 * @return void Early bail if there are no notices.
	 */
	public function no_ssl_notice() {
		if ( app()->shared->http_enabled() ) {
			return; // Filter says no, we're good.
		}

		if ( ! function_exists( 'is_ssl' ) ) {
			require ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( is_ssl() ) {

			// We have SSL, we're good!
			return;
		}

		?>
		<div class="notice notice-error">
			<p>
				<?php esc_html_e( 'WDS SSO requires HTTPS, you may experience errors and issues logging in until your site is configured to use HTTPS.', 'wds-sso' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Deactivation scripts.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.1
	 */
	public function deactivate_scripts() {
		if ( apply_filters( 'wds_sso_allow_clean_up_users', true ) ) {
			$replacement_author = $this->get_replacement_author_info();

			wp_enqueue_script( 'wds-sso-deactivate-script', plugins_url( 'assets/js/wds-sso-deactivate.js', $this->plugin_file ), array( 'jquery' ), time(), true );
			wp_localize_script( 'wds-sso-deactivate-script', 'wdsSSODeactivation', array(
				'confirm' => sprintf(
					// Translators: Warning shown when deactivating plugin.
					__( 'PLEASE READ!!! Keep SSO Users? Click Ok to keep SSO users, CHOOSE CANCEL TO REMOVE ALL SSO USERS and associate their content with a new %s user.', 'wds-sso' ),
					$replacement_author['user_login']
				),
				'nonce'   => wp_create_nonce( 'clean_up_sso_users' ),
			) );
		}
	}

	/**
	 * Register the autoloader.
	 *
	 * @since  1.0.0
	 * @author  Justin Foell
	 */
	private function auto_loader() {

		// Register our autoloader.
		spl_autoload_register( array( $this, 'autoload' ) );
	}

	/**
	 * Load vendor libraries.
	 *
	 * @author Aubrey Portwood
	 * @since  1.0.0
	 */
	private function vendor() {
		$autoload = $this->path . 'vendor/autoload.php';
		if ( is_readable( $autoload ) ) {
			require_once $autoload;
		}
	}

	/**
	 * Require classes.
	 *
	 * @author Justin Foell, Jay Wood
	 * @since  1.0.0
	 *
	 * @param string $class_name Fully qualified name of class to try and load.
	 *
	 * @return  void Early exit if we can't load the class.
	 */
	public function autoload( $class_name ) {

		// If our class doesn't have our namespace, don't load it.
		if ( 0 !== strpos( $class_name, 'WebDevStudios\\SSO\\' ) ) {
			return;
		}

		$parts = explode( '\\', $class_name );

		// Include our file.
		$includes_dir = trailingslashit( $this->path ) . 'includes/';
		$file         = 'class-' . strtolower( end( $parts ) ) . '.php';

		if ( stream_resolve_include_path( $includes_dir . $file ) ) {
			require_once $includes_dir . $file;
		}
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
	 * @author Aubrey Portwood, Pavel Korotenko, Jay Wood
	 * @since  1.0.0
	 */
	private function attach() {

		// Roles.
		$this->roles = new Roles( $this->is_proxy() );

		// Both.
		$this->shared   = new Shared();
		$this->settings = new Settings( $this->is_proxy() );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$this->cli = new CLI();
		}

		if ( $this->is_proxy() ) {

			// Proxy.
			$this->proxy = new Proxy();
		} else {

			// Client.
			$this->user   = new User();
			$this->login  = new Login();
			$this->auth   = new Auth();
			$this->compat = new Compat();
		}
	}

	/**
	 * Whether or not this server is the proxy.
	 *
	 * @author Justin Foell
	 * @since  1.0.0
	 *
	 * @return boolean True if proxy, false otherwise.
	 */
	public function is_proxy() {
		return (bool) $this->is_proxy;
	}

	/**
	 * This plugin's url.
	 *
	 * @author Justin Foell
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
	 * @author Kailan W.
	 * @author Justin Foell
	 *
	 * @since 1.0.0
	 */
	public function deactivate_plugin() {

		// Clean up users.
		$this->maybe_clean_up_users();

		// Clear out the keys.
		delete_option( 'wds_sso_key' );
		if ( is_multisite() ) {
			delete_site_option( 'wds_sso_key' );
		}
	}

	/**
	 * Gets the replacement author info - the user which content will be
	 * attributed to upon SSO deactivation.
	 *
	 * @return array User arguments suitable for wp_insert_user().
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2.0.0
	 */
	public function get_replacement_author_info() {

		return apply_filters(
			'wds_sso_replacement_author_info',
			array(
				'user_email' => 'author@example.com',
				'user_login' => 'author',
				'user_pass'  => wp_generate_password( 100 ),
				'role'       => 'author',
			)
		);
	}

	/**
	 * Clean up users.
	 *
	 * @author Justin Foell
	 * @since  1.0.0
	 *
	 * The reasoning behind this is when we handoff a project to the client,
	 * we may want to clean up all WDS users out of the system.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.1
	 *
	 * @return void Early bail if we're not supposed to clean up users.
	 */
	public function maybe_clean_up_users() {
		if ( ! apply_filters( 'wds_sso_allow_clean_up_users', true ) ) {

			// Allow programmatic turning-off of this feature.
			return;
		}

		if ( ! isset( $_REQUEST['clean_up_sso_users'] ) ) {

			// The command was not issued.
			return;
		}

		if ( ! app()->user->is_admin() ) {

			// Only let super-admin and admins do this.
			return;
		}

		// Check our nonce to ensure we're doing the right action.
		check_admin_referer( 'clean_up_sso_users', '_sso_nonce' );

		global $wpdb;

		/*
		 * Use a direct queries because WP_User_Query also looks at
		 * wp_capabilities - which may have been removed from a user,
		 * but the user still lingers.
		 */

		// Finds of all _wds_sso users.
		$user_ids = $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key=%s", '_wds_sso' ) );

		// Check if a main user exists.
		$new_user_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key=%s", '_wds_sso_main' ) );
		if ( ! $new_user_id ) {
			$new_user_id = apply_filters( 'wds_sso_main_user', false );

			if ( false === $new_user_id ) {

				// Create a user account and attempt user account creation.
				$new_user_id = wp_insert_user( $this->get_replacement_author_info() );
			}

			// Add the _wds_sso_main key.
			update_user_meta( $new_user_id, '_wds_sso_main', true );
		}

		// The blog ids.
		$blog_ids = array();

		// If SSO user IDs are found and a replacement ID exists, loop through removing SSO users and attributing content.
		if ( ! empty( $user_ids ) && $new_user_id ) {
			if ( is_multisite() ) {

				// Require for wpmu_delete_user().
				require_once ABSPATH . 'wp-admin/includes/ms.php';

				// Get the blog ids.
				$blog_ids = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->blogs}" );
			}

			// Avoid memory overruns due to cache invalidation.
			wp_suspend_cache_invalidation( true );

			foreach ( $user_ids as $user_id ) {
				if ( is_multisite() ) {
					if ( ! empty( $blog_ids ) ) {
						foreach ( $blog_ids as $blog_id ) {
							switch_to_blog( $blog_id );

							// Allow someone to override the per-blog replacement user.
							$new_user_id = apply_filters( 'wds_sso_blog_main_user', $new_user_id, $blog_id );

							// Transfer content to main user ID for current blog and remove user from blog.
							remove_user_from_blog( $user_id, $blog_id, $new_user_id );

							restore_current_blog();
						}
					}

					// Revoke Super Admin privilege. wpmu_delete_user() won't actually delete Super Admins.
					revoke_super_admin( $user_id );

					// Remove the user completely after all content has be re-attributed from all sites.
					$wpdb->delete( $wpdb->users, array( 'ID' => $user_id ) );

				} else {

					// Delete and transfer content to main user ID for the blog.
					wp_delete_user( $user_id, $new_user_id );

				} // End if().
			} // End foreach().

			// Turn caching back on now that we're done.
			wp_suspend_cache_invalidation( false );
		}
	}
}
