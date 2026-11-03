<?php

namespace DonationManager\orphanfollowup;
use function DonationManager\organizations\{get_priority_organizations,is_priority};
use function DonationManager\donations\{get_donation_zip_code,get_donation_data};
use function DonationManager\templates\{render_template};
use function DonationManager\emails\{send_email};

/**
 * Follow-up email to orphaned donors who turned down fee-based pickup, and
 * the "upgrade" to priority pickup it offers.
 *
 * A donation is orphaned when it went to the default trans dept (see
 * is_orphaned_donation()), and the donor turned down fee-based pickup when
 * `fee_based` is `0`. Some hours later (the `orphan_followup_delay` option,
 * 48 by default) sweep() emails each of those donors once, with a button for
 * every priority partner in their zip. Zips without one get nothing.
 *
 * The button opens the [priority-upgrade] page, which asks the donor to
 * confirm (email scanners open every link, so the click alone mustn't do
 * anything). Confirming moves the same donation to the partner and sends it
 * the normal way: no second donation is created.
 */

const CACHE_GROUP = 'donman_orphan_followup';
const UPGRADE_PAGE = 'priority-upgrade';

/**
 * Donations older than the delay plus this are never emailed, so turning
 * the follow-up on doesn't email the whole backlog.
 */
const WINDOW = DAY_IN_SECONDS;
const BATCH_SIZE = 50;

/**
 * Post meta. The timestamps are local time, Y-m-d H:i:s.
 */
const SENT_META = '_orphan_followup_sent';
const SKIPPED_META = '_orphan_followup_skipped';
const UPGRADED_META = '_priority_upgraded_at';
const SOURCE_META = '_priority_upgrade_source';
const FROM_ORG_META = '_priority_upgraded_from_org';
const FROM_TRANS_DEPT_META = '_priority_upgraded_from_trans_dept';

/**
 * @return bool Whether the follow-up email is on.
 */
function is_enabled(){
  return (bool) get_field( 'orphan_followup', 'option' );
}

/**
 * @return int Hours after the donation before the follow-up goes out.
 */
function get_delay_hours(){
  $delay = get_field( 'orphan_followup_delay', 'option' );
  return ( is_numeric( $delay ) && 0 < $delay )? (int) $delay : 48;
}

/**
 * Donations due a follow-up: orphaned, fee-based declined, past the delay
 * but not older than the delay plus WINDOW, and not yet sent or skipped.
 *
 * @param int $limit Maximum donations to return.
 * @return array Donation IDs, oldest first.
 */
function get_due_donations( $limit = BATCH_SIZE ){
  global $wpdb;

  $default_trans_dept = get_field( 'default_transportation_department', 'option' );
  if( ! $default_trans_dept || ! get_field( 'orphaned_donation_routing', 'option' ) )
    return [];

  $newest = current_time( 'timestamp' ) - get_delay_hours() * HOUR_IN_SECONDS;

  return array_map( 'intval', $wpdb->get_col( $wpdb->prepare(
    "SELECT p.ID
    FROM {$wpdb->posts} AS p
    INNER JOIN {$wpdb->postmeta} AS t ON t.post_id = p.ID AND t.meta_key = 'trans_dept' AND t.meta_value = %s
    INNER JOIN {$wpdb->postmeta} AS f ON f.post_id = p.ID AND f.meta_key = 'fee_based' AND f.meta_value = '0'
    LEFT JOIN {$wpdb->postmeta} AS s ON s.post_id = p.ID AND s.meta_key IN (%s,%s)
    WHERE p.post_type = 'donation' AND p.post_status = 'publish' AND p.post_date >= %s AND p.post_date < %s
      AND s.meta_id IS NULL
    ORDER BY p.post_date
    LIMIT %d",
    $default_trans_dept->ID, SENT_META, SKIPPED_META,
    date( 'Y-m-d H:i:s', $newest - WINDOW ), date( 'Y-m-d H:i:s', $newest ), $limit
  ) ) );
}

/**
 * The priority partners a donation can be upgraded to.
 *
 * get_priority_organizations() falls back to the default organization (which
 * emails every priority contact in the radius); that isn't a partner, so it's
 * left out.
 *
 * @param int $donation_id The donation ID.
 * @return array [ [ 'id', 'name', 'trans_dept_id' ] ].
 */
function get_partners( $donation_id ){
  $zip = get_donation_zip_code( $donation_id );
  if( ! $zip )
    return [];

  $default_org = get_field( 'default_organization', 'option' );
  $partners = [];
  foreach( (array) get_priority_organizations( $zip ) as $org ){
    if( empty( $org['id'] ) || empty( $org['trans_dept_id'] ) )
      continue;
    if( ( $default_org && $default_org->ID == $org['id'] ) || ! is_priority( $org['id'] ) )
      continue;
    $partners[ $org['id'] ] = [
      'id'            => (int) $org['id'],
      'name'          => $org['name'],
      'trans_dept_id' => (int) $org['trans_dept_id'],
    ];
  }

  return array_values( $partners );
}

/**
 * The key that signs an upgrade link.
 *
 * @param int $donation_id   The donation ID.
 * @param int $org_id        The partner's organization ID.
 * @param int $trans_dept_id The partner's trans dept ID.
 * @return string
 */
function get_upgrade_key( $donation_id, $org_id, $trans_dept_id ){
  return substr( hash_hmac( 'sha256', "priority-upgrade|{$donation_id}|{$org_id}|{$trans_dept_id}", wp_salt( 'auth' ) ), 0, 32 );
}

/**
 * The link to the [priority-upgrade] page for one partner.
 *
 * @param int   $donation_id The donation ID.
 * @param array $partner     From get_partners().
 * @return string
 */
function get_upgrade_url( $donation_id, $partner ){
  return add_query_arg( [
    'd' => $donation_id,
    'o' => $partner['id'],
    't' => $partner['trans_dept_id'],
    'k' => get_upgrade_key( $donation_id, $partner['id'], $partner['trans_dept_id'] ),
  ], home_url( '/' . UPGRADE_PAGE . '/' ) );
}

/**
 * Checks an upgrade link's query args.
 *
 * @param array $args `d`, `o`, `t` and `k`, from the link.
 * @return array|\WP_Error [ 'donation' => WP_Post, 'partner' => array ].
 */
function check_upgrade_link( $args ){
  foreach( [ 'd', 'o', 't', 'k' ] as $arg ){
    if( empty( $args[ $arg ] ) )
      return new \WP_Error( 'invalid', 'This link is incomplete.' );
  }

  $donation_id = (int) $args['d'];
  $org_id = (int) $args['o'];
  $trans_dept_id = (int) $args['t'];
  if( ! hash_equals( get_upgrade_key( $donation_id, $org_id, $trans_dept_id ), (string) $args['k'] ) )
    return new \WP_Error( 'invalid', 'This link isn\'t valid.' );

  $donation = get_post( $donation_id );
  if( ! $donation || 'donation' != $donation->post_type || 'publish' != $donation->post_status )
    return new \WP_Error( 'missing', 'We couldn\'t find this donation.' );

  return [
    'donation' => $donation,
    'partner'  => [ 'id' => $org_id, 'name' => get_the_title( $org_id ), 'trans_dept_id' => $trans_dept_id ],
  ];
}

/**
 * Emails a donor the follow-up.
 *
 * @param int         $donation_id The donation ID.
 * @param string|null $to          Send a test copy here instead. Doesn't mark the donation as sent.
 * @return string `sent`, or why not: `no-partner`, `no-email` or `mail-failed`.
 */
function send_followup( $donation_id, $to = null ){
  $partners = get_partners( $donation_id );
  if( ! $partners )
    return 'no-partner';

  $email = get_field( 'donor_email', $donation_id );
  if( ! $to && ! is_email( $email ) )
    return 'no-email';

  $name = trim( get_field( 'donor_name', $donation_id ) );
  $items = get_post_meta( $donation_id, 'items', true );

  $buttons = [];
  foreach( $partners as $partner ){
    $buttons[] = [
      'name' => $partner['name'],
      'url'  => get_upgrade_url( $donation_id, $partner ),
    ];
  }

  $days = round( ( current_time( 'timestamp' ) - strtotime( get_post_field( 'post_date', $donation_id ) ) ) / DAY_IN_SECONDS );
  $when = [ 1 => 'Yesterday', 2 => 'Two days ago', 3 => 'Three days ago' ];

  $html = render_template( 'email.donor-orphan-followup', [
    'when'           => isset( $when[ $days ] )? $when[ $days ] : 'Recently',
    'first_name'     => strtok( $name, ' ' ),
    'items'          => is_array( $items )? implode( ', ', $items ) : '',
    'zip'            => get_donation_zip_code( $donation_id ),
    'partners'       => $buttons,
    'single_partner' => 1 == count( $buttons ),
    'partner_name'   => $buttons[0]['name'],
    'year'           => current_time( 'Y' ),
    'facebook_icon'  => DONMAN_PLUGIN_URL . 'lib/images/facebook.png',
    'x_icon'         => DONMAN_PLUGIN_URL . 'lib/images/twitter.png',
    'instagram_icon' => DONMAN_PLUGIN_URL . 'lib/images/instagram.png',
  ] );

  $headers = [
    'From: PickUpMyDonation.com <contact@pickupmydonation.com>',
    'Sender: PickUpMyDonation.com <contact@pickupmydonation.com>',
  ];
  $recipient = $to ?: $name . ' <' . $email . '>';

  add_filter( 'wp_mail_content_type', __NAMESPACE__ . '\\html_content_type' );
  $sent = wp_mail( $recipient, 'Still need your items picked up?', $html, $headers );
  remove_filter( 'wp_mail_content_type', __NAMESPACE__ . '\\html_content_type' );

  if( ! $sent )
    return 'mail-failed';

  if( ! $to )
    update_post_meta( $donation_id, SENT_META, current_time( 'mysql' ) );

  return 'sent';
}

/**
 * @return string
 */
function html_content_type(){
  return 'text/html';
}

/**
 * Emails the donations that are due a follow-up. Run every 15 minutes by
 * `wp dm orphan-followup` from the site user's crontab.
 *
 * A donation without a priority partner or donor email is marked skipped,
 * so it doesn't hold up the batch. A failed send is retried next run.
 *
 * @return array [ donation ID => result from send_followup() ].
 */
function sweep(){
  if( ! is_enabled() )
    return [];
  if( ! wp_cache_add( 'sweep_lock', 1, CACHE_GROUP, 10 * MINUTE_IN_SECONDS ) )
    return [];

  $results = [];
  foreach( get_due_donations() as $donation_id ){
    $results[ $donation_id ] = send_followup( $donation_id );
    if( in_array( $results[ $donation_id ], [ 'no-partner', 'no-email' ] ) )
      update_post_meta( $donation_id, SKIPPED_META, $results[ $donation_id ] );
  }

  wp_cache_delete( 'sweep_lock', CACHE_GROUP );

  return $results;
}

/**
 * Moves a donation to a priority partner and sends it to them.
 *
 * The donation keeps its ID, items and pickup dates; its organization and
 * trans dept become the partner's, and it's marked priority and fee-based.
 * Then the partner gets it as a new donation (email and/or API) and the
 * donor gets a confirmation. stats\refresh_recent() recounts its day.
 *
 * @param int   $donation_id The donation ID.
 * @param array $partner     `id` and `trans_dept_id`.
 * @return true|\WP_Error
 */
function upgrade_donation( $donation_id, $partner ){
  if( get_post_meta( $donation_id, UPGRADED_META, true ) )
    return new \WP_Error( 'upgraded', 'This donation has already been sent to a priority partner.' );
  if( ! in_array( (int) $partner['id'], wp_list_pluck( get_partners( $donation_id ), 'id' ) ) )
    return new \WP_Error( 'partner', 'This partner no longer serves your zip code.' );
  if( ! wp_cache_add( 'upgrade_' . $donation_id, 1, CACHE_GROUP, MINUTE_IN_SECONDS ) )
    return new \WP_Error( 'busy', 'This donation is already being sent.' );

  $post = get_post( $donation_id );

  update_post_meta( $donation_id, FROM_ORG_META, get_post_meta( $donation_id, 'organization', true ) );
  update_post_meta( $donation_id, FROM_TRANS_DEPT_META, get_post_meta( $donation_id, 'trans_dept', true ) );
  update_field( 'field_629f7024abec7', $partner['id'], $donation_id ); // organization
  update_field( 'field_629f704fabec8', $partner['trans_dept_id'], $donation_id ); // trans_dept
  update_field( 'field_64cbba71ccb99', 1, $donation_id ); // fee_based
  update_post_meta( $donation_id, 'priority', 1 );
  update_post_meta( $donation_id, '_organization_name', get_the_title( $partner['id'] ) );
  update_post_meta( $donation_id, SOURCE_META, 'orphan-followup' );
  update_post_meta( $donation_id, UPGRADED_META, current_time( 'mysql' ) );

  if( 0 !== strpos( $post->post_title, 'PRIORITY - ' ) )
    wp_update_post( [ 'ID' => $donation_id, 'post_title' => 'PRIORITY - ' . $post->post_title ] );

  // send_email() reads the donation from the session, as at the end of the
  // donation form. The receipt doesn't name the organization, so the saved
  // one still applies.
  $had_session = isset( $_SESSION );
  $session = $had_session? $_SESSION : null;
  $_SESSION = [
    'donor'           => get_donation_data( get_post( $donation_id ) ),
    'donationreceipt' => $post->post_content,
  ];
  send_email( 'trans_dept_notification' );
  send_email( 'donor_confirmation' );
  if( $had_session ){
    $_SESSION = $session;
  } else {
    unset( $_SESSION );
  }

  return true;
}

/**
 * Handles the Confirm button on the [priority-upgrade] page, then redirects
 * back to the page, which now shows the result.
 */
function handle_confirm(){
  if( 'POST' != $_SERVER['REQUEST_METHOD'] || ! isset( $_POST['donman_priority_upgrade'] ) )
    return;

  $args = wp_unslash( array_intersect_key( $_POST, array_flip( [ 'd', 'o', 't', 'k' ] ) ) );
  $link = check_upgrade_link( $args );
  if( is_wp_error( $link ) )
    return;

  $result = upgrade_donation( $link['donation']->ID, $link['partner'] );
  $query = $args;
  if( is_wp_error( $result ) )
    $query['error'] = $result->get_error_code();

  wp_safe_redirect( add_query_arg( array_map( 'rawurlencode', $query ), home_url( '/' . UPGRADE_PAGE . '/' ) ) );
  exit;
}
add_action( 'template_redirect', __NAMESPACE__ . '\\handle_confirm' );

/**
 * Lets Donations → All Donations list only upgraded donations
 * (`&donman_upgraded=1`), for the link on the Dashboard widget.
 *
 * @param \WP_Query $query The query.
 */
function filter_upgraded_donations( $query ){
  if( ! is_admin() || ! $query->is_main_query() || empty( $_GET['donman_upgraded'] ) || 'donation' != $query->get( 'post_type' ) )
    return;
  $query->set( 'meta_key', UPGRADED_META );
  $query->set( 'orderby', 'meta_value' );
  $query->set( 'order', 'DESC' );
}
add_action( 'pre_get_posts', __NAMESPACE__ . '\\filter_upgraded_donations' );
