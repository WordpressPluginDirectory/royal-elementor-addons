<?php
namespace WprAddons\Includes\Controls;

use Elementor\Control_Select2;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPR_Select2 extends Control_Select2 {

	const TYPE = 'wpr-select2';

	/**
	 * Returns the type of the control
	 */
	public function get_type() {
		return self::TYPE;
	}
}
