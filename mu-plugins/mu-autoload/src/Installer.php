<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName -- PSR4
/**
 * Installer for an mu-plugin autoloader.
 *
 * @author Justin Foell <justin.foell@webdevstudios.com>
 * @since  2019-11-12
 * @package WebDevStudios\MUAutoload
 */

namespace WebDevStudios\MUAutoload;

use Composer\Script\Event;
use ErrorException;

/**
 * Installer class.
 *
 * @author Justin Foell <justin.foell@webdevstudios.com>
 * @since  2019-11-12
 */
class Installer {

	/**
	 * Installer called by post-update-cmd composer script.
	 *
	 * @param Event $event Composer event.
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2019-11-12
	 */
	public static function install( Event $event ) {
		echo 'Creating mu-autoload.php... ';
		if ( ! self::include_wp( dirname( __FILE__ ) ) ) {
			echo "Couldn't include WP... guessing location\n";

			if ( ! self::wp_location_best_guess( $event->getComposer()->getPackage()->getExtra() ) ) {
				echo "mu-plugin autoloader installation aborted\n";
				exit( 1 );
			}
		}

		$vendor_dir = $event->getComposer()->getConfig()->get( 'vendor-dir' );
		$base_path  = defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : ABSPATH . 'wp-content';
		$file_path  = $base_path . '/mu-plugins/mu-autoload.php';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents -- OK, local only.
		$result = file_put_contents(
			$file_path,
			self::get_autoloader_contents( self::get_wp_autoload_path( $vendor_dir ) )
		);

		if ( false === $result ) {
			echo "Unable to write file to: {$file_path}\n";
			exit( 1 );
		} else {
			echo "Wrote {$result} bytes to: {$file_path}\n";
		}
	}

	/**
	 * Recursively climb the directory tree looking for wp-load.php.
	 *
	 * @param string $dir Path to start at.
	 * @return boolean True if wp-load.php was included.
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2019-11-12
	 */
	private static function include_wp( $dir ) {
		$dir = realpath( $dir );

		$dir = self::normalize_path( $dir );

		// Stop b/c we've traversed up to the root.
		if ( self::is_root( $dir ) ) {
			return false;
		}

		$wp_load = $dir . DIRECTORY_SEPARATOR . 'wp-load.php';

		if ( is_readable( $wp_load ) ) {
			// Turn debug ON to prevent usage of `@`!
			define( 'WP_DEBUG', true );

			try {
				require_once $wp_load;
			} catch ( ErrorException $ee ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- empty OK.

				/*
				 * We may encounter a database exception if it's not hooked up,
				 * that's OK if we can still get to the define()s.
				 */
				if ( ! defined( 'ABSPATH' ) && ! defined( 'WP_CONTENT_DIR' ) ) {
					return false;
				}
			}
			return true;
		}

		return self::include_wp( $dir . DIRECTORY_SEPARATOR . '..' );
	}

	/**
	 * Try to guess the WP install directory based on installer-paths in composer.json.
	 *
	 * @param array $extra Composer file extra section.
	 * @return boolean True if location guessed and path define()s set, false otherwise.
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2020-02-04
	 */
	private static function wp_location_best_guess( array $extra ) {
		if ( empty( $extra['installer-paths'] ) ) {
			return false;
		}

		$composer_json_dir = getcwd();

		foreach ( $extra['installer-paths'] as $path => $constraints ) {
			$value = ( is_array( $constraints ) && count( $constraints ) === 1 ) ? current( $constraints ) : $constraints;

			if ( in_array( $value, array( 'type:wordpress-muplugin', 'type:wordpress-plugin' ), true ) ) {
				$parts = explode( '/', $path );

				foreach ( $parts as $index => $dir ) {
					if ( in_array( $dir, array( 'wp-content', 'mu-plugins', 'plugins' ), true ) ) {

						$extra_path = 'wp-content' !== $dir ? '/..' : '';
						$partial    = array_slice( $parts, 0, $index + 1 );
						$wp_content = realpath( $composer_json_dir . '/' . join( '/', $partial ) . $extra_path );

						define( 'WP_CONTENT_DIR', $wp_content );
						define( 'ABSPATH', realpath( $wp_content . '/..' ) );

						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Is the directory the root directory.
	 *
	 * @param string $dir Directory path.
	 * @return boolean Whether or not it's the root.
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2020-07-17
	 */
	private static function is_root( $dir ) {
		// Remove drive letter and colon on windows.
		if ( ':' === substr( $dir, 1, 1 ) ) {
			$dir = substr( $dir, 2 );
		}

		return '/' === $dir;
	}

	/**
	 * Normalize filesystem path.
	 *
	 * @see https://developer.wordpress.org/reference/functions/wp_normalize_path/
	 * @param string $path Filesystem path.
	 * @return string Path with directory separators normalized.
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2020-07-17
	 */
	private static function normalize_path( $path ) {
		// Standardise all paths to use '/'.
		$path = str_replace( '\\', '/', $path );

		// Replace multiple slashes down to a singular, allowing for network shares having two slashes.
		$path = preg_replace( '|(?<=.)/+|', '/', $path );

		// Windows paths should uppercase the drive letter.
		if ( ':' === substr( $path, 1, 1 ) ) {
			$path = ucfirst( $path );
		}

		return $path;
	}

	/**
	 * Try to do some WP constant substitutions in the autoload directory path.
	 *
	 * @param string $vendor_dir Vendor directory.
	 * @return string Autoload file path, quoted for include, with possible WP constant substitutions.
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2019-11-12
	 */
	private static function get_wp_autoload_path( $vendor_dir ) {
		$vendor_dir = self::normalize_path( $vendor_dir );

		if ( defined( 'WP_CONTENT_DIR' ) ) {
			$wp_content_dir = self::normalize_path( WP_CONTENT_DIR );

			if ( 0 === strpos( $vendor_dir, $wp_content_dir ) ) {
				return "WP_CONTENT_DIR . '" . substr( $vendor_dir, strlen( $wp_content_dir ) ) . "/autoload.php'";
			}
		}

		if ( defined( 'ABSPATH' ) ) {
			$abspath = self::normalize_path( ABSPATH );

			if ( 0 === strpos( $vendor_dir, $abspath ) ) {

				return "ABSPATH . '" . substr( $vendor_dir, strlen( $abspath ) ) . "/autoload.php'";
			}
		}
		return "'{$vendor_dir}/autoload.php'";
	}

	/**
	 * Get the autoloader file contents.
	 *
	 * @param string $autoload_path Path to autoload.php.
	 * @return string PHP autoloader file contents.
	 * @author Justin Foell <justin.foell@webdevstudios.com>
	 * @since  2019-11-12
	 */
	private static function get_autoloader_contents( $autoload_path ) {
		$date = date( 'Y-m-d' );
		return <<<LOADER
<?php
/**
 * Autoload classes required by the project.
 *
 * @author Justin Foell <justin.foell@webdevstudios.com>
 * @since  {$date}
 * @package WebDevStudios\MUAutoload
 */

\$autoload = {$autoload_path};

if ( is_readable( \$autoload ) ) {
	require_once \$autoload;
}
LOADER;
	}
}
