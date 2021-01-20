<?php
/**
 * Autoload classes required by the project.
 *
 * @author Justin Foell <justin.foell@webdevstudios.com>
 * @since  2021-01-20
 * @package WebDevStudios\MUAutoload
 */

$autoload = WP_CONTENT_DIR . '/vendor/autoload.php';

if ( is_readable( $autoload ) ) {
	require_once $autoload;
}