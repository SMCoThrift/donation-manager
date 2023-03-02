<?php

namespace DonationManager\shortcodes;
use function DonationManager\templates\{render_template};
use function DonationManager\utilities\{get_alert};

function get_alert_shortcode( $atts, $content = '' ){
  $args = shortcode_atts([
    'type'        => 'info',
    'title'       => null,
    'css_classes' => null,
  ], $atts, $shortcode = '' );

  return get_alert([ 'type' => $args['type'], 'title' => $args['title'], 'description' => $content, 'css_classes' => $args['css_classes'] ]);
}
add_shortcode( 'getalert', __NAMESPACE__ . '\\get_alert_shortcode' );
