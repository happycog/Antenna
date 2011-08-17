Antenna for ExpressionEngine 1 & 2
========

**Antenna** is a plugin that will generate the exact, most up-to-date YouTube or Vimeo embed code available. It also gives you access to the video's title, its author, the author's YouTube/Vimeo URL, and a thumbnail. All you have to do is pass it a single URL. 

You can also output various pieces of metadata about the YouTube/Vimeo video.

For ExpressionEngine 1 installation, pi.antenna.php should be added to /system/plugins/

For ExpressionEngine 2 installation, the directory /antenna should be placed in /system/expressionengine/third_party/

Usage
-------

	{exp:antenna url='{the_youtube_or_vimeo_url}' max_width="232" max_height="323"  wmode="transparent|opaque|window"}
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


Set the max\_width and/or max\_height for whatever size your website requires. The video will be resized to be within those dimensions, and will stay at the correct proportions.

The optional wmode parameter can be used if you're experiencing issues positioning HTML content infront of the embedded media. It accepts values of transparent, opaque and window.

If used as a single tag, it returns the HTML embed/object code for the video. If used as a pair, you get access to the 5 variables above and can use them in conditionals.

If you're using Vimeo, you get access to three more parameters:

- vimeo_byline='true/false' -- Shows the byline on the video. Defaults to true.
- vimeo_title='true/false' -- Shows the title on the video. Defaults to true.
- vimeo_autoplay='true/false' -- Automatically start playback of the video. Defaults to false.

**NOTE** For this to work with all urls please ensure that in Weblog/Channel -> Preferences, you have 'Automatically turn URLs and email addresses into links?' set to 'No'. 

Compatibility
-------

**Antenna** is compatible with ExpressionEngine 1 & 2. The version for ExpressionEngine 1 has been tested on 1.6.9 but will likely work with older versions. The version for ExpressionEngine 2 has been tested on 2.1.

You must be using PHP 5.2+, though I haven't tested with PHP 5.3, and you must have the cURL library and PECL json installed.

Warranty/License
-------

There's no warranty of any kind. If you find a bug, please tell me and I may try to fix it. It's provided completely as-is; if something breaks, you lose data, or something else bad happens, the author(s) and owner(s) of this plugin are in no way responsible.

This plugin is owned by Matt Weinberg. You can modify it and use it for your own personal or commercial projects, but you can't redistribute it. EE2 and Vimeo functionality added by Adam Wiggall (@turnandface).
 wmode compatibility added by Tom Davies (@metadaptive).
