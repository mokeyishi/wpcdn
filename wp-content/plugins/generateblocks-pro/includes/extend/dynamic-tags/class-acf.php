<?php
/**
 * The class to integrate advance custom fields to dynamic content post meta.
 *
 * @package Generateblocks/Extend/DynamicTags
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * GenerateBlocks Pro Advanced custom fields integration
 *
 * @since 1.4.0
 */
class GenerateBlocks_Pro_Dynamic_Tags_ACF extends GenerateBlocks_Pro_Singleton {


	/**
	 * Stored ACF option keys.
	 *
	 * @var array
	 */
	public $acf_option_fields = [];


	/**
	 * Init function
	 * The post meta value.
	 */
	public function init() {

		if ( ! class_exists( 'ACF' ) ) {
			return; // Exit if Advanced Custom Fields plugin is not active.
		}

		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

		add_filter(
			'generateblocks_get_meta_pre_value',
			[ $this, 'get_meta_pre_value' ],
			10,
			5
		);

		add_filter(
			'generateblocks_dynamic_tags_post_record_response',
			[ $this, 'add_acf_meta_to_post_record' ],
			10,
			3
		);
	}
	/**
	 * Add appropriate meta pre values.
	 *
	 * @param string|null $pre_value The pre - filtered value, or null if unset.
	 * @param int         $id The entity ID used to fetch the meta value.
	 * @param string      $key The meta key to fetch.
	 * @param string      $callable function name to call. Should be a native WordPress function ( ex: get_post_meta).
	 */
	public function get_meta_pre_value( $pre_value, $id, $key, $callable ) {
		switch ( $callable ) {
			case 'get_post_meta':
				$value = self::get_post_meta_pre_value( $pre_value, $id, $key );
				break;
			case 'get_user_meta':
				$value = self::get_user_meta_pre_value( $pre_value, $id, $key );
				break;
			case 'get_term_meta':
				$value = self::get_term_meta_pre_value( $pre_value, $id, $key );
				break;
			case 'get_option':
				$value = self::get_option_pre_value( $pre_value, $id, $key );
				break;
			default:
		}

		return $value;
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'generateblocks-pro/v1',
			'/get-acf-option-fields',
			[
				'methods'  => 'GET',
				'callback' => [ $this, 'get_acf_option_fields_rest' ],
				'permission_callback' => function() {
					return current_user_can( 'edit_posts' );
				},
			]
		);
	}

	/**
	 *
	 * This function checks if the provided key is an Advanced Custom Fields (ACF) field key.
	 * If it is, the function retrieves the ACF field value using the `get_field` function. Sub fields
	 * can be passed to the $key param using a "." to separate the parent and sub fields (ex: parent_child.sub_field).
	 *
	 * @since 1.8.0
	 *
	 * @param string $value The current post meta value.
	 * @param int    $id    The ID of the post.
	 * @param string $key   The post meta key.
	 * @param string $type  The post meta type.
	 *
	 * @return mixed The updated post meta value. If the key is not an ACF field key, the original value is returned.
	 */
	public function maybe_get_field( $value, $id, $key, $type = '' ) {
		$acf_id           = $type ? "{$type}_{$id}" : $id;
		$key_parts        = array_map( 'trim', explode( '.', $key ) );
		$parent_name      = $key_parts[0];
		$is_acf_field     = false;
		$parent_is_option = 'option' === $type ? $this->is_acf_option( $parent_name ) : false;

		if ( $parent_is_option ) {
			$acf_id = 'option';
		}

		if ( $parent_is_option ) {
			$is_acf_field = true;
		} else {
			$acf_keys     = acf_get_meta( $acf_id );
			$is_acf_field = isset( $acf_keys[ $parent_name ] );
		}

		if ( ! $is_acf_field ) {
			return $value;
		}

		return get_field( $parent_name, $acf_id );
	}

	/**
	 * Filters the post meta value before it's returned.
	 *
	 * This function checks if the provided key is an Advanced Custom Fields( ACF ) field key.
	 * if it is, the function retrieves the ACF field value using the `get_field` function.
	 *
	 * @since 1.8.0
	 *
	 * @param string $value   The current post meta value.
	 * @param int    $post_id The ID of the post.
	 * @param string $key     The post meta key.
	 *
	 * @return mixed The updated post meta value. if the key is not an ACF field key, the original value is returned.
	 */
	public function get_post_meta_pre_value( $value, $post_id, $key ) {
		return $this->maybe_get_field( $value, $post_id, $key );
	}

	/**
	 * Filters the term meta value before it's returned.
	 *
	 * This function checks if the provided key is an Advanced Custom Fields (ACF) field key.
	 * If it is, the function retrieves the ACF field value using the `get_field` function.
	 *
	 * @param string $value The current term meta value.
	 * @param int    $id    The ID of the term to query meta from.
	 * @param string $key   The term meta key.
	 *
	 * @return mixed The updated post meta value. If the key is not an ACF field key, the original value is returned.
	 */
	public function get_term_meta_pre_value( $value, $id, $key ) {
		return $this->maybe_get_field( $value, $id, $key, 'term' );
	}

	/**
	 * Filters the user meta value before it's returned.
	 *
	 * This function checks if the provided key is an Advanced Custom Fields (ACF) field key.
	 * If it is, the function retrieves the ACF field value using the `get_field` function.
	 *
	 * @param string $value The current user meta value.
	 * @param int    $id    The ID of the user to query meta from.
	 * @param string $key   The user meta key.
	 *
	 * @return mixed The updated post meta value. If the key is not an ACF field key, the original value is returned.
	 */
	public function get_user_meta_pre_value( $value, $id, $key ) {
		return $this->maybe_get_field( $value, $id, $key, 'user' );
	}

	/**
	 * Filters the option value before it's returned.
	 *
	 * This function checks if the provided key is an Advanced Custom Fields (ACF) field key.
	 * If it is, the function retrieves the ACF field value using the `get_field` function.
	 *
	 * @param string $value The current option meta value.
	 * @param int    $id    The ACF ID of the option to retrieve.
	 * @param string $key   The option key.
	 *
	 * @return mixed The updated post meta value. If the key is not an ACF field key, the original value is returned.
	 */
	public function get_option_pre_value( $value, $id, $key ) {
		return $this->maybe_get_field( $value, $id, $key, 'option' );
	}

	/**
	 * Filters the post meta post record to include ACF meta field keys and values.
	 *
	 * @param object $response Post object from the response.
	 * @param int    $id ID of the post record.
	 *
	 * @return mixed
	 */
	public function add_acf_meta_to_post_record( $response, $id ) {
		$acf_meta = acf_get_meta( $id );

		if ( $acf_meta ) {

			$response->acf = array_filter(
				$acf_meta,
				function ( $key ) {
					return strpos( $key, '_' ) !== 0;
				},
				ARRAY_FILTER_USE_KEY
			);

		}

		return $response;
	}

	/**
	 * Retrieves all ACf option fields using the default option prefix.
	 *
	 * @return array
	 */
	public function get_acf_option_fields() {
		if ( ! function_exists( 'acf_get_option_meta' ) ) {
			return [];
		}

		$options = array_filter(
			acf_get_option_meta( 'options' ),
			function( $key ) {
				return strpos( $key, '_' ) !== 0;
			},
			ARRAY_FILTER_USE_KEY
		);

		$response = [];

		foreach ( $options as $key => $array_value ) {
			$response[ $key ] = $array_value[0];
		}

		return $response;
	}

	/**
	 * Rest controller for get-acf-option-fields.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_acf_option_fields_rest() {
		if ( ! $this->acf_option_fields ) {
			$this->acf_option_fields = $this->get_acf_option_fields();
		}

		return rest_ensure_response( $this->acf_option_fields );
	}

	/**
	 * Check if a given field name is an ACF option or not.
	 *
	 * @param string $field_name The name of the field to check.
	 * @return bool Return true if the option is an ACF option.
	 */
	public function is_acf_option( $field_name ) {
		if ( ! $this->acf_option_fields ) {
			$this->acf_option_fields = $this->get_acf_option_fields();
		}

		return isset( $this->acf_option_fields[ $field_name ] );
	}
}

GenerateBlocks_Pro_Dynamic_Tags_ACF::get_instance()->init();
