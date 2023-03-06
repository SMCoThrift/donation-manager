<?php
namespace DonationManager\shortcodes;
use function DonationManager\utilities\{get_alert};
use function DonationManager\contacts\{get_contact};
use function DonationManager\donations\{is_claimed,claim_donation,add_click_timestamp,get_first_click_to_claim};

function click_to_claim(){
  $html = '';

  $donation_hash = $_GET['dh'];
  if( empty( $donation_hash ) )
    return get_alert(['description' => 'Nothing to see here.', 'type' => 'info']);

  $contact_id = $_GET['cid'];
  if( empty( $contact_id ) || ! is_numeric( $contact_id ) )
    return get_alert(['description' => 'Missing contact info.', 'type' => 'info']);

  $args = [
    'post_type'   => 'donation',
    'meta_key'    => 'donation_hash',
    'meta_value'  => $donation_hash,
  ];
  $donations = get_posts( $args );
  if( $donations ){
    $donation = $donations[0];
    $claimed = is_claimed( $donation->ID );
    //$contact = get_contact( $contact_id ); // Properties: store_name, email_address, zipcode

    /**
     * CLICK TO CLAIM
     *
     * If CLAIMED, add a "click timestamp" showing when
     * the Network Provider clicked on the link.
     *
     * If NOT CLAIMED, run `claim_donation()`.
     */
    if( $claimed ){
      add_click_timestamp( $donation->ID, $contact_id );
      $claim_data = get_first_click_to_claim( $donation->ID );
      $alert_title = null;
      $alert_text = '';
      if( $contact_id == $claim_data['contact_id'] ){
        $alert_text.= 'You were the first to view this donation, but other organzations in your area may have also viewed this information since you first clicked.';
      } else {
        $alert_text.= 'Another organization in your area has viewed this info.';
      }
      $alert_text = '<p>' . $alert_text . ' You may still contact the donor to see if these items are still available for pick up.</p><p>First viewed: ' . date( 'l, F j, Y \a\t h:ia (\E\S\T)', strtotime( $claim_data['timestamp'] ) ) . '</p>';
      $alert_type = 'info';
    } else {
      claim_donation( $donation->ID, $contact_id );
      add_click_timestamp( $donation->ID, $contact_id );
      $alert_title = 'You\'re First! Claim this Donation';
      $alert_text = '<p>We\'ve notified other organzations about this pick up request. <strong><em>You\'re the first to view this donor\'s info.</em></strong> Contact the donor below to schedule a pick up.</p>';
      $alert_type = 'success';
    }
    $html.= get_alert(['type' => $alert_type, 'title' => $alert_title, 'description' => $alert_text, 'css_classes' => 'large-title' ]);
    $html.= '<p><em>Do you want to be the only organization receiving these requests?</em> <a href="' . site_url( 'partner-benefits') . '" target="_blank">Sign up to be an Exclusive Partner</a> and start increasing the quality and quantity of donation requests you receive.</p><hr style="margin-bottom: 1em;">';

    $donation_receipt = $donation->post_content;
    $html.= $donation_receipt;
  } else {
    $html = get_alert(['description' => 'Invalid Click to Claim link, no data returned.']);
  }

  return $html;
}
add_shortcode( 'click_to_claim', __NAMESPACE__ . '\\click_to_claim' );