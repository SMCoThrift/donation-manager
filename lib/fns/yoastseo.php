<?php

namespace DonationManager\yoastseo;

/**
 * Hooks to `wpseo_metadesc` provided by Yoast SEO.
 *
 * @since x.x.x
 *
 * @param string $description Meta description for current page.
 * @return string Filtered meta description.
 */
function add_seo_page_metadesc( $description ){
  global $post;
  if( property_exists( $post, 'post_content' ) && ! is_null( $post->post_content ) && has_shortcode( $post->post_content, 'organization-seo-page' ) ){
    //return 'This is a test description.';

    $regex_pattern = get_shortcode_regex();
    preg_match ( '/'.$regex_pattern.'/s', $post->post_content, $regex_matches );
    if( $regex_matches[2] == 'organization-seo-page' ){
      //  Parse the `id` and `location` attributes from the shortcode
      preg_match( '/id=[\"\']?([0-9]+)[\"\']?/', $regex_matches[3], $matches );
      if( $matches )
        $id = $matches[1];

      preg_match( '/keyword=[\"\']{1}(.*)[\"\']{1}/U', $regex_matches[3], $matches );
      if( $matches )
        $location = $matches[1];

            if( isset( $location ) && isset( $id ) ){
              $organization = get_the_title( $id );
              $format = 'Looking for a donation pick up provider in the %1$s area? Look no further...%2$s picks up donations in the following %1$s area Zip Codes.';
              $description = sprintf( $format, $location, $organization );
            }
    }
  }

  return $description;
}
add_filter( 'wpseo_metadesc', __NAMESPACE__ . '\\add_seo_page_metadesc', 11, 1 );