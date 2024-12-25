<?php
/**
 * The Dynamic Tags class file.
 *
 * @package GenerateBlocks_Pro\Dynamic_Tags
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class for handling dynamic tags.
 *
 * @since 2.0.0
 */
class GenerateBlocks_Pro_Dynamic_Tags_Register extends GenerateBlocks_Pro_Singleton {
	/**
	 * Initialize all hooks.
	 *
	 * @return void
	 */
	public function init() {
		if ( ! class_exists( 'GenerateBlocks_Register_Dynamic_Tag' ) ) {
			return;
		}

		add_action( 'init', [ $this, 'register' ] );
	}

	/**
	 * Register the tags.
	 *
	 * @return void
	 */
	public function register() {
		new GenerateBlocks_Register_Dynamic_Tag(
			[
				'title'       => __( 'Archive Title', 'generateblocks' ),
				'tag'         => 'archive_title',
				'type'        => 'archive',
				'supports'    => [],
				'description' => __( 'Get the title for the current archive being viewed.', 'generateblocks' ),
				'return'      => [ $this, 'get_archive_title' ],
			]
		);

		new GenerateBlocks_Register_Dynamic_Tag(
			[
				'title'    => __( 'Option', 'generateblocks-pro' ),
				'tag'      => 'option',
				'type'     => 'option',
				'supports' => [ 'meta' ],
				'return'   => [ $this, 'get_option' ],
			]
		);

		new GenerateBlocks_Register_Dynamic_Tag(
			[
				'title'       => __( 'Term Meta', 'generateblocks-pro' ),
				'tag'         => 'term_meta',
				'type'        => 'term',
				'supports'    => [ 'meta', 'source' ],
				'description' => __( 'Access term meta by key for the specified term. Return value must be a string.', 'generateblocks' ),
				'return'      => [ $this, 'get_term_meta' ],
			]
		);

		new GenerateBlocks_Register_Dynamic_Tag(
			[
				'title'       => __( 'User Meta', 'generateblocks' ),
				'tag'         => 'user_meta',
				'type'        => 'user',
				'supports'    => [ 'meta', 'source' ],
				'description' => __( 'Access user meta by key for the specified user. Return value must be a string.', 'generateblocks' ),
				'return'      => [ $this, 'get_user_meta' ],
			]
		);

		new GenerateBlocks_Register_Dynamic_Tag(
			[
				'title'    => __( 'Current year', 'generateblocks' ),
				'tag'      => 'current_year',
				'type'     => 'site',
				'supports' => [],
				'return'   => [ $this, 'get_current_year' ],
			]
		);

		new GenerateBlocks_Register_Dynamic_Tag(
			[
				'title'    => __( 'Site Title', 'generateblocks' ),
				'tag'      => 'site_title',
				'type'     => 'site',
				'supports' => [],
				'return'   => [ $this, 'get_site_title' ],
			]
		);

		new GenerateBlocks_Register_Dynamic_Tag(
			[
				'title'    => __( 'Site Tagline', 'generateblocks' ),
				'tag'      => 'site_tagline',
				'type'     => 'site',
				'supports' => [],
				'return'   => [ $this, 'get_site_tagline' ],
			]
		);

		new GenerateBlocks_Register_Dynamic_Tag(
			[
				'title'      => __( 'Loop Index', 'generateblocks' ),
				'tag'        => 'loop_index',
				'type'       => 'looper',
				'supports'   => [],
				'visibility' => [
					'context' => [
						'generateblocks/loopIndex',
					],
				],
				'options' => [
					'zeroBased' => [
						'type'  => 'checkbox',
						'label' => __( 'Use zero-based index', 'generateblocks' ),
						'help'  => __( 'Enable this to start the loop index count from 0.', 'generateblocks' ),
					],
				],
				'description' => __( 'The numbered index of the loop item.', 'generateblocks' ),
				'return'      => [ $this, 'get_loop_index' ],
			]
		);

		new GenerateBlocks_Register_Dynamic_Tag(
			[
				'title'      => __( 'Loop Item', 'generateblocks' ),
				'tag'        => 'loop_item',
				'type'       => 'looper',
				'supports'   => [ 'properties' ],
				'visibility' => [
					'context' => [
						'generateblocks/loopItem',
					],
				],
				'description' => __( 'The current loop item data.', 'generateblocks' ),
				'return'      => [ $this, 'get_loop_item' ],
			]
		);
	}

	/**
	 * Get the archive title.
	 *
	 * @param array  $options The options.
	 * @param object $block The block.
	 * @param object $instance The block instance.
	 * @return string
	 */
	public static function get_archive_title( $options, $block, $instance ) {
		$output = '';

		if ( is_category() ) {
			$output = single_cat_title( '', false );
		} elseif ( is_tag() ) {
			$output = single_tag_title( '', false );
		} elseif ( is_author() ) {
			$output = get_the_author();
		} elseif ( is_post_type_archive() ) {
			$output = post_type_archive_title( '', false );
		} elseif ( is_tax() ) {
			$output = single_term_title( '', false );
		}

		return GenerateBlocks_Dynamic_Tag_Callbacks::output( $output, $options, $instance );
	}

	/**
	 * Get the option.
	 *
	 * @param array $options The options.
	 * @return string
	 */
	public static function get_option( $options ) {
		$default     = $options['default'] ?? '';
		$key         = $options['key'] ?? '';
		$key_parts   = array_map( 'trim', explode( '.', $key ) );
		$parent_name = $key_parts[0];
		$output      = '';

		if ( empty( $key ) ) {
			return GenerateBlocks_Dynamic_Tag_Callbacks::output( $output, $options );
		}

		$allowed_options = [
			'siteurl',
			'blogname',
			'blogdescription',
			'home',
			'time_format',
			'user_count',
		];

		$acf_keys = array_keys(
			GenerateBlocks_Pro_Dynamic_Tags_ACF::get_instance()->get_acf_option_fields()
		);

		/**
		 * $allowed_options contains an array of keys from the options table along with the
		 * acf option keys. Disallowed keys will return an empty string.
		 *
		 * @since 2.0.0
		 * @param array $allowed_options Array of allowed option keys.
		 */
		$allowed_options = apply_filters(
			'generateblocks_dynamic_tags_allowed_options',
			array_merge( $allowed_options, $acf_keys )
		);

		if ( ! in_array( $parent_name, $allowed_options, true ) ) {
			return GenerateBlocks_Dynamic_Tag_Callbacks::output( $output, $options );

		}

		$value = GenerateBlocks_Meta_Handler::get_option( $key, true, $default );

		if ( ! $value ) {
			return GenerateBlocks_Dynamic_Tag_Callbacks::output( $output, $options );
		}

		add_filter( 'wp_kses_allowed_html', [ 'GenerateBlocks_Dynamic_Tags', 'expand_allowed_html' ], 10, 2 );
		$output = wp_kses_post( $value );
		remove_filter( 'wp_kses_allowed_html', [ 'GenerateBlocks_Dynamic_Tags', 'expand_allowed_html' ], 10, 2 );

		return GenerateBlocks_Dynamic_Tag_Callbacks::output( $output, $options );
	}

	/**
	 * Get the term meta.
	 *
	 * @param array  $options The options.
	 * @param array  $block The block.
	 * @param object $instance The block instance.
	 * @return string
	 */
	public static function get_term_meta( $options, $block, $instance ) {
		$id = GenerateBlocks_Dynamic_Tags::get_id( $options, 'term', $instance );

		if ( ! $id ) {
			return GenerateBlocks_Dynamic_Tag_Callbacks::output( '', $options, $instance );
		}

		$key    = $options['key'] ?? '';
		$output = '';

		if ( empty( $key ) ) {
			return GenerateBlocks_Dynamic_Tag_Callbacks::output( $output, $options, $instance );
		}

		$value = GenerateBlocks_Meta_Handler::get_term_meta( $id, $key, true );

		if ( ! $value ) {
			return GenerateBlocks_Dynamic_Tag_Callbacks::output( $output, $options, $instance );
		}

		add_filter( 'wp_kses_allowed_html', [ 'GenerateBlocks_Dynamic_Tags', 'expand_allowed_html' ], 10, 2 );
		$output = wp_kses_post( $value );
		remove_filter( 'wp_kses_allowed_html', [ 'GenerateBlocks_Dynamic_Tags', 'expand_allowed_html' ], 10, 2 );

		return GenerateBlocks_Dynamic_Tag_Callbacks::output( $output, $options, $instance );
	}

	/**
	 * Get the user meta.
	 *
	 * @param array  $options The options.
	 * @param array  $block The block.
	 * @param object $instance The block instance.
	 * @return string
	 */
	public static function get_user_meta( $options, $block, $instance ) {
		$id = GenerateBlocks_Dynamic_Tags::get_id( $options, 'user', $instance );

		if ( ! $id ) {
			return GenerateBlocks_Dynamic_Tag_Callbacks::output( '', $options, $instance );
		}

		$key    = $options['key'] ?? '';
		$output = '';

		if ( empty( $key ) ) {
			return GenerateBlocks_Dynamic_Tag_Callbacks::output( $output, $options, $instance );
		}

		$value = GenerateBlocks_Meta_Handler::get_user_meta( $id, $key, true );

		if ( ! $value ) {
			return GenerateBlocks_Dynamic_Tag_Callbacks::output( $output, $options, $instance );
		}

		add_filter( 'wp_kses_allowed_html', [ 'GenerateBlocks_Dynamic_Tags', 'expand_allowed_html' ], 10, 2 );
		$output = wp_kses_post( $value );
		remove_filter( 'wp_kses_allowed_html', [ 'GenerateBlocks_Dynamic_Tags', 'expand_allowed_html' ], 10, 2 );

		return GenerateBlocks_Dynamic_Tag_Callbacks::output( $output, $options, $instance );
	}

	/**
	 * Get the current year.
	 *
	 * @param array  $options The options.
	 * @param array  $block The block.
	 * @param object $instance The block instance.
	 *
	 * @return string
	 */
	public static function get_current_year( $options, $block, $instance ) {
		$output = wp_date( 'Y' );

		return GenerateBlocks_Dynamic_Tag_Callbacks::output( $output, $options, $instance );
	}

	/**
	 * Get the site title from settings.
	 *
	 * @param array  $options The options.
	 * @param array  $block The block.
	 * @param object $instance The block instance.
	 * @return string
	 */
	public static function get_site_title( $options, $block, $instance ) {
		$output = get_option( 'blogname' );

		return GenerateBlocks_Dynamic_Tag_Callbacks::output( $output, $options, $instance );
	}

	/**
	 * Get the site tagline from settings.
	 *
	 * @param array  $options The options.
	 * @param array  $block The block.
	 * @param object $instance The block instance.
	 * @return string
	 */
	public static function get_site_tagline( $options, $block, $instance ) {
		$output = get_option( 'blogdescription' );

		return GenerateBlocks_Dynamic_Tag_Callbacks::output( $output, $options, $instance );
	}

	/**
	 * Get the index of the current looper block loop.
	 *
	 * @param array  $options The options.
	 * @param array  $block The block.
	 * @param object $instance The block instance.
	 * @return int The loop index number.
	 */
	public static function get_loop_index( $options, $block, $instance ) {
		$use_zero_based = $options['zeroBased'] ?? false;
		$loop_index = (int) isset( $instance->context['generateblocks/loopIndex'] )
		? $instance->context['generateblocks/loopIndex']
		: -1;

		if ( $use_zero_based ) {
			--$loop_index;
		}

		if ( $loop_index > -1 ) {
			return (string) $loop_index;
		}
	}

	/**
	 * Get the current loop item.
	 *
	 * @param array  $options The options.
	 * @param array  $block The block.
	 * @param object $instance The block instance.
	 * @return string Value of the loop item or a given key's value from the loop item.
	 */
	public static function get_loop_item( $options, $block, $instance ) {
		$key       = $options['key'] ?? '';
		$fallback  = $options['fallback'] ?? '';
		$loop_item = $instance->context['generateblocks/loopItem'] ?? [];
		$output    = GenerateBlocks_Meta_Handler::get_value( $key, $loop_item, true, $fallback );

		return GenerateBlocks_Dynamic_Tag_Callbacks::output( $output, $options, $instance );
	}
}

GenerateBlocks_Pro_Dynamic_Tags_Register::get_instance()->init();
