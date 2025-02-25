<?php
/**
 * Sends organization reports.
 *
 * @param      string  $month  The month in 'Y-m' format (e.g. 2017-01)
 */

if( ! isset( $DMReports ) )
  $DMReports = \DMReports::get_instance();

if( isset( $orgs ) && is_array( $orgs ) ){
  // nothing, $orgs has been set by --orgs=<org_ids> in /lib/classes/cli.php
} else {
  $orgs = $DMReports->get_all_orgs();  
}

uber_log( '🔔 $orgs = ' . print_r( $orgs, true ) );
//die();

foreach( $orgs as $key => $org_id ){
  // Continue if we don't have any `monthly_report_emails` for the org
  $monthly_report_emails = strip_tags( get_post_meta( $org_id, 'monthly_report_emails', true ) );
  $organization_name = get_the_title( $org_id );
  uber_log( '🔔 Monthly Report Emails emails for `' . $organization_name . ' (#' . $org_id . ')`' . "\n - Sending to: " . $monthly_report_emails . "\n - Month: " . $month );
  if( empty( $monthly_report_emails ) )
    continue;

  $email_array = explode( ",", $monthly_report_emails );

  foreach( $email_array as $key => $email ){
    if( ! is_email( trim( $email ) ) ){
      unset( $email_array[$key] );
    } else {
      $email_array[$key] = trim( $email );
    }
  }
  if( 0 == count( $email_array ) ){
    uber_log( '👋 NO EMAILS for ' . $organization_name );
    continue;
  }

  $donations = $DMReports->get_donations( $org_id, $month );
  if( is_null( $donations ) || empty( $donations ) ){
    uber_log( '  🚨 No donations for ' . $organization_name );
    continue;
  } else {
    $donation_count = count( $donations );
    uber_log( '  ✅ ' . $donation_count . ' donations for ' . $organization_name );
  }



  // Only send report emails to orgs with 5 or more donations during the month
  if( is_array( $donations ) && 5 <= $donation_count ){
    \WP_CLI::line( '🔔 ' . strtoupper( $organization_name ) . ' received ' . $donation_count . ' donations.' );

    // Build a donation report CSV
    $csv = '"Date/Time Modified","DonorName","DonorCompany","DonorAddress","DonorCity","DonorState","DonorZip","DonorPhone","DonorEmail","DonationAddress","DonationCity","DonationState","DonationZip","DonationDescription","PickupDate1","PickupDate2","PickupDate3","PreferredDonorCode"' . "\n" . implode( "\n", $donations );
    $filename = $month . '_' . sanitize_file_name( $organization_name ) . '.csv';
    $attachment_id = DonationManager\utilities\save_report_csv( $filename, $csv );
    $attachment_file = get_attached_file( $attachment_id );

    // Send the report
    $args = [ 'org_id' => $org_id, 'month' => $month, 'attachment_file' => $attachment_file, 'donation_count' => $donation_count, 'to' => $email_array, 'force' => $force ];
    $DMReports->send_donation_report( $args );

    // Clean up
    wp_delete_attachment( $attachment_id, true );
  } else {
    \WP_CLI::line( '🛑 ' . $donation_count . ' donations found for `' . $organization_name . '`. No report sent.' );
  }
}
