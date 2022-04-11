<?php
/**
 * Plugin Name:     Donation Manager
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     Online donation manager built for SMCoThrift and PickUpMyDonation.com. This plugin displays the donation form and handles donation submissions.
 * Author:          Michael Wender
 * Author URI:      https://mwender.com
 * Text Domain:     donation-manager
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Donation_Manager
 */

// Your code starts here.

/**
 * Enhanced logging.
 *
 * @param      string  $message  The log message
 */
function uber_log( $message = null ){
  static $counter = 1;

  $bt = debug_backtrace();
  $caller = array_shift( $bt );

  if( 1 == $counter )
    error_log( "\n\n" . str_repeat('-', 25 ) . ' STARTING DEBUG [' . date('h:i:sa', current_time('timestamp') ) . '] ' . str_repeat('-', 25 ) . "\n\n" );
  error_log( "\n" . $counter . '. ' . basename( $caller['file'] ) . '::' . $caller['line'] . "\n" . $message . "\n---\n" );
  $counter++;
}