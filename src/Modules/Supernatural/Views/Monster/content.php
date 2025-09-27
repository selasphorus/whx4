<?php
use atc\WHx4\Core\PostTypeHandler;

/** @var WP_Post $post */
$handler = PostTypeHandler::getHandlerForPost($post);
if ($handler) {
    $postId = $handler->getPostId();
    // Monster-specific data
    $color = ($handler && method_exists($handler, 'getColor')) ? $handler->getColor() : '';
    //$sn = ($handler && method_exists($handler, 'getSN')) ? $handler->getSN() : '';
}
?>

<div>
Monster view test -- content (partial/appended).
<?php
echo "postId: " . $postId;
echo "color: " . $color . '<br />';
//echo "secret name: " . $sn . '<br />';
?>
</div>

<div class="troubleshootingg">
<?php
//echo 'post: <pre>' . print_r($post,true) . '</pre>'; // ok
//echo 'handler: <pre>' . print_r($handler,true) . '</pre>'; // ok
?>
</div>
