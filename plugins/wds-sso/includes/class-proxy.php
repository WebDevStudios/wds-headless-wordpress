<?php
/**
 * Proxy Instance.
 *
 * @package WebDevStudios\SSO
 * @since   1.0.0
 */

namespace WebDevStudios\SSO;

use function WebDevStudios\SSO\Proxy\app as app;
use Google_Client;
use Google_Service_Oauth2;
use Google_Service_Exception;

/**
 * Authenticates to Google for requesting clients.
 *
 * @author Justin Foell
 * @since  1.0.0
 */
class Proxy {

	/**
	 * Client URL to allow redirecting to via wp_safe_redirect().
	 *
	 * @var string $client_url
	 *
	 * @author Justin Foell
	 * @since  1.0.0
	 */
	private $client_url = null;

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
	 * Attach to relevant WP hooks.
	 *
	 * @author Justin Foell
	 * @since  1.0.0
	 */
	private function hooks() {
		add_action( 'init', array( $this, 'handle_proxy_request' ) );
		add_filter( 'allowed_redirect_hosts', array( $this, 'allowed_redirect_hosts' ) );
	}

	/**
	 * Handle proxy requests.
	 *
	 * @author Justin Foell
	 * @since  1.0.0
	 */
	public function handle_proxy_request() {

		// @codingStandardsIgnoreLine: Using $_GET okay here.
		$args = wp_parse_args( $_GET, array(
			'code'    => '',
			'state'   => '',
			'wds-sso' => false,
		) );

		if ( false !== $args['wds-sso'] ) {

			// Redirect them to Google.
			wp_safe_redirect( $this->get_google_url() );
			exit;
		}

		if ( ( ! empty( $args['state'] ) && ! empty( $args['code'] ) )
				&& ( is_string( $args['state'] ) && is_string( $args['code'] ) ) ) {

			// We came back from google, verify the person's email.
			try {
				$state = app()->shared->decode_state( $args['state'] );
			} catch ( Exception $e ) {
				wp_die( esc_html( $e->getMessage() ) );
			}

			// Ensure we have an email set.
			if ( empty( $state['email'] ) ) {

				$email = $this->get_email_for_code( $args['code'] );
				if ( ! is_email( $email ) ) {

					// Halt execution here before a redirect.
					// Translators: A message to show the email provided was invalid.
					wp_die( sprintf( esc_html__( 'Invalid email: %$1s.', 'wds-sso' ), esc_html( $state['email'] ) ) );
				}

				// Otherwise set and return a valid email.
				$state['email'] = $email;
			}

			// Add this URL to our safe list.
			$this->client_url = $state['redirect'];

			// Re-encode state and pass on.
			$url = add_query_arg( array( 'state' => urlencode( app()->shared->encode_state( $state ) ) ), $state['redirect'] );  // @codingStandardsIgnoreLine Only one associative key here.

			// Redirect.
			wp_safe_redirect( esc_url_raw( $url ) );
			exit;
		}
	}

	/**
	 * Add allowed hosts for safe redirect.
	 *
	 * @author Justin Foell
	 * @since  1.0.0
	 *
	 * @param  array $hosts Hosts we are safe to redirect to.
	 * @return array $hosts Hosts array with additional entries.
	 */
	public function allowed_redirect_hosts( $hosts ) {

		// Allow google.
		$hosts[] = 'accounts.google.com';

		// Add the requesting client URL to the safe list.
		app()->shared->add_host( $hosts, $this->client_url );

		return $hosts;
	}

	/**
	 * Authenticate OAuth code with google, then retrieve user email.
	 *
	 * @author Justin Foell
	 * @since  1.0.0
	 *
	 * @param string $code OAuth code returned after login.
	 *
	 * @return string Email address.
	 */
	private function get_email_for_code( $code ) {

		// Verify with google.
		try {
			$client = $this->get_google_client();
			$client->fetchAccessTokenWithAuthCode( $code );
			$oauth2_service = new Google_Service_Oauth2( $client );
			$userinfo       = $oauth2_service->userinfo->get();
		} catch ( Google_Service_Exception $gse ) {

			// Google returns JSON formatted exception messages.
			$json = $gse->getMessage();
			$json = json_decode( $json );

			if ( isset( $json->error->message ) && isset( $json->error->code ) ) {

				// Halt execution here before a redirect.
				wp_die( esc_html( $json->error->message ), 'Google_Service_Exception', absint( $json->error->code ) );
			}
		}

		return ! empty( $userinfo['email'] ) ? filter_var( $userinfo['email'], FILTER_SANITIZE_EMAIL ) : '';
	}

	/**
	 * Return partially configured Google_Client with stored credentials.
	 *
	 * @author Justin Foell
	 * @since  1.0.0
	 *
	 * @return Google_Client Object pre-initialized with our configuration settings.
	 */
	private function get_google_client() {
		$client = new Google_Client();

		$client->setClientId( app()->settings->get_client_id() );
		$client->setClientSecret( app()->settings->get_client_secret() );
		$client->setRedirectUri( wp_login_url() );

		return $client;
	}

	/**
	 * URL to redirect for authorization.
	 *
	 * @author Justin Foell, Pavel Korotenko
	 * @since  1.0.0
	 *
	 * @return string URL to Google Auth.
	 */
	private function get_google_url() {
		$client = $this->get_google_client();

		$client->addScope( 'email' );
		$client->addScope( Google_Service_Oauth2::USERINFO_PROFILE );

		// Set hosted domain parameter to 0 by default.
		$hd = 0;

		// Get state parameter and decode it.
		if ( ! empty( filter_input( INPUT_GET, 'state' ) ) ) {
			$state = app()->shared->decode_state( filter_input( INPUT_GET, 'state' ) );

			// If there's hd parameter in state, add it to variable.
			if ( ! empty( $state['hd'] ) ) {
				$hd = $state['hd'];
			}
		}

		$domains = app()->settings->get_auth_domains();

		foreach ( $domains as $domain ) {
			if ( ! filter_var( $domain, FILTER_VALIDATE_DOMAIN ) ) {
				continue;
			}

			$client->setHostedDomain( $domain );
		}

		// State should always be passed in by the client.
		if ( ! empty( filter_input( INPUT_GET, 'state' ) ) ) {
			$client->setState( filter_input( INPUT_GET, 'state' ) );
		}

		return esc_url_raw( $client->createAuthUrl() );
	}
}
