<?php

namespace DonationManager\donations;
use function DonationManager\templates\{render_template};
use function DonationManager\utilities\{get_referer};
use function DonationManager\transdepts\{get_trans_dept_contact};

/**
 * Adds a click timestamp to an Orphaned Donation.
 *
 * @param      int  $donation_id  The donation ID
 * @param      int  $contact_id   The contact ID
 *
 * @return     int|false  Number of Orphaned Donations updated on success or FALSE on failure.
 */
function add_click_timestamp( $donation_id, $contact_id ){
  uber_log('🔔 add_click_timestamp('.$donation_id.','.$contact_id.')');
  if( ! is_numeric( $donation_id ) || ! is_numeric( $contact_id ) )
    return false;

  global $wpdb;
  $result = $wpdb->update(
    $wpdb->prefix . 'donman_orphaned_donations',
    [ 'click_timestamp' => current_time( 'mysql' ) ],
    [ 'donation_id' => $donation_id, 'contact_id' => $contact_id ]
  );
  uber_log('🔔 $result = '. $result);

  return $result;
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

    $exists = orphaned_donation_exists([ 'contact_id' => $args['contact_id'], 'donation_id' => $args['donation_id'] ]);

    if( ! $exists ){
      $wpdb->insert( $wpdb->prefix . 'donman_orphaned_donations', $args, array( '%d', '%d', '%s') );
      if( WP_CLI && class_exists( 'WP_CLI' ) )
        \WP_CLI::line( '✅ Adding orphaned donation #' . $args['donation_id'] . ' for contact_id = ' . $args['contact_id'] );
    } else {
      if( WP_CLI && class_exists( 'WP_CLI' ) )
        \WP_CLI::line( '🚨 Orphaned Donation exists. Skipping...' );
    }
}

/**
 * Returns a "Click to Claim" URL.
 *
 * @param      string  $dh                    Donation hash.
 * @param      int     $contact_id            The contact ID
 * @param      int     $orphaned_donation_id  The orphaned donation ID
 *
 * @return     string  The "Click to Claim" link.
 */
function get_click_to_claim_link( $dh, $contact_id ){
  return site_url( 'click-to-claim/?dh=' . $dh . '&cid=' . $contact_id );
}

/**
 * Claims a donation.
 *
 * @param      int  $donation_id  The donation ID.
 * @param      int  $contact_id   The contact ID.
 *
 * @return     mixed       Returns Meta ID upon success or false on failure
 */
function claim_donation( $donation_id, $contact_id ){
  if( ! is_numeric( $donation_id) )
    return new \WP_Error( 'notanumber', __( 'Non numeric Donation ID.' ) );
  $posttype = get_post_type( $donation_id );
  if( 'donation' != $posttype )
    return new \WP_Error( 'notadonation', __( 'Provided ID is not a Donation ID.' ) );

  $success = false;
  if( ! is_claimed( $donation_id ) ){
    $success = add_post_meta( $donation_id, 'claimed', true );
    add_post_meta( $donation_id, 'claimed_contact_id', $contact_id );
    add_post_meta( $donation_id, 'claimed_timestamp', current_time( 'mysql' ) );
  }

  return $success;
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
function get_donation_receipt( $donation = array(), $click_to_claim = false ){
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

  if( $click_to_claim ){
    $donor_info = $donor_info . '<br>' . $donation['address']['city'] . ', ' . $donation['address']['state'] . ' ' . $donation['address']['zip'] . '<br>(<em>Click the button above to get this donor\'s full information.</em>)';
    $pickup_address = '(<em>Click the button above to get this donor\'s full information.</em>)';
    $preferred_contact_method = '(<em>Click the button above to get this donor\'s full information.</em>)';
  } else {
    $donor_info = $donor_info . '<br>' . $donation['address']['address'] . '<br>' . $donation['address']['city'] . ', ' . $donation['address']['state'] . ' ' . $donation['address']['zip'] . '<br>' . $donation['phone'] . '<br>' . $donation['email'];
    $pickup_address = $donation[$pickup_add_key]['address'] . '<br>' . $donation[$pickup_add_key]['city'] . ', ' . $donation[$pickup_add_key]['state'] . ' ' . $donation[$pickup_add_key]['zip'];
    $preferred_contact_method = $donation['preferred_contact_method'] . ' - ' . $contact_info;
  }

  $data = [
    'id'          => $donation['ID'],
    'donor_info'  => $donor_info,
    'pickupaddress' => $pickup_address,
    'pickupaddress_query' => urlencode( $donation[$pickup_add_key]['address'] . ', ' . $donation[$pickup_add_key]['city'] . ', ' . $donation[$pickup_add_key]['state'] . ' ' . $donation[$pickup_add_key]['zip'] ),
    'preferred_contact_method' => $preferred_contact_method,
    'items' => implode( ', ', $donation['items'] ),
    'description' => nl2br( $donation['description'] ),
    'screening_questions' => $screening_questions,
    'pickuplocation' =>  $donation['pickuplocation'],
    'pickup_code' => $donation['pickup_code'],
    'preferred_code' => $donation['preferred_code'],
    'reason' => $donation['reason'],
    'click_to_claim' => $click_to_claim,
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
 * Gets the donation zip code given a Donation ID.
 *
 * @param      int  $id     The Donation ID
 *
 * @return     mixed    The donation zip code or FALSE if unsuccessful.
 */
function get_donation_zip_code( $id = null ){
  if( is_null( $id ) )
    return false;

  $pickup_codes = wp_get_post_terms( $id, 'pickup_code', [ 'fields' => 'names' ] );
  if( ! $pickup_codes )
    return false;

  foreach( $pickup_codes as $pickup_code ){
    return $pickup_code;
  }
}

/**
 * Gets the first click to claim timestamp.
 *
 * @param      int  $contact_id   The contact identifier
 * @param      int  $donation_id  The donation identifier
 *
 * @return     string|false    The first click to claim.
 */
function get_first_click_to_claim( $donation_id = null ){
  if( is_null( $donation_id ) )
    return false;
  if( ! is_numeric( $donation_id ) )
    return false;

  $claimed_timestamp = get_post_meta( $donation_id, 'claimed_timestamp', true );
  $claimed_contact_id = get_post_meta( $donation_id, 'claimed_contact_id', true );
  return $claim_data = [
    'timestamp'   => $claimed_timestamp,
    'contact_id'  => $claimed_contact_id,
  ];
}

/**
 * Determines whether the specified donation is claimed.
 *
 * @param      int $donation_id  The donation ID
 *
 * @return     bool       True if the specified donation is claimed, False otherwise.
 */
function is_claimed( $donation_id ){
  if( ! is_numeric( $donation_id) )
    return new \WP_Error( 'notanumber', __( 'Non numeric Donation ID.' ) );
  $posttype = get_post_type( $donation_id );
  if( 'donation' != $posttype )
    return new \WP_Error( 'notadonation', __( 'Provided ID is not a Donation ID.' ) );

  $claimed = get_post_meta( $donation_id, 'claimed', true );

  return $claimed;
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

/**
 * Determines if orphaned donation exists.
 *
 * @param      array  $args{
 *   @type  int  $contact_id  The contact ID.
 *   @type  int  $donation_id The donation ID.
 * }
 *
 * @return     bool    True if orphaned donation exists, False otherwise.
 */
function orphaned_donation_exists( $args ){
  global $wpdb;
  $args = shortcode_atts([
    'contact_id'  => null,
    'donation_id' => null,
  ], $args );

  if( is_null( $args['contact_id'] ) || ! is_numeric( $args['contact_id'] ) )
      return false;

  if( is_null( $args['donation_id'] ) || ! is_numeric( $args['donation_id'] ) )
      return false;
  $sql = $wpdb->prepare( "SELECT count( ID ) AS total FROM {$wpdb->prefix}donman_orphaned_donations WHERE contact_id=%d AND donation_id=%d", $args['contact_id'], $args['donation_id'] );
  $result = $wpdb->get_results( $sql );
  if ( $wpdb->last_error )
    \WP_CLI::error( '🚨 wpdb error: ' . $wpdb->last_error );

  $total = $result[0]->total;
  $exists = ( $total > 0 )? true : false ;
  return $exists;
}

/**
 * Saves a donation hash.
 *
 * @param      int  $ID     The Donation ID.
 *
 * @return     mixed    Returns Donation Hash upon successful save or FALSE.
 */
function save_donation_hash( $ID = null ){
  if( is_null( $ID ) || empty( $ID ) || ! is_numeric( $ID ) )
    return false;

  $postype = get_post_type( $ID );
  if( 'donation' != $postype )
    return false;

  $donation = get_post( $ID );
  if( $donation ){
    $post_date = $donation->post_date;
    $zipcode = get_donation_zip_code( $ID );
    $donation_hash = wp_hash_password( $post_date . $zipcode );
    update_post_meta( $ID, 'donation_hash', $donation_hash );
    return $donation_hash;
  }
}

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
