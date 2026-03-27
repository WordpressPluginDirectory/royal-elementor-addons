<?php

use \Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPR_DC_Section_Pro_Features extends WPR_DC_Section_Base {

	public function get_id() {
		return 'wpr_dc_pro_features';
	}

	public function get_label() {
		return 'Pro Features <span class="dashicons dashicons-star-filled"></span>';
	}

	public function get_order() {
		return 999;
	}

	public function is_available() {
		return ! ( defined( 'WPR_ADDONS_PRO_VERSION' ) && wpr_fs()->can_use_premium_code() );
	}

	public function register_controls( $element ) {
		$features = [
			'User Profile conditions',
			'Page & Content conditions',
			'Date & Time scheduling',
			'Visitor Location & GeoIP',
			'URL & Parameters',
			'WooCommerce conditions',
			'Language conditions',
			'Custom Fields (ACF)',
			'Dynamic Tags',
			'Interaction triggers',
			'Random & View Limits',
			'Fallback Content',
		];

		$list_html = '';
		foreach ( $features as $feature ) {
			$list_html .= '<li>' . $feature . '</li>';
		}

		$element->add_control( 'wpr_dc_pro_features_list', [
			'type' => Controls_Manager::RAW_HTML,
			'raw' => '<ul>' . $list_html . '</ul>
					  <a href="https://royal-elementor-addons.com/?ref=rea-plugin-panel-pro-sec-display-conditions-upgrade-pro#purchasepro" target="_blank">Get Pro version</a>',
			'content_classes' => 'wpr-pro-features-list',
		]);
	}

	public function evaluate( $settings ) {
		return null;
	}
}
