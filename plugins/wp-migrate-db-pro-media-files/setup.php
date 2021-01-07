<?php

function wpmdb_setup_media_files_addon( $cli ) {
	global $wpmdbpro_media_files;

	$container = \DeliciousBrains\WPMDB\Container::getInstance();
	if ( class_exists( '\DeliciousBrains\WPMDB\Pro\ServiceProvider' ) ) {
		$container->get( 'media_files_addon' )->register();
		$container->get( 'media_files_addon_base' )->register();
		$container->get( 'media_files_addon_local' )->register();
		$container->get( 'media_files_addon_remote' )->register();
		$container->get( 'media_files_cli' )->register();
		if ( $cli ) {
			$wpmdbpro_media_files = \DeliciousBrains\WPMDB\Container::getInstance()->get( 'media_files_cli' );
		} else {
			$wpmdbpro_media_files = \DeliciousBrains\WPMDB\Container::getInstance()->get( 'media_files_addon' );
		}
	}

	// Allows hooks to bypass the regular admin / ajax checks to force load the Media Files addon (required for the CLI addon)
	$force_load = apply_filters( 'wp_migrate_db_pro_media_files_force_load', false );

	if ( false === $force_load && ! is_null( $wpmdbpro_media_files ) ) {
		return $wpmdbpro_media_files;
	}

	if ( false === $force_load && ( ! function_exists( 'wp_migrate_db_pro_loaded' ) || ! wp_migrate_db_pro_loaded() ) ) {
		return false;
	}

	load_plugin_textdomain( 'wp-migrate-db-pro-media-files', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	return $wpmdbpro_media_files;
}
