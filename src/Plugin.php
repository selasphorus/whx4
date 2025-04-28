<?php

// Initialize the plugin, register hooks, and manage dependencies

namespace atc\WHx4;

use atc\WHx4\Core\CoreServices;
use atc\WHx4\Core\PostTypeRegistrar;
//use atc\WHx4\Core\TaxonomyRegistrar;
use atc\WHx4\Core\FieldGroupLoader;
use atc\WHx4\Core\Contracts\ModuleInterface;
use atc\WHx4\Core\SettingsManager;
use atc\WHx4\Admin\SettingsPageController;
///use atc\WHx4\Core\ViewLoader;
///use atc\WHx4\Utils\TitleFilter;
//
use atc\WHx4\ACF\JsonPaths;
use atc\WHx4\ACF\RestrictAccess;
use atc\WHx4\ACF\BlockRegistrar;

final class Plugin
{
    private static ?self $instance = null;

	// NB: Set the actual modules array via boot (whx4.php) -- this way, Plugin class contains logic only, and other plugins or themes can register additional modules dynamically
	protected array $availableModules = [];
    protected array $activeModules = [];
    protected bool $modulesLoaded = false;

    protected PostTypeRegistrar $postTypeRegistrar;
    //protected TaxonomyRegistrar $taxonomyRegistrar;
    protected FieldGroupLoader $fieldGroupLoader;
    protected SettingsManager $settingsManager;

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct()
    {
        // Initialize internal state or dependencies
        $this->settingsManager = new SettingsManager( $this );
    }

    // Convenience accessor. Keeps $settingsManager protected inside the Plugin class
    // call e.g. from inside module: `if ( $this->plugin->getSettingsManager()->isPostTypeEnabled( 'monster' ) ) {}`
    public function getSettingsManager(): SettingsManager
    {
        return $this->settingsManager;
    }

    /**
     * Prevent cloning of the instance.
     */
    private function __clone() {}

    /**
     * Prevent unserializing of the instance.
     */
    public function __wakeup()
    {
        throw new \RuntimeException( 'Cannot unserialize a singleton.' );
    }

    public static function getInstance(): self
    {
        if ( static::$instance === null ) {
            static::$instance = new self();
        }
        return static::$instance;
    }

    public function boot(): void
    {
        $this->defineConstants();
        $this->registerAdminHooks();
        $this->registerPublicHooks();
        $this->initializeCore();
    }

    protected function registerAdminHooks(): void
    {
        if ( is_admin() ) {
            ( new SettingsPageController( $this ) )->addHooks();
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAdminAssets' ] );
        }
    }

    protected function registerPublicHooks(): void
    {
        // on 'init': Register post types, taxonomies, shortcodes
        add_action( 'init', [ $this, 'registerPostTypes' ], 10 );
        add_action( 'acf/init', [ $this, 'registerFieldGroups' ], 11 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueuePublicAssets' ] );
    }

    protected function initializeCore(): void
    {
        CoreServices::boot();

        // Load modules and config
        $modules = apply_filters( 'whx4_register_modules', [] );
        $this->setAvailableModules( $modules );

        $this->loadActiveModules();
        $this->bootModules();

        $this->postTypeRegistrar  = new PostTypeRegistrar( $this );
        $this->fieldGroupLoader   = new FieldGroupLoader( $this );
    }

	public function enqueueAdminAssets(string $hook): void
	{
		if ( $hook !== 'settings_page_whx4-settings' ) {
			return;
		}

		wp_enqueue_script(
			'whx4-settings',
			WHX4_PLUGIN_DIR . '/assets/js/settings.js',
			[],
			'1.0',
			true
		);

		/*wp_enqueue_style(
			'whx4-settings',
			WHX4_PLUGIN_DIR . '/assets/css/settings.css',
			[],
			'1.0'
		);*/
	}

    public function enqueuePublicAssets(): void
    {
        $fpath = WHX4_PLUGIN_DIR . '/assets/css/whx4.css';
    	if (file_exists($fpath)) { $ver = filemtime($fpath); } else { $ver = "240823"; }
    	wp_enqueue_style( 'whx4-style', plugins_url( 'css/whx4.css', __FILE__ ), $ver );
    }

	public function maybeLoadSettingsManager(): void
	{
		if ( is_admin() && $this->isSettingsPage() ) {
			$this->settingsManager = new SettingsManager( $this );
		}
	}

	protected function isSettingsPage(): bool
	{
		return isset( $_GET['page'] ) && $_GET['page'] === 'whx4_settings';
	}

    protected function defineConstants(): void //private function defineConstants()
    {
    	define( 'WHX4_TEXTDOMAIN', 'whx4' );
        define( 'WHX4_PLUGIN_DIR', WP_PLUGIN_DIR. '/whx4/' ); //define( 'WHX4_PLUGIN_DIR', __DIR__ );
        //define('WHX4_DIR', plugin_dir_path(__FILE__));
        define( 'WHX4_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
        define( 'WHX4_PLUGIN_BLOCKS', WHX4_PLUGIN_DIR . '/blocks/' );
    	define( 'WHX4_VERSION', '2.0.0' );
    	//error_log( '=== WHx4 defineConstants() complete ===' ); //ok
    }

	public function setAvailableModules( array $modules ): void
	{
		foreach( $modules as $slug => $class ) {
			if ( is_subclass_of( $class, ModuleInterface::class ) ) {
				$this->availableModules[$slug] = $class;
			}
		}
	}

	public function getAvailableModules(): array
	{
		return $this->availableModules;
	}

    protected function loadActiveModules(): void
    {
        if ( $this->modulesLoaded ) {
            return;
        }

        $activeSlugs = $this->settingsManager->getActiveModuleSlugs();

        foreach ( $activeSlugs as $slug ) {
            if ( isset( $this->availableModules[ $slug ] ) ) {
                //$this->activeModules[] = $this->availableModules[ $slug ]; // v1
                $moduleClass = $this->availableModules[ $slug ];
                $module = new $moduleClass();

                /*if ( method_exists( $module, 'setPlugin' ) ) {
                    $module->setPlugin( $this ); // Optional: inject plugin
                }*/
                //$this->activeModules[ $slug ] = $module;
                $this->activeModules[ $slug ] = $moduleClass;
            }
        }

        $this->modulesLoaded = true;
    }

    public function getActiveModules(): array
    {
        $this->loadActiveModules();
        return $this->activeModules;
    }

    public function bootModules(): void
    {
        //error_log( '=== Plugin: bootModules() ===' );
        foreach ( $this->getActiveModules() as $moduleClass ) {
            //error_log( 'About to attempt instantiation for moduleClass: ' . $moduleClass );
        	$module = new $moduleClass();
        	if ( $module instanceof ModuleInterface ) {
				$module->setPlugin( $this );
			}
			//error_log( 'About to attempt module boot() for moduleClass: ' . $moduleClass );
            if ( method_exists( $module, 'boot' ) ) {
                $module->boot();
            } else {
            	error_log( 'boot() method missing for moduleClass: ' . $moduleClass );
            }
        }
    }
/*
    protected function bootModules(): void
    {
        foreach ( $this->activeModules as $module ) {
            if ( method_exists( $module, 'boot' ) ) {
                $module->boot();
            }
        }
    }
*/
    /**
     * Returns all enabled post types across active modules,
     * based on both the module definitions and plugin settings.
     */
    /*public function getActivePostTypes(): array
    {
        return $this->getSettingsManager()->getEnabledPostTypeSlugs();
    }*/
    public function getActivePostTypes(): array
	{
    	//error_log( '=== START getActivePostTypes() ===' );

    	$this->loadActiveModules();
		$enabledPostTypesByModule = $this->getSettingsManager()->getEnabledPostTypeSlugsByModule();

		//error_log("Loaded enabled post types: " . print_r($enabledPostTypesByModule, true));

		$postTypeClasses = [];

		foreach( $this->activeModules as $moduleSlug => $moduleClass ) {
			//error_log("moduleSlug: " . print_r($moduleSlug, true));
			try {
				if( !class_exists($moduleClass) ) {
					error_log("Class $moduleClass does not exist.");
					continue;
				}

				if( !is_subclass_of($moduleClass, \atc\Whx4\Core\Contracts\ModuleInterface::class) ) {
					error_log("Class $moduleClass is not a ModuleInterface.");
					continue;
				}

				//$slug = strtolower($moduleClass::getName());
				$moduleInstance = new $moduleClass();
				$moduleSlug = strtolower($moduleInstance->getName());

				if( !method_exists($moduleClass, 'getPostTypes') ) {
					error_log("Module $moduleClass does not implement getPostTypes().");
					continue;
				}

				//$definedPostTypes = $moduleClass::getPostTypes();
				$definedPostTypes = $moduleInstance->getPostTypeHandlerClasses();
				//error_log("definedPostTypes: " . print_r($definedPostTypes, true));

				$enabled = $enabledPostTypesByModule[ $moduleSlug ] ?? $definedPostTypes;

				//error_log("Module $moduleSlug: defined=" . implode(',', $definedPostTypes) . "; enabled=" . implode(',', $enabled));

				//foreach ($definedPostTypes as $postTypeSlug => $name) {
				foreach ( $definedPostTypes as $postTypeHandlerClass ) {
					$handler = new $postTypeHandlerClass(); //$postTypeHandler = new $postTypeHandlerClass();
					$postTypeSlug = $handler->getSlug();
					//$className = $handler->getLabels()['singular_name'];
					if  (in_array( $postTypeSlug, $enabled, true )) {
						$postTypeClasses[] = $postTypeHandlerClass; //$className;
					} else {
						//error_log("Post type '$postTypeSlug' from module '$moduleSlug' is not enabled.");
					}
				}
			} catch( \Throwable $e ) {
				error_log("Exception in getActivePostTypes for module $moduleSlug: " . $e->getMessage());
			}
		}

		//error_log( '=== END getActivePostTypes() ===' );
		//error_log("active postTypeClasses: " . print_r($postTypeClasses, true));

		return array_unique($postTypeClasses);
	}

	//getEnabledTaxonomies

    /**
     * Loop through each module and register its post types.
     */
    public function registerPostTypes(): void
	{
		//error_log( '=== registerPostTypes() ===' );

		$activePostTypes = $this->getActivePostTypes();
		//error_log( 'activePostTypes: '.print_r($activePostTypes, true) );

		if ( empty( $activePostTypes ) ) {
			error_log( 'No active post types found. Skipping registration.' );
			return;
		}

		$this->postTypeRegistrar->registerMany( $activePostTypes );
	}

    public function registerFieldGroups(): void
    {
        error_log( '=== registerFieldGroups ===' );
        $this->fieldGroupLoader->registerAll();
    }

    protected function registerTaxonomies(): void
    {
		// Register Custom Taxonomies for active modules
    }


	/// WIP

	public function assignPostTypeCapabilities(): void {
		$this->postTypeRegistrar->assignPostTypeCapabilities();
	}

	public function removePostTypeCapabilities(): void {
		$this->postTypeRegistrar->removePostTypeCapabilities();
	}

	//
    protected function use_custom_caps() {
		$use_custom_caps = false;
		if ( isset($options['use_custom_caps']) && !empty($options['use_custom_caps']) ) {
			$use_custom_caps = true;
		}
		return $use_custom_caps;
	}

    /*
    protected static ?self $instance = null;

    protected ?string $entry_point = null;

    public static function get_instance(): self {
       if ( is_null( self::$instance ) ) {
          self::$instance = new self();
       }

       return self::$instance;
    }

    public static function run( string $entry_point ): self {
       $plugin = self::get_instance();

       $plugin->entry_point = $entry_point;

       register_activation_hook( $entry_point, function () {
          self::activate();
       } );

       register_deactivation_hook( $entry_point, function () {
          self::deactivate();
       } );

       // Other initialization code...

       return $plugin;
    }
    */

    public function getViewPath( string $view, string $module = '' ): ?string
	{
		return ViewLoader::getViewPath( $view, $module );
	}
	/* Inside, e.g. Supernatural\Module:
	$path = $this->getViewPath( 'monster-list' );
	if ( $path ) {
		include $path;
	}
	*/

	public function renderView( string $view, array $vars = [], string $module = '' ): void
	{
		ViewLoader::render( $view, $vars );
	}

	public function renderViewToString( string $view, array $vars = [], string $module = '' ): string
	{
		return ViewLoader::renderToString( $view, $vars );
	}
	/*
	Usage in a shortcode or module method:
	//
	$html = $this->plugin->renderViewToString( 'partials/monster-list', [
		'monsters' => $this->getMonsterPosts(),
	]);
	*/

    protected static function activate(): void { //public static function activate() {
       flush_rewrite_rules();
    }

    protected static function deactivate(): void { //public static function deactivate() {
       flush_rewrite_rules();
    }

    /*
	register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
	register_activation_hook( __FILE__, 'whx4_flush_rewrites' );
	function whx4_flush_rewrites() {
		// call your CPT registration function here (it should also be hooked into 'init')
		myplugin_custom_post_types_registration();
		flush_rewrite_rules();
	}
	*/

}

