<?php

// Only run if in the WP_CLI
if( defined( 'WP_CLI' ) && 'WP_CLI' ){

  /**
   * The Donation Manager CLI.
   */
  class DonationManagerCLI {

    /**
     * Generate Donation Manager reports.
     *
     * ## OPTIONS
     *
     * --type=<type>
     * : The specific report. Can be:
     * - networkmembers (PMD 2.0 this was called by setting `--provider=nonexclusive`)
     * - organizations (PMD 2.0 this was the default for `wp donman sendreports`, these are the `exclusive` providers)
     * - zipsbytransdept (11/28/2022 (07:25) - doesn't appear to be in use anywhere)
     *
     * --month=<month>
     * : The month in Y-m (e.g. 2022-11) format.
     *
     * [--format=<table|csv|json|yaml|ids|count>]
     * : Output format of the report (i.e.  ‘table’, ‘json’, ‘csv’, ‘yaml’, ‘ids’, ‘count’)
     */
    function report( $args, $assoc_args ){
      $type = $assoc_args['type'];
      $month = $assoc_args['month'];
      $month_array = explode( '-', $month );
      if( ! is_numeric( $month_array[0] ) || ! is_numeric( $month_array[1] ) )
        WP_CLI::error( '🚨 Provided `month` is not formatted correctly. Please provide a month in the following format: YYYY-MM.' );

      $format = ( isset( $assoc_args['format'] ) )? $assoc_args['format'] : 'table' ;

      $report_file = DONMAN_PLUGIN_PATH . 'lib/fns/cli/report.' . $type . '.php';
      if( ! file_exists( $report_file ) )
        WP_CLI::error( '✋ File `' . basename( $report_file ) . '` does not exist! This command expects to find `lib/fns/cli/' . basename( $report_file ) . '` with the necessary logic for generating the `' . $type . '` report.' );

      require_once( $report_file );
    }

    /**
     * Test Donation Manager internal functions.
     *
     * ## OPTIONS
     *
     * [<values>...]
     * : Values required by some functions for testing.
     *
     * --function=<function>
     * : The function to test.
     * ---
     * Available functions to test:
     *   - get_all_donations
     *   - get_default_organization
     *   - get_donation_contact
     *   - get_donation_meta
     *   - get_organizations
     *   - get_orphaned_donation_contacts
     *   - get_priority_organizations
     *   - get_screening_questions
     *   - get_trans_dept_ids
     *   - is_orphaned_donation
     *   - save_donation
     * ---
     *
     * ## EXAMPLES
     *
     *   wp dm test --function=get_default_organization
     */
    function test( $args, $assoc_args ){
      define( 'WP_CLI_TEST', true );

      $function = $assoc_args['function'];

      switch( $function ){
        case 'get_all_donations':
          if(
            ! isset( $args[0] ) ||
            7 != strlen( $args[0] ) ||
            ! stristr( $args[0], '-' ) ||
            ! is_numeric( substr( $args[0], 0, 4 ) ) ||
            ! is_numeric( substr( $args[0], 5, 2 ) )
          )
            WP_CLI::error( 'Please the month you want to retrieve in YYYY-MM format as the first positional argument.' );

          require_once( DONMAN_PLUGIN_PATH . 'lib/classes/donation-reports.php' );
          $DMreports = DMReports::get_instance();
          // Get _offset and donations
          $offset = 0;
          $month = $args[0];
          $donations_per_page = 100;
          $donations = $DMreports->get_all_donations( $offset, $donations_per_page, $month );
          WP_CLI::line( '🔔 get_all_donations( ' . $offset . ', ' . $donations_per_page .', ' . $month . ' ) = ' . print_r( $donations, true ) );
          break;

        case 'get_default_organization':
          $priority = false;
          if( ! isset( $args[0] ) ){
            WP_CLI::line( '👉 You may add `true` as the first positional argument to return the default Priority Organization.' );
          } else if( 'true' == $args[0] ) {
            WP_CLI::line( '👉 Testing for default PRIORITY Organization.' );
            $priority = true;
          }
          $org = DonationManager\organizations\get_default_organization( $priority );
          WP_CLI::line( '🔔 get_default_organization( `' . $priority . '` ) returns $org = ' . print_r( $org, true ) );
          break;

        case 'get_donation_contact':
          if( ! isset( $args[0] ) )
            WP_CLI::error( 'Please provide a Donation ID as the first positional argument.' );
          $donation_id = $args[0];
          if( ! isset( $args[1] ) ){
            WP_CLI::error( '2nd argument must be either `donor` or `transdept`.' );
          } else {
            $contact_type = $args[1];
            WP_CLI::line( 'Testing with $contact_type set to ' . $contact_type );
          }
          $contact = DonationManager\donations\get_donation_contact( $donation_id, $contact_type );
          WP_CLI::line( '🔔 get_donation_contact( ' . $donation_id . ', ' . $contact_type . ' ) returns: ' . print_r( $contact, true ) );
          break;

        case 'get_donation_meta':
          if( ! isset( $args[0] ) )
            WP_CLI::error( 'Please provide a Donation ID as the first positional argument.' );
          $donation_id = $args[0];
          if( ! is_numeric( $donation_id ) )
            WP_CLI::error( '🚨 Donation ID is NOT numeric!' );
          $posttype = get_post_type( $donation_id );
          if( ! 'donation' == $posttype )
            WP_CLI::error( '🚨 $posttype for #' . $donation_id . ' is ' . $posttype . '. Please provide an ID for a donation.' );

          $custom_fields = get_post_custom( $donation_id );
          WP_CLI::line('🔔 $custom_fields = ' . print_r( $custom_fields, true ) );
          break;

        case 'get_priority_organizations':
          if( ! isset( $args[0] ) )
            WP_CLI::error( 'This test requires a pickup code as the first postional argument.' );
          $pickup_code = $args[0];
          $priority_orgs = DonationManager\organizations\get_priority_organizations( $pickup_code );
          WP_CLI::line( '🔔 get_priority_organizations() returns $priority_orgs = ' . print_r( $priority_orgs, true ) );
          break;

        case 'get_organizations':
          if( ! isset( $args[0] ) || ! is_numeric( $args[0] ) )
            WP_CLI::error( 'This test requires a numeric zip code as the first postional argument.' );
          $organizations = DonationManager\organizations\get_organizations( $args[0] );
          WP_CLI::line( '🔔 get_organizations() returns $organizations = ' . print_r( $organizations, true ) );
          break;

        case 'get_orphaned_donation_contacts':
          if( ! isset( $args[0] ) || ! is_numeric( $args[0] ) )
            WP_CLI::error( 'This test requires a numeric zip code as the first positional argument.' );
          $pickup_code = $args[0];
          if( ! isset( $args[1] ) || ! is_numeric( $args[1] ) )
            WP_CLI::line( 'Using a pickup radius of 15 miles.' );
          $orphaned_pickup_radius = ( ! isset( $args[1] ) || ! is_numeric( $args[1] ) )? 15 : $args[1] ;
          WP_CLI::line( 'Using these options as well:' . "\n  • \$priority = 0\n  • \$fields = store_name,email_address,zipcode,priority\n  • \$duplicates = true\n  • \$show_in_results = 0" );

          $contacts = DonationManager\orphanedproviders\get_orphaned_donation_contacts( [ 'pcode' => $pickup_code, 'radius' => $orphaned_pickup_radius, 'priority' => 0, 'fields' => 'store_name,email_address,zipcode,priority', 'duplicates' => true, 'show_in_results' => 0 ] );
          //$contacts = DonationManager\orphanedproviders\get_orphaned_donation_contacts( array( 'pcode' => $pickup_code, 'limit' => 1, 'priority' => 1 ) );
          if( ! is_wp_error( $contacts ) ){
            WP_CLI::line( '🔔 get_orphaned_donation_contacts() returns: ' . print_r( $contacts, true ) );
          } else {
            $errors = $contacts->get_error_messages();
            WP_CLI::error( '🔔 There were the following errors: ' . print_r( $errors, true ) );
          }
          break;

        case 'get_screening_questions':
          $org_id = null;
          if( ! isset( $args[0] ) || ! is_numeric( $args[0] ) ){
            WP_CLI::line( '👉 No Org ID provided, testing for default screening questions.' );
          } else {
            WP_CLI::line( '🔔 Testing for Org ID = ' . $args[0] );
            $org_id = $args[0];
          }
          $screening_questions = DonationManager\organizations\get_screening_questions( $org_id );
          WP_CLI::line( '🔔 get_screening_questions() returns $screening_questions = ' . print_r( $screening_questions, true ) );
          break;

        case 'get_trans_dept_ids':
          if( ! isset( $args[0] ) || ! is_numeric( $args[0] ) )
            WP_CLI::error( 'This test requires a numeric Org ID as the first postional argument.' );
          $org_id = $args[0];
          $trans_depts = DonationManager\transdepts\get_trans_dept_ids( $org_id );
          WP_CLI::line( '🔔 get_trans_dept_ids( ' . $org_id . ' ) = ' . print_r( $trans_depts, true ) );
          break;

        case 'is_orphaned_donation':
          if( ! isset( $args[0] ) || ! is_numeric( $args[0] ) )
            WP_CLI::error( 'This test requires a numeric Transportation Department ID as the first postional argument.' );
          $trans_dept_id = $args[0];
          $orphaned = DonationManager\organizations\is_orphaned_donation( $trans_dept_id );
          if( $orphaned ){
            WP_CLI::line( '👉 Donation IS orphaned.' );
          } else {
            WP_CLI::line( '👉 Donation is NOT orphaned.' );
          }
          break;

        case 'save_donation':
          $use_different_pickup_address = ( isset( $args[0] ) || in_array( strtolower( $args[0] ), [ 'yes', 'no' ] ) )? $args[0] : 'no' ;
          WP_CLI::line( '🔔 $use_different_pickup_address = ' . $use_different_pickup_address );
          $donation = DonationManager\utilities\get_test_donation( $use_different_pickup_address );
          $_SESSION['donor'] = $donation;
          $ID = DonationManager\donations\save_donation( $donation );
          $_SESSION['donor']['ID'] = $ID;
          DonationManager\donations\tag_donation( $ID, $donation );
          DonationManager\emails\send_email( 'trans_dept_notification' );
          DonationManager\emails\send_email( 'donor_confirmation' );
          break;

        default:
          WP_CLI::error('🚨 No test written for `' . $function . '()`.');
      }
    }

    /**
     * Writes donation stats to a JSON file.
     */
    function writestats(){
      WP_CLI::line('🔔 Running `dm writestats`...');
      $stats = new \stdClass();
      $stats->donations = new \stdClass();

      \WP_CLI::log( 'Getting stats from Donation Manager:' );

      $db_donations = \wp_count_posts( 'donation' );
      $archived_donations = DonationManager\helpers\get_archived_donations();

      \WP_CLI::log('- Archived Donations: ' . $archived_donations['total'] );
      \WP_CLI::log('- Donations in the DB: ' . $db_donations->publish );
      //$db_donations->publish = $db_donations->publish + $archived_donations['total'];

      $stats->donations->alltime = new \stdClass();
      $stats->donations->alltime->number = intval( $db_donations->publish + $archived_donations['total']);
      $stats->donations->alltime->value = DonationManager\helpers\get_donations_value( $stats->donations->alltime->number );
      \WP_CLI::log( '- All Time: ' . number_format( $stats->donations->alltime->number ) . ' total donations valued at $' . number_format( $stats->donations->alltime->value ) . '.' );

      $stats->donations->thisyear = new \stdClass();
      $db_donations_this_year = DonationManager\helpers\get_donations_by_interval( 'this_year' );
      $current_time = \current_time( 'Y-m-d' ) . ' first day of this year';
      $dt = \date_create( $current_time );
      $current_year = $dt->format( 'Y' );
      $archived_donations_this_year = DonationManager\helpers\get_archived_donations( $current_year );
      $stats->donations->thisyear->number = intval( $db_donations_this_year + $archived_donations_this_year['total'] );
      $stats->donations->thisyear->value = DonationManager\helpers\get_donations_value( $stats->donations->thisyear->number );

      $stats->donations->lastmonth = new \stdClass();
      $db_donations_last_month = DonationManager\helpers\get_donations_by_interval( 'last_month' );
      $current_time = \current_time( 'Y-m-d' ) . ' first day of last month';
      $dt = \date_create( $current_time );
      $last_months_year = $dt->format( 'Y' );
      $last_month = $dt->format( 'm' );
      $archived_donations_last_month = DonationManager\helpers\get_archived_donations( $last_months_year, $last_month );
      $stats->donations->lastmonth->number = intval( $db_donations_last_month + $archived_donations_last_month['total'] );
      $stats->donations->lastmonth->value = DonationManager\helpers\get_donations_value( $stats->donations->lastmonth->number );

      \WP_CLI::log( '- This Year: ' . number_format( $stats->donations->thisyear->number ) . ' donations valued at $' . number_format( $stats->donations->thisyear->value ) . '.' );
      \WP_CLI::log( '- Last Month: ' . number_format( $stats->donations->lastmonth->number ) . ' donations valued at $' . number_format( $stats->donations->lastmonth->value ) . '.' );

      $json_string = json_encode( $stats );
      file_put_contents( DONMAN_PLUGIN_PATH . 'stats.json', $json_string );

      \WP_CLI::success('Donation stats written to ' . DONMAN_PLUGIN_PATH . 'stats.json.');
    }
  }
  WP_CLI::add_command( 'dm', 'DonationManagerCLI' );

} else {
  define( 'WP_CLI', false );
  define( 'WP_CLI_TEST', false );
}