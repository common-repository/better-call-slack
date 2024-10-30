<?php
class Comment_New_Comment_BCS_Event_Handler extends BCS_Event {
	public function __construct() {
		parent::__construct( 'comment-new-comment', 'comment_post', 10, 3 );

		// Set default values.
		$this->set_message( __( 'A new comment was just added, marked as :status: in :post_type: - :title:', 'better-call-slack' ) );
		$this->set_placeholder( 'post_type' );
		$this->set_placeholder( 'title' );
		$this->set_placeholder( 'status' );
	}

	public function get_post_type_option() {
		return array(
			'category'    => 'wordpress',
			'subcategory' => 'comments',
			'title'       => __( 'When a comment is added to the database.', 'better-call-slack' ),
		);
	}


	public function handler( $comment_id, $comment_approved, $commentdata ) {
		$comment_id = intval( $comment_id );
		$post       = get_post( get_comment( $comment_id )->comment_post_ID );
		$post_type  = get_post_type_object( get_post_type( $post ) );

		$status = '';
		switch ( $comment_approved ) {
			case 'spam':
				$status = __( 'spam', 'better-call-slack' );
				break;
			case 1:
			case '1':
				$status = __( 'approved', 'better-call-slack' );
				break;
			case 0:
			case '0':
				$status = __( 'not approved', 'better-call-slack' );
				break;
			default:
				if ( is_wp_error( $comment_approved ) ) {
					$status = $comment_approved->get_error_message();
				} else {
					$status = __( 'unknown status', 'better-call-slack' );
				}
		}
		$status = apply_filters( 'bcs_event_comment_new_comment_approved_status', $status, $comment_approved );

		$this->set_placeholder( 'post_type', $post_type->labels->singular_name );
		$this->set_placeholder( 'title', html_entity_decode( get_the_title( $post ), ENT_QUOTES ) );
		$this->set_placeholder( 'status', $status );
		$this->message_action = get_edit_comment_link( $comment_id );

		return true;
	}

}
