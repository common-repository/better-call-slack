<?php
class Core_Upgrade_Available_BCS_Event_Handler extends BCS_Event {
	public function __construct() {
		parent::__construct( 'core-upgrade-available', 'admin_init', 10, 0 );

		// Set default values.
		$this->set_message( __( 'WordPress core version :core_version: is available.', 'better-call-slack' ) );
		$this->set_placeholder( 'core_version' );
		$this->set_placeholder( 'current_version' );
	}

	public function get_post_type_option() {
		return array(
			'category'    => 'wordpress',
			'subcategory' => 'core',
			'title'       => __( 'Core upgrade available.', 'better-call-slack' ),
		);
	}


	public function handler() {
		$transient = "bcs-event-{$this->id_base}-notification";

		$core_update_transient = get_transient( $transient );

		$transient_expire = apply_filters( 'bcs_event_core_upgrade_available_recheck', WEEK_IN_SECONDS );

		if ( false !== $core_update_transient ) {
			return false;
		}

		if ( ! function_exists( 'get_core_updates' ) ) {
			return false;
		}

		$updates = get_core_updates( array(
			'dismissed' => false,
		) );

		if ( empty( $updates ) ) {
			return false;
		}

		$latest = array_shift( $updates );
		if ( ! in_array( $latest->response, array( 'development', 'latest' ), true ) ) {
			return false;
		}

		global $wp_version;

		if ( version_compare( $latest->version, $wp_version, '<=' ) ) {
			return false;
		}

		set_transient( $transient, time(), $transient_expire );

		$this->set_placeholder( 'core_version', $latest->version );
		$this->set_placeholder( 'current_version', $wp_version );
		$this->message_action = admin_url( '/update-core.php' );

		return true;
	}

}
