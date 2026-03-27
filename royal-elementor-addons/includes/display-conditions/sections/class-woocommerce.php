<?php

use \Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPR_DC_Section_Woocommerce extends WPR_DC_Section_Base {

	public function get_id() {
		return 'wpr_dc_woocommerce';
	}

	public function get_label() {
		return esc_html__( 'WooCommerce', 'wpr-addons' );
	}

	public function get_order() {
		return 100;
	}

	public function is_available() {
		return class_exists( 'WooCommerce' );
	}

	public function register_controls( $element ) {
		$element->add_control( 'wpr_dc_woocommerce_pro_notice', [
			'type' => Controls_Manager::RAW_HTML,
			'raw' => '<span style="color:#2a2a2a;">WooCommerce conditions</span> are available in the <strong><a href="https://royal-elementor-addons.com/?ref=rea-plugin-panel-display-conditions-upgrade-pro#purchasepro" target="_blank">Pro version</a></strong>',
			'content_classes' => 'wpr-pro-notice',
		]);
	}

	public function evaluate( $settings ) {
		return null;
	}
}
