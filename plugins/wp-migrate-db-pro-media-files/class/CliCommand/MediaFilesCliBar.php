<?php

namespace DeliciousBrains\WPMDBMF\CliCommand;

/**
 * Class WPMDBPro_Media_Files_CLI_Bar
 * Simple wrapper for \cli\progress\bar that
 * provides setter access to the _message property
 */
class MediaFilesCliBar extends \cli\progress\Bar {

	public function setMessage( $message ) {
		$this->_message = $message;
	}
}
