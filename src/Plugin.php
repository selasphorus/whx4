<?php

// Initialize the plugin, register hooks, and manage dependencies

namespace atc\WHx4;

use atc\WHx4\Core\PostTypeRegistrar;
use atc\WHx4\Core\FieldGroupLoader;
use atc\WHx4\Core\Contracts\ModuleInterface;
use atc\WHx4\Admin\SettingsManager;
//
use atc\WHx4\ACF\JsonPaths;
use atc\WHx4\ACF\RestrictAccess;
use atc\WHx4\ACF\BlockRegistrar;

final class Plugin
{
    private static ?self $instance = null;
    
    protected PostTypeRegistrar $postTypeRegistrar;
    protected FieldGroupLoader $fieldGroupLoader;
    protected SettingsManager $settingsManager;

	// Set modules array via boot
	// This way, Plugin contains logic only, and other plugins or themes can register additional modules dynamically
	protected array $availableModules = []; // 'supernatural' => \YourPlugin\Modules\Supernatural\Module::class
   	//protected array $availableModules = [ 'supernatural' => atc\WHx4\Modules\Supernatural\Module::class ]; // default tft
    
    protected array $activeModules = [];
    protected bool $modulesLoaded = false;

    //protected function __construct() {}
    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct()
    {
        // Initialize internal state or dependencies
    }
    
    /**
     * Prevent cloning of the instance.
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserializing of the instance.
     */
    public function __wakeup()
    {
        throw new \RuntimeException('Cannot unserialize a singleton.');
    }

    public static function getInstance(): self
    {
        if( static::$instance === null ) {
            static::$instance = new self();
        }

        return static::$instance;
    }
    
    public function boot(): void
    {
    	// Step 1 -- on 'plugins_loaded': Load modules, config, and class setup
        $this->loadCore();
        
        // Step 2 -- on 'init': Register post types, taxonomies, shortcodes
        add_action( 'init', [ $this, 'registerPostTypes' ] );
        
        // Step 3 -- on 'acf/init': Register ACF fields
        add_action( 'acf/init', [ $this, 'registerFieldGroups' ] );
        
        // Step 4 -- on admin_init
        add_action( 'admin_init', [ $this, 'loadAdmin' ] );
        
        // Step 4a: Admin scripts/styles
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAdminAssets' ] );
        
        // Step 4b: Frontend scripts/styles
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueuePublicAssets' ] );
    }

    public function loadCore(): void
    {
    	// Load modules and config
        $modules = apply_filters( 'whx4_register_modules', [] );
        $this->setAvailableModules( $modules );
        $this->loadActiveModules();

		// Initialize core components
        $this->defineConstants();
        $this->postTypeRegistrar = new PostTypeRegistrar( $this );
        $this->fieldGroupLoader = new FieldGroupLoader( $this );
    }

	public function loadAdmin(): void
	{
		$this->settingsManager = new SettingsManager( $this );
	}

    public function registerFieldGroups(): void
    {
        $this->fieldGroupLoader->registerAll();
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

	
	
	// Call this during plugin init (e.g. hooked into 'init').
	public function registerHooks(): void
	{
		// Register post types
		$this->registerPostTypes();
		
		// Register ACF field groups (after ACF is ready)
		JsonPaths::register();
		RestrictAccess::register(); //RestrictAccess::apply();
		BlockRegistrar::register();
		add_action( 'acf/init', [ $this->fieldGroupLoader, 'registerAll' ] );
	
		// And enqueue assets
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
		add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
	}

    // Call this during plugin init (e.g. hooked into 'init').
	public function bootv1(): void
	{/*
		//error_log( '=== WHx4 boot() Step 1 ===' ); //ok
		
		// 1. Register available modules via filter
		$modules = apply_filters( 'whx4_register_modules', [] );
		$this->setAvailableModules( $modules );

		//error_log( '=== WHx4 boot() Step 2 ===' ); //ok
		
		// 2. Load active modules from settings
		$this->loadActiveModules();

		//error_log( '=== WHx4 boot() Step 3 ===' ); //ok
	
		// 3. Initialize core components
		$this->defineConstants();
		$this->postTypeRegistrar = new PostTypeRegistrar($this);
		$this->fieldGroupLoader = new FieldGroupLoader($this);

		//error_log( '=== WHx4 boot() Step 4 ===' ); //ok

		// 4. Register ACF field groups (after ACF is ready)
		JsonPaths::register();
		RestrictAccess::register(); //RestrictAccess::apply();
		BlockRegistrar::register();
		add_action( 'acf/init', [ $this->fieldGroupLoader, 'registerAll' ] );

		//error_log( '=== WHx4 boot() Step 5 ===' ); //ok
	
		// 5. Hook into WordPress lifecycle
		$this->setupActions();
		$this->settingsManager = new SettingsManager($this);
	*/}
    
    private function defineConstants()
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
			if( is_subclass_of( $class, ModuleInterface::class ) ) {
				$this->availableModules[$slug] = $class;
			}
		}
	}

	public function getAvailableModules(): array
	{
		error_log( '=== WHx4 getAvailableModules() ===' );
		return $this->availableModules;
	}

	/**
     * Load active modules from the saved plugin settings.
     */
    protected function loadActiveModules(): void
    {
        if( $this->modulesLoaded ) {
            return;
        }

        $settings = get_option( 'whx4_plugin_settings', [] );
        // Get array of enabled modules (e.g. ['supernatural', 'people', 'places'])
        $activeModuleSlugs = $settings['active_modules'] ?? [];

        foreach( $activeModuleSlugs as $slug ) {
            if( isset( $this->availableModules[$slug] ) ) {
                $this->activeModules[] = $this->availableModules[$slug];
            }
        }

        $this->modulesLoaded = true;
    }

    public function getActiveModules(): array
    {
        $this->loadActiveModules();
        return $this->activeModules;
    }

    /**
     * Returns all enabled post types across active modules,
     * based on both the module definitions and plugin settings.
     */

    /*
    public function getActivePostTypes(): array
    {
        $this->loadActiveModules();

        $settings = get_option( 'whx4_plugin_settings', [] );
        $enabledPostTypesByModule = $settings['enabled_post_types'] ?? [];

        $postTypes = [];

        foreach( $this->activeModules as $moduleClass ) {
            if( is_subclass_of( $moduleClass, ModuleInterface::class ) ) {
                $slug = $moduleClass::getSlug();
                $definedPostTypes = $moduleClass::getPostTypes();
                //$enabled = $enabledPostTypesByModule[$slug] ?? $definedPostTypes;
                $enabled = $enabledPostTypesByModule[$slug] ?? array_keys( $definedPostTypes );
                
                //foreach( $definedPostTypes as $type ) {
                foreach( array_keys( $definedPostTypes ) as $type ) {
					if( in_array( $type, $enabled, true ) ) {
						$postTypes[] = $type;
					}
				}
            }
        }

        return array_unique( $postTypes );
    }
    */
    
    public function getActivePostTypes(): array
	{
		
    	error_log( '=== WHx4 getActivePostTypes() ===' );
    	
    	$this->loadActiveModules();
	
		$settings = get_option( 'whx4_plugin_settings', [] );
		$enabledPostTypesByModule = $settings['enabled_post_types'] ?? [];
	
		error_log("Loaded enabled post types: " . print_r($enabledPostTypesByModule, true));
	
		$postTypes = [];
	
		foreach( $this->activeModules as $moduleClass ) {
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
				$definedPostTypes = $moduleInstance->getPostTypes();
				error_log("definedPostTypes: " . print_r($definedPostTypes, true));
				
				$enabled = $enabledPostTypesByModule[ $moduleSlug ] ?? $definedPostTypes;
	
				error_log("Module $slug: defined=" . implode(',', $definedPostTypes) . "; enabled=" . implode(',', $enabled));
	
				foreach ($definedPostTypes as $postTypeSlug => $name) {
					if (in_array( $postTypeSlug, $enabled, true )) {
						$postTypes[] = $postTypeSlug;
					} else {
						error_log("Post type '$postTypeSlug' from module '$moduleSlug' is not enabled.");
					}
				}
			} catch( \Throwable $e ) {
				error_log("Exception in getActivePostTypes for module $moduleClass: " . $e->getMessage());
			}
		}
	
		//error_log( '=== WHx4 getActivePostTypes() complete -- about to return ===' );
		error_log("active postTypes: " . print_r($postTypes, true));
		
		return array_unique($postTypes);
	}

    /**
     * Loop through each module and register its post types.
     */
    public function registerPostTypes(): void
	{
		error_log( '=== WHx4 registerPostTypes() ===' );
		
		$activePostTypes = $this->getActivePostTypes();

		error_log( 'activePostTypes: '.print_r($activePostTypes, true) );
	
		if( empty( $activePostTypes ) ) {
			error_log( 'No active post types found. Skipping registration.' );
			return;
		}
	
		$this->postTypeRegistrar->registerMany( $activePostTypes );
    	//$this->postTypeRegistrar->registerMany( $this->getActivePostTypes() );
	}
    
    protected function registerTaxonomies(): void
    {
		// Register Custom Taxonomies for active modules		
    }    


	/**
	 * Set up Hooks and Actions
	 */
	protected function setupActions(): void
	{
		error_log( '=== WHx4 setupActions() ===' );
    	add_action( 'init', [ $this, 'registerPostTypes' ] );
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
        //register_activation_hook( DIR_PATH, [ 'WHx4', 'activate' ] );
		//register_deactivation_hook( DIR_PATH, [ 'WHx4', 'deactivate' ] );
    }
    
    protected function use_custom_caps() {
		$use_custom_caps = false;
		if ( isset($options['use_custom_caps']) && !empty($options['use_custom_caps']) ) {
			$use_custom_caps = true;
		}
		return $use_custom_caps;
	}
    
    public function load_components() {  
        $dbm = new DatabaseManager();
        //$api = new MailchimpAPI();  
        //new AdminSettings($db, $api);  
        //new FrontendForm($db);  
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

