<?php
/**
 * Person Content View
 * 
 * Displays account status and transaction statistics
 * Pure presentation layer - all data preparation done in Person handler
 * 
 * @var string $dates Person dates
 * @var string $compositions Person dates
 * @var array $viewData Prepared transaction statistics
 * @var array $postMeta Post meta for debug display
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<?php 
// Dates -- appended to post_title?
// Compositions
//if ( !empty($compositions) ) {
?>
    <h3>Compositions:</h3>
<?php
    foreach ( $compositions as $composition ) {
        echo $composition;
    }
//}
?>

<div>
<h3>Person view WIP</h3>
<?php //echo "postMeta: <pre>" . print_r($postMeta,true) . '</pre>'; // Ok ?>
</div>

