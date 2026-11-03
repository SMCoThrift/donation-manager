<?php
namespace DonationManager\shortcodes;
use const DonationManager\orphanfollowup\{UPGRADED_META};
use function DonationManager\orphanfollowup\{check_upgrade_link,get_partners};
use function DonationManager\utilities\{get_alert};

/**
 * The page the orphan follow-up email's buttons open: asks the donor to
 * confirm sending their donation to a priority partner. The Confirm button
 * posts back to the same page (see orphanfollowup\handle_confirm()).
 *
 * @return string HTML.
 */
function priority_upgrade(){
  wp_enqueue_style( 'form' );
  $args = wp_unslash( array_intersect_key( $_GET, array_flip( [ 'd', 'o', 't', 'k' ] ) ) );
  $link = check_upgrade_link( $args );
  if( is_wp_error( $link ) )
    return get_alert( [ 'type' => 'warning', 'description' => '<p>' . esc_html( $link->get_error_message() ) . ' Please use the button in your email, or <a href="' . esc_url( home_url( '/' ) ) . '">start a new donation</a>.</p>' ] );

  $donation = $link['donation'];
  $partner = $link['partner'];
  $partner_name = esc_html( $partner['name'] );

  if( get_post_meta( $donation->ID, UPGRADED_META, true ) ){
    $org_id = (int) get_post_meta( $donation->ID, 'organization', true );
    if( $org_id != $partner['id'] )
      return get_alert( [ 'type' => 'info', 'title' => 'Already sent', 'description' => '<p>Your donation has already been sent to ' . esc_html( get_the_title( $org_id ) ) . '. They\'ll contact you soon.</p>' ] );

    $contact = ( 'Email' == get_post_meta( $donation->ID, 'preferred_contact_method', true ) )? get_field( 'donor_email', $donation->ID ) : get_field( 'donor_phone', $donation->ID );
    return get_alert( [ 'type' => 'success', 'title' => 'Done! Your donation has been sent to ' . $partner_name . ( '?' == substr( $partner['name'], -1 )? '' : '.' ), 'description' => '<p>They\'ll contact you soon at ' . esc_html( $contact ) . '. We\'ve also emailed you a copy for your records.</p>' ] );
  }

  $errors = [
    'partner' => 'This partner no longer serves your zip code.',
    'busy'    => 'Your donation is being sent. Please reload this page in a minute.',
  ];
  $html = '';
  if( isset( $_GET['error'] ) && isset( $errors[ $_GET['error'] ] ) )
    $html.= get_alert( [ 'type' => 'warning', 'description' => '<p>' . $errors[ $_GET['error'] ] . '</p>' ] );

  if( ! in_array( $partner['id'], wp_list_pluck( get_partners( $donation->ID ), 'id' ) ) )
    return $html . get_alert( [ 'type' => 'info', 'description' => '<p>This donation can no longer be sent to ' . $partner_name . '. Please <a href="' . esc_url( home_url( '/' ) ) . '">start a new donation</a>.</p>' ] );

  $hidden = '';
  foreach( $args as $name => $value )
    $hidden.= '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';

  $html.= '<div class="donman-priority-upgrade">';
  // Some partner names already end in one (1-800-GOT-JUNK?).
  $html.= '<h2>Send your donation to ' . $partner_name . ( '?' == substr( $partner['name'], -1 )? '' : '?' ) . '</h2>';
  $html.= '<p>' . $partner_name . ' is a <strong>fee-based</strong> pickup service. They\'ll contact you to confirm the price and a pickup time before anything is scheduled.</p>';
  $html.= '<div class="donman-priority-upgrade-receipt" style="background: #e6e7e9; padding: 1em 1.5em; margin: 1.5em 0;">' . $donation->post_content . '</div>';
  $html.= '<form method="post" action="">' . $hidden . '<input type="hidden" name="donman_priority_upgrade" value="1"><button type="submit" class="btn btn-primary button btn-lg btn-block elementor-button">Yes, send it to ' . $partner_name . '</button></form>';
  $html.= '</div>';

  return $html;
}
add_shortcode( 'priority-upgrade', __NAMESPACE__ . '\\priority_upgrade' );
