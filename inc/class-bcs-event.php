<?php
abstract class BCS_Event {
	/**
	 * Root ID for all events of this type.
	 *
	 * @var string
	 */
	public $id_base = '';

	public $display_priority = 10;

	protected $action = '';
	protected $action_priority = 10;
	protected $action_params = 1;

	private $message = '';
	private $default_message = false;
	private $placeholders = array();
	public $message_action = false;

	public $bulk_support = false;
	public $bulk_message = '';
	public $bulk_field_title = false;
	public $bulk_field_value = false;
	private $bulk_data = array();

	private $times_handler_ran = 0;

	public $post_meta = array();

	public function __construct( $id_base, $action, $action_priority = 10, $action_params = 1 ) {
		$this->set_id_base( $id_base );

		$this->action          = $action;
		$this->action_priority = $action_priority;
		$this->action_params   = $action_params;
	}

	abstract public function get_post_type_option();

	public function register_action() {
		add_action( $this->action, array( $this, '_handler' ), $this->action_priority, $this->action_params );
	}

	/**
	 * The action's callback.
	 *
	 * It can't be marked as abstract, since the function's parameters may vary depending on action.
	 */
	public function _handler() {
		$args = func_get_args();

		$handler = array( $this, 'handler' );
		if ( is_callable( $handler ) ) {
			$ran = call_user_func_array( $handler, $args );

			if ( is_null( $ran ) || true === (bool) $ran ) {
				$this->times_handler_ran ++;
			}
		}
	}

	/**
	 * The action's callback.
	 *
	 * It can't be marked as abstract, since the function's parameters may vary depending on action.
	 */
//	public function handler() {
//		die( 'function BCS_Event::handler() must be over-ridden in a sub-class.' );
//	}

	protected function get_action() {
		return $this->action;
	}

	protected function set_id_base( $id_base ) {
		$this->id_base = sanitize_key( $id_base );
	}

	public function set_placeholder( $name, $value = '' ) {
		$name = sanitize_key( $name );
		$this->placeholders[ $name ] = $value;

		$this->bulk_data[ $this->times_handler_ran ]['placeholders'][ $name ] = $value;
	}

	public function get_placeholder( $name, $bulk = false, $bulk_index = 0 ) {
		$name = sanitize_key( $name );

		if ( $bulk ) {
			if ( array_key_exists( $name, $this->bulk_data[ $bulk_index ]['placeholders'] ) ) {
				return $this->bulk_data[ $bulk_index ]['placeholders'][ $name ];
			}
		} elseif ( array_key_exists( $name, $this->placeholders ) ) {
			return $this->placeholders[ $name ];
		}

		return false;
	}

	public function get_placeholders( $bulk = false, $bulk_index = 0 ) {
		if ( $bulk ) {
			return $this->bulk_data[ $bulk_index ]['placeholders'];
		}

		return $this->placeholders;
	}

	public function get_placeholder_names() {
		return array_keys( $this->placeholders );
	}

	public function set_message( $value ) {
		$this->message = sanitize_text_field( $value );
		if ( false === $this->default_message && ! empty( $this->message ) ) {
			$this->default_message = $this->message;
		}

		$this->bulk_data[ $this->times_handler_ran ]['message'] = $this->message;
	}

	public function get_message( $bulk = false, $bulk_index = 0 ) {
		if ( $bulk ) {
			return $this->bulk_data[ $this->times_handler_ran ]['message'];
		} elseif ( empty( $this->message ) ) {
			return $this->default_message;
		}

		return $this->message;
	}

	public function get_formated_message( $message = false, $placeholders = false ) {
		if ( false === $message ) {
			$message = $this->get_message();
		}

		if ( false === $placeholders ) {
			$placeholders = $this->placeholders;
		}

		$placeholders = array_merge( $placeholders, array(
			'count' => $this->times_handler_ran,
		) );

		$message = str_replace(
			array_map( array( $this, 'prepare_placeholder' ), array_keys( $placeholders ) ),
			array_values( $placeholders ),
			$message
		);

		return $message;
	}

	public function get_formated_bulk_fields() {
		$fields = array();
		foreach ( $this->bulk_data as $data ) {
			$field = array(
				'title' => $this->get_formated_message( $this->bulk_field_title, $data['placeholders'] ),
				'value' => $this->get_formated_message( $this->bulk_field_value, $data['placeholders'] ),
			);

			$fields[] = $field;
		}

		return $fields;
	}


	public function times_run() {
		return $this->times_handler_ran;
	}

	public function has_run() {
		return $this->times_handler_ran > 0;
	}

	public function is_bulk() {
		$bulk_threshold = apply_filters( 'better_call_slack_event_bulk_threshold', 2, $this->id_base, $this );
		return $this->bulk_support && $this->times_handler_ran >= $bulk_threshold;
	}

	public function get_events_meta_array() {
		$info = $this->get_post_type_option();
		$array = array(
			$info['category'] => array(
				'data' => array(
					$info['subcategory'] => array(
						'data' => array(
							array(
								'id_base'      => $this->id_base,
								'action'       => $this->get_action(),
								'title'        => $info['title'],
								'message'      => $this->get_message(),
								'priority'     => $this->display_priority,
								'placeholders' => $this->get_placeholder_names(),
							),
						),
					),
				),
			),
		);

		return $array;
	}

	public function prepare_placeholder( $placeholder ) {
		return ':' . $placeholder . ':';
	}
}