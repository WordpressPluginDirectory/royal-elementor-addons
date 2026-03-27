<?php

use \Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPR_DC_Section_Device_Browser extends WPR_DC_Section_Base {

	public function get_id() {
		return 'wpr_dc_device_browser';
	}

	public function get_label() {
		return esc_html__( 'Device & Browser', 'wpr-addons' );
	}

	public function get_order() {
		return 70;
	}

	public function register_controls( $element ) {
		$element->add_control(
			'wpr_dc_devices',
			[
				'label'       => esc_html__( 'Devices', 'wpr-addons' ),
				'description' => esc_html__( 'Hidden elements are removed from the page code for faster loading.', 'wpr-addons' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'default'     => [],
				'options'     => [
					'desktop' => esc_html__( 'Desktop', 'wpr-addons' ),
					'tablet'  => esc_html__( 'Tablet', 'wpr-addons' ),
					'mobile'  => esc_html__( 'Mobile', 'wpr-addons' ),
				],
				'render_type' => 'none',
			]
		);

		$element->add_control(
			'wpr_dc_browsers',
			[
				'label'       => esc_html__( 'Browsers', 'wpr-addons' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'default'     => [],
				'options'     => [
					'chrome'  => esc_html__( 'Chrome', 'wpr-addons' ),
					'firefox' => esc_html__( 'Firefox', 'wpr-addons' ),
					'safari'  => esc_html__( 'Safari', 'wpr-addons' ),
					'edge'    => esc_html__( 'Edge', 'wpr-addons' ),
					'opera'   => esc_html__( 'Opera', 'wpr-addons' ),
					'ie'      => esc_html__( 'Internet Explorer', 'wpr-addons' ),
				],
				'render_type' => 'none',
			]
		);
	}

	public function evaluate( $settings ) {
		$devices  = ! empty( $settings['wpr_dc_devices'] ) ? $settings['wpr_dc_devices'] : [];
		$browsers = ! empty( $settings['wpr_dc_browsers'] ) ? $settings['wpr_dc_browsers'] : [];

		if ( empty( $devices ) && empty( $browsers ) ) {
			return null;
		}

		// Get cached UA info from the manager
		global $wpr_dc_manager;
		$ua_info = null;

		if ( isset( $wpr_dc_manager ) && method_exists( $wpr_dc_manager, 'get_user_agent_info' ) ) {
			$ua_info = $wpr_dc_manager->get_user_agent_info();
		}

		if ( ! $ua_info ) {
			$ua_info = $this->parse_user_agent();
		}

		$results = [];

		if ( ! empty( $devices ) ) {
			$results[] = in_array( $ua_info['device'], $devices, true );
		}

		if ( ! empty( $browsers ) ) {
			$results[] = in_array( $ua_info['browser'], $browsers, true );
		}

		if ( empty( $results ) ) {
			return null;
		}

		return ! in_array( false, $results, true );
	}

	private function parse_user_agent() {
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		return [
			'device'  => $this->detect_device( $ua ),
			'browser' => $this->detect_browser( $ua ),
		];
	}

	private function detect_device( $ua ) {
		if ( preg_match( '/iPad|Android(?!.*Mobile)|Tablet/i', $ua ) ) {
			return 'tablet';
		}
		if ( preg_match( '/Mobile|iPhone|iPod|Android.*Mobile|webOS|BlackBerry|Opera Mini|IEMobile/i', $ua ) ) {
			return 'mobile';
		}
		return 'desktop';
	}

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
