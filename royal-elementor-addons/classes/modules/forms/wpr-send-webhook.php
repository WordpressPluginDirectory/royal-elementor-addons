<?php
namespace WprAddons\Classes\Modules\Forms;

use WprAddons\Classes\Utilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WPR_Send_Webhook setup
 *
 * @since 3.4.6
 */
class WPR_Send_Webhook {

	public function __construct() {
		add_action( 'wp_ajax_wpr_form_builder_webhook', [ $this, 'send_webhook' ] );
		add_action( 'wp_ajax_nopriv_wpr_form_builder_webhook', [ $this, 'send_webhook' ] );
		add_action( 'elementor/editor/after_save', [ $this, 'persist_webhook_urls_on_save' ], 10, 2 );
	}

	/**
	 * Persist validated webhook URLs when an Elementor document is saved.
	 *
	 * Avoids relying solely on widget render() (which runs on draft preview).
	 *
	 * @param int   $post_id Post ID.
	 * @param array $data    Elementor document data.
	 */
	public function persist_webhook_urls_on_save( $post_id, $data ) {
		if ( ! current_user_can( 'publish_posts' ) ) {
			return;
		}

		if ( ! is_array( $data ) ) {
			return;
		}

		$this->walk_elements_for_webhook( $data );
	}

	/**
	 * Recursively find Form Builder widgets and store safe webhook URLs.
	 *
	 * @param array $elements Elementor elements tree.
	 */
	protected function walk_elements_for_webhook( $elements ) {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			$widget_type = isset( $element['widgetType'] ) ? $element['widgetType'] : '';
			if ( 'wpr-form-builder' === $widget_type && ! empty( $element['id'] ) ) {
				$settings    = isset( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : [];
				$webhook_url = isset( $settings['webhook_url'] ) ? $settings['webhook_url'] : '';
				$validated   = Utilities::wpr_validate_webhook_url( $webhook_url );

				if ( is_wp_error( $validated ) ) {
					update_option( 'wpr_webhook_url_' . $element['id'], '' );
				} else {
					update_option( 'wpr_webhook_url_' . $element['id'], $validated );
				}
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$this->walk_elements_for_webhook( $element['elements'] );
			}
		}
	}

	public function send_webhook() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'wpr-addons-js' ) ) {
			return; // Get out of here, the nonce is rotten!
		}

		$form_id = isset( $_POST['wpr_form_id'] ) ? sanitize_text_field( wp_unslash( $_POST['wpr_form_id'] ) ) : '';
		if ( '' === $form_id || ! preg_match( '/^[A-Za-z0-9_-]+$/', $form_id ) ) {
			wp_send_json_error( [
				'action'  => 'wpr_form_builder_webhook',
				'message' => esc_html__( 'Invalid form ID.', 'wpr-addons' ),
				'status'  => 'error',
			] );
		}

		$webhook_url = get_option( 'wpr_webhook_url_' . $form_id, '' );
		$webhook_url = Utilities::wpr_validate_webhook_url( $webhook_url );

		if ( is_wp_error( $webhook_url ) || '' === $webhook_url ) {
			wp_send_json_error( [
				'action'  => 'wpr_form_builder_webhook',
				'message' => esc_html__( 'Webhook error', 'wpr-addons' ),
				'status'  => 'error',
				'details' => is_wp_error( $webhook_url ) ? $webhook_url->get_error_message() : esc_html__( 'Webhook URL is missing or invalid.', 'wpr-addons' ),
			] );
		}

		$message_body = [];
		$form_content = isset( $_POST['form_content'] ) && is_array( $_POST['form_content'] ) ? wp_unslash( $_POST['form_content'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized per-field below.

		foreach ( $form_content as $key => $value ) {
			if ( ! is_array( $value ) ) {
				continue;
			}

			$field_key   = isset( $value[2] ) && '' !== $value[2] ? sanitize_text_field( $value[2] ) : sanitize_text_field( (string) $key );
			$field_value = isset( $value[1] ) ? $value[1] : '';

			if ( is_array( $field_value ) ) {
				$message_body[ $field_key ] = implode( "\n", array_map( 'sanitize_text_field', $field_value ) );
			} else {
				$message_body[ $field_key ] = sanitize_text_field( $field_value );
			}
		}

		$message_body['form_id']   = $form_id;
		$message_body['form_name'] = isset( $_POST['form_name'] ) ? sanitize_text_field( wp_unslash( $_POST['form_name'] ) ) : '';

		$args = [
			'body'        => $message_body,
			'timeout'     => 15,
			'redirection' => 3,
		];

		// wp_safe_remote_post rejects unsafe/private URLs and re-validates redirects.
		$response      = wp_safe_remote_post( $webhook_url, $args );
		$response_code = (int) wp_remote_retrieve_response_code( $response );
		$is_success    = ! is_wp_error( $response ) && $response_code >= 200 && $response_code < 300;

		if ( ! $is_success ) {
			wp_send_json_error( [
				'action'  => 'wpr_form_builder_webhook',
				'message' => esc_html__( 'Webhook error', 'wpr-addons' ),
				'status'  => 'error',
				'details' => wp_json_encode( $message_body ),
			] );
		}

		wp_send_json_success( [
			'action'  => 'wpr_form_builder_webhook',
			'message' => esc_html__( 'Webhook success', 'wpr-addons' ),
			'status'  => 'success',
			'details' => wp_json_encode( $message_body ),
		] );
	}
}

new WPR_Send_Webhook();
