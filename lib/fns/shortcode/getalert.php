<?php

namespace DonationManager\shortcodes;
use function DonationManager\templates\{render_template};
use function DonationManager\utilities\{get_alert};

/**
 * Shortcode handler for displaying an alert box.
 *
 * This shortcode renders an alert box with a specified type, title, 
 * and optional CSS classes. The alert content is passed as the enclosed content.
 *
 * @param array  $atts    {
 *     Optional. An array of shortcode attributes.
 *
 *     @type string $type        The type of alert (e.g., 'info', 'warning', 'error'). Default 'info'.
 *     @type string $title       The title of the alert. Default null.
 *     @type string $css_classes Additional CSS classes to apply. Default null.
 * }
 * @param string $content The enclosed content to be used as the alert description.
 * @return string The generated alert box HTML.
 */
function get_alert_shortcode( $atts, $content = '' ){
  $args = shortcode_atts([
    'type'        => 'info',
    'title'       => null,
    'css_classes' => null,
  ], $atts, $shortcode = '' );

  return get_alert([ 'type' => $args['type'], 'title' => $args['title'], 'description' => $content, 'css_classes' => $args['css_classes'] ]);
}
add_shortcode( 'getalert', __NAMESPACE__ . '\\get_alert_shortcode' );
