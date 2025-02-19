<?php

namespace DonationManager\emails;
use function DonationManager\utilities\{get_referer};
use function DonationManager\templates\{render_template,get_template_part};
use function DonationManager\transdepts\{get_trans_dept_contact};
use function DonationManager\apirouting\{send_api_post};
use function DonationManager\orphanedproviders\{get_orphaned_provider_contact,get_orphaned_donation_contacts};
use function DonationManager\organizations\{is_orphaned_donation,get_organizations};
use function DonationManager\donations\{get_donation_routing_method,add_orphaned_donation,get_donation_receipt,get_click_to_claim_link};

/**
 * Sends an alert email to PMD Admin.
 *
 * @since 1.0.1
 *
 * @param string $message Specify the message to send.
 * @return void
 */
function notify_admin( $message = '' ){
  switch( $message ){
    case 'invalid_link':
      send_email( 'invalid_link' );
    break;

    case 'zipcode_mismatch':
      send_email('zipcode_mismatch');
      break;

    default:
      //send_email( 'missing_org_transdept_notification' );
      $pickup_code = ( 'Yes' == $_SESSION['donor']['different_pickup_address'] )? $_SESSION['donor']['pickup_address']['zip'] : $_SESSION['donor']['address']['zip'];
      header( 'Location: ' . home_url( '/select-your-organization/?pcode=' . $pickup_code . '&message=no_org_transdept' ) );
      die();
      break;
  }
}

/**
 * Sends donor confirmation and transportation dept emails.
 *
 * The FROM: address for all emails sent by this function is
 * `PickUpMyDonation <noreply@pickupmydonation.com>`. The
 * Reply-To contact is the Transportation Department contact.
 *
 * @since 1.0.0
 *
 * @param string $type Specifies email `type` (e.g. donor_confirmation).
 * @return void
 */
function send_email( $type = '' ){
    if( array_key_exists( 'donor', $_SESSION ) ){
      $donor = $_SESSION['donor'];
      $organization_name = get_the_title( $donor['org_id'] );
      $donor_trans_dept_id = $donor['trans_dept_id'];
    } else {
      $donor = [];
      $organization_name = '';
      $donor_trans_dept_id = null;
    }


    $orphaned_donation = false;

    // If isset( $donor['orphan_provider_id'] ), donor is using an Orphaned By-Pass link
    if( isset( $donor['orphan_provider_id'] ) && is_numeric( $donor['orphan_provider_id'] ) ){
      $tc = get_orphaned_provider_contact( $donor['orphan_provider_id'] );
      $organization_name = $tc['store_name'];
    } else if( array_key_exists( 'trans_dept_id', $donor ) ) {
      $tc = get_trans_dept_contact( $donor['trans_dept_id'] );

      //* Is this an ORPHANED DONATION? `true` or `false`?
      $orphaned_donation = is_orphaned_donation( $donor_trans_dept_id );
    }

    //* Get ORPHANED DONATION Contacts, LIMIT to 50 inside a `ORPHANED_PICKUP_RADIUS` mile radius
    if( $orphaned_donation ){

        // PRIORITY PICK UP?
        // Is this donation routing to for-profit/priority pick up providers?
        $priority = 0;
        if( isset( $donor['priority'] ) && 1 == $donor['priority'] )
            $priority = 1;
        $orphaned_pickup_radius = get_field( 'orphaned_pickup_radius', 'option' );
        $radius = ( is_numeric( $orphaned_pickup_radius ) )? $orphaned_pickup_radius : 15 ;
        $bcc_emails = get_orphaned_donation_contacts( array( 'pcode' => $donor['pickup_code'], 'limit' => 50, 'radius' => $radius, 'priority' => $priority ) );
        if( is_array( $bcc_emails ) && 0 < count( $bcc_emails ) )
            $tc['orphaned_donation_contacts'] = $bcc_emails;
    }

    // Setup preferred contact info
    if( array_key_exists( 'preferred_contact_method', $donor ) ){
      $contact_info = ( 'Email' == $donor['preferred_contact_method'] )? '<a href="mailto:' . $donor['email'] . '">' . $donor['email'] . '</a>' : $donor['phone'];
    } else {
      $contact_info = '';
    }

    // Retrieve the donation receipt
    //$donationreceipt = $this->get_property( 'donationreceipt' );
    $donationreceipt = ( array_key_exists( 'donationreceipt', $_SESSION ) )? $_SESSION['donationreceipt'] : '' ;

    // Does this org allow user photo uploads?
    if( array_key_exists( 'donor', $_SESSION ) ){
      $user_photo_uploads = [
        'on'        => get_field( 'pickup_settings_allow_user_photo_uploads', $_SESSION['donor']['org_id'] ),
        'required'  => get_field( 'pickup_settings_user_photo_uploads_required', $_SESSION['donor']['org_id'] ),
      ];
    } else {
      $user_photo_uploads = [
        'on'        => false,
        'required'  => false,
      ];
    }

    $headers = array();

    switch( $type ){
        /**
         * INVALID LINK ALERT
         *
         * Alerts admins when a referring URL sends traffic to the site via an invalid link.
         *
         * @context  Administrators
         */
        case 'invalid_link':
            if( ! get_referer() )
                return;

            $html = render_template( 'email.user-portal-notification', [
              'header_logo' => DONMAN_PLUGIN_URL . 'lib/images/pickupmydonation-logo-inverted_1200x144.png',
              'year' => date( 'Y' ),
              'email_content' => '<div style="text-align: left;"><p>The following page has an invalid link to our system:</p><p>Referrering URL = ' . get_referer() . '</p></div>',
            ]);

            $recipients = array( 'webmaster@pickupmydonation.com' );
            $subject = 'PMD Admin Notification - Invalid Link';
            $headers[] = 'Reply-To: PMD Support <support@pickupmydonation.com>';
        break;

        /**
         * MISSING ORGANIZATION/TRANSPORTATION DEPARTMENT ALERT
         *
         * Alerts admins when a donation comes through without an Organization and/or a Transportation Department set.
         *
         * @content  Administrator
         */
        case 'missing_org_transdept_notification':
            $html = render_template( 'email.user-portal-notification', [
              'header_logo' => DONMAN_PLUGIN_URL . 'lib/images/pickupmydonation-logo-inverted_1200x144.png',
              'year' => date( 'Y' ),              
              'email_content' => '<div style="text-align: left;"><p>This donation doesn\'t have an ORG and/or TRANS_DEPT set:</p><pre style="overflow: hidden;">$_SESSION[\'donor\'] = ' . print_r( $_SESSION['donor'], true ) . '</pre></div>',
            ]);
            $recipients = array( 'webmaster@pickupmydonation.com' );
            $subject = 'PMD Admin Notification - No Org/Trans Dept Set';
            $headers[] = 'Reply-To: PMD Support <support@pickupmydonation.com>';
        break;

        /**
         * ZIPCODE MISMATCH ALERT
         *
         * Used to alert admins when there's a mismatch between the searched zip code and the pick up address.
         *
         * @context  Administrator
         */
        case 'zipcode_mismatch':
            $html = render_template( 'email.user-portal-notification', [
              'header_logo' => DONMAN_PLUGIN_URL . 'lib/images/pickupmydonation-logo-inverted_1200x144.png',
              'year' => date( 'Y' ),              
              'email_content' => '<div style="text-align: left;"><p>' . $_POST['donor']['address']['name']['first'] . ' ' . $_POST['donor']['address']['name']['last'] . '<br />$_SESSION[\'donor\'][\'pickup_code\'] = ' . $_SESSION['donor']['pickup_code'] . '<br />$_POST[\'donor\'][\'address\'][\'zip\'] = ' . $_POST['donor']['address']['zip'] . '</p><p>URL PATH = ' . print_r( $_SESSION['donor']['url_path'], true ) . '</p></div>',
            ]);
            $recipients = ['webmaster@pickupmydonation.com'];
            $subject = 'PMD Zip Code Error - ' . esc_attr( $_POST['donor']['address']['name']['first'] ) . ' ' . esc_attr( $_POST['donor']['address']['name']['last'] );
            $headers[] = 'Reply-To: PMD Support <support@pickupmydonation.com>';
            break;

        /**
         * DONOR CONFIRMATION
         *
         * Sent to donors immediately after their donation is entered into the system.
         *
         * @context  Donor
         */
        case 'donor_confirmation':
          $contact_name = ( empty( $tc['contact_name'] ) )? 'Scheduling Coordinator' :  $tc['contact_name'] ;
          $contact_title = ( empty( $tc['contact_title'] ) )? 'Pick Up Scheduling' : $tc['contact_title'];

          $trans_contact = $contact_name;
          if( ! empty( $tc['contact_email'] ) )
            $trans_contact.= ' (<a href="mailto:' . $tc['contact_email'] . '">' . $tc['contact_email'] . '</a>)';

          $trans_contact.= '<br>' . $organization_name . ', ' . $contact_title;

          if( ! empty( $tc['phone'] ) )
            $trans_contact.= '<br>' . $tc['phone'];

          $orphaned_donation_note = '';
          if(
            $orphaned_donation
            && isset( $tc['orphaned_donation_contacts'] )
            && is_array( $tc['orphaned_donation_contacts'] )
            && 0 < count( $tc['orphaned_donation_contacts'] )
          ){
            $template = ( true == $priority )? 'email.donor.priority-donation-note' : 'email.donor.orphaned-donation-note';
            $orphaned_donation_note = get_template_part( $template, array( 'total_notified' => count( $tc['orphaned_donation_contacts'] ) ) );
          }

          // Handlebars Email Template
          $hbs_vars = [
            'organization_name'         => $organization_name,
            'donationreceipt'           => $donationreceipt,
            'trans_contact'             => $trans_contact,
            'orphaned_donation_note'    => $orphaned_donation_note,
            'allow_user_photo_uploads'  => $user_photo_uploads['on'],
            'year'                      => current_time( 'Y' ),
            'facebook_icon'             => DONMAN_PLUGIN_URL . 'lib/images/facebook.png',
            'x_icon'                    => DONMAN_PLUGIN_URL . 'lib/images/twitter.png',
            'instagram_icon'            => DONMAN_PLUGIN_URL . 'lib/images/instagram.png',
          ];
          if( $logo_url = get_the_post_thumbnail_url( $donor['org_id'], 'full' ) )
            $hbs_vars['organization_logo'] = $logo_url;

          if( $website = get_post_meta( $donor['org_id'], 'website', true ) )
            $hbs_vars['website'] = $website;

          $html = render_template( 'email.donor-confirmation', $hbs_vars );

          $recipients = array( $donor['address']['name']['first'] . ' ' . $donor['address']['name']['last'] . ' <' . $donor['email'] . '>' );
          // On localhost with Mailhog, long subject lines result in double encodeing with `=?us-ascii?Q?` getting added to subject.
          $subject = ( DONMAN_DEV_ENV )? 'Thank You For Your Donation' : 'Thank You for Donating to ' . $organization_name ;
          //uber_log('🔔 👉 Sending Trans Dept email with subject = ' . $subject );

          // Set Reply-To the Transportation Department
          $headers[] = 'Sender: PickUpMyDonation.com <contact@pickupmydonation.com>';
          $headers[] = 'Reply-To: ' . $tc['contact_name'] . ' <' . $tc['contact_email'] . '>';
        break;

        /**
         * TRANSPORTATION DEPARTMENT NOTIFICATION
         *
         * Email sent to the contact for a Transportation Department.
         *
         * @context  Provider
         */
        case 'trans_dept_notification':
          // Donation Routing Method
          if( ! $orphaned_donation ){
            $donor['routing_method'] = get_donation_routing_method( $donor['org_id'] );
            if( 'email' != $donor['routing_method'] ){
              send_api_post( $donor );
              uber_log('🔔 `trans_dept_notification` donor routing_method = ' . $donor['routing_method'] );

              switch( $donor['routing_method'] ){
                case '1800gj_api':
                  // nothing, send API post and email
                  break;

                case 'chhj_api':
                  // College Hunks prefers to only receive notifications via their API (no emails):
                  return;

                default:
                  // nothing, send API post and email
                  break;
              }
            }
          } else if ( $orphaned_donation && is_array( $donor ) && array_key_exists( 'pickup_code', $donor ) ){
            /**
             * SEND ORPHANED DONATION EMAILS TO PRIORITY PARTNERS
             *
             * Using `get_organizations()` we will return all orgs for a
             * zip code. Then we'll retrieve any emails for PRIORITY
             * Orgs and include them in the distribution.
             */
            $fee_based = ( array_key_exists( 'fee_based', $donor ) )? $donor['fee_based'] : true ;
            if( $fee_based ){
              $orgs = get_organizations( $donor['pickup_code'] );
              $priority_recipients = [];
              foreach ( $orgs as $org ) {
                /**
                 * For any Priority Orgs with an API Endpoint, also
                 * send an API Post of the donation:
                 */
                if( stristr( $org['routing_method'], 'api' ) ){
                  $donor['routing_method'] = $org['routing_method'];
                  send_api_post( $donor );
                  $donor['routing_method'] = 'email';
                }

                /**
                 * Build an array of priority recipient emails.
                 */
                if( $org['priority_pickup'] && array_key_exists( 'trans_dept_emails', $org ) && 0 < count( $org['trans_dept_emails'] ) ){
                  // Get the trans_dept email contact
                  foreach( $org['trans_dept_emails'] as $priority_email ){
                    $priority_recipients[] = $priority_email;
                  }
                }
              } // foreach
            } // if ( $fee_based )
          }

          $recipients = array( $tc['contact_email'] );
          if( is_array( $tc['cc_emails'] ) ){
              $cc_emails = $tc['cc_emails'];
          } else if( stristr( $tc['cc_emails'], ',' ) ){
              $cc_emails = explode( ',', str_replace( ' ', '', $tc['cc_emails'] ) );
          } else if( is_email( $tc['cc_emails'] ) ){
              $cc_emails = array( $tc['cc_emails'] );
          }

          if( isset( $cc_emails ) )
            $recipients = array_merge( $recipients, $cc_emails );

          $subject = 'Scheduling Request from ' . $donor['address']['name']['first'] . ' ' .$donor['address']['name']['last'];

          //* Setup Emails for ORPHANED DONATION Contacts and adjust the SUBJECT
          $orphaned_donation_note = '';
          if(
            $orphaned_donation
            && isset( $tc['orphaned_donation_contacts'] )
            && is_array( $tc['orphaned_donation_contacts'] )
            && 0 < count( $tc['orphaned_donation_contacts'] )
          ){
            foreach( $tc['orphaned_donation_contacts'] as $contact_id => $contact_email ){
              $recipients[$contact_id] = $contact_email;
              add_orphaned_donation( array( 'contact_id' => $contact_id, 'donation_id' => $donor['ID'] ) );
            }

            $subject = 'Large Item ';
            if( ! $priority )
              $subject.= 'Donation ';
            $subject.= 'Pick Up Requested by ';
            $subject.= $donor['address']['name']['first'] . ' ' .$donor['address']['name']['last'];

            // Orphaned Donation Note - Non-profit/Priority
            $template = ( true == $priority )? 'email.trans-dept.priority-donation-note' : 'email.trans-dept.orphaned-donation-note';
            $orphaned_donation_note = get_template_part( $template );
          }

          // Record Orphaned Donation for By-Pass links
          if( isset( $donor['orphan_provider_id'] ) && is_numeric( $donor['orphan_provider_id'] ) ){
            add_orphaned_donation( [ 'contact_id' => $donor['orphan_provider_id'], 'donation_id' => $donor['ID'] ] );
          }

          // User Uploaded Photos
          $user_uploaded_image = '';
          if( isset( $donor['image'] ) && ! empty( $donor['image'] ) && is_array( $donor['image'] ) )
          {
            $user_uploaded_image = [];
            foreach( $donor['image'] as $image ){
              // TODO: Add validation via Cloudinary
              $image_url = cloudinary_url( $image['public_id'], [
                'secure' => true,
                'width' => 800,
                'height' => 600,
                'crop' => 'fit',
                'cloud_name' => CLOUDINARY_CLOUD_NAME,
                'format' => 'jpg',
              ]);
              $user_uploaded_image[] = $image_url;
            }
            uber_log( '🔔 $user_uploaded_image = ' . print_r( $user_uploaded_image, true ) );
          }

          // HANDLEBARS TEMPLATE
          // 03/06/2023 (10:40) - if TRUE == $orphaned_donation, the donation receipt will omit donor contact details in favor of using the "Click to Claim" link
          // 12/07/2023 (10:28) - Removing "Click to Claim", originally 'donationreceipt' => get_donation_receipt( $donor, $orphaned_donation )
          $hbs_vars = [
            'donor_name' => $donor['address']['name']['first'] . ' ' .$donor['address']['name']['last'],
            'contact_info' => str_replace( '<a href', '<a style="color: #6f6f6f; text-decoration: none;" href', $contact_info ),
            'donationreceipt' => get_donation_receipt( $donor, false ),
            'orphaned_donation_note' => $orphaned_donation_note,
            'organization_name' => $organization_name,
            'year'                      => current_time( 'Y' ),
            'facebook_icon'             => DONMAN_PLUGIN_URL . 'lib/images/facebook.png',
            'x_icon'                    => DONMAN_PLUGIN_URL . 'lib/images/twitter.png',
            'instagram_icon'            => DONMAN_PLUGIN_URL . 'lib/images/instagram.png',            
          ];

          if( isset( $user_uploaded_image ) && ! empty( $user_uploaded_image ) )
            $hbs_vars['user_uploaded_image'] = $user_uploaded_image;

          /**
           * 02/13/2019 (13:00) - UNIQUE UNSUBSCRIBE LINK PER RECIPIENT
           *
           * Rather than generating one email html, if we want a unique unsubscribe link
           * in each, we need to generate the html for each email address.
           */
          if( DMDEBUG_VERBOSE )
            uber_log('$recipients = ' . print_r( $recipients, true ) );

          foreach ( $recipients as $contact_id => $email ) {
            $hbs_vars['email'] = $email;
            /**
             * 12/07/2023 (10:29) - Disabling "Click to Claim"
             */
            //if( array_key_exists( 'donation_hash', $_SESSION['donor'] ) )
              //$hbs_vars['click_to_claim'] = get_click_to_claim_link( $_SESSION['donor']['donation_hash'], $contact_id );
            $hbs_vars['click_to_claim'] = false;
            $discrete_html_emails[$email] = render_template( 'email.trans-dept-notification', $hbs_vars );
          }

          /**
           * Orphaned Donations with $priority_recipients will also include a full copy
           * of the Donation Receipt to send to those Priority Partners:
           */
          if( $orphaned_donation && isset( $priority_recipients ) && 0 < count( $priority_recipients ) ){
            $hbs_vars['donationreceipt'] = get_donation_receipt( $donor );
            $hbs_vars['click_to_claim'] = false;
            $hbs_vars['orphaned_donation_note'] = get_template_part( 'email.trans-dept.orphaned-donation-note-for-priority-partner' );
            foreach( $priority_recipients as $priority_recipient_email ){
              $discrete_html_emails[$priority_recipient_email] = render_template( 'email.trans-dept-notification', $hbs_vars );
            }
          }

          // Set Reply-To our donor
          $headers[] = 'Reply-To: ' . $donor['address']['name']['first'] . ' ' .$donor['address']['name']['last'] . ' <' . $donor['email'] . '>';
        break;

    }

    // Set the from: address emails as follows:
    //
    // - `donor_confirmation`       = transdept-_DONATION_ID_@inbound.pickupmydonation.com
    // - `trans_dept_notification`  = donor-_DONATION_ID_@inbound.pickupmydonation.com
    //
    // All emails sent to *@inbound.pickupmydonation.com will
    // be processed at https://www.pickupmydonation.com/inbound/.
    // DMShortcodes::inbound_email_processing() does the processing.
    //

    if( 'donor_confirmation' == $type ){
      add_filter( 'wp_mail_from', function( $email ){
        $donor = $_SESSION['donor'];
        $donation_id = $donor['ID'];
        return 'transdept-' . $donation_id . '@inbound.pickupmydonation.com';
      } );

      add_filter( 'wp_mail_from_name', function( $name ){
        $donor = $_SESSION['donor'];
        $tc = get_trans_dept_contact( $donor['trans_dept_id'] );
        return $tc['contact_name'];
      });
    } elseif ( 'trans_dept_notification' == $type ) {
      add_filter( 'wp_mail_from', function( $email ){
        $donor = $_SESSION['donor'];
        $donation_id = $donor['ID'];
        return 'donor-' . $donation_id . '@inbound.pickupmydonation.com';
      } );

      add_filter( 'wp_mail_from_name', function( $name ){
        $donor = $_SESSION['donor'];
        return $donor['address']['name']['first'] . ' ' .$donor['address']['name']['last'];
      });
    } else {
      add_filter( 'wp_mail_from', function( $email ){
        return 'contact@pickupmydonation.com';
      } );
      add_filter( 'wp_mail_from_name', function( $name ){
        return 'PickUpMyDonation.com';
      });
    }

    add_filter( 'wp_mail_content_type', __NAMESPACE__ . '\\return_content_type' );

    $subject = html_entity_decode( $subject, ENT_COMPAT, 'UTF-8' );

    /**
     * MailHog miss-encodes the subject line (i.e. you get "=?us-ascii?Q?" with no
     * subject showing). Reducing the strlen below 40 chars so we see it during
     * local development.
     *
     * Ref: https://github.com/mailhog/MailHog/issues/282
     */
    if( DONMAN_DEV_ENV ){
      if( 40 < strlen( $subject ) )
        $subject = substr( $subject, 0, 37 ) . '...';
    }

    if( true == $orphaned_donation && 'trans_dept_notification' == $type ){

      // Send normal email to default contact, any cc_emails for the
      // trans dept are included in $recipients. So, we use this to
      // add national pick up providers to the orphaned distribution.
      if( isset( $discrete_html_emails ) && is_array( $discrete_html_emails ) && 0 < count( $discrete_html_emails ) ){
        foreach ( $discrete_html_emails as $discrete_email => $discrete_html ) {
          wp_mail( $discrete_email, $subject, $discrete_html, $headers );
        }
      }

      /**
       * 11/01/2024 (11:58) - NOTE: I don't think this code is necessary
       * anymore b/c of the code above which sends API posts for all
       * Orphaned Donations with Fee Based checked. Furthermore, the
       * below would only send requests for valid zip codes due to the
       * zip code check [is_valid_pickupcode()] I have inside
       * send_api_post().
       */
      // Send API post to CHHJ-API, College Hunks Hauling receives
      // all orphans via this:
      $donor['routing_method'] = 'api-chhj';
      if( $donor['fee_based'] )
        send_api_post( $donor );
    } else {
      if( 'trans_dept_notification' == $type ){
        foreach ($recipients as $email ) {
          $hbs_vars['email'] = $email;
          $html = render_template( 'email.trans-dept-notification', $hbs_vars );
          wp_mail( $email, $subject, $html, $headers );
        }
      } else {
        wp_mail( $recipients, $subject, $html, $headers );
        if( DMDEBUG ){
          $log_recipients = ( is_array( $recipients ) )? implode( ', ', $recipients ) : $recipients ;
          $log_headers = ( is_array( $headers ) )? implode( "\t\n", $headers ) : $headers ;
          uber_log( "🔔 send_email( `$type` ):\n\$recipients = " . $log_recipients . "\n\$subject = $subject\n\$html = [...]\n\$headers = " . $log_headers );
        }
      }
    }

    remove_filter( 'wp_mail_content_type', __NAMESPACE__ . '\\return_content_type' );
}

function return_content_type(){
  return 'text/html';
}