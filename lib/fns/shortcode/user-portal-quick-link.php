<?php

namespace DonationManager\shortcodes;
use function DonationManager\templates\{render_template};

/**
 * Gets the User Portal Quick Link HTML.
 *
 * @return     string  The user portal quick link html.
 */
function get_user_portal_quick_link_html(){
  $current_user = wp_get_current_user();

  $html = '';
  if( $current_user ){
    $org_id = get_user_meta( $current_user->ID, 'organization', true );
    $trans_dept_id = get_user_meta( $current_user->ID, 'department', true );

    if( 'organization' == get_post_type( $org_id ) && 'trans_dept' == get_post_type( $trans_dept_id ) ){
      $quick_link = home_url('/step-one/?oid=' . $org_id . '&tid=' . $trans_dept_id );
      $html = render_template( 'user-portal-quick-link', [ 'quick_link' => $quick_link ] );
    }
  }
  return $html;
}
add_shortcode( 'user_portal_quick_link', __NAMESPACE__ . '\\get_user_portal_quick_link_html' );