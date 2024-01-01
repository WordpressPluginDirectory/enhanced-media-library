<?php

if ( ! defined( 'ABSPATH' ) )
	exit;



/**
 *  wpuxss_eml_mimes_validate
 *
 *  @type     callback function
 *  @since    1.0
 *  @created  15/10/13
 */

if ( ! function_exists( 'wpuxss_eml_mimes_validate' ) ) {

    function wpuxss_eml_mimes_validate( $input ) {

        if ( ! $input ) $input = array();


        if ( isset( $_POST['eml-restore-mime-types-settings'] ) ) {

            $input = get_site_option( 'wpuxss_eml_mimes_backup', array() );

            add_settings_error(
                'mime-types',
                'eml_mime_types_restored',
                __('MIME Types settings restored.', 'enhanced-media-library'),
                'updated'
            );
        }
        else {

            add_settings_error(
                'mime-types',
                'eml_mime_types_saved',
                __('MIME Types settings saved.', 'enhanced-media-library'),
                'updated'
            );
        }


        foreach ( $input as $type => $mime ) {

            $sanitized_type = wpuxss_eml_sanitize_extension( $type );

            if ( $sanitized_type !== $type ) {

                $input[$sanitized_type] = $input[$type];
                unset($input[$type]);
                $type = $sanitized_type;
            }

            $input[$type]['filter'] = isset( $mime['filter'] ) && !! $mime['filter'] ? 1 : 0;
            $input[$type]['upload'] = isset( $mime['upload'] ) && !! $mime['upload'] ? 1 : 0;

            $input[$type]['mime'] = sanitize_mime_type($mime['mime']);
            $input[$type]['singular'] = sanitize_text_field($mime['singular']);
            $input[$type]['plural'] = sanitize_text_field($mime['plural']);
        }

        return $input;
    }
}



/**
 *  wpuxss_eml_sanitize_extension
 *
 *  Based on the original sanitize_key
 *
 *  @since    1.0
 *  @created  24/10/13
 */

if ( ! function_exists( 'wpuxss_eml_sanitize_extension' ) ) {

    function wpuxss_eml_sanitize_extension( $key ) {

        $key = strtolower( $key );
        $key = preg_replace( '/[^a-z0-9|]/', '', $key );
        return $key;
    }
}



/**
 *  wpuxss_eml_post_mime_types
 *
 *  @since    1.0
 *  @created  03/08/13
 */

add_filter( 'post_mime_types', 'wpuxss_eml_post_mime_types' );

if ( ! function_exists( 'wpuxss_eml_post_mime_types' ) ) {

    function wpuxss_eml_post_mime_types( $post_mime_types ) {

        $wpuxss_eml_mimes = get_option('wpuxss_eml_mimes');

        if ( ! empty( $wpuxss_eml_mimes ) ) {

            foreach ( $wpuxss_eml_mimes as $extension => $mime ) {

                if ( (bool) $mime['filter'] ) {

                    $mime_type = sanitize_mime_type( $mime['mime'] );

                    $post_mime_types[$mime_type] = array(
                        esc_html( $mime['plural'] ),
                        'Manage ' . esc_html( $mime['plural'] ),
                        _n_noop( esc_html( $mime['singular'] ) . ' <span class="count">(%s)</span>', esc_html( $mime['plural'] ) . ' <span class="count">(%s)</span>' )
                    );
                }
            }
        }

        return $post_mime_types;
    }
}



/**
 *  wpuxss_eml_upload_mimes
 *
 *  Allowed mime types
 *
 *  @since    1.0
 *  @created  03/08/13
 */

add_filter( 'upload_mimes', 'wpuxss_eml_upload_mimes' );

if ( ! function_exists( 'wpuxss_eml_upload_mimes' ) ) {

    function wpuxss_eml_upload_mimes( $existing_mimes = array() ) {

        $wpuxss_eml_mimes = get_option('wpuxss_eml_mimes');

        if ( ! empty( $wpuxss_eml_mimes ) ) {

            foreach ( $wpuxss_eml_mimes as $extension => $mime ) {

                $extension = wpuxss_eml_sanitize_extension( $extension );


                if ( (bool) $mime['upload'] ) {
                    $existing_mimes[$extension] = sanitize_mime_type( $mime['mime'] );
                }
                else {
                    unset( $existing_mimes[$extension] );
                }
            }
        }

        return $existing_mimes;
    }
}



/**
 *  wpuxss_eml_check_filetype_and_ext
 *
 *  Allowed mime types
 *
 *  @since    2.8
 *  @created  10/2020
 */

add_filter( 'wp_check_filetype_and_ext', 'wpuxss_eml_check_filetype_and_ext', 10, 5 );

if ( ! function_exists( 'wpuxss_eml_check_filetype_and_ext' ) ) {

    function wpuxss_eml_check_filetype_and_ext( $types, $file, $filename, $mimes, $real_mime = false ) {

        $wpuxss_eml_mimes = get_option('wpuxss_eml_mimes');

        if ( empty( $wpuxss_eml_mimes ) ) {
            return $types;
        }
        
        foreach ( $wpuxss_eml_mimes as $extension => $mime ) {

            if ( (bool) $mime['upload'] ) {
            
                if ( false !== strpos( $filename, '.'.$extension ) ) {
                    $types['ext'] = wpuxss_eml_sanitize_extension( $extension );
                    $types['type'] = sanitize_mime_type( $mime['mime'] );
                }
            }
        }

        return $types;
    }
}



/**
 *  wpuxss_eml_mime_types
 *
 *  All mime Types
 *
 *  @since    1.0
 *  @created  03/08/13
 */

add_filter( 'mime_types', 'wpuxss_eml_mime_types' );

if ( ! function_exists( 'wpuxss_eml_mime_types' ) ) {

    function wpuxss_eml_mime_types( $default_mimes ) {

        $new_mimes = array();
        $wpuxss_eml_mimes = get_option( 'wpuxss_eml_mimes' );

        if ( false !== $wpuxss_eml_mimes ) {

            foreach ( $wpuxss_eml_mimes as $extension => $mime ) {

                $extension = wpuxss_eml_sanitize_extension( $extension );
                $new_mimes[$extension] = sanitize_mime_type( $mime['mime'] );
            }

            return $new_mimes;
        }

        return $default_mimes;
    }
}



/**
 *  wp_generate_attachment_metadata
 *
 *  Add dimentions for SVG mime type
 *
 *  @type     filter callback
 *  @since    2.8.9
 *  @created  12/2021
 */

add_filter( 'wp_generate_attachment_metadata', function( $metadata, $attachment_id, $context ) {

    if ( get_post_mime_type( $attachment_id ) == 'image/svg+xml' ) {
        $svg_path = get_attached_file( $attachment_id );
        $dimensions = wpuxss_svg_dimensions( $svg_path );
        $metadata['width'] = $dimensions->width;
        $metadata['height'] = $dimensions->height;
    }
    return $metadata;

}, 10, 3 );



/**
 *  wp_prepare_attachment_for_js
 *
 *  Pass SVG dimensions to the attachment popup
 *
 *  @type     filter callback
 *  @since    2.8.9
 *  @created  12/2021
 */

add_filter( 'wp_prepare_attachment_for_js', function( $response, $attachment, $meta) {

    if ( $response['mime'] == 'image/svg+xml' && empty( $response['sizes'] ) ) {

        $svg_path = get_attached_file( $attachment->ID );

        if( ! file_exists( $svg_path ) ) {
            $svg_path = $response['url'];
        }

        $dimensions = wpuxss_svg_dimensions( $svg_path );
        $response['sizes'] = array(
            'full' => array(
                'url'         => $response['url'],
                'width'       => $dimensions->width,
                'height'      => $dimensions->height,
                'orientation' => $dimensions->width > $dimensions->height ? 'landscape' : 'portrait'
            )
        );
    }
    return $response;

}, 10, 3 );



/**
 *  wpuxss_svg_dimensions
 *
 *  Get SVG dimensions
 *
 *  @since    2.8.9
 *  @created  12/2021
 */

if ( ! function_exists( 'wpuxss_svg_dimensions' ) ) {

    function wpuxss_svg_dimensions( $svg ) {

        $svg = simplexml_load_file( $svg );
        $width = 0;
        $height = 0;

        if ( $svg ) {

            $attributes = $svg->attributes();

            if( isset( $attributes->width, $attributes->height ) ) {
                $width = (int) $attributes->width;
                $height = (int) $attributes->height;
            } elseif ( isset( $attributes->viewBox ) ) {
                $sizes = explode( ' ', $attributes->viewBox );
                if( isset( $sizes[2], $sizes[3] ) ) {
                    $width = (int) $sizes[2];
                    $height = (int) $sizes[3];
                }
            }
        }
        return (object) array( 'width' => $width, 'height' => $height );
    }
}
