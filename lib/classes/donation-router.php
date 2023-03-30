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
    } else {
      wp_mail( 'webmaster@pickupmydonation.com', 'API Post Error', 'We received the following error when attempting to post Donation #' . $donation_id . ' by API:' . "\n\n" . $message );
    }
  }
}
?>