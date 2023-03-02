<?php

namespace DonationManager\shortcodes;
use function DonationManager\helpers\{get_archived_donations,get_donations_value,get_donations_by_interval};

/**
 * Retrieves the total value of all Donations.
 *
 * The `total_donations_value` is stored as a transient
 * with an expiration of 30 minutes.
 *
 * @return     int  The total value of all donations.
 */
function get_donation_stats( $atts ){
  $args = shortcode_atts([
    'stat' => 'total-value',
  ], $atts );

  $stat = 0;

  if( in_array( $args['stat'], [ 'donations-last-month', 'donations-last-month-value' ] ) ){
    $stats = new \stdClass();
    $stats->donations = new \stdClass();
    $stats->donations->lastmonth = new \stdClass();
    $db_donations_last_month = get_donations_by_interval( 'last_month' );
    $current_time = \current_time( 'Y-m-d' ) . ' first day of last month';
    $dt = \date_create( $current_time );
    $last_months_year = $dt->format( 'Y' );
    $last_month = $dt->format( 'm' );
    $archived_donations_last_month = get_archived_donations( $last_months_year, $last_month );
    $stats->donations->lastmonth->number = intval( $db_donations_last_month + $archived_donations_last_month['total'] );
    //uber_log('$last_months_year = ' . $last_months_year ."\n". ' $last_month = ' . $last_month . "\n\$args['stat'] = " . $args['stat'] . "\n\$archived_donations_last_month = " . print_r( $archived_donations_last_month, true ) . "\n\$db_donations_last_month = " . $db_donations_last_month );
  }

  switch( $args['stat'] ){
    case 'donations-last-month':
      $stat = $stats->donations->lastmonth->number;
      break;

    case 'donations-last-month-value':
      $stat = get_donations_value( $stats->donations->lastmonth->number );;
      break;

    case 'total-value':
    case 'total_value':
    default:
      if( false === ( $total_donations_value = get_transient( 'total_donations_value' ) ) ){
        $archived_donations = get_archived_donations();
        $db_donations = \wp_count_posts( 'donation' );

        $total_donations = $db_donations->publish + $archived_donations['total'];

        $total_donations_value = AVERAGE_DONATION_VALUE * $total_donations;
        set_transient( 'total_donations_value', $total_donations_value, 30 * MINUTE_IN_SECONDS );
      }
      $stat = $total_donations_value;
      break;
  }
  return $stat;
}
add_shortcode( 'donation-stats', __NAMESPACE__ . '\\get_donation_stats' );