<?php
/**
 * WPLogger: Post
 *
 * Post class file.
 *
 * @since 1.0.0
 * @package WPLogger
 */
namespace WPLogger\Logger;

/** If this file is called directly, abort. */
if ( ! defined( 'ABSPATH' ) ) exit;

/** Uses */
use UAParser\Exception\FileNotFoundException;
use WP_Post;
use WPLogger\Plugin\Admin;
use WPLogger\WPLogger;

/**
 * Class Post for logging
 *
 * @package WPLogger
 */
class Post
{
    /**
     * Stores post data before update
     *
     * @var WP_Post
     */
    public $old_post;
    /**
     * Stores post permalink before update
     *
     * @var string
     */
    public $old_link;
    /**
     * Stores page template before update
     *
     * @var
     */
    public $old_template;
    /**
     * Stores post taxes before update
     *
     * @var array
     */
    public $old_taxes;
    /**
     * Stores post sticky status before update
     *
     * @var boolean
     */
    public $old_sticky;
    /**
     * Stores post meta before update
     *
     * @var array
     */
    public $old_metas;
    /**
     * Post types that will not log
     *
     * @var string[]
     */
    public $excludes;
	/**
	 * Post changes list
	 *
	 * @var array
	 */
	public $changesList;
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
     * Post class constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $this->excludes = array(
            'post_type' => array(
                WP_LOGGER_POST_TYPE,
                'revision',
            ),
            'taxonomy'  => array(
                'wplog_importance',
                'wplog_type',
            ),
            'meta'      => array(
                '_encloseme',
                '_edit_lock',
                '_edit_last',
                '_wp_page_template',
                '_wp_old_slug',
				'_thumbnail_id',
	            '_old_metas', /** used for storing old metas for changes checking */
            )
        );
        /** get events settings */
        $this->logger   = new Logger;
        $this->admin    = new Admin;
        $this->admin->setEventsSettingsFields();
        $this->admin->retrieveEventsSettings();
        /** get settings */
        $this->admin->setSettingsFields();
        $this->admin->retrieveSettings();
        $excluded_post_types = $this->admin->getSetting( 'exclude_post_types' );
        if ( ! empty( $excluded_post_types ) )
            $this->excludes['post_type'] = array_unique(
                array_merge( $this->excludes['post_type'], $excluded_post_types ),
                SORT_REGULAR
            );
	    $excluded_post_metas = $this->admin->getSetting( 'exclude_post_metas' );
	    if ( ! empty( $excluded_post_metas ) )
		    $this->excludes['meta'] = array_unique(
			    array_merge( $this->excludes['meta'], $excluded_post_metas ),
			    SORT_REGULAR
		    );
	    /** change list params: 'name', 'type'(modified, added, removed), 'value'(optional), 'old_value'(optional) */
	    $this->changesList = array();
	    /** Post opened */
	    if ( $this->admin->getEventSetting( 'open_post' ) )
		    add_action( 'admin_action_edit', array( $this, 'postOpened' ), 10 );
        /** Add or update post */
        if (
            $this->admin->getEventSetting( 'new_post' ) ||
            $this->admin->getEventSetting( 'update_post' )
        ) { 
            add_action( 'pre_post_update', array( $this, 'beforePostEdit' ), 10, 2 );
            add_action( 'save_post', array( $this, 'postSave' ), 10, 2 );
            add_action( 'add_attachment', array( $this, 'addAttachment') );
        }
        /** Post trash and deletion actions */
        if ( $this->admin->getEventSetting( 'trash_post' ) )
            add_action( 'wp_trash_post', array( $this, 'postTrash' ), 10, 1 );
        if ( $this->admin->getEventSetting( 'untrash_post' ) )
            add_action( 'untrash_post', array( $this, 'postUntrash' ), 10, 1 );
        if ( $this->admin->getEventSetting( 'delete_post' ) )
            add_action( 'delete_post', array( $this, 'postDelete' ), 10, 2 );
        /** Post sticky actions */
        if ( $this->admin->getEventSetting( 'sticky_post' ) ) {
            add_action( 'post_stuck', array( $this, 'postStuck' ), 10, 1 );
            add_action( 'post_unstuck', array( $this, 'postStuck' ), 10, 1 );
        }
        /** Post taxonomy actions */
        if ( $this->admin->getEventSetting( 'term_create' ) )
            add_action( 'create_term', array( $this, 'termCreation' ), 10, 1 );
        if ( $this->admin->getEventSetting( 'term_delete' ) )
            add_action( 'pre_delete_term', array( $this, 'termDeletion' ), 10, 2 );
        if ( $this->admin->getEventSetting( 'term_update' ) )
            add_filter('wp_update_term_data', array( $this, 'updateTermData' ), 10, 4 );
    }

    /**
     * Initialize this class for direct usage
     *
     * @return Post
     * @return void
     */
    public static function initialize(): Post
    {
        return new self;
    }

    /**
     * For action before save post
     *
     * @param  integer $postID
     * @param  array   $data
     * @return void
     */
    public function beforePostEdit( int $postID, array $data )
    {
        if ( in_array( $data['post_type'], $this->excludes['post_type'] ) ) return;
	    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        $post  = get_post( $postID );
        if ( ! empty( $post ) && $post instanceof WP_Post ) {
	        $metas              = get_post_meta( $postID );
            $this->old_post     = $post;
            $this->old_link     = get_permalink( $postID );
            $this->old_template = $this->getPostTemplate( $this->old_post );
            $this->old_taxes    = $this->getPostTaxonomies( $postID );
            $this->old_sticky   = in_array( $postID, get_option( 'sticky_posts' ), true );
	        $this->old_metas    = unserialize( $metas['_old_metas'][0] );
        }
    }

	/**
	 * For action on new post or updated
	 *
	 * @param  integer $postID
	 * @param  WP_Post $post
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function postSave( int $postID, WP_Post $post )
    {
        if ( in_array( $post->post_type, $this->excludes['post_type'] ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        if ( $post->post_date == $post->post_modified && $post->post_status != 'auto-draft' ) {
            /** New post */
            if ( $this->admin->getEventSetting( 'new_post' ) ){
                $message = __( 'New post :', 'lite-wp-logger' ) . esc_attr__( $post->post_type ) . '<br>' . WPLogger::varLight( $post );

                $this->logger->add( array(
                    'title'      => __( 'New post: ', 'lite-wp-logger' ) . esc_attr__( $post->post_type ) .
                                    ', ' . esc_attr__( $post->post_status ),
                    'message'    => $message,
                    'type'       => 'post_added',
                    'importance' => 'medium',
                    'metas'      => array(
                        'post_data' => $post,
                        'post_type' => $post->post_type,
                        'desc'      => __( 'Post type: ', 'lite-wp-logger' ) . esc_attr__( $post->post_type ) . '<br>' .
                                       __( 'Post status: ', 'lite-wp-logger' ) . esc_attr__( $post->post_status ) . '<br>' .
                                       __( 'Post ID: ', 'lite-wp-logger' ) . esc_attr( $post->ID ) . ', ' .
                                       '<a target="_blank" href="' . get_edit_post_link( $post->ID ) . '">' .
                                       __( 'Edit Post', 'lite-wp-logger' ) . '</a>, ' .
                                       '<a target="_blank" href="' . wp_get_post_revisions_url( $post->ID ) . '">' .
                                       __( 'Post revisions', 'lite-wp-logger' ) . '</a>',
                    )
                ), 'new_post' );
            }
        } elseif ( $post->post_status != 'auto-draft' ){
            /** Updated post */
            if ( $this->admin->getEventSetting( 'update_post' ) ) {
                if ( $post->post_type == 'page' )
                    $this->checkChange( 'parent', $post );
                $this->checkChange( 'status', $post );
                $this->checkChange( 'author', $post );
                $this->checkChange( 'visibility', $post );
                $this->checkChange( 'date', $post );
                $this->checkChange( 'permalink', $post );
                $this->checkChange( 'comment', $post );
                $this->checkChange( 'ping', $post );
                $this->checkChange( 'title', $post );
                $this->checkChange( 'content', $post );
                $this->checkChange( 'taxonomies', $post );
                $this->checkChange( 'featuredImage', $post );
                $this->checkChange( 'meta', $post );

				if ( ! empty( $this->changesList ) ) {
					$message = __( 'Post: ', 'lite-wp-logger' ) .
					           esc_attr__( $post->post_type ) .
					           __( ' modified', 'lite-wp-logger' ) . '<br>' .
					           '<b>' . __( 'Changes: ', 'lite-wp-logger' ) . '</b>';

					$inlineChanges = '';
					foreach ( $this->changesList as $change )
						$inlineChanges .= ( ( str_starts_with( $change['name'], 'meta_' ) )?
							__( 'Meta', 'lite-wp-logger' ) . ' ' . esc_attr( ltrim( $change['name'], 'meta_' ) ) :
							esc_attr__( $this->snakeToPascal( $change['name'] ), 'lite-wp-logger' ) ) . ', ';
					$inlineChanges  = rtrim( $inlineChanges, ', ' );
					$message       .= $inlineChanges . '<br>';

					foreach ( $this->changesList as $change ) {
						$message .= '<br><b>' . ( ( str_starts_with( $change['name'], 'meta_' ) )?
									__( 'Meta', 'lite-wp-logger' ) . ' ' . ltrim( $change['name'], 'meta_' ) :
									esc_attr__( $this->snakeToPascal( $change['name'] ), 'lite-wp-logger' ) ) . ' ' .
						            esc_attr__( $change['type'], 'lite-wp-logger' ) . ':</b><br>';

						if ( $change['type'] == 'modified' ) {
							$message .= __( 'Old value:', 'lite-wp-logger' ) . '<br>' .
							            $change['old_value'] . '<br>';
							$message .= __( 'New value:', 'lite-wp-logger' ) . '<br>' .
							            $change['value'] . '<br>';
						} elseif ( $change['type'] == 'added' ) {
							$message .= __( 'Value:', 'lite-wp-logger' ) . '<br>' .
							            $change['value'] . '<br>';
						} elseif ( $change['type'] == 'removed' ) {
							$message .= __( 'Old value:', 'lite-wp-logger' ) . '<br>' .
							            $change['old_value'] . '<br>';
						}
					}
					$message .= '<br><b>' . __( 'Post data:', 'lite-wp-logger' ) . '.</b><br>' . WPLogger::varLight( $post );

					$this->logger->add( array(
						'title'      => __( 'Post: ', 'lite-wp-logger' ) . esc_attr__( $post->post_type ) . __( ' modified', 'lite-wp-logger' ),
						'message'    => $message,
						'type'       => 'post_modified',
						'importance' => 'medium',
						'metas'      => array(
							'post_data' => $post,
							'post_type' => $post->post_type,
							'desc'      => __( 'Changes: ', 'lite-wp-logger' ) . $inlineChanges . '<br>' .
							               __( 'Post type: ', 'lite-wp-logger' ) . esc_attr__( $post->post_type ) . '<br>' .
							               __( 'Post status: ', 'lite-wp-logger' ) . esc_attr__( $post->post_status ) . '<br>' .
							               __( 'Post ID: ', 'lite-wp-logger' ) . esc_attr( $post->ID ) . ', ' .
							               '<a target="_blank" href="' . get_edit_post_link( $post->ID ) . '">' .
							               __( 'Edit Post', 'lite-wp-logger' ) . '</a>, ' .
							               '<a target="_blank" href="' . wp_get_post_revisions_url( $post->ID ) . '">' .
							               __( 'Post revisions', 'lite-wp-logger' ) . '</a>',
						),
					), 'update_post' );
				}
            }
        } else {
	        /** Auto Draft */
	        if ( $this->admin->getEventSetting( 'auto_draft_post' ) ){
		        $message = __( 'Auto draft post :', 'lite-wp-logger' ) . $post->post_type . '<br>' . WPLogger::varLight( $post );

		        $this->logger->add( array(
			        'title'      => __( 'Auto draft post: ', 'lite-wp-logger' ) . esc_attr__( $post->post_type ),
			        'message'    => $message,
			        'type'       => 'post_draft',
			        'importance' => 'medium',
			        'metas'      => array(
				        'post_data' => $post,
				        'post_type' => $post->post_type,
				        'desc'      => __( 'Post type: ', 'lite-wp-logger' ) . esc_attr__( $post->post_type ) . '<br>' .
				                       __( 'Post status: ', 'lite-wp-logger' ) . esc_attr__( $post->post_status ) . '<br>' .
				                       '<a target="_blank" href="' . get_edit_post_link( $postID ) . '">' .
				                       __( 'Post ID: ', 'lite-wp-logger' ) . esc_attr__( $post->ID ) . ', ' .
				                       '<a target="_blank" href="' . get_edit_post_link( $post->ID ) . '">' .
				                       __( 'Edit Post', 'lite-wp-logger' ) . '</a>, ' .
				                       '<a target="_blank" href="' . wp_get_post_revisions_url( $post->ID ) . '">' .
				                       __( 'Post revisions', 'lite-wp-logger' ) . '</a>',
			        )
		        ), 'auto_draft_post' );
	        }
        }
    }

	/**
	 * Post opened in editor
	 *
	 * @return void
	 * @throws FileNotFoundException
	 */
	public function postOpened()
	{
		global $pagenow;
		if ( 'post.php' !== $pagenow ) return;

		$postID = isset( $_GET['post'] ) ? (int) sanitize_text_field( wp_unslash( $_GET['post'] ) ) : false;
		if ( empty( $postID ) ) return;

		$post = get_post( $postID );
		if ( in_array( $post->post_type, $this->excludes['post_type'] ) ) return;

		if ( is_user_logged_in() && is_admin() ) {

			// adding current metas into a meta for changes checking
			$metas = get_post_meta( $postID );
			if( isset( $metas['_old_metas'] ) ) unset( $metas['_old_metas'] );
			update_post_meta( $postID, '_old_metas', $metas );

			$current_path = isset( $_SERVER['SCRIPT_NAME'] ) ? esc_url_raw( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) . '?post=' . $post->ID : false;
			$referrer     = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : false;

			// Check referrer URL.
			if ( ! empty( $referrer ) ) {
				// Parse the referrer.
				$parsed_url = wp_parse_url( $referrer );
				// If the referrer is post-new then we can ignore this one.
				if ( isset( $parsed_url['path'] ) && 'post-new' === basename( $parsed_url['path'], '.php' ) ) return;
			}

			// Ignore this if we were on the same page so we avoid double audit entries.
			if ( ! empty( $referrer ) && strpos( $referrer, $current_path ) !== false ) return;

			$message = __( 'Post: ', 'lite-wp-logger' ) . esc_attr__( $post->post_type ) .
			           __( ' Opened in editor', 'lite-wp-logger' ) . '<br>' .
			           WPLogger::varLight( $post );

			$this->logger->add( array(
				'title'      => __( 'Post: ', 'lite-wp-logger' ) . esc_attr__( $post->post_type ) .
				                __( ' opened in editor', 'lite-wp-logger' ),
				'message'    => $message,
				'type'       => 'post_opened',
				'importance' => 'low',
				'metas'      => array(
					'post_data' => $post,
					'post_type' => $post->post_type,
					'desc'      => __( 'Post type: ', 'lite-wp-logger' ) . esc_attr__( $post->post_type ) . '<br>' .
					               __( 'Post status: ', 'lite-wp-logger' ) . esc_attr__( $post->post_status ) . '<br>' .
					               __( 'Post ID: ', 'lite-wp-logger' ) . esc_attr( $post->ID ) . ', ' .
					               '<a target="_blank" href="' . get_edit_post_link( $post->ID ) . '">' .
					               __( 'Edit Post', 'lite-wp-logger' ) . '</a>, ' .
					               '<a target="_blank" href="' . wp_get_post_revisions_url( $post->ID ) . '">' .
					               __( 'Post revisions', 'lite-wp-logger' ) . '</a>',
				),
			), 'open_post' );
		}
	}

	/**
	 * For action on new attachment
	 *
	 * @param  integer $postID
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function addAttachment( int $postID )
    {
        remove_action( 'save_post', array( $this, 'postSave' ) );
        remove_action( 'pre_post_update', array( $this, 'beforePostEdit' ) );

        $post = get_post( $postID );
        if ( in_array( $post->post_type, $this->excludes['post_type'] ) ) return;

        $message = __( 'Attachment:', 'lite-wp-logger' ) . __( ' added:', 'lite-wp-logger' ) . '<br>' .
                   WPLogger::varLight( $post );

        $this->logger->add( array(
            'title'      => __( 'Attachment:', 'lite-wp-logger' ) . __( ' added', 'lite-wp-logger' ),
            'message'    => $message,
            'type'       => 'post_attachment',
            'importance' => 'medium',
            'metas'      => array(
                'post_data' => $post,
                'post_type' => $post->post_type,
	            'desc'      => __( 'Attachment ID:', 'lite-wp-logger' ) . esc_attr( $postID ) . '<br>' .
	                           '<a target="_blank" href="' . admin_url( 'upload.php' ) . '">' . __( 'Media library', 'lite-wp-logger' ) . '</a>'
            ),
        ), 'new_post' );
    }

    /**
     * For finding page template name
     *
     * @param  WP_Post $post
     * @return string
     * @return void
     */
    protected function getPostTemplate( WP_Post $post )
    {
        if ( ! isset( $post->ID ) ) return '';

        $id       = $post->ID;
        $template = get_page_template_slug( $id );
        $pageName = $post->post_name;

        $templates = array();
        if ( $template && 0 === validate_file( $template ) )
            $templates[] = $template;
        if ( $pageName )
            $templates[] = 'page-' . $pageName . '.php';
        if ( $id )
            $templates[] = 'page-' . $id . '.php';
        $templates[] = 'page.php';
        return get_query_template( 'page', $templates );
    }

    /**
     * For getting all taxonomies for post
     * @param  integer $postID
     * @return array
     */
    protected function getPostTaxonomies( int $postID )
    {
        if ( empty( $postID ) ) return array();
        $taxonomies = get_taxonomies( array() );
        return wp_get_object_terms( $postID, $taxonomies, array( 'fields' => 'ids' ) );
    }

	/**
	 * Before a post is sent to the Trash.
	 *
	 * @param  integer $postID
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function postTrash( int $postID )
    {
        remove_action( 'save_post', array( $this, 'postSave' ) );
        remove_action( 'pre_post_update', array( $this, 'beforePostEdit' ) );

        $post = get_post( $postID );
        if ( in_array( $post->post_type, $this->excludes['post_type'] ) || $post->post_status == 'auto-draft' ) return;

        $message = __( 'Post: ', 'lite-wp-logger' ) . esc_attr__( $post->post_type ) .
                   __( ' trashed', 'lite-wp-logger' ) . '<br>' . WPLogger::varLight( $post );

        $this->logger->add( array(
            'title'      => __( 'Post: ', 'lite-wp-logger' ) . esc_attr__( $post->post_type ) .
                            __( ' trashed ', 'lite-wp-logger' ),
            'message'    => $message,
            'type'       => 'post_trash',
            'importance' => 'high',
            'metas'      => array(
                'post_data' => $post,
                'post_type' => $post->post_type,
                'desc'      => __( 'Post type: ', 'lite-wp-logger' ) . esc_attr__( $post->post_type ),
            ),
        ), 'trash_post' );
    }

	/**
	 * Before a post is restored from the Trash
	 *
	 * @param  integer $postID
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function postUntrash( int $postID )
    {
        remove_action( 'save_post', array( $this, 'postSave' ) );
        remove_action( 'pre_post_update', array( $this, 'beforePostEdit' ) );

        $post = get_post( $postID );
        if ( in_array( $post->post_type, $this->excludes['post_type'] ) || $post->post_status == 'auto-draft' ) return;
        $message = __( 'Post: ', 'lite-wp-logger' ) . esc_attr__( $post->post_type ) .
                   __( ' untrashed:', 'lite-wp-logger' ) . '<br>' . WPLogger::varLight( $post );

        $this->logger->add( array(
            'title'      => __( 'Post: ', 'lite-wp-logger' ) . esc_attr__( $post->post_type ) . __( ' untrashed', 'lite-wp-logger' ),
            'message'    => $message,
            'type'       => 'post_untrash',
            'importance' => 'high',
            'metas'      => array(
                'post_data' => $post,
                'post_type' => $post->post_type,
                'desc'      => __( 'Post type: ', 'lite-wp-logger' ) . esc_attr__( $post->post_type ),
            ),
        ), 'untrash_post' );
    }

	/**
	 * Before a post is deleted from the database
	 *
	 * @param  integer $postID
	 * @param  WP_Post $post
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function postDelete( int $postID, WP_Post $post )
    {
        remove_action( 'save_post', array( $this, 'postSave' ) );
        remove_action( 'pre_post_update', array( $this, 'beforePostEdit' ) );

	    if ( in_array( $post->post_type, $this->excludes['post_type'] ) || $post->post_status == 'auto-draft' ) return;

        $message = __( 'Post: ', 'lite-wp-logger' ) . esc_attr__( $post->post_type ) .
                   __( ' deleted:', 'lite-wp-logger' ) . '<br>' . WPLogger::varLight( $post );

        $this->logger->add( array(
            'title'      => __( 'Post: ', 'lite-wp-logger' ) . esc_attr__( $post->post_type ) . __( ' deleted', 'lite-wp-logger' ),
            'message'    => $message,
            'type'       => 'post_delete',
            'importance' => 'high',
            'metas'      => array(
                'post_data' => $post,
                'post_type' => $post->post_type,
                'desc'      => __( 'Post type: ', 'lite-wp-logger' ) . esc_attr__( $post->post_type ),
            ),
        ), 'delete_post' );
    }

	/**
	 * Checking post sticky changes
	 *
	 * @param  integer $postID
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function postStuck( int $postID )
    {
        $post = get_post( $postID );
        if ( in_array( $post->post_type, $this->excludes['post_type'] ) ) return;

	    $sticky = in_array( $post->ID, get_option( 'sticky_posts' ), true );
	    if ( $this->old_sticky != $sticky ) {
		    $pre_message = ( $sticky )? __( 'added to', 'lite-wp-logger' ) : __( 'removed from', 'lite-wp-logger' );
		    $message     = __( 'Post: ', 'lite-wp-logger' ) . esc_attr__( $post->post_type ) . ' ' .
		                   esc_attr( $pre_message ) . ' ' . __( 'sticky', 'lite-wp-logger' ) . '<br>' .
		                   WPLogger::varLight( $post );

		    $this->logger->add( array(
			    'title'      => __( 'Post ', 'lite-wp-logger' ) . esc_attr( $pre_message ) . ' ' . __( 'sticky', 'lite-wp-logger' ),
			    'message'    => $message,
			    'type'       => 'post_sticky',
			    'importance' => 'medium',
			    'metas'      => array(
				    'post_data' => $post,
				    'post_type' => $post->post_type,
				    'desc'      => __( 'Post type: ', 'lite-wp-logger' ) . esc_attr__( $post->post_type ) . '<br>' .
				                   __( 'Post status: ', 'lite-wp-logger' ) . esc_attr__( $post->post_status ) . '<br>' .
				                   __( 'Post ID: ', 'lite-wp-logger' ) . esc_attr( $post->ID ) . '. ' .
				                   '<a target="_blank" href="' . get_edit_post_link( $post->ID ) . '">' .
				                   __( 'Edit Post', 'lite-wp-logger' ) . '</a>',
			    ),
		    ), 'sticky_post' );
	    }
    }

	/**
	 * For logging term creation
	 *
	 * @param  int $termID
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function termCreation( int $termID )
    {
        $term = get_term( $termID );
        if ( in_array( $term->taxonomy, $this->excludes['taxonomy'] ) ) return;

        $message = __( 'Term created. id: ', 'lite-wp-logger' ) . esc_attr( $termID ) . '<br>'.
                   __( 'Name: ', 'lite-wp-logger' ) . esc_attr( $term->name ) . '<br>' .
                   __( 'Taxonomy: ', 'lite-wp-logger' ) . esc_attr( $term->taxonomy ) . '<br>' . WPLogger::varLight( $term );

        $this->logger->add( array(
            'title'      => __( 'Term created', 'lite-wp-logger' ) . ': ' . esc_attr( $term->name ),
            'message'    => $message,
            'type'       => 'term_create',
            'importance' => 'medium',
	        'metas'      => array(
				'term_data' => $term,
				'desc'      => __( 'Term ID: ', 'lite-wp-logger' ) . esc_attr( $termID ) . '<br>' .
				               __( 'Taxonomy: ', 'lite-wp-logger' ) . esc_attr( $term->taxonomy ),
	        ),
        ), 'term_create' );
    }

	/**
	 * For logging term deletion
	 *
	 * @param  int $termID
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function termDeletion( int $termID )
    {
        $term = get_term( $termID );
        if ( in_array( $term->taxonomy, $this->excludes['taxonomy'] ) ) return;

        $term = get_term( $termID );
        $message = __( 'Term deleted. id: ', 'lite-wp-logger' ) . esc_attr( $termID ) . '<br>' .
                   __( 'Name: ', 'lite-wp-logger' ) . esc_attr( $term->name ) . '<br>' .
                   __( 'Taxonomy: ', 'lite-wp-logger' ) . esc_attr( $term->taxonomy ) . '<br>' . WPLogger::varLight( $term );

        $this->logger->add( array(
            'title'      => __( 'Term deleted', 'lite-wp-logger' ) . ': ' . $term->name,
            'message'    => $message,
            'type'       => 'term_delete',
            'importance' => 'high',
            'metas'      => array(
	            'term_data' => $term,
	            'desc'      => __( 'Term ID: ', 'lite-wp-logger' ) . esc_attr( $termID ) . '<br>' .
	                           __( 'Taxonomy: ', 'lite-wp-logger' ) . esc_attr( $term->taxonomy ),
            ),
        ), 'term_delete' );
    }

	/**
	 * For logging term update
	 *
	 * @param  array  $data
	 * @param  int    $termID
	 * @param  string $taxonomy
	 * @param  array  $args
	 * @return void
	 * @throws FileNotFoundException
	 */
    public function updateTermData( array $data, int $termID, string $taxonomy, array $args )
    {
        if ( in_array( $taxonomy, $this->excludes['taxonomy'] ) ) return;

        $term    = get_term( $termID );
        $message = __( 'Term updated', 'lite-wp-logger' ) . '<br>' . __( 'Update data:', 'lite-wp-logger' ) .
                   '<br>' . WPLogger::varLight( $data ) . '<br>' . __( 'Arguments:', 'lite-wp-logger' ) .
                   '<br>' . WPLogger::varLight( $args ) . '<br>' . WPLogger::varLight( $term );

        $this->logger->add( array(
            'title'      => __( 'Term updated', 'lite-wp-logger' ) . ': ' . esc_attr( $term->name ),
            'message'    => $message,
            'type'       => 'term_update',
            'importance' => 'medium',
            'metas'      => array(
	            'term_data' => $term,
	            'desc'      => __( 'Term ID: ', 'lite-wp-logger' ) . esc_attr( $termID ) . '<br>' .
	                           __( 'Taxonomy: ', 'lite-wp-logger' ) . esc_attr( $term->taxonomy ),
            ),
        ), 'term_update' );
    }

    /**
     * For calling function defined in item to check changes
     *
     * @param  string  $item
     * @param  WP_Post $post
     * @param  mixed   ...$args
     * @return false|void
     */
    protected function checkChange( string $item, WP_Post $post, ...$args )
    {
        return call_user_func( array( $this, 'check' . ucfirst( $item ) . 'Change' ), $post , ...$args );
    }

	/**
	 * Check Post status Change
	 *
	 * @param  WP_Post $post
	 * @return void
	 */
	protected function checkStatusChange( WP_Post $post )
	{
		$from = $this->old_post->post_status;
		$to   = $post->post_status;

		if ( $from != $to )
			$this->changesList[] = array(
				'name'      => 'status',
				'type'      => 'modified',
				'value'     => $post->post_status,
				'old_value' => $this->old_post->post_status,
			);
	}

	/**
	 * For checking parent change of page
	 *
	 * @param  WP_Post $post
	 * @return void
	 */
	protected function checkParentChange( WP_Post $post )
	{
		if ( $post->post_type == 'page' && $this->old_post->post_parent != $post->post_parent )
			$this->changesList[] = array(
				'name'      => 'parent',
				'type'      => 'modified',
				'value'     => '<a target="_blank" href="' . get_permalink( $post->post_parent ) . '">' .
				               get_the_title( $post->post_parent ) . '</a>',
				'old_value' => '<a target="_blank" href="' . get_permalink( $this->old_post->post_parent ) . '">' .
				               get_the_title( $this->old_post->post_parent ) . '</a>',
			);
	}

    /**
     * For checking author change of post
     *
     * @param  WP_Post $post
     * @return void
     */
    protected function checkAuthorChange( WP_Post $post )
    {
        if ( ! $this->old_post ) return;
        if ( $this->old_post->post_author == $post->post_author ) return;

        $old_author = get_userdata( $this->old_post->post_author );
        $old_author = is_object( $old_author )? $old_author->data->user_login . '(' . $this->old_post->post_author . ')' : 'N/A';
        $new_author = get_userdata( $post->post_author );
        $new_author = is_object( $new_author )? $new_author->data->user_login . '(' . $post->post_author . ')' : 'N/A';

	    $this->changesList[] = array(
		    'name'      => 'author',
		    'type'      => 'modified',
		    'value'     => $new_author,
		    'old_value' => $old_author,
	    );
    }

    /**
     * For checking visibility change of post
     *
     * @param  WP_Post $post
     * @return void
     */
    protected function checkVisibilityChange( WP_Post $post )
    {
        if ( $this->old_post->post_password ) $old_visibility = 'Password Protected';
        elseif ( $this->old_post->post_status == 'private' ) $old_visibility = 'Private';
        else $old_visibility = 'Public';

        if ( $post->post_password ) $new_visibility = 'Password Protected';
        elseif ( $post->post_status == 'private' ) $new_visibility = 'Private';
        else $new_visibility = 'Public';

        if ( $old_visibility != $new_visibility )
	        $this->changesList[] = array(
		        'name'      => 'visibility',
		        'type'      => 'modified',
		        'value'     => $new_visibility,
		        'old_value' => $old_visibility,
	        );
    }

    /**
     * For checking date change of post
     *
     * @param  WP_Post $post
     * @return void
     */
    protected function checkDateChange( WP_Post $post )
    {
        if ( $this->old_post->post_status == 'pending' ) return;

        $from = strtotime( $this->old_post->post_date );
        $to   = strtotime( $post->post_date );

        if ( $from != $to )
	        $this->changesList[] = array(
		        'name'      => 'date',
		        'type'      => 'modified',
		        'value'     => $post->post_date,
		        'old_value' => $this->old_post->post_date,
	        );
    }

    /**
     * For checking permalink change of post
     *
     * @param  WP_Post $post
     * @return void
     */
    protected function checkPermalinkChange( WP_Post $post )
    {
        if ( in_array( $post->post_status, array( 'draft', 'pending' ), true ) ) {
            $old_link = $this->old_post->post_name;
            $new_link = $post->post_name;
        } else {
            $old_link = $this->old_link;
            $new_link = get_permalink( $post->ID );
        }

        if ( $old_link != $new_link )
	        $this->changesList[] = array(
		        'name'      => 'permalink',
		        'type'      => 'modified',
		        'value'     => $new_link,
		        'old_value' => $old_link,
	        );
    }

    /**
     * For checking comment status change of post
     *
     * @param  WP_Post $post
     * @return void
     */
    protected function checkCommentChange( WP_Post $post )
    {
        if ( $this->old_post->comment_status != $post->comment_status )
	        $this->changesList[] = array(
		        'name'      => 'comment_status',
		        'type'      => 'modified',
		        'value'     => $post->comment_status,
		        'old_value' => $this->old_post->comment_status,
	        );
    }

    /**
     * For checking ping status change of post
     *
     * @param  WP_Post $post
     * @return void
     */
    protected function checkPingChange( WP_Post $post )
    {
        if ( $this->old_post->ping_status != $post->ping_status )
	        $this->changesList[] = array(
		        'name'      => 'ping_status',
		        'type'      => 'modified',
		        'value'     => $post->ping_status,
		        'old_value' => $this->old_post->ping_status,
	        );
    }

    /**
     * For checking title change of post
     *
     * @param  WP_Post $post
     * @return void
     */
    protected function checkTitleChange( WP_Post $post )
    {
        if ( $this->old_post->post_title != $post->post_title )
	        $this->changesList[] = array(
		        'name'      => 'title',
		        'type'      => 'modified',
		        'value'     => $post->post_title,
		        'old_value' => $this->old_post->post_title,
	        );
    }

    /**
     * For checking content change of post
     *
     * @param  WP_Post $post
     * @return void
     */
    protected function checkContentChange( WP_Post $post )
    {
        if ( $this->old_post->post_content != $post->post_content )
	        $this->changesList[] = array(
		        'name'      => 'content',
		        'type'      => 'modified',
		        'value'     => '<div class="logger-varlight"><div class="varlight-inner">' . $post->post_content . '</div></div>',
		        'old_value' => '<div class="logger-varlight"><div class="varlight-inner">' . $this->old_post->post_content . '</div></div>',
	        );
    }

	/**
	 * For checking content change of post
	 *
	 * @param  WP_Post $post
	 * @return void
	 * @throws FileNotFoundException
	 */
    protected function checkTaxonomiesChange( WP_Post $post )
    {
        $taxes = $this->getPostTaxonomies( $post->ID );

        if ( ! empty( array_merge(
            array_diff( $taxes, $this->old_taxes ),
            array_diff( $this->old_taxes, $taxes )
        ) ) ) {

            $message = __( 'Post: ', 'lite-wp-logger' ) .
                       esc_attr__( $post->post_type ) .
                       ' ' . __( 'Taxes changes from: ', 'lite-wp-logger' ) . esc_attr( implode( ',', $this->old_taxes ) ) .
                       ' ' . __( 'to: ', 'lite-wp-logger' ) . implode( ',', $taxes ) . '<br>' .
                       WPLogger::varLight( $post );

            $this->logger->add( array(
                'title'      => __( 'Post: ', 'lite-wp-logger' ) . esc_attr__( $post->post_type ) .
                                ' ' . __( 'taxonomies changed', 'lite-wp-logger' ),
                'message'    => $message,
                'type'       => 'post_taxes',
                'importance' => 'medium',
                'metas'      => array(
                    'post_data' => $post,
                    'post_type' => $post->post_type,
                    'desc'      => __( 'Post type: ', 'lite-wp-logger' ) . esc_attr__( $post->post_type ) . '<br>' .
                                   __( 'Post status: ', 'lite-wp-logger' ) . esc_attr__( $post->post_status ) . '<br>' .
                                   __( 'Post ID: ', 'lite-wp-logger' ) . esc_attr( $post->ID ) . ', ' .
			                       '<a target="_blank" href="' . get_edit_post_link( $post->ID ) . '">' .
			                       __( 'Edit Post', 'lite-wp-logger' ) . '</a>, ' .
			                       '<a target="_blank" href="' . wp_get_post_revisions_url( $post->ID ) . '">' .
			                       __( 'Post revisions', 'lite-wp-logger' ) . '</a>',
                ),
            ), 'update_post' );
        }
    }

    /**
     * Check Page Template Update
     *
     * @param  WP_Post $post
     * @param  string  $meta_value
     * @return void
     */
    protected function checkTemplateChange( WP_Post $post, string $meta_value )
    {
        $old_tmpl = ( $this->old_template && 'page' !== basename( $this->old_template, '.php' ) )?
            ucwords( str_replace( array( '-', '_' ), ' ', basename( $this->old_template, '.php' ) ) ) :
            __( 'Default template', 'lite-wp-logger' );

        $new_tmpl = ( $meta_value )?
            ucwords( str_replace( array( '-', '_' ), ' ', basename( $meta_value ) ) ) :
            __( 'Default', 'lite-wp-logger' );

        if ( $old_tmpl!= $new_tmpl )
	        $this->changesList[] = array(
		        'name'      => 'template',
		        'type'      => 'modified',
		        'value'     => $new_tmpl,
		        'old_value' => $old_tmpl,
	        );
    }

    /**
     * For checking changes in post featured image
     *
     * @param  WP_Post $post
     * @return void
     */
    protected function checkFeaturedImageChange( WP_Post $post )
    {
	    $prevID     = $this->old_metas['_thumbnail_id'][0];
        $prevImage = ( isset( $prevID ) )?
            wp_get_attachment_metadata( $this->old_metas['_thumbnail_id'][0] ) : false;

		$newID     = get_post_meta( $post->ID, '_thumbnail_id', true );
        $newImage  = wp_get_attachment_metadata( $newID );

        if ( empty( $newImage['file'] ) && empty( $prevImage['file'] ) ) return;
	    if ( $newImage['file'] == $prevImage['file'] ) return;

        $type = 'modified';
        if ( empty( $prevImage['file'] ) && ! empty( $newImage['file'] ) )
            $type  = 'added';
        elseif ( ! empty( $prevImage['file'] ) && empty( $newImage['file'] ) )
            $type  = 'removed';

	    $this->changesList[] = array(
		    'name'      => 'image',
		    'type'      => $type,
		    'value'     => ( empty( $newImage['file'] ) )? null : (
			    wp_get_attachment_image( $newID ) . '<br>' . $newImage['file']
		    ),
		    'old_value' => ( empty( $prevImage['file'] ) )? null : (
			    wp_get_attachment_image( $prevID ) . '<br>' . $prevImage['file']
		    ),
	    );
    }

    /**
     * For checking changes in post meta
     *
     * @param  WP_Post $post
     * @return void
     */
    protected function checkMetaChange( WP_Post $post )
    {
        $metas = get_post_meta( $post->ID );
		unset( $metas['_old_metas'] );
        // changed or added metas
        foreach ( $metas as $key => $meta ) {
            if ( in_array( $key, $this->excludes['meta'] ) ) continue;

            $prevMeta = ( isset( $this->old_metas[ $key ][0] ) )? $this->old_metas[ $key ][0] : false;
            $newMeta  = $meta[0];

            if ( empty( $newMeta ) && empty( $prevMeta ) ) continue;
            if ( $newMeta == $prevMeta ) continue;

            if ( empty( $prevMeta ) && ! empty( $newMeta ) ) $type = 'added';
            else $type = 'modified';

	        $this->changesList[] = array(
		        'name'      => 'meta_' . $key,
		        'type'      => $type,
		        'value'     => '<div class="logger-varlight"><div class="varlight-inner">' . $newMeta . '</div></div>',
		        'old_value' => '<div class="logger-varlight"><div class="varlight-inner">' . $prevMeta . '</div></div>',
	        );
        }
        /** removed metas */
        $k1     = array_keys( $metas );
        $k2     = array_keys( $this->old_metas );
        $kDiffs = array_diff( $k2, $k1 );

        if ( ! empty( $kDiffs ) )
            foreach ( $kDiffs as $kDiff ){
	            if ( in_array( $kDiff, $this->excludes['meta'] ) ) continue;
	            $this->changesList[] = array(
		            'name'      => 'meta_' . $kDiff,
		            'type'      => 'removed',
		            'old_value' => '<div class="logger-varlight"><div class="varlight-inner">' .
		                           $this->old_metas[ $kDiff ][0] . '</div></div>',
	            );
            }
    }

	/**
	 * For convert snake case to pascal case
	 *
	 * @param  string $snake_string
	 * @return string
	 */
	public function snakeToPascal( string $snake_string )
	{
		return ucwords( str_replace( '_', ' ', $snake_string ) );
	}

}