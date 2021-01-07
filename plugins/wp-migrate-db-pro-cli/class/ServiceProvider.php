<?php

namespace DeliciousBrains\WPMDBCli;

class ServiceProvider extends \DeliciousBrains\WPMDB\Pro\ServiceProvider {

	/**
	 * @var CliAddon
	 */
	public $cli_addon;
	/**
	 * @var Cli
	 */
	public $cli_addon_cli;
	/**
	 * @var Setting
	 */
	public $cli_settings;


	public function __construct() {
		parent::__construct();

		$this->cli_addon = new CliAddon(
			$this->addon,
			$this->properties
		);

		$this->cli_addon_cli = new Cli(
			$this->form_data,
			$this->util,
			$this->cli_manager,
			$this->table,
			$this->error_log,
			$this->initiate_migration,
			$this->finalize_migration,
			$this->http_helper,
			$this->migration_manager,
			$this->migration_state_manager,
			$this->connection,
			$this->backup_export,
			$this->properties,
			$this->multisite,
			$this->import
		);

		$this->cli_settings = new Setting(
			$this->form_data,
			$this->util,
			$this->cli_manager,
			$this->table,
			$this->error_log,
			$this->initiate_migration,
			$this->finalize_migration,
			$this->http_helper,
			$this->migration_manager,
			$this->migration_state_manager,
			$this->license,
			$this->settings
		);
	}
}
