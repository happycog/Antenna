Antenna for ExpressionEngine 1
========

Antenna is a plugin that will generate the exact, most up-to-date YouTube embed code available. It also gives you access to the video's title, its author, the author's YouTube URL, and a thumbnail. All you have to do is pass it a single URL. 

You can also output various pieces of metadata about the YouTube video.

Usage
-------

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


Set the max\_width and/or max\_height for whatever size your website requires. The video will be resized to be within those dimensions, and will stay at the correct proportions.

If used as a single tag, it returns the HTML embed/object code for the video. If used as a pair, you get access to the 5 variables above and can use them in conditionals.