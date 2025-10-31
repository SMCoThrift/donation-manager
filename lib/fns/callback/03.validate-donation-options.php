<?php
use function DonationManager\utilities\{get_alert};

/**
 *  03. VALIDATE DONATION OPTIONS/ITEMS
 */
if( isset( $_POST['donor']['options'] ) ) {

  // At least one donation option needs to be checked:
  $one_donation_option_is_checked = function( $options, $form ) {
    $checked = false;
    foreach( $options as $option ){
      if( array_key_exists( 'field_value', $option ) ){
        $checked = true;
        break;
      }
    }
    return $checked;
  };

  $form = new \Form\Validator([
      'options' => ['checked' => $one_donation_option_is_checked],
      'description' => ['required', 'trim']
  ]);
  $form->setValues( array( 'description' => $_POST['donor']['description'], 'options' => $_POST['donor']['options'] ) );

  if( $form->validate( $_POST ) ) {
    /**
     * ARE WE PICKING UP THIS DONATION?
     *
     * By default, we set the form to `no-pickup-message`. Then
     * we check each donation option to see if we are picking
     * up this item (i.e. true == $option['pickup'] ). If we
     * pickup any of the items, then we set the form to
     * `screening-questions`.
     */
    $_SESSION['donor']['form'] = 'no-pickup-message';

    // Should we skip the screening questions?
    $skip = false;
    $pickup = false;
    $_SESSION['donor']['items'] = [];
    foreach( $_POST['donor']['options'] as $option ) {
      if( ! empty( $option['field_value'] ) ) {
        if( true == $option['skipquestions'] && false == $skip )
          $skip = true;

        if( true == $option['pickup'] && false == $pickup ) {
          $pickup = true;
          $_SESSION['donor']['form'] = 'screening-questions';
        }

        // Store this donation option in our donor array
        $term_id = $option['term_id'];
        if( ! in_array( $option['field_value'], $_SESSION['donor']['items'] ) )
          $_SESSION['donor']['items'][$term_id] = $option['field_value'];
      }
    }
    $_SESSION['donor']['description'] = strip_tags( $_POST['donor']['description'] );
    //if( DONMAN_DEV_ENV )
      //uber_log( '🔔 Donation Descripton = ' . $_SESSION['donor']['description'] );

    /**
     * For Priority Pick Ups, we need to skip the screening questions
     * and also we bypass the "no pick up" message for any items
     * which are marked "No Pickup".
     */
    if( isset( $_SESSION['donor']['priority'] ) && 1 == $_SESSION['donor']['priority'] ){
      $skip = true; // Bypasses screening questions
      $_SESSION['donor']['form'] = 'screening-questions'; // Bypasses no pick up message
    }

    /**
     * When we skip questions, we actually request the "screening questions" page.
     * Then, via a hook to `template_redirect`, we pull the `nextpage` variable
     * from the shortcode on that page, and we do another redirect using the
     * value of that variable.
     */
    if( true == $skip )
      $_SESSION['donor']['skipquestions'] = true;

    if( isset( $_POST['nextpage'] ) && ! empty( $_POST['nextpage'] ) ){
      session_write_close();
      $location = trailingslashit( $_POST['nextpage'] );
      uber_log('🔔 Redirecting to ' . $location );
      wp_safe_redirect( $location );
      exit;
    } else {
      DonationManager\globals\add_html( '<div class="alert alert-error">No $_POST[nextpage] defined.</div>' );
    }
  } else {
    $errors = $form->getErrors();
    if( is_array( $errors ) && array_key_exists( 'options', $errors ) && true == $errors['options']['checked'] )
        $msg[] = 'Please select at least one donation item.';
    if( is_array( $errors ) && array_key_exists( 'description', $errors ) && true == $errors['description']['required'] )
        $msg[] = 'Please enter a description of your item(s).';
    DonationManager\globals\add_html( get_alert([
      'type'        => 'danger',
      'description' => '<p>There was a problem with your submission. Please correct the following errors:</p><ul><li>' . implode( '</li><li>', $msg ) . '</li></ul>',
    ]) );
  }

}