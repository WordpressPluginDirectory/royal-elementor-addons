<?php
use Elementor\Controls_Manager;
use WprAddons\Classes\Utilities;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Wpr_Equal_Height {
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
			'wpr_section_equal_height',
			[
				'tab'   => Controls_Manager::TAB_ADVANCED,
                'label' =>  sprintf(esc_html__('Equal Height - %s', 'wpr-addons'), esc_html('RA')),
            ]
		);

        $element->add_control(
            'wpr_section_equal_height_update',
            [
                'type' => Controls_Manager::RAW_HTML,
                'raw' => '<div class="elementor-update-preview editor-wpr-preview-update"><span>Update changes to Preview</span><button class="elementor-button elementor-button-success" onclick="elementor.reloadPreview();">Apply</button>',
                'separator' => 'after'
            ]
        );

		$element->add_control (
			'wpr_enable_equal_height',
			[
				'type' => Controls_Manager::SWITCHER,
				'label' => esc_html__( 'Enable Equal Height', 'wpr-addons' ),
				'description' => esc_html__( 'Makes all selected child elements the same height within this section/container. Useful for aligning columns, cards, or widgets in a row so they match regardless of content length. Click "Apply" above after enabling.', 'wpr-addons' ),
				'default' => 'no',
				'return_value' => 'yes',
				'prefix_class' => 'wpr-equal-height-',
				'render_type' => 'template',
			]
		);

		$element->add_control(
			'wpr_equal_height_target_type',
			[
				'label'     => esc_html__( 'Equalize', 'wpr-addons' ),
				'description' => esc_html__( 'Choose what to equalize: "Widgets" lets you pick specific widget types from a list, "Custom Selector" lets you target elements by CSS class name.', 'wpr-addons' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'widget',
				'options'   => [
					'widget' => esc_html__( 'Widgets', 'wpr-addons' ),
					'custom' => esc_html__( 'Custom Selector', 'wpr-addons' ),
				],
				'condition' => [
					'wpr_enable_equal_height' => 'yes',
				],
			]
		);

		$element->add_control(
			'wpr_equal_height_target',
			[
				'label'              => esc_html__( 'Widgets', 'wpr-addons' ),
				'description'        => esc_html__( 'Select which widget types should have equal height within this section.', 'wpr-addons' ),
				'type'               => 'wpr-select2',
				'render_type'        => 'template',
				'label_block'        => true,
				'multiple'           => true,
				'frontend_available' => true,
				'condition'          => [
					'wpr_enable_equal_height' => 'yes',
					'wpr_equal_height_target_type' => 'widget',
				],
			]
		);

		$element->add_control(
			'wpr_equal_height_custom_target',
			array(
				'label'       => esc_html__( 'Selectors', 'wpr-addons' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'placeholder' => esc_html__( '.class-name, .class-name2 .my-custom-class', 'wpr-addons' ),
				'description' => esc_html__( 'Enter CSS selectors separated by commas. All matching elements within this section will be set to equal height.', 'wpr-addons' ),
				'render_type' => 'none',
				'condition'   => [
					'wpr_enable_equal_height' => 'yes',
					'wpr_equal_height_target_type'     => 'custom',
				],
			)
		);

		$element->add_control(
			'wpr_enable_equal_height_on',
			array(
				'label'       => esc_html__( 'Enable Equal Height on', 'wpr-addons' ),
				'description' => esc_html__( 'Choose which screen sizes should apply equal height. Deselect smaller breakpoints if you want columns to stack naturally on mobile.', 'wpr-addons' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => Utilities::get_all_breakpoints(),
				'label_block' => true,
				'default'     => Utilities::get_all_breakpoints( 'keys' ),
				'condition'   => [
					'wpr_enable_equal_height' => 'yes',
				],
			)
		);

        $element->end_controls_section();

    }

	public function enqueue_scripts() {

		// if ( ! wp_script_is( 'pa-eq-height', 'enqueued' ) ) {
		// 	wp_enqueue_script( 'pa-eq-height' );
		// }

	}

    public function _before_render( $element ) {
        if ( $element->get_name() !== 'section' && $element->get_name() !== 'container' ) {
            return;
        }

		$settings = $element->get_settings_for_display();
		
		if ( 'yes' === $settings['wpr_enable_equal_height'] ) {
			
			$target_type = $settings['wpr_equal_height_target_type'];

			if ( 'custom' === $target_type ) {
				$target = array_map( 'trim', explode( ',', (string) $settings['wpr_equal_height_custom_target'] ) );
				$target = array_values( array_filter( $target ) );
			} else {
				$target = $settings['wpr_equal_height_target'] ?? [];
				if ( is_array( $target ) ) {
					$target = array_values( $target );
				} elseif ( is_string( $target ) && '' !== $target ) {
					$target = [ $target ];
				} else {
					$target = [];
				}
			}

			$enable_on = $settings['wpr_enable_equal_height_on'] ?? [];
			if ( ! is_array( $enable_on ) ) {
				$enable_on = '' !== (string) $enable_on ? [ $enable_on ] : [];
			} else {
				$enable_on = array_values( $enable_on );
			}

			$equal_height_settings = array(
				'wpr_eh_target-type' => $target_type,
				'wpr_eh_target'     => $target,
				'enable_on'   => $enable_on,
			);

			$element->add_render_attribute( '_wrapper', 'data-wpr-equal-height', wp_json_encode( $equal_height_settings ) );
		}
    }
    
    public function _print_template( $template, $widget ) {
		if ( $widget->get_name() !== 'section' && $widget->get_name() !== 'container' ) {
			return $template;
		}

		ob_start();

		?>
		<# if( 'yes' === settings.wpr_enable_equal_height ) {

			view.addRenderAttribute( 'wpr_equal_height', 'id', 'wpr-equal-height-' + view.getID() );
			var targetType = settings.wpr_equal_height_target_type,

				target = 'custom' === targetType ? settings.wpr_equal_height_custom_target.split(',').map(function(s){ return s.trim(); }).filter(function(s){ return s; }) : settings.wpr_equal_height_target,

				addonSettings = {
					'wpr_eh_target-type': targetType,
					'wpr_eh_target': target,
					'enable_on':settings.wpr_enable_equal_height_on
				};

			view.addRenderAttribute( 'equal_height', {
				'id' : 'wpr-equal-height-' + view.getID(),
				'data-wpr-equal-height': JSON.stringify( addonSettings )
			});

		#>
			<div {{{ view.getRenderAttributeString( 'equal_height' ) }}}></div>
		<# } #>
		<?php

		// how to render attributes without creating new div using view.addRenderAttributes
		$equal_height_content = ob_get_contents();

		ob_end_clean();

		return $template . $equal_height_content;
    }

}

new Wpr_Equal_Height();