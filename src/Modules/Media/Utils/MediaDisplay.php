<?php

declare(strict_types=1);

namespace atc\WHx4\Modules\Media\Utils;

use atc\WXC\Environment;

/**
 * MediaDisplay
 *
 * Handles front-end rendering of media associated with posts:
 * featured images, audio players, video players, and webcasts.
 *
 * Entry points for the WXC Display layer:
 *   - getPostThumb() — resolves and renders a post image as HTML
 *   - getPostImage() — resolves a post image ID (no HTML output)
 *   - getMediaPlayer() — resolves and renders an audio/video player
 *
 * TODO: Extract webcast-specific methods (getWebcastUrl, getWebcastStatus,
 * getStatusMessage, getLiveWebcastId, postIsWebcastEligible) into a
 * dedicated WebcastHelper class. They are substantial enough to warrant
 * their own home and have no dependency on image/player rendering.
 *
 * TODO: The EM-specific fallback in getPostThumb() (em_get_event / parent
 * event image lookup) should be extracted to a 'wxc_post_image_fallback'
 * filter hook, with the Events module responding to it. MediaDisplay should
 * not have a hard dependency on Events Manager.
 *
 */
class MediaDisplay
{
    // =========================================================================
    // IMAGE METHODS
    // =========================================================================

    /**
     * Resolve a post image ID from multiple fallback sources.
     *
     * Does NOT produce HTML — returns an info array with keys:
     *   imgID    int|null   Resolved attachment ID, or null if nothing found.
     *   imgType  string     'post_image' or 'attachment_image'.
     *   imgClass string     Additional CSS classes to apply to the image wrapper.
     *   info     string     Debug/TS output.
     *
     * @param  int|null $postID   Post ID. Required.
     * @param  string   $format   'singular' or 'excerpt'. Controls source priority.
     * @param  string[]|string $sources  Which sources to check. Pass 'all' for every
     *                            source, or an array of: 'featured', 'gallery',
     *                            'custom_thumb', 'content'.
     * @return array{imgID:int|null,imgType:string,imgClass:string,info:string}
     */
    public static function getPostImage(
        ?int $postID = null,
        string $format = 'singular',
        array|string $sources = ['featured_image', 'gallery']
    ): array 
    {
        /*if (!$postID) {
            return ['imgID' => null, 'imgType' => 'post_image', 'imgClass' => '', 'info' => ''];
        }*/
        if ( !$postID ) { return null; }
        
        $postType = get_post_type($postID);
        $fcnId    = '[MediaDisplay::getPostImage] ';
        $imgID    = null;
        $imgType  = 'post_image'; // other option: attachment_image
        $imgClass = '';
        $tsInfo   = '';

        // Expand 'all' shorthand to a full source list
        if ($sources === 'all') {
            $sources = ['featured', 'gallery', 'custom_thumb', 'content'];
        }

        // --- Custom thumbnail (archive/grid contexts only) ---
        // Only checked when not displaying a singular post, to allow per-post
        // image overrides set via the 'custom_thumb' meta field.
        if ($format !== 'singular' && in_array('custom_thumb', $sources, true)) {
            $customThumbId = get_post_meta($postID, 'custom_thumb', true);
            if ($customThumbId) {
                $tsInfo .= $fcnId . "custom_thumb_id found: $customThumbId<br />";
                $imgID = (int) $customThumbId;
            }
        }

        // --- Sermon author image (archive contexts only) ---
        // If the sermon has 'author_image_for_archive' set, use the author's
        // photo instead of the post's featured image.
        if (!$imgID && $format !== 'singular' && $postType === 'sermon' && !is_singular('sermon')) {
            if (get_field('author_image_for_archive', $postID)) {
                $imgID = self::getAuthorImgId($postID);
                $imgClass .= ' author_img_for_archive';
            }
        }

        // --- Featured image ---
        if (!$imgID) {
            if (has_post_thumbnail($postID)) {
                $imgID  = (int) get_post_thumbnail_id($postID);
                $tsInfo .= $fcnId . "Featured image found (ID: $imgID).<br />";
            } else {
                $tsInfo .= $fcnId . "No featured image for postID $postID.<br />";

                // --- Gallery fallback ---
                if (in_array('gallery', $sources, true)) {
                    $imageGallery = get_post_meta($postID, 'image_gallery', true);
                    if (is_array($imageGallery) && count($imageGallery) > 0) {
                        // Select a random image from the gallery.
                        // TODO: consider a deterministic rotation (e.g. based on
                        // event date or day of week) for more controlled display.
                        $randomIndex = array_rand($imageGallery, 1);
                        $imgID       = (int) $imageGallery[$randomIndex];
                        $imgType     = 'attachment_image';
                        $tsInfo     .= $fcnId . "Gallery image selected (ID: $imgID).<br />";
                    }
                }

                // --- First image from post content ---
                if (!$imgID && in_array('content', $sources, true) && function_exists('get_first_image_from_post_content')) {
                    $contentImg = get_first_image_from_post_content($postID);
                    if ($contentImg && !empty($contentImg['id'])) {
                        $imgID  = (int) $contentImg['id'];
                        $tsInfo .= $fcnId . "Content image found (ID: $imgID).<br />";
                    }
                }

                // --- Attached media fallback ---
                // Note: WordPress only considers images 'attached' if they were
                // uploaded directly to the post. Images may remain 'attached'
                // in the Media Library even after removal from post content.
                if (!$imgID) {
                    $images = get_attached_media('image', $postID);
                    if ($images) {
                        // Use the last attached image (most recently uploaded)
                        $imgID  = (int) array_key_last($images);
                        $tsInfo .= $fcnId . "Attached media image found (ID: $imgID).<br />";
                    }
                }
    
                // TODO: If there's STILL no image, use a placeholder
                // TODO: make it possible to designate placeholder image(s) for archives via CMS and retrieve it using new version of get_placeholder_img fcn
                // TODO: designate placeholders *per category*?? via category/taxonomy ui?
            }
        }

        return [
            'imgID'    => $imgID,
            'imgType'  => $imgType,
            'imgClass' => $imgClass,
            'info'     => $tsInfo,
        ];
    }

    /**
     * Resolve and render a post image as HTML. (Formerly: sdg_post_thumbnail)
     *
     * This is the primary image entry point for the WXC Display layer.
     * Wired into the 'wxc_post_image' filter from MediaModule::boot().
     *
     * Returns HTML string, or empty string if no image is found or display
     * is suppressed by context guards (password-protected posts, singular
     * posts with a media player already showing above, etc.).
     *
     * @param  array $args {
     *   @type int|null  $post_id      Post ID. Defaults to get_the_ID().
     *   @type string    $format       'singular' or 'excerpt'. Default 'singular'.
     *   @type string|int[] $img_size  WP image size. Default 'thumbnail'.
     *   @type string[]  $sources      Image sources to check. Default ['featured_image','gallery'].
     *   @type bool      $echo         Echo output if true, return if false. Default true.
     *   @type string    $return_value 'html' or 'id'. Default 'html'.
     * }
     * @return string|int|null HTML string, attachment ID, or null.
     */
    public static function getPostThumb(array $args = []): string|int|null
    {
        $defaults = [
            'post_id'      => null,
            'format'       => 'singular',
            'img_size'     => 'thumbnail',
            'sources'      => ['featured_image', 'gallery'],
            'echo'         => true,
            'return_value' => 'html',
        ];

        // Parse & Extract args
        $args    = wp_parse_args($args, $defaults);
        $postId  = $args['post_id'] ?? get_the_ID();
        $format  = (string) $args['format'];
        $imgSize = $args['img_size'];
        $sources = $args['sources'];
        $echo    = (bool) $args['echo'];
        $returnValue = (string) $args['return_value'];

        $tsInfo  = '';
        $imgHtml = '';

        // For singular posts, force full-size image
        if ($format === 'singular' && !is_page('events')) {
            $imgSize = 'full';
        }

        // --- Context guards ---
        // Suppress image display in contexts where it would be redundant or inappropriate.

        // Don't show the image if a video player is already showing above the content
        $mpArgs      = ['post_id' => $postId, 'position' => 'above', 'media_type' => 'video'];
        $mpInfo      = self::getMediaPlayer($mpArgs);
        $playerStatus = $mpInfo['status'];

        if ($format === 'singular' && $playerStatus === 'ready') {
            return $echo ? null : '';
        }

        if (post_password_required($postId) || is_attachment($postId)) {
            return $echo ? null : '';
        }

        // Suppress on centered page template (theme-specific guard)
        // TODO: consider making this filterable rather than hardcoded
        if (is_page_template('page-centered.php') && $postId === get_the_ID()) {
            return $echo ? null : '';
        }

        // Suppress when featured_image_display is set to a non-inline mode
        if (is_singular() && $postId === get_the_ID()
            && in_array(get_field('featured_image_display', $postId), ['background', 'thumbnail', 'banner'], true)
        ) {
            return $echo ? null : '';
        }

        // --- Resolve image ID ---
        $img   = self::getPostImage($postId, $format, $sources);
        $imgID = $img['imgID'] ?? null;
        $tsInfo .= $img['info'];

        // --- EM parent event fallback ---
        // If no image was found, check the parent recurring event for an image.
        // TODO: Extract this to a 'wxc_post_image_fallback' filter so the Events
        // module handles its own fallback and MediaDisplay has no EM dependency.
        if (!$imgID) {
            $tsInfo .= 'No image found; checking parent event if applicable.<br />';
            $parentPostId = self::resolveEmParentPostId($postId, $tsInfo);
            if ($parentPostId) {
                $img   = self::getPostImage($parentPostId, $format, $sources);
                $tsInfo .= $img['info'];
                $imgID  = $img['imgID'] ?? null;
            }
        }
        
        // Set up classes for styling
        $imgClass = trim('post-thumbnail sdg ' . ($img['imgClass'] ?? ''));
        if (is_singular('event')) {
            $imgClass .= ' event-image';
        }
        if ($imgSize !== 'full' && (is_archive() || is_post_type_archive())) {
            $imgClass .= ' float-left';
        }

        // --- Return ID early if requested ---
        if ($returnValue === 'id') {
            $result = $imgID;
            if ($echo) {
                echo $result;
                return null;
            }
            return $result;
        }

        // --- Build HTML ---
        $imgTag      = '';
        $captionHtml = '';

        if (!$imgID) {
            // No image found — use placeholder if available
            if (function_exists('get_placeholder_img')) {
                $imgTag = get_placeholder_img();
            }
        } else {
                // Retrieve the caption, if there is one
                if ( get_post( $imgID ) ) { $caption = get_post( $imgID )->post_excerpt; } else { $caption = null; }
                if ( !empty($caption) && $format == "singular" && !is_singular('person') ) {
                    $classes .= " has-caption";
                    $ts_info .= $fcnId."Caption found for imgID $imgID: '$caption'<br />";
                } else {
                    $classes .= " no-caption";
                    $ts_info .= $fcnId."No caption found for imgID $imgID<br />";
                }
                //
                if ( $caption != "" ) {
                    $caption_class = "sdg_post_thumbnail featured_image_caption";
                    $caption_html = '<p class="'. $caption_class . '">' . $caption . '</p>';
                } else {
                    $caption_html = '<br />';
                }
    
                // Set up the img_html
                if ( $format == "singular" && !( is_page('events') ) ) {
                    $ts_info .= $fcnId."post format is_singular<br />";
                    
                    if ( has_post_thumbnail($post_id) ) {
                        if ( is_singular('person') ) {
                            $img_size = "medium"; // portrait
                            $classes .= " float-left";
                        }
                        $classes .= " is_singular";
                        $ts_info .= "get image via get_the_post_thumbnail<br />";
                        $img_tag = get_the_post_thumbnail( $post_id, $img_size );
                    } else {
                        $ts_info .= "get image via wp_get_attachment_image<br />";
                        $img_tag = wp_get_attachment_image( $imgID, $img_size, false, array( "class" => "featured_attachment" ) );
                    }
        
                    $img_html .= '<div class="'.$classes.'">';
                    $img_html .= $img_tag;
                    $img_html .= $caption_html;
                    $img_html .= '</div><!-- .post-thumbnail -->';
    
                } else if ( !( $format == "singular" && is_page('events') ) ) {
                    // NOT singular -- aka archives, search results, &c. EXCEPT events archive
                    $ts_info .= $fcnId."NOT is_singular<br />";
        
                    if ( $imgID ) {
                        // display attachment via thumbnail_id
                        $ts_info .= "get image via wp_get_attachment_image<br />";
                        $img_tag = wp_get_attachment_image( $imgID, $img_size, false, array( "class" => "featured_attachment" ) );
        
                        $ts_info .= $fcnId.'post_id: '.$post_id.'; thumbnail_id: '.$imgID;
                        if ( isset($images)) { $ts_info .= $fcnId.'<pre>'.print_r($images,true).'</pre>'; }
                    } else {
                        $ts_info .= 'Use placeholder img';
                        if ( function_exists( 'get_placeholder_img' ) ) {
                            $img_tag = get_placeholder_img();
                        }
                    }
    
                }
                if ( empty($img_html) && !empty($img_tag) ) {
                    $classes .= " float-left"; //$classes .= " NOT_is_singular";
                    $img_html .= '<a class="'.$classes.'" href="'.get_the_permalink( $post_id ).'" aria-hidden="true">';
                    $img_html .= $img_tag;
                    $img_html .= '</a>';
                }
    
            } // END if ( empty($imgID) ) {
        } // END if ( $return_value == "html" && !empty($imgID )
    
        if ( $return_value == "html" ) {
            $info .= $img_html;
        } else { // $return_value == "id"
            $info = $imgID;
        }

        if ($echo) {
            echo $imgHtml;
            return null;
        }

        return $imgHtml;
    }

    // =========================================================================
    // AUDIO / VIDEO PLAYER
    // =========================================================================

    /**
     * Resolve and render an audio or video player for a post.
     *
     * Checks ACF fields (featured_AV, media_format, video_id, audio_file, etc.)
     * and builds the appropriate player HTML based on the media type and format.
     *
     * Returns an array with keys:
     *   player   string   Player HTML (empty if none applicable).
     *   status   string   'ready' | 'unknown' | 'N/A for this position' | etc.
     *   position string   The position arg as passed.
     *   ts_info  string   Debug output.
     *
     * @param  array $args {
     *   @type int|null $post_id      Post ID. Defaults to get_the_ID().
     *   @type bool     $status_only  If true, return status string only. Default false.
     *   @type string   $position     'above' | 'below' | 'banner'. Default null.
     *   @type string   $media_type   'video' | 'audio' | 'unknown'. Default 'unknown'.
     *   @type string   $url          Override URL for webcast stream. Default null.
     * }
     * @return array{player:string,status:string,position:string,ts_info:string}
     */
    public static function getMediaPlayer(array $args = []): array
    {
        $defaults = [
            'post_id'     => null,
            'status_only' => false,
            'position'    => null,
            'media_type'  => 'unknown',
            'url'         => null,
            'called_by'      => null, // option for TS to indicate origin of function call -- e.g. theme-header
        ];

        $args      = wp_parse_args($args, $defaults);
        $postId    = $args['post_id'] ?? get_the_ID();
        $statusOnly = (bool) $args['status_only'];
        $position  = $args['position'];
        $mediaType = (string) $args['media_type'];
        $url       = $args['url'];

        $info          = '';
        $tsInfo        = '';
        $player        = '';
        $playerStatus  = 'unknown';
        $playerPosition = 'unknown';
        $src           = null;
        $featuredVideo = false;
        $featuredAudio = false;

        // --- Read ACF media fields ---
        $featuredAV   = get_field('featured_AV', $postId);   // checkbox: ['featured_video','featured_audio','webcast'] ==> TODO mod to: ['video','audio','webcast']
        $mediaFormat  = get_field('media_format', $postId);  // checkbox: ['youtube','vimeo','video','audio']

        $mediaPlayerActive = !empty($featuredAV);
        $multimedia        = is_array($featuredAV) && count($featuredAV) > 1;

        // Normalise media_format to a scalar when only one format is selected
        if (empty($mediaFormat)) {
            $mediaFormat = null;
        } elseif (is_array($mediaFormat) && count($mediaFormat) === 1) {
            $mediaFormat = $mediaFormat[0];
        }

        $tsInfo .= "featured_AV: " . print_r($featuredAV, true) . "<br />";
        $tsInfo .= "media_format: " . print_r($mediaFormat, true) . "<br />";

        // --- Determine media type and player position from featured AV ---
        if (is_array($featuredAV) && in_array('video', $featuredAV, true)) {
            $featuredVideo   = true;
            $playerPosition  = (string) get_field('video_player_position', $postId); // above/below/banner
            if ($mediaType === 'unknown' && $playerPosition === $position) {
                $mediaType = 'video';
            }
        }

        if (is_array($featuredAV) && in_array('audio', $featuredAV, true)) {
            $featuredAudio  = true;
            $playerPosition = (string) get_field('audio_player_position', $postId);
            if ($mediaType === 'unknown' && $playerPosition === $position) {
                $mediaType = 'audio';
            }
        }

        // Only proceed if the player belongs in the requested position
        if ($playerPosition !== $position) {
            return [
                'player'   => '',
                'status'   => 'N/A for this position',
                'position' => (string) $position,
                'ts_info'  => $tsInfo,
            ];
        }

        // --- Resolve source URL and refine media format ---
        if ($mediaType === 'video') {
            $videoId    = get_field('video_id', $postId);
            $ytTs       = get_field('yt_ts', $postId);       // YouTube timestamp
            $ytSeriesId = get_field('yt_series_id', $postId);
            $ytListId   = get_field('yt_list_id', $postId);

            // Prefer mobile video file when on a mobile device
            $videoFile = wp_is_mobile() ? get_field('video_file_mobile', $postId) : null;
            if (empty($videoFile)) {
                $videoFile = get_field('video_file', $postId);
            }

            $src = is_array($videoFile) ? ($videoFile['url'] ?? null) : ($videoFile ?: null);

            // Refine media_format based on what source data is available
            if ($src && is_array($mediaFormat) && in_array('video', $mediaFormat, true)) {
                $mediaFormat = 'video';
            } elseif ($videoId && is_array($mediaFormat) && in_array('vimeo', $mediaFormat, true)) {
                $mediaFormat = 'vimeo';
            } elseif (empty($src) && !empty($videoId)) {
                $mediaFormat = 'youtube';
            }

        } elseif ($mediaType === 'audio') {
            $audioFile   = get_field('audio_file', $postId);
            $src         = is_array($audioFile) ? ($audioFile['url'] ?? null) : ($audioFile ?: null);
            $mediaFormat = $src ? 'audio' : 'unknown';
        } else {
            $mediaFormat = 'unknown';
        }

        // --- Webcast URL override ---
        $webcast = get_field('webcast', $postId);
        if ($webcast) {
            $webcastStatus = self::getWebcastStatus($postId);
            $url           = self::getWebcastUrl($postId);
            $tsInfo       .= "webcast_status: $webcastStatus; webcast_url: $url<br />";
        }
    
		/*
		DEPRECATED -- FFR:
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

        // --- Build player HTML ---
        if ($mediaFormat === 'audio') {
			// Preload auto => Playback position defaults to 00:00  -- allows for clearer nav to other time points before play button has been pressed
			$atts = ['src' => $src, 'preload' => 'auto'];

			if (!empty($src)) { 
				// Audio file from Media Library
				$playerStatus = 'ready';
				if (!$statusOnly) {
					$player .= '<div class="audio_player">';
                    // Use WordPress audio shortcode (MediaElement.js) for stylable HTML5 player
                    $player .= wp_audio_shortcode($atts);
                    $player .= '</div>';
                }
            } elseif (!empty($url)) {
                // Audio file by URL
                // HLS stream fallback (m3u8 etc.)
                $playerStatus = 'ready';
                if (!$statusOnly) {
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
		} else if ( $media_format == "video" && !empty($src) ) {
			// Video file from Media Library
			$playerStatus = 'ready';
            if (!$statusOnly) {
				$player .= '<div class="hero vidfile video-container">';
				$player .= '<video poster="" id="section-home-hero-video" class="hero-video" src="'.$src.'" autoplay="autoplay" loop="loop" preload="auto" muted="true" playsinline="playsinline"></video>';
				$player .= '</div>';
			}
		} else if ( $media_format == "vimeo" && $video_id ) {
			// Vimeo iframe embed
			$playerStatus = 'ready';
			$src = 'https://player.vimeo.com/video/'.$video_id;
			if (!$statusOnly) {
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

			$ts_info .= $fcnId."src: '".$src."'<br />";

			if ( !empty($src) && $statusOnly == false ) {

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

		if ( $statusOnly === true ) {
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
				$ts_info .= $fcnId."multimedia: ".$multimedia.'/ media_format: '.$media_format.'<br />';
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
				$ts_info .= $fcnId."show_cta: FALSE<br />";
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
			
			// --- Cuepoints (HTML5 audio only) ---
            $cuepoints = get_field('cuepoints', $postId);
            if ($cuepoints) {
                $info .= self::renderCuepoints($cuepoints);
            }

            // --- CTA below player ---
            if ($position !== 'banner' && !$suppressCta && get_post_type($postId) !== 'sermon' && $postId !== 232540) {
                $info .= $cta;
            }
			/*if ( !empty($player)
				&& $player_position == $position
				&& $player_status == "ready"
				//&& $player_status != "before"
				//&& !is_dev_site()
				&& $show_cta !== false
				&& $post_id != 232540
				&& get_post_type($post_id) != 'sermon' ) {
				$info .= $cta;
			}*/
		}

        $tsInfo .= "player_status: $playerStatus<br />";

        return [
            'player'   => $info,
            'status'   => $playerStatus,
            'position' => (string) $position,
            'ts_info'  => $tsInfo,
        ];
    }

    // =========================================================================
    // WEBCAST HELPERS
    // TODO: Move these to a dedicated WebcastHelper class.
    // =========================================================================

    /**
     * Determine whether a post is eligible to display a webcast player.
     * Eligibility is based on post type and assigned taxonomy terms.
     */
    public static function postIsWebcastEligible(?int $postId = null): bool
    {
        if ($postId === null) {
            $postId = get_the_ID();
        }

        return is_singular(['event', 'post', 'page', 'sermon'])
            && (
                has_term('webcasts', 'event-categories', $postId)
                || in_category('webcasts', $postId)
                || has_term('webcasts', 'page_tag', $postId)
                || has_tag('webcasts', $postId)
                || has_term('webcasts', 'admin_tag', $postId)
            );
    }

    /**
     * Resolve the webcast stream URL for a post.
     * Returns null if a Vimeo ID or audio file is present (those are
     * handled via getMediaPlayer directly).
     */
    public static function getWebcastUrl(?int $postId = null): ?string
    {
        if ($postId === null) {
            $postId = get_the_ID();
        }

        // If a hosted video or audio file exists, no stream URL is needed
        if (!empty(get_field('video_id', $postId)) || !empty(get_field('audio_file', $postId))) {
            return null;
        }

        $status = self::getWebcastStatus($postId);

        return match ($status) {
            'on_demand' => get_field('url_ondemand', $postId) ?: null,
            'live'      => get_field('url_live', $postId) ?: null,
            default     => null,
        };
    }

    /**
     * Determine the current webcast status for a post.
     * Falls back to inferring status from available URLs when not set manually.
     *
     * @return string|false  Status string, or false if post is not webcast-eligible.
     */
    public static function getWebcastStatus(?int $postId = null): string|false
    {
        if ($postId === null) {
            $postId = get_the_ID();
        }

        if (!self::postIsWebcastEligible($postId)) {
            return false;
        }

        $status = (string) get_field('webcast_status', $postId);

        // If status was not set manually, infer it from available URLs
        if (empty($status) || $status === 'tbd') {
            if (!empty(get_field('url_ondemand', $postId))) {
                return 'on_demand';
            }
            if (!empty(get_field('url_live', $postId))) {
                return 'live';
            }
            return 'unknown';
        }

        return $status;
    }

    /**
     * Return a human-readable status message for a webcast post.
     * Used to inform visitors of upcoming, unavailable, or cancelled webcasts.
     */
    public static function getStatusMessage(?int $postId = null, string $messageType = 'webcast_status'): string
    {
        if ($postId === null) {
            $postId = get_the_ID();
        }

        $postType      = get_post_type($postId);
        $statusMessage = '';

        if ($messageType !== 'webcast_status') {
            return $statusMessage;
        }

        if (!self::postIsWebcastEligible($postId)) {
            return $statusMessage;
        }

        $webcastStatus = self::getWebcastStatus($postId);
        $videoId       = get_field('video_id', $postId);
        $mediaFormat   = get_field('media_format', $postId);

        if ($webcastStatus === 'before') {
            if (empty($videoId) || $mediaFormat === 'vimeo_recurring') {
                $liveStart = is_singular('event')
                    ? get_post_meta($postId, '_event_start_local', true)
                    : null;

                if ($liveStart) {
                    $startTs  = strtotime($liveStart);
                    $now      = current_time('timestamp');
                    $today    = date('F d, Y', $now);
                    $startDay = date('F d, Y', $startTs);
                    $startTime = date('H:i a', $startTs);

                    if ($startTs > $now) {
                        $statusMessage = 'A live webcast will be available starting ';
                        $statusMessage .= ($today === $startDay) ? 'today' : 'on ' . $startDay;
                        $statusMessage .= ' at ' . $startTime . '.';
                    }
                } elseif ($postType !== 'sermon') {
                    $statusMessage = 'This webcast is not yet available.';
                }
            }
        } elseif ($webcastStatus === 'after' && $postType !== 'sermon') {
            $statusMessage = 'An on-demand webcast will be available shortly.';
        } elseif ($webcastStatus === 'technical_difficulties') {
            $statusMessage = 'This webcast is currently unavailable due to technical difficulties. We apologize for the inconvenience.';
        } elseif ($webcastStatus === 'cancelled' && $postType !== 'sermon') {
            $statusMessage = 'This webcast has been cancelled. We apologize for any inconvenience.';
        } elseif (!in_array($webcastStatus, ['live', 'on_demand', 'unknown'], true) && $postType !== 'sermon') {
            $statusMessage = 'This webcast is currently unavailable.';
        }

        return $statusMessage;
    }

    /**
     * Return the post ID of a currently live webcast event, or null if none.
     * Checks for events tagged with webcast_status = 'live' scheduled for today.
     */
    public static function getLiveWebcastId(): ?int
    {
        $query = new \WP_Query([
            'post_type'      => 'event',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'cache_results'  => false,
            'meta_query'     => [
                'relation'    => 'AND',
                'status_clause' => [
                    'key'   => 'webcast_status',
                    'value' => 'live',
                ],
                'date_clause' => [
                    'key'   => '_event_start_date',
                    'value' => date('Y-m-d'),
                ],
                'time_clause' => [
                    'key'     => '_event_start_time',
                    'compare' => 'EXISTS',
                ],
            ],
            'orderby' => [
                'date_clause' => 'ASC',
                'time_clause' => 'DESC',
            ],
        ]);

        $posts = $query->posts;
        return !empty($posts) ? (int) $posts[0] : null;
    }
    
    // Obsolete(?)
    ///add_shortcode('display_webcast', 'display_webcast');
    function display_webcast( $post_id = null )
    {
        
        if ( $post_id == null ) { $post_id = get_the_ID(); }
        
        $info = ""; // init
        
        if ( post_is_webcast_eligible( $post_id ) ) {
            
            $mp_args = array('post_id' => $post_id ); // , 'position' => 'above' 
            $media_info = self::getMediaPlayer( $mp_args );
            //$media_info = self::getMediaPlayer( $post_id );
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
            $postType = get_post_type( $post_id );
            $post_categories = wp_get_post_categories( $post_id );
            $post_tags = get_the_tags( $post_id );
            $page_tags = get_the_terms( $post_id, 'page_tag' );
            $event_categories = get_the_terms( $post_id, 'event-categories' );
            //$terms_string = join(', ', wp_list_pluck($term_obj_list, 'name'));        
            
            $info .= "<!-- Terms for post_id $post_id of type $postType: \n";
            if ( $post_categories ) { $info .= "categories: "       . print_r($post_categories, true)."\n"; }
            if ( $event_categories ){ $info .= "event_categories: " . print_r($event_categories, true)."\n"; }
            if ( $post_tags )       { $info .= "post_tags: "        . print_r($post_tags, true)."\n"; }
            if ( $page_tags )       { $info .= "page_tags: "        . print_r($page_tags, true)."\n"; }
            $info .= " -->";
            */
                
        }
        
        return $info;
    }
    
    

    // =========================================================================
    // MEDIA LIST
    // =========================================================================

    /**
     * Render a linked list of media library attachments, optionally grouped by year.
     *
     * Used primarily for music lists. Supports grouping by year, with liturgical
     * date fallback for music lists named after holidays (Easter, Ash Wednesday, etc.)
     *
     * TODO: The liturgical date lookup is tightly coupled to a specific custom
     * post type ('liturgical_date_calc'). Consider making the CPT slug configurable.
     *
     * @param  array $atts {
     *   @type string|null $type        MIME type filter (e.g. 'pdf'). Default null.
     *   @type string|null $category    Media category taxonomy slug. Default null.
     *   @type string|null $grouped_by  'year' for year-grouped output. Default null.
     * }
     * @return string HTML output.
     */
    public static function sdg_list_media_items(array $atts = []): string
    {
        global $wpdb;

        $args = shortcode_atts([
            'type'       => null,
            'category'   => null,
            'grouped_by' => null,
        ], $atts);

        $type      = $args['type'];
        $category  = $args['category'];
        $groupedBy = $args['grouped_by'];

        $mimeTypes = [];
        if ($type === 'pdf') {
            $mimeTypes[] = 'application/pdf';
        } elseif ($type) {
            $mimeTypes[] = $type;
        }

        $wpArgs = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        if (!empty($mimeTypes)) {
            $wpArgs['post_mime_type'] = $mimeTypes;
        }

        if ($category !== null) {
            $wpArgs['tax_query'] = [[
                'taxonomy' => 'media_category',
                'field'    => 'slug',
                'terms'    => $category,
            ]];
        }
    
        $arr_posts = new WP_Query( $wp_args );
        $posts = $arr_posts->posts;

        if (empty($posts) || is_wp_error($posts)) {
            return '<p>No items found.</p>';
        }
        
        $info .= '<div class="media_list">';
        $items  = [];
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
            }
        }

        // Sort by sort_date descending for year-grouped output
        if ($groupedBy === 'year' && function_exists('sdg_arr_sort')) {
            usort($items, sdg_arr_sort('value', 'sort_date', 'DESC'));
        }

        $theYear = '';
        foreach ($items as $item) {
            if ($groupedBy === 'year' && $item['year'] !== $theYear) {
                $theYear = $item['year'];
                $info   .= '<h2>' . esc_html($theYear) . '</h2>';
            }
            $info .= '<a href="' . esc_url($item['url']) . '" target="_blank">' . esc_html($item['title']) . '</a><br />';
        }

        $info .= '</div>';
        wp_reset_postdata();

        return $info;
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Render HTML5 audio cuepoints as seek buttons.
     * Only applicable to posts using the WordPress audio shortcode player.
     *
     * @param  array $rows  ACF repeater rows for the 'cuepoints' field.
     * @return string
     */
    private static function renderCuepoints(array $rows): string
    {
        $out  = '<!-- HTML5 Player Cuepoints -->';
        $out .= '<script>';
        $out .= 'var vid = document.getElementsByClassName("wp-audio-shortcode")[0];';
        $out .= 'function setCurTime(seconds) { vid.currentTime = seconds; }';
        $out .= '</script>';
        $out .= '<div id="cuepoints" class="cuepoints scroll">';

        foreach ($rows as $row) {
            $name      = ucwords(strtolower((string) $row['name']));
            $startTime = (string) $row['start_time'];
            $endTime   = (string) $row['end_time'];
            $buttonId  = $name . '-' . str_replace(':', '', $startTime);

            // Strip leading zeros from sub-hour timestamps for readability
            $displayStart = preg_replace('/^00:/', '', $startTime);
            $displayEnd   = preg_replace('/^00:/', '', $endTime);

            $startSeconds = function_exists('xtime_to_seconds') ? xtime_to_seconds($startTime) : 0;

            $out .= '<div class="cuepoint">';
            $out .= '<span class="cue_name"><button id="' . esc_attr($buttonId) . '" onclick="setCurTime(' . (int)$startSeconds . ')" type="button" class="cue_button">' . esc_html($name) . '</button></span>';
            if ($displayStart) {
                $out .= '<span class="cue_time">' . esc_html($displayStart);
                if ($displayEnd) {
                    $out .= '-' . esc_html($displayEnd);
                }
                $out .= '</span>';
            }
            $out .= '</div>';
        }

        $out .= '</div>';
        return $out;
    }

    /**
     * Attempt to resolve the parent post ID of a recurring EM event.
     * Used as a fallback when no image is found for the current post.
     *
     * TODO: Remove this method and replace with a 'wxc_post_image_fallback'
     * filter that the Events module responds to, eliminating the hard
     * dependency on Events Manager from this class.
     *
     * @param  int    $postId  Post ID to look up.
     * @param  string &$tsInfo Debug string (passed by reference).
     * @return int|null        Parent post ID, or null if not applicable.
     */
    private static function resolveEmParentPostId(int $postId, string &$tsInfo): ?int
    {
        if (!function_exists('em_get_event')) {
            return null;
        }

        $event = em_get_event($postId, 'post_id');
        if (!$event || !$event->event_id) {
            return null;
        }

        $recurrenceSet = $event->get_recurrence_set();
        if (!$recurrenceSet) {
            return null;
        }

        $parentEvent = em_get_event($recurrenceSet->event_id, 'event_id');
        if (!$parentEvent) {
            return null;
        }

        $tsInfo .= 'Resolved EM parent post ID: ' . $parentEvent->post_id . '<br />';
        return (int) $parentEvent->post_id;
    }

    /**
     * Get the attachment ID of the author image for a sermon post.
     * Placeholder — implement based on your actual author image field setup.
     *
     * TODO: Implement this properly based on the author image ACF field.
     */
    private static function getAuthorImgId(int $postId): ?int
    {
        // TODO: implement — e.g. return get_field('author_image', $postId)['ID'] ?? null;
        return null;
    }
    
    
    // =========================================================================
    // =========================================================================
     
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
}