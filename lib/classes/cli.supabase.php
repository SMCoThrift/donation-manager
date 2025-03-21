<?php
if ( ! class_exists( 'WP_CLI' ) ) {
    return;
}

use function DonationManager\transdepts\{get_trans_dept_contact};

/**
 * Manages Supabase synchronization.
 */
class DM_Supabase_Command {

    private $supabase_url;
    private $supabase_apikey;

    public function __construct() {
      $this->supabase_url = defined('SUPABASE_URL') && SUPABASE_URL ? SUPABASE_URL : false;
      $this->supabase_apikey = defined('SUPABASE_APIKEY') && SUPABASE_APIKEY ? SUPABASE_APIKEY : false;
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
     * ## EXAMPLES
     *
     *     wp dm supabase sync donations
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


        switch ( $table ) {
            case 'donations':
                $this->sync_donations();
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
     */
    private function sync_donations() {
        WP_CLI::log( "Syncing donations..." );
        // Add logic to sync donations
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
          if( stristr( $organization->post_title, 'College Hunks Hauling Junk' ) && 'College Hunks Hauling Junk' != $organization->post_title ){
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
     *
     * @param string $post_type The post type to retrieve.
     * @param array  $post_ids  Optional. An array of post IDs to retrieve. Default is an empty array.
     *
     * @return WP_Post[] An array of WP_Post objects matching the query.
     */
    private function get_wp_posts( $post_type, $post_ids = [] ) {
      $args = [
        'post_type'      => $post_type,
        'posts_per_page' => -1,
        'post_status'    => 'publish',
      ];
      
      if( is_array( $post_ids ) && 0 < count( $post_ids ) )
        $args['post__in'] = $post_ids;      

      return get_posts( $args );
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
      $query_url = add_query_arg([
          $key     => 'eq.' . $value,
          'select' => $key . ',title',
      ], $this->supabase_url . "/rest/v1/{$table}" );

      $response = wp_remote_get( $query_url, [
          'headers' => [
              'apikey'        => $this->supabase_apikey,
              'Authorization' => 'Bearer ' . $this->supabase_apikey,
          ],
      ]);
      $body = json_decode( wp_remote_retrieve_body( $response ), true );

      $record = false;
      if( is_array( $body ) && array_key_exists( 0, $body ) ){
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
     * @return void
     */
    private function upsert_supabase_record( $table, $key, $value, $data ) {
        $existing_record = $this->get_existing_supabase_record( $table, $key, $value );
        if( $existing_record == $data ){
          WP_CLI::log( " 🔵 No changes detected. Skipping update for {$table} record {$key}: {$value}.");
          return;
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
            WP_CLI::log( " 🟨 Updating record in {$table} for {$key}: {$value}" );
            $query_url = add_query_arg([ $key => 'eq.' . intval( $value ) ], $this->supabase_url . "/rest/v1/{$table}" );

            $request_args['method'] = 'PATCH';
            $response = wp_remote_request( $query_url, $request_args );
        } else {
            WP_CLI::log( " ✅ Inserting new record into {$table} for {$key}: {$value}" );
            $query_url = $this->supabase_url  . "/rest/v1/{$table}";

            $response = wp_remote_post( $query_url, $request_args );
        }

        if( is_wp_error( $response ) ){
          WP_CLI::log( "❌ Failed to update record in {$table}: " . $response->get_error_message() );
          return;          
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        if ( $status_code < 200 || $status_code >= 300 ) {
            WP_CLI::log( "❌ Unexpected response code ({$status_code}) while updating {$table} record {$key}: {$value}" );
            WP_CLI::log( "🔎 Response: " . print_r( wp_remote_retrieve_body( $response ), true ) );
        }        
    }

}

WP_CLI::add_command( 'dm supabase', 'DM_Supabase_Command' );