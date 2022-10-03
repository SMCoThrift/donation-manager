<?php

namespace DonationManager\donations;

/**
 * Supplies content for custom columns.
 *
 * @param      string  $column  The column
 */
function custom_column_content( $column ){
    global $post;
    switch( $column ){
        case 'org':
            $org_id = get_field( 'organization', $post->ID );
            $org_name = '';
            if( is_numeric( $org_id ) )
              $org_name = get_the_title( $org_id );
            if( empty( $org_name ) ){
              $post_status = get_post_status( $post->ID );
              $color = ( 'publish' == $post_status )? 'f00' : '333' ;
              $text = ( 'publish' == $post_status )? 'NOT SET!' : 'not set';
              $org_name = '<code style="color: #' . $color . '; font-weight: bold;">' . $text . '</code>';
            }

            echo $org_name;
        break;
    }
}
add_action( 'manage_donation_posts_custom_column', __NAMESPACE__ . '\\custom_column_content', 10, 2 );
add_action( 'manage_store_posts_custom_column', __NAMESPACE__ . '\\custom_column_content', 10, 2 );
add_action( 'manage_trans_dept_posts_custom_column', __NAMESPACE__ . '\\custom_column_content', 10, 2 );

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
        'date' => 'Date',
    );
    return $defaults;
}
add_filter( 'manage_donation_posts_columns', __NAMESPACE__ . '\\columns_for_donation' );

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