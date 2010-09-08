<?php

/**
 * ExpressionEngine Plugin Metadata
 */
$plugin_info = array(
	'pi_name'			=> 'Antenna',
	'pi_version'		=> '1',
	'pi_author'			=> 'Matt Weinberg',
	'pi_author_url'		=> 'http://www.VectorMediaGroup.com',
	'pi_description'	=> 'Returns the embed code and various pieces of metadata for YouTube Videos',
	'pi_usage'			=> Antenna::usage()
);

/**
 * The Antenna plugin will generate the YouTube embed
 * code for a single or multiple YouTube clips. It will
 * also give you back the Author, their URL, the video title,
 * and a thumbnail.
 *
 * @package Antenna
 */
class Antenna {
	public $return_data = '';
	
	/**
	 * PHP4/EE compatibility
	 *
	 * @return void
	 */
	public function Antenna() 
	{
		$this->__construct();
	}
	
	/**
	 * Takes a YouTube url and parameters
	 * does a cURL request and returns the 
	 * the embed code and metadata to EE 
	 *
	 * @return void
	 */
	public function __construct()
	{
		global $TMPL, $FNS;

		$tagdata = $TMPL->tagdata;

		//Check to see if it's a one-off tag or a pair
		$mode = ($tagdata) ? "pair" : "single";
		
		$plugin_vars = array(
			"title"         =>  "video_title",
			"html"          =>  "embed_code",
			"author_name"   =>  "video_author",
			"author_url"    =>  "video_author_url",
			"thumbnail_url" =>  "video_thumbnail"
		);

		//Deal with the parameters
		$url = ($TMPL->fetch_param('url')) ?  html_entity_decode($TMPL->fetch_param('url')) : false;
		$max_width = ($TMPL->fetch_param('max_width')) ? "&maxwidth=" . $TMPL->fetch_param('max_width') : "";
		$max_height = ($TMPL->fetch_param('max_height')) ? "&maxheight=" . $TMPL->fetch_param('max_height') : "";

		// If it's not YouTube just keep on going
		if (!$url || substr($url, 0, 23) != "http://www.youtube.com/") 
		{
			$tagdata = $FNS->prep_conditionals($tagdata, array());
			$this->return_data = $tagdata;
			return;
		}

		$url = "http://www.youtube.com/oembed?url=" .urlencode($url) . "&format=json" . $maxwidth . $maxheight;
		
		//Create the youtube info and youtube header variables
		list($youtube_info, $youtube_header) = $this->curl($url);

		if (!$youtube_info || $youtube_header != "200") 
		{
			$tagdata = $FNS->prep_conditionals($tagdata, array());
			$this->return_data = $tagdata;
			return;
		}
		
		//Decode the cURL data
		$youtube_info = json_decode($youtube_info);

		//Handle a single tag
		if ($mode == "single") 
		{
			$this->return_data = $youtube_info->html;
			return;
		}
		
		//Handle multiple tag with tagdata
		foreach ($plugin_vars as $key => $var) 
		{
			if (isset($youtube_info->$key)) 
			{
				$youtube_data[$var] = $youtube_info->$key;
			}
		}

		$tagdata = $FNS->prep_conditionals($tagdata, $youtube_data);

		foreach ($youtube_data as $key => $value) 
		{
			$tagdata = $TMPL->swap_var_single($key, $value, $tagdata);
		}

		$this->return_data = $tagdata;
		
		return;
	}
	
	/**
	 * Generates a CURL request to the YouTube URL
	 * to make sure that 
	 *
	 * @param string $yt_url The YouTube URL
	 * @return void
	 */
	public function curl($yt_url) {
	    $curl = curl_init();
	
		// Our cURL options
		$options = array(
			CURLOPT_URL =>  $yt_url, 
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_CONNECTTIMEOUT => 10,
		); 
		
		curl_setopt_array($curl, $options);

		$youtube_info = curl_exec($curl);
		$youtube_header = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		//Close the request
		curl_close($curl);

		return array($youtube_info, $youtube_header);
	}
	
	/**
	 * ExpressionEngine plugins require this for displaying
	 * usage in the control panel
	 *
	 * @return void
	 */
    public function usage() 
	{
		ob_start();
?>
Antenna is a plugin that will generate the exact, most up-to-date YouTube embed code available. It also gives you access to the video's title, its author, the author's YouTube URL, and a thumbnail. All you have to do is pass it a single URL. 

You can also output various pieces of metadata about the YouTube video.

{exp:antenna url='{the_youtube_url}' max_width="232" max_height="323"}
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

If used as a single tag, it returns the HTML embed/object code for the video. If used as a pair, you get access to the 5 variables above and can use them in conditionals.

<?php
		$buffer = ob_get_contents();
		
		ob_end_clean(); 
	
		return $buffer;
	}
}
?>