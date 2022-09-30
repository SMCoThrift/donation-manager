<?php
use function DonationManager\templates\{render_template};
use function DonationManager\globals\{add_html,get_html};
use function DonationManager\utilities\{get_alert,get_posted_var};
use function DonationManager\realtors\{get_realtor_ads};
use function DonationManager\helpers\{get_donation_reason_select,get_state_select};

$checked_yes = '';
$checked_no = '';
if( isset( $_POST['donor']['different_pickup_address'] ) ) {
    if( 'Yes' == $_POST['donor']['different_pickup_address'] ) {
        $checked_yes = ' checked="checked"';
    } else {
        $checked_no = ' checked="checked"';
    }
} else {
    if ( isset( $_SESSION['donor']['different_pickup_address'] ) ){
         if( 'Yes' == $_SESSION['donor']['different_pickup_address'] ) {
            $checked_yes = ' checked="checked"';
        } else {
            $checked_no = ' checked="checked"';
        }
    } else {
        $checked_no = ' checked="checked"';
    }
}

$checked_phone = '';
$checked_email = '';
if( isset( $_POST['donor']['preferred_contact_method'] ) ) {
    if( 'Phone' == $_POST['donor']['preferred_contact_method'] ) {
        $checked_phone = ' checked="checked"';
    } else {
        $checked_email = ' checked="checked"';
    }
} else {
    if( isset( $_SESSION['donor']['preferred_contact_method'] ) ){
        if( 'Phone' == $_SESSION['donor']['preferred_contact_method'] ) {
            $checked_phone = ' checked="checked"';
        } else {
            $checked_email = ' checked="checked"';
        }
    } else {
        $checked_email = ' checked="checked"';
    }
}

$posted_vars = [
  'first_name' => 'donor:address:name:first',
  'last_name' => 'donor:address:name:last',
  'address' => 'donor:address:address',
  'city' => 'donor:address:city',
  'zip' => 'donor:address:zip',
  'pickup_address' => 'donor:pickup_address:address',
  'pickup_address_city' => 'donor:pickup_address:city',
  'pickup_address_zip' => 'donor:pickup_address:zip',
  'donor_email' => 'donor:email',
  'donor_phone' => 'donor:phone',
  'donor_preferred_code' => 'donor:preferred_code',
  'donor_company' => 'donor:address:company'
];
foreach( $posted_vars as $key => $var ){
  $$key = get_posted_var( $var );
}

if( ! isset( $_POST['donor']['address']['state'] ) && isset( $_SESSION['donor']['address']['state'] ) ){
  $_POST['donor']['address']['state'] = $_SESSION['donor']['address']['state'];
}
if( ! isset( $_POST['donor']['pickup_address']['state'] ) && isset( $_SESSION['donor']['pickup_address']['state'] ) ){
  $_POST['donor']['pickup_address']['state'] = $_SESSION['donor']['pickup_address']['state'];
}

$hbs_vars = [
  'nextpage' => $nextpage,
  'state' => get_state_select(),
  'pickup_state' => get_state_select( 'pickup_address' ),
  'checked_yes' => $checked_yes,
  'checked_no' => $checked_no,
  'checked_phone' => $checked_phone,
  'checked_email' => $checked_email,
  'donor_company' => $donor_company,
  'donor_name_first' => $first_name,
  'donor_name_last' => $last_name,
  'donor_address' => $address,
  'donor_city' => $city,
  'donor_zip' => $zip,
  'donor_pickup_address' => $pickup_address,
  'donor_pickup_city' => $pickup_address_city,
  'donor_pickup_zip' => $pickup_address_zip,
  'donor_email' => $donor_email,
  'donor_phone' => $donor_phone,
  'preferred_code' => $donor_preferred_code,
  'reason_option' => get_donation_reason_select(),
];

if( $allow_user_photo_uploads && isset( $_SESSION['donor']['image'] ) )
{
    $uploaded_image = '';
    $images = $_SESSION['donor']['image'];
    foreach( $images as $image ){
        $uploaded_image.= cl_image_tag( $image['public_id'], [
            'format' => 'jpg',
            'cloud_name' => CLOUDINARY_CLOUD_NAME,
            'crop' => 'fill',
            'width' => 200,
            'height' => 120,
            'style' => 'margin: 0 10px 10px 0; border: 1px solid #eee;',
        ]);
    }
    $hbs_vars['uploaded_image'] = $uploaded_image;
}


if( empty( $template ) )
    $template = 'form4.contact-details-form';
$html = render_template( $template, $hbs_vars );
add_html( $html );

// Add Realtor Ads to the bottom of the form.
$realtor_ads = get_realtor_ads([ $_SESSION['donor']['org_id'] ]);
if( $realtor_ads && 0 < count( $realtor_ads ) ){
    foreach( $realtor_ads as $ad ){
        add_html($ad);
    }
}