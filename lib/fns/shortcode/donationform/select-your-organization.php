<?php
use function DonationManager\templates\{render_template};
use function DonationManager\globals\{add_html,get_html};
use function DonationManager\utilities\{get_alert};
use function DonationManager\organizations\{get_organizations,get_default_organization,get_orphaned_donation_contacts};
use function DonationManager\transdepts\{get_trans_dept_ads};
use function DonationManager\realtors\{get_realtor_ads};

$ads = [];
$pickup_code = $_REQUEST['pcode'];

$organizations = get_organizations( $pickup_code );

if( false == $organizations || 0 == count( $organizations ) ){
    // Default non-profit org
    $default_org[0] = get_default_organization();
    // Add orphaned pick up providers
    if( ! isset( $default_org[0]['providers'] ) ){
      $orphaned_pickup_radius = get_field( 'orphaned_pickup_radius', 'option' );
      $default_org[0]['providers'] = get_orphaned_donation_contacts( [ 'pcode' => $pickup_code, 'radius' => $orphaned_pickup_radius, 'priority' => 0, 'fields' => 'store_name,email_address,zipcode,priority', 'duplicates' => false, 'show_in_results' => 1 ] );
    }

    $organizations[] = $default_org[0];

    // Default priority org
    $default_priority_org = get_default_organization( true );

    // Only provide the PRIORITY option for areas where there is
    // a priority provider in the contacts table.
    $contacts = get_orphaned_donation_contacts( array( 'pcode' => $pickup_code, 'limit' => 1, 'priority' => 1 ) );

    if( is_array( $contacts ) && 0 < count( $contacts ) )
        $organizations[] = $default_priority_org[0];
}

if( 0 == count( $organizations ) )
    add_html( get_alert(['title' => 'No default organization found!', 'type' => 'warning', 'description' => 'No default organization has been specified in the Donation Manager settings.']) );

/**
 * When `?message=no_org_transdept`, we show the following to
 * users who have reached the last form without having an
 * org/trans_dept saved in $_SESSION['donor']:
 */
if( isset( $_REQUEST['message'] ) && ! empty( $_REQUEST['message'] ) && 'no_org_transdept' == $_REQUEST['message'] )
  add_html( get_alert([
    'type' => 'danger',
    'description' => 'We are sorry, but somehow you reached the end of our donation process without having an organization saved for your donation details. Because of this error, we have redirected you back to the "Select Your Organization" screen based off of the ZIP code for your pickup address.<br /><br />If you have any questions, or if you can provide any further details to us, please email <a href="mailto:webmaster@pickupmydonation.com">webmaster@pickupmydonation.com</a>.'
  ]) );

$priority_rows = [];
$priority_ads = [];

foreach( $organizations as $org ) {
    // Setup button link
    $link = '';

    if(
      isset( $org['alternate_donate_now_url'] )
      && filter_var( $org['alternate_donate_now_url'], FILTER_VALIDATE_URL )
    ){
      $link = $org['alternate_donate_now_url'];
    } else if( array_key_exists( 'id', $org ) && array_key_exists( 'trans_dept_id', $org ) ) {
      $link = $nextpage . '?oid=' . $org['id'] . '&tid=' . $org['trans_dept_id'];
    }

    if( isset( $org['priority_pickup'] ) && true == $org['priority_pickup'] && ! stristr( $link, '&priority=1') ){
        $link.= '&priority=1';
    } else if( isset( $org['priority_pickup'] ) &&  false == $org['priority_pickup'] && ! stristr( $link, '&priority=0' ) ) {
        $link.= '&priority=0';
    } else if( ! stristr( $link, '&priority=0' ) ) {
        $link.= '&priority=0';
    }

    $css_classes = array();
    if( isset( $org['priority_pickup'] ) && true == $org['priority_pickup'] )
        $css_classes[] = 'priority';

    // Setup button text
    $donation_button_text = get_field( 'donation_button_text', 'option' );
    if( isset( $org['button_text'] ) ){
        $button_text = $org['button_text'];
    } else if ( isset( $org['priority_pickup'] ) && $org['priority_pickup'] ){
        $button_text = $donation_button_text['priority'];
    } else {
        $button_text = $donation_button_text['non_profit'];
    }

    $css_classes = ( 0 < count( $css_classes ) ) ? ' ' . implode( ' ', $css_classes ) : '';

    //$replace = array( $org['name'], $org['desc'], $link, $button_text, $css_classes ); // 02/21/2017 (16:20) - unused/legacy code?

    $row = [
        'css_classes'   => $css_classes,
        'link'          => $link,
        'button_text'   => $button_text,
        'name'          => $org['name'],
        'desc'          => $org['desc'],
        'pause_pickups' => $org['pause_pickups'],
        'org_id'        => $org['id'],
        'edit_url'      => $org['edit_url'],
    ];
    if( isset( $org['providers'] ) && ! empty( $org['providers'] ) )
        $row['providers'] = $org['providers'];

    /**
     * DEPRECATED/UNUSED
     *
     * 08/04/2022 (10:28) - It appears that we DO NOT use Transportation
     * Department ads currently. For example, we have the KARM trans
     * dept with an ad for volunteering, but that ad isn't showing
     * currently on pickupmydonation.com
     *
     * Therefore, we may remove this code at some point in the future.
     */
    /*
    if( false !== ( $ads = get_trans_dept_ads( $org['trans_dept_id'] ) ) )
        $row['ads'] = $ads;
    unset( $ads );
    //*/

    if( isset( $org['priority_pickup'] ) && $org['priority_pickup'] ){
        $priority_rows[] = $row;
    } else {
        $rows[] = $row;
    }
}
if( ! is_array( $rows ) )
    $rows = [];

/**
 * REALTOR ADS
 *
 * We are feeding our array of FREE /Non-Profit Pickup
 * Providers (i.e. $rows) to get_realtor_ads().
 */
$realtor_ads = get_realtor_ads( $rows );

if( 0 < count( $priority_rows ) )
    $rows = array_merge( $rows, $priority_rows );

$hbs_vars = [ 'rows' => $rows ];
if( empty( $template ) )
    $template = 'form1.select-your-organization';
$html = render_template( $template, $hbs_vars );
add_html( $html );

/**
 * Adding REALTOR Ads below our Organizations.
 */
if( $realtor_ads && 0 < count( $realtor_ads ) ){
  foreach( $realtor_ads as $ad ){
    add_html( $ad );
  }
}
