<?php

namespace DonationManager\realtors;

/**
 * Given an array of Org IDs, returns an array of Realtor Ads in HTML.
 *
 * @param      array          $orgs   The orgs
 *
 * @return     array|boolean  The realtor ads.
 */
function get_realtor_ads( $orgs = [] ){
  if( ! is_array( $orgs ) )
    return false;

  if( 0 == count( $orgs ) )
    return false;

  $realtor_ads = [];
  foreach( $orgs as $org ){
    $org_id = ( is_numeric( $org ) )? $org : $org['org_id'] ;
    $pickup_settings = get_field( 'pickup_settings', $org_id );

    $realtor_ad_url = null;
    if( array_key_exists( 'realtor_ad_standard_banner', $pickup_settings ) )
      $realtor_ad_url = $pickup_settings['realtor_ad_standard_banner']['url'];

    $realtor_ad_link = null;
    if( array_key_exists( 'realtor_ad_link', $pickup_settings ) )
      $realtor_ad_link = $pickup_settings['realtor_ad_link'];

    // JAKE asks me to update this so that it will do square ads.

    $ad_html = false;
    if( $realtor_ad_url ){
        $ad_html = '<img src="' . $realtor_ad_url . '" style="width: 100%; height: auto;">';
        if( $realtor_ad_link )
            $ad_html = '<a href="' . $realtor_ad_link . '" target="_blank">' . $ad_html . '</a>';
    }

    if( $ad_html )
        $realtor_ads[] = $ad_html;
  }

  return $realtor_ads;
}