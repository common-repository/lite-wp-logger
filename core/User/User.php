<?php
/**
 * WPLogger: User
 *
 * User class file.
 *
 * @since 1.0.0
 * @package WPLogger
 */
namespace WPLogger\User;

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Uses */
use UAParser\Exception\FileNotFoundException;
use WPLogger\Database\Session;
use WPLogger\Logger\Logger;
use WP_User;
use WP_Error;
use WPLogger\Plugin\Admin;
use WPLogger\WPLogger;

/**
 * Class User
 *
 * @package WPLogger
 */
class User
{
    /**
     * Used for using session class
     *
     * @var Session
     */
    public $session;
    /**
     * Stores current user object
     *
     * @var WP_User
     */
    public $current_user;
    /**
     * Using the logger
     *
     * @var Logger
     */
    public $logger;
    /**
     * Using admin class
     *
     * @var Admin
     */
    public $admin;

    /**
     * User constructor.
     *
     * @param  WP_User|null $user
     * @return void
     */
    public function __construct( WP_User $user = null )
    {
	    $this->current_user = ( $user )? : wp_get_current_user();
    }

    /**
     * Initialize this class for direct usage
     *
     * @return void
     */
    public static function initialize(): User
    {
        return new self;
    }

	/**
	 * Setup user actions
	 *
	 * @return void
	 */
	public function setup()
	{
		$this->logger       = new Logger;
		$this->session      = new Session;
		/** get events settings */
		$this->admin        = new Admin;
		$this->admin->setEventsSettingsFields();
		$this->admin->retrieveEventsSettings();
		/**  Action for login */
		if ( $this->admin->getEventSetting( 'login' ) )
			add_action( 'set_auth_cookie', array( $this, 'login' ), 10, 6 );
		elseif ( $this->admin->getEventSetting( 'session' ) )
			add_action( 'set_auth_cookie', array( $this, 'onlySession' ), 10, 6 );
		/** Actions for logout */
		if ( $this->admin->getEventSetting( 'logout' ) ) {
			add_action( 'wp_logout', array( $this, 'logout' ), 5 );
			add_action( 'clear_auth_cookie', array( $this, 'beforeLogout' ), 10 );
		}
		/** Action for login failed */
		if ( $this->admin->getEventSetting( 'login_fail' ) )
			add_action( 'wp_login_failed', array( $this, 'loginFail' ), 10, 2 );
		/** Actions for password reset */
		if ( $this->admin->getEventSetting( 'reset_pass' ) ) {
			add_action( 'password_reset', array( $this, 'passwordReset' ), 10, 2 );
			add_action( 'lostpassword_post', array( $this, 'lostPassword' ), 10, 2 );
		}
	}

	/**
	 * For logging user login in admin panel
	 *
	 * @param  string $auth_cookie
	 * @param  int    $expire
	 * @param  int    $expiration
	 * @param  int    $user_id
	 * @param  string $scheme
	 * @param  string $token
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function login( string $auth_cookie, int $expire, int $expiration, int $user_id, string $scheme, string $token )
    {
        $user = get_userdata( $user_id );
        /** check if user is an object */
        if ( ! $user instanceof WP_User ) return;
        $expiration_dt = date( 'Y-m-d H:i:s', $expiration );

        if ( $this->admin->getEventSetting( 'session' ) )
            $this->session->add(
                $user_id,
                $token,
                $expiration_dt,
                $this->getUserIp(),
                $user->roles
            );

	    if ( $this->admin->getEventSetting( 'user_profile_update' ) ) {
		    $metas = get_user_meta( $user_id );
		    if( isset( $metas['_old_metas'] ) ) unset( $metas['_old_metas'] );
		    update_user_meta( $user_id, '_old_metas', $metas );
	    }

        $this->logger->add( array(
            'title'      => __( 'User logged in', 'lite-wp-logger' ),
            'message'    => __( 'User logged in ', 'lite-wp-logger' ) . '<br>' . WPLogger::varLight( $user ),
            'type'       => 'login_login',
            'importance' => 'low',
            'metas'      => array(
                'user_data' => $user,
                'desc'      => __( 'Login expire: ', 'lite-wp-logger' ) . date( 'd M Y, H:i:s', $expiration ) . '<br>' .
                               __( 'User ID: ', 'lite-wp-logger' ) . esc_attr( $user_id ) . '<br>' .
                               __( 'User name: ', 'lite-wp-logger' ) . esc_attr( $user->data->display_name ) . '<br>' .
                               '<a target="_blank" href="' . admin_url( 'user-edit.php?user_id=' . $user_id ) . '">' .
                               __( 'Manage user', 'lite-wp-logger' ) . '</a>',
            )
        ), 'login', $user );
    }

    /**
     * For adding only user online session
     *
     * @param  string $auth_cookie
     * @param  int    $expire
     * @param  int    $expiration
     * @param  int    $user_id
     * @param  string $scheme
     * @param  string $token
     * @return void
     */
    public function onlySession( string $auth_cookie, int $expire, int $expiration, int $user_id, string $scheme, string $token )
    {
        $user = get_userdata( $user_id );
        /** check if user is an object */
        if ( ! $user instanceof WP_User ) return;
        $expiration_dt = date( 'Y-m-d H:i:s', $expiration );

        $this->session->add(
            $user_id,
            $token,
            $expiration_dt,
            $this->getUserIp(),
            $user->roles
        );
    }

    /**
     * For setting current user before logout
     *
     * @return void
     */
    public function beforeLogout()
    {
        $this->current_user = wp_get_current_user();
    }

	/**
	 * For removing user session and logging after logout
	 *
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function logout()
    {
        $message = __( 'User logged out.', 'lite-wp-logger' ) . ' <br>' .
            __( 'User:', 'lite-wp-logger' ) . '<br>' . WPLogger::varLight( $this->current_user );

        $this->logger->add( array(
            'title'      => __( 'User logged out', 'lite-wp-logger' ),
            'message'    => $message,
            'type'       => 'login_logout',
            'importance' => 'low',
            'metas'      => array(
                'user_data' => $this->current_user,
	            'desc'      => __( 'User ID: ', 'lite-wp-logger' ) . esc_attr( $this->current_user->ID ) . '<br>' .
	                           __( 'User name: ', 'lite-wp-logger' ) . esc_attr( $this->current_user->data->display_name ) . '<br>' .
	                           '<a target="_blank" href="' . admin_url( 'user-edit.php?user_id=' . $this->current_user->ID ) . '">' .
	                           __( 'Manage user', 'lite-wp-logger' ) . '</a>',
            ),
        ), 'logout', $this->current_user );

        $this->session->remove( $this->current_user->ID );
    }

	/**
	 * For logging login failed if user exist
	 *
	 * @param  string   $username
	 * @param  WP_Error $error
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function loginFail( string $username, WP_Error $error )
    {
        $username = sanitize_user( $username );
        $user = get_user_by( 'login', $username );
        if ( empty( $user ) ) $user = get_user_by( 'email', $username );
        if ( empty( $user ) ) {
            $user = new WP_User;
            $user->data->user_login = $username;
        }

        if( $user )
            $this->logger->add( array(
                'title'      => __( 'User login failed', 'lite-wp-logger' ),
                'message'    => __( 'WP error: ', 'lite-wp-logger' ) . ' <br>' . WPLogger::varLight( $error ),
                'type'       => 'login_fail',
                'importance' => 'urgent',
                'metas'      => array(
                    'user_data' => $user,
                    'desc'      => esc_attr( $error->get_error_message() ),
                )
            ), 'login_fail', $user );
    }

	/**
	 *  For logging user password reset
	 *
	 * @param  WP_User $user
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function passwordReset( WP_User $user )
    {
        $message =__( 'User reset password', 'lite-wp-logger' ) . '<br>' .
                  __( 'User:', 'lite-wp-logger' ) . '<br>' . WPLogger::varLight( $user );

        $this->logger->add( array(
            'title'      => __( 'User password reset', 'lite-wp-logger' ),
            'message'    => $message,
            'type'       => 'login_reset',
            'importance' => 'medium',
            'metas'      => array(
                'user_data' => $user,
                'desc'      => __( 'User ID: ', 'lite-wp-logger' ) . esc_attr( $user->ID ) . '<br>' .
                               __( 'User name: ', 'lite-wp-logger' ) . esc_attr( $user->data->display_name ) . '<br>' .
                               '<a target="_blank" href="' . admin_url( 'user-edit.php?user_id=' . $user->ID ) . '">' .
                               __( 'Manage user', 'lite-wp-logger' ) . '</a>',
            )
        ), 'reset_pass', $user );
    }

	/**
	 * For logging user lost password request
	 *
	 * @param  WP_Error $errors
	 * @param  WP_User  $user
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function lostPassword( WP_Error $errors, WP_User $user )
    {
        $message = __( 'User requested for password lost.', 'lite-wp-logger' ) . ' <br>' .
                   __( 'WP error: ', 'lite-wp-logger' ) . '<br>' . WPLogger::varLight( $errors ). '<br>' .
                   __( 'User:', 'lite-wp-logger' ) . '<br>' . WPLogger::varLight( $user );

        $this->logger->add( array(
            'title'      => __( 'User lost password request', 'lite-wp-logger' ),
            'message'    => $message,
            'type'       => 'login_lost',
            'importance' => 'medium',
            'metas'      => array(
                'user_data' => $user,
                'desc'      => $errors->get_error_message() . '<br>' .
                               __( 'User ID: ', 'lite-wp-logger' ) . esc_attr( $user->ID ) . '<br>' .
                               __( 'User name: ', 'lite-wp-logger' ) . esc_attr( $user->data->display_name ) . '<br>' .
                               '<a target="_blank" href="' . admin_url( 'user-edit.php?user_id=' . $user->ID ) . '">' .
                               __( 'Manage user', 'lite-wp-logger' ) . '</a>',
            )
        ), 'reset_pass', $user );
    }

    /**
     * Getting current user IP address
     *
     * @return string | void
     */
    public function getUserIp()
    {
        $fields = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        );

        foreach ( $fields as $ip_field )
            if ( ! empty( $_SERVER[ $ip_field ] ) )
                return ( $_SERVER[ $ip_field ] != '::1' )? sanitize_text_field( $_SERVER[ $ip_field ] ) : '127.0.0.1';

        return null;
    }

}