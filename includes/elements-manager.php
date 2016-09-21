<?php
namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Elements_Manager {
	/**
	 * @var Element_Base[]
	 */
	private $_element_types = null;

	private function _init_elements() {
		$this->_elements = [];

		$this->register_element( __NAMESPACE__ . '\Element_Column' );
		$this->register_element( __NAMESPACE__ . '\Element_Section' );

		do_action( 'elementor/elements/elements_registered' );
	}

	private function require_files() {
		require_once ELEMENTOR_PATH . 'includes/elements/base.php';

		require ELEMENTOR_PATH . 'includes/elements/column.php';
		require ELEMENTOR_PATH . 'includes/elements/section.php';
	}

	public function get_categories() {
		// TODO: Need to filter
		return [
			'basic' => [
				'title' => __( 'Elements', 'elementor' ),
				'icon' => 'font',
			],
			'pojo' => [
				'title' => __( 'Pojo Themes', 'elementor' ),
				'icon' => 'pojome',
			],
			'wordpress' => [
				'title' => __( 'WordPress', 'elementor' ),
				'icon' => 'wordpress',
			],
		];
	}

	public function register_element( $element_class ) {
		/** @var Element_Base $element_class */
		if ( ! class_exists( $element_class ) ) {
			return new \WP_Error( 'element_class_name_not_exists' );
		}

		$this->_elements[ $element_class::get_name() ] = [
			'class' => $element_class,
		];

		return true;
	}

	public function unregister_element_type( $name ) {
		if ( ! isset( $this->_element_types[ $name ] ) ) {
			return false;
		}

		unset( $this->_element_types[ $name ] );

		return true;
	}

	public function get_element_types( $element_name = null ) {
		if ( is_null( $this->_element_types ) ) {
			$this->_init_elements();
		}

		if ( $element_name ) {
			return isset( $this->_element_types[ $element_name ] ) ? $this->_element_types[ $element_name ] : null;
		}

		return $this->_element_types;
	}

	public function get_element_types_config() {
		$config = [];

			/** @var Element_Base $class */
			$class = $element_data['class'];

			$config[ $class::get_name() ] = $class::get_config();
		foreach ( $this->get_element_types() as $element ) {
		}

		return $config;
	}

	public function render_elements_content() {
		foreach ( $this->get_elements() as $element_data ) {
			/** @var Element_Base $class */
			$class = $element_data['class'];

			$class::print_template();
		}
	}

	public function ajax_save_builder() {
		if ( empty( $_POST['_nonce'] ) || ! wp_verify_nonce( $_POST['_nonce'], 'elementor-editing' ) ) {
			wp_send_json_error( new \WP_Error( 'token_expired' ) );
		}

		if ( empty( $_POST['post_id'] ) ) {
			wp_send_json_error( new \WP_Error( 'no_post_id' ) );
		}

		if ( ! User::is_current_user_can_edit( $_POST['post_id'] ) ) {
			wp_send_json_error( new \WP_Error( 'no_access' ) );
		}

		if ( isset( $_POST['revision'] ) && DB::REVISION_PUBLISH === $_POST['revision'] ) {
			$revision = DB::REVISION_PUBLISH;
		} else {
			$revision = DB::REVISION_DRAFT;
		}
		$posted = json_decode( stripslashes( html_entity_decode( $_POST['data'] ) ), true );

		Plugin::instance()->db->save_editor( $_POST['post_id'], $posted, $revision );

		wp_send_json_success();
	}

	public function __construct() {
		$this->require_files();

		add_action( 'wp_ajax_elementor_save_builder', [ $this, 'ajax_save_builder' ] );
	}
}
