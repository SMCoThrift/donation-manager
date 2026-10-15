<?php

namespace DonationManager\apiqueue;
use function DonationManager\apirouting\{send_api_post};

/**
 * Partner API posts run from WP-Cron instead of the donor's request.
 *
 * Production runs `wp cron event run --due-now` every minute from the
 * site user's crontab (DISABLE_WP_CRON is on), so a queued post goes out
 * within about a minute and never holds a PHP-FPM worker while the
 * partner's API responds.
 */
const HOOK = 'donman_send_api_post';
const MAX_ATTEMPTS = 3;
const OVERDUE_AFTER = 15 * MINUTE_IN_SECONDS;
const ALERT_EMAIL = 'webmaster@pickupmydonation.com';

/**
 * Seconds to wait before each retry, keyed by the attempt being scheduled.
 */
const RETRY_DELAYS = [
  2 => 5 * MINUTE_IN_SECONDS,
  3 => 30 * MINUTE_IN_SECONDS,
];

/**
 * Queues a donation for posting to a partner API.
 *
 * Takes the same array as send_api_post(), including `routing_method`,
 * and stores it whole in the cron event so the partner receives exactly
 * what the donor submitted.
 *
 * @param array $donation The donation array.
 * @return bool True if the event was scheduled.
 */
function queue_api_post( $donation ){
  if( ! is_array( $donation ) || empty( $donation['ID'] ) || empty( $donation['routing_method'] ) )
    return false;

  // With one spelling, WP-Cron's duplicate check drops a second CHHJ post
  // for the same donation (see the orphaned-donation path in emails.php).
  $donation['routing_method'] = method_key( $donation['routing_method'] );

  $scheduled = wp_schedule_single_event( time(), HOOK, [ $donation, 1 ], true );
  if( is_wp_error( $scheduled ) && 'duplicate_event' == $scheduled->get_error_code() )
    return true;
  if( true !== $scheduled ){
    // Never lose a partner post: fall back to posting inline.
    error_log( '[DonationManager] Unable to queue API post for Donation #' . $donation['ID'] . '; posting inline.' );
    send_api_post( $donation );
    return false;
  }

  return true;
}

/**
 * Normalizes a routing method so the CHHJ aliases share one key.
 *
 * @param string $routing_method The routing method.
 * @return string
 */
function method_key( $routing_method ){
  return ( 'api-chhj' == $routing_method )? 'chhj_api' : sanitize_key( $routing_method );
}

/**
 * Cron handler: posts a queued donation and schedules a retry if needed.
 *
 * Retries on a timeout, a transport error, or a 5xx response. A 4xx
 * means the partner rejected the data, so it isn't retried.
 *
 * @param array $donation The donation array.
 * @param int   $attempt  Which attempt this is (1-based).
 */
function handle_api_post( $donation, $attempt = 1 ){
  $donation_id = $donation['ID'];
  $method = method_key( $donation['routing_method'] );
  $done_key = '_api_queue_done_' . $method;

  // Each partner gets each donation once.
  if( get_post_meta( $donation_id, $done_key, true ) )
    return;

  // Keeps two overlapping cron runs from posting the same event.
  $lock = 'api_post_lock_' . $donation_id . '_' . $method;
  if( ! wp_cache_add( $lock, 1, 'donman', 2 * MINUTE_IN_SECONDS ) )
    return;

  require_once DONMAN_PLUGIN_PATH . 'lib/classes/donation-router.php';
  \DonationRouter::reset_last_response();
  send_api_post( $donation );
  $code = \DonationRouter::last_response_code();

  wp_cache_delete( $lock, 'donman' );

  // Nothing was posted (dev mode, or the pickup code isn't the partner's).
  if( is_null( $code ) )
    return;

  $retryable = ( 0 === $code || 408 === $code || 500 <= $code );
  if( $retryable && $attempt < MAX_ATTEMPTS ){
    $next = $attempt + 1;
    wp_schedule_single_event( time() + RETRY_DELAYS[ $next ], HOOK, [ $donation, $next ] );
    error_log( '[DonationManager] API post for Donation #' . $donation_id . ' (' . $method . ') returned ' . $code . '; retry ' . $next . ' of ' . MAX_ATTEMPTS . ' scheduled.' );
    return;
  }

  update_post_meta( $donation_id, $done_key, $code );
  if( $retryable )
    error_log( '[DonationManager] API post for Donation #' . $donation_id . ' (' . $method . ') failed after ' . MAX_ATTEMPTS . ' attempts (last code ' . $code . ').' );
}
add_action( HOOK, __NAMESPACE__ . '\\handle_api_post', 10, 2 );

/**
 * Returns the IDs of donations with a partner API post waiting in the queue.
 *
 * Reads the (autoloaded) cron array once per request.
 *
 * @return int[]
 */
function get_queued_donation_ids(){
  static $ids = null;
  if( is_null( $ids ) ){
    $ids = [];
    foreach( (array) _get_cron_array() as $hooks ){
      if( ! isset( $hooks[ HOOK ] ) )
        continue;
      foreach( $hooks[ HOOK ] as $event )
        $ids[] = (int) $event['args'][0]['ID'];
    }
  }
  return $ids;
}

/**
 * Counts queued API posts that should have run more than 15 minutes ago.
 *
 * @return int
 */
function count_overdue_posts(){
  $overdue = 0;
  $cutoff = time() - OVERDUE_AFTER;
  foreach( (array) _get_cron_array() as $timestamp => $hooks ){
    if( $timestamp > $cutoff )
      break; // The cron array is sorted by time.
    if( isset( $hooks[ HOOK ] ) )
      $overdue += count( $hooks[ HOOK ] );
  }
  return $overdue;
}

/**
 * Emails an alert if partner API posts are piling up (e.g. the system
 * cron has stopped). Checked at most every 5 minutes, alerts at most
 * every 6 hours.
 */
function check_queue_health(){
  if( ( defined( 'WP_CLI' ) && WP_CLI ) || wp_doing_cron() )
    return;
  if( get_transient( 'donman_api_queue_checked' ) )
    return;
  set_transient( 'donman_api_queue_checked', 1, 5 * MINUTE_IN_SECONDS );

  $overdue = count_overdue_posts();
  if( 0 === $overdue || get_transient( 'donman_api_queue_alerted' ) )
    return;
  set_transient( 'donman_api_queue_alerted', 1, 6 * HOUR_IN_SECONDS );

  wp_mail(
    ALERT_EMAIL,
    'Partner API posts are not going out',
    $overdue . ' queued partner API post(s) on ' . home_url() . ' are more than ' . ( OVERDUE_AFTER / MINUTE_IN_SECONDS ) . " minutes overdue.\n\nCheck that the site user's crontab is running `wp cron event run --due-now`, then run it by hand to send the backlog."
  );
}
add_action( 'shutdown', __NAMESPACE__ . '\\check_queue_health' );

/**
 * Shows the same warning in wp-admin.
 */
function admin_notice(){
  if( ! current_user_can( 'manage_options' ) )
    return;
  $overdue = count_overdue_posts();
  if( 0 === $overdue )
    return;
  printf(
    '<div class="notice notice-error"><p><strong>Donation Manager:</strong> %d partner API post(s) are more than %d minutes overdue. The system cron may have stopped.</p></div>',
    $overdue,
    OVERDUE_AFTER / MINUTE_IN_SECONDS
  );
}
add_action( 'admin_notices', __NAMESPACE__ . '\\admin_notice' );
