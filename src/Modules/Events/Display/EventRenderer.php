<?php

declare(strict_types=1);

namespace atc\WHx4\Modules\Events\Display;

use atc\WXC\Display\ContentRenderer;

/**
 * Renderer for the whx4_event post type.
 *
 * Overrides only the methods that differ from the generic base:
 *   - getItemMeta()        — surfaces the event start date/time in list items
 *   - getTableColumns()    — adds a Date column before Title
 *   - getTableCells()      — populates that Date column
 *   - getArchiveGroupKey() — groups by event start year, not post_date year
 *
 * renderList(), renderTable(), renderGrid(), and renderArchive() are all
 * inherited from ContentRenderer without modification.
 */
final class EventRenderer extends ContentRenderer
{
    private const META_START = 'whx4_events_start';

    // -------------------------------------------------------------------------
    // Registration
    // -------------------------------------------------------------------------

    /**
     * Register this renderer via the WXC filter so ContentRenderer::make()
     * can find it automatically.
     *
     * Call this from the Events module boot sequence:
     *   EventRenderer::register();
     */
    public static function register(): void
    {
        add_filter('wxc_content_renderer_class', static function (?string $class, string $postType): ?string {
            if ($postType === 'whx4_event' || $postType === 'event') {
                return static::class;
            }
            return $class;
        }, 10, 2);
    }

    // -------------------------------------------------------------------------
    // Override points
    // -------------------------------------------------------------------------

    /**
     * {@inheritdoc}
     *
     * Appends a formatted event start date/time beneath the title.
     */
    protected function getItemMeta(\WP_Post $post, array $atts): string
    {
        $start = $this->resolveStart($post);

        if (!$start) {
            return '';
        }

        return '<time class="wxc-event__start" datetime="' . esc_attr($start->format('c')) . '">'
             . esc_html($start->format(get_option('date_format') . ' ' . get_option('time_format')))
             . '</time>';
    }

    /**
     * {@inheritdoc}
     *
     * Date column comes before Title for events.
     */
    protected function getTableColumns(): array
    {
        return [
            __('Date', 'whx4'),
            __('Title', 'whx4'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getTableCells(\WP_Post $post, array $atts): array
    {
        $start = $this->resolveStart($post);

        $dateCell  = $start
            ? '<time datetime="' . esc_attr($start->format('c')) . '">'
              . esc_html(date_i18n(get_option('date_format'), $start->getTimestamp()))
              . '</time>'
            : '&ndash;';

        $titleCell = '<a href="' . esc_url(get_permalink($post)) . '">'
                   . esc_html(get_the_title($post))
                   . '</a>';

        return [$dateCell, $titleCell];
    }

    /**
     * {@inheritdoc}
     *
     * Groups by the event start year rather than the post publication year.
     */
    protected function getArchiveGroupKey(\WP_Post $post): string|int
    {
        $start = $this->resolveStart($post);

        return $start ? $start->format('Y') : parent::getArchiveGroupKey($post);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Parse whx4_events_start into a DateTimeImmutable, or return null.
     *
     * @param  \WP_Post $post
     * @return \DateTimeImmutable|null
     */
    private function resolveStart(\WP_Post $post): ?\DateTimeImmutable
    {
        $raw = get_post_meta($post->ID, self::META_START, true);

        if (!$raw) {
            return null;
        }

        try {
            return new \DateTimeImmutable($raw);
        } catch (\Throwable) {
            return null;
        }
    }
}