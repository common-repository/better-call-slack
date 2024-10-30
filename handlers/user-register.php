<?php
class User_Register_BCS_Event_Handler extends BCS_Event {
	public function __construct() {
		parent::__construct( 'user-register', 'user_register', 10, 1 );

		// Set default values.
		$this->set_message( __( 'User :username: just joined us!', 'better-call-slack' ) );
		$this->set_placeholder( 'username' );
		$this->set_placeholder( 'email' );
	}

	public function get_post_type_option() {
		return array(
			'category'    => 'wordpress',
			'subcategory' => 'user',
			'title'       => __( 'When a new user registers.', 'better-call-slack' ),
		);
	}


	public function handler( $user_id ) {
		$user = get_userdata( $user_id );
		$this->set_placeholder( 'username', $user->user_login );
		$this->set_placeholder( 'email', $user->user_email );

		$this->message_action = get_edit_user_link( $user->ID );

		return true;
	}

}
