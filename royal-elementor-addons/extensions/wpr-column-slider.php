<?php
use Elementor\Controls_Manager;
use WprAddons\Classes\Utilities;
use Elementor\Group_Control_Typography;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Wpr_Column_Slider {
	public function __construct() {
		add_action( 'elementor/element/section/section_advanced/after_section_end', [ $this, 'register_controls' ], 10 );
		add_action( 'elementor/section/print_template', array( $this, '_print_template' ), 10, 2 );
		add_action( 'elementor/frontend/section/before_render', array( $this, '_before_render' ), 10, 1 );

		// FLEXBOX CONTAINER
		add_action( 'elementor/element/container/section_layout/after_section_end', [ $this, 'register_controls' ], 10 );
		add_action( 'elementor/container/print_template', array( $this, '_print_template' ), 10, 2 );
		add_action( 'elementor/frontend/container/before_render', array( $this, '_before_render' ), 10, 1 );

	}
    
    public function register_controls( $element ) {

		$element->start_controls_section(
			'wpr_section_column_slider',
			[
				'tab'   => Controls_Manager::TAB_ADVANCED,
                'label' =>  sprintf(esc_html__('Column Slider - %s', 'wpr-addons'), esc_html('RA')),
            ]
		);

        $element->add_control(
            'wpr_section_column_slider_update',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => '<div class="elementor-update-preview editor-wpr-preview-update"><span>Update changes to Preview</span><button class="elementor-button elementor-button-success" onclick="elementor.reloadPreview();">Apply</button>',
                'separator' => 'after'
            ]
        );

		$element->add_control (
			'wpr_enable_column_slider',
			[
				'type' => Controls_Manager::SWITCHER,
				'label' => esc_html__( 'Enable Column Slider', 'wpr-addons' ),
				'description' => esc_html__( 'Converts section columns into a horizontal sliding carousel. Each column becomes a slide that users can swipe or navigate through. Click "Apply" above after enabling to see the changes.', 'wpr-addons' ),
				'default' => 'no',
				'return_value' => 'yes',
				'prefix_class' => 'wpr-column-slider-',
				'render_type' => 'template',
			]
		);

		$element->add_responsive_control(
			'wpr_column_slider_slides_to_show',
			[
				'label' => esc_html__( 'Slides To Show', 'wpr-addons' ),
				'description' => esc_html__( 'Number of slides visible at once. Set different values per breakpoint for responsive behavior.', 'wpr-addons' ),
				'type' => Controls_Manager::NUMBER,
				'default' => 1,
				'min' => 1,
				'condition' => [
					'wpr_enable_column_slider' => 'yes',
				]
			]
		);

		$element->add_responsive_control(
			'wpr_column_slider_space_between',
			[
				'label' => __( 'Gutter', 'wpr-addons' ),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'default' => 5,
				'condition' => [
					'wpr_enable_column_slider' => 'yes',
				]
			]
		);

		$element->add_control(
			'wpr_column_slider_speed',
			[
				'label' => __( 'Speed', 'wpr-addons' ),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'default' => 3500,
				'condition' => [
					'wpr_enable_column_slider' => 'yes',
				]
			]
		);

		$element->add_control (
			'wpr_enable_cs_nav',
			[
				'type' => Controls_Manager::SWITCHER,
				'label' => esc_html__( 'Navigation', 'wpr-addons' ),
				'render_type' => 'template',
				'separator' => 'before',
				'condition' => [
					'wpr_enable_column_slider' => 'yes',
				]
			]
		);

		$element->add_control(
			'wpr_cs_nav_arrows',
			[
				'label' => esc_html__( 'Navigation Icon', 'wpr-addons' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'fas fa-angle',
				'options' => [
					'fas fa-angle' => esc_html__( 'Angle', 'wpr-addons' ),
					'fas fa-angle-double' => esc_html__( 'Angle Double', 'wpr-addons' ),
					'fas fa-arrow' => esc_html__( 'Arrow', 'wpr-addons' ),
					'fas fa-arrow-alt-circle' => esc_html__( 'Arrow Circle', 'wpr-addons' ),
					'far fa-arrow-alt-circle' => esc_html__( 'Arrow Circle Alt', 'wpr-addons' ),
					'fas fa-long-arrow-alt' => esc_html__( 'Long Arrow', 'wpr-addons' ),
					'fas fa-chevron' => esc_html__( 'Chevron', 'wpr-addons' ),
				],
				'condition' => [
					'wpr_enable_column_slider' => 'yes',
					'wpr_enable_cs_nav' => 'yes'
				]
			]
		);

		$element->start_controls_tabs(
			'wpr_cs_nav_tabs',
			[
				'condition' => [
					'wpr_enable_column_slider' => 'yes',
					'wpr_enable_cs_nav' => 'yes'
				]
			]
		);

		$element->start_controls_tab(
			'wpr_cs_nav_tab_normal',
			[
				'label' => __( 'Normal', 'wpr-addons' ),
			]
		);

		$element->add_control(
			'wpr_cs_nav_icon_color',
			[
				'label'  => esc_html__( 'Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#FFF',
				'selectors' => [
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-prev i' => 'color: {{VALUE}}',
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-next i' => 'color: {{VALUE}}',
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-prev svg' => 'fill: {{VALUE}}',
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-next svg' => 'fill: {{VALUE}}'
				],
				'condition' => [
					'wpr_enable_column_slider' => 'yes',
					'wpr_enable_cs_nav' => 'yes'
				]
			]
		);

		$element->add_control(
			'wpr_cs_nav_icon_bg_color',
			[
				'label'  => esc_html__( 'Background Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#605BE5',
				'selectors' => [
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-prev' => 'background-color: {{VALUE}}',
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-next' => 'background-color: {{VALUE}}',
				],
			]
		);
		
		$element->add_control(
			'wpr_cs_nav_icon_border_color',
			[
				'label'  => esc_html__( 'Border Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-prev' => 'border-color: {{VALUE}}',
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-next' => 'border-color: {{VALUE}}',
				]
			]
		);

		$element->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'box_shadow_navigation',
				'label' => __( 'Box Shadow', 'wpr-addons' ),
				'selector' => '{{WRAPPER}}.wpr-column-slider-yes .swiper-button-prev, {{WRAPPER}}.wpr-column-slider-yes .swiper-button-next',
			]
		);

		$element->add_control(
			'navigation_transition',
			[
				'label' => esc_html__( 'Transition', 'wpr-addons' ),
				'type' => Controls_Manager::NUMBER,
				'default' => 1,
				'min' => 0,
				'max' => 5,
				'step' => 0.1,
				'selectors' => [
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-prev' => '-webkit-transition: all {{VALUE}}s ease; transition: all {{VALUE}}s ease;',
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-next' => '-webkit-transition: all {{VALUE}}s ease; transition: all {{VALUE}}s ease;',
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-prev i' => '-webkit-transition-duration: {{VALUE}}s; transition-duration: {{VALUE}}s;',
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-next i' => '-webkit-transition-duration: {{VALUE}}s; transition-duration: {{VALUE}}s;',
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-prev svg' => '-webkit-transition-duration: {{VALUE}}s; transition-duration: {{VALUE}}s;',
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-next svg' => '-webkit-transition-duration: {{VALUE}}s; transition-duration: {{VALUE}}s;'
				],
			]
		);
		
		$element->end_controls_tab();

		$element->start_controls_tab(
			'wpr_cs_nav_tab_hover',
			[
				'label' => __( 'Hover', 'wpr-addons' ),
			]
		);
		
		$element->add_control(
			'wpr_cs_nav_icon_color_hover',
			[
				'label'  => esc_html__( 'Icon Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-next:hover i' => 'color: {{VALUE}}',
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-prev:hover i' => 'color: {{VALUE}}',
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-prev:hover svg' => 'fill: {{VALUE}}',
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-next:hover svg' => 'fill: {{VALUE}}'
				]
			]
		);

		$element->add_control(
			'wpr_cs_nav_icon_bg_color_hover',
			[
				'label'  => esc_html__( 'Background Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#423EC0',
				'selectors' => [
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-prev:hover' => 'background-color: {{VALUE}}',
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-next:hover' => 'background-color: {{VALUE}}',
				],
			]
		);
		
		$element->add_control(
			'wpr_cs_nav_icon_border_color_hover',
			[
				'label'  => esc_html__( 'Border Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-prev:hover' => 'border-color: {{VALUE}}',
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-next:hover' => 'border-color: {{VALUE}}',
				]
			]
		);

		$element->add_group_control(
			\Elementor\Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'box_shadow_navigation_hover',
				'label' => __( 'Box Shadow', 'wpr-addons' ),
				'selector' => '{{WRAPPER}} .flipster__button:hover',
			]
		);

		$element->end_controls_tab();

		$element->end_controls_tabs();
		
		$element->add_responsive_control(
			'wpr_cs_nav_icon_size',
			[
				'type' => Controls_Manager::SLIDER,
				'label' => esc_html__( 'Icon Size', 'wpr-addons' ),
				'size_units' => [ 'px' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 200,
					]
				],
				'default' => [
					'unit' => 'px',
					'size' => 20,
				],			
				'selectors' => [
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-prev i' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-next i' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-prev svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-next svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};'
				],
				'separator' => 'before',
				'condition' => [
					'wpr_enable_column_slider' => 'yes',
					'wpr_enable_cs_nav' => 'yes'
				]
			]
		);
		
		$element->add_responsive_control(
			'wpr_cs_nav_icon_bg_size',
			[
				'type' => Controls_Manager::SLIDER,
				'label' => esc_html__( 'Box Size', 'wpr-addons' ),
				'size_units' => [ 'px' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 150,
					]
				],
				'default' => [
					'unit' => 'px',
					'size' => 35,
				],			
				'selectors' => [
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-prev' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-next' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};'
				],
				'condition' => [
					'wpr_enable_column_slider' => 'yes',
					'wpr_enable_cs_nav' => 'yes'
				]
			]
		);

		$element->add_control(
			'wpr_cs_nav_border',
			[
				'label' => esc_html__( 'Border Type', 'wpr-addons' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'none' => esc_html__( 'None', 'wpr-addons' ),
					'solid' => esc_html__( 'Solid', 'wpr-addons' ),
					'double' => esc_html__( 'Double', 'wpr-addons' ),
					'dotted' => esc_html__( 'Dotted', 'wpr-addons' ),
					'dashed' => esc_html__( 'Dashed', 'wpr-addons' ),
					'groove' => esc_html__( 'Groove', 'wpr-addons' ),
				],
				'default' => 'none',
				'selectors' => [
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-prev' => 'border-style: {{VALUE}};',
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-next' => 'border-style: {{VALUE}};'
				],
				'separator' => 'before',
				'condition' => [
					'wpr_enable_column_slider' => 'yes',
					'wpr_enable_cs_nav' => 'yes'
				]
			]
		);
		
		$element->add_control(
			'wpr_cs_nav_border_width',
			[
				'type' => Controls_Manager::DIMENSIONS,
				'label' => esc_html__( 'Border Width', 'wpr-addons' ),
				'size_units' => [ 'px', '%' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 150,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					]
				],
				'default' => [
					'top' => 1,
					'right' => 1,
					'bottom' => 1,
					'left' => 1,
					'unit' => 'px'
				],			
				'selectors' => [
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-prev' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',	
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-next' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition' => [
					'wpr_enable_column_slider' => 'yes',
					'wpr_enable_cs_nav' => 'yes',
					'wpr_cs_nav_border!' => 'none'
				]
			]
		);
		
		$element->add_control(
			'icon_border_radius',
			[
				'type' => Controls_Manager::DIMENSIONS,
				'label' => esc_html__( 'Border Radius', 'wpr-addons' ),
				'size_units' => [ 'px', '%' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 150,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					]
				],
				'default' => [
					'top' => 0,
					'right' => 0,
					'bottom' => 0,
					'left' => 0,
					'unit' => 'px'
				],			
				'selectors' => [
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-prev' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',	
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-button-next' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'
				],
				'condition' => [
					'wpr_enable_column_slider' => 'yes',
					'wpr_enable_cs_nav' => 'yes'
				]
			]
		);

		$element->add_control (
			'wpr_enable_cs_pag',
			[
				'type' => Controls_Manager::SWITCHER,
				'label' => esc_html__( 'Pagination', 'wpr-addons' ),
				'render_type' => 'template',
				'separator' => 'before',
				'condition' => [
					'wpr_enable_column_slider' => 'yes',
				]
			]
		);

		$element->add_control(
			'wpr_cs_pag_type',
			[
				'label' => esc_html__( 'Pagination Type', 'wpr-addons' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'bullets',
				'options' => [
					'bullets' => esc_html__( 'Bullets', 'wpr-addons' ),
					'fraction' => esc_html__( 'Fraction', 'wpr-addons' ),
					'progressbar' => esc_html__( 'Progressbar', 'wpr-addons' ),
				],
				'condition' => [
					'wpr_enable_column_slider' => 'yes',
					'wpr_enable_cs_pag' => 'yes',
				]
			]
		);

		$element->add_control(
			'wpr_cs_pag_color',
			[
				'label'  => esc_html__( 'Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#605BE5',
				'selectors' => [
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-pagination-bullet' => 'background-color: {{VALUE}}',
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-pagination-fraction' => 'color: {{VALUE}}',
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-pagination-progressbar' => 'background-color: {{VALUE}};',
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-pagination-progressbar-fill' => 'background-color: {{VALUE}};'
				],
				'condition' => [
					'wpr_enable_column_slider' => 'yes',
					'wpr_enable_cs_pag' => 'yes',
				]
			]
		);

		$element->add_control(
			'wpr_cs_pag_active_color',
			[
				'label'  => esc_html__( 'Active Bullet Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-pagination-bullet-active' => 'background-color: {{VALUE}}',
				],
				'condition' => [
					'wpr_enable_column_slider' => 'yes',
					'wpr_enable_cs_pag' => 'yes',
					'wpr_cs_pag_type' => 'bullets'
				]
			]
		);

		$element->add_control(
			'wpr_cs_pag_bg_color',
			[
				'label'  => esc_html__( 'Bar Background', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#EDEDED',
				'selectors' => [
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-pagination-progressbar' => 'background-color: {{VALUE}};'
				],
				'condition' => [
					'wpr_enable_column_slider' => 'yes',
					'wpr_enable_cs_pag' => 'yes',
					'wpr_cs_pag_type' => 'progressbar'
				]
			]
		);

		$element->add_responsive_control(
			'wpr_cs_pag_progressbar_height',
			[
				'type' => Controls_Manager::SLIDER,
				'label' => esc_html__( 'Progress Bar Height', 'wpr-addons' ),
				'size_units' => [ 'px' ],
				'range' => [
					'px' => [
						'min' => 1,
						'max' => 15,
					]
				],
				'default' => [
					'unit' => 'px',
					'size' => 5,
				],
				'selectors' => [
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-pagination-progressbar' => 'height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-pagination-progressbar-fill' => 'height: {{SIZE}}{{UNIT}};'
				],
				'condition' => [
					'wpr_enable_column_slider' => 'yes',
					'wpr_enable_cs_pag' => 'yes',
					'wpr_cs_pag_type' => 'progressbar'
				]
			]
		);
		
		$element->add_responsive_control(
			'wpr_cs_pag_size',
			[
				'type' => Controls_Manager::SLIDER,
				'label' => esc_html__( 'Box Size', 'wpr-addons' ),
				'size_units' => [ 'px' ],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 50,
					]
				],
				'default' => [
					'unit' => 'px',
					'size' => 7,
				],			
				'selectors' => [
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-pagination-bullet' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};'
				],
				'condition' => [
					'wpr_enable_column_slider' => 'yes',
					'wpr_enable_cs_pag' => 'yes',
					'wpr_cs_pag_type' => 'bullets'
				]
			]
		);
		
		$element->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			[
				'name' => 'wpr_cs_pag_fraction_typography',
				'label' => __( 'Typography', 'wpr-addons' ),
				'selector' => '{{WRAPPER}}.wpr-column-slider-yes .swiper-pagination-fraction',
				'fields_options' => [
					'typography' => [
						'default' => 'custom',
					],
					'font_size'   => [
						'default' => [
							'size' => '14',
							'unit' => 'px',
						]
					]
				],
				'condition' => [
					'wpr_enable_column_slider' => 'yes',
					'wpr_enable_cs_pag' => 'yes',
					'wpr_cs_pag_type' => 'fraction'
				]
			]
		);

		$element->add_responsive_control(
			'wpr_cs_pag_margin',
			[
				'label' => esc_html__( 'Margin', 'wpr-addons' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default' => [
					'top' => 0,
					'right' => 6,
					'bottom' => 0,
					'left' => 6,
				],
				'selectors' => [
					'{{WRAPPER}}.wpr-column-slider-yes .swiper-pagination-bullet' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition' => [
					'wpr_enable_column_slider' => 'yes',
					'wpr_enable_cs_pag' => 'yes',
					'wpr_cs_pag_type' => 'bullets'
				]
			]
		);

		$element->add_control (
			'wpr_enable_column_slider_autoplay',
			[
				'type' => Controls_Manager::SWITCHER,
				'label' => esc_html__( 'Autoplay', 'wpr-addons' ),
				'render_type' => 'template',
				'separator' => 'before',
				'condition' => [
					'wpr_enable_column_slider' => 'yes',
				]
			]
		);

		$element->add_control(
			'wpr_column_slider_delay',
			[
				'label' => __( 'Delay', 'wpr-addons' ),
				'type' => \Elementor\Controls_Manager::NUMBER,
				'default' => 1000,
				'condition' => [
					'wpr_enable_column_slider' => 'yes',
					'wpr_enable_column_slider_autoplay' => 'yes'
				]
			]
		);

		$element->add_control (
			'wpr_enable_column_slider_loop',
			[
				'type' => Controls_Manager::SWITCHER,
				'label' => esc_html__( 'Loop', 'wpr-addons' ),
				'render_type' => 'template',
				'separator' => 'before',
				'condition' => [
					'wpr_enable_column_slider' => 'yes',
				]
			]
		);

        $element->end_controls_section();

    }

    public function _before_render( $element ) {
        if ( $element->get_name() !== 'section' && $element->get_name() !== 'container' ) {
            return;
        }

		$settings = $element->get_settings_for_display();
		
		if ( 'yes' === $settings['wpr_enable_column_slider'] ) {
			if ( wp_style_is( 'e-swiper', 'registered' ) ) {
				wp_enqueue_style( 'e-swiper' );
			} elseif ( wp_style_is( 'swiper', 'registered' ) ) {
				wp_enqueue_style( 'swiper' );
			}
			
			$navigation = $settings['wpr_enable_cs_nav'];
			$pagination = $settings['wpr_enable_cs_pag'];
			$pagination_type = isset($settings['wpr_cs_pag_type']) ? $settings['wpr_cs_pag_type'] : '';
			$autoplay = $settings['wpr_enable_column_slider_autoplay'];
			$loop = $settings['wpr_enable_column_slider_loop'];
			$slides_to_show = $settings['wpr_column_slider_slides_to_show'];
			$slides_to_show_widescreen = isset($settings['wpr_column_slider_slides_to_show_widescreen']) ? $settings['wpr_column_slider_slides_to_show_widescreen'] : $slides_to_show;
			$slides_to_show_laptop = isset($settings['wpr_column_slider_slides_to_show_laptop']) ? $settings['wpr_column_slider_slides_to_show_laptop'] : $settings['wpr_column_slider_slides_to_show'];
			$slides_to_show_tablet_extra = isset($settings['wpr_column_slider_slides_to_show_tablet_extra']) ? $settings['wpr_column_slider_slides_to_show_tablet_extra'] : $slides_to_show_laptop;
			$slides_to_show_tablet = isset($settings['wpr_column_slider_slides_to_show_tablet']) ? $settings['wpr_column_slider_slides_to_show_tablet'] : $slides_to_show_tablet_extra;
			$slides_to_show_mobile_extra = isset($settings['wpr_column_slider_slides_to_show_mobile_extra']) ? $settings['wpr_column_slider_slides_to_show_mobile_extra'] : $slides_to_show_tablet;
			$slides_to_show_mobile = isset($settings['wpr_column_slider_slides_to_show_mobile']) ? $settings['wpr_column_slider_slides_to_show_mobile'] : $slides_to_show_mobile_extra;
			$space_between = $settings['wpr_column_slider_space_between'];
			$space_between_widescreen = isset($settings['wpr_column_slider_space_between_widescreen']) ? $settings['wpr_column_slider_space_between_widescreen'] : $space_between;
			$space_between_laptop = isset($settings['wpr_column_slider_space_between_laptop']) ? $settings['wpr_column_slider_space_between_laptop'] : $space_between;
			$space_between_tablet_extra = isset($settings['wpr_column_slider_space_between_tablet_extra']) ? $settings['wpr_column_slider_space_between_tablet_extra'] : $space_between_laptop;
			$space_between_tablet = isset($settings['wpr_column_slider_space_between_tablet']) ? $settings['wpr_column_slider_space_between_tablet'] : $space_between_tablet_extra;
			$space_between_mobile_extra = isset($settings['wpr_column_slider_space_between_mobile_extra']) ? $settings['wpr_column_slider_space_between_mobile_extra'] : $space_between_tablet;
			$space_between_mobile = isset($settings['wpr_column_slider_space_between_mobile']) ? $settings['wpr_column_slider_space_between_mobile'] : $space_between_mobile_extra;
			$delay = isset($settings['wpr_column_slider_delay']) ? $settings['wpr_column_slider_delay'] : '';
			$speed = $settings['wpr_column_slider_speed'];

			$column_slider_settings = array(
				'wpr_cs_navigation' => $navigation,
				'wpr_cs_pagination' => $pagination,
				'wpr_cs_pagination_type' => $pagination_type,
				'wpr_cs_autoplay' => $autoplay,
				'wpr_cs_loop' => $loop,
				'wpr_cs_slides_to_show' => $slides_to_show,
				'wpr_cs_slides_to_show_widescreen' => $slides_to_show_widescreen,
				'wpr_cs_slides_to_show_laptop' => $slides_to_show_laptop,
				'wpr_cs_slides_to_show_tablet_extra' => $slides_to_show_tablet_extra,
				'wpr_cs_slides_to_show_tablet' => $slides_to_show_tablet,
				'wpr_cs_slides_to_show_mobile_extra' => $slides_to_show_mobile_extra,
				'wpr_cs_slides_to_show_mobile' => $slides_to_show_mobile,
				'wpr_cs_space_between' => $space_between,
				'wpr_cs_space_between_widescreen' => $space_between_widescreen,
				'wpr_cs_space_between_laptop' => $space_between_laptop,
				'wpr_cs_space_between_tablet_extra' => $space_between_tablet_extra,
				'wpr_cs_space_between_tablet' => $space_between_tablet,
				'wpr_cs_space_between_mobile_extra' => $space_between_mobile_extra,
				'wpr_cs_space_between_mobile' => $space_between_mobile,
				'wpr_cs_delay' => $delay,
				'wpr_cs_speed' => $speed,
				// 'enable_on'   => $settings['wpr_enable_equal_height_on'],
			);

			if ( 'yes' === $settings['wpr_enable_cs_nav'] ) {
				echo '<div class="wpr-column-slider-navigation">';
					echo Utilities::get_wpr_icon( $settings['wpr_cs_nav_arrows'], 'left' );
					echo Utilities::get_wpr_icon( $settings['wpr_cs_nav_arrows'], 'right' );
				echo '</div>';
			}

			$element->add_render_attribute( '_wrapper', 'data-wpr-column-slider', wp_json_encode( $column_slider_settings ) );
		}
    }
    
    public function _print_template( $template, $widget ) {
		if ( $widget->get_name() !== 'section' && $widget->get_name() !== 'container' ) {
			return $template;
		}

		ob_start();

		?>
		<# if( 'yes' === settings.wpr_enable_column_slider ) {

			<!-- view.addRenderAttribute( 'wpr_column_slider', 'id', 'wpr-column-slider-' + view.getID() ); -->
			var navigation = settings.wpr_enable_cs_nav;
			var pagination = settings.wpr_enable_cs_pag;
			var pagination_type = settings.wpr_cs_pag_type ? settings.wpr_cs_pag_type : '';
			var autoplay = settings.wpr_enable_column_slider_autoplay;
			var loop = settings.wpr_enable_column_slider_loop;
			var slides_to_show = settings.wpr_column_slider_slides_to_show;
			var slides_to_show_widescreen = settings.wpr_column_slider_slides_to_show_widescreen ? settings.wpr_column_slider_slides_to_show_widescreen : slides_to_show;
			var slides_to_show_laptop = settings.wpr_column_slider_slides_to_show_laptop ? settings.wpr_column_slider_slides_to_show_laptop : slides_to_show;
			var slides_to_show_tablet_extra = settings.wpr_column_slider_slides_to_show_tablet_extra ? settings.wpr_column_slider_slides_to_show_tablet_extra : slides_to_show_laptop;
			var slides_to_show_tablet = settings.wpr_column_slider_slides_to_show_tablet ? settings.wpr_column_slider_slides_to_show_tablet : slides_to_show_tablet_extra;
			var slides_to_show_mobile_extra = settings.wpr_column_slider_slides_to_show_mobile_extra ? settings.wpr_column_slider_slides_to_show_mobile_extra : slides_to_show_tablet ;
			var slides_to_show_mobile = settings.wpr_column_slider_slides_to_show_mobile ? settings.wpr_column_slider_slides_to_show_mobile : slides_to_show_mobile_extra;
			var space_between = settings.wpr_column_slider_space_between;
			var space_between_widescreen = settings.wpr_column_slider_space_between_widescreen ? settings.wpr_column_slider_space_between_widescreen : space_between;
			var space_between_laptop = settings.wpr_column_slider_space_between_laptop ? settings.wpr_column_slider_space_between_laptop : space_between;
			var space_between_tablet_extra = settings.wpr_column_slider_space_between_tablet_extra ? settings.wpr_column_slider_space_between_tablet_extra : space_between_laptop;
			var space_between_tablet = settings.wpr_column_slider_space_between_tablet ? settings.wpr_column_slider_space_between_tablet : space_between_tablet_extra;
			var space_between_mobile_extra = settings.wpr_column_slider_space_between_mobile_extra ? settings.wpr_column_slider_space_between_mobile_extra : space_between_tablet;
			var space_between_mobile = settings.wpr_column_slider_space_between_mobile ? settings.wpr_column_slider_space_between_mobile : space_between_mobile_extra;
			var delay = settings.wpr_column_slider_delay ? settings.wpr_column_slider_delay : '';
			var speed = settings.wpr_column_slider_speed;

				columnSliderSettings = {
					'wpr_cs_navigation': navigation,
					'wpr_cs_pagination': pagination,
					'wpr_cs_pagination_type': pagination_type,
					'wpr_cs_autoplay': autoplay,
					'wpr_cs_loop': loop,
					'wpr_cs_slides_to_show': slides_to_show,
					'wpr_cs_slides_to_show_widescreen': slides_to_show_widescreen,
					'wpr_cs_slides_to_show_laptop': slides_to_show_laptop,
					'wpr_cs_slides_to_show_tablet_extra': slides_to_show_tablet_extra,
					'wpr_cs_slides_to_show_tablet': slides_to_show_tablet,
					'wpr_cs_slides_to_show_mobile_extra': slides_to_show_mobile_extra,
					'wpr_cs_slides_to_show_mobile': slides_to_show_mobile,
					'wpr_cs_space_between': space_between,
					'wpr_cs_space_between_widescreen': space_between_widescreen,
					'wpr_cs_space_between_laptop': space_between_laptop,
					'wpr_cs_space_between_tablet_extra': space_between_tablet_extra,
					'wpr_cs_space_between_tablet': space_between_tablet,
					'wpr_cs_space_between_mobile_extra': space_between_mobile_extra,
					'wpr_cs_space_between_mobile': space_between_mobile,
					'wpr_cs_delay': delay,
					'wpr_cs_speed': speed,
					<!-- 'enable_on':settings.wpr_enable_equal_height_on -->
				};

			view.addRenderAttribute( 'wpr_column_slider', {
				'class' : 'wpr-column-slider-editor',
				'id' : 'wpr-column-slider-' + view.getID(),
				'data-wpr-column-slider': JSON.stringify( columnSliderSettings )
			});

			var csNavigationArrows = settings.wpr_cs_nav_arrows;

			view.addRenderAttribute( 'wpr_navigation_arrows_left', {
				'class' : csNavigationArrows + '-left',
			});

			view.addRenderAttribute( 'wpr_navigation_arrows_right', {
				'class' : csNavigationArrows + '-right',
			});

		#>
			<div {{{ view.getRenderAttributeString( 'wpr_column_slider' ) }}}>
				<# if ( settings.wpr_enable_cs_nav === 'yes' ) { #>
				<div class='wpr-column-slider-navigation-editor'>
					<i {{{ view.getRenderAttributeString( 'wpr_navigation_arrows_left' ) }}}></i>
					<i {{{ view.getRenderAttributeString( 'wpr_navigation_arrows_right' ) }}}></i>
				</div>
				<# } #>
			</div>
		<# } #>
		<?php

		// how to render attributes without creating new div using view.addRenderAttributes
		$column_slider_content = ob_get_contents();

		ob_end_clean();

		return $template . $column_slider_content;
    }

}

new Wpr_Column_Slider();