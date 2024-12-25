<?php

function get_options_pages_ids() {
    $options_page_ids = array();

    // We use get_posts() here instaed of WP_Query to get a static loop and not interfere with the main loop
    // If we use WP_Query, it will for example break the output of Query Loop block in the block editor
    // Ref: https://developer.wordpress.org/reference/functions/get_posts/
    // Ref: https://kinsta.com/blog/wordpress-get_posts/
    // Ref: https://digwp.com/2011/05/loops/
    $args = array(
        'post_type'         => 'asenha_options_page',
        'post_status'       => 'publish',
        'numberposts'       => -1, // use this instead of posts_per_page
        'orderby'           => 'title',
        'order'             => 'ASC',
    );
    
    $options_pages = get_posts( $args );
    
    if ( ! empty( $options_pages ) ) {
        foreach ( $options_pages as $options_page ) {
            $options_page_ids[] = $options_page->ID;
        }
    }
    
    return $options_page_ids;    
}

function get_options_pages_cf() {
    $options_pages_fields = array();
    $options_page_ids = get_options_pages_ids();
    
    if ( ! empty( $options_page_ids ) ) {
        foreach ( $options_page_ids as $id ) {
            $fields = CFG()->find_fields( array( 'post_id' => $id ) );
            foreach ( $fields as $field ) {
                $options_pages_fields[$field['name']] = $id; // array of field_name => options_page_post_id pairs
            }
        }
    }
    
    return $options_pages_fields;
}

function get_cf_info( $field_name = false, $post_id = false ) {
    
    // Normalizing for getting all field values
    if ( false == $field_name || 'all' == $field_name ) {
        $field_name = false; 
    }
    
    // Get post ID
    global $post;        

    if ( false === $post_id ) {
        
        // Array of field_name => asenha_options_page post ID pairs
        $options_pages_fields = get_options_pages_cf();
        $options_pages_field_names = array_keys( $options_pages_fields );
        
        if ( in_array( $field_name, $options_pages_field_names ) ) {
            // We assign the post ID of the options page
            $post_id = $options_pages_fields[$field_name];
        } else {
            // This is a request for a value of a field that's part of a page / post / custom post
            $post_id = ( ! empty( $post->ID ) ) ? $post->ID : get_the_ID();        
        }
    }
    
    // Get custom field info
    $cf_info = CFG()->get_field_info( $field_name, $post_id );
    
    return $cf_info;
}

function get_cf( $field_name = false, $output_format = 'default', $post_id = false ) {
    
    // Normalizing for getting all field values
    if ( false == $field_name || 'all' == $field_name ) {
        $field_name = false; 
    }
    
    // For CSS class naming in custom field output
    $field_name_slug = str_replace( '_', '-', $field_name );

    // Get post ID
    global $post;        
    if ( false === $post_id ) {
        if ( 'option' == $output_format || false !== strpos( $output_format, 'option__' ) ) {
            // This is a request for a value of a field that's part of an options page. 
            // Let's try to get the post ID of that page.

            // Array of field_name => asenha_options_page post ID pairs
            $options_pages_fields = get_options_pages_cf();
            $options_pages_field_names = array_keys( $options_pages_fields );
            
            // We assign the post ID of the options page
            if ( in_array( $field_name, $options_pages_field_names ) ) {
                $post_id = $options_pages_fields[$field_name];
            }
        } else {
            // Check if this field is part of an options page
            $field_info = get_cf_info( $field_name );

            if ( isset( $field_info['option_pages'] ) && ! empty( $field_info['option_pages'] ) ) {
                $options_pages_ids = array_keys( $field_info['option_pages'] );
                // Use the first ID for now, which is the most common use case.
                // i.e. a field group is most probably only assigned to a singe option page
                $post_id = $options_pages_ids[0];
            } else {
                // This is a request for a value of a field that's part of a page / post / custom post
                $post_id = ( ! empty( $post-> ID ) ) ? $post->ID : get_the_ID();                
            }
        }
    }
    
    // Get custom field info
    $cf_info = CFG()->get_field_info( $field_name, $post_id );
    $cf_type = isset( $cf_info['type'] ) ? $cf_info['type'] : 'text';

    // Set the base format when getting CFG()->get() return value
    if ( 'default' == $output_format ) {
        $base_format = 'api';
    } elseif ( 'raw' == $output_format) {
        $base_format = 'raw';
    } elseif ( 'option' == $output_format || false !== strpos( $output_format, 'option__' ) ) { 
        // For getting the value of a field that's part of an options page
        $base_format = 'api';
    } else {
        if ( 'radio' == $cf_type || 'select' == $cf_type || 'checkbox' == $cf_type ) {
            // So we get the option values and labels intact and not as an indexed array
            $base_format = 'api';       
        } else {
            $base_format = 'raw';
        }
    }
    
    // Get the exact format from a request for field value in an options page
    // e.g. from 'option__link' into 'link'
    if ( false !== strpos( $output_format, 'option__' ) ) {
        $output_format = str_replace( 'option__', '',$output_format );
    }
        
    // Get the value of custom field
    $options = array( 'format' => $base_format );
    $cf_value = CFG()->get( $field_name, $post_id, $options );

    // Process custom field value further
            
    if ( 'text' == $cf_type || 'textarea' == $cf_type || 'wysiwyg' == $cf_type || 'color' == $cf_type ) {

    	if ( 'default' == $output_format || 'raw' == $output_format || 'option' == $output_format ) {
            if ( 'wysiwyg' == $cf_type ) {
                return wpautop( $cf_value );                
            } else {
                return $cf_value;
            }
    	}
        
        if ( 'link' == $output_format ) {
            return '<a href="' . $cf_value . '">' . $cf_value . '</a>';
        }
        
        if ( 'email' == $output_format ) {
            return '<a href="mailto:' . $cf_value . '">' . $cf_value . '</a>';
        }

    } elseif ( 'number' == $cf_type ) {

        if ( 'default' == $output_format || 'raw' == $output_format || 'option' == $output_format ) {
            return $cf_value;       
        }

        if ( false !== strpos( $output_format, 'format__' ) ) {
            $output_parts = explode( '__', $output_format );
            $locale = ! empty( $output_parts[1] ) ? $output_parts[1] : 'en_US'; // e.g. en_US
            
            switch ( $locale ) {
                case 'comma':
                    $locale = 'en_US';
                    break;

                case 'dot':
                    $locale = 'de_DE';
                    break;

                case 'space':
                    $locale = 'fr_FR';
                    break;
            }

            if ( class_exists( 'NumberFormatter' ) ) {
                $formatter = new NumberFormatter( $locale, NumberFormatter::DECIMAL );
                return $formatter->format( $cf_value );             
            } else {
                return $cf_value;
            }

        }
                
    } elseif ( 'true_false' == $cf_type ) {

        switch ( $output_format ) {
            case 'default':
            case 'option':
                return ( 1 == $cf_value ) ? true : false;
                break;
            case 'raw':
                return $cf_value;
                break;
            case 'true_false':
                return ( 1 == $cf_value ) ? 'True' : 'False';
                break;
            case 'yes_no':
                return ( 1 == $cf_value ) ? 'Yes' : 'No';
                break;
            case 'check_cross':
                // True: https://icon-sets.iconify.design/fa-solid/check/
                // False: https://icon-sets.iconify.design/emojione-monotone/cross-mark/
                return ( 1 == $cf_value ) ? '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 512 512"><path fill="currentColor" d="m173.898 439.404l-166.4-166.4c-9.997-9.997-9.997-26.206 0-36.204l36.203-36.204c9.997-9.998 26.207-9.998 36.204 0L192 312.69L432.095 72.596c9.997-9.997 26.207-9.997 36.204 0l36.203 36.204c9.997 9.997 9.997 26.206 0 36.204l-294.4 294.401c-9.998 9.997-26.207 9.997-36.204-.001"/></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 64 64"><path fill="currentColor" d="M62 10.571L53.429 2L32 23.429L10.571 2L2 10.571L23.429 32L2 53.429L10.571 62L32 40.571L53.429 62L62 53.429L40.571 32z"/></svg>';
                break;
            case 'toggle_on_off':
                // True: https://icon-sets.iconify.design/la/toggle-on/
                // False: https://icon-sets.iconify.design/la/toggle-off/
                return ( 1 == $cf_value ) ? '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32"><path fill="currentColor" d="M9 7c-4.96 0-9 4.035-9 9s4.04 9 9 9h14c4.957 0 9-4.043 9-9s-4.043-9-9-9zm14 2c3.879 0 7 3.121 7 7s-3.121 7-7 7s-7-3.121-7-7s3.121-7 7-7"/></svg>' : '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32"><path fill="currentColor" d="M9 7c-.621 0-1.227.066-1.813.188a9.238 9.238 0 0 0-.875.218A9.073 9.073 0 0 0 .72 12.5c-.114.27-.227.531-.313.813A8.848 8.848 0 0 0 0 16c0 .93.145 1.813.406 2.656c.004.008-.004.024 0 .032A9.073 9.073 0 0 0 5.5 24.28c.27.114.531.227.813.313A8.83 8.83 0 0 0 9 24.999h14c4.957 0 9-4.043 9-9s-4.043-9-9-9zm0 2c3.879 0 7 3.121 7 7s-3.121 7-7 7s-7-3.121-7-7c0-.242.008-.484.031-.719A6.985 6.985 0 0 1 9 9m5.625 0H23c3.879 0 7 3.121 7 7s-3.121 7-7 7h-8.375C16.675 21.348 18 18.828 18 16c0-2.828-1.324-5.348-3.375-7"/></svg>';
                break;
        }
		
    } elseif ( 'radio' == $cf_type || 'select' == $cf_type || 'checkbox' == $cf_type ) {
        
		// Return array
    	if ( 'default' == $output_format || 'raw' == $output_format || 'option' == $output_format ) {
    		return $cf_value;
    	}

    	// Return comma separated labels (array values)
    	if ( 'values_c' == $output_format || 'labels' == $output_format ) {

            if ( is_array( $cf_value ) ) {
                $values = array_values( $cf_value );

                if ( count( $values ) > 1 ) {
                    return implode( ', ', $values );
                } else {
                    return $values[0];
                }
            } else {
                return;
            }

    	}

        // Return comma separated values (array keys)
        if ( 'keys_c' == $output_format || 'values' == $output_format ) {

            if ( is_array( $cf_value ) ) {
                $keys = array_keys( $cf_value );
                
                if ( count( $keys ) > 1 ) {
                    return implode( ', ', $keys );
                } else {
                    return $keys[0];
                }
            } else {
                return;
            }

        }

    } elseif ( 'date' == $cf_type ) {
    	
    	if ( 'default' == $output_format || 'raw' == $output_format || 'option' == $output_format ) {
    		return $cf_value;
    	} else {
    		return wp_date( $output_format, strtotime( $cf_value ) );
    	}
    	
    } elseif ( 'hyperlink' == $cf_type ) {
    	
    	if ( 'default' == $output_format || 'raw' == $output_format || 'option' == $output_format ) {
	    	return $cf_value;		
    	}
        
        if ( 'link' == $output_format ) {
            return get_cf_hyperlink( $cf_value );
        }

        if ( 'url' == $output_format ) {
            return $cf_value['url'];
        }
    	
    } elseif ( 'file' === $cf_type ) {
    	
    	$file_type = $cf_info['options']['file_type'];
    	$return_value = $cf_info['options']['return_value'];

        // For all file types
        if ( 'url' == $output_format ) {
            return wp_get_attachment_url( $cf_value );
        }

        // For all file types
        if ( 'file_link' == $output_format ) {
            $attachment_url = wp_get_attachment_url( $cf_value );
            return get_cf_file_link( $attachment_url );
        }

    	if ( 'image' === $file_type ) {
    		
	     	// Output attachment ID
	    	if ( 'default' == $output_format || 'raw' == $output_format || 'option' == $output_format ) {
	    		return $cf_value;
	    	}
            
            if ( ! empty( $cf_value ) ) {
                // Output image URL
                if ( false !== strpos( $output_format, 'image_url' ) ) {
                    $image_size = str_replace( 'image_url__', '', $output_format );
                    return wp_get_attachment_image_url( $cf_value, $image_size );
                }

                // Output actual image
                if ( false !== strpos( $output_format, 'image_view' ) ) {
                    $image_size = str_replace( 'image_view__', '', $output_format );
                    return get_cf_image_view( $cf_value, $image_size, $field_name_slug );
                }                
            } else {
                return;
            }

    	}

    	if ( 'video' === $file_type ) {
	     	// Output attachment ID
	    	if ( 'default' == $output_format || 'raw' == $output_format || 'option' == $output_format ) {
	    		return $cf_value;
	    	}

	     	// Output video player. $cf_value will be the attachment ID with a custom output of 'video_player'.
	    	if ( 'video_player' == $output_format ) {
	    		$file_url = wp_get_attachment_url( $cf_value );
                return get_cf_video_player( $file_url );
	    	}       		    		
    	}

    	if ( 'audio' === $file_type ) {
	     	// Output attachment ID
	    	if ( 'default' == $output_format || 'raw' == $output_format || 'option' == $output_format ) {
	    		return $cf_value;
	    	}

	     	// Output audio player. $cf_value will be the attachment ID with a custom output of 'audio_player'.
	    	if ( 'audio_player' == $output_format ) {
	    		$file_url = wp_get_attachment_url( $cf_value );
                return get_cf_audio_player( $file_url );
	    	}
	    }

    	if ( 'pdf' === $file_type ) {
	     	// Output attachment ID
	    	if ( 'default' == $output_format || 'raw' == $output_format || 'option' == $output_format ) {
	    		return $cf_value;
	    	}

	     	// Output PDF viewer. $cf_value will be the attachment ID with a custom output of 'audio_player'.
	    	if ( 'pdf_viewer' == $output_format ) {
	    		$file_url = wp_get_attachment_url( $cf_value );
                return (string) get_cf_pdf_viewer( $file_url );
	    	}
    	}

    	if ( 'any' === $file_type || 'file' === $file_type ) {
	     	// Output attachment ID
	    	if ( 'default' == $output_format || 'raw' == $output_format || 'option' == $output_format ) {
	    		return $cf_value;
	    	}
	    }

    } elseif ( 'gallery' === $cf_type ) {

        if ( 'raw' == $output_format || 'default' == $output_format || 'option' == $output_format ) {
            // raw: comma separated attachment IDs
            // default: indexed array of attachment IDs
            return $cf_value;
        }

        elseif ( false !== strpos( $output_format, 'gallery_grid' ) ) {
            $attachment_ids = $cf_value; // comma-separated attachment IDs

            // $output_format example: gallery_grid__medium__columns_3
            $output_parameters = explode( '__', $output_format );
            
            if ( isset( $output_parameters[1] ) && ! empty( $output_parameters[1] ) ) {
                $image_size = $output_parameters[1];
            } else {
                $image_size = 'medium';
            }

            if ( isset( $output_parameters[2] ) && ! empty( $output_parameters[2] ) ) {
                $columns_number = str_replace( 'columns_', '', $output_parameters[2] );
            } else {
                $columns_number = '4';
            }

            return get_cf_gallery_grid( $attachment_ids, $image_size, $columns_number );
        }

        elseif ( false !== strpos( $output_format, 'gallery_justified' ) ) {
            $attachment_ids = explode( ',', $cf_value );

            $image_size = str_replace( 'gallery_justified__', '', $output_format );
            $image_size = ! empty( $image_size ) ? $image_size : 'medium';
                        
            return get_cf_gallery_justified( $attachment_ids, $image_size );
        }

        elseif ( false !== strpos( $output_format, 'gallery_masonry' ) ) {
            $attachment_ids = explode( ',', $cf_value );

            $image_size = str_replace( 'gallery_masonry__', '', $output_format );
            $image_size = ! empty( $image_size ) ? $image_size : 'medium';

            return get_cf_gallery_masonry( $attachment_ids, $image_size );
        }
        
    } elseif ( 'term' == $cf_type ) {
        
        if ( 'default' == $output_format || 'raw' == $output_format || 'option' == $output_format ) {
            return $cf_value;       
        } else {
            return get_cf_terms( $cf_value, $output_format );            
        }
        
    } elseif ( 'user' == $cf_type ) {
        
        if ( 'default' == $output_format || 'raw' == $output_format || 'option' == $output_format ) {
            return $cf_value;       
        } else {
            return get_cf_users( $cf_value, $output_format );
        }
        
    } else {

    	if ( 'default' == $output_format || 'raw' == $output_format || 'option' == $output_format ) {
	        return $cf_value;
    	}

    }

}

function the_cf( $field_name = false, $output_format = 'default', $post_id = false ) {
	
	$cf_value = get_cf( $field_name, $output_format, $post_id );
	
	if ( ! is_array( $cf_value ) ) {
		echo $cf_value;
	} else {
		echo var_dump( $cf_value );		
	}

}

function cf_shortcode_cb( $shortcode_atts ) {
    
    $default_atts = array(
        'name'      => '',
        'output'    => 'default',
        'post_id'   => false,
    );
    
    $atts = shortcode_atts( $default_atts, $shortcode_atts );
    
    return get_cf( $atts['name'], $atts['output'], $atts['post_id'] );
    
}
add_shortcode( 'cf', 'cf_shortcode_cb' );

function get_cf_hyperlink( $hyperlink_array ) {
    $url = isset( $hyperlink_array['url'] ) ? $hyperlink_array['url'] : '/';
    $text = isset( $hyperlink_array['text'] ) ? $hyperlink_array['text'] : 'Link';
    $target = isset( $hyperlink_array['target'] ) ? $hyperlink_array['target'] : '_blank';

    return (string) '<a href="' . $url . '" target="' . $target . '">' . $text . '</a>';
}

function get_cf_file_link( $attachment_url ) {
    if ( ! empty( $attachment_url ) ) {
        $url_parts = explode( '/', $attachment_url );                   
    } else {
        $url_parts = array();
    }

    if ( is_array( $url_parts ) && ! empty( $url_parts ) ) {
        $file_name = $url_parts[count($url_parts)-1];               
    } else {
        $file_name = '';
    }
    return '<a href="' . $attachment_url . '" class="custom-field-file-link" target="_blank">' . $file_name . '</a>';    
}

function get_cf_gallery_grid( $attachment_ids = '', $image_size = 'medium', $columns_number = 5 ) {
    $output = '';
    if ( ! empty( $attachment_ids ) ) {
        // Reference: https://codepen.io/svelts/pen/ogboNV by Sven Lötscher
        $output .= '<style>
                    .ase-gallery-grid {
                        margin-bottom: 1em;
                    }
                    .ase-gallery-grid .gallery {
                        display: flex;
                        flex-wrap: wrap;
                        margin: -0.25em;
                        grid-gap: 0;
                    }
                    ';
        switch ( $columns_number ) {
            case '2';
                $output .= '.ase-gallery-grid .gallery-columns-2 .gallery-item {
                        max-width: 50%;
                    }
                    .block-editor-page .swp-rnk-preview .ase-gallery-grid .gallery-columns-2 .gallery-item {
                        max-width: 48%;
                    }';
                break;

            case '3';
                $output .= '.ase-gallery-grid .gallery-columns-3 .gallery-item {
                        max-width: 33.3333%;
                    }
                    .block-editor-page .swp-rnk-preview .ase-gallery-grid .gallery-columns-3 .gallery-item {
                        max-width: 32%;
                    }';
                break;

            case '4';
                $output .= '.ase-gallery-grid .gallery-columns-4 .gallery-item {
                        max-width: 25%;
                    }
                    .block-editor-page .swp-rnk-preview .ase-gallery-grid .gallery-columns-4 .gallery-item {
                        max-width: 23%;
                    }';
                break;

            case '5';
                $output .= '.ase-gallery-grid .gallery-columns-5 .gallery-item {
                        max-width: 20%;
                    }
                    .block-editor-page .swp-rnk-preview .ase-gallery-grid .gallery-columns-5 .gallery-item {
                        max-width: 18.5%;
                    }';
                break;

            case '6';
                $output .= '.ase-gallery-grid .gallery-columns-6 .gallery-item {
                        max-width: 16.6666%;
                    }
                    .block-editor-page .swp-rnk-preview .ase-gallery-grid .gallery-columns-6 .gallery-item {
                        max-width: 15%;
                    }';
                break;

            case '7';
                $output .= '.ase-gallery-grid .gallery-columns-7 .gallery-item {
                        max-width: 14.2857%;
                    }
                    .block-editor-page .swp-rnk-preview .ase-gallery-grid .gallery-columns-7 .gallery-item {
                        max-width: 13%;
                    }';
                break;

            case '8';
                $output .= '.ase-gallery-grid .gallery-columns-8 .gallery-item {
                        max-width: 12.5%;
                    }
                    .block-editor-page .swp-rnk-preview .ase-gallery-grid .gallery-columns-8 .gallery-item {
                        max-width: 11%;
                    }';
                break;

            case '9';
                $output .= '.ase-gallery-grid .gallery-columns-9 .gallery-item {
                        max-width: 11.1111%;
                    }
                    .block-editor-page .swp-rnk-preview .ase-gallery-grid .gallery-columns-9 .gallery-item {
                        max-width: 9.5%;
                    }';
                break;

            case '10';
                $output .= '.ase-gallery-grid .gallery-columns-10 .gallery-item {
                        max-width: 10%;
                    }
                    .block-editor-page .swp-rnk-preview .ase-gallery-grid .gallery-columns-10 .gallery-item {
                        max-width: 8.5%;
                    }';
                break;
        }
        $output .= '
            .ase-gallery-grid figure {
                margin-bottom: 0;
            }
            .ase-gallery-grid .gallery-item {
                box-sizing: border-box;
                display: inline-block;
                padding: 0.25em;
            }
            .ase-gallery-grid .gallery-item img {
                max-width: 100%;
                height: 100%;
            }
            .ase-gallery-grid .gallery-icon {
                height: 100%;
                padding: 0;
            }
            .ase-gallery-grid .gallery-icon img {
                height: 100%;
                object-fit: cover;
            }
            </style>';
        $wp_gallery = do_shortcode( '[gallery ids="' . $attachment_ids . '" size="' . $image_size . '" columns="' . $columns_number . '" ]' );
        $output .= '<div class="ase-gallery ase-gallery-grid">';
        $output .= $wp_gallery;
        $output .= '</div>';
    }

    return $output;    
}

function get_cf_gallery_justified( $attachment_ids = array(), $image_size = 'medium' ) {
    $output = '';
    if ( is_array( $attachment_ids ) && ! empty( $attachment_ids ) ) {
        // Reference: https://codepen.io/w3work/pen/bGGWNBQ by Sven
        $output .= '
            <style>
                :root {
                  --gallery-row-height: 80px;
                  --gallery-gap: .625em;
                }

                .ase-gallery-justified {
                  display: flex;
                  width: 100%;
                  overflow: hidden;
                  flex-wrap: wrap;
                  gap: 0.5em;
                  margin-bottom: 1em;
                  /* margin-bottom: calc(-1 * var(--gallery-gap, 1em)); */
                  /* margin-left: calc(-1 * var(--gallery-gap, 1em)); */
                }
                .ase-gallery-justified:after {
                  content: "";
                  flex-grow: 999999999;
                  min-width: var(--gallery-row-height);
                  height: 0;
                }
                .ase-gallery-justified a {
                  display: block;
                  height: var(--gallery-row-height);
                  flex-grow: 1;
                  /* margin-bottom: var(--gallery-gap, 1em);
                  margin-left: var(--gallery-gap, 1em);
                    overflow: hidden; */
                }
                .ase-gallery-justified a img {
                  height: var(--gallery-row-height);
                  object-fit: cover;
                  max-width: 100%;
                  min-width: 100%;
                  vertical-align: bottom;
                  transition: .375s;
                }
                .ase-gallery-justified a img:hover {
                    transform: scale(1.05);
                }

                @media only screen and (min-width: 768px) {
                  :root {
                    --gallery-row-height: 120px;
                  }
                }
                @media only screen and (min-width: 1280px) {
                  :root {
                    --gallery-row-height: 150px;
                  }
                }
            </style>';
        $output .= '<div class="ase-gallery ase-gallery-justified">';
        foreach ( $attachment_ids as $attachment_id ) {
            $image_full_url = wp_get_attachment_image_url( $attachment_id, 'full' );
            $image_thumbnail_url = wp_get_attachment_image_url( $attachment_id, $image_size );
            $output .= '<a href="' .$image_full_url. '"><img src="' . $image_thumbnail_url . '" /></a>';
        }                
        $output .= '</div>';
    }
    
    return $output;
}

function get_cf_gallery_masonry( $attachment_ids = array(), $image_size = 'medium' ) {
    $output = '';
    if ( is_array( $attachment_ids ) && ! empty( $attachment_ids ) ) {
        // Reference: https://codepen.io/svelts/pen/ogboNV by Sven Lötscher
        $output .= '
            <style>
            .ase-gallery-masonry {
                display: block;
                width: 100%;
                column-width: 180px;
                column-gap: .5em;
                margin-bottom: 1em;
            }
            .ase-gallery-masonry a {
                display: block;
                width: 100%;
                margin-bottom: .5em;
                overflow: hidden;
            }
            .ase-gallery-masonry a img {
                display: block;
                width: 100%;
                height: auto;
                transition: .375s;
            }
            .ase-gallery-masonry a img:hover {
                transform: scale(1.025);
            }
            </style>';
        $output .= '<div class="ase-gallery ase-gallery-masonry">';
        foreach ( $attachment_ids as $attachment_id ) {
            $image_full_url = wp_get_attachment_image_url( $attachment_id, 'full' );
            $image_thumbnail_url = wp_get_attachment_image_url( $attachment_id, $image_size );
            $output .= '<a href="' .$image_full_url. '"><img src="' . $image_thumbnail_url . '" /></a>';
        }                
        $output .= '</div>';
    }

    return $output;    
}

function get_cf_image_view( $attachment_id, $image_size, $field_name_slug ) {
    return '<img src="' . wp_get_attachment_image_url( $attachment_id, $image_size ) . '" class="' . esc_attr( $field_name_slug ) . ' ' . esc_attr( $image_size ) . '"/>';                    
    
}

function get_cf_video_player( $file_url ) {
    if ( filter_var( $file_url, FILTER_VALIDATE_URL ) && false !== strpos( $file_url, 'http' ) ) {
        $output = do_shortcode( '[video src="' . $file_url . '"]' );
        if ( function_exists( 'bricks_is_frontend' ) && bricks_is_frontend() ) {
            // Rendered by Bricks builder, do not add inline CSS, video player already displays fine
        } else {
            $output .= '<style>';
            if ( is_plugin_active( 'breakdance/plugin.php' ) || function_exists( '\Breakdance\DynamicData\registerField' ) || class_exists( '\Breakdance\DynamicData\Field' ) ) {
                // With Bricks builder, no need to define width or height. Player already looks fine. So, add/do nothing here.
            } else {
                // Let's add width and height so video player looks and works well in most themes.
                $output .= '.wp-video, video.wp-video-shortcode, .mejs-container.mejs-video, .mejs-overlay.load {
                        width: 100% !important;
                        height: 100% !important;
                    }';                        
            }
            // Let's output the rest of the inline CSS.
            $output .= '.mejs-container.mejs-video {
                    padding-top: 56.25%;
                }
                .wp-video, video.wp-video-shortcode {
                    max-width: 100% !important;
                }
                video.wp-video-shortcode {
                    position: relative;
                }
                .mejs-video .mejs-mediaelement {
                    position: absolute;
                    top: 0;
                    right: 0;
                    bottom: 0;
                    left: 0;
                }
                .mejs-video .mejs-controls {
                    /* display: none; */
                }
                .mejs-video .mejs-overlay-play {
                    top: 0;
                    right: 0;
                    bottom: 0;
                    left: 0;
                    width: auto !important;
                    height: auto !important;
                }
                </style>';
        }
        return $output;
    } else {
        return;
    }
}

function get_cf_audio_player( $file_url ) {
    if ( filter_var( $file_url, FILTER_VALIDATE_URL ) && false !== strpos( $file_url, 'http' ) ) {
        return do_shortcode( '[audio src="' . $file_url . '"]' );
    } else {
        return;
    }
}

function get_cf_pdf_viewer( $file_url ) {
    if ( filter_var( $file_url, FILTER_VALIDATE_URL ) && false !== strpos( $file_url, 'http' ) ) {
        $random_number = rand(1,1000);
        if ( function_exists( 'bricks_is_frontend' ) && bricks_is_frontend() ) {
            $width = '100%';
            $height = '640px';
        } else {
            $width = '48rem';
            $height = '32rem';
        }
        return '<div id="pdf-viewer-'. $random_number .'" class="pdfobject-viewer"></div>
                <style>
                .pdfobject-container { width: ' . $width . '; height: ' . $height . '; border: 1rem solid rgba(0,0,0,.1); }
                @media screen and (max-width: 768px) {
                    .pdfobject-container { width: 100%; height: 32rem; }
                }
                </style>
                <script src="' . ASENHA_URL . 'assets/premium/js/pdfobject.js"></script>
                <script>PDFObject.embed("' . $file_url . '", "#pdf-viewer-'. $random_number .'");</script>';
    } else {
        return;
    }
}

function get_cf_terms( $cf_value, $output_format ) {
    if ( is_array( $cf_value ) && count( $cf_value ) > 0 ) {
        switch ( $output_format ) {
            case 'names':
                $names = array();
                foreach( $cf_value as $term_id ) {
                    $term_id = (int) $term_id;
                    $term = get_term( $term_id );
                    $names[] = $term->name;
                }
                $names = implode(', ', $names );
                return $names;
                break;
                
            case 'names_archive_links':
                $names_archive_links = array();
                foreach( $cf_value as $term_id ) {
                    $term_id = (int) $term_id;
                    $term = get_term( $term_id );
                    $names_archive_links[] = '<a href="' . get_term_link( $term_id ) . '">' . $term->name . '</a>';
                }
                $names_archive_links = implode(', ', $names_archive_links );
                return $names_archive_links;
                break;

            case 'names_edit_links':
                $names_edit_links = array();
                foreach( $cf_value as $term_id ) {
                    $term_id = (int) $term_id;
                    $term = get_term( $term_id );
                    $names_edit_links[] = '<a href="' . get_edit_term_link( $term_id ) . '">' . $term->name . '</a>';
                }
                $names_edit_links = implode(', ', $names_edit_links );
                return $names_edit_links;
                break;
        }
    } else {
        return '';
    }
    
}

function get_cf_users( $cf_value, $output_format ) {
    if ( is_array( $cf_value ) && count( $cf_value ) > 0 ) {
        switch ( $output_format ) {
            case 'first_names':
                $first_names = array();
                foreach( $cf_value as $user_id ) {
                    $user_id = (int) $user_id;
                    $user = get_user_by( 'id', $user_id );
                    $first_names[] = $user->user_firstname;
                }
                $first_names = implode(', ', $first_names );
                return $first_names;
                break;

            case 'last_names':
                $last_names = array();
                foreach( $cf_value as $user_id ) {
                    $user_id = (int) $user_id;
                    $user = get_user_by( 'id', $user_id );
                    $last_names[] = $user->user_lastname;
                }
                $last_names = implode(', ', $last_names );
                return $last_names;
                break;

            case 'display_names':
                $display_names = array();
                foreach( $cf_value as $user_id ) {
                    $user_id = (int) $user_id;
                    $user = get_user_by( 'id', $user_id );
                    $display_names[] = $user->display_name;
                }
                $display_names = implode(', ', $display_names );
                return $display_names;
                break;

            case 'usernames':
                $usernames = array();
                foreach( $cf_value as $user_id ) {
                    $user_id = (int) $user_id;
                    $user = get_user_by( 'id', $user_id );
                    $usernames[] = $user->user_login;
                }
                $usernames = implode(', ', $usernames );
                return $usernames;
                break;
        }
    } else {
        return '';
    }
}

function get_cf_related_to( $field_name = false, $output_format = 'default', $base_format = 'raw', $post_id = false ) {

    $field_name_slug = str_replace( '_', '-', $field_name );

    if ( in_array( $base_format, array( 'raw', 'api', 'input' ) ) ) {
        $options = array( 'format' => $base_format );
    } else {
        $options = array( 'format' => 'raw' );
    }

    $related_to = CFG()->get( $field_name, $post_id, $options );

    if ( 'default' === $output_format ) {
        return $related_to;        
    }

    if ( false !== strpos( $output_format, 'titles_only_c' ) ) {
    	return cf_titles_only_c( $field_name_slug, $related_to );
    }
    
    if ( false !== strpos( $output_format, 'titles_only_v' ) ) {
    	return cf_titles_only_v( $field_name_slug, $output_format, $related_to );
    }

    if ( false !== strpos( $output_format, 'image_titles_v' ) ) {
    	return cf_image_titles_v( $field_name_slug, $related_to, $output_format );
    }

    if ( false !== strpos( $output_format, 'image_titles_h' ) ) {
    	return cf_image_titles_h( $field_name_slug, $related_to, $output_format );
    }

}

function the_cf_related_to( $field_name = false, $output_format = 'default', $base_format = 'raw', $post_id = false) {
	
	$related_to = get_cf_related_to( $field_name, $output_format, $base_format, $post_id );
	
	if ( ! is_array( $related_to ) ) {
		echo $related_to;
	} else {
		echo var_dump( $related_to );		
	}

}

function cf_related_to_shortcode_cb( $shortcode_atts ) {
    
    $default_atts = array(
        'name'      => '',
        'output'    => 'default',
        'base'      => 'raw',
        'post_id'   => false,
    );
    
    $atts = shortcode_atts( $default_atts, $shortcode_atts );
    
    return get_cf_related_to( $atts['name'], $atts['output'], $atts['base'], $atts['post_id'] );
    
}
add_shortcode( 'cf_related_to', 'cf_related_to_shortcode_cb' );

function get_cf_related_from( $field_name = false, $output_format = 'default', $related_from_post_type = false, $related_from_post_status = 'publish', $field_type = 'relationship', $post_id = false ) {

    $field_name_slug = str_replace( '_', '-', $field_name );

    global $post;
    
    if ( false === $post_id ) {
        $post_id = ( ! empty( $post-> ID ) ) ? $post->ID : get_the_ID();
    }
    
    $options = array( 
        'field_type'    => $field_type,
    );

    if ( false !== $field_name ) {
        $options['field_name'] = $field_name;            
    }

    if ( false !== $related_from_post_type ) {
        $options['post_type'] = $related_from_post_type;            
    }

    if ( false !== $related_from_post_status ) {
        $options['post_status'] = $related_from_post_status;            
    }

    $related_from = CFG()->get_reverse_related( $post_id, $options );
            
    if ( 'default' == $output_format ) {
        return $related_from;
    }
    
    if ( false !== strpos( $output_format, 'titles_only_c' ) ) {
    	return cf_titles_only_c( $field_name_slug, $related_from );
    }

    if ( false !== strpos( $output_format, 'titles_only_v' ) ) {
    	return cf_titles_only_v( $field_name_slug, $output_format, $related_from );
    }

    if ( false !== strpos( $output_format, 'image_titles_v' ) ) {
        return cf_image_titles_v( $field_name_slug, $related_from, $output_format );
    }

    if ( false !== strpos( $output_format, 'image_titles_h' ) ) {
    	return cf_image_titles_h( $field_name_slug, $related_from, $output_format );
    }

}

function the_cf_related_from( $field_name = false, $output_format = 'default', $related_from_post_type = false, $related_from_post_status = 'publish', $field_type = 'relationship', $post_id = false ) {
	
	$related_from = get_cf_related_from( $field_name, $output_format, $related_from_post_type, $related_from_post_status, $field_type, $post_id );
	
	if ( ! is_array( $related_from ) ) {
		echo $related_from;
	} else {
		echo var_dump( $related_from );		
	}

}

function cf_related_from_shortcode_cb( $shortcode_atts ) {
    
    $default_atts = array(
        'name'          => '',
        'output'        => 'default',
        'post_type'     => false,
        'post_status'   => 'publish',
        'field_type'    => 'relationship',
        'post_id'       => false,
    );
    
    $atts = shortcode_atts( $default_atts, $shortcode_atts );
    
    return get_cf_related_from( $atts['name'], $atts['output'], $atts['post_type'], $atts['post_status'], $atts['field_type'], $atts['post_id'] );
    
}
add_shortcode( 'cf_related_from', 'cf_related_from_shortcode_cb' );

/**
 * Output comma separated, linked titles of the related posts/objects
 * @param  string 	$field_name_slug   	slugified $field_name to use for class names
 * @param  array 	$related_ids_array 	array of related post IDs
 */
function cf_titles_only_c( $field_name_slug, $related_ids_array ) {
    $output = '<div class="related related-' . esc_attr( $field_name_slug ) . ' titles-c">';
    if ( is_array( $related_ids_array ) && count( $related_ids_array ) > 0 ) {

        $count = count( $related_ids_array );
        $i = 1;

        foreach ( $related_ids_array as $object_id ) {

            $post = get_post( $object_id );

            if ( is_object( $post ) ) {
                $output .= '<a class="related-item" href="' . get_the_permalink( $object_id ) . '" style="font-size:inherit;font-weight:600;">' . trim( $post->post_title ) . '</a>';

                if ( $i < $count ) { 
                    $output .= ', '; 
                }
            }

            $i++;

        }

    }

    $output .= '</div>';
    
    return $output;
}

/**
 * Output list of linked titles of the related posts/objects
 * @param  string 	$field_name_slug   	slugified $field_name to use for class names
 * @param  array 	$related_ids_array 	array of related post IDs
 */
function cf_titles_only_v( $field_name_slug, $output_format, $related_ids_array ) {
	$output_format = explode( '__', $output_format );
	$list_type = isset( $output_format[1] ) ? $output_format[1] : 'div';
	
	if ( 'div' == $list_type ) {
		$parent_element = 'div';
		$child_element = 'div';
	}
	
	if ( 'ol' == $list_type ) {
		$parent_element = 'ol';
		$child_element = 'li';
	}

	if ( 'ul' == $list_type ) {
		$parent_element = 'ul';
		$child_element = 'li';
	}
	
    $output = '<' . $parent_element . ' class="related related-' . $field_name_slug . ' titles-v">';

    if ( is_array( $related_ids_array ) && count( $related_ids_array ) > 0 ) {

        foreach ( $related_ids_array as $object_id ) {

            $post = get_post( $object_id );

            if ( is_object( $post ) ) {
                $output .= '<' . $child_element . ' class="related-item">
                	<a href="' . get_the_permalink( $object_id ) . '" style="font-size:inherit;font-weight:600;">' . $post->post_title . '</a>
                </' . $child_element . '>';
            }

        }

    }

    $output .= '</' . $parent_element . '>';
    
    return $output;
    
}

/**
 * Output list of linked image-titles of the related posts/objects
 * @param  string 	$field_name_slug   	slugified $field_name to use for class names
 * @param  array 	$related_ids_array 	array of related post IDs
 */
function cf_image_titles_v( $field_name_slug, $related_ids_array, $output_format ) {

    $output = '<div class="related related-' . $field_name_slug . ' image-titles-v" style="display:flex;flex-direction:column;gap:16px;flex-wrap:wrap;">';

    if ( is_array( $related_ids_array ) && count( $related_ids_array ) > 0 ) {

        $output_format_parts = explode( '__', $output_format );
        $image_size = isset( $output_format_parts[1] ) ? $output_format_parts[1] : 'thumbnail';

        foreach ( $related_ids_array as $object_id ) {

            $post = get_post( $object_id );

            if ( is_object( $post ) ) {
                $output .= '<a class="related-item" href="' . get_the_permalink( $post->ID ) . '">
                    <div class="related-item-div" style="display:flex;flex-direction:row;">
                        <img src="' . get_the_post_thumbnail_url( $post->ID, $image_size ) . '" class="custom-field-file-image-as-id" style="width:50px;height:50px;margin-right:8px;">
                        <div style="font-size:inherit;font-weight:600;">' .
                            $post->post_title .
                        '</div>
                </div>
                </a>';
            }

        }

    }

    $output .= '</div>';

    return $output;
}

/**
 * Output horizontal grid of linked image-titles of the related posts/objects
 * @param  string 	$field_name_slug   	slugified $field_name to use for class names
 * @param  array 	$related_ids_array 	array of related post IDs
 */
function cf_image_titles_h( $field_name_slug, $related_ids_array, $output_format ) {

    $output = '<div class="related related-' . $field_name_slug . ' image-titles-h" style="display:flex;gap:16px;flex-wrap:wrap;">';

    if ( is_array( $related_ids_array ) && count( $related_ids_array ) > 0 ) {

        $output_format_parts = explode( '__', $output_format );
        $image_size = isset( $output_format_parts[1] ) ? $output_format_parts[1] : 'thumbnail';

        foreach ( $related_ids_array as $object_id ) {

            $post = get_post( $object_id );
            $featured_image_id = get_post_thumbnail_id( $object_id );
            $image_info = wp_get_attachment_image_src( $featured_image_id, $image_size );

            if ( $image_info ) {
            	$image_width = $image_info[1];
            } else {
            	$image_width = get_option( $image_size . '_size_w', '150' );
            }

            if ( is_object( $post ) ) {

                $output .= '<a class="related-item" href="' . get_the_permalink( $post->ID ) . '">
                    <div class="related-item-div" style="max-width:' . $image_width . 'px">
                        ' . get_the_post_thumbnail( $post->ID, $image_size ) . '
                        <div style="font-size:inherit;font-weight:600;text-align:center;">
                            ' . $post->post_title . '
                        </div>
                </div>
                </a>';

            }

        }

    }

    $output .= '</div>';
    
    return $output;

}