<?php

namespace DonationManager\stats;

/**
 * Daily stats rollup for the wp-admin Dashboard and Donations → Stats.
 *
 * Viewing stats must never scan donations, so every screen reads the small
 * `donman_stats` table (one row per period, date and metric). It's filled by:
 *
 * - refresh_recent(), a WP-Cron job every 15 minutes that recounts the last
 *   few days from donation posts (a few hundred rows via the post_date index).
 * - `wp dm stats rebuild`, a one-time backfill: daily rows for every day that
 *   still has donation posts, daily orphaned-donation rows for all time, and
 *   monthly rows imported from the legacy options for archived months.
 *
 * Monthly figures prefer the sum of a month's daily rows, falling back to the
 * imported monthly row when the month has no daily data.
 */

const CRON_HOOK = 'donman_stats_refresh';
const CACHE_GROUP = 'donman_stats';
const REFRESH_DAYS = 3;

/**
 * An API donation with no response yet is "pending" for this long, then a fail.
 */
const PENDING_WINDOW = 2 * HOUR_IN_SECONDS;

/**
 * Partners that receive donations via API, keyed by the stats prefix.
 */
const PARTNERS = [
  '1800gj' => [ 'name' => '1-800-GOT-JUNK?', 'api_method' => '1800gj_api', 'option' => '1800gj_donations' ],
  'chhj'   => [ 'name' => 'College Hunks', 'api_method' => 'chhj_api', 'option' => 'chhj_donations' ],
];

/**
 * @return string The stats table name.
 */
function table(){
  global $wpdb;
  return $wpdb->prefix . 'donman_stats';
}

/**
 * Metrics written by rollup_days().
 *
 * @return array Metric keys.
 */
function day_metrics(){
  $metrics = [ 'donations', 'orphaned_donations', 'orphaned_emails' ];
  foreach( array_keys( PARTNERS ) as $partner ){
    foreach( [ 'non_priority', 'priority', 'fails', 'pending' ] as $field )
      $metrics[] = "{$partner}_{$field}";
  }
  return $metrics;
}

/**
 * Recounts the daily stats for a date range from donation posts.
 *
 * @param string $start First day, Y-m-d.
 * @param string $end   Last day, Y-m-d (inclusive).
 * @return int Number of stat rows written.
 */
function rollup_days( $start, $end ){
  global $wpdb;

  $from = $start . ' 00:00:00';
  $to = date( 'Y-m-d', strtotime( $end . ' +1 day' ) ) . ' 00:00:00';
  $pending_after = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - PENDING_WINDOW );
  $rows = [];

  $donations = $wpdb->get_results( $wpdb->prepare(
    "SELECT DATE(post_date) AS d, COUNT(*) AS n
    FROM {$wpdb->posts}
    WHERE post_type = 'donation' AND post_status = 'publish' AND post_date >= %s AND post_date < %s
    GROUP BY d",
    $from, $to
  ) );
  foreach( $donations as $row )
    $rows[ $row->d ]['donations'] = $row->n;

  $methods = wp_list_pluck( PARTNERS, 'api_method' );
  $partner_keys = array_flip( $methods );
  $in = implode( ',', array_fill( 0, count( $methods ), '%s' ) );
  // Start from the api_method rows (meta_key index): postmeta has no
  // (post_id, meta_key) index, so starting from posts would read all ~55
  // meta rows of every donation in the range just to find the ~1 in 6 that
  // went to a partner. On prod: 0.25s vs 0.5s for 3 days, 1.3s vs 4.5s for
  // a month.
  $partners = $wpdb->get_results( $wpdb->prepare(
    "SELECT DATE(p.post_date) AS d, m.meta_value AS method,
      SUM( o.meta_value = 'PickUpMyDonation.com' ) AS non_priority,
      SUM( o.meta_value IS NULL OR o.meta_value <> 'PickUpMyDonation.com' ) AS priority,
      SUM( ( c.meta_value IS NULL OR c.meta_value IN ('','pending') ) AND p.post_date >= %s ) AS pending,
      SUM( c.meta_value IS NULL OR c.meta_value <> '200' ) AS not_ok
    FROM {$wpdb->postmeta} AS m
    STRAIGHT_JOIN {$wpdb->posts} AS p ON p.ID = m.post_id
    LEFT JOIN {$wpdb->postmeta} AS o ON o.post_id = p.ID AND o.meta_key = '_organization_name'
    LEFT JOIN {$wpdb->postmeta} AS c ON c.post_id = p.ID AND c.meta_key = 'api_response_code'
    WHERE m.meta_key = 'api_method' AND m.meta_value IN ($in)
      AND p.post_type = 'donation' AND p.post_status = 'publish' AND p.post_date >= %s AND p.post_date < %s
    GROUP BY d, method",
    array_merge( [ $pending_after ], array_values( $methods ), [ $from, $to ] )
  ) );
  foreach( $partners as $row ){
    $partner = $partner_keys[ $row->method ];
    $rows[ $row->d ]["{$partner}_non_priority"] = $row->non_priority;
    $rows[ $row->d ]["{$partner}_priority"] = $row->priority;
    $rows[ $row->d ]["{$partner}_pending"] = $row->pending;
    $rows[ $row->d ]["{$partner}_fails"] = $row->not_ok - $row->pending;
  }

  $orphaned = $wpdb->get_results( $wpdb->prepare(
    "SELECT DATE(p.post_date) AS d, COUNT(DISTINCT od.donation_id) AS donations, COUNT(*) AS emails
    FROM {$wpdb->posts} AS p
    INNER JOIN {$wpdb->prefix}donman_orphaned_donations AS od ON od.donation_id = p.ID
    WHERE p.post_type = 'donation' AND p.post_status = 'publish' AND p.post_date >= %s AND p.post_date < %s
    GROUP BY d",
    $from, $to
  ) );
  foreach( $orphaned as $row ){
    $rows[ $row->d ]['orphaned_donations'] = $row->donations;
    $rows[ $row->d ]['orphaned_emails'] = $row->emails;
  }

  // Days and metrics with no rows are zeros, so clear the range first.
  $metrics = day_metrics();
  $in = implode( ',', array_fill( 0, count( $metrics ), '%s' ) );
  $wpdb->query( $wpdb->prepare(
    'DELETE FROM ' . table() . " WHERE period = 'day' AND stat_date BETWEEN %s AND %s AND metric IN ($in)",
    array_merge( [ $start, $end ], $metrics )
  ) );

  $written = write_rows( 'day', $rows );
  flush_cache();

  return $written;
}

/**
 * Daily orphaned-donation stats for days before a date, from the
 * orphaned_donations table alone (their donation posts may be archived).
 *
 * Scans the whole table, so it's for `wp dm stats rebuild` only.
 *
 * @param string $before Y-m-d. Days before this one are counted.
 * @return int Number of stat rows written.
 */
function backfill_orphaned_before( $before ){
  global $wpdb;

  $results = $wpdb->get_results( $wpdb->prepare(
    "SELECT DATE(timestamp) AS d, COUNT(DISTINCT donation_id) AS donations, COUNT(*) AS emails
    FROM {$wpdb->prefix}donman_orphaned_donations
    WHERE timestamp < %s
    GROUP BY d",
    $before . ' 00:00:00'
  ) );

  $rows = [];
  foreach( $results as $row ){
    $rows[ $row->d ]['orphaned_donations'] = $row->donations;
    $rows[ $row->d ]['orphaned_emails'] = $row->emails;
  }

  $wpdb->query( $wpdb->prepare(
    'DELETE FROM ' . table() . " WHERE period = 'day' AND stat_date < %s AND metric IN ('orphaned_donations','orphaned_emails')",
    $before
  ) );

  $written = write_rows( 'day', $rows );
  flush_cache();

  return $written;
}

/**
 * Imports monthly rows for archived months from the legacy options:
 * `{partner}_donations` (written by `wp dm stats priority`) and the ACF
 * `donation_stats` repeater (donation counts recorded by `wp dm archive`).
 *
 * @return int Number of stat rows written.
 */
function import_legacy_months(){
  global $wpdb;

  $rows = [];
  foreach( PARTNERS as $partner => $config ){
    $months = get_option( $config['option'] );
    if( ! is_array( $months ) )
      continue;
    foreach( $months as $month => $stats ){
      $date = $month . '-01';
      $rows[ $date ]["{$partner}_non_priority"] = $stats['non-priority'];
      $rows[ $date ]["{$partner}_priority"] = $stats['priority'];
      $rows[ $date ]["{$partner}_fails"] = $stats['fails'];
    }
  }

  $archived = (int) get_option( 'options_donation_stats' );
  for( $i = 0; $i < $archived; $i++ ){
    $year = get_option( "options_donation_stats_{$i}_year" );
    $month = get_option( "options_donation_stats_{$i}_month" );
    $donations = get_option( "options_donation_stats_{$i}_donations" );
    if( ! is_numeric( $year ) || ! is_numeric( $month ) || ! is_numeric( $donations ) )
      continue;
    // A month archived in more than one run has more than one row.
    $date = sprintf( '%04d-%02d-01', $year, $month );
    $rows[ $date ]['donations'] = ( isset( $rows[ $date ]['donations'] )? $rows[ $date ]['donations'] : 0 ) + $donations;
  }

  $wpdb->query( 'DELETE FROM ' . table() . " WHERE period = 'month'" );
  $written = write_rows( 'month', $rows );
  flush_cache();

  return $written;
}

/**
 * Inserts stat rows, skipping zeros.
 *
 * @param string $period 'day' or 'month'.
 * @param array  $rows   [ Y-m-d => [ metric => value ] ].
 * @return int Number of rows written.
 */
function write_rows( $period, $rows ){
  global $wpdb;

  $values = [];
  foreach( $rows as $date => $metrics ){
    foreach( $metrics as $metric => $value ){
      if( 0 < (int) $value )
        $values[] = $wpdb->prepare( '(%s,%s,%s,%d)', $period, $date, $metric, $value );
    }
  }

  foreach( array_chunk( $values, 500 ) as $chunk )
    $wpdb->query( 'REPLACE INTO ' . table() . ' (period,stat_date,metric,value) VALUES ' . implode( ',', $chunk ) );

  return count( $values );
}

/**
 * Invalidates cached stats reads.
 */
function flush_cache(){
  wp_cache_set_last_changed( CACHE_GROUP );
}

/**
 * Daily stats for a date range.
 *
 * @param string $start First day, Y-m-d.
 * @param string $end   Last day, Y-m-d (inclusive).
 * @return array [ Y-m-d => [ metric => value ] ] for every day in the range.
 */
function get_daily( $start, $end ){
  global $wpdb;

  $cache_key = 'daily:' . $start . ':' . $end . ':' . wp_cache_get_last_changed( CACHE_GROUP );
  $days = wp_cache_get( $cache_key, CACHE_GROUP );
  if( false !== $days )
    return $days;

  $days = [];
  for( $ts = strtotime( $start ); $ts <= strtotime( $end ); $ts = strtotime( '+1 day', $ts ) )
    $days[ date( 'Y-m-d', $ts ) ] = [];

  $results = $wpdb->get_results( $wpdb->prepare(
    'SELECT stat_date, metric, value FROM ' . table() . " WHERE period = 'day' AND stat_date BETWEEN %s AND %s",
    $start, $end
  ) );
  foreach( $results as $row )
    $days[ $row->stat_date ][ $row->metric ] = (int) $row->value;

  wp_cache_set( $cache_key, $days, CACHE_GROUP, DAY_IN_SECONDS );

  return $days;
}

/**
 * Monthly stats for a range of months.
 *
 * Each metric is the sum of the month's daily rows when it has any, or else
 * the month's imported legacy row.
 *
 * @param string $first First month, Y-m.
 * @param string $last  Last month, Y-m (inclusive).
 * @return array [ Y-m => [ 'metrics' => [ metric => value ], 'has_days' => bool ] ], newest first. `has_days` means the month has daily donation counts.
 */
function get_monthly( $first, $last ){
  global $wpdb;

  $cache_key = 'monthly:' . $first . ':' . $last . ':' . wp_cache_get_last_changed( CACHE_GROUP );
  $months = wp_cache_get( $cache_key, CACHE_GROUP );
  if( false !== $months )
    return $months;

  $months = [];
  for( $ts = strtotime( $last . '-01' ); $ts >= strtotime( $first . '-01' ); $ts = strtotime( '-1 month', $ts ) )
    $months[ date( 'Y-m', $ts ) ] = [ 'metrics' => [], 'has_days' => false ];

  $results = $wpdb->get_results( $wpdb->prepare(
    "SELECT period, DATE_FORMAT(stat_date,'%%Y-%%m') AS month, metric, SUM(value) AS value
    FROM " . table() . "
    WHERE stat_date >= %s AND stat_date < %s
    GROUP BY period, month, metric",
    $first . '-01', date( 'Y-m-d', strtotime( $last . '-01 +1 month' ) )
  ) );

  $legacy = [];
  foreach( $results as $row ){
    if( 'day' == $row->period ){
      $months[ $row->month ]['metrics'][ $row->metric ] = (int) $row->value;
      // Only months with daily donation counts are worth drilling into.
      if( 'donations' == $row->metric )
        $months[ $row->month ]['has_days'] = true;
    } else {
      $legacy[ $row->month ][ $row->metric ] = (int) $row->value;
    }
  }
  foreach( $legacy as $month => $metrics ){
    // A legacy row only fills metrics the month has no daily data for. Daily
    // partner data covers all four fields, so the legacy fields stay out then.
    foreach( $metrics as $metric => $value ){
      $partner = strtok( $metric, '_' );
      $daily_partner = array_key_exists( $partner, PARTNERS ) && isset( $months[ $month ]['metrics'][ "{$partner}_priority" ] );
      if( ! isset( $months[ $month ]['metrics'][ $metric ] ) && ! $daily_partner )
        $months[ $month ]['metrics'][ $metric ] = $value;
    }
  }

  wp_cache_set( $cache_key, $months, CACHE_GROUP, DAY_IN_SECONDS );

  return $months;
}

/**
 * The first month with any stats.
 *
 * @return string|null Y-m, or null if the table is empty.
 */
function get_first_month(){
  global $wpdb;

  $cache_key = 'first_month:' . wp_cache_get_last_changed( CACHE_GROUP );
  $first = wp_cache_get( $cache_key, CACHE_GROUP );
  if( false === $first ){
    $first = (string) $wpdb->get_var( "SELECT DATE_FORMAT(MIN(stat_date),'%Y-%m') FROM " . table() );
    wp_cache_set( $cache_key, $first, CACHE_GROUP, DAY_IN_SECONDS );
  }

  return ( '' === $first )? null : $first;
}

/**
 * A partner's figures from a set of metrics.
 *
 * @param array  $metrics [ metric => value ].
 * @param string $partner Partner key from PARTNERS.
 * @return array non_priority, priority, total, fails, pending, and success_rate (null when nothing has a response yet).
 */
function partner_figures( $metrics, $partner ){
  $get = function( $field ) use ( $metrics, $partner ){
    return isset( $metrics[ "{$partner}_{$field}" ] )? $metrics[ "{$partner}_{$field}" ] : 0;
  };
  $figures = [
    'non_priority' => $get( 'non_priority' ),
    'priority'     => $get( 'priority' ),
    'fails'        => $get( 'fails' ),
    'pending'      => $get( 'pending' ),
  ];
  $figures['total'] = $figures['non_priority'] + $figures['priority'];
  $answered = $figures['total'] - $figures['pending'];
  $figures['success_rate'] = ( 0 < $answered )? ( $answered - $figures['fails'] ) / $answered * 100 : null;

  return $figures;
}

/**
 * Cron: recounts the last few days.
 */
function refresh_recent(){
  $today = current_time( 'Y-m-d' );
  rollup_days( date( 'Y-m-d', strtotime( $today . ' -' . ( REFRESH_DAYS - 1 ) . ' days' ) ), $today );
}
add_action( CRON_HOOK, __NAMESPACE__ . '\\refresh_recent' );

/**
 * Adds a 15-minute cron interval.
 *
 * @param array $schedules Cron schedules.
 * @return array
 */
function cron_schedules( $schedules ){
  $schedules['donman_15_minutes'] = [ 'interval' => 15 * MINUTE_IN_SECONDS, 'display' => 'Every 15 minutes' ];
  return $schedules;
}
add_filter( 'cron_schedules', __NAMESPACE__ . '\\cron_schedules' );

/**
 * Schedules the refresh if it isn't already.
 */
function schedule_refresh(){
  if( ! wp_next_scheduled( CRON_HOOK ) )
    wp_schedule_event( time(), 'donman_15_minutes', CRON_HOOK );
}
add_action( 'init', __NAMESPACE__ . '\\schedule_refresh' );
