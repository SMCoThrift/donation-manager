<?php

namespace DonationManager\donations;
use function DonationManager\templates\{render_template};
use function DonationManager\utilities\{get_referer};
use function DonationManager\transdepts\{get_trans_dept_contact};

/**
 * Saves a donation to the database
 *
 * @since 1.0.0
 *
 * @param array $donation Donation array.
 * @return int Donation post id.
 */
function save_donation( $donation = array() ){
  if( empty( $donation ) || 0 == count( $donation ) )
    return false;

  if( is_duplicate_donation( $donation ) ){
    return false;
  } else {
    $hash = get_donation_hash( $donation );
    set_transient( 'dm_donation_' . $hash, 1, DONATION_TIMEOUT );
  }

  $post = [ 'post_type' => 'donation' ];

  if( isset( $donation['post_date'] ) && ! empty( $donation['post_date'] ) )
    $post['post_date'] = date( 'Y-m-d H:i:s', strtotime( $donation['post_date'] ) );
  if( isset( $donation['post_date_gmt'] ) && ! empty( $donation['post_date_gmt'] ) )
    $post['post_date_gmt'] = date( 'Y-m-d H:i:s', strtotime( $donation['post_date_gmt'] ) );

  $ID = wp_insert_post( $post );

  $donation['ID'] = $ID;
  $_SESSION['donor']['ID'] = $ID;
  $donationreceipt = get_donation_receipt( $donation );
  // 09/13/2022 (09:30) - Since we're not inside an object, the following won't work.
  // Need to figure out what to do with the below:
  //$this->set_property( 'donationreceipt', $donationreceipt );
  $_SESSION['donationreceipt'] = $donationreceipt;

  $post = [
    'ID' => $ID,
    'post_content' => $donationreceipt,
    'post_title' => implode( ', ', $donation['items'] ) . ' - ' . $donation['address']['name']['first'] . ' ' . $donation['address']['name']['last'],
    'post_status' => 'publish',
  ];

  if( isset( $donation['priority'] ) && true == $donation['priority'] )
    $post['post_title'] = 'PRIORITY - ' . $post['post_title'];

  wp_update_post( $post );

  // Map ACF field names to $_POST vars
  $post_meta = array(
    'organization' => 'org_id',
    'trans_dept' => 'trans_dept_id',
    'donor_name' => '',
    'donor_email' => 'email',
    'donor_phone' => 'phone',
    'donor_company' => '',
    'donor_address' => '',
    'donor_city' => '',
    'donor_state' => '',
    'donor_zip' => '',
    'pickup_address' => '',
    'pickup_city' => '',
    'pickup_state' => '',
    'pickup_zip' => '',
    'pickup_description' => 'description',
    'pickupdate1' => 'pickupdate1',
    'pickuptime1' => 'pickuptime1',
    'pickupdate2' => 'pickupdate2',
    'pickuptime2' => 'pickuptime2',
    'pickupdate3' => 'pickupdate3',
    'pickuptime3' => 'pickuptime3',
    'preferred_code' => 'preferred_code',
    'legacy_id' => 'legacy_id',
    'referer' => 'referer',
    'image' => '',
    'reason' => '',
  );

  // Get Donation ACF Field Group
  $field_group = donman_get_acf_field_group( 'group_629f701864f58' );
  $fields = $field_group->fields;

  foreach( $fields as $field ){
    switch( $field->name ){
      case 'address':
      case 'pickup_address':
        $address_sub_fields = ['company','address','city','state','zip'];
        /*
        $address_sub_fields = [
          'address' => [ 'donor_company' => 'company', 'donor_address' => 'street', 'donor_city' => 'city', 'donor_state' => 'state', 'donor_zip' => 'zip' ],
          'pickup_address'  => [ 'pickup_company' => 'company', 'pickup_address' => 'street', 'pickup_city' => 'city', 'pickup_state' => 'state', 'pickup_zip' => 'zip' ],
        ];
        $sub_fields = $address_sub_fields[ $field->name ];
        /**/
        foreach( $address_sub_fields as $field_name ){
          // Check for $field_name in $donor[ $field->name ] sub-array (e.g. $donor['pickup_address']['city']):
          if( array_key_exists( $field->name, $donation ) && array_key_exists( $field_name, $donation[ $field->name ] ) ){
            $value = $donation[ $field->name ][ $field_name ];
            if( 'address' == $field_name )
              $field_name = 'street';
            update_field( $field->key, [ $field_name => $value ], $ID );
            if( WP_CLI )
              \WP_CLI::line( '👉 update_field( ' . $field->key . ', [ ' . $field_name . ' => ' . $value . ' ], ' . $ID . ' );' );
          }
        }
        break;

      case 'donor':
        $sub_fields = [ 'name', 'email', 'phone' ];
        foreach( $sub_fields as $field_name ){
          switch( $field_name ){
            case 'name':
              $value = $donation['address']['name']['first'] . ' ' . $donation['address']['name']['last'];
              break;

            default:
              $value = $donation[ $field_name ];
              break;
          }
          update_field( $field->key, [ $field_name => $value ], $ID );
        }
        break;

      case 'pickup_times':
        $sub_field_key = $field->sub_fields[0]->key;
        for ( $i = 1; $i < 4; $i++) {
          $pickup_time = $donation[ 'pickupdate' . $i ] . ' ' . $donation[ 'pickuptime' . $i ];
          add_row( $field->key, [ $sub_field_key => $pickup_time ], $ID );
        }
        break;

      case 'referer':
        $referer = \DonationManager\utilities\get_referer();
        update_field( $field->key, $referer, $ID );
        break;

      default:
        $donation_array_key = $post_meta[ $field->name ];
        $meta_value = ( isset( $donation[$donation_array_key] ) )? $donation[$donation_array_key] : '' ;
        update_field( $field->key, $meta_value, $ID );
        break;
    }
  }

  // Save _organization_name for sorting purposes
  add_post_meta( $ID, '_organization_name', get_the_title( $donation['org_id'] ) );

  return $ID;
}

/**
 * Records an orphaned donation record (i.e. analytics for orphaned donations)
 *
 * @since 1.2.0
 *
 * @param type $var Description.
 * @param type $var Optional. Description.
 * @return type Description. (@return void if a non-returning function)
 */
function add_orphaned_donation( $args ){
    global $wpdb;

    $args = shortcode_atts( array(
        'contact_id' => null,
        'donation_id' => null,
        'timestamp' => current_time( 'mysql' ),
    ), $args );

    if( is_null( $args['contact_id'] ) || ! is_numeric( $args['contact_id'] ) )
        return false;

    if( is_null( $args['donation_id'] ) || ! is_numeric( $args['donation_id'] ) )
        return false;

    $wpdb->insert( $wpdb->prefix . 'donman_orphaned_donations', $args, array( '%d', '%d', '%s') );
}

/**
 * Applies terms to the donation.
 *
 * @param      int    $ID        The Donation ID.
 * @param      array  $donation  The donation array.
 */
function tag_donation( $ID = null, $donation = [] ){
  if( empty( $ID ) || ! is_numeric( $ID ) )
    return;

  // Tag pickup_items/donation_options
  if( isset( $donation['items'] ) && ! in_array( 'PMD 1.0 Donation', $donation['items'] ) ){
    $item_ids = array_keys( $donation['items'] );
    $item_ids = array_map( 'intval', $item_ids );
    $item_ids = array_unique( $item_ids );
    wp_set_object_terms( $ID, $item_ids, 'donation_option' );
  }

  // Tag the pickup_location
  if( isset( $donation['pickuplocation'] ) ){
    $pickup_location_slug = sanitize_title( $donation['pickuplocation'] );
    wp_set_object_terms( $ID, $pickup_location_slug, 'pickup_location' );
  }

  // Tag the pickup_code
  if( isset( $donation['pickup_code'] ) ){
    $pickup_code_slug = sanitize_title( $donation['pickup_code'] );
    wp_set_object_terms( $ID, $pickup_code_slug, 'pickup_code' );
  }

  // Tag the screening_question(s)
  if( array_key_exists( 'screening_questions', $donation ) && is_array( $donation['screening_questions'] ) ){
    $screening_question_ids = array_keys( $donation['screening_questions'] );
    $screening_question_ids = array_map( 'intval', $screening_question_ids );
    $screening_question_ids = array_unique( $screening_question_ids );
    wp_set_object_terms( $ID, $screening_question_ids, 'screening_question' );
  }
}

/**
 * Given a donation ID and a contact type, returns contact
 * info for a donation’s donor or trans dept contact.
 *
 * @since 1.4.4
 *
 * @param int $donation_id Donation ID.
 * @param string $contact_type Either `donor` or `transdept`.
 * @return array Contact name and email.
 */
function get_donation_contact( $donation_id = null, $contact_type = null ){
    if( is_null( $donation_id ) || is_null( $contact_type ) )
        return false;

    $contact = array();

    switch( $contact_type ){
        case 'donor':
            $contact['contact_email'] = get_field( 'donor_email', $donation_id );
            $contact['contact_name'] = get_field( 'donor_name', $donation_id );
        break;

        case 'transdept':
            $id = get_field( 'trans_dept', $donation_id );
            $contact = get_trans_dept_contact( $id );
        break;
    }

    return $contact;
}

/**
 * Generates a donation hash
 *
 * @access (for functions: only use if private)
 * @since 1.x.x
 *
 * @param array $donation Donation array.
 * @return str MD5 hash generated from donation array.
 */
function get_donation_hash( $donation ){
  if( empty( $donation ) || ! is_array( $donation ) )
    return false;

  $donation_string = $donation['address']['name']['first'] . $donation['address']['name']['last'] . $donation['email'];
  $hash = md5( $donation_string );
  return $hash;
}

/**
 * Compiles the donation into an HTML receipt
 *
 * @since 1.0.0
 *
 * @param array $donation Donation array.
 * @return string Donation receipt HTML.
 */
function get_donation_receipt( $donation = array() ){
  if( empty( $donation ) || ! is_array( $donation ) )
    return '<p>No data sent to <code>get_donation_receipt</code>!</p>';

  // Setup preferred contact info
  $contact_info = ( 'Email' == $donation['preferred_contact_method'] )? '<a href="mailto:' . $donation['email'] . '">' . $donation['email'] . '</a>' : $donation['phone'];

  // Setup the $key we use to generate the pickup address
  $pickup_add_key = ( 'Yes' == $donation['different_pickup_address'] )? 'pickup_address' : 'address';

  // Format Screening Questions
  if( isset( $donation['screening_questions'] ) && is_array( $donation['screening_questions'] ) ){
    $screening_questions = array();
    foreach( $donation['screening_questions'] as $screening_question ){
      $screening_questions[] = $screening_question['question'] . ' <em>' . $screening_question['answer'] . '</em>';
    }
    $screening_questions = '<ul><li>' . implode( '</li><li>', $screening_questions ) . '</li></ul>';
  } else {
    $screening_questions = '<em>Not applicable.</em>';
  }

  if( ! empty( $donation['address']['company'] ) ){
    $donor_info = $donation['address']['company'] . '<br>c/o ' .$donation['address']['name']['first'] . ' ' . $donation['address']['name']['last'];
  } else {
    $donor_info = $donation['address']['name']['first'] . ' ' . $donation['address']['name']['last'];
  }

  $data = [
    'id'          => $donation['ID'],
    'donor_info'  => $donor_info . '<br>' . $donation['address']['address'] . '<br>' . $donation['address']['city'] . ', ' . $donation['address']['state'] . ' ' . $donation['address']['zip'] . '<br>' . $donation['phone'] . '<br>' . $donation['email'],
    'pickupaddress' => $donation[$pickup_add_key]['address'] . '<br>' . $donation[$pickup_add_key]['city'] . ', ' . $donation[$pickup_add_key]['state'] . ' ' . $donation[$pickup_add_key]['zip'],
    'pickupaddress_query' => urlencode( $donation[$pickup_add_key]['address'] . ', ' . $donation[$pickup_add_key]['city'] . ', ' . $donation[$pickup_add_key]['state'] . ' ' . $donation[$pickup_add_key]['zip'] ),
    'preferred_contact_method' => $donation['preferred_contact_method'] . ' - ' . $contact_info,
    'items' => implode( ', ', $donation['items'] ),
    'description' => nl2br( $donation['description'] ),
    'screening_questions' => $screening_questions,
    'pickuplocation' =>  $donation['pickuplocation'],
    'pickup_code' => $donation['pickup_code'],
    'preferred_code' => $donation['preferred_code'],
    'reason' => $donation['reason'],
  ];

  if( ! empty( $donation['pickupdate1'] ) ){
    $data['pickupdates'] = [
      0 => [ 'date' => $donation['pickupdate1'], 'time' => $donation['pickuptime1'] ],
      1 => [ 'date' => $donation['pickupdate2'], 'time' => $donation['pickuptime2'] ],
      2 => [ 'date' => $donation['pickupdate3'], 'time' => $donation['pickuptime3'] ],
    ];
  }
  $donationreceipt = render_template( 'email.donation-receipt', $data );

  return $donationreceipt;
}

/**
 * Returns organization’s donation routing method.
 *
 * @access self::send_email()
 * @since 1.4.0
 *
 * @param int $org_id Organization ID.
 * @return string Organization's routing method. Defaults to `email`.
 */
function get_donation_routing_method( $org_id = null ){
  if( is_null( $org_id ) )
    return false;

  $donation_routing = get_field( 'pickup_settings_donation_routing', $org_id );

  if( empty( $donation_routing ) )
    $donation_routing = 'email';

  return $donation_routing;
}

/**
 * Checks to see if a donation is a duplicate
 *
 * @since 1.4.6
 *
 * @param array $donation Donation array.
 * @return bool Returns `true` if a duplicate exists.
 */
function is_duplicate_donation( $donation ){
  $duplicate = false;

  $hash = get_donation_hash( $donation );
  $duplicate = get_transient( 'dm_donation_' . $hash );

  return $duplicate;
}