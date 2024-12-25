<?php
/**
 * Extend the Query block.
 *
 * @package GenerateBlocksPro\Extend\Query
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Extend the default Query block.
 *
 * @since 2.0.0
 */
class GenerateBlocks_Pro_Block_Query extends GenerateBlocks_Pro_Singleton {
	// Add new Query types here.
	const TYPE_POST_META = 'post_meta';
	const TYPE_OPTION    = 'option';

	/**
	 * Init function.
	 */
	public function init() {
		if ( ! class_exists( 'GenerateBlocks_Meta_Handler' ) ) {
			return;
		}

		add_filter( 'generateblocks_query_data', [ $this, 'set_query_data' ], 10, 3 );
		add_filter( 'generateblocks_dynamic_tag_id', [ $this, 'set_dynamic_tag_id' ], 10, 3 );

		add_filter( 'generateblocks_query_wp_query_args', [ $this, 'exclude_current_post' ] );
		add_filter( 'generateblocks_query_wp_query_args', [ $this, 'include_current_author' ] );
		add_filter( 'generateblocks_query_wp_query_args', [ $this, 'exclude_current_author' ] );
	}

	/**
	 * Update the dynamic tag's ID if it's a post tag, it has a valid loop item ID, and no other ID is set in options.
	 *
	 * @param int    $id The current ID value for the tag.
	 * @param array  $options The tag options.
	 * @param object $instance The block instance for the block containing the tag.
	 * @return int The ID for the dynamic tag.
	 */
	public function set_dynamic_tag_id( $id, $options, $instance ) {
		// If an ID is set in options, use the original ID.
		if ( $options['id'] ?? false ) {
			return $id;
		}

		$loop_item = $instance->context['generateblocks/loopItem'] ?? null;

		if ( ! $loop_item ) {
			return $id;
		}

		// Look for the ID or id keys and return the original $id if none can be found.
		if ( is_array( $loop_item ) ) {
			return $loop_item['ID'] ?? $loop_item['id'] ?? $id;
		} elseif ( is_object( $loop_item ) ) {
			return $loop_item->ID ?? $loop_item->id ?? $id;
		}

		return $id;
	}

	/**
	 *  Set the query data for certain types.
	 *
	 * @param array  $query_data The current query data.
	 * @param string $query_type The type of query.
	 * @param array  $attributes An array of block attributes.
	 *
	 * @return array An array of query data.
	 */
	public function set_query_data( $query_data, $query_type, $attributes ) {
		if ( self::TYPE_POST_META === $query_type ) {
			$query    = $attributes['query'] ?? [];
			$id       = $query['meta_key_id'] ?? get_the_ID();
			$meta_key = $query['meta_key'] ?? '';
			$value    = GenerateBlocks_Meta_Handler::get_post_meta( $id, $meta_key, false );
			$data     = is_array( $value ) ? $value : [];

			return [
				'data'       => $data,
				'no_results' => empty( $data ),
				'args'       => $query,
			];
		}

		if ( self::TYPE_OPTION === $query_type ) {
			$query    = $attributes['query'] ?? [];
			$meta_key = $query['meta_key'] ?? '';
			$value    = GenerateBlocks_Meta_Handler::get_option( $meta_key, false );
			$data     = is_array( $value ) ? $value : [];

			return [
				'data'       => $data,
				'no_results' => empty( $data ),
				'args'       => $query,
			];
		}

		return $query_data;
	}

	/**
	 * Exclude current post from the query.
	 *
	 * @since 1.3.0
	 * @param Array $query_args The query arguments.
	 *
	 * @return Array The query arguments without current post.
	 */
	public function exclude_current_post( $query_args ) {
		if (
			isset( $query_args['post__not_in'] ) &&
			in_array( 'current', $query_args['post__not_in'] ) &&
			get_post_type() === $query_args['post_type']
		) {
			if ( ! in_array( get_the_ID(), $query_args['post__not_in'] ) ) {
				$query_args['post__not_in'][] = get_the_ID();
			}

			$exclude_current_index = array_search( 'current', $query_args['post__not_in'] );
			array_splice( $query_args['post__not_in'], $exclude_current_index, 1 );

			// This is to avoid current post being dynamically added to post__in which will show him in the result set.
			if (
				isset( $query_args['post__in'] ) &&
				in_array( get_the_ID(), $query_args['post__in'] )
			) {
				$current_post_index = array_search( get_the_ID(), $query_args['post__in'] );
				array_splice( $query_args['post__in'], $current_post_index, 1 );
			}
		}

		return $query_args;
	}

	/**
	 * Include posts of current post author to the query.
	 *
	 * @since 1.3.0
	 * @param array $query_args The query arguments.
	 *
	 * @return array The query arguments.
	 */
	public function include_current_author( $query_args ) {
		return self::add_current_author( $query_args, 'author__in' );
	}

	/**
	 * Exclude posts of current post author to the query.
	 *
	 * @since 1.3.0
	 * @param array $query_args The query arguments.
	 *
	 * @return array The query arguments.
	 */
	public function exclude_current_author( $query_args ) {
		return self::add_current_author( $query_args, 'author__not_in' );
	}

	/**
	 * Include current author to a query argument.
	 *
	 * @since 1.3.0
	 * @param array  $query_args The query arguments.
	 * @param string $key The query argument key.
	 *
	 * @return array The query arguments.
	 */
	public function add_current_author( $query_args, $key ) {
		if (
			isset( $query_args[ $key ] ) &&
			in_array( 'current', $query_args[ $key ] )
		) {
			$current_post_author_index = array_search( 'current', $query_args[ $key ] );
			array_splice( $query_args[ $key ], $current_post_author_index, 1 );

			if ( ! in_array( get_the_author_meta( 'ID' ), $query_args[ $key ] ) ) {
				$query_args[ $key ][] = get_the_author_meta( 'ID' );
			}
		}

		return $query_args;
	}
}

GenerateBlocks_Pro_Block_Query::get_instance()->init();
