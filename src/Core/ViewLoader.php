<?php

declare(strict_types=1);

namespace atc\WHx4\Core;
use atc\WHx4\Core\WHx4;
use atc\WHx4\Core\ViewKind;
use atc\WHx4\Utils\Text;
use atc\WHx4\Utils\ClassInfo;

/**
 * Framework-level view resolver & renderer.
 * Responsibilities:
 *  - Register module view roots
 *  - Resolve view paths with layered overrides (theme → module → plugin)
 *  - Render views to output or string
 *
 * View "spec":
 *   - kind: one of ViewKind (enum instance) or string ('module', 'posttype', ...)
 *   - module: module slug (e.g. 'supernatural')
 *   - post_type: post type slug (e.g. 'monster')
 *   - allow_theme: whether to check theme overrides (default true)
 */
final class ViewLoader
{
    /** @var array<string,string> Module slug => absolute view root path */
    protected static array $moduleViewRoots = [];

    public static function hasViewRoot( string $moduleSlug ): bool
    {
        $slug = Text::slugify($moduleSlug); // just in case
        return isset(self::$moduleViewRoots[$slug]);
    }

    /**
     * Register a module-specific view directory (e.g., src/Modules/Supernatural/Views)
     */
    public static function registerModuleViewRoot( string $moduleSlug, string $absolutePath ): void
    {
        $key = Text::slugify($moduleSlug); // just in case
        $viewRoot = rtrim( $absolutePath, '/' );
        error_log( '=== viewRoot for moduleSlug/key: ' . $key . ' is: ' . $viewRoot . '===' );
        self::$moduleViewRoots[ $key ] = $viewRoot;
    }

    /**
     * Echo a rendered view immediately.
     *
     * @param array<string,mixed> $vars
     * @param array{kind?:string|ViewKind,module?:string,post_type?:string,allow_theme?:bool} $spec
     */
    public static function render( string $view, array $vars = [], array $spec = [] ): void
    {
        echo self::renderToString($view, $vars, $spec);
    }

    /**
     * Return the rendered view as a string. Uses output buffering.
     *
     * @param array<string,mixed> $vars
     * @param array{kind?:string|ViewKind,module?:string,post_type?:string,allow_theme?:bool} $spec
     */
    public static function renderToString(string $view, array $vars = [], array $spec = []): string
    {
        $path = self::getViewPath($view, $spec);

        if ($path) {
            ob_start();
            extract($vars, EXTR_SKIP);
            include $path;
            return ob_get_clean();
        }

        $kind   = self::normalizeKind($spec['kind'] ?? null);
        $module = Text::slug($spec['module'] ?? '');
        $ptype  = Text::slug($spec['post_type'] ?? '');

        return '<div class="notice notice-error"><p>' .
            esc_html("View not found: {$view} (kind: {$kind}, module: {$module}, post_type: {$ptype})") .
            '</p></div>';
    }

    /**
     * Resolve a view to a concrete file path, or null if not found.
     *
     * @param array{kind?:string|ViewKind,module?:string,post_type?:string,allow_theme?:bool} $spec
     */
    public static function getViewPath(string $view, array $spec = []): ?string
    {
        foreach (self::generateSearchPaths($view, $spec) as $path) {
            if (file_exists($path)) {
                //error_log( '=== File found at path: ' . $path . '===' );
                return $path;
            }
            error_log( '=== No file_exists at path: ' . $path . '===' );
        }

        return null;
    }

    ////

    /* Usage examples:
    From a module:
    $this->renderView( 'monster-list', [
        'monsters' => $this->getMonsterPosts(),
    ]);

    If views/supernatural/monster-list.php doesn’t exist, it will fall back to:
    views/monster-list.php
    */

    /**
     * Build ordered candidate paths:
     *   1) Theme overrides (child → parent)
     *   2) Module-registered root (src/Modules/<Module>/Views)
     *   3) Plugin fallback (rex/views/<view>.php)
     *
     * @param array{module?:string,post_type?:string,allow_theme?:bool} $spec
     * @return string[]
     */
    protected static function generateSearchPaths(string $view, array $spec = []): array
    {
        $paths       = [];
        $module      = Text::slugify($spec['module'] ?? '');
        $postType    = Text::slugify($spec['post_type'] ?? '');
        $allowTheme  = $spec['allow_theme'] ?? true;

        // 1) Theme overrides (child → parent)
        if ($allowTheme) {
            foreach ([get_stylesheet_directory(), get_template_directory()] as $root) {
                self::appendPermutations(
                    $paths,
                    rtrim($root, '/'),
                    'rex',          // theme subdir
                    $module,
                    $postType,
                    $view,
                    true            // include module-specific permutations
                );
            }
        }

        // 2) Module-registered root (e.g., rex/src/Modules/Supernatural/Views)
        if ($module !== '' && isset(self::$moduleViewRoots[$module])) {
            $root = rtrim(self::$moduleViewRoots[$module], '/');
            if ($postType !== '') {
                $paths[] = "{$root}/{$postType}/{$view}.php";
            }
            $paths[] = "{$root}/{$view}.php";
        }

        // 3) Plugin fallback (rex/views/<view>.php)
        self::appendPermutations(
            $paths,
            rtrim(WHX4_PLUGIN_DIR, '/'),
            'views',        // plugin views dir
            $module,
            $postType,
            $view,
            false           // no module-specific permutations under plugin/views
        );

        return $paths;
    }

    /**
     * Append most-specific → least-specific permutations under a root.
     * When $includeModule is false, only "<root>/<subdir>/<view>.php" is appended.
     *
     * @param array<string> $list
     */
    private static function appendPermutations(
        array &$list,
        string $root,
        string $subdir,
        string $module,
        string $postType,
        string $view,
        bool $includeModule
    ): void
    {
        $base = rtrim($root, '/');
        $sub  = trim($subdir, '/');
        $base = $sub !== '' ? "{$base}/{$sub}" : $base;

        if ($includeModule && $module !== '' && $postType !== '') {
            $list[] = "{$base}/{$module}/{$postType}/{$view}.php";
        }

        if ($includeModule && $module !== '') {
            $list[] = "{$base}/{$module}/{$view}.php";
        }

        $list[] = "{$base}/{$view}.php";
    }

    ///////

    /**
     * Normalize the "kind" input to a lowercase string.
     *
     * Accepts:
     *  - null
     *  - raw string (e.g. "posttype")
     *  - ViewKind enum instance
     */
    private static function normalizeKind(mixed $kind): string
    {
        if ($kind instanceof ViewKind) {
            return Text::slugify($kind->value);
        }

        $k = Text::slugify((string) $kind);
        return $k !== '' ? $k : ViewKind::GLOBAL->value;
    }

}
