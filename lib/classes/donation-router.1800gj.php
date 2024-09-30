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

    public function submit_donation( $donation ){
      if( ! defined( 'GOTJUNK_CLIENT_TOKEN' ) )
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
        $pickup_dates = array(
            '- ' . $donation['pickupdate1'] . ', ' . $donation['pickuptime1'],
            '- ' . $donation['pickupdate2'] . ', ' . $donation['pickuptime2'],
            '- ' . $donation['pickupdate3'] . ', ' . $donation['pickuptime3']
        );

        // $special_instructions = pick updates and $donation['pickup_address']
        $special_instructions = $type_of_items . "\n\n# PREFERRED PICK UP DATES\n" . implode( "\n", $pickup_dates ) . $pickup_address;

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
          'customer_type' => 'Residential',
          'language'  => 'English',
          'company'   => 'null',
          'notes'     => $special_instructions,
          'source'    => 'PUMD - Pickup My Donation',
      ),
    );
        $this->save_api_post( $donation['ID'], $args );

        // Don't send if we're debugging:
        /*
        if( DONMAN_DEV_ENV ){
            uber_log('INFO: `DONMAN_DEV_ENV` is ON. 1800GJ pickup request not sent.');
            return;
        }
        /**/


        $response = wp_remote_post( 'https://o2ebrands.workflows.oktapreview.com/api/flo/225e48540ee79c7d2463d88dfdf3ff94/invoke?clientToken=' . GOTJUNK_CLIENT_TOKEN, $args );
        //uber_log('🔔 1-800-GOT-JUNK API Response: ' . print_r( $response, true ) );
        $this->save_api_response( $donation['ID'], $response );
    }
}
?>