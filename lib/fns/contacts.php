<?php

namespace DonationManager\contacts;

/**
 * Gets a contact.
 *
 * @param      int  $contact_id  The contact ID.
 *
 * @return     mixed    The contact object or FALSE upon failure..
 */
function get_contact( $contact_id ){
  if( ! is_numeric( $contact_id ) )
    return false;

  global $wpdb;
  $contacts = $wpdb->get_results( $wpdb->prepare( "SELECT ID,store_name,zipcode,email_address FROM {$wpdb->prefix}donman_contacts WHERE ID=%d", $contact_id ) );
  if( $contacts && is_array( $contacts ) ){
    return $contacts[0];
  } else {
    return false;
  }
}