<?php
/**
 * WPLogger: Plugin
 *
 * Plugin class file.
 *
 * @since 1.0.0
 * @package WPLogger
 */
namespace WPLogger\Logger;

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Uses */
use UAParser\Exception\FileNotFoundException;
use WPLogger\Plugin\Admin;
use WPLogger\WPLogger;

/**
 * Class Plugin for logging
 *
 * @package WPLogger
 */
class Plugin
{
    /**
     * For using logger class
     *
     * @var Logger
     */
    private $logger;
    /**
     * List of Plugins
     *
     * @var array
     */
    protected $old_plugins;
    /**
     * Using admin class
     *
     * @var Admin
     */
    public $admin;

    /**
     * Plugin class constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $this->old_plugins = array();
        $this->logger      = new Logger;
        $this->admin       = new Admin;
        $this->admin->setEventsSettingsFields();
        $this->admin->retrieveEventsSettings();
        /** Plugin actions */
        if ( $this->admin->getEventSetting( 'activate_plugin' ) )
            add_action('activated_plugin', array( $this, 'activatedPlugin' ), 10, 1);
        if ( $this->admin->getEventSetting( 'deactivate_plugin' ) )
            add_action('deactivated_plugin', array( $this, 'deactivatedPlugin' ), 10, 1);
        if ( $this->admin->getEventSetting( 'update_plugin' ) )
            add_action('upgrader_process_complete', array( $this, 'pluginUpdate' ), 10, 2);
        if (
            $this->admin->getEventSetting( 'install_plugin' ) ||
            $this->admin->getEventSetting( 'uninstall_plugin' )
        ) {
            add_action( 'admin_init', array( $this, 'pluginsInit' ) );
            add_action( 'shutdown', array( $this, 'pluginInstall' ) );
        }
    }

    /**
     * Initialize this class for direct usage
     *
     * @return Plugin
     */
    public static function initialize(): Plugin
    {
        return new self;
    }

	/**
	 * Fires when any plugin activated
	 *
	 * @param  string $plugin_path
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function activatedPlugin( string $plugin_path )
    {
        $plugin_file = ABSPATH . 'wp-content/plugins/' . $plugin_path;
        $plugin_data = get_plugin_data( $plugin_file );
        $message     = __( 'Plugin activated. Name: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Name'] ) .
            __( ' Version: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Version'] ) . '<br>' .
            __( 'Plugin Data:', 'lite-wp-logger' ) . '<br>' .
            WPLogger::varLight( $plugin_data );

        $this->logger->add( array(
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
        ), 'activate_plugin' );
    }

	/**
	 * Fires when any plugin deactivated
	 *
	 * @param  string $plugin_path
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function deactivatedPlugin( string $plugin_path )
    {
        $plugin_file = ABSPATH . 'wp-content/plugins/' . $plugin_path;
        $plugin_data = get_plugin_data( $plugin_file );
        $message     = __( 'Plugin deactivated. Name: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Name'] ) .
            __( ' Version: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Version'] ) . '<br>' .
            __( 'Plugin Data:', 'lite-wp-logger' ) . '<br>' .
            WPLogger::varLight( $plugin_data );

        $this->logger->add( array(
            'title'      => __( 'Plugin deactivated: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Name'] ),
            'message'    => $message,
            'type'       => 'plugin_deactivate',
            'importance' => 'high',
            'metas'      => array(
                'plugin_data' => $plugin_data,
                'desc'        => __( 'Plugin name: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Name'] ) . '<br>' .
                                 __( 'Plugin version: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Version'] ) . '<br>' .
                                 '<a target="_blank" href="' . admin_url( 'plugins.php' ) . '">' .
                                 __( 'Manage plugins', 'lite-wp-logger' ) . '</a>',
            )
        ), 'deactivate_plugin' );
    }

	/**
	 * Fires when updating progress complete
	 *
	 * @param  object $upgrader_object
	 * @param  array  $options
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function pluginUpdate( $upgrader_object, array $options )
    {
        if ( isset( $options['plugins'] ) )
            foreach ( $options['plugins'] as $plugin ) {
                $plugin_file = ABSPATH . 'wp-content/plugins/' . $plugin;
                $plugin_data = get_plugin_data( $plugin_file );
                $message     =  __( 'Plugin updated. Name: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Name'] ) .
                    __( ' Version: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Version'] ) . '<br>' .
                    __( 'Plugin Data:', 'lite-wp-logger' ) . '<br>'.
                    WPLogger::varLight( $plugin_data );

                $this->logger->add( array(
                    'title'      => __( 'Plugin updated: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Name'] ),
                    'message'    => $message,
                    'type'       => 'plugin_updated',
                    'importance' => 'medium',
                    'metas'      => array(
                        'plugin_data' => $plugin_data,
                        'desc'        => __( 'Plugin name: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Name'] ) . '<br>' .
                                         __( 'Plugin version: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Version'] ) . '<br>' .
                                         '<a target="_blank" href="' . admin_url( 'plugins.php' ) . '">' .
                                         __( 'Manage plugins', 'lite-wp-logger' ) . '</a>',
                    )
                ), 'update_plugin' );
            }
    }

    /**
     * Initializing plugins list
     *
     * @return void
     */
    public function pluginsInit()
    {
        $this->old_plugins = get_plugins();
    }

	/**
	 * Fires when Install, uninstall plugin
	 *
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function pluginInstall()
    {
	    /** Filter global arrays for security. */
        $post_array  = filter_input_array( INPUT_POST );
        $get_array   = filter_input_array( INPUT_GET );

        $action = '';
        if ( isset( $get_array['action'] ) && '-1' != $get_array['action'] )
            $action = $get_array['action'];
        elseif ( isset( $post_array['action'] ) && '-1' != $post_array['action'] )
            $action = $post_array['action'];

        if ( isset( $get_array['action2']) && '-1' != $get_array['action2'] )
            $action = $get_array['action2'];
        elseif ( isset( $post_array['action2'] ) && '-1' != $post_array['action2'] )
            $action = $post_array['action2'];

        /** Install plugin */
        if ( $this->admin->getEventSetting( 'install_plugin' ) ) {
            if ( in_array( $action, array( 'install-plugin', 'upload-plugin', 'run_addon_install' ) )
                && current_user_can( 'install_plugins' ) ) {
                $plugins     = array_values( array_diff( array_keys( get_plugins() ), array_keys( $this->old_plugins ) ) );
                $plugin_list = get_plugins();
                if ( $plugins[0] )
                    foreach ( $plugins as $plugin ) {
                        $plugin_path = $plugin;
                        $plugin_data = $plugin_list[ $plugin_path ];
                        $message     = __( 'Plugin installed. Name: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Name'] ) .
                            __( ' Version: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Version'] ) . '<br>' .
                            __( 'Plugin Data:', 'lite-wp-logger' ) . '<br>' .
                            WPLogger::varLight( $plugin_data );

                        $this->logger->add( array(
                            'title'      => __( 'Plugin installed: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Name'] ),
                            'message'    => $message,
                            'type'       => 'plugin_install',
                            'importance' => 'high',
                            'metas'      => array(
                                'plugin_data' => $plugin_data,
                                'desc'        => __( 'Plugin name: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Name'] ) . '<br>' .
                                                 __( 'Plugin version: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Version'] ) . '<br>' .
                                                 '<a target="_blank" href="' . admin_url( 'plugins.php' ) . '">' .
                                                 __( 'Manage plugins', 'lite-wp-logger' ) . '</a>',
                            )
                        ), 'install_plugin' );
                    }
                return;
            }
        }
        /** Uninstall plugin */
        if ( $this->admin->getEventSetting( 'uninstall_plugin' ) ) {
            if ( $action == 'delete-plugin' && current_user_can( 'delete_plugins' ) ) {
                if ( isset( $post_array['plugin'] ) ) {
                    $plugin_data = $this->old_plugins[ $post_array['plugin'] ];
                    $message     = __( 'Plugin uninstalled. Name: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Name'] ) .
                        __( ' Version: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Version'] ) . '<br>' .
                        __( 'Plugin Data:', 'lite-wp-logger' ) . '<br>' .
                        WPLogger::varLight( $plugin_data );

                    $this->logger->add( array(
                        'title'      => __( 'Plugin uninstalled: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Name'] ),
                        'message'    => $message,
                        'type'       => 'plugin_uninstall',
                        'importance' => 'high',
                        'metas'      => array(
                            'plugin_data' => $plugin_data,
                            'desc'        => __( 'Plugin name: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Name'] ) . '<br>' .
                                             __( 'Plugin version: ', 'lite-wp-logger' ) . esc_attr( $plugin_data['Version'] ) . '<br>' .
                                             '<a target="_blank" href="' . admin_url( 'plugins.php' ) . '">' .
                                             __( 'Manage plugins', 'lite-wp-logger' ) . '</a>',
                        )
                    ), 'uninstall_plugin' );
                }
            }
        }
    }

}