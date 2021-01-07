/* globals wdsSSODeactivation */
if ( window.hasOwnProperty( 'wdsSSODeactivation' ) ) {

	/**
	 * Deactivation.
	 *
	 * @author Aubrey Portwood <aubrey@webdevstudios.com>
	 * @since  1.2.1
	 */
	window.wdsSSODeactivation = ( function( $, self ) {
		let $link;

		/**
		 * Ready.
		 *
		 * @author Aubrey Portwood <aubrey@webdevstudios.com>
		 * @since  1.2.1
		 */
		function ready() {
			$link = $( 'tr[data-plugin="wds-sso/wds-sso.php"] .deactivate a' );
			promptOnDeactivate();
		}

		/**
		 * Prompt to remove users on deactivate.
		 *
		 * @author Aubrey Portwood <aubrey@webdevstudios.com>
		 * @since  1.2.1
		 */
		function promptOnDeactivate() {
			$link

				// In fact, move it to a data attribute.
				.data( 'href', $link.attr( 'href' ) ).attr( 'href', '#' )

				// Now, when they click it, figure out if they want to remove the users.
				.on( 'click', function() {
					if ( ! confirm( self.confirm ) ) { // eslint-disable-line no-alert

						// Cancel means they want to remove users.
						$link.data( 'href', $link.data( 'href' ) + '&clean_up_sso_users=1&_sso_nonce=' + self.nonce );
					}

					// Go to the link (maybe modified above).
					window.location.href = $link.data( 'href' );
				} );
		}

		$( ready );

		return self; // Return public things.
	} ( jQuery, wdsSSODeactivation ) );
} // End if().
