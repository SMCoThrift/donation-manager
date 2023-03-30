<?php

namespace DonationManager\donations;
use function DonationManager\organizations\{is_orphaned_donation,get_default_organization};
use function DonationManager\donations\{get_orphaned_donation_notifications};
use function DonationManager\orphanedproviders\{get_orphaned_provider_contact};

/**
 * Admin CSS
 */
function admin_custom_css(){
  echo '<style>
  .post-type-donation #taxonomy-pickup_code{width: 100px;}
  .post-type-trans_dept #taxonomy-pickup_code{width: 60%;}
  .response-pill{font-size: 12px; padding: 0 4px; border-radius: 3px; background-color: #999; color: #fff; display: inline-block; text-transform: uppercase;}
  .response-pill.success{background-color: #09c500;}
  .response-pill.warning{background-color: #f68428;}
  .response-pill.error2{background-color: #cb3131;}
  .response-pill.note{background-color: #ccc;}
  .response-msg{font-size: 11px; color: #999;}
  </style>';
}
add_action( 'admin_head', __NAMESPACE__ . '\\admin_custom_css' );

/**
 * Supplies content for custom columns.
 *
 * @param      string  $column  The column
 */
function custom_column_content( $column ){
  global $post;

  switch( $column ){
    case 'api-response':
      $html = custom_column_api_response_content( $post->ID );
      echo $html;
      break;

    case 'org':
        $org_id = get_field( 'organization', $post->ID );
        if( is_object( $org_id ) )
          $org_id = $org_id->ID;

        $org_name = '';
        if( is_numeric( $org_id ) )
          $org_name = get_the_title( $org_id );
        if( empty( $org_name ) ){
          $post_status = get_post_status( $post->ID );
          $color = ( 'publish' == $post_status )? 'f00' : '333' ;
          $text = ( 'publish' == $post_status )? 'NOT SET!' : 'not set';
          $org_name = '<code style="color: #' . $color . '; font-weight: bold;">' . $text . '</code>';
        }
        $trans_dept = get_field( 'trans_dept', $post->ID );
        if( is_object( $trans_dept ) && is_orphaned_donation( $trans_dept->ID ) ){
          $notifications = get_orphaned_donation_notifications( $post->ID );
          //echo '<pre>' . print_r( $notifications, true ) . '</pre>';
          $rows = [];
          if( is_array( $notifications ) && 0 < count( $notifications ) ){
            foreach( $notifications as $notification ){
              $cells = [];
              $contact = get_orphaned_provider_contact( $notification->contact_id );
              $cells[] = $contact['store_name'] . '<br>(' . $contact['contact_email'] . ')';
              $cells[] = ( ! empty( $notification->click_timestamp ) )? date( 'm/d/Y h:ia (\E\S\T)', strtotime( $notification->click_timestamp ) ) : '' ;
              $rows[] = '<tr><td style="padding: 4px">' . implode( '</td><td style="padding: 2px 4px">', $cells ) . '</td></tr>';
            }
          }
          echo $org_name . '<table class="striped orphaned-contacts"><tbody>' . implode( '', $rows ) . '</tbody></table>';
        } else {
          echo $org_name;
        }
    break;

    case 'trans_dept':
      $trans_dept = get_field( 'trans_dept', $post->ID );
      echo $trans_dept->post_title;
      break;
  }
}
add_action( 'manage_donation_posts_custom_column', __NAMESPACE__ . '\\custom_column_content', 10, 2 );
add_action( 'manage_store_posts_custom_column', __NAMESPACE__ . '\\custom_column_content', 10, 2 );
add_action( 'manage_trans_dept_posts_custom_column', __NAMESPACE__ . '\\custom_column_content', 10, 2 );

/**
 * Returns the HTML for the API Response column.
 *
 * @param      int     $donation_id  The donation identifier
 *
 * @return     string  HTML for the API Response column.
 */
function custom_column_api_response_content( $donation_id ){
  $html = [];
  $default_org = get_default_organization();
  $org = get_post_meta( $donation_id, 'organization', true );
  if( is_numeric( $org ) )
    $routing_method = get_donation_routing_method( $org );
  //$html[] = 'routing_method: ' . $routing_method . '<br>';
  $api_response = get_post_meta( $donation_id, 'api_response', true );

  if( 480325 > $donation_id ){
    if( $org == $default_org['id'] || 'email' != $routing_method ){

      $response = @unserialize( $api_response );
      if( is_array( $response ) && array_key_exists( 'response', $response ) ){
        $response_code = $response['response']['code'];
        $response_msg = $response['response']['message'];

        switch( $response_code ){
          case 200:
            $html[] = '<div class="response-pill success">Success</div>';
            break;

          default:
            $html[] = '<div class="response-pill warning">Response Code: ' . $response_code . '</div>';
        }
        $html[] = '<div class="response-msg">Msg: ' . $response_msg . '</div>';
      } else if( ! empty( $api_response ) && 's:' != substr( $api_response, 0, 2 ) ){
        $response_css_class = ( stristr( strtolower( $api_response ), 'error' ) )? 'error2' : 'warning' ;
        $response_notice = ( stristr( strtolower( $api_response ), 'error' ) )? 'Error' : 'Warning' ;
        $html[] = '<div class="response-pill ' . $response_css_class . '">' . $response_notice . '</div>';
        $html[] = '<div class="response-msg">Msg: ' . $api_response . '</div>';
      } else {
        $html[] = '<div class="response-pill warning">No API Response</div>';
      }
    } else {
      $html[] = '<div class="response-pill note">Not Sent/Emailed</div>';
    }
  } else {
    if( $org == $default_org['id'] || 'email' != $routing_method ){
      /**
       * After donations with an ID of >= 480325 have the Response Code
       * and Message stored as separate meta fields.
       */
      $response_code = get_post_meta( $donation_id, 'api_response_code', true );
      $response_message = get_post_meta( $donation_id, 'api_response_message', true );
      switch( $response_code ){
        case 200:
        case 201:
        case 202:
        case 203:
        case 204:
        case 205:
          $html[] = '<div class="response-pill success">Success</div>';
          $html[] = '<div class="response-msg">' . $response_code . '/' . $response_message . '</div>';
          break;

        case 400:
        case 401:
        case 402:
        case 403:
        case 404:
          $html[] = '<div class="response-pill error2">Error</div>';
          $html[] = '<div class="response-msg">Msg: ' . $response_message . '</div>';
          break;

        default:
          if( empty( $response_code ) && is_string( $api_response ) && stristr( strtolower( $api_response ), 'operation timed out' ) ){
            $html[] = '<div class="response-pill error2">Error</div>';
            $html[] = '<div class="response-msg">API Msg: ' . $api_response . '</div>';
          } else {
            $html[] = '<div class="response-pill warning">Warning (Code: ' . $response_code . ')</div>';
            $html[] = '<div class="response-msg">Msg: ' . $response_message . '</div>';
          }
      }
    } else {
      $html[] = '<div class="response-pill note">Not Sent/Emailed</div>';
    }
  }

  return implode( '', $html );
}

/**
 * Handles sorting for custom columns.
 *
 * @param      array  $vars   The variables
 *
 * @return     array  Modified array with sorting added.
 */
function custom_columns_sort( $vars ){
    if( ! isset( $vars['orderby'] ) )
        return $vars;

    switch( $vars['orderby'] ){
        case 'organization':
            $vars = array_merge( $vars, array(
                'meta_key' => '_organization_name',
                'orderby' => 'meta_value'
            ));
        break;
    }

    return $vars;
}
add_filter( 'request', __NAMESPACE__ . '\\custom_columns_sort' );

/**
 * Adds columns to admin donation custom post_type listings.
 *
 * @since 1.0.1
 *
 * @param array $defaults Array of default columns for the CPT.
 * @return array Modified array of columns.
 */
function columns_for_donation( $defaults ){
    $defaults = array(
        'cb' => '<input type="checkbox" />',
        'title' => 'Title',
        'org' => 'Organization',
        'taxonomy-donation_option' => 'Donation Options',
        'taxonomy-pickup_code' => 'Pickup Codes',
        'api-response'  => 'API Response',
        'date' => 'Date',
    );
    return $defaults;
}
add_filter( 'manage_donation_posts_columns', __NAMESPACE__ . '\\columns_for_donation' );

/**
 * Adds columns to admin store custom post_type listings.
 *
 * @since 1.0.1
 *
 * @param array $defaults Array of default columns for the CPT.
 * @return array Modified array of columns.
 */
function columns_for_store( $defaults ){
    $defaults = array(
        'cb'            => '<input type="checkbox" />',
        'title'         => 'Title',
        'trans_dept'    => 'Transportation Department',
    );
    return $defaults;
}
add_filter( 'manage_store_posts_columns', __NAMESPACE__ . '\\columns_for_store' );

/**
 * Adds columns to admin trans_dept custom post_type listings.
 *
 * @since 1.0.1
 *
 * @param array $defaults Array of default columns for the CPT.
 * @return array Modified array of columns.
 */
function columns_for_trans_dept( $defaults ){
    $defaults = array(
        'cb' => '<input type="checkbox" />',
        'title' => 'Title',
        'org' => 'Organization',
        'taxonomy-pickup_code' => 'Pickup Codes',
    );
    return $defaults;
}
add_filter( 'manage_trans_dept_posts_columns', __NAMESPACE__ . '\\columns_for_trans_dept' );

/**
 * Update CPTs with _organization_name used for sorting in admin.
 *
 * @since 1.0.1
 *
 * @param int $post_id Current post ID.
 * @return void
 */
function custom_save_post( $post_id ){

    if( \wp_is_post_revision( $post_id ) )
        return;

    // Only update valid CPTs
    $post_type = \get_post_type( $post_id );
    $valid_cpts = array( 'donation', 'store', 'trans_dept' );
    if( ! in_array( $post_type, $valid_cpts ) )
        return;

    switch ( $post_type ) {
        case 'store':
            $trans_dept = get_field( 'trans_dept', $post_id );
            if( $trans_dept && property_exists( $trans_dept, 'ID' ) ){
              $org = get_field( 'organization', $trans_dept->ID );
              if( $org && is_object( $org ) && property_exists( $org, 'ID' ) ){
                $org_id = $org->ID;
              } else if( $org && is_integer( $org ) ){
                $org_id = $org;
              }
            }
        break;
        case 'donation':
        case 'trans_dept':
            $org = get_field( 'organization', $post_id );
            if( $org && is_object( $org ) && property_exists( $org, 'ID' ) )
              $org_id = $org->ID;
        break;
    }

    if( isset( $org_id ) && is_numeric( $org_id ) ){
      $org_name = get_the_title( $org_id );

      if( ! empty( $org_name ) )
        update_post_meta( $post_id, '_organization_name', $org_name );
    }

}
add_action( 'save_post', __NAMESPACE__ . '\\custom_save_post' );

/**
 * Specifies sortable columns for our CPTs.
 *
 * @param      array  $sortables  The sortables
 *
 * @return     array  Array of sortable columns.
 */
function custom_sortable_columns( $sortables ){
  return array(
    'title' => 'title',
    'org' => 'organization'
  );
}
add_filter( 'manage_edit-donation_sortable_columns', __NAMESPACE__ . '\\custom_sortable_columns' );
add_filter( 'manage_edit-store_sortable_columns', __NAMESPACE__ . '\\custom_sortable_columns' );
add_filter( 'manage_edit-trans_dept_sortable_columns', __NAMESPACE__ . '\\custom_sortable_columns' );