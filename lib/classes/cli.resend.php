<?php
// Only run if in the WP_CLI
if( defined( 'WP_CLI' ) && 'WP_CLI' && true == WP_CLI ){
  /**
   * Resend donations which failed to post to an external API.
   */
  class DonationManagerResend {

  }
  WP_CLI::add_command( 'dm resend', 'DonationManagerResend' );
} else {
  if( ! defined( 'WP_CLI' ) )
    define( 'WP_CLI', false );
}