<?php

namespace DonationManager\shortcodes;
use function DonationManager\templates\{render_template};
use function DonationManager\utilities\{get_alert};

function get_alert_shortcode( $atts, $content = '' ){
  $args = shortcode_atts([
    'type' => 'info',
    'title' => null,
  ], $atts, $shortcode = '' );

  return get_alert([ 'type' => $args['type'], 'description' => $content ]);
}
add_shortcode( 'getalert', __NAMESPACE__ . '\\get_alert_shortcode' );
