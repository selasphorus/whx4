<?php
// Check to see if we're dealing with an individual instance of a recurring event
$event_instance = get_query_var('event_instance');
if ( $event_instance ) {
    echo 'event_instance: ' . $event_instance . "<br />";
}
?>

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
</div> <!-- close .whx4-event-instances-list -->
