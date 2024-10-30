<?php
/**
 * WPLogger: Wizard page
 *
 * Used for admin wizard page backend
 *
 * @since 1.0.0
 * @package WPLogger
 */
namespace WPLogger\Plugin;

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Wizard
 *
 * @package WPLogger
 */
class Wizard
{
    /**
     * Wizard constructor.
     *
     * @return void
     */
    public function __construct()
    {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueues' ) );
        /** save wizard */
        add_action( 'wp_ajax_' . WP_LOGGER_NAME_LINLINE . '_saveWizard', array( $this, 'saveWizard' ) );
    }

    /**
     * Initialize this class for direct usage
     *
     * @return Wizard
     */
    public static function initialize(): Wizard
    {
        return new self;
    }

    /**
     * Including assets for wizard page
     *
     * @param  string $suffix
     * @return void
     */
    public function enqueues( string $suffix )
    {
        if( $suffix != 'admin_page_' . WP_LOGGER_NAME_LINLINE . '-wizard' ) return;
        
        $adminAssets = 'assets/admin/';
	    /** styles */
	    wp_enqueue_style( WP_LOGGER_NAME . '-bootstrap', WP_LOGGER_DIR_URL . $adminAssets . 'css/global/bootstrap.css', array(), WP_LOGGER_VERSION );
	    wp_enqueue_style( WP_LOGGER_NAME . '-mdi', WP_LOGGER_DIR_URL . $adminAssets . 'css/global/mdi.css', array(), WP_LOGGER_VERSION );
        wp_enqueue_style( WP_LOGGER_NAME . '-wizard-base', WP_LOGGER_DIR_URL . $adminAssets . 'css/wizard/base.css', array(), WP_LOGGER_VERSION );
        wp_enqueue_style( WP_LOGGER_NAME . '-wizard-style', WP_LOGGER_DIR_URL . $adminAssets . 'css/wizard/style.css', array(), WP_LOGGER_VERSION );
	    /** scripts */
        wp_enqueue_script( WP_LOGGER_NAME . '-jquery-steps', WP_LOGGER_DIR_URL . $adminAssets . 'js/global/jquery.steps.js', array( 'jquery' ), WP_LOGGER_VERSION, true );
        wp_enqueue_script( WP_LOGGER_NAME . '-wizard-scripts', WP_LOGGER_DIR_URL . $adminAssets . 'js/wizard/script.js', array( 'jquery' ), WP_LOGGER_VERSION, true );
	    $wizard_translations = array(
		    'loading'                               => __( 'Loading ...', 'lite-wp-logger' ),
		    'previous'                              => __( 'Previous', 'lite-wp-logger' ),
		    'skip_wizard'                           => __( 'Skip Wizard', 'lite-wp-logger' ),
		    'next'                                  => __( 'Next', 'lite-wp-logger' ),
		    'finish'                                => __( 'Finish', 'lite-wp-logger' ),
		    'something_went_wrong_please_try_again' => __( 'Something went wrong, Please try again!', 'lite-wp-logger' ),
	    );
		wp_localize_script( WP_LOGGER_NAME . '-wizard-scripts', 'wizard_vars', array(
            'url'         => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( WP_LOGGER_NAME . '-ajax' ),
            'plugin_name' => WP_LOGGER_NAME_LINLINE,
            'admin_url'   => admin_url( 'admin.php?page=wplogger-reports' ),
            'translations' => $wizard_translations,
		) );
    }

    /**
     * For checking plugin admin ajax nonce
     *
     * @return void
     */
    private function nonce()
    {
	    if (
		    ! isset( $_POST['nonce'] ) || !
		    wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), WP_LOGGER_NAME . '-ajax' )
	    )
		    wp_die( 'Nonce died :)' );
    }

    /**
     * For ajax saving wizard
     *
     * @return void
     */
    public function saveWizard()
    {
        $this->nonce();

        $plugin_admin = new Admin;
        $plugin_admin->retrieveSettings();
        $plugin_admin->retrieveEventsSettings();

        $plugin_settings = $plugin_admin->settings;
        $events_settings = $plugin_admin->events_settings;

        $logs_expire = $plugin_admin->getSetting( 'logs_expire' );
        $plugin_settings['logs_expire'] = ( isset( $_POST['logs_expire'] ) && is_numeric( $_POST['logs_expire'] ) )?
	        sanitize_text_field( $_POST['logs_expire'] ) : $logs_expire;

        $event_login = ( $plugin_admin->getEventSetting( 'login' ) )? "1":"0";
        $events_settings['login'] = ( isset( $_POST['event_login'] ) && is_numeric( $_POST['event_login'] ) )?
	        sanitize_text_field( $_POST['event_login'] ) : $event_login;

        $event_session = ( $plugin_admin->getEventSetting( 'session' ) )? "1":"0";
        $events_settings['session'] = ( isset( $_POST['event_session'] ) && is_numeric( $_POST['event_session'] ) )?
	        sanitize_text_field( $_POST['event_session'] ) : $event_session;

        $event_new_post = ( $plugin_admin->getEventSetting( 'new_post' ) )? "1":"0";
        $events_settings['new_post'] = ( isset( $_POST['event_new_post'] ) && is_numeric( $_POST['event_new_post'] ) )?
	        sanitize_text_field( $_POST['event_new_post'] ) : $event_new_post;

        $event_delete_post = ( $plugin_admin->getEventSetting( 'delete_post' ) )? "1":"0";
        $events_settings['delete_post']  = ( isset( $_POST['event_delete_post'] ) && is_numeric( $_POST['event_delete_post'] ) )?
	        sanitize_text_field( $_POST['event_delete_post'] ) : $event_delete_post;

        update_option( WP_LOGGER_NAME_LINLINE . '-settings', $plugin_settings );
        update_option( WP_LOGGER_NAME_LINLINE . '-events-settings', $events_settings );

        wp_send_json( true );

        exit();
    }

}