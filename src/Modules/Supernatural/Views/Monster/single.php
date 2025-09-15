<?php
use atc\WHx4\Core\PostTypeHandler;

/** @var WP_Post $post */
$handler = PostTypeHandler::getHandlerForPost($post);

// Example: Person-specific data
$color = ($handler && method_exists($handler, 'getColor'))
    ? $handler->getColor($post)
    : '';
?>


<div>
Monster view test -- single.
<?php echo "color: " . $color; ?>
</div>
