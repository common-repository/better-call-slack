<?php
class Comment_Set_Comment_Status_BCS_Event_Handler extends BCS_Event {
	public function __construct() {
		parent::__construct( 'comment-set-comment-status', 'wp_set_comment_status', 10, 2 );

		// Set default values.
		$this->set_message( __( ':post_type: :title: A comment was just :status:', 'better-call-slack' ) );
		$this->set_placeholder( 'post_type' );
		$this->set_placeholder( 'title' );
		$this->set_placeholder( 'status' );

		$this->bulk_support = true;
		/* translators: :count: is a number. :status: is a comment status such as deleted, spam, approved, etc. */
		$this->bulk_message = __( ':count: comments were just marked as ":status:"', 'better-call-slack' );
		$this->bulk_field_title = ':post_type:';
		$this->bulk_field_value = ':title:';
	}

	public function get_post_type_option() {
		return array(
			'category'    => 'wordpress',
			'subcategory' => 'comments',
			'title'       => __( "When a comment's status changes.", 'better-call-slack' ),
		);
	}


	public function handler( $comment_id, $comment_status ) {
		$comment_id = intval( $comment_id );
		$post       = get_post( get_comment( $comment_id )->comment_post_ID );
		$post_type  = get_post_type_object( get_post_type( $post ) );

		$status = '';
		switch ( $comment_status ) {
			case 'delete':
				$status = __( 'deleted', 'better-call-slack' );
				break;
			case 'approve':
				$status = __( 'approved', 'better-call-slack' );
				break;
			case 'spam':
				$status = __( 'spammed', 'better-call-slack' );
				break;
			case 'hold':
				$status = __( 'held', 'better-call-slack' );
				break;
			case 'trash':
				$status = __( 'trashed', 'better-call-slack' );
				break;
			case '1':
				$status = __( 'untrashed', 'better-call-slack' );
				break;
			case '0':
				$status = __( 'unspammed', 'better-call-slack' );
				break;
		}

		$this->set_placeholder( 'post_type', $post_type->labels->singular_name );
		$this->set_placeholder( 'title', html_entity_decode( get_the_title( $post ), ENT_QUOTES ) );
		$this->set_placeholder( 'status', $status );

		if ( ! $this->is_bulk() && $this->times_run() < 2 ) {
			$this->message_action = get_edit_comment_link( $comment_id );
		} else {
			$this->message_action = admin_url( '/edit-comments.php' );
		}

		return true;
	}

}
