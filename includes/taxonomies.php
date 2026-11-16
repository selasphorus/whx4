<?php

defined( 'ABSPATH' ) or die( 'Nope!' );

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

// Get plugin options to determine which modules are active
$options = get_option( 'whx4_settings' );
if ( get_field('whx4_active_modules', 'option') ) { $active_modules = get_field('whx4_active_modules', 'option'); } else { $active_modules = array(); }
//if ( isset($options['options_whx4_active_modules']) ) { $active_modules = $options['options_whx4_active_modules']; } else { $active_modules = array(); }

/*** Taxonomies for PEOPLE ***/

/*if ( in_array('people', $active_modules ) ) {

    // Custom Taxonomy: Person Title ==> TBD
    // TODO: phase out this taxonomy -- currently in use only for nycago -- replace with affiliations via person_role
    function register_taxonomy_person_title() {
        $labels = array(
            'name'              => _x( 'Person Titles', 'taxonomy general name' ),
            'singular_name'     => _x( 'Person Title', 'taxonomy singular name' ),
            'search_items'      => __( 'Search Person Titles' ),
            'all_items'         => __( 'All Person Titles' ),
            'parent_item'       => __( 'Parent Person Title' ),
            'parent_item_colon' => __( 'Parent Person Title:' ),
            'edit_item'         => __( 'Edit Person Title' ),
            'update_item'       => __( 'Update Person Title' ),
            'add_new_item'      => __( 'Add New Person Title' ),
            'new_item_name'     => __( 'New Person Title Name' ),
            'menu_name'         => __( 'Person Titles' ),
        );
        $args = array(
            'labels'            => $labels,
            'description'          => '',
            'public'               => true,
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => [ 'slug' => 'person_title' ],
        );
        register_taxonomy( 'person_title', [ 'person' ], $args );
    }
    add_action( 'init', 'register_taxonomy_person_title' );
}*/


/*** Taxonomies for EVENT PROGRAMS -- TMP Transitional until Programs module is operational ***/

if ( in_array('events', $active_modules ) && !in_array('programs', $active_modules ) ) {

    // Custom Taxonomy: Program Label ==> Ok WHx4
    // TODO: consider generalizing this taxonomy to something like info_label or item_label? Or at least remove the limit to events so it can also be used with e.g. Projects?
    function register_taxonomy_program_label() {
        $labels = array(
            'name'              => _x( 'Program Labels', 'taxonomy general name' ),
            'singular_name'     => _x( 'Program Label', 'taxonomy singular name' ),
            'search_items'      => __( 'Search Program Labels' ),
            'all_items'         => __( 'All Program Labels' ),
            'parent_item'       => __( 'Parent Program Label' ),
            'parent_item_colon' => __( 'Parent Program Label:' ),
            'edit_item'         => __( 'Edit Program Label' ),
            'update_item'       => __( 'Update Program Label' ),
            'add_new_item'      => __( 'Add New Program Label' ),
            'new_item_name'     => __( 'New Program Label Name' ),
            'menu_name'         => __( 'Program Labels' ),
        );
        $args = array(
            'labels'            => $labels,
            'description'          => '',
            'public'               => true,
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_in_menu'      => true,
            'show_admin_column' => true,
            'meta_box_cb'       => false,
            'query_var'         => true,
            'rewrite'           => [ 'slug' => 'program_label' ],
        );
        if ( whx4_custom_caps() ) {
            $cap = 'event_program';
            $args['capabilities'] = array(
                'manage_terms'  =>   'manage_'.$cap.'_terms',
                'edit_terms'    =>   'edit_'.$cap.'_terms',
                'delete_terms'  =>   'delete_'.$cap.'_terms',
                'assign_terms'  =>   'assign_'.$cap.'_terms',
            );
        }
        register_taxonomy( 'program_label', [ 'event', 'event_program' ], $args );
    }
    add_action( 'init', 'register_taxonomy_program_label' );

    // Event Categories -- EM slug: event-categories
    // Event Tags -- EM slug: event-tags

}
