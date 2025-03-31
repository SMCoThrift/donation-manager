<?php
if ( ! class_exists( 'WP_CLI' ) ) {
  return;
}

use function DonationManager\organizations\{get_priority_organizations};
use function DonationManager\transdepts\{get_trans_dept_contact};

/**
 * Manages Supabase synchronization.
 */
class DM_Supabase_Command {

  private $supabase_url;
  private $supabase_apikey;
  private $sync_log;

  public function __construct() {
    $this->supabase_url = defined( 'SUPABASE_URL' ) && SUPABASE_URL ? SUPABASE_URL : false;
    $this->supabase_apikey = defined( 'SUPABASE_APIKEY' ) && SUPABASE_APIKEY ? SUPABASE_APIKEY : false;
  }

  /**
   * Synchronize data between WordPress and Supabase.
   *
   * ## OPTIONS
   *
   * <table>
   * : The table to sync. Options: donations, organizations, trans_depts.
   *
   * [<post_ids>...]
   * : Optional list of WordPress Post IDs to sync. If provided, only these posts will be synchronized.
   *
   * [--limit=<limit>]
   * : Optional. Specify a limit for the number of donations to sync. Defaults to 100 when syncing donations.
   *
   * [--start_date=<start_date>]
   * : Optional. Start date in 'YYYY-MM-DD' format.
   *
   * [--end_date=<end_date>]
   * : Optional. End date in 'YYYY-MM-DD' format.
   *
   * ## EXAMPLES
   *
   *     wp dm supabase sync donations --limit=200
   *     wp dm supabase sync donations --start_date=2024-11-01 --end_date=2024-11-02
   *     wp dm supabase sync organizations 123 456 789
   *
   * @when after_wp_load
   *
   * @param array $args       Positional arguments, including the table name.
   * @param array $assoc_args Associative arguments passed to the command.
   */
  public function sync( $args, $assoc_args ) {
    if ( ! $this->supabase_url || ! $this->supabase_apikey ) {
      WP_CLI::error( "Supabase credentials are missing. Ensure SUPABASE_URL and SUPABASE_APIKEY are defined in your .env file." );
    }

    $table = array_shift( $args );
    $post_ids = ! empty( $args ) ? array_map( 'intval', $args ) : [];

    if ( ! in_array( $table, [ 'donations', 'organizations', 'trans_depts' ], true ) ) {
      WP_CLI::error( "Invalid table specified. Allowed tables: donations, organizations, trans_depts." );
    }

    WP_CLI::log( "🔔 Syncing data for table: {$table}" );

    // Validate and set the 'limit' parameter.
    $limit = isset( $assoc_args['limit'] ) ? intval( $assoc_args['limit'] ) : null;
    if ( isset( $assoc_args['limit'] ) && $limit <= 0 ) {
      WP_CLI::error( __( 'The limit parameter must be a positive integer.', 'donation-manager' ) );
    }
    // For donations, use a default limit of 100 if not specified.
    if ( 'donations' === $table && null === $limit ) {
      $limit = 100;
    }

    // Validate and set the 'start_date' and 'end_date' parameters:
    $start_date = isset( $assoc_args['start_date'] )? $assoc_args['start_date'] : null ;
    if( isset( $assoc_args['start_date'] && ! $this->is_valid_date( $start_date ) )
      WP_CLI::error( __( 'The start_date parameter must be in YYYY-MM-DD format.', 'donation-manager' ) );

    $end_date = isset( $assoc_args['end_date'] )? $assoc_args['end_date'] : null ;
    if( isset( $assoc_args['end_date'] && ! $this->is_valid_date( $end_date ) )
      WP_CLI::error( __( 'The end_date parameter must be in YYYY-MM-DD format.', 'donation-manager' ) );    

    switch ( $table ) {
      case 'donations':
        $this->sync_donations( $limit, $start_date, $end_date );
        break;
      case 'organizations':
        $this->sync_organizations( $post_ids );
        break;
      case 'trans_depts':
        $this->sync_trans_depts( $post_ids );
        break;
    }

    WP_CLI::success( "Synchronization for {$table} completed successfully." );
  }

  /**
   * Sync donations data.
   *
   * @param int $limit Maximum number of donation posts to sync.
   */
  private function sync_donations( $limit, $start_date = null, $end_date = null ) {
    WP_CLI::log( "Syncing donations with a limit of {$limit}..." );
    
    // Retrieve donations with the specified limit.
    $donations = $this->get_donations( $limit, $start_date, $end_date );
    $total_donations = count( $donations );

    if( 0 == $total_donations ){
      WP_CLI::error( 'No donations found for your specified arguments.' );
      return;
    } else {
      WP_CLI::log( "👉 We found {$total_donations} donations to sync." );
      $progress = \WP_CLI\Utils\make_progress_bar( 'Syncing donations...', $total_donations );
    }

    foreach ( $donations as $donation ) {
      $organization = get_field( 'organization', $donation->ID );
      $trans_dept = get_field( 'trans_dept', $donation->ID );
      $fee_based = get_field( 'fee_based', $donation->ID );

      $terms = get_the_terms( $donation->ID, 'pickup_code' );
      $pickup_codes_list = '';
      if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
        $pickup_codes = wp_list_pluck( $terms, 'name' );
        $pickup_codes_list = implode( ', ', $pickup_codes );
      }

      $priority_org_id = null;
      if( is_numeric( $pickup_codes_list ) ){
        $priority_orgs = get_priority_organizations( $pickup_codes_list );
        if( is_array( $priority_orgs ) && 0 < count( $priority_orgs ) ){
          $priority_org = $priority_orgs[0];
          $priority_org_id = $priority_org['id'];
        }
      }

      $data = [
        'donation_id'               => $donation->ID,
        'trans_dept_id'             => $trans_dept->ID,
        'organization_id'           => $organization->ID,
        'title'                     => $donation->post_title,
        'description'               => $donation->post_content,
        'fee_based'                 => $fee_based,
        'pickup_code'               => $pickup_codes_list,
        'priority_organization_id'  => $priority_org_id,
        'post_date'                 => $donation->post_date,
      ];

      $success = $this->upsert_supabase_record( 'donations', 'donation_id', $donation->ID, $data );

      // Record successful sync to supabase:
      if ( $success ) {
        update_post_meta( $donation->ID, 'supabase_sync', true );
      }
      $progress->tick();
    }

    $progress->finish();
    if( ! empty( $this->sync_log ) && is_array( $this->sync_log ) && 0 < count( $this->sync_log ) ){
      WP_CLI::log( "\n\nWe recorded the following logs during the sync:" );
      foreach( $this->sync_log as $log_entry ){
        WP_CLI::log( $log_entry );
      }
    }
  }

  /**
   * Sync organizations data.
   *
   * @param array $post_ids Optional array of post IDs to limit the sync.
   */
  private function sync_organizations( $post_ids = [] ) {
    WP_CLI::log( "Syncing organizations..." );
    
    // Get organizations from WP
    $organizations = $this->get_wp_posts( 'organization', $post_ids );

    foreach ( $organizations as $organization ) {
      // Skip all CHHJ except National Org
      if ( stristr( $organization->post_title, 'College Hunks Hauling Junk' ) && 'College Hunks Hauling Junk' !== $organization->post_title ) {
        continue;
      }

      $data = [
        'title'           => $organization->post_title,
        'organization_id' => $organization->ID,
      ];

      $this->upsert_supabase_record( 'organizations', 'organization_id', $organization->ID, $data );
    }
  }

  /**
   * Sync transaction departments data.
   *
   * @param array $post_ids Optional array of post IDs to limit the sync.
   */
  private function sync_trans_depts( $post_ids = [] ) {
    WP_CLI::log( "Syncing transaction departments..." );

    $trans_depts = $this->get_wp_posts( 'trans_dept', $post_ids );

    foreach ( $trans_depts as $trans_dept ) {
      $organization_id = get_field( 'organization', $trans_dept->ID );
      $contact = get_trans_dept_contact( $trans_dept->ID );

      $data = [
        'title'           => $trans_dept->post_title,
        'trans_dept_id'   => $trans_dept->ID,
        'organization_id' => $organization_id,
        'contact_title'   => $contact['contact_title'],
        'contact_name'    => $contact['contact_name'],
        'contact_email'   => $contact['contact_email'],
        'cc_emails'       => $contact['cc_emails'],
        'phone'           => $contact['phone'],
      ];

      $this->upsert_supabase_record( 'trans_depts', 'trans_dept_id', $trans_dept->ID, $data );
    }
  }

  /**
   * Retrieves WordPress posts of a specified post type, optionally filtered by post IDs.
   * Supports limiting the number of posts returned and ordering by post date in descending 
   * order by default.
   *
   * @param string $post_type The post type to retrieve.
   * @param array  $post_ids  Optional. An array of post IDs to retrieve. Default is an empty array.
   * @param int    $limit     Optional. Maximum number of posts to retrieve. Default is -1 (no limit).
   *
   * @return WP_Post[] An array of WP_Post objects matching the query.
   */
  private function get_wp_posts( $post_type, $post_ids = [], $limit = -1 ) {
    $args = [
      'post_type'      => $post_type,
      'posts_per_page' => $limit,
      'post_status'    => 'publish',
      'orderby'        => 'date',
      'order'          => 'DESC',
    ];

    if ( is_array( $post_ids ) && count( $post_ids ) > 0 ) {
      $args['post__in'] = $post_ids;
    }

    return get_posts( $args );
  }

  /**
   * Retrieves Donation posts that have not been synced with Supabase.
   * Skips posts where 'supabase_sync' meta is explicitly set to 'true'.
   * Efficiently handles large datasets by querying in batches.
   * Allows filtering by an optional date range.
   *
   * @param int    $limit      Optional. Maximum number of donation posts to retrieve. Default is -1 (no limit).
   * @param string $start_date Optional. Start date in 'YYYY-MM-DD' format. Default is empty.
   * @param string $end_date   Optional. End date in 'YYYY-MM-DD' format. Default is empty.
   *
   * @return WP_Post[] An array of WP_Post objects matching the criteria.
   */
  private function get_donations( $limit = -1, $start_date = '', $end_date = '' ) {
    $batch_size = 50;
    $paged      = 1;
    $collected  = [];

    // Validate date format if provided
    if ( ! empty( $start_date ) && ! $this->is_valid_date( $start_date ) ) {
      WP_CLI::error( "Invalid start_date format. Expected format: YYYY-MM-DD." );
    }

    if ( ! empty( $end_date ) && ! $this->is_valid_date( $end_date ) ) {
      WP_CLI::error( "Invalid end_date format. Expected format: YYYY-MM-DD." );
    }

    while ( $limit === -1 || count( $collected ) < $limit ) {
      $args = [
        'post_type'      => 'donation',
        'posts_per_page' => $batch_size,
        'paged'          => $paged,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'fields'         => 'all',
      ];

      // Add date query if start_date or end_date is provided
      if ( ! empty( $start_date ) || ! empty( $end_date ) ) {
        $date_query = [];
        
        if ( ! empty( $start_date ) ) {
          $date_query['after'] = $start_date;
        }
        
        if ( ! empty( $end_date ) ) {
          $date_query['before'] = $end_date;
        }
        
        $date_query['inclusive'] = true;
        $args['date_query']      = [ $date_query ];
      }

      $query = new WP_Query( $args );

      if ( ! $query->have_posts() ) {
        break;
      }

      foreach ( $query->posts as $post ) {
        $sync_flag = get_post_meta( $post->ID, 'supabase_sync', true );
        if ( filter_var( $sync_flag, FILTER_VALIDATE_BOOLEAN ) ) {
          continue;
        }

        $collected[] = $post;

        if ( $limit > 0 && count( $collected ) >= $limit ) {
          break 2; // Exit both loops
        }
      }

      $paged++;
    }

    return $collected;
  }

  /**
   * Validates date format as YYYY-MM-DD.
   *
   * @param string $date The date string to validate.
   * @return bool True if valid, false otherwise.
   */
  private function is_valid_date( $date ) {
    $d = DateTime::createFromFormat( 'Y-m-d', $date );
    return $d && $d->format( 'Y-m-d' ) === $date;
  }

  /**
   * Logs a message to the sync log.
   *
   * Adds the provided message to the `$sync_log` array if the message is not null.
   *
   * @param string|null $message The message to log. If null, no action is taken.
   */
  private function log( $message = null ){
    if( ! is_null( $message ) )
      $this->sync_log[] = $message ;
  }


  /**
   * Retrieves an existing record from a Supabase table based on a given key-value pair.
   *
   * @param string $table The name of the Supabase table to query.
   * @param string $key   The column name to search for the specified value.
   * @param mixed  $value The value to match in the given column.
   *
   * @return array|false The retrieved record as an associative array if found, or false if no record is found.
   */
  private function get_existing_supabase_record( $table, $key, $value ) {
    $query_url = add_query_arg( [
      $key     => 'eq.' . $value,
      'select' => $key . ',title',
    ], $this->supabase_url . "/rest/v1/{$table}" );

    $response = wp_remote_get( $query_url, [
      'headers' => [
        'apikey'        => $this->supabase_apikey,
        'Authorization' => 'Bearer ' . $this->supabase_apikey,
      ],
    ] );
    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    $record = false;
    if ( is_array( $body ) && array_key_exists( 0, $body ) ) {
      $record = $body[0];
    } 

    return $record;
  }

  /**
   * Inserts or updates a record in a Supabase table based on a given key-value pair.
   *
   * @param string $table The name of the Supabase table to modify.
   * @param string $key   The column name used for identifying the record.
   * @param mixed  $value The value to match in the given column.
   * @param array  $data  The data to insert or update.
   *
   * @return bool TRUE upon successful upsert.
   */
  private function upsert_supabase_record( $table, $key, $value, $data ) {
    $status = true;

    $existing_record = $this->get_existing_supabase_record( $table, $key, $value );
    if ( $existing_record == $data ) {
      WP_CLI::log( " 🔵 No changes detected. Skipping update for {$table} record {$key}: {$value}." );
      return false;
    } 

    $request_args = [
      'headers' => [
        'apikey'        => $this->supabase_apikey,
        'Authorization' => 'Bearer ' . $this->supabase_apikey,
        'Content-Type'  => 'application/json',
        'Prefer'        => 'return=minimal',
      ],
      'body'  => wp_json_encode( $data ),
    ];

    if ( ! empty( $existing_record ) ) {
      $this->log( " 🟨 Updating record in {$table} for {$key}: {$value}" );
      $query_url = add_query_arg( [ $key => 'eq.' . intval( $value ) ], $this->supabase_url . "/rest/v1/{$table}" );
      $request_args['method'] = 'PATCH';
      $response = wp_remote_request( $query_url, $request_args );
    } else {
      //$this->log( " ✅ Inserting new record into {$table} for {$key}: {$value}" );
      $query_url = $this->supabase_url . "/rest/v1/{$table}";
      $response = wp_remote_post( $query_url, $request_args );
    }

    if ( is_wp_error( $response ) ) {
      $this->log( "❌ Failed to update record in {$table}: " . $response->get_error_message() );
      $status = false;
    }

    $status_code = wp_remote_retrieve_response_code( $response );
    if ( $status_code < 200 || $status_code >= 300 ) {
      $this->log( "❌ Unexpected response code ({$status_code}) while updating {$table} record {$key}: {$value}" );
      $this->log( "🔎 Response: " . print_r( wp_remote_retrieve_body( $response ), true ) );
      $status = false;
    }

    return $status;
  }

}

WP_CLI::add_command( 'dm supabase', 'DM_Supabase_Command' );