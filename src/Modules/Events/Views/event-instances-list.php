<h3>Event Instances</h3>
<div class="whx4-event-instances-list">
<!-- event-instances-list.php -->
<?php foreach ( $instances as $instance ): ?>
    <div class="event-instance">
        <a href="<?php echo esc_url( $instance['permalink'] ); ?>" target="_blank">
            <?php echo esc_html( $instance['datetime']->format( 'F j, Y' ) ); ?>
        </a>
        
        <?php if ( $instance['is_override'] ): ?>
            <span class="badge">Rescheduled</span>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<?php
/*foreach ( $instances as $instance ):
    $date = $instance['datetime'];
    $label = $date->format( 'M j, Y' );
    $date_str = $date->format( 'Y-m-d' );
    $is_excluded = in_array( $date_str, $excluded, true );
    //
    //echo '<pre>'.print_r($instance,true).'</pre>'; // tft
    ?>

    <div class="whx4-instance-block" data-date="<?php echo esc_attr( $date_str ); ?>" data-post-id="<?php echo esc_attr( $post_id ); ?>">
    <div class="whx4-instance-date"><?php echo esc_html( $label ); ?></div>
    <div class="whx4-instance-actions">

    <?php
    // TODO: replace buttons with Edit/View links
    if ( isset($replacements[ $date_str ] ) ) {
        echo '<a href="' . esc_url( get_edit_post_link( $replacements[ $date_str ] ) ) . '" target="_blank" class="button">Edit replacement</a>';
    } elseif ( $is_excluded ) {
        echo '<span class="icon-button disabled"><img src="'.WHX4_PLUGIN_URL.'assets/graphics/excluded.png" alt="Excluded"></span>&nbsp;';
        //echo '<span class="button disabled">Excluded</span> ';
        echo '<button type="button" class="button icon-button whx4-unexclude-date" data-action="unexclude_date"><img src="'.WHX4_PLUGIN_URL.'assets/graphics/unexclude.png" alt="UN-exclude"></button>';
    } else {
        echo '<button type="button" class="button icon-button whx4-exclude-date" data-action="exclude_date"><img src="'.WHX4_PLUGIN_URL.'assets/graphics/exclude.png" alt="Exclude"></button> ';
        echo '<button type="button" class="button icon-button whx4-create-replacement" data-action="create_replacement"><img src="'.WHX4_PLUGIN_URL.'assets/graphics/detach.png" alt="Create Replacement Event"></button>';
    }
    ?>
    </div> <!-- close .whx4-instance-actions -->
    </div> <!-- close .whx4-instance-block -->

<?php
endforeach;*/
?>
</div> <!-- close .whx4-event-instances-list -->
