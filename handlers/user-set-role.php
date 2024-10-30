<?php
class User_Set_Role_BCS_Event_Handler extends BCS_Event {
	public function __construct() {
		parent::__construct( 'user-set-role', 'set_user_role', 10, 3 );

		// Set default values.
		$this->set_message( __( "User :username:'s role changed from :old_roles: to :new_role:.", 'better-call-slack' ) );
		$this->set_placeholder( 'username' );
		$this->set_placeholder( 'old_roles' );
		$this->set_placeholder( 'new_role' );
	}

	public function get_post_type_option() {
		return array(
			'category'    => 'wordpress',
			'subcategory' => 'user',
			'title'       => __( "When a user's role is changed.", 'better-call-slack' ),
		);
	}

	public function handler( $user_id, $role, $old_roles ) {
		global $pagenow;

		$current_user = get_current_user_id();

		// Don't trigger for registrations using the default form.
		if ( 'wp-login.php' === $pagenow && 0 === $current_user ) {
			return false;
		}

		// Don't trigger for registrations using the admin form, that have the default role.
		if ( 'user-new.php' === $pagenow && get_option( 'default_role' ) === $role ) {
			return false;
		}

		$user = get_userdata( $user_id );

		if ( empty( $role ) ) {
			$roles = 'no-role';
		}

		if ( empty( $old_roles ) ) {
			$old_roles = array( 'no-role' );
		}

		$this->set_placeholder( 'username', $user->user_login );
		$this->set_placeholder( 'old_roles', implode( ', ', $old_roles ) );
		$this->set_placeholder( 'new_role', $role );

		$this->message_action = get_edit_user_link( $user->ID );

		return true;
	}

}
