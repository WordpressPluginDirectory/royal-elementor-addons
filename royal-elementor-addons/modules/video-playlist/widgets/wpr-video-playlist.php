<?php
namespace WprAddons\Modules\VideoPlaylist\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Repeater;
use Elementor\Group_Control_Image_Size;
use WprAddons\Classes\Utilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class Wpr_Video_Playlist extends Widget_Base {

	public function get_name() {
		return 'wpr-video-playlist';
	}

	public function get_title() {
		return esc_html__( 'Video Playlist', 'wpr-addons' );
	}

	public function get_icon() {
		return 'wpr-icon eicon-video-playlist';
	}

	public function get_categories() {
		return [ 'wpr-widgets' ];
	}

	public function get_keywords() {
		return [ 'royal', 'video playlist' ];
	}

	public function has_widget_inner_wrapper(): bool {
		return ! \Elementor\Plugin::$instance->experiments->is_feature_active( 'e_optimized_markup' );
	}

	public function get_custom_help_url() {
		if ( empty(get_option('wpr_wl_plugin_links')) )
        // return 'https://royal-elementor-addons.com/contact/?ref=rea-plugin-panel-video-playlist-help-btn';
    		return 'https://wordpress.org/support/plugin/royal-elementor-addons/';
    }

	public function is_reload_preview_required() {
		return true;
	}

	public function add_control_playlist_query() {
		$this->add_control(
			'playlist_query',
			[
				'label' => esc_html__( 'Playlist Query', 'wpr-addons' ),
				'type' => Controls_Manager::SELECT,
				'default' => 'custom',
				'options' => [
					'custom' => esc_html__( 'Custom URLs', 'wpr-addons' ),
					'pro-ytpl' => esc_html__( 'YouTube Playlist (Pro)', 'wpr-addons' ),
				],
			]
		);
	}

	public function add_control_youtube_api_key() {}

	public function add_control_youtube_playlist_id() {}

	public function add_repeater_args_video_urls() {
		return [
			'label' => esc_html__( 'Video URLs', 'wpr-addons' ),
			'type' => Controls_Manager::REPEATER,
			'default' => [
				[ 'video_url' => [ 'url' => 'https://youtu.be/OrtzJs-wzlw' ] ],
				[ 'video_url' => [ 'url' => 'https://youtu.be/zCfzzUuX8HE' ] ],
				[ 'video_url' => [ 'url' => 'https://youtu.be/Abw5LIIfgEo' ] ],
			],
			'title_field' => '{{ video_url.url }}',
			'condition' => [
				'playlist_query' => 'custom',
			],
		];
	}

	public function add_repeater_args_custom_title() {
		return [
			'type' => Controls_Manager::HIDDEN,
			'default' => '',
		];
	}

	protected function register_controls() {

		$this->start_controls_section(
			'settings_section',
			[
				'label' => esc_html__( 'Settings', 'wpr-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

        $this->add_control(
            'query_group_title',
            [
                'label' => esc_html__( 'Query', 'wpr-addons' ),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

		$this->add_control_playlist_query();

		// Upgrade to Pro Notice.
		Utilities::upgrade_pro_notice( $this, Controls_Manager::RAW_HTML, 'video-playlist', 'playlist_query', [ 'pro-ytpl' ] );

		$this->add_control_youtube_api_key();
		$this->add_control_youtube_playlist_id();
        $this->add_control(
            'title_group_title',
            [
                'label' => esc_html__( 'Title', 'wpr-addons' ),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
    
        $this->add_control(
            'title_tag',
            [
                'label' => esc_html__( 'HTML Tag', 'wpr-addons' ),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'h3',
                'options' => [
                    'h1' => 'H1',
                    'h2' => 'H2',
                    'h3' => 'H3',
                    'h4' => 'H4',
                    'h5' => 'H5',
                    'h6' => 'H6',
                    'div' => 'div',
                    'span' => 'span',
                    'P' => 'p'
                ]
            ]
        );
    
        $this->add_control(
            'playlist_title',
            [
                'label' => esc_html__( 'Playlist Title', 'wpr-addons' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__( 'Now Playing', 'wpr-addons' ),
            ]
        );

        $this->add_control(
            'urls_group_title',
            [
                'label' => esc_html__( 'Custom Video URLs', 'wpr-addons' ),
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
                'condition' => [
                    'playlist_query' => 'custom',
                ],
            ]
        );

		$repeater = new Repeater();

		$repeater->add_control(
			'video_url',
			[
				'label' => esc_html__( 'Video URL', 'wpr-addons' ),
				'type' => Controls_Manager::URL,
				'default' => [
					'url' => 'https://youtu.be/OrtzJs-wzlw',
				],
				'label_block' => true,
			]
		);

		$repeater->add_control(
			'custom_title',
			$this->add_repeater_args_custom_title()
		);

		$video_urls_args = $this->add_repeater_args_video_urls();
		$video_urls_args['fields'] = $repeater->get_controls();

		$this->add_control(
			'video_urls',
			$video_urls_args
		);

		if ( !defined('WPR_ADDONS_PRO_VERSION') || !wpr_fs()->can_use_premium_code() ) {
			$this->add_control(
				'video_urls_pro_notice',
				[
					'type' => Controls_Manager::RAW_HTML,
					'raw' => __('More than 3 Items are<br>available in the <strong><a href="https://royal-elementor-addons.com/?ref=rea-plugin-panel-form-builder-upgrade-pro#purchasepro" target="_blank">Pro version</a></strong>'),
					'content_classes' => 'wpr-pro-notice',
					'condition' => [
						'playlist_query' => 'custom',
					],
				]
			);
		}

		$this->end_controls_section();

		// Section: Pro Features
		Utilities::pro_features_list_section( $this, '', Controls_Manager::RAW_HTML, 'video-playlist', [
			'Dynamic YouTube Playlist Query',
			'Unlimited Custom Video Items',
            'Custom Video Titles for Custom Items',
		] );

		$this->start_controls_section(
			'general_section',
			[
				'label' => esc_html__( 'General', 'wpr-addons' ),
				'tab' => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'playlist_position',
			[
				'label' => esc_html__( 'Playlist Position', 'wpr-addons' ),
				'type' => Controls_Manager::CHOOSE,
				'toggle' => false,
				'default' => 'right',
				'options' => [
					'left' => [
						'title' => esc_html__( 'Left', 'wpr-addons' ),
						'icon' => 'eicon-h-align-left',
					],
					'right' => [
						'title' => esc_html__( 'Right', 'wpr-addons' ),
						'icon' => 'eicon-h-align-right',
					],
				],
			]
		);

		$this->add_control(
			'widget_accent_color',
			[
				'label' => esc_html__( 'Accent Color', 'wpr-addons' ),
				'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .wpr-vplaylist-controller' => 'background-color: {{VALUE}};',
                ],
				'default' => '#f84643',
			]
		);

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => esc_html__( 'Playlist Title Typography', 'wpr-addons' ),
                'selector' => '{{WRAPPER}} .wpr-vplaylist-heading span',
				'fields_options' => [
					'typography' => [
						'default' => 'custom',
					],
					'font_size' => [
						'default' => [
							'size' => '15',
							'unit' => 'px'
						]
					]
				]
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'current_title_typography',
                'label' => esc_html__( 'Video Title Typography', 'wpr-addons' ),
                'selector' => '{{WRAPPER}} .wpr-vplaylist-current-title, {{WRAPPER}} .wpr-vplaylist-info-title',
				'fields_options' => [
					'typography' => [
						'default' => 'custom',
					],
					'font_size' => [
						'default' => [
							'size' => '15',
							'unit' => 'px'
						]
					]
				]
            ]
        );

		$this->end_controls_section();

		// Add more sections and controls as needed
	}
    
    private function get_youtube_playlist_videos($playlist_id, $api_key) {
        $videos = [];
    
        if ( empty($api_key) ) {
            return $videos;
        }
    
        $api_url = sprintf(
            'https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults=50&playlistId=%s&key=%s',
            urlencode($playlist_id),
            urlencode($api_key)
        );
    
        $response = wp_remote_get($api_url);
    
        if ( is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200 ) {
            return $videos;
        }
    
        $body = json_decode(wp_remote_retrieve_body($response), true);
    
        if ( !empty($body['items']) ) {
            foreach ( $body['items'] as $item ) {
                if ( !empty($item['snippet']['resourceId']['videoId']) ) {
                    $videos[] = 'https://youtu.be/' . sanitize_text_field($item['snippet']['resourceId']['videoId']);
                }
            }
        }
    
        return $videos;
    }

    public function wpr_get_svg_icon( $icon ) {
        static $icons_array = null;
    
        if ( is_null( $icons_array ) ) {
            $url = trailingslashit( WPR_ADDONS_ASSETS_URL ) . 'img/svg/svg-icons.json';
    
            $response = wp_remote_get( $url );
    
            if ( is_wp_error( $response ) ) {
                $icons_array = [];
            } else {
                $body = wp_remote_retrieve_body( $response );
                $icons_array = json_decode( $body, true );
            }
        }
    
        $output  = '<span class="wpr-svg-icon wpr-inline-flex">';
        $output .= isset( $icons_array[ $icon ] ) ? $icons_array[ $icon ] : '';
        $output .= '</span>';
    
        return $output;
    }    
    
    protected function render() {
        $settings = $this->get_settings_for_display();

        $title_tag       = !empty($settings['title_tag']) ? $settings['title_tag'] : 'h6';
        $playlist_title  = !empty($settings['playlist_title']) ? $settings['playlist_title'] : esc_html__('Now Playing', 'wpr-addons');
        $playlist_query  = !empty($settings['playlist_query']) ? $settings['playlist_query'] : 'custom';
		$playlist_position = ! empty( $settings['playlist_position'] ) ? $settings['playlist_position'] : 'right';
		$playlist_position_class = 'left' === $playlist_position ? ' wpr-vplaylist-pos-left' : '';

        $video_urls = [];
		$video_titles = [];

        if ( 'playlist' === $playlist_query && !empty($settings['youtube_playlist_id']) ) {
            $video_urls = $this->get_youtube_playlist_videos($settings['youtube_playlist_id'], $settings['youtube_api_key']);
        } elseif ( !empty($settings['video_urls']) && is_array($settings['video_urls']) ) {
			$is_pro_active = defined( 'WPR_ADDONS_PRO_VERSION' ) && function_exists( 'wpr_fs' ) && wpr_fs()->can_use_premium_code();
            foreach ( $settings['video_urls'] as $index => $video_item ) {
				if ( ! $is_pro_active && $index >= 3 ) {
					break;
				}
                if ( !empty($video_item['video_url']['url']) ) {
                    $video_urls[] = esc_url($video_item['video_url']['url']);
					$video_titles[] = (isset($video_item['custom_title']) && ! empty( $video_item['custom_title'] )) ? sanitize_text_field( $video_item['custom_title'] ) : '';
                }
            }
        }

        if ( empty($video_urls) ) {
            return;
        }

        echo '<div class="wpr-vplaylist-wrap' . esc_attr( $playlist_position_class ) . '">';

            // Video Player
            echo '<div class="video-player-wrap">';
                echo '<div class="video-player">';
                    echo '<div class="wpr-vplaylist-main"></div>';
                echo '</div>';
            echo '</div>';

            // Playlist Panel
            echo '<div class="wpr-vplaylist-thumbs-wrap">';

                echo '<div class="wpr-vplaylist-highlight">';
                    echo '<div class="wpr-vplaylist-heading">';
                        echo '<span>' . esc_html($playlist_title) . '</span>';
                        echo '<' . esc_attr($title_tag) . ' class="wpr-vplaylist-current-title"></' . esc_attr($title_tag) . '>';
                    echo '</div>';

                    echo '<div class="wpr-vplaylist-controller">';
                        echo '<div class="wpr-play">' . $this->wpr_get_svg_icon('play') . '</div>';
                        echo '<div class="wpr-pause">' . $this->wpr_get_svg_icon('pause') . '</div>';
                    echo '</div>';
                echo '</div>';

                echo '<div class="wpr-vplaylist-thumbs" data-urls=\'' . esc_attr(wp_json_encode($video_urls)) . '\' data-titles=\'' . esc_attr(wp_json_encode($video_titles)) . '\'>';
                    echo '<ul></ul>';
                echo '</div>';

            echo '</div>';

        echo '</div>';
    }

}
