<?php

use WprAddons\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Featured Video metabox for WooCommerce products.
 * Used by the WPR Woo Grid widget to display a hover video over the featured image.
 *
 * Meta keys:
 *   wpr_featured_video_source : 'upload' | 'url'
 *   wpr_featured_video_id     : attachment id (when source = upload)
 *   wpr_featured_video_url    : external/youtube/vimeo url (when source = url)
 */

add_action( 'add_meta_boxes', 'wpr_featured_video_add_metabox' );
function wpr_featured_video_add_metabox() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	$meta_option = get_option( 'wpr_meta_featured_video_product', 'on' );
	if ( 'on' !== $meta_option ) {
		return;
	}

	add_meta_box(
		'wpr-featured-video',
		__( 'Featured Video', 'wpr-addons' ),
		'wpr_featured_video_metabox',
		'product',
		'side',
		'low'
	);
}

function wpr_featured_video_metabox( $post ) {
	$source = get_post_meta( $post->ID, 'wpr_featured_video_source', true );
	$video_id = get_post_meta( $post->ID, 'wpr_featured_video_id', true );
	$video_url = get_post_meta( $post->ID, 'wpr_featured_video_url', true );

	if ( empty( $source ) ) {
		$source = 'upload';
	}

	wp_nonce_field( 'wpr_featured_video_save', 'wpr_featured_video_nonce' );

	$attachment_url = $video_id ? wp_get_attachment_url( $video_id ) : '';
	$has_upload = ! empty( $video_id ) && ! empty( $attachment_url );

	$src_show = ['upload' => '', 'url' => ''];
	$src_show[ $source ] = 'block';
	$src_hide = 'upload' === $source ? 'url' : 'upload';
	$src_show[ $src_hide ] = 'none';
	?>
	<p style="margin-top: 6px;">
		<label for="wpr_featured_video_source" style="display:block;font-weight:600;margin-bottom:4px;">
			<?php esc_html_e( 'Video Source', 'wpr-addons' ); ?>
		</label>
		<select id="wpr_featured_video_source" name="wpr_featured_video_source" style="width:100%;">
			<option value="upload" <?php selected( $source, 'upload' ); ?>><?php esc_html_e( 'Self Hosted (Upload)', 'wpr-addons' ); ?></option>
			<option value="url" <?php selected( $source, 'url' ); ?>><?php esc_html_e( 'External URL (YouTube / Vimeo / MP4)', 'wpr-addons' ); ?></option>
		</select>
	</p>

	<div class="wpr-featured-video-upload-wrap" style="display:<?php echo esc_attr( $src_show['upload'] ); ?>;">
		<div class="wpr-featured-video-preview" style="margin-bottom:6px;">
			<?php if ( $has_upload ) : ?>
				<video src="<?php echo esc_url( $attachment_url ); ?>" controls muted preload="metadata" style="width:100%;max-height:180px;background:#000;"></video>
			<?php else : ?>
				<div class="wpr-featured-video-placeholder" style="width:100%;height:120px;background:#f1f1f1;border:1px dashed #c3c4c7;display:flex;align-items:center;justify-content:center;color:#646970;font-size:12px;">
					<?php esc_html_e( 'No video selected', 'wpr-addons' ); ?>
				</div>
			<?php endif; ?>
		</div>
		<p class="hide-if-no-js" style="margin:0;">
			<a href="javascript:;"
			   id="wpr_featured_video_upload_button"
			   class="button"
			   data-uploader_title="<?php esc_attr_e( 'Choose a video', 'wpr-addons' ); ?>"
			   data-uploader_button_text="<?php esc_attr_e( 'Set featured video', 'wpr-addons' ); ?>"
			   style="<?php echo $has_upload ? 'display:none;' : ''; ?>">
				<?php esc_html_e( 'Set featured video', 'wpr-addons' ); ?>
			</a>
			<a href="javascript:;"
			   id="wpr_featured_video_remove_button"
			   class="button"
			   style="<?php echo $has_upload ? '' : 'display:none;'; ?>">
				<?php esc_html_e( 'Remove featured video', 'wpr-addons' ); ?>
			</a>
		</p>
		<input type="hidden" id="wpr_featured_video_id" name="wpr_featured_video_id" value="<?php echo esc_attr( $video_id ); ?>" />
	</div>

	<div class="wpr-featured-video-url-wrap" style="display:<?php echo esc_attr( $src_show['url'] ); ?>;">
		<p style="margin-top:6px;">
			<label for="wpr_featured_video_url" style="display:block;font-weight:600;margin-bottom:4px;">
				<?php esc_html_e( 'Video URL', 'wpr-addons' ); ?>
			</label>
			<input type="url"
				   id="wpr_featured_video_url"
				   name="wpr_featured_video_url"
				   value="<?php echo esc_attr( $video_url ); ?>"
				   placeholder="https://www.youtube.com/watch?v=..."
				   style="width:100%;" />
		</p>
		<p style="color:#646970;font-size:11px;margin:4px 0 0;">
			<?php esc_html_e( 'Supports YouTube, Vimeo and direct MP4/WebM URLs.', 'wpr-addons' ); ?>
		</p>
	</div>
	<?php
}

add_action( 'save_post_product', 'wpr_featured_video_save', 10, 1 );
function wpr_featured_video_save( $post_id ) {
	if ( ! isset( $_POST['wpr_featured_video_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpr_featured_video_nonce'] ) ), 'wpr_featured_video_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$source = isset( $_POST['wpr_featured_video_source'] ) ? sanitize_key( $_POST['wpr_featured_video_source'] ) : 'upload';
	if ( ! in_array( $source, ['upload', 'url'], true ) ) {
		$source = 'upload';
	}
	update_post_meta( $post_id, 'wpr_featured_video_source', $source );

	$video_id = isset( $_POST['wpr_featured_video_id'] ) ? absint( $_POST['wpr_featured_video_id'] ) : 0;
	update_post_meta( $post_id, 'wpr_featured_video_id', $video_id );

	$video_url = isset( $_POST['wpr_featured_video_url'] ) ? esc_url_raw( wp_unslash( $_POST['wpr_featured_video_url'] ) ) : '';
	update_post_meta( $post_id, 'wpr_featured_video_url', $video_url );
}

add_action( 'admin_enqueue_scripts', 'wpr_featured_video_enqueue_admin' );
function wpr_featured_video_enqueue_admin( $hook ) {
	if ( ! in_array( $hook, ['post.php', 'post-new.php'], true ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && 'product' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_media();

	wp_enqueue_script(
		'wpr-featured-video-js',
		WPR_ADDONS_URL . 'assets/js/admin/metabox/featured-video.js',
		['jquery'],
		Plugin::instance()->get_version(),
		true
	);
}
