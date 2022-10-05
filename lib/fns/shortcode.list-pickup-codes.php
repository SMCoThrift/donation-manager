<?php

namespace DonationManager\shortcodes;
use function DonationManager\utilities\{get_alert};
use function DonationManager\transdepts\{get_trans_dept_ids,get_pickup_codes_html};

function get_pickup_codes( $atts ){
  extract( shortcode_atts( array(
    'id' => null,
    'tid' => null,
    'title' => 'donation pick up for',
    'keyword' => null,
    'location' => null,
    'showheading' =>  true,
    'donate_button' => '',
  ), $atts ) );

  if( is_null( $id ) )
    return get_alert([ 'description' => '<strong>Error!</strong> Pickup Code List: Org ID can not be null!', 'type' => 'danger' ]);

  $organization = get_the_title( $id );

  if( is_null( $keyword ) )
    $keyword = $organization;

      if( ! is_null( $tid ) && is_numeric( $tid ) ){
        $ids = [ $tid ];
      } else {
        // Select all trans_dept where OrgID=$id.
        $ids = get_trans_dept_ids( $id );
        if( 0 === count( $ids ) ){
          return get_alert([
            'description' => '<strong>Warning:</strong> Pickup Code List: No Transportation Depts for given org ID (' . $id . ')!',
            'type' => 'warning'
          ]);
        }
      }

  // 2. For each trans_dept, SELECT all pickup_codes.
  $links = '';
  foreach( $ids as $trans_dept_id ){
    $links.= get_pickup_codes_html( $trans_dept_id, $title );
  }

  if( true == $showheading ){
    if( is_null( $location ) )
      $location = $keyword;
    $format = '<h2>%2$s donation pick up &ndash; Zip Codes</h2><p><em>Looking for a donation pick up provider in the %3$s area?</em> Look no further...
<em>%4$s</em> picks up donations in the following %3$s area Zip Codes. Click on the button or your Zip Code to donate now:</p>%5$s<div class="ziprow">%1$s<br class="clearfix" /></div>';
    $html = sprintf( $format, $links, $keyword, $location, $organization, $donate_button );
  } else {
    $format = '<div class="ziprow">%1$s<br class="clearfix" /></div>';
    $html = sprintf( $format, $links );
  }

  return $html;
}
add_shortcode( 'list-pickup-codes', __NAMESPACE__ . '\\get_pickup_codes' );