<?php
/**
 * WPLogger
 *
 * WPLogger class file.
 *
 * @since 1.0.0
 * @package WPLogger
 */
namespace WPLogger;

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Uses */
use UAParser\Exception\FileNotFoundException;
use WPLogger\Database\Setup;
use WPLogger\Database\Session;
use WPLogger\Logger\Comment;
use WPLogger\Logger\Logger;
use WPLogger\Logger\Plugin;
use WPLogger\Logger\Post;
use WPLogger\Logger\Settings;
use WPLogger\Logger\System;
use WPLogger\Logger\Theme;
use WPLogger\Logger\User as UserLog;
use WPLogger\Plugin\Report;
use WPLogger\Plugin\Wizard;
use WPLogger\User\User;
use WPLogger\Plugin\Admin;

/**
 * WPLogger is main class of plugin
 *
 * @package WPLogger
 */
class WPLogger {

	/**
	 * Stores plugin tables prefix in DB
	 *
	 * @var string
	 */
	public $table_prefix;
	/**
	 * Stores plugin minimum PHP version
	 *
	 * @var string
	 */
	const MIN_PHP_VERSION = '7.0';
	/**
	 * Stores plugin minimum PHP version
	 *
	 * @var WPLogger
	 */
	private static $instance;

	/**
	 * WPLogger constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		 $this->table_prefix = 'logger_';
	}

	/**
	 * WPLogger instance.
	 *
	 * @return WPLogger
	 */
	public static function get_instance(): WPLogger {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Fires when plugin activated
	 *
	 * @return void
	 */
	public static function activate() {
		 $db = new Setup();
		$db->createTables();
		/** for session init */
		update_option( WP_LOGGER_NAME_LINLINE . '-session-init', '' );
	}

	/**
	 * Fires when plugin deactivated
	 *
	 * @return void
	 */
	public static function deactivate() {
		/** for session init */
		update_option( WP_LOGGER_NAME_LINLINE . '-session-init', '' );
	}

	/**
	 * Used for loading plugin i18n
	 *
	 * @return void
	 */
	public function textDomain() {
		load_plugin_textdomain(
			WP_LOGGER_NAME,
			false,
			dirname( WP_LOGGER_BASENAME, 2 ) . '/languages/'
		);
	}

	/**
	 * Used for i18n action
	 *
	 * @return void
	 */
	private function set_locale() {
		 add_action( 'plugins_loaded', array( $this, 'textDomain' ) );
	}

	/**
	 * Custom Var export highlighter
	 *
	 * @param  mixed $var
	 * @return string
	 */
	public static function varLight( $var ) {
		$retData = highlight_string( "<?php \$d =\n" . var_export( $var, true ) . "\n; ?>", true );
		$retData = str_replace(
			array(
				'<span style="color: #0000BB">&lt;?php&nbsp;$d&nbsp;</span><span style="color: #007700">=<br />',
				'<br />;&nbsp;</span><span style="color: #0000BB">?&gt;</span>',
			),
			array(
				'<span style="color: #007700">',
				'</span>',
			),
			$retData
		);
		return '<div class="logger-varlight"><div class="varlight-inner">' . $retData . '</div></div>';
	}

	/**
	 * Used for initializing user sessions
	 *
	 * @return void
	 */
	public function initSession() {
		 $c_id    = get_current_user_id();
		$session  = new Session();
		$sessions = $session->getAll();
		$user_ids = array();

		foreach ( $sessions as $sess ) {
			$user_ids[] = $sess->user_id;
		}

		$user_ids = array_diff( $user_ids, array( $c_id ) );
		if ( ! empty( $user_ids ) ) {
			$session->removeBulk( $user_ids );
		}

		$cookie        = wp_parse_auth_cookie( '', 'logged_in' );
		$session_token = ! empty( $cookie['token'] ) ? $cookie['token'] : '';
		$expiration_dt = date( 'Y-m-d H:i:s', $cookie['expiration'] );
		$user          = new User();
		$theUser       = get_userdata( $c_id );

		$session->add(
			$c_id,
			$session_token,
			$expiration_dt,
			$user->getUserIp(),
			$theUser->roles
		);
	}

	/**
	 * Runs Logger initial classes
	 *
	 * @return void
	 * @throws FileNotFoundException
	 */
	public function initialize() {
		/** for session init */
		if ( get_option( WP_LOGGER_NAME_LINLINE . '-session-init' ) == '' ) {
			$this->initSession();
			update_option( WP_LOGGER_NAME_LINLINE . '-session-init', 'done' );
		}
		$this->set_locale();
		User::initialize()->setup();
		Logger::initialize()->setup();
		Admin::initialize()->setup();
		/** reports page */
		Report::initialize();
		/** plugin setup wizard */
		Wizard::initialize();
		/** loggers */
		UserLog::initialize();
		Post::initialize();
		Comment::initialize();
		Settings::initialize();
		System::initialize();
		Plugin::initialize();
		Theme::initialize();
		/** only for the first activation */
		if ( ! get_option( WP_LOGGER_NAME_LINLINE . '-first-init' ) ) {
			$logger      = new Logger();
			$plugin_file = ABSPATH . 'wp-content/plugins/' . WP_LOGGER_BASENAME;
			$plugin_data = get_plugin_data( $plugin_file );
			$message     = __( 'Plugin activated. Name: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Name'] ) .
						   __( ' Version: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Version'] ) . '<br>' .
						   __( 'Plugin Data:', 'lite-wp-logger' ) . '<br>' .
						   self::varLight( $plugin_data );

			$logger->add(
				array(
					'title'      => __( 'Plugin activated: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Name'] ),
					'message'    => $message,
					'type'       => 'plugin_activate',
					'importance' => 'high',
					'metas'      => array(
						'plugin_data' => $plugin_data,
						'desc'        => __( 'Plugin name: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Name'] ) . '<br>' .
										 __( 'Plugin version: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Version'] ) . '<br>' .
										 '<a target="_blank" href="' . admin_url( 'plugins.php' ) . '">' .
										 __( 'Manage plugins', 'lite-wp-logger' ) . '</a>',
					),
				),
				'activate_plugin'
			);

			update_option( WP_LOGGER_NAME_LINLINE . '-first-init', 'done' );
		}
	}
}
