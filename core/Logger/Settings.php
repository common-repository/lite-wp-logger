<?php
/**
 * WPLogger: Settings
 *
 * Settings class file.
 *
 * @since 1.0.0
 * @package WPLogger
 */
namespace WPLogger\Logger;

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Uses */
use UAParser\Exception\FileNotFoundException;
use WPLogger\Plugin\Admin;
use WPLogger\WPLogger;

/**
 * Class Settings for logging
 *
 * @package WPLogger
 */
class Settings {

	/**
	 * List of options
	 *
	 * @var Logger
	 */
	public $settings_options;
	/**
	 * Excluded options not to be logged
	 *
	 * @var Logger
	 */
	public $options_exclude;
	/**
	 * For using logger class
	 *
	 * @var Logger
	 */
	private $logger;
	/**
	 * Using admin class
	 *
	 * @var Admin
	 */
	public $admin;

	/**
	 * Settings class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		 $this->logger = new Logger();
		/** get events settings */
		$this->admin = new Admin();
		$this->admin->setEventsSettingsFields();
		$this->admin->retrieveEventsSettings();
		/** get settings */
		$this->admin->setSettingsFields();
		$this->admin->retrieveSettings();
		/**
		 * Settings actions
		 */
		if ( $this->admin->getEventSetting( 'update_option' ) ) {
			add_action( 'update_option', array( $this, 'updateOption' ), 10, 3 );
		}
	}

	public function optionsInit() {
		 $this->settings_options = array(
			 'discussion'                       => array(
				 'blacklist_keys',
				 'comment_max_links',
				 'comment_moderation',
				 'comments_notify',
				 'default_comment_status',
				 'default_ping_status',
				 'default_pingback_flag',
				 'moderation_keys',
				 'moderation_notify',
				 'require_name_email',
				 'thread_comments',
				 'thread_comments_depth',
				 'show_avatars',
				 'avatar_rating',
				 'avatar_default',
				 'close_comments_for_old_posts',
				 'close_comments_days_old',
				 'show_comments_cookies_opt_in',
				 'page_comments',
				 'comments_per_page',
				 'default_comments_page',
				 'comment_order',
				 'comment_whitelist',
			 ),
			 'general'                          => array(
				 'admin_email',
				 'blogdescription',
				 'blogname',
				 'comment_registration',
				 'date_format',
				 'default_role',
				 'gmt_offset',
				 'home',
				 'siteurl',
				 'start_of_week',
				 'time_format',
				 'timezone_string',
				 'users_can_register',
			 ),
			 'links'                            => array(
				 'links_updated_date_format',
				 'links_recently_updated_prepend',
				 'links_recently_updated_append',
				 'links_recently_updated_time',
			 ),
			 'media'                            => array(
				 'thumbnail_size_w',
				 'thumbnail_size_h',
				 'thumbnail_crop',
				 'medium_size_w',
				 'medium_size_h',
				 'large_size_w',
				 'large_size_h',
				 'embed_autourls',
				 'embed_size_w',
				 'embed_size_h',
			 ),
			 'miscellaneous'                    => array(
				 'hack_file',
				 'html_type',
				 'secret',
				 'upload_path',
				 'upload_url_path',
				 'uploads_use_yearmonth_folders',
				 'use_linksupdate',
			 ),
			 'permalinks'                       => array(
				 'permalink_structure',
				 'category_base',
				 'tag_base',
			 ),
			 'privacy'                          => array(
				 'blog_public',
			 ),
			 'Reading'                          => array(
				 'blog_charset',
				 'gzipcompression',
				 'page_on_front',
				 'page_for_posts',
				 'posts_per_page',
				 'posts_per_rss',
				 'rss_language',
				 'rss_use_excerpt',
				 'show_on_front',
			 ),
			 'themes'                           => array(
				 'template',
				 'stylesheet',
			 ),
			 'writing'                          => array(
				 'default_category',
				 'default_email_category',
				 'default_link_category',
				 'default_post_edit_rows',
				 'mailserver_login',
				 'mailserver_pass',
				 'mailserver_port',
				 'mailserver_url',
				 'ping_sites',
				 'use_balanceTags',
				 'use_smilies',
				 'use_trackback',
				 'enable_app',
				 'enable_xmlrpc',
			 ),
			 'other'                            => array(
				 'advanced_edit',
				 'recently_edited',
				 'image_default_link_type',
				 'image_default_size',
				 'image_default_align',
				 'sidebars_widgets',
				 'sticky_posts',
				 'widget_categories',
				 'widget_text',
				 'widget_rss',
			 ),
			 'plugin_' . WP_LOGGER_NAME_LINLINE => array(
				 WP_LOGGER_NAME_LINLINE . '-settings',
				 WP_LOGGER_NAME_LINLINE . '-events-settings',
				 WP_LOGGER_NAME_LINLINE . '-events-email-settings',
			 ),
		 );
		 $this->settings_options = apply_filters( WP_LOGGER_NAME_LINLINE . '_modify_settings_options', $this->settings_options );
		 $this->options_exclude  = array(
			 '(.*)transient(.*)',
			 'woocommerce_queue(.*)',
			 'action_scheduler(.*)',
			 'recovery_keys',
			 'recently_activated',
			 'cron',
			 'active_plugins',
			 'rewrite_rules',
			 'theme_switched',
			 'stylesheet',
			 'template',
			 'current_theme',
			 'action_scheduler_lock_async-request-runner',
			 'woocommerce_admin_notices',
			 'recovery_mode_email_last_sent',
			 'wc_remote_inbox_notifications_stored_state',
			 'theme_mods_',
			 'https_detection_errors',
			 'woocommerce_marketplace_suggestions',
			 'fs_api_cache',
			 'elementor_remote_info_library',
			 'elementor_remote_info_feed_data',
			 'fs_accounts',
			 WP_LOGGER_NAME_LINLINE . '-session-init',
		 );
		 $this->options_exclude  = apply_filters( WP_LOGGER_NAME_LINLINE . '_modify_options_exclude', $this->options_exclude );
		 /** User exclusion in settings */
		 $excluded_options = $this->admin->getSetting( 'exclude_options' );
		 if ( ! empty( $excluded_options ) ) {
            $this->options_exclude = array_unique(
				(array) array_merge( $this->options_exclude, $excluded_options ),
				SORT_REGULAR
            );
		 }
	}
	/**
	 * Initialize this class for direct usage
	 *
	 * @return Settings
	 */
	public static function initialize(): Settings {
		return new self();
	}

	/**
	 * Fires when any option updates
	 *
	 * @param  string $option
	 * @param  mixed  $old_value
	 * @param  mixed  $value
	 * @return void
	 * @throws FileNotFoundException
	 */
	public function updateOption( string $option, $old_value, $value ) {
		/** init all options and exclusions */
		$this->optionsInit();
		if ( preg_match( '/' . implode( '|', $this->options_exclude ) . '/', $option ) ) {
			return;
		}
		if ( $old_value == $value ) {
			return;
		}

		$logData                  = array(
			'title'      => __( 'Option updated', 'lite-wp-logger' ),
			'type'       => 'option_update',
			'importance' => 'medium',
			'metas'      => array(),
		);
		$logData['metas']['desc'] = __( 'Option name: ', 'lite-wp-logger' ) . ' ' . $option;

		if ( gettype( $old_value ) != 'string' || gettype( $value ) != 'string' ) {
			$logData['message'] = __( 'Option: ', 'lite-wp-logger' ) . $option . __( ' updated', 'lite-wp-logger' ) . '.<br>' .
								  __( 'old value:', 'lite-wp-logger' ) . '<br>' . WPLogger::varLight( $old_value ) . '<br>' .
								  __( 'new value:', 'lite-wp-logger' ) . '<br>' . WPLogger::varLight( $value );
		} else {
			$logData['message']        = __( 'Option: ', 'lite-wp-logger' ) . $option . __( ' updated', 'lite-wp-logger' ) . '.<br>' .
								  __( 'old value:', 'lite-wp-logger' ) . '<br>' . $old_value . '<br>' .
								  __( 'new value:', 'lite-wp-logger' ) . '<br>' . $value;
			$logData['metas']['desc'] .= '<br>' . __( 'Old value: ', 'lite-wp-logger' ) . $old_value . '<br>' .
										 __( 'New value: ', 'lite-wp-logger' ) . $value;
		}

		if ( isset( $_GET['page'] ) ) {
			$logData['metas']['settings_page'] = sanitize_text_field( $_GET['page'] );
			$logData['metas']['settings_page'] = sanitize_text_field( $logData['metas']['settings_page'] );
		}

		/** logging if settings option */
		foreach ( $this->settings_options as $sKey => $sOptions ) {
			if ( $sKey == 'other' ) {
				continue;
			}
			if ( in_array( $option, $sOptions ) ) {
				if ( substr( $sKey, 0, 6 ) == 'plugin' ) {
					$logData['title'] = ucfirst( substr( $sKey, 7 ) ) . ' ' . __( 'Option updated', 'lite-wp-logger' );
				}

				$logData['message']       .= '<br>' . __( 'Option referer: ', 'lite-wp-logger' ) . ' settings->' .
									   esc_attr( $sKey . '->' . $option );
				$logData['metas']['desc'] .= '<br>' . __( 'Option referer: ', 'lite-wp-logger' ) . ' settings->' .
											 esc_attr( $sKey . '->' . $option );
			}
		}

		$this->logger->add( $logData, 'update_option' );
	}

}
