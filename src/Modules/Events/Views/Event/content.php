<?php
/**
 * Event Content View
 * 
 * Displays event status and transaction statistics
 * Pure presentation layer - all data preparation done in Event handler
 * 
 * @var string $startDate Event start date
 * @var array $viewData Prepared event data
 * @var array $postMeta Post meta for debug display
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="event-view">
    <div class="event-summary">
        <p><strong>Event Date:</strong> <?php echo esc_html($startDate); ?></p>
        <!--p><strong>Total Transactions on Record:</strong> <?php //echo $viewData['total_count']; ?></p-->
    </div>
    
    <hr class="debug-divider" />
    <details class="debug-info">
        <summary>Post Meta (debug)</summary>
        <pre><?php print_r($postMeta); ?></pre>
    </details>
</div>