<?php
class Plugin_Deactivated_BCS_Event_Handler extends BCS_Event {
	public function __construct() {
		parent::__construct( 'plugin-deactivated', 'deactivated_plugin', 10, 2 );

		// Set default values.
		$this->set_message( __( 'Plugin :plugin_name: was just deactivated by :current_user:.', 'better-call-slack' ) );
		$this->set_placeholder( 'plugin_name' );
	}

	public function get_post_type_option() {
		return array(
			'category'    => 'wordpress',
			'subcategory' => 'plugins',
			'title'       => __( 'When a plugin is deactivated.', 'better-call-slack' ),
		);
	}

	public function handler( $plugin_file, $network_wide ) {
		$file = WP_PLUGIN_DIR . '/' . $plugin_file;
		$plugin = get_plugin_data( $file );

		$this->set_placeholder( 'plugin_name', $plugin['Name'] );

		return true;
	}

}
