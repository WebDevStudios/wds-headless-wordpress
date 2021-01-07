<?php
/**
 * Plugin Name: WDS Single Sign-On
 * Plugin URI: https://github.com/WebDevStudios/wds-sso/
 * Description: WDS Single Sign-On requests for Google.
 * Version: 2.0.1
 * Author: WebDevStudios
 * Author URI: https://webdevstudios.com
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wds-sso
 * Network: True
 *
 * @since 1.0.0
 * @package  WebDevStudios\SSO
 */

// Plugin can act as proxy and client.
require_once 'wds-sso-proxy.php';
require_once 'wds-sso-client.php';
