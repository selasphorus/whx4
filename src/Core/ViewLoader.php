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
     * @param array{kind?:string|ViewKind,module?:string,post_type?:string,allow_theme?:bool} $specs
     */
    public static function render( string $view, array $vars = [], array $specs = [] ): void
    {
        echo self::renderToString($view, $vars, $specs);
    }

    /**
     * Return the rendered view as a string. Uses output buffering.
     *
     * @param array<string,mixed> $vars
     * @param array{kind?:string|ViewKind,module?:string,post_type?:string,allow_theme?:bool} $specs
     */
    public static function renderToString(string $view, array $vars = [], array $specs = []): string
    {
        error_log( '=== renderToString for view: ' . $view . ' with vars: ' . print_r($vars,true) . ' and spec: ' . print_r($specs,true) . '===' );
        $path = self::getViewPath($view, $specs);

        if ($path) {
            ob_start();
            extract($vars, EXTR_SKIP);
            include $path;
            return ob_get_clean();
        }

        $kind   = self::normalizeKind($specs['kind'] ?? null);
        $module = Text::slug($specs['module'] ?? '');
        $ptype  = Text::slug($specs['post_type'] ?? '');

        return '<div class="notice notice-error"><p>' .
            esc_html("View not found: {$view} (kind: {$kind}, module: {$module}, post_type: {$ptype})") .
            '</p></div>';
    }

    /**
     * Resolve a view to a concrete file path, or null if not found.
     *
     * @param array{kind?:string|ViewKind,module?:string,post_type?:string,allow_theme?:bool} $specs
     */
    public static function getViewPath(string $view, array $specs = []): ?string
    {
        error_log( '=== getViewPath for view: ' . $view . ' with spec: ' . print_r($specs,true) . '===' );
        foreach (self::generateSearchPaths($view, $specs) as $path) {
            if (file_exists($path)) {
                //error_log( '=== File found at path: ' . $path . '===' );
                return $path;
            }
            error_log( 'No file_exists at path: ' . $path . '' );
        }

        return null;
    }

    ////

    /**
     * Build ordered candidate paths:
     *   1) Theme overrides (child → parent)
     *   2) Module-registered root (src/Modules/<Module>/Views)
     *   3) Plugin fallback (whx4/views/<view>.php)
     *
     * @param array{module?:string,post_type?:string,allow_theme?:bool} $specs
     * @return string[]
     */
    protected static function generateSearchPaths(string $view, array $specs = []): array
    {
        $paths       = [];
        $kind        = Text::slugify($specs['kind'] ?? '');
        $module      = Text::slugify($specs['module'] ?? '');
        $postType    = Text::slugify($specs['post_type'] ?? '');
        $allowTheme  = $specs['allow_theme'] ?? true;
        error_log( 'kind: ' . $kind . '' );
        //error_log( 'module: ' . $module . '' );
        error_log( 'postType: ' . $postType . '' );

        // 1) Theme overrides (child → parent)
        if ($allowTheme) {
            foreach ([get_stylesheet_directory(), get_template_directory()] as $root) {
                self::appendPermutations(
                    $paths,
                    rtrim($root, '/'),
                    'whx4',          // theme subdir
                    $module,
                    $postType,
                    $view,
                    true            // include module-specific permutations
                );
            }
        }

        // 2) Module-registered root (e.g., whx4/src/Modules/Supernatural/Views)
        if ($module !== '' && isset(self::$moduleViewRoots[$module])) {
            //error_log( 'self::moduleViewRoots[module]: ' . self::$moduleViewRoots[$module] . '' );
            $root = rtrim(self::$moduleViewRoots[$module], '/');
            //error_log( 'root: ' . $root . '' );
            if ($postType !== '') {
                $postType = Text::camel($postType);
                $paths[] = "{$root}/{$postType}/{$view}.php";
            }
            $paths[] = "{$root}/{$view}.php";
        }

        // 3) Plugin fallback (whx4/views/<view>.php)
        //error_log( 'WHX4_PLUGIN_DIR: ' . WHX4_PLUGIN_DIR . '' );
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
