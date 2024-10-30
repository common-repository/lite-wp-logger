<?php
/**
 * WPLogger: Comment
 *
 * Used for comments logging.
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
use WP_Comment;
use WPLogger\WPLogger;
use WPLogger\Plugin\Admin;

/**
 * Class Comment for logging
 *
 * @package WPLogger
 */
class Comment {

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
	 * Comment class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		 $this->logger = new Logger();
		$this->admin   = new Admin();
		$this->admin->setEventsSettingsFields();
		$this->admin->retrieveEventsSettings();
		/**
		 * Comment actions
		 */
		if ( $this->admin->getEventSetting( 'new_comments' ) ) {
			add_action( 'comment_post', array( $this, 'comment' ), 10, 3 );
		}
		if ( $this->admin->getEventSetting( 'edit_comments' ) ) {
			add_action( 'edit_comment', array( $this, 'commentEdit' ), 10, 2 );
		}
		if ( $this->admin->getEventSetting( 'comments_status' ) ) {
			add_action( 'transition_comment_status', array( $this, 'commentStatusTrans' ), 10, 3 );
		}
		if ( $this->admin->getEventSetting( 'comments_spam' ) ) {
			add_action( 'spammed_comment', array( $this, 'commentSpam' ), 10, 2 );
		}
		if ( $this->admin->getEventSetting( 'comments_unspam' ) ) {
			add_action( 'unspammed_comment', array( $this, 'commentUnspam' ), 10, 2 );
		}
		if ( $this->admin->getEventSetting( 'comments_trash' ) ) {
			add_action( 'trashed_comment', array( $this, 'commentTrash' ), 10, 2 );
		}
		if ( $this->admin->getEventSetting( 'comments_untrash' ) ) {
			add_action( 'untrashed_comment', array( $this, 'commentUntrash' ), 10, 2 );
		}
		if ( $this->admin->getEventSetting( 'comments_delete' ) ) {
			add_action( 'deleted_comment', array( $this, 'commentDeleted' ), 10, 2 );
		}
	}

	/**
	 * Initialize this class for direct usage
	 *
	 * @return Comment
	 */
	public static function initialize(): Comment {
		return new self();
	}

	/**
	 * Fires immediately after a comment is inserted
	 *
	 * @param  int   $commentID
	 * @param  mixed $comment_approved
	 * @param  array $commentData
	 * @return void
	 * @throws FileNotFoundException
	 */
	public function comment( int $commentID, $comment_approved, array $commentData ) {
		/** Check if the comment is response to another comment. */
		if ( isset( $commentData['comment_parent'] ) && $commentData['comment_parent'] ) {
			$this->commentLog( $commentID, 'new_reply' );
			return;
		}
		/** new comment */
		if ( $comment_approved != 'spam' ) {
			$this->commentLog( $commentID, 'new' );
		}
	}

	/**
	 * Fires immediately after a comment is updated in the database.
	 *
	 * @param  int   $commentID
	 * @param  array $data
	 * @return void
	 * @throws FileNotFoundException
	 */
	public function commentEdit( int $commentID, array $data ) {
		$message = __( 'Changed data:', 'lite-wp-logger' ) . '<br>' . WPLogger::varLight( $data );
		$this->commentLog( $commentID, 'updated', null, $message );
	}

	/**
	 * Fires when the comment status is in transition
	 *
	 * @param  int|string $new_status
	 * @param  int|string $old_status
	 * @param  WP_Comment $comment
	 * @return void
	 * @throws FileNotFoundException
	 */
	public function commentStatusTrans( $new_status, $old_status, WP_Comment $comment ) {
		$skipList = array( 'spam', 'trash', 'delete' );
		if ( in_array( $new_status, $skipList ) || in_array( $old_status, $skipList ) ) {
			return;
		}

		$message = __( 'Changed from ', 'lite-wp-logger' ) . __( $old_status, 'lite-wp-logger' ) .
			__( ' status to ', 'lite-wp-logger' ) . __( $new_status, 'lite-wp-logger' );
		$this->commentLog( $comment->comment_ID, 'status_changed', $comment, $message );
	}

	/**
	 * Fires immediately after a comment is marked as Spam
	 *
	 * @param  int        $commentID
	 * @param  WP_Comment $comment
	 * @return void
	 * @throws FileNotFoundException
	 */
	public function commentSpam( int $commentID, WP_Comment $comment ) {
		$this->commentLog( $commentID, 'spammed', $comment );
	}

	/**
	 * Fires immediately after a comment is unmarked as Spam.
	 *
	 * @param  int        $commentID
	 * @param  WP_Comment $comment
	 * @return void
	 * @throws FileNotFoundException
	 */
	public function commentUnspam( int $commentID, WP_Comment $comment ) {
		$this->commentLog( $commentID, 'unspammed', $comment );
	}

	/**
	 * Fires immediately after a comment is sent to Trash.
	 *
	 * @param  int        $commentID
	 * @param  WP_Comment $comment
	 * @return void
	 * @throws FileNotFoundException
	 */
	public function commentTrash( int $commentID, WP_Comment $comment ) {
		$this->commentLog( $commentID, 'trashed', $comment );
	}

	/**
	 * Fires immediately after a comment is restored from the Trash.
	 *
	 * @param int        $commentID
	 * @param WP_Comment $comment
	 * @return void
	 * @throws FileNotFoundException
	 */
	public function commentUntrash( int $commentID, WP_Comment $comment ) {
		 $this->commentLog( $commentID, 'untrashed', $comment );
	}

	/**
	 * Fires immediately after a comment is deleted from the database.
	 *
	 * @param int        $commentID
	 * @param WP_Comment $comment
	 * @return void
	 * @throws FileNotFoundException
	 */
	public function commentDeleted( int $commentID, WP_Comment $comment ) {
		 $this->commentLog( $commentID, 'deleted', $comment );
	}

	/**
	 * Comment logger
	 *
	 * @param  int             $commentID
	 * @param  string          $type
	 * @param  WP_Comment|null $comment
	 * @param  string          $message
	 * @return void
	 * @throws FileNotFoundException
	 */
	protected function commentLog( int $commentID, string $type, WP_Comment $comment = null, string $message = '' ) {
		if ( ! $comment ) {
			$comment = get_comment( $commentID );
		}
		if ( empty( $message ) ) {
			$message = esc_attr__( $this->unSlug( $type ) . ' Comment.', 'lite-wp-logger' ) .
				   '<br>' . WPLogger::varLight( $comment );
		}

		$skipList    = array( 'spam', 'trash', 'delete' );
		$loggerEvent = '';

		if ( $type == 'new' || $type == 'new_reply' ) {
			$loggerEvent = 'new_comments';
		} elseif ( $type == 'updated' ) {
			$loggerEvent = 'edit_comments';
		} elseif ( $type == 'status_changed' ) {
			$loggerEvent = 'comments_status';
		} elseif ( $type == 'spammed' ) {
			$loggerEvent = 'comments_spam';
		} elseif ( $type == 'unspammed' ) {
			$loggerEvent = 'comments_unspam';
		} elseif ( $type == 'trashed' ) {
			$loggerEvent = 'comments_trash';
		} elseif ( $type == 'untrashed' ) {
			$loggerEvent = 'comments_untrash';
		} elseif ( $type == 'deleted' ) {
			$loggerEvent = 'comments_delete';
		}

		$this->logger->add(
			array(
				'title'      => esc_attr__( $this->unSlug( $type ) . ' comment', 'lite-wp-logger' ) . ' ' .
								__( 'on post', 'lite-wp-logger' ) . ' ' . esc_attr( get_the_title( $comment->comment_post_ID ) ),
				'message'    => $message,
				'type'       => 'comment_' . $type,
				'importance' => 'low',
				'metas'      => array(
					'comment_data' => $comment,
					'desc'         => __( 'Post type: ', 'lite-wp-logger' ) . get_post_type( $comment->comment_post_ID ) . '<br>' .
									 __( 'Comment ID: ', 'lite-wp-logger' ) . esc_attr( $commentID ) . '<br>' .
									 ( ( in_array( wp_get_comment_status( $commentID ), $skipList ) ) ? '' :
									 '<a target="_blank" href="' . get_permalink( $comment->comment_post_ID ) . '#comment-' .
									 esc_attr( $commentID ) . '">' . __( 'Comment URL', 'lite-wp-logger' ) . '</a>' . ' . ' .
									 '<a target="_blank" href="' . get_edit_comment_link( $commentID ) . '">' .
									 __( 'Edit', 'lite-wp-logger' ) . '</a>' ),
				),
			),
			$loggerEvent
		);
	}

	/**
	 * For making better to read
	 *
	 * @param  string $text
	 * @return string
	 */
	private function unSlug( string $text ) {
		$text = str_replace( '_', ' ', $text );
		return ucfirst( $text );
	}
}
