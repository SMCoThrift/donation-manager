<?php
namespace DonationManager\shortcodes;
use function DonationManager\utilities\{get_alert};
use function DonationManager\donations\{get_donation_contact};

/**
 * Processes inbound webhook notifications from Mandrill
 *
 * @since 1.4.4
 *
 * @param array $atts Shortcode attributes.
 * @return string Shortcode output.
 */
function inbound_email_processing( $atts ){
    $atts = shortcode_atts( array(
    'notify_webmaster' => true,
  ), $atts );

  if( 'false' === $atts['notify_webmaster'] )
    $atts['notify_webmaster'] = false;
  settype( $atts['notify_webmaster'], 'boolean' );

  if( ! isset( $_POST['mandrill_events'] ) )
    return get_alert([
      'type'        => 'danger',
      'description' => '<strong>ERROR:</strong> No <code>mandrill_events</code> received.',
    ]);

  $mandrill_events = json_decode( stripslashes( $_POST['mandrill_events'] ), true );
  if( is_array( $mandrill_events ) ){
    foreach( $mandrill_events as $event ){
      switch( $event['event'] ){
        case 'inbound':
          $subject = $event['msg']['subject'];
          $message = $event['msg']['html'];
          $to = $event['msg']['email'];
          // Inbound email format example: donor-89777@inbound.pickupmydonation.com
          // - donor = this is being sent to the donor
          // - 89777 = the donation ID
          $email_parts = explode( '@', $to );
          if( stristr( $email_parts[0], '-' ) ){
            $recipient = explode( '-', $email_parts[0] );
            $contact_type = $recipient[0];
            $donation_id = $recipient[1];
            global $from;
            switch( $contact_type ){
              case 'donor':
                $contact = get_donation_contact( $donation_id, 'donor' );
                $from = 'transdept-' . $donation_id . '@inbound.pickupmydonation.com';
              break;
              case 'transdept':
                $contact = get_donation_contact( $donation_id, 'transdept' );
                $from = 'donor-' . $donation_id . '@inbound.pickupmydonation.com';
              break;
            }
            $to = $contact['contact_email'];
          }

          add_filter( 'wp_mail_content_type', function( $content_type ){
            return 'text/html';
          } );

          if( true === $atts['notify_webmaster'] )
            wp_mail( 'webmaster@pickupmydonation.com', 'Mandrill Event - Inbound Email', 'The following was sent to ' . $to . "\n------\n\n" . $message );

              add_filter( 'wp_mail_from', function( $email ){
                  global $from;
                  return $from;
              } );

              add_filter( 'wp_mail_from_name', function( $name ){
                  return 'PMD Replies';
              });

          // Check to see if message is empty
          $stripped_message = trim( strip_tags( $message ) );

          if( ! empty( $stripped_message) )
            wp_mail( $to, $subject, $message );
        break;
      }
    }
  }

  return get_alert([
    'type'        => 'success',
    'description' => 'The event has been processed.',
  ]);
}
add_shortcode( 'inbound_email_processing', __NAMESPACE__ . '\\inbound_email_processing' );