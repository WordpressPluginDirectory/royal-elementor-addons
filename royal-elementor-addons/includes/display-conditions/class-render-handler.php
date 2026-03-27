<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles element hiding: DOM removal via output buffering, CSS-only hiding, and fallback content.
 */
class WPR_DC_Render_Handler {

	private static $instance = null;

	/**
	 * Track which elements are being hidden and how.
	 */
	private $hidden_elements = [];

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Start hiding an element.
	 * Called from before_render when conditions determine the element should be hidden.
	 */
	public function start_hiding( $element, $settings, $css_only = false ) {
		$id = $element->get_id();

		if ( $css_only ) {
			$element->add_render_attribute( '_wrapper', 'class', 'wpr-dc-hidden' );
			$this->hidden_elements[ $id ] = [
				'type'     => 'css',
				'settings' => $settings,
			];
		} else {
			ob_start();
			$this->hidden_elements[ $id ] = [
				'type'     => 'dom',
				'settings' => $settings,
			];
		}
	}

	/**
	 * Finish hiding an element.
	 * Called from after_render — discards buffer for DOM removal, renders fallback if needed.
	 */
	public function finish_hiding( $element ) {
		$id = $element->get_id();

		if ( ! isset( $this->hidden_elements[ $id ] ) ) {
			return;
		}

		$hidden = $this->hidden_elements[ $id ];
		unset( $this->hidden_elements[ $id ] );

		if ( 'dom' === $hidden['type'] ) {
			ob_end_clean();
			$this->maybe_render_fallback( $hidden['settings'] );
		}
	}

	/**
	 * Check if an element is currently being hidden.
	 */
	public function is_hiding( $element ) {
		return isset( $this->hidden_elements[ $element->get_id() ] );
	}

	/**
	 * Render fallback content if enabled in settings.
	 */
	private function maybe_render_fallback( $settings ) {
		if ( empty( $settings['wpr_dc_fallback_enabled'] ) || 'yes' !== $settings['wpr_dc_fallback_enabled'] ) {
			return;
		}

		$type = ! empty( $settings['wpr_dc_fallback_type'] ) ? $settings['wpr_dc_fallback_type'] : 'text';

		echo '<div class="wpr-dc-fallback">';

		if ( 'text' === $type && ! empty( $settings['wpr_dc_fallback_text'] ) ) {
			echo wp_kses_post( $settings['wpr_dc_fallback_text'] );
		} elseif ( 'template' === $type && ! empty( $settings['wpr_dc_fallback_template'] ) ) {
			$template_id = absint( $settings['wpr_dc_fallback_template'] );
			if ( $template_id ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $template_id );
			}
		}

		echo '</div>';
	}
}
