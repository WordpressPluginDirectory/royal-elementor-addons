<?php
use Elementor\Controls_Manager;
use WprAddons\Classes\Utilities;
use Elementor\Group_Control_Typography;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Wpr_Column_Slider extends Wpr_Extensions_Base {
	public function __construct() {
		add_action( 'elementor/element/section/section_advanced/after_section_end', [ $this, 'register_controls' ], 10 );
		add_action( 'elementor/section/print_template', array( $this, '_print_template' ), 10, 2 );
		add_action( 'elementor/frontend/section/before_render', array( $this, '_before_render' ), 10, 1 );

		// FLEXBOX CONTAINER
		add_action( 'elementor/element/container/section_layout/after_section_end', [ $this, 'register_controls' ], 10 );
		add_action( 'elementor/container/print_template', array( $this, '_print_template' ), 10, 2 );
		add_action( 'elementor/frontend/container/before_render', array( $this, '_before_render' ), 10, 1 );

	}

	private function get_slides_to_show_value( $value, $fallback = 1 ) {
		$value = absint( $value );
		$fallback = max( 1, absint( $fallback ) );

		if ( $value < 1 ) {
			$value = $fallback;
		}

		if ( ! $this->has_active_pro_license() ) {
			$value = min( $value, 2 );
		}

		return $value;
	}

	private function get_slides_to_scroll_value( $value, $fallback = 1 ) {
		$value = absint( $value );
		$fallback = max( 1, absint( $fallback ) );

		if ( $value < 1 ) {
			$value = $fallback;
		}

		if ( ! $this->has_active_pro_license() ) {
			$value = min( $value, 2 );
		}

		return $value;
	}

	private function add_control_slides_to_show( $element ) {
		if ( ! $this->maybe_call_pro_method( '\WprAddonsPro\Extensions\Wpr_Column_Slider_Pro', 'add_control_slides_to_show', [ $element ] ) ) {
			$element->add_responsive_control(
				'wpr_column_slider_slides_to_show',
				[
					'label' => esc_html__( 'Slides To Show', 'wpr-addons' ),
					'description' => esc_html__( 'Number of slides visible at once. Free supports up to 2 slides per view.', 'wpr-addons' ),
					'type' => Controls_Manager::NUMBER,
					'default' => 1,
					'min' => 1,
					'max' => 2,
					'condition' => [
						'wpr_enable_column_slider' => 'yes',
					],
				]
			);

			$element->add_control(
				'wpr_column_slider_slides_to_show_pro_notice',
				[
					'type' => Controls_Manager::RAW_HTML,
					'raw' => 'More than 2 slides per view are available<br> in the <strong><a href="https://royal-elementor-addons.com/?ref=rea-plugin-panel-column-slider-upgrade-pro#purchasepro" target="_blank">Pro version</a></strong>',
					'content_classes' => 'wpr-pro-notice',
					'condition' => [
						'wpr_enable_column_slider' => 'yes',
					],
				]
			);
		}
	}

	private function add_control_autoplay( $element ) {
		if ( ! $this->maybe_call_pro_method( '\WprAddonsPro\Extensions\Wpr_Column_Slider_Pro', 'add_control_autoplay', [ $element ] ) ) {
			$element->add_control(
				'wpr_enable_column_slider_autoplay',
				[
					'type' => Controls_Manager::SWITCHER,
					'label' => sprintf( esc_html__( 'Autoplay %s', 'wpr-addons' ), '<i class="eicon-pro-icon"></i>' ),
					'separator' => 'before',
					'classes' => 'wpr-pro-control',
					'condition' => [
						'wpr_enable_column_slider' => 'yes',
					],
				]
			);
		}

		$this->maybe_call_pro_method( '\WprAddonsPro\Extensions\Wpr_Column_Slider_Pro', 'add_control_autoplay_delay', [ $element ] );
	}

	private function add_control_slides_to_scroll( $element ) {
		if ( ! $this->maybe_call_pro_method( '\WprAddonsPro\Extensions\Wpr_Column_Slider_Pro', 'add_control_slides_to_scroll', [ $element ] ) ) {
			$element->add_control(
				'wpr_column_slider_slides_to_scroll',
				[
					'label' => sprintf( esc_html__( 'Slides To Scroll %s', 'wpr-addons' ), '<i class="eicon-pro-icon"></i>' ),
					'type' => Controls_Manager::NUMBER,
					'default' => 1,
					'min' => 1,
					'max' => 2,
					'classes' => 'wpr-pro-control',
					'condition' => [
						'wpr_enable_column_slider' => 'yes',
					],
				]
			);
		}
	}

	private function add_control_pagination_type( $element ) {
		if ( ! $this->maybe_call_pro_method( '\WprAddonsPro\Extensions\Wpr_Column_Slider_Pro', 'add_control_pagination_type', [ $element ] ) ) {
			$element->add_control(
				'wpr_cs_pag_type',
				[
					'label' => esc_html__( 'Pagination Type', 'wpr-addons' ),
					'type' => Controls_Manager::SELECT,
					'default' => 'fraction',
					'options' => [
						'fraction' => esc_html__( 'Fraction', 'wpr-addons' ),
						'pro-bullets' => esc_html__( 'Bullets (Pro)', 'wpr-addons' ),
						'pro-progressbar' => esc_html__( 'Progressbar (Pro)', 'wpr-addons' ),
					],
					'condition' => [
						'wpr_enable_column_slider' => 'yes',
						'wpr_enable_cs_pag' => 'yes',
					],
				]
			);

			Utilities::upgrade_pro_notice(
				$element,
				Controls_Manager::RAW_HTML,
				'column-slider',
				'wpr_cs_pag_type',
				[ 'pro-bullets', 'pro-progressbar' ]
			);
		}
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

		$this->add_control_slides_to_show( $element );

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

		$this->add_control_slides_to_scroll( $element );

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

		$this->add_control_pagination_type( $element );

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

		$this->add_control_autoplay( $element );

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
			$autoplay = isset( $settings['wpr_enable_column_slider_autoplay'] ) ? $settings['wpr_enable_column_slider_autoplay'] : '';
			$loop = $settings['wpr_enable_column_slider_loop'];
			$slides_to_show = $this->get_slides_to_show_value( $settings['wpr_column_slider_slides_to_show'] );
			$slides_to_show_widescreen = $this->get_slides_to_show_value( isset( $settings['wpr_column_slider_slides_to_show_widescreen'] ) ? $settings['wpr_column_slider_slides_to_show_widescreen'] : $slides_to_show, $slides_to_show );
			$slides_to_show_laptop = $this->get_slides_to_show_value( isset( $settings['wpr_column_slider_slides_to_show_laptop'] ) ? $settings['wpr_column_slider_slides_to_show_laptop'] : $slides_to_show, $slides_to_show );
			$slides_to_show_tablet_extra = $this->get_slides_to_show_value( isset( $settings['wpr_column_slider_slides_to_show_tablet_extra'] ) ? $settings['wpr_column_slider_slides_to_show_tablet_extra'] : $slides_to_show_laptop, $slides_to_show_laptop );
			$slides_to_show_tablet = $this->get_slides_to_show_value( isset( $settings['wpr_column_slider_slides_to_show_tablet'] ) ? $settings['wpr_column_slider_slides_to_show_tablet'] : $slides_to_show_tablet_extra, $slides_to_show_tablet_extra );
			$slides_to_show_mobile_extra = $this->get_slides_to_show_value( isset( $settings['wpr_column_slider_slides_to_show_mobile_extra'] ) ? $settings['wpr_column_slider_slides_to_show_mobile_extra'] : $slides_to_show_tablet, $slides_to_show_tablet );
			$slides_to_show_mobile = $this->get_slides_to_show_value( isset( $settings['wpr_column_slider_slides_to_show_mobile'] ) ? $settings['wpr_column_slider_slides_to_show_mobile'] : $slides_to_show_mobile_extra, $slides_to_show_mobile_extra );
			$slides_to_scroll = $this->get_slides_to_scroll_value( isset( $settings['wpr_column_slider_slides_to_scroll'] ) ? $settings['wpr_column_slider_slides_to_scroll'] : 1 );
			$space_between = $settings['wpr_column_slider_space_between'];
			$space_between_widescreen = isset($settings['wpr_column_slider_space_between_widescreen']) ? $settings['wpr_column_slider_space_between_widescreen'] : $space_between;
			$space_between_laptop = isset($settings['wpr_column_slider_space_between_laptop']) ? $settings['wpr_column_slider_space_between_laptop'] : $space_between;
			$space_between_tablet_extra = isset($settings['wpr_column_slider_space_between_tablet_extra']) ? $settings['wpr_column_slider_space_between_tablet_extra'] : $space_between_laptop;
			$space_between_tablet = isset($settings['wpr_column_slider_space_between_tablet']) ? $settings['wpr_column_slider_space_between_tablet'] : $space_between_tablet_extra;
			$space_between_mobile_extra = isset($settings['wpr_column_slider_space_between_mobile_extra']) ? $settings['wpr_column_slider_space_between_mobile_extra'] : $space_between_tablet;
			$space_between_mobile = isset($settings['wpr_column_slider_space_between_mobile']) ? $settings['wpr_column_slider_space_between_mobile'] : $space_between_mobile_extra;
			$delay = isset($settings['wpr_column_slider_delay']) ? $settings['wpr_column_slider_delay'] : '';
			$speed = $settings['wpr_column_slider_speed'];

			if ( ! $this->has_active_pro_license() ) {
				$autoplay = '';
				$delay = '';

				if ( in_array( $pagination_type, [ 'bullets', 'progressbar', 'pro-bullets', 'pro-progressbar' ], true ) ) {
					$pagination_type = 'fraction';
				}
			}

			$column_slider_settings = [
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
				'wpr_cs_slides_to_scroll' => $slides_to_scroll,
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
			];

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
			var hasProColumnSlider = <?php echo $this->has_active_pro_license() ? 'true' : 'false'; ?>;
			var getSlidesToShowValue = function( value, fallback ) {
				var parsedValue = parseInt( value, 10 );
				var parsedFallback = parseInt( fallback, 10 );

				if ( isNaN( parsedFallback ) || parsedFallback < 1 ) {
					parsedFallback = 1;
				}

				if ( isNaN( parsedValue ) || parsedValue < 1 ) {
					parsedValue = parsedFallback;
				}

				if ( ! hasProColumnSlider ) {
					parsedValue = Math.min( parsedValue, 2 );
				}

				return parsedValue;
			};

			<!-- view.addRenderAttribute( 'wpr_column_slider', 'id', 'wpr-column-slider-' + view.getID() ); -->
			var navigation = settings.wpr_enable_cs_nav;
			var pagination = settings.wpr_enable_cs_pag;
			var pagination_type = settings.wpr_cs_pag_type ? settings.wpr_cs_pag_type : '';
			var autoplay = hasProColumnSlider ? settings.wpr_enable_column_slider_autoplay : '';
			var loop = settings.wpr_enable_column_slider_loop;
			var slides_to_show = getSlidesToShowValue( settings.wpr_column_slider_slides_to_show, 1 );
			var slides_to_show_widescreen = getSlidesToShowValue( settings.wpr_column_slider_slides_to_show_widescreen, slides_to_show );
			var slides_to_show_laptop = getSlidesToShowValue( settings.wpr_column_slider_slides_to_show_laptop, slides_to_show );
			var slides_to_show_tablet_extra = getSlidesToShowValue( settings.wpr_column_slider_slides_to_show_tablet_extra, slides_to_show_laptop );
			var slides_to_show_tablet = getSlidesToShowValue( settings.wpr_column_slider_slides_to_show_tablet, slides_to_show_tablet_extra );
			var slides_to_show_mobile_extra = getSlidesToShowValue( settings.wpr_column_slider_slides_to_show_mobile_extra, slides_to_show_tablet );
			var slides_to_show_mobile = getSlidesToShowValue( settings.wpr_column_slider_slides_to_show_mobile, slides_to_show_mobile_extra );
			var slides_to_scroll = getSlidesToShowValue( settings.wpr_column_slider_slides_to_scroll, 1 );
			var space_between = settings.wpr_column_slider_space_between;
			var space_between_widescreen = settings.wpr_column_slider_space_between_widescreen ? settings.wpr_column_slider_space_between_widescreen : space_between;
			var space_between_laptop = settings.wpr_column_slider_space_between_laptop ? settings.wpr_column_slider_space_between_laptop : space_between;
			var space_between_tablet_extra = settings.wpr_column_slider_space_between_tablet_extra ? settings.wpr_column_slider_space_between_tablet_extra : space_between_laptop;
			var space_between_tablet = settings.wpr_column_slider_space_between_tablet ? settings.wpr_column_slider_space_between_tablet : space_between_tablet_extra;
			var space_between_mobile_extra = settings.wpr_column_slider_space_between_mobile_extra ? settings.wpr_column_slider_space_between_mobile_extra : space_between_tablet;
			var space_between_mobile = settings.wpr_column_slider_space_between_mobile ? settings.wpr_column_slider_space_between_mobile : space_between_mobile_extra;
			var delay = hasProColumnSlider && settings.wpr_column_slider_delay ? settings.wpr_column_slider_delay : '';
			var speed = settings.wpr_column_slider_speed;

			if ( ! hasProColumnSlider && ( pagination_type === 'bullets' || pagination_type === 'progressbar' || pagination_type === 'pro-bullets' || pagination_type === 'pro-progressbar' ) ) {
				pagination_type = 'fraction';
			}

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
					'wpr_cs_slides_to_scroll': slides_to_scroll,
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