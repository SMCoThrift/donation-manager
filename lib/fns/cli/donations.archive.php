<?php
    $query_args = [
      'post_type'       => 'donation',
      'post_status'     => 'publish',
      'posts_per_page'  => -1,
      'fields'          => 'ids',
    ];

    if( preg_match('/[0-9]{4}/', $args[0] ) ){
      $year = $args[0];
      $query_args['year'] = $year;
    } else {
      WP_CLI::error( 'Invalid year, must be in the format YYYY.', true );
    }

    if( isset( $assoc_args['month'] ) && preg_match( '/[0-9]{1,2}/', $assoc_args['month'] ) ){
      $month = ltrim( $assoc_args['month'], '0' );
      if( 12 < $month )
        WP_CLI::error('Month must be a numeral, 1-12.');

      $archive_limit = ( defined( 'DM_ARCHIVE_LIMIT' ) && is_int( DM_ARCHIVE_LIMIT ) )? DM_ARCHIVE_LIMIT : 3;
      $archive_time = strtotime( $year . '-' . $month );
      $archive_time_limit = current_time( 'timestamp' ) - ( MONTH_IN_SECONDS * $archive_limit );
      if( $archive_time > $archive_time_limit )
        WP_CLI::error( "The archive date you provided was less than {$archive_limit} months in the past. Please try again using an archive date greater than {$archive_limit} months in the past.\n\n👉 NOTE: You may define this limit by setting DM_ARCHIVE_LIMIT via the environment variables.", true );

      $query_args['monthnum'] = $month;
    } else if( isset( $assoc_args['month'] ) && ! preg_match( '/[0-9]{1,2}/', $assoc_args['month'] ) ){
      WP_CLI::warning( 'Month is not in format `MM`, disregarding...' );
    }

    if( ! isset( $month ) )
      WP_CLI::error('Please set a month.');

    // DRY RUN
    if( isset( $assoc_args['dry-run'] ) ){
      $dry_run = ( $assoc_args['dry-run'] === 'false' )? false : true ;
    } else {
      $dry_run = true;
    }

    WP_CLI::line('You are archiving donations from:');
    if( $year )
      WP_CLI::line('• Year: ' . $year );
    if( isset( $month ) )
      WP_CLI::line('• Month: ' . $month );


    $donations = get_posts( $query_args );
    $no_of_archived_donations = count( $donations );
    WP_CLI::line('• ' . $no_of_archived_donations . ' donations will be archived.');
    if( 0 === $no_of_archived_donations )
      WP_CLI::error('There are no donations to archive from your specifed time frame.');

    if( $donations ){
      $BackgroundDeleteDonationProcess = $GLOBALS['BackgroundDeleteDonationProcess'];

      $progress = WP_CLI\Utils\make_progress_bar( 'Archiving donations...', $no_of_archived_donations );
      foreach( $donations as $donation ){
        if( ! $dry_run ){
          //WP_CLI::line('• Archiving donation #' . $donation );
          //wp_delete_post( $donation, true );
          $BackgroundDeleteDonationProcess->push_to_queue( $donation );
        }
        $progress->tick();
      }
      if( ! $dry_run )
        $BackgroundDeleteDonationProcess->save()->dispatch();
      $progress->finish();
    }

    $donation_stats = [ 'year' => $year, 'month' => $month, 'donations' => $no_of_archived_donations ];
    if( ! $dry_run )
      add_row( 'donation_stats', $donation_stats, 'option' );

    if( $dry_run ){
      WP_CLI::warning('This was a `dry run`. To actually archive the donations, run with flag `--dry-run=false`.');
      WP_CLI::line('If this had not been a `dry run`, the following stat would have been added to `donation_stats`: ' . "\n\n" . print_r( $donation_stats, true ) );
    }
