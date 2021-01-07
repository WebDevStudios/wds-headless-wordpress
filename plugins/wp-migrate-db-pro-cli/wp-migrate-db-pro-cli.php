<?php
/*
Plugin Name: WP Migrate DB Pro CLI
Plugin URI: https://deliciousbrains.com/wp-migrate-db-pro/
Description: An extension to WP Migrate DB Pro, allows you to execute migrations using a function call or via WP-CLI
Author: Delicious Brains
Version: 1.3.5
Author URI: https://deliciousbrains.com
Network: True
*/

// Copyright (c) 2013 Delicious Brains. All rights reserved.
//
// Released under the GPL license
// http://www.opensource.org/licenses/gpl-license.php
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// **********************************************************************

require_once 'version.php';
$GLOBALS['wpmdb_meta']['wp-migrate-db-pro-cli']['folder'] = basename( plugin_dir_path( __FILE__ ) );

if ( version_compare( PHP_VERSION, '5.4', '>=' ) ) {
	require_once __DIR__ . '/class/autoload.php';
	require_once __DIR__ . '/setup.php';
}

function wp_migrate_db_pro_cli_addon_loaded() {
	if ( ! class_exists( '\DeliciousBrains\WPMDB\Pro\WPMigrateDBPro' ) ) {
		return;
	}

	if ( function_exists( 'wp_migrate_db_pro' ) ) {
		wp_migrate_db_pro();
	} else {
		return false;
	}

	if ( function_exists( 'wpmdb_setup_cli_addon' ) ) {
		wpmdb_setup_cli_addon();
	}
}

add_action( 'plugins_loaded', 'wp_migrate_db_pro_cli_addon_loaded', 20 );

/**
 * Populate the $wpmdbpro_cli global with an instance of the WPMDBPro_CLI class and return it.
 *
 * @return WPMDBPro_CLI The one true global instance of the WPMDBPro_CLI class.
 */
function wp_migrate_db_pro_cli_addon() {
	global $wpmdbpro_cli;

	if ( ! is_null( $wpmdbpro_cli ) ) {
		return $wpmdbpro_cli;
	}

	do_action( 'wp_migrate_db_pro_cli_before_load' );

	if ( function_exists( 'wpmdb_get_cli_addon_instance' ) ) {
		$wpmdbpro_cli = wpmdb_get_cli_addon_instance();
	}

	do_action( 'wp_migrate_db_pro_cli_after_load' );

	return $wpmdbpro_cli;
}
