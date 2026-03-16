<?php
/**
 * Event Content View
 * 
 * Displays event info
 * Pure presentation layer - all data preparation done in Event handler
 * 
 * @var string $startDate Event start date
 * @var array $viewData Prepared event data
 * @var array $postMeta Post meta for debug display
 */

if (!defined('ABSPATH')) {
    exit;
}

$instance_date = get_query_var('event_instance'); // WIP 03/16/26
echo "instance_date: ".print_r($instance_date, true);
?>

<div class="whx4-event-view">
    <h2>WHx4 Event Content View</h2>
    <div class="event-summary">
        <p><strong>Event Date:</strong> <?php echo esc_html($startDate); ?></p>
        <!--p><strong>Total Transactions on Record:</strong> <?php //echo $viewData['total_count']; ?></p-->
        <?php
        if ($instance_date) {
			echo "Event view WIP: Event Instance<hr />";
		}
		?>
    </div>
    
    <hr class="debug-divider" />
    <details class="debug-info">
        <summary>Post Meta (debug)</summary>
        <pre><?php print_r($postMeta); ?></pre>
    </details>
</div>