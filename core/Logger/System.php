<?php
/**
 * WPLogger: System
 *
 * System class file.
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
 * Class System for logging
 * @package WPLogger
 */
class System
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
     * System class constructor.
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
        /** System actions */
        if ( $this->admin->getEventSetting( 'core_update' ) ){
	        add_action( '_core_updated_successfully', array( $this, 'coreUpdate' ), 10, 1 );
	        add_action( 'automatic_updates_complete', array( $this, 'coreAutoUpdate' ), 10, 1 );
        }
    }

    /**
     * Initialize this class for direct usage
     *
     * @return System
     */
    public static function initialize(): System
    {
        return new self;
    }

	/**
	 * WordPress core update.
	 *
	 * @param  string $wp_version
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function coreUpdate( string $wp_version )
    {
        $message = __( 'WP core updated to ', 'lite-wp-logger' ) . esc_attr( $wp_version );

        $this->logger->add( array(
            'title'      => __( 'WP core updated', 'lite-wp-logger' ),
            'message'    => $message,
            'type'       => 'core_update',
            'importance' => 'high',
            'metas'      => array(
                'desc' => __( 'Updated version: ', 'lite-wp-logger' )  . esc_attr( $wp_version ) . '<br>' .
                          '<a target="_blank" href="' . admin_url( 'update-core.php' ) . '">' .
                          __( 'Admin updates', 'lite-wp-logger' ) . '</a>',
            ),
        ), 'core_update' );
    }

	/**
	 * WordPress auto core update.
	 *
	 * @param  array $automatic
	 * @return void
	 * @throws FileNotFoundException
	 */
	public function coreAutoUpdate( array $automatic )
	{
		if ( isset( $automatic['core'][0] ) ){
			$obj         = $automatic['core'][0];
			$old_version = get_bloginfo( 'version' );

			$message = __( 'WP core updated from ', 'lite-wp-logger' ) . esc_attr( $old_version ) .
			           __( ' to ', 'lite-wp-logger' ) . esc_attr( $obj->item->version ) . '<br>' .
			           __( 'System Data:', 'lite-wp-logger' ) . '<br>' .
			           WPLogger::varLight( $automatic );

			$this->logger->add( array(
				'title'      => __( 'WP core auto update', 'lite-wp-logger' ),
				'message'    => $message,
				'type'       => 'core_update',
				'importance' => 'high',
				'metas'      => array(
					'system_data' => $automatic,
					'desc'        => __( 'Previous version: ', 'lite-wp-logger' )  . esc_attr( $old_version ) . '<br>' .
					                 __( 'Updated version: ', 'lite-wp-logger' )  . esc_attr( $obj->item->version ) . '<br>' .
					                 '<a target="_blank" href="' . admin_url( 'update-core.php' ) . '">' .
					                 __( 'Admin updates', 'lite-wp-logger' ) . '</a>',
				),
			), 'core_update' );
		}
	}

}