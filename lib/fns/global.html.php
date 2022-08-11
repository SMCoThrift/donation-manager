<?php

namespace DonationManager\globals;

/**
 * Compiles HTML to our global HTML variable
 *
 * @param      string  $html    The html
 * @param      bool    $append  If TRUE, add the HTML to the end of the string.
 */
function add_html( $html = '', $append = true ){
  global $donman_html;

  if( $append ){
    $donman_html.= $html;
  } else {
    $donman_html = $html . $donman_html;
  }
}

function get_html(){
  global $donman_html;

  return $donman_html;
}