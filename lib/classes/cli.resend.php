<?php
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
     * ## EXAMPLES
     *
     *     wp dm resend
     *     wp dm resend --age=10
     *
     * @param array $args       Positional arguments.
     * @param array $assoc_args Associative arguments.
     */
    public function __invoke( $args, $assoc_args ) {
      $age = isset( $assoc_args['age'] ) ? (int) $assoc_args['age'] : 5;
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
        'date_query'     => [
          [
            'after'     => "{$age} minutes ago",
            'inclusive' => true,
          ],
        ],
        'posts_per_page' => -1,
      ];

      $donations = get_posts( $query_args );

      if ( empty( $donations ) ) {
        WP_CLI::success( 'No donations found to resend.' );
        return;
      }

      foreach ( $donations as $donation ) {
        $donation_data = $this->build_donation_array( $donation );
        \DonationManager\apirouting\send_api_post( $donation_data );
        WP_CLI::log( "Resent donation ID {$donation->ID}." );
      }

      WP_CLI::success( 'Finished resending donations.' );
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

      return array_merge(
        [
          'pickup_code'             => get_post_meta( $donation->ID, 'pickup_code', true ),
          'org_id'                  => $org ? $org->ID : '',
          'trans_dept_id'           => $trans_dept ? $trans_dept->ID : '',
          'priority'                => get_post_meta( $donation->ID, 'priority', true ),
          'items'                   => get_post_meta( $donation->ID, 'items', true ),
          'description'             => get_field( 'pickup_description', $donation->ID ),
          'address'                 => [
            'name'                  => [
              'first'               => get_field( 'donor_name_first', $donation->ID ),
              'last'                => get_field( 'donor_name_last', $donation->ID ),
            ],
            'company'               => get_field( 'address_company', $donation->ID ),
            'address'               => get_field( 'address_street', $donation->ID ),
            'city'                  => get_field( 'address_city', $donation->ID ),
            'state'                 => get_field( 'address_state', $donation->ID ),
            'zip'                   => get_field( 'address_zip', $donation->ID ),
          ],
          'different_pickup_address' => get_post_meta( $donation->ID, 'different_pickup_address', true ),
          'email'                   => get_field( 'donor_email', $donation->ID ),
          'phone'                   => get_field( 'donor_phone', $donation->ID ),
          'preferred_contact_method' => get_post_meta( $donation->ID, 'preferred_contact_method', true ),
          'preferred_code'          => get_post_meta( $donation->ID, 'preferred_code', true ),
          'reason'                  => get_post_meta( $donation->ID, 'reason', true ),
          'pickuplocation'          => get_post_meta( $donation->ID, 'pickuplocation', true ),
          'fee_based'               => get_post_meta( $donation->ID, 'fee_based', true ),
          'ID'                      => $donation->ID,
        ],
        $pickup_data
      );
    }
  }

  WP_CLI::add_command( 'dm resend', 'DonationManagerResend' );
} else {
  if ( ! defined( 'WP_CLI' ) ) {
    define( 'WP_CLI', false );
  }
}
