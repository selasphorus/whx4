<?php

declare(strict_types=1);

namespace atc\WHx4\Modules\Events\Shortcodes;

use atc\WHx4\Core\WHx4;
use atc\WHx4\Core\PostTypeHandler;
use atc\WHx4\Core\ViewLoader;
use atc\WHx4\Core\Query\PostQuery;
use atc\WHx4\Core\Contracts\ShortcodeInterface;

final class EventsShortcode implements ShortcodeInterface
{
    // Adjust to your actual CPT slug (e.g., 'whx4_event' or 'event').
    private const CPT = 'whx4_event';

    public static function tag(): string
    {
        return 'whx4_events';
    }

    /** @param array<string,mixed> $atts */
    public function render(array $atts = [], string $content = '', string $tag = ''): string //$tag ?: self::tag()
    {
        $info = "";

        $defaults = [
            'post_type'      => self::CPT,
            'post_status'    => 'publish',
            'view'           => 'list', // list|grid|table (your view can branch on this)
            'limit'          => 10,
            'order'          => 'ASC',
            'orderby'        => 'meta_value', // typical for date-sorted events
            'scope'          => '', // e.g. today|this_week|{ start,end }
            'start_date'     => '', // or 'date_start'?
            'end_date'       => '', // or 'date_end'?
            'event_category' => '', // CSV of slugs
            'paged'          => '',
            // 'include_past' => '0', // optional, if you add this later
        ];

        $atts = shortcode_atts($defaults, $atts, $tag);
        //$info .= "atts: <pre>" . print_r($atts, true) . "</pre>";

        // Paging (avoid ternary precedence pitfalls).
        $qv = (int)get_query_var('paged');
        $paged = $atts['paged'] !== '' ? (int)$atts['paged'] : ($qv > 0 ? $qv : 1);

        // Taxonomy filter: event_category (CSV → array of slugs).
        $cats = [];
        if ($atts['event_category'] !== '') {
            $cats = array_values(array_filter(array_map('trim', explode(',', (string)$atts['event_category']))));
        }
        //$includePast = in_array(strtolower((string)$atts['include_past']), ['1','true','yes'], true);

        // Scope: either a named scope (string) OR an explicit {start,end} window.
        // Leave null if neither provided (PostQuery can decide defaults).
        $scope = null;
        if ($atts['scope'] !== '') {
            $scope = (string)$atts['scope']; // e.g., "today", "this_week"
        } elseif ($atts['start_date'] !== '' || $atts['end_date'] !== '') {
            $scope = [
                'start' => $atts['start_date'] !== '' ? (string)$atts['start_date'] : null,
                'end'   => $atts['end_date'] !== '' ? (string)$atts['end_date']   : null,
            ];
        }

        // Build normalized PostQuery params (not raw WP_Query args).
        // PostQuery will:
        // - resolve the scope via ScopedDateResolver
        // - assemble meta_query via MetaQueryBuilder (overlapRange on start/end keys)
        // - assemble tax_query (later via TaxQueryBuilder)
        $params = [
            'post_type'   => (string)$atts['post_type'],
            'post_status' => (string)$atts['post_status'],
            'paged'       => $paged, //or: max(1, $paged),
            'posts_per_page' => (int)$atts['limit'],
            'order'       => (string)$atts['order'],
            'orderby'     => (string)$atts['orderby'],

            // Hint for PostQuery when sorting by meta_value:
            // set meta_key to the event start key so WP can sort efficiently.
            'meta_key'    => $atts['orderby'] === 'meta_value' ? 'whx4_events_start_date' : null,

            // Date mapping for events (range semantics).
            'date_meta'   => [
                'key' => 'whx4_events_start_date',
                //'start_key' => 'whx4_events_start_date', //'start_date',
                //'end_key'   => 'whx4_events_end_date', //'end_date',
                'meta_type' => 'DATE', // events are typically DATETIME, not DATE -- yes??? review acf fields setup...
            ],

            // Scope (string or {start,end} array or null)
            'scope'       => $scope,

            // Tax filters (slug field by default)
            'tax'         => $cats ? ['event_category' => $cats] : [],
        ];
        $info .= "params: <pre>" . print_r($params, true) . "</pre>";

        // Run the query
        $query  = new PostQuery();
        $result = $query->find($params);
        $posts = $result['posts'] ?? [];

        // Troubleshooting info
        $info .= "[" . $result['found'] . "] posts found: <pre>" . print_r($posts, true) . "</pre>";
        $info .= "wp_args: <pre>" . print_r($result['args'], true) . "</pre>";
        $info .= "query_request: <pre>" . $result['query_request'] . "</pre>";
        //
        $pagination = [
            'found'     => $result['found']     ?? 0,
            'max_pages' => $result['max_pages'] ?? 0,
            'paged'     => $paged,
        ];

        // Handler factory so views can call CPT methods safely.
        $handlerFactory = [PostTypeHandler::class, 'getHandlerForPost'];

        // Choose a view variant by atts['view'].
        // Expect your ViewLoader to resolve "events/{list|grid|table}.php" (or similar).
        $viewVariant = in_array($atts['view'], ['list','grid','table'], true) ? $atts['view'] : 'list';
        $view = "{$viewVariant}"; //$view = "events/{$viewVariant}";

        $vars = [
            'posts'      => $posts,
            'handler'    => $handlerFactory,
            'atts'       => $atts,
            'pagination' => $pagination,
            'info' => $info,
        ];

        return ViewLoader::renderToString(
            $view,
            $vars,
            [ 'kind' => 'partial', 'module' => 'events', 'post_type' => 'event' ] // specs
        );
    }
}
