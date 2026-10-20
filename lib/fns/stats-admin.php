<?php

namespace DonationManager\stats\admin;
use const DonationManager\stats\PARTNERS;
use function DonationManager\stats\{get_daily,get_monthly,get_first_month,partner_figures};

/**
 * PMD stats in wp-admin: the Dashboard (our widget replaces all the others)
 * and Donations → Stats (months, drilling down to days).
 *
 * Everything here reads the precomputed stats table via lib/fns/stats.php,
 * and the charts are inline SVG, so a page view costs a couple of small
 * queries and no extra assets.
 */

const PAGE_SLUG = 'donman-stats';
const CAPABILITY = 'activate_plugins';
const WIDGET_ID = 'donman_stats';

/**
 * Chartable series for the Stats page, keyed by the `chart` query arg.
 */
const CHARTS = [
  'donations'          => 'Donations',
  'priority'           => 'Priority donations',
  'orphaned_donations' => 'Orphaned donations',
  '1800gj'             => '1-800-GOT-JUNK? API',
  'chhj'               => 'College Hunks API',
];

/**
 * The value a chart plots for one day or month.
 *
 * @param array  $metrics [ metric => value ].
 * @param string $chart   Key from CHARTS.
 * @return int
 */
function chart_value( $metrics, $chart ){
  // Priority donations are the ones the partners pay for, across all of them.
  if( 'priority' == $chart ){
    $total = 0;
    foreach( array_keys( PARTNERS ) as $partner )
      $total+= partner_figures( $metrics, $partner )['priority'];
    return $total;
  }
  if( array_key_exists( $chart, PARTNERS ) )
    return partner_figures( $metrics, $chart )['total'];
  return isset( $metrics[ $chart ] )? $metrics[ $chart ] : 0;
}

/**
 * Tooltip content for one day or month: every chartable series, with the
 * charted one marked.
 *
 * @param string $heading The day or month.
 * @param array  $metrics [ metric => value ].
 * @param string $chart   Key from CHARTS.
 * @return array heading and rows of [ label, value, note, current ].
 */
function tooltip( $heading, $metrics, $chart ){
  $rows = [];
  foreach( CHARTS as $key => $label ){
    $note = '';
    if( array_key_exists( $key, PARTNERS ) ){
      $rate = partner_figures( $metrics, $key )['success_rate'];
      if( ! is_null( $rate ) )
        $note = format_rate( $rate ) . ' success';
    }
    $rows[] = [ $label, number_format( chart_value( $metrics, $key ) ), $note, $key == $chart ];
  }
  return [ 'heading' => $heading, 'rows' => $rows ];
}

/**
 * URL of the Stats page.
 *
 * @param array $args Query args (month, year, chart).
 * @return string
 */
function page_url( $args = [] ){
  return add_query_arg( array_merge( [ 'post_type' => 'donation', 'page' => PAGE_SLUG ], $args ), admin_url( 'edit.php' ) );
}

/**
 * Formats a success rate.
 *
 * @param float|null $rate Percentage.
 * @return string
 */
function format_rate( $rate ){
  return is_null( $rate )? '–' : number_format( $rate, 1 ) . '%';
}

/**
 * Renders a single-series bar chart as inline SVG.
 *
 * Each column has a full-height hit area carrying its tooltip() data, which
 * tooltip_script() shows on hover.
 *
 * @param array  $points [ [ 'label' => axis label or '', 'tip' => tooltip() data, 'value' => int ] ].
 * @param string $name   Accessible name for the chart.
 * @param string $series Optional series class, e.g. `priority`.
 * @return string SVG markup.
 */
function bar_chart( $points, $name, $series = '' ){
  $width = 900;
  $height = 160;
  $top = 8;
  $bottom = 20;
  $left = 52;
  $plot_h = $height - $top - $bottom;
  $plot_w = $width - $left;

  $max = max( 1, max( wp_list_pluck( $points, 'value' ) ) );
  // A "nice" tick (1, 2, 2.5 or 5 × 10^n) giving at most 4 gridlines.
  $raw = $max / 4;
  $step = pow( 10, floor( log10( $raw ) ) );
  foreach( [ 1, 2, 2.5, 5, 10 ] as $m ){
    $tick = $step * $m;
    if( $tick >= $raw )
      break;
  }
  $tick = max( 1, $tick );
  $scale_max = ceil( $max / $tick ) * $tick;

  $svg = '<svg class="donman-chart' . ( $series? ' series-' . esc_attr( $series ) : '' ) . '" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="' . esc_attr( $name ) . '">';

  for( $v = 0; $v <= $scale_max; $v+= $tick ){
    $y = $top + $plot_h - ( $v / $scale_max * $plot_h );
    $svg.= '<line class="grid" x1="' . $left . '" x2="' . $width . '" y1="' . $y . '" y2="' . $y . '"/>';
    $svg.= '<text class="axis" x="' . ( $left - 6 ) . '" y="' . ( $y + 4 ) . '" text-anchor="end">' . esc_html( number_format( $v ) ) . '</text>';
  }

  $col = $plot_w / max( 1, count( $points ) );
  $bar_w = max( 1, $col - 2 );
  $radius = min( 4, $bar_w / 2 );
  foreach( array_values( $points ) as $i => $point ){
    $x = $left + $i * $col + 1;
    $h = $point['value'] / $scale_max * $plot_h;
    $base = $top + $plot_h;
    $svg.= '<g class="col" data-tip="' . esc_attr( wp_json_encode( $point['tip'] ) ) . '">';
    $svg.= '<rect class="hit" x="' . ( $left + $i * $col ) . '" y="' . $top . '" width="' . $col . '" height="' . $plot_h . '"/>';
    if( 0 < $h ){
      $r = min( $radius, $h );
      // Rounded top corners only, anchored to the baseline.
      $svg.= sprintf(
        '<path class="bar" d="M%1$.1f %2$.1f V%3$.1f Q%1$.1f %4$.1f %5$.1f %4$.1f H%6$.1f Q%7$.1f %4$.1f %7$.1f %3$.1f V%2$.1f Z"/>',
        $x, $base, $base - $h + $r, $base - $h, $x + $r, $x + $bar_w - $r, $x + $bar_w
      );
    }
    $svg.= '</g>';
    if( '' !== $point['label'] )
      $svg.= '<text class="axis" x="' . ( $x + $bar_w / 2 ) . '" y="' . ( $height - 4 ) . '" text-anchor="middle">' . esc_html( $point['label'] ) . '</text>';
  }

  return $svg . '</svg>';
}

/**
 * Table header cells for the stats columns.
 *
 * @param string $first_col Heading for the first column.
 * @param bool   $pending   Show a Pending column per partner.
 * @return string
 */
function table_head( $first_col, $pending = false ){
  $span = $pending? 6 : 5;
  $html = '<thead><tr><th rowspan="2" class="first">' . esc_html( $first_col ) . '</th><th rowspan="2">Donations</th><th rowspan="2">Orphaned<br>donations</th>';
  foreach( PARTNERS as $partner )
    $html.= '<th colspan="' . $span . '" class="group">' . esc_html( $partner['name'] ) . ' API</th>';
  $html.= '</tr><tr>';
  foreach( PARTNERS as $partner ){
    $html.= '<th>Non-Priority</th><th class="priority">Priority</th><th>Fails</th>';
    if( $pending )
      $html.= '<th>Pending</th>';
    $html.= '<th>Total</th><th>Success</th>';
  }
  return $html . '</tr></thead>';
}

/**
 * Table cells for one row of stats.
 *
 * @param array $metrics [ metric => value ].
 * @param bool  $pending Show a Pending column per partner.
 * @return string
 */
function table_cells( $metrics, $pending = false ){
  $n = function( $key ) use ( $metrics ){
    return number_format( isset( $metrics[ $key ] )? $metrics[ $key ] : 0 );
  };
  $html = '<td>' . $n( 'donations' ) . '</td><td>' . $n( 'orphaned_donations' ) . '</td>';
  foreach( array_keys( PARTNERS ) as $partner ){
    $f = partner_figures( $metrics, $partner );
    $html.= '<td>' . number_format( $f['non_priority'] ) . '</td><td class="priority">' . number_format( $f['priority'] ) . '</td><td>' . number_format( $f['fails'] ) . '</td>';
    if( $pending )
      $html.= '<td>' . number_format( $f['pending'] ) . '</td>';
    $html.= '<td>' . number_format( $f['total'] ) . '</td><td>' . format_rate( $f['success_rate'] ) . '</td>';
  }
  return $html;
}

/**
 * Sums metrics across rows.
 *
 * @param array $rows [ key => [ metric => value ] ].
 * @return array [ metric => value ].
 */
function sum_metrics( $rows ){
  $sum = [];
  foreach( $rows as $metrics ){
    foreach( $metrics as $metric => $value )
      $sum[ $metric ] = ( isset( $sum[ $metric ] )? $sum[ $metric ] : 0 ) + $value;
  }
  return $sum;
}

/**
 * Registers Donations → Stats.
 */
function admin_menu(){
  add_submenu_page( 'edit.php?post_type=donation', 'Stats', 'Stats', CAPABILITY, PAGE_SLUG, __NAMESPACE__ . '\\render_page' );
}
add_action( 'admin_menu', __NAMESPACE__ . '\\admin_menu' );

/**
 * Renders Donations → Stats.
 */
function render_page(){
  $chart = ( isset( $_GET['chart'] ) && array_key_exists( $_GET['chart'], CHARTS ) )? $_GET['chart'] : 'donations';
  $month = ( isset( $_GET['month'] ) && preg_match( '/^\d{4}-\d{2}$/', $_GET['month'] ) )? $_GET['month'] : null;
  $year = ( isset( $_GET['year'] ) && preg_match( '/^\d{4}$/', $_GET['year'] ) )? $_GET['year'] : null;
  $current_month = current_time( 'Y-m' );
  $first_month = get_first_month();

  echo '<div class="wrap donman-stats">';
  echo '<h1>Stats</h1>';

  if( is_null( $first_month ) ){
    echo '<p>No stats yet. Run <code>wp dm stats rebuild</code> to build them.</p></div>';
    return;
  }

  if( $month ){
    render_month( $month, $chart, $current_month, $first_month );
  } else {
    render_months( $year, $chart, $current_month, $first_month );
  }

  echo '<p class="description">Updated every 15 minutes. Daily figures start in ' . esc_html( date( 'F Y', strtotime( daily_since() . '-01' ) ) ) . ', when the oldest donations still on the site were made; earlier months show monthly totals only. An API donation with no response yet counts as Pending for 2 hours, then as a Fail.</p>';
  echo '</div>';
}

/**
 * The first month with daily partner stats.
 *
 * @return string Y-m.
 */
function daily_since(){
  global $wpdb;
  $since = wp_cache_get( 'daily_since:' . wp_cache_get_last_changed( \DonationManager\stats\CACHE_GROUP ), \DonationManager\stats\CACHE_GROUP );
  if( false === $since ){
    $since = (string) $wpdb->get_var( "SELECT DATE_FORMAT(MIN(stat_date),'%Y-%m') FROM " . \DonationManager\stats\table() . " WHERE period = 'day' AND metric = 'donations'" );
    wp_cache_set( 'daily_since:' . wp_cache_get_last_changed( \DonationManager\stats\CACHE_GROUP ), $since, \DonationManager\stats\CACHE_GROUP, DAY_IN_SECONDS );
  }
  return $since;
}

/**
 * Chart picker links.
 *
 * @param string $chart Current chart.
 * @param array  $args  Query args to keep.
 * @return string
 */
function chart_picker( $chart, $args ){
  $links = [];
  foreach( CHARTS as $key => $label ){
    $links[] = ( $key == $chart )
      ? '<strong>' . esc_html( $label ) . '</strong>'
      : '<a href="' . esc_url( page_url( array_merge( $args, [ 'chart' => $key ] ) ) ) . '">' . esc_html( $label ) . '</a>';
  }
  return '<p class="donman-picker">Chart: ' . implode( ' | ', $links ) . '</p>';
}

/**
 * Months view: the last 12 months, or one calendar year.
 *
 * @param string|null $year          Y, or null for the last 12 months.
 * @param string      $chart         Key from CHARTS.
 * @param string      $current_month Y-m.
 * @param string      $first_month   Y-m.
 */
function render_months( $year, $chart, $current_month, $first_month ){
  if( $year ){
    $first = max( $year . '-01', $first_month );
    $last = min( $year . '-12', $current_month );
  } else {
    $first = max( date( 'Y-m', strtotime( $current_month . '-01 -11 months' ) ), $first_month );
    $last = $current_month;
  }

  // Year navigation.
  $nav = [ $year? '<a href="' . esc_url( page_url( [ 'chart' => $chart ] ) ) . '">Last 12 months</a>' : '<strong>Last 12 months</strong>' ];
  for( $y = (int) substr( $current_month, 0, 4 ); $y >= (int) substr( $first_month, 0, 4 ); $y-- ){
    $nav[] = ( $y == $year )? '<strong>' . $y . '</strong>' : '<a href="' . esc_url( page_url( [ 'year' => $y, 'chart' => $chart ] ) ) . '">' . $y . '</a>';
  }
  echo '<p class="donman-picker">' . implode( ' | ', $nav ) . '</p>';

  if( $first > $last ){
    echo '<p>No stats for this period.</p>';
    return;
  }

  $months = get_monthly( $first, $last );

  $points = [];
  foreach( array_reverse( $months, true ) as $ym => $data ){
    $value = chart_value( $data['metrics'], $chart );
    $points[] = [ 'label' => date( 'M', strtotime( $ym . '-01' ) ), 'tip' => tooltip( date( 'F Y', strtotime( $ym . '-01' ) ), $data['metrics'], $chart ), 'value' => $value ];
  }
  echo chart_picker( $chart, $year? [ 'year' => $year ] : [] );
  echo '<div class="donman-chart-wrap">' . bar_chart( $points, CHARTS[ $chart ] . ' per month', $chart ) . '</div>';

  echo '<p>Click a month to see its days.</p>';
  echo '<div class="donman-table-wrap"><table class="widefat striped donman-stats-table">' . table_head( 'Month' ) . '<tbody>';
  foreach( $months as $ym => $data ){
    $label = esc_html( date( 'M Y', strtotime( $ym . '-01' ) ) );
    if( $data['has_days'] )
      $label = '<a href="' . esc_url( page_url( [ 'month' => $ym, 'chart' => $chart ] ) ) . '">' . $label . '</a>';
    echo '<tr><th scope="row" class="first">' . $label . '</th>' . table_cells( $data['metrics'] ) . '</tr>';
  }
  echo '</tbody></table></div>';
}

/**
 * Days view for one month.
 *
 * @param string $month         Y-m.
 * @param string $chart         Key from CHARTS.
 * @param string $current_month Y-m.
 * @param string $first_month   Y-m.
 */
function render_month( $month, $chart, $current_month, $first_month ){
  $start = $month . '-01';
  $end = min( date( 'Y-m-t', strtotime( $start ) ), current_time( 'Y-m-d' ) );
  $days = ( $start <= $end )? get_daily( $start, $end ) : [];

  $prev = date( 'Y-m', strtotime( $start . ' -1 month' ) );
  $next = date( 'Y-m', strtotime( $start . ' +1 month' ) );
  $nav = [ '<a href="' . esc_url( page_url( [ 'year' => substr( $month, 0, 4 ), 'chart' => $chart ] ) ) . '">← All of ' . esc_html( substr( $month, 0, 4 ) ) . '</a>' ];
  if( $prev >= $first_month )
    $nav[] = '<a href="' . esc_url( page_url( [ 'month' => $prev, 'chart' => $chart ] ) ) . '">‹ ' . esc_html( date( 'M Y', strtotime( $prev . '-01' ) ) ) . '</a>';
  if( $next <= $current_month )
    $nav[] = '<a href="' . esc_url( page_url( [ 'month' => $next, 'chart' => $chart ] ) ) . '">' . esc_html( date( 'M Y', strtotime( $next . '-01' ) ) ) . ' ›</a>';
  echo '<p class="donman-picker">' . implode( ' | ', $nav ) . '</p>';
  echo '<h2>' . esc_html( date( 'F Y', strtotime( $start ) ) ) . '</h2>';

  if( empty( $days ) ){
    echo '<p>No stats for this month.</p>';
    return;
  }

  $points = [];
  foreach( $days as $date => $metrics ){
    $value = chart_value( $metrics, $chart );
    $day = date( 'j', strtotime( $date ) );
    $points[] = [ 'label' => ( 1 == $day || 0 == $day % 5 )? $day : '', 'tip' => tooltip( date( 'D, M j', strtotime( $date ) ), $metrics, $chart ), 'value' => $value ];
  }
  echo chart_picker( $chart, [ 'month' => $month ] );
  echo '<div class="donman-chart-wrap">' . bar_chart( $points, CHARTS[ $chart ] . ' per day, ' . date( 'F Y', strtotime( $start ) ), $chart ) . '</div>';

  $total = sum_metrics( $days );
  $pending = false;
  foreach( array_keys( PARTNERS ) as $partner )
    $pending = $pending || ! empty( $total[ "{$partner}_pending" ] );

  echo '<div class="donman-table-wrap"><table class="widefat striped donman-stats-table">' . table_head( 'Day', $pending ) . '<tbody>';
  foreach( array_reverse( $days, true ) as $date => $metrics )
    echo '<tr><th scope="row" class="first">' . esc_html( date( 'D, M j', strtotime( $date ) ) ) . '</th>' . table_cells( $metrics, $pending ) . '</tr>';
  echo '</tbody><tfoot><tr><th scope="row" class="first">Total</th>' . table_cells( $total, $pending ) . '</tr></tfoot></table></div>';
}

/**
 * Replaces every Dashboard widget with ours.
 *
 * Runs last on wp_dashboard_setup so it also catches plugins' widgets.
 */
function dashboard_setup(){
  global $wp_meta_boxes;

  remove_action( 'welcome_panel', 'wp_welcome_panel' );
  if( isset( $wp_meta_boxes['dashboard'] ) )
    $wp_meta_boxes['dashboard'] = [];

  if( current_user_can( CAPABILITY ) )
    wp_add_dashboard_widget( WIDGET_ID, 'PickUpMyDonation.com Stats', __NAMESPACE__ . '\\render_widget' );
}
add_action( 'wp_dashboard_setup', __NAMESPACE__ . '\\dashboard_setup', PHP_INT_MAX );

/**
 * Renders the Dashboard widget: the last 30 days, and this month vs. last.
 */
function render_widget(){
  $today = current_time( 'Y-m-d' );
  $start = date( 'Y-m-d', strtotime( $today . ' -29 days' ) );
  $days = get_daily( $start, $today );
  $last_30 = sum_metrics( $days );

  $this_month = current_time( 'Y-m' );
  $last_month = date( 'Y-m', strtotime( $this_month . '-01 -1 month' ) );
  $months = get_monthly( $last_month, $this_month );

  echo '<div class="donman-stats">';
  echo '<h3>Last 30 days</h3><div class="donman-tiles">';
  $split = [];
  foreach( PARTNERS as $partner => $config )
    $split[] = esc_html( $config['name'] ) . ' ' . number_format( partner_figures( $last_30, $partner )['priority'] );
  $tiles = [
    [ 'Donations', number_format( isset( $last_30['donations'] )? $last_30['donations'] : 0 ), '' ],
    [ 'Priority donations', number_format( chart_value( $last_30, 'priority' ) ) . '<span>' . implode( ' · ', $split ) . '</span>', 'priority' ],
    [ 'Orphaned donations', number_format( isset( $last_30['orphaned_donations'] )? $last_30['orphaned_donations'] : 0 ), '' ],
  ];
  foreach( PARTNERS as $partner => $config ){
    $f = partner_figures( $last_30, $partner );
    $tiles[] = [ $config['name'] . ' API', number_format( $f['total'] ) . '<span>' . format_rate( $f['success_rate'] ) . ' success</span>', '' ];
  }
  foreach( $tiles as list( $label, $value, $class ) )
    echo '<div class="donman-tile ' . esc_attr( $class ) . '"><div class="label">' . esc_html( $label ) . '</div><div class="value">' . $value . '</div></div>';
  echo '</div>';

  foreach( [ 'donations', 'priority' ] as $chart ){
    $points = [];
    foreach( $days as $date => $metrics ){
      $value = chart_value( $metrics, $chart );
      $points[] = [ 'label' => ( 0 == count( $points ) % 7 )? date( 'M j', strtotime( $date ) ) : '', 'tip' => tooltip( date( 'D, M j', strtotime( $date ) ), $metrics, $chart ), 'value' => $value ];
    }
    echo '<h3>' . esc_html( CHARTS[ $chart ] ) . ' per day</h3><div class="donman-chart-wrap">' . bar_chart( $points, CHARTS[ $chart ] . ' per day, last 30 days', $chart ) . '</div>';
  }

  echo '<h3>This month vs. last month</h3>';
  echo '<div class="donman-table-wrap"><table class="widefat striped donman-stats-table">' . table_head( 'Month' ) . '<tbody>';
  foreach( $months as $ym => $data ){
    $label = '<a href="' . esc_url( page_url( [ 'month' => $ym ] ) ) . '">' . esc_html( date( 'M Y', strtotime( $ym . '-01' ) ) ) . '</a>';
    echo '<tr><th scope="row" class="first">' . $label . '</th>' . table_cells( $data['metrics'] ) . '</tr>';
  }
  echo '</tbody></table></div>';

  echo '<p><a class="button" href="' . esc_url( page_url() ) . '">All stats by month and day →</a> <span class="description">Updated every 15 minutes.</span></p>';
  echo '</div>';
}

/**
 * Styles for the Dashboard widget and the Stats page.
 */
function admin_css(){
  $screen = get_current_screen();
  if( ! $screen || ! in_array( $screen->id, [ 'dashboard', 'donation_page_' . PAGE_SLUG ] ) )
    return;
  echo '<style>
  #dashboard-widgets #postbox-container-1{width:100%;}
  #dashboard-widgets #postbox-container-2,#dashboard-widgets #postbox-container-3,#dashboard-widgets #postbox-container-4{display:none;}
  .donman-stats{--donman-bar:#2271b1;--donman-bar-hover:#135e96;--donman-priority:#008a20;--donman-priority-hover:#005c12;--donman-priority-tint:#edfaef;--donman-grid:#e0e0e0;--donman-ink-muted:#646970;}
  .donman-stats h3{margin:1.2em 0 .5em;font-size:14px;font-weight:600;}
  .donman-stats h3:first-child{margin-top:.4em;}
  .donman-tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;}
  .donman-tile{border:1px solid #dcdcde;border-radius:4px;padding:10px 12px;background:#fff;}
  .donman-tile .label{color:var(--donman-ink-muted);font-size:12px;}
  .donman-tile .value{font-size:22px;font-weight:600;line-height:1.4;}
  .donman-tile .value span{display:block;font-size:12px;font-weight:400;color:var(--donman-ink-muted);}
  .donman-tile.priority{background:var(--donman-priority-tint);border-color:var(--donman-priority);border-left-width:4px;}
  .donman-chart-wrap{max-width:900px;position:relative;}
  .donman-tip{position:absolute;z-index:10;pointer-events:none;background:#fff;border:1px solid #dcdcde;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,.15);padding:8px 10px;font-size:12px;line-height:1.5;white-space:nowrap;}
  .donman-tip[hidden]{display:none;}
  .donman-tip .heading{font-weight:600;margin-bottom:4px;}
  .donman-tip table{border-collapse:collapse;}
  .donman-tip td{padding:0 0 0 12px;color:var(--donman-ink-muted);}
  .donman-tip td:first-child{padding-left:0;}
  .donman-tip td.value{text-align:right;color:#1d2327;font-variant-numeric:tabular-nums;}
  .donman-tip tr.current td{font-weight:600;color:#1d2327;}
  .donman-chart{display:block;width:100%;height:auto;}
  .donman-chart .bar{fill:var(--donman-bar);}
  .donman-chart .col:hover .bar{fill:var(--donman-bar-hover);}
  .donman-chart.series-priority .bar{fill:var(--donman-priority);}
  .donman-chart.series-priority .col:hover .bar{fill:var(--donman-priority-hover);}
  .donman-chart .hit{fill:transparent;}
  .donman-chart .grid{stroke:var(--donman-grid);stroke-width:1;vector-effect:non-scaling-stroke;}
  .donman-chart .axis{fill:var(--donman-ink-muted);font-size:11px;}
  .donman-picker{margin:.6em 0;}
  .donman-table-wrap{overflow-x:auto;}
  .donman-stats-table{width:auto;min-width:100%;}
  .donman-stats-table th,.donman-stats-table td{text-align:right;white-space:nowrap;padding:6px 10px;}
  .donman-stats-table .first{text-align:left;}
  .donman-stats-table thead th{font-weight:600;}
  .donman-stats-table thead th.group{text-align:center;border-bottom:1px solid #dcdcde;}
  .donman-stats-table tfoot th,.donman-stats-table tfoot td{font-weight:600;}
  table.widefat.donman-stats-table tfoot td{text-align:right;}
  table.widefat.donman-stats-table .priority{background:var(--donman-priority-tint);font-weight:600;}
  table.widefat.donman-stats-table thead th.priority{box-shadow:inset 0 3px 0 var(--donman-priority);}
  </style>';
}
add_action( 'admin_head', __NAMESPACE__ . '\\admin_css' );

/**
 * Shows a chart column's tooltip() data on hover.
 */
function tooltip_script(){
  $screen = get_current_screen();
  if( ! $screen || ! in_array( $screen->id, [ 'dashboard', 'donation_page_' . PAGE_SLUG ] ) )
    return;
  ?>
  <script>
  document.querySelectorAll( '.donman-chart-wrap' ).forEach( function( wrap ){
    var tip = document.createElement( 'div' );
    tip.className = 'donman-tip';
    tip.hidden = true;
    wrap.appendChild( tip );

    var esc = function( text ){
      var span = document.createElement( 'span' );
      span.textContent = text;
      return span.innerHTML;
    };

    wrap.querySelectorAll( '.col' ).forEach( function( col ){
      col.addEventListener( 'mouseenter', function(){
        var data = JSON.parse( col.dataset.tip );
        var html = '<div class="heading">' + esc( data.heading ) + '</div><table>';
        data.rows.forEach( function( row ){
          html += '<tr' + ( row[3] ? ' class="current"' : '' ) + '><td>' + esc( row[0] ) + '</td><td class="value">' + esc( row[1] ) + '</td><td>' + esc( row[2] ) + '</td></tr>';
        } );
        tip.innerHTML = html + '</table>';
        tip.hidden = false;

        // Beside the column (left of it near the right edge), level with
        // the top of its bar, so it never covers the bar being read.
        var w = wrap.getBoundingClientRect(), c = col.getBoundingClientRect();
        var bar = col.querySelector( '.bar' );
        var barTop = bar ? bar.getBoundingClientRect().top : c.bottom;
        var left = c.right - w.left + 8;
        if( left + tip.offsetWidth > w.width )
          left = c.left - w.left - tip.offsetWidth - 8;
        tip.style.left = Math.max( 0, left ) + 'px';
        tip.style.top = Math.max( 0, Math.min( barTop - w.top, w.height - tip.offsetHeight ) ) + 'px';
      } );
      col.addEventListener( 'mouseleave', function(){
        tip.hidden = true;
      } );
    } );
  } );
  </script>
  <?php
}
add_action( 'admin_footer', __NAMESPACE__ . '\\tooltip_script' );
