<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract base class for all visibility condition sections.
 * Each collapsible section in the Visibility tab extends this class.
 */
abstract class WPR_DC_Section_Base {

	/**
	 * Unique section ID (e.g. 'wpr_dc_visitor_roles').
	 */
	abstract public function get_id();

	/**
	 * Section label shown in the Elementor panel.
	 */
	abstract public function get_label();

	/**
	 * Sort order in the tab (10, 20, 30...).
	 */
	abstract public function get_order();

	/**
	 * Register Elementor controls for this section.
	 */
	abstract public function register_controls( $element );

	/**
	 * Evaluate conditions for this section.
	 *
	 * @return bool|null true = conditions met, false = not met, null = not configured (skip)
	 */
	abstract public function evaluate( $settings );

	/**
	 * Whether this section is available (e.g. required plugin is active).
	 */
	public function is_available() {
		return true;
	}

	/**
	 * Compare two values using the given operator.
	 */
	protected function compare( $actual, $operator, $expected ) {
		switch ( $operator ) {
			case 'isset':
				return '' !== $actual && null !== $actual && false !== $actual;
			case 'empty':
				return '' === $actual || null === $actual || false === $actual;
			case 'equals':
				return (string) $actual === (string) $expected;
			case 'not_equals':
				return (string) $actual !== (string) $expected;
			case 'contains':
				return false !== strpos( (string) $actual, (string) $expected );
			case 'not_contains':
				return false === strpos( (string) $actual, (string) $expected );
			case 'starts_with':
				return 0 === strpos( (string) $actual, (string) $expected );
			case 'ends_with':
				return substr( (string) $actual, -strlen( (string) $expected ) ) === (string) $expected;
			case 'less_than':
				return (float) $actual < (float) $expected;
			case 'greater_than':
				return (float) $actual > (float) $expected;
			case 'less_equal':
				return (float) $actual <= (float) $expected;
			case 'greater_equal':
				return (float) $actual >= (float) $expected;
			default:
				return true;
		}
	}

	/**
	 * Get comparison operators for select dropdowns.
	 */
	protected function get_comparison_operators() {
		return [
			'isset'         => esc_html__( 'Has any value', 'wpr-addons' ),
			'empty'         => esc_html__( 'Is empty', 'wpr-addons' ),
			'equals'        => esc_html__( 'Equals', 'wpr-addons' ),
			'not_equals'    => esc_html__( 'Does not equal', 'wpr-addons' ),
			'contains'      => esc_html__( 'Contains', 'wpr-addons' ),
			'not_contains'  => esc_html__( 'Does not contain', 'wpr-addons' ),
			'starts_with'   => esc_html__( 'Starts with', 'wpr-addons' ),
			'ends_with'     => esc_html__( 'Ends with', 'wpr-addons' ),
			'less_than'     => esc_html__( 'Less than', 'wpr-addons' ),
			'greater_than'  => esc_html__( 'Greater than', 'wpr-addons' ),
			'less_equal'    => esc_html__( 'Less than or equal to', 'wpr-addons' ),
			'greater_equal' => esc_html__( 'Greater than or equal to', 'wpr-addons' ),
		];
	}
}
