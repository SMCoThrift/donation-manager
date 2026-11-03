<?php
use function DonationManager\donations\get_donation_data;

// Only run if in the WP_CLI
if ( defined( 'WP_CLI' ) && WP_CLI ) {
  /**
   * Resend donations which failed to post to an external API.
   */
  class DonationManagerResend {

    /**
     * Executes the WP CLI command to resend failed donations.
     *
     * ## OPTIONS
     *
     * [--age=<minutes>]
     * : The age in minutes of the donations to resend. Defaults to 5.
     *
     * [--ids=<post_ids>]
     * : Comma-separated list of post IDs to resend. Overrides the age parameter if provided.
     *
     * ## EXAMPLES
     *
     *     wp dm resend
     *     wp dm resend --age=10
     *     wp dm resend --ids=123,456,789
     *
     * @param array $args       Positional arguments.
     * @param array $assoc_args Associative arguments.
     */
    public function __invoke( $args, $assoc_args ) {
      $query_args = [
        'post_type'      => 'donation',
        'post_status'    => 'publish',
        'meta_query'     => [
          [
            'key'     => 'api_response_code',
            'value'   => '408',
            'compare' => '=',
          ],
        ],
        'posts_per_page' => -1,
      ];

      if ( isset( $assoc_args['ids'] ) ) {
        $post_ids = array_map( 'intval', explode( ',', $assoc_args['ids'] ) );
        $query_args['post__in'] = $post_ids;
      } else {
        $age = isset( $assoc_args['age'] ) ? (int) $assoc_args['age'] : 5;
        $query_args['date_query'] = [
          [
            'after'     => "{$age} minutes ago",
            'inclusive' => true,
          ],
        ];
      }

      $donations = get_posts( $query_args );

      if ( empty( $donations ) ) {
        WP_CLI::success( 'No donations found to resend.' );
        return;
      }

      $BackgroundResendProcess = $GLOBALS['BackgroundResendProcess'];
      foreach ( $donations as $donation ) {
        $donation_data = get_donation_data( $donation );
        update_post_meta( $donation->ID, 'api_response_code', 'pending' );
        $BackgroundResendProcess->push_to_queue( $donation_data );
        WP_CLI::log( "Queuing Donation #{$donation->ID} for a resend." );
      }
      $BackgroundResendProcess->save()->dispatch();

      WP_CLI::success( 'Finished queueing donations for RESEND.' );
    }
  }

  WP_CLI::add_command( 'dm resend', 'DonationManagerResend' );
} else {
  if ( ! defined( 'WP_CLI' ) ) {
    define( 'WP_CLI', false );
  }
}
