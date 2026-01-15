<?php
namespace atc\WHx4\Modules\Media\Utils;

class MediaDisplay
{
    private static $instance = null;
    
    public static function getInstance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {}
    
    /************** A/V FUNCTIONS ***************/
    
    // Get Media Player -- Based on contents of ACF A/V Info fields
	//function get_media_player ( $post_id = null, $status_only = false, $position = null, $media_type = 'unknown', $url = null, $called_by = null ) {
	//get_media_player
	public static function getMediaPlayer( array $args = [] ): array
	{
		// TS/logging setup
		$do_ts = devmode_active( array("sdg", "media") );
		$do_log = false;
		$fcn_id = "[sdg-gmp] ";
		sdg_log( "divline2", $do_log );
	
		// Defaults
		$defaults = array(
			'post_id'        => null,
			'status_only'    => false,
			'position'        => null,
			'media_type'    => 'unknown',
			'url'            => null,
			'called_by'      => null, // option for TS to indicate origin of function call -- e.g. theme-header
			'do_ts'          => false,
		);
	
		// Init vars
		$arr_info = array(); // return info and status, or status only, depending on options selected
		$info = "";
		$ts_info = "";
		//
		$media_player_active = false;
		$player = "";
		$player_status = "unknown";
		$player_position = "unknown";
		$src = null;
		$featured_video = false;
		$featured_audio = false;
		$multimedia = false; // does the post have both featured audio and video?
	
		// Parse & Extract args
		$args = wp_parse_args( $args, $defaults );
		extract( $args );
		$ts_info .= $fcn_id."get_media_player parsed/extracted args: <pre>".print_r($args, true)."</pre>";
	
		if ( $post_id == null ) { $post_id = get_the_ID(); }
		//$ts_info .= $fcn_id."atts on init ==> post_id: '".$post_id."'; position: '".$position."'; media_type: '".$media_type."'; status_only: '[".$status_only."]'<br />";
		// If it's not a webcast-eligible post, then abort
		//if ( !post_is_webcast_eligible( $post_id ) ) { return false;  }
	
		// Get the basic media info
		$featured_AV = get_field('featured_AV', $post_id); // array of options (checkboxes field) including: featured_video, featured_audio, webcast (WIP)
		$media_format = get_field('media_format', $post_id); // array of options (checkboxes) including: youtube, vimeo, video, audio -- // formerly: $webcast_format = get_field('webcast_format', $post_id);
		if ( !empty($featured_AV) ) { $media_player_active = true; }
		//
		$ts_info .= $fcn_id."featured_AV: ".print_r($featured_AV, true)."<br />";
		$ts_info .= $fcn_id."media_format: ".print_r($media_format, true)."<br />";
	
		if ( is_array($featured_AV) && count($featured_AV) > 1 ) {
			$multimedia = true;
			$ts_info .= $fcn_id."MULTIPLE FEATURED A/V MEDIA FOUND<br />";
		} else {
			$ts_info .= $fcn_id."Multimedia FALSE<br />";
		}
	
		if ( empty($media_format) ) { $media_format = null; } else if ( is_array($media_format) && count($media_format) == 1 ) { $media_format = $media_format[0]; }
	
		// Get additional vars based on presence of featured audio and/or video
		if ( is_array($featured_AV) && in_array( 'video', $featured_AV) ) {
			$featured_video = true;
			$player_position = get_field('video_player_position', $post_id); // above/below/banner
			$ts_info .= $fcn_id."player_position: '".$player_position."' / position: '".$position."'<br />";
			if ( $media_type == "unknown" && $player_position == $position ) {
				$media_type = 'video';
				$ts_info .= $fcn_id."media_type REVISED: '".$media_type."'<br />";
			}
		}
		if ( is_array($featured_AV) && in_array( 'audio', $featured_AV) ) {
			$featured_audio = true;
			$player_position = get_field('audio_player_position', $post_id); // above/below/banner
			$ts_info .= $fcn_id."player_position: '".$player_position."'<br />";
			if ( $media_type == "unknown" && $player_position == $position ) {
				$media_type = 'audio';
				$ts_info .= $fcn_id."media_type REVISED: '".$media_type."'<br />";
			}
		}
	
		// Make sure we're looking to show a player in this position; if not, cut it short
		if ( $player_position == $position ) {
	
			if ( $media_type == "video" ) {
	
				//$ts_info .= $fcn_id."media_type REVISED: '".$media_type."'<br />";
				$video_file = null;
				$video_id = get_field('video_id', $post_id);
				$yt_ts = get_field('yt_ts', $post_id); // YT timestamp
				$yt_series_id = get_field('yt_series_id', $post_id);
				$yt_list_id = get_field('yt_list_id', $post_id);
	
				// Mobile or desktop? If mobile, check to see if a smaller version is available -- WIP
				if ( wp_is_mobile() ) {
					$video_file = get_field('video_file_mobile'); //$video_file = get_field('featured_video_mobile');
				}
				if (empty($video_file) ) {
					$video_file = get_field('video_file'); //$video_file = get_field('featured_video');
				}
				if ( is_array($video_file) ) {
					$src = $video_file['url'];
				} else if ( !empty($video_file) ) {
					$src = $video_file;
				}
	
				$ts_info .= $fcn_id."video_id: '".$video_id."'; video_file src: '".$src."'<br />";
	
				if ( $src && is_array($media_format) && in_array( 'video', $media_format) ) {
					$media_format = "video";
				} else if ( $video_id && is_array($media_format) && in_array( 'vimeo', $media_format) ) {
					$media_format = "vimeo";
				} else if ( empty($src) && !empty($video_id) ){
					$media_format = "youtube";
				}
	
			} else if ( $media_type == "audio" ) {
	
				$audio_file = get_field('audio_file', $post_id);
				$ts_info .= $fcn_id."audio_file: '".$audio_file."<br />";
				if ( $audio_file ) { $media_format = "audio"; } else { $media_format = "unknown"; }
				if ( is_array($audio_file) ) { $src = $audio_file['url']; } else { $src = $audio_file; }
	
			} else {
	
				$media_format = "unknown";
	
			}
	
			$ts_info .= $fcn_id."media_format REVISED: '".print_r($media_format,true)."'<br />";
			//if ( $media_format != 'unknown' ) { $player_status = "ready"; }
	
			// Webcast?
			$webcast = get_field('webcast', $post_id);
			//if ( is_array($featured_AV) && in_array( 'webcast', $featured_AV) ) {
			if ( $webcast ) {
				$webcast_status = get_webcast_status( $post_id );
				//if ( $webcast_status == "live" || $webcast_status == "on_demand" ) { }
				$url = get_webcast_url( $post_id ); //if ( empty($video_id)) { $src = get_webcast_url( $post_id ); }
				$ts_info .= $fcn_id."webcast_status: '".$webcast_status."'; webcast_url: '".$url."'<br />";
			}
	
			/*
			DEPRECATED:
			---
			Webcast Format Options:
			---
			vimeo : Vimeo Video/One-time Event
			vimeo_recurring : Vimeo Recurring Event
			youtube: YouTube
			youtube_list : YouTube Playlist
			video : Video (formerly: Flowplayer -- future use tbd)
			video_as_audio : Video as Audio
			video_as_audio_live : Video as Audio - Livestream
			audio : Audio Only
			---
			*/
	
			if ( $media_format == "audio" ) {
	
				$type = wp_check_filetype( $src, wp_get_mime_types() ); // tft
				$ext = $type['ext'];
				$ts_info .= "audio_file ext: ".$ext."<br />"; // tft
				$atts = array('src' => $src, 'preload' => 'auto' ); // Playback position defaults to 00:00 via preload -- allows for clearer nav to other time points before play button has been pressed
				$ts_info .= $fcn_id."audio_player atts: ".print_r($atts,true)."<br />";
	
				if ( !empty($src) && !empty($ext) && !empty($atts) ) { // && !empty($url)
	
					// Audio file from Media Library
	
					$player_status = "ready";
	
					if ( $status_only == false ) {
						// Audio Player: HTML5 'generic' player via WP audio shortcode (summons mejs -- https://www.mediaelementjs.com/ -- stylable player)
						// NB default browser player has VERY limited styling options, which is why we're using the shortcode
						$player .= '<div class="audio_player">'; // media_player
						$player .= wp_audio_shortcode( $atts );
						$player .= '</div>';
					}
	
				} else if ( !empty($url) ) {
	
					// Audio file by URL
	
					$player_status = "ready";
	
					if ( $status_only == false ) {
	
						// For m3u8 files, use generic HTML5 player for now, even though the styling is lousy. Can't get it to work yet via WP shortcode.
						$player .= '<div class="audio_player video_as_audio">';
						$player .= '<audio id="'.$player_id.'" class="masked" style="height: 3.5rem; width: 100%;" controls="controls" width="300" height="150">';
						$player .= 'Your browser does not support the audio element.';
						$player .= '</audio>';
						$player .= '</div>';
	
						// Create array of necessary attributes for HLS JS
						$atts = array('src' => $src, 'player_id' => $player_id ); // other options: $masked
						// Load HLS JS
						$player .= "Load HLS JS<br />";
						$player .= load_hls_js( $atts );
					}
	
				}
	
			} else if ( $media_format == "video" && !empty($src) ) { //} else if ( $media_format == "video" && isset($video_file['url']) ) {
	
				// Video file from Media Library
	
				$player_status = "ready";
	
				if ( $status_only == false ) {
					$player .= '<div class="hero vidfile video-container">';
					$player .= '<video poster="" id="section-home-hero-video" class="hero-video" src="'.$src.'" autoplay="autoplay" loop="loop" preload="auto" muted="true" playsinline="playsinline"></video>';
					$player .= '</div>';
				}
	
			} else if ( $media_format == "vimeo" && $video_id ) {
	
				// Vimeo iframe embed
	
				$player_status = "ready";
	
				$src = 'https://player.vimeo.com/video/'.$video_id;
	
				if ( $status_only == false ) {
					$class = "vimeo_container";
					if ( $player_position == "banner" ) { $class .= " hero vimeo video-container"; }
					$player .= '<div class="'.$class.'">';
					if ( $player_position == "banner" ) {
						$player .= '<video poster="" id="section-home-hero-video" class="hero-video" src="'.$src.'" autoplay="autoplay" loop="loop" preload="auto" muted="true" playsinline="playsinline" controls></video>';
					} else {
						$player .= '<iframe id="vimeo" src="'.$src.'" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen style="position:absolute; top:0; left:0; width:100%; height:100%;"></iframe>';
					}
					$player .= '</div>';
				}
	
			} else if ( $media_format == "youtube" ) {
				//&& ( !has_post_thumbnail( $post_id ) || ( $webcast_status == "live" || $webcast_status == "on_demand" ) )
	
				// WIP -- deal w/ webcasts w/ status other than live/on_demand
	
				// Get SRC
				if ( !empty($yt_series_id) && !empty($yt_list_id) ) { // && $media_format == "youtube_list"
					$src = 'https://www.youtube.com/embed/videoseries?si='.$yt_series_id.'?enablejsapi=1&list='.$yt_list_id.'&autoplay=0&loop=1&mute=0&controls=1';
					//https://www.youtube.com/embed/videoseries?si=gYNXkhOf6D2fbK_y&amp;list=PLXqJV8BgiyOQBPR5CWMs0KNCi3UyUl0BH
				} else if ( !empty($video_id) ) {
					$src = 'https://www.youtube.com/embed/'.$video_id.'?enablejsapi=1&playlist='.$video_id.'&autoplay=0&loop=1&mute=0&controls=1';
					//$src = 'https://www.youtube.com/embed/'.$youtube_id.'?&playlist='.$youtube_id.'&autoplay=1&loop=1&mute=1&controls=0'; // old theme header version -- note controls
					//$src = 'https://www.youtube.com/watch?v='.$video_id;
				} else {
					$src = null;
				}
	
				$ts_info .= $fcn_id."src: '".$src."'<br />";
	
				//if ( $src ) { $player_status = "ready"; }
	
				if ( !empty($src) && $status_only == false ) {
	
					$player_status = "ready";
	
					// Timestamp?
					if ( $yt_ts ) { $src .= "&start=".$yt_ts; }
	
					// Assemble media player iframe
					$player .= '<div class="hero video-container youtube-responsive-container">';
					$player .= '<iframe width="100%" height="100%" src="'.$src.'" title="YouTube video player" enablejsapi="true" frameborder="0" allowfullscreen></iframe>'; // controls=0 // allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
					$player .= '</div>';
				}
	
			}
	
			//if ( $webcast && $webcast_status == "before" && $player_status == "unknown" ) { $player_status = "before"; }
	
			if ( $status_only === true ) {
				return $player_status;
			}
	
			// CTA?
			$show_cta = get_post_meta( $post_id, 'show_cta', true );
			$cta = "";
	
			// If show_cta is true and the Media player is active for this post, regardless of player or webcast status, show the CTA
			if ( $media_player_active && $show_cta ) {
	
				// TODO: get this content from some post type manageable via the front end, by slug or id (e.g. 'cta-for-webcasts')
				// post type for CTAs could be e.g. "Notifications", or post in "CTAs" post category, or...
				// -- or by special category of content associated w/ CPTs?
				$status_message = get_status_message ( $post_id, 'webcast_status' );
				if ( $show_cta == "1" ) { $show_cta = true; } else { $show_cta = false; }
				// WIP -- don't show the CTA twice...
				if ( $multimedia && $media_format == "audio" ) {
					$show_cta = false;
				} else {
					$ts_info .= $fcn_id."multimedia: ".$multimedia.'/ media_format: '.$media_format.'<br />';
				}
				if ( $show_cta ) {
					$ts_info .= 'show_cta: TRUE<br />';
					$cta .= '<div class="cta">';
					$cta .= '<h2>Support Saint Thomas Church</h2>';
					//$cta .= '<h2>Support Our Ministry</h2>';
					////$cta .= '<a href="https://www.saintthomaschurch.org/product/one-time-donation/" target="_blank" class="button">Make a donation for the work of the Episcopal Church in the Holy Land on Good Friday</a>';
					//$cta .= '<a href="https://www.saintthomaschurch.org/product/annual-appeal-pledge/" target="_blank" class="button">Pledge to our Annual Appeal</a>&nbsp;';
					//$cta .= '<a href="https://www.saintthomaschurch.org/product/one-time-donation/" target="_blank" class="button">Make a Donation</a>';
					//$cta .= '<a href="https://www.saintthomaschurch.org/product/make-a-payment-on-your-annual-appeal-pledge/" target="_blank" class="button">Make an Annual Appeal Pledge Payment</a>';
					$cta .= '<a href="https://www.saintthomaschurch.org/give/" target="_blank" class="button">Support Saint Thomas</a>&nbsp;';
					$cta .= '<br />';
					$cta .= '<h3>You can also text "give" to <a href="sms://+18559382085">(855) 938-2085</a></h3>';
					//$cta .= '<h3><a href="sms://+18559382085?body=give">You can also text "give" to (855) 938-2085</a></h3>';
					$cta .= '</div>';
				} else {
					$ts_info .= $fcn_id."show_cta: FALSE<br />";
				}
	
				//
				if ( $status_message !== "" && $position != "banner" ) {
					$info .= '<p class="message-info">'.$status_message.'</p>';
					if ( $show_cta !== false
						&& get_post_type($post_id) != 'sermon' // Don't show CTA for sermons
						//&& !is_dev_site() // Don't show CTA on dev site. It's annoying clutter.
						) {
						$info .= $cta;
					}
					//return $info; // tmp disabled because it made "before" Vimeo vids not show up
				}
			}
	
			// If there's media to display, show the player
			if ( $player_status == "ready" ) {
	
				if ( $player != "" && is_singular('sermon') && has_term( 'webcasts', 'admin_tag', $post_id ) && get_post_meta( $post_id, 'audio_file', true ) != "" && $position != "banner" ) {
					$player = '<h3 id="sermon-audio" name="sermon-audio"><a>Sermon Audio</a></h3>'.$player;
				}
	
				if ( !empty($player) ) {
					if ( $player_position == $position ) {
						$info .= "<!-- MEDIA_PLAYER -->";
						$info .= $player;
						$info .= "<!-- /MEDIA_PLAYER -->";
					} else {
						$ts_info .= "NB: player_position != $position ==> don't show the player, even though there is one.<br />";
					}
	
				} else {
					$info .= "<!-- NO MEDIA_PLAYER AVAILABLE -->";
				}
	
				// Assemble Cuepoints (for non-Vimeo webcasts only -- HTML5 Audio-only
				$rows = get_field('cuepoints', $post_id); // ACF function: https://www.advancedcustomfields.com/resources/get_field/ -- TODO: change to use have_rows() instead?
				/*if ( have_rows('cuepoints', $post_id) ) { // ACF function: https://www.advancedcustomfields.com/resources/have_rows/
					while ( have_rows('cuepoints', $post_id) ) : the_row();
						$XXX = get_sub_field('XXX'); // ACF function: https://www.advancedcustomfields.com/resources/get_sub_field/
					endwhile;
				} // end if
				*/
	
				// Loop through rows and assemble cuepoints
				if ($rows) {
	
					// Cuepoints
	
					$info .= '<!-- HTML5 Player Cuepoints -->'; // tft
					// TODO: move this to sdg.js?
					$info .= '<script>';
					$info .= '  var vid = document.getElementsByClassName("wp-audio-shortcode")[0];';
					$info .= '  function setCurTime( seconds ) {';
					$info .= '    vid.currentTime = seconds;';
					$info .= '  }';
					$info .= '</script>';
	
					// Cuepoints
					$seek_buttons = ""; // init
					$button_actions = ""; // init
	
					$info .= '<div id="cuepoints" class="cuepoints scroll">';
	
					foreach( $rows as $row ) {
	
						//print_r($row); // tft
						$name = ucwords(strtolower($row['name'])); // Deal w/ the fact that many cuepoint labels were entered in UPPERCASE... :-[
						$start_time = $row['start_time'];
						$end_time = $row['end_time'];
						$button_id = $name.'-'.str_replace(':','',$start_time);
	
						// If the start_time is < 1hr, don't show the initial pair of zeros
						if ( substr( $start_time, 0, 3 ) === "00:" ) { $start_time = substr( $start_time, 3 ); }
						// Likewise, if the end_time is < 1hr, don't show the initial pair of zeros
						if ( substr( $end_time, 0, 3 ) === "00:" ) { $end_time = substr( $end_time, 3 ); }
	
						// Convert cuepoints to number of seconds for use in player
						$start_time_seconds = xtime_to_seconds($row['start_time']);
						$end_time_seconds = xtime_to_seconds($row['end_time']);
	
						$seek_buttons .= '<div class="cuepoint">';
						$seek_buttons .= '<span class="cue_name"><button id="'.$button_id.'" onclick="setCurTime('.$start_time_seconds.')" type="button" class="cue_button">'.$name.'</button></span>';
						if ( $start_time ) {
							$seek_buttons .= '<span class="cue_time">'.$start_time;
							if ( $end_time ) { $seek_buttons .= '-'.$end_time; }
							$seek_buttons .= '</span>';
						}
						$seek_buttons .= '</div>';
	
					}
	
					$info .= $seek_buttons;
					$info .= '</div>';
	
				} // END if ($rows) for Cuepoints
	
				// Add call to action beneath media player
				if ( !empty($player)
					&& $player_position == $position
					&& $player_status == "ready"
					//&& $player_status != "before"
					//&& !is_dev_site()
					&& $show_cta !== false
					&& $post_id != 232540
					&& get_post_type($post_id) != 'sermon' ) {
					$info .= $cta;
				}
	
			}
	
		} else { $player_status = "N/A for this position"; }
	
		$ts_info .= $fcn_id."player_status: ".$player_status."<br />";
		if ( $ts_info ) { $ts_info .= "+~+~+~+~+~+~+~+<br />"; }
		//if ( $ts_info != "" && ( $do_ts === true || $do_ts == "" ) ) { $info .= '<div class="troubleshooting">'.$ts_info.'</div>'; }
		$arr_info['player'] = $info;
	
		//if ( $ts_info != "" && ( $do_ts === true || $do_ts == "media" || $do_ts == "events" ) ) { $arr_info['ts_info'] = $ts_info; } else { $arr_info['ts_info'] = null; }
		$arr_info['ts_info'] = $ts_info;
		$arr_info['position'] = $position;
		$arr_info['status'] = $player_status;
	
		return $arr_info;
	
	}
	
	// Get a linked list of Media Items -- Currently used only (?) for music lists: /wp-admin/post.php?post=121048&action=edit >> TODO: Fold this in to a more general display function
	//add_shortcode('list_media_items', 'sdg_list_media_items');
	public static function sdg_list_media_items ( array $atts = [] ) 
	{
	    global $wpdb;
	
		$info = "";
		$mime_types = array();
	
		$args = shortcode_atts( array(
			'type'        => null,
			'category'    => null,
			'grouped_by'  => null,
		), $atts );
	
		// Extract
		extract( $args );
	
		if ($type == "pdf") {
			$mime_types[] = "application/pdf";
		} else {
			$mime_types[] = $type;
		}
	
		//$unsupported_mimes  = array( 'image/jpeg', 'image/gif', 'image/png', 'image/bmp', 'image/tiff', 'image/x-icon' );
		//$all_mimes          = get_allowed_mime_types();
		//$mime_types       = array_diff( $all_mimes, $unsupported_mimes );
	
		$wp_args = array(
			'post_type' => 'attachment',
			'post_status' => 'inherit',
			'posts_per_page' => -1,
			'orderby' => 'date',
			'order' => 'DESC',
		);
	
		if ( !empty($mime_types) ) {
			$wp_args['post_mime_type'] = $mime_types;
		}
		//'post_mime_type' => 'image/gif',
	
		if ( $category !== null ) {
			$wp_args['tax_query'] = array(
				array(
					'taxonomy'     => 'media_category',
					'field'     => 'slug',
					'terms'     => $category
				)
			);
		}
	
		$arr_posts = new WP_Query( $wp_args );
		$posts = $arr_posts->posts;
		//$info .= print_r($arr_posts, true);
		//$info .= "<!-- Last SQL-Query: ".$wpdb->last_query." -->";
	
		if ( !empty( $posts ) && !is_wp_error( $posts ) ){
	
			$info .= '<div class="media_list">';
			// init
			$items  = array();
			$months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
			$litdates = array('ash_wednesday_date' => 'Ash Wednesday', 'easter_date' => 'Easter', 'pentecost_date' => 'Pentecost');
			$the_year = "";
	
			// Loop through the posts; built items array
			foreach ( $posts as $post ) {
	
				setup_postdata( $post );
	
				$title = $post->post_title;
				$post_id = $post->ID;
				$url = wp_get_attachment_url($post_id); // get_attachment_link($post_id);
	
				// Don't display the words "Music List", if present in the title
				if (strpos(strtolower($title), 'music list') !== false) {
					$title = str_ireplace("Music List", "", $title);
				}
	
				if ( $grouped_by == "year" ) {
	
					// init
					$start_month = "";
					$end_month = "";
	
					// Extract year from filename
					$pattern = '/((19|20)\d{2})/';
					if ( preg_match($pattern, $title, $matches, PREG_OFFSET_CAPTURE) ) {
						$year = trim($matches[0][0]);
					} else {
						$year = null;
					}
	
					// For Music Lists, don't display the year in the title
					if ( $category == "music-lists" ) {
						$title = str_ireplace($year, "", $title);
					}
	
					// Get liturgical date calc info per year, in order to deal w/ lists named according to holidays (Easter, Ash Wednesday, Pentecost) instead of months
					// e.g. January-Easter 2019; Easter-September 2015
					if ( $year != $the_year ) {
	
						$the_year = $year;
	
						$wp_args = array(
							'post_type'   => 'liturgical_date_calc',
							'post_status' => 'publish',
							'posts_per_page' => 1,
							'meta_query' => array(
								array(
									'key'     => 'litdate_year',
									'value'   => $year.'-01-01'
								)
							)
						);
						$liturgical_date_calc_post_id = null; // init
						$liturgical_date_calc_post_obj = new WP_Query( $wp_args );
						if ( $liturgical_date_calc_post_obj ) {
							$liturgical_date_calc_post = $liturgical_date_calc_post_obj->posts;
							$liturgical_date_calc_post_id = $liturgical_date_calc_post[0]->ID;
							$info .= "<!-- Found liturgical_date_calc_post for year $year with ID: ".$liturgical_date_calc_post[0]->ID." -->";
							//$info .= "<!-- Found liturgical_date_calc_post for year $year: ".print_r($liturgical_date_calc_post, true)." >> ID: ".$liturgical_date_calc_post[0]->ID." -->"; // tft
						} else {
							$info .= "<!-- NO liturgical_date_calc_post found for year $year -->";
						} // tft
	
					}
	
					foreach ( $months AS $i => $month ) {
	
						$num = (string)$i+1;
						//$num = (string)$num;
						$numlength = strlen($num);
						if ($numlength == 1) {
							$num = "0".$num;
						}
	
						if (stripos($title, $month."-") !== false) {
							$start_month = $num;
							$info .= "<!-- Found start_month: $start_month from title: $title -->"; // tft
						} else if (stripos($title, $month) !== false && stripos($title, "-") === false) {
							$start_month = $num;
							$info .= "<!-- Found start_month: $start_month from title: $title (no hyphens) -->"; // tft
						}
	
						if (stripos($title, "-".$month) !== false) {
							$end_month = $num;
							$info .= "<!-- Found end_month: $end_month from title: $title -->"; // tft
						}
					}
	
					// If no start_month was found, look for: Easter, Ash Wednesday, Pentecost
					if ( $start_month == "" && $liturgical_date_calc_post_id ) {
						$info .= "<!-- [post_id: $post_id] No start_month found >> try via litdates -->"; // tft
						foreach ( $litdates AS $date_field => $litdate ) {
							if (stripos($title, $litdate."-") !== false) {
								$info .= "<!-- Found litdate match (start): $litdate in title: $title -->"; // tft
								$start_date = get_post_meta( $liturgical_date_calc_post_id, $date_field, true);
								$info .= "<!-- Found start_date via litdate: $start_date (date_field: $date_field) -->"; // tft
								$start_month = date('m', strtotime($start_date) );
							} else if (stripos($title, $litdate."-") !== false) {
								$info .= "<!-- Found litdate match (end): $litdate in title: $title -->"; // tft
								$end_date = get_post_meta( $liturgical_date_calc_post_id, $date_field, true);
								$end_month = date('m', strtotime($start_date) );
							}
						}
					}
	
					$sort_date = $year.$start_month;
					$items[] = array('id' => $post_id, 'title' => $title, 'url' => $url, 'year' => $year, 'sort_date' => $sort_date, 'start_month' => $start_month, 'end_month' => $end_month);
	
				} else {
					$items[] = array('id' => $post_id, 'title' => $title, 'url' => $url);
				}
	
				$info .= "<!-- +-----+ -->";
	
			}
	
			if ( $grouped_by == "year" ) {
				usort($items, sdg_arr_sort('value', 'sort_date', 'DESC'));
			}
	
			$the_year = ""; // reset
			foreach ( $items as $item ) {
	
				if ( $item['year'] != $the_year ) {
					$the_year = $item['year'];
					$info .= '<h2>'.$the_year.'</h2>';
				}
				if ( $grouped_by == "year" ) {
					$info .= "<!-- ".$item['sort_date']." -->";
				}
				$info .= '<a href="'.$item['url'].'" target="_new">'.$item['title'].'</a>';
				$info .= '<br />';
	
			}
	
			$info .= '</div>'; // close media_list div
	
		} else {
			$info .= "<p>No items found.</p>";
			$info .= "Last SQL-Query: ".$wpdb->last_query."";
			//$info .= "<!-- Last SQL-Query: ".$wpdb->last_query." -->";
		}
		return $info;
	}
	
	/************** IMAGE FUNCTIONS ***************/
	
	// Custom fcn for thumbnail/featured image display
	// WIP refactoring
	//public function getPostThumb( $post = null, $size = 'thumbnail', $args = [] ) {
	public static function getPostThumb ( array $args = [] ) 
	{
		error_log( "MediaDisplay::whx4_post_thumbnail" );
		//error_log('[MediaDisplay] args: ' . print_r($args, true));
		
		// Init vars
		$info = "";
		$ts_info = "";
	
		// Defaults
		$defaults = array(
			'post_id'    => null,
			'format'    => "singular", // default to singular; other option is excerpt
			'img_size'    => "thumbnail",
			'sources'    => array("featured_image", "gallery"),
			'echo'        => true,
			'return_value' => 'html',
			//'do_ts'      => false,
		);
	
		// Parse & Extract args
		$args = wp_parse_args( $args, $defaults );
		extract( $args );
		//error_log("[MediaDisplay] whx4_post_thumbnail parsed/extracted args: <pre>".print_r($args, true)."</pre>";
	
		if ( $post_id == null ) { $post_id = get_the_ID(); }
		$post_type = get_post_type( $post_id );
		$img_id = null;
		if ( $return_value == "html" ) {
			$img_html = "";
			$caption_html = "";
		}
	
		$img_type = "post_image"; // other option: attachment_image
	
		$image_gallery = array();
		if ( $sources == "all" ) {
			$sources = array("featured", "gallery", "custom_thumb", "content");
		}
	
		if ( $format == "singular" && !is_page('events') ) {
			$img_size = "full";
		}
	
		/*
		//error_log("[MediaDisplay]";
		$ts_info .= $fcn_id."vars:<br/>";
		$ts_info .= "&emsp; post_id: $post_id<br />";
		$ts_info .= "&emsp; format: $format<br />";
		$ts_info .= "&emsp; get_the_ID(): ".get_the_ID()."<br />";
		$ts_info .= "&emsp; img_size: ".print_r($img_size, true)."<br />";
		$ts_info .= "&emsp; sources: ".print_r($sources, true)."<br />";
		$ts_info .= "&emsp; return_value: $return_value<br />";
		*/
	
		// Make sure this is a proper context for display of the featured image
	
		$mp_args = array('post_id' => $post_id, 'status_only' => true, 'position' => 'above', 'media_type' => 'video' );
		$player_status = get_media_player( $mp_args );
	
		$mp_args = array('post_id' => $post_id, 'position' => 'above', 'media_type' => 'video' );
		$mp_info = get_media_player( $mp_args );
		$player_status = $mp_info['status'];
		//error_log("[MediaDisplay] mp ts info: ".$mp_info['ts_info'];
	
		if ( $format == "singular" && $player_status == "ready" ) {
			return;
		} else {
			error_log("[MediaDisplay] player_status: ".print_r($player_status,true));
		}
		if ( post_password_required($post_id) || is_attachment($post_id) ) {
			return;
		} else if ( has_term( 'video-webcasts', 'event-categories' ) && is_singular('event') ) {
			// featured images for events are handled via Events > Settings > Formatting AND via events.php (#_EVENTIMAGE)
			//return;
		} else if ( has_term( 'video-webcasts', 'category' ) ) {
			//
		} else if ( is_page_template('page-centered.php') && $post_id == get_the_ID() ) {
			return;
		} else if ( is_singular() && $post_id == get_the_ID() && in_array( get_field('featured_image_display'), array( "background", "thumbnail", "banner" ) ) ) {
			return; // wip
		}
	
		error_log("[MediaDisplay] Ok to display the image, if one has been found.");
	
		// Ok to display the image! Set up classes for styling
		$classes = "post-thumbnail sdg";
		//$classes .= " zoom-fade"; //if ( is_dev_site() ) { $classes .= " zoom-fade"; }
		if ( is_singular('event') ) { $classes .= " event-image"; }
		if ( $img_size != "full" && ( is_archive() || is_post_type_archive() ) ) { $classes .= " float-left"; }
		//
	
		// Are we using the custom image, if any is set?
		// Do this only for archive and grid display, not for singular posts of any kind (? people ?)
		if ( $format != "singular" && in_array("custom_thumb", $sources ) ) {
			error_log("[MediaDisplay] Check for custom_thumb");
			// First, check to see if the post has a Custom Thumbnail
			$custom_thumb_id = get_post_meta( $post_id, 'custom_thumb', true );
			if ( $custom_thumb_id ) {
				error_log("[MediaDisplay] custom_thumb_id found: $custom_thumb_id");
				$img_id = $custom_thumb_id;
			}
		}
	
		// WIP: order?
		// If this is a sermon, are we using the author image
		if ( $format != "singular" && $post_type == "sermon" && !is_singular('sermon') ) {
			if ( get_field('author_image_for_archive') ) {
				$img_id = get_author_img_id ( $post_id );
				$classes .= " author_img_for_archive";
			} else {
				error_log("[MediaDisplay] author_image_for_archive set to false");
			}
		}
	
		// If we're not using the custom thumb, or if none was found, then proceed to look for other image options for the post
		if ( !$img_id ) {
	
			// Check to see if the given post has a featured image
			if ( has_post_thumbnail( $post_id ) ) {
	
				$img_id = get_post_thumbnail_id( $post_id );
				error_log("[MediaDisplay] post has a featured image.");
	
			} else {
	
				error_log("[MediaDisplay] post has NO featured image.");
	
				// If there's no featured image, see if there are any other images that we can use instead
	
				// Image Gallery?
				if ( in_array("gallery", $sources ) ) {
					// get image gallery images and select one at random
					$image_gallery = get_post_meta( $post_id, 'image_gallery', true );
					if ( is_array($image_gallery) && count($image_gallery) > 0 ) {
						error_log("[MediaDisplay] Found an image_gallery array.");
						error_log("[MediaDisplay] image_gallery: <pre>".print_r($image_gallery, true)."</pre>");
						$i = array_rand($image_gallery,1); // Get one random image ID -- tmp solution
						// WIP: figure out how to have a more controlled rotation -- based on event date? day? cookie?
						$img_id = $image_gallery[$i];
						$img_type = $fcn_id."attachment_image";
						error_log("[MediaDisplay] Random thumbnail ID: $img_id");
					} else {
						error_log("[MediaDisplay] No image_gallery found.");
					}
				}
	
				// Image(s) in post content?
				if ( empty($img_id) && in_array("content", $sources ) && function_exists('get_first_image_from_post_content') ) {
					$image_info = get_first_image_from_post_content( $post_id );
					if ( $image_info ) {
						$img_id = $image_info['id'];
					}
				}
	
				// Image attachment(s)?
				if ( empty($img_id) ) {
	
					// The following approach would be a good default except that images only seem to count as 'attached' if they were directly UPLOADED to the post
					// Also, images uploaded to a post remain "attached" according to the Media Library even after they're deleted from the post.
					$images = get_attached_media( 'image', $post_id );
					//$images = get_children( "post_parent=".$post_id."&post_type=attachment&post_mime_type=image&numberposts=1" );
					if ($images) {
						//$img_id = $images[0];
						foreach ($images as $attachment_id => $attachment) {
							$img_id = $attachment_id;
						}
					}
	
				}
	
				// If there's STILL no image, use a placeholder
				// TODO: make it possible to designate placeholder image(s) for archives via CMS and retrieve it using new version of get_placeholder_img fcn
				// TODO: designate placeholders *per category*?? via category/taxonomy ui?
				if ( empty($img_id) ) {
					//if ( function_exists( 'is_dev_site' ) && is_dev_site() ) { $img_id = 121560; } else { $img_id = 121560; } // Fifth Avenue Entrance
					$img_id = null;
				}
			}
		}
	
		if ( $return_value == "html" && !empty($img_id ) ) {
	
			// For html return format, add caption, if there is one
	
			// Retrieve the caption
			if ( get_post( $img_id ) ) { $caption = get_post( $img_id )->post_excerpt; } else { $caption = null; }
			if ( !empty($caption) && $format == "singular" && !is_singular('person') ) {
				$classes .= " has-caption";
				error_log("[MediaDisplay] Caption found for img_id $img_id: '$caption'");
			} else {
				$classes .= " no-caption";
				error_log("[MediaDisplay] No caption found for img_id $img_id");
			}
	
			if ( $caption != "" ) {
				$caption_class = "whx4_post_thumbnail featured_image_caption";
				$caption_html = '<p class="'. $caption_class . '">' . $caption . '</p>';
			} else {
				$caption_html = '<br />';
			}
	
			// Set up the img_html
			if ( $format == "singular" && !( is_page('events') ) ) {
	
				error_log("[MediaDisplay] post format is_singular");
				if ( has_post_thumbnail($post_id) ) {
	
					if ( is_singular('person') ) {
						$img_size = "medium"; // portrait
						$classes .= " float-left";
					}
	
					$classes .= " is_singular";
	
					$img_html .= '<div class="'.$classes.'">';
					$img_html .= get_the_post_thumbnail( $post_id, $img_size );
					$img_html .= $caption_html;
					$img_html .= '</div><!-- .post-thumbnail -->';
	
				} else {
	
					// If an image_gallery was found, show one image as the featured image
					// TODO: streamline this
					if ( $img_id && is_array($image_gallery) && count($image_gallery) > 0 ) {
						error_log("[MediaDisplay] image_gallery image");
						$img_html .= '<div class="'.$classes.'">';
						$img_html .= wp_get_attachment_image( $img_id, $img_size, false, array( "class" => "featured_attachment" ) );
						$img_html .= $caption_html;
						$img_html .= '</div><!-- .post-thumbnail -->';
					}
	
				}
	
			} else if ( !( $format == "singular" && is_page('events') ) ) {
	
				error_log("[MediaDisplay] NOT is_singular");
	
				// NOT singular -- aka archives, search results, &c.
				$img_tag = "";
	
				if ( $img_id ) {
	
					// display attachment via thumbnail_id
					$img_tag = wp_get_attachment_image( $img_id, $img_size, false, array( "class" => "featured_attachment" ) );
	
					error_log("[MediaDisplay] post_id: ".$post_id.'; thumbnail_id: '.$img_id);
					if ( isset($images)) { error_log("[MediaDisplay] <pre>".print_r($images,true).'</pre>'); }
	
				} else {
	
					error_log("[MediaDisplay] Use placeholder img");
	
					if ( function_exists( 'get_placeholder_img' ) ) {
						$img_tag = get_placeholder_img();
					}
				}
	
				if ( !empty($img_tag) ) {
					$classes .= " float-left"; //$classes .= " NOT_is_singular";
					$img_html .= '<a class="'.$classes.'" href="'.get_the_permalink( $post_id ).'" aria-hidden="true">';
					$img_html .= $img_tag;
					$img_html .= '</a>';
				}
	
			} // END if is_singular()
		} // END if ( $return_value == "html" && !empty($img_id )
	
		if ( $return_value == "html" ) {
			$info .= $img_html;
		} else { // $return_value == "id"
			$info = $img_id;
		}
	
		// Echo or return info
		if ( $echo == true ) { echo $info; } else { return $info; }
	}
	
	public static function sdg_get_placeholder_img() 
	{
	
		$info = "";
	
		$placeholder = get_page_by_title('woocommerce-placeholder', OBJECT, 'attachment');
		if ( $placeholder ) {
			$placeholder_id = $placeholder->ID;
			if ( wp_attachment_is_image($placeholder_id) ) {
				//$info .= "Placeholder image found with id '$placeholder_id'."; // tft
				$img_atts = wp_get_attachment_image_src($placeholder_id, 'medium');
				$img = '<img src="'.$img_atts[0].'" class="bordered" />';
			} else {
				//$info. "Attachment with id '$placeholder_id' is not an image."; // tft
			}
		} else {
			//$info .= "woocommerce-placeholder not found"; // tft
		}
	
		$info .= $img;
	
		return $info;
	}
	
	/**
	 * Show captions for featured images
	 *
	 * @param string $html          Post thumbnail HTML.
	 * @param int    $post_id       Post ID.
	 * @param int    $post_image_id Post image ID.
	 * @return string Filtered post image HTML.
	 */
	//add_filter( 'post_thumbnail_html', 'sdg_post_image_html', 10, 3 );
	function sdg_post_image_html( $html, $post_id, $post_image_id ) 
	{
	
		if ( is_singular() && !in_array( get_field('featured_image_display'), array( "background", "thumbnail", "banner" ) ) ) {
	
			$html .= '<!-- fcn sdg_post_image_html -->';
	
			$featured_image_id = get_post_thumbnail_id();
			if ( $featured_image_id ) {
				$caption = get_post( $featured_image_id )->post_excerpt;
				if ( $caption != "" ) {
					$caption_class = "sdg_post_image featured_image_caption";
					$html = $html . '<p class="'. $caption_class . '">' . $caption . '</p>'; // <!-- This displays the caption below the featured image -->
				} else {
					$html = $html . '<br />';
				}
			}
	
			$html .= '<!-- /fcn sdg_post_image_html -->';
	
		}
	
		return $html;
	}
	
	// TODO: combine this next function with the previous one to remove redundancy
	/**
	 * Show captions for attachment images
	 *
	 * @param string $html          Image HTML.
	 * @param int    $attachment_id Image ID.
	 * @return string Filtered post image HTML.
	 */
	//apply_filters( 'wp_get_attachment_image', string $html, int $attachment_id, string|int[] $size, bool $icon, string[] $attr )
	/*add_filter( 'wp_get_attachment_image', 'sdg_attachment_image_html', 10, 3 );
	function sdg_attachment_image_html( $html, $attachment_id, $post_image_id ) {
	
		// TODO: fix this for other post types. How to tell if attachment was called from content-excerpt.php template?
		if ( is_singular('event') && !in_array( get_field('featured_image_display'), array( "background", "thumbnail", "banner" ) ) ) {
	
			$html .= '<!-- fcn sdg_attachment_image_html -->';
	
			if ( $attachment_id ) {
				$caption = get_post( $attachment_id )->post_excerpt;
				if ( $caption != "" ) {
					$caption_class = "featured_image_caption";
					$html = $html . '<p class="'. $caption_class . '">' . $caption . '</p>'; // <!-- This displays the caption below the featured image -->
				} else {
					$html = $html . '<br />';
				}
			}
	
			$html .= '<!-- /fcn sdg_attachment_image_html -->';
	
		}
	
		return $html;
	}*/
	
	// Function to display featured caption in EM event template
	///add_shortcode( 'featured_image_caption', 'sdg_featured_image_caption' );
	function sdg_featured_image_caption ( $post_id = null, $attachment_id = null ) 
	{
	
		global $post;
		global $wp_query;
		$info = "";
		$caption = "";
	
		if ( $attachment_id ) {
	
		} else {
			if ( $post_id == null ) { $post_id = get_the_ID(); }
		}
	
		// Retrieve the caption (if any) and return it for display
		if ( get_post_thumbnail_id() ) {
			$caption = get_post( get_post_thumbnail_id() )->post_excerpt;
		}
	
		if ( $caption != "" && !in_array( get_field('featured_image_display'), array( "background", "thumbnail", "banner" ) ) ) {
			$caption_class = "sdg_featured_image_caption";
			$info .= '<p class="'. $caption_class . '">';
			$info .= $caption;
			$info .= '</p>';
		} else {
			$info .= '<p class="zeromargin">&nbsp;</p>'; //$info .= '<br class="empty_caption" />';
		}
	
		return $info;
	
	}
	
	/*********** WEBCASTS ***********/
	
	function post_is_webcast_eligible( $post_id = null )
	{
		
		if ($post_id == null) { $post_id = get_the_ID(); }
		
		if ( is_singular( array( 'event', 'post', 'page', 'sermon' ) ) 
			  && ( has_term( 'webcasts', 'event-categories', $post_id ) 
				  || in_category( 'webcasts', $post_id ) 
				  || has_term( 'webcasts', 'page_tag', $post_id ) 
				  || has_tag( 'webcasts', $post_id) 
				  || has_term( 'webcasts', 'admin_tag', $post_id ) )
		   ) {
			return true;
		}
		
		// Post does not have Webcast Info enabled
		return false;
		
	}
	
	// Obsolete(?)
	///add_shortcode('display_webcast', 'display_webcast');
	function display_webcast( $post_id = null )
	{
		
		if ( $post_id == null ) { $post_id = get_the_ID(); }
		
		$info = ""; // init
		
		if ( post_is_webcast_eligible( $post_id ) ) {
			
			$mp_args = array('post_id' => $post_id ); // , 'position' => 'above' 
			$media_info = get_media_player( $mp_args );
			//$media_info = get_media_player( $post_id );
			$player_status = $media_info['status'];
			
			$info .= "<!-- Webcast Audio/Video Player for post_id: $post_id -->";
			$info .= $media_info['player'];
			$info .= "<!-- player_status: $player_status -->";
			$info .= '<!-- /Webcast Audio/Video Player -->'; 
			
		} else {
			
			return null;
			
			//$info .= "<!-- NOT post_is_webcast_eligible. -->";
			//$info .= '<br style="clear:both" />';
			// For troubleshooting only
			
			/*
			$post_type = get_post_type( $post_id );
			$post_categories = wp_get_post_categories( $post_id );
			$post_tags = get_the_tags( $post_id );
			$page_tags = get_the_terms( $post_id, 'page_tag' );
			$event_categories = get_the_terms( $post_id, 'event-categories' );
			//$terms_string = join(', ', wp_list_pluck($term_obj_list, 'name'));        
			
			$info .= "<!-- Terms for post_id $post_id of type $post_type: \n";
			if ( $post_categories ) { $info .= "categories: "       . print_r($post_categories, true)."\n"; }
			if ( $event_categories ){ $info .= "event_categories: " . print_r($event_categories, true)."\n"; }
			if ( $post_tags )       { $info .= "post_tags: "        . print_r($post_tags, true)."\n"; }
			if ( $page_tags )       { $info .= "page_tags: "        . print_r($page_tags, true)."\n"; }
			$info .= " -->";
			*/
				
		}
		
		return $info;
	}
	
	function get_webcast_url( $post_id = null, $cuepoint = null )
	{
		
		// init vars
		if ($post_id === null) { $post_id = get_the_ID(); }
		$info = "";
		$post_type = null;
		
		$video_id = get_field('video_id', $post_id);
		//$vimeo_id = get_field('vimeo_id', $post_id);
		$audio_file = get_field('audio_file', $post_id);
		// Don't even try to return a URL if there's a Vimeo ID or Audio File on record
		if ( !empty($video_id) || !empty($audio_file) ) { return null; } //  || !empty($vimeo_id)
		
		// TODO: implement jump to cuepoint via query parameter
	
		$webcast_status = get_webcast_status( $post_id );
		
		//$info .= "webcast_status: $webcast_status"; // tft
		if ( $webcast_status == 'on_demand' ) {
			$src = get_field('url_ondemand', $post_id);
		} else if ( $webcast_status == 'live' ) {
			$src = get_field('url_live', $post_id);
		} else {
			$src = null;
			//$src = "webcast_status: $webcast_status"; // ftf
		}
		
		return $src;
	}
	
	function get_webcast_status( $post_id = null )
	{
		
		// init vars
		if ($post_id === null) { $post_id = get_the_ID(); }
		$info = "";
		$post_type = null;
		$now = current_time( 'mysql' ); // Get current date/time via WP function to ensure time zone matches site.
		
		if ( !post_is_webcast_eligible( $post_id ) ) { return false; }
		
		// ACF function: https://www.advancedcustomfields.com/resources/get_field/
		$webcast_status = get_field('webcast_status', $post_id);
		
		// If webcast_status wasn't set/selected manually, deduce it from the available webcast info
		if ( empty($webcast_status) || $webcast_status == "tbd" ) {
		
			$url_live = get_field('url_live', $post_id);
			$url_ondemand = get_field('url_ondemand', $post_id);
			
			// before, live, after, on_demand, technical_difficulties
			if ( !empty( $url_ondemand ) ) { 
				$webcast_status = 'on_demand';
			} elseif ( !empty( $url_live ) ) { 
				$webcast_status = 'live';
			} else {
				$webcast_status = 'unknown';
			}
			
		}
		
		return $webcast_status;
	}
	
	function get_status_message ( $post_id = null, $message_type = 'webcast_status' )
	{
		
		if ( $post_id == null ) { $post_id = get_the_ID(); }
		$post_type = get_post_type( $post_id );
		$status_message = "";
		//$status_message = "post_id: '".$post_id."'; message_type: '".$message_type."'"; // tft
		
		if ( $message_type == 'webcast_status' ) {
			
			if ( !post_is_webcast_eligible( $post_id ) ) {
				return $status_message; // return false;
			}
			
			$webcast_status = get_webcast_status( $post_id );
			$media_format = get_field('media_format', $post_id);
			$video_id = get_field('video_id', $post_id);
			//$vimeo_id = get_field('vimeo_id', $post_id);
			
			//$technical_difficulties = get_field('technical_difficulties', $post_id);
			//if ( $technical_difficulties == 'true' ) { $webcast_status = "technical_difficulties"; }
			//$status_message .= "technical_difficulties: '".$technical_difficulties."'"; // tft
			
			if ( $webcast_status === "before" ) {
				if ( empty( $video_id ) || $media_format == "vimeo_recurring" ) {
					// If live_start is set, display message saying that the webcast will be available on that date/time
					$live_start = null;
					//$live_start = get_field('live_start', $post_id); // deprecated -- leaving this here in case it becomes useful again -- for streaming on non-event posts
					if ( $live_start == null && is_singular('event') ) { $live_start = get_post_meta( $post_id, '_event_start_local', true ); }
					if ( $live_start != "" ) {
	
						$start_timestamp = strtotime($live_start);
						$now = current_time( 'timestamp' );
						$today = date('F d, Y', $now);
						$start_day = date('F d, Y', $start_timestamp);
						$start_time = date('H:i a', $start_timestamp);
	
						if ( $start_timestamp > $now ) {
							$status_message .= "A live webcast will be available starting ";
							if ( $today == $start_day ) {
								$status_message .= "today ";
							} else {
								$status_message .= "on ".$start_day;
							}
							$status_message .= " at ".$start_time.".";
						} else {
							//$status_message .= "<!-- live but past... -->";
						}
	
					} else if ( $post_type != 'sermon' ) {
						$status_message = "This webcast is not yet available.";
					}
				}
			} else if ( $webcast_status === "after" && $post_type != 'sermon' ) {
				$status_message .= "An on-demand webcast will be available shortly.";
			} else if ( $webcast_status === "live" ) {
				//$status_message .= "";
			}  else if ( $webcast_status === "on_demand" ) {
				//$status_message .= "";
			} else if ( $webcast_status === "technical_difficulties" ) {
				$status_message .= "This webcast is currently unavailable due to technical difficulties. We apologize for the inconvenience.";
			} else if ( $webcast_status === "cancelled" && $post_type != 'sermon' ) {
				$status_message .= "This webcast has been cancelled. We apologize for any inconvenience.";
			} else if ( $post_type != 'sermon' ) {
				// NB: there's no special message for $webcast_status === "live". This means that if the status is live but the live URL no longer works, this generic message will display.
				$status_message = "This webcast is currently unavailable."; 
			}
		}
		
		//$status_message .= "<!-- post_id: '".$post_id."'; webcast_status: '".$webcast_status."' -->"; // tft
		
		return $status_message;
	}
	
	// Get ID of post which is currently livestreaming, if any
	function get_live_webcast_id ()
	{
	
		$post_id = null;
	
		$wp_args = array(
			'post_type'   => 'event',
			'post_status' => 'publish',
			'posts_per_page' => 1,
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'     => 'webcast_status',
					'value'   => 'live'
				),
				'date_clause' => array(
					'key'     => '_event_start_date',
					'value'   => date('Y-m-d'), // today! (in case AG forgot to update the status after a live stream was over...)
				),
				'time_clause' => array(
					'key' => '_event_start_time',
					'compare' => 'EXISTS',
				),
			),
			'orderby'	=> array(
				'date_clause' => 'ASC',
				'time_clause' => 'DESC',
			),
			'fields' => 'ids',
			'cache_results' => false,
		);
		
		$query = new WP_Query( $wp_args );
		$posts = $query->posts;
		
		if ( count($posts) > 0 ) {
			$post_id = $posts[0];        
		}
		
		return $post_id;
	
	}
}