<?php
/**
 * WPLogger: Logger
 *
 * Logger class file.
 * Logger is the main class for logging in a custom post type.
 *
 * @since 1.0.0
 * @package WPLogger
 */
namespace WPLogger\Logger;

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Uses */
use UAParser\Exception\FileNotFoundException;
use WPLogger\Plugin\Email;
use WPLogger\User\User;
use WPLogger\Plugin\Admin;
use WP_User;
use WP_Post;
use WP_Query;

/**
 * Logger class
 *
 * @package WPLogger
 */
class Logger {

	/**
	 * Name used for slug and taxonomies
	 *
	 * @var string
	 */
	public $slug;
	/**
	 * Name used for showing and storing in content
	 *
	 * @var string
	 */
	public $name;
	/**
	 * Pleural name used for showing and storing in content
	 *
	 * @var string
	 */
	public $names;
	/**
	 * Using admin class
	 *
	 * @var Admin
	 */
	public $admin;
	/**
	 * List allowed html for wp_kses
	 *
	 * @var array
	 */
	public static $allowed_html = array(
		'a'      => array(
			'href'  => array(),
			'title' => array(),
		),
		'br'     => array(),
		'em'     => array(),
		'strong' => array(),
		'code'   => array(
			'class' => array(),
			'style' => array(),
		),
		'span'   => array(
			'class' => array(),
			'style' => array(),
		),
		'div'    => array(
			'class' => array(),
			'style' => array(),
		),
	);

	/**
	 * Logger constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->slug  = substr( WP_LOGGER_POST_TYPE, 0, -1 );
		$this->name  = __( 'Activity log', 'lite-wp-logger' );
		$this->names = __( 'Activity logs', 'lite-wp-logger' );
		/** get settings */
		$this->admin = new Admin();
		$this->admin->setSettingsFields();
		$this->admin->retrieveSettings();
		$this->admin->setEventsSettingsFields();
		$this->admin->retrieveEventsEmailSettings();
	}

	/**
	 * Initialize this class for direct usage
	 *
	 * @return Logger
	 */
	public static function initialize(): Logger {
		return new self();
	}

	/**
	 * One time running for setting up post type
	 *
	 * @return void
	 */
	public function setup() {
		$this->taxImportance();
		$this->taxType();
		$this->registerPostType();
		/** default order logs */
		add_action( 'pre_get_posts', array( $this, 'orderByDate' ) );
		/** custom log admin columns */
		add_filter( 'manage_edit-' . WP_LOGGER_POST_TYPE . '_columns', array( $this, 'adminColumns' ) );
		add_action( 'manage_' . WP_LOGGER_POST_TYPE . '_posts_custom_column', array( $this, 'adminColumnsData' ), 10, 2 );
		/** custom bulk actions */
		add_filter( 'bulk_actions-edit-' . WP_LOGGER_POST_TYPE, array( $this, 'bulkActions' ) );
		/** remove publish area in show log */
		add_action( 'admin_menu', array( $this, 'removePublishBox' ) );
		/** filter default row actions */
		add_filter( 'page_row_actions', array( $this, 'rowActions' ), 10, 2 );
		/** filter logs functions */
		add_action( 'restrict_manage_posts', array( $this, 'filterAddons' ) );
		add_filter( 'parse_query', array( $this, 'filterTerm' ) );
		/** admin logs scripts */
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueues' ) );
		/** old logs remover schedule */
		add_action( WP_LOGGER_NAME_LINLINE . '_logs_scheduler', array( $this, 'logsScheduler' ) );
		if ( ! wp_next_scheduled( WP_LOGGER_NAME_LINLINE . '_logs_scheduler' ) ) {
			wp_schedule_event(
				strtotime( '00:00:00' ),
				'daily',
				WP_LOGGER_NAME_LINLINE . '_logs_scheduler'
			);
		}

		/**  custom bottom html */
		add_filter( 'admin_footer_text', array( $this, 'logsFooter' ) );
		/**  admin ajax for getting log details */
		add_action( 'wp_ajax_' . WP_LOGGER_NAME_LINLINE . '_getLogDetails', array( $this, 'getLogDetails' ) );
	}

	/**
	 * For registering custom post type for logger
	 *
	 * @return void
	 */
	public function registerPostType() {
		$labels = array(
			'name'              => $this->names,
			'singular_name'     => $this->name,
			'parent_item_colon' => ':',
			'menu_name'         => $this->names,
		);

		$args = array(
			'labels'              => $labels,
			'hierarchical'        => true,
			'description'         => WP_LOGGER_NAME_OUTPUT . __( ' post type', 'lite-wp-logger' ),
			'supports'            => array( '' ),
			'taxonomies'          => array( $this->slug . '_type', $this->slug . '_importance' ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => WP_LOGGER_NAME_LINLINE,
			'show_in_rest'        => false,
			'menu_position'       => 2,
			'menu_icon'           => 'dashicons-welcome-write-blog',
			'show_in_nav_menus'   => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'query_var'           => true,
			'can_export'          => true,
			'rewrite'             => true,
			'capability_type'     => 'post',
			'capabilities'        => array( 'create_posts' => false ),
			'map_meta_cap'        => true,
		);

		register_post_type( WP_LOGGER_POST_TYPE, $args );
	}

	/**
	 * For registering Importance taxonomy for logger
	 *
	 * @return void
	 */
	public function taxImportance() {
		register_taxonomy(
			$this->slug . '_importance',
			WP_LOGGER_POST_TYPE,
			array(
				'hierarchical'       => true,
				'show_in_rest'       => false,
				'label'              => __( 'Importance', 'lite-wp-logger' ),
				'singular_name'      => __( 'Importance', 'lite-wp-logger' ),
				'rewrite'            => true,
				'query_var'          => true,
				'show_in_quick_edit' => false,
				'meta_box_cb'        => false,
			)
		);
	}

	/**
	 * For registering Type taxonomy for logger
	 *
	 * @return void
	 */
	public function taxType() {
		register_taxonomy(
			$this->slug . '_type',
			WP_LOGGER_POST_TYPE,
			array(
				'hierarchical'       => true,
				'show_in_rest'       => false,
				'label'              => __( 'Types', 'lite-wp-logger' ),
				'labels'             => array( 'menu_name' => __( 'Log types', 'lite-wp-logger' ) ),
				'singular_name'      => __( 'Type', 'lite-wp-logger' ),
				'rewrite'            => true,
				'query_var'          => true,
				'show_in_quick_edit' => false,
				'meta_box_cb'        => false,
			)
		);
	}

	/**
	 * Adding new log to logger post type
	 *
	 * @param  array        $data
	 * @param  string       $event
	 * @param  WP_User|null $user
	 * @return integer
	 * @throws FileNotFoundException
	 */
	public function add( array $data, string $event, WP_User $user = null ) {
		$user = new User( $user );
		if ( ! $user->current_user instanceof WP_User && ! ( isset( $user->current_user ) ) ) {
			return 0;
		}

		$user_roles = $user->current_user->roles;
		$user_id    = $user->current_user->ID;
		$user_ip    = $user->getUserIp();
		$log_anyway = array( 'login', 'logout', 'login_fail', 'reset_password', 'lost_password' );

		if ( ! isset( $user_id ) ) {
			$user_id = 0;
		}
		if ( ! isset( $user->current_user->data->user_login ) ) {
			$user->current_user->data->user_login = 'Anonymous';
		}

		/** Check excluded users */
		$excluded_users = $this->admin->getSetting( 'exclude_users' );
		if ( in_array( $user_id, $excluded_users ) && ! in_array( $data['type'], $log_anyway ) ) {
			return 0;
		}

		/** Check excluded ips */
		$excluded_ips = $this->admin->getSetting( 'exclude_ips' );
		if ( in_array( $user_ip, $excluded_ips ) ) {
			return 0;
		}

		/** Check excluded roles */
		$excluded_roles = $this->admin->getSetting( 'exclude_roles' );
		foreach ( $user_roles as $role ) {
			if ( in_array( $role, $excluded_roles ) && ! in_array( $data['type'], $log_anyway ) ) {
				return 0;
			}
		}

		$user_data = json_decode( json_encode( $user->current_user->data ), true );
		unset( $user_data['user_registered'] );
		unset( $user_data['user_activation_key'] );
		unset( $user_data['user_pass'] );

		$args = array(
			'post_title'   => $data['title'],
			'post_status'  => 'publish',
			'post_author'  => $user_id,
			'post_content' => $data['message'],
			'post_type'    => WP_LOGGER_POST_TYPE,
			'meta_input'   => array(
				'client_ip'    => $user_ip,
				'client_roles' => $user_roles,
				'client_data'  => $user_data,
				'client_agent' => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ),
			),
		);

		if ( ! empty( $data['metas'] ) ) {
			foreach ( $data['metas'] as $mKey => $mVal ) {
				$args['meta_input'][ $mKey ] = $mVal;
			}
		}

		$postID    = wp_insert_post( $args );
		$exTypes   = explode( '_', $data['type'] );
		$typeSlugs = array();

		for ( $exI = 0; $exI < count( $exTypes ); $exI++ ) {
			$term_slug        = $exTypes[ $exI ];
			$term_parent_slug = null;
			$term_parent_id   = 0;

			if ( $exI > 0 ) {
				for ( $exIP = $exI - 1; $exIP >= 0; $exIP-- ) {
					$term_slug = $exTypes[ $exIP ] . '_' . $term_slug;
				}
				$tempSlice        = $exTypes;
				$term_parent_slug = implode( '_', array_splice( $tempSlice, 0, $exI ) );
			}

			if ( $term_parent_slug ) {
				$term_parent_id = get_term_by( 'slug', $term_parent_slug, $this->slug . '_type' )->term_id;
			}

			if ( ! term_exists( $term_slug, $this->slug . '_type', ( $term_parent_id ) ? : null ) ) {
				$tArgs = array( 'slug' => $term_slug );
				if ( $term_parent_slug ) {
					$tArgs['parent'] = $term_parent_id;
				}
				wp_insert_term( $term_slug, $this->slug . '_type', $tArgs );
			}
			$typeSlugs[] = $term_slug;
		}
		wp_set_object_terms( $postID, $typeSlugs, $this->slug . '_type' );
		wp_set_object_terms( $postID, $data['importance'], $this->slug . '_importance' );

		/** Email event */
		if ( $this->admin->getEventEmailSetting( $event ) ) {
			$emails = $this->admin->getSetting( 'notify_emails' );
			Email::initialize()->sendMail(
				array(
					'title'      => $data['title'],
					'importance' => $data['importance'],
					'types'      => $data['type'],
					'desc'       => $data['metas']['desc'],
					'user_data'  => $user_data,
					'user_ip'    => $user_ip,
					'user_agent' => sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ),
					'emails'     => $emails,
				)
			);
		}

		return $postID;
	}

	/**
	 * Custom order by date in admin
	 *
	 * @param  WP_Query $query
	 * @return WP_Query
	 */
	public function orderByDate( WP_Query $query ) {
		global $pagenow;
		if (
			is_admin() &&
			$pagenow == 'edit.php' &&
			$query->is_main_query() &&
			$query->query['post_type'] == WP_LOGGER_POST_TYPE
		) {
			if ( ! empty( $_GET['datefrom'] ) || ! empty( $_GET['dateto'] ) ) {
				$query->set(
					'date_query',
					array(
						'after'     => sanitize_text_field( $_GET['datefrom'] ),
						'before'    => sanitize_text_field( $_GET['dateto'] ),
						'inclusive' => true,
						'column'    => 'post_date',
					)
				);
			} else {
				$query->set( 'orderby', 'date' );
				$query->set( 'order', 'DESC' );
			}
		}
		return $query;
	}

	/**
	 * Customize post type admin columns
	 *
	 * @param  array $columns
	 * @return array
	 */
	public function adminColumns( array $columns ) {
		unset( $columns['title'] );
		unset( $columns['date'] );
		unset( $columns['title'] );
		$columns['log_importance'] = __( 'Severity', 'lite-wp-logger' );
		$columns['log_type']       = __( 'Type', 'lite-wp-logger' );
		$columns['log_user']       = __( 'User', 'lite-wp-logger' );
		$columns['log_ip']         = __( 'IP', 'lite-wp-logger' );
		$columns['log_date']       = __( 'Date', 'lite-wp-logger' );
		$columns['log_details']    = __( 'Details', 'lite-wp-logger' );
		return $columns;
	}

	/**
	 * Customize post type admin columns data
	 *
	 * @param  string $column
	 * @param  int    $postId
	 * @return void
	 */
	public function adminColumnsData( string $column, int $postId ) {
		$customLogsColumns = array(
			'log_importance',
			'log_type',
			'log_user',
			'log_ip',
			'log_date',
			'log_details',
		);
		if ( in_array( $column, $customLogsColumns ) ) {
			call_user_func( array( $this, 'logsColumn' . ucfirst( ltrim( $column, 'log_' ) ) ), $postId );
		}
	}

	/**
	 * For customizing Type viewing & filter in log viewer
	 *
	 * @param  int $postId
	 * @return void
	 */
	public function logsColumnType( int $postId ) {
		 $types = get_the_terms( $postId, $this->slug . '_type' );
		if ( $types && is_array( $types ) ) {
			_e( 'Main: ', 'lite-wp-logger' );
			printf(
				'<a href="%s">%s</a>',
				admin_url( 'edit.php?post_type=' . WP_LOGGER_POST_TYPE . '&' . $this->slug . '_type=' . $types[0]->slug ),
				esc_attr__( $this->snakeToPascal( $types[0]->name ), 'lite-wp-logger' )
			);
			echo '<br>';
			if ( count( $types ) > 1 ) {
				$typeLastIndex = count( $types ) - 1;
				printf(
					'<a href="%s">%s</a>',
					admin_url(
						'edit.php?post_type=' . WP_LOGGER_POST_TYPE . '&' .
							   esc_attr( $this->slug . '_type=' . $types[ $typeLastIndex ]->slug )
					),
					esc_attr__( $this->snakeToPascal( $types[ $typeLastIndex ]->name ), 'lite-wp-logger' )
				);
			}
		} else {
			_e( 'None', 'lite-wp-logger' );
		}
	}

	/**
	 * For customizing Importance viewing & filter in log viewer
	 *
	 * @param  int $postId
	 * @return void
	 */
	public function logsColumnImportance( int $postId ) {
		$terms = get_the_terms( $postId, $this->slug . '_importance' );
		if ( $terms && is_array( $terms ) ) {
			printf(
				'<a class="log-severity" href="%s" title="%s"><i class="dashicons-before dashicons-warning severity-%s"></i></a>',
				admin_url(
					'edit.php?post_type=' . WP_LOGGER_POST_TYPE . '&' .
						   esc_attr( $this->slug . '_importance=' . $terms[0]->slug )
				),
				esc_attr__( $terms[0]->name, 'lite-wp-logger' ),
				$terms[0]->slug
			);
		} else {
			_e( 'None', 'lite-wp-logger' );
		}
	}

	/**
	 * For adding UserData viewing in log viewer
	 *
	 * @param  int $postId
	 * @return void
	 */
	public function logsColumnUser( int $postId ) {
		 $user_data = get_post_meta( $postId, 'client_data', true );
		$user_roles = get_post_meta( $postId, 'client_roles', true );
		if ( ( isset( $user_data['ID'] ) || isset( $user_data->ID ) ) && ( $user_data['ID'] != 0 || $user_data->ID != 0 ) ) {
			if ( is_array( $user_data ) ) {
				echo get_avatar( $user_data['ID'], 32, '', '', array( 'class' => 'log-user-avatar' ) );
				printf(
					'<a class="log-user" href="%s">%s</a><span>%s</span>',
					admin_url( 'user-edit.php?user_id=' . esc_attr( $user_data['ID'] ) ),
					esc_attr( $user_data['user_login'] ),
					implode( ',', $user_roles )
				);
			} else {
				echo get_avatar( $user_data->ID, 32, '', '', array( 'class' => 'log-user-avatar' ) );
				printf(
					'<a class="log-user" href="%s">%s</a><span>%s</span>',
					admin_url( 'user-edit.php?user_id=' . esc_attr( $user_data->ID ) ),
					esc_attr( $user_data->user_login ),
					implode( ',', $user_roles )
				);
			}
		} else {
			echo '<span class="dashicons dashicons-wordpress wsal-system-icon log-user-avatar"></span>';
			printf(
				'<a class="log-user" href="%s">%s</a><span>%s</span>',
				'#',
				esc_attr( $user_data['user_login'] ),
				esc_attr__( 'Anonymous', 'lite-wp-logger' )
			);
		}
	}

	/**
	 * For adding UserIP viewing in log viewer
	 *
	 * @param  int $postId
	 * @return void
	 */
	public function logsColumnIp( int $postId ) {
		$theIP = get_post_meta( $postId, 'client_ip', true );
		printf(
			'<a target="_blank" href="https://whatismyipaddress.com/ip/%s" title="%s">%s</a>',
			esc_attr( $theIP ),
			esc_attr__( 'Show IP info', 'lite-wp-logger' ),
			esc_attr( $theIP )
		);
	}

	/**
	 * For customizing date viewing in log viewer
	 *
	 * @param  int $postId
	 * @return void
	 */
	public function logsColumnDate( int $postId ) {
		printf(
			'<span>%s,</span><br><div class="from-now" data-unix="%s">...</div>',
			get_the_time( 'd M Y, H:i:s', $postId ),
			get_post_timestamp( $postId )
		);
	}

	/**
	 * For adding show details button
	 *
	 * @param  int $postId
	 * @return void
	 */
	public function logsColumnDetails( int $postId ) {      ?>
		<div class="log-details-block">
			<a class="log-details-btn" href="#" data-logid="<?php echo esc_attr( $postId ); ?>">
				<i class="dashicons-before dashicons-text-page"></i>
			</a>
			<div>
				<strong><?php the_title(); ?></strong><br>
				<?php echo wp_kses( get_post_meta( $postId, 'desc', true ), self::$allowed_html ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * For removing default Publish meta box in post type
	 *
	 * @return void
	 */
	public function removePublishBox() {
		remove_meta_box( 'submitdiv', WP_LOGGER_POST_TYPE, 'side' );
	}

	/**
	 * For customizing in row actions in admin
	 *
	 * @param  array   $actions
	 * @param  WP_Post $post
	 * @return array
	 */
	public function rowActions( array $actions, WP_Post $post ) {
		if ( $post->post_type != WP_LOGGER_POST_TYPE || $post->post_status == 'trash' ) {
			return $actions;
		}
		unset( $actions['edit'] );
		unset( $actions['inline hide-if-no-js'] );
		return $actions;
	}

	/**
	 * For display of custom addon in filter form in post type
	 *
	 * @return void
	 */
	public function filterAddons() {
		global $typenow;
		if ( $typenow != WP_LOGGER_POST_TYPE ) {
			return;
		}

		wp_dropdown_categories(
			array(
				'show_option_all' => __( 'Show all types', 'lite-wp-logger' ),
				'taxonomy'        => $this->slug . '_type',
				'name'            => $this->slug . '_type',
				'orderby'         => 'name',
				'selected'        => isset( $_GET[ $this->slug . '_type' ] ) ? sanitize_text_field( $_GET[ $this->slug . '_type' ] ) : '',
				'show_count'      => true,
				'hide_empty'      => true,
			)
		);

		wp_dropdown_categories(
			array(
				'show_option_all' => __( 'Show all importance', 'lite-wp-logger' ),
				'taxonomy'        => $this->slug . '_importance',
				'name'            => $this->slug . '_importance',
				'orderby'         => 'name',
				'selected'        => isset( $_GET[ $this->slug . '_importance' ] ) ? sanitize_text_field( $_GET[ $this->slug . '_importance' ] ) : '',
				'show_count'      => true,
				'hide_empty'      => true,
			)
		);

		printf(
			'<select name="role" id="role" class="postform"><option value="0">%s</option>',
			__( 'Show all roles', 'lite-wp-logger' )
		);
		wp_dropdown_roles( sanitize_text_field( $_GET['role'] ?? '' ) );
		echo '</select>';

		$dateFrom = ( isset( $_GET['datefrom'] ) && $_GET['datefrom'] ) ? sanitize_text_field( $_GET['datefrom'] ) : '';
		$dateTo   = ( isset( $_GET['dateto'] ) && $_GET['dateto'] ) ? sanitize_text_field( $_GET['dateto'] ) : '';

		printf(
			'<input type="text" style="width:95px;" name="datefrom" placeholder="%s" value="%s">',
			__( 'Date From', 'lite-wp-logger' ),
			$dateFrom
		);
		printf(
			'<input type="text" style="width:95px;" name="dateto" placeholder="%s" value="%s">',
			__( 'Date To', 'lite-wp-logger' ),
			$dateTo
		);
	}

	/**
	 * Filter logs in admin
	 *
	 * @param  WP_Query $query
	 * @return void
	 */
	public function filterTerm( WP_Query $query ) {
		 global $pagenow;
		$post_type = WP_LOGGER_POST_TYPE;
		$q_vars    = &$query->query_vars;
		if (
			$pagenow == 'edit.php' &&
			isset( $q_vars['post_type'] ) &&
			$q_vars['post_type'] == $post_type
		) {
			if (
				isset( $q_vars[ $this->slug . '_type' ] ) &&
				is_numeric( $q_vars[ $this->slug . '_type' ] ) &&
				$q_vars[ $this->slug . '_type' ] != 0
			) {
				$term                            = get_term_by( 'id', $q_vars[ $this->slug . '_type' ], $this->slug . '_type' );
				$q_vars[ $this->slug . '_type' ] = $term->slug;
			} elseif (
				isset( $q_vars[ $this->slug . '_importance' ] ) &&
				is_numeric( $q_vars[ $this->slug . '_importance' ] ) &&
				$q_vars[ $this->slug . '_importance' ] != 0
			) {
				$term                                  = get_term_by( 'id', $q_vars[ $this->slug . '_importance' ], $this->slug . '_importance' );
				$q_vars[ $this->slug . '_importance' ] = $term->slug;
			}
			if ( isset( $_GET['role'] ) && $_GET['role'] != '0' ) {
				$q_vars['meta_query'] = array(
					array(
						'key'     => 'client_roles',
						'value'   => '(.*)' . sanitize_text_field( $_GET['role'] ) . '(.*)',
						'compare' => 'REGEXP',
					),
				);
			}
		}
	}

	/**
	 * Including jquery ui and assets
	 *
	 * @return void
	 */
	public function enqueues( $suffix ) {
		global $post_type;
		if ( ! ( $post_type == WP_LOGGER_POST_TYPE && $suffix == 'edit.php' ) ) {
			return;
		}

		$adminAssets = 'assets/admin/';
		wp_enqueue_style( WP_LOGGER_NAME . '-jquery-ui', WP_LOGGER_DIR_URL . $adminAssets . 'css/global/jquery.ui.css', array(), WP_LOGGER_VERSION );
		wp_enqueue_style( WP_LOGGER_NAME . '-logs-style', WP_LOGGER_DIR_URL . $adminAssets . 'css/logs/style.css', array(), WP_LOGGER_VERSION );
		/** scripts */
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( WP_LOGGER_NAME . '-ua-parser', WP_LOGGER_DIR_URL . $adminAssets . 'js/global/ua.parser.js', array( 'jquery' ), WP_LOGGER_VERSION, true );
		wp_enqueue_script( 'moment' );
		wp_enqueue_script( WP_LOGGER_NAME . '-logs-script', WP_LOGGER_DIR_URL . $adminAssets . 'js/logs/script.js', array( 'jquery', 'jquery-ui-datepicker' ), WP_LOGGER_VERSION, true );
		wp_localize_script(
			WP_LOGGER_NAME . '-logs-script',
			'logs_vars',
			array(
				'url'          => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( WP_LOGGER_NAME . '-ajax' ),
				'plugin_name'  => WP_LOGGER_NAME_LINLINE,
				'settings'     => array(
					'logs_auto_refresh'     => $this->admin->getSetting( 'logs_auto_refresh' ),
					'logs_refresh_interval' => $this->admin->getSetting( 'logs_refresh_interval' ),
				),
				'translations' => array(
					'title'       => __( 'Title: ', 'lite-wp-logger' ),
					'importance'  => __( 'Importance: ', 'lite-wp-logger' ),
					'type'        => __( 'Type: ', 'lite-wp-logger' ),
					'user_agent'  => __( 'User agent: ', 'lite-wp-logger' ),
					'user_ip'     => __( 'User IP: ', 'lite-wp-logger' ),
					'user_name'   => __( 'User Name: ', 'lite-wp-logger' ),
					'user_email'  => __( 'User Email: ', 'lite-wp-logger' ),
					'log_details' => __( 'Log details: ', 'lite-wp-logger' ),
					'os'          => __( 'OS: ', 'lite-wp-logger' ),
					'browser'     => __( 'Browser: ', 'lite-wp-logger' ),
				),
			)
		);
	}

	/**
	 * Logs scheduler for deleting old logs
	 *
	 * @return void
	 */
	public function logsScheduler() {
		global $wpdb;
		$days = $this->admin->getSetting( 'logs_expire' );

		$wpdb->query(
			$wpdb->prepare(
				esc_sql(
					'DELETE a, b, c
            FROM ' . $wpdb->base_prefix . 'posts a
            LEFT JOIN ' . $wpdb->base_prefix . 'postmeta b ON ( a.ID = b.post_id )
            LEFT JOIN ' . $wpdb->base_prefix . "term_relationships c ON ( a.ID = c.object_id )
            WHERE post_type = '" . WP_LOGGER_POST_TYPE . "' DATEDIFF(day, post_date, NOW()) > " . $days . ';'
				)
			)
		);
	}

	/**
	 * For checking plugin admin ajax nonce
	 *
	 * @return void
	 */
	private function nonce() {
		if (
			! isset( $_POST['nonce'] ) || !
			wp_verify_nonce( sanitize_text_field( $_POST['nonce'] ), WP_LOGGER_NAME . '-ajax' )
		) {
			wp_die( 'Nonce died :)' );
		}
	}

	/**
	 * For adding custom html in logs post type footer
	 *
	 * @return void
	 */
	public function logsFooter() {
		global $post_type;
		if ( $post_type != WP_LOGGER_POST_TYPE ) {
			return;
		}
		?>
		<div class="log-auto-loading"><span class="loading-img"></span></div>
		<div class="log-details-viewer-back"></div>
		<div class="log-details-viewer">
			<div class="title">
				<i class="dashicons-before dashicons-no-alt log-details-viewer-close"></i>
				<h4><?php __( 'Log Details: ', 'lite-wp-logger' ); ?></h4>
			</div>
			<div class="content"></div>
		</div>
		<?php
	}

	/**
	 * For ajax getting log data
	 *
	 * @return void
	 */
	public function getLogDetails() {
		$this->nonce();
		$logID = ( isset( $_POST['log_id'] ) && $_POST['log_id'] ) ? sanitize_text_field( $_POST['log_id'] ) : null;

		if ( $logID ) {
			$post = get_post( $logID );
			if ( $post ) {
				$type       = get_the_terms( $post->ID, 'wplog_type' );
				$importance = get_the_terms( $post->ID, 'wplog_importance' );
				$user       = get_post_meta( $post->ID, 'client_data', true );
				if ( ! empty( $user ) ) {
					unset( $user->user_pass );
					unset( $user->user_activation_key );
				}
				$ip     = get_post_meta( $post->ID, 'client_ip', true );
				$agent  = get_post_meta( $post->ID, 'client_agent', true );
				$log    = array(
					'title'      => $post->post_title,
					'user'       => $user,
					'type'       => ( ! empty( $type ) ) ? esc_attr__( $this->snakeToPascal( $type[0]->name ), 'lite-wp-logger' ) : '',
					'importance' => ( ! empty( $importance ) ) ? esc_attr__( $importance[0]->name, 'lite-wp-logger' ) : '',
					'content'    => wp_kses( $post->post_content, self::$allowed_html ),
					'ip'         => $ip,
					'agent'      => $agent,
				);
				$result = array( 'data' => $log );
			} else {
				$result = array( 'data' => 'wrong id' );
			}
		} else {
			$result = array( 'data' => 'wrong id' );
		}

		wp_send_json( $result );
		exit();
	}

	/**
	 * For convert snake case to pascal case
	 *
	 * @param  string $snake_string
	 * @return string
	 */
	public function snakeToPascal( string $snake_string ) {
		 return ucwords( str_replace( '_', ' ', $snake_string ) );
	}

	/**
	 * For adding custom bulk actions in edit post
	 *
	 * @param  array $bulk_array
	 * @return array
	 */
	public function bulkActions( array $bulk_array ) {
		unset( $bulk_array['edit'] );
		return $bulk_array;
	}

}
