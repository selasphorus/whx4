<?php

defined( 'ABSPATH' ) or die( 'Nope!' );

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin file, not much I can do when called directly.';
    exit;
}

/*********** POST/EVENT RELATIONSHIPS ***********/

function get_related_event( $post_id = null, $post_type = null, $link = true, $link_text = null )
{
    $logCtx = ['stc', 'events'];
    $info = ""; // init
    $ts_info = "";
    if ($post_id === null) { $post_id = get_the_ID(); }

    // If we don't have actual values for both parameters, there's not enough info to proceed
    if ($post_id === null || $post_type === null) { return null; }

    $event_id = get_related_posts( $post_id, $post_type, 'event', 'single' ); // get_related_posts( $post_id = null, $related_post_type = null, $related_field_name = null, $return = 'all' )
    //echo "event_id: $event_id; post_id: $post_id"; // tft
    //$ts_info .= "event_id: $event_id; post_id: post_id<br />"; // tft

    if ($event_id && $event_id !== "no posts") {
        if ($link === true) {
            $info .= '<a href="'. esc_url(get_the_permalink($event_id)) . '" title="'.get_the_title($event_id).'">';
            if ($link_text !== null) { $info .= $link_text; } else { $info .= get_the_title($event_id); }
            $info .= '</a>';
        } else {
            $info .= get_the_title($event_id);
        }
        //$info .= '<a href="'. esc_url(get_the_permalink($event_id)) . '" title="event_id: '.$event_id.'/post_id: '.$post_id.'">' . get_the_title($event_id) . '</a>';
    } else {
        //$ts_info .= "event_id: $event_id; post_id: post_id<br />";
        return null;
    }

    //$info .= '<a href="'. esc_url(get_permalink($event_id)) . '">' . get_the_title($event_id) . '</a>';

    if ( $ts_info != "" ) { $info .= '<div class="troubleshooting">'.$ts_info.'</div>'; }

    return $info;

}

// WIP: Get Related Events based on program info
// TODO: make this not so terribly slow!!!
function get_related_events ( $meta_field = null, $term_id = null, $return_fields = 'ids' )
{
    $logCtx = ['stc', 'events'];

    // Init vars
    $arr_info = array();
    $ts_info = "";

    // Determine meta_key based on field name, with XYZ as a wildcard placeholder (must do this to avoid hashing)
    if ( $meta_field == "program_label" ) {
        $meta_key = "program_items_XYZ_item_label";
    } else if ( $meta_field == "program_item" ) {
        $meta_key = "program_items_XYZ_program_item";
    } else if ( $meta_field == "role" ) {
        $meta_key = "personnel_XYZ_role";
    } else if ( $meta_field == "person" ) {
        $meta_key = "personnel_XYZ_person";
    } else {
        $meta_key = "";
    }

    $ts_info .= "meta_field: ".$meta_field."; meta_key: ".$meta_key."; term_id: ".$term_id."<br />";

    // Build query args
    $wp_args = array(
        'posts_per_page'=> -1,
        'post_type'        => 'event',
        'meta_query'    => array(
            array(
                'key'        => $meta_key,
                'compare'     => 'LIKE',
                //'value'     => $term_id,
                'value'     => '"' . $term_id . '"', // matches exactly "123", not just 123. This prevents a match for "1234"
            )
        ),
        'orderby'    => 'meta_value',
        'order'     => 'DESC',
        'meta_key'     => '_event_start_date',
        'fields' => $return_fields,
    );

    $query = new WP_Query( $wp_args );
    $event_posts = $query->posts;


    $ts_info .= "wp_args: <pre>".print_r($wp_args, true)."</pre>";
    $ts_info .= "event_posts: <pre>".print_r($event_posts, true)."</pre>";
    $ts_info .= "Last SQL-Query: <pre>{$query->request} </pre><br />";

    if ( $event_posts ) {
        // WIP
    } else {
        $ts_info .= "No related events found.<br />";
        //$ts_info .= "Last SQL-Query: <pre>{$query->request} </pre><br />";
        //$ts_info .= "Query object: <pre>{$query} </pre><br />";
    }

    $arr_info['event_posts'] = $event_posts;
    $arr_info['ts_info'] = $ts_info;

    return $arr_info;

}

/*** EVENT BOOKINGS ***/

// See sdg_placeholders


/*** EVENTS/WEBCASTS ***/

add_shortcode( 'display_webcasts', 'display_webcast_events' );
function display_webcast_events()
{
    // Ensure the global $post variable is in scope
    //global $post; // ??? Is this actually necessary here?
    $info = "";

    // Query Events Manager [EM] posts
    // TODO: test this...
    $wp_args = array(
        'post_type'         => 'event',
        'posts_per_page'    => 5,
        'scope'               => 'future',
        'tax_query'         => array(
            array(
                'taxonomy'     => 'event-categories',
                'field'     => 'slug',
                'terms'     => 'webcasts'
            )
        )
    );

    $result = new WP_Query( $wp_args );
    $upcoming_events = $result->posts;

    // Loop through the events: set up each one as
    // the current post then use template tags to
    // display the title and content
    if (count($upcoming_events) > 0) { $info .= "<h2>Upcoming</h2>"; }
    foreach ( $upcoming_events as $post ) {
        setup_postdata( $post );

        // This time, let's throw in an event-specific
        // template tag to show the date after the title!
        $info .= '<h4>' . $post->post_title . '</h4>';
        $event_date = get_post_meta( $post->ID, '_event_start_date', true );
        $info .= '<p>' . $event_date . '</p>';
        //$event_date = get_post_meta( $event_id, '_event_start_date', true );
    }

    // Query Events Manager [EM] posts
    // TODO: test this...
    $wp_args = array(
        'post_type'         => 'event',
        'posts_per_page'    => 5,
        'scope'               => 'past',
        'tax_query'         => array(
            array(
                'taxonomy'     => 'event-categories',
                'field'     => 'slug',
                'terms'     => 'webcasts'
            )
        )
    );

    $result = new WP_Query( $wp_args );
    $past_events = $result->posts;

    if (count($past_events) > 0) { $info .= "<h2>Past</h2>"; }
    foreach ( $past_events as $post ) {
        setup_postdata( $post );

        // This time, let's throw in an event-specific
        // template tag to show the date after the title!
        $info .= '<h3><a href="'. get_permalink($post->ID) . '">' . $post->post_title . '</a></h3>';
        $event_date = get_post_meta( $post->ID, '_event_start_date', true );
        $info .= '<p>' . $event_date . '</p>';
    }

    return $info;
}

/***** wip *****/

// Event archives -- top-of-page content

// Special Date Content
function get_special_date_content( $the_date = null )
{
    $logCtx = ['stc', 'events'];

    $info = "";
    $ts_info = "";
    $content = "";

    if ( empty($the_date) ) { return null; }

    if ( !preg_match('/[0-9]{4}-[0-9]{2}-[0-9]{2}/', $the_date) ) {
        $the_date = date_i18n('Y-m-d', strtotime($the_date) ); // format the date, as needed
    }

    /*
    $ts_info = "get_special_date_content<br />";
    $ts_info .= "the_date: '$the_date'<br />";
    $ts_info .= "print_r the_date: '".print_r($the_date, true)."'<br />"; // tft
    */

    // NB: set event record to "All day" and assign 'special-notice' event category

    // Build query args
    $wp_args = array(
        'posts_per_page'=> 1, // get one event only
        'post_type'        => 'event',
        'meta_query'    => array(
            array(
                'key'     => '_event_start_date',
                'value'   => $the_date,
            )
        ),
        'tax_query'    => array(
            array(
                'taxonomy' => 'event-categories',
                'field'    => 'slug',
                'terms'    => 'special-notice',
            )
        ),
    );

    $query = new WP_Query( $wp_args );
    $posts = $query->posts;

    if ( $posts ) {

        $timestamp = strtotime($the_date);
        $fixed_date_str = date("F d", $timestamp ); // day w/ leading zeros
        $ts_info .= "timestamp: '$timestamp'<br />";

        foreach ( $posts as $post ) {
            //$info .= "<pre>".print_r($post, true)."</pre>"; // tft
            $post_id = $post->ID;
            if ( $post_id ) {
                $notice_text = $post->post_content;
                $content .= $notice_text;
            }
        }

        $classes = "message centered special-notice";
        if ( str_word_count($content) < 75 ) { $classes .= " scalloped"; }
        $ts_info .= str_word_count($content)." words";

        // TS issue w/ paragraphs
        $content = wpautop($content);

        $info .= '<div class="'.$classes.'">';
        $info .= $content;
        $info .= '</div>';

    } else {

        $ts_info .= "No posts found by fcn get_special_date_content for date $the_date<br />";

    }

    if ( $ts_info != "" ) { $info .= '<div class="troubleshooting">'.$ts_info.'</div>'; }

    return $info;

}

// Add "series" to acceptable EM search parameters (attributes)
add_filter('em_events_get_default_search','whx4_custom_event_search_parameters',1,2);
add_filter('em_calendar_get_default_search','whx4_custom_event_search_parameters',1,2);
function whx4_custom_event_search_parameters($args, $array)
{
    $args['series'] = false; // registers 'series' (ID) as an acceptable value, set to false by default
    if ( !empty($array['series']) && is_numeric($array['series']) ) {
        $args['series'] = $array['series'];
    }
    //
    $args['context'] = false; // registers 'context' as an acceptable value, set to false by default (for snippet e.g.)
    if ( !empty($array['context']) ) {
        $args['context'] = $array['context'];
    }
    return $args;

}

// Program/Event info via Event CPT & ACF -- for Admin use/Troubleshooting
add_shortcode('display_event_stats', 'display_event_stats');
function display_event_stats( $atts = array() )
{
    $logCtx = ['stc', 'events', 'admin'];
    $info = ""; // init

    // Extract args
    $args = shortcode_atts( array(
        'post_id'    => get_the_ID(),
    ), $atts );
    extract( $args );

    $info .= 'ID: <span class="nb">'.$post_id.'</span>; ';
    $post   = get_post( $post_id );
    $post_meta = get_post_meta( $post_id );

    $recurrence_id = get_post_meta( $post_id, '_recurrence_id', true );
    if ( $recurrence_id ) { $info .= 'RID: <span class="nb">'.$recurrence_id.'</span>; '; }

    $parent_id = $post->post_parent;
    if ( $parent_id ) { $info .= 'parent_id: <span class="nb">'.$parent_id.'</span>; '; }

    // Get the personnel & program_items repeater field values (ACF)
    $personnel = get_field('personnel', $post_id);
    if ( is_array($personnel) && count($personnel) > 0 ) { $info .= '<span class="nb">'.count($personnel).'</span>'." pers.; "; }

    $program_items = get_field('program_items', $post_id);
    if ( is_array($program_items) && count($program_items) > 0 ) { $info .= '<span class="nb">'.count($program_items).'</span>'." prog.; "; }

    //Variable: Additional characters which will be considered as a 'word'
    $char_list = ""; /** MODIFY IF YOU LIKE.  Add characters inside the single quotes. **/
    //$char_list = '0123456789'; /** If you want to count numbers as 'words' **/
    //$char_list = '&@'; /** If you want count certain symbols as 'words' **/
    $word_count = str_word_count(strip_tags($post->post_content), 0, $char_list);
    $info .= '[<span class="nb">'.$word_count.'</span> words]';

    //$info .= "<pre>".print_r($post,true)."</pre>";
    //$info .= "<pre>".print_r($post_meta,true)."</pre>";
    //$info .= "Delete"; // add delete link...

    $info = '<span class="troubleshooting inline">'.$info.'</span>';

    return $info;
}

// Tidier slugs for recurring event instances
/*
function append_slug($data)
{
    global $post_ID;

    //if (empty($data['post_name'])) {
    if (!empty($data['post_name']) && $data['post_status'] == "publish" && $data['post_type'] == "post") {

        if( !is_numeric(substr($data['post_name'], -4)) ) {
              $random = rand(1111,9999);
              $data['post_name'] = sanitize_title($data['post_title'], $post_ID);
              $data['post_name'] .= '-' . $random;
          }

        $data['post_name'] = sanitize_title($data['post_title'], $post_ID);
        $data['post_name'] .= '-' . generate_arbitrary_number_here();
    }

    return $data;
}

add_filter('wp_insert_post_data', 'append_slug', 10);
*/
