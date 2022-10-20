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
     * --report=<report>
     * : The specific report. Can be:
     * - networkmembers
     * - organizations
     * - zipbytransdept
     *
     * [--format=<table|csv|json|yaml|ids|count>]
     * : Output format of the report (i.e.  ‘table’, ‘json’, ‘csv’, ‘yaml’, ‘ids’, ‘count’)
     */
    function report( $args, $assoc_args ){
      $report = $assoc_args['report'];
      $format = ( isset( $assoc_args['format'] ) )? $assoc_args['format'] : 'table' ;

      $report_file = DONMAN_PLUGIN_PATH . 'lib/fns/cli/report.' . $report . '.php';
      if( ! file_exists( $report_file ) )
        WP_CLI::error( '✋ File `' . basename( $report_file ) . '` does not exist! This command expects to find `lib/fns/cli/' . basename( $report_file ) . '` with the necessary logic for generating the `' . $report . '` report.' );

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
     *   - get_default_organization
     *   - get_donation_contact
     *   - get_organizations
     *   - get_priority_organizations
     *   - get_screening_questions
     *   - get_trans_dept_ids
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
        case 'get_default_organization':
          $org = DonationManager\organizations\get_default_organization();
          WP_CLI::line( '🔔 get_default_organization() returns $org = ' . print_r( $org, true ) );
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