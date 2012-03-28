<?php

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Antenna Plugin
 * Copyright Matt Weinberg, www.VectorMediaGroup.com
 */

$plugin_info = array(
	'pi_name'			=> 'Antenna',
	'pi_version'		=> '1.17',
	'pi_author'			=> 'Matt Weinberg',
	'pi_author_url'		=> 'http://www.VectorMediaGroup.com',
	'pi_description'	=> 'Returns the embed code and various pieces of metadata for YouTube, Vimeo, Wistia, and Viddler Videos',
	'pi_usage'			=> Antenna::usage()
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
	public $refresh_cache = 10080;			// in mintues (default is 1 week)
	public $cache_expired = FALSE;

	public function Antenna() 
	{
		$this->EE =& get_instance();

		$tagdata = $this->EE->TMPL->tagdata;

		// Check to see if it's a one-off tag or a pair
		$mode = ($tagdata) ? "pair" : "single";
		
		$plugin_vars = array(
			"title"         =>  "video_title",
			"html"          =>  "embed_code",
			"author_name"   =>  "video_author",
			"author_url"    =>  "video_author_url",
			"thumbnail_url" =>  "video_thumbnail"
		);
		
		$video_data = array();

		foreach ($plugin_vars as $var) {
			$video_data[$var] = false;
		}

		// Deal with the parameters
		$video_url = ($this->EE->TMPL->fetch_param('url')) ?  html_entity_decode($this->EE->TMPL->fetch_param('url')) : false;
		$max_width = ($this->EE->TMPL->fetch_param('max_width')) ? "&maxwidth=" . $this->EE->TMPL->fetch_param('max_width') : "";
		$max_height = ($this->EE->TMPL->fetch_param('max_height')) ? "&maxheight=" . $this->EE->TMPL->fetch_param('max_height') : "";
		$wmode = ($this->EE->TMPL->fetch_param('wmode')) ? $this->EE->TMPL->fetch_param('wmode') : "";
		$wmode_param = ($this->EE->TMPL->fetch_param('wmode')) ? "&wmode=" . $this->EE->TMPL->fetch_param('wmode') : "";

		// Correct for a bug in YouTube response if only maxheight is set and the video is over 612px wide
		if (empty($max_height)) $max_height = "&maxheight=" . $this->EE->TMPL->fetch_param('max_width');

		// Cache can be disabled by setting 0 as the cache_minutes param
		if ($this->EE->TMPL->fetch_param('cache_minutes') !== FALSE && is_numeric($this->EE->TMPL->fetch_param('cache_minutes'))) {
			$this->refresh_cache = $this->EE->TMPL->fetch_param('cache_minutes');
		}

		// Some optional YouTube parameters
		$youtube_rel = $this->EE->TMPL->fetch_param('youtube_rel', null);
		
		// Some optional Vimeo parameters
		$vimeo_byline = ($this->EE->TMPL->fetch_param('vimeo_byline') == "false") ? "&byline=false" : "";
		$vimeo_title = ($this->EE->TMPL->fetch_param('vimeo_title') == "false") ? "&title=false" : "";
		$vimeo_autoplay = ($this->EE->TMPL->fetch_param('vimeo_autoplay') == "true") ? "&autoplay=true" : "";
		$vimeo_portrait = ($this->EE->TMPL->fetch_param('vimeo_portrait') == "false") ? "&portrait=0" : "";

		// Some optional Viddler parameters
		$viddler_type = ($this->EE->TMPL->fetch_param('viddler_type')) ? "&type=" . $this->EE->TMPL->fetch_param('viddler_type') : "";
		$viddler_ratio = ($this->EE->TMPL->fetch_param('viddler_ratio')) ? "&ratio=" . $this->EE->TMPL->fetch_param('viddler_ratio') : "";

		// If it's not YouTube, Vimeo, Wistia, or Viddler bail
		if (strpos($video_url, "youtube.com/") !== FALSE OR strpos($video_url, "youtu.be/") !== FALSE) {
			$url = "http://www.youtube.com/oembed?format=xml&iframe=1&url=";
		} else if (strpos($video_url, "vimeo.com/") !== FALSE) {
			$url = "http://vimeo.com/api/oembed.xml?url=";
		} else if (strpos($video_url, "wistia.com/") !== FALSE) {
			$url = "http://app.wistia.com/embed/oembed.xml?url=";		
		} else if (strpos($video_url, "viddler.com/") !== FALSE) {
			$url = "http://lab.viddler.com/services/oembed/?format=xml&url=";		
		} else {
			$tagdata = $this->EE->functions->var_swap($tagdata, $video_data);
			$this->return_data = $tagdata;
			return;
		}


		$url .= urlencode($video_url) . $max_width . $max_height . $wmode_param . $vimeo_byline . $vimeo_title . $vimeo_autoplay . $vimeo_portrait . $viddler_type . $viddler_ratio;
		
		// checking if url has been cached
		$cached_url = $this->_check_cache($url);
		
		if (! $this->refresh_cache OR $this->cache_expired OR ! $cached_url)
		{
			//Create the info and header variables
			list($video_info, $video_header) = $this->curl($url);

			if (!$video_info || $video_header != "200") 
			{
				$tagdata = $this->EE->functions->var_swap($tagdata, $video_data);
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

    	// Inject YouTube rel value if required
    	if (!is_null($youtube_rel))
		{
			preg_match('/.*?src="(.*?)".*?/', $video_info->html, $matches);
			if (!empty($matches[1])) $video_info->html = str_replace($matches[1], $matches[1] . '&rel=' . $youtube_rel, $video_info->html);
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

		$tagdata = $this->EE->functions->prep_conditionals($tagdata, $video_data);

		foreach ($video_data as $key => $value) 
		{
			$tagdata = $this->EE->TMPL->swap_var_single($key, $value, $tagdata);
		}

		$this->return_data = $tagdata;

		return;
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
		
		$dir = APPPATH . 'cache/' . $this->cache_name . '/';
		
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
		
		$dir = APPPATH . 'cache/' . $this->cache_name . '/';

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
	
	/**
	 * ExpressionEngine plugins require this for displaying
	 * usage in the control panel
	 * @access public
	 * @return string 
	 */
    public function usage() 
	{
		ob_start();
?>
Antenna is a plugin that will generate the exact, most up-to-date YouTube, Vimeo, Wistia, or Viddler embed code available. It also gives you access to the video’s title, its author, the author’s YouTube/Vimeo URL, and a thumbnail. All you have to do is pass it a single URL.

You can also output various pieces of metadata about the YouTube video.

{exp:antenna url='{the_youtube_or_vimeo_url}' max_width="232" max_height="323" cache_minutes="120" wmode="transparent|opaque|window"}
    {embed_code}
    {video_title}
    {video_author}
    {video_author_url}
    {video_thumbnail}
	
	{if embed_code}
		It worked! {embed_code}
	{if:else}
		No video to display here.
	{/if}
	
{/exp:antenna}

Set the max_width and/or max_height for whatever size your website requires. The video will be resized to be within those dimensions, and will stay at the correct proportions.

The optional wmode parameter can be used if you're experiencing issues positioning HTML content in front of the embedded media. It accepts values of transparent, opaque and window.

If used as a single tag, it returns the HTML embed/object code for the video. If used as a pair, you get access to the 5 variables above and can use them in conditionals.

The cache defaults to 1 week. It can be completely disabled by setting cache_minutes="0".

Note for Wistia users: Wistia doesn’t return as much data as the other providers, so not all of the above variables will work. Also, you may need to contact Wistia and ask them to enable "oEmbed support" on your account for this to work.

<?php
		$buffer = ob_get_contents();
		
		ob_end_clean(); 
	
		return $buffer;
	}
	// END
}