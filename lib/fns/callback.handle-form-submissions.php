<?php

namespace DonationManager\callbacks;
use function DonationManager\utilities\{get_alert,donman_start_session};
use function DonationManager\globals\{add_html};

/**
 * Handles form submissions for the donation flow.
 *
 * Moved from `init` to `template_redirect` to ensure:
 *  - Only front-end template loads trigger processing
 *  - Reduces duplicate triggers from assets preloading
 *
 * @since 1.0.0
 * @return void
 */
function handle_form_submissions() {

  // Only front-end
  if ( wp_doing_ajax() || is_admin() ) {
    return;
  }

  // Only run on pages in the donation flow
  $flow_pages = [
    'donate-now',
    'select-your-organization',
    'step-one',
    'step-two',
    'step-three',
    'step-four',
    'thank-you',
  ];

  // If this is a GET request, only run on flow pages
  if ( $_SERVER['REQUEST_METHOD'] === 'GET' && ! is_page( $flow_pages ) ) {
    return;
  } 

  // Ensure session initialized
  donman_start_session();

  // Prevent re-processing right after redirect
  if ( ! empty( $_SESSION['donor']['_redirecting'] ) ) {
    unset( $_SESSION['donor']['_redirecting'] );
    if ( DMDEBUG_VERBOSE ) {
      uber_log('⛔ Skipping processing — post-redirect bounce');
    }
    return;
  }

  // Routing: determine which step callback to run
  $callback = null;
  $method   = $_SERVER['REQUEST_METHOD'];

  /**
   * STEP 1 — ZIP / donation code (POST)
   */
  if ( $method === 'POST' && isset( $_POST['donman_step'] ) && $_POST['donman_step'] === 'pickup_zip' ) {
    $callback = '01.initial-pickup-code-validation';

  /**
   * STEP 2 — select organization (GET)
   * User clicked organization button → process provider selection
   */
  //🔔🔔🔔🔔🔔 } else if ( isset( $_REQUEST['oid'] ) && isset( $_REQUEST['tid'] ) && ! isset( $_POST['donor'] ) ){
  // https://pickupmydonation.test/step-one?oid=313&tid=737&priority=0
  } elseif ( $method === 'GET' && isset( $_GET['oid'], $_GET['tid'] ) ) {
    $callback = '02.describe-your-donation';

  /**
   * STEP 3 — donation options (POST)
   */
  //} elseif ( $method === 'POST' && isset( $_POST['donor']['options'] ) ) {
  } elseif ( $method === 'POST' && isset($_POST['donman_step']) && $_POST['donman_step'] === 'donation_description' ) {  
    $callback = '03.validate-donation-options';

  /**
   * STEP 4 — screening questions (POST)
   */
  } elseif ( $method === 'POST' && isset( $_POST['donor']['questions'] ) ) {
    $callback = '04.validate-screening-questions';

  /**
   * STEP 5 — contact details (POST)
   */
  } elseif ( $method === 'POST' && isset( $_POST['donor']['address'] ) ) {
    $callback = '05.validate-contact-details';

  /**
   * STEP 6 — pickup dates (POST)
   */
  } elseif ( $method === 'POST' && isset( $_POST['donor']['pickupdate1'] ) ) {
    $callback = '06.a.validate-pickup-dates';

  /**
   * STEP 6B — Skip dates and go to pickup location
   */
  } elseif ( $method === 'POST' && isset( $_POST['skip_pickup_dates'] ) && true == $_POST['skip_pickup_dates'] ) {
    $callback = '06.b.validate-pickup-location';
  }


  if ( ! $callback ) {
    if ( DMDEBUG_VERBOSE ) {
      uber_log( '🔔 No callback determined — this POST is not part of donor flow.' );
    }
    return;
  }

  $callback_filename = DONMAN_PLUGIN_PATH . 'lib/fns/callback/' . $callback . '.php';

  if ( ! file_exists( $callback_filename ) ) {
    add_html( get_alert([
      'description' => "Missing callback file: <code>{$callback}</code>"
    ]) );
    return;
  }

  if ( DMDEBUG_VERBOSE ) {
    uber_log("📁 Executing callback: {$callback}");
  }

  require_once $callback_filename;
}
add_action( 'template_redirect', __NAMESPACE__ . '\\handle_form_submissions', 1 );


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
function handle_form_submissions_ORIGINAL(){
  if( wp_doing_ajax() || is_admin() )
    return;

  if( defined( 'WP_CLI' ) && WP_CLI )
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
    if( DMDEBUG_VERBOSE )
      uber_log( '🔔 handle_form_submissions() $callback is null. No `init` form processing.' );
    return;
  }

  $callback_filename = DONMAN_PLUGIN_PATH . 'lib/fns/callback/' . $callback . '.php';
  if( DMDEBUG_VERBOSE )
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
//add_action( 'init', __NAMESPACE__ . '\\handle_form_submissions', 99 );