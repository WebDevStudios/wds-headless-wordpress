<?php

namespace DeliciousBrains\WPMDBCli;

use DeliciousBrains\WPMDB\Common\Properties\Properties;
use DeliciousBrains\WPMDB\Pro\Addon\Addon;
use DeliciousBrains\WPMDB\Pro\Addon\AddonAbstract;

class CliAddon extends AddonAbstract {

	const MDB_VERSION_REQUIRED = '1.9.3b1';

	public function __construct(
		Addon $addon,
		Properties $properties
	) {
		parent::__construct( $addon, $properties );
	}

	public function register() {
		$this->plugin_slug    = 'wp-migrate-db-pro-cli';
		$this->plugin_version = $GLOBALS['wpmdb_meta']['wp-migrate-db-pro-cli']['version'];
		$this->addon_name     = 'WP Migrate DB Pro CLI';

		if ( ! $this->meets_version_requirements( self::MDB_VERSION_REQUIRED ) ) {
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				printf( __( 'Update Required - The CLI Addon requires WP Migrate DB Pro %s or higher.' ), self::MDB_VERSION_REQUIRED );
				echo PHP_EOL;
				exit;
			}
			return;
		}
	}
}
