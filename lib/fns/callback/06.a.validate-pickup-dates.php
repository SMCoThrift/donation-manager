<?php
use function DonationManager\utilities\{get_alert,get_posted_var};
use function DonationManager\globals\{add_html};
use function DonationManager\emails\{notify_admin,send_email};
use function DonationManager\donations\{save_donation,tag_donation,save_donation_hash};
use function DonationManager\organizations\{is_orphaned_donation};

/**
 * 06. VALIDATE PICKUP DATES
 */
if( isset( $_POST['donor']['pickupdate1'] ) ){
  $dates_must_be_unique = function( $value, $form ){
    $dates = array( $_POST['donor']['pickupdate1'], $_POST['donor']['pickupdate2'], $_POST['donor']['pickupdate3'] );
    $date_values = array_count_values( $dates );
    // if this date is found only once in the array, return 1. If > 1, return false.
    if( 1 == $date_values[$value] ){
      return true;
    } else {
      return false;
    }
  };

  $regexp_date = '/(([0-9]{2})\/([0-9]{2})\/([0-9]{4}))/';
  $form = new Form\Validator([
    'Preferred Pickup Date 1' => [ 'required', 'trim', 'max_length' => 10, 'regexp' => $regexp_date, 'unique' => $dates_must_be_unique ],
    'Date 1 Time' => [ 'required', 'trim' ],
    'Preferred Pickup Date 2' => [ 'required', 'trim', 'max_length' => 10, 'regexp' => $regexp_date, 'unique' => $dates_must_be_unique ],
    'Date 2 Time' => [ 'required', 'trim' ],
    'Preferred Pickup Date 3' => [ 'required', 'trim', 'max_length' => 10, 'regexp' => $regexp_date, 'unique' => $dates_must_be_unique ],
    'Date 3 Time' => [ 'required', 'trim' ],
    'Pickup Location' => [ 'required', 'trim' ],
  ]);

  $form->setValues( array(
    'Preferred Pickup Date 1' => get_posted_var( 'donor:pickupdate1' ),
    'Date 1 Time' => get_posted_var( 'donor:pickuptime1' ),
    'Preferred Pickup Date 2' => get_posted_var( 'donor:pickupdate2' ),
    'Date 2 Time' => get_posted_var( 'donor:pickuptime2' ),
    'Preferred Pickup Date 3' => get_posted_var( 'donor:pickupdate3' ),
    'Date 3 Time' => get_posted_var( 'donor:pickuptime3' ),
    'Pickup Location' => get_posted_var( 'donor:pickuplocation' ),
  ));

  if( $form->validate( $_POST ) ){
    for( $x = 1; $x < 4; $x++ ){
      $_SESSION['donor']['pickupdate' . $x ] = $_POST['donor']['pickupdate' . $x ];
      $_SESSION['donor']['pickuptime' . $x ] = $_POST['donor']['pickuptime' . $x ];
    }
    $_SESSION['donor']['pickuplocation'] = $_POST['donor']['pickuplocation'];
    $_SESSION['donor']['fee_based'] = ( array_key_exists( 'fee_based', $_POST['donor'] ) )? $_POST['donor']['fee_based'] : null ;

    // Notify admin if missing ORG or TRANS DEPT
    if( empty( $_SESSION['donor']['org_id'] ) || empty( $_SESSION['donor']['trans_dept_id'] ) )
      notify_admin( 'missing_org_transdept' );

    // Save the donation to the database and send the confirmation and notification emails.
    if( $ID = save_donation( $_SESSION['donor'] ) ){
      tag_donation( $ID, $_SESSION['donor'] );
      if( is_orphaned_donation( $_SESSION['donor']['trans_dept_id'] ) )
        $_SESSION['donor']['donation_hash'] = save_donation_hash( $ID );

      send_email( 'trans_dept_notification' );
      send_email( 'donor_confirmation' );
      $_SESSION['donor']['form'] = 'thank-you';
    } else {
      $_SESSION['donor']['form'] = 'duplicate-submission';
    }

    // Redirect to next step
    session_write_close();
    wp_safe_redirect( trailingslashit( $_REQUEST['nextpage'] ) );
    exit;
  } else {
    $errors = $form->getErrors();
    $error_msg = array();
    foreach( $errors as $field => $array ){
      if( array_key_exists( 'required', $array ) && true == $array['required'] )
          $error_msg[] = '<strong><em>' . $field . '</em></strong> is a required field.';
      if( isset( $array['max_length'] ) )
          $error_msg[] = '<strong><em>' . $field . '</em></strong> can not exceed <em>' . $array['max_length'] . '</em> characters.';
      if( array_key_exists( 'regexp', $array ) && true == $array['regexp'] )
          $error_msg[] = '<strong><em>' . $field . '</em></strong> must be a date in the format MM/DD/YYYY.';
      if( array_key_exists( 'unique', $array ) && true == $array['unique'] )
          $error_msg[] = '<strong><em>' . $field . '</em></strong> matches another date. Please select three <em>unique</em> dates.';
    }
    if( 0 < count( $error_msg ) ){
      $error_msg_html = get_alert([
        'description' => '<p>Please correct the following errors:</p><ul><li>' .implode( '</li><li>', $error_msg ) . '</li></ul>',
        'type'  => 'danger',
      ]);

      //$error_msg_html = '<div class="alert alert-danger"><p>Please correct the following errors:</p><ul><li>' .implode( '</li><li>', $error_msg ) . '</li></ul></div>';
      add_html( $error_msg_html );
    }
  }
}