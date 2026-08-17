<?php

use WprAddons\Admin\Includes\WPR_Conditions_Manager;
use WprAddons\Classes\Utilities;

/**
 * Ultimate Addons for Elementor (Header Footer Elementor) compatibility.
 *
 * REA Theme Builder content templates load wpr-canvas.php, which skips theme
 * header/footer hooks HFE normally uses. This restores HFE layouts on that canvas
 * when REA is not already printing its own header/footer there.
 */
class Wpr_HFE_Compat {

	/**
	 * Instance of Wpr_HFE_Compat.
	 *
	 * @var Wpr_HFE_Compat
	 */
	private static $instance;

	/**
	 * Initiator
	 *
	 * @return Wpr_HFE_Compat
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Wpr_HFE_Compat();

			add_action( 'wp', [ self::$instance, 'hooks' ] );
		}

		return self::$instance;
	}

	/**
	 * Run all the Actions / Filters.
	 */
	public function hooks() {
		// Only when REA Theme Builder replaces the page with its canvas shell.
		if ( is_null( WPR_Conditions_Manager::canvas_page_content_display_conditions() ) ) {
			return;
		}

		if ( function_exists( 'hfe_header_enabled' ) && hfe_header_enabled() ) {
			add_action( 'elementor/page_templates/canvas/before_content', [ $this, 'render_header' ] );
		}

		if ( function_exists( 'hfe_is_before_footer_enabled' ) && hfe_is_before_footer_enabled() ) {
			add_action( 'elementor/page_templates/canvas/after_content', [ $this, 'render_before_footer' ], 9 );
		}

		if ( function_exists( 'hfe_footer_enabled' ) && hfe_footer_enabled() ) {
			add_action( 'elementor/page_templates/canvas/after_content', [ $this, 'render_footer' ] );
		}
	}

	/**
	 * Print HFE header on REA canvas when nothing else already handles it.
	 */
	public function render_header() {
		if ( $this->is_rea_canvas_header_active() ) {
			return;
		}

		// HFE global theme support already prints via wp_body_open on wpr-canvas.
		if ( has_action( 'wp_body_open', [ 'Header_Footer_Elementor', 'get_header_content' ] ) ) {
			return;
		}

		// Official Elementor canvas + HFE canvas option — leave to HFE_Elementor_Canvas_Compat.
		if ( 'elementor_canvas' === get_page_template_slug() ) {
			return;
		}

		if ( function_exists( 'hfe_render_header' ) ) {
			hfe_render_header();
		}
	}

	/**
	 * Print HFE before-footer on REA canvas.
	 */
	public function render_before_footer() {
		if ( $this->is_rea_canvas_footer_active() ) {
			return;
		}

		if ( has_action( 'wp_footer', [ 'Header_Footer_Elementor', 'get_before_footer_content' ] ) ) {
			return;
		}

		if ( 'elementor_canvas' === get_page_template_slug() ) {
			return;
		}

		if ( function_exists( 'hfe_render_before_footer' ) ) {
			hfe_render_before_footer();
		}
	}

	/**
	 * Print HFE footer on REA canvas when nothing else already handles it.
	 */
	public function render_footer() {
		if ( $this->is_rea_canvas_footer_active() ) {
			return;
		}

		// HFE global theme support already prints via wp_footer on wpr-canvas.
		if ( has_action( 'wp_footer', [ 'Header_Footer_Elementor', 'get_footer_content' ] ) ) {
			return;
		}

		if ( 'elementor_canvas' === get_page_template_slug() ) {
			return;
		}

		if ( function_exists( 'hfe_render_footer' ) ) {
			hfe_render_footer();
		}
	}

	/**
	 * Whether REA will print its own header on canvas (show on canvas enabled).
	 *
	 * @return bool
	 */
	private function is_rea_canvas_header_active() {
		$conditions = json_decode( get_option( 'wpr_header_conditions', '[]' ), true );
		$template_slug = WPR_Conditions_Manager::header_footer_display_conditions( $conditions );

		if ( is_null( $template_slug ) ) {
			return false;
		}

		$template_id = Utilities::get_template_id( $template_slug );
		$show_on_canvas = get_post_meta( $template_id, 'wpr_header_show_on_canvas', true );

		if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
			$default_language_code = apply_filters( 'wpml_default_language', null );
			$current_language_code = apply_filters( 'wpml_current_language', null );

			if ( $current_language_code && $current_language_code !== $default_language_code ) {
				$show_on_canvas = 'true';
			}
		}

		return ( ! empty( $show_on_canvas ) && 'true' === $show_on_canvas );
	}

	/**
	 * Whether REA will print its own footer on canvas (show on canvas enabled).
	 *
	 * @return bool
	 */
	private function is_rea_canvas_footer_active() {
		$conditions = json_decode( get_option( 'wpr_footer_conditions', '[]' ), true );
		$template_slug = WPR_Conditions_Manager::header_footer_display_conditions( $conditions );

		if ( is_null( $template_slug ) ) {
			return false;
		}

		$template_id = Utilities::get_template_id( $template_slug );
		$show_on_canvas = get_post_meta( $template_id, 'wpr_footer_show_on_canvas', true );

		if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
			$default_language_code = apply_filters( 'wpml_default_language', null );
			$current_language_code = apply_filters( 'wpml_current_language', null );

			if ( $current_language_code && $current_language_code !== $default_language_code ) {
				$show_on_canvas = 'true';
			}
		}

		return ( ! empty( $show_on_canvas ) && 'true' === $show_on_canvas );
	}
}

Wpr_HFE_Compat::instance();
