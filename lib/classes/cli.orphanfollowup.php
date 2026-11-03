<?php
use function DonationManager\orphanfollowup\{is_enabled,get_due_donations,get_partners,get_upgrade_url,send_followup,sweep};

// Only run if in the WP_CLI
if ( defined( 'WP_CLI' ) && WP_CLI ) {
  /**
   * The follow-up email to orphaned donors who declined fee-based pickup.
   */
  class DonationManagerOrphanFollowup {

    /**
     * Sends the follow-ups that are due now, or shows who would get one.
     *
     * Without options, runs the same sweep as the 15-minute cron (one batch).
     *
     * ## OPTIONS
     *
     * [--dry-run]
     * : List the donations due a follow-up and the partners each would be offered. Sends nothing, and works while the follow-up is off.
     *
     * [--donation=<id>]
     * : One donation, eligible or not. With --dry-run, prints its upgrade links.
     *
     * [--to=<email>]
     * : With --donation, send a test copy of its follow-up here. The donation isn't marked as sent.
     *
     * [--limit=<number>]
     * : With --dry-run, how many due donations to list. Defaults to 50.
     *
     * ## EXAMPLES
     *
     *     wp dm orphan-followup --dry-run
     *     wp dm orphan-followup --donation=1054592 --dry-run
     *     wp dm orphan-followup --donation=1054592 --to=you@example.com
     *     wp dm orphan-followup
     *
     * @param array $args       Positional arguments.
     * @param array $assoc_args Associative arguments.
     */
    public function __invoke( $args, $assoc_args ) {
      $dry_run = WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', false );
      $donation_id = isset( $assoc_args['donation'] ) ? (int) $assoc_args['donation'] : 0;

      if ( $donation_id ) {
        if ( 'donation' != get_post_type( $donation_id ) )
          WP_CLI::error( "#{$donation_id} isn't a donation." );

        $partners = get_partners( $donation_id );
        if ( ! $partners )
          WP_CLI::error( "#{$donation_id}'s zip has no priority partner, so it gets no follow-up." );

        if ( $dry_run ) {
          foreach ( $partners as $partner )
            WP_CLI::line( $partner['name'] . ': ' . get_upgrade_url( $donation_id, $partner ) );
          return;
        }

        if ( empty( $assoc_args['to'] ) || ! is_email( $assoc_args['to'] ) )
          WP_CLI::error( 'Pass --to=<email> to send a test copy, or --dry-run to see the links.' );
        $result = send_followup( $donation_id, $assoc_args['to'] );
        if ( 'sent' != $result )
          WP_CLI::error( "Not sent: {$result}." );
        WP_CLI::success( "Sent #{$donation_id}'s follow-up to {$assoc_args['to']}." );
        return;
      }

      if ( $dry_run ) {
        $limit = isset( $assoc_args['limit'] ) ? (int) $assoc_args['limit'] : 50;
        $items = [];
        foreach ( get_due_donations( $limit ) as $id ) {
          $items[] = [
            'ID'       => $id,
            'date'     => get_post_field( 'post_date', $id ),
            'zip'      => DonationManager\donations\get_donation_zip_code( $id ),
            'email'    => get_field( 'donor_email', $id ),
            'partners' => implode( ', ', wp_list_pluck( get_partners( $id ), 'name' ) ) ?: '(none: skipped)',
          ];
        }
        if ( ! $items ) {
          WP_CLI::success( 'No donations are due a follow-up.' );
          return;
        }
        WP_CLI\Utils\format_items( 'table', $items, [ 'ID', 'date', 'zip', 'email', 'partners' ] );
        WP_CLI::line( count( $items ) . ' due, ' . count( array_filter( wp_list_pluck( $items, 'partners' ), function( $p ){ return '(none: skipped)' != $p; } ) ) . ' would be emailed.' . ( is_enabled() ? '' : ' (The follow-up is off.)' ) );
        return;
      }

      if ( ! is_enabled() )
        WP_CLI::error( 'The follow-up is off. Turn it on under Donation Manager → General.' );
      $results = sweep();
      foreach ( $results as $id => $result )
        WP_CLI::line( "#{$id}: {$result}" );
      WP_CLI::success( count( array_keys( $results, 'sent' ) ) . ' sent, ' . ( count( $results ) - count( array_keys( $results, 'sent' ) ) ) . ' not.' );
    }
  }

  WP_CLI::add_command( 'dm orphan-followup', 'DonationManagerOrphanFollowup' );
}
