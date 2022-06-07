<?php

function cptui_register_my_taxes_donation_option() {

  /**
   * Taxonomy: Donation Options.
   */

  $labels = [
    "name" => __( "Donation Options", "donation-manager" ),
    "singular_name" => __( "Donation Option", "donation-manager" ),
    "menu_name" => __( "Donation Options", "donation-manager" ),
    "all_items" => __( "All Donation Options", "donation-manager" ),
    "edit_item" => __( "Edit Donation Option", "donation-manager" ),
    "view_item" => __( "View Donation Option", "donation-manager" ),
    "update_item" => __( "Update Donation Option name", "donation-manager" ),
    "add_new_item" => __( "Add new Donation Option", "donation-manager" ),
    "new_item_name" => __( "New Donation Option name", "donation-manager" ),
    "parent_item" => __( "Parent Donation Option", "donation-manager" ),
    "parent_item_colon" => __( "Parent Donation Option:", "donation-manager" ),
    "search_items" => __( "Search Donation Options", "donation-manager" ),
    "popular_items" => __( "Popular Donation Options", "donation-manager" ),
    "separate_items_with_commas" => __( "Separate Donation Options with commas", "donation-manager" ),
    "add_or_remove_items" => __( "Add or remove Donation Options", "donation-manager" ),
    "choose_from_most_used" => __( "Choose from the most used Donation Options", "donation-manager" ),
    "not_found" => __( "No Donation Options found", "donation-manager" ),
    "no_terms" => __( "No Donation Options", "donation-manager" ),
    "items_list_navigation" => __( "Donation Options list navigation", "donation-manager" ),
    "items_list" => __( "Donation Options list", "donation-manager" ),
    "back_to_items" => __( "Back to Donation Options", "donation-manager" ),
    "name_field_description" => __( "The name is how it appears on your site.", "donation-manager" ),
    "parent_field_description" => __( "Assign a parent term to create a hierarchy. The term Jazz, for example, would be the parent of Bebop and Big Band.", "donation-manager" ),
    "slug_field_description" => __( "The slug is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.", "donation-manager" ),
    "desc_field_description" => __( "The description is not prominent by default; however, some themes may show it.", "donation-manager" ),
  ];

  
  $args = [
    "label" => __( "Donation Options", "donation-manager" ),
    "labels" => $labels,
    "public" => false,
    "publicly_queryable" => true,
    "hierarchical" => false,
    "show_ui" => true,
    "show_in_menu" => true,
    "show_in_nav_menus" => false,
    "query_var" => true,
    "rewrite" => [ 'slug' => 'donation_option', 'with_front' => true, ],
    "show_admin_column" => false,
    "show_in_rest" => true,
    "show_tagcloud" => false,
    "rest_base" => "donation_option",
    "rest_controller_class" => "WP_REST_Terms_Controller",
    "rest_namespace" => "wp/v2",
    "show_in_quick_edit" => false,
    "sort" => false,
    "show_in_graphql" => false,
  ];
  register_taxonomy( "donation_option", [ "organization" ], $args );
}
add_action( 'init', 'cptui_register_my_taxes_donation_option' );

