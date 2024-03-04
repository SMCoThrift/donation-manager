<?php

namespace DonationManager\shortcodes;
use function DonationManager\utilities\{get_alert,get_boilerplate};
use function DonationManager\transdepts\{get_trans_dept_ids,get_pickup_codes};
use function DonationManager\templates\{render_template};

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

/**
 * Gets the HTML for linking to the user's Org Page within the User Portal
 *
 * @param      array  $atts {
 *   @type  int  $org_id The Organization ID.
 * }
 *
 * @return     string  The link to the user's organizational page.
 */
function get_user_portal_org_page_html( $atts ){
  $args = shortcode_atts([
    'org_id' => null,
  ], $atts );

  if( is_null( $args['org_id'] ) )
    return get_alert(['description' => '$org_id is <code>null</code>.', 'type' => 'danger']);

  if( ! is_numeric( $args['org_id'] ) )
    return get_alert(['description' => '$org_id must be a number.', 'type' => 'danger']);

  if( 'organization' != get_post_type( $args['org_id'] ) )
    return get_alert(['description' => 'No Organization found for ID ' . $args['org_id'] . '.', 'type' => 'danger']);

  $show_org_page = get_field( 'org_page', $args['org_id'] );
  if( ! $show_org_page )
    return '<h2>Your Organization SEO Page</h2>' . get_alert(['description' => 'Your Organization SEO Page is not currently enabled. Please contact <a href="mailto:support@pickupmydonation.com?subject=Our%20Organization%20SEO%20Page">support@pickupmydonation.com</a> for details on how to get it activated.', 'type' => 'info']);

  $org_page_url = get_org_page_url( ['org_id' => $args['org_id'] ] );

  return render_template( 'user-portal-organization-page', ['org_page_url' => $org_page_url]);
}
add_shortcode( 'user_portal_org_page', __NAMESPACE__ . '\\get_user_portal_org_page_html' );

/**
 * Returns the permalink for an Organization CPT.
 *
 * @param      array  $atts {
 *   @type  int  $org_id The Organization ID.
 * }
 *
 * @return     string  The organization page url.
 */
function get_org_page_url( $atts ){
    $args = shortcode_atts([
      'org_id' => null,
    ], $atts );

    if( is_null( $args['org_id'] ) || ! is_numeric( $args['org_id'] ) ){
      $current_user = wp_get_current_user();
      if( $current_user )
        $org_id = get_user_meta( $current_user->ID, 'organization', true );
    } else {
      $org_id = $args['org_id'];
    }

    $url = '';

    if( 'organization' != get_post_type( $org_id ) )
      return $url;

    $show_org_page = get_field( 'org_page', $org_id );
    if( ! $show_org_page )
      return $url;

    $url = get_permalink( $org_id );

    return $url;
}
add_shortcode( 'get_org_page_url', __NAMESPACE__ . '\\get_org_page_url' );