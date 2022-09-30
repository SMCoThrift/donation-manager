<?php
use function DonationManager\organizations\{get_priority_pickup_links};
use function DonationManager\stores\{get_stores_footer};
use function DonationManager\globals\{add_html};
$no_pickup_message = get_field( 'no_pickup_message', 'option' );

$organization = get_the_title( $_SESSION['donor']['org_id'] );

// Priority Donation Backlinks
$priority_html = get_priority_pickup_links( $_SESSION['donor']['pickup_code'] );

$search = array( '{organization}', '{priority_pickup_option}' );
$replace = array( $organization, $priority_html );
$html.= str_replace( $search, $replace, $no_pickup_message );
$html.= get_stores_footer( $_SESSION['donor']['trans_dept_id'] );
add_html( $html );