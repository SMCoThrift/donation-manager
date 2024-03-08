 <?php
use function DonationManager\emails\{notify_admin};
use function DonationManager\globals\{add_html};
use function DonationManager\utilities\{get_alert};

/**
 * 05. VALIDATE CONTACT DETAILS
 */
if( isset( $_POST['donor']['address'] ) ) {
    $match_pickupcode_and_zipcode = function( $confirmation, $form ){
      if( DMDEBUG_VERBOSE )
        uber_log('🔔 $confirmation = ' . $confirmation . "\n" . '🔔 $form->ZIP = ' . $form->ZIP );

      // Only check specific zip codes.
      $zipcodes_to_check = [ 37116 ];
      if( in_array( $confirmation, $zipcodes_to_check ) )
        return $form->ZIP == $confirmation;

      return true;
    };

  $validations = [
    'First Name' => [ 'required', 'trim', 'max_length' => 40 ],
    'Last Name' => [ 'required', 'trim', 'max_length' => 40 ],
    'Address' => [ 'required', 'trim', 'max_length' => 255 ],
    'City' => [ 'required', 'trim', 'max_length' => 80 ],
    'State' => [ 'required', 'trim', 'max_length' => 80 ],
    'ZIP' => [ 'required', 'trim', 'max_length' => 14 ],
    'Contact Email' => [ 'required', 'email', 'trim', 'max_length' => 255 ],
    'Contact Phone' => [ 'required', 'trim', 'max_length' => 30 ],
    'Preferred Donor Code' => [ 'max_length' => 30, 'regexp' => "/^([\w-_]+)$/" ],
    'Reason for Donating' => [ 'max_length' => 140, 'trim' ],
  ];

  // Original zip code must match pickup code
  $validations['session_pickupcode'] = [ 'zipcodes_must_match' => $match_pickupcode_and_zipcode ];

  $form = new \Form\Validator( $validations );

  $pickup_zipcode = ( 'Yes' ==  $_POST['donor']['different_pickup_address'] )? $_POST['donor']['pickup_address']['zip'] : $_POST['donor']['address']['zip'] ;
  $preferred_code = ( isset( $_POST['donor']['preferred_code'] ) )? $_POST['donor']['preferred_code'] : '' ;
  $form->setValues( array(
    'First Name' => $_POST['donor']['address']['name']['first'],
    'Last Name' => $_POST['donor']['address']['name']['last'],
    'Address' => $_POST['donor']['address']['address'],
    'City' => $_POST['donor']['address']['city'],
    'State' => $_POST['donor']['address']['state'],
    'ZIP' => $_POST['donor']['address']['zip'],
    'Contact Email' => $_POST['donor']['email'],
    'Contact Phone' => $_POST['donor']['phone'],
    'Preferred Donor Code' => $preferred_code,
    'Reason for Donating' => $_POST['donor']['reason'],
    'session_pickupcode' => $_SESSION['donor']['pickup_code'],
  ));

  $form->validate([ 'session_pickupcode' => $_SESSION['donor']['pickup_code'] ]);

  if( 'Yes' ==  $_POST['donor']['different_pickup_address'] ){
    $form->addRules([
      'Pickup Address' => [ 'required', 'trim', 'max_length' => 255 ],
      'Pickup City' => [ 'required', 'trim', 'max_length' => 80 ],
      'Pickup State' => [ 'required', 'trim', 'max_length' => 80 ],
      'Pickup ZIP' => [ 'required', 'trim', 'max_length' => 14 ],
    ]);
    $form->addValues( array(
      'Pickup Address' => $_POST['donor']['pickup_address']['address'],
      'Pickup City' => $_POST['donor']['pickup_address']['city'],
      'Pickup State' => $_POST['donor']['pickup_address']['state'],
      'Pickup ZIP' => $_POST['donor']['pickup_address']['zip'],
    ));
  }

  if( $form->validate( $_POST ) ){
    // Store contact details in $_SESSION[donor]
    $_SESSION['donor']['address'] = $_POST['donor']['address'];
    $_SESSION['donor']['different_pickup_address'] = $_POST['donor']['different_pickup_address'];

    if( 'Yes' == $_SESSION['donor']['different_pickup_address'] )
      $_SESSION['donor']['pickup_address'] = $_POST['donor']['pickup_address'];

    $_SESSION['donor']['email'] = $_POST['donor']['email'];
    $_SESSION['donor']['phone'] = $_POST['donor']['phone'];
    $_SESSION['donor']['preferred_contact_method'] = 'Phone'; // 02/15/2024 (15:39) - Hardcoding Preferred Contact Method
    $_SESSION['donor']['preferred_code'] = $preferred_code;
    $_SESSION['donor']['reason'] = $_POST['donor']['reason'];

    /**
     * SET $_SESSION['donor']['pickup_code'] FOR DONORS WHO BYPASSED EARLIER SCREENS
     *
     * Whenever our clients link directly to their donation options form,
     * the donor will reach this point without having
     * $_SESSION['donor']['pickup_code'] set. So, we set it here according
     * to the donor's address/pickup_address:
     */
    if( ! isset( $_SESSION['donor']['pickup_code'] ) )
      $_SESSION['donor']['pickup_code'] = ( 'Yes' == $_POST['donor']['different_pickup_address'] )? $_POST['donor']['pickup_address']['zip'] : $_POST['donor']['address']['zip'] ;

    // Redirect to next step
    $skip_pickup_dates_array = get_field( 'pickup_settings_skip_pickup_dates', $_SESSION['donor']['org_id'] );
    if( DMDEBUG_VERBOSE )
      uber_log('🔔 $skip_pickup_dates_array = ' . print_r( $skip_pickup_dates_array, true ) );

    $skip_pickup_dates = ( is_array( $skip_pickup_dates_array ) && array_key_exists( 0, $skip_pickup_dates_array ) )? $skip_pickup_dates_array[0] : false ;
    if( DMDEBUG_VERBOSE )
      uber_log( '🔔 $skip_pickup_dates = ' . $skip_pickup_dates );
    $_SESSION['donor']['form'] = ( 'yes' == $skip_pickup_dates )? 'location-of-items' : 'select-preferred-pickup-dates';

    //$_SESSION['donor']['form'] = 'select-preferred-pickup-dates';
    session_write_close();
    header( 'Location: ' . $_REQUEST['nextpage'] );
    die();
  } else {
    $errors = $form->getErrors();
    $error_msg = array();
    foreach( $errors as $field => $array ){
      if( isset( $array['required'] ) && true == $array['required'] )
        $error_msg[] = '<strong><em>' . $field . '</em></strong> is a required field.';
      if( isset( $array['max_length'] ) )
        $error_msg[] = '<strong><em>' . $field . '</em></strong> can not exceed <em>' . $array['max_length'] . '</em> characters.';
      if( isset( $array['email'] ) && true == $array['email'] )
        $error_msg[] = '<strong><em>' . $field . '</em></strong> must be a valid email address.';

      // Preferred Donor Code:
      if( 'Preferred Donor Code' == $field )
        $error_msg[] = '<strong><em>' . $field . '</em></strong> must contain only letters, numbers, dashes, and underscores.';

      $pickup_zipcode = ( 'Yes' ==  $_POST['donor']['different_pickup_address'] )? $_POST['donor']['pickup_address']['zip'] : $_POST['donor']['address']['zip'] ;
      if( 'session_pickupcode' == $field ){
        $error_msg[] = '<strong>Zip Code Mismatch:</strong><br />Your original Zip Code (<code>' . $_SESSION['donor']['pickup_code'] . '</code>) and your Pick Up Zip Code <code>' . $pickup_zipcode . '</code> do not match. To fix, you may:<br/><br/>1) Update your pickup address below with an address in the <code>' . $_SESSION['donor']['pickup_code'] . '</code> zip code, OR<br/><br/>2) <a href="'. home_url('select-your-organization/?pcode=' . $pickup_zipcode ) .'">Start over using <code>' . $pickup_zipcode . '</code></a> to start the donation process.';
        notify_admin('zipcode_mismatch');
      }
    }
    if( 0 < count( $error_msg ) ){
      $error_msg_html = get_alert([
        'type'        => 'danger',
        'description' => '<p>Please correct the following errors:</p><ul><li>' .implode( '</li><li>', $error_msg ) . '</li></ul>',
      ]);
      add_html( $error_msg_html );
    }
  }
}