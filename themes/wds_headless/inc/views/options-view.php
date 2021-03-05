<?php
/**
 * The options page for the headless theme.
 *
 * @author  WebDevStudios
 * @package wds-headless-theme
 * @since   1.0
 */
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Theme Settings', 'wds' ); ?></h1>

	<div class="wrap">
		<label for="wds-custom-not-found-page">
			<strong><?php esc_html_e( '404 Page', 'wds' ); ?></strong>
			<select name="wds-custom-not-found-page">
				<option value="default"><?php esc_html_e( 'Default...', 'wds' ); ?></option>
				<?php foreach ( get_pages() as $current_page ) : // phpcs:ignore ?>
					<option value="<?php esc_attr( $current_page->ID ); ?>">
						<?php echo $current_page->post_title; //phpcs:ignore ?>
					</option>
				<?php endforeach; ?>
			</select>
		</label>
	</div>


	<form action="" method="post">
		<?php submit_button( __( 'Save', 'wds' ) ); ?>
		<?php wp_nonce_field( 'wds-headless-options-save', 'wds-headless-options-save-nonce' ); ?>
	</form>
</div>
