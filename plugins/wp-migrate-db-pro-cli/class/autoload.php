<?php
// Load main MDB Autoloader and classes

$autoloader_path = '';
if ( isset( $GLOBALS['wpmdb_meta']['wp-migrate-db-pro']['abspath'] ) ) {
	$autoloader_path = $GLOBALS['wpmdb_meta']['wp-migrate-db-pro']['abspath'] . '/class/autoload.php';
}

if ( file_exists( $autoloader_path ) ) {
	require_once $autoloader_path;
}


spl_autoload_register( function ( $class ) {

	// project-specific namespace prefix
	$prefix = 'DeliciousBrains\\WPMDBCli\\';

	// base directory for the namespace prefix
	$base_dir = __DIR__ . '/';

	// does the class use the namespace prefix?
	$len = strlen( $prefix );
	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		// no, move to the next registered autoloader
		return;
	}

	// get the relative class name
	$relative_class = substr( $class, $len );

	// replace the namespace prefix with the base directory, replace namespace
	// separators with directory separators in the relative class name, append
	// with .php
	$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

	// if the file exists, require it
	if ( file_exists( $file ) ) {
		require $file;
	}
} );
