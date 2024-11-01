=== Wordbook ===

Contributors: rtsai
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=wordbook@tsaiberspace.net&currency_code=USD
Tags: facebook, minifeed, newsfeed, crosspost
Requires at least: 2.2
Tested up to: 3.0
Stable tag: 0.16.3

This plugin allows you to cross-post your blog posts to your Facebook Wall. Your Facebook "Boxes" tab will show your most recent blog posts.

== Description ==

This plugin allows you to cross-post your blog posts to your Facebook Wall. Your Facebook "Boxes" tab will show your most recent blog posts.

== Installation ==

1. [Download](http://wordpress.org/extend/plugins/wordbook/) the ZIP file.
1. Unzip the ZIP file.
1. Upload the `wordbook` directory to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Navigate to `Options` &rarr; `Wordbook` for configuration and follow the on-screen prompts.

== Frequently Asked Questions ==

= Isn't Wordbook the same as importing my blog posts into Facebook Notes? =

It is certainly similar, but not the same:

- Facebook Notes imports and caches your blog posts (e.g., it subscribes to your blog's RSS feed).

  Wordbook uses the Facebook API to actively update your Facebook Wall just as if you had posted an update yourself on facebook.com. This means that your updates are likely to appear faster than waiting around for the Facebook Notes RSS reader to notice your update. It also means that you can make changes to your blog postings *after* initially publishing them.

- With Wordbook, your blog postings will have their own space in your Facebook profile, instead of having to compete for space with your other posted Facebook Notes.

- Your updates will show up with a nifty WordPress logo next to them instead of the normal "Notes" icon :).

- Your Facebook Notes' comments will not show up on your WordPress blog. Wordbook links everything back to your blog, so your comments will also stay on your blog (it does mean your readers will have to leave Facebook).

= How is this different from the WordPress application? =

The [WordPress application](http://www.facebook.com/apps/application.php?id=2373049596) allows you to post to your [wordpress.com](http://www.wordpress.com/) blog directly from within Facebook. You cannot use the Facebook app with a self-hosted WordPress blog.

This Wordbook plugin works in the reverse direction. When you publish a new post or page, the plugin, in conjunction with the [Wordbook](http://www.facebook.com/apps/application.php?id=3353257731) Facebook application, cross-posts your new blog entry to your Facebook account. You cannot use Wordbook with a blog hosted at wordpress.com.

= Why aren't my blog posts showing up in Facebook? =

- Wordbook will not publish password-protected posts.

- Any errors Wordbook encounters while communicating with Facebook will be recorded in error logs; the error logs (if any) are viewable in the "Wordbook" panel of the "Options" WordPress admin page.

- To discourage spammy behavior, Wordbook only publishes a given post to Facebook once within a 24-hour period of time. There is currently no user interface for "resetting" this clock to enable a re-publish of recently-published posts. Those comfortable with MySQL may manipulate the `wordbook_postlogs` table (`TRUNCATE` the table to enable re-publishing for all posts).

- Facebook sometimes just doesn't work (other developers have also reported this problem with their Facebook apps; it's not just Wordbook); there is nothing the Wordbook plugin can do about this.

= My WordPress database doesn't use the default 'wp_' table prefix. Will this plugin still work? =

The plugin is aware of database table prefixes. Specifically, things should work correctly for a single WordPress install supporting multiple blogs in a single database.

= How do I reset my Wordbook/WordPress configuration so I can start over from scratch? =

1. Click the "Reset configuration" button in the "Wordbook" panel of the "Options" WordPress admin page.
1. Deactivate the Wordbook plugin from your WordPress installation.
1. [Uninstall Wordbook](http://www.facebook.com/apps/application.php?id=3353257731) from your Facebook account.
1. Download the [latest version](http://wordpress.org/extend/plugins/wordbook/)
1. Re-install and re-activate the plugin.

= How do I report problems or submit feature requests? =

Use the [Wordbook Discussion Board](http://www.facebook.com/apps/application.php?v=app_2373072738&id=3353257731). Either start a new topic, or add to an existing topic.

Do *not* use the Wordbook Wall for support or feature requests.

== Features ==

- Works with a complementary [Facebook application](http://www.facebook.com/apps/application.php?id=3353257731) to update your Facebook Wall and friends' News Feeds about your blog and page postings.
- A miniature version of your recent blog posts will be displayed in the "Boxes" tab of your Facebook profile.
- Supports multi-author blogs: each blog author notifies only their own friends of their blog/page posts.

== Bugs that will be fixed ==

1. Internationalization/Localization

   I would like for Wordbook to work everywhere, but I lack expertise in this sort of thing and welcome any pointers and/or patches.

== Feature requests under consideration (as time permits) ==

1. How can I customize the way Wordbook appears in my profile?

   For the immediately-forseeable future, Wordbook will not be that configurable. The only immediately-available configuration is the number of posts showing up in the mini-blog. This number is the same as the number of posts that show up on the front page of your blog; navigate to your Dashboard Options &rarr; Reading and configure the "Blog Pages: Show at most" option.

1. Wordbook sends *all* my posts to Facebook. Can I do this selectively?

   At this time, no. You will have to log in to Facebook immediately after posting and manually remove the item from your Wall.
   
   Someday I'll add a "Post to Facebook" checkbox next to the WordPress "Publish" button or something.

1. I have multiple blogs; I'd like for all of them to update the same Facebook account.

   This is possible, but does not work at this time. This features requires a UI to allow Facebook session keys (currently not displayed anywhere in the UI) to be cut-and-pasted between multiple blogs.

1. I use WordPress excerpts for my RSS feeds; Wordbook doesn't use this for the profile box.

   This is a Wordbook bug; it doesn't know about excerpts yet.

== Feature requests not under consideration ==

1. My WordPress is 1.x/2.0/2.1. The plugin doesn't work!

   This plugin is written for and tested on WordPress-2.3. I cannot test on older WordPress releases. I will happily accept any patches, if you are able to write a patch to get things to work. Otherwise, you really should consider upgrading your WordPress installation.

1. Can this work with PHP-4.x or MySQL-4.x?

   It is true that the [WordPress minimum requirements](http://wordpress.org/about/requirements/) are PHP-4.3 or greater and MySQL-4.0 or greater.

   There are two obstacles to Wordbook getting PHP4 support again: (a) My development environment is PHP5 and MySQL-5.0, and (b) Facebook has stopped releasing an "official" client library for PHP4.

   I cannot actively maintain PHP4 support, but will happily accept patches.

== Screenshots ==

1. WordPress updating a Facebook profile's Wall.
1. Summary of WordPress blog posts in a Facebook profile.

== Changelog ==

Full Subversion changelog: [http://plugins.trac.wordpress.org/log/wordbook/](http://plugins.trac.wordpress.org/log/wordbook/)

= 0.16.3 =

- Add extra debugging messages when there is a problem.
- Fix version mismatch between readme.txt and wordbook.php.

= 0.16.1 =

- Fix reported problems with wordbook_fbclient_methods being undefined.

= 0.16 =

- Handle single/double quotes in img tags.
- Fix setup page.
- HTML entity-ify title HTML strings.
- Be more flexible with version strings from MySQL.
- Validate FacebookRestClient.
- Internal refactoring.

= 0.15.7 =

Remove unnecessary `require()` statements to avoid conflicts with other uses of `pluggable.php`.

= 0.15.5 =

Decode HTML entities before publishing to Facebook.

= 0.15.3 =

Update Facebook Connect URL from obsolete `authorize.php` to `prompt_permissions.php` to fix stream_publish setup problems.

= 0.15 =

Use the new [stream.publish](http://wiki.developers.facebook.com/index.php/Stream.publish) API instead of the deprecated [feed.publishUserAction](http://wiki.developers.facebook.com/index.php/Feed.publishUserAction) API.

= 0.14.10 =

Fix regular expression to avoid obsolete img 'lowsrc' attribute.

= 0.14.8 =

Add support for WPG2 tags.

== Upgrade Notice ==

= 0.15 =

This version uses the new [streamm.publish](http://wiki.developers.facebook.com/index.php/Stream.publish) API instead of the deprecated [feed.publishUserAction](http://wiki.developers.facebook.com/index.php/Feed.publishUserAction) API. Facebook has announced that support for `feed.publishUserAction` will be discontinued Jan 5 2010 (which means Wordbook will stop working).
