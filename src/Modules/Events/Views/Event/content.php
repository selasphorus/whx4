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
    <div class="event-summary">
        <p><strong>Event Start Date:</strong> <?php echo esc_html($startDate); ?></p>
        <!--p><strong>Total Instances on Record:</strong> <?php //echo $viewData['total_count']; ?></p-->
    </div>
    
    <!-- Event instances (pre-rendered nested view) -->
    <?php echo $instancesListHtml; ?>
    
    <hr class="debug-divider" />
    <details class="debug-info">
        <summary>Post Meta (debug)</summary>
        <pre><?php print_r($postMeta); ?></pre>
    </details>
</div>