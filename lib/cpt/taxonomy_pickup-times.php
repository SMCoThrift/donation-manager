<?php

function cptui_register_my_taxes_pickup_time() {

  /**
   * Taxonomy: Pickup Times.
   */

  $labels = [
    "name" => __( "Pickup Times", "donation-manager" ),
    "singular_name" => __( "Pickup Time", "donation-manager" ),
    "menu_name" => __( "Pickup Times", "donation-manager" ),
    "all_items" => __( "All Pickup Times", "donation-manager" ),
    "edit_item" => __( "Edit Pickup Time", "donation-manager" ),
    "view_item" => __( "View Pickup Time", "donation-manager" ),
    "update_item" => __( "Update Pickup Time name", "donation-manager" ),
    "add_new_item" => __( "Add new Pickup Time", "donation-manager" ),
    "new_item_name" => __( "New Pickup Time name", "donation-manager" ),
    "parent_item" => __( "Parent Pickup Time", "donation-manager" ),
    "parent_item_colon" => __( "Parent Pickup Time:", "donation-manager" ),
    "search_items" => __( "Search Pickup Times", "donation-manager" ),
    "popular_items" => __( "Popular Pickup Times", "donation-manager" ),
    "separate_items_with_commas" => __( "Separate Pickup Times with commas", "donation-manager" ),
    "add_or_remove_items" => __( "Add or remove Pickup Times", "donation-manager" ),
    "choose_from_most_used" => __( "Choose from the most used Pickup Times", "donation-manager" ),
    "not_found" => __( "No Pickup Times found", "donation-manager" ),
    "no_terms" => __( "No Pickup Times", "donation-manager" ),
    "items_list_navigation" => __( "Pickup Times list navigation", "donation-manager" ),
    "items_list" => __( "Pickup Times list", "donation-manager" ),
    "back_to_items" => __( "Back to Pickup Times", "donation-manager" ),
    "name_field_description" => __( "The name is how it appears on your site.", "donation-manager" ),
    "parent_field_description" => __( "Assign a parent term to create a hierarchy. The term Jazz, for example, would be the parent of Bebop and Big Band.", "donation-manager" ),
    "slug_field_description" => __( "The slug is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.", "donation-manager" ),
    "desc_field_description" => __( "The description is not prominent by default; however, some themes may show it.", "donation-manager" ),
  ];

  
  $args = [
    "label" => __( "Pickup Times", "donation-manager" ),
    "labels" => $labels,
    "public" => false,
    "publicly_queryable" => true,
    "hierarchical" => false,
    "show_ui" => true,
    "show_in_menu" => true,
    "show_in_nav_menus" => true,
    "query_var" => true,
    "rewrite" => [ 'slug' => 'pickup_time', 'with_front' => true, ],
    "show_admin_column" => false,
    "show_in_rest" => true,
    "show_tagcloud" => false,
    "rest_base" => "pickup_time",
    "rest_controller_class" => "WP_REST_Terms_Controller",
    "rest_namespace" => "wp/v2",
    "show_in_quick_edit" => false,
    "sort" => false,
    "show_in_graphql" => false,
  ];
  register_taxonomy( "pickup_time", [ "organization" ], $args );
}
add_action( 'init', 'cptui_register_my_taxes_pickup_time' );

