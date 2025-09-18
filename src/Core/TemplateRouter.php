<?php
namespace atc\WHx4\Core;

use atc\WHx4\Core\ViewLoader;

/**
 * Routes WP's single/archive template resolution for WHx4-managed CPTs.
 * Honors native theme overrides (single-{pt}.php, archive-{pt}.php) first,
 * then falls back to the WHx4 view cascade via ViewLoader.
 */
final class TemplateRouter
{
    /**
     * Register filters once (e.g., from CoreServices::boot()).
     */
    public static function boot(): void
    {
        add_filter('single_template', [self::class, 'route'], 20, 1);
        add_filter('archive_template', [self::class, 'route'], 20, 1);
    }

    /**
     * Entrypoint for both filters; dispatches by current_filter().
     */
    public static function route(string $template): string
    {
        $hook = current_filter();

        if ($hook === 'single_template') {
            if (!is_singular()) {
                return $template;
            }

            $pt = get_post_type();
            // If no post type or the theme already provides single-{pt}.php, keep it.
            if (!$pt || ($template && basename($template) === "single-{$pt}.php")) {
                return $template;
            }

            return self::locatePostTypeTemplate('single', $pt, $template) ?: $template;
        }

        if ($hook === 'archive_template') {
            if (!is_post_type_archive()) {
                return $template;
            }

            $pt = get_query_var('post_type');
            // Skip multi-type archives; only handle a single CPT archive.
            if (is_array($pt)) {
                $pt = count($pt) === 1 ? reset($pt) : null;
            }

            // If unknown or the theme already provides archive-{pt}.php, keep it.
            if (!$pt || ($template && basename($template) === "archive-{$pt}.php")) {
                return $template;
            }

            return self::locatePostTypeTemplate('archive', $pt, $template) ?: $template;
        }

        return $template;
    }

    /**
     * Find the WHx4 view path for a CPT and view kind ("single"|"archive").
     * Returns the absolute path if found; otherwise null (caller falls back to WP's template).
     */
    private static function locatePostTypeTemplate(string $kind, string $postType, string $fallback): ?string
    {
        $key = ViewLoader::viewKeyForPostType($postType); // e.g. "supernatural/monster"
        if (!$key) {
            return null; // Not a WHx4-managed CPT or no handler registered.
        }

        [$module] = explode('/', $key, 2);
        $view = "{$postType}/{$kind}"; // e.g. "monster/single" or "monster/archive"

        return ViewLoader::getViewPath($view, $module) ?? $fallback;
    }
}
