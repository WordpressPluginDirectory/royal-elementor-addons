<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WordPress eXtended RSS file parser implementations
 *
 * @package WordPress
 * @subpackage Importer
 */

/**
 * WXR Parser that uses regular expressions. Fallback for installs without an XML parser.
 */
class WXR_Parser_Regex {
	/**
	 * @var bool
	 */
	private $has_gzip;

	private $authors = [];
	private $posts = [];
	private $categories = [];
	private $tags = [];
	private $terms = [];
	private $base_url = '';
	private $base_blog_url = '';

	/**
	 * @param string $file
	 *
	 * @return array|\WP_Error
	 */
	public function parse( $file ) {
		$wxr_version = '';
		$in_multiline = false;

		$multiline_content = '';

		$multiline_tags = [
			'item' => [
				'posts',
				function ( $post ) {
					return $this->process_post( $post );
				},
			],
			'wp:category' => [
				'categories',
				function ( $category ) {
					return $this->process_category( $category );
				},
			],
			'wp:tag' => [
				'tags',
				function ( $tag ) {
					return $this->process_tag( $tag );
				},
			],
			'wp:term' => [
				'terms',
				function ( $term ) {
					return $this->process_term( $term );
				},
			],
		];

		$fp = $this->fopen( $file, 'r' );
		if ( $fp ) {
			while ( ! $this->feof( $fp ) ) {
				$importline = rtrim( $this->fgets( $fp ) );

				if ( ! $wxr_version && preg_match( '|<wp:wxr_version>(\d+\.\d+)</wp:wxr_version>|', $importline, $version ) ) {
					$wxr_version = $version[1];
				}

				if ( false !== strpos( $importline, '<wp:base_site_url>' ) ) {
					preg_match( '|<wp:base_site_url>(.*?)</wp:base_site_url>|is', $importline, $url );
					$this->base_url = $url[1];
					continue;
				}

				if ( false !== strpos( $importline, '<wp:base_blog_url>' ) ) {
					preg_match( '|<wp:base_blog_url>(.*?)</wp:base_blog_url>|is', $importline, $blog_url );
					$this->base_blog_url = $blog_url[1];
					continue;
				} else {
					$this->base_blog_url = $this->base_url;
				}

				if ( false !== strpos( $importline, '<wp:author>' ) ) {
					preg_match( '|<wp:author>(.*?)</wp:author>|is', $importline, $author );
					$a = $this->process_author( $author[1] );
					$this->authors[ $a['author_login'] ] = $a;
					continue;
				}

				foreach ( $multiline_tags as $tag => $handler ) {
					// Handle multi-line tags on a singular line.
					if ( preg_match( '|<'. $tag .'>(.*?)</'. $tag .'>|is', $importline, $matches ) ) {
						$this->{$handler[0]}[] = call_user_func( $handler[1], $matches[1] );

						continue;
					}

					$pos = strpos( $importline, "<$tag>" );

					if ( false !== $pos ) {
						// Take note of any content after the opening tag.
						$multiline_content = trim( substr( $importline, $pos + strlen( $tag ) + 2 ) );

						// We don't want to have this line added to `$is_multiline` below.
						$importline = '';
						$in_multiline = $tag;

						continue;
					}

					$pos = strpos( $importline, "</$tag>" );

					if ( false !== $pos ) {
						$in_multiline = false;
						$multiline_content .= trim( substr( $importline, 0, $pos ) );

						$this->{$handler[0]}[] = call_user_func( $handler[1], $multiline_content );
					}
				}

				if ( $in_multiline && $importline ) {
					$multiline_content .= $importline . "\n";
				}
			}

			$this->fclose( $fp );
		}

		if ( ! $wxr_version ) {
			return new WP_Error( 'WXR_parse_error', esc_html__( 'This does not appear to be a WXR file, missing/invalid WXR version number', 'wpr-addons' ) );
		}

		return [
			'authors' => $this->authors,
			'posts' => $this->posts,
			'categories' => $this->categories,
			'tags' => $this->tags,
			'terms' => $this->terms,
			'base_url' => $this->base_url,
			'base_blog_url' => $this->base_blog_url,
			'version' => $wxr_version,
		];
	}

	private function process_category( $category ) {
		$term = [
			'term_id' => $this->get_tag( $category, 'wp:term_id' ),
			'cat_name' => $this->get_tag( $category, 'wp:cat_name' ),
			'category_nicename' => $this->get_tag( $category, 'wp:category_nicename' ),
			'category_parent' => $this->get_tag( $category, 'wp:category_parent' ),
			'category_description' => $this->get_tag( $category, 'wp:category_description' ),
		];

		$term_meta = $this->process_meta( $category, 'wp:termmeta' );
		if ( ! empty( $term_meta ) ) {
			$term['termmeta'] = $term_meta;
		}

		return $term;
	}

	private function process_tag( $tag ) {
		$term = [
			'term_id' => $this->get_tag( $tag, 'wp:term_id' ),
			'tag_name' => $this->get_tag( $tag, 'wp:tag_name' ),
			'tag_slug' => $this->get_tag( $tag, 'wp:tag_slug' ),
			'tag_description' => $this->get_tag( $tag, 'wp:tag_description' ),
		];

		$term_meta = $this->process_meta( $tag, 'wp:termmeta' );
		if ( ! empty( $term_meta ) ) {
			$term['termmeta'] = $term_meta;
		}

		return $term;
	}

	private function process_term( $term ) {
		$term_data = [
			'term_id' => $this->get_tag( $term, 'wp:term_id' ),
			'term_taxonomy' => $this->get_tag( $term, 'wp:term_taxonomy' ),
			'slug' => $this->get_tag( $term, 'wp:term_slug' ),
			'term_parent' => $this->get_tag( $term, 'wp:term_parent' ),
			'term_name' => $this->get_tag( $term, 'wp:term_name' ),
			'term_description' => $this->get_tag( $term, 'wp:term_description' ),
		];

		$term_meta = $this->process_meta( $term, 'wp:termmeta' );
		if ( ! empty( $term_meta ) ) {
			$term_data['termmeta'] = $term_meta;
		}

		return $term_data;
	}

	private function process_meta( $string, $tag ) {
		$parsed_meta = [];

		preg_match_all( "|<$tag>(.+?)</$tag>|is", $string, $meta );

		if ( ! isset( $meta[1] ) ) {
			return $parsed_meta;
		}

		foreach ( $meta[1] as $m ) {
			$parsed_meta[] = [
				'key' => $this->get_tag( $m, 'wp:meta_key' ),
				'value' => $this->get_tag( $m, 'wp:meta_value' ),
			];
		}

		return $parsed_meta;
	}

	private function process_author( $a ) {
		return [
			'author_id' => $this->get_tag( $a, 'wp:author_id' ),
			'author_login' => $this->get_tag( $a, 'wp:author_login' ),
			'author_email' => $this->get_tag( $a, 'wp:author_email' ),
			'author_display_name' => $this->get_tag( $a, 'wp:author_display_name' ),
			'author_first_name' => $this->get_tag( $a, 'wp:author_first_name' ),
			'author_last_name' => $this->get_tag( $a, 'wp:author_last_name' ),
		];
	}

	private function process_post( $post ) {
		$normalize_tag_callback = function ( $matches ) {
			return $this->normalize_tag( $matches );
		};

		$post_id = $this->get_tag( $post, 'wp:post_id' );
		$post_title = $this->get_tag( $post, 'title' );
		$post_date = $this->get_tag( $post, 'wp:post_date' );
		$post_date_gmt = $this->get_tag( $post, 'wp:post_date_gmt' );
		$comment_status = $this->get_tag( $post, 'wp:comment_status' );
		$ping_status = $this->get_tag( $post, 'wp:ping_status' );
		$status = $this->get_tag( $post, 'wp:status' );
		$post_name = $this->get_tag( $post, 'wp:post_name' );
		$post_parent = $this->get_tag( $post, 'wp:post_parent' );
		$menu_order = $this->get_tag( $post, 'wp:menu_order' );
		$post_type = $this->get_tag( $post, 'wp:post_type' );
		$post_password = $this->get_tag( $post, 'wp:post_password' );
		$is_sticky = $this->get_tag( $post, 'wp:is_sticky' );
		$guid = $this->get_tag( $post, 'guid' );
		$post_author = $this->get_tag( $post, 'dc:creator' );

		$post_excerpt = $this->get_tag( $post, 'excerpt:encoded' );
		$post_excerpt = preg_replace_callback( '|<(/?[A-Z]+)|', $normalize_tag_callback, $post_excerpt );
		$post_excerpt = str_replace( '<br>', '<br />', $post_excerpt );
		$post_excerpt = str_replace( '<hr>', '<hr />', $post_excerpt );

		$post_content = $this->get_tag( $post, 'content:encoded' );
		$post_content = preg_replace_callback( '|<(/?[A-Z]+)|', $normalize_tag_callback, $post_content );
		$post_content = str_replace( '<br>', '<br />', $post_content );
		$post_content = str_replace( '<hr>', '<hr />', $post_content );

		$postdata = compact( 'post_id', 'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_excerpt', 'post_title', 'status', 'post_name', 'comment_status', 'ping_status', 'guid', 'post_parent', 'menu_order', 'post_type', 'post_password', 'is_sticky' );

		$attachment_url = $this->get_tag( $post, 'wp:attachment_url' );
		if ( $attachment_url ) {
			$postdata['attachment_url'] = $attachment_url;
		}

		preg_match_all( '|<category domain="([^"]+?)" nicename="([^"]+?)">(.+?)</category>|is', $post, $terms, PREG_SET_ORDER );
		foreach ( $terms as $t ) {
			$post_terms[] = [
				'slug' => $t[2],
				'domain' => $t[1],
				'name' => str_replace( [ '<![CDATA[', ']]>' ], '', $t[3] ),
			];
		}
		if ( ! empty( $post_terms ) ) {
			$postdata['terms'] = $post_terms;
		}

		preg_match_all( '|<wp:comment>(.+?)</wp:comment>|is', $post, $comments );
		$comments = $comments[1];
		if ( $comments ) {
			foreach ( $comments as $comment ) {
				$post_comments[] = [
					'comment_id' => $this->get_tag( $comment, 'wp:comment_id' ),
					'comment_author' => $this->get_tag( $comment, 'wp:comment_author' ),
					'comment_author_email' => $this->get_tag( $comment, 'wp:comment_author_email' ),
					'comment_author_IP' => $this->get_tag( $comment, 'wp:comment_author_IP' ),
					'comment_author_url' => $this->get_tag( $comment, 'wp:comment_author_url' ),
					'comment_date' => $this->get_tag( $comment, 'wp:comment_date' ),
					'comment_date_gmt' => $this->get_tag( $comment, 'wp:comment_date_gmt' ),
					'comment_content' => $this->get_tag( $comment, 'wp:comment_content' ),
					'comment_approved' => $this->get_tag( $comment, 'wp:comment_approved' ),
					'comment_type' => $this->get_tag( $comment, 'wp:comment_type' ),
					'comment_parent' => $this->get_tag( $comment, 'wp:comment_parent' ),
					'comment_user_id' => $this->get_tag( $comment, 'wp:comment_user_id' ),
					'commentmeta' => $this->process_meta( $comment, 'wp:commentmeta' ),
				];
			}
		}
		if ( ! empty( $post_comments ) ) {
			$postdata['comments'] = $post_comments;
		}

		$post_meta = $this->process_meta( $post, 'wp:postmeta' );
		if ( ! empty( $post_meta ) ) {
			$postdata['postmeta'] = $post_meta;
		}

		return $postdata;
	}

	private function get_tag( $string, $tag ) {
		preg_match( "|<$tag.*?>(.*?)</$tag>|is", $string, $return );
		if ( isset( $return[1] ) ) {
			if ( substr( $return[1], 0, 9 ) == '<![CDATA[' ) {
				if ( strpos( $return[1], ']]]]><![CDATA[>' ) !== false ) {
					preg_match_all( '|<!\[CDATA\[(.*?)\]\]>|s', $return[1], $matches );
					$return = '';
					foreach ( $matches[1] as $match ) {
						$return .= $match;
					}
				} else {
					$return = preg_replace( '|^<!\[CDATA\[(.*)\]\]>$|s', '$1', $return[1] );
				}
			} else {
				$return = $return[1];
			}
		} else {
			$return = '';
		}

		return $return;
	}

	private function normalize_tag( $matches ) {
		return '<' . strtolower( $matches[1] );
	}

	private function fopen( $filename, $mode = 'r' ) {
		if ( $this->has_gzip ) {
			return gzopen( $filename, $mode );
		}

		return fopen( $filename, $mode );
	}

	private function feof( $fp ) {
		if ( $this->has_gzip ) {
			return gzeof( $fp );
		}

		return feof( $fp );
	}

	private function fgets( $fp, $len = 8192 ) {
		if ( $this->has_gzip ) {
			return gzgets( $fp, $len );
		}

		return fgets( $fp, $len );
	}

	private function fclose( $fp ) {
		if ( $this->has_gzip ) {
			return gzclose( $fp );
		}

		return fclose( $fp );
	}

	public function __construct() {
		$this->has_gzip = is_callable( 'gzopen' );
	}
}

/**
 * WordPress eXtended RSS file parser implementations,
 * Originally made by WordPress part of WordPress/Importer.
 * https://plugins.trac.wordpress.org/browser/wordpress-importer/trunk/parsers/class-wxr-parser-simplexml.php
 *
 * What was done:
 * Reformat of the code.
 * Removed variable '$internal_errors'.
 * Changed text domain.
 */

/**
 * WXR Parser that makes use of the SimpleXML PHP extension.
 */
class WXR_Parser_SimpleXML {

	/**
	 * @param string $file
	 *
	 * @return array|\WP_Error
	 */
	public function parse( $file ) {
		$authors = [];
		$posts = [];
		$categories = [];
		$tags = [];
		$terms = [];

		libxml_use_internal_errors( true );

		$dom = new \DOMDocument();
		$old_value = null;

		$libxml_disable_entity_loader_exists = function_exists( 'libxml_disable_entity_loader' );

		if ( PHP_VERSION_ID < 80000 && $libxml_disable_entity_loader_exists ) {
			// Only call libxml_disable_entity_loader() for PHP versions before 8.0
			$old_value = libxml_disable_entity_loader( true ); // phpcs:ignore Generic.PHP.DeprecatedFunctions.Deprecated
		}
		
		$success = $dom->loadXML( file_get_contents( $file ) );
		
		if ( PHP_VERSION_ID < 80000 && $libxml_disable_entity_loader_exists && ! is_null( $old_value ) ) {
			libxml_disable_entity_loader( $old_value ); // phpcs:ignore Generic.PHP.DeprecatedFunctions.Deprecated
		}

		if ( ! $success || isset( $dom->doctype ) ) {
			return new WP_Error( 'SimpleXML_parse_error', esc_html__( 'There was an error when reading this WXR file', 'wpr-addons' ), libxml_get_errors() );
		}

		$xml = simplexml_import_dom( $dom );
		unset( $dom );

		// Halt if loading produces an error.
		if ( ! $xml ) {
			return new WP_Error( 'SimpleXML_parse_error', esc_html__( 'There was an error when reading this WXR file', 'wpr-addons' ), libxml_get_errors() );
		}

		$wxr_version = $xml->xpath( '/rss/channel/wp:wxr_version' );
		if ( ! $wxr_version ) {
			return new WP_Error( 'WXR_parse_error', esc_html__( 'This does not appear to be a WXR file, missing/invalid WXR version number', 'wpr-addons' ) );
		}

		$wxr_version = (string) trim( $wxr_version[0] );
		// Confirm that we are dealing with the correct file format.
		if ( ! preg_match( '/^\d+\.\d+$/', $wxr_version ) ) {
			return new WP_Error( 'WXR_parse_error', esc_html__( 'This does not appear to be a WXR file, missing/invalid WXR version number', 'wpr-addons' ) );
		}

		$base_url = $xml->xpath( '/rss/channel/wp:base_site_url' );
		$base_url = (string) trim( isset( $base_url[0] ) ? $base_url[0] : '' );

		$base_blog_url = $xml->xpath( '/rss/channel/wp:base_blog_url' );
		if ( $base_blog_url ) {
			$base_blog_url = (string) trim( $base_blog_url[0] );
		} else {
			$base_blog_url = $base_url;
		}

		$page_on_front = $xml->xpath( '/rss/channel/wp:page_on_front' );

		if ( $page_on_front ) {
			$page_on_front = (int) $page_on_front[0];
		}

		$namespaces = $xml->getDocNamespaces();
		if ( ! isset( $namespaces['wp'] ) ) {
			$namespaces['wp'] = 'http://wordpress.org/export/1.1/';
		}
		if ( ! isset( $namespaces['excerpt'] ) ) {
			$namespaces['excerpt'] = 'http://wordpress.org/export/1.1/excerpt/';
		}

		// Grab authors.
		foreach ( $xml->xpath( '/rss/channel/wp:author' ) as $author_arr ) {
			$a = $author_arr->children( $namespaces['wp'] );
			$login = (string) $a->author_login;
			$authors[ $login ] = [
				'author_id' => (int) $a->author_id,
				'author_login' => $login,
				'author_email' => (string) $a->author_email,
				'author_display_name' => (string) $a->author_display_name,
				'author_first_name' => (string) $a->author_first_name,
				'author_last_name' => (string) $a->author_last_name,
			];
		}

		// Grab cats, tags and terms.
		foreach ( $xml->xpath( '/rss/channel/wp:category' ) as $term_arr ) {
			$t = $term_arr->children( $namespaces['wp'] );
			$category = [
				'term_id' => (int) $t->term_id,
				'category_nicename' => (string) $t->category_nicename,
				'category_parent' => (string) $t->category_parent,
				'cat_name' => (string) $t->cat_name,
				'category_description' => (string) $t->category_description,
			];

			foreach ( $t->termmeta as $meta ) {
				$category['termmeta'][] = [
					'key' => (string) $meta->meta_key,
					'value' => (string) $meta->meta_value,
				];
			}

			$categories[] = $category;
		}

		foreach ( $xml->xpath( '/rss/channel/wp:tag' ) as $term_arr ) {
			$t = $term_arr->children( $namespaces['wp'] );
			$tag = [
				'term_id' => (int) $t->term_id,
				'tag_slug' => (string) $t->tag_slug,
				'tag_name' => (string) $t->tag_name,
				'tag_description' => (string) $t->tag_description,
			];

			foreach ( $t->termmeta as $meta ) {
				$tag['termmeta'][] = [
					'key' => (string) $meta->meta_key,
					'value' => (string) $meta->meta_value,
				];
			}

			$tags[] = $tag;
		}

		foreach ( $xml->xpath( '/rss/channel/wp:term' ) as $term_arr ) {
			$t = $term_arr->children( $namespaces['wp'] );
			$term = [
				'term_id' => (int) $t->term_id,
				'term_taxonomy' => (string) $t->term_taxonomy,
				'slug' => (string) $t->term_slug,
				'term_parent' => (string) $t->term_parent,
				'term_name' => (string) $t->term_name,
				'term_description' => (string) $t->term_description,
			];

			foreach ( $t->termmeta as $meta ) {
				$term['termmeta'][] = [
					'key' => (string) $meta->meta_key,
					'value' => (string) $meta->meta_value,
				];
			}

			$terms[] = $term;
		}

		// Grab posts.
		foreach ( $xml->channel->item as $item ) {
			$post = [
				'post_title' => (string) $item->title,
				'guid' => (string) $item->guid,
			];

			$dc = $item->children( 'http://purl.org/dc/elements/1.1/' );
			$post['post_author'] = (string) $dc->creator;

			$content = $item->children( 'http://purl.org/rss/1.0/modules/content/' );
			$excerpt = $item->children( $namespaces['excerpt'] );
			$post['post_content'] = (string) $content->encoded;
			$post['post_excerpt'] = (string) $excerpt->encoded;

			$wp = $item->children( $namespaces['wp'] );
			$post['post_id'] = (int) $wp->post_id;
			$post['post_date'] = (string) $wp->post_date;
			$post['post_date_gmt'] = (string) $wp->post_date_gmt;
			$post['comment_status'] = (string) $wp->comment_status;
			$post['ping_status'] = (string) $wp->ping_status;
			$post['post_name'] = (string) $wp->post_name;
			$post['status'] = (string) $wp->status;
			$post['post_parent'] = (int) $wp->post_parent;
			$post['menu_order'] = (int) $wp->menu_order;
			$post['post_type'] = (string) $wp->post_type;
			$post['post_password'] = (string) $wp->post_password;
			$post['is_sticky'] = (int) $wp->is_sticky;

			if ( isset( $wp->attachment_url ) ) {
				$post['attachment_url'] = (string) $wp->attachment_url;
			}

			foreach ( $item->category as $c ) {
				$att = $c->attributes();
				if ( isset( $att['nicename'] ) ) {
					$post['terms'][] = [
						'name' => (string) $c,
						'slug' => (string) $att['nicename'],
						'domain' => (string) $att['domain'],
					];
				}
			}

			foreach ( $wp->postmeta as $meta ) {
				$post['postmeta'][] = [
					'key' => (string) $meta->meta_key,
					'value' => (string) $meta->meta_value,
				];
			}

			foreach ( $wp->comment as $comment ) {
				$meta = [];
				if ( isset( $comment->commentmeta ) ) {
					foreach ( $comment->commentmeta as $m ) {
						$meta[] = [
							'key' => (string) $m->meta_key,
							'value' => (string) $m->meta_value,
						];
					}
				}

				$post['comments'][] = [
					'comment_id' => (int) $comment->comment_id,
					'comment_author' => (string) $comment->comment_author,
					'comment_author_email' => (string) $comment->comment_author_email,
					'comment_author_IP' => (string) $comment->comment_author_IP,
					'comment_author_url' => (string) $comment->comment_author_url,
					'comment_date' => (string) $comment->comment_date,
					'comment_date_gmt' => (string) $comment->comment_date_gmt,
					'comment_content' => (string) $comment->comment_content,
					'comment_approved' => (string) $comment->comment_approved,
					'comment_type' => (string) $comment->comment_type,
					'comment_parent' => (string) $comment->comment_parent,
					'comment_user_id' => (int) $comment->comment_user_id,
					'commentmeta' => $meta,
				];
			}

			$posts[] = $post;
		}

		return [
			'authors' => $authors,
			'posts' => $posts,
			'categories' => $categories,
			'tags' => $tags,
			'terms' => $terms,
			'base_url' => $base_url,
			'base_blog_url' => $base_blog_url,
			'page_on_front' => $page_on_front,
			'version' => $wxr_version,
		];
	}
}


/**
 * WordPress eXtended RSS file parser implementations,
 * Originally made by WordPress part of WordPress/Importer.
 * https://plugins.trac.wordpress.org/browser/wordpress-importer/trunk/parsers/class-wxr-parser-xml.php
 *
 * What was done:
 * Reformat of the code.
 * Added PHPDOC.
 * Changed text domain.
 * Added clear() method.
 * Added undeclared class properties.
 * Changed methods visibility.
 */

/**
 * WXR Parser that makes use of the XML Parser PHP extension.
 */
class WXR_Parser_XML {
	private static $wp_tags = [
		'wp:post_id',
		'wp:post_date',
		'wp:post_date_gmt',
		'wp:comment_status',
		'wp:ping_status',
		'wp:attachment_url',
		'wp:status',
		'wp:post_name',
		'wp:post_parent',
		'wp:menu_order',
		'wp:post_type',
		'wp:post_password',
		'wp:is_sticky',
		'wp:term_id',
		'wp:category_nicename',
		'wp:category_parent',
		'wp:cat_name',
		'wp:category_description',
		'wp:tag_slug',
		'wp:tag_name',
		'wp:tag_description',
		'wp:term_taxonomy',
		'wp:term_parent',
		'wp:term_name',
		'wp:term_description',
		'wp:author_id',
		'wp:author_login',
		'wp:author_email',
		'wp:author_display_name',
		'wp:author_first_name',
		'wp:author_last_name',
	];

	private static $wp_sub_tags = [
		'wp:comment_id',
		'wp:comment_author',
		'wp:comment_author_email',
		'wp:comment_author_url',
		'wp:comment_author_IP',
		'wp:comment_date',
		'wp:comment_date_gmt',
		'wp:comment_content',
		'wp:comment_approved',
		'wp:comment_type',
		'wp:comment_parent',
		'wp:comment_user_id',
	];

	/**
	 * @var string
	 */
	private $wxr_version;

	/**
	 * @var string
	 */
	private $cdata;

	/**
	 * @var array
	 */
	private $data;

	/**
	 * @var array
	 */
	private $sub_data;

	/**
	 * @var boolean
	 */
	private $in_post;

	/**
	 * @var boolean
	 */
	private $in_tag;

	/**
	 * @var boolean
	 */
	private $in_sub_tag;

	/**
	 * @var array
	 */
	private $authors;

	/**
	 * @var array
	 */
	private $posts;

	/**
	 * @var array
	 */
	private $term;

	/**
	 * @var array
	 */
	private $category;

	/**
	 * @var array
	 */
	private $tag;

	/**
	 * @var string
	 */
	private $base_url;

	/**
	 * @var string
	 */
	private $base_blog_url;

	/**
	 * @param string $file
	 *
	 * @return array|WP_Error
	 */
	public function parse( $file ) {
		$this->clear();

		$xml = xml_parser_create( 'UTF-8' );
		xml_parser_set_option( $xml, XML_OPTION_SKIP_WHITE, 1 );
		xml_parser_set_option( $xml, XML_OPTION_CASE_FOLDING, 0 );
		xml_set_object( $xml, $this );

		xml_set_character_data_handler( $xml, function ( $parser, $cdata ) {
			$this->cdata( $cdata );
		} );

		$tag_open_callback = function ( $parse, $tag, $attr ) {
			$this->tag_open( $tag, $attr );
		};

		$tag_close_callback = function ( $parser, $tag ) {
			$this->tag_close( $tag );
		};

		xml_set_element_handler( $xml, $tag_open_callback, $tag_close_callback );

		if ( ! xml_parse( $xml, file_get_contents( $file ), true ) ) {
			$current_line = xml_get_current_line_number( $xml );
			$current_column = xml_get_current_column_number( $xml );
			$error_code = xml_get_error_code( $xml );
			$error_string = xml_error_string( $error_code );

			return new WP_Error( 'XML_parse_error', 'There was an error when reading this WXR file', [
				$current_line,
				$current_column,
				$error_string,
			] );
		}
		xml_parser_free( $xml );

		if ( ! preg_match( '/^\d+\.\d+$/', $this->wxr_version ) ) {
			return new WP_Error( 'WXR_parse_error', esc_html__( 'This does not appear to be a WXR file, missing/invalid WXR version number', 'wpr-addons' ) );
		}

		return array(
			'authors' => $this->authors,
			'posts' => $this->posts,
			'categories' => $this->category,
			'tags' => $this->tag,
			'terms' => $this->term,
			'base_url' => $this->base_url,
			'base_blog_url' => $this->base_blog_url,
			'version' => $this->wxr_version,
		);
	}

	private function tag_open( $tag, $attr ) {
		if ( in_array( $tag, self::$wp_tags ) ) {
			$this->in_tag = substr( $tag, 3 );

			return;
		}

		if ( in_array( $tag, self::$wp_sub_tags ) ) {
			$this->in_sub_tag = substr( $tag, 3 );

			return;
		}

		switch ( $tag ) {
			case 'category':
				if ( isset( $attr['domain'], $attr['nicename'] ) ) {
					$this->sub_data['domain'] = $attr['domain'];
					$this->sub_data['slug'] = $attr['nicename'];
				}
				break;
			case 'item':
				$this->in_post = true;
				// No break !!!.
			case 'title':
				if ( $this->in_post ) {
					$this->in_tag = 'post_title';
				}
				break;
			case 'guid':
				$this->in_tag = 'guid';
				break;
			case 'dc:creator':
				$this->in_tag = 'post_author';
				break;
			case 'content:encoded':
				$this->in_tag = 'post_content';
				break;
			case 'excerpt:encoded':
				$this->in_tag = 'post_excerpt';
				break;

			case 'wp:term_slug':
				$this->in_tag = 'slug';
				break;
			case 'wp:meta_key':
				$this->in_sub_tag = 'key';
				break;
			case 'wp:meta_value':
				$this->in_sub_tag = 'value';
				break;
		}
	}

	private function cdata( $cdata ) {
		if ( ! trim( $cdata ) ) {
			return;
		}

		if ( false !== $this->in_tag || false !== $this->in_sub_tag ) {
			$this->cdata .= $cdata;
		} else {
			$this->cdata .= trim( $cdata );
		}
	}

	private function tag_close( $tag ) {
		switch ( $tag ) {
			case 'wp:comment':
				unset( $this->sub_data['key'], $this->sub_data['value'] ); // Remove meta sub_data.
				if ( ! empty( $this->sub_data ) ) {
					$this->data['comments'][] = $this->sub_data;
				}
				$this->sub_data = [];
				break;
			case 'wp:commentmeta':
				$this->sub_data['commentmeta'][] = [
					'key' => $this->sub_data['key'],
					'value' => $this->sub_data['value'],
				];
				break;
			case 'category':
				if ( ! empty( $this->sub_data ) ) {
					$this->sub_data['name'] = $this->cdata;
					$this->data['terms'][] = $this->sub_data;
				}
				$this->sub_data = [];
				break;
			case 'wp:postmeta':
				if ( ! empty( $this->sub_data ) ) {
					$this->data['postmeta'][] = $this->sub_data;
				}
				$this->sub_data = [];
				break;
			case 'item':
				$this->posts[] = $this->data;
				$this->data = [];
				break;
			case 'wp:category':
			case 'wp:tag':
			case 'wp:term':
				$n = substr( $tag, 3 );
				array_push( $this->$n, $this->data );
				$this->data = [];
				break;
			case 'wp:termmeta':
				if ( ! empty( $this->sub_data ) ) {
					$this->data['termmeta'][] = $this->sub_data;
				}
				$this->sub_data = [];
				break;
			case 'wp:author':
				if ( ! empty( $this->data['author_login'] ) ) {
					$this->authors[ $this->data['author_login'] ] = $this->data;
				}
				$this->data = [];
				break;
			case 'wp:base_site_url':
				$this->base_url = $this->cdata;
				if ( ! isset( $this->base_blog_url ) ) {
					$this->base_blog_url = $this->cdata;
				}
				break;
			case 'wp:base_blog_url':
				$this->base_blog_url = $this->cdata;
				break;
			case 'wp:wxr_version':
				$this->wxr_version = $this->cdata;
				break;

			default:
				if ( $this->in_sub_tag ) {
					$this->sub_data[ $this->in_sub_tag ] = $this->cdata;
					$this->in_sub_tag = false;
				} elseif ( $this->in_tag ) {
					$this->data[ $this->in_tag ] = $this->cdata;
					$this->in_tag = false;
				}
		}

		$this->cdata = '';
	}

	private function clear() {
		$this->wxr_version = '';

		$this->cdata = '';
		$this->data = [];
		$this->sub_data = [];

		$this->in_post = false;
		$this->in_tag = false;
		$this->in_sub_tag = false;

		$this->authors = [];
		$this->posts = [];
		$this->term = [];
		$this->category = [];
		$this->tag = [];
	}
}


/**
 * WordPress eXtended RSS file parser implementations,
 * Originally made by WordPress part of WordPress/Importer.
 * https://plugins.trac.wordpress.org/browser/wordpress-importer/trunk/parsers/class-wxr-parser.php
 *
 * What was done:
 * Reformat of the code.
 * Changed text domain.
 */

/**
 * WordPress Importer class for managing parsing of WXR files.
 */
class WXR_Parser {

	public function parse( $file ) {
		// Attempt to use proper XML parsers first.
		if ( extension_loaded( 'simplexml' ) ) {
			$parser = new WXR_Parser_SimpleXML();
			$result = $parser->parse( $file );

			// If SimpleXML succeeds or this is an invalid WXR file then return the results.
			if ( ! is_wp_error( $result ) || 'SimpleXML_parse_error' != $result->get_error_code() ) {
				return $result;
			}
		} elseif ( extension_loaded( 'xml' ) ) {
			$parser = new WXR_Parser_XML();
			$result = $parser->parse( $file );

			// If XMLParser succeeds or this is an invalid WXR file then return the results.
			if ( ! is_wp_error( $result ) || 'XML_parse_error' != $result->get_error_code() ) {
				return $result;
			}
		}

		// Use regular expressions if nothing else available or this is bad XML.
		$parser = new WXR_Parser_Regex();

		return $parser->parse( $file );
	}
}
