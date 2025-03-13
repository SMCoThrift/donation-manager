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
    private function sync_organizations() {
        WP_CLI::log( "Syncing organizations..." );
        
        // Get all organizations from WP
        $organizations = $this->get_wp_posts( 'organization' );

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
    private function sync_trans_depts() {
        WP_CLI::log( "Syncing transaction departments..." );
        // Add logic to sync transaction departments
    }

    private function get_wp_posts( $post_type ) {
      return get_posts([
        'post_type'      => $post_type,
        'posts_per_page' => -1,
        'post_status'    => 'publish',
      ]);
    }

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

      return json_decode( wp_remote_retrieve_body( $response ), true );
    }

    private function upsert_supabase_record( $table, $key, $value, $data ) {
        $existing_record = $this->get_existing_supabase_record( $table, $key, $value );

        if ( ! empty( $existing_record ) ) {
            WP_CLI::log( " 🟨 Updating record in {$table} for {$key}: {$value}" );
            $query_url = add_query_arg([ $key => 'eq.' . $value ], $this->supabase_url . "/rest/v1/{$table}" );

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
            WP_CLI::log( " ✅ Inserting new record into {$table} for {$key}: {$value}" );
            wp_remote_post( $this->supabase_url  . "/rest/v1/{$table}", [
                'headers' => [
                    'apikey'        => $this->supabase_apikey,
                    'Authorization' => 'Bearer ' . $this->supabase_apikey,
                    'Content-Type'  => 'application/json',
                    'Prefer'        => 'return=minimal',
                ],
                'body'    => wp_json_encode( $data ),
            ]);
        }
    }

}

WP_CLI::add_command( 'dm supabase', 'DM_Supabase_Command' );