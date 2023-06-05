<?php

use function DonationManager\templates\{render_template};
use function DonationManager\globals\{add_html,get_html};
use function DonationManager\donations\{get_donation_receipt};
use function DonationManager\utilities\{get_alert};
use function DonationManager\realtors\{get_realtor_ads};

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