<?php
namespace WprAddons\Classes\Modules\Forms;

use Elementor\Utils;
use WprAddons\Classes\Utilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WPR_File_Upload setup
 *
 * @since 3.4.6
 */

 class WPR_File_Upload {

	const TOKEN_TTL          = 6 * HOUR_IN_SECONDS;
	const RATE_LIMIT_PER_IP  = 30;
	const RATE_LIMIT_WINDOW  = HOUR_IN_SECONDS;

	public function __construct() {
		add_action('wp_ajax_wpr_addons_upload_file', [$this, 'handle_file_upload']);
		add_action('wp_ajax_nopriv_wpr_addons_upload_file', [$this, 'handle_file_upload']);
	}

	public function handle_file_upload() {
		// Public nonce — first line of defence only.
		if ( ! isset( $_POST['wpr_addons_nonce'] ) || ! wp_verify_nonce( $_POST['wpr_addons_nonce'], 'wpr-addons-js' ) ) {
			wp_send_json_error( ['message' => esc_html__( 'Security check failed.', 'wpr-addons' )], 403 );
		}

		// Per-render HMAC token: bound to post_id, field_id, allowed types, max size, expiry.
		$token         = isset( $_POST['upload_token'] ) ? sanitize_text_field( wp_unslash( $_POST['upload_token'] ) ) : '';
		$form_field_id = isset( $_POST['form_field_id'] ) ? sanitize_text_field( wp_unslash( $_POST['form_field_id'] ) ) : '';

		$payload = self::verify_upload_token( $token );
		if ( ! is_array( $payload ) || $form_field_id === '' || ! hash_equals( (string) ( $payload['f'] ?? '' ), $form_field_id ) ) {
			wp_send_json_error( ['message' => esc_html__( 'Permission denied.', 'wpr-addons' )], 403 );
		}

		// Verify the bound post is still viewable (skip for theme-builder/non-singular contexts where p=0).
		$bound_post_id = isset( $payload['p'] ) ? (int) $payload['p'] : 0;
		if ( $bound_post_id > 0 ) {
			$status = get_post_status( $bound_post_id );
			if ( $status && ! in_array( $status, ['publish', 'private'], true ) ) {
				wp_send_json_error( ['message' => esc_html__( 'Permission denied.', 'wpr-addons' )], 403 );
			}
		}

		// Per-IP rate limit.
		$ip_key = 'wpr_upload_rl_' . md5( (string) Utilities::get_client_ip() );
		$rate   = (int) get_transient( $ip_key );
		if ( $rate >= self::RATE_LIMIT_PER_IP ) {
			wp_send_json_error( ['message' => esc_html__( 'Too many requests.', 'wpr-addons' )], 429 );
		}
		set_transient( $ip_key, $rate + 1, self::RATE_LIMIT_WINDOW );

		// Server-trusted constraints from the signed token (ignore any client overrides).
		$max_file_size = isset( $payload['s'] ) && (float) $payload['s'] > 0
			? (float) $payload['s']
			: ( wp_max_upload_size() / pow( 1024, 2 ) ); // MB
		$allowed_file_types = isset( $payload['t'] ) ? (string) $payload['t'] : '';

		if ( ! isset( $_FILES['uploaded_file'] ) ) {
			if ( isset( $_POST['triggering_event'] ) && 'click' === $_POST['triggering_event'] ) {
				$upload_dir  = wp_upload_dir();
				$upload_path = $upload_dir['basedir'] . '/wpr-addons/forms';
				wp_mkdir_p( $upload_path );
				$this->harden_upload_dir( $upload_path );
			}
			wp_send_json_error( ['message' => esc_html__( 'No file was uploaded.', 'wpr-addons' )] );
		}

		$file = $_FILES['uploaded_file'];

		if ( ! empty( $file['error'] ) || empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			wp_send_json_error( ['message' => esc_html__( 'Upload error.', 'wpr-addons' )] );
		}

		if ( $file['size'] > $max_file_size * 1024 * 1024 ) {
			wp_send_json_error([
				'cause'   => 'filesize',
				'sizes'   => [ $max_file_size * 1024 * 1024, $file['size'] ],
				'message' => 'File size exceeds the allowed limit.'
			]);
		}

		if ( ! $this->file_validity( $file, $allowed_file_types ) ) {
			wp_send_json_error([
				'cause'   => 'filetype',
				'message' => esc_html__( 'File type is not valid.', 'wpr-addons' )
			]);
		}

		// Validation-only round-trip (no move).
		if ( ! isset( $_POST['triggering_event'] ) || 'click' !== $_POST['triggering_event'] ) {
			wp_send_json_success( ['message' => esc_html__( 'File validation passed', 'wpr-addons' )] );
		}

		$upload_dir  = wp_upload_dir();
		$upload_path = $upload_dir['basedir'] . '/wpr-addons/forms';
		wp_mkdir_p( $upload_path );
		$this->harden_upload_dir( $upload_path );

		$safe_name = sanitize_file_name( $file['name'] );
		if ( $safe_name === '' ) {
			wp_send_json_error( ['message' => esc_html__( 'Invalid filename.', 'wpr-addons' )] );
		}
		$filename = wp_unique_filename( $upload_path, $safe_name );

		if ( move_uploaded_file( $file['tmp_name'], $upload_path . '/' . $filename ) ) {
			@chmod( $upload_path . '/' . $filename, 0644 );
			wp_send_json_success( ['url' => $upload_dir['baseurl'] . '/wpr-addons/forms/' . $filename] );
		}

		wp_send_json_error( ['message' => esc_html__( 'Failed to upload the file.', 'wpr-addons' )] );
	}

	private function file_validity( $file, $allowed_file_types_csv = '' ) {
		$whitelist = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'ppt', 'pptx', 'odt', 'avi', 'ogg', 'm4a', 'mov', 'mp3', 'mp4', 'mpg', 'wav', 'wmv', 'txt'];

		if ( empty( $allowed_file_types_csv ) ) {
			$allowed_file_types_csv = implode( ',', $whitelist );
		}

		// Extension check via WP.
		$ft = wp_check_filetype( $file['name'] );
		if ( empty( $ft['ext'] ) ) {
			return false;
		}

		$f_extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

		$allowed = array_map( 'strtolower', array_map( 'trim', explode( ',', $allowed_file_types_csv ) ) );

		if ( ! in_array( $f_extension, $allowed, true )
			|| ! in_array( $f_extension, $whitelist, true )
			|| in_array( $f_extension, $this->get_exclusion_list(), true ) ) {
			return false;
		}

		// MIME check against actual file contents (defends against polyglots / spoofed extensions).
		$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
		if ( empty( $check['ext'] ) || empty( $check['type'] ) ) {
			return false;
		}

		return true;
	}

	private function get_exclusion_list() {
		static $exclusionlist = false;
		if ( ! $exclusionlist ) {
			$exclusionlist = [
				'php', 'php3', 'php4', 'php5', 'php6', 'phps', 'php7', 'phtml', 'phar',
				'shtml', 'pht', 'swf', 'html', 'htm', 'hta',
				'asp', 'aspx', 'cmd', 'csh', 'bat', 'jar', 'exe', 'com',
				'js', 'lnk', 'htaccess', 'htpasswd',
				'ps1', 'ps2', 'py', 'rb', 'pl', 'tmp', 'cgi',
				'svg', 'svgz'
			];
		}

		return $exclusionlist;
	}

	/**
	 * Drop deny-execute .htaccess and empty index.html into the uploads folder.
	 */
	private function harden_upload_dir( $path ) {
		$htaccess = $path . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			$rules  = "# Royal Addons: deny script execution and listing\n";
			$rules .= "Options -Indexes\n";
			$rules .= "<IfModule mod_php.c>\nphp_flag engine off\n</IfModule>\n";
			$rules .= "<IfModule mod_php5.c>\nphp_flag engine off\n</IfModule>\n";
			$rules .= "<IfModule mod_php7.c>\nphp_flag engine off\n</IfModule>\n";
			$rules .= "<IfModule mod_php8.c>\nphp_flag engine off\n</IfModule>\n";
			$rules .= "<FilesMatch \"\\.(php|php3|php4|php5|php6|php7|phtml|phar|pl|py|jsp|asp|aspx|sh|cgi|svg|svgz|html?|hta|htaccess|htpasswd)$\">\n";
			$rules .= "  Require all denied\n";
			$rules .= "  <IfModule !mod_authz_core.c>\n    Deny from all\n  </IfModule>\n";
			$rules .= "</FilesMatch>\n";
			@file_put_contents( $htaccess, $rules );
		}
		$index = $path . '/index.html';
		if ( ! file_exists( $index ) ) {
			@file_put_contents( $index, '' );
		}
	}

	/**
	 * Mint a stateless, HMAC-signed upload token for a freshly rendered upload field.
	 *
	 * @param int    $post_id            Current post being viewed (0 if non-singular).
	 * @param string $field_id           HTML id of the file input.
	 * @param string $allowed_types_csv  Allowed file extensions (csv).
	 * @param float  $max_size_mb        Per-file size cap (MB); 0 = server max.
	 * @return string Token (payload.signature).
	 */
	public static function mint_upload_token( $post_id, $field_id, $allowed_types_csv = '', $max_size_mb = 0 ) {
		$payload = [
			'p' => (int) $post_id,
			'f' => (string) $field_id,
			't' => (string) $allowed_types_csv,
			's' => (float) $max_size_mb,
			'e' => time() + self::TOKEN_TTL,
		];
		$payload_b64 = self::b64url_encode( wp_json_encode( $payload ) );
		$sig         = hash_hmac( 'sha256', $payload_b64, self::get_secret() );
		return $payload_b64 . '.' . $sig;
	}

	public static function verify_upload_token( $token ) {
		if ( ! is_string( $token ) || strpos( $token, '.' ) === false ) {
			return false;
		}
		list( $payload_b64, $sig ) = explode( '.', $token, 2 );
		if ( $payload_b64 === '' || $sig === '' ) {
			return false;
		}
		$expected = hash_hmac( 'sha256', $payload_b64, self::get_secret() );
		if ( ! hash_equals( $expected, $sig ) ) {
			return false;
		}
		$json    = self::b64url_decode( $payload_b64 );
		$payload = json_decode( $json, true );
		if ( ! is_array( $payload ) || empty( $payload['e'] ) || time() > (int) $payload['e'] ) {
			return false;
		}
		return $payload;
	}

	private static function get_secret() {
		static $secret = null;
		if ( null !== $secret ) {
			return $secret;
		}
		$secret = get_option( 'wpr_upload_token_secret' );
		if ( ! is_string( $secret ) || strlen( $secret ) < 32 ) {
			$secret = wp_generate_password( 64, true, true );
			update_option( 'wpr_upload_token_secret', $secret, false );
		}
		return $secret;
	}

	private static function b64url_encode( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	private static function b64url_decode( $data ) {
		$pad = strlen( $data ) % 4;
		if ( $pad ) {
			$data .= str_repeat( '=', 4 - $pad );
		}
		return base64_decode( strtr( $data, '-_', '+/' ) );
	}
 }

 new WPR_File_Upload();
