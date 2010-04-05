=== Shortcode Exec PHP ===
Contributors: Marcel Bokhorst
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=AJSBB7DGNA3MJ&lc=US&item_name=Shortcode%20Exec%20PHP%20WordPress%20Plugin&item_number=Marcel%20Bokhorst&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHosted
Tags: admin, shortcode, run, php, eval, execute, exec, code, post, posts, page, pages, comment, comments, sidebar, widget, widgets, rss, feed, feeds, AJAX
Requires at least: 2.5
Tested up to: 3.0-beta1
Stable tag: 0.4

Execute arbitrary, reusable PHP code in posts, pages, comments, widgets and RSS feeds using shortcodes in a safe and easy way

== Description ==

Using this plugin you can execute arbitrary [PHP](http://www.php.net/ "PHP") code using [shortcodes](http://codex.wordpress.org/Shortcode_API "Shortcode API") in your posts, pages, comments, widgets and RSS feeds, just like manually defined shortcodes. The shortcodes and associated PHP code are defined using the settings of this plugin. It is possible to parse and use shortcode parameters and to use shortcode content. Defined shortcodes can be deleted and disabled.

Advantages over other solutions:

1. Your texts do not have to contain PHP code
1. PHP code can be reused (by reusing the shortcode)
1. All PHP code is organized at one place
1. [Syntax highlighting](http://en.wikipedia.org/wiki/Syntax_highlighting "Syntax highlighting")
1. You can test your PHP code before using it

For those concerned about security (hopefully everybody): only administrators can define shortcodes and associated PHP code (see also the [FAQ](http://wordpress.org/extend/plugins/shortcode-exec-php/faq/ "FAQ")).

Please report any issue you have with this plugin on the [support page](http://blog.bokhorst.biz/3626/computers-en-internet/wordpress-plugin-shortcode-exec-php/ "Marcel's weblog"), so I can at least try to fix it.

See my [other plugins](http://wordpress.org/extend/plugins/profile/m66b "Marcel Bokhorst").

== Installation ==

*Using the WordPress dashboard*

1. Login to your weblog
1. Go to Plugins
1. Select Add New
1. Search for *Shortcode Exec PHP*
1. Select Install
1. Select Install Now
1. Select Activate Plugin

*Manual*

1. Download and unzip the plugin
1. Upload the entire shortcode-exec-php/ directory to the /wp-content/plugins/ directory
1. Activate the plugin through the Plugins menu in WordPress

== Frequently Asked Questions ==

= Why does the shortcode output appear before the text? =

Probably because you used the *echo* statement instead of the *return* statement.

= What happens when I disable a shortcode? =

The shortcode will not be handled and will appear unprocessed.

= Who can access the settings and the PHP code? =

Users with *manage\_options* capability (administrators).

= Who can use the defined shortcodes? =

Anyone who can create or modify posts, pages and/or widgets or can write comments (if enabled).

= How are PHP errors handled? =

Because the [PHP eval function](http://php.net/manual/en/function.eval.php "PHP eval function") is used, errors cannot be handled unfortunately, so test your code thoroughly.

= How many shortcodes can I define? =

Unlimited.

= Where are the shortcode definitions stored? =

The shortcode name, enabled indication and PHP code are stored as WordPress options.

= How can I change the styling of the settings? =

1. Copy *shortcode-exec-php.css* to your theme directory to prevent it from being overwritten by an update
2. Change the style sheet to your wishes; the style sheet contains documentation

= How do I test a shortcode with parameters? =

Indirectly, by using default values.

= Where can I ask questions, report bugs and request features? =

You can write a comment on the [support page](http://blog.bokhorst.biz/3626/computers-en-internet/wordpress-plugin-shortcode-exec-php/ "Marcel's weblog").

== Screenshots ==

1. Shortcode exec PHP

== Changelog ==

= 0.4 =
* Syntax highlighting
* In-place add, update and delete of shortcodes (using AJAX)
* Shortcodes can be tested in the administration backend

= 0.3 =
* Only administrators can see options and shortcode definitions now
* Shortcodes are sorted alphabetically in the administration backend

= 0.2 =
* Added options to enable shortcodes in excerps, comments and RSS feeds
* Added options to change width and height of PHP code textarea
* Improved layout of options

= 0.1 =
* Initial version

= 0.0 =
* Development version

== Upgrade Notice ==

= 0.4 =
Easier editing, syntax highlighting, shortcode testing

= 0.3 =
Better security, shortcode sorting

= 0.2 =
Added options to enable shortcodes in excerps, comments and RSS feeds and to set the size of the PHP code box

= 0.1 =
Initial version

== Acknowledgments ==

This plugin uses:

* [EditArea](http://www.cdolivet.com/index.php?page=editArea "EditArea")
by *Christophe Dolivet* and published under the GNU Lesser General Public License

* [jQuery JavaScript Library](http://jquery.com/ "jQuery") published under both the GNU General Public License and MIT License

All licenses are [GPL-Compatible Free Software Licenses](http://www.gnu.org/licenses/license-list.html#GPLCompatibleLicenses "GPL compatible").

