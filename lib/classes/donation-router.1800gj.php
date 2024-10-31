<?php
class GotJunkDonationRouter extends DonationRouter{
    private static $instance = null;

    public static function get_instance() {
        if( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    private function __construct() {

    }

    /**
     * Submit a donation to the 1-800-GOT-JUNK API.
     *
     * @param array $donation {
     *     Array of donation details.
     *
     *     @type string $ID                Donation ID.
     *     @type string $phone             Phone number of the donor.
     *     @type string $email             Email address of the donor.
     *     @type string $description       Description of items for pickup.
     *     @type string $pickuplocation    Location of items for pickup.
     *     @type array  $items             List of items for donation.
     *     @type bool   $different_pickup_address If 'Yes', indicates pickup address differs.
     *     @type array  $pickup_address    {
     *         Pickup address if different.
     *
     *         @type string $address       Street address.
     *         @type string $city          City name.
     *         @type string $state         State code.
     *         @type string $zip           ZIP code.
     *     }
     *     @type array  $address {
     *         Customer's address.
     *
     *         @type array $name {
     *             Customer name details.
     *
     *             @type string $first     First name.
     *             @type string $last      Last name.
     *         }
     *         @type string $address       Street address.
     *         @type string $city          City name.
     *         @type string $state         State code.
     *         @type string $zip           ZIP code.
     *         @type string $company       Company name, if applicable.
     *     }
     *     @type array  $screening_questions {
     *         Screening questions and answers.
     *
     *         @type string $question      Question text.
     *         @type string $answer        Answer text.
     *     }
     *     @type string $pickupdate1       Preferred pickup date 1.
     *     @type string $pickuptime1       Preferred pickup time 1.
     *     @type string $pickupdate2       Preferred pickup date 2.
     *     @type string $pickuptime2       Preferred pickup time 2.
     *     @type string $pickupdate3       Preferred pickup date 3.
     *     @type string $pickuptime3       Preferred pickup time 3.
     * }
     * @return WP_Error|void WP_Error object on failure, void on success.
     */
    public function submit_donation( $donation ){
      if( ! defined( 'GOTJUNK_API_EP' ) || ! GOTJUNK_API_EP )
        return new \WP_Error( 'no_gotjunk_api_ep', 'No 1-800-GOT-JUNK API End Point. Please define a valid `GOTJUNK_API_EP` in your .env.' );
      if( ! defined( 'GOTJUNK_CLIENT_TOKEN' ) || ! GOTJUNK_CLIENT_TOKEN )
        return new \WP_Error( 'no_gotjunk_client_token', 'No 1-800-GOT-JUNK CLIENT TOKEN. Please define a valid `GOTJUNK_CLIENT_TOKEN` in your .env.' );

      $questions = array();
      if( array_key_exists( 'screening_questions', $donation ) && is_array( $donation['screening_questions'] ) ){
          foreach( $donation['screening_questions'] as $screening_question ){
              $questions[] = '- ' . $screening_question['question'] . ' ' . $screening_question['answer'];
          }
      }
      $screening_questions = '';
      if( 0 < count( $questions ) )
          $screening_questions = "\n\n# SCREENING QUESTIONS\n" . implode( "\n", $questions );
      $type_of_items = "# TYPE OF ITEMS\n" . implode( ', ',  $donation['items'] ) . "\n\n# DESCRIPTION OF ITEMS\n" . $donation['description'] . "\n\n# LOCATION OF ITEMS\n" . $donation['pickuplocation'] . $screening_questions;

      $pickup_address = '';
      if( 'Yes' == $donation['different_pickup_address'] ){
          $pickup_address = "\n\n# PICK UP ADDRESS IS DIFFERENT FROM CUSTOMER ADDRESS:\n" . $donation['pickup_address']['address'] . "\n" . $donation['pickup_address']['city'] . ", " . $donation['pickup_address']['state'] . " " . $donation['pickup_address']['zip'] . "\n";
      }
      if( array_key_exists( 'pickupdate1', $donation ) ){
        $pickup_dates = array(
            '- ' . $donation['pickupdate1'] . ', ' . $donation['pickuptime1'],
            '- ' . $donation['pickupdate2'] . ', ' . $donation['pickuptime2'],
            '- ' . $donation['pickupdate3'] . ', ' . $donation['pickuptime3']
        );
      } else {
        $pickup_dates = array(
          '---',
          '---',
          '---',
        );
      }

      // $special_instructions = pick updates and $donation['pickup_address']
      $special_instructions = $type_of_items . "\n\n# PREFERRED PICK UP DATES\n" . implode( "\n", $pickup_dates ) . $pickup_address;
      $sanitized_instructions = json_encode( $special_instructions );

      // Remove leading and trailing quotes that json_encode adds to strings
      $sanitized_instructions = trim($sanitized_instructions, '"');

      $args = array(
        'body' => array(
          'first_name' => $donation['address']['name']['first'],
          'last_name' => $donation['address']['name']['last'],
          'phone' => $donation['phone'],
          'email' => $donation['email'],
          'street' => $donation['address']['address'],
          'city' => $donation['address']['city'],
          'state' => $donation['address']['state'],
          'country' => 'United States',
          'zip' => $donation['address']['zip'],
          'language'  => 'English',
          'notes'     => $sanitized_instructions,
          'source'    => 'PickUpMyDonation.com (PUMD)',
        ),
        'timeout' => 15,
      );

      if( array_key_exists( 'company', $donation['address'] ) && ! empty( $donation['address']['company'] ) ){
        $args['body']['company'] = $donation['address']['company'];
        $args['body']['customer_type'] = 'Commercial';
      } else {
        $args['body']['customer_type'] = 'Residential';
      }

      $this->save_api_post( $donation['ID'], $args );

      $remote_post_url = GOTJUNK_API_EP . '?clientToken=' . GOTJUNK_CLIENT_TOKEN;
      $response = wp_remote_post( $remote_post_url, $args );
      //*
      if( DONMAN_DEV_ENV ){
        uber_log( '🔔 1-800-GOT-JUNK POST:' . "\n\n👉 ENDPOINT:\n{$remote_post_url}\n\n👉 REQUEST: " . print_r( $args, true ) . "\n\n👉 REQUEST JSON: " . json_encode( $args ) . "\n\n👉 RESPONSE: " . print_r( $response, true ) );
      }
      /**/
      if( is_array( $response ) && array_key_exists( 'response', $response ) && 200 != $response['response']['code'] ){
        $response_body = json_decode( $response['body'], true );
        if( is_array( $response_body ) && array_key_exists( 'message', $response_body ) )
          $response['response']['message'] = $response_body['message'];
        //uber_log('🚨 API Response is an error! $response[body] = ' . $response['body'] . "\n\n\$response_body = " . print_r( $response_body, true ) . "\n\n\$response[response] = " . print_r( $response['response'], true ) );
      }
      $this->save_api_response( $donation['ID'], $response );
      $this->save_api_method( $donation['ID'], '1800gj_api' );
    }
}
?>