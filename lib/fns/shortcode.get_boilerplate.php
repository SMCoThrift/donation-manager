<?php

namespace DonationManager\shortcodes;

/**
 * Returns requested HTML
 *
 * @param      array  $atts {
 *   @type  string  $title The boilerplate title.
 * }
 *
 * @return     string  The requested HTML boilerplate.
 */
function get_boilerplate( $atts ){
  extract( shortcode_atts( array(
    'title' => null,
  ), $atts ) );

  if( is_null( $title ) )
    return;

  switch( $title ){
    case 'about-pmd':
    case 'aboutpmd':
      $html = '<h3 style="clear: both; display: block; margin-top: 40px;">About PickUpMyDonation.com</h3>
Our mission is to connect you with organizations who will pick up your donation. Our donation process is quick and simple. Schedule your donation pick up with our online donation pick up form. Our system sends your donation directly to your chosen pick up provider. They will then contact you to finalize your selected pick up date.';
    break;
  }

  return $html;
}
add_shortcode( 'boilerplate', __NAMESPACE__ . '\\get_boilerplate' );