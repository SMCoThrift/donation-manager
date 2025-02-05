<?php
class DonationRouter {
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
   * Saves the API method for a given donation.
   *
   * Updates the post meta for the specified donation with the provided API method.
   *
   * @param int    $donation_id ID of the donation post to update.
   * @param string $method      API method to be saved in the post meta.
   */
  public function save_api_method( $donation_id, $method ){
    update_post_meta( $donation_id, 'api_method', $method );
  }

  /**
   * Saving the data we are posting to an API.
   *
   * @param      int    $donation_id  The donation ID
   * @param      array  $args         The arguments we are posting to the API.
   */
  public function save_api_post( $donation_id, $args ){
    update_post_meta( $donation_id, 'api_post', $args );
  }

  /**
   * Saves an API response. Serializes response before saving.
   *
   * @param      string  $donation_id  The donation identifier
   * @param      mixed  $response     The response
   */
  public function save_api_response( $donation_id, $response ){
    $message = '';
    if( is_wp_error( $response ) ){
      $message = $response->get_error_message();
    } else if( is_array( $response ) || is_object( $response ) ){
      $message = serialize( $response );
    } else {
      $message = $response;
    }

    if( ! is_null( $donation_id ) ){
      update_post_meta( $donation_id, 'api_response', $message );
      $this->save_api_post_timestamp( $donation_id );

      /**
       * Handle cURL Timeouts
       *
       * When we have a cURL timeout, the message will be a string
       * containing "cURL error 28". So, we'll set the `api_reponse_code`
       * to "408" which represents a Gateway Timeout.
       */
      if( stristr( $message, 'cURL error 28') ){
        update_post_meta( $donation_id, 'api_response_code', 408 );
        update_post_meta( $donation_id, 'api_response_message', $message );
      }

      if( is_array( $response ) && array_key_exists( 'response', $response ) && is_array( $response['response'] ) ){
        if( array_key_exists( 'message', $response['response'] ) )
          update_post_meta( $donation_id, 'api_response_message', $response['response']['message'] );

        if( array_key_exists( 'code', $response['response'] ) )
          update_post_meta( $donation_id, 'api_response_code', $response['response']['code'] );
      }
    } else {
      wp_mail( 'webmaster@pickupmydonation.com', 'API Post Error', 'We received the following error when attempting to post Donation #' . $donation_id . ' by API:' . "\n\n" . $message );
    }
  }

  /**
   * Saves a new API post timestamp for a donation.
   *
   * Stores multiple timestamps for the given donation ID without overwriting existing ones.
   *
   * @param int $donation_id The ID of the donation post.
   */
  public function save_api_post_timestamp( $donation_id ){
    $timestamp = current_time( 'mysql' );
    add_post_meta( $donation_id, 'api_post_timestamp', $timestamp, false );    
  }
}
?>