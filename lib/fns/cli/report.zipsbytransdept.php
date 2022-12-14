<?php
/**
 * Generates a report of zip codes assigned to trans_depts.
 *
 * NOTE: 11/28/2022 (07:25) - the following doesn't appear to be called
 * anywhere in PMD 2.0. Adding the following code, but we haven't
 * tested it.
 *
 * ## OPTIONS
 *
 * [--format=<table|csv|json|yaml|ids|count>]
 * : Output format of the report (i.e.  ‘table’, ‘json’, ‘csv’, ‘yaml’, ‘ids’, ‘count’)
 */

// 1. Get all orgs
// 2. For each org, get all trans_depts.
// 3. For each trans_dept, get all zip codes.

$format = ( isset( $assoc_args['format'] ) )? $assoc_args['format'] : 'table' ;

$organizations = get_posts([
  'post_type' => 'organization',
  'posts_per_page' => -1,
]);
$progress = WP_CLI\Utils\make_progress_bar('Compiling report', count($organizations) );
foreach( $organizations as $org ){
  $trans_depts = get_posts([
    'post_type' => 'trans_dept',
    'posts_per_page' => -1,
    'meta_key' => 'organization',
    'meta_value' => $org->ID,
  ]);
  $org_trans_depts = [];
  $zip_codes = [];
  foreach ($trans_depts as $trans_dept ) {
    $org_trans_depts[] = $trans_dept->post_title;
    $pickup_codes = wp_get_post_terms( $trans_dept->ID, 'pickup_code' );
    if( $pickup_codes ){
      foreach( $pickup_codes as $pickup_code ){
        //error_log('$pickup_code = ' . print_r($pickup_code,true));
        $zip_codes[] = $pickup_code->name;
      }
    }
    //WP_CLI::line($trans_dept->post_title);
  }
  $org_rows[] = [
    'id' => $org->ID,
    'name' => $org->post_title,
    'trans_depts' => implode(', ', $org_trans_depts ),
    'zipcodes' => implode( ', ', $zip_codes ),
    'total_zips' => count( $zip_codes ),
  ];
  $progress->tick();
}
$progress->finish();
WP_CLI\Utils\format_items($format, $org_rows, 'id,name,trans_depts,zipcodes,total_zips' );