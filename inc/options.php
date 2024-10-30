<?php

class BetterCallSlack_Options {

	/**
	 * Get the arguments for the "add_submenu_page" function.
	 *
	 * @return array
	 */
	public function get_page_arguments() {
		return array(
			'options-general.php',
			'BetterCallSlack',
			__( 'Better Call Slack', 'better-call-slack' ),
			'manage_options',
			'better_call_slack',
			array( $this, 'render_options_page' ),
		);
	}

	/**
	 * Configure the admin page.
	 */
	public function configure() {

		//register settings
		register_setting( 'BetterCallSlack', 'better_call_slack_options' );

		// Register section and field
		add_settings_section(
			'better_call_slack_section',
			__( 'Better Call Slack Options', 'better-call-slack' ),
			array( $this, 'render_section' ),
			'BetterCallSlack'
		);

		add_settings_field(
			'better_call_slack_url',
			__( 'Webhook URL', 'better-call-slack' ),
			array( $this, 'render_url_field' ),
			'BetterCallSlack',
			'better_call_slack_section'
		);

		add_settings_field(
			'better_call_slack_bot_name',
			__( 'Bot Name', 'better-call-slack' ),
			array( $this, 'render_bot_name_field' ),
			'BetterCallSlack',
			'better_call_slack_section'
		);

		add_settings_field(
			'better_call_slack_bot_emoji',
			__( 'Bot Emoji', 'better-call-slack' ),
			array( $this, 'render_bot_emoji_field' ),
			'BetterCallSlack',
			'better_call_slack_section'
		);
	}

	public function render_options_page() {
		?>
		<div class="wrap" id="better-call-slack-admin">
			<h1><?php esc_html_e( 'Better Call Slack', 'better-call-slack' ); ?></h1>
			<form action="options.php" method="POST">
				<?php settings_fields( 'BetterCallSlack' ); ?>
				<?php do_settings_sections( 'BetterCallSlack' ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public function render_section() {
		echo '<p>' . esc_html_e( 'Fill in the default values for Webhook URL, Bot Name, and Bot Emoji.', 'better-call-slack' ) . '</p>';
	}

	public function render_url_field() {
		$options = $this->better_call_slack_sanitize_settings( get_option( 'better_call_slack_options' ) );
		$url     = $options['better_call_slack_url'];
		?>
			<input type="url" class="regular-text" name="better_call_slack_options[better_call_slack_url]" value="<?php echo esc_url( $url ); ?>">
			<a href="https://www.cssigniter.com/docs/better-call-slack" target="_blank"><?php esc_html_e( 'Where do I find that?', 'better-call-slack' ); ?></a>
		<?php
	}

	public function render_bot_name_field() {
		$options  = $this->better_call_slack_sanitize_settings( get_option( 'better_call_slack_options' ) );
		$bot_name = $options['better_call_slack_bot_name'];
		?>
			<input type="text" name="better_call_slack_options[better_call_slack_bot_name]" value="<?php echo esc_attr( $bot_name ); ?>">
		<?php
	}

	public function render_bot_emoji_field() {
		$options   = $this->better_call_slack_sanitize_settings( get_option( 'better_call_slack_options' ) );
		$bot_emoji = $options['better_call_slack_bot_emoji'];
		?>
			<input type="text" name="better_call_slack_options[better_call_slack_bot_emoji]" value="<?php echo esc_attr( $bot_emoji ); ?>">
		<?php
	}

	function better_call_slack_sanitize_settings( $options ) {
		$defaults = array(
			'better_call_slack_url'       => '',
			'better_call_slack_bot_name'  => '',
			'better_call_slack_bot_emoji' => '',
		);

		$options = wp_parse_args( $options, $defaults );

		foreach ( $options as $option => $value ) {
			if ( 'better_call_slack_url' === $option ) {
				$options[ $option ] = esc_url_raw( $value );
			} else {
				$options[ $option ] = sanitize_text_field( $value );
			}
		}

		return $options;
	}

}
