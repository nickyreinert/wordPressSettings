<?php

// handle changes with care, make backups, test thoroughly, 
// implement step by step, unless you know what you're doing
// if you see something that's nonsense, I'd be happy if you drop me a line
//
// this file belongs to your child theme, like /wp-content/themes/aChild/functions.php

// remove google fonts or fonts hosted on foreign premises in general
// this requires some manual action beforehand
// you need to find out, how google fonts (or whatever font you want to remove) 
// is being implemented, like what handler-name
// to find out, search the source code of the parent's theme 
// for a line containing fonts.google, it will contain the unique handler
// which you have to pass to the wp_dequeue_style function. 
add_action( 'wp_print_styles', 'dequeue_google_fonts_style', 99999);

// if this does not work, you may just hook into the style function of your
// parents theme and "purge" it - but this is really the sledgehammer approach
function rowling_load_style() {return FALSE;}

// disable emojis and co.
add_action( 'init', 'disable_emojis' );
add_filter( 'emoji_svg_url', '__return_false' );
add_action( 'init', 'disable_wp_emojicons' );

// load scripts in footer (ref. https://www.kevinleary.net/move-javascript-bottom-wordpress/)
// this may lead to flickering (raw site content loads, then styles are applied), so
// disable entire hook if required, or edit the callback function add_action('after_setup_theme', 'footer_enqueue_scripts');

// strip version info from static files to make them cacheable again (ref. https://wordpress.stackexchange.com/questions/195235/move-wordpress-native-javascript-to-bottom-of-page)
add_filter( 'script_loader_src', 'remove_version_parameter', 15, 1 );
add_filter( 'style_loader_src', 'remove_version_parameter', 15, 1 );

// defer java script (ref. https://kinsta.com/blog/defer-parsing-of-javascript/#functions)
add_filter( 'script_loader_tag', 'defer_parsing_of_js', 10 );
   
// deactivate gravatar 
add_filter( 'option_show_avatars', '__return_false' );

// deactivate XML RPC and remove reference from html header, 
// only if you don't need pingbacks or want to manager your site with an app
add_filter( 'xmlrpc_enabled', '__return_false' );
remove_action('wp_head', 'rsd_link');

// remove Windows Live Writer manifest from header (only if you do not use the Windows Live Writer, of course)
remove_action( 'wp_head', 'wlwmanifest_link');

// remove shortlinks from HTML header and HTTP header
remove_action( 'wp_head', 'wp_shortlink_wp_head');
add_filter('after_setup_theme', 'remove_shortlink_from_http_header');

//hide wordpress version, it will not protect you against attackers, 
// but at least creates a mist
remove_action('wp_head', 'wp_generator');

// remove links to related pages (used by some browser for navigation purposes
remove_action('wp_head', 'start_post_rel_link');
remove_action('wp_head', 'index_rel_link');
remove_action('wp_head', 'adjacent_posts_rel_link');

// removes REST-API references from HTML header and HTTP header
remove_action('wp_head', 'rest_output_link_wp_head', 10);
remove_action('template_redirect', 'rest_output_link_header', 11, 0);

// allow REST-API for authenticated users only (ref. https://developer.wordpress.org/rest-api/frequently-asked-questions/)
// (REST-API should not be disabled completely, as it's be used by lots of plugins 
// as well as some backend features
add_filter('rest_authentication_errors', 'rest_api_auth');

// deactivate embedding-feature (ref. https://kinsta.com/de/wissensdatenbank/deaktivierst-embeds-wordpress/#disable-embeds-code)
add_action( 'init', 'disable_embeds_code_init', 9999 );

// --------------------------------------------------------------------------------------------------------
// that's all, following lines just contain the actuall callbacks

function rest_api_auth($result) {
    // If a previous authentication check was applied,
    // pass that result along without modification.
    if ( true === $result || is_wp_error( $result ) ) {
        return $result;
    }
 
    // No authentication has been performed yet.
    // Return an error if user is not logged in.
    if ( ! is_user_logged_in() ) {
        return new WP_Error(
            'rest_not_logged_in',
            __( 'You are not currently logged in.' ),
            array( 'status' => 401 )
        );
    }
 
    // Our custom authentication check should have no effect
    // on logged-in requests
    return $result;
}

function disable_embeds_code_init() {
    remove_action( 'rest_api_init', 'wp_oembed_register_route' );
    add_filter( 'embed_oembed_discover', '__return_false' );
    remove_filter( 'oembed_dataparse', 'wp_filter_oembed_result', 10 );
    remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
    remove_action( 'wp_head', 'wp_oembed_add_host_js' );
    add_filter( 'tiny_mce_plugins', 'disable_embeds_tiny_mce_plugin' );
    add_filter( 'rewrite_rules_array', 'disable_embeds_rewrites' );
    remove_filter( 'pre_oembed_result', 'wp_filter_pre_oembed_result', 10 );
}

function disable_embeds_tiny_mce_plugin($plugins) {
    return array_diff($plugins, array('wpembed'));
}

function disable_embeds_rewrites($rules) {
    foreach($rules as $rule => $rewrite) {
        if(false !== strpos($rewrite, 'embed=true')) {
            unset($rules[$rule]);
        }
    }
    return $rules;
}

function remove_shortlink_from_http_header() {

    remove_action( 'template_redirect', 'wp_shortlink_header', 11);

}

function dequeue_google_fonts_style() {

      wp_dequeue_style( ['rowling_google_fonts', 'rowling_fontawesome'] );
      
}

/**
 * Disable the emoji's
 */
function disable_emojis() {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    add_filter( 'tiny_mce_plugins', 'disable_emojis_tinymce' );
    add_filter( 'wp_resource_hints', 'disable_emojis_remove_dns_prefetch', 10, 2 );
}
   

/**
 * Filter function used to remove the tinymce emoji plugin.
 *
 * @param array $plugins
 * @return array Difference betwen the two arrays
 */
function disable_emojis_tinymce( $plugins ) {

    if ( is_array( $plugins ) ) {
        
        return array_diff( $plugins, array( 'wpemoji' ) );

    } else {
        
        return array();

    }
}
   
/**
* Remove emoji CDN hostname from DNS prefetching hints.
*
* @param array $urls URLs to print for resource hints.
* @param string $relation_type The relation type the URLs are printed for.
* @return array Difference betwen the two arrays.
*/
function disable_emojis_remove_dns_prefetch( $urls, $relation_type ) {
    if ( 'dns-prefetch' == $relation_type ) {
    
        /** This filter is documented in wp-includes/formatting.php */
        $emoji_svg_url = apply_filters( 'emoji_svg_url', 'https://s.w.org/images/core/emoji/2/svg/' );

        $urls = array_diff( $urls, array( $emoji_svg_url ) );
    }

    return $urls;
}    

function disable_emojicons_tinymce( $plugins ) {

    if ( is_array( $plugins ) ) {
    
        return array_diff( $plugins, array( 'wpemoji' ) );

    } else {

      return array();

    }
}

function disable_wp_emojicons() {

    // all actions related to emojis
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
  
    // filter to remove TinyMCE emojis
    add_filter( 'tiny_mce_plugins', 'disable_emojicons_tinymce' );

}

function footer_enqueue_scripts() {
    remove_action('wp_head', 'wp_print_scripts');
    remove_action('wp_head', 'wp_print_head_scripts', 9);
    remove_action('wp_head', 'wp_enqueue_scripts', 1);
    add_action('wp_footer', 'wp_print_scripts', 5);
    add_action('wp_footer', 'wp_enqueue_scripts', 5);
    add_action('wp_footer', 'wp_print_head_scripts', 5);
}

function remove_version_parameter($src){

    // Check if version parameter exist
    $parts = explode( '?ver', $src );
    
    // return without version parameter
    return $parts[0];
    
}

function defer_parsing_of_js( $url ) {
    
    if ( is_user_logged_in() ) return $url; //don't break WP Admin
    
    if ( strpos( $url, '.js' ) === FALSE) return $url; // only process JavaScript files
    
    if ( strpos( $url, 'jquery.js' ) ) return $url;  // skip JQuery, you may remove that line, depending on your needs
    
    return str_replace( ' src', ' defer src', $url );

}
