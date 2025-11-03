<?php
use function DonationManager\utilities\{get_alert};
use function DonationManager\globals\{add_html};

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
            wp_safe_redirect( trailingslashit( $_REQUEST['nextpage'] ) . '?pcode=' . $pickupcode );
            exit;
        }
    } else {
        $step = 'default';
        $msg = [];
        $errors = $form->getErrors();
        if( array_key_exists( 'regexp', $errors['pickupcode'] ) && true == $errors['pickupcode']['regexp'] )
            $msg[] = 'Zip or Donation Code can only be made up of numbers, letters, dashes, and underscores.';
        if( array_key_exists( 'required', $errors['pickupcode'] ) && true == $errors['pickupcode']['required'] )
            $msg[] = 'Zip or Donation Code can not be blank.';
        $alert = get_alert(['description' => '<p>Invalid pick up code! Please correct the following errors:</p><ul><li>' . implode( '</li><
            li>', $msg ) . '</li></ul>', 'type' => 'danger']);
        add_html( $alert );
    }
}