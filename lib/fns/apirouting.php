<?php

namespace DonationManager\apirouting;

/**
 * Sends a donation directly to a third-party API.
 *
 * @since 1.4.1
 *
 * @param array $donation The donation array.
 * @return void
 */
function send_api_post( $donation ){
  if( DONMAN_DEV_ENV ){
    uber_log('🔔 We are in Development Mode, not sending API Post.');
    return true;
  }

  switch( $donation['routing_method'] ){
    case 'api-chhj':
      require_once DONMAN_PLUGIN_PATH . 'lib/classes/donation-router.php';
      require_once DONMAN_PLUGIN_PATH . 'lib/classes/donation-router.chhj.php';
      $CHHJDonationRouter = CHHJDonationRouter::get_instance();
      $CHHJDonationRouter->submit_donation( $donation );
      return true;
    break;
  }
}