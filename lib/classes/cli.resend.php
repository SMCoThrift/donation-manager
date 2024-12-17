<?php
use function DonationManager\donations\get_donation_routing_method;

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
        $donation_data = $this->build_donation_array( $donation );
        update_post_meta( $donation->ID, 'api_response_code', 'pending' );
        $BackgroundResendProcess->push_to_queue( $donation_data );
        WP_CLI::log( "Queuing Donation #{$donation->ID} for a resend." );
      }
      $BackgroundResendProcess->save()->dispatch();

      WP_CLI::success( 'Finished queueing donations for RESEND.' );
    }


    /**
     * Builds the donation data array from a donation post.
     *
     * This function processes the donation post and retrieves all the necessary
     * fields to construct the array required by the external API.
     *
     * @param WP_Post $donation The donation post object.
     * @return array The formatted donation data array.
     */
    private function build_donation_array( $donation ) {
      $org = get_field( 'organization', $donation->ID );
      $trans_dept = get_field( 'trans_dept', $donation->ID );

      // Parse pickup times from the ACF repeater field
      $pickup_times = get_field( 'pickup_times', $donation->ID );
      $pickup_data = [];

      if ( is_array( $pickup_times ) ) {
        foreach ( $pickup_times as $index => $pickup ) {
          $pickup_data[ "pickupdate" . ($index + 1) ] = $pickup['pick_up_time'] ? explode( ' ', $pickup['pick_up_time'], 2 )[0] : '';
          $pickup_data[ "pickuptime" . ($index + 1) ] = $pickup['pick_up_time'] ? explode( ' ', $pickup['pick_up_time'], 2 )[1] : '';
        }
      }

      $donor_name = get_field( 'donor_name', $donation->ID );
      $donor_name_parts = explode( ' ', $donor_name );
      $donor_first_name = array_shift( $donor_name_parts );
      $donor_last_name = implode( ' ', $donor_name_parts );

      $different_pickup_address = get_field( 'pickup_address_street', $donation->ID ) ? 'Yes' : 'No';

      $pickup_address = $different_pickup_address === 'Yes' ? [
        'address' => get_field( 'pickup_address_street', $donation->ID ),
        'city'    => get_field( 'pickup_address_city', $donation->ID ),
        'state'   => get_field( 'pickup_address_state', $donation->ID ),
        'zip'     => get_field( 'pickup_address_zip', $donation->ID ),
      ] : [];

      $pickup_code_term = wp_get_post_terms( $donation->ID, 'pickup_code', [ 'fields' => 'names' ] );
      $pickup_code = $pickup_code_term ? $pickup_code_term[0] : '';

      return array_merge(
        [
          'ID'                      => $donation->ID,
          'routing_method'          => get_donation_routing_method( $org->ID ),
          'pickup_code'             => $pickup_code,
          'org_id'                  => $org ? $org->ID : '',
          'trans_dept_id'           => $trans_dept ? $trans_dept->ID : '',
          'priority'                => get_post_meta( $donation->ID, 'priority', true ),
          'items'                   => get_post_meta( $donation->ID, 'items', true ),
          'description'             => get_field( 'pickup_description', $donation->ID ),
          'address'                 => [
            'name'                  => [
              'first'               => $donor_first_name,
              'last'                => $donor_last_name,
            ],
            'company'               => get_field( 'address_company', $donation->ID ),
            'address'               => get_field( 'address_street', $donation->ID ),
            'city'                  => get_field( 'address_city', $donation->ID ),
            'state'                 => get_field( 'address_state', $donation->ID ),
            'zip'                   => get_field( 'address_zip', $donation->ID ),
          ],
          'different_pickup_address' => $different_pickup_address,
          'email'                   => get_field( 'donor_email', $donation->ID ),
          'phone'                   => get_field( 'donor_phone', $donation->ID ),
          'preferred_contact_method' => get_post_meta( $donation->ID, 'preferred_contact_method', true ),
          'preferred_code'          => get_post_meta( $donation->ID, 'preferred_code', true ),
          'reason'                  => get_post_meta( $donation->ID, 'reason', true ),
          'pickuplocation'          => get_post_meta( $donation->ID, 'pickuplocation', true ),
          'fee_based'               => get_post_meta( $donation->ID, 'fee_based', true ),
        ],
        $pickup_data,
        $different_pickup_address === 'Yes' ? [ 'pickup_address' => $pickup_address ] : []
      );
    }
  }

  WP_CLI::add_command( 'dm resend', 'DonationManagerResend' );
} else {
  if ( ! defined( 'WP_CLI' ) ) {
    define( 'WP_CLI', false );
  }
}
