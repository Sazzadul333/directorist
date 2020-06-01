<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// atbdp_get_shortcode_template_paths
function atbdp_get_shortcode_template_paths( $template_file ) {
    $theme_template_file  = '/directorist/shortcodes/' . $template_file . '.php';
    $theme_template_path  = get_stylesheet_directory() . $theme_template_file;
    $plugin_template_path = ATBDP_TEMPLATES_DIR . 'public-templates/shortcodes/' . $template_file . '.php';

    return array(
        'theme'  => $theme_template_path,
        'plugin' => $plugin_template_path,
    );
}

// atbdp_get_template
function atbdp_get_template( $template_file, $args = array(), $template = null, $extract_template = false ) {
    if ( is_array( $args ) ) {
        extract( $args );
    }

    if ( $template && is_object( $template ) && true == $extract_template ) {
        extract( (array)$template );
    }

    $theme_template  = '/directorist/' . $template_file . '.php';
    $plugin_template = ATBDP_TEMPLATES_DIR . 'public-templates/' . $template_file . '.php';

    if ( file_exists( get_stylesheet_directory() . $theme_template ) ) {
        $file = get_stylesheet_directory() . $theme_template;
    } elseif ( file_exists( get_template_directory() . $theme_template ) ) {
        $file = get_template_directory() . $theme_template;
    } else {
        $file = $plugin_template;
    }

    include $file;
}

// atbdp_get_template
function atbdp_get_ext_template( $template_file, $extension_file, $args = array(), $template = null, $extract_template = false ) {
    if ( is_array( $args ) ) {
        extract( $args );
    }

    if ( $template && is_object( $template ) && true == $extract_template ) {
        extract( (array)$template );
    }

    $theme_template  = '/directorist/' . $template_file . '.php';
    $plugin_template = $extension_file . '.php';

    if ( file_exists( get_stylesheet_directory() . $theme_template ) ) {
        $file = get_stylesheet_directory() . $theme_template;
    } elseif ( file_exists( get_template_directory() . $theme_template ) ) {
        $file = get_template_directory() . $theme_template;
    } else {
        $file = $plugin_template;
    }

    include $file;
}

// atbdp_get_shortcode_template
function atbdp_get_shortcode_template( $template, $args = array(), $helper = null, $extract_helper = false ) {
    $template = 'shortcodes/' . $template;
    atbdp_get_template( $template, $args, $helper, $extract_helper );
}

// atbdp_return_shortcode_template
function atbdp_return_shortcode_template( $template, $args = array() ) {
    $template = 'shortcodes/' . $template;
    ob_start();
    atbdp_get_template( $template, $args );

    return ob_get_clean();
}

// atbdp_get_shortcode_template
function atbdp_get_shortcode_ext_template( $template_file, $extension_file, $args = array(), $helper = null, $extract_helper = false ) {
    $template_file = 'shortcodes/extension/' . $template_file;
    atbdp_get_ext_template( $template_file, $extension_file, $args, $helper, $extract_helper );
}

// atbdp_get_widget_template
function atbdp_get_widget_template( $template, $args = array(), $helper = null, $extract_helper = false ) {
    $template = 'widgets/' . $template;
    atbdp_get_template( $template, $args, $helper, $extract_helper );
}

// atbdp_listings_header
function atbdp_listings_header( $atts = array() ) {
    ob_start();

    if ( ! empty( $atts['header'] ) && 'yes' !== $atts['header'] ) {
        return '';
    }

    $model = new Directorist_All_Listings( $atts );

    atbdp_get_shortcode_template( 'listings-archive/listings-header', null, $model, true );
    $header = ob_get_clean();

    /**
     * @since 6.3.5
     *
     * @filter key: atbdp_listing_header_html
     */
    $template_data = (array)$model;
    extract( $template_data );

    $filter_args = compact( 'display_header', 'header_container_fluid', 'search_more_filters_fields', 'listing_filters_button', 'header_title', 'listing_filters_icon', 'display_viewas_dropdown', 'display_sortby_dropdown', 'filters', 'view_as_text', 'view_as_items', 'sort_by_text', 'sort_by_items', 'listing_location_address', 'filters_button' );
    echo apply_filters( 'atbdp_listing_header_html', $header, $filter_args );
}

// atbdp_listings_loop
function atbdp_listings_loop( $loop_file, $atts = array() ) {
    $model     = new Directorist_All_Listings( $atts );
    $loop_data = $model->listings_loop_data();

    atbdp_get_shortcode_template( "listings-archive/loop/$loop_file", $loop_data, $model, true );
}

// atbdp_listings_map
function atbdp_listings_map( $atts ) {
    $model = new Directorist_All_Listings( $atts );
    $select_listing_map = $model->select_listing_map;
    
    if ( 'openstreet' === $select_listing_map ) {
        $model->load_openstreet_map();
        return;
    }

    if ( 'google' === $select_listing_map ) {
        $model->load_google_map();
    }
}

// atbdp_search_result_page_link
function atbdp_search_result_page_link() {
    echo ATBDP_Permalink::get_search_result_page_link();
}