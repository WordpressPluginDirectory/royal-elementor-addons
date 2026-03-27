<?php

use WprAddons\Classes\Utilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Visibility (Display Conditions) Extension
 * Adds a dedicated 4th tab in Elementor with conditional visibility controls.
 */
class Wpr_Display_Conditions {

	public function __construct() {
		add_action( 'elementor/init', [ $this, 'register_tab' ] );
		add_action( 'elementor/init', [ $this, 'load_classes' ], 20 );

		add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'editor_styles' ] );
		add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'editor_scripts' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'frontend_styles' ] );
	}

	/**
	 * Register the custom Visibility tab in Elementor.
	 */
	public function register_tab() {
		\Elementor\Controls_Manager::add_tab(
			'wpr_display_conditions',
			esc_html__( 'Visibility', 'wpr-addons' )
		);
	}

	/**
	 * Load all display condition classes and initialize the manager.
	 */
	public function load_classes() {
		$base = WPR_ADDONS_PATH . 'includes/display-conditions/';

		require_once $base . 'class-section-base.php';
		require_once $base . 'class-render-handler.php';
		require_once $base . 'class-conditions-manager.php';

		// Allow pro plugin to load override classes before the manager instantiates
		do_action( 'wpr_display_conditions_classes_loaded' );

		new WPR_DC_Conditions_Manager();
	}

	/**
	 * Editor styles for tab icon and condition indicators.
	 */
	public function editor_styles() {
		wp_enqueue_style(
			'wpr-display-conditions-editor',
			WPR_ADDONS_URL . 'assets/css/admin/wpr-display-conditions.css',
			[],
			WPR_ADDONS_VERSION
		);
	}

	/**
	 * Editor scripts for active condition indicators.
	 */
	public function editor_scripts() {
		wp_enqueue_script(
			'wpr-display-conditions-editor',
			WPR_ADDONS_URL . 'assets/js/admin/wpr-display-conditions-editor.js',
			[ 'elementor-editor' ],
			WPR_ADDONS_VERSION,
			true
		);
	}

	/**
	 * Minimal frontend styles for hidden elements and fallback.
	 */
	public function frontend_styles() {
		wp_add_inline_style(
			'elementor-frontend',
			'.wpr-dc-hidden{display:none!important}.wpr-dc-fallback{margin:0;padding:0}'
		);
	}
}

new Wpr_Display_Conditions();
