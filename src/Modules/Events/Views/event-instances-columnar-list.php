
<div class="whx4-event-instances-columns">
<?php
foreach ( $instances as $date ):
    $date_str = $date->format( 'Y-m-d' );

    echo ViewLoader::renderToString( 'event-instance-div', [
        'post_id'    => $post_id,
        'date_str'   => $date_str,
        'excluded'   => in_array( $date_str, $excluded, true ), //in_array( $date->format( 'Y-m-d' ), $excluded, true ),
        'replacement_id' => $replacement_id, //$replacements[ $date_str ] ?? null,
    ], 'events' );

endforeach;
?>
</div> <!-- close .whx4-event-instances-columns -->
