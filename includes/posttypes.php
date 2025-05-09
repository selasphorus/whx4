<?php

defined( 'ABSPATH' ) or die( 'Nope!' );

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

// Flush rewrite rules -- to be activated only temporarily for TS of CPT 404s
function whx4_flush_rewrite_rules() {
    flush_rewrite_rules();
}
//add_action( 'init', 'whx4_flush_rewrite_rules' );

/*
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
register_activation_hook( __FILE__, 'whx4_flush_rewrites' );
function whx4_flush_rewrites() {
    // call your CPT registration function here (it should also be hooked into 'init')
    myplugin_custom_post_types_registration();
    flush_rewrite_rules();
}
*/

// Get plugin options to determine which modules are active
$options = get_option( 'whx4_settings' );
if ( get_field('whx4_active_modules', 'option') ) { $active_modules = get_field('whx4_active_modules', 'option'); } else { $active_modules = array(); }
//if ( isset($options['options_whx4_active_modules']) ) { $active_modules = unserialize($options['options_whx4_active_modules']); } else { $active_modules = array(); }

function whx4_custom_caps() {
    $use_custom_caps = false;
    if ( isset($options['use_custom_caps']) && !empty($options['use_custom_caps']) ) {
        $use_custom_caps = true;
    }
    return $use_custom_caps;
}

// TODO: change "person" to "individual", to better include plants and animals? w/ ACF field groups based on category/species
if ( in_array('people', $active_modules ) ) { // && !post_type_exists('person')

    // Person
    function register_post_type_person() {

        if ( whx4_custom_caps() ) { $caps = array('person', 'people'); } else { $caps = "post"; }
        //if ( whx4_custom_caps() ) { $caps = "person"; } else { $caps = "post"; }

        $labels = array(
            'name' => __( 'People', 'whx4' ),
            'singular_name' => __( 'Person', 'whx4' ),
            'add_new' => __( 'New Person', 'whx4' ),
            'add_new_item' => __( 'Add New Person', 'whx4' ),
            'edit_item' => __( 'Edit Person', 'whx4' ),
            'new_item' => __( 'New Person', 'whx4' ),
            'view_item' => __( 'View Person', 'whx4' ),
            'search_items' => __( 'Search People', 'whx4' ),
            'not_found' =>  __( 'No People Found', 'whx4' ),
            'not_found_in_trash' => __( 'No People found in Trash', 'whx4' ),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable'=> true,
            'show_ui'             => true,
            'show_in_menu'         => true,
            'query_var'            => true,
            'rewrite'            => array( 'slug' => 'people' ), // permalink structure slug
            'capability_type'    => $caps,
            'map_meta_cap'        => true,
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_icon'            => 'dashicons-groups',
            //'menu_position'        => null,
            'supports'             => array( 'title', 'author', 'editor', 'excerpt', 'revisions', 'thumbnail', 'custom-fields', 'page-attributes' ),
            'taxonomies'        => array( 'person_category', 'person_title', 'admin_tag' ), //, 'person_tag', 'people_category'
            'show_in_rest'        => false, // false = use classic, not block editor
            //'delete_with_user'     => false,
        );

        register_post_type( 'person', $args );

    }
    add_action( 'init', 'register_post_type_person' );

    // Identity
    function register_post_type_identity() {

        if ( whx4_custom_caps() ) { $caps = "person"; } else { $caps = "post"; }

        $labels = array(
            'name' => __( 'Identities', 'whx4' ),
            'singular_name' => __( 'Identity', 'whx4' ),
            'add_new' => __( 'New Identity', 'whx4' ),
            'add_new_item' => __( 'Add New Identity', 'whx4' ),
            'edit_item' => __( 'Edit Identity', 'whx4' ),
            'new_item' => __( 'New Identity', 'whx4' ),
            'view_item' => __( 'View Identity', 'whx4' ),
            'search_items' => __( 'Search Identities', 'whx4' ),
            'not_found' =>  __( 'No Identities Found', 'whx4' ),
            'not_found_in_trash' => __( 'No Identities found in Trash', 'whx4' ),
            /*
            'menu_name' => 'Identities',
            'all_items' => 'Identities',
            'view_items' => 'View Identities',
            'parent_item_colon' => 'Parent Identity:',
            'archives' => 'Identity Archives',
            'attributes' => 'Identity Attributes',
            'insert_into_item' => 'Insert into identity',
            'uploaded_to_this_item' => 'Uploaded to this identity',
            'filter_items_list' => 'Filter identities list',
            'filter_by_date' => 'Filter identities by date',
            'items_list_navigation' => 'Identities list navigation',
            'items_list' => 'Identities list',
            'item_published' => 'Identity published.',
            'item_published_privately' => 'Identity published privately.',
            'item_reverted_to_draft' => 'Identity reverted to draft.',
            'item_scheduled' => 'Identity scheduled.',
            'item_updated' => 'Identity updated.',
            'item_link' => 'Identity Link',
            'item_link_description' => 'A link to a identity.',
            */
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable'=> true,
            'show_ui'             => true,
            'show_in_menu'         => 'edit.php?post_type=person',
            'query_var'            => true,
            'rewrite'            => array( 'slug' => 'identities' ), // permalink structure slug
            'capability_type'    => $caps,
            'map_meta_cap'        => true,
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_icon'            => 'dashicons-groups',
            'menu_position'        => null,
            'supports'             => array( 'title', 'author', 'editor', 'excerpt', 'revisions', 'thumbnail', 'custom-fields', 'page-attributes' ),
            'taxonomies'        => array( 'person_category' ),
            'show_in_rest'        => false, // false = use classic, not block editor
            'delete_with_user'     => false,
        );

        register_post_type( 'identity', $args );

    }
    add_action( 'init', 'register_post_type_identity' );

    // Group
    // TODO: Figure out how to allow for individual sites to customize labels -- e.g. "Ensembles" for STC(?)
    function register_post_type_group() {

        if ( whx4_custom_caps() ) { $caps = "group"; } else { $caps = "post"; }

        $labels = array(
            'name' => __( 'Groups', 'whx4' ),
            'singular_name' => __( 'Group', 'whx4' ),
            'add_new' => __( 'New Group', 'whx4' ),
            'add_new_item' => __( 'Add New Group', 'whx4' ),
            'edit_item' => __( 'Edit Group', 'whx4' ),
            'new_item' => __( 'New Group', 'whx4' ),
            'view_item' => __( 'View Group', 'whx4' ),
            'search_items' => __( 'Search Groups', 'whx4' ),
            'not_found' =>  __( 'No Groups Found', 'whx4' ),
            'not_found_in_trash' => __( 'No Group found in Trash', 'whx4' ),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable'=> true,
            'show_ui'             => true,
            'show_in_menu'         => true,
            'query_var'            => true,
            'rewrite'            => array( 'slug' => 'groups' ), // permalink structure slug
            'capability_type'    => $caps,
            'map_meta_cap'         => true,
            'has_archive'          => true,
            'hierarchical'         => true,
            //'menu_icon'            => 'dashicons-groups',
            'menu_position'        => null,
            'supports'             => array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //
            'taxonomies'        => array( 'admin_tag', 'group_category' ),
            'show_in_rest'        => false,
        );

        register_post_type( 'group', $args );

    }
    add_action( 'init', 'register_post_type_group' );

    if ( whx4_queenbee() ) {
        // Roster -- WIP
        function register_post_type_roster() {

            if ( whx4_custom_caps() ) { $caps = "roster"; } else { $caps = "post"; }

            $labels = array(
                'name' => __( 'Rosters', 'whx4' ),
                'singular_name' => __( 'Roster', 'whx4' ),
                'add_new' => __( 'New Roster', 'whx4' ),
                'add_new_item' => __( 'Add New Roster', 'whx4' ),
                'edit_item' => __( 'Edit Roster', 'whx4' ),
                'new_item' => __( 'New Roster', 'whx4' ),
                'view_item' => __( 'View Roster', 'whx4' ),
                'search_items' => __( 'Search Rosters', 'whx4' ),
                'not_found' =>  __( 'No Rosters Found', 'whx4' ),
                'not_found_in_trash' => __( 'No Rosters found in Trash', 'whx4' ),
            );

            $args = array(
                'labels' => $labels,
                'public' => true,
                'publicly_queryable'=> true,
                'show_ui'             => true,
                'show_in_menu'         => true,
                'query_var'            => true,
                'rewrite'            => array( 'slug' => 'rosters' ), // permalink structure slug
                'capability_type'    => $caps,
                'map_meta_cap'         => true,
                'has_archive'          => true,
                'hierarchical'         => true,
                //'menu_icon'            => 'dashicons-groups',
                'menu_position'        => null,
                'supports'             => array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //
                'taxonomies'        => array( 'admin_tag' ), //, 'group_category'
                'show_in_rest'        => false,
            );

            register_post_type( 'roster', $args );

        }
        add_action( 'init', 'register_post_type_roster' );
    }
}


/*** PLACES, venues, addresses... ***/

// TBD: do we need this? or just groups -- buildings -- addresses...?
//if ( in_array('venues', $active_modules ) ) {
if ( in_array('places', $active_modules ) ) {

    // Venue
    function register_post_type_venue() {

        if ( whx4_custom_caps() ) { $caps = array('place', 'places'); } else { $caps = "post"; }
        //if ( whx4_custom_caps() ) { $caps = array('venue', 'venues'); } else { $caps = "post"; } // old: clean caps

        $labels = array(
            'name' => __( 'Venues', 'whx4' ),
            'singular_name' => __( 'Venue', 'whx4' ),
            'add_new' => __( 'New Venue', 'whx4' ),
            'add_new_item' => __( 'Add New Venue', 'whx4' ),
            'edit_item' => __( 'Edit Venue', 'whx4' ),
            'new_item' => __( 'New Venue', 'whx4' ),
            'view_item' => __( 'View Venue', 'whx4' ),
            'search_items' => __( 'Search Venues', 'whx4' ),
            'not_found' =>  __( 'No Venues Found', 'whx4' ),
            'not_found_in_trash' => __( 'No Venues found in Trash', 'whx4' ),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable'=> true,
            'show_ui'             => true,
            'show_in_menu'         => true,
            'query_var'            => true,
            'rewrite'            => array( 'slug' => 'venues' ), // permalink structure slug
            'capability_type'    => $caps,
            'map_meta_cap'        => true,
            'has_archive'         => true,
            'hierarchical'        => true,
            'menu_icon'            => 'dashicons-admin-multisite',
            'menu_position'        => null,
            'supports'             => array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //
            'taxonomies'        => array( 'admin_tag', 'venue_category' ),
            'show_in_rest'        => false, // i.e. false = use classic, not block editor
        );

        register_post_type( 'venue', $args );

    }
    add_action( 'init', 'register_post_type_venue' );

}

/*** ADDRESSES ***/
// An address record should link a person or group with a location -- WIP 11/04/24
// Multiple addresses may be needed to capture formatting differences if mailing list management is a concern
// *** TODO: also add CPT: location? or VENUE for use when EM is not active? TBD...
// Check for EM activation...
// Fields to add via ACF: cross_street, neighborhood, location_status, notes... related_entities....
// Add those same fields to EM location if EM is active?
//if ( in_array('places', $active_modules ) ) {
if ( in_array('addresses', $active_modules ) ) {

    // Address
    function register_post_type_address() {

        if ( whx4_custom_caps() ) { $caps = array('place', 'places'); } else { $caps = "post"; }
        // old => clean caps:
        //if ( whx4_custom_caps() ) { $caps = array('address', 'addresses'); } else { $caps = "post"; }
        //if ( whx4_custom_caps() ) { $caps = array('location', 'locations'); } else { $caps = "post"; }

        $labels = array(
            'name' => __( 'Addresses', 'whx4' ),
            'singular_name' => __( 'Address', 'whx4' ),
            'add_new' => __( 'New Address', 'whx4' ),
            'add_new_item' => __( 'Add New Address', 'whx4' ),
            'edit_item' => __( 'Edit Address', 'whx4' ),
            'new_item' => __( 'New Address', 'whx4' ),
            'view_item' => __( 'View Address', 'whx4' ),
            'search_items' => __( 'Search Addresses', 'whx4' ),
            'not_found' =>  __( 'No Addresses Found', 'whx4' ),
            'not_found_in_trash' => __( 'No Addresses found in Trash', 'whx4' ),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable'=> true,
            'show_ui'              => true,
            'show_in_menu'         => 'edit.php?post_type=venue',
            'query_var'            => true,
            'rewrite'            => array( 'slug' => 'addresses' ), // permalink structure slug
            'capability_type'    => $caps,
            'map_meta_cap'        => true,
            'has_archive'         => true,
            'hierarchical'        => false,
            //'menu_icon'            => 'dashicons-welcome-write-blog',
            'menu_position'        => null,
            'supports'             => array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //
            'taxonomies'        => array( 'admin_tag' ),
            'show_in_rest'        => false, // i.e. false = use classic, not block editor
        );

        register_post_type( 'address', $args );

    }
    add_action( 'init', 'register_post_type_address' ); // NB: Addresses as distinct from locations/venues/buildings

}


// TBD: does it make sense to have a separate post type for buildings?
// Consider possibilities: multiple buildings at same address/location over time; buildings re-purposed, e.g. synagogue turned into church, etc.
// Fields to include: location_id, related_entities, building_status (e.g. extant; raised), date_built, date_demolished, building_history (renovations etc.)
//if ( in_array('places', $active_modules ) ) {
if ( in_array('buildings', $active_modules ) ) {

    // Building -- change to "Structure"? TBD
    function register_post_type_building() {

        if ( whx4_custom_caps() ) { $caps = array('place', 'places'); } else { $caps = "post"; }
        //if ( whx4_custom_caps() ) { $caps = array('location', 'locations'); } else { $caps = "post"; } // old => clean caps

        $labels = array(
            'name' => __( 'Buildings', 'whx4' ),
            'singular_name' => __( 'Building', 'whx4' ),
            'add_new' => __( 'New Building', 'whx4' ),
            'add_new_item' => __( 'Add New Building', 'whx4' ),
            'edit_item' => __( 'Edit Building', 'whx4' ),
            'new_item' => __( 'New Building', 'whx4' ),
            'view_item' => __( 'View Building', 'whx4' ),
            'search_items' => __( 'Search Buildings', 'whx4' ),
            'not_found' =>  __( 'No Buildings Found', 'whx4' ),
            'not_found_in_trash' => __( 'No Buildings found in Trash', 'whx4' ),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable'=> true,
            'show_ui'              => true,
            'show_in_menu'         => 'edit.php?post_type=venue',
            'query_var'            => true,
            'rewrite'            => array( 'slug' => 'buildings' ), // permalink structure slug
            'capability_type'    => $caps,
            'map_meta_cap'        => true,
            'has_archive'         => true,
            'hierarchical'        => false,
            //'menu_icon'            => 'dashicons-welcome-write-blog',
            'menu_position'        => null,
            'supports'             => array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //
            'taxonomies'        => array( 'admin_tag' ),
            'show_in_rest'        => false, // i.e. false = use classic, not block editor
        );

        register_post_type( 'building', $args );

    }
    add_action( 'init', 'register_post_type_building' ); // NB: redundant w/ EM locations -- disabled for venues module 08/20/22
    // Fields to add via ACF to EM location: cross_street, neighborhood, location_status, notes... related_entities....


}


/*** EVENTS (extended EM) ***/

if ( in_array('events', $active_modules ) ) {

    // Event -- if EM is not active
    /* +~+~+~+~+~+~+~+~+~+~+~+~+~+~+~+~+~+~+~+~+~+~+~+~+ */
    /*if ( !post_type_exists('event') ) {

        function brdhv_register_post_type_event() {

            $labels = array(
                'name' => __( 'Birdhive Events', 'bkkp' ),
                'singular_name' => __( 'Event', 'bkkp' ),
                'add_new' => __( 'New Event', 'bkkp' ),
                'add_new_item' => __( 'Add New Event', 'bkkp' ),
                'edit_item' => __( 'Edit Event', 'bkkp' ),
                'new_item' => __( 'New Event', 'bkkp' ),
                'view_item' => __( 'View Event', 'bkkp' ),
                'search_items' => __( 'Search Events', 'bkkp' ),
                'not_found' =>  __( 'No Events Found', 'bkkp' ),
                'not_found_in_trash' => __( 'No Events found in Trash', 'bkkp' ),
            );

            $args = array(
                'labels' => $labels,
                'public' => true,
                'publicly_queryable' => true,
                'show_ui'            => true,
                'show_in_menu'       => true,
                'query_var'          => true,
                'rewrite'            => array( 'slug' => 'events' ),
                'capability_type' => $caps,
                'map_meta_cap'       => true,
                'has_archive'        => true,
                'hierarchical'       => false,
                //'menu_icon'          => 'dashicons-yes-alt',
                'menu_position'      => null,
                'supports'           => array( 'title', 'author', 'thumbnail', 'editor', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ), //
                'taxonomies' => array( 'admin_tag', 'event_category' ),
                'show_in_rest'        => true,
            );

            register_post_type( 'event', $args );

        }
        add_action( 'init', 'brdhv_register_post_type_event' );

    }*/

    // Event Series
    function register_post_type_event_series() {

        if ( whx4_custom_caps() ) { $caps = array('event', 'events'); } else { $caps = "post"; }

        $labels = array(
            'name' => __( 'Event Series', 'whx4' ),
            'singular_name' => __( 'Event Series', 'whx4' ),
            'add_new' => __( 'New Event Series', 'whx4' ),
            'add_new_item' => __( 'Add New Event Series', 'whx4' ),
            'edit_item' => __( 'Edit Event Series', 'whx4' ),
            'new_item' => __( 'New Event Series', 'whx4' ),
            'view_item' => __( 'View Event Series', 'whx4' ),
            'search_items' => __( 'Search Event Series', 'whx4' ),
            'not_found' =>  __( 'No Event Series Found', 'whx4' ),
            'not_found_in_trash' => __( 'No Event Series found in Trash', 'whx4' ),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable'=> true,
            'show_ui'              => true,
            'show_in_menu'         => 'edit.php?post_type=event',
            'query_var'            => true,
            'rewrite'            => array( 'slug' => 'event-series' ), // permalink structure slug
            'capability_type'    => $caps,
            'map_meta_cap'        => true,
            'has_archive'         => true,
            'hierarchical'        => false,
            //'menu_icon'            => 'dashicons-book',
            'menu_position'        => null,
            'supports'             => array( 'title', 'author', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'revisions', 'page-attributes' ),
            'taxonomies'        => array( 'admin_tag', 'event-categories' ), // 'event_category'
            'show_in_rest'        => false,
        );

        register_post_type( 'event_series', $args );

    }
    add_action( 'init', 'register_post_type_event_series' );

}


// ACF Bi-directional fields
// WIP!
// https://www.advancedcustomfields.com/resources/bidirectional-relationships/
if ( !function_exists( 'bidirectional_acf_update_value' ) ) {
    function bidirectional_acf_update_value( $value, $post_id, $field  ) {

        // vars
        $field_name = $field['name'];
        $field_key = $field['key'];
        $global_name = 'is_updating_' . $field_name;

        // bail early if this filter was triggered from the update_field() function called within the loop below
        // - this prevents an infinite loop
        if( !empty($GLOBALS[ $global_name ]) ) return $value;


        // set global variable to avoid inifite loop
        // - could also remove_filter() then add_filter() again, but this is simpler
        $GLOBALS[ $global_name ] = 1;


        // loop over selected posts and add this $post_id
        if( is_array($value) ) {

            foreach( $value as $post_id2 ) {

                // load existing related posts
                $value2 = get_field($field_name, $post_id2, false);

                // allow for selected posts to not contain a value
                if( empty($value2) ) {
                    $value2 = array();
                }

                // bail early if the current $post_id is already found in selected post's $value2
                if( in_array($post_id, $value2) ) continue;

                // append the current $post_id to the selected post's 'related_posts' value
                $value2[] = $post_id;

                // update the selected post's value (use field's key for performance)
                update_field($field_key, $value2, $post_id2);

            }

        }

        // find posts which have been removed
        $old_value = get_field($field_name, $post_id, false);

        if ( is_array($old_value) ) {

            foreach( $old_value as $post_id2 ) {

                // bail early if this value has not been removed
                if( is_array($value) && in_array($post_id2, $value) ) continue;

                // load existing related posts
                $value2 = get_field($field_name, $post_id2, false);

                // bail early if no value
                if( empty($value2) ) continue;

                // find the position of $post_id within $value2 so we can remove it
                $pos = array_search($post_id, $value2);

                // remove
                unset( $value2[ $pos] );

                // update the un-selected post's value (use field's key for performance)
                update_field($field_key, $value2, $post_id2);

            }

        }

        // reset global varibale to allow this filter to function as per normal
        $GLOBALS[ $global_name ] = 0;

        // return
        return $value;

    }
}

// WIP!
if ( !function_exists( 'acf_update_related_field_on_save' ) ) {
    function acf_update_related_field_on_save ( $post_id ) {

        // TODO: figure out how to handle repeater field sub_fields -- e.g. repertoire_events << event program_items

        // Get newly saved values -- all fields
        //$values = get_fields( $post_id );

        // Check the current (updated) value of a specific field.
        $rows = get_field('program_items', $post_id);
        if ( $rows ) {
            foreach( $rows as $row ) {
                if ( isset($row['program_item'][0]) ) {
                    foreach ( $row['program_item'] as $program_item_obj_id ) {
                        $item_post_type = get_post_type( $program_item_obj_id );
                        if ( $item_post_type == 'repertoire' ) {
                            $rep_related_events = get_field('related_events', $program_item_obj_id);
                            if ( $rep_related_events ) {
                                // Check to see if post_id is already saved to rep record
                            } else {
                                // No related_events set yet, so add the post_id
                                //update_field('related_events', $post_id, $program_item_obj_id );
                            }
                        }
                    }
                }
            }
        }

    }
}

/*
// WIP
add_action( 'acf/init', 'whx4_bidirectional_field_updates' );
function whx4_bidirectional_field_updates () {
    if ( in_array('events', $active_modules ) ) {
        add_filter('acf/update_value/name=series_events', 'bidirectional_acf_update_value', 10, 3);
    }
}
*/
?>
