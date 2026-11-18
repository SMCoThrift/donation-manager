<?php

namespace DonationManager\cron;

/**
 * Keeps WP-Cron from losing events that other processes schedule.
 *
 * WordPress keeps every cron event in one option, `cron`, and changes it
 * by reading the whole array, editing it, and saving it back. Options are
 * cached for the life of a PHP process, so a process that read `cron` a
 * while ago saves back its old copy and wipes out any event another
 * process added in the meantime.
 *
 * The every-minute `wp cron event run --due-now` is such a process: it
 * reads `cron` when it boots and saves it as it runs each event, often
 * 10 or more seconds later. A partner API post queued by a donor's
 * request in that window was lost (lib/fns/apiqueue.php), about 7% of
 * them from 2026-10-15 to 2026-11-18, and nearly half of the donations
 * submitted in the first five seconds of a minute.
 *
 * The fix: read `cron` from the database instead of the cache whenever
 * the copy might be old. In WP-CLI and WP-Cron that's every read. In a
 * web request it's every read after the request first changes a cron
 * event, so plain `wp_next_scheduled()` checks on page loads don't cost
 * a query. That leaves a race of milliseconds instead of seconds.
 */

/**
 * Whether every read of `cron` should go to the database.
 *
 * @var bool
 */
$GLOBALS['donman_fresh_cron'] = ( defined( 'WP_CLI' ) && WP_CLI ) || ( defined( 'DOING_CRON' ) && DOING_CRON );

/**
 * Reads the `cron` option straight from the database.
 *
 * @param mixed $pre The short-circuit value (false).
 * @return mixed The cron array, or $pre to fall back to get_option().
 */
function fresh_cron_option( $pre ){
  if( empty( $GLOBALS['donman_fresh_cron'] ) )
    return $pre;

  global $wpdb;
  $value = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", 'cron' ) );
  if( is_null( $value ) )
    return $pre;

  return maybe_unserialize( $value );
}
add_filter( 'pre_option_cron', __NAMESPACE__ . '\\fresh_cron_option' );

/**
 * Turns on fresh reads before this request first changes a cron event.
 *
 * Hooked to the `pre_*` filters that run just before WordPress reads the
 * cron array to change it. Returns the filter value untouched.
 *
 * @param mixed $pre The filter value.
 * @return mixed
 */
function before_cron_change( $pre ){
  $GLOBALS['donman_fresh_cron'] = true;
  return $pre;
}
foreach( [ 'pre_schedule_event', 'pre_reschedule_event', 'pre_unschedule_event', 'pre_clear_scheduled_hook', 'pre_unschedule_hook' ] as $filter ){
  add_filter( $filter, __NAMESPACE__ . '\\before_cron_change', 1 );
}
