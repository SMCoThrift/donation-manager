<?php

namespace DonationManager\transdepts;
use function DonationManager\templates\{render_template};

/**
 * Returns HTML for transportation department ads.
 *
 * DEPRECATED/USED - see note in
 * lib/fns/shortcode/donationform/select-your-organization.php
 *
 * @param int $id Transportation Department ID.
 * @return string HTML for banner ads, or FALSE if no ads.
 */
function get_trans_dept_ads( $id = null ){
  if( is_null( $id ) )
      return;

  $html = false;

  if( have_rows( 'ads', $id ) ):
    $x = 0;
    while( have_rows( 'ads', $id ) ): the_row();
      $ad = get_sub_field( 'ad' );
      $link = get_sub_field( 'ad_url' );
      uber_log('🔔 $ad = ' . print_r( $ad, true ) );
      if( is_array( $ad ) && array_key_exists( 'url', $ad ) )
        $ads[$x]['src'] = $ad['url'];
      if( $link )
        $ads[$x]['link'] = $link;
      $x++;
    endwhile;
  endif;

  /*
  for( $x = 1; $x <= 3; $x++ ){
    $graphic = get_post_meta( $id, 'ad_' . $x . '_graphic', true );
    if( $graphic ){
      $attachment = wp_get_attachment_image_src( $graphic['ID'], 'full' );
      $ads[$x]['src'] = $attachment[0];
      $link = get_post_meta( $id, 'ad_' . $x . '_link', true );
      if( $link )
        $ads[$x]['link'] = $link;
    }
  }
  */
  $banners = [];
  if( isset( $ads ) && 0 < count( $ads ) ){
    for( $x = 1; $x <= 3; $x++ ){
      if( isset( $ads[$x] ) && $ads[$x] ){
        $banner = '<img src="' . $ads[$x]['src'] . '" style="max-width: 100%;" />';
        if( $ads[$x]['link'] )
          $banner = '<a href="' . $ads[$x]['link'] . '" target="_blank" rel="nofollow">' . $banner . '</a>';
        $banners[] = [ 'banner' => $banner ];
      }
    }
    if( isset( $banners ) && 0 < count( $banners ) )
      $html = render_template( 'banner-ad-row', [ 'banners' => $banners ] );
  }

  return $html;
}