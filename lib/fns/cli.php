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
    }
  }
  WP_CLI::add_command( 'dm', 'DonationManagerCLI' );

} else {
  define( 'WP_CLI', false );
  define( 'WP_CLI_TEST', false );
}