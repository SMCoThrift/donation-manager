<?php
if ( ! class_exists( 'WP_CLI' ) ) {
    return;
}

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
     * ## EXAMPLES
     *
     *     wp dm supabase sync donations
     *
     * @when after_wp_load
     */
    public function sync( $args, $assoc_args ) {
      if ( ! $this->supabase_url || ! $this->supabase_apikey ) {
        WP_CLI::error( "Supabase credentials are missing. Ensure SUPABASE_URL and SUPABASE_APIKEY are defined in your .env file." );
      }

      list( $table ) = $args;
      
      if ( ! in_array( $table, [ 'donations', 'organizations', 'trans_depts' ], true ) ) {
        WP_CLI::error( "Invalid table specified. Allowed tables: donations, organizations, trans_depts." );
      }

      WP_CLI::log( "🔔 Syncing data for table: {$table}" );


        switch ( $table ) {
            case 'donations':
                $this->sync_donations();
                break;
            case 'organizations':
                $this->sync_organizations();
                break;
            case 'trans_depts':
                $this->sync_trans_depts();
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
     */
    private function sync_organizations_composer_lib(){
      WP_CLI::log( "Syncing organizations..." );
      
      // Get all organizations from WP
      $args = [
          'post_type'      => 'organization',
          'posts_per_page' => -1,
          'post_status'    => 'publish',
      ];
      $organizations = get_posts( $args );  
      
      foreach ( $organizations as $organization ) {
        $data = [
          'title'           => $organization->post_title,
          'organization_id' => $organization->ID,
        ];

        // Continue here...
        WP_CLI::log( '👉 Checking `' . $data['title'] . '` (' . $data['organization_id'] . ').' );
        $response = $this->supabase->filterOrg( 'organizations', $data['organization_id'] );
        WP_CLI::log( '$response = ' . print_r( $response, true ) );
      }
    }


    /**
     * Sync organizations data.
     */
    private function sync_organizations() {
        WP_CLI::log( "Syncing organizations..." );
        
        // Get all organizations from WP
        $args = [
            'post_type'      => 'organization',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ];
        $organizations = get_posts( $args );

        foreach ( $organizations as $organization ) {
            // Skip all CHHJ except National Org
            if( stristr( $organization->post_title, 'College Hunks Hauling Junk' ) && 'College Hunks Hauling Junk' != $organization->post_title ){
              //WP_CLI::log( "⚠️ Skipping {$organization->post_title}." );
              continue;
            }

            $data = [
              'title'           => $organization->post_title,
              'organization_id' => $organization->ID,
            ];

            // Properly formatted query URL
            $query_url = add_query_arg([
                'organization_id' => 'eq.' . $organization->ID,
                'select'          => 'organization_id,title',
            ], $this->supabase_url . '/rest/v1/organizations' );

            // Check if organization exists in Supabase
            WP_CLI::log( "\nChecking `{$organization->post_title}` ({$organization->ID}). \n 🔗 Query URL: {$query_url}" );

            $response = wp_remote_get( $query_url, [
                'headers' => [
                    'apikey'        => $this->supabase_apikey,
                    'Authorization' => 'Bearer ' . $this->supabase_apikey,
                ],
            ]);
            //WP_CLI::log( ' - Response: ' . print_r( $response, true ) );

            $body = wp_remote_retrieve_body( $response );
            $existing_records = json_decode( $body, true );
            if( $existing_records ){
              WP_CLI::log( " ✅ #{$organization->ID} exists." );
              //WP_CLI::log( ' 👉 existing_records: ' . print_r( $existing_records[0], true ) );
            }

            if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
                WP_CLI::warning( "Supabase API error: " . print_r( $body, true ) );
                continue;
            }

            if ( ! empty( $existing_records ) ) {
              if( $existing_records[0] == $data ){
                WP_CLI::log( " ⏭️ Skipping {$data['title']}, nothing to update." );
                continue;
              }

              // Update existing record
              WP_CLI::log( " 🟨 Updating organization: {$organization->post_title}" );

              $query_url = add_query_arg([
                'organization_id' => 'eq.' . $organization->ID,
              ], $this->supabase_url . '/rest/v1/organizations' );

              wp_remote_request( $query_url, [
                  'method'  => 'PATCH',
                  'headers' => [
                      'apikey'        => $this->supabase_apikey,
                      'Authorization' => 'Bearer ' . $this->supabase_apikey,
                      'Content-Type'  => 'application/json',
                      'Prefer'        => 'return=minimal',
                  ],
                  'body'    => wp_json_encode( $data ),
              ]);
            } else {
              // Insert new record
              WP_CLI::log( " ✅ Inserting new organization: {$organization->post_title}" );
              //*
              wp_remote_post( $this->supabase_url  . '/rest/v1/organizations', [
                  'headers' => [
                      'apikey'        => $this->supabase_apikey,
                      'Authorization' => 'Bearer ' . $this->supabase_apikey,
                      'Content-Type'  => 'application/json',
                      'Prefer'        => 'return=minimal',
                  ],
                  'body'    => wp_json_encode( $data ),
              ]);
              /**/
            }
        }
    }

    /**
     * Sync transaction departments data.
     */
    private function sync_trans_depts() {
        WP_CLI::log( "Syncing transaction departments..." );
        // Add logic to sync transaction departments
    }
}

WP_CLI::add_command( 'dm supabase', 'DM_Supabase_Command' );