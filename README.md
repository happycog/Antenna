Antenna for ExpressionEngine 1 & 2
========

**Antenna** is a plugin that will generate the exact, most up-to-date YouTube, Vimeo, Wistia, or Viddler embed code available. It also gives you access to the video's title, its author, the author's YouTube/Vimeo URL, and a thumbnail. All you have to do is pass it a single URL.

You can also output various pieces of metadata about the video.

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

	    {!-- For Vimeo Only --}
	    {video_description}

	    {if embed_code}
	        It worked! {embed_code}
	    {if:else}
	        No video to display here.
	    {/if}

	{/exp:antenna}


Set the max\_width and/or max\_height for whatever size your website requires. The video will be resized to be within those dimensions, and will stay at the correct proportions.

The optional wmode parameter can be used if you're experiencing issues positioning HTML content in front of the embedded media. It accepts values of transparent, opaque and window.

If used as a single tag, it returns the HTML embed/object code for the video. If used as a pair, you get access to the 5 variables above and can use them in conditionals.

If you're using YouTube, you get access to one more parameter:

- youtube_rel='0/1' -- Show related videos at end of video. Defaults to 1.

If you're using Vimeo, you get access to four more parameters and one more variable:

- vimeo_byline='true/false' -- Shows the byline on the video. Defaults to true.
- vimeo_title='true/false' -- Shows the title on the video. Defaults to true.
- vimeo_portrait='true/false' -- Shows the user's avatar on the video. Defaults to true.
- vimeo_autoplay='true/false' -- Automatically start playback of the video. Defaults to false.
- vimeo_api='true/false' -- Adds 'api=1' to the vimeo embed url to allow JavaScript API usage. Defaults to false.
- {video_description} -- The description of the video, as set in Vimeo

If you're using Viddler, you get access to two more parameters:

- viddler_type='simple/player' -- Specifies the player type. Defaults to player.
- viddler_ratio='widescreen/fullscreen' -- Aspect ratio will be automatically determined if not set.

**NOTE** For this to work with all urls please ensure that in Weblog/Channel -> Preferences, you have 'Automatically turn URLs and email addresses into links?' set to 'No'.

Compatibility
-------

**Antenna** is compatible with ExpressionEngine 1 & 2. The version for ExpressionEngine 1 has been tested on 1.6.9 but will likely work with older versions. The version for ExpressionEngine 2 has been tested on 2.1+.

You must be using PHP 5.2+, though I haven't tested with PHP 5.3, and you must have either the cURL library installed or allow_url_fopen enabled.

Warranty/License
-------

There's no warranty of any kind. If you find a bug, please tell me and I may try to fix it. It's provided completely as-is; if something breaks, you lose data, or something else bad happens, the author(s) and owner(s) of this plugin are in no way responsible.

This plugin is owned by Matt Weinberg. You can modify it and use it for your own personal or commercial projects, but you can't redistribute it. EE2 and Vimeo functionality added by Adam Wiggall (@turnandface). Wmode compatibility added by Tom Davies (@metadaptive).
