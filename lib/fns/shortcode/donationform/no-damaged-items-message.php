<?php
use function DonationManager\templates\{render_template};
use function DonationManager\globals\{add_html};
use function DonationManager\organizations\{get_priority_pickup_links};
use function DonationManager\stores\{get_stores_footer};

$no_damaged_items_message = wpautop( get_field( 'no_damaged_items_message', 'option' ) );

$organization = get_the_title( $_SESSION['donor']['org_id'] );

// Priority Donation Backlinks
// 08/16/2022 (11:05) - TODO refactor DonationManager\organizations\get_priority_pickup_links();
$priority_html = get_priority_pickup_links( $_SESSION['donor']['pickup_code'] );

$search = array( '{organization}', '{priority_pickup_option}' );
$replace = array( $organization, $priority_html );
$html = str_replace( $search, $replace, $no_damaged_items_message );

$html.= get_stores_footer( $_SESSION['donor']['trans_dept_id'], false );
add_html( $html );