<?php
/**
 * The plugin bootstrap file
 *
 * @since             1.0.0
 * @package           Wp_Logger
 *
 * @wordpress-plugin
 * Plugin Name:       WPLogger
 * Plugin URI:        https://wp-logger.com
 * Description:       WP-Logger is a real-time user activity and monitoring log plugin. It helps WordPress webmasters keep an eye on what is happening on their websites.
 * Version:           2.0.0
 * Author:            WPLogger Teams
 * Author URI:        https://wp-logger.com/about/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       lite-wp-logger
 * Domain Path:       /languages
 */

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Uses */
use UAParser\Exception\FileNotFoundException;
use WPLogger\WPLogger;

/**
 * Plugin definitions
 */
const WP_LOGGER_VERSION      = '1.0.0';
const WP_LOGGER_FILE         = __FILE__;
const WP_LOGGER_NAME         = 'wp-logger';
const WP_LOGGER_NAME_LINLINE = 'wplogger';
const WP_LOGGER_NAME_OUTPUT  = 'WP Logger';
const WP_LOGGER_POST_TYPE    = 'wplogs';
define( 'WP_LOGGER_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_LOGGER_DIR_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_LOGGER_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Running composer autoload
 */
require_once 'vendor/autoload.php';

/**
 * The code that runs during plugin activation
 *
 * @return void
 */
function activate_wp_logger() {
	 WPLogger::activate();
}
register_activation_hook( WP_LOGGER_FILE, 'activate_wp_logger' );

/**
 * The code that runs during plugin deactivation
 *
 * @return void
 */
function deactivate_wp_logger() {
	WPLogger::deactivate();
}
register_deactivation_hook( WP_LOGGER_FILE, 'deactivate_wp_logger' );

/**
 * Starts plugin execution.
 *
 * @return void
 * @throws FileNotFoundException
 */
function init_wpLogger() {
	$wp_logger = WPLogger::get_instance();
	$wp_logger->initialize();
}
add_action( 'init', 'init_wpLogger', 20 );

/**
 * Freemius needs to be loaded before plugins_loaded.
 * Otherwise, the fs register_unistall_hook will get
 * Freemius not defined.
 *
 * @return mixed
 */
require_once 'includes/freemius.php';
if ( function_exists( 'wp_logger_fs' ) ) {
	wp_logger_fs()->set_basename( false, __FILE__ );
	return;
}

/**
 * Custom debug viewer
 *
 * @param  mixed $data
 * @param  int   $die
 * @return void
 */
function wp_logger_deBug_me( $data = 'Debug ME :)', int $die = 1 ) {
	echo '<pre>';
	print_r( $data );
	echo '</pre>';
	if ( $die ) {
		wp_die();
	}
}
