<?php

function cptui_register_my_cpts_donation() {

  /**
   * Post Type: Donations.
   */

  $labels = [
    "name" => __( "Donations", "hello-elementor" ),
    "singular_name" => __( "Donation", "hello-elementor" ),
    "menu_name" => __( "My Donations", "hello-elementor" ),
    "all_items" => __( "All Donations", "hello-elementor" ),
    "add_new" => __( "Add new", "hello-elementor" ),
    "add_new_item" => __( "Add new Donation", "hello-elementor" ),
    "edit_item" => __( "Edit Donation", "hello-elementor" ),
    "new_item" => __( "New Donation", "hello-elementor" ),
    "view_item" => __( "View Donation", "hello-elementor" ),
    "view_items" => __( "View Donations", "hello-elementor" ),
    "search_items" => __( "Search Donations", "hello-elementor" ),
    "not_found" => __( "No Donations found", "hello-elementor" ),
    "not_found_in_trash" => __( "No Donations found in trash", "hello-elementor" ),
    "parent" => __( "Parent Donation:", "hello-elementor" ),
    "featured_image" => __( "Featured image for this Donation", "hello-elementor" ),
    "set_featured_image" => __( "Set featured image for this Donation", "hello-elementor" ),
    "remove_featured_image" => __( "Remove featured image for this Donation", "hello-elementor" ),
    "use_featured_image" => __( "Use as featured image for this Donation", "hello-elementor" ),
    "archives" => __( "Donation archives", "hello-elementor" ),
    "insert_into_item" => __( "Insert into Donation", "hello-elementor" ),
    "uploaded_to_this_item" => __( "Upload to this Donation", "hello-elementor" ),
    "filter_items_list" => __( "Filter Donations list", "hello-elementor" ),
    "items_list_navigation" => __( "Donations list navigation", "hello-elementor" ),
    "items_list" => __( "Donations list", "hello-elementor" ),
    "attributes" => __( "Donations attributes", "hello-elementor" ),
    "name_admin_bar" => __( "Donation", "hello-elementor" ),
    "item_published" => __( "Donation published", "hello-elementor" ),
    "item_published_privately" => __( "Donation published privately.", "hello-elementor" ),
    "item_reverted_to_draft" => __( "Donation reverted to draft.", "hello-elementor" ),
    "item_scheduled" => __( "Donation scheduled", "hello-elementor" ),
    "item_updated" => __( "Donation updated.", "hello-elementor" ),
    "parent_item_colon" => __( "Parent Donation:", "hello-elementor" ),
  ];

  $args = [
    "label" => __( "Donations", "hello-elementor" ),
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
    "rewrite" => [ "slug" => "donation", "with_front" => true ],
    "query_var" => true,
    "menu_position" => 8,
    "menu_icon" => "dashicons-archive",
    "supports" => [ "title", "editor" ],
    "show_in_graphql" => false,
  ];

  register_post_type( "donation", $args );
}

add_action( 'init', 'cptui_register_my_cpts_donation' );

