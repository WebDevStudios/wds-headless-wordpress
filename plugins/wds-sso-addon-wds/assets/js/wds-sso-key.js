/* globals wdsSSOKey */
if ( window.hasOwnProperty( 'wdsSSOKey' ) ) {

	/**
	 * WDS SSO Key.
	 */
	window.wdsSSOKey = ( function( $, self ) {

		/**
		 * Ready
		 *
		 * @author Aubrey Portwood <aubrey@webdevstudios.com>
		 * @since  1.0.0
		 */
		function ready() {
			const $trigger = $( '#wds-sso-key' );

			// When you click the link to add the SSO key to the DB.
			$trigger.attr( 'href', '#' ).attr( 'target', '' ).on( 'click.sign', function() {
				$.ajax( {
					method: 'post',
					url: self.adminUrl,

					// Send this data.
					data: {
						action: 'wds_send_sso_key',
						key: prompt( self.prompt ), // eslint-disable-line no-alert
						nonce: self.nonce,
					},

					/**
					 * Success
					 *
					 * @author Aubrey Portwood <aubrey@webdevstudios.com>
					 * @since  1.0.0
					 *
					 * @param  {Object} response Response Data.
					 */
					success( response ) {
						if ( response.data.error ) {
							alert( response.data.error ); // eslint-disable-line no-alert
						}

						window.location.reload();
					},
				} );
			} );
		}

		$( ready );

		return self; // Return public things.
	} ( jQuery, wdsSSOKey ) );

} // End if().
