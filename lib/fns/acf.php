<?php

/**
 * Saves ACF configuration as JSON.
 *
 * @param      string  $path   The path
 *
 * @return     string  Returns ACF JSON save location.
 */
function donman_acf_json_save_point( $path ) {
  $path = plugin_dir_path( __FILE__ ) . '../acf-json';
  return $path;
}
add_filter('acf/settings/save_json', 'donman_acf_json_save_point');

/**
 * Loads ACF configuration from JSON.
 *
 * @param      array  $paths  The paths
 *
 * @return     array  Array of ACF JSON locations.
 */
function donman_acf_json_load_point( $paths ) {
    // remove original path
    unset($paths[0]);

    // append path
    $paths[] = plugin_dir_path( __FILE__ ) . '../acf-json';

    // return
    return $paths;
}
add_filter('acf/settings/load_json', 'donman_acf_json_load_point');

if( function_exists( 'acf_add_options_page' ) ){
  acf_add_options_page([
    'page_title'  => 'Donation Manager Settings',
    'menu_title'  => 'Donation Manager Settings',
    'menu_slug'   => 'donation-manager-settings',
    'capability'  => 'edit_posts',
  ]);
}