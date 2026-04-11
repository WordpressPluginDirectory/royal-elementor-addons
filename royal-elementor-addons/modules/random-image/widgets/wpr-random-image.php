<?php
namespace WprAddons\Modules\RandomImage\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Core\Responsive\Responsive;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Group_Control_Css_Filter;
use Elementor\Group_Control_Image_Size;
use WprAddons\Classes\Utilities;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Wpr_Random_Image extends Widget_Base {
	
	public function get_name() {
		return 'wpr-random-image';
	}

	public function get_title() {
		return esc_html__( 'Random Image', 'wpr-addons' );
	}

	public function get_icon() {
		return 'wpr-icon eicon-image';
	}

	public function get_categories() {
		return [ 'wpr-widgets'];
	}

	public function get_keywords() {
		return [ 'Random Image', 'Image', 'Image', 'Generator', 'Image Generator'];
	}

    public function get_custom_help_url() {
        return 'https://royal-elementor-addons.com/contact/?ref=rea-plugin-panel-random-image-help-btn';
    }

    protected function register_controls() {
		$this->start_controls_section(
			'section_random_image',
			[
				'label' => esc_html__( 'General', 'wpr-addons' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'category',
			[
				'label' => esc_html__( 'Category', 'wpr-addons' ),
				'type' => Controls_Manager::TEXT,
				'default' => '',
				'separator' => 'before'
			]
		);

		$this->add_group_control( //TODO: find out usage
			Group_Control_Image_Size::get_type(),
			[ 
				 
				'name' => 'wpr_thumbnail', // Usage: `{name}_size` and `{name}_custom_dimension`, in this case `thumbnail_size` and `thumbnail_custom_dimension`.
				'separator' => 'none',
			]
		);

		$this->add_control(
			'enable_image_caption',
			[
				'label' => esc_html__( 'Caption', 'wpr-addons' ),
				'type' => Controls_Manager::SWITCHER,
				'separator' => 'before'
			]
		);

		$this->add_control(
			'image_caption_position',
			[
				'label' => esc_html__( 'Position', 'wpr-addons' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'normal',
				'options' => [
					'normal' => esc_html__( 'Normal', 'wpr-addons' ),
					'overlay' => esc_html__( 'Overlay', 'wpr-addons' ),
				],
				'condition' => [
					'enable_image_caption' => 'yes'
				],
				'prefix_class' => 'wpr-random-image-'
			]
		);

		$this->add_control(
			'hr_image_align',
			[
				'type' => \Elementor\Controls_Manager::DIVIDER,
			]
		);
		
		$this->add_responsive_control(
			'random_image_text_alignment',
			[
				'label' => esc_html__( 'Text Align', 'wpr-addons' ),
				'type' => Controls_Manager::CHOOSE,
				'label_block' => false,
				'default' => 'center',
				'options' => [
					'left' => [
						'title' => esc_html__( 'Start', 'wpr-addons' ),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'wpr-addons' ),
						'icon' => 'eicon-text-align-center',
					],
					'right' => [
						'title' => esc_html__( 'End', 'wpr-addons' ),
						'icon' => 'eicon-text-align-right',
					],
				],
                'selectors' => [
					'{{WRAPPER}} .wpr-random-image-gallery' => 'text-align: {{VALUE}};',
					'{{WRAPPER}} .wpr-random-image-overlay' => 'text-align: {{VALUE}};',
				],
				'condition' => [
					'enable_image_caption' => 'yes',
					'image_caption_position' => 'normal'
				]
			]
		);
		
		$this->add_responsive_control(
			'random_image_alignment',
			[
				'label' => esc_html__( 'Image Align', 'wpr-addons' ),
				'type' => Controls_Manager::CHOOSE,
				'label_block' => false,
				'default' => 'center',
				'options' => [
					'flex-start' => [
						'title' => esc_html__( 'Start', 'wpr-addons' ),
						'icon' => 'eicon-h-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'wpr-addons' ),
						'icon' => 'eicon-h-align-center',
					],
					'flex-end' => [
						'title' => esc_html__( 'End', 'wpr-addons' ),
						'icon' => 'eicon-h-align-right',
					],
				],
                'selectors' => [
					'{{WRAPPER}} .wpr-random-image-gallery' => 'display: flex; justify-content: {{VALUE}};',
					// '{{WRAPPER}} .wpr-random-image-gallery .wpr-random-image-inner-cont' => 'display: inline-block;',
				],
			]
		);

        $this->end_controls_section(); // End Controls Section

		
		$this->start_controls_section(
			'custom_random_image_section',
			[
				'label' => __( 'Image', 'wpr-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);
		
		$repeater = new \Elementor\Repeater();

		$repeater->add_control(
			'custom_category',
			[
				'label' => esc_html__( 'Category', 'wpr-addons' ),
				'type' => Controls_Manager::TEXT,
				'default' => 'Type Category Here',
				'separator' => 'before'
			]
		);
		
		$repeater->add_control(
			'random_gallery',
			[
				'label'     => __( 'Add Images', 'wpr-addons' ),
				'type'      => Controls_Manager::GALLERY,
				'dynamic'   => [
					'active' => true,
				],
			]
		);

		// $repeater->add_control(
		// 	'random_gallery_url',
		// 	[
		// 		'type' => Controls_Manager::URL,
		// 		'placeholder' => esc_html__( 'https://your-link.com', 'wpr-addons' ),
		// 		'show_label' => false,
		// 	]
		// );

		$this->add_control(
			'list',
			[
				'label' => __( 'Repeater List', 'wpr-addons' ),
				'type' => \Elementor\Controls_Manager::REPEATER,
				'fields' => $repeater->get_controls(),
				'default' => [
					[
						'custom_category' => __( 'Title #1', 'wpr-addons' ),
					],
					[
						'custom_category' => __( 'Title #2', 'wpr-addons' ),
					],
				],
				'title_field' => '{{{ custom_category }}}',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'random_image_styles_section',
			[
				'label' => __( 'Image', 'wpr-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			[
				'name' => 'css_filters',
				'selector' => '{{WRAPPER}} .wpr-random-image-gallery img',
				'separator' => 'before'
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'box_shadow',
				'selector' => '{{WRAPPER}} .wpr-random-image-gallery img'
			]
		);

		$this->add_responsive_control(
			'random_image_width',
			[
				'type' => Controls_Manager::SLIDER,
				'label' => esc_html__( 'Width', 'wpr-addons' ),
				'size_units' => [ 'px', 'vw', '%' ],
				'range' => [
					'px' => [
						'min' => 20,
						'max' => 1500,
					],
					'vh' => [
						'min' => 20,
						'max' => 100,
					],
					'%' => [
						'min' => 10,
						'max' => 100
					]
				],
				'default' => [
					'unit' => '%',
					'size' => 100,
				],
				'selectors' => [
					'{{WRAPPER}} .wpr-random-image-gallery' => 'width: {{SIZE}}{{UNIT}}; margin: auto;',
				],
				// 'separator' => 'before',
			]
		);

		$this->add_control(
			'opacity',
			[
				'label' => esc_html__( 'Opacity', 'wpr-addons' ),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 0.10,
						'max' => 1,
						'step' => 0.01,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wpr-random-image-gallery' => 'opacity: {{SIZE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'border',
				'label' => esc_html__( 'Border', 'wpr-addons' ),
				'fields_options' => [
					'border' => [
						'default' => 'solid',
					],
					'width' => [
						'default' => [
							'top' => 1,
							'right' => 1,
							'bottom' => 1,
							'left' => 1,
							'isLinked' => true,
						],
					],
					'color' => [
						'default' => '#E8E8E8',
					],
				],
				'selector' => '{{WRAPPER}} .wpr-random-image-gallery img',
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'wpr-addons' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default' => [
					'top' => 2,
					'right' => 2,
					'bottom' => 2,
					'left' => 2,
				],
				'selectors' => [
					'{{WRAPPER}} .wpr-random-image-gallery img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'random_image_caption_styles_section',
			[
				'label' => __( 'Caption', 'wpr-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
				'condition' => [
					'enable_image_caption' => 'yes'
				]
			]
		);

		$this->add_control(
			'caption_color',
			[
				'label' => __( 'Color', 'wpr-addons' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .wpr-attachment-caption span' => 'color: {{VALUE}}'
				],
			]
		);

		$this->add_control(
			'caption_bg_color',
			[
				'label' => __( 'Background Color', 'wpr-addons' ),
				'type' => \Elementor\Controls_Manager::COLOR,
				'default' => 'rgba(0, 0, 0, 0.4)',
				'selectors' => [
					'{{WRAPPER}} .wpr-attachment-caption' => 'background-color: {{VALUE}}'
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'title_typography',
				'selector' => '{{WRAPPER}} .wpr-attachment-caption',
			]
		);

		$this->add_responsive_control(
			'overlay_hegiht',
			[
				'label' => esc_html__( 'Overlay Hegiht', 'wpr-addons' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ '%', 'px' ],
				'default' => [
					'unit' => '%',
					'size' => 100,
				],
				'range' => [
					'%' => [
						'min' => 0,
						'max' => 100,
					],
					'px' => [
						'min' => 0,
						'max' => 500,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wpr-attachment-caption' => 'height: {{SIZE}}{{UNIT}}; top:calc((100% - {{SIZE}}{{UNIT}})/2); left:calc((100% - {{overlay_width.SIZE}}{{overlay_width.UNIT}})/2);',
				],
				'separator' => 'before',
				'condition' => [
					'image_caption_position' => 'overlay'
				]
			]
		);

		$this->add_control(
			'overlay_width',
			[
				'label' => esc_html__( 'Overlay Width', 'wpr-addons' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ '%', 'px' ],
				'default' => [
					'unit' => '%',
					'size' => 100,
				],
				'range' => [
					'%' => [
						'min' => 0,
						'max' => 100,
					],
					'px' => [
						'min' => 0,
						'max' => 500,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .wpr-attachment-caption' => 'width: {{SIZE}}{{UNIT}}; top: calc((100% - {{overlay_hegiht.SIZE}}{{overlay_hegiht.UNIT}})/2); left:calc((100% - {{SIZE}}{{UNIT}})/2);',
				],
				'condition' => [
					'image_caption_position' => 'overlay'
				]
			]
		);

		$this->add_responsive_control(
			'caption_padding',
			[
				'label' => esc_html__( 'Padding', 'wpr-addons' ),
				'type' => Controls_Manager::DIMENSIONS,
				'default' => [
					'top' => 0,
					'right' => 0,
					'bottom' => 0,
					'left' => 0,
				],
				'size_units' => [ 'px', '%' ],
				'selectors' => [
					'{{WRAPPER}} .wpr-attachment-caption' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);


		$this->end_controls_section();
    }

	public function tryCategoriesList() {

	}

	protected function render() {
        $settings = $this->get_settings_for_display();

			$categorized_images_array = [];
			$categorized_gallery_images_array = '';
			$uncategorized_gallery_images_array = [];
			$category_name_for_gallery = '';
			$category_title = '';

			if ( $settings['list'] ) {

				foreach ( $settings['list'] as $item ) {
					
					if ( $settings['category'] === $item['custom_category'] ) {
						$categorized_gallery_images_array = $item['random_gallery'];
						$category_name_for_gallery = $item['custom_category'];
						$category_title = $item['custom_category'];
					}

					if ( array_key_exists($item['custom_category'], $categorized_images_array) ) {
						$categorized_images_array[$item['custom_category']] = $categorized_images_array[$item['custom_category']];
					} else {
						$categorized_images_array[$item['custom_category']] = [];
					}

					if ( '' === $settings['category'] ) {
						$uncategorized_gallery_images_array = array_merge($uncategorized_gallery_images_array, $item['random_gallery']);
					}
				}
				
			}
					
			echo '<div class="wpr-random-image-gallery">';

				if( '' === $settings['category'] ) {
					
					$random_index = 1 < count($uncategorized_gallery_images_array) ? wp_rand(0, count($uncategorized_gallery_images_array) - 1) : '';

					

					$thumbnail_size = $settings['wpr_thumbnail_size'];
					$thumbnail_custom_dimension = $settings['wpr_thumbnail_custom_dimension'];

					$image_caption = 'yes' === $settings['enable_image_caption'] ? '<p class="wpr-attachment-caption"><span style="vertical-align: middle; text-align: center;">' . wp_get_attachment_caption($uncategorized_gallery_images_array[$random_index]['id']) . '</span></p>' : '';

					if ( 'custom' === $settings['wpr_thumbnail_size'] ) {
						$custom_size = array ( $thumbnail_custom_dimension['width'],$thumbnail_custom_dimension['height']);
						$image = wp_get_attachment_image($uncategorized_gallery_images_array[$random_index]['id'], $custom_size , true);
					} else {
						$image = !empty($uncategorized_gallery_images_array) ? wp_get_attachment_image($uncategorized_gallery_images_array[$random_index]['id'], 
						$thumbnail_size , true) : '';
					}

					echo '<div class="wpr-random-image-inner-cont" style="position: relative;">';
						echo $image; 
						echo $image_caption;
					echo '</div>';

				} else {


					if ( $category_name_for_gallery === $settings['category'] ) {

						$random_index = 1 < count($categorized_gallery_images_array) ? wp_rand(0, count($categorized_gallery_images_array) - 1) : 0;

						$thumbnail_size = $settings['wpr_thumbnail_size'];
						$thumbnail_custom_dimension = $settings['wpr_thumbnail_custom_dimension'];

						$image_caption = 'yes' === $settings['enable_image_caption'] ? '<p class="wpr-attachment-caption"><span style="vertical-align: middle; text-align: center;">' . wp_get_attachment_caption($categorized_gallery_images_array[$random_index]['id']) . '</span></p>' : '';

						if ( 'custom' === $settings['wpr_thumbnail_size'] ) {
							$custom_size = array( $thumbnail_custom_dimension['width'],$thumbnail_custom_dimension['height'] );
							$image = wp_get_attachment_image( $categorized_gallery_images_array[$random_index]['id'], $custom_size , true );
						} else {
							$image = wp_get_attachment_image( $categorized_gallery_images_array[$random_index]['id'], 
							$thumbnail_size , true );
						}

						echo '<div class="wpr-random-image-inner-cont" style="position: relative;">';
							echo $image;
							echo $image_caption;
						echo '</div>';

					} else {
						echo '<div>No Images In This Category...</div>';
					}

				}
			echo '</div>';
    }
}