<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Shared helper for Free extensions that can defer methods to Pro classes.
 */
if ( class_exists( 'Wpr_Extensions_Base', false ) ) {
	return;
}

class Wpr_Extensions_Base {

	/**
	 * Check if the Pro plugin is active and license is valid.
	 */
	protected function has_active_pro_license() {
		if ( ! defined( 'WPR_ADDONS_PRO_VERSION' ) || ! function_exists( 'wpr_fs' ) ) {
			return false;
		}

		$wpr_fs = wpr_fs();

		return is_object( $wpr_fs ) && method_exists( $wpr_fs, 'can_use_premium_code' ) && $wpr_fs->can_use_premium_code();
	}

	/**
	 * Call a Pro class method when available.
	 *
	 * @param string $pro_class Fully qualified class name.
	 * @param string $method    Method name.
	 * @param array  $args      Arguments to pass.
	 * @return bool True when the Pro method was called, false otherwise.
	 */
	protected function maybe_call_pro_method( $pro_class, $method, $args = [] ) {
		if ( ! $this->has_active_pro_license() || ! class_exists( $pro_class ) || ! is_callable( [ $pro_class, $method ] ) ) {
			return false;
		}

		call_user_func_array( [ $pro_class, $method ], $args );

		return true;
	}
}
