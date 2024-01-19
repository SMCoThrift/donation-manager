<?php

namespace DonationManager\transdepts;
use function DonationManager\templates\{render_template};
use function DonationManager\utilities\{get_alert};

/**
 * Gets the pickup codes html.
 *
 * DEPRECATED? - 10/05/2022 (15:21) - searched production database
 * for the string `list-pickup-codes` which is the shortcode that
 * calls this function, and found no instances of it.
 *
 * @param      int     $tid    The trans_dept ID.
 * @param      string  $title  The title
 *
 * @return     string  The pickup codes html.
 */
function get_pickup_codes_html( $tid, $title = 'donation pick up for' ){
  $pickup_codes = wp_get_object_terms( $tid, 'pickup_code' );

  if( empty( $pickup_codes ) )
    return;

  if( ! is_wp_error( $pickup_codes ) ){
    $columns = 6;
    $links = '';
    $col = 1;
    $last = end( $pickup_codes );
    reset( $pickup_codes );
    foreach( $pickup_codes as $pickup_code ){
      if( 1 === $col )
        $links.= '<div class="row" style="margin-bottom: 30px; text-align: center; font-size: 160%; font-weight: bold;">';
      $format = '<div class="col-md-2"><a href="/select-your-organization/?pcode=%1$s" title="%2$s %1$s">%1$s</a></div>';
      $links.= sprintf( $format, $pickup_code->name, $title );
      if( $columns == $col ){
        $links.= '</div>';
        $col = 1;
      } else {
        $col++;
        // If we're on the last element of the array, close the </div> for the div.row.
        if( $last === $pickup_code )
          $links.= '</div>';
      }
    }
  }

  return $links;
}

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
    return get_alert([ 'description' => '<strong>Error!</strong> Pickup Code List: Org ID can not be null!','type' =>  'danger'] );

  $organization = get_the_title( $id );

  if( is_null( $keyword ) )
    $keyword = $organization;

  if( ! is_null( $tid ) && is_numeric( $tid ) ){
    $ids = array( $tid );
  } else {
    // Select all trans_dept where OrgID=$id.
    $ids = get_trans_dept_ids( $id );
    if( 0 === count( $ids ) )
      return get_alert([ 'description' => '<strong>Warning:</strong> Pickup Code List: No Transportation Depts for given org ID (' . $id . ')!', 'type' => 'warning'] );
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

/**
 * Retrieves a transportation department contact
 */
function get_trans_dept_contact( $trans_dept_id = '' ) {
    if( empty( $trans_dept_id ) || ! is_numeric( $trans_dept_id ) )
        return false;

    $trans_dept_contact = [
      'contact_title' => get_field( 'contact_title', $trans_dept_id ),
      'contact_name'  => get_field( 'contact_name', $trans_dept_id ),
      'contact_email' => get_field( 'contact_email', $trans_dept_id ),
      'cc_emails'     => get_field( 'cc_emails', $trans_dept_id ),
      'phone'         => get_field( 'phone', $trans_dept_id ),
    ];

    // Populate any empty fields with the Parent Org's Default Transportation Contact fields:
    $org_id = null;
    $org_default_trans_dept_contact = null;
    $check_for_empty = [ 'contact_title', 'contact_name', 'contact_email', 'phone' ];
    foreach( $check_for_empty as $array_key ){
      if( empty( $trans_dept_contact[ $array_key ] ) ){
        if( empty( $org_id ) )
          $org_id = get_field( 'organization', $trans_dept_id );

        if( empty( $org_default_trans_dept_contact ) )
          $org_default_trans_dept_contact = get_field( 'default_trans_dept_contact', $org_id );

        if( is_array( $org_default_trans_dept_contact ) && array_key_exists( $array_key, $org_default_trans_dept_contact ) )
          $trans_dept_contact[ $array_key ] = $org_default_trans_dept_contact[ $array_key ];
      }
    }

    return $trans_dept_contact;
}

/**
 * Returns an array of trans_dept IDs for a given Org ID.
 *
 * @since 1.1.1
 *
 * @param int $id Organization ID.
 * @return array Array of trans_dept IDs.
 */
function get_trans_dept_ids( $id = null ){
    $ids = [];

    if( is_null( $id ) )
        return $ids;

    $trans_depts = get_posts([
      'meta_key'      => 'organization',
      'meta_compare'  => '=',
      'meta_type'     => 'NUMERIC',
      'meta_value'    => $id,
      'numberposts'   => -1,
      'post_type'     => 'trans_dept',
    ]);

    if( 0 === count( $trans_depts ) )
        return $ids;

    foreach( $trans_depts as $trans_dept ){
      $ids[] = $trans_dept->ID;
    }

    return $ids;
}