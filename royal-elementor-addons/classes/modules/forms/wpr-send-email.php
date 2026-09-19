<?php
namespace WprAddons\Classes\Modules\Forms;

use Elementor\Utils;
use WprAddons\Classes\Utilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WPR_Send_Email setup
 *
 * @since 3.4.6
 */

 class WPR_Send_Email {

    public function __construct() {
        add_action('wp_ajax_wpr_form_builder_email' , [$this, 'send_email']);
        add_action('wp_ajax_nopriv_wpr_form_builder_email',[$this, 'send_email']);
    }

    public function send_email() {

        $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

        if ( ! wp_verify_nonce( $nonce, 'wpr-addons-js' ) ) {
            return; // Get out of here, the nonce is rotten!
        }

		$form_content = isset( $_POST['form_content'] ) && is_array( $_POST['form_content'] ) ? wp_unslash( $_POST['form_content'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- escaped for the email body below.
        
        $message_body = [];

		foreach ( $form_content as $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}
			if ( isset( $field[0] ) && 'email' === $field[0] ) {
				$field_email = isset( $field[1] ) ? $field[1] : '';
				if ( ! is_email( $field_email ) ) {
					// The field is an email, but it is not a valid email address
					// Take action or abort function execution here
					wp_send_json_error( [
						'action' => 'wpr_form_builder_email',
						'message' => esc_html__( 'Email provided is invalid', 'wpr-addons' ),
						'status' => 'error',
					] );
				}
			}
		}
		
		$form_id      = isset( $_POST['wpr_form_id'] ) ? sanitize_text_field( wp_unslash( $_POST['wpr_form_id'] ) ) : '';
		$content_type = get_option( 'wpr_email_content_type_' . $form_id );
		$is_html      = ( 'html' === $content_type );

		$line_break = $is_html ? '<br>' : "\n";
    
		$email_fields = trim( (string) get_option( 'wpr_email_fields_' . $form_id ) );

		$replace_shortcode_with_value = function ( $matches ) use ( $form_content, $is_html ) {
			$field_id = $matches[1];
			foreach ( $form_content as $key => $value ) {
				if ( ! is_array( $value ) ) {
					continue;
				}
				$key_parts = explode( '-', (string) $key );
				$last_part = end( $key_parts );
				if ( $last_part === $field_id ) {
					$field_value = isset( $value[1] ) ? $value[1] : '';
					return $this->format_body_value( $field_value, $is_html );
				}
			}
			return '';
		};

		$replace_shortcode_with_labeled_value = function ( $matches ) use ( $form_content, $is_html ) {
			$field_id = $matches[1];
			foreach ( $form_content as $key => $value ) {
				if ( ! is_array( $value ) ) {
					continue;
				}
				$key_parts = explode( '-', (string) $key );
				$last_part = end( $key_parts );
				if ( $last_part === $field_id ) {
					$label       = isset( $value[2] ) ? $value[2] : '';
					$field_value = isset( $value[1] ) ? $value[1] : '';
					return $this->format_body_field_line( $label, $field_value, $is_html );
				}
			}
			return '';
		};
		
		if ( '[all-fields]' === $email_fields || str_contains( $email_fields, '[all-fields]' ) ) {

			$all_fields_content = [];

			foreach ( $form_content as $value ) {
				if ( ! is_array( $value ) ) {
					continue;
				}
				$label       = isset( $value[2] ) ? $value[2] : '';
				$field_value = isset( $value[1] ) ? $value[1] : '';
				$all_fields_content[] = $this->format_body_field_line( $label, $field_value, $is_html );
			}
			$all_fields_content = implode( "\n", $all_fields_content );

			$processed_message = str_replace( '[all-fields]', $all_fields_content, $email_fields );

			$processed_message = preg_replace_callback(
				'/\[id="([^"]+)"\]/',
				$replace_shortcode_with_value,
				$processed_message
			);
		} else {
			$processed_message = preg_replace_callback(
				'/\[id="([^"]+)"\]/',
				$replace_shortcode_with_labeled_value,
				$email_fields
			);
		}
		
		$meta_keys = get_option( 'wpr_meta_keys_' . $form_id );
		$meta_fields = [];

		if ( is_array( $meta_keys ) ) {
			foreach ( $meta_keys as $metadata_type ) {
				switch ( $metadata_type ) {
					case 'date':
						$meta_fields['date'] = [
							'title' => __( 'Date', 'wpr-addons' ),
							'value' => date_i18n( get_option( 'date_format' ) ),
						];
						break;

					case 'time':
						$meta_fields['time'] = [
							'title' => __( 'Time', 'wpr-addons' ),
							'value' => date_i18n( get_option( 'time_format' ) ),
						];
						break;

					case 'page_url':
						$referrer_url = get_option( 'wpr_referrer_' . $form_id ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
						$meta_fields['page_url'] = [
							'title' => __( 'Page URL', 'wpr-addons' ),
							'value' => $referrer_url ? esc_url( $referrer_url ) : '',
						];
						break;

					case 'page_title':
						$referrer_title = get_option( 'wpr_referrer_title_' . $form_id ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
						$meta_fields['page_title'] = [
							'title' => __( 'Page Title', 'wpr-addons' ),
							'value' => $referrer_title ? sanitize_text_field( $referrer_title ) : '',
						];
						break;

					case 'user_agent':
						$meta_fields['user_agent'] = [
							'title' => __( 'User Agent', 'wpr-addons' ),
							'value' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_textarea_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
						];
						break;

					case 'remote_ip':
						$meta_fields['remote_ip'] = [
							'title' => __( 'Remote IP', 'wpr-addons' ),
							'value' => Utilities::get_client_ip(),
						];
						break;

					case 'credit':
						$meta_fields['credit'] = [
							'title' => __( 'Powered by', 'wpr-addons' ),
							'value' => __( 'Royal Addons', 'wpr-addons' ),
						];
						break;
				}
			}
		}

		$email_meta = [];

		foreach ( $meta_fields as $value ) {
			$email_meta[] = $this->format_body_field_line( $value['title'], $value['value'], $is_html );
		}

        $to = get_option( 'wpr_email_to_' . $form_id );

		$to = preg_replace_callback(
			'/\[id="(\w+)"\]/',
			function ( $matches ) use ( $form_content ) {
				return $this->get_field_value( $matches[1], $form_content );
			},
			$to
		);

        $subject = get_option( 'wpr_email_subject_' . $form_id );

		$subject = preg_replace_callback(
			'/\[id="(\w+)"\]/',
			function ( $matches ) use ( $form_content ) {
				return $this->get_field_value( $matches[1], $form_content );
			},
			$subject
		);

		if ( $processed_message ) {
			$message_body[] = $processed_message;
		}
		
		// Prepare the message body with correct line breaks
		if ($content_type === 'html') {
			// Replace all instances of \n with <br> in each message body item
			foreach ($message_body as &$item) {
				$item = nl2br($item);
			}
			unset($item); // Break the reference with the last element
		}

        $body = implode($line_break, $message_body) . $line_break . '-----' . $line_break . implode($line_break, $email_meta);

		$cc_header = '';
		if ( ! empty( get_option( 'wpr_cc_header_' . $form_id ) ) ) {
			$cc_header = 'Cc: ' . get_option( 'wpr_cc_header_' . $form_id );

			$cc_header = preg_replace_callback(
				'/\[id="(\w+)"\]/',
				function ( $matches ) use ( $form_content ) {
					return $this->get_field_value( $matches[1], $form_content );
				},
				$cc_header
			);
		}

		$bcc_header = '';
		if ( ! empty( get_option( 'wpr_bcc_header_' . $form_id ) ) ) {
			$bcc_header = 'Bcc: ' . get_option( 'wpr_bcc_header_' . $form_id );

			$bcc_header = preg_replace_callback(
				'/\[id="(\w+)"\]/',
				function ( $matches ) use ( $form_content ) {
					return $this->get_field_value( $matches[1], $form_content );
				},
				$bcc_header
			);
		}
		
		$email_from_name = '';
		$email_from_mail = '';
		$reply_to        = '';

		if ( ! empty( get_option( 'wpr_reply_to_' . $form_id ) ) && ! empty( get_option( 'wpr_email_from_name_' . $form_id ) ) && ! empty( get_option( 'wpr_email_from_' . $form_id ) ) ) {
			
			preg_match_all( '/id="([^"]+)"/', get_option( 'wpr_reply_to_' . $form_id ), $matche );
			$reply_to_field_id = $matche[1];
			
			preg_match_all( '/id="([^"]+)"/', get_option( 'wpr_email_from_name_' . $form_id ), $matche );
			$email_from_name_field_id = $matche[1];
			
			preg_match_all( '/id="([^"]+)"/', get_option( 'wpr_email_from_' . $form_id ), $matche );
			$email_from_field_id = $matche[1];

			foreach ( $form_content as $key => $value ) {
				if ( ! is_array( $value ) ) {
					continue;
				}
				$key_parts = explode( '-', (string) $key );
				$last_part = end( $key_parts );

				if ( in_array( $last_part, $reply_to_field_id, true ) ) {
					$reply_to_address = $this->get_field_value( $last_part, $form_content );
				}

				if ( in_array( $last_part, $email_from_name_field_id, true ) ) {
					$email_from_name = $this->get_field_value( $last_part, $form_content );
				}

				if ( in_array( $last_part, $email_from_field_id, true ) ) {
					$email_from_mail = $this->get_field_value( $last_part, $form_content );
				}
			}

			if ( ! isset( $reply_to_address ) || empty( $reply_to_address ) ) {
				$reply_to_address = get_option( 'wpr_reply_to_' . $form_id );
			}

			if ( ! isset( $email_from_name ) || empty( $email_from_name ) ) {
				$email_from_name = get_option( 'wpr_email_from_name_' . $form_id );
			}

			if ( ! isset( $email_from_mail ) || empty( $email_from_mail ) ) {
				$email_from_mail = get_option( 'wpr_email_from_' . $form_id );
			}
			
			$reply_to = 'Reply-To: ' . $reply_to_address;
		}
		
		$email_from = sprintf( 'From: %s <%s>' . "\r\n", $email_from_name, $email_from_mail );
		
		$headers = array('Content-Type: text/'. $content_type .'; charset=UTF-8', $email_from, $cc_header, $bcc_header, $reply_to);
      
        // Send email using wp_mail() function
        $sent = wp_mail( $to, $subject, $body, $headers);

        if ( $sent ) {
			wp_send_json_success(array(
				'action' => 'wpr_form_builder_email',
				'message' => esc_html__('Message sent successfully', 'wpr-addons'),
				'status' => 'success',
				'details' => json_encode($message_body)
			));
        } else {
			wp_send_json_error(array(
				'action' => 'wpr_form_builder_email',
				'message' => esc_html__('Message could not be sent', 'wpr-addons'),
				'status' => 'error',
				'details' => json_encode($message_body)
			));
        }
    }
	
	/**
	 * Escape a scalar string for the notification email body.
	 *
	 * @param mixed $text    Raw text.
	 * @param bool  $is_html Whether the email is sent as HTML.
	 * @return string
	 */
	protected function format_body_text( $text, $is_html ) {
		$text = (string) $text;

		if ( $is_html ) {
			return esc_html( $text );
		}

		return $text;
	}

	/**
	 * Escape a submitted field value for the notification email body.
	 *
	 * @param mixed $value   Field value (string or list of strings).
	 * @param bool  $is_html Whether the email is sent as HTML.
	 * @return string
	 */
	protected function format_body_value( $value, $is_html ) {
		if ( is_array( $value ) ) {
			$value = $this->chosen_values_from_field( $value );
			$parts = [];
			foreach ( $value as $item ) {
				if ( is_array( $item ) ) {
					continue;
				}
				$parts[] = $this->format_body_text( $item, $is_html );
			}
			return implode( "\n", $parts );
		}

		return $this->format_body_text( $value, $is_html );
	}

	/**
	 * Reduce checkbox/radio payloads to selected values only.
	 *
	 * JS sends [ optionValue, isChecked, name, id ] per option.
	 *
	 * @param array $value Field value list.
	 * @return array
	 */
	protected function chosen_values_from_field( $value ) {
		if ( empty( $value ) ) {
			return [];
		}

		$first = reset( $value );
		if ( ! is_array( $first ) || ! array_key_exists( 0, $first ) || ! array_key_exists( 1, $first ) ) {
			return $value;
		}

		$chosen = [];
		foreach ( $value as $item ) {
			if ( ! is_array( $item ) ) {
				$chosen[] = $item;
				continue;
			}

			$checked = isset( $item[1] ) ? $item[1] : false;
			if ( true === $checked || 1 === $checked || '1' === $checked || 'true' === $checked ) {
				$chosen[] = isset( $item[0] ) ? $item[0] : '';
			}
		}

		return $chosen;
	}

	/**
	 * Format a "Label: value" line for the notification email body.
	 *
	 * @param mixed $label   Field label.
	 * @param mixed $value   Field value.
	 * @param bool  $is_html Whether the email is sent as HTML.
	 * @return string
	 */
	protected function format_body_field_line( $label, $value, $is_html ) {
		return $this->format_body_text( trim( (string) $label ), $is_html ) . ': ' . $this->format_body_value( $value, $is_html );
	}

	/**
	 * Get a submitted field value for email headers (not HTML).
	 *
	 * @param string $field_id     Field ID from an [id="..."] shortcode.
	 * @param array  $form_content Unslashed form_content POST payload.
	 * @return string
	 */
	public function get_field_value( $field_id, $form_content = [] ) {
		if ( ! is_array( $form_content ) ) {
			$form_content = [];
		}

		foreach ( $form_content as $key => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$key_parts = explode( '-', (string) $key );
			$last_part = end( $key_parts );

			if ( $last_part === $field_id ) {
				$value = isset( $field[1] ) ? $field[1] : '';
				if ( is_array( $value ) ) {
					$value = implode( ', ', $value );
				}
				return str_replace( [ "\r", "\n" ], '', (string) $value );
			}
		}

		return '';
	}
 }

 new WPR_Send_Email();