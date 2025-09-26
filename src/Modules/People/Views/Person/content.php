<?php
use atc\WHx4\Core\PostTypeHandler;
// This view displays supplementary info -- the regular content template (e.g. {thetheme}/template-parts/content.php) handles title, content, featured image

/** @var WP_Post $post */
$handler = PostTypeHandler::getHandlerForPost($post);
$pID = $handler->getPostId(); // or ->getPostId($post)

// Specific meta (single value)
$firstName = $handler?->getPostMeta('first_name', true) ?? '';

// All meta
$meta = $handler?->getPostMeta(); // array of all post meta

// Person-specific data
$dates = ($handler && method_exists($handler, 'getPersonDates')) ? $handler->getPersonDates() : '';
$sn = ($handler && method_exists($handler, 'getSN')) ? $handler->getSN() : '';
?>

<?php
// This is a very, very rough draft -- much of the below should be broken up into additional methods in the Person class -- e.g. getPublications (to cover both compositions and other pubs? TBD)

// Group <> Titles & Associations
// WIP

// Awards
// WIP
// TODO: include theme-specific content? via apply_filters?

// Dates
/*
// TODO: figure out where to put this -- probably appended to post_title?
$dates = get_person_dates( $pID, true );
if ( $dates && $dates != "" && $dates != "(-)" ) {
    $info .= $dates;
}*/

// Compositions
// TODO: consider eliminating check for has_term, in case someone forgot to apply the appropriate category
if ( has_term( 'composers', 'person_category', $pID ) ) {
    // Get compositions
    $arr_obj_compositions = $handler->getRelatedPosts( $pID, 'repertoire', 'composer' );
    if ( $arr_obj_compositions ) {

        echo "<h3>Compositions:</h3>";

        //$info .= "<p>arr_compositions (".count($arr_compositions)."): <pre>".print_r($arr_compositions, true)."</pre></p>";
        foreach ( $arr_obj_compositions as $composition ) {
            //$info .= $composition->post_title."<br />";
            $rep_info = get_rep_info( $composition->ID, 'display', false, true );
            echo makeLink( get_permalink($composition->ID), $rep_info, "TEST rep title" )."<br />";
        }
    }
}

// TODO: arranger, transcriber, translator, librettist

// Publications
/*
    // Editions
    $arr_obj_editions = $handler->getRelatedPosts( $pID, 'edition', 'editor' );

    if ( $arr_obj_editions ) {

        echo '<div class="publications">';
        echo "<h3>Publications:</h3>";

        //$info .= "<p>arr_obj_editions (".count($arr_obj_editionss)."): <pre>".print_r($arr_obj_editions, true)."</pre></p>";
        foreach ( $arr_obj_editions as $edition ) {
            //$info .= $edition->post_title."<br />";
            $info .= make_link( get_permalink($edition->ID), $edition->post_title, "TEST edition title" )."<br />";
        }

        $info .= '</div>';
    }
*/

// Sermons
// TODO: check if is in clergy category?
$arr_obj_sermons = $handler->getRelatedPosts( $pID, 'sermon', 'sermon_author' );
if ( $arr_obj_sermons ) {

    echo '<div class="devview sermons">';
    echo "<h3>Sermons:</h3>";

    foreach ( $arr_obj_sermons as $sermon ) {
        //echo $sermon->post_title."<br />";
        echo make_link( get_permalink($sermon->ID), $sermon->post_title, "TEST sermon title" )."<br />";
    }

    echo '</div>';
}

// Related Events
    /*
    $wp_args = array(
        'posts_per_page'=> -1,
        'post_type'		=> 'event',
        'meta_query'	=> array(
            array(
                'key'		=> "personnel_XYZ_person", // name of custom field, with XYZ as a wildcard placeholder (must do this to avoid hashing)
                'compare' 	=> 'LIKE',
                'value' 	=> '"' . $pID . '"', // matches exactly "123", not just 123. This prevents a match for "1234"
            )
        ),
        'orderby'	=> 'meta_value',
        'order'     => 'DESC',
        'meta_key' 	=> '_event_start_date',
    );

    $query = new WP_Query( $wp_args );
    $event_posts = $query->posts;
    $info .= "<!-- wp_args: <pre>".print_r($wp_args,true)."</pre> -->";
    $info .= "<!-- Last SQL-Query: {$query->request} -->";

    if ( $event_posts ) {
        global $post;
        $info .= '<div class="devview em_events">';
        //-- STC
        $info .= '<h3>Events at Saint Thomas Church:</h3>';
        foreach($event_posts as $post) {
            setup_postdata($post);
            // TODO: modify to show title & event date as link text
            $event_title = get_the_title();
            $date_str = get_post_meta( get_the_ID(), '_event_start_date', true );
            if ( $date_str ) { $event_title .= ", ".$date_str; }
            $info .= make_link( get_the_permalink(), $event_title ) . "<br />";
        }
        $info .= '</div>';
    } else {
        $info .= "<!-- No related events found for post_id: $pID -->";
    }
    wp_reset_query();
    */

// Person Categories
$term_obj_list = get_the_terms( $pID, 'person_category' );
if ( $term_obj_list ) {
    $terms_string = join(', ', wp_list_pluck($term_obj_list, 'name'));
    echo '<div class="devview categories">';
    if ( $terms_string ) {
        echo "<p>Categories: ".$terms_string."</p>";
    }
    echo '</div>';
}
?>

<div>
Person view: content (partial/appended).

<hr />
Post ID: <pre><?php print_r($pID,true); ?></pre>
Post Meta: <pre><?php print_r($meta,true); ?></pre>

<?php
echo "dates: " . $dates . '<br />';
//echo "secret name: " . $sn . '<br />';
//echo "getPostID: " . $handler->getPostID($post);
?>
</div>
