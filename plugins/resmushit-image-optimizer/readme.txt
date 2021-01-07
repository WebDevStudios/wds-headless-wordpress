=== reSmush.it : the only free Image Optimizer & compress plugin  ===
Contributors: resmushit
Tags: image, optimizer, image optimization, resmush.it, smush, jpg, png, gif, optimization, compression, Compress, Images, Pictures, Reduce Image Size, Smush, Smush.it
Requires at least: 4.0.0
Tested up to: 5.5.1
Stable tag: 0.3.11
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The FREE Image Optimizer which will compress your pictures and improve your SEO & performances by using reSmush.it, the 8 billion images API optimizer.

== Description ==

reSmush.it Image Optimizer allow to use **free Image optimization** based on [reSmush.it API](http://www.resmush.it/ "Image Optimization API, developped by Charles Bourgeaux"). reSmush.it provides image size reduction based on several advanced algorithms. The API accept JPG, PNG and GIF files up to **5MB**.

This plugin includes a bulk operation to optimize all your pictures in 2 clicks ! Change your image optimization level to fit your needs !
This service is used by more than **400,000** websites on different CMS (Wordpress, Drupal, Joomla, Magento, Prestashop...).

The plugin includes an option to exclude some pictures of the optimizer.

Since Aug. 2016, reSmush.it allows to optimize pictures up to 5MB, for free !

This plugin has initially been developped by [Maecia Agency](http://www.maecia.com/ "Maecia Drupal & Wordpress Agency"), Paris.

[](http://coderisk.com/wp/plugin/resmushit-image-optimizer/RIPS-Af6lJWjjj5)

== Installation ==

1. Upload `resmushit-image-optimizer` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. All your new pictures will be automatically optimized !


== Frequently Asked Questions ==


= How great is reSmush.it ? =

Since we've optimized more than 8,000,000,000 pictures, we've risen new skills. Our service is still in development to bring you new crazy functionalities.

= What about WebP and next generation image formats ? =

We're working on a new offer to bring you the best of these new features. Please be patient, it will come soon :)

= Is there an "Optimize on upload" feature ? =

Absolutely, this feature is enabled for all new pictures to be added, and can be disabled on will.

= Is there a CRON feature ? =

Yes, for big (and even for small) media Libraries, you can optimize your pictures using Cronjobs.

= Can I choose an optimisation level ? =

Yes, by default the optimization level is set at 92. But you can optimize more your pictures by reducing this optimization level.

= Can I go back to my original pictures ? =

Yes, by excluding/reverting this asset you'll have your original image available.

= Is it possible to exclude some pictures from the optimizer ? =

Yes, since version 0.1.2, you can easily exclude an asset from the optimizer.

= Have I a risk to lose my existing pictures ? =

Nope ! reSmush.it Image Optimizer creates a copy of the original pictures, and will perform optimizations only on copies.

= Is it free ? =

Yes ! Absolutely free, the only restriction is to send images below 5MB.

== Screenshots ==

1. The simple interface

== Changelog ==

= 0.3.11 =
* Fix : Optimize button not working when creating a new post
* Fix : Default value of variables incorrectly initialized
* Test on WP 5.5.1

= 0.3.10 =
* hotfix : deprecated function used

= 0.3.9 =
* Fix : OWASP & Security fix

= 0.3.8 =
* Fix : Fix warning in variable not set (metadata)
* Fix : Add an extension uppercase check

= 0.3.7 =
* Fix : CSS+JS load on every admin page, now restricted to reSmush.it pages & medias
* Fix : Links verification format for admin menu

= 0.3.6 =
* Fix : cron multiple run issue. 

= 0.3.5 =
* New header image, new WP description for plugin page.

= 0.3.4 =
* Issue in version number

= 0.3.3 =
* Fix double cron launch. Timeout added
* Fix "Reduce by 0 (0 saved)" message if statistics are disabled
* Return error if attachment file not found on disk

= 0.3.2 =
* Fix variable check (generate notice)

= 0.3.1 =
* Fix log write (permission issue)
* Fix "Reduce by 0 (0 saved)" error. Optimize single attachment while "Optimize on upload" is disabled

= 0.3.0 =
* Add Backup deletion option
* Add script to delete old backups
* Changed JS inclusion

= 0.2.5 =
* Add Preserve Exif Feature

= 0.2.4 =
* Fix issue on SQL request for table prefix different from 'wp_'

= 0.2.3 =
* Version number issue

= 0.2.2 =
* Fix settings automatically reinitialized.

= 0.2.1 =
* Complete French translation
* Plugin translation fix

= 0.2.0 =
* Add CRON feature
* Code refactoring
* Fix issue for big Media library, with a limitation while fetching attachments
* Fix log path issues

= 0.1.23 =
* Add Settings link to Plugin page
* Limit reSmush.it options to image attachments only
* Fix `RESMUSHIT_QLTY is not defined`

= 0.1.22 =
* Fix on attachment metadata incorrectly returned (will fix issues with other media libraries)

= 0.1.21 =
* Wordpress 5.0 compatibility

= 0.1.20 =
* Fix PHP errors with PHP 7.2
* Code refacto

= 0.1.19 =
* Fix JS on "Optimize" button for a single picture
* Provide a new "Force Optimization" for a single picture

= 0.1.18 =
* Avoid `filesize () : stat failed` errors if a picture file is missing
* Log check file permissions
* Check extensions on upload (avoid using reSmush.it API if it's not a picture)
* Increase API Timeout for big pictures (10 secs)

= 0.1.17 =
* Fix bug (non-working optimization) on bulk upload when "Optimize on upload" isn't selected
* New header banner for 4 billionth images optimized

= 0.1.16 =
* Add correction for allow_url_fopen support
* News feed loaded from a SSL URL

= 0.1.15 =
* Log rotate if file too big

= 0.1.14 =
* Tested up to Wordpress 4.9.5
* New contributor (resmushit)
* Translation completion

= 0.1.13 =
* Tested up to Wordpress 4.9.1
* New header banner for 3 billionth images optimized :)

= 0.1.12 =
* Tested up to Wordpress 4.8.1

= 0.1.11 =
* New header banner for 2 billionth images optimized :)

= 0.1.10 =
* Slovak translation fix

= 0.1.9 =
* Slovak translation fix

= 0.1.8 =
* Italian translation added (thanks to Cristian R.)
* Description minor correction

= 0.1.7 =
* Slovak translation added (thanks to Martin S.)

= 0.1.6 =
* Bug fix when images uploaded > 5MB
* List of files above 5MB
* Translation minor corrections

= 0.1.5 =
* Error management if webservice not reachable
* Filesize limitation increased from 2MB to 5MB

= 0.1.4 =
* CSS Fixes

= 0.1.3 =
* Translation correction
* News feed images correction

= 0.1.2 =
* Delete also original file when deleting an attachment
* Exclusion of an attachment of the reSmush.it optimization (checkboxes)
* Adding french translation
* Code optimizations
* 4.6.x check
* Minor bugs corrections

= 0.1.1 =
* Optimize on upload
* Statistics 
* Log services
* Interface rebuild
* News feed from feed.resmush.it

= 0.1.0 =
* plugin base
* bulk optimizer
