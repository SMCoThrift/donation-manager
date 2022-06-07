<?php

function cptui_register_my_cpts_organization() {

  /**
   * Post Type: Organizations.
   */

  $labels = [
    "name" => __( "Organizations", "donation-manager" ),
    "singular_name" => __( "Organization", "donation-manager" ),
    "menu_name" => __( "My Organizations", "donation-manager" ),
    "all_items" => __( "All Organizations", "donation-manager" ),
    "add_new" => __( "Add new", "donation-manager" ),
    "add_new_item" => __( "Add new Organization", "donation-manager" ),
    "edit_item" => __( "Edit Organization", "donation-manager" ),
    "new_item" => __( "New Organization", "donation-manager" ),
    "view_item" => __( "View Organization", "donation-manager" ),
    "view_items" => __( "View Organizations", "donation-manager" ),
    "search_items" => __( "Search Organizations", "donation-manager" ),
    "not_found" => __( "No Organizations found", "donation-manager" ),
    "not_found_in_trash" => __( "No Organizations found in trash", "donation-manager" ),
    "parent" => __( "Parent Organization:", "donation-manager" ),
    "featured_image" => __( "Featured image for this Organization", "donation-manager" ),
    "set_featured_image" => __( "Set featured image for this Organization", "donation-manager" ),
    "remove_featured_image" => __( "Remove featured image for this Organization", "donation-manager" ),
    "use_featured_image" => __( "Use as featured image for this Organization", "donation-manager" ),
    "archives" => __( "Organization archives", "donation-manager" ),
    "insert_into_item" => __( "Insert into Organization", "donation-manager" ),
    "uploaded_to_this_item" => __( "Upload to this Organization", "donation-manager" ),
    "filter_items_list" => __( "Filter Organizations list", "donation-manager" ),
    "items_list_navigation" => __( "Organizations list navigation", "donation-manager" ),
    "items_list" => __( "Organizations list", "donation-manager" ),
    "attributes" => __( "Organizations attributes", "donation-manager" ),
    "name_admin_bar" => __( "Organization", "donation-manager" ),
    "item_published" => __( "Organization published", "donation-manager" ),
    "item_published_privately" => __( "Organization published privately.", "donation-manager" ),
    "item_reverted_to_draft" => __( "Organization reverted to draft.", "donation-manager" ),
    "item_scheduled" => __( "Organization scheduled", "donation-manager" ),
    "item_updated" => __( "Organization updated.", "donation-manager" ),
    "parent_item_colon" => __( "Parent Organization:", "donation-manager" ),
  ];

  $args = [
    "label" => __( "Organizations", "donation-manager" ),
    "labels" => $labels,
    "description" => "",
    "public" => true,
    "publicly_queryable" => true,
    "show_ui" => true,
    "show_in_rest" => true,
    "rest_base" => "",
    "rest_controller_class" => "WP_REST_Posts_Controller",
    "rest_namespace" => "wp/v2",
    "has_archive" => false,
    "show_in_menu" => true,
    "show_in_nav_menus" => true,
    "delete_with_user" => false,
    "exclude_from_search" => false,
    "capability_type" => "post",
    "map_meta_cap" => true,
    "hierarchical" => false,
    "can_export" => false,
    "rewrite" => [ "slug" => "organization", "with_front" => true ],
    "query_var" => true,
    "menu_position" => 6,
    "menu_icon" => "dashicons-groups",
    "supports" => [ "title", "editor", "thumbnail" ],
    "show_in_graphql" => false,
  ];

  register_post_type( "organization", $args );
}
add_action( 'init', 'cptui_register_my_cpts_organization' );
