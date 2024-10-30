<?php
/**
 * WPLogger: User
 *
 * User class file.
 *
 * @since 1.0.0
 * @package WPLogger
 */
namespace WPLogger\Logger;

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Uses */
use UAParser\Exception\FileNotFoundException;
use WP_User;
use WPLogger\Plugin\Admin;
use WPLogger\WPLogger;

/**
 * Class User for logging
 *
 * @package WPLogger
 */
class User
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
	 * User data excludes
	 *
	 * @var string[]
	 */
	public $excludes;

    /**
     * User class constructor.
     *
     * @return void
     */
    public function __construct()
    {
	    $this->excludes = array(
		    'meta'      => array(
				'last_update',
			    '_old_metas', /** used for storing old metas for changes checking */
		    )
	    );
        $this->logger = new Logger;
        /** get events settings */
        $this->admin  = new Admin;
        $this->admin->setEventsSettingsFields();
        $this->admin->retrieveEventsSettings();
        /**
         * User actions
         */
        if ( $this->admin->getEventSetting( 'user_profile_update' ) ) {
	        add_action( 'personal_options_update', array( $this, 'beforeShowProfile' ), 10, 1 );
	        add_action( 'edit_user_profile_update', array( $this, 'beforeShowProfile' ), 10, 1 );
			add_action( 'profile_update', array( $this, 'userUpdated' ), 10, 2 );
        }
        if ( $this->admin->getEventSetting( 'user_role_change' ) )
            add_action( 'set_user_role', array( $this, 'userRoleChanged' ), 10, 3 );
        if ( $this->admin->getEventSetting( 'user_register' ) )
            add_action( 'user_register', array( $this, 'userRegister' ), 10, 1 );
        if ( $this->admin->getEventSetting( 'user_delete' ) )
            add_action( 'delete_user', array( $this, 'userDeleted' ), 10, 2 );
        if ( $this->admin->getEventSetting( 'user_super_admin' ) )
            add_action( 'grant_super_admin', array( $this, 'setSuperAdmin' ), 10, 1 );
        if ( $this->admin->getEventSetting( 'user_unsuper_admin' ) )
            add_action( 'revoke_super_admin', array( $this, 'unsetSuperAdmin' ), 10, 1 );
        if ( $this->admin->getEventSetting( 'user_spam' ) )
            add_action( 'make_spam_user', array( $this, 'spamUser' ), 10, 1 );
    }

    /**
     * Initialize this class for direct usage
     *
     * @return User
     */
    public static function initialize(): User
    {
        return new self;
    }

	/**
	 * Fires before showing user profile edit page
	 *
	 * @param  int     $user_id
	 * @return void
	 */
	public function beforeShowProfile( int $user_id )
	{
		$metas = get_user_meta( $user_id );
		if( isset( $metas['_old_metas'] ) ) unset( $metas['_old_metas'] );
		update_user_meta( $user_id, '_old_metas', $metas );
	}

	/**
	 * Fires after an existing user is updated.
	 *
	 * @param  int     $user_id
	 * @param  WP_User $old_user
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function userUpdated( int $user_id, WP_User $old_user )
    {
        $user          = get_userdata( $user_id );
	    $user_meta     = get_user_meta( $user_id );
	    $user_old_meta = unserialize( $user_meta['_old_metas'][0] );
	    unset( $user_meta['_old_metas'] );

	    unset( $user->data->user_activation_key );
        unset( $old_user->data->user_activation_key );

        if ( $user == $old_user && $user_meta == $user_old_meta ) return;

        $message = __( 'User profile updated', 'lite-wp-logger' ) . '<br>';

	    if ( $user_meta != $user_old_meta ) {
		    /** changed or added metas */
		    foreach ( $user_meta as $key => $meta ) {
			    if ( in_array( $key, $this->excludes['meta'] ) ) continue;

			    $prevMeta = ( isset( $user_old_meta[ $key ][0] ) )? $user_old_meta[ $key ][0] : false;
			    $newMeta  = $meta[0];

			    if ( empty( $newMeta ) && empty( $prevMeta ) ) continue;
			    if ( $newMeta == $prevMeta ) continue;

			    if ( empty( $prevMeta ) && ! empty( $newMeta ) ) $type = 'added';
			    else $type = 'modified';

			    $message .= '<br><b>'. __( 'User meta: ', 'lite-wp-logger' ) . '</b>' . esc_attr( $key ) . ' ' .
			                esc_attr__( $type, 'lite-wp-logger' ) . '<br>' .
			                __( 'New value: ', 'lite-wp-logger' ) . '<br>' .
			                '<div class="logger-varlight"><div class="varlight-inner">' . esc_attr( $newMeta ) . '</div></div>' .
			                __( 'Old value: ', 'lite-wp-logger' ) . '<br>' .
			                '<div class="logger-varlight"><div class="varlight-inner">' . esc_attr( $prevMeta ) . '</div></div>';
		    }
		    /** removed metas */
		    $k1     = array_keys( $user_meta );
		    $k2     = array_keys( $user_old_meta );
		    $kDiffs = array_diff( $k2, $k1 );
		    if ( ! empty( $kDiffs ) )
			    foreach ( $kDiffs as $kDiff ) {
				    if ( in_array( $kDiff, $this->excludes['meta'] ) ) continue;
				    $message .= '<br><b>'. __( 'User meta: ', 'lite-wp-logger' ) . '</b>' . esc_attr__( $key ) . ' ' .
				                __( 'removed', 'lite-wp-logger' ) . '<br>' .
				                __( 'Old value: ', 'lite-wp-logger' ) . '<br>' .
				                '<div class="logger-varlight"><div class="varlight-inner">' .
				                esc_attr( $user_old_meta[ $kDiff ][0] ) . '</div></div>';
			    }
		    $message .= '<br>';
	    }
        $message .= __( 'User data:', 'lite-wp-logger' ) . '<br>' . WPLogger::varLight( $user ) . '<br>' .
                    ( ( $user != $old_user )? __( 'Old User data:', 'lite-wp-logger' ) . '<br>' .  WPLogger::varLight( $old_user ) : '' );
        
        $this->logger->add( array(
            'title'      => __( 'User profile updated: ', 'lite-wp-logger' ) . esc_attr( $user->data->user_login ),
            'message'    => $message,
            'type'       => 'user_profile',
            'importance' => 'medium',
            'metas'      => array(
                'user_data'     => $user,
                'old_user_data' => $old_user,
	            'desc'          => __( 'User ID: ', 'lite-wp-logger' ) . esc_attr( $user_id ) . '<br>' .
	                               __( 'User name: ', 'lite-wp-logger' ) . esc_attr( $user->data->display_name ) . '<br>' .
	                               '<a target="_blank" href="' . admin_url( 'user-edit.php?user_id=' . $user_id ) . '">' .
	                               __( 'Manage user', 'lite-wp-logger' ) . '</a>',
            ),
        ), 'user_profile_update' );
    }

	/**
	 * Fires after the user’s role has changed.
	 *
	 * @param  int    $user_id
	 * @param  string $role
	 * @param  array  $old_roles
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function userRoleChanged( int $user_id, string $role, array $old_roles )
    {
        if ( empty( $old_roles ) ) return;

	    $user    = get_userdata( $user_id );

	    $message = __( 'User Roles updated', 'lite-wp-logger' ) . '.<br>' .
	               __( 'User:', 'lite-wp-logger' ) . '<br>' . WPLogger::varLight( $user ) . '<br>' .
	               __( 'New roles:', 'lite-wp-logger' ) . '<br>' . WPLogger::varLight( $user->roles ) . '<br>' .
	               __( 'Old roles:', 'lite-wp-logger' ) . '<br>' . WPLogger::varLight( $old_roles );
        
        $this->logger->add( array(
            'title'      => __( 'User Roles updated: ', 'lite-wp-logger' ) . esc_attr( $user->data->user_login ),
            'message'    => $message,
            'type'       => 'user_role',
            'importance' => 'high',
            'metas'      => array(
                'user_data' => $user,
                'desc'      => __( 'User ID: ', 'lite-wp-logger' ) . esc_attr( $user_id ) . '<br>' .
                               __( 'User name: ', 'lite-wp-logger' ) . esc_attr( $user->data->display_name ) . '<br>' .
                               '<a target="_blank" href="' . admin_url( 'user-edit.php?user_id=' . $user_id ) . '">' .
                               __( 'Manage user', 'lite-wp-logger' ) . '</a>',
            )
        ), 'user_role_change' );
    }

	/**
	 * Fires immediately after a new user is registered.
	 *
	 * @param  int $user_id
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function userRegister( int $user_id )
    {
	    $user    = get_userdata( $user_id );

	    $message = __( 'New User Registered', 'lite-wp-logger' ) . '. ' .
	               __( 'User ID: ', 'lite-wp-logger' ) . esc_attr( $user_id ) . '<br>' .
	               __( 'User data:', 'lite-wp-logger' ) . '<br>' . WPLogger::varLight( $user );
        
        $this->logger->add( array(
            'title'      => __( 'New User Registered: ', 'lite-wp-logger' ) . esc_attr( $user->data->user_login ),
            'message'    => $message,
            'type'       => 'user_new',
            'importance' => 'medium',
            'metas'      => array(
                'user_data' => $user,
                'desc'      => __( 'User ID: ', 'lite-wp-logger' ) . esc_attr( $user_id ) . '<br>' .
                               __( 'User name: ', 'lite-wp-logger' ) . esc_attr( $user->data->display_name ) . '<br>' .
                               '<a target="_blank" href="' . admin_url( 'user-edit.php?user_id=' . $user_id ) . '">' .
                               __( 'Manage user', 'lite-wp-logger' ) . '</a>',
            ),
        ), 'user_register', $user );
    }

	/**
	 * Fires immediately before a user is deleted from the database.
	 *
	 * @param  int      $user_id
	 * @param  int|null $reassign
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function userDeleted( int $user_id, $reassign )
    {
	    $user    = get_userdata( $user_id );

	    $message = __( 'User deleted: ', 'lite-wp-logger' ) . esc_attr( $user->data->user_login ) . '<br>';
        if ( $reassign )
			$message .= __( 'Posts assigned to User ID: ', 'lite-wp-logger' ) . esc_attr( $reassign ) . '<br>';
	    $message .= __( 'User data:', 'lite-wp-logger' ) . '<br>' . WPLogger::varLight( $user );
        
        $this->logger->add( array(
            'title'      => __( 'User Deleted: ', 'lite-wp-logger' ) . esc_attr( $user->data->user_login ),
            'message'    => $message,
            'type'       => 'user_delete',
            'importance' => 'high',
            'metas'      => array(
                'user_data' => $user,
                'desc'      => __( 'User ID: ', 'lite-wp-logger' ) . esc_attr( $user_id ) . '<br>' .
                               __( 'User name: ', 'lite-wp-logger' ) . esc_attr( $user->data->display_name ) . '<br>' .
                               '<a target="_blank" href="' . admin_url( 'user-edit.php?user_id=' . $user_id ) . '">' .
                               __( 'Manage user', 'lite-wp-logger' ) . '</a>',
            ),
        ), 'user_delete' );
    }

	/**
	 * Fires before the user is granted Super Admin privileges.
	 *
	 * @param  int $user_id
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function setSuperAdmin( int $user_id )
    {
	    $user    = get_userdata( $user_id );

	    $message = __( 'User set SuperAdmin: ', 'lite-wp-logger' ) . esc_attr( $user->data->user_login ) . '<br>' .
	               __( 'User data:', 'lite-wp-logger' ) . '<br>' . WPLogger::varLight( $user );
        
        $this->logger->add( array(
            'title'      => __( 'User set SuperAdmin: ', 'lite-wp-logger' ) . esc_attr( $user->data->user_login ),
            'message'    => $message,
            'type'       => 'user_superadmin',
            'importance' => 'high',
            'metas'      => array(
                'user_data' => $user,
                'desc'      => __( 'User ID: ', 'lite-wp-logger' ) . esc_attr( $user_id ) . '<br>' .
                               __( 'User name: ', 'lite-wp-logger' ) . esc_attr( $user->data->display_name ) . '<br>' .
                               '<a target="_blank" href="' . admin_url( 'user-edit.php?user_id=' . $user_id ) . '">' .
                               __( 'Manage user', 'lite-wp-logger' ) . '</a>',
            ),
        ), 'user_super_admin' );
    }

	/**
	 * Fires before the user’s Super Admin privileges are revoked.
	 *
	 * @param  int $user_id
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function unsetSuperAdmin( int $user_id )
    {
	    $user    = get_userdata( $user_id );

	    $message = __( 'User unset SuperAdmin: ', 'lite-wp-logger' ) . esc_attr( $user->data->user_login ) . '<br>' .
	               __( 'User data:', 'lite-wp-logger' ) . '<br>' . WPLogger::varLight( $user );

	    $this->logger->add( array(
            'title'      => __( 'User unset SuperAdmin: ', 'lite-wp-logger' ) . esc_attr( $user->data->user_login ),
            'message'    => $message,
            'type'       => 'user_unsuperadmin',
            'importance' => 'high',
            'metas'      => array(
                'user_data' => $user,
                'desc'      => __( 'User ID: ', 'lite-wp-logger' ) . esc_attr( $user_id ) . '<br>' .
                               __( 'User name: ', 'lite-wp-logger' ) . esc_attr( $user->data->display_name ) . '<br>' .
                               '<a target="_blank" href="' . admin_url( 'user-edit.php?user_id=' . $user_id ) . '">' .
                               __( 'Manage user', 'lite-wp-logger' ) . '</a>',
            ),
        ), 'user_unsuper_admin' );
    }

	/**
	 * Fires after the user is marked as a SPAM user.
	 *
	 * @param  int $user_id
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function spamUser( int $user_id )
    {
	    $user    = get_userdata( $user_id );

	    $message =  __( 'User spamed: ', 'lite-wp-logger' ) . esc_attr( $user->data->user_login ) . '<br>' .
                    __( 'User data:', 'lite-wp-logger' ) . '<br>' . WPLogger::varLight( $user );
        
        $this->logger->add( array(
            'title'      => __( 'User spamed: ', 'lite-wp-logger' ) . esc_attr( $user->data->user_login ),
            'message'    => $message,
            'type'       => 'user_spam',
            'importance' => 'high',
            'metas'      => array(
                'user_data' => $user,
                'desc'      => __( 'User ID: ', 'lite-wp-logger' ) . esc_attr( $user_id ) . '<br>' .
                               __( 'User name: ', 'lite-wp-logger' ) . esc_attr( $user->data->display_name ) . '<br>' .
                               '<a target="_blank" href="' . admin_url( 'user-edit.php?user_id=' . $user_id ) . '">' .
                               __( 'Manage user', 'lite-wp-logger' ) . '</a>',
            ),
        ), 'user_spam' );
    }

}