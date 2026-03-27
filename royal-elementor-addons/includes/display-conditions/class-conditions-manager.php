<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central manager for the Visibility tab.
 * Loads all sections, registers controls on all Elementor elements, evaluates conditions on render.
 */
class WPR_DC_Conditions_Manager {

	/**
	 * Registered section instances, sorted by order.
	 *
	 * @var WPR_DC_Section_Base[]
	 */
	private $sections = [];

	/**
	 * Cached user-agent parsing result.
	 */
	private $ua_parsed = null;

	public function __construct() {
		$this->load_sections();
		$this->setup_hooks();
	}

	/**
	 * Load and register all section classes from the sections/ directory.
	 */
	private function load_sections() {
		$sections_dir = WPR_ADDONS_PATH . 'includes/display-conditions/sections/';
		$files = glob( $sections_dir . 'class-*.php' );

		if ( ! $files ) {
			return;
		}

		foreach ( $files as $file ) {
			require_once $file;
		}

		// All section classes follow naming: WPR_DC_Section_{PascalName}
		// We collect them by checking declared classes that extend WPR_DC_Section_Base
		$section_classes = [
			'WPR_DC_Section_General_Settings',
			'WPR_DC_Section_Visitor_Roles',
			'WPR_DC_Section_User_Profile',
			'WPR_DC_Section_Page_Content',
			'WPR_DC_Section_Archive',
			'WPR_DC_Section_Date_Time',
			'WPR_DC_Section_Device_Browser',
			'WPR_DC_Section_Visitor_Location',
			'WPR_DC_Section_Url_Parameters',
			'WPR_DC_Section_Woocommerce',
			'WPR_DC_Section_Language',
			'WPR_DC_Section_Custom_Fields',
			'WPR_DC_Section_Dynamic_Tags',
			'WPR_DC_Section_Interaction',
			'WPR_DC_Section_Random_Limits',
			'WPR_DC_Section_Fallback_Content',
			'WPR_DC_Section_Pro_Features',
		];

		foreach ( $section_classes as $class_name ) {
			// Use pro override class if available
			$pro_class = $class_name . '_Pro';
			$use_class = class_exists( $pro_class ) ? $pro_class : $class_name;

			if ( class_exists( $use_class ) ) {
				$section = new $use_class();
				if ( $section->is_available() ) {
					$this->sections[] = $section;
				}
			}
		}

		// Sort by order
		usort( $this->sections, function ( $a, $b ) {
			return $a->get_order() - $b->get_order();
		} );
	}

	/**
	 * Hook into Elementor to register controls and intercept rendering.
	 */
	private function setup_hooks() {
		// Register controls on all element types
		$element_types = [ 'common', 'section', 'column', 'container' ];

		foreach ( $element_types as $type ) {
			add_action(
				"elementor/element/{$type}/section_effects/after_section_end",
				[ $this, 'register_tab_sections' ],
				10,
				2
			);
		}

		// Frontend: intercept rendering to show/hide elements
		$render_types = [ 'widget', 'section', 'column', 'container' ];

		foreach ( $render_types as $type ) {
			add_action( "elementor/frontend/{$type}/before_render", [ $this, 'before_render' ] );
			add_action( "elementor/frontend/{$type}/after_render", [ $this, 'after_render' ] );
		}

		// Prevent Elementor from caching elements that have visibility conditions.
		// Without this, the first render result gets cached and served to all users,
		// breaking role-based and other dynamic visibility logic.
		add_filter( 'elementor/element/is_dynamic_content', [ $this, 'disable_element_caching' ], 10, 2 );
	}

	/**
	 * Tell Elementor that elements with visibility conditions are dynamic
	 * so they are never served from the element cache.
	 *
	 * @param bool  $is_dynamic_content Current dynamic-content flag.
	 * @param array $raw_data           Raw element data including settings.
	 * @return bool
	 */
	public function disable_element_caching( $is_dynamic_content, $raw_data ) {
		if ( ! empty( $raw_data['settings']['wpr_dc_enabled'] ) ) {
			return true;
		}
		return $is_dynamic_content;
	}

	/**
	 * Register all visibility sections as collapsible panels in our custom tab.
	 */
	public function register_tab_sections( $element, $args ) {
		foreach ( $this->sections as $section ) {
			$section_args = [
				'tab'   => 'wpr_display_conditions',
				'label' => $section->get_label(),
			];

			// Hide all sections except General when visibility is not enabled
			if ( 'wpr_dc_general' !== $section->get_id() ) {
				$section_args['condition'] = [
					'wpr_dc_enabled' => 'yes',
				];
			}

			$element->start_controls_section(
				$section->get_id(),
				$section_args
			);

			$section->register_controls( $element );

			$element->end_controls_section();
		}
	}

	/**
	 * Before an element renders on the frontend — evaluate conditions and hide if needed.
	 */
	public function before_render( $element ) {
		// Never hide in editor
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return;
		}

		// Also skip preview mode so Elementor preview works
		if ( \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
			return;
		}

		$settings = $element->get_settings_for_display();

		// Check master toggle
		if ( empty( $settings['wpr_dc_enabled'] ) || 'yes' !== $settings['wpr_dc_enabled'] ) {
			return;
		}

		$mode     = ! empty( $settings['wpr_dc_mode'] ) ? $settings['wpr_dc_mode'] : 'show';
		$logic    = ! empty( $settings['wpr_dc_logic'] ) ? $settings['wpr_dc_logic'] : 'any';
		$css_only = ! empty( $settings['wpr_dc_css_only'] ) && 'yes' === $settings['wpr_dc_css_only'];

		// Collect results from evaluable sections (skip general, fallback, interaction)
		$skip_ids = [ 'wpr_dc_general', 'wpr_dc_fallback', 'wpr_dc_interaction' ];
		$results  = [];

		foreach ( $this->sections as $section ) {
			if ( in_array( $section->get_id(), $skip_ids, true ) ) {
				continue;
			}

			$result = $section->evaluate( $settings );

			if ( null !== $result ) {
				$results[] = $result;
			}
		}

		// No conditions configured — always show
		if ( empty( $results ) ) {
			return;
		}

		// Apply logic
		if ( 'all' === $logic ) {
			$conditions_met = ! in_array( false, $results, true );
		} else {
			$conditions_met = in_array( true, $results, true );
		}

		// Determine if element should be shown
		$should_show = ( 'show' === $mode ) ? $conditions_met : ! $conditions_met;

		if ( ! $should_show ) {
			WPR_DC_Render_Handler::instance()->start_hiding( $element, $settings, $css_only );
		}
	}

	/**
	 * After an element renders — close output buffer if hiding.
	 */
	public function after_render( $element ) {
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return;
		}

		WPR_DC_Render_Handler::instance()->finish_hiding( $element );
	}

	/**
	 * Get parsed user-agent info (cached per request).
	 */
	public function get_user_agent_info() {
		if ( null !== $this->ua_parsed ) {
			return $this->ua_parsed;
		}

		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		$this->ua_parsed = [
			'raw'     => $ua,
			'device'  => $this->detect_device( $ua ),
			'browser' => $this->detect_browser( $ua ),
		];

		return $this->ua_parsed;
	}

	/**
	 * Detect device type from user-agent string.
	 */
	private function detect_device( $ua ) {
		if ( preg_match( '/iPad|Android(?!.*Mobile)|Tablet/i', $ua ) ) {
			return 'tablet';
		}
		if ( preg_match( '/Mobile|iPhone|iPod|Android.*Mobile|webOS|BlackBerry|Opera Mini|IEMobile/i', $ua ) ) {
			return 'mobile';
		}
		return 'desktop';
	}

	/**
	 * Detect browser from user-agent string.
	 */
	private function detect_browser( $ua ) {
		if ( preg_match( '/Edg\//i', $ua ) ) {
			return 'edge';
		}
		if ( preg_match( '/OPR|Opera/i', $ua ) ) {
			return 'opera';
		}
		if ( preg_match( '/Chrome/i', $ua ) && ! preg_match( '/Edg|OPR/i', $ua ) ) {
			return 'chrome';
		}
		if ( preg_match( '/Safari/i', $ua ) && ! preg_match( '/Chrome|Edg|OPR/i', $ua ) ) {
			return 'safari';
		}
		if ( preg_match( '/Firefox/i', $ua ) ) {
			return 'firefox';
		}
		if ( preg_match( '/MSIE|Trident/i', $ua ) ) {
			return 'ie';
		}
		return 'other';
	}
}
