<?php

namespace DonationManager\callbacks;
use function DonationManager\utilities\{get_alert};
use function DonationManager\globals\{add_html};

/**
 * Hooks to `init`. Handles form submissions.
 *
 * The validation process typically sets $_SESSION['donor']['form'].
 * That variable controls which form/message is displayed by
 * callback_shortcode().
 *
 * @since 1.0.0
 *
 * @return void
 */
function handle_form_submissions(){
  /**
   *  01. INITIAL ZIP/PICKUP CODE VALIDATION
   */
  if( isset( $_REQUEST['pickupcode'] ) || isset( $_REQUEST['pcode'] ) ) {
      $form = new \Form\Validator([
          'pickupcode' => ['regexp' => '/^[a-zA-Z0-9_-]+\z/', 'required']
      ]);

      $pickupcode = ( isset( $_REQUEST['pickupcode'] ) )? $_REQUEST['pickupcode'] : $_REQUEST['pcode'] ;

      $form->setValues( [ 'pickupcode' => $pickupcode ] );

      if( $form->validate( $_REQUEST ) ) {
          $_SESSION['donor']['pickup_code'] = $pickupcode;
          $_SESSION['donor']['form'] = 'select-your-organization';
          if( isset( $_REQUEST['pickupcode'] ) ){
              $_SESSION['donor']['rewrite_titles'] = false;
              session_write_close();
              header( 'Location: ' . $_REQUEST['nextpage'] . '?pcode=' . $pickupcode );
              die();
          }
      } else {
          $step = 'default';
          $msg = [];
          $errors = $form->getErrors();
          if( true == $errors['pickupcode']['regexp'] )
              $msg[] = 'Zip or Donation Code can only be made up of numbers, letters, dashes, and underscores.';
          if( true == $errors['pickupcode']['required'] )
              $msg[] = 'Zip or Donation Code can not be blank.';
          $alert = get_alert(['description' => '<p>Invalid pick up code! Please correct the following errors:</p><ul><li>' . implode( '</li><
              li>', $msg ) . '</li></ul>', 'type' => 'danger']);
          add_html( $alert );
      }
  }

  /**
   *  02. DESCRIBE YOUR DONATION
   *
   *  The following is triggered by clicking on a DONATE NOW!
   *  link in the "Select Your Organization" list. It stores
   *  our chosen Organization and its associated transportation
   *  department. With those two vars set, we're able to display
   *  an Organization specific "Describe Your Donation" form.
   *
   *  We validate the `oid` and `tid` to make sure they are
   *  numeric, and we check to make sure a post exists for each
   *  of those IDs. If either of these values are not numeric or
   *  no post exists, we redirect back to the home page.
   *
   *  Non-numeric values for either `oid` or `tid` result in the
   *  donor making it all the way through the donation process
   *  w/o having an `org_id` and `trans_dept_id` set.
   */
  if ( isset( $_REQUEST['oid'] ) && isset( $_REQUEST['tid'] ) && ! isset( $_POST['donor'] ) ) {
      $is_numeric_validator = function( $number ){
          return is_numeric( $number );
      };

      $org_or_trans_dept_exists = function( $id ){
          return ( FALSE === get_post_status( $id ) )? false : true ;
      };

      $form = new \Form\Validator([
          'org_id' => ['is_numeric' => $is_numeric_validator, 'exists' => $org_or_trans_dept_exists],
          'trans_dept_id' => ['is_numeric' => $is_numeric_validator, 'exists' => $org_or_trans_dept_exists],
      ]);

      if( $form->validate( array( 'org_id' => $_REQUEST['oid'], 'trans_dept_id' => $_REQUEST['tid'] ) ) ){
          $_SESSION['donor']['form'] = 'describe-your-donation';
          $_SESSION['donor']['org_id'] = $_REQUEST['oid'];
          $_SESSION['donor']['trans_dept_id'] = $_REQUEST['tid'];
          $_SESSION['donor']['priority'] = ( isset( $_REQUEST['priority'] ) && 1 == $_REQUEST['priority'] ) ? 1 : 0 ;
          // Set Orphaned Pick Up Provider ID
          if( isset( $_REQUEST['orphanid'] ) && is_numeric( $_REQUEST['orphanid'] ) ){
              $_SESSION['donor']['orphan_provider_id'] = $_REQUEST['orphanid'];
          }
      } else {
          // Invalid org_id or trans_dept_id, redirect to site home page
          $this->notify_admin( 'invalid_link' );
          header( 'Location: ' . site_url() );
          die();
      }
  }

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
      $_SESSION['donor']['description'] = $_POST['donor']['description'];

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
        header( 'Location: ' . $_POST['nextpage'] );
        die();
      } else {
        $this->add_html( '<div class="alert alert-error">No $_POST[nextpage] defined.</div>' );
      }
    } else {
      $errors = $form->getErrors();
      if( true == $errors['options']['checked'] )
          $msg[] = 'Please select at least one donation item.';
      if( true == $errors['description']['required'] )
          $msg[] = 'Please enter a description of your item(s).';
      $html = '<div class="alert alert-danger"><p>There was a problem with your submission. Please correct the following errors:</p><ul><li>' . implode( '</li><li>', $msg ) . '</li></ul></div>';
      add_html( $html );
    }

  }
}
add_action( 'init', __NAMESPACE__ . '\\handle_form_submissions', 99 );