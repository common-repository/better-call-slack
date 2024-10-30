<?php
class Plugin_Update_Available_BCS_Event_Handler extends BCS_Event {
	public function __construct() {
		parent::__construct( 'plugin-update-available', 'admin_init', 10, 0 );

		// Set default values.
		$this->set_message( __( 'Plugin updates available for: :plugin_names:.', 'better-call-slack' ) );
		$this->set_placeholder( 'plugin_names' );
	}

	public function get_post_type_option() {
		return array(
			'category'    => 'wordpress',
			'subcategory' => 'plugins',
			'title'       => __( 'When a plugin update is available.', 'better-call-slack' ),
		);
	}


	public function handler() {
		$transient = "bcs-event-{$this->id_base}-notification";

		$theme_update_transient = get_transient( $transient );

		$transient_expire = apply_filters( 'bcs_event_plugin_update_available_recheck', WEEK_IN_SECONDS );

		if ( false !== $theme_update_transient ) {
			return false;
		}

		$updates = get_site_transient( 'update_plugins' );

		if ( ! empty( $updates ) && ! empty( $updates->response ) ) {

			$plugin_names = array();

			foreach ( $updates->response as $update ) {
				$file = WP_PLUGIN_DIR . '/' . $update->plugin;
				$plugin = get_plugin_data( $file );
				$plugin_names[] = $plugin['Name'];
			}

			set_transient( $transient, time(), $transient_expire );

			$this->set_placeholder( 'plugin_names', implode( ', ', $plugin_names ) );

			$this->message_action = admin_url( 'plugins.php' );

			return true;
		}


		return false;
	}

}
