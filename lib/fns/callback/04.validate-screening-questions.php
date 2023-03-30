<?php
use function DonationManager\globals\{add_html};
use function DonationManager\utilities\{get_alert};

/**
 *  04. VALIDATE SCREENING QUESTIONS
 */
if( isset( $_POST['donor']['questions'] ) ) {
    $each_question_answered = function( $value, $form ){
        $answered = true;
        foreach( $value as $key => $id ){
            if( is_array( $_POST['donor']['answers'] ) && ! array_key_exists( $id, $_POST['donor']['answers'] ) )
                $answered = false;
        }
        return $answered;
    };
    $form = new \Form\Validator([
        'answers' => [ 'required', 'each' => [ 'in' => array( 'Yes', 'No' ) ] ],
        'answered' => [ 'ids' => $each_question_answered ]
    ]);
    $form->setValues( array( 'answers' => $_POST['donor']['answers'], 'answered' => $_POST['donor']['question']['ids'] ) );

    // Does the organization allow additional details?
    $pickup_settings = get_field( 'pickup_settings', $_SESSION['donor']['org_id'] );
    if( is_array( $pickup_settings ) && array_key_exists( 'provide_additional_details', $pickup_settings ) )
      $provide_additional_details = $pickup_settings['provide_additional_details'];

    //$provide_additional_details = get_post_meta( $_SESSION['donor']['org_id'], 'provide_additional_details', true );
    if( $provide_additional_details ){
        foreach( $_POST['donor']['answers'] as $answer ){
            if( 'yes' == strtolower( $answer ) ){
                $form->addRules(['additional_details' => ['required'] ]);
                $form->setValue( 'additional_details', $_POST['donor']['additional_details'] );
                break;
            }
        }
    }

    // Photo Upload settings
    $user_photo_uploads = [
      'on'        => $pickup_settings['allow_user_photo_uploads'],
      'required'  => $pickup_settings['user_photo_uploads_required'],
    ];

    // The following doesn't validate on my local machine. Is this due to a CORS issue?
    if( isset( $_POST['user_photo_id'] ) && is_array( $_POST['user_photo_id'] ) )
        uber_log( '🔔 user_photo_id = ' . print_r( $_POST['user_photo_id'], true ) );
    if( isset( $_POST['image_public_id'] ) )
        uber_log( 'image_public_id = ' . $_POST['image_public_id'] );

    if( $user_photo_uploads['on'] && $user_photo_uploads['required'] ){
        $form->addRules([
            'user_photo_id' => ['required']
        ]);

        if( isset( $_POST['user_photo_id'] ) && ! empty( $_POST['user_photo_id'] ) ){
            $y = 0;
            $public_image_ids = ( stristr( $_POST['image_public_id'], ',' ) )? explode( ',', $_POST['image_public_id'] ) : [ $_POST['image_public_id'] ] ;
            foreach( $_POST['user_photo_id'] as $user_photo_id ){
                $preloaded = new \Cloudinary\PreloadedFile( $user_photo_id );
                if( $preloaded->is_valid() ){
                    $identifier = $preloaded->identifier();
                } else {
                    uber_log('Invalid upload signature.');
                    preg_match( '/image\/upload\/[0-9A-Za-z]+\/([0-9A-Za-z]+\.[a-z]+)#/', $user_photo_id, $matches );
                    uber_log( $matches );
                    $identifier = $matches[1];
                }

                $_SESSION['donor']['image'][] = [
                    'user_photo_id' => $user_photo_id,
                    'identifier'    => $identifier,
                    'public_id'     => $public_image_ids[$y],
                ];
                $y++;
            }
        }
    }

    $step = 'contact-details';
    if( $form->validate( $_POST ) ){
        if( isset( $_POST['donor']['answers'] ) ) {
            $redirect = true;

            if( $provide_additional_details && isset( $_POST['donor']['additional_details'] ) && ! empty( $_POST['donor']['additional_details'] ) )
                $_SESSION['donor']['description'].= "\n\n---- ADDITIONAL DETAILS for DAMAGED/PET/SMOKING Items ----\n" . $_POST['donor']['additional_details'];

            foreach( $_POST['donor']['answers'] as $key => $answer ) {
                $_SESSION['donor']['screening_questions'][$key] = array(
                    'question' => $_POST['donor']['questions'][$key],
                    'answer' => $_POST['donor']['answers'][$key]
                );
                if( 'Yes' == $answer && ! $provide_additional_details ) {
                    $_SESSION['donor']['form'] = 'no-damaged-items-message';
                    $redirect = false;
                }
            }

            if( true == $redirect ){
                $_SESSION['donor']['form'] = 'contact-details';
                if( isset( $_POST['nextpage'] ) && ! empty( $_POST['nextpage'] ) ){
                    session_write_close();
                    header( 'Location: ' . $_POST['nextpage'] );
                    die();
                } else {
                    add_html( '<div class="alert alert-error">No $_POST[nextpage] defined.</div>' );
                }
            }
        }
    } else {
        $errors = $form->getErrors();
        //uber_log(str_repeat('-',50));
        uber_log('$errors = ' . print_r( $errors, true ) );
        $error_msg = [];
        foreach ( $errors as $field => $array ) {
            switch( $field ){
                case 'answered':
                    $error_msg[] = 'Please answer each screening question.';
                break;
                case 'user_photo_id':
                   $error_msg[] = 'A photo of your donation is required.';
                break;
            }
        }
        add_html( get_alert(['type' => 'danger', 'description' => 'Please correct these errors:<ul><li>' . implode( '</li><li>', $error_msg ) . '</li></ul>' ]) );
    }
}