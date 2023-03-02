<?php

namespace DonationManager\shortcodes;

/**
 * Returns the `Donors in Your Area` display
 */
function get_donors_in_your_area( $atts ){
  $args = shortcode_atts([
    'pcode' => 30331,
    'radius' => 15,
    'days' => 30,
  ], $atts );

  wp_enqueue_script( 'donors-by-zipcode' );

  $html = file_get_contents( DONMAN_PLUGIN_PATH . 'lib/html/donors-in-your-area.html' );
  return $html;
}
add_shortcode( 'donors_in_your_area', __NAMESPACE__ . '\\get_donors_in_your_area' );