<?php

namespace DonationManager\shortcodes;

function get_organization_description( $atts ){
  extract( shortcode_atts( array(
    'id' => null,
    'showlead' => true,
    'location' => null,
  ), $atts ) );

  if( is_null( $id ) )
    return 'Organization Description: ID can not be null!';

  $org = get_post( $id );

  if( $org ){
    if( empty( $org->post_content ) )
      return 'Org Desc: No content enteried for <em>' . $org->post_title . '</em> (ID: ' . $id . ').';

    $organization_description = apply_filters( 'the_content', $org->post_content );

    if( true == $showlead )
      $organization_description = '<p class="lead" style="text-align: center; font-style: italic;">' . $org->post_title . ' provides ' . $location . ' donation pick up</p>' . $organization_description;

    return $organization_description;
  }
}
add_shortcode( 'organization-description', __NAMESPACE__ . '\\get_organization_description' );