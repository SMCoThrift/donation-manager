<?php

use function DonationManager\templates\{render_template};
use function DonationManager\globals\{add_html,get_html};
use function DonationManager\helpers\{get_socialshare_copy};
use function DonationManager\donations\{get_donation_receipt};
use function DonationManager\utilities\{get_alert};
use function DonationManager\realtors\{get_realtor_ads};

add_html( '<p>Thank you for donating! We will contact you to finalize your pickup date. Below is a copy of your donation receipt which you will also receive via email.</p>' );
$allow_user_photo_uploads = get_field( 'pickup_settings_allow_user_photo_uploads', $_SESSION['donor']['org_id'] );
if( ! $allow_user_photo_uploads )
{
  // Social Sharing
  $organization_name = get_the_title( $_SESSION['donor']['org_id'] );
  $donation_id_hashtag = '#id' . $_SESSION['donor']['ID'];
  $socialshare_copy = get_socialshare_copy( $organization_name, $donation_id_hashtag );

  $twitter_image = '<img alt="Twitter" style="width: 48px; height: 48px;" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAGAAAABgBAMAAAAQtmoLAAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAAAnUExURUdwTACz/wCz/wCz/wCx/wCz/wCz/wCv/wC0/wCy/wCv/wCz/wCz/zsxIToAAAAMdFJOUwCF79lbpkAQML8gcD9NoN0AAAJ+SURBVFjD7Ve9SxxBFN/zLt6iFp5GVGIT8EDMFhbGQLLFaVARLCRFRLBYIRIMFquSXhALIYWmO7nisLBKYRVCSONuTvx4f1T25nb2ZnffzLypguCv2Y95v3mf82bGsp7wKFD+bCS+9R0gODskzz4MDOEo//NJLT8EMYLBzp+de/ZYlhCOIEHIrFqBUzZRFZcvgYBJy7IjA732QAHWUMKqSIDleRfgjg0U4a9WQYxpNvINoIYQdhFCR24CMBVlNy9/3RlqRK+DOUIBURD5uhgNOdFrK0f4gSkoDbVtYR9vsoTNnHywNOIwU4TMCC4AitDiBHjQB5UbEr+fpwjPUPngUCAEVyKhByVMsTGH2+epg5Q42uDfraY6zzyUE4AwMELLt3gtJb88FYE7WRSt/K0geGjVvGtqCWUnHYrnTQ1B8DrOyNlPb0BFKAINCcF2DAmZ5a4j2DVZaWYRT98bjBFVcALA6xcU+Tv12kJKibtMjBFvMthyx5E0rz9Ewr1ycSGYUa93tO8lcSWh27EbNEJ3/V6Q5MNuh+g3SwM1E7faviiLKlXFlW67ycKX7eG6Wo3x3sRnhm1XTThNSZ9UKuMutWW0sU9en+RE3KQJffTalrTX3G7YzBAONISHbFB17XVGc5rC92fKzi+zKMKCSYyYF4pMhz52xivI/Z7Cj517Lq2OBKvGHXVTRU6Gm+oORguVQsFXMFOw55gpwOUD6W3jGA/RW1lIhyUdFU2ydTkiSXOQ93ijXq/Ly2g9P3mvqr3MoRcLedG9Miy6l74kQiWcMetLU2Z/QOLzUXnHW8w6Uq1pbpF2KhPVX4SLZ9/8RseXeuUL/XZrm12Gn/Df8A8mnQeNdhIkTQAAAABJRU5ErkJggg==" />';

  $social_post_text = '<div class="hidden-print" style="margin-bottom: 30px"><hr><h3>' . $twitter_image . ' Need faster service?</h3><h4>Tweet your donation with your Donation ID hashtag!</h4><p style="margin-bottom: 10px;">Tweet a photo of your donation along with your Donation ID hashtag. Some organizations respond faster when you do! Click to copy-and-paste:</p><textarea class="form-control" rows="3" onclick="this.setSelectionRange(0, this.value.length)" style="background-color: #eee;">' . $socialshare_copy . '</textarea><p class="help-block small">NOTE: Be sure to include a photo of your donation and the donation ID hashtag with your tweet (i.e. ' . $donation_id_hashtag . ').</p><hr></div>';

  if( false != $socialshare_copy )
    add_html( $social_post_text );
}

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