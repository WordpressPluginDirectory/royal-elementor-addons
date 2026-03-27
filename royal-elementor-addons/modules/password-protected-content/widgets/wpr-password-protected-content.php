<?php
namespace WprAddons\Modules\PasswordProtectedContent\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use WprAddons\Classes\Utilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Wpr_Password_Protected_Content extends Widget_Base {

	public function get_name() {
		return 'wpr-password-protected-content';
	}

	public function get_title() {
		return esc_html__( 'Password Protected Content', 'wpr-addons' );
	}

	public function get_icon() {
		return 'wpr-icon eicon-lock';
	}

	public function get_categories() {
		return [ 'wpr-widgets' ];
	}

	public function get_keywords() {
		return [ 'password', 'protected', 'content', 'restrict', 'role', 'gate' ];
	}

	public function check_password_cookie( $settings ) {
		$widget_id = $this->get_id();
		$password = isset( $settings['set_password'] ) ? $settings['set_password'] : '';
		$cookie_name = 'wpr_ppc_' . md5( $password . $widget_id );

		return isset( $_COOKIE[ $cookie_name ] ) && $_COOKIE[ $cookie_name ] === md5( $password );
	}

	public function wpr_ppc_template( $id ) {
		if ( empty( $id ) ) {
			return '';
		}

		if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
			$default_language_code = apply_filters( 'wpml_default_language', null );
			if ( ICL_LANGUAGE_CODE !== $default_language_code ) {
				$id = icl_object_id( $id, 'elementor_library', false, ICL_LANGUAGE_CODE );
			}
		}

		$edit_link = '<span class="wpr-template-edit-btn" data-permalink="' . esc_url( get_permalink( $id ) ) . '">Edit Template</span>';

		$type = get_post_meta( get_the_ID(), '_wpr_template_type', true ) || get_post_meta( $id, '_elementor_template_type', true );
		$has_css = 'internal' === get_option( 'elementor_css_print_method' ) || '' !== $type;

		\WprAddons\Classes\Utilities::enqueue_inner_template_assets( $id );

		return \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $id, $has_css ) . $edit_link;
	}

	public function add_control_content_type() {
		$this->add_control(
			'content_type',
			[
				'label' => esc_html__( 'Content Type', 'wpr-addons' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'text',
				'options' => [
					'text'         => esc_html__( 'Text', 'wpr-addons' ),
					'pro-template' => esc_html__( 'Elementor Template (Pro)', 'wpr-addons' ),
				],
			]
		);
	}

	protected function register_controls() {

		// Section: Protected Content
		$this->start_controls_section(
			'section_protected_content',
			[
				'label' => esc_html__( 'Protected Content', 'wpr-addons' ),
			]
		);

		$this->add_control_content_type();

		// Upgrade to Pro Notice
		Utilities::upgrade_pro_notice( $this, Controls_Manager::RAW_HTML, 'password-protected-content', 'content_type', ['pro-template'] );

		$this->add_control(
			'protected_content',
			[
				'label' => esc_html__( 'Content', 'wpr-addons' ),
				'type' => Controls_Manager::WYSIWYG,
				'default' => esc_html__( 'This is the protected content that only authorized users can see.', 'wpr-addons' ),
				'condition' => [
					'content_type' => 'text',
				],
			]
		);

		$this->add_control(
			'protected_template',
			[
				'label' => esc_html__( 'Select Template', 'wpr-addons' ),
				'type' => 'wpr-ajax-select2',
				'options' => 'ajaxselect2/get_elementor_templates',
				'label_block' => true,
				'condition' => [
					'content_type' => 'template',
				],
			]
		);

		$this->end_controls_section();

		// Section: Protection Type
		$this->start_controls_section(
			'section_protection_type',
			[
				'label' => esc_html__( 'Protection Type', 'wpr-addons' ),
			]
		);

		$this->add_control(
			'protection_type',
			[
				'label' => esc_html__( 'Protection Type', 'wpr-addons' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'password',
				'options' => [
					'role' => esc_html__( 'User Role', 'wpr-addons' ),
					'password' => esc_html__( 'Password', 'wpr-addons' ),
				],
			]
		);

		$this->add_control(
			'user_roles',
			[
				'label' => esc_html__( 'Select Roles', 'wpr-addons' ),
				'type' => Controls_Manager::SELECT2,
				'multiple' => true,
				'options' => Utilities::get_user_roles(),
				'default' => [ 'administrator' ],
				'condition' => [
					'protection_type' => 'role',
				],
			]
		);

		$this->add_control(
			'set_password',
			[
				'label' => esc_html__( 'Set Password', 'wpr-addons' ),
				'type' => Controls_Manager::TEXT,
				'input_type' => 'text',
				'default' => '',
				'condition' => [
					'protection_type' => 'password',
				],
			]
		);

		$this->add_control(
			'separator_message',
			[
				'type' => Controls_Manager::DIVIDER,
			]
		);

		$this->add_control(
			'show_error_message',
			[
				'label' => esc_html__( 'Show Protected Message', 'wpr-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => 'yes',
			]
		);

		$this->add_control(
			'public_text',
			[
				'label' => esc_html__( 'Protected Message', 'wpr-addons' ),
				'type' => Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'You do not have permission to see this content.', 'wpr-addons' ),
				'condition' => [
					'show_error_message' => 'yes',
				],
			]
		);

		$this->add_control(
			'separator_preview',
			[
				'type' => Controls_Manager::DIVIDER,
			]
		);

		$this->add_control(
			'role_show_error_preview',
			[
				'label' => esc_html__( 'Preview Protected Message', 'wpr-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => '',
				'condition' => [
					'protection_type' => 'role',
					'show_error_message' => 'yes',
				],
				'description' => esc_html__( 'Enable preview.', 'wpr-addons' ),
			]
		);

		$this->add_control(
			'password_show_content_preview',
			[
				'label' => esc_html__( 'Show Content Preview', 'wpr-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => '',
				'condition' => [
					'protection_type' => 'password',
				],
				'description' => esc_html__( 'Enable to preview the protected content in the editor for styling.', 'wpr-addons' ),
			]
		);

		$this->end_controls_section();

		// Section: Pro Features
		Utilities::pro_features_list_section( $this, '', Controls_Manager::RAW_HTML, 'password-protected-content', [
			'Content Type: Elementor Template - protect any template behind a password',
		] );

		// ===== Style Tab =====

		// Section: Form Container Style
		$this->start_controls_section(
			'section_style_form_container',
			[
				'label' => esc_html__( 'Form Container', 'wpr-addons' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'form_layout',
			[
				'label' => esc_html__( 'Form Layout', 'wpr-addons' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'separate',
				'options' => [
					'separate' => esc_html__( 'Separate', 'wpr-addons' ),
					'inline' => esc_html__( 'Inline', 'wpr-addons' ),
				],
				'condition' => [
					'protection_type' => 'password',
				],
			]
		);

		$this->add_responsive_control(
			'form_inline_gap',
			[
				'label' => esc_html__( 'Gap', 'wpr-addons' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 50,
					],
				],
				'default' => [
					'size' => 10,
					'unit' => 'px',
				],
				'condition' => [
					'protection_type' => 'password',
				],
				'selectors' => [
					'{{WRAPPER}} .wpr-ppc-form' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'separator_container_style',
			[
				'type' => Controls_Manager::DIVIDER,
				'condition' => [
					'protection_type' => 'password',
				],
			]
		);

		$this->add_control(
			'form_container_bg_color',
			[
				'label' => esc_html__( 'Background Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wpr-ppc-container' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'form_container_padding',
			[
				'label' => esc_html__( 'Padding', 'wpr-addons' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'default' => [
					'top' => '0',
					'right' => '0',
					'bottom' => '0',
					'left' => '0',
					'unit' => 'px',
					'isLinked' => true,
				],
				'selectors' => [
					'{{WRAPPER}} .wpr-ppc-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'form_container_border',
				'selector' => '{{WRAPPER}} .wpr-ppc-container',
			]
		);

		$this->add_responsive_control(
			'form_container_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'wpr-addons' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors' => [
					'{{WRAPPER}} .wpr-ppc-container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'form_container_box_shadow',
				'selector' => '{{WRAPPER}} .wpr-ppc-container',
			]
		);

		$this->add_responsive_control(
			'form_container_align',
			[
				'label' => esc_html__( 'Alignment', 'wpr-addons' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => esc_html__( 'Left', 'wpr-addons' ),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'wpr-addons' ),
						'icon' => 'eicon-text-align-center',
					],
					'right' => [
						'title' => esc_html__( 'Right', 'wpr-addons' ),
						'icon' => 'eicon-text-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wpr-ppc-container' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		// Section: Message Style
		$this->start_controls_section(
			'section_style_message',
			[
				'label' => esc_html__( 'Message', 'wpr-addons' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'message_color',
			[
				'label' => esc_html__( 'Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#333333',
				'selectors' => [
					'{{WRAPPER}} .wpr-ppc-message' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'message_bg_color',
			[
				'label' => esc_html__( 'Background Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wpr-ppc-message' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'message_typography',
				'selector' => '{{WRAPPER}} .wpr-ppc-message',
			]
		);

		$this->add_responsive_control(
			'message_align',
			[
				'label' => esc_html__( 'Alignment', 'wpr-addons' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => esc_html__( 'Left', 'wpr-addons' ),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'wpr-addons' ),
						'icon' => 'eicon-text-align-center',
					],
					'right' => [
						'title' => esc_html__( 'Right', 'wpr-addons' ),
						'icon' => 'eicon-text-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wpr-ppc-message' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'separator_message_spacing',
			[
				'type' => Controls_Manager::DIVIDER,
			]
		);

		$this->add_responsive_control(
			'message_padding',
			[
				'label' => esc_html__( 'Padding', 'wpr-addons' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .wpr-ppc-message' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'message_margin',
			[
				'label' => esc_html__( 'Margin', 'wpr-addons' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .wpr-ppc-message' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Section: Input Field Style
		$this->start_controls_section(
			'section_style_input',
			[
				'label' => esc_html__( 'Input Field', 'wpr-addons' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'protection_type' => 'password',
				],
			]
		);

		$this->add_control(
			'input_text_color',
			[
				'label' => esc_html__( 'Text Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#333333',
				'selectors' => [
					'{{WRAPPER}} .wpr-ppc-password-input' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'input_placeholder_color',
			[
				'label' => esc_html__( 'Placeholder Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#999999',
				'selectors' => [
					'{{WRAPPER}} .wpr-ppc-password-input::placeholder' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'input_bg_color',
			[
				'label' => esc_html__( 'Background Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .wpr-ppc-password-input' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'input_typography',
				'selector' => '{{WRAPPER}} .wpr-ppc-password-input',
			]
		);

		$this->add_control(
			'separator_input_border',
			[
				'type' => Controls_Manager::DIVIDER,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'input_border',
				'selector' => '{{WRAPPER}} .wpr-ppc-password-input',
				'fields_options' => [
					'border' => [
						'default' => 'solid',
					],
					'width' => [
						'default' => [
							'top' => '1',
							'right' => '1',
							'bottom' => '1',
							'left' => '1',
							'unit' => 'px',
							'isLinked' => true,
						],
					],
					'color' => [
						'default' => '#020101',
					],
				],
			]
		);

		$this->add_responsive_control(
			'input_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'wpr-addons' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors' => [
					'{{WRAPPER}} .wpr-ppc-password-input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'separator_input_spacing',
			[
				'type' => Controls_Manager::DIVIDER,
			]
		);

		$this->add_responsive_control(
			'input_padding',
			[
				'label' => esc_html__( 'Padding', 'wpr-addons' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'default' => [
					'top' => '10',
					'right' => '15',
					'bottom' => '10',
					'left' => '15',
					'unit' => 'px',
				],
				'selectors' => [
					'{{WRAPPER}} .wpr-ppc-password-input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Section: Submit Button Style
		$this->start_controls_section(
			'section_style_button',
			[
				'label' => esc_html__( 'Submit Button', 'wpr-addons' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'protection_type' => 'password',
				],
			]
		);

		$this->start_controls_tabs( 'button_style_tabs' );

		// Normal
		$this->start_controls_tab(
			'button_normal',
			[
				'label' => esc_html__( 'Normal', 'wpr-addons' ),
			]
		);

		$this->add_control(
			'button_text_color',
			[
				'label' => esc_html__( 'Text Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .wpr-ppc-submit-btn' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_bg_color',
			[
				'label' => esc_html__( 'Background Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#605BE5',
				'selectors' => [
					'{{WRAPPER}} .wpr-ppc-submit-btn' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_border_color',
			[
				'label' => esc_html__( 'Border Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wpr-ppc-submit-btn' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		// Hover
		$this->start_controls_tab(
			'button_hover',
			[
				'label' => esc_html__( 'Hover', 'wpr-addons' ),
			]
		);

		$this->add_control(
			'button_text_color_hover',
			[
				'label' => esc_html__( 'Text Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .wpr-ppc-submit-btn:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_bg_color_hover',
			[
				'label' => esc_html__( 'Background Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#5A3BE0',
				'selectors' => [
					'{{WRAPPER}} .wpr-ppc-submit-btn:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_border_color_hover',
			[
				'label' => esc_html__( 'Border Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wpr-ppc-submit-btn:hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'button_typography',
				'selector' => '{{WRAPPER}} .wpr-ppc-submit-btn',
				'separator' => 'before',
			]
		);

		$this->add_control(
			'separator_button_border',
			[
				'type' => Controls_Manager::DIVIDER,
			]
		);

		$this->add_responsive_control(
			'button_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'wpr-addons' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default' => [
					'top' => '3',
					'right' => '3',
					'bottom' => '3',
					'left' => '3',
					'unit' => 'px',
				],
				'selectors' => [
					'{{WRAPPER}} .wpr-ppc-submit-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_padding',
			[
				'label' => esc_html__( 'Padding', 'wpr-addons' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'default' => [
					'top' => '10',
					'right' => '30',
					'bottom' => '10',
					'left' => '30',
					'unit' => 'px',
				],
				'selectors' => [
					'{{WRAPPER}} .wpr-ppc-submit-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'separator_button_extra',
			[
				'type' => Controls_Manager::DIVIDER,
			]
		);

		$this->add_control(
			'button_transition',
			[
				'label' => esc_html__( 'Transition Duration', 'wpr-addons' ),
				'type' => Controls_Manager::NUMBER,
				'default' => 0.3,
				'min' => 0,
				'max' => 3,
				'step' => 0.1,
				'selectors' => [
					'{{WRAPPER}} .wpr-ppc-submit-btn' => 'transition-duration: {{VALUE}}s;',
				],
			]
		);

		$this->end_controls_section();

		// Section: Wrong Password Error Style
		$this->start_controls_section(
			'section_style_error',
			[
				'label' => esc_html__( 'Password Error', 'wpr-addons' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'protection_type' => 'password',
				],
			]
		);

		$this->add_control(
			'error_color',
			[
				'label' => esc_html__( 'Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#e74c3c',
				'selectors' => [
					'{{WRAPPER}} .wpr-ppc-error' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'error_typography',
				'selector' => '{{WRAPPER}} .wpr-ppc-error',
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		$is_editor = \Elementor\Plugin::$instance->editor->is_edit_mode();
		$authorized = false;

		if ( 'role' === $settings['protection_type'] ) {
			$allowed_roles = ! empty( $settings['user_roles'] ) ? $settings['user_roles'] : [];

			if ( $is_editor ) {
				// In editor: show content by default, show error only when preview switcher is on
				$authorized = 'yes' !== $settings['role_show_error_preview'];
			} elseif ( ! is_user_logged_in() ) {
				$authorized = in_array( 'guest', $allowed_roles, true );
			} else {
				$current_user = wp_get_current_user();
				$authorized = ! empty( array_intersect( $current_user->roles, $allowed_roles ) );
			}
		} elseif ( 'password' === $settings['protection_type'] ) {
			$authorized = $this->check_password_cookie( $settings );

			// Editor preview overrides
			if ( $is_editor ) {
				$authorized = 'yes' === $settings['password_show_content_preview'];
			}
		}

		$layout = isset( $settings['form_layout'] ) ? $settings['form_layout'] : 'separate';
		$form_class = 'wpr-ppc-form';
		if ( 'inline' === $layout ) {
			$form_class .= ' wpr-ppc-form-inline';
		}

		echo '<style>
			.wpr-ppc-form {
				display: flex;
				flex-direction: column;
			}
			.wpr-ppc-form .wpr-ppc-password-input {
				width: 100%;
			}
			.wpr-ppc-form-inline {
				flex-direction: row;
				flex-wrap: wrap;
				align-items: center;
			}
			.wpr-ppc-form-inline .wpr-ppc-password-input {
				flex: 1;
				min-width: 0;
			}
			.wpr-ppc-form-inline .wpr-ppc-error {
				width: 100%;
			}
		</style>';

		if ( $authorized ) {
			echo '<div class="wpr-ppc-container">';
			echo '<div class="wpr-ppc-content">';
			if ( 'text' === $settings['content_type'] ) {
				echo wp_kses_post( $settings['protected_content'] );
			} elseif ( 'template' === $settings['content_type'] && ! empty( $settings['protected_template'] ) ) {
				echo $this->wpr_ppc_template( $settings['protected_template'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</div>';
			echo '</div>';
		} elseif ( 'yes' === $settings['show_error_message'] || 'password' === $settings['protection_type'] ) {
			echo '<div class="wpr-ppc-container">';

			if ( 'yes' === $settings['show_error_message'] ) {
				echo '<div class="wpr-ppc-message">';
				echo wp_kses_post( $settings['public_text'] );
				echo '</div>';
			}

			if ( 'password' === $settings['protection_type'] ) {
				echo '<form class="' . esc_attr( $form_class ) . '" data-widget-id="' . esc_attr( $this->get_id() ) . '" data-post-id="' . esc_attr( get_the_ID() ) . '" data-nonce="' . esc_attr( wp_create_nonce( 'wpr_ppc_verify' ) ) . '">';
				echo '<input type="password" class="wpr-ppc-password-input" placeholder="' . esc_attr__( 'Enter Password', 'wpr-addons' ) . '" />';
				echo '<button type="submit" class="wpr-ppc-submit-btn">' . esc_html__( 'Submit', 'wpr-addons' ) . '</button>';
				echo '<div class="wpr-ppc-error" style="display:none;">' . esc_html__( 'Incorrect password. Please try again.', 'wpr-addons' ) . '</div>';
				echo '</form>';
			}

			echo '</div>';
		}
	}
}
