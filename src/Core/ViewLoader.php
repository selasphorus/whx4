<?php

namespace atc\WHx4\Core;
use atc\WHx4\Core\WHx4;

final class ViewLoader
{
    /** @var PluginContext|null */
    private static ?PluginContext $ctx = null;

    protected static array $moduleViewRoots = [];

    public static function hasViewRoot( string $slug ): bool
    {
        return isset( self::$moduleViewRoots[ $slug ] );
    }

    /**
     * Register a module-specific view directory (e.g., src/Modules/Supernatural/Views)
     */
    public static function registerModuleViewRoot( string $moduleSlug, string $absolutePath ): void
    {
        $key = strtolower( trim($moduleSlug) ); // just in case
        $viewRoot = rtrim( $absolutePath, '/' );
        error_log( '=== viewRoot for moduleSlug/key: ' . $key . ' is: ' . $viewRoot . '===' );
        self::$moduleViewRoots[ $key ] = $viewRoot;
    }

    /**
     * Echo a rendered view immediately.
     */
    public static function render( string $view, array $vars = [], string $module = '' ): void
    {
        echo self::renderToString( $view, $vars, $module );
    }

    /**
     * Return a rendered view as a string, searching in theme, module, and plugin folders. Uses output buffering.
     */
    public static function renderToString( string $view, array $vars = [], string $module = '' ): string
    {
        $path = self::getViewPath( $view, $module );

        if ( $path ) {
            ob_start();
            extract( $vars, EXTR_SKIP );
            include $path;
            return ob_get_clean();
        }

        return '<div class="notice notice-error"><p>' .
            esc_html( "View not found: $view (module: " . $module . ") at path: " . $path ) .
            '</p></div>';
    }

    /**
     * Get the resolved full path to a view (first match), or null if not found.
     */
    public static function getViewPath( string $view, string $module = '' ): ?string
    {
        foreach ( self::generateSearchPaths( $view, $module ) as $path ) {
            if ( file_exists( $path ) ) {
                error_log( '=== File found at path: ' . $path . '===' );
                return $path;
            }
            error_log( '=== No file_exists at path: ' . $path . '===' );
        }

        return null;
    }
    /* Usage examples:
    From a module:
    $this->renderView( 'monster-list', [
        'monsters' => $this->getMonsterPosts(),
    ]);

    If views/supernatural/monster-list.php doesn’t exist, it will fall back to:
    views/monster-list.php
    */

    /**
     * Generate an ordered list of candidate view paths based on view name and module.
     */
    protected static function generateSearchPaths( string $view, string $module = '' ): array
    {
        $paths = [];
        $moduleSlug = strtolower(trim($module));

        if ( $moduleSlug ) {
            // Theme overrides
            // 1a. Child theme override -- e.g. /themes/my-theme/whx4/{module}/view.php
            //error_log( '=== themeOverride path: ' . $themeOverride . '===' );
            $paths[] = get_stylesheet_directory() . "/whx4/{$moduleSlug}/{$view}.php";
            // 1b. Parent theme override
            $paths[] = get_template_directory()   . "/whx4/{$moduleSlug}/{$view}.php";

            // 2. Module-registered path -- e.g. src/Modules/Supernatural/Views/view.php
            if ( isset( self::$moduleViewRoots[ $module ] ) ) { // if (isset(self::$moduleViewRoots[$moduleSlug])) {
                $moduleViewPath = self::$moduleViewRoots[ $module ] . "/{$view}.php";
                error_log( '=== moduleViewPath: ' . $moduleViewPath . '===' );
                $paths[] = $moduleViewPath;
            }

            // 3. Plugin-level module fallback: Modules/{module}/Views/view.php -- could also add views/modules/{module}/view.php
            $pluginViewPath = WHX4_PLUGIN_DIR . "views/modules/" . $module . "/{$view}.php";
            error_log( '=== pluginViewPath: ' . $pluginViewPath . '===' );
            $paths[] = $pluginViewPath;
        }

        // 4. Plugin-level global fallback: views/view.php (shared/global)
        $paths[] = WHX4_PLUGIN_DIR . "views/{$view}.php";

        return $paths;
    }

    /**
     * Map a post type slug to "{moduleSlug}/{postType}" for view lookup.
     * Prefers Handler::getViewNamespace(); falls back to deriving module from FQCN.
     */
    public static function viewKeyForPostType(string $postType): ?string
    {
        $activePostTypes = WHx4::ctx()->getActivePostTypes(); // ['person' => \...Person::class]
        $handlerClass = $activePostTypes[$postType] ?? null;
        if (!$handlerClass || !class_exists($handlerClass)) {
            return null;
        }

        if (method_exists($handlerClass, 'getViewNamespace')) {
            $ns = strtolower((string) $handlerClass::getViewNamespace());
            return "{$ns}/{$postType}";
        }

        if (preg_match('#\\\\Modules\\\\([^\\\\]+)\\\\#', $handlerClass, $m)) {
            $module = strtolower($m[1]);
            return "{$module}/{$postType}";
        }

        return null;
    }

}
