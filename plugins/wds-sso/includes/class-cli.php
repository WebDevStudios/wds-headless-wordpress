<?php
/**
 * CLI commands Instance.
 *
 * @since 1.1.0
 * @package  WebDevStudios\SSO
 */

namespace WebDevStudios\SSO;

use \WP_CLI;
use \WP_CLI_Command;

/**
 * CLI commands for WDS-SSO.
 *
 * This class contains all of the
 * SSO CLI commands.
 *
 * @author Justin Foell
 * @since  1.1.0
 */
class CLI extends WP_CLI_Command {

	/**
	 * Arguments passed in.
	 *
	 * @var array
	 * @author Justin Foell
	 * @since  1.1.0
	 */
	private $args = array();

	/**
	 * Associative arguments passed in.
	 *
	 * @var array
	 * @author Justin Foell
	 * @since  1.1.0
	 */
	private $assoc_args = array();

	/**
	 * Decodes a state parameter for debugging.
	 *
	 * @author Justin Foell
	 * @since  1.1.0
	 *
	 * ## OPTIONS
	 *
	 * <state>
	 * : An encoded state parameter.
	 *
	 * @param array $args       Arguments.
	 * @param array $assoc_args Arguments.
	 */
	public function decode_state( $args, $assoc_args ) {
		$this->args       = $args;
		$this->assoc_args = $assoc_args;

		foreach ( $this->args as $state ) {
			$decoded = app()->shared->decode_state( $state );
			WP_CLI::log( print_r( $decoded, true ) ); // @codingStandardsIgnoreLine: Print_r desired here.
		}
	}
}

WP_CLI::add_command( 'wds-sso', __NAMESPACE__ . '\CLI' );
