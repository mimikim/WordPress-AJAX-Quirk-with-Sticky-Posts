<?php
function ajax_load_posts() {

    // set up variables
    $category_id = $_REQUEST['selected_category'];
    $post_format = $_REQUEST['selected_type'];
    $post_format_text =  'post-format-' . $post_format;

    $selected_posts = [];
    $pagenumber = $_REQUEST['page_number'];
    $posts_per_page = 10;

    // for example: ( page 1 - 1 ) * 10 = 0, offset by 0
    // ( page 2 - 1 ) * 10 = 10, offset by 10, etc.
    $offset = ( $pagenumber - 1 ) * $posts_per_page;

    $theme_post_formats = get_theme_support( 'post-formats' );
    $theme_post_formats_array = $theme_post_formats[0];

    // assemble array of all available post formats in a queryable form
    foreach( $theme_post_formats_array as $key => $value ) {
        $theme_post_formats_array[$key] = 'post-format-' . $value;
    }

    $args = array (
        'posts_per_page'    => -1,
        'order'             => 'DESC'
    );

    // if category has been selected
    if ( $category_id != 0 ) {
        $args['cat'] = $category_id;
    }

    // if post format has been selected (is a string because mixed type)
    if ( $post_format != '0' ) {

        // add post_format taxonomy to query arguments
        $args['tax_query'][] = array(
            'taxonomy'  => 'post_format',
            'field'     => 'slug'
        );

        // if format is default "post"
        if ( $post_format == 'article' ) {
            // then ignore all posts that have a post_format
            $args['tax_query'][0]['terms'] = $theme_post_formats_array;
            $args['tax_query'][0]['operator'] = 'NOT IN';

        } else {
            // otherwise, query selected post_format
            $args['tax_query'][0]['terms'] = $post_format_text;
        }
    }

    // determine count of all requested story posts
    $total_posts = get_posts($args);
    $total_posts_count = count( $total_posts );

    // list pluck IDs only
    $total_posts_id = wp_list_pluck( $total_posts, 'ID' );

    /*
     *  WP_Query by default organizes sticky posts at the top, get_posts does not.
     *  However, since this request is being served via AJAX in an independent php file,
     *  there is no post_id associated with this particular snippet of code.
     *  Meaning that, WP_Query cannot fulfill its default sticky_post function
     *  as found in /wp-includes/query.php, starting at line 3751. ¯\_(ツ)_/¯
     */

    $sticky = get_option( 'sticky_posts' );

    // there are sticky posts, do custom functionality
    if ( is_array($sticky) && !empty($sticky) ) {

        // get all sticky posts that fulfill the $args
        $args['post__in'] = $sticky;

        $sticky_posts = get_posts( $args );
        $sticky_posts_id= wp_list_pluck( $sticky_posts, 'ID' );

        // merge $total_posts_id into the end of $sticky
        $merged_total = array_merge( $sticky_posts_id, $total_posts_id );

        // remove duplicates and re-indexes
        $unique_total = array_values( array_unique( $merged_total ) );

        // return a slice of 10 elements, starting at the $offset location
        $selected_posts = array_slice( $unique_total, $offset, $posts_per_page );

    } else {

        // else, do wordpress default

        // add pagination/offet
        $args['posts_per_page'] = $posts_per_page;
        $args['offset'] = $offset;

        $query_posts = get_posts( $args );
        $selected_posts = wp_list_pluck( $query_posts, 'ID' );
    }

    // set up variables
    set_query_var( 'posts' , $selected_posts );
    set_query_var( 'total_count' , $total_posts_count );
    set_query_var( 'pagenumber' , $pagenumber );

    // get partial that will load output
    get_template_part('partials/load_posts');

    wp_die();
}