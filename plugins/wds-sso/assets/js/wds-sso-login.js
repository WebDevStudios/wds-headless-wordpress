/**
 * SSO Login JS.
 *
 * @since  1.0.0
 * @package  WebDevStudios\SSO
 */

/**
 * Add google login button above normal username and password inputs.
 *
 * @since  1.0.0
 * @author Justin Foell
 */
( function( $ ) {
	'use strict';

	let $loginForm, $googleLink, $httpsMessage;

	$( function() {
		$loginForm = $( '#loginform,#front-login-form' );
		$googleLink = $( 'p.wds-sso-login' );
		$httpsMessage = $( 'div.message.wds-sso-https-message' );

		if ( 1 === $loginForm.length && 1 === $googleLink.length ) {
			$loginForm.prepend( $googleLink );

			if ( 1 === $httpsMessage.length ) {
				$httpsMessage.insertBefore( $googleLink );
			}
		}
	} );
}( jQuery ) );
