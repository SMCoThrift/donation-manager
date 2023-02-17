<?php

namespace DonationManager\orphanedproviders;
use function DonationManager\helpers\{in_array_r};

/**
 * Gets the orphaned provider contact.
 *
 * @param      int   $orphan_provider_id  The orphan provider ID
 *
 * @return     array  The orphaned provider contact.
 */
function get_orphaned_provider_contact( $orphan_provider_id = '' ){
  if( empty( $orphan_provider_id ) )
      return false;

  global $wpdb;
  $row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'donman_contacts WHERE ID=%d', $orphan_provider_id ) );
  $contact['contact_title'] = 'Transportation Dept';
  $contact['store_name'] = $row->store_name;
  $contact['contact_email'] = $row->email_address;
  $contact['contact_name'] = 'Transport Manager';
  $contact['cc_emails'] = '';
  $contact['phone'] = '';

  return $contact;
}

/**
 * Returns emails for organizations within a specified radius of a given pickup code.
 *
 * @since 1.2.0
 *
 * @param array $args{
 *      @type int       $radius     Optional (defaults to ORPHANED_PICKUP_RADIUS miles). Radius in miles to retrieve organization contacts.
 *      @type string    $pcode      Zip Code.
 *      @type int       $limit      Optional. Max number of contacts to return.
 *      @type bool      $priority   Optional. Query priority contacts.
 *      @type string    $fields     Optional. Specify fields to return (e.g. `store_name,zipcode,email_address`). Defaults to `email_address`.
 *      @type bool      $duplicates Optional. Should we return duplicate stores? Defaults to TRUE.
 * }
 * @return array Returns an array of contact emails.
 */
function get_orphaned_donation_contacts( $args ){
  global $wpdb;

  $orphaned_pickup_radius = get_field( 'orphaned_pickup_radius', 'option' );
  if( empty( $orphaned_pickup_radius ) || ! is_numeric( $orphaned_pickup_radius ) )
    $orphaned_pickup_radius = 15;

  $args = shortcode_atts( array(
    'radius' => $orphaned_pickup_radius,
    'pcode' => null,
    'limit' => null,
    'priority' => 0,
    'fields' => 'email_address',
    'duplicates' => true,
    'show_in_results' => null,
  ), $args );

  //if( WP_CLI )
    //\WP_CLI::line( '🔔 running get_orphaned_donation_contacts( ' . print_r( $args, true ). ' );');

    // Validate $args['priority'], ensuring it is only `0` or `1`.
    if( ! in_array( $args['priority'], array( 0, 1 ) ) )
        $args['priority'] = 0;

    $error = new \WP_Error();

    if( empty( $args['pcode'] ) )
        return $error->add( 'nopcode', 'No $pcode sent to get_orphaned_donation_contacts().' );

    // Get the Lat/Lon of our pcode
    $sql = 'SELECT ID,Latitude,Longitude FROM ' . $wpdb->prefix . 'donman_zipcodes WHERE ZIPCode="%s" ORDER BY CityName ASC LIMIT 1';
    $coordinates = $wpdb->get_results( $wpdb->prepare( $sql, $args['pcode'] ) );

    if( ! $coordinates )
        return $error->add( 'nocoordinates', 'No coordinates returned for `' . $args['pcode'] . '`.' );

    $lat = $coordinates[0]->Latitude;
    $lon = $coordinates[0]->Longitude;

    // Get all zipcodes within $args['radius'] miles of our pcode
    $sql = 'SELECT distinct(ZipCode) FROM ' . $wpdb->prefix . 'donman_zipcodes  WHERE (3958*3.1415926*sqrt((Latitude-' . $lat . ')*(Latitude-' . $lat . ') + cos(Latitude/57.29578)*cos(' . $lat . '/57.29578)*(Longitude-' . $lon . ')*(Longitude-' . $lon . '))/180) <= %d';
    $zipcodes = $wpdb->get_results( $wpdb->prepare( $sql, $args['radius'] ) );

    if( ! $zipcodes )
        return $error->add( 'nozipcodes', 'No zip codes returned for ' . $args['pcode'] . '.' );

    if( $zipcodes ){
        $zipcodes_array = array();
        foreach( $zipcodes as $zipcode ){
            $zipcodes_array[] = $zipcode->ZipCode;
        }
        $zipcodes = implode( ',', $zipcodes_array );
    }

    // Get all email addresses for contacts in our group of zipcodes
    $sql = 'SELECT ID,' . $args['fields'] . ' FROM ' . $wpdb->prefix . 'donman_contacts WHERE receive_emails=1 AND priority=' . $args['priority'] . ' AND zipcode IN (' . $zipcodes . ')';

    if( isset( $args['show_in_results'] ) && ! is_null( $args['show_in_results'] ) && in_array( $args['show_in_results'], [0,1] ) )
        $sql.= ' AND show_in_results=' . $args['show_in_results'];

    if( ! is_null( $args['limit'] ) && is_numeric( $args['limit'] ) )
        $sql.= ' LIMIT ' . $args['limit'];

    $contacts = $wpdb->get_results( $sql, ARRAY_A );

    if( ! $contacts )
        return $error->add( 'nocontacts', 'No contacts returned for `' . $args['pcode'] . '`.' );

    if( $contacts ){
        $contacts_array = array();
        foreach( $contacts as $key => $contact ){
            // Dirty Data: Remove &#194;&#160; from end of Store Names
            if( isset( $contact['store_name'] ) && 'store_name' == $key ){
                $contact['store_name'] = preg_replace( '/[[:^print:]]/', '', $contact['store_name'] );
                $contact['store_name'] = str_replace(chr(194).chr(160), '', $contact['store_name']);
            }
            // Prevents duplicates from the same store
            if( isset( $contact['store_name'] )
                && ( false == $args['duplicates'] )
                && in_array_r( $contact['store_name'], $contacts_array )
            )
                continue;

            // Generate by-pass link
            $default_organization = get_field( 'default_organization', 'option' );
            $default_trans_dept = get_field( 'default_transportation_department', 'option' );
            $siteurl = get_option( 'siteurl' );
            $contact['by-pass-link'] = $siteurl . '/step-one/?oid=' . $default_organization->ID . '&tid=' . $default_trans_dept->ID . '&priority=0&orphanid=' . $contact['ID'];

            if( isset( $contact['email_address'] ) && ! in_array_r( $contact['email_address'], $contacts_array ) ){
              $contacts_array[$contact['ID']] = ( 'email_address' == $args['fields'] )? $contact['email_address'] : $contact;
            }

            /*
            // 02/28/2017 (12:32) - the following code doesn't appear to work
            if( isset( $contact['zipcode'] ) && trim( $args['pcode'] ) == trim( $contact['zipcode'] ) ){
                write_log('Before Giving Priority...'. "\n" .'$args[\'pcode\'] = '.$args['pcode'].";\n".'$contact[zipcode] = '.$contact['zipcode'].";\n".' $contacts_array = ' . print_r( $contacts_array, true ) . "\nSearching on this email address: " . $contact['email_address'] );
                // Give priority to the contact for this $args['pcode'],
                // otherwise we are setting the contact to the first
                // zipcode returned.
                if( 'email_address' == $args['fields'] ){
                    $key = array_search( $contact['email_address'], $contacts_array );
                } else {
                    $key = array_search( $contact['email_address'], array_column( $contacts_array, 'email_address' ) );
                }
                write_log("\n\n" . '$args[\'fields\'] = '.$args['fields'].'; '."\n".'Unsetting $contacts_array['.$key.'].' . "\n\n");
                unset( $contacts_array[$key] );
                $contacts_array[$contact['ID']] = ( 'email_address' == $args['fields'] )? $contact['email_address'] : $contact;
                write_log('After Giving Priority... '."\n".'$contacts_array = ' . print_r( $contacts_array, true ) );
            }
            */
        }
    }

    return $contacts_array;
}