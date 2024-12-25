<?php

namespace ASENHA\Classes;

use WP_Error;

/**
 * Class for Disable REST API module
 *
 * @since 6.9.5
 */
class Disable_REST_API {

    /**
     * Disable REST API for non-authenticated users. This is for WP v4.7 or later.
     *
     * @since 2.9.0
     */
    public function disable_rest_api() {

        if ( bwasenha_fs()->can_use_premium_code__premium_only() ) {
            $options = get_option( ASENHA_SLUG_U, array() );

            if ( ! function_exists( 'get_editable_roles' ) ) {
                require_once ABSPATH . 'wp-admin/includes/user.php';
            }
            
            $for_roles_all = array();
            $all_roles = get_editable_roles();
            $all_roles = array_keys( $all_roles ); // single dimensional array of all role slugs
            foreach ( $all_roles as $role ) {
                $for_roles_all[$role] = true;
            }
            
            $for_roles = isset( $options['enable_rest_api_for'] ) ? $options['enable_rest_api_for'] : $for_roles_all;
            $roles_rest_api_access_enabled = array();

            // Assemble single-dimensional array of roles for which duplication would be enabled
            if ( is_array( $for_roles ) && ( count( $for_roles ) > 0 ) ) {
                foreach( $for_roles as $role_slug => $rest_api_access_enabled ) {
                    if ( $rest_api_access_enabled ) {
                        $roles_rest_api_access_enabled[] = $role_slug;
                    }
                }
            }
        }
        
        $allow_rest_api_access = false;
        
        if ( ! is_user_logged_in() ) {
            $allow_rest_api_access = false;
        } else {
            if ( bwasenha_fs()->can_use_premium_code__premium_only() ) {
                if ( count( $roles_rest_api_access_enabled ) > 0 ) {
                    $current_user = wp_get_current_user();
                    $current_user_roles = (array) $current_user->roles; // single dimensional array of role slugs

                    foreach ( $current_user_roles as $role ) {
                        if ( in_array( $role, $roles_rest_api_access_enabled ) ) {
                            // Do something here
                            $allow_rest_api_access = true;
                            break;
                        }
                    }
                }
            } else {
                $allow_rest_api_access = true;
            }
        }

        if ( ! $allow_rest_api_access ) {
            return new WP_Error(
                'rest_api_authentication_required', 
                'The REST API has been restricted to authenticated users.', 
                array( 
                    'status' => rest_authorization_required_code() 
                ) 
            );            
        }

    }
    
}