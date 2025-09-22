<?php
use atc\WHx4\Core\PostTypeHandler;

/** @var WP_Post $post */
$handler = PostTypeHandler::getHandlerForPost($post);

// Monster-specific data
$color = ($handler && method_exists($handler, 'getColor'))
    ? $handler->getColor($post)
    : '';

$sn = ($handler && method_exists($handler, 'getSN'))
    ? $handler->getSN($post)
    : '';
?>

<div>
Monster view test -- content (partial/appended).
<?php echo "color: " . $color; ?><br />
<?php echo "secret name: " . $sn; ?><br />
<?php echo "getPostID: " . $handler->getPostID($post); ?>
</div>

<div class="troubleshootingg">
<?php //echo 'post: <pre>' . print_r($post,true) . '</pre>'; // ok ?>
<?php //echo 'handler: <pre>' . print_r($handler,true) . '</pre>'; // ok ?>
</div>
