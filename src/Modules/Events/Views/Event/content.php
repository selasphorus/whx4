<?php
/**
 * Event Content View
 * 
 * Displays event info including recurrences, if any
 * Pure presentation layer - all data preparation done in Event handler
 * 
 * @var string $startDate Event start date
 * @var array $viewData Prepared event data
 * @var array $postMeta Post meta for debug display
 * @var string $instancesListHtml Pre-rendered instances list (output unescaped)
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="event-view">   
    <?php
    // If this is a recurring event, list the instance(s)
    if ( $instancesListHtml ):
        echo $instancesListHtml; // Event instances (pre-rendered nested view)
    else:
    // If it's NOT a recurring event, just show the event start date
    ?>
		<div class="event-summary">
			<p><strong>Event Start Date:</strong> <?php echo esc_html($startDate); ?></p>
		</div>
    <?php
    endif;
    ?>
    
    <hr class="debug-divider" />
    <details class="debug-info">
        <summary>Post Meta (debug)</summary>
        <pre><?php print_r($postMeta); ?></pre>
    </details>
</div>