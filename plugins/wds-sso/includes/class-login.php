<?php
/**
 * Login Button Instance.
 *
 * @since 1.0.0
 * @package  WebDevStudios\SSO
 */

namespace WebDevStudios\SSO;

/**
 * Login class.
 *
 * This class is responsible for displaying a
 * Google login button that directs to user
 * to Google for authentication.
 *
 * @author Justin Foell
 * @since  1.0.0
 */
class Login {

	/**
	 * Construct.
	 *
	 * @author Justin Foell
	 * @since  1.0.0
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Add hooks.
	 *
	 * @author Justin Foell
	 * @since  1.0.0
	 */
	public function hooks() {

		// For the wp-login.php page.
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_scripts_and_styles' ) );
		add_action( 'login_form', array( $this, 'login_form' ) );

		// For the theme.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts_and_styles' ) );
		add_filter( 'login_form_top', array( $this, 'login_form' ) );

		add_filter( 'wds_sso_login_button', array( $this, 'wds_sso_login_button' ), 10, 3 );
	}

	/**
	 * Enqueue scripts & styles for button display.
	 *
	 * @return void
	 * @author Justin Foell
	 * @since  1.0.0
	 */
	public function enqueue_scripts_and_styles() {
		if ( ! app()->shared->is_login() ) {
			return;
		}

		wp_enqueue_script( 'wds-sso-login', app()->url( 'assets/js/wds-sso-login.js' ), array( 'jquery' ), time(), false );
		wp_enqueue_style( 'wds-sso-login', app()->url( 'assets/css/wds-sso-login.css' ), array( 'login' ), time(), false );
	}

	/**
	 * When we force HTTPS on a user, show a small notification about it.
	 *
	 * This is meant to be used in a context where the it can be replaced in a
	 * `sprintf` call.
	 *
	 * @access private
	 *
	 * @author Tom McFarlin <tom.mcfarlin@webdevstudios.com>
	 * @since  2019-04-30
	 *
	 * @return string
	 */
	private function get_https_notification() {
		if ( 'yes' === filter_input( INPUT_GET, 'sso_force_https' ) && is_ssl() ) {
			return sprintf(
				'<div class="message wds-sso-https-message">%s</div>',
				esc_html( 'Forced HTTPS, try again.', 'wds-sso' )
			);
		}

		return '';
	}

	/**
	 * Add button to the login form.
	 *
	 * @author Justin Foell
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 *
	 *  @since  1.0.0
	 * @since  1.2.0 If no SSO key, don't show login.
	 *
	 * @param string $form_html Form HTML.
	 * @return string Form HTML.
	 */
	public function login_form( $form_html = '' ) {
		if ( ! $this->sso_configured() ) {
			return $form_html;
		}

		/**
		 * Filter the Login Button Message
		 *
		 * @since  2.0.0
		 * @author Aubrey Portwood <aubrey@webdevstudios.com>
		 *
		 * @param string $button_message The message shown on the login form.
		 */
		$button_message = apply_filters( 'wds_sso_login_button_message', esc_html__( 'Sign in with Google', 'wds-sso' ) );

		/**
		 * Filter the new form
		 *
		 * @author Aubrey Portwood <aubrey@webdevstudios.com>
		 * @since  2.0.0
		 *
		 * @param string $form_html Form HTML with SSO login.
		 */
		$form_html .= apply_filters( 'wds_sso_login_button', 'wds-sso-login', 'button button-large button-primary sso-login2 hide-if-no-js', $button_message );

		if ( doing_action( 'login_form' ) ) {
			echo $form_html; // @codingStandardsIgnoreLine Output already escaped.
		}

		return $form_html;
	}

	/**
	 * Determine if SSO is configured.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  2.0.0
	 *
	 * @return bool
	 */
	private function sso_configured() {
		if ( ! app()->settings->is_settings_set() ) {
			return false;
		}

		return true;
	}

	/**
	 * Filter the login button using the custom container class names, custom button class names,
	 * and custom button text.
	 *
	 * Note:
	 * - This does *not* filter the URL for the login nor does it filter the notices.
	 * - The overall implementation can be cleaned up to be far more OOP in 2.1.
	 *
	 * @since 2019-04-08
	 * @author Tom McFarlin <tom.mcfarlin@webdevstudios.com>
	 *
	 * @param string $container_class_name the class names for the anchor's container.
	 * @param string $button_class_names the class names for the anchor.
	 * @param string $button_text the text for the anchor.
	 * @return string a sanitized, escaped version of the button to render on the front-end.
	 */
	public function wds_sso_login_button( $container_class_name, $button_class_names, $button_text ) {

		// If button text has been provided, use it.
		if ( ! empty( $button_text ) ) {
			$button = sprintf(
				'<a class="button button-large button-primary wds-sso-login hide-if-no-js" href="%s">%s</a>',
				esc_url( app()->auth->get_login_url() ),
				$button_text
			);
		}

		// If a button class has been provided but no text, then use the default text and custom name.
		if ( ! empty( $button_class_names ) && empty ( $button_text ) ) {
			$button = sprintf(
				'<a class="%s" href="%s">%s</a>',
				$button_class_names,
				esc_url( app()->auth->get_login_url() ),
				esc_html__( 'WebDevStudios Login', 'wds-sso' )
			);
		}

		// Use custom button class names and button text.
		if ( ! empty( $button_class_names ) && ! empty ( $button_text ) ) {
			$button = sprintf(
				'<a class="%s" href="%s">%s</a>',
				$button_class_names,
				esc_url( app()->auth->get_login_url() ),
				$button_text
			);
		}

		// Filter the button container's class name, if it's provided.
		if ( ! empty( $container_class_name ) ) {
			$button_container = sprintf(
				'%1$s<p class="' . $container_class_name . '">%2$s</p>',
				$this->get_https_notification(),
				$button
			);
		}

		// Sanitize the output.
		return wp_kses(
			$button_container,
			[
				'div' => [
					'class' => [],
				],
				'p'   => [
					'class' => [],
				],
				'a'   => [
					'class' => [],
					'href'  => [],
				],
			]
		);
	}
}
