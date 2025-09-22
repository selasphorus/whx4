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
<?php echo "color: " . $color; ?>
<?php echo "secret name: " . $sn; ?>
</div>

<div class="troubleshooting">
<?php echo 'post: <pre>' . print_r($post,true) . '</pre>'; ?>
<?php echo 'handler: <pre>' . print_r($handler,true) . '</pre>'; ?>
</div>
