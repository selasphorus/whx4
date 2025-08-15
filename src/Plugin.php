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
use atc\WHx4\Admin\FieldKeyAuditPageController;

use atc\WHx4\Core\ViewLoader;
///use atc\WHx4\Utils\TitleFilter;
//
use atc\WHx4\ACF\JsonPaths;
use atc\WHx4\ACF\RestrictAccess;
use atc\WHx4\ACF\BlockRegistrar;

final class Plugin
{
    private static ?self $instance = null;
    protected bool $booted = false;

	// NB: Set the actual modules array via boot (whx4.php) -- this way, Plugin class contains logic only, and other plugins or themes can register additional modules dynamically
	// WIP clean this up and simplify
	protected array $availableModules = [];
    protected array $activeModules = [];
    protected bool $modulesLoaded = false;
    protected bool $modulesBooted = false;
    /** @var list<class-string<ModuleInterface>> */
    protected array $bootedModules = [];
    // Make these nullable if they’re typed elsewhere
    protected ?PostTypeRegistrar $postTypeRegistrar = null;
    protected ?FieldGroupLoader $fieldGroupLoader = null;
    //protected TaxonomyRegistrar $taxonomyRegistrar;
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
    private function __clone()
    {}

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
        error_log( '=== Plugin::boot() ===' );
        if ( $this->booted ) {
            return;
		}

		// Allow others to register modules early
		do_action( 'whx4_pre_boot' );

        $this->defineConstants();
        $this->registerAdminHooks();
        $this->registerPublicHooks();
        //$this->initializeCore();

        // Defer core initialization until other plugins finished loading modules via filters
        add_action('plugins_loaded', [$this, 'finishBoot'], 20);

		// Continue with the rest of the boot process
		$this->booted = true;
    }

    public function finishBoot(): void
	{
	    error_log( '=== Plugin::finishBoot() ===' );

		// Make modules discoverable
		$this->initializeCore(); // sets available modules, seeds defaults if needed, loads/boots

		// Signal “modules are ready” (cap assignment can hook this or we can call inline)
		do_action('whx4_modules_booted', $this, $this->bootedModules);
	}

    protected function registerAdminHooks(): void
    {
        error_log( '=== Plugin::registerAdminHooks() ===' );
        if ( is_admin() ) {
            ( new SettingsPageController( $this ) )->addHooks();
            ( new FieldKeyAuditPageController( $this ) )->addHooks();
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAdminAssets' ] );
        }
    }

    protected function registerPublicHooks(): void
    {
        error_log( '=== Plugin::registerPublicHooks() ===' );
        // on 'init': Register post types, taxonomies, shortcodes
        add_action( 'init', [ $this, 'registerPostTypes' ], 10 );
        add_action( 'acf/init', [ $this, 'registerFieldGroups' ], 11 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueuePublicAssets' ] );

		// WIP
		// After modules boot, assign capabilities based on handlers
        add_action('whx4_modules_booted', function(Plugin $plugin) {
			$handlers = $plugin->getActivePostTypes(); //$handlers = $plugin->getActivePostTypeHandlers();
			if ($handlers) {
				$plugin->postTypeRegistrar->assignPostTypeCapabilities($handlers);
			}
		}, 20, 1);
    }

    protected function initializeCore(): void
    {
        error_log( '=== Plugin::initializeCore() ===' );

        CoreServices::boot();

        // Load modules and config

        // 1) Discover all modules registered by core + add‑ons
        $modules = apply_filters( 'whx4_register_modules', [] );
        $this->setAvailableModules( $modules );

        // 2) First‑run initializer: if no selection saved, enable defaults
        $this->ensureActiveModulesOptionSeeded();

        // 3) Load saved (or just‑seeded) active modules
        $this->loadActiveModules();

        // 4) Lazily build services
        $this->postTypeRegistrar = $this->postTypeRegistrar ?? new PostTypeRegistrar($this);
        $this->fieldGroupLoader  = $this->fieldGroupLoader  ?? new FieldGroupLoader($this);

        // 5) Boot active modules and remember which ones succeeded
        $this->bootActiveModules();
    }

	public function enqueueAdminAssets(string $hook): void
	{
		//if ( $hook !== 'settings_page_whx4-settings' ) { return; }

		wp_enqueue_script(
			'whx4-settings',
			WHX4_PLUGIN_URL . 'assets/js/settings.js',
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

    	wp_enqueue_style(
            'whx4-admin-style',
            WHX4_PLUGIN_URL . 'assets/css/whx4-admin.css',
            [],
            filemtime( WHX4_PLUGIN_DIR . 'assets/css/whx4-admin.css' )
        );
	}

    public function enqueuePublicAssets(): void
    {
    	wp_enqueue_style(
            'whx4-style',
            WHX4_PLUGIN_URL . 'assets/css/whx4.css',
            [],
            filemtime( WHX4_PLUGIN_DIR . 'assets/css/whx4.css' )
        );
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
    	define( 'WHX4_VERSION', '2.0.0' );
    	//error_log( '=== WHx4 defineConstants() complete ===' ); //ok
    }

	public function setAvailableModules( array $modules ): void
	{
        error_log( '=== Plugin::setAvailableModules() ===' );
		//error_log( 'modules: '.print_r($modules, true) );
		foreach( $modules as $slug => $class ) {
			if ( !class_exists( $class ) ) {
			     error_log( 'The class: ' .$class . ' does not exist.' );
			}
			if ( is_subclass_of( $class, ModuleInterface::class ) ) {
				$this->availableModules[$slug] = $class;
			} else {
			    error_log( 'Module with slug: ' .$slug . ' and class: ' .$class . ' is not a subclass of ModuleInterface.' );
			}
		}
	}

	public function getAvailableModules(): array
	{
		return $this->availableModules;
	}


	protected function ensureActiveModulesOptionSeeded(): void
	{
		error_log( '=== Plugin::ensureActiveModulesOptionSeeded() ===' );
		$saved = get_option('whx4_active_modules');

		// Only seed when the option is missing or empty
		if (!is_array($saved) || $saved === []) {
			update_option('whx4_active_modules', $this->getDefaultActiveModules());
			update_option('whx4_initialized', 1);
		}
	}

	/** @return string[] */
	protected function getDefaultActiveModules(): array
	{
	    error_log( '=== Plugin::getDefaultActiveModules() ===' );
		// Default = “all discovered modules”, overrideable via filter
		// Keys must match what you use in getAvailableModules(), e.g., slugs or FQCNs
		$all = array_keys($this->getAvailableModules());

		/** @var string[] */
		$defaults = apply_filters('whx4_default_active_modules', $all);
		return $defaults;
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


    //public function bootModules(): void
    public function bootActiveModules(): int
    {
        error_log( '=== Plugin::bootActiveModules() ===' );
        $this->bootedModules = [];
        //error_log( '=== Plugin: bootActiveModules() ===' );
        foreach ( $this->getActiveModules() as $moduleClass ) {
            //error_log( 'About to attempt instantiation for moduleClass: ' . $moduleClass );
        	$module = new $moduleClass();
        	if (!$module instanceof ModuleInterface) {
				error_log('Module does not implement ModuleInterface: '.$moduleClass);
				continue;
			}
        	$module->setPlugin($this);

        	//error_log( 'About to attempt module boot() for moduleClass: ' . $moduleClass );
        	try {
				if (method_exists($module, 'boot')) {
					$module->boot();
					$this->bootedModules[] = $moduleClass;
				} else {
					error_log('boot() method missing for moduleClass: '.$moduleClass);
				}
			} catch (\Throwable $e) {
				error_log('Error booting module '.$moduleClass.': '.$e->getMessage());
			}
        }
        $count = count($this->bootedModules);
		$this->modulesBooted = $count > 0;
		error_log($count . 'Modules booted');

		/**
		 * Fires after modules have attempted to boot.
		 *
		 * @param self     $plugin
		 * @param string[] $bootedModules
		 */
		do_action('whx4_modules_booted', $this, $this->bootedModules);

		return $count;
    }


/*public function getActivePostTypeHandlers(): array
{
    $handlers = [];
    foreach ($this->getBootedModules() as $moduleClass) {
        // @var ModuleInterface $module
        $module = new $moduleClass();
        $module->setPlugin($this);
        foreach ($module->getPostTypeHandlers() as $handler) {
            $handlers[] = $handler;
        }
    }
    return $handlers;
}*/

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
				$moduleSlug = strtolower($moduleInstance->getSlug()); //$moduleSlug = strtolower($moduleInstance->getName());

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
					    //error_log("Post type '$postTypeSlug' from module '$moduleSlug' is now enabled (class: '$postTypeHandlerClass' ).");
						$postTypeClasses[ $postTypeSlug ] = $postTypeHandlerClass; //$className;
					} else {
						error_log("Post type '$postTypeSlug' from module '$moduleSlug' is not enabled.");
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
		error_log( '=== registerPostTypes() ===' );

		// Abort if no modules have been booted
		if ( !$this->modulesBooted ) {
			return;
		}
		//$this->postTypeRegistrar?->registerAll($this);

		$activePostTypes = $this->getActivePostTypes();
		error_log( 'activePostTypes: '.print_r($activePostTypes, true) );

		if ( empty( $activePostTypes ) ) {
			error_log( 'No active post types found. Skipping registration.' );
			return;
		}

		$this->postTypeRegistrar?->registerMany( $activePostTypes );
		// WIP:
		//$this->postTypeRegistrar?->registerAll($this);
	}



    public function registerFieldGroups(): void
    {
        //error_log( '=== registerFieldGroups ===' );

        // Abort if no modules have been booted
		if ( !$this->modulesBooted ) {
			return;
		}

        $this->fieldGroupLoader?->registerAll();
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

