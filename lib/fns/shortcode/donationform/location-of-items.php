<?php
use function DonationManager\organizations\{get_pickuptimes,get_pickuplocations,get_priority_pickup_links};
use function DonationManager\utilities\{get_alert};
use function DonationManager\templates\{render_template};
use function DonationManager\realtors\{get_realtor_ads};
use function DonationManager\globals\{add_html};

$organization = get_the_title( $_SESSION['donor']['org_id'] );
$pickuplocations = get_pickuplocations( $_SESSION['donor']['org_id'] );
foreach( $pickuplocations as $key => $location ){
    $checked = ( isset( $_POST['donor']['pickuplocation'] ) && $location['name'] == $_POST['donor']['pickuplocation'] )? ' checked="checked"' : '';
    $locations[] = [
        'key' => $key,
        'location' => $location['name'],
        'location_attr_esc' => esc_attr( $location['name'] ),
        'checked' => $checked,
    ];
}

$hbs_vars = [
    'nextpage' => $nextpage,
    'pickuplocations' => $locations,
    'organization' => $organization,
];

if( empty( $template ) )
    $template = 'form5.location-of-items';
$html = render_template( $template, $hbs_vars );

add_html( $html );