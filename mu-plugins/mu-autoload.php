<?php
/**
 * Autoload classes required by the project.
 *
 * @author Justin Foell <justin.foell@webdevstudios.com>
 * @since  2021-01-07
 * @package WebDevStudios\MUAutoload
 */

$autoload = ABSPATH . 'wp-content/vendor/autoload.php';

if ( is_readable( $autoload ) ) {
	require_once $autoload;
}