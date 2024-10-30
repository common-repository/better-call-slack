<?php
/**
 * Plugin Name: Better Call Slack
 * Plugin URI: https://www.cssigniter.com/plugins/better-call-slack/
 * Description: Better Call Slack allows you to receive notifications on your preferred slack channel or username for various WordPress events.
 * Author: The CSSIgniter Team
 * Author URI: https://www.cssigniter.com
 * Version: 1.0.0
 * Text Domain: better-call-slack
 * Domain Path: languages
 *
 * Better Call Slack is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Better Call Slack is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Better Call Slack. If not, see <http://www.gnu.org/licenses/>.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main instance of Better Call Slack.
 *
 * Returns the working instance of Better Call Slack. No need for globals.
 *
 * @since 1.0.0
 */
class BetterCallSlack {

	/**
	 * BetterCallSlack version.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public static $version = '1.0.0';

	/**
	 * Instance of this class.
	 *
	 * @var BetterCallSlack
	 * @since 1.0.0
	 */
	protected static $instance = null;

	protected $event_handlers = array();
	protected $hooked_handlers = array();

	/**
	 * The URL directory path (with trailing slash) of the main plugin file.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	protected static $plugin_url = '';

	/**
	 * The filesystem directory path (with trailing slash) of the main plugin file.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	protected static $plugin_path = '';


	/**
	 * Notification post type name.
	 *
	 * @var string
	 * @since 1.0.0
	 */
	public $post_type = 'bcs_notification';

	/**
	 * BetterCallSlack Instance.
	 *
	 * Instantiates or reuses an instance of BetterCallSlack.
	 *
	 * @since 1.0.0
	 * @static
	 * @see BetterCallSlack()
	 * @return BetterCallSlack - Single instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * BetterCallSlack constructor. Intentionally left empty so that instances can be created without
	 * re-loading of resources (e.g. scripts/styles), or re-registering hooks.
	 * http://wordpress.stackexchange.com/questions/70055/best-way-to-initiate-a-class-in-a-wp-plugin
	 * https://gist.github.com/toscho/3804204
	 *
	 * @since 1.0.0
	 */
	public function __construct() {}

	/**
	 * Kickstarts plugin loading.
	 *
	 * @since 1.0.0
	 */
	public function plugin_setup() {
		self::$plugin_url  = plugin_dir_url( __FILE__ );
		self::$plugin_path = plugin_dir_path( __FILE__ );

		load_plugin_textdomain( 'better-call-slack', false, dirname( self::plugin_basename() ) . '/languages' );

		require_once plugin_dir_path( __FILE__ ) . 'inc/options.php';
		require_once plugin_dir_path( __FILE__ ) . 'inc/class-bcs-event.php';

		// Initialization needed in every request.
		$this->init();

		// Initialization needed in admin requests.
		$this->admin_init();

		do_action( 'better_call_slack_loaded' );
	}

	/**
	 * Registers actions that need to be run on both admin and frontend
	 *
	 * @since 1.0.0
	 */
	protected function init() {
		add_action( 'init', array( $this, 'register_post_types' ) );
		add_action( 'init', array( $this, 'register_scripts' ) );

		add_action( 'init', array( $this, 'register_handlers' ) );

		add_action( 'init', array( $this, 'hook_events' ) );

		add_action( 'shutdown', array( $this, 'do_notifications' ) );

		do_action( 'better_call_slack_init' );
	}

	public function register_handlers() {
		do_action( 'better_call_slack_before_register_handlers' );

		require_once $this->plugin_path() . 'handlers/core-upgrade-available.php';
		$this->add_handler( 'Core_Upgrade_Available_BCS_Event_Handler' );

		require_once $this->plugin_path() . 'handlers/user-register.php';
		$this->add_handler( 'User_Register_BCS_Event_Handler' );

		require_once $this->plugin_path() . 'handlers/user-login.php';
		$this->add_handler( 'User_Login_BCS_Event_Handler' );

		require_once $this->plugin_path() . 'handlers/user-delete.php';
		$this->add_handler( 'User_Delete_BCS_Event_Handler' );

		require_once $this->plugin_path() . 'handlers/user-set-role.php';
		$this->add_handler( 'User_Set_Role_BCS_Event_Handler' );

		require_once $this->plugin_path() . 'handlers/theme-switch.php';
		$this->add_handler( 'Theme_Switch_BCS_Event_Handler' );

		require_once $this->plugin_path() . 'handlers/theme-update-available.php';
		$this->add_handler( 'Theme_Update_Available_BCS_Event_Handler' );

		require_once $this->plugin_path() . 'handlers/plugin-activated.php';
		$this->add_handler( 'Plugin_Activated_BCS_Event_Handler' );

		require_once $this->plugin_path() . 'handlers/plugin-deactivated.php';
		$this->add_handler( 'Plugin_Deactivated_BCS_Event_Handler' );

		require_once $this->plugin_path() . 'handlers/plugin-update-available.php';
		$this->add_handler( 'Plugin_Update_Available_BCS_Event_Handler' );

		require_once $this->plugin_path() . 'handlers/post-transition-post-status.php';
		$this->add_handler( 'Post_Transition_Post_Status_BCS_Event_Handler' );

		require_once $this->plugin_path() . 'handlers/comment-new-comment.php';
		$this->add_handler( 'Comment_New_Comment_BCS_Event_Handler' );

		require_once $this->plugin_path() . 'handlers/comment-set-comment-status.php';
		$this->add_handler( 'Comment_Set_Comment_Status_BCS_Event_Handler' );

		do_action( 'better_call_slack_after_register_handlers' );
	}

	public function add_handler( $class ) {
		$this->event_handlers[ $class ] = new $class();
		return $this->event_handlers[ $class ];
	}

	public function remove_handler( $class ) {
		unset( $this->event_handlers[ $class ] );
	}


	/**
	 * Registers actions that need to be run on admin only.
	 *
	 * @since 1.0.0
	 */
	protected function admin_init() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_options' ) );

		do_action( 'better_call_slack_admin_init' );
	}

	public function add_admin_options() {
		$admin_page = new BetterCallSlack_Options();

		call_user_func_array( 'add_submenu_page', $admin_page->get_page_arguments() );
		$admin_page->configure();
	}

	public function get_plugin_options() {
		$options = get_option( 'better_call_slack_options' );

		return $options;
	}

	/**
	 * Register (but not enqueue) all scripts and styles to be used throughout the plugin.
	 *
	 * @since 1.0.0
	 */
	public function register_scripts() {
		wp_register_style( 'better-call-slack-admin-css', $this->plugin_url() . 'assets/css/bcs-admin-styles.css', array(), self::$version );
		wp_register_script( 'better-call-slack-admin-js', $this->plugin_url() . 'assets/js/bcs-admin-scripts.js', array(), self::$version );
	}

	/**
	 * Enqueues admin scripts and styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_admin_scripts( $hook ) {
		$screen = get_current_screen();

		if ( 'post' === $screen->base && $screen->post_type === $this->post_type ) {
			wp_enqueue_style( 'better-call-slack-admin-css' );
			wp_enqueue_script( 'better-call-slack-admin-js' );
		}
	}

	/**
	 * Post types registration.
	 *
	 * @since 1.0.0
	 */
	public function register_post_types() {
		$labels = array(
			'name'               => esc_html_x( 'Notifications', 'post type general name', 'better-call-slack' ),
			'singular_name'      => esc_html_x( 'Notification', 'post type singular name', 'better-call-slack' ),
			'menu_name'          => esc_html_x( 'Better Call Slack', 'admin menu', 'better-call-slack' ),
			'name_admin_bar'     => esc_html_x( 'Better Call Slack Notification', 'add new on admin bar', 'better-call-slack' ),
			'all_items'          => esc_html__( 'All Notifications', 'better-call-slack' ),
			'add_new'            => esc_html__( 'Add New', 'better-call-slack' ),
			'add_new_item'       => esc_html__( 'Add New Notification', 'better-call-slack' ),
			'edit_item'          => esc_html__( 'Edit Notification', 'better-call-slack' ),
			'new_item'           => esc_html__( 'New Notification', 'better-call-slack' ),
			'view_item'          => esc_html__( 'View Notification', 'better-call-slack' ),
			'search_items'       => esc_html__( 'Search Notifications', 'better-call-slack' ),
			'not_found'          => esc_html__( 'No notifications found', 'better-call-slack' ),
			'not_found_in_trash' => esc_html__( 'No notifications found in the trash', 'better-call-slack' ),
		);

		$args = array(
			'labels'          => $labels,
			'singular_label'  => esc_html_x( 'Notification', 'post type singular name', 'better-call-slack' ),
			'public'          => false,
			'show_ui'         => true,
			'capability_type' => 'post',
			'hierarchical'    => false,
			'has_archive'     => false,
			'supports'        => array( 'title' ),
			'menu_icon'       => 'data:image/svg+xml;base64,' . base64_encode('<svg width="1792" height="1792" viewBox="0 0 1792 1792" xmlns="http://www.w3.org/2000/svg"><path d="M1583 776q62 0 103.5 40.5t41.5 101.5q0 97-93 130l-172 59 56 167q7 21 7 47 0 59-42 102t-101 43q-47 0-85.5-27t-53.5-72l-55-165-310 106 55 164q8 24 8 47 0 59-42 102t-102 43q-47 0-85-27t-53-72l-55-163-153 53q-29 9-50 9-61 0-101.5-40t-40.5-101q0-47 27.5-85t71.5-53l156-53-105-313-156 54q-26 8-48 8-60 0-101-40.5t-41-100.5q0-47 27.5-85t71.5-53l157-53-53-159q-8-24-8-47 0-60 42-102.5t102-42.5q47 0 85 27t53 72l54 160 310-105-54-160q-8-24-8-47 0-59 42.5-102t101.5-43q47 0 85.5 27.5t53.5 71.5l53 161 162-55q21-6 43-6 60 0 102.5 39.5t42.5 98.5q0 45-30 81.5t-74 51.5l-157 54 105 316 164-56q24-8 46-8zm-794 262l310-105-105-315-310 107z" fill="#9ea3a8"/></svg>'),
		);

		register_post_type( $this->post_type, $args );
	}

	/**
	 * Registers metaboxes for the bcs_notification post type.
	 *
	 * @since 1.0.0
	 */
	public function add_meta_boxes() {
		add_meta_box( 'better-call-slack-config', esc_html__( 'Notification settings', 'better-call-slack' ), array( $this, 'metabox_config' ), $this->post_type, 'normal', 'high' );
	}

	/**
	 * Echoes the configuration metabox markup.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $object
	 * @param array $box
	 */
	public function metabox_config( $object, $box ) {
		wp_nonce_field( basename( __FILE__ ), $object->post_type . '_nonce' );

		$options = $this->get_plugin_options();

		$webhook_url       = $this->get_post_meta( $object->ID, 'webhook_url', $options['better_call_slack_url'] );
		$channel           = $this->get_post_meta( $object->ID, 'channel' );
		$bot_name          = $this->get_post_meta( $object->ID, 'bot_name', $options['better_call_slack_bot_name'] );
		$bot_emoji         = $this->get_post_meta( $object->ID, 'bot_emoji', $options['better_call_slack_bot_emoji'] );
		$priority          = $this->get_post_meta( $object->ID, 'priority', 'no-priority' );
		$post_type_exclude = $this->get_post_meta( $object->ID, 'post_type_exclude', array() );
		?>

		<div class="bcs-container">

			<h3 class="bcs-section-title"><?php esc_html_e( 'Step 1: Configuration', 'better-call-slack' ); ?></h3>
			<p class="bcs-section-subtitle"><?php esc_html_e( 'You can set some default configuration values at Settings > Better Call Slack.', 'better-call-slack' ); ?></p>

			<div class="bcs-section bcs-section-highlight">
				<table class="form-table bcs-form-table">
					<tbody>
						<tr>
							<th><label for="webhook_url"><?php esc_html_e( 'Notification Webhook URL', 'better-call-slack' ); ?></label></th>
							<td><input class="regular-text" type="text" id="webhook_url" name="webhook_url" value="<?php echo esc_attr( $webhook_url ); ?>"> <a href="https://www.cssigniter.com/docs/better-call-slack" target="_blank"><?php esc_html_e( 'Where do I find that?', 'better-call-slack' ); ?></a></td>
						</tr>
						<tr>
							<th><label for="channel"><?php esc_html_e( 'Channel to notify', 'better-call-slack' ); ?></label></th>
							<td><input type="text" id="channel" name="channel" value="<?php echo esc_attr( $channel ); ?>"></td>
						</tr>
						<tr>
							<th><label for="username"><?php esc_html_e( 'Bot name', 'better-call-slack' ); ?></label></th>
							<td><input type="text" id="bot_name" name="bot_name" value="<?php echo esc_attr( $bot_name ); ?>"></td>
						</tr>
						<tr>
							<th><label for="icon_emoji"><?php esc_html_e( 'Bot emoji', 'better-call-slack' ); ?></label></th>
							<td><input type="text" id="bot_emoji" name="bot_emoji" value="<?php echo esc_attr( $bot_emoji ); ?>"></td>
						</tr>
						<tr>
							<th><label for="priority"><?php esc_html_e( 'Show as high priority', 'better-call-slack' ); ?></label></th>
							<td>
								<select id="priority" name="priority">
									<option value="no-priority" <?php selected( $priority, 'no-priority' ); ?>><?php esc_html_e( 'Simple Notification', 'better-call-slack' ); ?></option>
									<option value="good" <?php selected( $priority, 'good' ); ?>><?php esc_html_e( 'Low Priority', 'better-call-slack' ); ?></option>
									<option value="warning" <?php selected( $priority, 'warning' ); ?>><?php esc_html_e( 'Medium Priority', 'better-call-slack' ); ?></option>
									<option value="danger" <?php selected( $priority, 'danger' ); ?>><?php esc_html_e( 'High Priority', 'better-call-slack' ); ?></option>
								</select>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<h3 class="bcs-section-title"><?php esc_html_e( 'Step 2: Select Notification', 'better-call-slack' ); ?></h3>
			<p class="bcs-section-subtitle"><?php esc_html_e( 'You can select multiple notifications.', 'better-call-slack' ); ?></p>

			<div class="bcs-section bcs-section-normal">
				<table class="form-table bcs-form-table">
					<tbody>
						<?php $notifications = $this->get_events_meta_array(); ?>

						<?php foreach ( $notifications as $category => $category_values ) : ?>
							<tr><td colspan="2"><h4 class="bcs-family"><?php echo esc_html( $category_values['label'] ); ?></h4></td></tr>

							<?php foreach ( $category_values['data'] as $subcategory => $subcategory_values ) : ?>
								<tr><td colspan="2"><h5 class='bcs-family-member'><?php echo esc_html( $subcategory_values['label'] ); ?></h5></td></tr>

								<?php foreach ( $subcategory_values['data'] as $event ) {
									$this->metabox_part_event( $event, $object->ID );
								} ?>
							<?php endforeach; ?>

						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<h3 class="bcs-section-title"><?php esc_html_e( 'Choose Post Types to exclude', 'better-call-slack' ); ?></h3>
			<p class="bcs-section-subtitle"><?php esc_html_e( 'For post related notifications, you can exclude some post types through this list.', 'better-call-slack' ); ?></p>

			<div class="bcs-section bcs-section-normal">
				<table class="form-table bcs-form-table">
					<tbody>
						<?php
							$available_post_types = get_post_types( array(
								'public' => true,
							), 'objects' );
							unset( $available_post_types['attachment'] );
						?>
						<?php foreach ( $available_post_types as $post_type ) : ?>
							<tr>
								<th><label for="post_type_exclude[<?php echo esc_attr( $post_type->name ); ?>]"><?php echo esc_html( $post_type->label ); ?></label></th>
								<td><input type="checkbox" name="post_type_exclude[<?php echo esc_attr( $post_type->name ); ?>]" value="1" <?php checked( in_array( $post_type->name, $post_type_exclude, true ), 1 ); ?>></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

		</div>
		<?php
	}

	public function metabox_part_event( $event, $post_id ) {
		$enabled = $this->get_post_meta( $post_id, 'event-' . $event['id_base'], false );
		$message = $this->get_post_meta( $post_id, 'event-' . $event['id_base'] . '-message', $event['message'] );
		$message = ! empty( $message ) ? $message : $event['message'];
		?>
		<tr>
			<th class="bcs-form-table-cell"><label class="bcs-family-member-child" for="event-<?php echo esc_attr( $event['id_base'] ); ?>"><?php echo esc_html( $event['title'] ); ?></label></th>
			<td>
				<input type="checkbox" class="bcs-form-checkbox" id="event-<?php echo esc_attr( $event['id_base'] ); ?>" name="event-<?php echo esc_attr( $event['id_base'] ); ?>" value="1" <?php checked( $enabled, 1 ); ?>>
				<?php if ( ! empty( $event['placeholders'] ) ) : ?>
					<small class="bcs-tags" style="display:none">
						<?php
							esc_html_e( 'Template tags available: ', 'better-call-slack' );
							foreach ( $event['placeholders'] as $tag ) {
								echo '<em class="bcs-tag">:' . esc_html( $tag ) . ':</em>';
							}
						?>
					</small>
				<?php endif; ?>
			</td>
		</tr>
		<tr class="bcs-template" style="display:none">
			<th class="bcs-form-table-cell"><label for="event-<?php echo esc_attr( $event['id_base'] ); ?>-message"><?php esc_html_e( 'Notification Message', 'better-call-slack' ); ?></label></th>
			<td>
				<input class="regular-text" type="text" id="event-<?php echo esc_attr( $event['id_base'] ); ?>-message" name="event-<?php echo esc_attr( $event['id_base'] ); ?>-message" value="<?php echo esc_attr( $message ); ?>">
			</td>
		</tr>
		<?php
	}

	public function save_post( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return false; }
		if ( isset( $_POST['post_view'] ) && 'list' === $_POST['post_view'] ) { return false; }
		if ( ! isset( $_POST['post_type'] ) || $_POST['post_type'] !== $this->post_type ) { return false; }
		if ( ! isset( $_POST[ $this->post_type . '_nonce' ] ) || ! wp_verify_nonce( $_POST[ $this->post_type . '_nonce' ], basename( __FILE__ ) ) ) { return false; }
		$post_type_obj = get_post_type_object( $this->post_type );
		if ( ! current_user_can( $post_type_obj->cap->edit_post, $post_id ) ) { return false; }


		update_post_meta( $post_id, 'webhook_url', esc_url_raw( $_POST['webhook_url'] ) );
		update_post_meta( $post_id, 'channel', sanitize_text_field( $_POST['channel'] ) );
		update_post_meta( $post_id, 'bot_name', sanitize_text_field( $_POST['bot_name'] ) );
		update_post_meta( $post_id, 'bot_emoji', sanitize_text_field( $_POST['bot_emoji'] ) );
		update_post_meta( $post_id, 'priority', $this->better_call_slack_sanitize_priority( $_POST['priority'] ) );


		$available_post_types = get_post_types( array(
			'public' => true,
		), 'objects' );
		unset( $available_post_types['attachment'] );

		$post_type_exclude = array();
		// The array will not exist if no post type exclude checkboxes are checked.
		if ( array_key_exists( 'post_type_exclude', $_POST ) ) {
			foreach ( $available_post_types as $post_type ) {
				if ( array_key_exists( $post_type->name, $_POST['post_type_exclude'] ) && 1 === (int) $_POST['post_type_exclude'][ $post_type->name ] ) {
					$post_type_exclude[] = $post_type->name;
				}
			}
		}
		update_post_meta( $post_id, 'post_type_exclude', $post_type_exclude );

		$notifications = $this->get_events_meta_array();
		foreach ( $notifications as $category => $category_values ) {
			foreach ( $category_values['data'] as $subcategory => $subcategory_values ) {
				foreach ( $subcategory_values['data'] as $event ) {
					$key         = 'event-' . $event['id_base'];
					$key_message = 'event-' . $event['id_base'] . '-message';
					update_post_meta( $post_id, $key, (int) isset( $_POST[ $key ] ) );

					$message = ! empty( $_POST[ $key_message ] ) ? $_POST[ $key_message ] : $event['message'];
					update_post_meta( $post_id, $key_message, sanitize_text_field( $message ) );
				}
			}
		}

		do_action( 'better_call_slack_save_post', $post_id );
	}

	public function get_post_meta( $post_id, $key, $default = '' ) {
		$keys = get_post_custom_keys( $post_id );

		$value = $default;

		if ( is_array( $keys ) && in_array( $key, $keys, true ) ) {
			$value = get_post_meta( $post_id, $key, true );
		}

		return $value;
	}

	public function get_events_meta_array() {
		$categories = array(
			'wordpress' => array(
				'label'    => _x( 'WordPress', 'event category', 'better-call-slack' ),
				'priority' => 10,
				'data'     => array(
					'core'     => array(
						'label'    => _x( 'Core', 'event sub-category', 'better-call-slack' ),
						'priority' => 10,
						'data'     => array(),
					),
					'user'     => array(
						'label'    => _x( 'User', 'event sub-category', 'better-call-slack' ),
						'priority' => 20,
						'data'     => array(),
					),
					'themes'   => array(
						'label'    => _x( 'Themes', 'event sub-category', 'better-call-slack' ),
						'priority' => 30,
						'data'     => array(),
					),
					'plugins'  => array(
						'label'    => _x( 'Plugins', 'event sub-category', 'better-call-slack' ),
						'priority' => 40,
						'data'     => array(),
					),
					'posts'    => array(
						'label'    => _x( 'Posts', 'event sub-category', 'better-call-slack' ),
						'priority' => 50,
						'data'     => array(),
					),
					'comments' => array(
						'label'    => _x( 'Comments', 'event sub-category', 'better-call-slack' ),
						'priority' => 60,
						'data'     => array(
							// Example entry.
							/*
							array(
								'id_base'      => BCS_Event->id_base,
								'action'       => BCS_Event->get_action(),
								'category'     => 'wordpress',
								'subcategory'  => 'comments',
								'title'        => __( "When a comment's status changes.", 'better-call-slack' ),
								'message'      => __( ':post_type: :title: A comment was just :status:', 'better-call-slack' ),
								'priority'     => BCS_Event->display_priority,
								'placeholders' => BCS_Event->get_placeholder_names(),
							),
							*/
						),
					),
				),
			),
//			'other'     => array(
//				'label'    => _x( 'Other', 'event category', 'better-call-slack' ),
//				'priority' => 100,
//				'data'     => array(
//					'core' => array(
//						'label'    => _x( 'Other', 'event sub-category', 'better-call-slack' ),
//						'priority' => 10,
//						'data'     => array(),
//					),
//				),
//			),
		);

		$categories = apply_filters( 'better_call_slack_event_categories', $categories );

		foreach ( $this->event_handlers as $event_handler ) {
			$categories = array_merge_recursive( $categories, $event_handler->get_events_meta_array() );
		}

		// TODO: Test that display priorities work on categories, subcategories, and events.
		$copy = $categories;
		foreach ( $categories as $category => $category_values ) {
			foreach ( $category_values['data'] as $subcategory => $subcategory_values ) {
				$copy[ $category ]['data'][ $subcategory ]['data'] = wp_list_sort( $subcategory_values['data'], 'priority', 'ASC' );
			}
			$copy[ $category ]['data'] = wp_list_sort( $category_values['data'], 'priority', 'ASC' );
		}
		$categories = $copy;
		unset( $copy );
		$categories = wp_list_sort( $categories, 'priority', 'ASC', true );

		return $categories;
	}

	public function hook_events() {
		$q = new WP_Query( array(
			'post_type'      => 'bcs_notification',
			'posts_per_page' => - 1,
		) );


		if ( $q->have_posts() ) {
			while ( $q->have_posts() ) {
				$q->the_post();

				$post_meta = array(
					'webhook_url'       => $this->get_post_meta( get_the_ID(), 'webhook_url' ),
					'channel'           => $this->get_post_meta( get_the_ID(), 'channel' ),
					'bot_name'          => $this->get_post_meta( get_the_ID(), 'bot_name' ),
					'bot_emoji'         => $this->get_post_meta( get_the_ID(), 'bot_emoji' ),
					'priority'          => $this->get_post_meta( get_the_ID(), 'priority', 'no-priority' ),
					'post_type_exclude' => $this->get_post_meta( get_the_ID(), 'post_type_exclude', array() ),
				);

				foreach ( $this->event_handlers as $event_handler ) {
					if ( 1 === (int) $this->get_post_meta( get_the_ID(), 'event-' . $event_handler->id_base ) ) {
						$message = $this->get_post_meta( get_the_ID(), 'event-' . $event_handler->id_base . '-message' );

						$event = clone $event_handler;
						$event->post_meta = $post_meta;
						$event->set_message( $message );
						$event->register_action();
						$this->hooked_handlers[] = $event;
					}
				}
		}
			wp_reset_postdata();
		}

		do_action( 'better_call_slack_events_hooked' );
	}

	public function do_notifications() {
		do_action( 'better_call_slack_before_slack_notifications' );

		/** @var $event BCS_Event */
		foreach ( $this->hooked_handlers as $event ) {
			if ( ! $event->has_run() ) {
				continue;
			}

			$payload = $this->get_slack_payload( $event );

			do_action( 'better_call_slack_before_slack_notification', $event );

			$resp = wp_remote_post( $event->post_meta['webhook_url'], array(
				'body'    => json_encode( $payload ),
				'headers' => array(
					'Content-Type' => 'application/json',
				),
			) );

			if ( is_wp_error( $resp ) ) {
				error_log( $resp->get_error_message() );
				return $resp;
			} else {
				$status  = intval( wp_remote_retrieve_response_code( $resp ) );
				$message = wp_remote_retrieve_body( $resp );
				if ( 200 !== $status ) {
					error_log( $message );
				}
			}

		}

		do_action( 'better_call_slack_after_slack_notifications' );

	}

	protected function get_slack_payload( BCS_Event $event ) {
		$payload = array();

		$payload['channel']    = $event->post_meta['channel'];
		$payload['username']   = $event->post_meta['bot_name'];
		$payload['icon_emoji'] = $event->post_meta['bot_emoji'];

		$attachment_fields = array();

		if ( ! $event->is_bulk() ) {
			if ( 1 === $event->times_run() ) {
				$message = $event->get_formated_message();
			} elseif( $event->times_run() > 1 ) {
				$messages = array();
				for ( $i = 0; $i < $event->times_run(); $i++ ) {
					$messages[] = $event->get_formated_message( $event->get_message(), $event->get_placeholders( true, $i ) );
				}
				$message = implode( "\n", $messages );
			}
		} else {
			$message = $event->get_formated_message( $event->bulk_message );
			$attachment_fields = $event->get_formated_bulk_fields();
		}

		$message = $event->get_formated_message( $message, $this->get_global_placeholders() );

		if ( empty( $message ) ) {
			/* translators: %s is a PHP class name. */
			$message = sprintf( esc_html__( 'Better Call Slack: Event %s has fired but provided no message.', 'better-call-slack' ), get_class( $event ) );
		}

		if ( false !== $event->message_action ) {
			$message .= "\n" . $event->message_action;
		}

		// Required encodings by Slack.
		$message = str_replace(
			array( '&', '<', '>' ),
			array( '&amp;', '&lt;', '&gt;' ),
			$message
		);


		if (
			( isset( $event->post_meta['priority'] ) && 'no-priority' !== $event->post_meta['priority'] )
			||
			! empty( $attachment_fields )
		) {
			$payload['attachments'] = array(
				array(
					'fallback'  => null,
					'text'      => $message,
					'mrkdwn_in' => array(
						'text',
					),
					'color'     => $event->post_meta['priority'],
					'fields'    => $attachment_fields,
				),
			);
		} else {
			$payload['text'] = $message;
		}

		return $payload;
	}

	public function get_global_placeholders() {
		return array(
			'site_name'    => get_bloginfo( 'name' ),
			'site_url'     => get_bloginfo( 'url' ),
			'current_user' => wp_get_current_user()->user_login,
		);
	}

	public function better_call_slack_sanitize_priority( $priority ) {
		$valid_priorities = array( 'no-priority', 'good', 'warning', 'danger' );

		if ( in_array( $priority, $valid_priorities) ) {
			return $priority;
		}

		return 'no-priority';
	}

	public function plugin_activated() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$this->register_post_types();

		do_action( 'better_call_slack_activated' );

		flush_rewrite_rules();
	}

	public function plugin_deactivated() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		unregister_post_type( $this->post_type );

		do_action( 'better_call_slack_deactivated' );

		flush_rewrite_rules();
	}

	public static function plugin_basename() {
		return plugin_basename( __FILE__ );
	}

	public function plugin_url() {
		return self::$plugin_url;
	}

	public function plugin_path() {
		return self::$plugin_path;
	}
}

/**
 * Main instance of Better Call Slack.
 *
 * Returns the working instance of Better Call Slack. No need for globals.
 *
 * @since  1.0.0
 * @return BetterCallSlack
 */
function BetterCallSlack() {
	return BetterCallSlack::instance();
}

add_action( 'plugins_loaded', array( BetterCallSlack(), 'plugin_setup' ) );
register_activation_hook( __FILE__, array( BetterCallSlack(), 'plugin_activated' ) );
register_deactivation_hook( __FILE__, array( BetterCallSlack(), 'plugin_deactivated' ) );
