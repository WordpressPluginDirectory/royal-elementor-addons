<?php
namespace WprAddons\Modules\Unfold\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Widget_Base;
use Elementor\Icons_Manager;
use Elementor\Plugin;
use WprAddons\Classes\Utilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Wpr_Unfold extends Widget_Base {

	public function get_name() {
		return 'wpr-unfold';
	}

	public function get_title() {
		return esc_html__( 'Unfold', 'wpr-addons' );
	}

	public function get_icon() {
		return 'wpr-icon eicon-spacer';
	}

	public function get_categories() {
		return [ 'wpr-widgets' ];
	}

	public function get_keywords() {
		return [ 'royal', 'unfold', 'fold', 'read more', 'expand', 'collapse', 'toggle content' ];
	}

	public function has_widget_inner_wrapper(): bool {
		return ! \Elementor\Plugin::$instance->experiments->is_feature_active( 'e_optimized_markup' );
	}

	public function get_custom_help_url() {
		if ( empty( get_option( 'wpr_wl_plugin_links' ) ) )
			return 'https://wordpress.org/support/plugin/royal-elementor-addons/';
		return '';
	}

	public function wpr_unfold_template( $id ) {
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

		return \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $id, $has_css ) . $edit_link;
	}

	public function add_control_unfold_content_type() {
		$this->add_control(
			'unfold_content_type',
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

		// Section: Content
		$this->start_controls_section(
			'section_unfold_content',
			[
				'label' => esc_html__( 'Content', 'wpr-addons' ),
			]
		);

		$this->add_control_unfold_content_type();

		// Upgrade to Pro Notice
		Utilities::upgrade_pro_notice( $this, Controls_Manager::RAW_HTML, 'unfold', 'unfold_content_type', ['pro-template'] );

		$this->add_control(
			'unfold_title',
			[
				'label' => esc_html__( 'Title', 'wpr-addons' ),
				'type' => Controls_Manager::TEXT,
				'default' => 'Add Your Heading Text Here',
				'dynamic' => [
					'active' => true,
				],
				'separator' => 'before',
				'condition' => [
					'unfold_content_type' => 'text',
				],
			]
		);

		$this->add_control(
			'unfold_content',
			[
				'label' => esc_html__( 'Content', 'wpr-addons' ),
				'type' => Controls_Manager::WYSIWYG,
				'default' => '<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Phasellus hendrerit. Pellentesque aliquet nibh nec urna. In nisi neque, aliquet vel, dapibus id, mattis vel, nisi. Sed pretium, ligula sollicitudin laoreet viverra, tortor libero sodales leo, eget blandit nunc tortor eu nibh. Nullam mollis. Ut justo. Suspendisse potenti.</p>

				<p>Sed egestas, ante et vulputate volutpat, eros pede semper est, vitae luctus metus libero eu augue. Morbi purus libero, faucibus adipiscing, commodo quis, gravida id, est. Sed lectus. Praesent elementum hendrerit tortor. Sed semper lorem at felis. Vestibulum volutpat, lacus a ultrices sagittis, mi neque euismod dui, eu pulvinar nunc sapien ornare nisl. Phasellus pede arcu, dapibus eu, fermentum et, dapibus sed, urna.</p>',
				'dynamic' => [
					'active' => true,
				],
				'condition' => [
					'unfold_content_type' => 'text',
				],
			]
		);

		$this->add_control(
			'unfold_template',
			[
				'label' => esc_html__( 'Select Template', 'wpr-addons' ),
				'type' => 'wpr-ajax-select2',
				'options' => 'ajaxselect2/get_elementor_templates',
				'label_block' => true,
				'condition' => [
					'unfold_content_type' => 'template',
				],
			]
		);

		$this->end_controls_section();

		// Section: Button
		$this->start_controls_section(
			'section_unfold_button',
			[
				'label' => esc_html__( 'Button', 'wpr-addons' ),
			]
		);

		$this->add_control(
			'unfold_expand_btn_text',
			[
				'label' => esc_html__( 'Expand Text', 'wpr-addons' ),
				'type' => Controls_Manager::TEXT,
				'default' => 'Read More',
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'unfold_expand_btn_icon',
			[
				'label' => esc_html__( 'Expand Icon', 'wpr-addons' ),
				'type' => Controls_Manager::ICONS,
				'skin' => 'inline',
				'label_block' => false,
				'separator' => 'after',
			]
		);

		$this->add_control(
			'unfold_collapse_btn_text',
			[
				'label' => esc_html__( 'Collapse Text', 'wpr-addons' ),
				'type' => Controls_Manager::TEXT,
				'default' => 'Read Less',
				'dynamic' => [
					'active' => true,
				],
			]
		);

		$this->add_control(
			'unfold_collapse_btn_icon',
			[
				'label' => esc_html__( 'Collapse Icon', 'wpr-addons' ),
				'type' => Controls_Manager::ICONS,
				'skin' => 'inline',
				'label_block' => false,
			]
		);

		$this->add_control(
			'unfold_btn_icon_position',
			[
				'label' => esc_html__( 'Icon Position', 'wpr-addons' ),
				'type' => Controls_Manager::SELECT,
				'separator' => 'before',
				'default' => 'after',
				'options' => [
					'before' => esc_html__( 'Before Text', 'wpr-addons' ),
					'after' => esc_html__( 'After Text', 'wpr-addons' ),
				],
				'selectors_dictionary' => [
					'before' => 'flex-direction: row-reverse; display: flex; align-items: center;',
					'after' => 'flex-direction: row; display: flex; align-items: center;',
				],
				'selectors' => [
					'{{WRAPPER}} .wpr-unfold-btn' => '{{VALUE}}',
				],
				'separator' => 'after',
			]
		);

		$this->add_responsive_control(
			'unfold_btn_alignment',
			[
				'label' => esc_html__( 'Button Alignment', 'wpr-addons' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'flex-start' => [
						'title' => esc_html__( 'Left', 'wpr-addons' ),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'wpr-addons' ),
						'icon' => 'eicon-text-align-center',
					],
					'flex-end' => [
						'title' => esc_html__( 'Right', 'wpr-addons' ),
						'icon' => 'eicon-text-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wpr-unfold-btn' => 'align-self: {{VALUE}};',
				],
				'default' => 'flex-start',
			]
		);

		$this->end_controls_section();

		// Section: Settings
		$this->start_controls_section(
			'section_unfold_settings',
			[
				'label' => esc_html__( 'Settings', 'wpr-addons' ),
			]
		);

		$this->add_responsive_control(
			'unfold_collapsed_height',
			[
				'label' => esc_html__( 'Content Height', 'wpr-addons' ),
				'type' => Controls_Manager::SLIDER,
				'render_type' => 'template',
				'default' => [
					'size' => 100,
				],
				'range' => [
					'px' => [
						'min' => 20,
						'max' => 800,
						'step' => 1,
					],
				],
				'size_units' => [ 'px' ],
				'selectors' => [
					'{{WRAPPER}} .wpr-unfold-data' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'unfold_transition_duration',
			[
				'label' => esc_html__( 'Transition Duration', 'wpr-addons' ),
				'type' => Controls_Manager::NUMBER,
				'min' => 0,
				'max' => 5000,
				'step' => 1,
				'default' => 300,
			]
		);

		$this->end_controls_section();

		// Section: Pro Features
		Utilities::pro_features_list_section( $this, '', Controls_Manager::RAW_HTML, 'unfold', [
			'Content Type: Elementor Template - unfold any template',
		] );

		// Style: Title
		$this->start_controls_section(
			'section_style_title',
			[
				'label' => esc_html__( 'Title', 'wpr-addons' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'unfold_content_type' => 'text',
					'unfold_title!' => '',
				],
			]
		);

		$this->add_control(
			'title_color',
			[
				'label' => esc_html__( 'Text Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wpr-unfold-heading' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'title_typography',
				'selector' => '{{WRAPPER}} .wpr-unfold-heading',
			]
		);

		$this->add_responsive_control(
			'title_distance',
			[
				'label' => esc_html__( 'Distance', 'wpr-addons' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 12,
				],
				'selectors' => [
					'{{WRAPPER}} .wpr-unfold-heading' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'title_alignment',
			[
				'label' => esc_html__( 'Alignment', 'wpr-addons' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'flex-start' => [
						'title' => esc_html__( 'Left', 'wpr-addons' ),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'wpr-addons' ),
						'icon' => 'eicon-text-align-center',
					],
					'flex-end' => [
						'title' => esc_html__( 'Right', 'wpr-addons' ),
						'icon' => 'eicon-text-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wpr-unfold-heading' => 'align-self: {{VALUE}};',
				],
				'default' => 'flex-start',
				'separator' => 'before',
			]
		);

		$this->end_controls_section();

		// Style: Description
		$this->start_controls_section(
			'section_style_description',
			[
				'label' => esc_html__( 'Description', 'wpr-addons' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'condition' => [
					'unfold_content_type' => 'text',
					'unfold_content!' => '',
				],
			]
		);

		$this->add_control(
			'desc_color',
			[
				'label' => esc_html__( 'Text Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wpr-unfold-content p' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'desc_typography',
				'selector' => '{{WRAPPER}} .wpr-unfold-content p',
			]
		);

		$this->add_responsive_control(
			'desc_paragraph_spacing',
			[
				'label' => esc_html__( 'Paragraph Spacing', 'wpr-addons' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wpr-unfold-content p:not(:last-child), {{WRAPPER}} .wpr-unfold-content ul:not(:last-child)' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'desc_alignment',
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
					'{{WRAPPER}} .wpr-unfold-data-inner' => 'text-align: {{VALUE}};',
				],
				'default' => 'left',
				'separator' => 'before',
			]
		);

		$this->end_controls_section();

		// Style: Overlay
		$this->start_controls_section(
			'section_style_overlay',
			[
				'label' => esc_html__( 'Overlay', 'wpr-addons' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'overlay_color',
			[
				'label' => esc_html__( 'Overlay Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .wpr-unfold-data:after' => 'background: linear-gradient(rgba(255, 255, 255, 0), {{VALUE}});',
				],
			]
		);

		$this->add_responsive_control(
			'overlay_height',
			[
				'label' => esc_html__( 'Overlay Height', 'wpr-addons' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range' => [
					'px' => [
						'min' => 20,
						'max' => 200,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wpr-unfold-data:after' => 'height: {{SIZE}}{{UNIT}}; line-height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// Style: Button
		$this->start_controls_section(
			'section_style_button',
			[
				'label' => esc_html__( 'Button', 'wpr-addons' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'tabs_button_colors' );

		$this->start_controls_tab(
			'tab_button_normal',
			[
				'label' => esc_html__( 'Normal', 'wpr-addons' ),
			]
		);

		$this->add_control(
			'button_color',
			[
				'label' => esc_html__( 'Text Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#222222',
				'selectors' => [
					'{{WRAPPER}} .wpr-unfold-btn' => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpr-unfold-btn .wpr-unfold-expand-icon' => 'color: {{VALUE}}; fill: {{VALUE}};',
					'{{WRAPPER}} .wpr-unfold-btn .wpr-unfold-collapse-icon' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'button_bg_color',
				'selector' => '{{WRAPPER}} .wpr-unfold-btn',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_button_hover',
			[
				'label' => esc_html__( 'Hover', 'wpr-addons' ),
			]
		);

		$this->add_control(
			'button_hover_color',
			[
				'label' => esc_html__( 'Text Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#605BE5',
				'selectors' => [
					'{{WRAPPER}} .wpr-unfold-btn:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .wpr-unfold-btn:hover .wpr-unfold-expand-icon' => 'color: {{VALUE}}; fill: {{VALUE}};',
					'{{WRAPPER}} .wpr-unfold-btn:hover .wpr-unfold-collapse-icon' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'button_hover_bg_color',
				'selector' => '{{WRAPPER}} .wpr-unfold-btn:hover',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'button_typography',
				'selector' => '{{WRAPPER}} .wpr-unfold-btn',
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'button_box_shadow',
				'selector' => '{{WRAPPER}} .wpr-unfold-btn',
			]
		);

		$this->add_control(
			'button_transition_duration',
			[
				'label' => esc_html__( 'Transition Duration', 'wpr-addons' ),
				'type' => Controls_Manager::NUMBER,
				'default' => 0.2,
				'min' => 0,
				'max' => 5,
				'step' => 0.1,
				'selectors' => [
					'{{WRAPPER}} .wpr-unfold-btn' => '-webkit-transition: all {{VALUE}}s ease; transition: all {{VALUE}}s ease;',
					'{{WRAPPER}} .wpr-unfold-btn svg' => '-webkit-transition: all {{VALUE}}s ease; transition: all {{VALUE}}s ease;',
				],
			]
		);

		$this->add_responsive_control(
			'button_icon_size',
			[
				'label' => esc_html__( 'Icon Size', 'wpr-addons' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 50,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 10,
				],
				'selectors' => [
					'{{WRAPPER}} .wpr-unfold-btn svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'button_icon_spacing',
			[
				'label' => esc_html__( 'Icon Spacing', 'wpr-addons' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 25,
					],
				],
				'default' => [
					'size' => 5,
				],
				'selectors' => [
					'{{WRAPPER}} .wpr-unfold-btn' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'button_border',
				'selector' => '{{WRAPPER}} .wpr-unfold-btn',
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'button_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'wpr-addons' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .wpr-unfold-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_padding',
			[
				'label' => esc_html__( 'Padding', 'wpr-addons' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors' => [
					'{{WRAPPER}} .wpr-unfold-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'button_distance',
			[
				'label' => esc_html__( 'Distance', 'wpr-addons' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 30,
				],
				'selectors' => [
					'{{WRAPPER}} .wpr-unfold-btn' => 'margin-top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$config = [
			'expand_text' => ! empty( $settings['unfold_expand_btn_text'] ) ? esc_attr( $settings['unfold_expand_btn_text'] ) : '',
			'collapse_text' => ! empty( $settings['unfold_collapse_btn_text'] ) ? esc_attr( $settings['unfold_collapse_btn_text'] ) : '',
			'collapse_height' => ! empty( $settings['unfold_collapsed_height']['size'] ) ? intval( $settings['unfold_collapsed_height']['size'] ) : 79,
			'transition_duration' => ! empty( $settings['unfold_transition_duration'] ) ? intval( $settings['unfold_transition_duration'] ) : 300,
		];

		$wrapper_class = 'wpr-unfold-wrapper';

		$this->add_render_attribute( 'wrapper', [
			'class' => $wrapper_class,
			'data-config' => wp_json_encode( $config ),
		] );

		?>
		<div <?php $this->print_render_attribute_string( 'wrapper' ); ?>>
			<?php if ( 'text' === $settings['unfold_content_type'] && ! empty( $settings['unfold_title'] ) ) : ?>
				<h3 class="wpr-unfold-heading"><?php echo esc_html( $settings['unfold_title'] ); ?></h3>
			<?php endif; ?>

			<div class="wpr-unfold-data">
				<div class="wpr-unfold-data-inner">
					<?php if ( 'template' === $settings['unfold_content_type'] && ! empty( $settings['unfold_template'] ) ) : ?>
						<div class="wpr-unfold-content"><?php echo $this->wpr_unfold_template( $settings['unfold_template'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<?php else : ?>
						<div class="wpr-unfold-content"><?php echo do_shortcode( wp_kses_post( $settings['unfold_content'] ) ); ?></div>
					<?php endif; ?>
				</div>
			</div>

			<button class="wpr-unfold-btn">
				<?php echo ! empty( $settings['unfold_expand_btn_text'] ) ? esc_html( $settings['unfold_expand_btn_text'] ) : ''; ?>
				<?php Icons_Manager::render_icon( $settings['unfold_expand_btn_icon'], [ 'aria-hidden' => 'true', 'class' => 'wpr-unfold-expand-icon' ] ); ?>
				<?php Icons_Manager::render_icon( $settings['unfold_collapse_btn_icon'], [ 'aria-hidden' => 'true', 'class' => 'wpr-unfold-collapse-icon' ] ); ?>
			</button>
		</div>
		<?php
	}
}
