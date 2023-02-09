<?php
namespace DonationManager\shortcodes;
use function DonationManager\utilities\{get_alert};

/**
 * Displays content for a City Page Realtor Description.
 *
 * @return     string  The Realtor Description HTML
 */
function city_page_realtor_description(){
  global $post;
  $html = [];
  $nonprofit_partners = get_post_meta( $post->ID, 'non_profit_partner', true );
  if( is_array( $nonprofit_partners ) && 0 < count( $nonprofit_partners ) ){
    foreach( $nonprofit_partners as $nonprofit_id ){
      $desc = get_post_meta( $nonprofit_id, 'realtor_description', true );
      if( ! empty( $desc ) ){
        $html[] = '<h3>Buying or Selling in ' . get_post_meta( $post->ID, 'city', true ) . '</h3>';
        $html[] = $desc;
      }
    }
  }

  return implode( "\n", $html );
}
add_shortcode( 'city_page_realtor_description', __NAMESPACE__ . '\\city_page_realtor_description' );

/**
 * Displays content for a City Page sidebar.
 *
 * @return     string  City Page sidebar HTML
 */
function city_page_sidebar(){
  global $post;
  $realtor_ads = [];
  $nonprofit_partners = get_post_meta( $post->ID, 'non_profit_partner', true );
  $html = [];
  $html[] = '<style>.widget.partner p{text-align: center;}</style>';

  /**
   * Featured Non-Profit Partner
   */
  if( is_array( $nonprofit_partners ) && 0 < count( $nonprofit_partners ) ){
    $html[] = '<div class="widget partner">';
    $partner_text = ( 1 < count( $nonprofit_partners ) )? 'Partners' : 'Partner' ;
    $html[] = '<h3>Featured Non Profit ' . $partner_text . '</h3>';
    foreach( $nonprofit_partners as $nonprofit_id ){
      $website = ( $url = get_post_meta( $nonprofit_id, 'website', true ) )? $url : '' ;
      $format = ( ! empty( $website ) )? '<a href="%2$s" target="_blank">%1$s</a>' : '%1$s';
      if( $thumbnail = get_the_post_thumbnail( $nonprofit_id, 'large', ['style'=>'max-width: 100%; height: auto;'] ) )
          $html[] = sprintf( $format, $thumbnail, $website );
      $html[] = '<p>' . sprintf( $format, get_the_title( $nonprofit_id ), $website ) . '</p>';

      $realtor_ad = ( $realtor_ad = get_post_meta( $nonprofit_id, 'realtor_ad_medium_banner', true ) )? $realtor_ad : false ;
      $realtor_ad_link = ( $realtor_ad_link = get_post_meta( $nonprofit_id, 'realtor_ad_link', true ) )? $realtor_ad_link : false ;

      if( $realtor_ad )
          $realtor_ads[] = '<a href="' . $realtor_ad_link . '"><img src="' . wp_get_attachment_url( $realtor_ad['ID'] ) . '" style="width: 100%; height: auto;"></a>';
    }
    $html[] = '</div>';
  }

  /**
   * Priority Partner
   */
  if( $priority_id = get_post_meta( $post->ID, 'priority_partner', true ) ){
    $priority_website = get_post_meta( $priority_id, 'website', true );
    $priority_format = ( ! empty( $priority_website ) )? '<a href="%2$s" target="_blank">%1$s</a>' : '%1$s';

    $html[] = '<div class="widget partner"><h3>Priority Partner</h3>';
    if( $priority_thumbnail = get_the_post_thumbnail( $priority_id, 'large', ['style'=>'max-width: 100%; height: auto;'] ) )
        $html[] = sprintf( $priority_format, $priority_thumbnail, $priority_website );

    $html[] = '<p>' . sprintf( $priority_format, get_the_title( $priority_id ), $priority_website ) . '</p>';
    $html[] = '</div>';
  }

  /**
   * REALTOR AD
   */
  if( 0 < count($realtor_ads) ){
    $html[] = '<div class="widget" style="margin: 1em 0;">';
    foreach( $realtor_ads as $ad ){
        $html[] = $ad;
    }
    $html[] = '</div>';
  }

  if( $sidebar_content = get_post_meta( $post->ID, 'sidebar_content', true ) ){
      $sidebar_content = apply_filters( 'the_content', $sidebar_content );
      $html[] = '<div class="widget">';
      $html[] = '<h3>Additional Information</h3>';
      $html[] = $sidebar_content;
      $html[] = '</div>';
  }

  return implode( "\n", $html );
}
add_shortcode( 'city_page_sidebar', __NAMESPACE__ . '\\city_page_sidebar' );