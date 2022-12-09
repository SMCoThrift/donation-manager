<?php

/**
 * Sends network member reports.
 *
 * @param      string  $month  The month in `Y-m` format
 */

if( ! isset( $DMReports ) )
  $DMReports = \DMReports::get_instance();

$network_members = $DMReports->get_all_network_members();

if( ! isset( $DMOrphanedDonations ) )
  $DMOrphanedDonations = \DMOrphanedDonations::get_instance();

global $wpdb;

foreach ( $network_members as $network_member ) {
  $network_member_sql = $DMOrphanedDonations->_get_orphaned_donations_query([
    'start_date' => $month,
    'search' => $network_member,
    'filterby' => 'store_name'
  ]);

  $network_member_data = $wpdb->get_results( $network_member_sql );
  if( 0 < count( $network_member_data ) ){
    $total_donations = 0;
    $receipients = [];
    foreach ( $network_member_data as $zipcode_data ) {
      /*
       Setup array as $network_member_data[email_address] = [total_donations=>donation_count,ID=>array()];
       */
      if( array_key_exists( $zipcode_data->email_address, $receipients ) ){
        $receipients[$zipcode_data->email_address]['total_donations'] = $receipients[$zipcode_data->email_address]['total_donations'] + $zipcode_data->total_donations;
        $receipients[$zipcode_data->email_address]['ID'][] = intval( $zipcode_data->ID );
      } else {
        $receipients[$zipcode_data->email_address]['total_donations'] = $zipcode_data->total_donations;
        $receipients[$zipcode_data->email_address]['ID'][] = intval( $zipcode_data->ID );
      }
    }

    // Remove receipients with < 5 donations
    foreach ( $receipients as $email_address => $data ) {
      if( 4 >= $data['total_donations'] )
        unset( $receipients[$email_address] );
    }

    // Send the reports
    if( 0 < count( $receipients ) ){
      // Send the report
      foreach ( $receipients as $email_address => $data ) {
        $args = [
          'ID' => $data['ID'],
          'email_address' => $email_address,
          'donation_count' => $data['total_donations'],
          'month' => $month,
        ];
        $DMReports->send_network_member_report( $args );
      }
    } else {
      \WP_CLI::line('No reports for `' . $network_member . '` as no recipients received > 4 donations.');
    }
  }
}