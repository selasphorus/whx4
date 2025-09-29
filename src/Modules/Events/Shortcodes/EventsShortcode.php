<?php

declare(strict_types=1);

namespace atc\WHx4\Modules\Events\Shortcodes;

use WP_Post;
use atc\WHx4\Core\WHx4;
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
    public function render(array $atts, string $content = '', string $tag = ''): string
    {
        $info = "";

        $atts = shortcode_atts([
            'scope'           => '',
            'date_start'      => '',
            'date_end'        => '',
            'event_category'  => '',
            'per_page'        => '10',
            'paged'           => '',
            'include_past'    => '0',
            'view'            => 'list',
            'post_status'     => 'publish',
        ], $atts, $tag ?: self::tag());
        $info .= "atts: <pre>" . print_r($atts, true) . "</pre>"; // tft
        //
        $paged = (int) ($atts['paged'] !== '' ? $atts['paged'] : get_query_var('paged', 1));
        $cats  = array_filter(array_map('trim', $atts['event_category'] !== '' ? explode(',', (string)$atts['event_category']) : []));
        $includePast = in_array(strtolower((string)$atts['include_past']), ['1','true','yes'], true);

        // Resolve services via WHx4::ctx()
        $ctx   = WHx4::ctx();
        $query = new PostQuery($ctx);

        $params = [
            'post_type'      => self::CPT,
            'per_page'       => max(1, (int)$atts['per_page']),
            'paged'          => max(1, $paged),
            'scope'          => (string)$atts['scope'],
            'date_start'     => $atts['date_start'] !== '' ? (string)$atts['date_start'] : null,
            'date_end'       => $atts['date_end'] !== '' ? (string)$atts['date_end'] : null,
            'event_category' => $cats ?: null,
            'include_past'   => $includePast,
            'post_status'    => (string)$atts['post_status'],
        ];
        $info .= "params: <pre>" . print_r($params, true) . "</pre>"; // tft

        $result = $query->find($params);
        $posts  = $result['posts'];

        // Factory so views can call handler methods safely.
        $handlerFactory = function(WP_Post $post) use ($ctx) {
            $map = $ctx->getActivePostTypes();
            $class = $map[$post->post_type] ?? null;
            return $class ? new $class($post) : null;
        };

        $vars = [
            'posts'      => $posts,
            'handler'    => $handlerFactory,
            'atts'       => $atts,
            'pagination' => [
                'found'     => $result['found'],
                'max_pages' => $result['max_pages'],
                'paged'     => $params['paged'],
            ],
            'info'       => $info,
        ];

        //renderToString(string $view, array $vars = [], array $specs = [])
        $html = ViewLoader::renderToString( 'list',
            // vars
            $vars, //[ 'post' => $post ],
            // specs
            [ 'kind' => 'partial', 'module' => 'events', 'post_type' => 'event' ]
        );
        //

        return $html;
    }
}
