<?php
class Post_Transition_Post_Status_BCS_Event_Handler extends BCS_Event {
	public function __construct() {
		parent::__construct( 'post-transition-post-status', 'transition_post_status', 10, 3 );

		// Set default values.
		$this->set_message( __( ':post_type: status for :title: was changed from :old_status: to :new_status: by :current_user:.', 'better-call-slack' ) );
		$this->set_placeholder( 'post_type' );
		$this->set_placeholder( 'title' );
		$this->set_placeholder( 'old_status' );
		$this->set_placeholder( 'new_status' );

		$this->bulk_support = true;
		/* translators: :count: is a number. :status: is a comment status such as deleted, spam, approved, etc. */
		$this->bulk_message = __( 'The statuses of :count: items were just changed to :new_status: by :current_user:.', 'better-call-slack' );
		$this->bulk_field_title = ':title:';
		$this->bulk_field_value = '';
	}

	public function get_post_type_option() {
		return array(
			'category'    => 'wordpress',
			'subcategory' => 'posts',
			'title'       => __( 'When the status of a post has changed.', 'better-call-slack' ),
		);
	}


	public function handler( $new_status, $old_status, $post ) {

		// Ignore unrelated changes (E.g. Change the author of a post)
		if ( $new_status === $old_status ) {
			return false;
		}

		// Ignore 'auto-draft'
		if ( 'auto-draft' === $new_status || 'auto-draft' === $old_status ) {
			return false;
		}

		// Ignore revisions-related statuses
		if ( 'inherit' === $new_status ) {
			return false;
		}

		$post_type = get_post_type_object( get_post_type( $post ) );

		if ( 'revision' === $post_type ) {
			return false;
		}

		if ( false === $post_type->public || in_array( $post_type->name, $this->post_meta['post_type_exclude'], true ) ) {
			return false;
		}

		$this->set_placeholder( 'post_type', $post_type->labels->singular_name );
		$this->set_placeholder( 'title', html_entity_decode( get_the_title( $post ), ENT_QUOTES ) );
		$this->set_placeholder( 'old_status', $old_status );
		$this->set_placeholder( 'new_status', $new_status );

		if ( ! $this->is_bulk() && 'publish' === $new_status ) {
			$this->message_action = get_edit_post_link( $post->ID, 'raw' );
		} else {
			$this->message_action = admin_url( sprintf( '/edit.php?post_type=%s&post_status=%s',
				$post->post_type,
				$new_status
			) );
		}

		return true;
	}

}
