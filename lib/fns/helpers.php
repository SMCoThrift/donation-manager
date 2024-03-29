<?php
namespace DonationManager\helpers;

/**
 * Gets archived donations.
 *
 * @param      int  $year   The year (optional)
 * @param      int  $month  The month (optional)
 *
 * @return     array   The archived donations.
 */
function get_archived_donations( $year = null, $month = null ){
  $archived_donations = [];
  $archived_donations['total'] = 0;
  if( have_rows( 'donation_stats', 'option' ) ){
    $total = 0;
    while( have_rows( 'donation_stats', 'option' ) ): the_row();
      $row_year = get_sub_field( 'year' );
      if( ! is_null( $year ) && $row_year != $year )
        continue;

      $row_month = get_sub_field( 'month' );
      if( ! is_null( $month) && $row_month != $month )
        continue;

      $row_donations = get_sub_field( 'donations' );
      if( is_numeric( $row_donations ) )
        $total+= $row_donations;
      $archived_donations[ $row_year ][ $row_month ] = $row_donations;
    endwhile;
    $archived_donations['total'] = $total;
  }

  return $archived_donations;
}

/**
 * Returns content type for use in filter `wp_mail_content_type`.
 *
 * @return     string  The content type.
 */
function get_content_type(){
    return 'text/html';
}

/**
 * Given a 5 digit US Zip Code, returns the coordinates of that zip.
 *
 * @param   int     The zip code.
 * @return  array   Array of the zip code's coordinates.
 */
function get_coordinates( $zipcode = null ){
    if( is_null( $zipcode ) )
        return false;

    global $wpdb;

    if( ! preg_match( '/[0-9]{5}/', $zipcode ) )
        return false;

    // Get the Lat/Lon of our Zip Code
    $sql = 'SELECT ID,Latitude,Longitude FROM ' . $wpdb->prefix . 'donman_zipcodes WHERE ZIPCode="%s" ORDER BY CityName ASC LIMIT 1';
    $result = $wpdb->get_results( $wpdb->prepare( $sql, $zipcode ) );

    $lat = round( $result[0]->Latitude, 3 );
    $lng = round( $result[0]->Longitude, 3 );
    $coordinates = [ 'lat' => $lat, 'lng' => $lng ];

    return $coordinates;
}

/**
 * Returns donations from a specified interval.
 *
 * @since 1.4.6
 *
 * @param string $interval Time interval (e.g. `last_month`).
 * @return int Number of donations for a given time interval.
 */
function get_donations_by_interval( $interval = null ){
  if( is_null( $interval ) )
    return false;

  global $wpdb;

  switch ( $interval ) {
    case 'this_year':
      $current_time = \current_time( 'Y-m-d' ) . ' first day of this year';
      $dt = \date_create( $current_time );
      $year = $dt->format( 'Y' );
      $format = "SELECT count(ID) FROM {$wpdb->posts} WHERE post_type='donation' AND post_status='publish' AND YEAR(post_date)=%d";
      $sql = $wpdb->prepare( $format, $year );
      break;

    case 'last_month':
      $current_time = \current_time( 'Y-m-d' ) . ' first day of last month';
      $dt = \date_create( $current_time );
      $year = $dt->format( 'Y' );
      $month = $dt->format( 'm' );
      $format = "SELECT count(ID) FROM {$wpdb->posts} WHERE post_type='donation' AND post_status='publish' AND YEAR(post_date)=%d AND MONTH(post_date)=%d";
      $sql = $wpdb->prepare( $format, $year, $month );
      break;
  }

  $donations = $wpdb->get_var( $sql );

  return $donations;
}

/**
 * Multiplies a donation number by a value and returns the dollar amount.
 *
 * @since 1.4.6
 *
 * @param int $donations Number of donations.
 * @return string Dollar value of donations.
 */
function get_donations_value( $donations = 0 ){
  $value = $donations * AVERAGE_DONATION_VALUE;
  return $value;
}

/**
 * Retrieves the options for "What led you to donate today?"
 */
function get_donation_reason_select(){
    $reasons = [
        'Replacing furniture/appliance(s)',
        'Moving soon',
        'Remodeling',
        'Cleaning',
        'No use for item(s)',
        'Other'
    ];
    $options[] = '<option value="">Select your reason for donating...</option>';
    foreach( $reasons as $reason ){
      $selected = '';
      if( isset( $_POST['donor']['reason'] ) && $reason == $_POST['donor']['reason'] ){
        $selected = ( isset( $_POST['donor']['reason'] ) && $reason == $_POST['donor']['reason'] )? ' selected="selected"' : '';
      } else if ( isset( $_SESSION['donor']['reason'] ) && $reason == $_SESSION['donor']['reason'] ){
        $selected = ( isset( $_SESSION['donor']['reason'] ) && $reason == $_SESSION['donor']['reason'] )? ' selected="selected"' : '';
      }
      $options[] = '<option value="' . $reason . '"' . $selected . '>' . $reason . '</option>';
    }
    return '<select class="form-control" name="donor[reason]">' .  implode( '', $options ) . '</select>';
}

/**
 * Retrieves state select input
 */
function get_state_select( $var = 'address' ) {
    $html = '';

    $states = array(
        'Alabama'=>'AL',
        'Alaska'=>'AK',
        'Arizona'=>'AZ',
        'Arkansas'=>'AR',
        'California'=>'CA',
        'Colorado'=>'CO',
        'Connecticut'=>'CT',
        'Delaware'=>'DE',
        'Florida'=>'FL',
        'Georgia'=>'GA',
        'Hawaii'=>'HI',
        'Idaho'=>'ID',
        'Illinois'=>'IL',
        'Indiana'=>'IN',
        'Iowa'=>'IA',
        'Kansas'=>'KS',
        'Kentucky'=>'KY',
        'Louisiana'=>'LA',
        'Maine'=>'ME',
        'Maryland'=>'MD',
        'Massachusetts'=>'MA',
        'Michigan'=>'MI',
        'Minnesota'=>'MN',
        'Mississippi'=>'MS',
        'Missouri'=>'MO',
        'Montana'=>'MT',
        'Nebraska'=>'NE',
        'Nevada'=>'NV',
        'New Hampshire'=>'NH',
        'New Jersey'=>'NJ',
        'New Mexico'=>'NM',
        'New York'=>'NY',
        'North Carolina'=>'NC',
        'North Dakota'=>'ND',
        'Ohio'=>'OH',
        'Oklahoma'=>'OK',
        'Oregon'=>'OR',
        'Pennsylvania'=>'PA',
        'Rhode Island'=>'RI',
        'South Carolina'=>'SC',
        'South Dakota'=>'SD',
        'Tennessee'=>'TN',
        'Texas'=>'TX',
        'Utah'=>'UT',
        'Vermont'=>'VT',
        'Virginia'=>'VA',
        'Washington'=>'WA',
        'Washington DC' => 'DC',
        'West Virginia'=>'WV',
        'Wisconsin'=>'WI',
        'Wyoming'=>'WY'
    );
    $html.= '<option value="">Select a state...</option>';
    foreach( $states as $state => $abbr ){
        $selected = ( isset( $_POST['donor'][$var]['state'] ) && $abbr == $_POST['donor'][$var]['state'] )? ' selected="selected"' : '';
        $html.= '<option value="' . $abbr . '"' . $selected . '>' . $state . '</option>';
    }
    return '<select class="form-control" name="donor[' . $var . '][state]">' .  $html . '</select>';
}

/**
 * Multidimensional in_array() search
 *
 * @param      mixed   $needle    The needle
 * @param      array   $haystack  The haystack
 * @param      boolean  $strict   Check type of $needle in the $haystack?
 *
 * @return     boolean  Returns TRUE if $needle is found in $haystack
 */
function in_array_r($needle, $haystack, $strict = false) {
    foreach ($haystack as $item) {
        if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
            return true;
        }
    }

    return false;
}
