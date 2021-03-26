<?php
/**
 * A partial for displaying a success or failure  message if options are successfully saved.
 *
 * @author  WebDevStudios
 * @package wds-headless-theme
 * @since   1.0
 */

if ( 'true' === filter_input( INPUT_GET, 'success' ) ) : ?>
	<div class="notice notice-success is-dismissible">
		<p><?php esc_html_e( 'Changes successfully saved!', 'wds' ); ?></p>
	</div>
<?php endif; ?>

<?php if ( 'false' === filter_input( INPUT_GET, 'success' ) ) : ?>
	<div class="notice notice-error is-dismissible">
		<p><?php esc_html_e( 'There was a problem saving your settings.', 'wds' ); ?></p>
	</div>
<?php endif; ?>
