<?php
/**
 * WPLogger: Setup
 *
 * Setup database class file.
 *
 * @since 1.0.0
 * @package WPLogger
 */
namespace WPLogger\Database;

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Uses */
use WPLogger\WPLogger;

/**
 * Class Setup
 * @package WPLogger
 */
class Setup
{
    /**
     * Preparing table tames
     * @var array|string[]
     */
    public $tables;

    /**
     * Setup constructor.
     */
    public function __construct()
    {
        $this->tables = array(
            'sessions',
        );
    }

    /**
     * Create required DB tables
     * @return bool
     */
    public function createTables()
    {
        global $wpdb;
        $prefix          = $wpdb->base_prefix . WPLogger::get_instance()->table_prefix . 'sessions';
        $charset_collate = $wpdb->get_charset_collate();
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $table_name = $prefix.$this->tables[0];
        $sql        = "CREATE TABLE IF NOT EXISTS `{$table_name}` (
	      user_id BIGINT(20) NOT NULL,
	      session_token VARCHAR(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
	      creation_time datetime NOT NULL,
	      expiry_time datetime NOT NULL,
	      ip VARCHAR(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
	      roles LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
	      sites LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
	      PRIMARY KEY (session_token)                 
	    ) $charset_collate; ";

	    $sql = esc_sql( $sql );

        dbDelta( $sql );

        return empty( $wpdb->last_error );
    }
}