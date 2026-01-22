<?php
/**
 * The template for displaying Event archive pages
 *
 * Based on apostle theme archive
 *
 */

get_header(); ?>
<!-- wpt: archive-events -->

    <div id="primary" class="content-area"><!-- fullwidth -->
        <main id="main" class="site-main">

            <header class="page-header">
                <h1 class="page-title archive">Events</h1>
            </header><!-- .page-header -->

            <?php
            $scope = get_query_var('scope');
			if ( $scope ):
				echo 'scope: ' . $scope . "<br />";
			endif;
            /*if ( is_post_type_archive() ) {
                $cpt_args = null;
                
                global $wp_query;
                if ( $cpt_args ) { 
                    //$args = $cpt_args;
                    echo '<!-- about to merge cpt_args with query_vars -->';
                    $args = array_merge( $wp_query->query_vars, $cpt_args );
                } else { 
                    $args = $wp_query->query_vars;
                }
            
                echo '<!-- merged args'.": <pre>".print_r( $args, true )."</pre> -->";
                query_posts( $args );
            }*/

            //
            if ( have_posts() ) {
            
                // Loop through the posts 
                while ( have_posts() ) {
                    the_post();

                    $post_type = atc_get_type_for_template();
                    echo "<!-- post_type_for_template: ".$post_type." -->";
                    get_template_part( 'template-parts/content', $post_type );

                } // endwhile;

                // Previous/next page navigation.
                the_posts_pagination(
                    array(
                        'prev_text'          => __( 'Previous page', 'twentysixteen' ),
                        'next_text'          => __( 'Next page', 'twentysixteen' ),
                        'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'twentysixteen' ) . ' </span>',
                    )
                );

            // If no content, include the "No posts found" template.
            } else {
            
                if ( empty( $featured_posts ) ) {
                    get_template_part( 'template-parts/content', 'none' );
                }
                
            }
        ?>

        </main><!-- .site-main -->
    </div><!-- .content-area -->

<?php if ( !function_exists('devmode_active') || devmode_active() != "ns") { get_sidebar(); } ?>
<?php get_footer(); ?>
