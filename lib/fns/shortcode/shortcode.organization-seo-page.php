<?php

namespace DonationManager\shortcodes;
use function DonationManager\utilities\{get_alert,get_boilerplate};
use function DonationManager\transdepts\{get_trans_dept_ids,get_pickup_codes};

function get_organization_seo_page( $atts ){
  extract( shortcode_atts( array(
      'id' => null,
      'keyword' => null,
      'location' => null,
      'label' => null,
      'tid' => null,
  ), $atts ) );

  if( is_null( $id ) )
    return get_alert([ 'description' => '<strong>Error!</strong> No $id passed to get_organization_seo_page().', 'type' => 'danger' ] );

  $html[] = DonationManager\shortcodes\get_organization_description( [ 'id' => $id, 'location' => $location ] );

  // Get all Trans
  $trans_dept_ids = get_trans_dept_ids( $id );
      if( 0 === count( $trans_dept_ids ) )
        return get_alert([ 'description' => '<strong>Warning:</strong> Pickup Code List: No Transportation Depts for given org ID (' . $id . ')!', 'type' => 'warning' ] );

      $x = 1;
      foreach( $trans_dept_ids as $tid ){
        $donate_now_button = DonationManager\shortcodes\get_donate_now_button( [ 'id' => $id, 'tid' => $tid, 'label' => $label, 'showlead' => $showlead ] );
        $html[] = get_pickup_codes( array( 'id' => $id, 'tid' => $tid, 'keyword' => $keyword, 'location' => $location, 'title' => $location . ' donation pick up', 'donate_button' => $donate_now_button ) );

        // Show the donate now button lead paragraph for the last set of pickup codes
        $showlead = ( $x == count( $trans_dept_ids ) )? true : false;
        $html[] = get_donate_now_button( array( 'id' => $id, 'tid' => $tid, 'label' => $label, 'showlead' => $showlead ) );
        $x++;
      }

  $html[] = get_boilerplate(['title' => 'about-pmd']);

  return implode( "\n", $html );
}
add_shortcode( 'organization-seo-page', __NAMESPACE__ . '\\get_organization_seo_page' );