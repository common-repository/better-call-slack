<?php
class Theme_Update_Available_BCS_Event_Handler extends BCS_Event {
	public function __construct() {
		parent::__construct( 'theme-update-available', 'admin_init', 10, 0 );

		// Set default values.
		$this->set_message( __( 'Theme updates available for: :theme_names:.', 'better-call-slack' ) );
		$this->set_placeholder( 'theme_names' );
	}

	public function get_post_type_option() {
		return array(
			'category'    => 'wordpress',
			'subcategory' => 'themes',
			'title'       => __( 'When a theme update is available.', 'better-call-slack' ),
		);
	}


	public function handler() {
		$transient = "bcs-event-{$this->id_base}-notification";

		$theme_update_transient = get_transient( $transient );

		$transient_expire = apply_filters( 'bcs_event_theme_update_available_recheck', WEEK_IN_SECONDS );

		if ( false !== $theme_update_transient ) {
			return false;
		}

		$updates = get_site_transient( 'update_themes' );

		if ( ! empty( $updates ) && ! empty( $updates->response ) ) {

			$theme_names = array();

			foreach ( $updates->response as $update ) {
				$theme_names[] = wp_get_theme( $update['theme'] )->name;
			}

			set_transient( $transient, time(), $transient_expire );

			$this->set_placeholder( 'theme_names', implode( ', ', $theme_names ) );

			$this->message_action = admin_url( 'themes.php' );

			return true;
		}


		return false;
	}

}
