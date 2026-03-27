<?php

use \Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPR_DC_Section_Dynamic_Tags extends WPR_DC_Section_Base {

	public function get_id() {
		return 'wpr_dc_dynamic_tags';
	}

	public function get_label() {
		return esc_html__( 'Dynamic Tags', 'wpr-addons' );
	}

	public function get_order() {
		return 130;
	}

	public function register_controls( $element ) {
		$element->add_control( 'wpr_dc_dynamic_tags_pro_notice', [
			'type' => Controls_Manager::RAW_HTML,
			'raw' => '<span style="color:#2a2a2a;">Dynamic Tags conditions</span> are available in the <strong><a href="https://royal-elementor-addons.com/?ref=rea-plugin-panel-display-conditions-upgrade-pro#purchasepro" target="_blank">Pro version</a></strong>',
			'content_classes' => 'wpr-pro-notice',
		]);
	}

	public function evaluate( $settings ) {
		return null;
	}
}
