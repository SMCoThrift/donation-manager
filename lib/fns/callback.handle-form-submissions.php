<?php

namespace DonationManager\callbacks;
use function DonationManager\utilities\{get_alert};
use function DonationManager\globals\{add_html};

/**
 * Hooks to `init`. Handles form submissions.
 *
 * The validation process typically sets $_SESSION['donor']['form'].
 * That variable controls which form/message is displayed by
 * callback_shortcode().
 *
 * @since 1.0.0
 *
 * @return void
 */
function handle_form_submissions(){
  if( wp_doing_ajax() )
    return;

  $callback = null;

  if( isset( $_REQUEST['pickupcode'] ) || isset( $_REQUEST['pcode'] ) ) {
    $callback = '01.initial-pickup-code-validation';
  } else if ( isset( $_REQUEST['oid'] ) && isset( $_REQUEST['tid'] ) && ! isset( $_POST['donor'] ) ){
    $callback = '02.describe-your-donation';
  } else if( isset( $_POST['donor']['options'] ) ){
    $callback = '03.validate-donation-options';
  } else if( isset( $_POST['donor']['questions'] ) ){
    $callback = '04.validate-screening-questions';
  } else if( isset( $_POST['donor']['address'] ) ){
    $callback = '05.validate-contact-details';
  } else if( isset( $_POST['donor']['pickupdate1'] ) ){
    $callback = '06.a.validate-pickup-dates';
  } else if( isset( $_POST['skip_pickup_dates'] ) && true == $_POST['skip_pickup_dates'] ){
    $callback = '06.b.validate-pickup-location';
  }

  if( is_null( $callback ) ){
    uber_log( '🔔 handle_form_submissions() $callback is null. No `init` form processing.' );
    return;
  }

  $callback_filename = DONMAN_PLUGIN_PATH . 'lib/fns/callback/' . $callback . '.php';
  uber_log( '📂 $callback_filename = ' . basename( $callback_filename ) );

  /**
   * If $callback_filename does not exist, "bail" and show alert:
   */
  if( ! file_exists( $callback_filename ) ){
    add_html( get_alert(['description' => 'I could not find <code>lib/fns/callback/' . basename( $callback_filename ) . '</code>. Please create this file with any neccessary callback processing for this form.']) );
    return;
  }

  require_once( $callback_filename );
}
add_action( 'init', __NAMESPACE__ . '\\handle_form_submissions', 99 );