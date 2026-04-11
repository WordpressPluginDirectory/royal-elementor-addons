<?php
namespace WprAddons\Modules\Separator\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Core\Responsive\Responsive;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Group_Control_Image_Size;
use WprAddons\Classes\Utilities;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Wpr_Separator extends Widget_Base {

    public function get_name() {
        return 'wpr-separator';
    }

    public function get_title() {
        return esc_html__( 'Separator', 'wpr-addons' );
    }

    public function get_icon() {
        return 'wpr-icon eicon-divider';
    }

    public function get_categories() {
        return [ 'wpr-widgets'];
    }

    public function get_keywords() {
        return [ 'royal', 'separator', 'divider', 'line' ];
    }

	public function has_widget_inner_wrapper(): bool {
		return ! \Elementor\Plugin::$instance->experiments->is_feature_active( 'e_optimized_markup' );
	}

	public function get_script_depends() {
		return [ 'wpr-lottie-animations' ];
	}

    public function get_custom_help_url() {
        if ( empty(get_option('wpr_wl_plugin_links')) )
        // return 'https://royal-elementor-addons.com/contact/?ref=rea-plugin-panel-grid-help-btn';
            return 'https://wordpress.org/support/plugin/royal-elementor-addons/';
    }

    protected function register_controls() {

        // Tab: Content ==============
        // Section: General ------------
        $this->start_controls_section(
                'section_general',
                [
                    'label' => esc_html__( 'General', 'wpr-addons' ),
                    'tab' => Controls_Manager::TAB_CONTENT,
                ]
        );

        // Utilities::wpr_library_buttons( $this, Controls_Manager::RAW_HTML );
        
		$this->add_control(
			'number_of_lines',
			[
				'label' => esc_html__( 'Number of Lines', 'wpr-addons' ),
				'type' => Controls_Manager::SELECT,
				'default' => 1,
				'options' => [
					1 => esc_html__( 'One', 'wpr-addons' ),
					2 => esc_html__( 'Two', 'wpr-addons' ),
					3 => esc_html__( 'Three', 'wpr-addons' ),
					4 => esc_html__( 'Four', 'wpr-addons' ),
					5 => esc_html__( 'Five', 'wpr-addons' ),
				],
				'render_type' => 'template',
			]
		);
        
		$this->add_control(
			'style_of_lines',
			[
				'label' => esc_html__( 'Style of Lines', 'wpr-addons' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'solid',
				'options' => [
					'solid' => esc_html__( 'Solid', 'wpr-addons' ),
					'dotted' => esc_html__( 'Dotted', 'wpr-addons' ),
					'dashed' => esc_html__( 'Dashed', 'wpr-addons' ),
					'double' => esc_html__( 'Double', 'wpr-addons' ),
					'groove' => esc_html__( 'Groove', 'wpr-addons' ),
				],
                'selectors' => [
                    '{{WRAPPER}} .wpr-separator-wrap hr' => 'border-top-style: {{VALUE}}'
                ]
			]
		);
        
		$this->add_control(
			'separator_content_type',
			[
				'label' => esc_html__( 'Content Type', 'wpr-addons' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'icon',
				'options' => [
                    'none' => esc_html__('None', 'wpr-addons'),
					'icon' => esc_html__( 'Icon', 'wpr-addons' ),
					'image' => esc_html__( 'Image', 'wpr-addons' ),
					'text' => esc_html__( 'Text', 'wpr-addons' ),
					// 'animation' => esc_html__( 'Lottie Animation', 'wpr-addons' ),
				],
				'render_type' => 'template',
				'separator' => 'before',
			]
		);

		$this->add_control(
			'separator_icon',
			[
				'label' => esc_html__( 'Select Icon', 'wpr-addons' ),
				'type' => Controls_Manager::ICONS,
				'skin' => 'inline',
				'label_block' => false,
				'default' => [
					'value' => 'fas fa-star',
					'library' => 'fa-solid',
				],
				'condition' => [
					'separator_content_type' => 'icon'
				]
			]
		);

        $this->add_control(
			'separator_image',
			[
				'label' => __( 'Choose Image', 'wpr-addons' ),
				'type' => Controls_Manager::MEDIA,
				'default' => [
					'url' => WPR_ADDONS_ASSETS_URL . 'img/logo-40x40.png',
				],
				'condition' => [
					'separator_content_type' => 'image'
				]
			]
		);

		$this->add_control(
			'separator_text',
			[
				'label' => esc_html__( 'Text', 'wpr-addons' ),
				'type' => Controls_Manager::TEXT,
				'default' => 'SEPARATOR',
				'condition' => [
                    'separator_content_type' => 'text'
				]
			]
		);

		$this->add_control(
			'separator_animation_source',
			[
				'label'   => esc_html__( 'Animation Source', 'wpr-addons' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'url'  => esc_html__( 'External URL', 'wpr-addons' ),
					'file' => esc_html__( 'Media File', 'wpr-addons' ),
				],
				'default' => 'url',
				'condition' => [
					'separator_content_type' => 'animation'
				]
			]
		);

		$this->add_control(
			'separator_animation_json_url',
			[
				'label'       => esc_html__( 'Animation JSON URL', 'wpr-addons' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'https://assets3.lottiefiles.com/packages/lf20_ghs9bkkc.json',
				'label_block' => true,
				'condition'   => [
					'separator_content_type' => 'animation',
					'separator_animation_source' => 'url',
				],
			]
		);

		$this->add_control(
			'separator_animation_json_file',
			[
				'label'      => esc_html__( 'Upload JSON File', 'wpr-addons' ),
				'type'       => Controls_Manager::MEDIA,
				'media_type' => 'application/json',
				'condition'  => [
					'separator_content_type' => 'animation',
					'separator_animation_source' => 'file',
				]
			]
		);

		$this->add_control(
			'separator_animation_loop',
			[
				'label' => esc_html__( 'Loop', 'wpr-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => 'yes',
				'return_value' => 'yes',
				'condition' => [
					'separator_content_type' => 'animation'
				]
			]
		);

		$this->add_control(
			'separator_animation_autoplay',
			[
				'label' => esc_html__( 'Autoplay', 'wpr-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => 'yes',
				'return_value' => 'yes',
				'condition' => [
					'separator_content_type' => 'animation'
				]
			]
		);

		$this->add_control(
			'separator_animation_renderer',
			[
				'label' => esc_html__( 'Renderer', 'wpr-addons' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'svg' => esc_html__( 'SVG', 'wpr-addons' ),
					'canvas' => esc_html__( 'Canvas', 'wpr-addons' ),
					'html' => esc_html__( 'HTML', 'wpr-addons' ),
				],
				'default' => 'svg',
				'condition' => [
					'separator_content_type' => 'animation'
				]
			]
		);

		$this->add_control(
			'enable_separator_link',
			[
				'label' => esc_html__( 'Enable Link', 'wpr-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'label_block' => false,
				'separator' => 'before'
			]
		);

		$this->add_control(
			'separator_link',
			[
				'label' => esc_html__( 'Link', 'wpr-addons' ),
				'type' => Controls_Manager::URL,
				'placeholder' => esc_html__( 'https://your-link.com', 'plugin-name' ),
				'default' => [
					'url' => '',
					'is_external' => true,
					'nofollow' => true,
					'custom_attributes' => '',
				],
				'label_block' => true,
				'condition' => [
					'enable_separator_link' => 'yes'
				]
			]
		);

		$this->add_control(
			'pagination_align',
			[
				'label' => esc_html__( 'Alignment', 'wpr-addons' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'left'    => [
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
					]
				],
				'default' => 'center',
				'separator' => 'before',
				'selectors_dictionary' => [
					'left' => 'flex-start',
					'center' => 'center',
					'right' => 'flex-end',
				],
				'selectors' => [
					'{{WRAPPER}} .wpr-separator-outer-wrap' => 'display: flex; justify-content: {{VALUE}};'
				]
			]
		);


        $this->end_controls_section();

        // Tab: Style ==============
        // Section: Line ------------
        $this->start_controls_section(
                'section_line_style',
                [
                    'label' => esc_html__( 'Line', 'wpr-addons' ),
                    'tab' => Controls_Manager::TAB_STYLE
                ]
        );

		$this->add_control(
			'separator_line_color',
			[
				'label'  => esc_html__( 'Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#605BE5',
				'selectors' => [
					'{{WRAPPER}} .wpr-separator-wrap hr' => 'border-top-color: {{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'separators_height',
			[
				'type' => Controls_Manager::SLIDER,
				'label' => esc_html__( 'Height', 'wpr-addons' ),
				'size_units' => [ 'px'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 30,
					]
				],
				'default' => [
					'unit' => 'px',
					'size' => 1,
				],
				'selectors' => [
					'{{WRAPPER}} .line-before hr' => 'border-top-width: {{SIZE}}{{UNIT}}; transform: translateY(50% - {{SIZE}}{{UNIT}});',
					'{{WRAPPER}} .line-after hr' => 'border-top-width: {{SIZE}}{{UNIT}}; transform: translateY(50% - {{SIZE}}{{UNIT}});',
				],
			]
		);

		$this->add_responsive_control(
			'separators_width',
			[
				'type' => Controls_Manager::SLIDER,
				'label' => esc_html__( 'Width', 'wpr-addons' ),
				'size_units' => [ 'px', '%'],
				'range' => [
					'px' => [
						'min' => 50,
						'max' => 1000,
					],
					'%' => [
						'min' => 5,
						'max' => 100,
					]
				],
				'default' => [
					'unit' => '%',
					'size' => 100,
				],
				'selectors' => [
					'{{WRAPPER}} .wpr-separator-wrap' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'separators_distance',
			[
				'type' => Controls_Manager::SLIDER,
				'label' => esc_html__( 'Horizontal Distance', 'wpr-addons' ),
				'size_units' => [ 'px'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 50,
					]
				],
				'default' => [
					'unit' => 'px',
					'size' => 3,
				],
				'selectors' => [
					'{{WRAPPER}} .line-before' => 'margin-right: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .line-after' => 'margin-left: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'separators_distance_vr',
			[
				'type' => Controls_Manager::SLIDER,
				'label' => esc_html__( 'Vertical Distance', 'wpr-addons' ),
				'size_units' => [ 'px'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 50,
					]
				],
				'default' => [
					'unit' => 'px',
					'size' => 3,
				],
				'selectors' => [
					'{{WRAPPER}} .line-before hr:not(:last-child)' => 'margin-bottom: {{SIZE}}{{UNIT}}',
					'{{WRAPPER}} .line-after hr:not(:last-child)' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				],
				'condition' => [
					'number_of_lines!' => 1
				]
			]
		);

        $this->end_controls_section();

        // Tab: Style ==============
        // Section: Title ------------
        $this->start_controls_section(
                'section_title_style',
                [
                    'label' => esc_html__( 'Title', 'wpr-addons' ),
                    'tab' => Controls_Manager::TAB_STYLE,
                    'condition' => [
                        'separator_content_type' => 'text'
                    ]
                ]
        );

		$this->add_control(
			'separator_text_color',
			[
				'label'  => esc_html__( 'Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#605BE5',
				'selectors' => [
					'{{WRAPPER}} .wpr-separator-content p' => 'color: {{VALUE}}',
				],
			]
		);
		
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'pagination_content_typography',
				'label' => __( 'Typography', 'wpr-addons' ),
				'selector' => '{{WRAPPER}} .wpr-separator-content p',
				'fields_options' => [
					'typography' => [
						'default' => 'custom',
					],
					'font_weight' => [
						'default' => '400',
					],
					'font_family' => [
						'default' => 'Roboto',
					],
					'font_size'   => [
						'default' => [
							'size' => '14',
							'unit' => 'px',
						]
					]
				]
			]
		);

        $this->end_controls_section();

        // Tab: Style ==============
        // Section: Image ------------
        $this->start_controls_section(
                'section_image_style',
                [
                    'label' => esc_html__( 'Image', 'wpr-addons' ),
                    'tab' => Controls_Manager::TAB_STYLE,
                    'condition' => [
                        'separator_content_type' => 'image'
                    ]
                ]
        );

		$this->add_control(
			'separator_image_br_color',
			[
				'label'  => esc_html__( 'Border Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#000',
				'selectors' => [
					'{{WRAPPER}} .wpr-separator-content img' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			[
				'name' => 'separator_image_size',
				'default' => 'full',
				'separator' => 'before'
			]
		);
		
		$this->add_responsive_control(
			'image_size',
			[
				'type' => Controls_Manager::SLIDER,
				'label' => esc_html__( 'Width', 'wpr-addons' ),
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
					'{{WRAPPER}} .wpr-separator-content img' => 'width: {{SIZE}}{{UNIT}}; height: auto;',
				]
			]
		);

		$this->add_control(
			'border',
			[
				'label' => esc_html__( 'Border Type', 'wpr-addons' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'none' => esc_html__( 'None', 'wpr-addons' ),
					'solid' => esc_html__( 'Solid', 'wpr-addons' ),
					'double' => esc_html__( 'Double', 'wpr-addons' ),
					'dotted' => esc_html__( 'Dotted', 'wpr-addons' ),
					'dashed' => esc_html__( 'Dashed', 'wpr-addons' ),
					'groove' => esc_html__( 'Groove', 'wpr-addons' )
				],
				'default' => 'none',
				'selectors' => [
					'{{WRAPPER}} .wpr-separator-content img' => 'border-style: {{VALUE}};',
				],
				'separator' => 'before'
			]
		);
		
		$this->add_control(
			'img_border_width',
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
					'{{WRAPPER}} .wpr-separator-content img' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',	
				],
				'condition' => [
					'border!' => 'none'
				]
			]
		);
		
		$this->add_control(
			'img_border_radius',
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
					'{{WRAPPER}} .wpr-separator-content img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',	
				]
			]
		);

        $this->end_controls_section();

        // Tab: Style ==============
        // Section: Icon ------------
        $this->start_controls_section(
                'section_icon_style',
                [
                    'label' => esc_html__( 'Icon', 'wpr-addons' ),
                    'tab' => Controls_Manager::TAB_STYLE,
                    'condition' => [
                        'separator_content_type' => 'icon'
                    ]
                ]
        );

		$this->add_control(
			'separator_icon_color',
			[
				'label'  => esc_html__( 'Color', 'wpr-addons' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#605BE5',
				'selectors' => [
					'{{WRAPPER}} .wpr-separator-wrap i' => 'color: {{VALUE}}',
					'{{WRAPPER}} .wpr-separator-wrap svg' => 'fill: {{VALUE}}',
				],
			]
		);
		
		$this->add_responsive_control(
			'icon_size',
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
					'{{WRAPPER}} .wpr-separator-wrap i' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .wpr-separator-wrap svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
				'separator' => 'before'
			]
		);

        $this->end_controls_section();
    }

	public function separator_lottie_attributes( $settings ) {
		$attributes = [
			'loop' => isset( $settings['separator_animation_loop'] ) ? $settings['separator_animation_loop'] : 'yes',
			'autoplay' => isset( $settings['separator_animation_autoplay'] ) ? $settings['separator_animation_autoplay'] : 'yes',
			'lottie_renderer' => isset( $settings['separator_animation_renderer'] ) ? $settings['separator_animation_renderer'] : 'svg',
		];

		return wp_json_encode( $attributes );
	}

    public function render_separator_content($settings) {
        switch ($settings['separator_content_type']) {
            case 'image':		
              // Defaults
              if ( '' !== $settings['separator_image']['url'] ) {
                $image_src = $settings['separator_image']['url'];
              } else {
                $image_src = Group_Control_Image_Size::get_attachment_image_src( $settings['separator_image']['id'], 'separator_image_size', $settings );	
              }
              echo '<div class="wpr-separator-content">';
                echo '<img src="'. esc_url( $image_src ) .'">';
              echo '</div>';
              break;
            case 'text':
              echo '<div class="wpr-separator-content">';
                echo '<p>'. $settings['separator_text'] .'</p>';
              echo '</div>';
              break;
            case 'animation':
			  $separator_lottie_json = 'url' === $settings['separator_animation_source']
				? esc_url( $settings['separator_animation_json_url'] )
				: ( ! empty( $settings['separator_animation_json_file']['url'] ) ? esc_url( $settings['separator_animation_json_file']['url'] ) : '' );

			  if ( '' === $separator_lottie_json ) {
				$separator_lottie_json = WPR_ADDONS_URL .'modules/lottie-animations/assets/default.json';
			  }

              echo '<div class="wpr-separator-content">';
				echo '<div class="wpr-separator-animations" data-settings="'. esc_attr( $this->separator_lottie_attributes( $settings ) ) .'" data-json-url="'. esc_url( $separator_lottie_json ) .'"></div>';
              echo '</div>';
              break;
            case 'none';
              echo '';
              break;

            default:
                \Elementor\Icons_Manager::render_icon( $settings['separator_icon'], [ 'aria-hidden' => 'true' ] );
          }
    }

    protected function render() {
        // Get Settings
        $settings = $this->get_settings_for_display();
        extract($settings);

		if ( 'yes' === $enable_separator_link ) : 
		$this->add_link_attributes( 'separator_link', $separator_link );
		echo '<a class="wpr-separator-outer-wrap" '. $this->get_render_attribute_string( 'separator_link' ) .'>';
		else :
		echo '<div class="wpr-separator-outer-wrap">';
		endif;
		?>
        <div class="wpr-separator-wrap">
            <div class="line-before">
                <?php for ($counter = 0; $counter < intval($number_of_lines); $counter++ ) : ?>
                    <hr>
                <?php endfor; ?>
            </div>

            <?php echo $this->render_separator_content($settings) ?>

            <div class="line-after">
                <?php for ($counter = 0; $counter < intval($number_of_lines); $counter++ ) : ?>
                    <hr>
                <?php endfor; ?>
            </div>
        </div>

        <?php
		if ( 'yes' === $enable_separator_link ) :
		echo '</a>';
		else:
		echo '</div>';
		endif ;
    }
}