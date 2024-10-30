<?php
/**
 * WPLogger: Theme
 *
 * Theme class file.
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
 * Class Theme for logging
 *
 * @package WPLogger
 */
class Theme
{
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
     * Theme class constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $this->logger = new Logger;
        /** get events settings */
        $this->admin  = new Admin;
        $this->admin->setEventsSettingsFields();
        $this->admin->retrieveEventsSettings();
        /**
         * Theme actions
         */
        if ( $this->admin->getEventSetting( 'change_theme' ) )
            add_action( 'update_option_stylesheet', array( $this, 'changeThemes' ), 10, 3 );
        if ( $this->admin->getEventSetting( 'update_theme' ) )
            add_action( 'upgrader_process_complete', array( $this, 'themeUpdate' ), 10, 2 );
        if (
            $this->admin->getEventSetting( 'install_theme' ) ||
            $this->admin->getEventSetting( 'uninstall_theme' )
        ) {
            add_action( 'update_option__site_transient_theme_roots', array( $this, 'checkThemes' ), 10, 3 );
        }
    }

    /**
     * Initialize this class for direct usage
     *
     * @return Theme
     */
    public static function initialize(): Theme
    {
        return new self;
    }

	/**
	 * Fires if any theme add or remove
	 *
	 * @param  mixed $old_value
	 * @param  mixed $value
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function checkThemes( $old_value, $value )
    {
        $themes_up   = array_diff_key( $value, $old_value );
        $themes_down = array_diff_key( $old_value, $value );

        if ( ! empty( $themes_up ) ) {
            if ( $this->admin->getEventSetting( 'install_theme' ) ) {
                reset( $themes_up );
                $theme_key = key( $themes_up );
                $theme     = wp_get_theme( $theme_key );
                $message   = __( 'New theme installed', 'lite-wp-logger' ) .
                             '. <br>' . WPLogger::varLight( $theme );

                $this->logger->add( array(
                    'title'      => __( 'New theme installed', 'lite-wp-logger' ) .
                        ': ' . $theme_key,
                    'message'    => $message,
                    'type'       => 'theme_install',
                    'importance' => 'high',
                    'metas'      => array(
                        'theme_data' => $theme,
                        'desc'       => __( 'Theme:', 'lite-wp-logger' ) . ' ' . esc_attr( $theme_key ) . '<br>' .
                                        __( 'Version:', 'lite-wp-logger' ) . ' ' . esc_attr( $theme->get( 'Version' ) ) .
                                        '<br>' . '<a target="_blank" href="' . admin_url( 'themes.php' ) . '">' .
                                        __( 'Mange themes', 'lite-wp-logger' ) . '</a>',
                    )
                ), 'install_theme' );
            }
        } elseif ( ! empty( $themes_down ) ) {
            if ( $this->admin->getEventSetting( 'uninstall_theme' ) ) {
                reset( $themes_down );
                $theme_key = key( $themes_down );
                $theme     = wp_get_theme( $theme_key );
                $message   = __( 'Theme deleted', 'lite-wp-logger' ) . '. <br>' . WPLogger::varLight( $theme );
                $this->logger->add( array(
                    'title'      => __( 'Theme deleted', 'lite-wp-logger' ) .
                        ': ' . $theme_key,
                    'message'    => $message,
                    'type'       => 'theme_delete',
                    'importance' => 'high',
                    'metas'      => array(
                        'theme_data' => $theme,
                        'desc'       => __( 'Theme:', 'lite-wp-logger' ) . ' ' . esc_attr( $theme_key ) . '<br>' .
                                        __( 'Version:', 'lite-wp-logger' ) . ' ' . esc_attr( $theme->get( 'Version' ) ) .
                                        '<br>' . '<a target="_blank" href="' . admin_url( 'themes.php' ) . '">' .
                                        __( 'Mange themes', 'lite-wp-logger' ) . '</a>',
                    )
                ), 'uninstall_theme' );
            }
        }
    }

	/**
	 * Fires if theme changed
	 *
	 * @param  string $old_value
	 * @param  string $value
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function changeThemes( string $old_value, string $value )
    {
        if ( $old_value == $value ) return;
        $old_theme = wp_get_theme( $old_value );
        $theme     = wp_get_theme( $value );

        $message = __( 'Theme changed from ', 'lite-wp-logger' ) . esc_attr( $old_value ) .
                   __( ' to ', 'lite-wp-logger' ) . esc_attr( $value ) . '<br>' .
                   __( 'Old Theme:', 'lite-wp-logger' ) . '<br>' . WPLogger::varLight( $old_theme ) . '<br>' .
                   __( 'New Theme:', 'lite-wp-logger' ) . '<br>' . WPLogger::varLight( $theme );

        $this->logger->add( array(
            'title'      => __( 'Theme changed:', 'lite-wp-logger' ) . ' ' . $value,
            'message'    => $message,
            'type'       => 'theme_change',
            'importance' => 'high',
            'metas'      => array(
                'theme_data' => $theme,
	            'desc'        => __( 'Old Theme:', 'lite-wp-logger' ) . ' ' . esc_attr( $old_value ) . '<br>' .
	                             __( 'New Theme:', 'lite-wp-logger' ) . ' ' . esc_attr( $value ) . '<br>' .
	                             __( 'Version:', 'lite-wp-logger' ) . ' ' . esc_attr( $theme->get( 'Version' ) ) . '<br>' .
	                             '<a target="_blank" href="' . admin_url( 'themes.php' ) . '">' .
	                             __( 'Mange themes', 'lite-wp-logger' ) . '</a>',
            ),
        ), 'change_theme' );
    }

	/**
	 * Fires when updating progress complete
	 *
	 * @param  object $upgrader_object
	 * @param  array  $options
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function themeUpdate( $upgrader_object, array $options )
    {
        if ( isset( $options['themes'] ) )
            foreach ( $options['themes'] as $theme_name ) {
                $theme   = wp_get_theme( $theme_name );
                $message =  __( 'Theme updated. Name: ', 'lite-wp-logger' ) . esc_attr( $theme_name ) . '<br>' . WPLogger::varLight( $theme );

                $this->logger->add( array(
                    'title'      => __( 'Theme updated:', 'lite-wp-logger' ) . ' ' . esc_attr( $theme_name ),
                    'message'    => $message,
                    'type'       => 'theme_updated',
                    'importance' => 'medium',
                    'metas'      => array(
                        'theme_data' => $theme,
                        'desc'        => __( 'Theme:', 'lite-wp-logger' ) . ' ' . esc_attr( $theme_name ) . '<br>' .
                                         __( 'Version:', 'lite-wp-logger' ) . ' ' . esc_attr( $theme->get( 'Version' ) ) . '<br>' .
                                         '<a target="_blank" href="' . admin_url( 'themes.php' ) . '">' .
                                         __( 'Mange themes', 'lite-wp-logger' ) . '</a>',
                    )
                ), 'update_theme' );
            }
    }

}