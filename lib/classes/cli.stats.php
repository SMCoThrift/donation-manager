<?php
// Only run if in the WP_CLI
if( defined( 'WP_CLI' ) && 'WP_CLI' && true == WP_CLI ){
  /**
   * Statistical tools for Donation Manager.
   */
  class DonationManagerStats {

    /**
     * Generates stats for Priority Partners.
     *
     * ## OPTIONS
     *
     * <org_key>
     * : Specifies the organization for whom we're generating stats.
     * ---
     * default: 1800gj
     * options:
     *   - 1800gj
     *   - chhj
     * ---
     *
     * [<month>]
     * : Month in YYYY-MM format for which we're gathering stats. Defaults to the current month.
     * ---
     * default:
     * ---
     *
     * [--show]
     * : Output the stats in a table.
     * ---
     * default: false
     * options:
     *   - true
     *   - false
     * ---
     *
     * ## EXAMPLES
     *
     *     wp dm stats priority 1800gj 2024-10
     *     wp dm stats priority chhj --show=true
     */
    function priority( $args, $assoc_args ){
      list( $org_key, $month ) = $args;
      if( empty( $month ) ){
        $timestamp = current_time( 'mysql' );
        $dateObj = date_create( $timestamp );
        $date = date_format( $dateObj, 'Y-m');
      }
      WP_CLI::line( '👉 YYYY-MM = ' . $date );
      if( ! stristr( $date, '-' ) || 7 != strlen( $date ) || empty( $date ) || '-' != substr( $date, 4, 1 ) )
        WP_CLI::error( '🚨 Please provide a month in the format YYYY-MM as the first positional argument when calling this file.');

      $date_array = explode( '-', $date );
      $year = $date_array[0];
      $month = $date_array[1];

      WP_CLI::line( '$year = ' . $year . "\n" . '$month = ' . $month );

      WP_CLI::line("Running Priority Stats for {$org_key} in {$month}");
      $show = ( array_key_exists( 'show', $assoc_args ) && 'true' === strtolower( $assoc_args['show'] ) );

      $organizations = [
        'chhj' => [ 'id' => 511971, 'name' => 'College Hunks Hauling Junk', 'api_method' => 'chhj_api' ],
        '1800gj' => [ 'id' => 521689, 'name' => '1-800-GOT-JUNK?', 'api_method' => '1800gj_api' ],
      ];
      if( $org_key ){
        $org_name =  $organizations[ $org_key ]['name'];
        $org_id =  $organizations[ $org_key ]['id'];
        $org_api_method =  $organizations[ $org_key ]['api_method'];
        WP_CLI::line( '⚙️ Organization: ' . $org_name );
      } else {
        WP_CLI::error( 'Please specify your org key (chhj or 1800gj) with the 1st positional argument.' );
      }

      $query_args = [
        'post_type'       => 'donation',
        'order'           => 'ASC',
        'orderby'         => 'ID',
        'posts_per_page'  => -1,
        'date_query'  => [
          [
            'year' => $year,
            'month' => $month,
          ],
        ],
        'meta_query'  => [
          [
            'key'     => 'api_method',
            'value'   => $org_api_method,
            'type'    => 'CHAR',
            'compare' => '=',
          ],
        ],
      ];
      $donations = get_posts( $query_args );
      /////
      if( $show ){
        WP_CLI::line( 'Building Table...' );
        $rows = [];
        $row = 1;
        foreach ( $donations as $donation ) {
          $organization_name = get_post_meta( $donation->ID, '_organization_name', true );
          $sanitized_title = str_replace([ '&#8211;', '&amp;' ], [ '-', '&' ], get_the_title( $donation->ID ) );
          $response_code = get_post_meta( $donation->ID, 'api_response_code', true );
          $response_message = get_post_meta( $donation->ID, 'api_response_message', true );
          if( empty( $response_code ) )
            $response_code = 'EMPTY';
          //*
          if( 200 == $response_code ){
            $response_code = '✅ ' . $response_code;
          } else {
            if( 404 == $response_code ){
              $response_message.= ' (' . get_post_meta( $donation->ID, 'address_zip', true ) . ')';
            }
            $response_code = '🚨 ' . $response_code;
          }
          /**/
          $rows[] = [
            'No.' => $row,
            'ID' => $donation->ID,
            'Date' => get_the_date( 'Y-m-d H:i:s', $donation->ID ),
            'Code' => $response_code,
            'Message' => $response_message,
            'API Method'  => get_post_meta( $donation->ID, 'api_method', true ),
            'Title' => substr( $sanitized_title, 0, 40 ),
            'Organization' => $organization_name,
          ];
          $row++;
        }
        WP_CLI\Utils\format_items( 'table', $rows, 'No.,ID,Date,Code,Message,API Method,Title,Organization' );
      }
      $donation_counts = [
        'priority'      => 0,
        'non-priority'  => 0,
      ];

      $fails = 0;
      $failed_rows = [];
      foreach ( $donations as $donation ) {
        $organization_name = get_post_meta( $donation->ID, '_organization_name', true );
        $api_response = get_post_meta( $donation->ID, 'api_response', true );
        $response_code = get_post_meta( $donation->ID, 'api_response_code', true );
        $response_message = get_post_meta( $donation->ID, 'api_response_message', true );
        if( 200 != $response_code  ){
          $fails++;
          $reason = 'Unknown';
          if( stristr( $api_response, 'Invalid phone number' ) ){
            $reason = 'Invalid phone number';
          } else if ( stristr( $api_response, 'Invalid type conversion' ) ){
            $reason = 'Parse error/String not escaped properly';
          } else if ( stristr( $api_response, 'Area Not Serviced' ) ){
            $reason = 'Area Not Serviced (Zip Code: ' . get_post_meta( $donation->ID, 'address_zip', true ). ')';
          } else if ( stristr( $api_response, 'cURL error 28' ) ){
            $reason = $api_response;
          }
          $failed_rows[] = [
            'No.' => $fails,
            'ID'  => $donation->ID,
            'Date'  => get_the_date( 'Y-m-d H:i:s', $donation->ID ),
            'Code'  => $response_code,
            'Message' => $response_message,
            'Reason'  => substr( $reason, 0, 60 ),
            'Organization' => get_post_meta( $donation->ID, '_organization_name', true ),
          ];
          //WP_CLI::line('🚨 #' . $donation->ID . ' ' . $organization_name . ' (' . $response_code . ' - ' . $response_message . '). Reason: ' . $reason );
        }
        if( 'PickUpMyDonation.com' == $organization_name ){
          $donation_counts['non-priority']++;
        } else {
          $donation_counts['priority']++;
        }
      }

      $failure_rate = ($fails/( $donation_counts['non-priority'] + $donation_counts['priority'] ) ) * 100;
      $success_rate = 100 - $failure_rate;
      $success_rate_percentage = number_format( $success_rate, 2 );

      $stats = [];
      $stats[] = [
        'Month'         => $month,
        'Total'         => $donation_counts['non-priority'] + $donation_counts['priority'],
        'Non-Priority'  => $donation_counts['non-priority'],
        'Priority'      => $donation_counts['priority'],
        'Fails'         => $fails,
        'Success Rate'  => $success_rate_percentage . '%',
      ];
      WP_CLI\Utils\format_items( 'table', $stats, 'Month,Total,Non-Priority,Priority,Fails,Success Rate' );
      WP_CLI::line( 'NOTE: Success Rate is calculated by dividing fails by the total number of Non-Priority and Priority donations and subtracting from 100%.' );
      WP_CLI\Utils\format_items( 'table', $failed_rows, 'No.,ID,Date,Code,Message,Reason,Organization' );

      $donation_stats_option = get_option( "{$org_key}_donations" );
      if( ! is_array( $donation_stats_option ) )
        $donation_stats_option = [];

      $donation_stats_option[ $month ] = [
        'non-priority'  => $donation_counts['non-priority'],
        'priority'      => $donation_counts['priority'],
        'fails'         => $fails,
        'success_rate_percentage' => $success_rate_percentage,
      ];
      update_option( "{$org_key}_donations", $donation_stats_option );
      /////
    }
  }
  WP_CLI::add_command( 'dm stats', 'DonationManagerStats' );
} else {
  if( ! defined( 'WP_CLI' ) )
    define( 'WP_CLI', false );
}