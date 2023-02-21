<?php

function cptui_register_my_cpts_donation() {

  /**
   * Post Type: Donations.
   */

  $labels = [
    "name" => esc_html__( "Donations", "hello-elementor" ),
    "singular_name" => esc_html__( "Donation", "hello-elementor" ),
    "menu_name" => esc_html__( "My Donations", "hello-elementor" ),
    "all_items" => esc_html__( "All Donations", "hello-elementor" ),
    "add_new" => esc_html__( "Add new", "hello-elementor" ),
    "add_new_item" => esc_html__( "Add new Donation", "hello-elementor" ),
    "edit_item" => esc_html__( "Edit Donation", "hello-elementor" ),
    "new_item" => esc_html__( "New Donation", "hello-elementor" ),
    "view_item" => esc_html__( "View Donation", "hello-elementor" ),
    "view_items" => esc_html__( "View Donations", "hello-elementor" ),
    "search_items" => esc_html__( "Search Donations", "hello-elementor" ),
    "not_found" => esc_html__( "No Donations found", "hello-elementor" ),
    "not_found_in_trash" => esc_html__( "No Donations found in trash", "hello-elementor" ),
    "parent" => esc_html__( "Parent Donation:", "hello-elementor" ),
    "featured_image" => esc_html__( "Featured image for this Donation", "hello-elementor" ),
    "set_featured_image" => esc_html__( "Set featured image for this Donation", "hello-elementor" ),
    "remove_featured_image" => esc_html__( "Remove featured image for this Donation", "hello-elementor" ),
    "use_featured_image" => esc_html__( "Use as featured image for this Donation", "hello-elementor" ),
    "archives" => esc_html__( "Donation archives", "hello-elementor" ),
    "insert_into_item" => esc_html__( "Insert into Donation", "hello-elementor" ),
    "uploaded_to_this_item" => esc_html__( "Upload to this Donation", "hello-elementor" ),
    "filter_items_list" => esc_html__( "Filter Donations list", "hello-elementor" ),
    "items_list_navigation" => esc_html__( "Donations list navigation", "hello-elementor" ),
    "items_list" => esc_html__( "Donations list", "hello-elementor" ),
    "attributes" => esc_html__( "Donations attributes", "hello-elementor" ),
    "name_admin_bar" => esc_html__( "Donation", "hello-elementor" ),
    "item_published" => esc_html__( "Donation published", "hello-elementor" ),
    "item_published_privately" => esc_html__( "Donation published privately.", "hello-elementor" ),
    "item_reverted_to_draft" => esc_html__( "Donation reverted to draft.", "hello-elementor" ),
    "item_scheduled" => esc_html__( "Donation scheduled", "hello-elementor" ),
    "item_updated" => esc_html__( "Donation updated.", "hello-elementor" ),
    "parent_item_colon" => esc_html__( "Parent Donation:", "hello-elementor" ),
  ];

  $args = [
    "label" => esc_html__( "Donations", "hello-elementor" ),
    "labels" => $labels,
    "description" => "",
    "public" => true,
    "publicly_queryable" => false,
    "show_ui" => true,
    "show_in_rest" => false,
    "rest_base" => "",
    "rest_controller_class" => "WP_REST_Posts_Controller",
    "rest_namespace" => "wp/v2",
    "has_archive" => false,
    "show_in_menu" => true,
    "show_in_nav_menus" => true,
    "delete_with_user" => false,
    "exclude_from_search" => true,
    "capability_type" => "post",
    "map_meta_cap" => true,
    "hierarchical" => false,
    "can_export" => false,
    "rewrite" => [ "slug" => "donation", "with_front" => true ],
    "query_var" => true,
    "menu_position" => 9,
    "menu_icon" => "dashicons-archive",
    "supports" => [ "title", "editor" ],
    "show_in_graphql" => false,
  ];

  register_post_type( "donation", $args );
}

add_action( 'init', 'cptui_register_my_cpts_donation' );

