<?php

namespace DonationManager\stores;
use function DonationManager\templates\{render_template};
use function DonationManager\transdepts\{get_trans_dept_contact};
use function DonationManager\utilities\{get_alert};

/**
 * Retrieves HTML for showing Trans Dept Contact and all Stores for Trans Dept.
 */
function get_stores_footer( $trans_dept_id, $get_stores = true ) {
  $html = '';
  // Get our trans dept director
  $trans_dept_contact = get_trans_dept_contact( $trans_dept_id );
  if( empty( $trans_dept_contact['contact_email'] ) ) {
      $html.= get_alert(['type' => 'danger', 'description' => 'ERROR: No <code>contact_email</code> defined. Please inform support of this error.']);
  } else {
    $organization = ( isset( $_SESSION['donor']['org_id'] ) )? get_the_title( $_SESSION['donor']['org_id'] ) : null ;
    $data = [
      'name'          => $trans_dept_contact['contact_name'],
      'email'         => $trans_dept_contact['contact_email'],
      'organization'  => $organization,
      'title'         => $trans_dept_contact['contact_title'],
      'phone'         => $trans_dept_contact['phone'],
      'stores'        => null,
    ];

    if( false == $get_stores ){
      $html = render_template( 'no-pickup', $data );
      return $html;
    }

    // Query the Transportation Department's stores
    $args = array(
      'post_type' => 'store',
      'posts_per_page' => -1,
      'meta_query' => array(
        array(
          'key' => 'trans_dept',
          'value' => $trans_dept_id,
        )
      )
    );
    $stores = get_posts( $args );
    if( $stores ) {
      foreach( $stores as $store ){
        $store_data = get_field( 'address', $store->ID );
        $data['stores'][] = [
          'name'      => $store->post_title,
          'address'   => $store_data['street'],
          'city'      => $store_data['city'],
          'state'     => $store_data['state'],
          'zip_code'  => $store_data['zip_code'],
          'phone'     => $store_data['phone'],
        ];
      }
    }
  }

  $html = render_template( 'no-pickup', $data );
  return $html;
}