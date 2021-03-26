<?php
/**
 * The options page for the headless theme.
 *
 * @author  WebDevStudios
 * @package wds-headless-theme
 * @since   1.0
 */

require_once 'partials/admin-notice.php';
?>

<div class="wrap">
	<h1><?php esc_html_e( get_admin_page_title() ); // phpcs:ignore ?></h1>
	<?php if ( _wds_headless_has_defined_all_constants() ) : ?>
		<p>
			<?php esc_html_e( 'You have defined at least one constant in your wp-config.php file. If you wish to use the rest of the fields in this file, delete the constants from your wp-config.php file.', 'wds' ); ?>
		</p>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_html( admin_url( 'admin-post.php' ) ); ?>">
		<p>
			<label for="wds-headless-404-page">
				<?php esc_html_e( '404 Page', 'wds' ); ?>
				<select name="wds-headless-404-page" id="wds-headless-404-page">
					<option value="default"><?php esc_html_e( 'Default...', 'wds' ); ?></option>
					<?php foreach ( get_pages() as $current_page ) : ?>
						<option value="<?php esc_attr_e( $current_page->ID ); ?>"
						<?php selected( $current_page->ID, get_option( 'wds-headless-404-page' ), true ); // phpcs:ignore ?>
						>
							<?php esc_html_e( $current_page->post_title ); //phpcs:ignore ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>
		</p>

		<p>
			<label for="wds-headless-frontend-url">
				<?php esc_html_e( 'Frontend URL', 'wds' ); ?>
				<input
					name="wds-headless-frontend-url" id="wds-headless-frontend-url"
					type="text" value="<?php esc_attr_e( get_option( 'wds-headless-frontend-url' ) ); // phpcs:ignore ?>"
					<?php wds_headless_is_disabled_field( 'wds-headless-front-url' ); ?>
				/>
			</label>
		</p>

		<p>
			<label for="wds-headless-preview-secret">
				<?php esc_html_e( 'Preview Secret', 'wds' ); ?>
				<input
					name="wds-headless-preview-secret" id="wds-headless-preview-secret"
					type="password" value="<?php esc_attr_e( get_option( 'wds-headless-preview-secret' ) ); // phpcs:ignore ?>"
					<?php wds_headless_is_disabled_field( 'wds-headless-preview-secret' ); ?>
				/>
			</label>
		</p>

		<p>
			<label for="wds-headless-jwt-auth-key">
				<?php esc_html_e( 'JWT Auth Key', 'wds' ); ?>
				<input
					name="wds-headless-jwt-auth-key" id="wds-headless-jwt-auth-key"
					type="password" value="<?php esc_attr_e( get_option( 'wds-headless-jwt-auth-key' ) ); // phpcs:ignore ?>"
					<?php wds_headless_is_disabled_field( 'wds-headless-jwt-auth-key' ); ?>
				/>
			</label>
		</p>

		<?php
		wp_nonce_field( 'wds-headless-settings-save', 'wds-headless-settings-save-nonce' );
		submit_button();
		?>
  </form>
</div>
