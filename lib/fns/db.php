<?php
/**
 * This file implements the database.
 *
 * - 1.0.0 - Adding donman_zipcodes table
 * - 2.0.0 - Adding donman_contacts table
 * - 3.0.0 - Adding donman_orphaned_donations table
 *
 * @author     Mwender
 * @since      2022
 */

global $donman_db_version;
$donman_db_version = '3.0.0';

function donman_install(){
  global $wpdb;
  global $donman_db_version;
  $installed_version = get_option( 'donman_db_version' );

  $charset_collate = $wpdb->get_charset_collate();
  require_once ABSPATH . 'wp-admin/includes/upgrade.php';

  $table_name = $wpdb->prefix . 'donman_zipcodes';
  if( $installed_version != $donman_db_version ){
    $sql = "CREATE TABLE $table_name (
      ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      ZIPCode mediumint(5) unsigned NOT NULL,
      ZIPType char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
      CityName varchar(35) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
      CityType char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
      CountyName varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
      CountyFIPS varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
      StateName varchar(35) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
      StateAbbr char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
      StateFIPS varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
      MSACode varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
      AreaCode varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
      TimeZone varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
      UTC mediumint(9) NOT NULL,
      DST char(1) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
      Latitude decimal(14,7) NOT NULL,
      Longitude decimal(14,7) NOT NULL,
      PRIMARY KEY  (ID),
      KEY ZIPCode (ZIPCode),
      KEY CityName (CityName),
      KEY StateName (StateName),
      KEY city_stateabbr (CityName,StateAbbr),
      KEY StateAbbr (StateAbbr)
    ) $charset_collate;";
  }
  dbDelta( $sql );

  $table_name = $wpdb->prefix . 'donman_contacts';
  if( $installed_version != $donman_db_version ){
    $sql = "CREATE TABLE $table_name (
      ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      store_name varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
      zipcode bigint(10) unsigned NOT NULL,
      email_address varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
      receive_emails tinyint(1) unsigned NOT NULL DEFAULT '1',
      unsubscribe_hash varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
      priority tinyint(1) unsigned NOT NULL DEFAULT '0',
      show_in_results tinyint(1) unsigned NOT NULL DEFAULT '0',
      last_donation_report varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
      PRIMARY KEY  (ID)
    ) $charset_collate;";
  }
  dbDelta( $sql );

  $table_name = $wpdb->prefix . 'donman_orphaned_donations';
  if( $installed_version != $donman_db_version ){
    $sql = "CREATE TABLE $table_name (
      ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      donation_id bigint(20) unsigned DEFAULT NULL,
      contact_id bigint(20) unsigned DEFAULT NULL,
      timestamp datetime DEFAULT NULL,
      PRIMARY KEY  (ID)
    ) $charset_collate;";
  }
  dbDelta( $sql );

  update_option( 'donman_db_version', $donman_db_version );
}
register_activation_hook( __FILE__, 'donman_install' );

function donman_update_db_check() {
    global $donman_db_version;
    if ( get_site_option( 'donman_db_version' ) != $donman_db_version ) {
        donman_install();
    }
}
add_action( 'plugins_loaded', 'donman_update_db_check' );