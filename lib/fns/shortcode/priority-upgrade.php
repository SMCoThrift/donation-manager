<?php
namespace DonationManager\shortcodes;
use const DonationManager\orphanfollowup\{UPGRADE_PAGE,UPGRADED_META};
use function DonationManager\orphanfollowup\{check_upgrade_link,get_partners};
use function DonationManager\donations\{get_donation_data};
use function DonationManager\utilities\{get_alert};

/**
 * The page the orphan follow-up email's buttons open: asks the donor to
 * confirm sending their donation to a priority partner. The Send buttons
 * post back to the same page (see orphanfollowup\handle_confirm()).
 *
 * Styled by lib/css/priority-upgrade.css, which loads only on this page.
 *
 * @return string HTML.
 */
function priority_upgrade(){
  wp_enqueue_style( 'form' );
  $args = wp_unslash( array_intersect_key( $_GET, array_flip( [ 'd', 'o', 't', 'k' ] ) ) );
  $link = check_upgrade_link( $args );
  if( is_wp_error( $link ) )
    return upgrade_panel( 'warning', 'We couldn\'t open this request', '<p>' . esc_html( $link->get_error_message() ) . ' Please use the button in your email, or <a href="' . esc_url( home_url( '/' ) ) . '">start a new donation</a>.</p>' );

  $donation = $link['donation'];
  $partner = $link['partner'];
  $partner_name = esc_html( $partner['name'] );
  $data = get_donation_data( $donation );

  if( get_post_meta( $donation->ID, UPGRADED_META, true ) ){
    $org_id = (int) get_post_meta( $donation->ID, 'organization', true );
    if( $org_id != $partner['id'] )
      return upgrade_panel( 'info', 'Already sent', '<p>Your request has already been sent to ' . esc_html( get_the_title( $org_id ) ) . '. They\'ll contact you soon.</p>', $org_id );

    $body = '<p>They\'ll contact you soon ' . esc_html( contact_phrase( $data ) ) . '. Nothing is scheduled until they do.</p>';
    $body.= '<p>We\'ve also emailed you a copy of your request for your records.</p>';
    $body.= '<p><a href="' . esc_url( home_url( '/' ) ) . '">Back to PickUpMyDonation.com</a></p>';
    return upgrade_panel( 'success', 'Done! Your request has been sent to ' . $partner_name . end_punctuation( $partner['name'], '.' ), $body, $partner['id'] );
  }

  if( ! in_array( $partner['id'], wp_list_pluck( get_partners( $donation->ID ), 'id' ) ) )
    return upgrade_panel( 'info', 'This request can\'t be sent', '<p>' . $partner_name . ' no longer serves this zip code. Please <a href="' . esc_url( home_url( '/' ) ) . '">start a new donation</a>.</p>' );

  $errors = [
    'partner' => 'This partner no longer serves your zip code.',
    'busy'    => 'Your request is being sent. Please reload this page in a minute.',
  ];
  $html = '';
  if( isset( $_GET['error'] ) && isset( $errors[ $_GET['error'] ] ) )
    $html.= get_alert( [ 'type' => 'warning', 'description' => '<p>' . $errors[ $_GET['error'] ] . '</p>' ] );

  $hidden = '';
  foreach( $args as $name => $value )
    $hidden.= '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';
  $button = '<button type="submit" class="elementor-button donman-upgrade-button">Send my request to ' . $partner_name . '</button>';

  // Both buttons disable on submit, so a double click can't post twice.
  $html.= '<form class="donman-upgrade" method="post" action="" onsubmit="this.querySelectorAll(\'button\').forEach(function(b){b.disabled=true;b.textContent=\'Sending…\';});">' . $hidden . '<input type="hidden" name="donman_priority_upgrade" value="1">';
  $html.= '<h1 class="donman-upgrade-heading">Send your donation to <span class="donman-upgrade-nowrap">' . $partner_name . end_punctuation( $partner['name'], '?' ) . '</span></h1>';
  $html.= '<div class="donman-upgrade-head"><div class="donman-upgrade-title">' . partner_logo( $partner['id'] ) . '</div><div class="donman-upgrade-action">' . $button . '</div></div>';
  $html.= '<div class="donman-upgrade-fee"><strong>' . $partner_name . ' is a fee-based pickup service.</strong> They\'ll contact you to confirm the price and a pickup time before anything is scheduled.</div>';
  $html.= donation_summary( $data, $partner_name );
  $html.= str_replace( 'donman-upgrade-button', 'donman-upgrade-button donman-upgrade-button-block', $button );
  $html.= '<p class="donman-upgrade-note">Nothing is scheduled until ' . $partner_name . ' contacts you. Changed your mind? Just close this page, and nothing will be sent.</p>';
  $html.= '</form>';

  return $html;
}
add_shortcode( 'priority-upgrade', __NAMESPACE__ . '\\priority_upgrade' );

/**
 * A status panel: done, already sent, or a problem with the link.
 *
 * @param string   $type   `success`, `info` or `warning`.
 * @param string   $title  Heading (escaped).
 * @param string   $body   HTML.
 * @param int|null $org_id Show this organization's logo.
 * @return string HTML.
 */
function upgrade_panel( $type, $title, $body, $org_id = null ){
  return '<div class="donman-upgrade-panel donman-upgrade-panel-' . esc_attr( $type ) . '">' . ( $org_id? partner_logo( $org_id ) : '' ) . '<h1>' . $title . '</h1>' . $body . '</div>';
}

/**
 * @param int $org_id Organization ID.
 * @return string The organization's logo (featured image), or ''.
 */
function partner_logo( $org_id ){
  $url = get_the_post_thumbnail_url( $org_id, 'medium' );
  return $url? '<img class="donman-upgrade-logo" src="' . esc_url( $url ) . '" alt="' . esc_attr( get_the_title( $org_id ) ) . '">' : '';
}

/**
 * Punctuation to end a sentence with a partner's name, unless the name
 * already ends in some (1-800-GOT-JUNK?).
 *
 * @param string $name The partner's name.
 * @param string $mark `.` or `?`.
 * @return string
 */
function end_punctuation( $name, $mark ){
  return in_array( substr( $name, -1 ), [ '?', '.', '!' ] )? '' : $mark;
}

/**
 * How the partner will contact the donor, e.g. "by phone at (555) 555-1234".
 *
 * @param array $data From get_donation_data().
 * @return string
 */
function contact_phrase( $data ){
  return ( 'Email' == $data['preferred_contact_method'] )? 'by email at ' . $data['email'] : 'by phone at ' . $data['phone'];
}

/**
 * The donor-facing summary of a donation: what the partner will get.
 *
 * @param array  $data         From get_donation_data().
 * @param string $partner_name Escaped partner name.
 * @return string HTML.
 */
function donation_summary( $data, $partner_name ){
  $address = ( 'Yes' == $data['different_pickup_address'] && ! empty( $data['pickup_address'] ) )? $data['pickup_address'] : $data['address'];

  $rows = [];
  $items = is_array( $data['items'] )? implode( ', ', $data['items'] ) : '';
  $description = trim( (string) $data['description'] );
  $rows['Items'] = '<strong>' . esc_html( $items ) . '</strong>' . ( $description? '<br>' . nl2br( esc_html( stripslashes( $description ) ) ) : '' );
  $rows['Pickup address'] = esc_html( $address['address'] ) . '<br>' . esc_html( $address['city'] . ', ' . $address['state'] . ' ' . $address['zip'] );
  if( ! empty( $data['pickuplocation'] ) )
    $rows['Pickup address'].= '<br><span class="donman-upgrade-muted">Items are: ' . esc_html( $data['pickuplocation'] ) . '</span>';
  $rows['How they\'ll contact you'] = esc_html( ucfirst( contact_phrase( $data ) ) );

  $dates = [];
  for( $i = 1; $i <= 3; $i++ ){
    if( ! empty( $data[ 'pickupdate' . $i ] ) )
      $dates[] = esc_html( $data[ 'pickupdate' . $i ] . ', ' . $data[ 'pickuptime' . $i ] );
  }
  if( $dates )
    $rows['Dates you suggested'] = implode( '<br>', $dates ) . '<br><span class="donman-upgrade-muted">' . $partner_name . ' will contact you to confirm a time.</span>';

  $html = '<div class="donman-upgrade-summary"><h2>Your request</h2><dl>';
  foreach( $rows as $label => $value )
    $html.= '<dt>' . esc_html( $label ) . '</dt><dd>' . $value . '</dd>';
  $html.= '</dl></div>';

  return $html;
}

/**
 * Page setup for the upgrade page: its stylesheet, a body class, no theme
 * page title (the form has its own heading), and no search indexing.
 */
function priority_upgrade_page_setup(){
  if( ! is_page( UPGRADE_PAGE ) )
    return;

  wp_enqueue_style( 'donman-priority-upgrade', DONMAN_PLUGIN_URL . 'lib/css/priority-upgrade.css', null, filemtime( DONMAN_PLUGIN_PATH . 'lib/css/priority-upgrade.css' ) );
  add_filter( 'body_class', function( $classes ){
    $classes[] = 'donman-priority-upgrade';
    return $classes;
  } );
  add_filter( 'hello_elementor_page_title', '__return_false' );
  add_filter( 'wp_robots', 'wp_robots_no_robots' );
  add_filter( 'wpseo_robots', function(){
    return 'noindex, nofollow';
  } );
}
add_action( 'template_redirect', __NAMESPACE__ . '\\priority_upgrade_page_setup' );
