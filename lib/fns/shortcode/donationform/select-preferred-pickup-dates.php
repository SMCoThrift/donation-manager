<?php
use function DonationManager\organizations\{get_pickuptimes,get_pickuplocations,get_priority_pickup_links,is_orphaned_donation};
use function DonationManager\utilities\{get_alert};
use function DonationManager\templates\{render_template};
use function DonationManager\realtors\{get_realtor_ads};
use function DonationManager\globals\{add_html};

if( ! isset( $_SESSION['donor']['org_id'] ) ){
  if( current_user_can( 'activate_plugins' ) ){
    add_html( 
      get_alert([
        'type' => 'danger',
        'description' => 'No <code>$_SESSION[\'donor\'][\'org_id\']</code> set! Aborting Preferred Pick Up Date processing...'
      ])
    );
  }
  return;
} 

$pickuptimes = get_pickuptimes( $_SESSION['donor']['org_id'] );

for ( $i=1; $i < 4; $i++ ) {
  $datevalue = (
    isset( $_POST['donor']['pickupdate' . $i] )
    && preg_match( '/(([0-9]{2})\/([0-9]{2})\/([0-9]{4}))/', $_POST['donor']['pickupdate' . $i] )
  )? $_POST['donor']['pickupdate' . $i] : '';

  $pickupdates[$i] = [
    'value' => $datevalue,
  ];

  $timevalue = ( isset( $_POST['donor']['pickuptime' . $i ] ) )? $_POST['donor']['pickuptime' . $i ] : '';

  foreach( $pickuptimes as $id => $time ){
    $checked = ( $timevalue == $time['name'] )? true : false ;
    $pickupdates[$i]['times'][] = [
      'value'   => $time['name'],
      'checked' => $checked,
    ];
  }
}
//uber_log( $pickupdates, '$pickupdates' );

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

// Priority Donation Backlinks
$priority_html = '';
if( false == $_SESSION['donor']['priority'] ){
  $priority_html = get_alert([
    'type'        => 'info',
    'description' => '<strong>Priority Pick Up Option:</strong> <em>Need expedited service?</em> <a href="#" id="show-priority">Click for details &rarr;</a>',
    'css_classes' => 'priority-alert'
  ]);
  $priority_html.= get_alert([
    'type'        => 'warning',
    'title'       => 'Priority Pick Up Option',
    'css_classes' => 'large-title elementor-alert-hidden priority-note',
    'description' => '<p>We work as hard as we can to serve all of our donors in a timely fashion. If you need expedited service or you don\'t see a time that works in our calendar, click below to request a pick up from a priority pick up provider. Priority pickup providers are payment based service providers and will discuss fees upon contacting you.</p>' . get_priority_pickup_links( $_SESSION['donor']['pickup_code'] ),
    'dismissable' => true,
  ]);
}

$hbs_vars = [
    /*'pickupdays' => $days,*/
    'priority_pickup_option' => $priority_html,
    'pickuplocations' => $locations,
    'nextpage' => $nextpage,
    'pickupdates' => $pickupdates,
];
$hbs_vars['date_note'] = get_alert([
  'type' => 'warning',
  'description' => '<p><strong>Please note:</strong> <em>NONE</em> of the dates and times you select are confirmed until our schedulers are able to contact you directly.</p>'
]);
$hbs_vars['sms_consent_note'] = get_alert([
  'type' => 'info',
  'title' => 'Communications Consent',
  'description' => 'By submitting this form, you consent to being contacted by our partners via SMS, phone call, or email. You may receive a call outside of standard calling hours such as evenings or weekends.',
]);

// Setup HBS vars for Pick Up Provider Choice on Orphaned Donations
if( is_orphaned_donation( $_SESSION['donor']['trans_dept_id'] ) ){
  $hbs_vars['orphaned_donation'] = true;

  $hbs_vars['checked_fee_based_true'] = '';
  $hbs_vars['checked_fee_based_false'] = '';
  if( isset( $_POST['donor']['fee_based'] ) ){
    $hbs_vars['fee_based'] = $_POST['donor']['fee_based'];
    if( $hbs_vars['fee_based'] ){
      $hbs_vars['checked_fee_based_true'] = ' checked="checked"';
    } else {
      $hbs_vars['checked_fee_based_false'] = ' checked="checked"';
    }
  } else {
    $hbs_vars['fee_based'] = ( isset( $_SESSION['donor']['fee_based'] ) )? $_SESSION['donor']['fee_based'] : true ;
    $hbs_vars['checked_fee_based_true'] = ' checked="checked"';
  }
}

if( empty( $template ) )
  $template = 'form5.pickup-dates';
$html = render_template( $template, $hbs_vars );
add_html( $html );

// Add Realtor Ads to the bottom of the form.
$realtor_ads = get_realtor_ads([ $_SESSION['donor']['org_id'] ]);
if( $realtor_ads && 0 < count( $realtor_ads ) ){
  foreach( $realtor_ads as $ad ){
    add_html($ad);
  }
}