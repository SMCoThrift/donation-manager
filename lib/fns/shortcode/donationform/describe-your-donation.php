<?php
use function DonationManager\templates\{render_template};
use function DonationManager\globals\{add_html,get_html};
use function DonationManager\utilities\{get_alert};
use function DonationManager\organizations\{get_organization_meta_array};
use function DonationManager\realtors\{get_realtor_ads};

$org_id = $_SESSION['donor']['org_id'];
$pickup_settings = get_field( 'pickup_settings', $org_id );

uber_log('🔔 $org_id = ' . $org_id );

$step_one_notice = get_field( 'pickup_settings_step_one_notice', $org_id );
if( ! empty( $step_one_notice ) ){
  $step_one_notice = get_alert([
    'description' => $step_one_notice,
    'type'        => 'info',
  ]);
}

if( true == $_SESSION['donor']['priority'] ){
  $step_one_alert = get_alert([
    'type'        => 'warning',
    'title'       => 'Please Read',
    'css_classes' => 'large-title',
    'description' => 'You have selected our <strong>Expedited Pick Up Service</strong>.  Your request will be sent to our <strong>Fee Based</strong> pick up partners (<em>fee to be determined by the pick up provider</em>) who will in most cases be able to handle your request within 24 hours, bring quality donations to a local non-profit, and help you dispose of unwanted and/or unsellable items.  <br/><br/>If you reached this page in error, <a href="' . site_url() . '/select-your-organization/?pcode=' . $_SESSION['donor']['pickup_code'] . '&priority=0">CLICK HERE</a> and select <em>Free Pick Up</em>.',
  ]);
}
if( isset( $step_one_alert ) )
  $step_one_notice = $step_one_alert . $step_one_notice;

$donation_options = [];

// Get alternate Donation Option Descriptions
$alt_donation_option_descriptions = [];
if( have_rows( 'donation_option_descriptions', $org_id ) ){
  while( have_rows( 'donation_option_descriptions', $org_id ) ): the_row();
    $id = get_sub_field( 'donation_option_id' );
    $desc = get_sub_field( 'description' );
    $alt_donation_option_descriptions[ $id[0] ] = $desc;
  endwhile;
}
//uber_log( '🔔 $alt_donation_option_descriptions = ' . print_r( $alt_donation_option_descriptions, true ) );

$terms = wp_get_post_terms( $org_id, 'donation_option' );
if( empty( $terms ) ){
  $terms = get_field( 'default_options_default_donation_options', 'option' );
}

foreach( $terms as $term ) {
  /**
   * $donation_option_desc
   *
   * Populate our Donation Option descriptions by:
   *
   *   1) checking for an Alternate Donation Option desc in $alt_donation_option_descriptions
   *   2) the description that comes with the term.
   *
   * @var        string
   */
  $donation_option_desc = ( array_key_exists( $term->term_id, $alt_donation_option_descriptions ) )? wpautop( $alt_donation_option_descriptions[ $term->term_id ] ) : wpautop( $term->description );

  if( empty( $donation_option_desc ) && current_user_can( 'edit_posts' ) )
    $donation_option_desc = get_alert(['description' => 'No description entered for "' . $term->name . '". <a href="' . get_edit_term_link( $term, 'donation_option' ) . '" target="_blank">Edit</a> this term.']);

  $pickup = get_field( 'pickup', 'donation_option_' . $term->term_id );
  $skip_questions = get_field( 'skip_questions', 'donation_option_' . $term->term_id );

  $donation_options[] = [
    'name'            => $term->name,
    'desc'            => $donation_option_desc,
    'value'           => esc_attr( $term->name ),
    'pickup'          => $pickup,
    'skip_questions'  => $skip_questions,
    'term_id'         => $term->term_id
  ];
}

$checkboxes = array();

foreach( $donation_options as $key => $opt ) {
  $checked = '';
  if( isset( $_SESSION['donor']['items'][$opt['term_id']] ) )
      $checked = ' checked="checked"';
  if( isset( $_POST['donor'] ) && array_key_exists( 'options', $_POST['donor'] ) && trim( $_POST['donor']['options'][$key]['field_value'] ) == $opt['value'] )
      $checked = ' checked="checked"';

  $checkboxes[] = [
    'key' => $key,
    'value' => $opt['value'],
    'checked' => $checked,
    'name' => html_entity_decode( $opt['name'] ),
    'desc' => $opt['desc'],
    'pickup' => $opt['pickup'],
    'skip_questions' => $opt['skip_questions'],
    'term_id' => $opt['term_id'],
  ];
}

$description = '';
if( isset( $_SESSION['donor']['description'] ) )
    $description = esc_textarea( $_SESSION['donor']['description'] );
if( isset( $_POST['donor']['description'] ) )
    $description = esc_textarea( $_POST['donor']['description'] );

$hbs_vars = [
    'checkboxes' => $checkboxes,
    'step_one_notice' => $step_one_notice,
    'description' => $description,
    'nextpage' => $nextpage
];
if( empty( $template ) )
    $template = 'form2.donation-options-form';
$html = render_template( $template, $hbs_vars );
add_html( $html );

// Add Realtor Ads to the bottom of the form.
$realtor_ads = get_realtor_ads([ $org_id ]);
if( $realtor_ads && 0 < count( $realtor_ads ) ){
  foreach( $realtor_ads as $ad ){
    add_html($ad);
  }
}