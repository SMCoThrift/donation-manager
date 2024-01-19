<?php
use function DonationManager\templates\{render_template};
use function DonationManager\globals\{add_html};
use function DonationManager\organizations\{get_priority_pickup_links};
use function DonationManager\stores\{get_stores_footer};

$no_damaged_items_message = wpautop( get_field( 'no_damaged_items_message', 'option' ) );

$organization = get_the_title( $_SESSION['donor']['org_id'] );

// Priority Donation Backlinks
// 08/16/2022 (11:05) - TODO refactor DonationManager\organizations\get_priority_pickup_links();
$priority_html = (
  isset( $_SESSION['donor'] )
  && is_array( $_SESSION['donor'] )
  && array_key_exists( 'pickup_code', $_SESSION['donor'] )
)? get_priority_pickup_links( $_SESSION['donor']['pickup_code'] ) : false ;

$search = array( '{organization}', '{priority_pickup_option}', '{store_signature}' );
$replace = array( $organization, $priority_html, get_stores_footer( $_SESSION['donor']['trans_dept_id'], false ) );
$html = str_replace( $search, $replace, $no_damaged_items_message );

add_html( $html );