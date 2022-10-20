<?php

namespace DonationManager\shortcodes;
use function DonationManager\helpers\{get_archived_donations};

/**
 * Retrieves the total value of all Donations.
 *
 * The `total_donations_value` is stored as a transient
 * with an expiration of 30 minutes.
 *
 * @return     int  The total value of all donations.
 */
function get_donation_stats(){
  if( false === ( $total_donations_value = get_transient( 'total_donations_value' ) ) ){
    $archived_donations = get_archived_donations();
    $db_donations = \wp_count_posts( 'donation' );

    $total_donations = $db_donations->publish + $archived_donations['total'];

    $total_donations_value = AVERAGE_DONATION_VALUE * $total_donations;
    set_transient( 'total_donations_value', $total_donations_value, 30 * MINUTE_IN_SECONDS );
  }
  return $total_donations_value;
}
add_shortcode( 'donation-stats', __NAMESPACE__ . '\\get_donation_stats' );