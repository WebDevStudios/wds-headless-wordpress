<?php
/**
 * Admin Settings.
 *
 * @since 1.0.0
 * @package  WebDevStudios\SSO
 */

namespace WebDevStudios\SSO;

/**
 * Settings Class.
 *
 * @author Kailan W.
 *
 * @since 1.0.0
 */
class Settings {

	/**
	 * Fields.
	 *
	 * @author Kailan W.
	 *
	 * @since 1.0.0
	 *
	 * @var array $fields
	 */
	private $fields = array();

	/**
	 * Client ID.
	 *
	 * @author Kailan W.
	 *
	 * @since 1.0.0
	 *
	 * @var string $client_id
	 */
	private $client_id;

	/**
	 * Client Secret.
	 *
	 * @author Kailan W.
	 *
	 * @since 1.0.0
	 *
	 * @var string $client_secret
	 */
	private $client_secret;

	/**
	 * WDS SSO Key.
	 *
	 * @author Kailan W.
	 *
	 * @since 1.0.0
	 *
	 * @var string $wds_sso_key
	 */
	private $wds_sso_key;

	/**
	 * Whether this plugin is the proxy.
	 *
	 * @var boolean
	 * @author Kailan W.
	 * @since  1.0.0
	 */
	private static $is_proxy = false;

	/**
	 * Constructor.
	 *
	 * @author Kailan W., Pavel Korotenko
	 *
	 * @param string $is_proxy Whether this plugin is the proxy.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $is_proxy ) {
		if ( $is_proxy ) {
			self::$is_proxy = $is_proxy;
		}

		$this->hooks();
		$this->set_vars();
	}

	/**
	 * Set Object Vars.
	 *
	 * @author Kailan W., Justin Foell
	 *
	 * @since 1.0.0
	 */
	public function set_vars() {
		$client_id     = $this->get_option( 'wds_sso_client_id' );
		$client_secret = $this->get_option( 'wds_sso_client_secret' );
		$wds_sso_key   = $this->get_wds_sso_key();

		$this->client_id     = $client_id ? sanitize_text_field( $client_id ) : '';
		$this->client_secret = $client_secret ? sanitize_text_field( $client_secret ) : '';
		$this->wds_sso_key   = $wds_sso_key ? sanitize_text_field( $wds_sso_key ) : '';
	}

	/**
	 * Get an option.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.4.0
	 *
	 * @param  string $option  The option.
	 * @param  mixed  $default The default value.
	 * @return mixed           The value of the option.
	 */
	private function get_option( $option = '', $default = false ) {
		if ( ! is_multisite() ) {
			return get_option( $option, $default );
		}
		return get_site_option( $option, $default );
	}

	/**
	 * Get the WDS SSO Key
	 *
	 * @author Kailan W.
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 *
	 * @since  1.0.0
	 * @since  1.2.0 Added rot methods for storing in the DB.
	 *
	 * @return string The SSO Key.
	 */
	public function get_wds_sso_key() {

		// Check if constant is set.
		if ( $this->is_wds_sso_key_defined() ) {
			// Always use the constant first.
			return WDS_SSO_KEY;
		}

		// Use the DB as a fallback.
		return $this->get_option( 'wds_sso_key' );
	}

	/**
	 * Whether or not WDS_SSO_KEY is defined and not empty.
	 *
	 * @return boolean
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2.0.0
	 */
	private function is_wds_sso_key_defined() {
		return defined( 'WDS_SSO_KEY' ) && is_string( WDS_SSO_KEY ) && ! empty( WDS_SSO_KEY );
	}

	/**
	 * Whether or not we should create automatically create a WDS SSO Key.
	 *
	 * @return boolean
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2.0.0
	 */
	private function is_self_sign_eligible() {
		return $this->is_self_proxy() && empty( $this->get_wds_sso_key() );
	}

	/**
	 * (Un)rot a value.
	 *
	 * The place we likely use this is when we don't want exposed
	 * DB values to be fully exposed.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.0
	 *
	 * @param  string $value The value to (un)rot.
	 * @return string        The value, (un)rotted.
	 */
	public function rot( $value = '' ) {
		return str_rot13( $value ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_str_rot13 -- Purposeful obfuscation.
	}

	/**
	 * Initialize hooks.
	 *
	 * @author Kailan W.
	 *
	 * @since 1.0.0
	 * @return void Bail early if this has already been hooked in.
	 */
	public function hooks() {
		// Run only once (client/proxy same site).
		static $already_run = false;

		if ( $already_run ) {
			return;
		}

		$already_run = true;

		add_action( 'all_admin_notices', array( $this, 'add_admin_notice' ) );
		add_action( 'admin_init', array( $this, 'init_fields' ) );

		if ( is_multisite() ) {
			add_action( 'wpmu_options', array( $this, 'register_wpmu_fields' ) );
			add_action( 'update_wpmu_options', array( $this, 'update_wpmu_options' ) );
			add_filter( 'pre_site_option_wds_sso_key', array( $this, 'decode_db_key' ) );
			add_filter( 'pre_update_site_option_wds_sso_key', array( $this, 'encode_db_key' ) );
		} else {
			add_action( 'admin_init', array( $this, 'register_fields' ) );
			add_filter( 'pre_option_wds_sso_key', array( $this, 'decode_db_key' ) );
			add_filter( 'pre_update_option_wds_sso_key', array( $this, 'encode_db_key' ) );
		}
	}

	/**
	 * Array of Settings Fields.
	 *
	 * @author Kailan W.
	 *
	 * @since 1.0.0
	 */
	public function init_fields() {
		$this->fields = array();

		// Don't show Google Auth settings if using a proxy on another site.
		if ( $this->is_self_proxy() ) {
			$this->fields['wds_sso_client_id'] = array(
				'label'       => esc_html__( 'Client ID', 'wds-sso' ),
				'validation'  => 'esc_attr',
				'description' => esc_html__( 'Google Auth Client ID from the Google Developer Console.', 'wds-sso' ),
			);

			$this->fields['wds_sso_client_secret'] = array(
				'label'       => esc_html__( 'Client Secret', 'wds-sso' ),
				'validation'  => 'esc_attr',
				'description' => esc_html__( 'Google Auth Client Secret from the Google Developer Console.', 'wds-sso' ),
			);
		}

		/*
		 * Don't actually escape the key during validation, it will
		 * instead get rot13-ed right before it's inserted into
		 * the DB via encode_db_key().
		 */
		$this->fields['wds_sso_key'] = array(
			'label'       => esc_html__( 'Secure Key', 'wds-sso' ),
			'validation'  => function( $key ) { return $key; }, // phpcs:ignore Generic.Functions.OpeningFunctionBraceKernighanRitchie.ContentAfterBrace, WordPress.Arrays.ArrayDeclarationSpacing.ArrayItemNoNewLine -- One line!
			'callback'    => array( $this, 'field_html_wds_sso_key' ),
			'description' => esc_html__( "This key is used to secure your data between servers, change if you feel it's been compromised.", 'wds-sso' ),
		);
	}

	/**
	 * Check if proxy URL is the same as the current site.
	 *
	 * @return True if this site's hostname and proxy URL's hostname match, false otherise.
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2.0.0
	 */
	private function is_proxy_this_site() {
		return wp_parse_url( wp_login_url(), PHP_URL_HOST ) === wp_parse_url( app()->auth->get_proxy_url(), PHP_URL_HOST );
	}

	/**
	 * Whether or not this site is proxy-ing to itself.
	 *
	 * @return boolean True if this site is also the proxy, false otherwise.
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2.0.0
	 */
	private function is_self_proxy() {
		return self::$is_proxy && $this->is_proxy_this_site();
	}

	/**
	 * Parses arguments array with default arguments set, to make sure they're always set.
	 *
	 * @param array $args {
	 *
	 * Arguments array.
	 *
	 * @type string  $label       Field label.
	 * @type string  $placeholder Field placeholder.
	 * @type string  $description Field Description.
	 * @type string  $label_for   Parent section name for the field.
	 * @type mixed   $value       Field value.
	 * }
	 *
	 * @author Pavel Korotenko
	 * @return array Parsed arguments array.
	 * @since 1.0.0
	 */
	private function get_default_args( $args ) {
		return wp_parse_args( $args, array(
			'label'       => '',
			'placeholder' => '',
			'description' => '',
			'validation'  => 'esc_attr',
			'value'       => '',
		) );
	}

	/**
	 * Register the section.
	 *
	 * @author Aubrey Portwood
	 * @since  1.2.0
	 */
	public function add_settings_section() {

		// Make sure we only do this once as many things might call this.
		static $added = false;

		if ( ! $added ) {

			// Add the setting.
			$added = true;
			add_settings_section( 'wds_sso_field_section', esc_html__( 'WDS SSO Settings', 'wds-sso' ), array( $this, 'section_callback' ), 'general' );
		}
	}

	/**
	 * Register the settings fields.
	 *
	 * @author Kailan W., Pavel Korotenko, Aubrey Portwood
	 *
	 * @since 1.0.0
	 */
	public function register_fields() {

		// Add API settings section.
		$this->add_settings_section();

		// Check if the fields are available.
		if ( ! empty( $this->fields ) && is_array( $this->fields ) ) {
			foreach ( $this->fields as $field_id => $field ) {

				// Add label_for with field ID.
				$field['label_for'] = $field_id;

				// Set field value.
				$field['value'] = get_option( $field_id );

				// Parse incoming $field into an array and merge it with $defaults.
				$field_args = $this->get_default_args( $field );

				register_setting(
					'general',
					$field_id,
					$field['validation']
				);

				add_settings_field(
					$field_id,
					'<label for="' . esc_attr( $field_id ) . '">' . esc_html( $field['label'] ) . '</label>',
					isset( $field['callback'] ) && is_callable( $field['callback'] ) ? $field['callback'] : array( $this, 'field_html' ),
					'general',
					'wds_sso_field_section',
					$field_args
				);
			}
		}
	}

	/**
	 * API Settings Section callback.
	 *
	 * The function add_settings_section will generate its own title so the callback is only used in Multisite environments.
	 *
	 * @param array $args {
	 *
	 *     Overall description of args.
	 *
	 *     @type string  $id    ID description.
	 *     @type string  $label Label description.
	 * }
	 * @author Kailan W.
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 *
	 * @since 1.0.0
	 * @since 1.2.2 Added a seekable element for the URL slug in class-roles.php.
	 */
	public function section_callback( $args = array() ) {
		$defaults = array(
			'id'    => '',
			'title' => '',
		);

		// A seekable element.
		echo "<span id='wds-sso-setting'></span>";

		// Parse incoming $field into an array and merge it with $defaults.
		$args = wp_parse_args( $args, $defaults );

		/**
		 * Should we print the extra title?
		 *
		 * @since  1.2.0
		 * @author Aubrey Portwood
		 *
		 * @param boolean $print_title Show the extra title?
		 */
		$print_title = apply_filters( 'wds_sso_section_callback_print_title', true );

		// If not empty, print the title.
		if ( ( is_multisite() && is_network_admin() ) || ( ! empty( $args['title'] ) && $print_title ) ) {
			printf( '<h2 id="' . esc_attr( $args['id'] ) . '">%s</h2>', esc_html( $args['title'] ) );
		}
	}

	/**
	 * Field HTML display.
	 *
	 * @param array $args Field arguments.
	 *
	 * @author Kailan W.
	 *
	 * @since 1.0.0
	 */
	public function field_html( $args = array() ) {
		$placeholder = '';

		// Check if placeholder is set and prepare attribute.
		if ( ! empty( $args['placeholder'] ) ) {
			$placeholder = 'placeholder="' . esc_attr( $args['placeholder'] ) . '"';
		}

		// Output input field.
		echo '<input type="password" class="regular-text" autocomplete="off" id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['label_for'] ) . '" value="' . esc_attr( $args['value'] ) . '" ' . $placeholder . ' />'; // @codingStandardsIgnoreLine: Escaping done elsewhere.

		// Output description if it exists.
		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
		}
	}

	/**
	 * WDS SSO Key Field specific HTML display.
	 *
	 * @param array $args Field arguments.
	 *
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 *
	 * @since 1.0.0
	 */
	public function field_html_wds_sso_key( $args = array() ) {
		$description = isset( $args['description'] ) ? $args['description'] : '';

		// Output input field.
		if ( $this->is_wds_sso_key_defined() ) {
			// If the key is defined then show obfuscated key in disabled input field.
			echo '<input type="password" disabled class="regular-text" autocomplete="off" id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['label_for'] ) . '" value="' . esc_attr( $this->get_wds_sso_key() ) . '" />'; // @codingStandardsIgnoreLine: Escaping done elsewhere.
			$description = sprintf(
				// Translators: WDS_SSO_KEY currently defined in wp-config.php.
				__( '%1$s currently defined in %2$s', 'wds-sso' ),
				'<code>WDS_SSO_KEY</code>',
				'<code>wp-config.php</code>'
			);
		} else {
			if ( $this->is_self_sign_eligible() ) {
				$args['value'] = wp_salt();
				// Translators: Key automatically generated, click "Save Changes" to save.
				$description = '<span>' . $args['description'] . ' ' . __( '<strong><br>We automatically generated a key for you, hit save to use the generated one, or replace to use your own.</strong>', 'wds-sso' ) . '</strong>';
			}
			echo '<input type="password" autocomplete="off" class="regular-text" id="' . esc_attr( $args['label_for'] ) . '" name="' . esc_attr( $args['label_for'] ) . '" value="' . esc_attr( $args['value'] ) . '" />'; // @codingStandardsIgnoreLine: Escaping done elsewhere.
		}

		// Output description if it exists.
		if ( ! empty( $description ) ) {
			printf( '<p class="description">%s</p>', wp_kses_post( $description ) );
		}
	}

	/**
	 * Output the Section and Fields on WPMU Settings.
	 *
	 * @author Kailan W., Pavel Korotenko, Aubrey Portwood
	 *
	 * @since 1.0.0
	 * @since 2.0.0 This adds the secure key to network, which we need.
	 */
	public function register_wpmu_fields() {

		// Check if the fields are available.
		if ( ! empty( $this->fields ) && is_array( $this->fields ) ) :

			$section_args = array(
				'id'    => 'wds_sso_field_section',
				'title' => esc_html__( 'WDS SSO Settings', 'wds-sso' ),
			); ?>

			<?php $this->section_callback( $section_args ); ?>

			<table id="wds_sso" class="form-table">
				<tbody>
					<?php
					foreach ( $this->fields as $field_id => $field ) :

						// Add label_for with field ID.
						$field['label_for'] = $field_id;

						// Set field value.
						$field['value'] = get_site_option( $field_id );

						// Parse incoming $field into an array and merge it with $defaults.
						$field = $this->get_default_args( $field );
						?>

						<tr>
							<th scope="row"><?php echo '<label for="' . esc_attr( $field_id ) . '">' . esc_html( $field['label'] ) . '</label>'; ?></th>
							<td>
								<?php
								$callback = isset( $field['callback'] ) && is_callable( $field['callback'] ) ? $field['callback'] : array( $this, 'field_html' );
								call_user_func( $callback, $field );
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php
		endif;
	}

	/**
	 * Update Network Option.
	 *
	 * @author Kailan W.
	 *
	 * @since 1.0.0
	 */
	public function update_wpmu_options() {

		// Validate the nonce/security.
		check_admin_referer( 'siteoptions' );

		// If the fields exists then loop through them.
		if ( ! empty( $this->fields ) && is_array( $this->fields ) ) {

			foreach ( $this->fields as $field_id => $field ) {
				if ( isset( $_POST[ $field_id ] ) ) {

					// Check if the POST key is set.
					if ( ! empty( $_POST[ $field_id ] ) ) {

						// Sanitize the value.
						if ( is_string( $_POST[ $field_id ] ) ) {
							$value = is_callable( $field['validation'] ) ? call_user_func( $field['validation'], sanitize_text_field( $_POST[ $field_id ] ) ) : null;
						} else {
							// If array, serialize each element of it.
							foreach ( $_POST[ $field_id ] as $field_key => $field_value ) {
								$_POST[ $field_id ][ $field_key ] = maybe_serialize( stripslashes( $field_value ) );
							}
							$value = $_POST[ $field_id ];
						}

						// If the value exist and is valid then update it.
						if ( ! empty( $value ) ) {
							update_site_option( $field_id, $value );
						} else {
							delete_site_option( $field_id );
						}
					} else {
						delete_site_option( $field_id );
					}
				}
			}
		}
	}

	/**
	 * Grabs the client ID property of this class.
	 *
	 * @author JayWood, Justin Foell
	 * @since  1.0.0
	 *
	 * @return string The client ID.
	 */
	public function get_client_id() {
		return $this->client_id;
	}

	/**
	 * Grabs the client ID property of this class.
	 *
	 * @author JayWood, Justin Foell
	 * @since  1.0.0
	 *
	 * @return string The client secret.
	 */
	public function get_client_secret() {
		return $this->client_secret;
	}

	/**
	 * Get domains that are allowed to authenticate.
	 *
	 * @author Pavel Korotenko, Justin Foell
	 * @since  2.0.0
	 *
	 * @return array Array of allowed domains.
	 */
	public function get_auth_domains() {
		return apply_filters( 'wds_sso_auth_domains', array() );
	}


	/**
	 * Check is API settings are set.
	 *
	 * @author Kailan W., Justin Foell, Aubrey Portwood
	 *
	 * @since 1.0.0
	 *
	 * @return boolean True if the settings set.
	 */
	public function is_settings_set() {
		if ( $this->is_self_proxy() && ( empty( $this->client_id ) || empty( $this->client_secret ) ) ) {
			return false;
		}

		if ( $this->is_self_proxy() && empty( $this->get_auth_domains() ) ) {
			return false;
		}

		if ( empty( $this->wds_sso_key ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Add Admin notice.
	 *
	 * @author Kailan W, Justin Foell
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.0.0
	 * @since  1.2.0 Admin messaged modified.
	 */
	public function add_admin_notice() {
		// Check that auth domain is set.
		if ( empty( $this->get_auth_domains() ) ) {
			// Translators: Message that authentication domains aren't set.
			$message = sprintf( __( "Valid WDS SSO authentication domains aren't set, this is usually done by activating a WDS SSO add-on plugin, or using the %s filter.", 'wds-sso' ), '<code>wds_sso_auth_domains</code>' );
			echo '<div class="notice notice-error"><p>' . wp_kses_post( $message ) . '</p></div>';
		}

		$display_notice = apply_filters( 'wds_sso_show_admin_notices', true, $this );

		// Check if the API client ID and secret have been set.
		if ( $display_notice && empty( $this->wds_sso_key ) ) {

			$key_html = '<code>WDS_SSO_KEY</code>';

			// translators: You've activated the WDS SSO plugin, but do not have the required settings setup, please set that up <a href="%s">now.</a>.
			$message = sprintf( __( "You've activated the WDS SSO plugin and configured a key, now you need to setup Google credentials <a href='%1\$s'>here</a>.", 'wds-sso' ), app()->settings->get_admin_settings_url() );

			// Show the message.
			echo '<div class="notice notice-error"><p>' . wp_kses_post( $message ) . '</p></div>';
		}
	}

	/**
	 * Run decoding on wds_sso_key before it's returned by get_(site_)option.
	 *
	 * @param mixed $value The value to filter.
	 * @return string Decoded wds_sso_key
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2.0.0
	 */
	public function decode_db_key( $value ) {

		// Avoid recursion - get_option is called within this function.
		static $is_running = false;

		if ( $is_running ) {
			return $value;
		}
		$is_running = true;

		if ( is_multisite() ) {
			$value = get_site_option( 'wds_sso_key' );
		} else {
			$value = get_option( 'wds_sso_key' );
		}

		$is_running = false;

		// Don't rot something that's not a string.
		if ( ! is_string( $value ) ) {
			return $value;
		}
		return $this->rot( $value );
	}

	/**
	 * Encode wds_sso_key before it's saved to the DB by update_(site_)option.
	 *
	 * @param string $value The unencoded value.
	 * @return string Encoded wds_sso_key
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2.0.0
	 */
	public function encode_db_key( $value ) {
		return $this->rot( $value );
	}

	/**
	 * Get the SSO Settings URL.
	 *
	 * @return string URL for settings.
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2.0.0
	 */
	public function get_admin_settings_url() {
		$id = '#wds-sso-setting';
		if ( is_multisite() ) {
			return network_admin_url( "settings.php{$id}", app()->shared->get_scheme() );
		}
		return admin_url( "options-general.php{$id}", app()->shared->get_scheme() );
	}
}

