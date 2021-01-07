<?php

namespace DeliciousBrains\WPMDBMF;

use DeliciousBrains\WPMDB\Container;
use DeliciousBrains\WPMDBMF\CliCommand\MediaFilesCli;

class ServiceProvider extends \DeliciousBrains\WPMDB\Pro\ServiceProvider {

	public $media_files_addon;
	public $media_files_cli;
	public $media_files_addon_remote;
	public $media_files_addon_local;
	public $media_files_addon_base;

	public function __construct() {
		parent::__construct();

		$this->media_files_addon = new MediaFilesAddon(
			$this->addon,
			$this->properties,
			$this->template
		);

		$this->media_files_addon_base = new MediaFilesBase(
			$this->filesystem,
			$this->migration_state_manager,
			$this->form_data
		);

		$this->media_files_addon_local = new MediaFilesLocal(
			$this->filesystem,
			$this->migration_state_manager,
			$this->form_data,
			$this->http,
			$this->settings,
			$this->util,
			$this->http_helper,
			$this->remote_post,
			$this->error_log,
			$this->state_data_container
		);

		$this->media_files_addon_remote = new MediaFilesRemote(
			$this->filesystem,
			$this->migration_state_manager,
			$this->form_data,
			$this->http,
			$this->settings,
			$this->util,
			$this->http_helper,
			$this->error_log,
			$this->properties,
			$this->scrambler
		);

		$this->media_files_cli = new MediaFilesCli(
			$this->addon,
			$this->properties,
			$this->template,
			$this->cli,
			$this->cli_manager,
			$this->util,
			$this->state_data_container
		);

	}
}
