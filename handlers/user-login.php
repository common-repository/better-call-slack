<?php
class User_Login_BCS_Event_Handler extends BCS_Event {
	public function __construct() {
		parent::__construct( 'user-login', 'wp_login', 10, 2 );

		// Set default values.
		$this->set_message( __( 'User :username: just logged in!', 'better-call-slack' ) );
		$this->set_placeholder( 'username' );
		$this->set_placeholder( 'email' );
	}

	public function get_post_type_option() {
		return array(
			'category'    => 'wordpress',
			'subcategory' => 'user',
			'title'       => __( 'When a user logs in.', 'better-call-slack' ),
		);
	}


	public function handler( $user_login, $user ) {
		$this->set_placeholder( 'username', $user->user_login );
		$this->set_placeholder( 'email', $user->user_email );

		$this->message_action = get_edit_user_link( $user->ID );

		return true;
	}

}
