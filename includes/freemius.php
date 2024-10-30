<?php

/**
 * Loads Freemius SDK
 */
/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Initializing freemius
 *
 * @return void
 * @throws Freemius_Exception
 */
function wp_logger_fs() {
	global  $wp_logger_fs;

	if ( ! isset( $wp_logger_fs ) ) {
		require_once WP_LOGGER_DIR_PATH . 'freemius/start.php';
		$wp_logger_fs = fs_dynamic_init(
			array(
				'id'             => '11055',
				'slug'           => 'lite-wp-logger',
				'premium_slug'   => 'wp-logger-premium',
				'type'           => 'plugin',
				'public_key'     => 'pk_1dedd0030ec340557b848874d9266',
				'is_premium'     => false,
				'premium_suffix' => 'Premium',
				'has_addons'     => true,
				'has_paid_plans' => true,
				'trial'          => array(
					'days'               => 14,
					'is_require_payment' => false,
				),
				'menu'           => array(
					'slug'       => 'wplogger',
					'first-path' => 'admin.php?page=wplogger-wizard',
					'support'    => false,
				),
				'is_live'        => true,
			)
		);
	}

	return $wp_logger_fs;
}

// Init Freemius.
wp_logger_fs();
do_action( 'wp_logger_fs_loaded' );
/**
 * Drop index table after uninstall.
 * When this hook is executed, the plugin files no longer exists.
 *
 * @return void
 */
function wp_logger_uninstall() {
	global  $wpdb;
	// clean DB
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'logger_sessions' );
	$wpdb->query(
		$wpdb->prepare(
			'DELETE a, b, c, d, e, f
            FROM ' . $wpdb->base_prefix . 'posts a
            LEFT JOIN ' . $wpdb->base_prefix . 'postmeta b ON ( a.ID = b.post_id )
            LEFT JOIN ' . $wpdb->base_prefix . 'term_relationships c ON ( a.ID = c.object_id )
            LEFT JOIN ' . $wpdb->base_prefix . 'term_taxonomy d ON ( c.term_taxonomy_id = d.term_taxonomy_id )
            LEFT JOIN ' . $wpdb->base_prefix . 'terms e ON ( d.term_id = e.term_id )
            LEFT JOIN ' . $wpdb->base_prefix . "termmeta f ON ( e.term_id = f.term_id )
            WHERE post_type = 'wplogs';"
		)
	);
	// clean options
	delete_option( 'wplogger-first-init' );
	delete_option( 'wplogger-session-init' );
	delete_option( 'wplogger-settings' );
	delete_option( 'wplogger-events-settings' );
}

wp_logger_fs()->add_action( 'after_uninstall', 'wp_logger_uninstall' );
