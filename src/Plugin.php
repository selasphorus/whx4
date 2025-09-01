<?php

// Initialize the plugin, register hooks, and manage dependencies

namespace atc\WHx4;

use atc\WHx4\Core\Contracts\PluginContext;
use atc\WHx4\Core\CoreServices;
use atc\WHx4\Core\BootOrder;
use atc\WHx4\Core\PostTypeRegistrar;
use atc\WHx4\Core\SubtypeRegistry;
use atc\WHx4\Core\TaxonomyRegistrar;
use atc\WHx4\Core\FieldGroupLoader;
//use atc\WHx4\Core\SubtypeTermSeeder;
use atc\WHx4\Core\Contracts\ModuleInterface;
use atc\WHx4\Core\SettingsManager;
use atc\WHx4\Admin\SettingsPageController;
use atc\WHx4\Admin\FieldKeyAuditPageController;

use atc\WHx4\Core\ViewLoader;
use atc\WHx4\Utils\TitleFilter;
//
use atc\WHx4\ACF\JsonPaths;
use atc\WHx4\ACF\RestrictAccess;
use atc\WHx4\ACF\BlockRegistrar;

final class Plugin implements PluginContext
{
    private static ?self $instance = null;
    protected bool $booted = false;

	// NB: Set the actual modules array via boot (whx4.php) -- this way, Plugin class contains logic only, and other plugins or themes can register additional modules dynamically
	// WIP clean this up and simplify
	protected array $availableModules = [];
    protected array $activeModules = [];
    protected array $activePostTypes = [];
    //
    protected bool $modulesLoaded = false;
    protected bool $modulesBooted = false;
    /** @var list<class-string<ModuleInterface>> */
    protected array $bootedModules = [];
    private bool $capsAssigned = false;

    // Make these nullable if they’re typed elsewhere
    protected ?PostTypeRegistrar $postTypeRegistrar = null;
    protected ?FieldGroupLoader $fieldGroupLoader = null;
    protected SettingsManager $settingsManager;
    protected ?SettingsManager $settings = null;

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
        error_log( '=== WHx4\Plugin::boot() ===' );
        if ( $this->booted ) {
            return;
		}

		// Allow others to register modules early
		do_action( 'whx4_pre_boot', $this );

        $this->defineConstants();
        $this->registerAdminHooks();

        // Run as early as possible on init so modules are ready before init:10 work.
		if ( did_action('init') ) {
			$this->finishBoot(); // if we're already past init (rare), just run now
		} else {
			add_action('init', [$this, 'finishBoot'], 0);
		}

		/*
		error_log( 'About to setContext for TitleFilter' );
        TitleFilter::setContext( $this ); // $this implements PluginContext
        TitleFilter::boot();
        */
        /*
        add_action( 'init', function() {
			\smith\Rex\Utils\TitleFilter::setContext( Plugin::getInstance() );
			\smith\Rex\Utils\TitleFilter::boot();
		}, 11 );
		*/

		$this->booted = true;
    }

    protected function registerAdminHooks(): void
    {
        //error_log( '=== Plugin::registerAdminHooks() ===' );
        if ( is_admin() ) {
            ( new SettingsPageController( $this ) )->addHooks();
            ( new FieldKeyAuditPageController( $this ) )->addHooks();
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAdminAssets' ] );
        }
    }

    /*protected function registerPublicHooks(): void
    {
        error_log( '=== Plugin::registerPublicHooks() ===' );
        // on 'init': Register post types, taxonomies, shortcodes
        add_action( 'init', [ $this, 'registerPostTypes' ], 10 );
        add_action( 'init', [ $this, 'collectSubtypes' ], 11 );
        add_action( 'acf/init', [ $this, 'registerFieldGroups' ], 11 );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueuePublicAssets' ] );

		// After modules boot, assign capabilities based on handlers
		add_action( 'whx4_modules_booted', [ $this, 'assignPostTypeCaps' ], 20, 2 );
    }*/

    public function finishBoot(): void
	{
	    //error_log('=== Plugin::finishBoot() @ init:0 ===');

		// Boot core services -- TODO: make sure this is still useful...
		CoreServices::boot();

        // Load modules and config

        // Discover all modules registered by core + add‑ons
        $modules = apply_filters( 'whx4_register_modules', [] );
		//error_log( 'modules discovered via whx4_register_modules: '.print_r($modules, true) );
        $this->setAvailableModules( $modules );

        // Settings
        $this->settings = $this->settings ?? new SettingsManager(); // redundant w/ _construct -- WIP

        // First‑run initializer: if no selection saved, enable defaults
        $this->settings->ensureInitialized($this->getAvailableModules());

        // Load saved (or just‑seeded) active modules
        $this->loadActiveModules();

        // Boot active modules and remember which ones succeeded
        $this->bootActiveModules();

		// Ensure the active CPTs filter is added AFTER CPTs (10) and BEFORE Taxonomies (12)
		// WIP 08/23/25
		add_action('init', function (): void {
			add_filter('whx4_active_post_types', function (array $cpts): array {
				// Return slugs of currently active CPTs
				return array_keys($this->getActivePostTypes());
			}, 10, 1);
		}, BootOrder::SUBTYPES); // 11

		// Register systems in the same order that they will run, though prioritied enforce the actual order on 'init' or 'acf/init'

		// Register Custom Post Types
		//(new \smith\Rex\Core\PostTypeRegistrar($this))->register(); // init:10
        $this->postTypeRegistrar = new PostTypeRegistrar($this); // instance-based (needs plugin state)
        $this->postTypeRegistrar->register();                    // add_action('init', ..., BootOrder::CPT)

        // Collect Subtypes
        SubtypeRegistry::register();                             // add_action('init', collect, BootOrder::SUBTYPE_COLLECT)

        // Register shared/global taxonomies? WIP
        /*
        add_filter('whx4_register_taxonomy_handlers', function(array $list): array {
			$list[] = \smith\Rex\Core\Taxonomies\RexTag::class; // object_types may be ['*'] or an explicit list
			return $list;
		});

		// ... and expose active CPTs (for the '*' wildcard) via a small filter the registrar reads:
		add_filter('whx4_active_post_types', function(array $cpts) use ($plugin): array {
			return array_keys($plugin->getActivePostTypes());
		});
		*/

        // Register Custom Taxonomies for active modules
        TaxonomyRegistrar::register();                           // add_action('init', bootstrap, BootOrder::TAXONOMY)

        // Seed subtype terms
        //SubtypeTermSeeder::register();                           // add_action('init', seed, BootOrder::TERM_SEED)

        // Register field groups (admin‑centric, depends on plugin state)
        $this->fieldGroupLoader = new FieldGroupLoader($this);
        $this->fieldGroupLoader->register();                     // add_action('acf/init', ..., BootOrder::ACF_FIELDS)

        // front-end assets (separate hook family)
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueuePublicAssets' ] ); // wip
        //\smith\Rex\Core\Assets::register();                      // add_action('wp_enqueue_scripts', ..., BootOrder::ENQUEUE_ASSETS)

	}

    /*protected function initializeCore(): void
    {
        error_log( '=== Plugin::initializeCore() ===' );

        CoreServices::boot();

        // Load modules and config

        // Discover all modules registered by core + add‑ons
        $modules = apply_filters( 'whx4_register_modules', [] );
        $this->setAvailableModules( $modules );

        // Settings
        $this->settings = $this->settings ?? new SettingsManager(); // redundant w/ _construct -- WIP

        // First‑run initializer: if no selection saved, enable defaults
        $this->settings->ensureInitialized($this->getAvailableModules());

        // Load saved (or just‑seeded) active modules
        $this->loadActiveModules();

        // Lazily build services
        $this->postTypeRegistrar = $this->postTypeRegistrar ?? new PostTypeRegistrar($this);
        $this->subtypeRegistry = $this->subtypeRegistry ?? new SubtypeRegistry($this);
        $this->fieldGroupLoader  = $this->fieldGroupLoader  ?? new FieldGroupLoader($this);

        // Boot active modules and remember which ones succeeded
        $this->bootActiveModules();
    }*/

    // TODO: move this to core Assets class?
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

    protected function defineConstants(): void
    {
    	define( 'WHX4_TEXTDOMAIN', 'whx4' );
    	define( 'WHX4_VERSION', '2.0.0' );
    	//error_log( '=== WHx4 defineConstants() complete ===' ); //ok
    }

	public function setAvailableModules( array $modules ): void
	{
        //error_log( '=== Plugin::setAvailableModules() ===' );
		//error_log( 'modules: '.print_r($modules, true) );

		// Validate classes -- make sure they implement ModuleInterface
		foreach( $modules as $slug => $class ) {
			if ( !class_exists( $class ) ) {
			     error_log( 'The class: ' .$class . ' does not exist.' );
			}
			if ( is_subclass_of( $class, ModuleInterface::class ) ) {
				$this->availableModules[$slug] = $class;
				//error_log( 'Module with slug: ' .$slug . ' and class: ' .$class . ' has been added to availableModules.' );
			} else {
			    error_log( 'Module with slug: ' .$slug . ' and class: ' .$class . ' is not a subclass of ModuleInterface.' );
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


    //public function bootModules(): void
    public function bootActiveModules(): int
    {
        //error_log( '=== Plugin::bootActiveModules() ===' );
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
					//error_log('Module booted! moduleClass: '.$moduleClass);
				} else {
					error_log('boot() method missing for moduleClass: '.$moduleClass);
				}
			} catch (\Throwable $e) {
				error_log('Error booting module '.$moduleClass.': '.$e->getMessage());
			}
        }
        $count = count($this->bootedModules);
		$this->modulesBooted = $count > 0;
		//error_log($count . ' Modules booted');

		/**
		 * Fires after modules have attempted to boot.
		 *
		 * @param self     $plugin
		 * @param string[] $bootedModules
		 */
		do_action('whx4_modules_booted', $this, $this->bootedModules);

		return $count;
    }

    public function modulesBooted(): bool
    {
        return $this->modulesBooted;
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
     * Items in return array are structured as follows: $postTypeClasses[ $postTypeSlug ] = $postTypeHandlerClass;
     */
    public function getActivePostTypes(): array
	{
    	//error_log( '=== Plugin::getActivePostTypes() ===' );

		// Don't reload activePostTypes if we've cached them already
		if ( ! empty( $this->activePostTypes ) ) {
		    //error_log( 'activePostTypes already cached' );
			return $this->activePostTypes;
		}

    	$this->loadActiveModules();
		$enabledPostTypesByModule = $this->getSettingsManager()->getEnabledPostTypeSlugsByModule();
		//$activeSlugsByModule = $this->settingsManager->getEnabledPostTypeSlugsByModule();
		//error_log("enabledPostTypesByModule: " . print_r($enabledPostTypesByModule, true));

		$postTypeClasses = [];

		foreach( $this->activeModules as $moduleSlug => $moduleClass ) {
			//error_log("About to look for activePostTypes for moduleSlug: " . print_r($moduleSlug, true));
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
				//$handlers = $module->getPostTypeHandlers();
				//error_log("definedPostTypes: " . print_r($definedPostTypes, true));

				$enabled = $enabledPostTypesByModule[ $moduleSlug ] ?? $definedPostTypes;
				//error_log("Module $moduleSlug: defined=" . implode(',', $definedPostTypes) . "; enabled=" . implode(',', $enabled));

				//foreach ($definedPostTypes as $postTypeSlug => $name) {
				//foreach ( $handlers as $handlerClass ) {
				foreach ( $definedPostTypes as $postTypeHandlerClass ) {
				    if ( ! class_exists( $postTypeHandlerClass ) ) {
						continue;
					}
					$handler = new $postTypeHandlerClass(); //$postTypeHandler = new $postTypeHandlerClass();
					$postTypeSlug = $handler->getSlug();
					//$slug = ( new $handlerClass( null ) )->getSlug();
					//$className = $handler->getLabels()['singular_name'];
					if  (in_array( $postTypeSlug, $enabled, true )) {
					    //error_log("Post type '$postTypeSlug' from module '$moduleSlug' is now enabled (class: '$postTypeHandlerClass' ).");
						//$postTypeClasses[ $postTypeSlug ] = $postTypeHandlerClass; //$className;
						$this->activePostTypes[ $postTypeSlug ] = $postTypeHandlerClass;
					} else {
						error_log("Post type '$postTypeSlug' from module '$moduleSlug' is not enabled.");
					}
					/*
					if (
						! isset( $activeSlugsByModule[ $moduleSlug ] ) ||
						! in_array( $slug, $activeSlugsByModule[ $moduleSlug ], true )
					) {
						continue;
					}
					*/
				}
			} catch( \Throwable $e ) {
				error_log("Exception in getActivePostTypes for module $moduleSlug: " . $e->getMessage());
			}
		}

		//error_log( '=== END getActivePostTypes() ===' );
		//error_log("active postTypeClasses: " . print_r($postTypeClasses, true));

		return $this->activePostTypes;
		//return array_unique($postTypeClasses);
	}

	//getEnabledTaxonomies

    // Loop through each module and register its post types.
    /*public function registerPostTypes(): void
	{
		error_log( '=== Plugin::registerPostTypes() ===' );

		// Abort if no modules have been booted
		if ( !$this->modulesBooted ) {
		    error_log( '=== no modules booted yet => abort ===' );
			return;
		}

		$activePostTypes = $this->getActivePostTypes();
		//error_log( 'Plugin::registerPostTypes() >> activePostTypes: '.print_r($activePostTypes, true) );

		if ( empty( $activePostTypes ) ) {
			error_log( 'No active post types found. Skipping registration.' );
			return;
		}

		$this->postTypeRegistrar?->registerMany( $activePostTypes );
	}*/
/*
    public function registerFieldGroups(): void
    {
        error_log( '=== registerFieldGroups ===' );

        // Abort if no modules have been booted
		if ( !$this->modulesBooted ) {
		    error_log( '=== no modules booted yet => abort ===' );
			return;
		}

        $this->fieldGroupLoader?->registerAll();
    }*/

	/// WIP
	public function assignPostTypeCaps(array $bootedModules = []): void
    {
        //error_log( '=== assignPostTypeCaps ===' );

        if ($this->capsAssigned) {
			return;
		}
		$this->capsAssigned = true;

        try {
            if (!$bootedModules) {
                error_log( 'No modules were booted; skipping.' );
                //self::log('No modules were booted; skipping.');
                return;
            }

            $handlers = $this->getActivePostTypes();
            //error_log( 'handlers: ' . print_r( $handlers, true ) );

            if (empty($handlers)) {
                error_log('No active post type handlers found; skipping.');
                //self::log('No active post type handlers found; skipping.');
                return;
            }

            if (!$this->postTypeRegistrar) {
                error_log('postTypeRegistrar is null; cannot assign capabilities.');
                //self::log('postTypeRegistrar is null; cannot assign capabilities.');
                return;
            }

            $count = is_countable($handlers) ? count($handlers) : 0;
            error_log("Assigning capabilities for {$count} handler(s).");
            //self::log("Assigning capabilities for {$count} handler(s).");
            //error_log( 'handlers: ' . print_r( $handlers, true ) );

            /*
            // Optional: short-circuit if nothing changed since last run
			$activeSlugs = array_keys($this->getActivePostTypes());
			$hash = md5(implode('|', $activeSlugs));
			$stored = get_option('whx4_caps_hash');

			if ($stored === $hash) {
				return;
			}
			*/

            $this->postTypeRegistrar->assignPostTypeCapabilities($handlers);

            //error_log('Capabilities assigned successfully.');
            //self::log('Capabilities assigned successfully.');

            //update_option('whx4_caps_hash', $hash);
        } catch (\Throwable $e) {
            error_log('Error in assignPostTypeCaps: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() );
            /*self::log(
                'Error in assignPostTypeCaps: ' . $e->getMessage() .
                ' @ ' . $e->getFile() . ':' . $e->getLine()
            );*/
        }
    }
// WIP 08/18/25
    /*private static function log(string $msg): void
    {
        // Prefer CLI output when available
        if (defined('WP_CLI') && \WP_CLI) {
            \WP_CLI::debug($msg, 'whx4');
            return;
        }

        // Otherwise log to php/wp debug.log
        error_log('[Rex] ' . $msg);
    }*/

	/*public function assignPostTypeCapabilities(): void {
		$this->postTypeRegistrar->assignPostTypeCapabilities();
	}

	public function removePostTypeCapabilities(): void {
		$this->postTypeRegistrar->removePostTypeCapabilities();
	}*/

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

    protected static function activate(): void {
       flush_rewrite_rules();
    }

    protected static function deactivate(): void {
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

