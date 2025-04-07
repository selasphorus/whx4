<?php

// Initialize the plugin, register hooks, and manage dependencies

namespace atc\WHx4\Core; //namespace atc\WHx4\Core;

class Plugin {
    
    /*
    public function __construct() {
        add_action('init', [ $this, 'init' ]);
    }

    public function init() {
        // Custom initialization logic
    }
    */
    
    
    public function __construct() {  
        $this->define_constants();
        $this->setup_actions(); //$this->init_hooks();
        //$this->activate_modules();
    }

    private function define_constants() {
        define( 'WHX4_PLUGIN_DIR', WP_PLUGIN_DIR. '/whx4/' ); //define( 'WHX4_PLUGIN_DIR', __DIR__ );
        //define('WHX4_DIR', plugin_dir_path(__FILE__));  
        define( 'WHX4_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
        define( 'WHX4_PLUGIN_BLOCKS', WHX4_PLUGIN_DIR . '/blocks/' );
    	define( 'WHX4_VERSION', '2.0.0' );
    }


	/**
	 * Set up Hooks and Actions
	 */
    private function setup_actions() { //public function setup_actions() { //private function init_hooks() {
        add_action('init', [$this, 'activate_modules']);
        //add_action('init', [$this, 'load_components']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
        //register_activation_hook( DIR_PATH, array( 'WHx4', 'activate' ) );
		//register_deactivation_hook( DIR_PATH, array( 'WHx4', 'deactivate' ) );
    }

    public function enqueue_admin_assets() {  
        //wp_enqueue_style('newsletter-admin', NEWSLETTER_DIR . 'assets/admin.css');  
    }

    public function enqueue_public_assets() {  
        $fpath = WHX4_PLUGIN_DIR . '/css/whx4.css';
    	if (file_exists($fpath)) { $ver = filemtime($fpath); } else { $ver = "240823"; }  
    	wp_enqueue_style( 'whx4-style', plugins_url( 'css/whx4.css', __FILE__ ), $ver );
    }
    
    protected function use_custom_caps() {
		$use_custom_caps = false;
		if ( isset($options['use_custom_caps']) && !empty($options['use_custom_caps']) ) {
			$use_custom_caps = true;
		}
		return $use_custom_caps;
	}

    public function activate_modules() { // why not private or protected?
    	
    	// Get plugin options to determine which modules are active
		$active_modules;
		$options = get_option( 'whx4_settings' );
		if ( get_field('whx4_active_modules', 'option') ) { $active_modules = get_field('whx4_active_modules', 'option'); } else { $active_modules = array(); }
		$cpts = array();
		
		$active_modules[] = 'monsters'; // tft
		
		// Activate each of the modules -- register post type(s) etc.
		foreach ( $active_modules as $module ) {
			
			switch( $module ) {
				case 'monsters':
					$cpts[] = array( 'name' => 'monster', 'plural_name' => 'monsters', 'caps' => array('post') );
				case 'people':
					// TODO: fix custom caps setup => 'caps' => array('person', 'people')
					$cpts[] = array( 'slug' => 'person', 'name' => 'Person', 'plural_name' => 'People', 'caps' => array('post'), 'taxonomies' => array( 'person_category', 'person_title', 'admin_tag' ) );
					$cpts[] = array( 'slug' => 'group', 'name' => 'Group', 'plural_name' => 'Groups', 'caps' => array('post'), 'show_in_menu' => 'edit.php?post_type=person' );
				case 'places':
					$cpts[] = array( 'slug' => 'venue', 'name' => 'Venue', 'plural_name' => 'Venues', 'caps' => array('post') ); //, 'taxonomies' => array( 'person_category', 'person_title', 'admin_tag' )
					$cpts[] = array( 'slug' => 'address', 'name' => 'Address', 'plural_name' => 'Addresses', 'caps' => array('post'), 'show_in_menu' => 'edit.php?post_type=venue' );
					$cpts[] = array( 'slug' => 'building', 'name' => 'Building', 'plural_name' => 'Buildings', 'caps' => array('post'), 'show_in_menu' => 'edit.php?post_type=venue' );
				case 'events':
					$cpts[] = array( 'slug' => 'event', 'name' => 'Event', 'plural_name' => 'Events', 'caps' => array('post') );
					$cpts[] = array( 'slug' => 'event_recurring', 'name' => 'Recurring Event', 'plural_name' => 'Recurring Events', 'caps' => array('post'), 'show_in_menu' => 'edit.php?post_type=whx4_event' );
					$cpts[] = array( 'slug' => 'event_series', 'name' => 'Event Series', 'plural_name' => 'Event Series', 'caps' => array('post'), 'show_in_menu' => 'edit.php?post_type=whx4_event' );
				default:
					//throw new Exception("Invalid module");
			}
			
			if ( function_exists('acf_add_options_page') ) {
				// Add module options page
				acf_add_options_sub_page(array(
					'page_title'	=> ucfirst($module).' Module Options',
					'menu_title'    => ucfirst($module).' Module Options', //'menu_title'    => 'Archive Options', //ucfirst($cpt_name).
					'menu_slug' 	=> $module.'-module-options',
					//'parent_slug'   => 'edit.php?post_type='.$primary_cpt,
				));
			}
			
		}
    
    	// Register Custom Post Types
    	$cptm = new \atc\WHx4\Core\CPTRegistrar(); // Formerly: CustomPostTypeManager();
		
		foreach ( $cpts as $cpt_args ) {
			$cpt_name = $cpt_args['name'];
			if ( !post_type_exists( $cpt_name ) ) {
				//echo "post_type ".$cpt_name." does not exist!"; // tft //$cpt_tft = $cptm->register_custom_post_type ( $cpt_args ); //var_dump($cpt_tft); // tft
				$cptm->register_custom_post_type ( $cpt_args );
				// TODO: Register associated taxonomies
			} else {
				//echo "post_type ".$cpt_name." already exists!"; // tft
			}
		}
		
    }
    
	/*
	public static function register_custom_post_types ( $cpts ) {
	
	}
	
	public static function register_custom_post_type ( $args, $caps ) {
	
	}
	*/    
    
    
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
    
}

// To initialize the class:
// $plugin = new \WHx4\Core\Plugin();

?>