<?php

use function DonationManager\templates\{render_template};
use function DonationManager\globals\{add_html,get_html};
use function DonationManager\donations\{get_donation_receipt};
use function DonationManager\utilities\{get_alert};
use function DonationManager\realtors\{get_realtor_ads};

/**
 * GA4 conversion tracking — fires exactly once per completed donation.
 *
 * Tied to $_SESSION['donor']['ID'], which save_donation() sets only when a new
 * donation record is created (see 06.a/06.b validate-pickup-*.php). The
 * `_ga_event_sent` flag is set immediately after so a page refresh, back/forward
 * navigation, or bookmarked revisit of /thank-you/ does NOT re-fire the event.
 * This replaces the old GA4 "Create event" rules that matched any page_view of
 * /thank-you/, which double- and over-counted conversions.
 *
 * donationform.php already calls session_write_close() for is_page('thank-you')
 * before this file loads, so the session must be explicitly reopened here or the
 * `_ga_event_sent` write below is silently lost — never persisted, so it never
 * sticks and the event re-fires on every refresh. donman_start_session() can't be
 * reused for this: it has a static "already started" guard that no-ops after its
 * first call this request.
 */
if ( ! empty( $_SESSION['donor']['ID'] ) && empty( $_SESSION['donor']['_ga_event_sent'] ) ) {
  if ( PHP_SESSION_ACTIVE !== session_status() && ! headers_sent() ) {
    session_start();
  }
  $donation_id = (int) $_SESSION['donor']['ID'];
  add_html( '<script>gtag("event", "donation_completed", {"transaction_id": "' . esc_js( $donation_id ) . '"});</script>' );
  $_SESSION['donor']['_ga_event_sent'] = true;
  session_write_close();
}

add_html( '<p>Thank you for donating! We will contact you to finalize your pickup date. Below is a copy of your donation receipt which you will also receive via email.</p>' );

// Retrieve the donation receipt
$donationreceipt = get_donation_receipt( $_SESSION['donor'] );

// Add the org logo and link to website
$logo_url = get_the_post_thumbnail_url( $_SESSION['donor']['org_id'], 'donor-email' );
$website = get_post_meta( $_SESSION['donor']['org_id'], 'website', true );
if( $logo_url && $website )
  add_html('<div style="text-align: center"><h3>Thank you for donating to:</h3><a href="' . $website . '" target="_blank"><img src="' . $logo_url . '" style="width: 300px;" /></a></div>');

add_html( '<div style="max-width: 600px; margin: 0 auto;">' . $donationreceipt . '</div>' );

// Unattended donations
add_html( get_alert([
  'type'        => 'warning',
  'description' => '<strong>IMPORTANT:</strong> If your donations are left unattended during pick up, copies of this ticket MUST be attached to all items or containers of items in order for them to be picked up.',
]) );

// Dates and times are not confirmed
add_html( get_alert([
  'type'        => 'info',
  'description' => '<em>PLEASE NOTE: The dates and times you selected during the donation process are not confirmed. Those dates will be used by our Transportation Director when he/she contacts you to schedule your actual pickup date.</em>',
]));

// Insert the Realtor Ad
$realtor_ads = get_realtor_ads([ $_SESSION['donor']['org_id'] ]);
if( $realtor_ads && 0 < count( $realtor_ads ) ){
  foreach( $realtor_ads as $ad ){
    add_html($ad);
  }
}