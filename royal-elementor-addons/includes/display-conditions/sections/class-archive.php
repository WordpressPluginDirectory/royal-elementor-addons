<?php

use \Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPR_DC_Section_Archive extends WPR_DC_Section_Base {

	public function get_id() {
		return 'wpr_dc_archive';
	}

	public function get_label() {
		return esc_html__( 'Archive', 'wpr-addons' );
	}

	public function get_order() {
		return 50;
	}

	public function register_controls( $element ) {
		$element->add_control(
			'wpr_dc_archive_types',
			[
				'label'       => esc_html__( 'Page Types', 'wpr-addons' ),
				'description' => esc_html__( 'Select page types to match.', 'wpr-addons' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'default'     => [],
				'options'     => [
					'is_front_page' => esc_html__( 'Front Page', 'wpr-addons' ),
					'is_home'       => esc_html__( 'Blog / Posts Page', 'wpr-addons' ),
					'is_singular'   => esc_html__( 'Any Single Post/Page', 'wpr-addons' ),
					'is_single'     => esc_html__( 'Single Post', 'wpr-addons' ),
					'is_page'       => esc_html__( 'Single Page', 'wpr-addons' ),
					'is_archive'    => esc_html__( 'Any Archive', 'wpr-addons' ),
					'is_category'   => esc_html__( 'Category Archive', 'wpr-addons' ),
					'is_tag'        => esc_html__( 'Tag Archive', 'wpr-addons' ),
					'is_tax'        => esc_html__( 'Taxonomy Archive', 'wpr-addons' ),
					'is_author'     => esc_html__( 'Author Archive', 'wpr-addons' ),
					'is_date'       => esc_html__( 'Date Archive', 'wpr-addons' ),
					'is_search'     => esc_html__( 'Search Results', 'wpr-addons' ),
					'is_404'        => esc_html__( '404 Page', 'wpr-addons' ),
				],
				'render_type' => 'none',
			]
		);

		$element->add_control(
			'wpr_dc_archive_term_property',
			[
				'label'       => esc_html__( 'Term Property', 'wpr-addons' ),
				'description' => esc_html__( 'Additional check on the current archive term.', 'wpr-addons' ),
				'type'        => Controls_Manager::SELECT,
				'label_block' => true,
				'default'     => '',
				'options'     => [
					''             => esc_html__( 'Not Set', 'wpr-addons' ),
					'has_children' => esc_html__( 'Has Child Terms', 'wpr-addons' ),
					'is_root'      => esc_html__( 'Is Root Term (no parent)', 'wpr-addons' ),
					'is_leaf'      => esc_html__( 'Is Leaf Term (no children)', 'wpr-addons' ),
					'has_posts'    => esc_html__( 'Has Posts', 'wpr-addons' ),
				],
				'render_type' => 'none',
			]
		);
	}

	public function evaluate( $settings ) {
		$types    = ! empty( $settings['wpr_dc_archive_types'] ) ? $settings['wpr_dc_archive_types'] : [];
		$property = ! empty( $settings['wpr_dc_archive_term_property'] ) ? $settings['wpr_dc_archive_term_property'] : '';

		if ( empty( $types ) && '' === $property ) {
			return null;
		}

		$results = [];

		// Check page types
		if ( ! empty( $types ) ) {
			$type_match = false;
			foreach ( $types as $condition_fn ) {
				if ( function_exists( $condition_fn ) && call_user_func( $condition_fn ) ) {
					$type_match = true;
					break;
				}
			}
			$results[] = $type_match;
		}

		// Check term property (only relevant on taxonomy archives)
		if ( '' !== $property ) {
			$results[] = $this->check_term_property( $property );
		}

		if ( empty( $results ) ) {
			return null;
		}

		return ! in_array( false, $results, true );
	}

	private function check_term_property( $property ) {
		$term = get_queried_object();

		if ( ! $term || ! isset( $term->term_id ) ) {
			return false;
		}

		switch ( $property ) {
			case 'has_children':
				$children = get_term_children( $term->term_id, $term->taxonomy );
				return ! empty( $children ) && ! is_wp_error( $children );

			case 'is_root':
				return 0 === (int) $term->parent;

			case 'is_leaf':
				$children = get_term_children( $term->term_id, $term->taxonomy );
				return empty( $children ) || is_wp_error( $children );

			case 'has_posts':
				return $term->count > 0;

			default:
				return true;
		}
	}
}
