<?php

namespace DonationManager\shortcodes;
use function DonationManager\utilities\{get_alert};

function get_donate_now_button( $atts, $content = null ){
    extract( shortcode_atts( array(
      'id' => null,
      'label' => 'Donate Now',
      'showlead' => true,
      'tid' => null,
      'title' => '',
  ), $atts ) );

  if( is_null( $id ) )
    return get_alert(['description' => '<strong>Error!</strong> Please specify an org ID as id="##".', 'type' => 'danger' ] );

  if( is_null( $tid ) )
    return get_alert(['description' => '<strong>Error!</strong> Please specify a Transportation Department ID as tid="##".', 'type' => 'danger']);

  $button_html = '<div style="margin-bottom: 40px;"><a class="btn btn-primary btn-lg" style="display: block; margin: 10px auto; clear: both; width: 360px;" href="' . get_site_url() . '/step-one?oid=%1$d&amp;tid=%2$d" title="%4$s">%3$s</a></div>';

  if( ! empty( $content ) )
    $label = $content;

  $html = sprintf( $button_html, $id, $tid, $label, $title );
  if( true == $showlead ){
    $lead = sprintf( '<p>We accept a wide variety of items for donation pick up. Schedule your <a href="' . get_site_url() . '/step-one?oid=%1$d&amp;tid=%2$d">donation pick up</a> today.</p>', $id, $tid );
    $html = $lead . $html;
  }

  return $html;
}