<?php
/**
 * WPLogger: Report page
 *
 * Used for admin report page backend
 *
 * @since 1.0.0
 * @package WPLogger
 */
namespace WPLogger\Plugin;

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Uses */
use WPLogger\Walker\jsonTermWalker;
use WPLogger\Database\Session;
use WP_Query;
use WP_User_Query;

/**
 * Class Report
 *
 * @package WPLogger
 */
class Report
{
    /**
     * Name used for slug and taxonomies
     *
     * @var string
     */
    public $slug = 'wplog';
    /**
     * Using admin class
     *
     * @var Admin
     */
    public $admin;

    /**
     * Report constructor.
     *
     * @return void
     */
    public function __construct()
    {
        /** get settings */
        $this->admin = new Admin;
        $this->admin->setSettingsFields();
        $this->admin->retrieveSettings();
	    /** enqueue */
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueues' ) );
        add_filter('script_loader_tag', array( $this, 'addTypeModule' ), 10, 3 );
	    /** notify new log */
        add_filter('heartbeat_received', array( $this, 'checkNotify'), 10, 2 );
	    /** home */
        add_action( 'wp_ajax_' . WP_LOGGER_NAME_LINLINE . '_getReportCards', array( $this, 'getReportCards' ) );
        add_action( 'wp_ajax_' . WP_LOGGER_NAME_LINLINE . '_getLogTopTypes', array( $this, 'getLogTopTypes' ) );
	    /** online users */
        add_action( 'wp_ajax_' . WP_LOGGER_NAME_LINLINE . '_getSessions', array( $this, 'getSessions' ) );
        add_action( 'wp_ajax_' . WP_LOGGER_NAME_LINLINE . '_getCurrentUser', array( $this, 'getCurrentUser' ) );
        add_action( 'wp_ajax_' . WP_LOGGER_NAME_LINLINE . '_removeSession', array( $this, 'removeSession' ) );
        add_action( 'wp_ajax_' . WP_LOGGER_NAME_LINLINE . '_removeAllSessions', array( $this, 'removeAllSessions' ) );
	    /** logs */
        add_action( 'wp_ajax_' . WP_LOGGER_NAME_LINLINE . '_getLastLogs', array( $this, 'getLastLogs' ) );
        add_action( 'wp_ajax_' . WP_LOGGER_NAME_LINLINE . '_getUsers', array( $this, 'getUsers' ) );
        add_action( 'wp_ajax_' . WP_LOGGER_NAME_LINLINE . '_getLogTypes', array( $this, 'getLogTypes' ) );
        add_action( 'wp_ajax_' . WP_LOGGER_NAME_LINLINE . '_getLogImportance', array( $this, 'getLogImportance' ) );
        add_action( 'wp_ajax_' . WP_LOGGER_NAME_LINLINE . '_getUserRoles', array( $this, 'getUserRoles' ) );
	    /** settings */
        add_action( 'wp_ajax_' . WP_LOGGER_NAME_LINLINE . '_getPostTypes', array( $this, 'getPostTypes' ) );
	    add_action( 'wp_ajax_' . WP_LOGGER_NAME_LINLINE . '_checkValidIp', array( $this, 'checkValidIp' ) );
	    add_action( 'wp_ajax_' . WP_LOGGER_NAME_LINLINE . '_checkValidEmail', array( $this, 'checkValidEmail' ) );
	    add_action( 'wp_ajax_' . WP_LOGGER_NAME_LINLINE . '_checkValidOption', array( $this, 'checkValidOption' ) );
        add_action( 'wp_ajax_' . WP_LOGGER_NAME_LINLINE . '_getSettingsFields', array( $this, 'getSettingsFields' ) );
        add_action( 'wp_ajax_' . WP_LOGGER_NAME_LINLINE . '_getSettings', array( $this, 'getSettings' ) );
        add_action( 'wp_ajax_' . WP_LOGGER_NAME_LINLINE . '_saveSettings', array( $this, 'saveSettings' ) );
        /** events settings */
        add_action( 'wp_ajax_' . WP_LOGGER_NAME_LINLINE . '_getEventsSettingsFields', array( $this, 'getEventsSettingsFields' ) );
        add_action( 'wp_ajax_' . WP_LOGGER_NAME_LINLINE . '_getEventsSettings', array( $this, 'getEventsSettings' ) );
        add_action( 'wp_ajax_' . WP_LOGGER_NAME_LINLINE . '_saveEventsSettings', array( $this, 'saveEventsSettings' ) );
    }

    /**
     * Initialize this class for direct usage
     * @return Report
     */
    public static function initialize(): Report
    {
        return new self;
    }

    /**
     * Including assets for report page
     * @param  string $suffix
     * @return void
     */
    public function enqueues( $suffix )
    {
		global $wp_logger_fs;
        if ( $suffix != WP_LOGGER_NAME . '_page_' . WP_LOGGER_NAME_LINLINE . '-reports' ) return;
        
        $adminAssets = 'assets/admin/';
        /** styles */
        wp_enqueue_style( WP_LOGGER_NAME . '-mdi', WP_LOGGER_DIR_URL . $adminAssets . 'css/global/mdi.css', array(), WP_LOGGER_VERSION );
        wp_enqueue_style( WP_LOGGER_NAME . '-reports-bundle', WP_LOGGER_DIR_URL . $adminAssets . 'css/reports/bundle.css', array(), WP_LOGGER_VERSION );
        wp_enqueue_style( WP_LOGGER_NAME . '-select2', WP_LOGGER_DIR_URL . $adminAssets . 'css/global/select2.css', array(), WP_LOGGER_VERSION );
        wp_enqueue_style( WP_LOGGER_NAME . '-select2-bs', WP_LOGGER_DIR_URL . $adminAssets . 'css/global/select2.bs.css', array(), WP_LOGGER_VERSION );
        wp_enqueue_style( WP_LOGGER_NAME . '-reports-layout', WP_LOGGER_DIR_URL . $adminAssets . 'css/reports/layout.css', array(), WP_LOGGER_VERSION );
        wp_enqueue_style( WP_LOGGER_NAME . '-reports-style', WP_LOGGER_DIR_URL . $adminAssets . 'css/reports/style.css', array(), WP_LOGGER_VERSION );
        /** scripts */
        wp_enqueue_script( WP_LOGGER_NAME . '-reports-bundle', WP_LOGGER_DIR_URL . $adminAssets . 'js/reports/bundle.js', array( 'jquery' ), WP_LOGGER_VERSION, true );
	    wp_enqueue_script( 'moment' );
	    wp_enqueue_script( WP_LOGGER_NAME . '-chart', WP_LOGGER_DIR_URL . $adminAssets . 'js/global/chart.js', array( 'jquery' ), WP_LOGGER_VERSION, true );
        wp_enqueue_script( WP_LOGGER_NAME . '-reports-dashboard', WP_LOGGER_DIR_URL . $adminAssets . 'js/reports/dashboard.js', array( 'jquery' ), WP_LOGGER_VERSION, true );
        wp_enqueue_script( WP_LOGGER_NAME . '-select2', WP_LOGGER_DIR_URL . $adminAssets . 'js/global/select2.js', array( 'jquery' ), WP_LOGGER_VERSION, true );
        wp_enqueue_script( WP_LOGGER_NAME . '-bs-datepicker', WP_LOGGER_DIR_URL . $adminAssets . 'js/global/bs.datepicker.js', array( 'jquery' ), WP_LOGGER_VERSION, true );
        wp_enqueue_script( WP_LOGGER_NAME . '-jspdf', WP_LOGGER_DIR_URL . $adminAssets . 'js/global/jspdf.js', array( 'jquery' ), WP_LOGGER_VERSION, true );
		wp_enqueue_script( WP_LOGGER_NAME . '-vue', WP_LOGGER_DIR_URL . $adminAssets . 'js/global/vue.js', array( 'jquery' ), WP_LOGGER_VERSION, true );
        wp_enqueue_script( WP_LOGGER_NAME . '-vue-router', WP_LOGGER_DIR_URL . $adminAssets . 'js/global/vue.router.js', array( 'jquery' ), WP_LOGGER_VERSION, true );
        wp_enqueue_script( WP_LOGGER_NAME . '-reports-app', WP_LOGGER_DIR_URL . $adminAssets . 'js/reports/app/app.js', array( 'jquery' ), WP_LOGGER_VERSION, true );
        $report_translations = array(
            'reports_quick_view'       => __( 'Reports Quick View', 'lite-wp-logger' ),
            'logged_in_today'          => __( 'Logged In Today', 'lite-wp-logger' ),
            'posts_this_week'          => __( 'Posts This Week', 'lite-wp-logger' ),
            'comments_this_week'       => __( 'Comments This Week', 'lite-wp-logger' ),
            'updates_this_week'        => __( 'Updates This Week', 'lite-wp-logger' ),
            'posts'                    => __( 'Posts', 'lite-wp-logger' ),
            'settings'                 => __( 'Settings', 'lite-wp-logger' ),
            'plugin_settings'          => __( 'Plugin Settings', 'lite-wp-logger' ),
            'loading'                  => __( 'Loading ...', 'lite-wp-logger' ),
            'general'                  => __( 'General', 'lite-wp-logger' ),
            'addons'                   => __( 'Addons', 'lite-wp-logger' ),
            'events_control'           => __( 'Events Control', 'lite-wp-logger' ),
            'events_control_settings'  => __( 'Events Control Settings', 'lite-wp-logger' ),
            'login'                    => __( 'Login', 'lite-wp-logger' ),
            'users'                    => __( 'Users', 'lite-wp-logger' ),
            'content'                  => __( 'Content', 'lite-wp-logger' ),
            'comments'                 => __( 'Comments', 'lite-wp-logger' ),
            'plugins'                  => __( 'Plugins', 'lite-wp-logger' ),
            'themes'                   => __( 'Themes', 'lite-wp-logger' ),
            'system'                   => __( 'System', 'lite-wp-logger' ),
            'save'                     => __( 'Save', 'lite-wp-logger' ),
            'saved'                    => __( 'Saved', 'lite-wp-logger' ),
            'online_users'             => __( 'Online Users', 'lite-wp-logger' ),
            'custom_report'            => __( 'Custom Report', 'lite-wp-logger' ),
            'reports'                  => __( 'Reports', 'lite-wp-logger' ),
            'logged_in_users'          => __( 'Logged In Users', 'lite-wp-logger' ),
            'latest_logged_in_users'   => __( 'latest logged in users', 'lite-wp-logger' ),
            'terminate_all'            => __( 'Terminate all', 'lite-wp-logger' ),
            'id'                       => __( 'ID', 'lite-wp-logger' ),
            'user'                     => __( 'User', 'lite-wp-logger' ),
            'create'                   => __( 'Create', 'lite-wp-logger' ),
            'expire'                   => __( 'Expire', 'lite-wp-logger' ),
            'actions'                  => __( 'Actions', 'lite-wp-logger' ),
            'terminate'                => __( 'Terminate', 'lite-wp-logger' ),
            'no_logs'                  => __( 'No Logs', 'lite-wp-logger' ),
            'custom_report_logs'       => __( 'Custom Report Logs', 'lite-wp-logger' ),
            'export_csv'               => __( 'Export CSV', 'lite-wp-logger' ),
            'export_pdf'               => __( 'Export PDF', 'lite-wp-logger' ),
            'show_filters'             => __( 'Show Filters', 'lite-wp-logger' ),
            'hide_filters'             => __( 'Hide Filters', 'lite-wp-logger' ),
            'filter_by_keyword'        => __( 'Filter by keyword:', 'lite-wp-logger' ),
            'filter_by_log_type'       => __( 'Filter by log type:', 'lite-wp-logger' ),
            'filter_by_log_importance' => __( 'Filter by log importance:', 'lite-wp-logger' ),
            'filter_by_user'           => __( 'Filter by user:', 'lite-wp-logger' ),
            'filter_by_user_role'      => __( 'Filter by user role:', 'lite-wp-logger' ),
            'filter_by_user_ip'        => __( 'Filter by user ip:', 'lite-wp-logger' ),
            'logs_per_page'            => __( 'Logs per page:', 'lite-wp-logger' ),
            'apply_filter'             => __( 'Apply filter', 'lite-wp-logger' ),
            'filter'                   => __( 'Filter', 'lite-wp-logger' ),
            'select_role'              => __( 'Select role', 'lite-wp-logger' ),
            'select_user'              => __( 'Select user', 'lite-wp-logger' ),
            'select_importance'        => __( 'Select importance', 'lite-wp-logger' ),
            'select_type'              => __( 'Select type', 'lite-wp-logger' ),
            'to'                       => __( 'to', 'lite-wp-logger' ),
            'date_from'                => __( 'Date from', 'lite-wp-logger' ),
            'date_to'                  => __( 'Date to', 'lite-wp-logger' ),
            'keyword'                  => __( 'Keyword', 'lite-wp-logger' ),
            'ip_address'               => __( 'IP address', 'lite-wp-logger' ),
            'log_details'              => __( 'Log Details', 'lite-wp-logger' ),
            'type'                     => __( 'Type', 'lite-wp-logger' ),
            'importance'               => __( 'Importance', 'lite-wp-logger' ),
            'date'                     => __( 'Date', 'lite-wp-logger' ),
            'the_ip_is_not_valid'      => __( 'The IP is not valid', 'lite-wp-logger' ),
            'the_email_is_not_valid'   => __( 'The Email is not valid', 'lite-wp-logger' ),
            'the_option_is_not_valid'  => __( 'The Option is not valid', 'lite-wp-logger' ),
            'this_item_exists'         => __( 'This item exists', 'lite-wp-logger' ),
            'remove_this_item'         => __( 'Remove this item?', 'lite-wp-logger' ),
            'select_users'             => __( 'Select users', 'lite-wp-logger' ),
            'select_roles'             => __( 'Select roles', 'lite-wp-logger' ),
            'select_post_types'        => __( 'Select post types', 'lite-wp-logger' ),
            'add'                      => __( 'Add', 'lite-wp-logger' ),
            'reload'                   => __( 'Reload', 'lite-wp-logger' ),
            'anonymous'                => __( 'Anonymous', 'lite-wp-logger' ),
	        'unsaved_changes'          => __( 'You have unsaved changes! Leave?', 'lite-wp-logger' ),
            'premium'                  => __( 'Premium', 'lite-wp-logger' ),
            'email_notify'             => __( 'Email notify this event', 'lite-wp-logger' ),
        );
        /** custom translations */
        $report_translations = apply_filters( WP_LOGGER_NAME_LINLINE . '_modify_report_translations', $report_translations );
        wp_localize_script( WP_LOGGER_NAME . '-reports-app', 'reports_vars', array(
            'url'          => admin_url( 'admin-ajax.php' ),
            'nonce'        => wp_create_nonce( WP_LOGGER_NAME . '-ajax' ),
            'plugin_name'  => WP_LOGGER_NAME_LINLINE,
            'plugin_url'   => WP_LOGGER_DIR_URL,
            'translations' => $report_translations,
	        'is_premium'   => (bool) $wp_logger_fs->can_use_premium_code(),
            'settings'     => array(
                'reports_auto_refresh'     => $this->admin->getSetting( 'reports_auto_refresh' ),
                'reports_refresh_interval' => $this->admin->getSetting( 'reports_refresh_interval' ),
            ),
        ) );
    }

    /**
     * For adding module type for script tag
     *
     * @param  string $tag
     * @param  string $handle
     * @param  string $src
     * @return string
     */
    public function addTypeModule( string $tag, string $handle, string $src )
    {
        if ( WP_LOGGER_NAME . '-reports-app' != $handle ) return $tag;
        return "<script type='module' src='" . esc_url( $src ) . "' id='" . esc_attr( $handle ) . "'></script>";
    }

    /**
     * For convert snake case to pascal case
     *
     * @param string $snake_string
     * @return string
     */
    public function snakeToPascal( string $snake_string )
    {
        return ucwords( str_replace( '_', ' ', $snake_string ) );
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
	 * For checking if plugin licenced
	 *
	 * @return bool
	 */
	private function checkPremium()
	{
		global $wp_logger_fs;
		return (bool) $wp_logger_fs->can_use_premium_code();
	}

    /**
     * For getting report cards data
     *
     * @return void
     */
    public function getReportCards()
    {
        $this->nonce();

        $result             = array();
	    /** count today users logged in */
        $today              = getdate();
        $userArgs           = array(
            'post_type'      => WP_LOGGER_POST_TYPE,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'posts_per_page' => -1,
            'date_query'     => array(
                array(
                    'year'  => $today['year'],
                    'month' => $today['mon'],
                    'day'   => $today['mday'],
                ),
            ),
            'tax_query'      => array(
                array(
                    'taxonomy' => 'wplog_type',
                    'field' => 'slug',
                    'terms' => 'login',
                )
            ),
        );
        $userQuery          = new WP_Query( $userArgs );
        $result['users']    = $userQuery->found_posts;

	    /** this week posts */
        $week               = array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );
        $start_of_week      = get_option( 'start_of_week' );

        $postArgs           = array(
            'post_type'      => WP_LOGGER_POST_TYPE,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'posts_per_page' => -1,
            'date_query'     => array(
                'after' => 'previous week '.$week[ $start_of_week ],
            ),
            'tax_query'      => array(
                array(
                    'taxonomy' => 'wplog_type',
                    'field'    => 'slug',
                    'terms'    => 'post_added',
                ),
            ),
            'meta_query'     => array(
                array(
                    'key'     => 'post_type',
                    'value'   => 'post',
                ),
            ),
        );
        $postQuery          = new WP_Query( $postArgs );
        $result['posts']    = $postQuery->found_posts;

	    /** this week comments */
        $commentArgs        = array(
            'post_type'      => WP_LOGGER_POST_TYPE,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'posts_per_page' => -1,
            'date_query'     => array(
                'after' => 'this week ' . $week[ $start_of_week ],
            ),
            'tax_query'      => array(
                array(
                    'taxonomy' => 'wplog_type',
                    'field'    => 'slug',
                    'terms'    => array( 'comment_new', 'comment_new_reply' ),
                ),
            ),
        );
        $commentQuery       = new WP_Query( $commentArgs );
        $result['comments'] = $commentQuery->found_posts;

        /** this week plugin update */
        $pluginArgs         = array(
            'post_type'      => WP_LOGGER_POST_TYPE,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'posts_per_page' => -1,
            'date_query'     => array(
                'after' => 'previous week '.$week[ $start_of_week ],
            ),
            'tax_query'      => array(
                array(
                    'taxonomy' => 'wplog_type',
                    'field'    => 'slug',
                    'terms'    => 'plugin_updated',
                ),
            ),
        );
        $pluginQuery        = new WP_Query( $pluginArgs );
        $result['plugins']  = $pluginQuery->found_posts;

        wp_send_json( $result );
        exit();
    }

    /**
     * For getting all parent type terms
     *
     * @return void
     */
    public function getLogTopTypes()
    {
        $this->nonce();

        $args  = array(
            'show_option_all'  => '',
            'show_option_none' => '',
            'title_li'         => '',
            'echo'             => 0,
            'hide_empty'       => false,
            'parent'           => 0,
            'taxonomy'         => $this->slug . '_type',
            'walker'           => new jsonTermWalker,
        );
        $types = json_decode( '[' . rtrim( wp_list_categories( $args ), ',' ) . ']', true );

        $types = array_slice( $types,0,17 );
        foreach ( $types as &$type )
            $type['title'] = __( $this->snakeToPascal( $type['name'] ), 'lite-wp-logger' );
        unset( $type );

        wp_send_json( $types );
        exit();
    }

    /**
     * For getting all active sessions
     *
     * @return void
     */
    public function getSessions()
    {
        $this->nonce();
	    $is_premium = $this->checkPremium();

	    if ( ! $is_premium ) {
		    wp_send_json( array(
			    array(
				    'user' => array(
					    'ID' => 0,
						'user_login' => 'Premium only',
			            'display_name' => '*',
					)
				)
		    ) );
		    exit();
	    }

        $sessions = Session::initialize()->getAll();

        /**
         * @todo rewrite this part with less query call
         */
        foreach ( $sessions as $session ) {
            unset( $session->session_token );
            $session->user = get_userdata( $session->user_id )->data;
            unset( $session->user->user_pass );
            unset( $session->user->user_activation_key );
            unset( $session->user->user_registered );
        }

        wp_send_json( $sessions );
        exit();
    }

    /**
     * For getting current user data
     *
     * @return void
     */
    public function getCurrentUser()
    {
        $this->nonce();

        $current_user = wp_get_current_user();

        wp_send_json( $current_user );
        exit();
    }

    /**
     * Removing an online session
     *
     * @return void
     */
    public function removeSession()
    {
        $this->nonce();

        $user_id = ( isset($_POST['uid'] ) && is_numeric( $_POST['uid'] ) )? $_POST['uid'] : 0;
	    $user_id = sanitize_text_field( $user_id );
        if ( ! $user_id ) {
            wp_send_json( 'wrong user id' );
            exit();
        }
        Session::initialize()->remove( $user_id );

        wp_send_json( 'done' );
        exit();
    }

    /**
     * Removing all online session
     *
     * @return void
     */
    public function removeAllSessions()
    {
        $this->nonce();

        $session  = Session::initialize();
        $sessions = $session->getAll();
        $user_ids = array();
        foreach ( $sessions as $sess )
            $user_ids[] = $sess->user_id;
        $user_ids = array_diff( $user_ids, array( get_current_user_id() ) );
        if ( ! empty( $user_ids ) )
            $session->removeBulk( $user_ids );

        wp_send_json( 'done' );
        exit();
    }

    /**
     * For getting logs in json
     *
     * @return void
     */
    public function getLastLogs()
    {
        $this->nonce();
		$is_premium = $this->checkPremium();

		if ( ! $is_premium ) {
			wp_send_json( array(
				'data'         => array(
					array(
						'title' => __( 'Premium only', 'lite-wp-logger' ),
						'desc' => __( 'To use Custom Report you need to activate premium version', 'lite-wp-logger' ),
						'user' => array(
							'user_login' => __( 'System', 'lite-wp-logger' )
						)
					)
				),
				'max_page'	   => 1,
				'current_page' => 1
			) );
			exit();
		}

	    $filters  = ( isset( $_POST['filters'] ) && $_POST['filters'] )? $this->sanitizeArray( $_POST['filters'] ) : null;
	    $paged    = ( isset( $_POST['page'] ) && $_POST['page'] )? sanitize_text_field( $_POST['page'] ) : 1;
	    $per_page = ( isset( $_POST['per_page'] ) && $_POST['per_page'] )? sanitize_text_field( $_POST['per_page'] ) : 15;

        if ( $per_page > 100 ) $per_page = 100;
        if ( $per_page < 1 ) $per_page = 1;

        $result       = array( 'data' => 'No result' );
        $logQueryArgs = array(
            'post_type'      => WP_LOGGER_POST_TYPE,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'posts_per_page' => $per_page,
            'paged'          => $paged
        );

        if ( $filters && is_array( $filters ) ) {
            if (
                ! empty( $filters['date_from'] ) ||
                ! empty( $filters['date_to'] )
            ) {
                $logQueryArgs['date_query'] = array(
                    'after'     => sanitize_text_field( $filters['date_from'] ),
                    'before'    => sanitize_text_field( $filters['date_to'] ),
                    'inclusive' => true,
                    'column'    => 'post_date'
                );
            }
            if ( isset( $filters['word'] ) && ! empty( $filters['word'] ) ) {
                $logQueryArgs['s'] = $filters['word'];
            }
            if ( isset( $filters['type'] ) && $filters['type'] && $filters['type'] != 0 ) {
                $term = get_term_by( 'id', $filters['type'], $this->slug . '_type' );
                $logQueryArgs[ $this->slug . '_type' ] = $term->slug;
            }
            if ( isset( $filters['importance'] ) && $filters['importance'] && $filters['importance'] != 0 ) {
                $term = get_term_by( 'id', $filters['importance'], $this->slug . '_importance' );
                $logQueryArgs[ $this->slug . '_importance' ] = $term->slug;
            }
            if ( isset( $filters['user'] ) && $filters['user'] && $filters['user'] != 0 ) {
                $logQueryArgs['author__in'] = array( $filters['user'] );
            } else {
                if ( isset( $filters['role'] ) && $filters['role'] ) {
                    $logQueryArgs['meta_query'] = array(
                        array(
                            'key'     => 'client_roles',
                            'value'   => '(.*)' . $filters['role'] . '(.*)',
                            'compare' => 'REGEXP',
                        ),
                    );
                }
                if ( isset( $filters['ip'] ) && ! empty( $filters['ip'] ) && filter_var( $filters['ip'], FILTER_VALIDATE_IP ) ) {
                    $logQueryArgs['meta_query'] = $logQueryArgs['meta_query'] ?? array();
                    $logQueryArgs['meta_query'][] = array(
                        'key'   => 'client_ip',
                        'value' => $filters['ip']
                    );
                }
            }
        }

        $logQuery = new WP_Query( $logQueryArgs );
        if ( $logQuery->have_posts() ) {
            $result['data'] = array();
            while ( $logQuery->have_posts() ) {
                $logQuery->the_post();
                $id         = get_the_ID();
                $types      = get_the_terms( $id, 'wplog_type' );
                $date       = get_post_timestamp( $id );
                $importance = get_the_terms( $id, 'wplog_importance' );
                $user       = get_post_meta( $id, 'client_data', true );

                $log = array(
                    'title'      => get_the_title(),
					'desc'       => get_post_meta( $id, 'desc', true ),
                    'user'       => $user,
                    'date'       => $date,
                    'mainType'   => '',
                    'type'       => '',
                    'importance' => '',
                );
                if ( ! empty( $types ) ) {
                    $log['mainType'] = array(
                        'name'  => $types[0]->name,
                        'title' => __( $this->snakeToPascal( $types[0]->name ), 'lite-wp-logger' ),
                    );
                    $lastTypeIndex = count( $types ) - 1;
                    $log['type'] = array(
                        'name'  => $types[ $lastTypeIndex ]->name,
                        'title' => __( $this->snakeToPascal( $types[ $lastTypeIndex ]->name ), 'lite-wp-logger' ),
                    );
                }
                if ( ! empty( $importance ) )
                    $log['importance'] = array(
                        'name'  => $importance[0]->name,
                        'title' => __( $this->snakeToPascal( $importance[0]->name ), 'lite-wp-logger' ),
                    );
                $result['data'][] = $log;
            }
            $result['max_page']     = $logQuery->max_num_pages;
            $result['current_page'] = $paged;
        }

        wp_send_json( $result );
        exit();
    }

    /**
     * For getting users list json
     *
     * @return void
     */
    public function getUsers()
    {
        $this->nonce();

        $result = array();

        $page      = ( isset( $_POST['page'] ) && is_numeric( $_POST['page'] ) )? sanitize_text_field( $_POST['page'] ) : 1;
        $per_page  = ( isset( $_POST['per_page'] ) && is_numeric( $_POST['per_page'] ) )? sanitize_text_field( $_POST['per_page'] ) : 10;
        $search    = ( isset( $_POST['search'] ) )? sanitize_text_field( $_POST['search'] ) : null;
        $no_option = isset( $_POST['no_option'] );
        $ids       = ( isset( $_POST['ids'] ) )? $this->sanitizeArray( $_POST['ids'] ) : null;

        $userQueryArgs = array(
            'number' => $per_page,
            'paged'  => $page,
            'fields' => array(
                'user_login',
                'ID',
                'display_name',
            ),
        );
        if ( $search )
            $userQueryArgs['search']  = '*' . $search . '*';
        if ( $ids && is_array( $ids ) )
            $userQueryArgs['include'] = $ids;

        $userQuery = new WP_User_Query( $userQueryArgs );
        $users     = $userQuery->get_results();
        if ( ! empty( $users ) ) {
            if($no_option)
                $result[] = array(
                    'ID'           => 0,
                    'user_login'   => __( 'Select user', 'lite-wp-logger' ),
                    'display_name' => '',
                );
            foreach ( $users as $user )
                $result[] = $user;
        }
        
        wp_send_json( $result );
        exit();
    }

    /**
     * For getting all type terms
     *
     * @return void
     */
    public function getLogTypes()
    {
        $this->nonce();

        $types = get_terms( array(
            'taxonomy'   => $this->slug . '_type',
            'hide_empty' => true,
        ) );

        $types = json_decode( json_encode( $types ), true );
        foreach ( $types as &$type )
            $type['title'] = __( $this->snakeToPascal( $type['name'] ), 'lite-wp-logger' );
        unset( $type );

        wp_send_json( $types );
        exit();
    }

    /**
     * For getting all importance terms
     *
     * @return void
     */
    public function getLogImportance()
    {
        $this->nonce();

        $importances = get_terms( array(
            'taxonomy'   => $this->slug . '_importance',
            'hide_empty' => true,
        ) );

        $importances = json_decode( json_encode( $importances ), true );
        foreach ( $importances as &$importance )
            $importance['title'] = __( $this->snakeToPascal( $importance['name'] ), 'lite-wp-logger' );
        unset( $importance );

        wp_send_json( $importances );
        exit();
    }

    /**
     * For getting user roles in json
     *
     * @return void
     */
    public function getUserRoles()
    {
        $this->nonce();

        $roles = wp_roles()->role_names;

        wp_send_json( $roles );
        exit();
    }

    /**
     * For getting all post types
     *
     * @return void
     */
    public function getPostTypes()
    {
        $this->nonce();

        $unsets = array(
            'attachment',
            'revision'
        );
        $post_types = get_post_types();

        foreach ( $unsets as $unset)
            unset( $post_types[ $unset ] );

        wp_send_json( $post_types );
        exit();
    }

    /**
     * For checking the IP is valid or not
     *
     * @return void
     */
    public function checkValidIp()
    {
        $this->nonce();

        $ip     = ( $_POST['ip'] )? sanitize_text_field( $_POST['ip'] ) : false;
        $result = false;

        if ( $ip && filter_var( $ip, FILTER_VALIDATE_IP ) )
            $result = true;

        wp_send_json( $result );
        exit();
    }

	/**
	 * For checking the Email is valid or not
	 *
	 * @return void
	 */
	public function checkValidEmail()
	{
		$this->nonce();

		$email = ( $_POST['email'] )? sanitize_email( $_POST['email'] ) : false;
		$result = false;

		if ( $email && filter_var( $email, FILTER_VALIDATE_EMAIL ) )
			$result = true;

		wp_send_json( $result );
		exit();
	}

	/**
	 * For checking the Option is valid or not
	 *
	 * @return void
	 */
	public function checkValidOption()
	{
		$this->nonce();

		$option = ( $_POST['option'] )? sanitize_text_field( $_POST['option'] ) : false;
		$result = false;

		if ( $option && get_option( $option ) )
			$result = true;

		/** Check if its regex */
		if( !$result && preg_match("/^\/.+\/[a-z]*$/i", $option ) )
			$result = true;

			wp_send_json( $result );
		exit();
	}

    /**
     * For getting settings
     *
     * @return void
     */
    public function getSettingsFields()
    {
        $this->nonce();

        $admin = new Admin;
        $admin->setSettingsFields();

        wp_send_json( $admin->settings_fields );
        exit();
    }

    /**
     * For getting settings
     *
     * @return void
     */
    public function getSettings()
    {
        $this->nonce();

        $admin = new Admin;
        $admin->retrieveSettings();

        wp_send_json( $admin->settings );
        exit();
    }

    /**
     * For saving settings
     *
     * @return void
     */
    public function saveSettings()
    {
	    global $wp_logger_fs;
        $this->nonce();

        $admin = new Admin;
        $admin->retrieveSettings();
        $admin->setSettingsFields();
        $plugin_settings = $admin->settings;
		$is_premium = $wp_logger_fs->can_use_premium_code();

        if ( $_POST['settings'] && is_array( $_POST['settings'] ) ) {
			$settings = $this->sanitizeArray( $_POST['settings'] );
            foreach ( $settings as $setting_key => $post_setting ) {
				if (
					! $admin->getSettingField( $setting_key, 'is_premium' ) ||
					( $admin->getSettingField( $setting_key, 'is_premium' ) && $is_premium )
				) {
					$setting = $admin->getSetting( $setting_key );
					if ( $post_setting && $post_setting == 'empty_array' )
						$plugin_settings[ $setting_key ] = array();
					else
						$plugin_settings[ $setting_key ] = $post_setting ?? $setting;
				}
            }
            update_option( WP_LOGGER_NAME_LINLINE . '-settings', $plugin_settings );
        }

        wp_send_json( $plugin_settings );
        exit();
    }

    /**
     * For getting events settings
     *
     * @return void
     */
    public function getEventsSettingsFields()
    {
        $this->nonce();

        $admin = new Admin;
        $admin->setEventsSettingsFields();

        wp_send_json( $admin->events_settings_fields );
        exit();
    }

    /**
     * For getting events settings
     *
     * @return void
     */
    public function getEventsSettings()
    {
        $this->nonce();

        $admin = new Admin;
        $admin->retrieveEventsSettings();
	    $admin->retrieveEventsEmailSettings();

        wp_send_json( array(
			'settings' => $admin->events_settings,
	        'emails'   => $admin->events_email_settings
        ) );
        exit();
    }

    /**
     * For saving events settings
     *
     * @return void
     */
    public function saveEventsSettings()
    {
	    global $wp_logger_fs;
        $this->nonce();

        $admin = new Admin;
        $admin->retrieveEventsSettings();
	    $admin->retrieveEventsEmailSettings();
        $admin->setEventsSettingsFields();
	    $events_settings       = $admin->events_settings;
	    $events_email_settings = $admin->events_email_settings;
	    $is_premium            = $wp_logger_fs->can_use_premium_code();

        if ( $_POST['events_settings'] && is_array( $_POST['events_settings'] ) ) {
			$settings = $this->sanitizeArray( $_POST['events_settings'] );
            foreach ( $settings as $setting_key => $post_setting ) {
	            if (
		            ! $admin->getEventSettingField( $setting_key, 'is_premium' ) ||
		            ( $admin->getEventSettingField( $setting_key, 'is_premium' ) && $is_premium )
	            ) {
		            $setting                         = $admin->getEventSetting( $setting_key );
		            $events_settings[ $setting_key ] = $post_setting ?? $setting;
	            }
            }
            update_option( WP_LOGGER_NAME_LINLINE . '-events-settings', $events_settings );
        }

	    if ( $_POST['events_email_settings'] && is_array( $_POST['events_email_settings'] ) && $is_premium ) {
		    $settings = $this->sanitizeArray( $_POST['events_email_settings'] );
		    foreach ( $settings as $email_setting_key => $post_setting ) {
			    $setting                                     = $admin->getEventEmailSetting( $email_setting_key );
			    $events_email_settings[ $email_setting_key ] = $post_setting ?? $setting;
		    }
		    update_option( WP_LOGGER_NAME_LINLINE . '-events-email-settings', $events_email_settings );
	    }

        wp_send_json( $events_settings );
        exit();
    }

    /**
     * Send new logs as notify in admin
     *
     * @param  array $response
     * @param  array $data
     * @return array
     */
    public function checkNotify( array $response, array $data )
    {
        if ( empty( $data[ WP_LOGGER_NAME_LINLINE . '_todate' ] ) )
            return $response;

        $theDateTime = $data[ WP_LOGGER_NAME_LINLINE . '_todate' ];
        $result      = array();

        $logQueryArgs = array(
            'post_type'      => WP_LOGGER_POST_TYPE,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'posts_per_page' => 10,
            'date_query'     => array(
                'after'     => sanitize_text_field( $theDateTime ),
                'inclusive' => true,
                'column'    => 'post_date'
            )
        );

        $logQuery = new WP_Query( $logQueryArgs );
        if ( $logQuery->have_posts() ) {
            while ( $logQuery->have_posts() ) {
                $logQuery->the_post();
                $user     = get_post_meta( get_the_ID(), 'client_data', true );
                $log      = array(
                    'title'    => get_the_title(),
                    'username' => $user['user_login'],
                );
                $result[] = $log;
            }
        }

        $response[ WP_LOGGER_NAME_LINLINE . '_newLogs' ] = $result;
        return $response;
    }

	/**
	 * For each array item
	 *
	 * @param  array $inputArray
	 * @return array
	 */
	public function sanitizeArray( array $inputArray )
	{
		$newInput = array();
		foreach ( $inputArray as $inputKey => $inputItem ){
			if ( is_array( $inputItem ) )
				$newInput[$inputKey] = $this->sanitizeArray( $inputItem );
			else
				$newInput[$inputKey] = sanitize_text_field( $inputItem );
		}
		return $newInput;
	}
}
