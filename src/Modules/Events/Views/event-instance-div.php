<?php
$date  = strtotime($date_str);
//$label = $date->format( 'M j, Y' );
$label = $date_str; // tft
?>

<div class="whx4-instance-block" data-date="<?php echo esc_attr( $date_str ); ?>" data-post-id="<?php echo esc_attr( $post_id ); ?>">
<div class="whx4-instance-date"><?php echo esc_html( $label ); ?></div>
<div class="whx4-instance-actions">

<?php
if ( $replacement_id ) {
    echo '<a href="' . esc_url( get_edit_post_link( $replacement_id ) ) . '" target="_blank" class="button">Edit replacement</a>';
} elseif ( $excluded ) {
    echo '<span class="icon-button disabled"><img src="'.WHX4_PLUGIN_URL.'assets/graphics/excluded.png" alt="Excluded"></span>&nbsp;';
    //echo '<span class="button disabled">Excluded</span> ';
    echo '<button type="button" class="button icon-button whx4-unexclude-date" data-action="unexclude_date""><img src="'.WHX4_PLUGIN_URL.'assets/graphics/unexclude.png" alt="Exclude"></button>';
} else {
    echo '<button type="button" class="button icon-button whx4-exclude-date" data-action="exclude_date""><img src="'.WHX4_PLUGIN_URL.'assets/graphics/exclude.png" alt="Exclude"></button> ';
    echo '<button type="button" class="button icon-button whx4-create-replacement" data-action="create_replacement""><img src="'.WHX4_PLUGIN_URL.'assets/graphics/detach.png" alt="Create Replacement Event"></button>';
}
?>
</div> <!-- close .whx4-instance-actions -->
</div> <!-- close .whx4-instance-block -->
