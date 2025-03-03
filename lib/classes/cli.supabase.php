<?php
if ( ! class_exists( 'WP_CLI' ) ) {
    return;
}

/**
 * Manages Supabase synchronization.
 */
class DM_Supabase_Command {

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
        list( $table ) = $args;
        
        if ( ! in_array( $table, [ 'donations', 'organizations', 'trans_depts' ], true ) ) {
            WP_CLI::error( "Invalid table specified. Allowed tables: donations, organizations, trans_depts." );
        }

        WP_CLI::log( "Syncing data for table: {$table}" );

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
        // Add logic to sync organizations
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
