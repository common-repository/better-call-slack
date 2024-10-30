<?php
class User_Delete_BCS_Event_Handler extends BCS_Event {
	public function __construct() {
		parent::__construct( 'user-delete', 'delete_user', 10, 2 );

		// Set default values.
		$this->set_message( __( 'User :username: was just removed!', 'better-call-slack' ) );
		$this->set_placeholder( 'username' );
		$this->set_placeholder( 'email' );
	}

	public function get_post_type_option() {
		return array(
			'category'    => 'wordpress',
			'subcategory' => 'user',
			'title'       => __( 'When a user is deleted.', 'better-call-slack' ),
		);
	}


	public function handler( $user_id, $reassign ) {
		$user = get_userdata( $user_id );
		$this->set_placeholder( 'username', $user->user_login );
		$this->set_placeholder( 'email', $user->user_email );

		return true;
	}

}
