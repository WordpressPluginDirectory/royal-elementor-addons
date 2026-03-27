<?php

use \Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPR_DC_Section_Language extends WPR_DC_Section_Base {

	public function get_id() {
		return 'wpr_dc_language';
	}

	public function get_label() {
		return esc_html__( 'Language', 'wpr-addons' );
	}

	public function get_order() {
		return 110;
	}

	public function is_available() {
		return defined( 'ICL_SITEPRESS_VERSION' ) || defined( 'POLYLANG_VERSION' ) || class_exists( 'TRP_Translate_Press' ) || defined( 'WEGLOT_VERSION' );
	}

	public function register_controls( $element ) {
		$element->add_control( 'wpr_dc_language_pro_notice', [
			'type' => Controls_Manager::RAW_HTML,
			'raw' => '<span style="color:#2a2a2a;">Language conditions</span> are available in the <strong><a href="https://royal-elementor-addons.com/?ref=rea-plugin-panel-display-conditions-upgrade-pro#purchasepro" target="_blank">Pro version</a></strong>',
			'content_classes' => 'wpr-pro-notice',
		]);
	}

	public function evaluate( $settings ) {
		return null;
	}
}
