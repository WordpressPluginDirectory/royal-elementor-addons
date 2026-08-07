<?php
namespace WprAddons\Classes\Modules;

use Elementor\Group_Control_Image_Size;
use WprAddons\Classes\Utilities;
use WprAddons\Classes\Modules\WPR_Post_Likes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPR_Media_Grid_Helpers setup
 *
 * @since 3.4.6
 */
class WPR_Media_Grid_Helpers {

	public function __construct() {
		add_action( 'wp_ajax_wpr_filter_grid_media', [ $this, 'wpr_filter_grid_media' ] );
		add_action( 'wp_ajax_nopriv_wpr_filter_grid_media', [ $this, 'wpr_filter_grid_media' ] );
		add_action( 'wp_ajax_wpr_get_media_filtered_count', [ $this, 'wpr_get_media_filtered_count' ] );
		add_action( 'wp_ajax_nopriv_wpr_get_media_filtered_count', [ $this, 'wpr_get_media_filtered_count' ] );
	}

	// Get Max Pages
	public static function get_max_num_pages( $settings ) {
		if ( isset( $_POST['wpr_url_params'] ) ) {
			$query = new \WP_Query( self::get_main_query_args( $settings, [] ) );
			$max_num_pages = intval( ceil( $query->max_num_pages ) );

			wp_reset_postdata();

			return $max_num_pages;
		} elseif ( isset( $_POST['grid_settings'] ) ) {
			$query = new \WP_Query( self::get_main_query_args( $settings, [] ) );
			$max_num_pages = intval( ceil( $query->max_num_pages ) );

			$adjusted_total_posts = max( 0, $query->found_posts - $query->query_vars['offset'] );
			$number_of_pages = ceil( $adjusted_total_posts / $query->query_vars['posts_per_page'] );

			wp_send_json_success( [
				'page_count' => $number_of_pages,
				'max_num_pages' => $max_num_pages,
				'query_found' => $query->found_posts,
				'query_offset' => $query->query_vars['offset'],
				'query_num' => $query->query_vars['posts_per_page'],
			] );

			wp_reset_postdata();

			return $max_num_pages;
		}

		$query = new \WP_Query( self::get_main_query_args( $settings, [] ) );
		$max_num_pages = intval( ceil( $query->max_num_pages ) );

		wp_reset_postdata();

		return $max_num_pages;
	}

	// Main Query Args
	public static function get_main_query_args( $settings, $params ) {
		$author = ! empty( $settings['query_author'] ) ? implode( ',', $settings['query_author'] ) : '';

		if ( get_query_var( 'paged' ) ) {
			$paged = get_query_var( 'paged' );
		} elseif ( get_query_var( 'page' ) ) {
			$paged = get_query_var( 'page' );
		} else {
			$paged = 1;
		}

		if ( empty( $settings['query_offset'] ) ) {
			$settings['query_offset'] = 0;
		}

		$offset = ( $paged - 1 ) * $settings['query_posts_per_page'] + $settings['query_offset'];

		if ( ! defined( 'WPR_ADDONS_PRO_VERSION' ) || ! wpr_fs()->can_use_premium_code() ) {
			$settings['query_randomize'] = '';
			$settings['order_posts'] = 'date';
		}

		$query_order_by = '' !== $settings['query_randomize'] ? $settings['query_randomize'] : $settings['order_posts'];

		if ( 'manual' === $settings['query_selection'] ) {
			$query_order_by = 'post__in';
		}

		$args = [
			'post_type' => 'attachment',
			'post_status' => 'inherit',
			'post_mime_type' => 'image',
			'tax_query' => self::get_tax_query_args( $settings ),
			'post__not_in' => $settings['query_exclude_attachment'],
			'posts_per_page' => $settings['query_posts_per_page'],
			'orderby' => $query_order_by,
			'author' => $author,
			'paged' => $paged,
			'offset' => $offset,
		];

		if ( 'manual' === $settings['query_selection'] ) {
			$post_ids = [ '' ];
			$attachments = $settings['query_manual_attachment'];
			$attachments_count = ! empty( $attachments ) ? count( $attachments ) : 0;

			for ( $i = 0; $i < $attachments_count; $i++ ) {
				if ( ! isset( $settings['query_manual_attachment'][ $i ]['id'] ) ) {
					continue;
				}
				array_push( $post_ids, $settings['query_manual_attachment'][ $i ]['id'] );
			}

			$orderby = '' === $settings['query_randomize'] ? 'post__in' : 'rand';

			$args = [
				'post_type' => 'attachment',
				'post_status' => 'inherit',
				'post__in' => $post_ids,
				'orderby' => $orderby,
				'paged' => $paged,
				'posts_per_page' => $settings['query_posts_per_page'],
			];

			$tax_query = self::get_tax_query_args( $settings );

			if ( ! empty( $tax_query ) ) {
				$args['tax_query'] = $tax_query;
			}
		}

		if ( isset( $_POST['wpr_offset'] ) ) {
			$args['offset'] = absint( $_POST['wpr_offset'] );
		}

		if ( 'rand' !== $query_order_by && 'manual' !== $settings['query_selection'] ) {
			$args['order'] = $settings['order_direction'];
		}

		return $args;
	}

	// Taxonomy Query Args
	public static function get_tax_query_args( $settings ) {
		$tax_query = [];

		if ( ! empty( $_POST['wpr_taxonomy'] ) && ! empty( $_POST['wpr_filter'] ) && 'all' !== $_POST['wpr_filter'] && '*' !== $_POST['wpr_taxonomy'] ) {
			$taxonomy = sanitize_text_field( wp_unslash( $_POST['wpr_taxonomy'] ) );

			if ( 'tag' === $taxonomy ) {
				$taxonomy = 'post_tag';
			}

			$tax_query[] = [
				'taxonomy' => $taxonomy,
				'field' => 'slug',
				'terms' => sanitize_text_field( wp_unslash( $_POST['wpr_filter'] ) ),
			];

			return $tax_query;
		}

		foreach ( get_object_taxonomies( 'attachment' ) as $tax ) {
			if ( ! empty( $settings[ 'query_taxonomy_' . $tax ] ) ) {
				$tax_query[] = [
					'taxonomy' => $tax,
					'field' => 'id',
					'terms' => $settings[ 'query_taxonomy_' . $tax ],
				];
			}
		}

		return $tax_query;
	}

	// Get Animation Class
	public static function get_animation_class( $data, $object ) {
		$class = '';

		if ( 'none' !== $data[ $object . '_animation' ] ) {
			$class .= ' wpr-' . $object . '-' . $data[ $object . '_animation' ];
			$class .= ' wpr-anim-size-' . $data[ $object . '_animation_size' ];
			$class .= ' wpr-anim-timing-' . $data[ $object . '_animation_timing' ];

			if ( 'yes' === $data[ $object . '_animation_tr' ] ) {
				$class .= ' wpr-anim-transparency';
			}
		}

		return $class;
	}

	// Get Image Effect Class
	public static function get_image_effect_class( $settings ) {
		$class = '';

		if ( ! defined( 'WPR_ADDONS_PRO_VERSION' ) || ! wpr_fs()->can_use_premium_code() ) {
			if ( in_array( $settings['image_effects'], [ 'pro-zi', 'pro-zo', 'pro-go', 'pro-bo' ], true ) ) {
				$settings['image_effects'] = 'none';
			}
		}

		if ( 'none' !== $settings['image_effects'] ) {
			$class .= ' wpr-' . $settings['image_effects'];
		}

		if ( 'slide' !== $settings['image_effects'] ) {
			$class .= ' wpr-effect-size-' . $settings['image_effects_size'];
		} else {
			$class .= ' wpr-effect-dir-' . $settings['image_effects_direction'];
		}

		return $class;
	}

	// Render Post Thumbnail
	public static function render_post_thumbnail( $settings ) {
		$id = get_the_ID();
		$src = Group_Control_Image_Size::get_attachment_image_src( $id, 'layout_image_crop', $settings );
		$alt = '' === wp_get_attachment_caption( $id ) ? get_the_title() : wp_get_attachment_caption( $id );

		echo '<div class="wpr-grid-image-wrap" data-src="' . esc_url( wp_get_attachment_url( $id ) ) . '">';
			echo '<img src="' . esc_url( $src ) . '" alt="' . esc_attr( $alt ) . '" class="wpr-anim-timing-' . esc_attr( $settings['image_effects_animation_timing'] ) . '">';
		echo '</div>';
	}

	// Render Media Overlay
	public static function render_media_overlay( $settings ) {
		echo '<div class="wpr-grid-media-hover-bg ' . esc_attr( self::get_animation_class( $settings, 'overlay' ) ) . '" data-url="' . esc_url( get_the_permalink( get_the_ID() ) ) . '">';

			if ( defined( 'WPR_ADDONS_PRO_VERSION' ) && wpr_fs()->can_use_premium_code() ) {
				if ( '' !== $settings['overlay_image']['url'] ) {
					echo '<img src="' . esc_url( $settings['overlay_image']['url'] ) . '">';
				}
			}

		echo '</div>';
	}

	// Render Post Title
	public static function render_post_title( $settings, $class, $general_settings = [] ) {
		$title_pointer = ! defined( 'WPR_ADDONS_PRO_VERSION' ) || ! wpr_fs()->can_use_premium_code() ? 'none' : $general_settings['title_pointer'];
		$title_pointer_animation = ! defined( 'WPR_ADDONS_PRO_VERSION' ) || ! wpr_fs()->can_use_premium_code() ? 'fade' : $general_settings['title_pointer_animation'];
		$pointer_item_class = ( isset( $general_settings['title_pointer'] ) && 'none' !== $general_settings['title_pointer'] ) ? 'class="wpr-pointer-item"' : '';

		$class .= ' wpr-pointer-' . $title_pointer;
		$class .= ' wpr-pointer-line-fx wpr-pointer-fx-' . $title_pointer_animation;

		$tags_whitelist = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p' ];
		$element_title_tag = Utilities::validate_html_tags_wl( $settings['element_title_tag'], 'h2', $tags_whitelist );

		echo '<' . esc_attr( $element_title_tag ) . ' class="' . esc_attr( $class ) . '">';
			echo '<div class="inner-block">';
				if ( 'yes' === $settings['element_disable_link'] ) {
					echo '<span ' . $pointer_item_class . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo esc_html( wp_trim_words( get_the_title(), $settings['element_word_count'] ) );
					echo '</span>';
				} else {
					echo '<a ' . $pointer_item_class . ' href="' . esc_url( get_the_permalink() ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo esc_html( wp_trim_words( get_the_title(), $settings['element_word_count'] ) );
					echo '</a>';
				}
			echo '</div>';
		echo '</' . esc_attr( $element_title_tag ) . '>';
	}

	// Render Post Excerpt
	public static function render_post_excerpt( $settings, $class ) {
		$dropcap_class = 'yes' === $settings['element_dropcap'] ? ' wpr-enable-dropcap' : '';
		$class .= $dropcap_class;

		if ( '' === get_the_excerpt() ) {
			return;
		}

		echo '<div class="' . esc_attr( $class ) . '">';
			echo '<div class="inner-block">';
				echo '<p>' . esc_html( wp_trim_words( get_the_excerpt(), $settings['element_word_count'] ) ) . '</p>';
			echo '</div>';
		echo '</div>';
	}

	// Render Post Date
	public static function render_post_date( $settings, $class ) {
		echo '<div class="' . esc_attr( $class ) . '">';
			echo '<div class="inner-block">';
				echo '<span>';
				if ( 'before' === $settings['element_extra_text_pos'] ) {
					echo '<span class="wpr-grid-extra-text-left">' . esc_html( $settings['element_extra_text'] ) . '</span>';
				}
				if ( 'before' === $settings['element_extra_icon_pos'] ) {
					ob_start();
					\Elementor\Icons_Manager::render_icon( $settings['element_extra_icon'], [ 'aria-hidden' => 'true' ] );
					$extra_icon = ob_get_clean();

					echo '<span class="wpr-grid-extra-icon-left">';
						echo $extra_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor Icons_Manager trusted markup.
					echo '</span>';
				}

				echo esc_html( apply_filters( 'the_date', get_the_date( '' ), get_option( 'date_format' ), '', '' ) );

				if ( 'after' === $settings['element_extra_icon_pos'] ) {
					ob_start();
					\Elementor\Icons_Manager::render_icon( $settings['element_extra_icon'], [ 'aria-hidden' => 'true' ] );
					$extra_icon = ob_get_clean();

					echo '<span class="wpr-grid-extra-icon-right">';
						echo $extra_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor Icons_Manager trusted markup.
					echo '</span>';
				}
				if ( 'after' === $settings['element_extra_text_pos'] ) {
					echo '<span class="wpr-grid-extra-text-right">' . esc_html( $settings['element_extra_text'] ) . '</span>';
				}
				echo '</span>';
			echo '</div>';
		echo '</div>';
	}

	// Render Post Time
	public static function render_post_time( $settings, $class ) {
		echo '<div class="' . esc_attr( $class ) . '">';
			echo '<div class="inner-block">';
				echo '<span>';
				if ( 'before' === $settings['element_extra_text_pos'] ) {
					echo '<span class="wpr-grid-extra-text-left">' . esc_html( $settings['element_extra_text'] ) . '</span>';
				}
				if ( 'before' === $settings['element_extra_icon_pos'] ) {
					ob_start();
					\Elementor\Icons_Manager::render_icon( $settings['element_extra_icon'], [ 'aria-hidden' => 'true' ] );
					$extra_icon = ob_get_clean();

					echo '<span class="wpr-grid-extra-icon-left">';
						echo $extra_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor Icons_Manager trusted markup.
					echo '</span>';
				}

				echo esc_html( get_the_time( '' ) );

				if ( 'after' === $settings['element_extra_icon_pos'] ) {
					ob_start();
					\Elementor\Icons_Manager::render_icon( $settings['element_extra_icon'], [ 'aria-hidden' => 'true' ] );
					$extra_icon = ob_get_clean();

					echo '<span class="wpr-grid-extra-icon-right">';
						echo $extra_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor Icons_Manager trusted markup.
					echo '</span>';
				}
				if ( 'after' === $settings['element_extra_text_pos'] ) {
					echo '<span class="wpr-grid-extra-text-right">' . esc_html( $settings['element_extra_text'] ) . '</span>';
				}
				echo '</span>';
			echo '</div>';
		echo '</div>';
	}

	// Render Post Author
	public static function render_post_author( $settings, $class ) {
		$author_id = get_post_field( 'post_author' );

		echo '<div class="' . esc_attr( $class ) . '">';
			echo '<div class="inner-block">';
				if ( 'before' === $settings['element_extra_text_pos'] ) {
					echo '<span class="wpr-grid-extra-text-left">' . esc_html( $settings['element_extra_text'] ) . '</span>';
				}

				echo '<a href="' . esc_url( get_author_posts_url( $author_id ) ) . '">';

				if ( 'before' === $settings['element_extra_icon_pos'] ) {
					ob_start();
					\Elementor\Icons_Manager::render_icon( $settings['element_extra_icon'], [ 'aria-hidden' => 'true' ] );
					$extra_icon = ob_get_clean();

					echo '<span class="wpr-grid-extra-icon-left">';
						echo $extra_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor Icons_Manager trusted markup.
					echo '</span>';
				}
					if ( 'yes' === $settings['element_show_avatar'] ) {
						echo get_avatar( $author_id, $settings['element_avatar_size'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}

					echo '<span>' . esc_html( get_the_author_meta( 'display_name', $author_id ) ) . '</span>';

				if ( 'after' === $settings['element_extra_icon_pos'] ) {
					ob_start();
					\Elementor\Icons_Manager::render_icon( $settings['element_extra_icon'], [ 'aria-hidden' => 'true' ] );
					$extra_icon = ob_get_clean();

					echo '<span class="wpr-grid-extra-icon-right">';
						echo $extra_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor Icons_Manager trusted markup.
					echo '</span>';
				}
				echo '</a>';

				if ( 'after' === $settings['element_extra_text_pos'] ) {
					echo '<span class="wpr-grid-extra-text-right">' . esc_html( $settings['element_extra_text'] ) . '</span>';
				}
			echo '</div>';
		echo '</div>';
	}

	public static function render_post_likes( $settings, $class, $post_id ) {
		if ( ! defined( 'WPR_ADDONS_PRO_VERSION' ) || ! wpr_fs()->can_use_premium_code() ) {
			return;
		}

		$post_likes = new WPR_Post_Likes();

		echo '<div class="' . esc_attr( $class ) . '">';
			echo '<div class="inner-block">';
				if ( 'before' === $settings['element_extra_text_pos'] ) {
					echo '<span class="wpr-grid-extra-text-left">' . esc_html( $settings['element_extra_text'] ) . '</span>';
				}

				echo $post_likes->get_button( $post_id, $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WPR_Post_Likes::get_button() returns pre-escaped HTML.

				if ( 'after' === $settings['element_extra_text_pos'] ) {
					echo '<span class="wpr-grid-extra-text-right">' . esc_html( $settings['element_extra_text'] ) . '</span>';
				}
			echo '</div>';
		echo '</div>';
	}

	public static function render_post_sharing_icons( $settings, $class ) {
		if ( ! defined( 'WPR_ADDONS_PRO_VERSION' ) || ! wpr_fs()->can_use_premium_code() ) {
			return;
		}

		$args = [
			'icons' => 'yes',
			'tooltip' => $settings['element_sharing_tooltip'],
			'url' => esc_url( get_permalink( get_queried_object_id() ) ),
			'title' => esc_html( get_the_title() ),
			'text' => esc_html( get_the_excerpt() ),
			'image' => esc_url( wp_get_attachment_url( get_the_ID() ) ),
		];

		$hidden_class = '';

		echo '<div class="' . esc_attr( $class ) . '">';
			echo '<div class="inner-block">';
				if ( 'before' === $settings['element_extra_text_pos'] ) {
					echo '<span class="wpr-grid-extra-text-left">' . esc_html( $settings['element_extra_text'] ) . '</span>';
				}

				echo '<span class="wpr-post-sharing">';

					if ( 'yes' === $settings['element_sharing_trigger'] ) {
						$hidden_class = ' wpr-sharing-hidden';
						$attributes  = ' data-action="' . esc_attr( $settings['element_sharing_trigger_action'] ) . '"';
						$attributes .= ' data-direction="' . esc_attr( $settings['element_sharing_trigger_direction'] ) . '"';

						echo '<a class="wpr-sharing-trigger wpr-sharing-icon"' . $attributes . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							if ( 'yes' === $settings['element_sharing_tooltip'] ) {
								echo '<span class="wpr-sharing-tooltip wpr-tooltip">' . esc_html__( 'Share', 'wpr-addons' ) . '</span>';
							}

							echo Utilities::get_wpr_icon( $settings['element_sharing_trigger_icon'], '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo '</a>';
					}

					echo '<span class="wpr-post-sharing-inner' . esc_attr( $hidden_class ) . '">';

					for ( $i = 1; $i < 7; $i++ ) {
						$args['network'] = $settings[ 'element_sharing_icon_' . $i ];
						echo Utilities::get_post_sharing_icon( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}

					echo '</span>';

				echo '</span>';

				if ( 'after' === $settings['element_extra_text_pos'] ) {
					echo '<span class="wpr-grid-extra-text-right">' . esc_html( $settings['element_extra_text'] ) . '</span>';
				}
			echo '</div>';
		echo '</div>';
	}

	// Render Post Lightbox
	public static function render_post_lightbox( $settings, $class, $post_id ) {
		echo '<div class="' . esc_attr( $class ) . '">';
			echo '<div class="inner-block">';
				$lightbox_source = get_the_post_thumbnail_url( $post_id );

				if ( 'audio' === get_post_format() ) {
					if ( 'meta' === $settings['element_lightbox_pfa_select'] ) {
						$utilities = new Utilities();
						$meta_value = get_post_meta( $post_id, $settings['element_lightbox_pfa_meta'], true );

						if ( false === strpos( $meta_value, '<iframe ' ) ) {
							add_filter( 'oembed_result', [ $utilities, 'filter_oembed_results' ], 50, 3 );
								$track_url = wp_oembed_get( $meta_value );
							remove_filter( 'oembed_result', [ $utilities, 'filter_oembed_results' ], 50 );
						} else {
							$track_url = Utilities::filter_oembed_results( $meta_value );
						}

						$lightbox_source = $track_url;
					}
				} elseif ( 'video' === get_post_format() ) {
					if ( 'meta' === $settings['element_lightbox_pfv_select'] ) {
						$meta_value = get_post_meta( $post_id, $settings['element_lightbox_pfv_meta'], true );

						if ( false === strpos( $meta_value, '<iframe ' ) ) {
							$video = \Elementor\Embed::get_video_properties( $meta_value );
						} else {
							$video = \Elementor\Embed::get_video_properties( Utilities::filter_oembed_results( $meta_value ) );
						}

						if ( 'youtube' === $video['provider'] ) {
							$video_url = '//www.youtube.com/embed/' . $video['video_id'] . '?feature=oembed&autoplay=1&controls=1';
						} elseif ( 'vimeo' === $video['provider'] ) {
							$video_url = 'https://player.vimeo.com/video/' . $video['video_id'] . '?autoplay=1#t=0';
						}

						if ( isset( $video_url ) ) {
							$lightbox_source = $video_url;
						}
					}
				}

				if ( false === $lightbox_source ) {
					$lightbox_source = wp_get_attachment_url( $post_id );
				}

				echo '<span data-src="' . esc_url( $lightbox_source ) . '">';

					if ( 'before' === $settings['element_extra_text_pos'] ) {
						echo '<span class="wpr-grid-extra-text-left">' . esc_html( $settings['element_extra_text'] ) . '</span>';
					}

					echo '<i class="' . esc_attr( $settings['element_extra_icon']['value'] ) . '"></i>';

					if ( 'after' === $settings['element_extra_text_pos'] ) {
						echo '<span class="wpr-grid-extra-text-right">' . esc_html( $settings['element_extra_text'] ) . '</span>';
					}

				echo '</span>';

				if ( 'yes' === $settings['element_lightbox_overlay'] ) {
					echo '<div class="wpr-grid-lightbox-overlay"></div>';
				}
			echo '</div>';
		echo '</div>';
	}

	public static function render_post_element_separator( $settings, $class ) {
		echo '<div class="' . esc_attr( $class . ' ' . $settings['element_separator_style'] ) . '">';
			echo '<div class="inner-block"><span></span></div>';
		echo '</div>';
	}

	public static function render_post_taxonomies( $settings, $class, $post_id, $general_settings = [] ) {
		$terms = wp_get_post_terms( $post_id, $settings['element_select'] );
		$count = 0;

		$tax1_pointer = ! defined( 'WPR_ADDONS_PRO_VERSION' ) || ! wpr_fs()->can_use_premium_code() ? 'none' : $general_settings['tax1_pointer'];
		$tax1_pointer_animation = ! defined( 'WPR_ADDONS_PRO_VERSION' ) || ! wpr_fs()->can_use_premium_code() ? 'fade' : $general_settings['tax1_pointer_animation'];
		$tax2_pointer = ! defined( 'WPR_ADDONS_PRO_VERSION' ) || ! wpr_fs()->can_use_premium_code() ? 'none' : $general_settings['tax2_pointer'];
		$tax2_pointer_animation = ! defined( 'WPR_ADDONS_PRO_VERSION' ) || ! wpr_fs()->can_use_premium_code() ? 'fade' : $general_settings['tax2_pointer_animation'];
		$pointer_item_class = ( ( isset( $general_settings['tax1_pointer'] ) && 'none' !== $general_settings['tax1_pointer'] ) || ( isset( $general_settings['tax2_pointer'] ) && 'none' !== $general_settings['tax2_pointer'] ) ) ? 'class="wpr-pointer-item"' : '';

		if ( 'wpr-grid-tax-style-1' === $settings['element_tax_style'] ) {
			$class .= ' wpr-pointer-' . $tax1_pointer;
			$class .= ' wpr-pointer-line-fx wpr-pointer-fx-' . $tax1_pointer_animation;
		} else {
			$class .= ' wpr-pointer-' . $tax2_pointer;
			$class .= ' wpr-pointer-line-fx wpr-pointer-fx-' . $tax2_pointer_animation;
		}

		echo '<div class="' . esc_attr( $class . ' ' . $settings['element_tax_style'] ) . '">';
			echo '<div class="inner-block">';
				if ( 'before' === $settings['element_extra_text_pos'] ) {
					echo '<span class="wpr-grid-extra-text-left">' . esc_html( $settings['element_extra_text'] ) . '</span>';
				}
				if ( 'before' === $settings['element_extra_icon_pos'] ) {
					ob_start();
					\Elementor\Icons_Manager::render_icon( $settings['element_extra_icon'], [ 'aria-hidden' => 'true' ] );
					$extra_icon = ob_get_clean();

					echo '<span class="wpr-grid-extra-icon-left">';
						echo $extra_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor Icons_Manager trusted markup.
					echo '</span>';
				}

				foreach ( $terms as $term ) {
					echo '<a ' . $pointer_item_class . ' href="' . esc_url( get_term_link( $term->term_id ) ) . '">'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo esc_html( $term->name );
						if ( ++$count !== count( $terms ) ) {
							echo '<span class="tax-sep">' . esc_html( $settings['element_tax_sep'] ) . '</span>';
						}
					echo '</a>';
				}

				if ( 'after' === $settings['element_extra_icon_pos'] ) {
					ob_start();
					\Elementor\Icons_Manager::render_icon( $settings['element_extra_icon'], [ 'aria-hidden' => 'true' ] );
					$extra_icon = ob_get_clean();

					echo '<span class="wpr-grid-extra-icon-right">';
						echo $extra_icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor Icons_Manager trusted markup.
					echo '</span>';
				}
				if ( 'after' === $settings['element_extra_text_pos'] ) {
					echo '<span class="wpr-grid-extra-text-right">' . esc_html( $settings['element_extra_text'] ) . '</span>';
				}
			echo '</div>';
		echo '</div>';
	}

	public static function get_elements( $type, $settings, $class, $post_id, $general_settings ) {
		if ( 'pro-lk' === $type || 'pro-shr' === $type ) {
			$type = 'title';
		}

		switch ( $type ) {
			case 'title':
				self::render_post_title( $settings, $class, $general_settings );
				break;

			case 'caption':
				self::render_post_excerpt( $settings, $class );
				break;

			case 'date':
				self::render_post_date( $settings, $class );
				break;

			case 'time':
				self::render_post_time( $settings, $class );
				break;

			case 'author':
				self::render_post_author( $settings, $class );
				break;

			case 'likes':
				self::render_post_likes( $settings, $class, $post_id );
				break;

			case 'sharing':
				self::render_post_sharing_icons( $settings, $class );
				break;

			case 'lightbox':
				self::render_post_lightbox( $settings, $class, $post_id );
				break;

			case 'separator':
				self::render_post_element_separator( $settings, $class );
				break;

			default:
				self::render_post_taxonomies( $settings, $class, $post_id, $general_settings );
				break;
		}
	}

	public static function get_elements_by_location( $location, $settings, $post_id ) {
		$locations = [];

		foreach ( $settings['grid_elements'] as $data ) {
			$place = $data['element_location'];
			$align_vr = $data['element_align_vr'];

			if ( ! defined( 'WPR_ADDONS_PRO_VERSION' ) || ! wpr_fs()->can_use_premium_code() ) {
				$align_vr = 'middle';
			}

			if ( ! isset( $locations[ $place ] ) ) {
				$locations[ $place ] = [];
			}

			if ( 'over' === $place ) {
				if ( ! isset( $locations[ $place ][ $align_vr ] ) ) {
					$locations[ $place ][ $align_vr ] = [];
				}

				array_push( $locations[ $place ][ $align_vr ], $data );
			} else {
				array_push( $locations[ $place ], $data );
			}
		}

		if ( empty( $locations[ $location ] ) ) {
			return;
		}

		if ( 'over' === $location ) {
			foreach ( $locations[ $location ] as $align => $elements ) {
				if ( 'middle' === $align ) {
					echo '<div class="wpr-cv-container"><div class="wpr-cv-outer"><div class="wpr-cv-inner">';
				}

				echo '<div class="wpr-grid-media-hover-' . esc_attr( $align ) . ' elementor-clearfix">';
					foreach ( $elements as $data ) {
						$class  = 'wpr-grid-item-' . $data['element_select'];
						$class .= ' elementor-repeater-item-' . $data['_id'];
						$class .= ' wpr-grid-item-display-' . $data['element_display'];
						$class .= ' wpr-grid-item-align-' . $data['element_align_hr'];
						$class .= self::get_animation_class( $data, 'element' );

						self::get_elements( $data['element_select'], $data, $class, $post_id, $settings );
					}
				echo '</div>';

				if ( 'middle' === $align ) {
					echo '</div></div></div>';
				}
			}
		} else {
			echo '<div class="wpr-grid-item-' . esc_attr( $location ) . '-content elementor-clearfix">';
				foreach ( $locations[ $location ] as $data ) {
					$class  = 'wpr-grid-item-' . $data['element_select'];
					$class .= ' elementor-repeater-item-' . $data['_id'];
					$class .= ' wpr-grid-item-display-' . $data['element_display'];
					$class .= ' wpr-grid-item-align-' . $data['element_align_hr'];

					self::get_elements( $data['element_select'], $data, $class, $post_id, $settings );
				}
			echo '</div>';
		}
	}

	public static function render_grid_item( $settings, $post_id ) {
		$post_class = implode( ' ', get_post_class( 'wpr-grid-item elementor-clearfix', $post_id ) );

		echo '<article class="' . esc_attr( $post_class ) . '">';
			echo '<div class="wpr-grid-item-inner">';

			self::get_elements_by_location( 'above', $settings, $post_id );

			echo '<div class="wpr-grid-media-wrap' . esc_attr( self::get_image_effect_class( $settings ) ) . ' ">';
				self::render_post_thumbnail( $settings );

				echo '<div class="wpr-grid-media-hover wpr-animation-wrap">';
					self::render_media_overlay( $settings );
					self::get_elements_by_location( 'over', $settings, $post_id );
				echo '</div>';
			echo '</div>';

			self::get_elements_by_location( 'below', $settings, $post_id );

			echo '</div>';
		echo '</article>';
	}

	public static function render_posts_loop( $settings ) {
		$posts = new \WP_Query( self::get_main_query_args( $settings, [] ) );

		if ( $posts->have_posts() ) {
			while ( $posts->have_posts() ) {
				$posts->the_post();

				if ( ! wp_attachment_is( 'image', get_the_ID() ) ) {
					continue;
				}

				self::render_grid_item( $settings, get_the_ID() );
			}

			wp_reset_postdata();
		}

		return $posts;
	}

	public static function render_grid_pagination( $settings ) {
		if ( 'yes' !== $settings['layout_pagination'] || 1 === self::get_max_num_pages( $settings ) || 'slider' === $settings['layout_select'] ) {
			return;
		}

		global $paged;
		$pages = self::get_max_num_pages( $settings );
		$paged = empty( $paged ) ? 1 : $paged;

		if ( ! defined( 'WPR_ADDONS_PRO_VERSION' ) || ! wpr_fs()->can_use_premium_code() ) {
			$settings['pagination_type'] = 'pro-is' === $settings['pagination_type'] ? 'default' : $settings['pagination_type'];
		}

		echo '<div class="wpr-grid-pagination elementor-clearfix wpr-grid-pagination-' . esc_attr( $settings['pagination_type'] ) . '">';

		if ( 'default' === $settings['pagination_type'] ) {
			if ( $paged < $pages ) {
				echo '<a href="' . esc_url( get_pagenum_link( $paged + 1, true ) ) . '" class="wpr-prev-post-link">';
					echo Utilities::get_wpr_icon( $settings['pagination_on_icon'], 'left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo esc_html( $settings['pagination_older_text'] );
				echo '</a>';
			} elseif ( 'yes' === $settings['pagination_disabled_arrows'] ) {
				echo '<span class="wpr-prev-post-link wpr-disabled-arrow">';
					echo Utilities::get_wpr_icon( $settings['pagination_on_icon'], 'left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo esc_html( $settings['pagination_older_text'] );
				echo '</span>';
			}

			if ( $paged > 1 ) {
				echo '<a href="' . esc_url( get_pagenum_link( $paged - 1, true ) ) . '" class="wpr-next-post-link">';
					echo esc_html( $settings['pagination_newer_text'] );
					echo Utilities::get_wpr_icon( $settings['pagination_on_icon'], 'right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '</a>';
			} elseif ( 'yes' === $settings['pagination_disabled_arrows'] ) {
				echo '<span class="wpr-next-post-link wpr-disabled-arrow">';
					echo esc_html( $settings['pagination_newer_text'] );
					echo Utilities::get_wpr_icon( $settings['pagination_on_icon'], 'right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '</span>';
			}
		} elseif ( 'numbered' === $settings['pagination_type'] ) {
			$range = $settings['pagination_range'];
			$showitems = ( $range * 2 ) + 1;

			if ( 1 !== $pages ) {
				if ( 'yes' === $settings['pagination_prev_next'] || 'yes' === $settings['pagination_first_last'] ) {
					echo '<div class="wpr-grid-pagi-left-arrows">';

					if ( 'yes' === $settings['pagination_first_last'] ) {
						if ( $paged >= 2 ) {
							echo '<a href="' . esc_url( get_pagenum_link( 1, true ) ) . '" class="wpr-first-page">';
								echo Utilities::get_wpr_icon( $settings['pagination_fl_icon'], 'left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								echo '<span>' . esc_html( $settings['pagination_first_text'] ) . '</span>';
							echo '</a>';
						} elseif ( 'yes' === $settings['pagination_disabled_arrows'] ) {
							echo '<span class="wpr-first-page wpr-disabled-arrow">';
								echo Utilities::get_wpr_icon( $settings['pagination_fl_icon'], 'left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								echo '<span>' . esc_html( $settings['pagination_first_text'] ) . '</span>';
							echo '</span>';
						}
					}

					if ( 'yes' === $settings['pagination_prev_next'] ) {
						if ( $paged > 1 ) {
							echo '<a href="' . esc_url( get_pagenum_link( $paged - 1, true ) ) . '" class="wpr-prev-page">';
								echo Utilities::get_wpr_icon( $settings['pagination_pn_icon'], 'left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								echo '<span>' . esc_html( $settings['pagination_prev_text'] ) . '</span>';
							echo '</a>';
						} elseif ( 'yes' === $settings['pagination_disabled_arrows'] ) {
							echo '<span class="wpr-prev-page wpr-disabled-arrow">';
								echo Utilities::get_wpr_icon( $settings['pagination_pn_icon'], 'left' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								echo '<span>' . esc_html( $settings['pagination_prev_text'] ) . '</span>';
							echo '</span>';
						}
					}

					echo '</div>';
				}

				for ( $i = 1; $i <= $pages; $i++ ) {
					if ( 1 !== $pages && ( ! ( $i >= $paged + $range + 1 || $i <= $paged - $range - 1 ) || $pages <= $showitems ) ) {
						if ( $paged === $i ) {
							echo '<span class="wpr-grid-current-page">' . esc_html( $i ) . '</span>';
						} else {
							echo '<a href="' . esc_url( get_pagenum_link( $i, true ) ) . '">' . esc_html( $i ) . '</a>';
						}
					}
				}

				if ( 'yes' === $settings['pagination_prev_next'] || 'yes' === $settings['pagination_first_last'] ) {
					echo '<div class="wpr-grid-pagi-right-arrows">';

					if ( 'yes' === $settings['pagination_prev_next'] ) {
						if ( $paged < $pages ) {
							echo '<a href="' . esc_url( get_pagenum_link( $paged + 1, true ) ) . '" class="wpr-next-page">';
								echo '<span>' . esc_html( $settings['pagination_next_text'] ) . '</span>';
								echo Utilities::get_wpr_icon( $settings['pagination_pn_icon'], 'right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo '</a>';
						} elseif ( 'yes' === $settings['pagination_disabled_arrows'] ) {
							echo '<span class="wpr-next-page wpr-disabled-arrow">';
								echo '<span>' . esc_html( $settings['pagination_next_text'] ) . '</span>';
								echo Utilities::get_wpr_icon( $settings['pagination_pn_icon'], 'right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo '</span>';
						}
					}

					if ( 'yes' === $settings['pagination_first_last'] ) {
						if ( $paged <= $pages - 1 ) {
							echo '<a href="' . esc_url( get_pagenum_link( $pages, true ) ) . '" class="wpr-last-page">';
								echo '<span>' . esc_html( $settings['pagination_last_text'] ) . '</span>';
								echo Utilities::get_wpr_icon( $settings['pagination_fl_icon'], 'right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo '</a>';
						} elseif ( 'yes' === $settings['pagination_disabled_arrows'] ) {
							echo '<span class="wpr-last-page wpr-disabled-arrow">';
								echo '<span>' . esc_html( $settings['pagination_last_text'] ) . '</span>';
								echo Utilities::get_wpr_icon( $settings['pagination_fl_icon'], 'right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo '</span>';
						}
					}

					echo '</div>';
				}
			}
		} else {
			$wpr_next_page = esc_url( get_pagenum_link( $paged + 1, true ) );
			$load_more_tag = isset( $settings['pagination_load_more_tag'] ) ? $settings['pagination_load_more_tag'] : 'a';

			if ( 'yes' === $settings['filters_experiment'] && 'button' === $load_more_tag ) {
				echo '<button type="button" class="wpr-load-more-btn" data-wpr-next-page="' . esc_attr( $wpr_next_page ) . '" data-e-disable-page-transition>';
					echo esc_html( $settings['pagination_load_more_text'] );
				echo '</button>';
			} else {
				echo '<a href="' . esc_url( $wpr_next_page ) . '" class="wpr-load-more-btn" data-e-disable-page-transition>';
					echo esc_html( $settings['pagination_load_more_text'] );
				echo '</a>';
			}

			echo '<div class="wpr-pagination-loading">';
				switch ( $settings['pagination_animation'] ) {
					case 'loader-1':
						echo '<div class="wpr-double-bounce">';
							echo '<div class="wpr-child wpr-double-bounce1"></div>';
							echo '<div class="wpr-child wpr-double-bounce2"></div>';
						echo '</div>';
						break;
					case 'loader-2':
						echo '<div class="wpr-wave">';
							echo '<div class="wpr-rect wpr-rect1"></div>';
							echo '<div class="wpr-rect wpr-rect2"></div>';
							echo '<div class="wpr-rect wpr-rect3"></div>';
							echo '<div class="wpr-rect wpr-rect4"></div>';
							echo '<div class="wpr-rect wpr-rect5"></div>';
						echo '</div>';
						break;
					case 'loader-3':
						echo '<div class="wpr-spinner wpr-spinner-pulse"></div>';
						break;
					case 'loader-4':
						echo '<div class="wpr-chasing-dots">';
							echo '<div class="wpr-child wpr-dot1"></div>';
							echo '<div class="wpr-child wpr-dot2"></div>';
						echo '</div>';
						break;
					case 'loader-5':
						echo '<div class="wpr-three-bounce">';
							echo '<div class="wpr-child wpr-bounce1"></div>';
							echo '<div class="wpr-child wpr-bounce2"></div>';
							echo '<div class="wpr-child wpr-bounce3"></div>';
						echo '</div>';
						break;
					case 'loader-6':
						echo '<div class="wpr-fading-circle">';
							echo '<div class="wpr-circle wpr-circle1"></div>';
							echo '<div class="wpr-circle wpr-circle2"></div>';
							echo '<div class="wpr-circle wpr-circle3"></div>';
							echo '<div class="wpr-circle wpr-circle4"></div>';
							echo '<div class="wpr-circle wpr-circle5"></div>';
							echo '<div class="wpr-circle wpr-circle6"></div>';
							echo '<div class="wpr-circle wpr-circle7"></div>';
							echo '<div class="wpr-circle wpr-circle8"></div>';
							echo '<div class="wpr-circle wpr-circle9"></div>';
							echo '<div class="wpr-circle wpr-circle10"></div>';
							echo '<div class="wpr-circle wpr-circle11"></div>';
							echo '<div class="wpr-circle wpr-circle12"></div>';
						echo '</div>';
						break;
				}
			echo '</div>';

			echo '<p class="wpr-pagination-finish">' . esc_html( $settings['pagination_finish_text'] ) . '</p>';
		}

		echo '</div>';
	}

	public function wpr_get_media_filtered_count() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'wpr-addons-js' ) ) {
			wp_send_json_error( [
				'message' => esc_html__( 'Security check failed.', 'wpr-addons' ),
			] );
		}

		if ( isset( $_POST['grid_settings'] ) ) {
			$settings = $_POST['grid_settings'];
			self::get_max_num_pages( $settings );
		}

		wp_die();
	}

	public function wpr_filter_grid_media() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'wpr-addons-js' ) ) {
			wp_send_json_error( [
				'message' => esc_html__( 'Security check failed.', 'wpr-addons' ),
			] );
		}

		$settings = isset( $_POST['grid_settings'] ) && is_array( $_POST['grid_settings'] ) ? $_POST['grid_settings'] : [];

		$start = microtime( true );

		ob_start();

		$posts = self::render_posts_loop( $settings );

		if ( 0 === (int) $posts->found_posts && ! empty( $settings['query_not_found_text'] ) ) {
			echo '<h2>' . esc_html( $settings['query_not_found_text'] ) . '</h2>';
		}

		$output = ob_get_clean();

		$end = microtime( true );
		$duration = round( ( $end - $start ) * 1000, 2 );

		wp_send_json_success( [
			'output' => $output,
			'duration' => $duration,
			'found_posts' => $posts->found_posts,
			'post_count' => $posts->post_count,
		] );

		wp_die();
	}
}

new WPR_Media_Grid_Helpers();
