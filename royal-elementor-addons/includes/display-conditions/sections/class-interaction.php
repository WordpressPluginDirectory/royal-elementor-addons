<?php

use \Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPR_DC_Section_Interaction extends WPR_DC_Section_Base {

	public function get_id() {
		return 'wpr_dc_interaction';
	}

	public function get_label() {
		return esc_html__( 'Interaction', 'wpr-addons' );
	}

	public function get_order() {
		return 140;
	}

	public function register_controls( $element ) {
		$element->add_control( 'wpr_dc_interaction_pro_notice', [
			'type' => Controls_Manager::RAW_HTML,
			'raw' => '<span style="color:#2a2a2a;">Interaction conditions</span> are available in the <strong><a href="https://royal-elementor-addons.com/?ref=rea-plugin-panel-display-conditions-upgrade-pro#purchasepro" target="_blank">Pro version</a></strong>',
			'content_classes' => 'wpr-pro-notice',
		]);
	}

	public function evaluate( $settings ) {
		return null;
	}
}
