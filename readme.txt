=== Utopian Images ===
Contributors: uwpweb
Donate link: 
Tags: thumbnails,images,thumbs,thumb,thumbnail,attachments
Requires at least: 4.0
Tested up to: 4.8
Stable tag: trunk
Requires PHP: 5.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

This bullet-proof thumbnail script finds all possible sources for a post or term's image including WP's native thumbnail, attachments, object content and meta

== Description ==

This bullet-proof thumbnail script finds all possible sources for a post or term's image including WP's native thumbnail, attachments, object content and meta

== Installation ==

Once the plugin is activated, you can use the plugin's function inside your theme's files.

Because the plugin returns a url, this function should be used in replacement of img src values. The following example gives you an idea of how the function should be used.

<code>< img src="<?php if (function_exists('uwp_thumb')) {echo uwp_thumb(array('id' => '111', 'type' => 'post');}?>" /></code>

The function accepts an optional array of arguments which for now include 'id', and 'type'. These are only needed if you're calling the function outside of the loop.

Options can be found in 'Settings-> Utopian Images. Please refer to the FAQ section to understand these options.

== Frequently Asked Questions ==

= What does Thumbnail Source Priorities option do? =

There are four sources that Utopian Images checks to find an object's image. Because the plugin stops looking once it finds an image, you may want to reorder the sources if you have preferences about which are used. This is done by clicking and dragging on the options. The default setting is ideal for most sites.

= What does the Comma-Separated Meta Key Matches option do? =

One of the places that the plugin looks for an object's image is in your site's meta data. Using comma's to seperate, you can add keywords to be used for matches against the meta key names in your database. You don't have to use the entire meta key, just a search word that can be found within it. The default setting is ideal for most sites.


== Screenshots ==


== Changelog ==


== Upgrade Notice ==