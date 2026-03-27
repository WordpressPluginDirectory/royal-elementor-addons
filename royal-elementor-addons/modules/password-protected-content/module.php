<?php
namespace WprAddons\Modules\PasswordProtectedContent;

use WprAddons\Base\Module_Base;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Module extends Module_Base {

	public function get_widgets() {
		return [
			'Wpr_Password_Protected_Content',
		];
	}

	public function get_name() {
		return 'wpr-password-protected-content';
	}
}
