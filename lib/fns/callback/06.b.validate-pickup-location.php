<?php
use function DonationManager\utilities\{get_alert,get_posted_var};
use function DonationManager\globals\{add_html};
use function DonationManager\emails\{notify_admin,send_email};
use function DonationManager\donations\{save_donation,tag_donation,save_donation_hash};
use function DonationManager\organizations\{is_orphaned_donation};

        /**
         * 06b. VALIDATE PICKUP LOCATION (Skipping Pickup Dates)
         */
        if( isset( $_POST['skip_pickup_dates'] ) && true == $_POST['skip_pickup_dates'] ){
            $form = new Form\Validator([
                'Pickup Location' => [ 'required', 'trim' ],
            ]);

            $form->setValues( array(
                'Pickup Location' => $_POST['donor']['pickuplocation'],
            ));

            if( $form->validate( $_POST ) ){
                $_SESSION['donor']['pickuplocation' ] = $_POST['donor']['pickuplocation' ];

                // Notify admin if missing ORG or TRANS DEPT
                if( empty( $_SESSION['donor']['org_id'] ) || empty( $_SESSION['donor']['trans_dept_id'] ) )
                    $this->notify_admin( 'missing_org_transdept' );

                // Save the donation to the database and send the confirmation and notification emails.
                /*
                if( $ID = $this->save_donation( $_SESSION['donor'] ) ){
                    $this->tag_donation( $ID, $_SESSION['donor'] );
                    $this->send_email( 'trans_dept_notification' );
                    $this->send_email( 'donor_confirmation' );
                    $_SESSION['donor']['form'] = 'thank-you';
                } else {
                    $_SESSION['donor']['form'] = 'duplicate-submission';
                }
                /**/
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
                header( 'Location: ' . $_REQUEST['nextpage'] );
                die();
            } else {
                $errors = $form->getErrors();
                $error_msg = array();
                foreach( $errors as $field => $array ){
                    if( true == $array['required'] )
                        $error_msg[] = '<strong><em>' . $field . '</em></strong> is a required field.';
                }
                if( 0 < count( $error_msg ) ){
                    $error_msg_html = '<div class="alert alert-danger"><p>Please correct the following errors:</p><ul><li>' .implode( '</li><li>', $error_msg ) . '</li></ul></div>';
                    add_html( $error_msg_html );
                }
            }
        }