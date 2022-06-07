<?php

function cptui_register_my_taxes_pickup_location() {

  /**
   * Taxonomy: Pickup Locations.
   */

  $labels = [
    "name" => __( "Pickup Locations", "donation-manager" ),
    "singular_name" => __( "Pickup Location", "donation-manager" ),
    "menu_name" => __( "Pickup Locations", "donation-manager" ),
    "all_items" => __( "All Pickup Locations", "donation-manager" ),
    "edit_item" => __( "Edit Pickup Location", "donation-manager" ),
    "view_item" => __( "View Pickup Location", "donation-manager" ),
    "update_item" => __( "Update Pickup Location name", "donation-manager" ),
    "add_new_item" => __( "Add new Pickup Location", "donation-manager" ),
    "new_item_name" => __( "New Pickup Location name", "donation-manager" ),
    "parent_item" => __( "Parent Pickup Location", "donation-manager" ),
    "parent_item_colon" => __( "Parent Pickup Location:", "donation-manager" ),
    "search_items" => __( "Search Pickup Locations", "donation-manager" ),
    "popular_items" => __( "Popular Pickup Locations", "donation-manager" ),
    "separate_items_with_commas" => __( "Separate Pickup Locations with commas", "donation-manager" ),
    "add_or_remove_items" => __( "Add or remove Pickup Locations", "donation-manager" ),
    "choose_from_most_used" => __( "Choose from the most used Pickup Locations", "donation-manager" ),
    "not_found" => __( "No Pickup Locations found", "donation-manager" ),
    "no_terms" => __( "No Pickup Locations", "donation-manager" ),
    "items_list_navigation" => __( "Pickup Locations list navigation", "donation-manager" ),
    "items_list" => __( "Pickup Locations list", "donation-manager" ),
    "back_to_items" => __( "Back to Pickup Locations", "donation-manager" ),
    "name_field_description" => __( "The name is how it appears on your site.", "donation-manager" ),
    "parent_field_description" => __( "Assign a parent term to create a hierarchy. The term Jazz, for example, would be the parent of Bebop and Big Band.", "donation-manager" ),
    "slug_field_description" => __( "The slug is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.", "donation-manager" ),
    "desc_field_description" => __( "The description is not prominent by default; however, some themes may show it.", "donation-manager" ),
  ];

  
  $args = [
    "label" => __( "Pickup Locations", "donation-manager" ),
    "labels" => $labels,
    "public" => false,
    "publicly_queryable" => true,
    "hierarchical" => false,
    "show_ui" => true,
    "show_in_menu" => true,
    "show_in_nav_menus" => true,
    "query_var" => true,
    "rewrite" => [ 'slug' => 'pickup_location', 'with_front' => true, ],
    "show_admin_column" => false,
    "show_in_rest" => true,
    "show_tagcloud" => false,
    "rest_base" => "pickup_location",
    "rest_controller_class" => "WP_REST_Terms_Controller",
    "rest_namespace" => "wp/v2",
    "show_in_quick_edit" => false,
    "sort" => false,
    "show_in_graphql" => false,
  ];
  register_taxonomy( "pickup_location", [ "organization" ], $args );
}
add_action( 'init', 'cptui_register_my_taxes_pickup_location' );
