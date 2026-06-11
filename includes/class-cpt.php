<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AFC_CPT {

    public function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'init', array( $this, 'register_category_taxonomy' ) );
    }

    public function register_post_type() {
        $labels = array(
            'name'               => __( 'Partners', 'afc-partner-directory' ),
            'singular_name'      => __( 'Partner', 'afc-partner-directory' ),
            'add_new'            => __( 'Add New Partner', 'afc-partner-directory' ),
            'add_new_item'       => __( 'Add New Partner', 'afc-partner-directory' ),
            'edit_item'          => __( 'Edit Partner', 'afc-partner-directory' ),
            'new_item'           => __( 'New Partner', 'afc-partner-directory' ),
            'view_item'          => __( 'View Partner', 'afc-partner-directory' ),
            'search_items'       => __( 'Search Partners', 'afc-partner-directory' ),
            'not_found'          => __( 'No partners found', 'afc-partner-directory' ),
            'not_found_in_trash' => __( 'No partners found in Trash', 'afc-partner-directory' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'partners' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 20,
            'menu_icon'          => 'dashicons-groups',
            'supports'           => array( 'title', 'thumbnail' ),
            'show_in_rest'       => true,
        );

        register_post_type( 'partner', $args );
    }

    public function register_category_taxonomy() {
        $labels = array(
            'name'          => __( 'Partner Categories', 'afc-partner-directory' ),
            'singular_name' => __( 'Partner Category', 'afc-partner-directory' ),
            'search_items'  => __( 'Search Categories', 'afc-partner-directory' ),
            'all_items'     => __( 'All Categories', 'afc-partner-directory' ),
            'edit_item'     => __( 'Edit Category', 'afc-partner-directory' ),
            'update_item'   => __( 'Update Category', 'afc-partner-directory' ),
            'add_new_item'  => __( 'Add New Category', 'afc-partner-directory' ),
            'new_item_name' => __( 'New Category Name', 'afc-partner-directory' ),
            'menu_name'     => __( 'Categories', 'afc-partner-directory' ),
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'partner-category' ),
            'show_in_rest'      => true,
        );

        register_taxonomy( 'partner_category', array( 'partner' ), $args );
    }
}
