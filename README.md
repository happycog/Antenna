Antenna for ExpressionEngine 2 & 3
========

**Antenna** is a plugin that will generate the exact, most up-to-date YouTube, Vimeo, Wistia, or Viddler embed code available. With an [embed.ly](http://embed.ly/) API key, you also have access to [hundreds more video providers](http://embed.ly/providers). In addition to the video's embed code, **Antenna** also provides you with the video's title, its author, the author's YouTube/Vimeo URL, and a thumbnail. All you have to do is pass it a single URL.

You can also output various pieces of metadata about the video.

Support for ExpressionEngine 1 ended with v1.23. That version can still be [downloaded here](https://github.com/vector/Antenna/releases/tag/v1.23).

Support for ExpressionEngine 3 started with version 2.

Installation
-------

- ExpressionEngine 2:
  - Place the directory `/third_party/antenna` in `/system/expressionengine/third_party/`.
- ExpressionEngine 3:
  - Place the directory `/third_party/antenna` in `/system/user/addons/`.
  - Go go the addon manager and click "Install" next to Antenna.

Usage
-------

	{exp:antenna url='{the_youtube_or_vimeo_url}' max_width="232" max_height="323"  wmode="transparent|opaque|window"}
	    {embed_code}
	    {video_title}
	    {video_author}
	    {video_author_url}
	    {video_thumbnail}
	    {video_mediumres}
	    {video_highres}
	    {video_provider}
	    {video_ratio}
	    {video_width}
	    {video_height}
	    {video_provider_id}

	    {!-- For Vimeo Only --}
	    {video_description}

	    {if embed_code}
	        It worked! {embed_code}
	    {if:else}
	        No video to display here.
	    {/if}
	{/exp:antenna}


Set the `max_width` and/or `max_height` for whatever size your website requires. The video will be resized to be within those dimensions, and will stay at the correct proportions.

The optional `wmode` parameter can be used if you're experiencing issues positioning HTML content in front of the embedded media. It accepts values of transparent, opaque and window.

If used as a single tag, it returns the HTML embed/object code for the video. If used as a pair, you get access to the variables above and can use them in conditionals.

There are three image sizes available for videos: `{video_thumbnail}`, `{video_mediumres}`, and `{video_highres}`. They are not consistent across services but they should fall into rough size brackets. `{video_thumbnail}` is going to be between 100-200px wide; `{video_mediumres}` will be around 400-500px wide; and `{video_highres}` will be at least the full size of your uploaded video and could be as wide as 1280px.

Antenna will automatically enforce HTTPS if the provided video URL has a protocol of `https://` and is supported by the video service. Alternatively, you can also attempt to force the particular service to return the HTTPS resource by adding the parameter:

    force_https='true'

If you're using YouTube, you get access to several more parameters, such as:

- `youtube_rel='0/1'` -- Show related videos at end of video. Defaults to 1.
- `youtube_showinfo ='0/1'` -- Show the video title and uploader. Defaults to 1.

See [this guide from Google](https://developers.google.com/youtube/player_parameters#Parameters) for the full list of HTML5 parameters available for YouTube videos. Prefix each parameter with `youtube_` when adding these parameters to your Antenna template tag.

If you're using Vimeo, you get access to more parameters and one more variable:

- `vimeo_byline='true/false'` -- Shows the byline on the video. Defaults to true.
- `vimeo_title='true/false'` -- Shows the title on the video. Defaults to true.
- `vimeo_portrait='true/false'` -- Shows the user's avatar on the video. Defaults to true.
- `vimeo_autoplay='true/false'` -- Automatically start playback of the video. Defaults to false.
- `vimeo_api='true/false'` -- Adds 'api=1' to the vimeo embed url to allow JavaScript API usage. Defaults to false. *NOTE*: only use this if you're using the legacy Vimeo "Froogaloop" API. Keep it off if you're using the new, Player.js API.
- `vimeo_color='EFEFEF'` -- changes the color of the player controls, and the color of the video title (if enabled). The parameter here expects a hex color code.
- `vimeo_loop='true/false'` -- loops the Vimeo video automatically
- `{video_description}` -- The description of the video, as set in Vimeo

If you're using Viddler, you get access to two more parameters:

- `viddler_type='simple/player'` -- Specifies the player type. Defaults to player.
- `viddler_ratio='widescreen/fullscreen'` -- Aspect ratio will be automatically determined if not set.

The plugin automatically caches returned data for one week. You can control this setting with the `cache_minutes` parameter, or set `cache_minutes` to "`0`" to disable the cache.

**NOTE** For this to work with all urls please ensure that in Weblog/Channel -> Preferences, you have 'Automatically turn URLs and email addresses into links?' set to 'No'.

Embed.ly Support
-------

By including an [embed.ly](http://embed.ly/) API key, you have access to [hundreds more video providers](http://embed.ly/providers). If your URL is not for YouTube, Vimeo, Wistia, or Viddler, **Antenna** will check embed.ly to pull the correct relevant video embed and information.

Simply include your embed.ly API key as a parameter on your `{exp:antenna}` tag like:

```
{exp:antenna url='{example_vine_url}' embedly_key="xxxxxxxxxx" max_width="232" max_height="323"  wmode="transparent|opaque|window"}
	...
{/exp:antenna}
```

or set your embed.ly API key globally by adding it to ExpressionEngine's `config.php` file:

```
$config['antenna_embedly_key'] = 'xxxxxxxxxx';
```

Compatibility
-------

**Antenna** is compatible with ExpressionEngine 2 & 3. The version for ExpressionEngine 2 requires 2.6+. The version for ExpressionEngine 3 has been tested on 3.0+.

You must be using PHP 5.2+ and you must have either the `cURL` library installed or `allow_url_fopen` enabled.

*Note for Wistia users*: Wistia doesn't return as much data as the other providers, so not all of the above variables will work. Also, you may need to contact Wistia and ask them to enable "oEmbed support" on your account.

Warranty/License
-------

There's no warranty of any kind. If you find a bug, please tell me and I may try to fix it. It's provided completely as-is; if something breaks, you lose data, or something else bad happens, the author(s) and owner(s) of this plugin are in no way responsible.

This plugin is owned by Matt Weinberg. You can modify it and use it for your own personal or commercial projects, but you can't redistribute it. EE2 and Vimeo functionality added by Adam Wiggall (@turnandface). Wmode compatibility added by Tom Davies (@metadaptive).
