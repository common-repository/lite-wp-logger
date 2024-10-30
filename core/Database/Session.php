<?php
/**
 * WPLogger: Session
 *
 * Session class file.
 *
 * @since 1.0.0
 * @package WPLogger
 */
namespace WPLogger\Database;

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Uses */
use WPLogger\WPLogger;
use WP_Session_Tokens;
use wpdb;

/**
 * Class Session
 * @package WPLogger
 */
class Session
{
    /**
     * Defining table name
     * @var string
     */
    public $table;
    /**
     * For using wpdb
     * @var wpdb
     */
    public $db;

    /**
     *  Session constructor.
     */
    public function __construct()
    {
        global $wpdb;
        $this->table = 'sessions';
        $this->db    = $wpdb;
        $this->table = $wpdb->base_prefix . WPLogger::get_instance()->table_prefix . $this->table;
    }

    /**
     * Initialize this class for direct usage
     * @return Session
     */
    public static function initialize(): Session
    {
        return new self;
    }

    /**
     * For adding new user active session
     *
     * @param  int    $user_id
     * @param  string $session_token
     * @param  string $expiry_time
     * @param  string $ip
     * @param  array  $roles
     * @param  string $sites
     * @return bool|int
     */
    public function add( int $user_id, string $session_token, string $expiry_time, string $ip, array $roles, string $sites = '' )
    {
        return $this->db->replace($this->table, array(
            'user_id'       => $user_id,
            'session_token' => $session_token,
            'creation_time' => current_time( 'mysql' ),
            'expiry_time'   => $expiry_time,
            'ip'            => $ip,
            'roles'         => json_encode( $roles ),
            'sites'         => $sites,
        ));
    }

    /**
     * For deleting session by ID
     *
     * @param  int $user_id
     * @return bool|int
     */
    public function remove( int $user_id )
    {
        $this->db->query( esc_sql( "DELETE FROM wp_usermeta WHERE meta_key='session_tokens' AND user_id=" . $user_id . ";" ) );
        $wp_sessions = WP_Session_Tokens::get_instance( $user_id );
        $wp_sessions->destroy_all();
        return $this->db->delete( $this->table, array(
            'user_id' => $user_id,
        ));
    }

    /**
     * For deleting bulk sessions
     *
     * @param  array $user_ids
     * @return bool|int
     */
    public function removeBulk( array $user_ids )
    {
        foreach ( $user_ids as $user_id ) {
            $wp_sessions = WP_Session_Tokens::get_instance( $user_id );
            $wp_sessions->destroy_all();
        }
        $user_ids = implode( ',', array_map( 'absint', $user_ids ) );
        $this->db->query( esc_sql( "DELETE FROM wp_usermeta WHERE meta_key='session_tokens' AND user_id IN(" . $user_ids . ");" ) );
        return $this->db->query( esc_sql( 'DELETE FROM ' . $this->table . ' WHERE user_id IN(' . $user_ids . ')' ) );
    }

    /**
     * For getting session by ID
     *
     * @param  int $id
     * @return array|object|null
     */
    public function getByID( int $id )
    {
        return $this->db->get_results( esc_sql("SELECT * FROM " . $this->table . " WHERE id = " . $id . ";" ) );
    }

    /**
     * For getting session by User
     *
     * @param  int $userID
     * @return array|object|null
     */
    public function getByUser( int $userID )
    {
        return $this->db->get_results( esc_sql( "SELECT * FROM " . $this->table . " WHERE user_id = " . $userID . ";" ) );
    }

    /**
     * For getting all active sessions
     * @return array|object|null
     */
    public function getAll()
    {
        return $this->db->get_results( esc_sql( "SELECT  * FROM " . $this->table .
            " WHERE expiry_time >= '" . current_time( 'mysql' ) . "' GROUP BY user_id;" ) );
    }
}
