<?php

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
        DonationManager\emails\notify_admin( 'invalid_link' );
        header( 'Location: ' . home_url() );
    }
}