<?php
class Theme_Switch_BCS_Event_Handler extends BCS_Event {
	public function __construct() {
		parent::__construct( 'theme-switch', 'after_switch_theme', 10, 2 );

		// Set default values.
		$this->set_message( __( 'Theme :theme_name: v:theme_version: was just activated by :current_user:.', 'better-call-slack' ) );
		$this->set_placeholder( 'theme_name' );
		$this->set_placeholder( 'theme_version' );
		$this->set_placeholder( 'old_name' );
		$this->set_placeholder( 'old_version' );
	}

	public function get_post_type_option() {
		return array(
			'category'    => 'wordpress',
			'subcategory' => 'themes',
			'title'       => __( 'When the active theme is changed.', 'better-call-slack' ),
		);
	}

	public function handler( $old_theme, $old_theme_obj ) {
		$this->set_placeholder( 'theme_name', wp_get_theme()->name );
		$this->set_placeholder( 'theme_version', wp_get_theme()->version );
		$this->set_placeholder( 'old_name', $old_theme_obj->name );
		$this->set_placeholder( 'old_version', $old_theme_obj->version );

		return true;
	}

}
