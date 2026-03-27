<?php

use \Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPR_DC_Section_General_Settings extends WPR_DC_Section_Base {

	public function get_id() {
		return 'wpr_dc_general';
	}

	public function get_label() {
		return esc_html__( 'Visibility', 'wpr-addons' );
	}

	public function get_order() {
		return 10;
	}

	public function register_controls( $element ) {
		$element->add_control(
			'wpr_dc_enabled',
			[
				'label'        => esc_html__( 'Enable Visibility Logic', 'wpr-addons' ),
				'description'  => esc_html__( 'Powered by Royal Addons for Elementor.', 'wpr-addons' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => '',
				'return_value' => 'yes',
				'render_type'  => 'none',
			]
		);

		$element->add_control(
			'wpr_dc_mode',
			[
				'label'       => esc_html__( 'When conditions are met', 'wpr-addons' ),
				'type'        => Controls_Manager::SELECT,
				'label_block' => true,
				'default'     => 'show',
				'options'     => [
					'show' => esc_html__( 'Show this element', 'wpr-addons' ),
					'hide' => esc_html__( 'Hide this element', 'wpr-addons' ),
				],
				'condition' => [
					'wpr_dc_enabled' => 'yes',
				],
				'render_type' => 'none',
			]
		);

		$element->add_control(
			'wpr_dc_logic',
			[
				'label'       => esc_html__( 'Condition logic', 'wpr-addons' ),
				'type'        => Controls_Manager::SELECT,
				'label_block' => true,
				'default'     => 'any',
				'options'     => [
					'any' => esc_html__( 'Any condition is true (OR)', 'wpr-addons' ),
					'all' => esc_html__( 'All conditions are true (AND)', 'wpr-addons' ),
				],
				'condition' => [
					'wpr_dc_enabled' => 'yes',
				],
				'render_type' => 'none',
			]
		);

		$element->add_control(
			'wpr_dc_css_only',
			[
				'label'       => esc_html__( 'Hide with CSS only', 'wpr-addons' ),
				'description' => esc_html__( 'Keep the element in the page code but hide it visually. By default, hidden elements are removed from the page completely.', 'wpr-addons' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'return_value'=> 'yes',
				'condition'   => [
					'wpr_dc_enabled' => 'yes',
				],
				'render_type' => 'none',
			]
		);
	}

	/**
	 * General settings don't evaluate conditions — they provide global config.
	 */
	public function evaluate( $settings ) {
		return null;
	}
}
