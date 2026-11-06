<?php

namespace DonationManager\analytics;
use function DonationManager\organizations\{is_orphaned_donation};

/**
 * GA4 conversion events for the donation form.
 *
 * Every completed donation fires `donation_completed` on the thank-you page.
 * A donation that was sent to a priority partner also fires
 * `priority_partner_donation`, which Google Ads imports as its own
 * conversion action.
 *
 * "Sent to a priority partner" is recorded on the donation by send_email(),
 * at the moment the trans dept notification goes out, so GA4 and our own
 * records use the same definition and can be compared (see PARTNER_META):
 *
 * - `direct`: the donor picked a priority org on Select Your Organization.
 * - `orphan_fee_based`: an orphaned donation whose donor accepted fee-based
 *   pickup, sent to the priority orgs in the zip by API or email. Orphans
 *   that only go to the fee-based contacts in `donman_contacts` don't count.
 * - `followup_upgrade`: an orphan follow-up upgrade (see orphanfollowup.php).
 *   Recorded, but no event fires for it: the donor isn't on the thank-you
 *   page, and counting upgrades would teach Ads to favor donors who first
 *   chose free pickup.
 *
 * No conversion value is sent. It's set in GA4 or Google Ads instead, so
 * the value of a lead isn't in the page source.
 */

/**
 * Post meta: `route` and the `partners` (`id`, `name`, `delivery`) the
 * donation was sent to.
 */
const PARTNER_META = '_priority_partner';

/**
 * Records that a donation was sent to a priority partner.
 *
 * The default organization (PMD) never counts. Recording the same partner
 * twice does nothing.
 *
 * @param int    $donation_id The donation ID.
 * @param int    $org_id      The partner's organization ID.
 * @param string $delivery    `api` or `email`.
 * @param string $route       `direct` or `orphan_fee_based`. Overridden with
 *                            `followup_upgrade` for an upgraded donation.
 * @return void
 */
function record_priority_partner( $donation_id, $org_id, $delivery, $route ){
  if( empty( $donation_id ) || empty( $org_id ) )
    return;

  $default_org = get_field( 'default_organization', 'option' );
  if( $default_org && $default_org->ID == $org_id )
    return;

  if( get_post_meta( $donation_id, '_priority_upgrade_source', true ) )
    $route = 'followup_upgrade';

  $record = get_post_meta( $donation_id, PARTNER_META, true );
  if( ! is_array( $record ) || $record['route'] != $route )
    $record = [ 'route' => $route, 'partners' => [] ];

  if( in_array( (int) $org_id, wp_list_pluck( $record['partners'], 'id' ) ) )
    return;

  $record['partners'][] = [
    'id'       => (int) $org_id,
    'name'     => get_post_field( 'post_title', $org_id ), // Unfiltered, for GA4 reports.
    'delivery' => $delivery,
  ];
  update_post_meta( $donation_id, PARTNER_META, $record );
}

/**
 * @param string $routing_method An org's `pickup_settings_donation_routing`.
 * @return string `api` or `email`.
 */
function delivery( $routing_method ){
  return stristr( (string) $routing_method, 'api' )? 'api' : 'email';
}

/**
 * The `<script>` with the GA4 events for a completed donation.
 *
 * @param array $donor The donor session (`ID`, `trans_dept_id`).
 * @return string
 */
function get_conversion_script( $donor ){
  $donation_id = (int) $donor['ID'];
  $record = get_post_meta( $donation_id, PARTNER_META, true );
  $priority = is_array( $record ) && ! empty( $record['partners'] ) && 'followup_upgrade' != $record['route'];

  if( $priority ){
    $donation_type = 'priority';
  } else if( ! empty( $donor['trans_dept_id'] ) && is_orphaned_donation( $donor['trans_dept_id'] ) ){
    $donation_type = 'orphan';
  } else {
    $donation_type = 'nonprofit';
  }

  $script = 'gtag("event", "donation_completed", ' . wp_json_encode( [
    'transaction_id' => (string) $donation_id,
    'donation_type'  => $donation_type,
  ] ) . ');';

  if( $priority ){
    $script.= 'gtag("event", "priority_partner_donation", ' . wp_json_encode( [
      'transaction_id' => (string) $donation_id,
      'partner'        => implode( ', ', wp_list_pluck( $record['partners'], 'name' ) ),
      'route'          => $record['route'],
      'delivery'       => implode( ', ', array_unique( wp_list_pluck( $record['partners'], 'delivery' ) ) ),
    ] ) . ');';
  }

  return '<script>' . $script . '</script>';
}
