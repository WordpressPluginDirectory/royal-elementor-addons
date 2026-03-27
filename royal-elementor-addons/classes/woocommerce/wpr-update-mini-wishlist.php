<?php
namespace WprAddons\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WPR_Update_Mini_Wishlist setup
 *
 * @since 1.0
 */
class WPR_Update_Mini_Wishlist { 

    /**
    ** Constructor
    */
    public function __construct() {
        // add_action('init', [$this, 'register_wishlist_cpt']);
        add_action( 'wp_ajax_update_mini_wishlist',[$this, 'update_mini_wishlist'] );
        add_action( 'wp_ajax_nopriv_update_mini_wishlist',[$this, 'update_mini_wishlist'] );
    }

	// Add two new functions for handling cookies
	public function get_wishlist_from_cookie() {
        if (isset($_COOKIE['wpr_wishlist'])) {
            $raw = json_decode(stripslashes($_COOKIE['wpr_wishlist']), true);
            return is_array($raw) ? array_filter(array_map('absint', $raw)) : array();
        } else if ( isset($_COOKIE['wpr_wishlist_'. get_current_blog_id() .'']) ) {
            $raw = json_decode(stripslashes($_COOKIE['wpr_wishlist_'. get_current_blog_id() .'']), true);
            return is_array($raw) ? array_filter(array_map('absint', $raw)) : array();
        }
        return array();
	}
    
    function update_mini_wishlist() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wpr-addons-js' ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Security check failed.', 'wpr-addons' ) ) );
        }
        if ( ! isset( $_POST['product_id'] ) ) {
            return;
        }
        $product_id = absint( $_POST['product_id'] );
        $user_id = get_current_user_id();

        
        if ($user_id > 0) {
            $wishlist = get_user_meta($user_id, 'wpr_wishlist', true);
            if (!$wishlist) {
                $wishlist = array();
            }
        } else {
            $wishlist = $this->get_wishlist_from_cookie();
        }

        $product = wc_get_product( $product_id );
        $product_data = [];
        if ( $product ) {
            $product_data['product_url'] = $product->get_permalink();
            $product_data['product_image'] = $product->get_image();
            $product_data['product_title'] = $product->get_title();
            $product_data['product_price'] = $product->get_price_html();
            $product_data['product_id'] = $product->get_id();
            $product_data['wishlist_count'] = sizeof($wishlist);
        }

       wp_send_json($product_data);

       wp_die();
    }
}

new WPR_Update_Mini_Wishlist();