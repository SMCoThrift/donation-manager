<?php

namespace DonationManager\organizations;
use function DonationManager\utilities\{get_alert};
use function DonationManager\helpers\{in_array_r};
use function DonationManager\templates\{render_template};
use function DonationManager\orphanedproviders\{get_orphaned_donation_contacts};

/**
 * Gets the default organization.
 *
 * @param      bool        $priority  The priority
 *
 * @return     array|bool  The default organization.
 */
function get_default_organization( $priority = false ) {
  if( WP_CLI && DMDEBUG_VERBOSE )
      \WP_CLI::line( '🔔 running get_default_organization()... ');

  $default_organization = get_field( 'default_organization', 'option' );
  if( WP_CLI && DMDEBUG_VERBOSE )
    \WP_CLI::line( '🔔 default_organization = ' . print_r( $default_organization, true ) );
  if( ! $default_organization ){
    if( WP_CLI && DMDEBUG_VERBOSE )
      \WP_CLI::line( '🚨 No default organization set! Check the settings page for the plugin.' );
    return false;
  }

  $default_trans_dept = get_field( 'default_transportation_department', 'option' );
  if( WP_CLI && DMDEBUG_VERBOSE )
    \WP_CLI::line( '🔔 default_trans_dept = ' . print_r( $default_trans_dept, true ) );
  if( ! $default_trans_dept ){
    if( WP_CLI && DMDEBUG_VERBOSE )
      \WP_CLI::line( '🚨 No default transportation department set! Check the settings page for the plugin.' );
    return false;
  }


  $button_texts = get_field( 'donation_button_text', 'option' );

  $organization = []; // initialize our return variable
  $organization['id'] = $default_organization->ID;

  if( $priority ){
    $organization['name'] = 'Expedited Pick Up Service';
    $organization['desc'] = get_alert([
      'title'         => null,
      'description'   => 'Choosing <strong>PRIORITY</strong> Pick Up will send your request to all of the <em>fee-based</em> pick up providers in our database. These providers will pick up "almost" <strong>ANYTHING</strong> you have for a fee, and their service provides <em>additional benefits</em> such as the removal of items from anywhere inside your property to be taken to a local non-profit, as well as the removal of junk and items local non-profits cannot accept.<br><br><em>In most cases your donation is still tax-deductible, and these organizations will respond in 24hrs or less. Check with whichever pick up provider you choose.</em>',
      'type'          => 'info',
    ]);
  } else {
    $organization['name'] = $default_organization->post_title;
    $organization['desc'] = $default_organization->post_content;
  }

  $organization['trans_dept_id'] = $default_trans_dept->ID;
  $organization['trans_dept_emails'] = null; // Adding this `null` value to match array returned in get_organizations()

  if( $priority ){
    $organization['alternate_donate_now_url'] = site_url( '/step-one/?oid=' . $default_organization->ID . '&tid=' . $default_trans_dept->ID . '&priority=1' );
    $organization['button_text'] = $button_texts['priority'];
    $organization['priority_pickup'] = true;
  } else {
    /**
     * 07/05/2022 (11:09) - I found 11 instances of an `alternate_donate_now_url`
     * meta key stored in the PMD production DB, but in each case, this meta row
     * had no value stored. Therefore, I don't think I've ever setup this meta.
     * Furthermore, I see no evidence of any GUI for it either. The following
     * code probably needs to be removed:
     */
    //$alternate_donate_now_url = get_post_meta( $default_organization->ID, 'alternate_donate_now_url', true );
    $organization['alternate_donate_now_url'] = null;
    $organization['button_text'] = $button_texts['non_profit'];
    $organization['priority_pickup'] = false;
  }

  $organization['pause_pickups'] = get_field( 'pickup_settings_pause_pickups', $default_organization->ID );
  $organization['edit_url'] = ( current_user_can( 'edit_posts' ) )? get_edit_post_link( $default_organization->ID, 'link' ) : false ;

  return $organization;
}

/**
 * Retrieves all organizations for a given pickup code.
 *
 * @param      string  $pickup_code  The pickup code
 *
 * @return     array   The organizations.
 */
function get_organizations( $pickup_code ) {
  $args = [
    'post_type'   => 'trans_dept',
    'post_status' => 'publish',
    'tax_query'   => [
      [
        'taxonomy'  => 'pickup_code',
        'terms'     => $pickup_code,
        'field'     => 'slug'
      ]
    ],
  ];
  if( WP_CLI && class_exists( 'WP_CLI' ) )
    \WP_CLI::line( '🔔 get_organizations() query args = ' . print_r( $args, true ) );

  $trans_depts = get_posts( $args );

  $organizations = [];

  if( $trans_depts ):
    /**
     * PRIORITY PICKUP SERVICE
     *
     * For organizations with meta `priority_pickup` == true, we
     * will also include our default pick up provider (i.e.
     * orphaned routing) in markets with just one pickup provider.
     * This will give donors a choice between paying for pick up
     * or using our orphaned routing process.
     */
    $priority_pickup = false;
    $button_texts = get_field( 'donation_button_text', 'option' );

    foreach( $trans_depts as $trans_dept ){

      $org_id = get_post_meta( $trans_dept->ID, 'organization', true );
      $organization = get_post( $org_id );

      $pickup_settings = get_field( 'pickup_settings', $organization->ID );
      $priority_pickup = false;
      $pause_pickups = false;
      if( $pickup_settings ){
        if( array_key_exists( 'priority_pickup', $pickup_settings ) )
          $priority_pickup = $pickup_settings['priority_pickup'];

        if( array_key_exists( 'pause_pickups', $pickup_settings ) )
          $pause_pickups = $pickup_settings['pause_pickups'];
      }

      // Contact Emails
      $trans_dept_contact_emails = [];
      $contact_email = get_field( 'contact_email', $trans_dept->ID );

      // If no contact email for the Trans Dept, check the parent Organization:
      if( empty( $contact_email ) && ! empty( $org_id ) && is_numeric( $org_id ) ){
        $contact_email = get_field( 'default_trans_dept_contact_contact_email', $org_id );
        if( ! empty( $contact_email ) )
          $trans_dept_contact_emails[] = $contact_email;
      } else if( ! empty( $contact_email ) && is_email( $contact_email ) ){
        $trans_dept_contact_emails[] = $contact_email;
      }

      $cc_emails = get_field( 'cc_emails', $trans_dept->ID );
      if( ! empty( $cc_emails ) ){
        $cc_emails = ( stristr( $cc_emails, ',' ) )? explode( ',', $cc_emails ) : [ $cc_emails ];
        if( 0 < count( $cc_emails ) )
          $trans_dept_contact_emails = array_merge( $trans_dept_contact_emails, $cc_emails );
      }

      $edit_url = ( current_user_can( 'edit_posts' ) && isset( $org ) )? get_edit_post_link( $org->ID, 'link' ) : false ;
      $use_transportation_department_name = get_post_meta( $trans_dept->ID, 'use_transportation_department_name', true );
      if( $use_transportation_department_name ){
        $org_name = str_replace( [ 'Transportation Dept', 'Store' ], '', $trans_dept->post_title );
      } else {
        $org_name = $organization->post_title;
      }

      $button_text = ( $priority_pickup )? $button_texts['priority'] : $button_texts['non_profit'] ;

      if( $organization ){
        $organizations[] = [
          'id'                        => $organization->ID,
          'name'                      => $org_name,
          'desc'                      => $organization->post_content,
          'trans_dept_id'             => $trans_dept->ID,
          'trans_dept_emails'         => $trans_dept_contact_emails,
          'alternate_donate_now_url'  => null,
          'button_text'               => $button_text,
          'priority_pickup'           => $priority_pickup,
          'pause_pickups'             => $pause_pickups,
          'edit_url'                  => $edit_url,
        ];
      }

    }

    /**
     * We have only 1 org for this pickup_code, and it is a fee-based
     * priority pick up provider. So, we need to add our default pick
     * up provider (i.e. PMD) to the beginning of the list.
     */
    if( 1 == count( $organizations ) && true == $priority_pickup ){
        $default_org = get_default_organization();

        $orphaned_pickup_radius = get_field( 'orphaned_pickup_radius', 'option' );
        if( empty( $orphaned_pickup_radius ) || ! is_numeric( $orphaned_pickup_radius ) )
          $orphaned_pickup_radius = 15;

        // Get list of Orphaned Pick Up Providers
        $providers = get_orphaned_donation_contacts([
          'pcode' => $pickup_code,
          'radius' => $orphaned_pickup_radius,
          'priority' => 0,
          'fields' => 'store_name,email_address,zipcode,priority',
          'duplicates' => false, 'show_in_results' => 1
        ]);
        if( is_array( $providers ) && 0 < count( $providers ) )
            $default_org['providers'] = $providers;

        /* 07/05/2022 (11:15) - don't think we're using this:
        if( isset( $org['alternate_donate_now_url'] ) )
            $default_org['alternate_donate_now_url'] = $org['alternate_donate_now_url'];
        */
        array_unshift( $organizations, $default_org );
    }
  endif;

  return $organizations;
}

/**
 * Retrieves an array of meta_field data for an organization.
 *
 * TODO: Replace get_pickuplocations() and get_pickuptimes()
 * with this function.
 *
 * @link URL short description.
 * @global type $varname short description.
 *
 * @since 1.0.1
 *
 * @param int $org_id Organization ID.
 * @param string $taxonomy Name of the meta field we're retrieving.
 * @return array An array of arrays with each sub-array having a term ID and name.
 */
function get_organization_meta_array( $org_id, $taxonomy ){
  /**
   * We need the following terms from an Organization:
   *
   * - pickup_locations
   * - donation_options
   * - pickup_times
   * - screening_questions
   */

  $terms = wp_get_post_terms( $org_id, $taxonomy );

  $meta_array = array();
  $x = 1;
  if( $terms ){
      foreach( $terms as $term ){
          $pod = pods( $taxonomy );
          $pod->fetch( $term->term_id );
          $order = $pod->field( 'order' );
          $key = ( ! array_key_exists( $order, $meta_array ) )? $order : $x;
          $meta_array[$key] = array( 'id' => $term->term_id, 'name' => $term->name );
          $x++;
      }
  } else {
      $default_meta_ids = $this->get_default_setting_array( $taxonomy . 's' );
      if( is_array( $default_meta_ids ) && 0 < count( $default_meta_ids ) ) {
          foreach( $default_meta_ids as $meta_id ) {
              $term = get_term( $meta_id, $taxonomy );
              $pod = pods( $taxonomy );
              $pod->fetch( $meta_id );
              $order = $pod->field( 'order' );
              $key = ( ! array_key_exists( $order, $meta_array ) )? $order : $x;
              $meta_array[$key] = array( 'id' => $meta_id, 'name' => $term->name );
              $x++;
          }
      }
  }

  ksort( $meta_array );

  return $meta_array;
}

/**
 * Retrieves an org's pickup locations.
 */
function get_pickuplocations( $org_id ){
  $terms = wp_get_post_terms( $org_id, 'pickup_location' );
  if( 0 == count( $terms ) )
    $terms = get_field( 'default_options_default_pickup_locations', 'option' );

  $pickuplocations = [];
  if( is_array( $terms ) && 0 < count( $terms ) ){
    foreach( $terms as $term ){
      $pickuplocations[] = [
        'id'    => $term->term_id,
        'name'  => $term->name,
      ];
    }
  }
  //uber_log( '🔔 $pickuplocations = ' . print_r( $pickuplocations, true ) );

  return $pickuplocations;
}

/**
 * Retrieves an organization's picktup times.
 */
function get_pickuptimes( $org_id ){
  $terms = wp_get_post_terms( $org_id, 'pickup_time' );
  if( 0 == count( $terms ) )
    $terms = get_field( 'default_options_default_pickup_times', 'option' );

  usort( $terms, function( $a, $b ){
    return $a->term_order <=> $b->term_order;
  });

  $pickuptimes = [];
  if( 0 < count( $terms ) ){
    foreach( $terms as $term ){
      $pickuptimes[] = [
        'id'    => $term->term_id,
        'name'  => $term->name,
      ];
    }
  }

  return $pickuptimes;
}

/**
 * Returns priority organizations for a given $pickup_code.
 */
function get_priority_organizations( $pickup_code = null ){
  if( is_null( $pickup_code ) )
    return false;

  $args = array(
    'post_type' => 'trans_dept',
    'tax_query' => array(
      array(
        'taxonomy'  => 'pickup_code',
        'terms'     => $pickup_code,
        'field'     => 'slug'
      )
    )
  );
  $query = new \WP_Query( $args );

  $organizations = array();

  if( $query->have_posts() ){
    if( WP_CLI && class_exists( 'WP_CLI' ) )
      \WP_CLI::line( '🔔 Looping through Transporation Departments: $query->posts...' );

    while( $query->have_posts() ): $query->the_post();
      global $post;
      setup_postdata( $post );
      $org_id = get_post_meta( $post->ID, 'organization', true );
      if( WP_CLI && class_exists( 'WP_CLI' ) )
        \WP_CLI::line( '🔔 TRANS DEPT: ' . get_the_title() . ' (ORG: ' . get_the_title( $org_id ) . ', ID: ' . $org_id . ')' );

      // If no `organization` is set, $org_id is a `string`. Therefore
      // we must continue to the next post.
      // 08/16/2022 (12:29) - 👆👇 ???
      /*
      if( 'string' == gettype( $org_id ) )
        continue;
      /**/

      $pickup_settings = get_field( 'pickup_settings', $org_id );
      if( $pickup_settings ):
        // 08/03/2022 (03:16) - not current stored
        // if( array_key_exists( 'alternate_donate_now_url', $pickup_settings ) )
        //   $alternate_donate_now_url = $pickup_settings['alternate_donate_now_url']; // 08/03/2022 (03:16) - not current stored

        if( array_key_exists( 'priority_pickup', $pickup_settings ) )
          $priority_pickup = $pickup_settings['priority_pickup'];
      endif;

      if( $org_id && $priority_pickup ){
        $organizations[] = [
          'id' => $org_id,
          'name' => get_the_title( $org_id ),
          'desc' => get_the_content( null, false, $org_id ),
          'trans_dept_id' => $post->ID,
          /*'alternate_donate_now_url' => $alternate_donate_now_url,*/
          'priority_pickup' => 1,
        ];
      }
    endwhile;
    wp_reset_postdata();
    if( 0 == count( $organizations ) )
      $organizations[] = get_default_organization( true );
  } else {
      // No orgs for this zip, return PMD as priority so we can
      // use the Priority Orphan DB
      $default_org = get_default_organization( true );

      // Only provide the PRIORITY option for areas where there is
      // a priority provider in the contacts table.
      $contacts = get_orphaned_donation_contacts( array( 'pcode' => $pickup_code, 'limit' => 1, 'priority' => 1 ) );

      if( is_null( $contacts ) || 0 < count( $contacts ) )
          $organizations[] = $default_org;
  }

  return $organizations;
}

/**
 * Returns Priority Pick Up HTML.
 */
function get_priority_pickup_links( $pickup_code = null, $note = null ){
    if( is_null( $pickup_code ) )
        return false;

    // Priority Donation Backlinks
    $priority_html = '';
    $priority_orgs = get_priority_organizations( $pickup_code );
    if( is_array( $priority_orgs ) ){
        foreach( $priority_orgs as $org ){
            // Setup button link
            if(
                isset( $org['alternate_donate_now_url'] )
                && filter_var( $org['alternate_donate_now_url'], FILTER_VALIDATE_URL )
            ){
                $link = $org['alternate_donate_now_url'];
            } else {
                $link = '/step-one/?oid=' . $org['id'] . '&tid=' . $org['trans_dept_id'] . '&priority=1';
            }

            $donation_button_text = get_field( 'donation_button_text', 'option' );
            $priority_button_text = ( array_key_exists( 'priority', $donation_button_text ) && ! empty( $donation_button_text['priority'] ) )?  $donation_button_text['priority'] : 'Click here for Priority Pick Up' ;

            $row = [
                'name' => $org['name'],
                'link' => $link,
                'button_text' => $priority_button_text,
                'css_classes' => ' priority',
                'desc' => '',
            ];
            if( stristr( $org['name'], 'College Hunks' ) )
                $row['desc'] = '<div style="text-align: center; font-size: 1.25em;"><div style="margin-bottom: 1em">OR</div>Call <a href="tel:888-912-4902">(888) 912-4902</a> for Priority Pick Up</div>';
            $rows[] = $row;
        }
        $hbs_vars = [
            'rows' => $rows,
        ];
        $priority_rows = render_template( 'form1.select-your-organization', $hbs_vars ); // 08/16/2022 (11:21) - originally `form1.select-your-organization.rows`

        /*
        if( is_null( $note ) )
            $note = 'Even though your items don\'t qualify for pick up, you can connect with our "fee based" priority pick up partner that will pick up items we can\'t use as well as any other items you would like to recycle or throw away:';

        $priority_html = '<div class="alert alert-warning"><h3 style="margin-top: 0;">Priority Pick Up Option</h3><p style="margin-bottom: 20px;">' . $note . '</p>' . $priority_rows . '</div>';
        /**/

        $priority_html = get_alert([
          'type' => 'warning',
          'description' => '<p>If you need a priority/fee-based pick up option, consider choosing our local priority partner:</p>' . $priority_rows
        ]);
    }

    return $priority_html;
}

/**
 * Retrieves an organization's screening questions. If none are assigned, returns the default questions.
 */
function get_screening_questions( $org_id = null ) {
  $screening_questions = [];

  if( ! is_null( $org_id ) && is_numeric( $org_id ) ){
    $terms = wp_get_post_terms( $org_id, 'screening_question' );
    if( WP_CLI && class_exists( 'WP_CLI' ) )
      \WP_CLI::line( '🔔 Screen Questions set for `' . get_the_title( $org_id ) . '` = ' . print_r( $terms, true ) );
  }

  if( ! isset( $terms ) || empty( $terms ) ){
    $terms = get_field( 'default_options_default_screening_questions', 'option' );
  }

  foreach( $terms as $term ) {
    $screening_questions[] = array( 'id' => $term->term_id, 'name' => $term->name, 'desc' => $term->description );
  }

  return $screening_questions;
}

/**
 * Checks if a donation is `orphaned`.
 *
 * In order for this function to return `true`, orphaned
 * donation routing must be ON, and the donation must be
 * using the default pick up provider.
 *
 * @access DonationManager\emails\{send_email}
 * @since 1.3.0
 *
 * @param int $donor_trans_dept_id Trans dept ID associated with donation.
 * @return bool Returns `true` for orphaned donations.
 */
function is_orphaned_donation( $donor_trans_dept_id = 0 ){
  $orphaned_donation_routing = get_field( 'orphaned_donation_routing', 'option' );
  $default_trans_dept = get_field( 'default_transportation_department', 'option' );

  if( WP_CLI && class_exists( 'WP_CLI' ) )
    \WP_CLI::line( '🔔 is_orphaned_donation()' . "\n" . ' - $orphaned_donation_routing = ' . $orphaned_donation_routing . "\n - \$default_trans_dept->ID " . $default_trans_dept->ID );

  if(
      true == $orphaned_donation_routing
      && $donor_trans_dept_id == $default_trans_dept->ID
  ){
      return true;
  } else {
      return false;
  }
}

/**
 * Returns true|false depending on an Organization's priority pickup status.
 *
 * @param      int  $org_id  The organization identifier
 *
 * @return     bool    True if the specified organization identifier is priority, False otherwise.
 */
function is_priority( $org_id ){
  $priority_pickup = false;
  $pickup_settings = get_field( 'pickup_settings', $org_id );
  if( array_key_exists( 'priority_pickup', $pickup_settings ) )
    $priority_pickup = $pickup_settings['priority_pickup'];
  return $priority_pickup;
}