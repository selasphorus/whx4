<?php

namespace atc\WHx4\Modules\Snippets;

use atc\WXC\Module as BaseModule;
use atc\WXC\Shortcodes\ShortcodeManager;

use atc\WHx4\Modules\Snippets\PostTypes\Snippet;

final class SnippetsModule extends BaseModule
{
    public function boot(): void
    {
        $this->registerDefaultViewRoot();
        parent::boot();

        //ShortcodeManager::add(Shortcodes\MediaPlayerShortcode::class);
        //ShortcodeManager::add(Shortcodes\AccountsShortcode::class);
    }

    public function getPostTypeHandlerClasses(): array
    {
        return [
            Snippet::class,
        ];
    }
    
    //
    
    
	// TODO -- with all following methods: rename, refactor to simplify, standardize etc.
	
	// Get array of snippet IDs matching given attributes
	function get_snippets ( $args = array() )
	{
		// TS/logging setup
		$do_ts = devmode_active( array("sdg", "snippets") );
		$do_log = false;
		sdg_log( "divline2", $do_log );
		sdg_log( "function called: show_snippets", $do_log );
	
		// Init vars
		$arr_result = array();
		$info = "";
		$ts_info = "";
		$post_type = null;
		$active_snippets = array(); // this array will containing snippets matched for display on the given post
	
		// Defaults
		$defaults = array(
			'post_id' => null,
			'limit'   => -1,
			'run_updates'  => false,
			'devmode' => false,
			'return' => 'info',
			'snippet_position' => 'side', // other option: bottom
			'sidebar_id' => 'sidebar-1', // default sidebar -- phase this out in favor position var?
			'classes' => array(), // for use when called by stc_body_class fcn
		);
	
		// Parse & Extract args
		$args = wp_parse_args( $args, $defaults );
		extract( $args );
	
		/*if ( $devmode ) {
			$info .= '<h2>Snippets -- WIP</h2>';
			//$info .= '<p>show : Show everywhere<br />hide : Hide everywhere<br />selected : Show widget on selected<br />notselected : Hide widget on selected</p>';
			$info .= "args: <pre>".print_r($args, true)."</pre>";
		}*/
		//if ( $do_ts && !empty($ts_info) ) { $info .= "get_snippets args: <pre>".print_r($args, true)."</pre>"; }
	
		// Is this a single post of some kind, or another kind of page (e.g. taxonomy archive)
	
		// is_singular, is_archive, is_tax, is_post_type_archive
	
		// Get post_type, if applicable
		if ( is_singular() ) { // is_single
			$info .= "is_singular<br />";
			if ( $post_id === null ) { $post_id = get_the_ID(); }
			$post_type = get_post_type( $post_id );
		} else {
			$info .= "NOT is_singular<br />";
			//$post_type = get_post_type( get_queried_object_id() );
			$post_type = "N/A";
			//post_type_archive_title();
			if ( is_archive() ) {
				$info .= "is_archive<br />";
				// what kind of archive?
				$object = get_queried_object();
				if ( is_object($object) ) {
					$object_class = get_class($object);
					$info .= "object_class: ".$object_class."<br />";
					//
					//$info .= "get_queried_object: <pre>".print_r($object,true)."</pre>";
					if ( is_tax() ) {
						$tax = $object->taxonomy;
						$info .= "tax: ".$tax."<br />";
						$tax_obj = get_taxonomy($tax);
						$tax_post_types = $tax_obj->object_type;
						$info .= "tax_post_types: ".print_r($tax_post_types,true)."<br />";
						if ( is_array($tax_post_types) && count($tax_post_types) == 1 ) { $post_type = $tax_post_types[0]; }
					} else if ( is_post_type_archive() ) {
						$info .= "is_post_type_archive: ";
						$post_archive_title = post_type_archive_title("",false);
						$info .= $post_archive_title."<br />";
						if ( $object && $object->name ) {
							$object_name = $object->name;
						} else {
							$object_name = strtolower($post_archive_title);
						}
						$info .= "object_name: ".$object_name."<br />";
						$post_type = $object_name;
					} else {
						//$info .= "get_the_archive_title: ".get_the_archive_title()."<br />";
						//$info .= "post_type_archive_title: ".post_type_archive_title()."<br />";
					}
				}
				// WIP
			}
		}
		$info .= "post_type: $post_type<br />";
	
		// Check for custom sidebars
		$cs = get_post_meta( $post_id, '_cs_replacements', true );
		//if ( $cs ) { $info .= "custom sidebar: <pre>".print_r($cs, true)."</pre>"; }
		//e.g. Array( [sidebar-1] => cs-17 )
	
		// Set up basic query args for snippets retrieval
		$wp_args = array(
			'post_type'        => 'snippet',
			'post_status'    => 'publish',
			'posts_per_page'=> $limit,
			'fields'        => 'ids',
			//'orderby'        => 'meta_value',
			//'order'            => 'ASC',
			//'meta_key'        => 'sidebar_sortnum',
			'orderby' => array(
				'priority_clause' => 'ASC',
				'sort_clause' => 'ASC',
			),
		);
	
		// Meta query
		$meta_query = array(
			'relation' => 'AND',
			'snippet_display' => array(
				'key' => 'snippet_display',
				'value' => array('show', 'selected', 'notselected'),
				'compare' => 'IN',
			),
			'sort_clause' => array(
				'key' => 'sidebar_sortnum',
				'compare' => 'EXISTS',
			),
			'priority_clause' => array(
				'key' => 'snippet_priority',
				'compare' => 'EXISTS',
			),
			'snippet_position' => array(
				'key' => 'snippet_position',
				'value' => $snippet_position,
				'compare' => '=',
			),
			// The sidebar clause ensures that we don't get widgets from bottom-widgets, wp_inactive_widgets, etc.
			/*'sidebar_id' => array(
				'relation' => 'OR',
				array(
					'key' => 'sidebar_id',
					'value' => $sidebar_id,
					'compare' => '=',
				),
				array(
					'key' => 'sidebar_id',
					'value' => 'cs-',
					'compare' => 'LIKE',
				),
			),*/
		);
		$wp_args['meta_query'] = $meta_query;
	
		$arr_posts = new WP_Query( $wp_args );
		$snippets = $arr_posts->posts;
		//$info .= "WP_Query run as follows:";
		//$info .= "wp_args: <pre>".print_r($wp_args, true)."</pre>";
		//$info .= "wp_query: <pre>".$arr_posts->request."</pre>"; // print sql tft
		$info .= "[".count($snippets)."] snippets found.<br />";
	
		// Determine which snippets should be displayed for the post in question
		foreach ( $snippets as $snippet_id ) {
	
			$snippet_info = "";
			$snippet_logic_info = "";
			//
			$snippet_display = get_post_meta( $snippet_id, 'snippet_display', true );
			$sidebar_id = get_post_meta( $snippet_id, 'sidebar_id', true );
			$any_all = get_post_meta( $snippet_id, 'any_all', true );
			if ( empty($any_all) ) { $any_all = "any"; } // TODO: update_post_meta
			//
			$title = get_the_title( $snippet_id );
			$widget_uid = get_post_meta( $snippet_id, 'widget_uid', true );
			//
			$snippet_status = "unknown"; // init
			$snippet_info .= $title.' ['.$snippet_id.'/'.$widget_uid.'/'.$snippet_display;
			if ( $sidebar_id ) { $snippet_info .= '/'.$sidebar_id; }
			$snippet_info .= ']<br />';
	
			// Run updates?
			if ( $run_updates ) { $snippet_info .= '<div class="code">'.update_snippet_logic ( array( 'snippet_id' => $snippet_id ) ).'</div>'; }
	
			// TMP during transition?
			// TODO: add snippet status field?
			if ( $sidebar_id == "wp_inactive_widgets" ) {
	
				$snippet_status = "inactive";
				$snippet_logic_info .= "Snippet belongs to wp_inactive_widgets, i.e. status is inactive<br />";
				// TODO: remove from active_snippets array, if it was previously added...
	
			} else if ( $snippet_display == "show" ) {
	
				$active_snippets[] = $snippet_id; // add the item to the active_snippets array
				$snippet_status = "active";
				$snippet_logic_info .= "Snippet is set to show everywhere<br />";
				//$snippet_logic_info .= "=> snippet_id added to active_snippets array<br />";
				$snippet_logic_info .= ">> ADDED TO ARRAY<br />";
	
			} else {
	
				// Conditional display -- determine whether the given post should display this widget
				$snippet_logic_info .= "<h3>Analysing display conditions...</h3>";
	
				// Set default snippet status for show on selected vs hide on selected
				if ( $snippet_display == "selected" ) {
					$snippet_status = "inactive";
				} else if ( $snippet_display == "notselected" ) {
					$active_snippets[] = $snippet_id; // add the item to the active_snippets array
					$snippet_logic_info .= "[>> ADDED TO ARRAY] (by default/notselected)<br />";
					$snippet_status = "active";
				}
	
				// Loop through meta_keys in order from most general to most specific
				$meta_keys = array( 'target_by_location', 'target_by_post_type', 'target_by_post_type_archive', 'target_by_taxonomy_archive', 'target_by_taxonomy', 'target_by_post', 'exclude_by_post', 'target_by_url', 'exclude_by_url' );
				//$meta_keys = array( 'target_by_post', 'exclude_by_post', 'target_by_url', 'exclude_by_url', 'target_by_taxonomy', 'target_by_taxonomy_archive', 'target_by_post_type', 'target_by_post_type_archive', 'target_by_location' );
				foreach ( $meta_keys as $key ) {
	
					$$key = get_post_meta( $snippet_id, $key, true );
					//$snippet_info .= "key: $key => ".$$key."<br />";
	
					if ( !empty($$key) ) { //  && is_array($$key) && count($$key) == 1 && !empty($$key[0])
	
						$snippet_logic_info .= "key: $key =><br />";//$snippet_logic_info .= "key: $key => [".print_r($$key, true)."]<br />"; // ." [count: ".count($$key)."]"
						//$snippet_logic_info .= "[".print_r($$key, true)."]<br />";
	
						if ( ( $key == 'target_by_post_type' && $post_type != "N/A" ) || $key == 'target_by_post_type_archive') {
	
							if ( $key == 'target_by_post_type' ) {
								// This condition applies to singular posts only
								// Is the current page singular?
								if ( is_singular() ) {
									$snippet_logic_info .= "current page is_singular<br />";
								} else {
									$snippet_logic_info .= "current page NOT is_singular >> target_by_post_type does not apply<br /><br />";
									continue;
								}
							} else {
								// This condition applies to archives only
								// Is the current page some kind of archive?
								if ( is_archive() ) {
									$snippet_logic_info .= "current page is_archive<br />";
								} else {
									$snippet_logic_info .= "current page NOT is_archive >> target_by_post_type_archive does not apply<br /><br />";
									continue;
								}
								if ( is_post_type_archive() ) { $snippet_logic_info .= "current page is_post_type_archive<br />"; }
							}
	
							// Is the given post of the matching type?
							$target_post_types = get_field($key, $snippet_id, false);
							//$snippet_logic_info .= "target_post_types: <pre>".print_r($target_post_types, true)."</pre><br />";
							//
							//
							// WIP: stored values are not bare post types, but rather e.g. [Array ( [0] => is_archive-event [1] => is_singular-person [2] => is_singular-product ) ]
							// => parse accordingly
							//
							//if ( $target_type && $post_type == $target_type ) {
							if ( is_array($target_post_types) && in_array($post_type, $target_post_types) ) {
								$snippet_logic_info .= "current post_type is in target post_types array<br />";//$snippet_logic_info .= "current post_type [".$post_type."] is in target post_types array<br />";//$snippet_logic_info .= "This post matches target post_type [$target_type].<br />";
								// TODO: figure out whether to do the any/all check now, or
								// just add the id to the array and remove it later if "all" AND another condition requires exclusion?
								if ( $snippet_display == "selected" && $any_all == "any" ) { // WIP: this ignores the "exclude by post" and "exclude by url" fields
									$active_snippets[] = $snippet_id; // add the item to the active_snippets array
									$snippet_status = "active";
									//$snippet_logic_info .= "=> snippet_id added to active_snippets array<br />";
									$snippet_logic_info .= ">> ADDED TO ARRAY<br />";
									//$snippet_info .= '<div class="code '.$snippet_status.'">'.$snippet_logic_info.'</div>';
									//$snippet_logic_info .= "=> BREAK<br />";
									//break;
								} else if ( $snippet_display == "notselected" ) {
									$active_snippets = array_diff($active_snippets, array($snippet_id)); // remove the item from the active_snippets array
									$snippet_status = "inactive";
									$snippet_logic_info .= "...but because snippet_display == notselected, that means it should not be shown<br />";
									$snippet_logic_info .= ">> REMOVED FROM ARRAY<br />";
								}
							} else {
								$snippet_logic_info .= "This post does NOT match any of the array values.<br />";
								$snippet_logic_info .= "=> continue<br />";
							}
	
						} else if ( $key == 'target_by_post' || $key == 'exclude_by_post' ) {
	
							if ( is_singular() ) {
								$snippet_logic_info .= "current page is_singular<br />";
							} else {
								$snippet_logic_info .= "current page NOT is_singular >> $key does not apply<br /><br />";
								continue;
							}
	
							// Is the given post targetted or excluded?
							$target_posts = get_field($key, $snippet_id, false);
							if ( is_array($target_posts) && !empty($target_posts) && in_array($post_id, $target_posts) ) {
	
								// Post is in the target array
								$snippet_logic_info .= "This post is in the target_posts array<br />";
								// If it's for inclusion, add it to the array
								if ( $key == 'target_by_post' && $snippet_display == "selected" ) { //$any_all == "any" &&
									$active_snippets[] = $snippet_id; // add the item to the active_snippets array
									$snippet_status = "active";
									$snippet_logic_info .= ">> ADDED TO ARRAY<br />";
									//$snippet_logic_info .= "=> snippet_id added to active_snippets array (target_by_post/selected)<br />";
									//$snippet_info .= '<div class="code '.$snippet_status.'">'.$snippet_logic_info.'</div>';
									$snippet_logic_info .= "=> BREAK<br />";
									break;
								} else if ( $key == 'exclude_by_post' && $snippet_display == "notselected" ) { //$any_all == "any" &&
									$active_snippets[] = $snippet_id; // add the item to the active_snippets array
									$snippet_status = "active";
									$snippet_logic_info .= ">> ADDED TO ARRAY<br />";
									//$snippet_logic_info .= "=> snippet_id added to active_snippets array (exclude_by_post/notselected)<br />";
									//$snippet_info .= '<div class="code '.$snippet_status.'">'.$snippet_logic_info.'</div>';
									$snippet_logic_info .= "=> BREAK<br />";
									break;
								}
								// Snippet is inactive -- is in array, and either selected/excluded or notselected/targeted
								$active_snippets = array_diff($active_snippets, array($snippet_id)); // remove the item from the active_snippets array
								$snippet_logic_info .= "=> snippet inactive due to key: ".$key."/".$snippet_display."<br />";
								$snippet_logic_info .= ">> REMOVED FROM ARRAY<br />";
								//if ( $snippet_display == "selected" ) { $snippet_status = "inactive"; }
								$snippet_status = "inactive"; // ???
								break;
	
							} else {
	
								if ( empty($target_posts) ) { // redundant?
									$snippet_logic_info .= "The target_posts array is empty.<br />";
								} else {
									//???
									$snippet_logic_info .= "This post is NOT in the target_posts array.<br />";
									$snippet_logic_info .= "<!-- post_id: $post_id/target_posts: ".print_r($target_posts, true)." -->";
									if ( $snippet_display == "selected" && $any_all == "all" ) {
										$active_snippets = array_diff($active_snippets, array($snippet_id)); // remove the item from the active_snippets array
										$snippet_status = "inactive";
										$snippet_logic_info .= ">> REMOVED FROM ARRAY<br />";
									}
								}
								$snippet_logic_info .= "=> continue<br />";
							}
	
						} else if ( $key == 'target_by_url' || $key == 'exclude_by_url' ) {
	
							// Is the given post targetted or excluded?
							$target_urls = get_field($key, $snippet_id, false);
	
							// Loop through target urls looking for matches
							if ( is_array($target_urls) && !empty($target_urls) ) {
	
								//$snippet_logic_info .= "target_urls (<em>".$key."</em>): <br />";
								//$snippet_logic_info .= $key." target_urls: ".print_r($target_urls, true)."<br /><hr />";
	
								// Get current page path and/or slug
								global $wp;
								$current_url = home_url( add_query_arg( array(), $wp->request ) );
								//$snippet_logic_info .= "current_url: ".$current_url."<br />";
								$permalink = get_the_permalink($post_id);
								//$snippet_logic_info .= "permalink: ".$permalink."<br />";
								//if ( $permalink != $current_url ) { $current_url = $permalink; }
								$current_path = parse_url($current_url, PHP_URL_PATH);
								$snippet_logic_info .= "current_path: ".$current_path."<br />";
								$snippet_logic_info .= "-----<br />";
	
								foreach ( $target_urls as $k => $v ) {
	
									$url_match = false; // init
									//$snippet_logic_info .= "target_url (v): ".print_r($v, true)." (k: ".print_r($k, true).")<br />";
	
									// WIP/TODO: get field key from key name?
									//$field_key = acf_maybe_get_field( 'field_name', false, false );
									if ( $key == 'target_by_url' ) {
										$field_key = 'field_6530630a97804';
									} else {
										$field_key = 'field_65306bc897806';
									}
									if ( isset($v[$field_key]) ) {
	
										$url = $v[$field_key];
										if ( substr($url, -1) == "/" ) { $url = substr($url, 0, -1); } // Trim trailing slash, if any
	
										//$snippet_logic_info .= "target_url :: k: $k => v: ".print_r($v, true)."<br />";
										//$snippet_logic_info .= "target_url: ".$url."<br />";
										// compare url to current post path/slug
	
										if ( $url == $current_path ) {
											// URL matches current path
											$snippet_logic_info .= "target_url: ".$url." matches current_path<br />";
											$url_match = true;
										} else if ( strpos($url, '*') !== false ) {
											// Check for wildcard match
											$snippet_logic_info .= "** Wildcard url<br />";
											$snippet_logic_info .= "target_url: ".print_r($v, true)."<br />"; //$snippet_logic_info .= "target_url :: k: $k => v: ".print_r($v, true)."<br />";
	
											// Remove the asterisk to get the url_base
											// TODO: build in option for asterisk mid-url, e.g. /events/2022-07-31/?category=webcasts >> /events/*/?category=webcasts
											$url_base = trim( substr($url, 0, strpos($url, '*')) );
											// clean up the bases so that the /s don't get in the way -- TODO: do this more efficiently, maybe with a custom trim fcn?
											if ( substr($url_base, 0, 1) == "/" ) { $url_base = substr($url_base, 1); } // Trim leading slash, if any
											if ( substr($url_base, -1) == "/" ) { $url_base = substr($url_base, 0, -1); } // Trim trailing slash, if any
											$snippet_logic_info .= "url_base: $url_base<br />";
											$current_path_base = $current_path;
											if ( $current_path_base && substr($current_path_base, 0, 1) == "/" ) { $current_path_base = substr($current_path_base, 1); } // Trim leading slash, if any
											if ( $current_path_base && substr($current_path_base, -1) == "/" ) { $current_path_base = substr($current_path_base, 0, -1); } // Trim trailing slash, if any
											$snippet_logic_info .= "current_path_base: $current_path_base<br />";
											// match to $current_path? true if current_path begins with url_base
											if ( $current_path_base && substr($current_path_base, 0, strlen($url_base)) == $url_base ) {
												$url_match = true;
												$snippet_logic_info .= "current_path_base begins with wildcard url_base: $url_base >> url_match = TRUE<br />";
											}
											$snippet_logic_info .= "---<br />";
										} else {
											//$snippet_logic_info .= "target_url $url does not match current_path $current_path<br />";
											//$snippet_logic_info .= "target_url: ".print_r($v, true)."<br />";
										}
									} else {
										$snippet_logic_info .= "field_key '$field_key' not set for v: ".print_r($v,true)."<br />";
									}
									if ( $url_match == true ) {
										if ( $key == 'target_by_url' && $snippet_display == "selected" ) {
											$active_snippets[] = $snippet_id; // add the item to the active_snippets array
											$snippet_status = "active";
											$snippet_logic_info .= ">> ADDED TO ARRAY<br />";
											//$snippet_logic_info .= "=> snippet_id added to active_snippets array (target_by_url/selected)<br />";
											//$snippet_info .= '<div class="code '.$snippet_status.'">'.$snippet_logic_info.'</div>';
											$snippet_logic_info .= "=> BREAK<br />";
											$snippet_logic_info .= "---<br />";
											break;
										} else if ( $key == 'exclude_by_url' && $snippet_display == "notselected" ) {
											$active_snippets[] = $snippet_id; // add the item to the active_snippets array
											$snippet_status = "active";
											$snippet_logic_info .= ">> ADDED TO ARRAY<br />";
											//$snippet_logic_info .= "=> snippet_id added to active_snippets array (exclude_by_url/notselected)<br />";
											//$snippet_info .= '<div class="code '.$snippet_status.'">'.$snippet_logic_info.'</div>';
											$snippet_logic_info .= "=> BREAK<br />";
											$snippet_logic_info .= "---<br />";
											break;
										}
										// Snippet is inactive -- found in target urls, and either selected/excluded or notselected/targeted
										$active_snippets = array_diff($active_snippets, array($snippet_id)); // remove the item from the active_snippets array
										$snippet_status = "inactive";
										$snippet_logic_info .= "=> snippet inactive due to key: ".$key."/".$snippet_display."<br />";
										$snippet_logic_info .= ">> REMOVED FROM ARRAY<br />";
										$snippet_logic_info .= "---<br />";
										break;
									}
								} // foreach ( $target_urls as $k => $v ) {
								if ( $snippet_status == "inactive" ) { $snippet_logic_info .= "current_path not targeted<br />"; }
	
							} // if ( is_array($target_urls) && !empty($target_urls) ) {
	
						} else if ( $key == 'target_by_taxonomy' ) {
	
							if ( is_search() && !is_category() && !is_archive() ) {
								$snippet_logic_info .= "search results => $key does not apply<br /><br />";
								continue;
							}
	
							$target_taxonomies = get_field($key, $snippet_id, false);
							//$snippet_logic_info .= "target_taxonomies: <pre>".print_r($target_taxonomies, true)."</pre><br />";
							$arr_post_taxonomies = get_post_taxonomies($post_id);
							//$snippet_logic_info .= "arr_post_taxonomies: <pre>".print_r($arr_post_taxonomies, true)."</pre><br />";
							//$arr_post_terms = wp_get_post_terms( $post->ID, 'my_taxonomy', array( 'fields' => 'names' ) );
	
							// TODO: simplify this logic
							$arr_match = match_terms( $target_taxonomies, $post_id, $snippet_display );
							$term_match = $arr_match['match'];
							$snippet_logic_info .= $arr_match['info'];
							if ( $term_match ) { // ! empty( $target_taxonomies ) &&
								$snippet_logic_info .= "This post matches the target taxonomy terms [".$term_match."/".$snippet_display."]<br />";
								if ( $snippet_display == "selected" || $term_match === "exception") {
									$active_snippets[] = $snippet_id; // add the item to the active_snippets array
									$snippet_logic_info .= ">> ADDED TO ARRAY<br />";
									$snippet_status = "active";
								} else {
									$active_snippets = array_diff($active_snippets, array($snippet_id)); // remove the item from the active_snippets array
									$snippet_status = "inactive";
									$snippet_logic_info .= "...but because snippet_display == notselected, that means it should not be shown<br />";
									$snippet_logic_info .= ">> REMOVED FROM ARRAY<br />";
									if ( $any_all == "all" ) { $snippet_logic_info .= "=> BREAK<br />"; break; }
								}
								//$snippet_logic_info .= "=> BREAK<br />";
								//break;
							} else {
								$snippet_logic_info .= "This post does NOT match the target taxonomy terms<br />";
								if ( $snippet_display == "selected" && $any_all == "all" ) {
									$active_snippets = array_diff($active_snippets, array($snippet_id)); // remove the item from the active_snippets array
									$snippet_status = "inactive";
									$snippet_logic_info .= ">> REMOVED FROM ARRAY<br />";
									$snippet_logic_info .= "=> BREAK<br />";
									break;
								} else if ( $snippet_display == "notselected" ) {
									// WIP
									//$active_snippets[] = $snippet_id; // add the item to the active_snippets array
									//$snippet_status = "active";
									//$snippet_logic_info .= "...but because snippet_display == notselected, that means it should be shown<br />";
								}
								// break?
							}
	
						} else if ( $key == 'target_by_taxonomy_archive' ) {
	
							$target_taxonomies = get_field($key, $snippet_id, false);
							//$snippet_logic_info .= "target_taxonomies (archives): <pre>".print_r($target_taxonomies, true)."</pre><br />";
	
							if ( is_tax() ) {
								// If this is a taxonomy archive AND target_taxonomies are set, check for a match
								$snippet_logic_info .= "current page is_tax<br />";
								foreach ( $target_taxonomies as $taxonomy ) {
									if ( is_tax($taxonomy) ) {
										$snippet_logic_info .= "This post is_tax archive for target taxonomy: $taxonomy<br />";
										if ( $snippet_display == "selected" ) {
											$active_snippets[] = $snippet_id; // add the item to the active_snippets array
											$snippet_logic_info .= ">> ADDED TO ARRAY<br />";
											$snippet_status = "active";
											$snippet_logic_info .= "=> BREAK<br />";
											break;
										} else {
											$active_snippets = array_diff($active_snippets, array($snippet_id)); // remove the item from the active_snippets array
											$snippet_status = "inactive";
											$snippet_logic_info .= "...but because snippet_display == notselected, that means it should NOT be shown<br />";
											$snippet_logic_info .= ">> REMOVED FROM ARRAY<br />";
										}
									}
								}
							}
	
						} else if ( $key == 'target_by_location' ) {
							// Is the given post/page in the right site location?
							$target_locations = get_field($key, $snippet_id, false);
							$locations = array( 'is_home', 'is_single', 'is_page', 'is_archive', 'is_search', 'is_attachment', 'is_category', 'is_tag' ); // is_singular
							$current_locations = array();
							foreach ( $locations as $location ) {
								if ( $location() ) {
									$snippet_logic_info .= "current page/post ".$location."<br />";
									$current_locations[] = $location;
								}
							}
							//
							$snippet_logic_info .= "target_locations: ".print_r($target_locations, true)."<br />";
							$snippet_logic_info .= "current_locations: ".print_r($current_locations, true)."<br />";
							if ( count($current_locations) == 1 ) { $current_location = $current_locations[0]; } else { $current_location = "multiple"; } // wip
							//
							//if ( match_locations( $target_locations, $post_id ) ) { // TODO? make match_locations fcn?
							if ( in_array($current_location, $target_locations) ) {
								//$active_snippets[] = $snippet_id; // add the item to the active_snippets array
								//$snippet_status = "active";
								$snippet_logic_info .= "This post matches the target_locations<br />";
								if ( $snippet_display == "selected" ) {
									$active_snippets[] = $snippet_id; // add the item to the active_snippets array
									$snippet_logic_info .= ">> ADDED TO ARRAY<br />";
									$snippet_status = "active";
									//$snippet_logic_info .= "=> BREAK<br />";
									//break;
								} else {
									$active_snippets = array_diff($active_snippets, array($snippet_id)); // remove the item from the active_snippets array
									$snippet_status = "inactive";
									$snippet_logic_info .= "...but because snippet_display == notselected, that means it should NOT be shown<br />";
									$snippet_logic_info .= ">> REMOVED FROM ARRAY<br />";
								}
							} else {
								$snippet_logic_info .= "This post does NOT match the target_locations<br />";
								if ( $snippet_display == "selected" && $any_all == "all" ) {
									$active_snippets = array_diff($active_snippets, array($snippet_id)); // remove the item from the active_snippets array
									$snippet_status = "inactive";
									$snippet_logic_info .= "(selected/ALL)<br />";
									$snippet_logic_info .= ">> REMOVED FROM ARRAY<br />";
								}
							}
							//
						} else {
							$snippet_logic_info .= "unmatched key: ".$key."<br />";
						}
						$snippet_logic_info .= "<br />";
	
					} else {
						$snippet_logic_info .= "key: $key => [empty]<br /><br />";
					}
				}
	
			}
	
			// If snippet has been deemed active, but this is a search page we don't want to show the snippet on search pages, then remove it from the active array
			if ( is_search() && !is_category() && !is_archive() && $snippet_status == "active" ) {
				$snippet_logic_info .= " *** This is a search page *** <br />";
				$snippet_logic_info .= "snippet_display: ".$snippet_display." / any_all: ".$any_all;
				if ( !isset($target_locations) ) { $target_locations = array(); }
				if ( ( !in_array('is_search', $target_locations) && ( $snippet_display == "selected" ) ) || // && $any_all == "all"
					 ( in_array('is_search', $target_locations) && ( $snippet_display == "notselected" )) // && $any_all == "all"
					) {
					$active_snippets = array_diff($active_snippets, array($snippet_id)); // remove the item from the active_snippets array
					$snippet_status = "inactive";
					$snippet_logic_info .= " *** This is a search page AND is_search is NOT a targeted location (or is an excluded location) ***<br />";
					$snippet_logic_info .= ">> REMOVED FROM ARRAY<br />";
				}
			}
	
			$snippet_logic_info .= "<hr />";
			$snippet_logic_info .= "snippet_status: ".$snippet_status;
			$snippet_info .= '<div class="code '.$snippet_status.'">'.$snippet_logic_info.'</div>';
			//
			if ( $ts_info != "" && ( $do_ts === true || $do_ts == "snippets" )  ) { $snippet_info = '<div class="troubleshooting">'.$snippet_info.'</div>'; }
			$info .= $snippet_info;
		}
	
		// Make sure there are no duplicates in the active_snippets array
		$active_snippets = array_unique($active_snippets); // SORT_REGULAR
	
		//$active_snippets[] = 330389; // tft
	
		// If returning array of IDs, finish here
		if ( $return == "ids" ) { return $active_snippets; }
	
		$arr_result['info'] = $info;
		$arr_result['ids'] = $active_snippets;
	
		return $arr_result;
	
	}
	
	//
	function get_snippet_by_widget_uid ( $widget_uid = null )
	{
	
		$snippet_id = null;
		$info = "";
		$snippets = array();
	
		if ( $widget_uid ) {
			$wp_args = array(
				'post_type'   => 'snippet',
				'post_status' => 'publish',
				'meta_key'    => 'widget_uid',
				'meta_value'  => $widget_uid,
				'fields'      => 'ids'
			);
			$snippets = get_posts($wp_args);
		}
	
		if ( $snippets ) {
			//$info .= "snippets: <pre>".print_r($snippets,true)."</pre><hr />";
			// get existing post id
			if ( count($snippets) == 1 ) {
				$snippet_id = $snippets[0];
			} else if ( count($snippets) > 1 ) {
				//$info .= "More than one matching snippet!<br />";
				//$info .= "snippets: <pre>".print_r($snippets,true)."</pre><hr />";
			}
			//$info .= "snippet_id: ".$snippet_id."<br />";
		}
	
		return $snippet_id;
	
	}
	
	function get_snippet_by_post_id ( $post_id = null, $return = "id" )
	{
	
		$arr_result = array();
		$info = "";
		$snippet_id = null;
		$snippets = array();
	
		$info .= ">> get_snippet_by_post_id <<<br />";
	
		if ( $post_id ) {
			$wp_args = array(
				'post_type'   => 'snippet',
				'post_status' => 'publish',
				//'meta_key'    => 'post_id',
				//'meta_value'  => $post_id,
				'fields'      => 'ids',
				'meta_query'    => array(
					array(
						'key'        => 'post_ids',
						'compare'     => 'LIKE',
						'value'     => $post_id,//'value'     => '"'.$post_id.'"', // matches exactly "123", not just 123. This prevents a match for "1234"
					)
				),
			);
			$snippets = get_posts($wp_args);
		}
	
		//$info .= "wp_args: <pre>".print_r($wp_args,true)."</pre><hr />";
		if ( $snippets ) {
			$info .= "snippets: <pre>".print_r($snippets,true)."</pre><hr />";
			$snippet_id = $snippets[0];
			if ( count($snippets) == 1 ) {
				$snippet_id = $snippets[0];
			} else if ( count($snippets) > 1 ) {
				$info .= "More than one matching snippet!<br />";
				//$info .= "snippets: <pre>".print_r($snippets,true)."</pre><hr />";
			}
			//$info .= "snippet_id: ".$snippet_id."<br />";
		} else {
			global $wpdb;
			//$info .= "wp_query: <pre>".print_r( $wpdb->last_query, true)."</pre>";
		}
	
		// If returning id alone finish here
		if ( $return == "id" ) { return $snippet_id; }
	
		$arr_result['info'] = $info;
		$arr_result['id'] = $snippet_id;
	
		return $arr_result;
	
	}
	
	function get_snippet_by_content ( $snippet_title = null, $snippet_content = null, $return = "id" )
	{
	
		$arr_result = array();
		$info = "";
		$snippet_id = null;
		$snippets = array();
	
		$info .= ">> get_snippet_by_content <<<br />";
	
		//$query = new WP_Query( array( 's' => 'keyword' ) );
		if ( $snippet_title || $snippet_content ) {
			$wp_args = array(
				'post_type'   => 'snippet',
				'post_status' => 'publish',
				//'post_title' => $snippet_title, // Nope, this doesn't work
				//'post_content' => $snippet_content, // Nope, this neither
				's' => '"'.$snippet_title.'"',
				'search_columns' => array('post_title'),
				'fields'      => 'ids',
			);
			/*$meta_query = array(
				'relation' => 'AND',
				'snippet_display' => array(
					'key' => 'snippet_display',
					'value' => array('selected', 'notselected'),
					'compare' => 'IN',
				),
				'sidebar_id' => array(
					'key' => 'sidebar_id',
					'value' => 'cs-',
					'compare' => 'NOT LIKE',
				),
			);
			$wp_args['meta_query'] = $meta_query;*/
			$snippets = get_posts($wp_args);
		}
	
		//$info .= "wp_args: <pre>".print_r($wp_args,true)."</pre><hr />";
		if ( $snippets ) {
			//$info .= "snippets: <pre>".print_r($snippets,true)."</pre><hr />";
			foreach ( $snippets as $id ) {
				$post = get_post( $id );
				// Check to see if content also matches
				$post_content = $post->post_content;
				if ( $post_content == $snippet_content ) {
					$snippet_id = $id;
					break;
				}
			}
			// For TS
			if ( count($snippets) > 1 ) {
				$info .= "More than one matching snippet found (by post_title)!<br />";
				//$info .= "wp_args: <pre>".print_r($wp_args,true)."</pre><hr />";
				$info .= "snippets: <pre>".print_r($snippets,true)."</pre><hr />";
			}
			//$info .= "snippet_id: ".$snippet_id."<br />";
		}
	
		// If returning id alone finish here
		if ( $return == "id" ) { return $snippet_id; }
	
		$arr_result['info'] = $info;
		$arr_result['id'] = $snippet_id;
	
		return $arr_result;
	
	}
	
	/*** Copied from mods to WidgetContext ***/
	//
	function match_terms( $rules, $post_id, $snippet_display )
	{
		// Init vars
		$arr_result = array();
		$match = null; // return true, false, or exception (??? wip)
		$ts_info = "";
		$num_matches = 0;
	
		if ( $post_id == null ) { $post_id = get_the_ID(); }
		$ts_info .= "match_terms post_id: ".$post_id." for snippet_display: ".$snippet_display."<br />";
	
		if ( function_exists('sdg_log') ) {
			//sdg_log("divline2");
			//sdg_log("function called: match_terms");
			//sdg_log("post_id: ".$post_id);
		}
	
		// Determine the match_type
		if ( (strpos($rules, '||') !== false && strpos($rules, '&&') !== false )  // String includes both 'and' AND 'or' operators
			|| preg_match("/\s*\:\s*-\s*/", $rules) != 0 // has_exclusions
			//|| preg_match("/(\\()*(\\))/", $x) !== false // String contains something (enclosed within parens)
			) {
			$match_type = 'complex';
		} else if (
			strpos($rules, '&&') !== false // If string contains "and" but no or operator, and no parens
			|| preg_match("/match_type\s*\:\s*all/", $rules) != 0 // Match if string contains "match_type:all" (with or without whitespace around colon)
			|| preg_match("/\s*\:\s*-\s*/", $rules) != 0 // has_exclusions
			) {
			$match_type = 'all';
		} else {
			$match_type = 'any'; // Default: match any of the given terms
		}
		$ts_info .= "match_type: ".$match_type."<br />----<br />";
		//$ts_info .= "rules (str): ".$rules."<br />";
	
		if ( function_exists('sdg_log') ) {
			//sdg_log("rules (str): '".$rules."'");
			//sdg_log("match_type: ".$match_type); // ."; has_exclusions: ".$has_exclusions
		}
	
		// Explode the rules string into an array, items separated by line breaks
		$pairs = explode( "\n", $rules );
		//if ( function_exists('sdg_log') ) { sdg_log("pairs: ".print_r($pairs,true)); }
	
		// Build an associative array of the given rules
		//$arr_rules = array_map('process_tax_pair', $pairs); // why doesn't this work???
		$arr_rules = array();
		foreach ( $pairs as $pair ) {
			$arr_rules[] = process_tax_pair($pair);
		}
	
		if ( function_exists('sdg_log') ) {
			if ( !empty($arr_rules) ) {
				//sdg_log"arr_rules: ".print_r($arr_rules,true));
			} else {
				//sdg_log"arr_rules is empty.");
			}
		}
	
		/*
		// TODO: deal w/ possibility of combinations of terms -- allow e.g. has term AND term (+); has term OR term; has term NOT term (-)?
	
		e.g.
		match_type:complex
		(event-categories:worship-services
		|| event-categories:video-webcasts)
		&&
		sermon_topic:abraham
	
		e.g.
		(event-categories:worship-services && category:music)
		|| sermon_topic:abraham
	
		*/
	
		$num_rules = count($arr_rules);
	
		if ( $arr_rules ) {
			foreach ( $arr_rules as $rule ) {
	
				if ( empty($rule) ) { continue; }
	
				$taxonomy = $rule['taxonomy'];
				$term = $rule['term'];
				if ( isset($rule['operator']) ) { $operator = $rule['operator']; } else { $operator = null; }
				$exclusion = $rule['exclusion'];
	
				if ( $taxonomy == 'match_type' || empty($taxonomy) ) {
					//if ( function_exists('sdg_log') ) { sdg_log("match_type or empty >> continue"); }
					continue; // This is not actually a taxonomy rule; move on to the next.
				}
	
				// Check to see if taxonomy even APPLIES to the given post before worrying about whether it matches a specific term in that taxonomy
				$arr_taxonomies = get_post_taxonomies(); // get_post_taxonomies( $post_id );
				if ( !in_array( $taxonomy, $arr_taxonomies ) ) {
					$ts_info .= "taxonomy '$taxonomy' does not apply<br />";
					//$ts_info .= " => arr_taxonomies: ".print_r($arr_taxonomies,true)."<br />";
					continue;
				}
	
				//if ( function_exists('sdg_log') ) { sdg_log("term: ".$term."; taxonomy: ".$taxonomy."; operator: ".$operator."; exclusion: ".$exclusion); }
				//$ts_info .= "term: ".$term."; taxonomy: ".$taxonomy."; operator: ".$operator."; exclusion: ".$exclusion."<br />";
				$term_info = "term: ".$term."; taxonomy: ".$taxonomy;
				if ( $operator ) { $term_info .= "; operator: ".$operator; }
				if ( $exclusion ) { $term_info .= "; exclusion: ".$exclusion; }
				$term_info .= "<br />";
	
				// Handle the matching based on the number and complexity of the rules
				if ( $num_rules == 1 ) {
	
					if ( has_term( $term, $taxonomy, $post_id ) ) {
						if ( $exclusion == 'no' ) {
							$ts_info .= "Match found (single rule; has_term ($term); exclusion false) >> return true<br />";
							//if ( function_exists('sdg_log') ) { sdg_log("Match found (single rule; has_term; exclusion false) >> return true"); }
							//return $ts_info; //
							//return true; // post has term for single rule AND term is not negated, therefore it is a match
							$match = true;
							break;
						} else {
							$ts_info .= "Match found (single rule; has_term ($term); exclusion TRUE) >> return false<br />";
							//if ( function_exists('sdg_log') ) { sdg_log("Match found (single rule; has_term; exclusion TRUE) >> return false"); }
							//return false;
							$match = false;
							break;
						}
					} else if ($exclusion == 'no') {
						//$ts_info .= "NO match found (single rule; NOT has_term; exclusion false) >> return false<br />";
						//if ( function_exists('sdg_log') ) { sdg_log("NO match found (single rule; NOT has_term; exclusion false) >> return false"); }
						//return false; // post has term but single rule requires posts withOUT that term, therefore no match
						$match = false;
						break;
					}
	
				} else if ( $match_type == 'any' && has_term( $term, $taxonomy, $post_id ) && $exclusion == 'no' ) {
	
					$ts_info .= "Match found (match_type 'any'; has_term ($term); exclusion false) >> return true<br />";
					//if ( function_exists('sdg_log') ) { sdg_log("match found (match_type 'any'; has_term; exclusion false) >> return true"); }
					//return true; // Match any => match found (no need to check remaining rules, if any)
					$match = true;
					break;
	
				} else if ( $match_type == 'all' ) {
	
					if ( has_term( $term, $taxonomy, $post_id ) ) {
						if ( $exclusion == 'yes' ) {
							$ts_info .= "Match found (match_type 'all'; has_term ($term); exclusion TRUE) >> return false<br />";
							//if ( function_exists('sdg_log') ) { sdg_log("Match found (match_type 'all'; has_term; exclusion TRUE) >> return false"); }
							//return false; // post has the term but rules say it must NOT have this term
							$match = false;
							break;
						} else {
							if ( $snippet_display == "notselected" ) {
								$ts_info .= "Match found (match_type 'all'; has_term ($term); exclusion TRUE; snippet_display NOTselected)... WIP<br />";
							} else {
								$ts_info .= "Ok so far! (match_type 'all'; has_term ($term); exclusion false) >> continue<br />";
								//if ( function_exists('sdg_log') ) { sdg_log("Ok so far! (match_type 'all'; has_term; exclusion false) >> continue"); }
							}
						}
					} else if ( $exclusion == 'no' ) {
						//$ts_info .= "NO match found (match_type 'all'; NOT has_term; exclusion false) >> return false<br />";
						//if ( function_exists('sdg_log') ) { sdg_log("NO match found (match_type 'all'; NOT has_term; exclusion false) >> return false"); }
						//return false; // post does not have the term and rules require it must match all
						$match = false;
						break;
					}
	
				} else if ( $match_type == 'complex' ) {
	
					if ( has_term( $term, $taxonomy, $post_id ) ) {
						if ( $exclusion == 'yes' ) {
							if ( $snippet_display == "selected" ) {
								$ts_info .= "Match found (match_type 'complex'; has_term ($term); exclusion TRUE) >> return false<br />";
								$match = false; // post has the term but rules say it must NOT have this term
							} else if ( $snippet_display == "notselected" ) {
								$ts_info .= "Match found (match_type 'complex'; has_term ($term); exclusion TRUE; snippet_display NOTselected) >> return TRUE<br />";
								$match = "exception"; // post has the term so it excluded from being hidden
							}
							break;
						} else {
							$ts_info .= "Ok so far! (match_type 'complex'; has_term ($term); exclusion false) >> continue<br />";
							//if ( function_exists('sdg_log') ) { sdg_log("Ok so far! (match_type 'complex'; has_term; exclusion false) >> continue"); }
							$num_matches++;
						}
					} else if ( $exclusion == 'no' ) {
						//$ts_info .= "NO match found (match_type 'complex'; NOT has_term; exclusion false) >> return false (?)<br />";
						//if ( function_exists('sdg_log') ) { sdg_log("NO match found (match_type 'complex'; NOT has_term; exclusion false) >> return false"); }
						//return false; // post does not have the term and rules require it must match all
					}
	
				}
	
				if ( $match == true ) { $ts_info .= $term_info."---<br />"; }
	
			} // end foreach $arr_rules
	
			// If we got through the entire list of rules and the post matched all the rules, return true
			if ( $match_type == 'all' && $num_matches == count($arr_rules) ) {
				$ts_info .= "match_type = all; num_matches [".$num_matches."] equal to count(arr_rules) [".count($arr_rules)."]<br />";
				//if ( function_exists('sdg_log') ) { sdg_log("Matched! (match_type 'all') >> return true"); }
				//return true;
				$match = true;
			} else if ( $match !== false && $match_type == 'complex' && $num_matches > 0 ) {
				// WIP
				//if ( function_exists('sdg_log') ) { sdg_log("Matched! (match_type 'complex') with at least one positive match (and no matches to excluded categories) >> return true"); }
				//return true;
				$match = true;
			}
	
		}
	
		$arr_result['match'] = $match;
		$arr_result['info'] = $ts_info;
		return $arr_result;
	
	}
	
	//
	function process_tax_pair($rule)
	{
		// TS/logging setup
		$do_ts = devmode_active( array("sdg", "snippets") );
		$do_log = false;
		sdg_log( "divline2", $do_log );
		sdg_log( "function called: process_tax_pair", $do_log );
		sdg_log( "rule: ".$rule, $do_log );
	
		$arr = array();
	
		// If this is an empty line, i.e. doesn't actually contain a pair, then return false
		if (strpos($rule, ':') === false) {
			return $arr;
		}
	
		// Remove all whitespace
		$x = preg_replace('/\s+/', '', $rule);
	
		// Check for operator
		if (strpos($x, '||') !== false) {
			$arr['operator'] = 'OR';
			$x = str_replace('||','',$x);
		} elseif (strpos($x, '&&') !== false) {
			$arr['operator'] = 'AND';
			$x = str_replace('&&','',$x);
		}
	
		// Check for minus sign to indicate EXclusion
		if (strpos($x, ':-') !== false) {
			$arr['exclusion'] = 'yes';
		} else {
			$arr['exclusion'] = 'no';
		}
	
		$arr['taxonomy'] = trim(substr($x,0,stripos($x,":")));
		//$arr['term'] = trim(substr($x,stripos($x,":")+1));
		$term = trim(substr($x,stripos($x,":")+1));
		$term = ltrim($term,"-");
		$arr['term'] = $term;
	
		/*
		$taxonomy = trim(substr($x,0,stripos($x,":")));
		$arr['taxonomy'] = $taxonomy;
		$arr['term'] = $term;
	
		if ($arr_tterms[$taxonomy]) {
			array_push($arr_tterms[$taxonomy],$term);
		} else {
			$arr_tterms[$taxonomy] = array($term);
		}
		*/
	
		return $arr;
	
	}
	
	//
	function make_terms_array($x)
	{
		$arr = array(trim(substr($x,0,stripos($x,":"))),trim(substr($x,stripos($x,":")+1)));
		return $arr;
	}
	
	/*** END copied from WidgetContext ***/
}
