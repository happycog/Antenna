<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Antenna Plugin
 * Copyright Matt Weinberg, www.VectorMediaGroup.com
 */

// Leaving this for EE 2 compatibility
$plugin_info = array(
	'pi_name'			=> 'Antenna',
	'pi_version'		=> '2.2',
	'pi_author'			=> 'Matt Weinberg',
	'pi_author_url'		=> 'http://www.VectorMediaGroup.com',
	'pi_description'	=> 'Returns the embed code and various pieces of metadata for YouTube, Vimeo, Wistia, and Viddler Videos',
	'pi_usage'			=> 'https://github.com/vector/antenna'
);

/**
 * The Antenna plugin will generate the YouTube or Vimeo embed
 * code for a single YouTube or Vimeo clip. It will also give
 * you back the Author, their URL, the video title,
 * and a thumbnail.
 *
 * @package Antenna
 */

class Antenna
{
	public $return_data = '';
	public $cache_name = 'antenna_urls';
	public $cache_path = '';
	public $refresh_cache = 10080;			// in mintues (default is 1 week)
	public $cache_expired = FALSE;

	public function __construct()
	{
		$this->_set_cache_path();

		$tagdata = ee()->TMPL->tagdata;

		// Check to see if it's a one-off tag or a pair
		$mode = ($tagdata) ? "pair" : "single";

		$plugin_vars = array(
			"title"         =>  "video_title",
			"html"          =>  "embed_code",
			"author_name"   =>  "video_author",
			"author_url"    =>  "video_author_url",
			"thumbnail_url" =>  "video_thumbnail",
			"medres_url"	=>  "video_mediumres",
			"highres_url"	=>  "video_highres",
			"description"   =>  "video_description",
			"provider"      =>  "video_provider",
			"width" 		=>	"video_width",
			"height" 		=>	"video_height",
			"video_id"		=>	"video_provider_id"
		);

		$video_data = array();

		foreach ($plugin_vars as $var) {
			$video_data[$var] = false;
		}

		// Deal with the parameters
		$video_url = (ee()->TMPL->fetch_param('url')) ? html_entity_decode(ee()->TMPL->fetch_param('url')) : false;
		$max_width = (ee()->TMPL->fetch_param('max_width')) ? "&maxwidth=" . ee()->TMPL->fetch_param('max_width') : '';
		$max_height = (ee()->TMPL->fetch_param('max_height')) ? "&maxheight=" . ee()->TMPL->fetch_param('max_height') : '';
		$wmode = ee()->TMPL->fetch_param('wmode', '');
		$wmode_param = ! empty($wmode) ? "&wmode=" . $wmode : '';

		// Check for embed.ly support
		$embedly_key = ee()->TMPL->fetch_param('embedly_key', false);
		if ( ! $embedly_key && ee()->config->item('antenna_embedly_key')) {
			$embedly_key = ee()->config->item('antenna_embedly_key');
		}

		// Cache can be disabled by setting 0 as the cache_minutes param
		if (ee()->TMPL->fetch_param('cache_minutes') !== FALSE && is_numeric(ee()->TMPL->fetch_param('cache_minutes'))) {
			$this->refresh_cache = ee()->TMPL->fetch_param('cache_minutes');
		}

		// Some optional YouTube parameters
		$youtube_options = array(
			'autohide',
			'autoplay',
			'cc_load_policy',
			'color',
			'controls',
			'disablekb',
			'enablejsapi',
			'end',
			'fs',
			'hl',
			'iv_load_policy',
			'list',
			'listType',
			'loop',
			'modestbranding',
			'origin',
			'playlist',
			'playsinline',
			'rel',
			'showinfo',
			'start',
			'theme'
		);
		$youtube_params = array();

		foreach($youtube_options as $option)
		{
			$param = ee()->TMPL->fetch_param('youtube_'.$option, null);
			if(!is_null($param))
			{
				$youtube_params[$option] = $param;
			}
		}

		// Some optional Vimeo parameters
		$vimeo_byline	= (ee()->TMPL->fetch_param('vimeo_byline') == "false") ? "&byline=false" : '';
		$vimeo_title	= (ee()->TMPL->fetch_param('vimeo_title') == "false") ? "&title=false" : '';
		$vimeo_autoplay	= (ee()->TMPL->fetch_param('vimeo_autoplay') == "true") ? "&autoplay=true" : '';
		$vimeo_portrait	= (ee()->TMPL->fetch_param('vimeo_portrait') == "false") ? "&portrait=0" : '';
		$vimeo_api	= (ee()->TMPL->fetch_param('vimeo_api') == "true") ? "&api=1" : '';
		$vimeo_loop	= (ee()->TMPL->fetch_param('vimeo_loop') == "true") ? "&loop=true" : '';
		$vimeo_color 	= (ee()->TMPL->fetch_param('vimeo_color') !== false) ? "&color=".str_replace('#', '', ee()->TMPL->fetch_param('vimeo_color')) : '';

		// Some optional Viddler parameters
		$viddler_type = (ee()->TMPL->fetch_param('viddler_type')) ? "&type=" . ee()->TMPL->fetch_param('viddler_type') : '';
		$viddler_ratio = (ee()->TMPL->fetch_param('viddler_ratio')) ? "&ratio=" . ee()->TMPL->fetch_param('viddler_ratio') : '';

		// Automatically handle scheme if https
		$is_https = false;
		if (ee()->TMPL->fetch_param('force_https') == "true" || parse_url($video_url, PHP_URL_SCHEME) == 'https') {
			$is_https = true;
		}

		// If it's not YouTube, Vimeo, Wistia, or Viddler bail
		if (strpos($video_url, "youtube.com/") !== FALSE OR strpos($video_url, "youtu.be/") !== FALSE) {
			// Correct for a bug in YouTube response if only maxheight is set and the video is over 612px wide
			if (empty($max_height)) $max_height = "&maxheight=" . $max_width;
			
			$url = 'http://www.youtube.com/oembed?format=xml&iframe=1' . ($is_https ? '&scheme=https' : '') . '&url=';
		} elseif (strpos($video_url, "vimeo.com/") !== FALSE) {
			$url = 'http' . ($is_https ? 's' : '') . '://vimeo.com/api/oembed.xml?url=';
		} elseif (strpos($video_url, "wistia.com/") !== FALSE) {
			$url = 'http://app.wistia.com/embed/oembed.xml?url=';
		} elseif (strpos($video_url, "viddler.com/") !== FALSE) {
			$url = 'http://www.viddler.com/oembed/?format=xml&url=';
		} elseif ($embedly_key) {
			$url = 'https://api.embedly.com/1/oembed?format=xml&key=' . urlencode($embedly_key) . '&url=';
		} else {
			$tagdata = ee()->functions->var_swap($tagdata, $video_data);
			$this->return_data = $tagdata;
			return;
		}

		$url .= urlencode($video_url) . $max_width . $max_height . $wmode_param . $vimeo_byline . $vimeo_title . $vimeo_autoplay . $vimeo_portrait . $vimeo_api . $vimeo_color . $viddler_type . $viddler_ratio . $vimeo_loop;

		// checking if url has been cached
		$cached_url = $this->_check_cache($url);

		if (! $this->refresh_cache OR $this->cache_expired OR ! $cached_url)
		{
			//Create the info and header variables
			list($video_info, $video_header) = $this->curl($url);

			if (!$video_info || $video_header != "200")
			{
				$tagdata = ee()->functions->var_swap($tagdata, $video_data);
				$this->return_data = $tagdata;
				return;
			}

			// write the data to cache if caching hasn't been disabled
			if ($this->refresh_cache) {
				$this->_write_cache($video_info, $url);
			}
		}
		else
		{
			$video_info = $cached_url;
		}

		// Decode the cURL data
		$video_info = simplexml_load_string($video_info);

    	// Inject wmode transparent if required
    	if ($wmode === 'transparent' || $wmode === 'opaque' || $wmode === 'window' ) {
	    	$param_str = '<param name="wmode" value="' . $wmode .'"></param>';
	      	$embed_str = ' wmode="' . $wmode .'" ';

	      	// Determine whether we are dealing with iframe or embed and handle accordingly
	      	if (strpos($video_info->html, "<iframe") === false) {
		        $param_pos = strpos( $video_info->html, "<embed" );
		        $video_info->html = substr($video_info->html, 0, $param_pos) . $param_str . substr($video_info->html, $param_pos);
	        	$param_pos = strpos( $video_info->html, "<embed" ) + 6;
	    	    $video_info->html =  substr($video_info->html, 0, $param_pos) . $embed_str . substr($video_info->html, $param_pos);
	    	}
	    	else
	    	{
	    		// Determine whether to add question mark to query string
	    		preg_match('/<iframe.*?src="(.*?)".*?<\/iframe>/i', $video_info->html, $matches);
	    		$append_query_marker = (strpos($matches[1], '?') !== false ? '' : '?');

	    		$video_info->html = preg_replace('/<iframe(.*?)src="(.*?)"(.*?)<\/iframe>/i', '<iframe$1src="$2' . $append_query_marker . '&wmode=' . $wmode . '"$3</iframe>', $video_info->html);
	    	}
    	}

    	// Inject YouTube parameters if required
    	if( !empty($youtube_params) && (strpos($video_url, "youtube.com/") !== FALSE OR strpos($video_url, "youtu.be/") !== FALSE))
		{
			$youtube_args = '';
			foreach($youtube_params as $param => $value)
			{
				$youtube_args .= '&'.$param.'=' . $value;
			}
			preg_match('/.*?src="(.*?)".*?/', $video_info->html, $matches);
			if (!empty($matches[1])) $video_info->html = str_replace($matches[1], $matches[1] . $youtube_args, $video_info->html);
		}


	// actually setting thumbnails at a reasonably consistent size, as well as getting higher-res images
	if(strpos($video_url, "youtube.com/") !== FALSE OR strpos($video_url, "youtu.be/") !== FALSE) {
		$video_info->highres_url = str_replace('hqdefault','maxresdefault',$video_info->thumbnail_url);
		$video_info->medres_url = $video_info->thumbnail_url;
		$video_info->thumbnail_url = str_replace('hqdefault','mqdefault',$video_info->thumbnail_url);
		$video_info->provider = "youtube";

		if(strpos($video_url, "youtube.com/") !== FALSE) {
			$qs = parse_url($video_url, PHP_URL_QUERY);
			parse_str($qs, $query_params);
			$video_info->video_id = $query_params['v'];
		} else {
			$video_info->video_id = ltrim(parse_url($video_url, PHP_URL_PATH), '/');
		}

		}
	else if (strpos($video_url, "vimeo.com/") !== FALSE) {
		$video_info->highres_url = preg_replace('/_(.*?)\./','_1280.',$video_info->thumbnail_url);
		$video_info->medres_url = preg_replace('/_(.*?)\./','_640.',$video_info->thumbnail_url);
		$video_info->thumbnail_url = preg_replace('/_(.*?)\./','_295.',$video_info->thumbnail_url);
		$video_info->provider = "vimeo";
		}
	else if (strpos($video_url, "wistia.com/") !== FALSE)
		{
		// For now, turn the image_crop_resized query string into an unrecognized value
		$video_info->highres_url = str_replace('image_crop_resized=','icrtemp=',$video_info->thumbnail_url);
		$video_info->medres_url = str_replace('?image_crop_resized=100x60','?image_crop_resized=640x400',$video_info->thumbnail_url);
		$video_info->thumbnail_url = str_replace('?image_crop_resized=100x60','?image_crop_resized=240x135',$video_info->thumbnail_url);
		$video_info->provider = "wistia";
		}
	else if (strpos($video_url, "viddler.com/") !== FALSE)
		{
		$video_info->highres_url = $video_info->thumbnail_url;
		$video_info->medres_url = $video_info->thumbnail_url;
		$video_info->thumbnail_url = str_replace('thumbnail_2','thumbnail_1',$video_info->thumbnail_url);
		$video_info->provider = "viddler";
		}


		// Handle a single tag
		if ($mode == "single")
		{
			$this->return_data = $video_info->html;
			return;
		}

		// Handle multiple tag with tagdata
		foreach ($plugin_vars as $key => $var)
		{
			if (isset($video_info->$key))
			{
				$video_data[$var] = $video_info->$key;
			}
		}

		if(!empty($video_info->width) && !empty($video_info->height))
		{
			$video_data['video_ratio'] = floatval($video_info->width / $video_info->height);
		}

		$tagdata = ee()->functions->prep_conditionals($tagdata, $video_data);

		foreach ($video_data as $key => $value)
		{
			$tagdata = ee()->TMPL->swap_var_single($key, $value, $tagdata);
		}

		$this->return_data = $tagdata;

		return;
	}

	private function _set_cache_path() {
		$this->cache_path = version_compare(APP_VER, '3.0.0', '>=') ? SYSPATH  . 'user/' : APPPATH;
		$this->cache_path .= 'cache/' . $this->cache_name . '/';
		return '';
	}

	/**
	 * Generates a CURL request to the YouTube URL
	 * to make sure that
	 *
	 * @param string $vid_url The YouTube URL
	 * @return void
	 */
	public function curl($vid_url) {
		// Do we have curl?
		if (function_exists('curl_init'))
		{
		    $curl = curl_init();

			// Our cURL options
			$options = array(
				CURLOPT_URL =>  $vid_url,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_CONNECTTIMEOUT => 10,
			);

			curl_setopt_array($curl, $options);

			$video_info = curl_exec($curl);
			$video_header = curl_getinfo($curl, CURLINFO_HTTP_CODE);

			//Close the request
			curl_close($curl);

		}
		// Do we have fopen?
		elseif (ini_get('allow_url_fopen') === TRUE)
		{
			$video_header = ($video_info = file_get_contents($vid_url)) ? '200' : TRUE;
		}
		else
		{
			$video_header = $video_info = FALSE;
		}

		return array($video_info, $video_header);
	}

	/**
	 * Check Cache
	 *
	 * Check for cached data
	 *
	 * @access	public
	 * @param	string
	 * @param	bool	Allow pulling of stale cache file
	 * @return	mixed - string if pulling from cache, FALSE if not
	 */
	function _check_cache($url)
	{
		// Check for cache directory

		$dir = $this->cache_path;

		if ( ! @is_dir($dir))
		{
			return FALSE;
		}

		// Check for cache file

        $file = $dir.md5($url);

		if ( ! file_exists($file) OR ! ($fp = @fopen($file, 'rb')))
		{
			return FALSE;
		}

		flock($fp, LOCK_SH);

		$cache = @fread($fp, filesize($file));

		flock($fp, LOCK_UN);

		fclose($fp);

        // Grab the timestamp from the first line

		$eol = strpos($cache, "\n");

		$timestamp = substr($cache, 0, $eol);
		$cache = trim((substr($cache, $eol)));

		if ( time() > ($timestamp + ($this->refresh_cache * 60)) )
		{
			$this->cache_expired = TRUE;
		}

        return $cache;
	}

	/**
	 * Write Cache
	 *
	 * Write the cached data
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	function _write_cache($data, $url)
	{
		// Check for cache directory

		$dir = $this->cache_path;

		if ( ! @is_dir($dir))
		{
			if ( ! @mkdir($dir, DIR_WRITE_MODE))
			{
				return FALSE;
			}

			@chmod($dir, DIR_WRITE_MODE);
		}

		// add a timestamp to the top of the file
		$data = time()."\n".$data;


		// Write the cached data

		$file = $dir.md5($url);

		if ( ! $fp = @fopen($file, 'wb'))
		{
			return FALSE;
		}

		flock($fp, LOCK_EX);
		fwrite($fp, $data);
		flock($fp, LOCK_UN);
		fclose($fp);

		@chmod($file, DIR_WRITE_MODE);
	}

}
