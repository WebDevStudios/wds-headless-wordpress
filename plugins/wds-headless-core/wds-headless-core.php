<?php
/**
 * Plugin Name: WDS Headless (Core)
 * Plugin URI: https://github.com/WebDevStudios/wds-headless-wordpress
 * Description: This plugin supports the JAMStack-powered frontend.
 * Author: WebDevStudios <contact@webdevstudios.com>
 * Author URI: https://webdevstudios.com
 * Version: 1.0.0
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * License: GPL-2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package WDS_Headless
 */

namespace WDS_Headless;

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

define( 'WDS_HEADLESS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Load TGM Plugin Activation.
require_once 'inc/tgm/tgm.php';

require_once 'inc/editor.php';
require_once 'inc/links.php';
require_once 'inc/media.php';
require_once 'inc/menus.php';
require_once 'inc/settings.php';
require_once 'inc/wp-graphql.php';
