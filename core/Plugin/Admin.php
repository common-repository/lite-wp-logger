<?php
/**
 * WPLogger: Plugin Admin
 *
 * Admin view and settings for plugin
 *
 * @since 1.0.0
 * @package WPLogger
 */
namespace WPLogger\Plugin;

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Admin
 *
 * @package WPLogger
 */
class Admin
{
	/**
	 * Plugin options prefix
	 *
	 * @var string
	 */
	public $option_prefix = '_wp_logger_';
	/**
	 * Plugin settings
	 *
	 * @var array
	 */
	public $settings;
	/**
	 * Plugin settings fields
	 *
	 * @var array
	 */
	public $settings_fields;
	/**
	 * Plugin events settings
	 *
	 * @var array
	 */
	public $events_settings;
	/**
	 * Plugin events settings
	 *
	 * @var array
	 */
	public $events_email_settings;
	/**
	 * Plugin events settings fields
	 *
	 * @var array
	 */
	public $events_settings_fields;

	/**
	 * Admin class constructor.
	 *
	 * @return void
	 */
	public function __construct()
	{
		 /** stuff */
	}

	/**
	 * Initialize this class for direct usage
	 *
	 * @return Admin
	 */
	public static function initialize(): Admin
	{
		return new self;
	}

	/**
	 * Setup admin.
	 *
	 * @return void
	 */
	public function setup()
	{
		/** check fs activation */
		global $wp_logger_fs;
		if ( $wp_logger_fs->can_use_premium_code() ) {
			add_action( 'admin_footer', array( $wp_logger_fs, '_add_license_activation_dialog_box' ) );
		}

		add_action( 'admin_init', array( $this, 'settingsInit' ) );
		add_action( 'admin_init', array( $this, 'eventsSettingsInit' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueues' ) );
		add_action( 'admin_menu', array( $this, 'adminMenuPages' ) );
		add_filter( 'plugin_action_links_' . WP_LOGGER_BASENAME, array( $this, 'plugin_manage_link' ), 10, 1 );
	}

	/**
	 * Adding admin custom styles and scripts for plugin
	 *
	 * @return void
	 */
	public function enqueues()
	{
		$adminAssets = 'assets/admin/';
		/** css */
		wp_enqueue_style( WP_LOGGER_NAME . '-core', WP_LOGGER_DIR_URL . $adminAssets . 'css/global/core.css', array(), WP_LOGGER_VERSION );
		wp_enqueue_style( WP_LOGGER_NAME . '-toastify', WP_LOGGER_DIR_URL . $adminAssets . 'css/global/toastify.css', array(), WP_LOGGER_VERSION );
		/** js */
		wp_enqueue_script( WP_LOGGER_NAME . '-toastify', WP_LOGGER_DIR_URL . $adminAssets . 'js/global/toastify.js', array( 'jquery' ), WP_LOGGER_VERSION, true );
		wp_enqueue_script( WP_LOGGER_NAME . '-core', WP_LOGGER_DIR_URL . $adminAssets . 'js/global/core.js', array( 'jquery' ), WP_LOGGER_VERSION, true );
		wp_localize_script(
			WP_LOGGER_NAME . '-core',
			'wplogger_vars',
			array(
				'plugin_name' => WP_LOGGER_NAME_LINLINE,
				'logs_url'    => admin_url( 'edit.php?post_type=' . WP_LOGGER_POST_TYPE ),
				'settings'    => array(
					'admin_notify' => $this->getSetting( 'admin_notify' ),
				),
			)
		);
	}

	/**
	 * Adding custom admin menu pages
	 *
	 * @return void
	 */
	public function adminMenuPages()
	{
		global $wp_logger_fs;
		add_menu_page(
			__( WP_LOGGER_NAME_OUTPUT, 'lite-wp-logger' ),
			__( WP_LOGGER_NAME_OUTPUT, 'lite-wp-logger' ),
			'manage_options',
			WP_LOGGER_NAME_LINLINE,
			'',
			WP_LOGGER_DIR_URL . 'assets/admin/img/favicon.svg',
			75
		);
		/** active licence if is not premium */
		if ( ! $wp_logger_fs->can_use_premium_code() ) {
			add_submenu_page(
				WP_LOGGER_NAME_LINLINE,
				__( WP_LOGGER_NAME_OUTPUT . ' - Activate licence', 'lite-wp-logger' ),
				__( 'Activate licence', 'lite-wp-logger' ),
				'manage_options',
				WP_LOGGER_NAME_LINLINE . '-activate',
				'',
				1
			);
		}
		/** reports */
		add_submenu_page(
			WP_LOGGER_NAME_LINLINE,
			__( WP_LOGGER_NAME_OUTPUT . ' - Events Control', 'lite-wp-logger' ),
			__( 'Events Control', 'lite-wp-logger' ),
			'manage_options',
			WP_LOGGER_NAME_LINLINE . '-reports/#/events',
			array( $this, 'adminReportsPage' ),
			1
		);
		add_submenu_page(
			WP_LOGGER_NAME_LINLINE,
			__( WP_LOGGER_NAME_OUTPUT . ' - Settings', 'lite-wp-logger' ),
			__( 'Settings', 'lite-wp-logger' ),
			'manage_options',
			WP_LOGGER_NAME_LINLINE . '-reports/#/settings',
			array( $this, 'adminReportsPage' ),
			1
		);
		add_submenu_page(
			WP_LOGGER_NAME_LINLINE,
			__( WP_LOGGER_NAME_OUTPUT . ' - Online Users', 'lite-wp-logger' ),
			__( 'Online Users', 'lite-wp-logger' ),
			'manage_options',
			WP_LOGGER_NAME_LINLINE . '-reports/#/online-users',
			array( $this, 'adminReportsPage' ),
			1
		);
		add_submenu_page(
			WP_LOGGER_NAME_LINLINE,
			__( WP_LOGGER_NAME_OUTPUT . ' - Custom Report', 'lite-wp-logger' ),
			__( 'Custom Report', 'lite-wp-logger' ),
			'manage_options',
			WP_LOGGER_NAME_LINLINE . '-reports/#/logs',
			array( $this, 'adminReportsPage' ),
			1
		);
		add_submenu_page(
			WP_LOGGER_NAME_LINLINE,
			__( WP_LOGGER_NAME_OUTPUT . ' - Reports', 'lite-wp-logger' ),
			__( 'Reports', 'lite-wp-logger' ),
			'manage_options',
			WP_LOGGER_NAME_LINLINE . '-reports',
			array( $this, 'adminReportsPage' ),
			1
		);
		/** Wizard */
		add_submenu_page(
			null,
			__( WP_LOGGER_NAME_OUTPUT . ' - Wizard', 'lite-wp-logger' ),
			__( 'Wizard', 'lite-wp-logger' ),
			'manage_options',
			WP_LOGGER_NAME_LINLINE . '-wizard',
			function ()
			{
				include WP_LOGGER_DIR_PATH . 'views/admin/wizard.php';
			},
			1
		);
	}

	/**
	 * Including reports page
	 *
	 * @return void
	 */
	public function adminReportsPage()
	{
		include WP_LOGGER_DIR_PATH . 'views/admin/reports.php';
	}

	/**
	 * Set settings fields
	 *
	 * @return void
	 */
	public function setSettingsFields()
	{
		$this->settings_fields = array(
			'general'    => array(
				array(
					'id'         => 'logs_expire',
					'name'       => __( 'How long store logs?', 'lite-wp-logger' ),
					'desc'       => __( 'Days to store logs', 'lite-wp-logger' ),
					'type'       => 'number',
					'is_premium' => false,
					'max'        => 180,
					'min'        => 1,
					'std'        => 30,
				),
				array(
					'id'         => 'admin_notify',
					'name'       => __( 'Notify new logs', 'lite-wp-logger' ),
					'desc'       => __( 'Notify new logs in all admin?', 'lite-wp-logger' ),
					'type'       => 'checkbox',
					'is_premium' => true,
					'std'        => false,
				),
				array(
					'id'         => 'logs_auto_refresh',
					'name'       => __( 'Logs auto refresh', 'lite-wp-logger' ),
					'desc'       => __( 'Logs auto refresh in Activity logs page?', 'lite-wp-logger' ),
					'type'       => 'checkbox',
					'is_premium' => true,
					'std'        => false,
				),
				array(
					'id'         => 'logs_refresh_interval',
					'name'       => __( 'Logs refresh interval', 'lite-wp-logger' ),
					'desc'       => __( 'Logs auto refresh each specified seconds', 'lite-wp-logger' ),
					'type'       => 'number',
					'is_premium' => true,
					'min'        => 10,
					'std'        => 30,
				),
				array(
					'id'         => 'reports_auto_refresh',
					'name'       => __( 'Reports auto refresh', 'lite-wp-logger' ),
					'desc'       => __( 'custom reports auto refresh in Activity logs page?', 'lite-wp-logger' ),
					'type'       => 'checkbox',
					'is_premium' => false,
					'std'        => true,
				),
				array(
					'id'         => 'reports_refresh_interval',
					'name'       => __( 'Reports refresh interval', 'lite-wp-logger' ),
					'desc'       => __( 'custom reports auto refresh each specified seconds', 'lite-wp-logger' ),
					'type'       => 'number',
					'is_premium' => false,
					'min'        => 10,
					'std'        => 30,
				),
				array(
					'id'         => 'notify_emails',
					'name'       => __( 'Emails to notify', 'lite-wp-logger' ),
					'desc'       => __( 'Enter emails that notify will sent to them', 'lite-wp-logger' ),
					'type'       => 'adder',
					'is_premium' => true,
					'options'    => 'emails',
					'std'        => array( get_option( 'admin_email' ) ),
				),
			),
			'exclusions' => array(
				array(
					'id'         => 'exclude_users',
					'name'       => __( 'Exclude Users', 'lite-wp-logger' ),
					'desc'       => __( 'Users to exclude from logging. (We still log login,logout,etc. if events enabled)', 'lite-wp-logger' ),
					'type'       => 'multi_select',
					'is_premium' => false,
					'options'    => 'users',
					'std'        => array(),
				),
				array(
					'id'         => 'exclude_roles',
					'name'       => __( 'Exclude User Roles', 'lite-wp-logger' ),
					'desc'       => __( 'User roles to exclude from logging. (We still log login,logout,etc. if events enabled)', 'lite-wp-logger' ),
					'type'       => 'multi_select',
					'is_premium' => false,
					'options'    => 'roles',
					'std'        => array(),
				),
				array(
					'id'         => 'exclude_ips',
					'name'       => __( 'Exclude IPs', 'lite-wp-logger' ),
					'desc'       => __( 'Enter IPs to exclude from logging', 'lite-wp-logger' ),
					'type'       => 'adder',
					'is_premium' => true,
					'options'    => 'ips',
					'std'        => array(),
				),
				array(
					'id'         => 'exclude_post_types',
					'name'       => __( 'Exclude Post types', 'lite-wp-logger' ),
					'desc'       => __( 'Post types to exclude from logging', 'lite-wp-logger' ),
					'type'       => 'multi_select',
					'is_premium' => true,
					'options'    => 'post_types',
					'std'        => array(),
				),
				array(
					'id'         => 'exclude_post_metas',
					'name'       => __( 'Exclude Post metas', 'lite-wp-logger' ),
					'desc'       => __( 'Post metas to exclude from logging', 'lite-wp-logger' ),
					'type'       => 'adder', // @todo: ajax check if this meta available
					'is_premium' => true,
					'options'    => 'post_metas',
					'std'        => array(),
				),
				array(
					'id'         => 'exclude_options',
					'name'       => __( 'Exclude Options', 'lite-wp-logger' ),
					'desc'       => __( 'Option keys to exclude from logging (regex allowed. example:/^[a]/)', 'lite-wp-logger' ),
					'type'       => 'adder',
					'is_premium' => true,
					'options'    => 'options',
					'std'        => array(),
				),
			),
		);
		$this->settings_fields = apply_filters( WP_LOGGER_NAME_LINLINE . '_modify_settings', $this->settings_fields );
	}

	/**
	 * Set events settings fields
	 *
	 * @return void
	 */
	public function setEventsSettingsFields()
	{
		 $this->events_settings_fields = array(
			 'login'    => array(
				 array(
					 'id'         => 'login',
					 'name'       => __( 'Login', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging user login?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => false,
					 'std'        => true,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'login_fail',
					 'name'       => __( 'Login failed', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging user fails?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => false,
					 'std'        => true,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'logout',
					 'name'       => __( 'Logout', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging user logout?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'reset_pass',
					 'name'       => __( 'Reset Password', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging user reset Password?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'session',
					 'name'       => __( 'Active sessions', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging user active login sessions?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => null,
				 ),
			 ),
			 'users'    => array(
				 array(
					 'id'         => 'user_register',
					 'name'       => __( 'User register', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging users register?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => false,
					 'std'        => true,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'user_profile_update',
					 'name'       => __( 'User profile update', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging users profile update?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => false,
					 'std'        => true,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'user_role_change',
					 'name'       => __( 'User role change', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging users role change?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'user_delete',
					 'name'       => __( 'User delete', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging users deletion?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'user_super_admin',
					 'name'       => __( 'User super admin', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging users set super admin?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'user_unsuper_admin',
					 'name'       => __( 'User revoke super admin', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging users revoke super admin?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'user_spam',
					 'name'       => __( 'User spam', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging users set as spam?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
			 ),
			 'content'  => array(
				 array(
					 'id'         => 'open_post',
					 'name'       => __( 'Open post', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging when post open for edit?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => false,
					 'std'        => true,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'new_post',
					 'name'       => __( 'New post', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every new post?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => false,
					 'std'        => true,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'auto_draft_post',
					 'name'       => __( 'Auto draft post', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every draft post?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'update_post',
					 'name'       => __( 'Update post', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every update post?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'trash_post',
					 'name'       => __( 'Trash post', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every trash post?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'untrash_post',
					 'name'       => __( 'Untrash post', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every untrash post?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'delete_post',
					 'name'       => __( 'Delete post', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every delete post?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'sticky_post',
					 'name'       => __( 'Sticky post', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every sticky post?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'term_create',
					 'name'       => __( 'Term create', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every term creation?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'term_update',
					 'name'       => __( 'Term update', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every term update?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'term_delete',
					 'name'       => __( 'Term delete', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every term deletion?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
			 ),
			 'comments' => array(
				 array(
					 'id'         => 'new_comments',
					 'name'       => __( 'New comments', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every new comments?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => false,
					 'std'        => true,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'edit_comments',
					 'name'       => __( 'Edit comments', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every edit comments?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => false,
					 'std'        => true,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'comments_status',
					 'name'       => __( 'Comments status', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every comments status change?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'comments_spam',
					 'name'       => __( 'Comments spam', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every comments marked as spam?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'comments_unspam',
					 'name'       => __( 'Comments unspam', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every comments marked unspam?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'comments_trash',
					 'name'       => __( 'Comments trash', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every comments trashed?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'comments_untrash',
					 'name'       => __( 'Comments trash', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every comments untrashed?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'comments_delete',
					 'name'       => __( 'Comments delete', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every comments deleted?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
			 ),
			 'plugins'  => array(
				 array(
					 'id'         => 'activate_plugin',
					 'name'       => __( 'Activate plugin', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every plugin activation?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => false,
					 'std'        => true,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'deactivate_plugin',
					 'name'       => __( 'Deactivate plugin', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every plugin deactivation?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => false,
					 'std'        => true,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'update_plugin',
					 'name'       => __( 'Update plugin', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every plugin update?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'install_plugin',
					 'name'       => __( 'Install plugin', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every plugin install?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'uninstall_plugin',
					 'name'       => __( 'Uninstall plugin', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every plugin uninstall?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
			 ),
			 'themes'   => array(
				 array(
					 'id'         => 'change_theme',
					 'name'       => __( 'Change theme', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every theme change?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => false,
					 'std'        => true,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'update_theme',
					 'name'       => __( 'Update theme', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every theme update?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => false,
					 'std'        => true,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'install_theme',
					 'name'       => __( 'Install theme', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every theme install?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'uninstall_theme',
					 'name'       => __( 'Uninstall theme', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every theme uninstall?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
			 ),
			 'system'   => array(
				 array(
					 'id'         => 'update_option',
					 'name'       => __( 'Update option', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging every option update?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
				 array(
					 'id'         => 'core_update',
					 'name'       => __( 'Core update', 'lite-wp-logger' ),
					 'desc'       => __( 'Logging WP core update?', 'lite-wp-logger' ),
					 'type'       => 'checkbox',
					 'is_premium' => true,
					 'std'        => false,
					 'email'      => false,
				 ),
			 ),
		 );
		 $this->events_settings_fields = apply_filters( WP_LOGGER_NAME_LINLINE . '_modify_events_settings', $this->events_settings_fields );
	}

	/**
	 * Retrieve settings data
	 *
	 * @return void
	 */
	public function retrieveSettings()
	{
		$this->settings = get_option( WP_LOGGER_NAME_LINLINE . '-settings' );
		if ( empty( $this->settings ) ) {
			$this->settings = array();
		}
	}

	/**
	 * Retrieve events settings data
	 *
	 * @return void
	 */
	public function retrieveEventsSettings()
	{
		$this->events_settings = get_option( WP_LOGGER_NAME_LINLINE . '-events-settings' );
		if ( empty( $this->events_settings ) ) {
			$this->events_settings = array();
		}
	}

	/**
	 * Retrieve events email settings data
	 *
	 * @return void
	 */
	public function retrieveEventsEmailSettings()
	{
		 $this->events_email_settings = get_option( WP_LOGGER_NAME_LINLINE . '-events-email-settings' );
		if ( empty( $this->events_email_settings ) ) {
			$this->events_email_settings = array();
		}
	}

	/**
	 * Initialize settings
	 *
	 * @return void
	 */
	public function settingsInit()
	{
		register_setting(
			WP_LOGGER_NAME_LINLINE . '-settings',
			WP_LOGGER_NAME_LINLINE . '-settings'
		);

		$this->setSettingsFields();
		$this->retrieveSettings();
	}

	/**
	 * Initialize events settings
	 *
	 * @return void
	 */
	public function eventsSettingsInit()
	{
		register_setting(
			WP_LOGGER_NAME_LINLINE . '-events-settings',
			WP_LOGGER_NAME_LINLINE . '-events-settings'
		);
		/** email settings */
		register_setting(
			WP_LOGGER_NAME_LINLINE . '-events-email-settings',
			WP_LOGGER_NAME_LINLINE . '-events-email-settings'
		);

		$this->setEventsSettingsFields();
		$this->retrieveEventsSettings();
		$this->retrieveEventsEmailSettings();
	}

	/**
	 * Get a setting data
	 *
	 * @param  string $setting_key
	 * @return mixed
	 */
	public function getSetting( string $setting_key )
	{
		 global $wp_logger_fs;

		if ( ! $wp_logger_fs->can_use_premium_code() && $this->getSettingField( $setting_key, 'is_premium' ) ) {
			return $this->getSettingField( $setting_key );
		} else {
			if ( isset( $this->settings[ $setting_key ] ) ) {
				if ( $this->getSettingField( $setting_key, 'type' ) == 'checkbox' ) {
					return $this->settings[ $setting_key ] == '1';
				} else {
					return $this->settings[ $setting_key ];
				}
			} else {
				return $this->getSettingField( $setting_key );
			}
		}
	}

	/**
	 * Get an event setting data
	 *
	 * @param  string $setting_key
	 * @return mixed
	 */
	public function getEventSetting( string $setting_key )
	{
		global $wp_logger_fs;

		if ( ! $wp_logger_fs->can_use_premium_code() && $this->getEventSettingField( $setting_key, 'is_premium' ) ) {
			return $this->getEventSettingField( $setting_key );
		} else {
			if ( isset( $this->events_settings[ $setting_key ] ) ) {
				return $this->events_settings[ $setting_key ] == '1';
			} else {
				return $this->getEventSettingField( $setting_key );
			}
		}
	}

	/**
	 * Get an event email setting data
	 *
	 * @param  string $setting_key
	 * @return mixed
	 */
	public function getEventEmailSetting( string $setting_key )
	{
		global $wp_logger_fs;

		if ( ! $wp_logger_fs->can_use_premium_code() ) {
			return $this->getEventSettingField( $setting_key, 'email' );
		} else {
			if ( isset( $this->events_email_settings[ $setting_key ] ) ) {
				return $this->events_email_settings[ $setting_key ] == '1';
			} else {
				return $this->getEventSettingField( $setting_key, 'email' );
			}
		}
	}

	/**
	 * For getting setting given field value
	 *
	 * @param  string $setting_key
	 * @param  string $setting_item
	 * @return mixed
	 */
	public function getSettingField( string $setting_key, string $setting_item = 'std' )
	{
		foreach ( $this->settings_fields as $setting_field ) {
			$foundKey = array_search( $setting_key, array_column( $setting_field, 'id' ) );
			if ( $foundKey || $foundKey === 0 ) {
				if ( isset( $setting_field[ $foundKey ][ $setting_item ] ) ) {
					return $setting_field[ $foundKey ][ $setting_item ];
				}
			}
		}
		return 0;
	}

	/**
	 * For getting event setting std value
	 *
	 * @param  string $setting_key
	 * @param  string $setting_item
	 * @return mixed
	 */
	public function getEventSettingField( string $setting_key, string $setting_item = 'std' )
	{
		foreach ( $this->events_settings_fields as $setting_field ) {
			$foundKey = array_search( $setting_key, array_column( $setting_field, 'id' ) );
			if ( $foundKey || $foundKey === 0 ) {
				if ( isset( $setting_field[ $foundKey ][ $setting_item ] ) ) {
					return $setting_field[ $foundKey ][ $setting_item ];
				}
			}
		}
		return 0;
	}

	/**
	 * Return the plugin action links.  This will only be called if the plugin
	 * is active.
	 *
	 * @param  array $links associative array of action names to anchor tags
	 * @return array        associative array of plugin action links
	 */
	public function plugin_manage_link( array $links )
	{
		$links['settings'] = '<a href="' . admin_url( 'admin.php?page=wplogger-pricing&trial=true' ) . '">' .
		                     __( 'Update To Trial', 'lite-wp-logger' ) . '</a>';
		return $links;
	}

}
