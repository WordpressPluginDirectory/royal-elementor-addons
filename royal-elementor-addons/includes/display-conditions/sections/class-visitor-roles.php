<?php

use \Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPR_DC_Section_Visitor_Roles extends WPR_DC_Section_Base {

	public function get_id() {
		return 'wpr_dc_visitor_roles';
	}

	public function get_label() {
		return esc_html__( 'Visitor & Roles', 'wpr-addons' );
	}

	public function get_order() {
		return 20;
	}

	public function register_controls( $element ) {
		$element->add_control(
			'wpr_dc_visitor_type',
			[
				'label'       => esc_html__( 'User Roles', 'wpr-addons' ),
				'description' => esc_html__( 'Select user roles. Leave empty to skip this condition.', 'wpr-addons' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'default'     => [],
				'options'     => $this->get_role_options(),
				'render_type' => 'none',
			]
		);

		$element->add_control(
			'wpr_dc_specific_users',
			[
				'label'       => esc_html__( 'Specific Users', 'wpr-addons' ),
				'description' => esc_html__( 'Comma-separated user IDs, emails, or usernames.', 'wpr-addons' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => '',
				'render_type' => 'none',
			]
		);

		$element->add_control(
			'wpr_dc_capability',
			[
				'label'       => esc_html__( 'Required Permission', 'wpr-addons' ),
				'description' => esc_html__( 'WordPress capability, e.g. manage_options, edit_posts.', 'wpr-addons' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'default'     => '',
				'render_type' => 'none',
			]
		);
	}

	public function evaluate( $settings ) {
		$roles      = ! empty( $settings['wpr_dc_visitor_type'] ) ? $settings['wpr_dc_visitor_type'] : [];
		$users      = ! empty( $settings['wpr_dc_specific_users'] ) ? trim( $settings['wpr_dc_specific_users'] ) : '';
		$capability = ! empty( $settings['wpr_dc_capability'] ) ? trim( $settings['wpr_dc_capability'] ) : '';

		// Nothing configured — skip
		if ( empty( $roles ) && '' === $users && '' === $capability ) {
			return null;
		}

		$results = [];

		// Check visitor type / roles
		if ( ! empty( $roles ) ) {
			$results[] = $this->check_roles( $roles );
		}

		// Check specific users
		if ( '' !== $users ) {
			$results[] = $this->check_specific_users( $users );
		}

		// Check capability
		if ( '' !== $capability ) {
			$results[] = current_user_can( $capability );
		}

		// Any sub-condition passing means this section passes (OR within section)
		return in_array( true, $results, true );
	}

	/**
	 * Check if current visitor matches any of the selected roles.
	 */
	private function check_roles( $roles ) {
		if ( in_array( 'guest', $roles, true ) && ! is_user_logged_in() ) {
			return true;
		}

		if ( in_array( 'logged_in', $roles, true ) && is_user_logged_in() ) {
			return true;
		}

		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			foreach ( $user->roles as $role ) {
				if ( in_array( $role, $roles, true ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if current user matches any of the specified users.
	 */
	private function check_specific_users( $users_string ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		$current_user = wp_get_current_user();
		$identifiers  = array_map( 'trim', explode( ',', $users_string ) );

		foreach ( $identifiers as $identifier ) {
			if ( '' === $identifier ) {
				continue;
			}

			// Check by ID
			if ( is_numeric( $identifier ) && (int) $identifier === $current_user->ID ) {
				return true;
			}

			// Check by email
			if ( is_email( $identifier ) && strtolower( $identifier ) === strtolower( $current_user->user_email ) ) {
				return true;
			}

			// Check by username
			if ( strtolower( $identifier ) === strtolower( $current_user->user_login ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Build role options for the select dropdown.
	 */
	private function get_role_options() {
		$options = [
			'guest'     => esc_html__( 'Guests (Not Logged In)', 'wpr-addons' ),
			'logged_in' => esc_html__( 'Any Logged-in User', 'wpr-addons' ),
		];

		$wp_roles = wp_roles();

		foreach ( $wp_roles->role_names as $slug => $name ) {
			$options[ $slug ] = $name;
		}

		return $options;
	}
}
