<?php

use \Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPR_DC_Section_Visitor_Location extends WPR_DC_Section_Base {

	public function get_id() {
		return 'wpr_dc_visitor_location';
	}

	public function get_label() {
		return esc_html__( 'Visitor Location', 'wpr-addons' );
	}

	public function get_order() {
		return 80;
	}

	public function register_controls( $element ) {
		$element->add_control( 'wpr_dc_visitor_location_pro_notice', [
			'type' => Controls_Manager::RAW_HTML,
			'raw' => '<span style="color:#2a2a2a;">Visitor Location conditions</span> are available in the <strong><a href="https://royal-elementor-addons.com/?ref=rea-plugin-panel-display-conditions-upgrade-pro#purchasepro" target="_blank">Pro version</a></strong>',
			'content_classes' => 'wpr-pro-notice',
		]);
	}

	public function evaluate( $settings ) {
		return null;
	}
}
