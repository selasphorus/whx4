<?php
/** @var WP_Post[] $posts */
/** @var callable $handler */ // function(WP_Post): ?PostTypeHandler
/** @var array{found:int,max_pages:int,paged:int} $pagination */
/** @var array<string,mixed> $atts */
?>
<div class="whx4-events whx4-events--list">
    <?php if(!$posts): ?>
        <p>No events found.</p>
    <?php else: ?>
        <ul class="whx4-events__items">
            <?php foreach($posts as $post): ?>
                <?php $h = $handler($post); ?>
                <?php
                $start = $h && method_exists($h, 'getPostMeta') ? (string)($h->getPostMeta('start_date') ?? '') : (string)get_post_meta($post->ID, 'start_date', true);
                $end   = $h && method_exists($h, 'getPostMeta') ? (string)($h->getPostMeta('end_date') ?? '')   : (string)get_post_meta($post->ID, 'end_date', true);
                ?>
                <li class="whx4-events__item">
                    <a href="<?php echo esc_url(get_permalink($post)); ?>">
                        <?php echo esc_html(get_the_title($post)); ?>
                    </a>
                    <?php if($start || $end): ?>
                        <div class="whx4-events__dates">
                            <?php echo esc_html(trim($start.($end && $end !== $start ? ' – '.$end : ''))); ?>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if($pagination['max_pages'] > 1): ?>
            <nav class="whx4-pagination" aria-label="Events pagination">
                <span>Page <?php echo (int)$pagination['paged']; ?> of <?php echo (int)$pagination['max_pages']; ?></span>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>
