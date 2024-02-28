<?php

namespace DonationManager\enqueues;

function enqueue_scripts(){

  if( isset( $_SESSION['donor']['form'] ) ){
      switch( $_SESSION['donor']['form'] ) {
          case 'contact-details':
              wp_register_script( 'jquery-mask-plugin', DONMAN_PLUGIN_URL . 'lib/components/vendor/jquery-mask-plugin/dist/jquery.mask.min.js', ['jquery'] );
              wp_enqueue_script( 'contactdetails', DONMAN_PLUGIN_URL . 'lib/js/contactdetails.js', ['jquery','jquery-mask-plugin'] );
          break;

          case 'select-preferred-pickup-dates':
              wp_enqueue_style( 'gl-datepicker', DONMAN_PLUGIN_URL . 'lib/css/glDatePicker.pmd.css' );
              wp_enqueue_script( 'gl-datepicker', DONMAN_PLUGIN_URL . 'lib/js/glDatePicker.min.js', ['jquery'], filemtime( DONMAN_PLUGIN_PATH . '/lib/js/glDatePicker.min.js' ) );
              wp_enqueue_script( 'gl-datepicker-init', DONMAN_PLUGIN_URL . 'lib/js/gl-datepicker.js', ['gl-datepicker'], filemtime( DONMAN_PLUGIN_PATH . '/lib/js/gl-datepicker.js' ) );

              /**
               * Date Picker Initialization
               */

              // Default pickup days are Mon-Sat:
              $pickup_dow = array( 1, 2, 3, 4, 5, 6 );

              // Default scheduling interval is 24hrs which is 2 days for the purposes of our date picker
              $scheduling_interval = 2;

              if( isset( $_SESSION['donor']['org_id'] ) && is_numeric( $_SESSION['donor']['org_id'] ) ) {
                  $pickup_dow_array = get_field( 'pickup_settings_pickup_dates', $_SESSION['donor']['org_id'] );
                  $pickup_dow_array = array_unique( $pickup_dow_array );

                  /**
                   * BUGFIX: Pickup Days of the Week were stored as strings in PMD 2.0.
                   * In PMD 3.0, we store these values as numbers (0-6 for Sun-Sat).
                   * However, we still have some old string values from importing PMD 2.0
                   * orgs. The follow code accounts for this by discarding the values
                   * saved for the Org and using the default values above.
                   */
                  if( isset( $pickup_dow_array ) && is_array( $pickup_dow_array ) && isset( $pickup_dow_array[0] ) && ! is_numeric( $pickup_dow_array[0] ) )
                    unset( $pickup_dow_array );

                  if( isset( $pickup_dow_array[0] ) && is_array( $pickup_dow_array[0] ) && ( 0 == count( $pickup_dow_array[0] ) ) )
                      unset( $pickup_dow_array ); // No pickup days set for org, skip $pickup_dow_array processing b/c it is empty!

                  if( isset( $pickup_dow_array ) && is_array( $pickup_dow_array ) && 0 < count( $pickup_dow_array ) ){
                      $pickup_dow = array();
                      foreach( $pickup_dow_array as $day ){
                          $pickup_dow[] = intval( $day );
                      }
                  }

                  $scheduling_interval = get_field( 'pickup_settings_minimum_scheduling_interval', $_SESSION['donor']['org_id'] );
              }

              if( empty( $scheduling_interval ) || ! is_numeric( $scheduling_interval ) )
                  $scheduling_interval = 2;

              $date = new \DateTime();
              $date->add( new \DateInterval( 'P' . $scheduling_interval . 'D' ) );
              $minPickUp = explode(',', $date->format( 'Y,n,j' ) );
              $date->add( new \DateInterval( 'P90D' ) );
              $maxPickUp = explode( ',', $date->format( 'Y,n,j' ) );

              $data = array(
                  'minPickUp0' => $minPickUp[0],
                  'minPickUp1' => $minPickUp[1] - 1,
                  'minPickUp2' => $minPickUp[2],
                  'maxPickUp0' => $maxPickUp[0],
                  'maxPickUp1' => $maxPickUp[1] - 1,
                  'maxPickUp2' => $maxPickUp[2],
                  'pickup_dow' => $pickup_dow,
              );
              wp_localize_script( 'gl-datepicker-init', 'vars', $data );
          break;
      } // switch( $_SESSION['donor']['form'] )
  } // if( isset( $_SESSION['donor']['form'] ) )

  wp_enqueue_style( 'user-dashboard', DONMAN_PLUGIN_URL . 'lib/css/user-dashboard.css', null, filemtime( DONMAN_PLUGIN_PATH . 'lib/css/user-dashboard.css' ) );

  if(is_user_logged_in()) {
//     wp_enqueue_style( 'tom-select', DONMAN_PLUGIN_URL . 'lib/css/tom-select.css' );
//     wp_enqueue_script( 'tom-select.complete.min', DONMAN_PLUGIN_URL . 'lib/js/tom-select.complete.min.js');
//     wp_enqueue_script( 'pickupcodes', DONMAN_PLUGIN_URL . 'lib/js/pickupcodes.js');
     wp_enqueue_script( 'userportal', DONMAN_PLUGIN_URL . 'lib/js/userportal.js');

//     wp_localize_script('pickupcodes', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
//         wp_enqueue_script( 'jquery' );
  }

  if( ! wp_script_is( 'jquery', 'done' ) )
  wp_enqueue_script( 'jquery' );
 if(defined('DM_GOOGLE_MAPS_API_KEY')){
  	wp_register_script( 'googlemaps', 'https://maps.googleapis.com/maps/api/js?key=' . DM_GOOGLE_MAPS_API_KEY, null, '1.0', true ); // &callback=initMap
 }
  wp_register_script( 'donors-by-zipcode', DONMAN_PLUGIN_URL . 'lib/js/donors-by-zipcode.js', ['googlemaps'], filemtime( DONMAN_PLUGIN_PATH . 'lib/js/donors-by-zipcode.js' ), true );

  $zipCodeMapsUrl = 'https://zipcodes.pickupmydonation.com/zipcodes/' ;
  wp_localize_script( 'donors-by-zipcode', 'wpvars', [ 'zipCodeMapsUrl' => $zipCodeMapsUrl ]);

  $dmscripts = file_get_contents( DONMAN_PLUGIN_PATH . 'lib/js/scripts.js' );
  wp_add_inline_script( 'jquery', $dmscripts );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_scripts', 101 );
