<?php

class DM_Resend_Process extends WP_Background_Process {

	/**
	 * @var string
	 */
	protected $prefix = 'donation_manager';

  /**
   * @var string
   */
  protected $action = 'resend_donation';

  /**
   * Handle
   *
   * Override this method to perform any actions required
   * during the async request.
   */
  protected function task( $donation ) {
    $success = \DonationManager\apirouting\send_api_post( $donation );
    if( ! $success )
      error_log( 'Unable to resend donation #' . $donation_id );

    return false;
  }

  /**
   * Complete
   *
   * Override if applicable, but ensure that the below actions are
   * performed, or, call parent::complete().
   */
  protected function complete() {
      parent::complete();

      // Show notice to user or perform some other arbitrary task...
      error_log('[DonationResendProcess] Donation has been resent.');
  }
}