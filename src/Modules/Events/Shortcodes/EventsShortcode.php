<?php

declare(strict_types=1);

namespace atc\WHx4\Modules\Events\Shortcodes;

use atc\WXC\App;
use atc\WXC\PostTypes\PostTypeHandler;
use atc\WXC\Templates\ViewLoader;
use atc\WXC\Query\PostQuery;
use atc\WXC\Contracts\ShortcodeInterface;
//
use atc\WHx4\Modules\Events\PostTypes\Event;

final class EventsShortcode implements ShortcodeInterface
{
    // Adjust to your actual CPT slug (e.g., 'whx4_event' or 'event').
    private const CPT = 'whx4_event';

    // This is the tag by which the shortcode will be called
    public static function tag(): string
    {
        return 'whx4_events';
    }

    public function render(array $atts = [], string $content = '', string $tag = ''): string
    {
        $info = "";

        // Merge with canonical defaults from the CPT handler (parent-powered).
        $atts = shortcode_atts(Event::queryDefaults(), $atts, $tag);
        //$atts = shortcode_atts(PostTypeHandler::queryDefaults(), $atts, $tag);
        //$atts['date_meta']['key'] ='whx4_events_start_date'; // tft

        // Run the unified query pipeline.
        $result = Event::find($atts);
        $posts  = $result['posts'] ?? [];

        // Pagination info for the view.
        $pagination = $result['pagination'] ?? ['found' => 0, 'max_pages' => 0, 'paged' => 1];

        // Troubleshooting info
        $info .= "[" . $result['pagination']['found'] . "] posts found<br />";
        //$info .= "posts: <pre>" . print_r($posts, true) . "</pre>";
        //$info .= "atts: <pre>" . print_r($atts, true) . "</pre>";
        //$info .= "wp_args: <pre>" . print_r($result['debug']['args'], true) . "</pre>";
        //$info .= "query_request: <pre>" . $result['debug']['query_request'] . "</pre>";

        // Handler factory so views can call CPT methods safely.
        $handlerFactory = [PostTypeHandler::class, 'getHandlerForPost'];

        // Choose a view variant (list|grid|table); fall back to list.
        $viewVariant = in_array($atts['view'], ['list', 'grid', 'table'], true) ? $atts['view'] : 'list';
        $view = $viewVariant;

        $vars = [
            'posts'      => $posts,
            'handler'    => $handlerFactory,
            'atts'       => $atts,
            'pagination' => $pagination,
            'info' => $info, // for TS -- deprecate in favor of:
            // Optionally pass debug through when WHX4_DEBUG is on:
            'debug'      => $result['debug'] ?? null,
        ];

        return ViewLoader::renderToString(
            $view,
            $vars,
            ['kind' => 'partial', 'module' => 'events', 'post_type' => 'event']
        );
    }
}
